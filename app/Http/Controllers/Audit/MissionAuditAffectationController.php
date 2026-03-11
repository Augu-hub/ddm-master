<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Session};
use Inertia\Inertia;

/**
 * MissionAuditAffectationController
 *
 * Structure réelle fruitiva (vérifiée) :
 *
 * mission_programmation : id, code_mission, libelle, numero_fpm, objectif,
 *   projet_id, mission_id (nullable FK→missions), lieux, date_debut, date_fin,
 *   duree (generated), programme, status, created_by, updated_by
 *   ⚠️  PAS de deleted_at — ne jamais faire ->whereNull('mp.deleted_at')
 *
 * missions : id, code, mission_type_id, title, objective, status ...
 *
 * mission_types : id, code, label, audit_type_code, audit_type_label,
 *   audit_color, audit_icon, is_active, sort_order
 *
 * Résolution audit_type_code :
 *   mp.mission_id → missions.mission_type_id → mission_types.audit_type_code
 *   Passe 1 : mission_types.audit_type_code si IN (AC|AF|AP|AM|RP|ES)
 *   Passe 2 : mission_types.code si lui-même IN (AC|AF|AP|AM|RP|ES)
 */
class MissionAuditAffectationController extends Controller
{
    private const VALID_CODES = ['AC', 'AF', 'AP', 'AM', 'RP', 'ES'];

    // ═══════════════════════════════════════════════════════════
    // PAGE PRINCIPALE
    // ═══════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        try {
            $auditTypesMeta = $this->getAuditTypesMeta();
            Log::info('[AuditAffectation] ── index START ──'
                . ' | ddmparam.audit_types: ' . $auditTypesMeta->count()
                . ' → [' . $auditTypesMeta->keys()->implode(', ') . ']'
                . ' | mission_id_param=' . ($request->query('mission_id') ?? 'none'));

            $rawMissions = $this->loadRawMissions();
            Log::info('[AuditAffectation] missions chargées: ' . $rawMissions->count());

            foreach ($rawMissions as $rm) {
                Log::info('[AuditAffectation]   [' . $rm->code_mission . '] '
                    . match(true) {
                        !$rm->mission_id      => '❌ mission_id=NULL',
                        !$rm->mission_type_id => '❌ mission_type_id=NULL (mission_id=' . $rm->mission_id . ')',
                        !$rm->mt_id           => '❌ mt_id=NULL (type_id=' . $rm->mission_type_id . ')',
                        default               => '✅ mt_id=' . $rm->mt_id
                                              . ' type_code=' . ($rm->type_code ?? 'NULL')
                                              . ' raw_atc=[' . ($rm->raw_atc) . ']',
                    }
                );
            }

            $entities    = $this->loadEntities($rawMissions->pluck('id')->toArray());
            $allMissions = $rawMissions->map(function ($r) use ($entities, $auditTypesMeta) {
                $r->entities_list = $entities[$r->id] ?? '';
                return $this->buildPayload($r, $auditTypesMeta);
            })->values();

            $ok   = $allMissions->filter(fn($m) => !empty($m['audit_type_code']))->count();
            $null = $allMissions->filter(fn($m) =>  empty($m['audit_type_code']))->count();
            Log::info("[AuditAffectation] résolution: {$ok} OK / {$null} NULL");

            $missionId    = $request->query('mission_id');
            $mission      = null;
            $auditForms   = [];
            $affectations = [];
            $auditeurs    = [];

            if ($missionId) {
                $mission = $this->getMissionData((int) $missionId, $auditTypesMeta);

                if (!$mission) {
                    Log::error("[AuditAffectation] ❌ mission_prog #{$missionId} introuvable");
                } elseif (!$mission->audit_type_code) {
                    Log::error('[AuditAffectation] ❌ [' . $mission->code_mission . '] audit_type=NULL');
                } else {
                    Log::info('[AuditAffectation] ✅ [' . $mission->code_mission . ']'
                        . ' audit_type=' . $mission->audit_type_code);

                    $auditForms   = $this->getFormsByCodeData($mission->audit_type_code);
                    $affectations = $this->getAffectationsData((int) $missionId);
                    $auditeurs    = $this->getAuditeursMissionData((int) $missionId);

                    Log::info('[AuditAffectation] '
                        . count($auditForms)   . ' phases | '
                        . count($affectations) . ' affectations | '
                        . count($auditeurs)    . ' entités auditeurs');
                }
            }

            Log::info('[AuditAffectation] ── index END ──');

            return Inertia::render('dashboards/Audit/Mission/Affectation/AuditForms', [
                'allMissions'  => $allMissions,
                'mission'      => $mission,
                'auditForms'   => $auditForms,
                'affectations' => $affectations,
                'auditeurs'    => $auditeurs,
                'sessionData'  => [
                    'tenant_id'       => Session::get('tenant_id'),
                    'is_auditor'      => Session::get('is_auditor'),
                    'auditor_id'      => Session::get('auditor_id'),
                    'auditor_code'    => Session::get('auditor_code'),
                    'is_global_admin' => Session::get('is_global_admin'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[AuditAffectation] ❌ FATAL index: ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    // API : formulaires ddmparam
    // ═══════════════════════════════════════════════════════════

    public function getFormsByCode(string $code)
    {
        try {
            $code = strtoupper(trim($code));
            if (!in_array($code, self::VALID_CODES)) {
                return response()->json(['success' => false,
                    'message' => "Code invalide [{$code}]. Valeurs: " . implode(', ', self::VALID_CODES)], 422);
            }
            $data = $this->getFormsByCodeData($code);
            if (empty($data)) {
                return response()->json(['success' => false,
                    'message' => "Aucun formulaire actif pour [{$code}] dans ddmparam"], 404);
            }
            $total = array_sum(array_map(fn($p) => count($p['forms']), $data));
            Log::info("[AuditAffectation] getFormsByCode [{$code}]: " . count($data) . " phases, {$total} formulaires");
            return response()->json(['success' => true, 'audit_type_code' => $code, 'phases' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // API : affectations existantes
    // ═══════════════════════════════════════════════════════════

    public function getAffectations(int $missionId)
    {
        try {
            $data = $this->getAffectationsData($missionId);
            return response()->json(['success' => true, 'affectations' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // API : sauvegarder
    // ═══════════════════════════════════════════════════════════

    public function saveAffectations(Request $request, int $missionId)
    {
        try {
            Log::info("[AuditAffectation] ── saveAffectations START #{$missionId} ──"
                . ' | items: ' . count($request->input('affectations', [])));

            $validated = $request->validate([
                'affectations'                          => 'required|array',
                'affectations.*.audit_form_id'          => 'required|integer',
                'affectations.*.checked'                => 'required|boolean',
                'affectations.*.status'                 => 'nullable|in:pending,in_progress,completed,skipped,blocked',
                'affectations.*.planned_start'          => 'nullable|date',
                'affectations.*.planned_end'            => 'nullable|date',
                'affectations.*.is_mandatory'           => 'nullable|boolean',
                'affectations.*.notes'                  => 'nullable|string|max:2000',
                'affectations.*.auditeurs'              => 'nullable|array',
                'affectations.*.auditeurs.*.auditor_id' => 'required|integer|exists:auditors,id',
                'affectations.*.auditeurs.*.role_code'  => 'nullable|string|max:20',
                'affectations.*.auditeurs.*.role_label' => 'nullable|string|max:100',
                'affectations.*.auditeurs.*.is_lead'    => 'nullable|boolean',
            ]);

            $auditTypesMeta = $this->getAuditTypesMeta();
            $mission        = $this->getMissionData($missionId, $auditTypesMeta);

            if (!$mission) {
                return response()->json(['error' => "Mission #{$missionId} introuvable"], 404);
            }

            $auditTypeCode = $mission->audit_type_code;

            if (!$auditTypeCode) {
                Log::error('[AuditAffectation] ❌ saveAffectations: audit_type_code=NULL'
                    . ' | code_mission='    . $mission->code_mission
                    . ' | cause: '          . ($mission->_debug_cause ?? 'inconnue'));
                return response()->json([
                    'error' => "Type d'audit non résolu pour [{$mission->code_mission}].",
                    'debug' => [
                        'mission_prog_id' => $missionId,
                        'code_mission'    => $mission->code_mission,
                        'mission_id'      => $mission->mission_id      ?? null,
                        'mission_type_id' => $mission->mission_type_id ?? null,
                        'type_code'       => $mission->type_code       ?? null,
                        'cause'           => $mission->_debug_cause    ?? 'inconnue',
                    ],
                ], 422);
            }

            $formsMeta = DB::table('ddmparam.audit_type_forms as f')
                ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
                ->where('at.code', $auditTypeCode)
                ->where('f.is_active', 1)
                ->get(['f.id','f.code','f.label','f.url_path','f.icon','f.phase_num','f.phase_label'])
                ->keyBy('id');

            Log::info("[AuditAffectation] saveAffectations [{$auditTypeCode}]:"
                . ' ' . $formsMeta->count() . ' formulaires dispo'
                . ' | ids: [' . $formsMeta->keys()->implode(', ') . ']');

            if ($formsMeta->isEmpty()) {
                return response()->json([
                    'error' => "Aucun formulaire actif dans ddmparam pour [{$auditTypeCode}]"
                ], 422);
            }

            $userId = Auth::id();

            DB::beginTransaction();
            $upserted = 0; $deleted = 0; $errors = [];

            foreach ($validated['affectations'] as $item) {
                $formId      = (int)  $item['audit_form_id'];
                $checked     = (bool) $item['checked'];
                $isMandatory = (bool) ($item['is_mandatory'] ?? false);

                if ($checked || $isMandatory) {
                    $meta = $formsMeta[$formId] ?? null;
                    if (!$meta) {
                        $msg = "Formulaire #{$formId} absent de ddmparam [{$auditTypeCode}]";
                        Log::warning("[AuditAffectation] ⚠️  {$msg}");
                        $errors[] = $msg; continue;
                    }

                    $existing = DB::table('mission_audit_affectations')
                        ->where('mission_programmation_id', $missionId)
                        ->where('audit_form_id', $formId)
                        ->whereNull('deleted_at')->first();

                    $affData = [
                        'status'        => $item['status']        ?? 'pending',
                        'planned_start' => $item['planned_start'] ?? null,
                        'planned_end'   => $item['planned_end']   ?? null,
                        'is_mandatory'  => $isMandatory ? 1 : 0,
                        'notes'         => $item['notes']         ?? null,
                        'updated_at'    => now(),
                    ];

                    if ($existing) {
                        DB::table('mission_audit_affectations')
                            ->where('id', $existing->id)->update($affData);
                        $affId = $existing->id;
                    } else {
                        // ✅ INSERT sans tenant_id / created_by_auditor_* (colonnes absentes en base)
                        $affId = DB::table('mission_audit_affectations')->insertGetId(array_merge($affData, [
                            'mission_programmation_id' => $missionId,
                            'mission_type_id'          => $mission->mission_type_id,
                            'audit_type_code'          => $auditTypeCode,
                            'audit_form_id'            => $formId,
                            'form_code'                => $meta->code,
                            'form_label'               => $meta->label,
                            'form_url_path'            => $meta->url_path,
                            'form_icon'                => $meta->icon,
                            'phase_num'                => $meta->phase_num,
                            'phase_label'              => $meta->phase_label,
                            'created_by_user_id'       => $userId,
                            'created_at'               => now(),
                        ]));
                    }

                    $this->syncAuditeursForAff($affId, $item['auditeurs'] ?? [], $userId);
                    $upserted++;

                } else {
                    $nb = DB::table('mission_audit_affectations')
                        ->where('mission_programmation_id', $missionId)
                        ->where('audit_form_id', $formId)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);
                    $deleted += $nb;
                }
            }

            DB::commit();
            Log::info("[AuditAffectation] ✅ COMMIT #{$missionId} [{$auditTypeCode}]"
                . " upserted={$upserted} deleted={$deleted} errors=" . count($errors));

            return response()->json([
                'success'         => true,
                'upserted'        => $upserted,
                'deleted'         => $deleted,
                'errors'          => $errors,
                'audit_type_code' => $auditTypeCode,
                'message'         => "{$upserted} formulaire(s) affecté(s) [{$auditTypeCode}], {$deleted} retiré(s)"
                    . (count($errors) ? ' — ' . count($errors) . ' erreur(s)' : ''),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[AuditAffectation] ❌ validation: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[AuditAffectation] ❌ FATAL saveAffectations: ' . $e->getMessage()
                . " | #{$missionId} | " . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // API : supprimer
    // ═══════════════════════════════════════════════════════════

    public function deleteAffectation(int $id)
    {
        try {
            $aff = DB::table('mission_audit_affectations')->where('id', $id)->whereNull('deleted_at')->first();
            if (!$aff) return response()->json(['error' => 'Affectation introuvable'], 404);
            DB::table('mission_audit_affectations')->where('id', $id)->update(['deleted_at' => now()]);
            Log::info("[AuditAffectation] deleteAffectation #{$id}");
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("[AuditAffectation] ❌ deleteAffectation #{$id}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // API : sync auditeurs
    // ═══════════════════════════════════════════════════════════

    public function syncAuditeurs(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'auditeurs'                 => 'required|array',
                'auditeurs.*.auditor_id'    => 'required|integer|exists:auditors,id',
                'auditeurs.*.role_code'     => 'nullable|string|max:20',
                'auditeurs.*.role_label'    => 'nullable|string|max:100',
                'auditeurs.*.is_lead'       => 'nullable|boolean',
            ]);
            $aff = DB::table('mission_audit_affectations')->where('id', $id)->whereNull('deleted_at')->first();
            if (!$aff) return response()->json(['error' => 'Affectation introuvable'], 404);
            $this->syncAuditeursForAff($id, $validated['auditeurs'], Auth::id());
            return response()->json(['success' => true, 'auditeurs' => $this->getAuditeursForAff($id)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("[AuditAffectation] ❌ syncAuditeurs #{$id}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // API : auditeurs mission
    // ═══════════════════════════════════════════════════════════

    public function getAuditeursMission(int $missionId)
    {
        try {
            $data = $this->getAuditeursMissionData($missionId);
            return response()->json(['success' => true, 'auditeurs' => $data]);
        } catch (\Exception $e) {
            Log::error("[AuditAffectation] ❌ getAuditeursMission #{$missionId}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVÉS
    // ═══════════════════════════════════════════════════════════

    private function loadRawMissions(): \Illuminate\Support\Collection
    {
        return DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->select([
                'mp.id',
                'mp.code_mission',
                'mp.libelle',
                'mp.status',
                'mp.date_debut',
                'mp.date_fin',
                'mp.mission_id',
                'm.mission_type_id',
                'mt.id    as mt_id',
                'mt.code  as type_code',
                'mt.label as type_label',
                DB::raw("COALESCE(mt.audit_type_code,  '') as raw_atc"),
                DB::raw("COALESCE(mt.audit_type_label, '') as raw_atl"),
                DB::raw("COALESCE(mt.audit_color,      '') as raw_color"),
                DB::raw("COALESCE(mt.audit_icon,       '') as raw_icon"),
            ])
            ->orderBy('mp.date_debut', 'desc')
            ->get();
    }

    private function loadEntities(array $mpIds): \Illuminate\Support\Collection
    {
        if (empty($mpIds)) return collect();
        return DB::table('mission_programmation_entity as mpe')
            ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->whereIn('mpe.mission_programmation_id', $mpIds)
            ->select([
                'mpe.mission_programmation_id',
                DB::raw("GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ', ') as entities_list"),
            ])
            ->groupBy('mpe.mission_programmation_id')
            ->pluck('entities_list', 'mission_programmation_id');
    }

    private function getMissionData(int $id, \Illuminate\Support\Collection $meta): ?object
    {
        $r = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->where('mp.id', $id)
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.status',
                'mp.date_debut', 'mp.date_fin', 'mp.mission_id',
                'm.mission_type_id',
                'mt.id    as mt_id',
                'mt.code  as type_code',
                'mt.label as type_label',
                DB::raw("COALESCE(mt.audit_type_code,  '') as raw_atc"),
                DB::raw("COALESCE(mt.audit_type_label, '') as raw_atl"),
                DB::raw("COALESCE(mt.audit_color,      '') as raw_color"),
                DB::raw("COALESCE(mt.audit_icon,       '') as raw_icon"),
            ])
            ->first();

        if (!$r) return null;

        $r->entities_list = DB::table('mission_programmation_entity as mpe')
            ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->where('mpe.mission_programmation_id', $id)
            ->orderBy('e.name')
            ->pluck('e.name')->implode(', ');

        return (object) $this->buildPayload($r, $meta);
    }

    private function buildPayload(object $r, \Illuminate\Support\Collection $meta): array
    {
        $atc = $cause = null;

        if (!$r->mission_id) {
            $cause = 'mission_id=NULL — aucune mission liée';
        } elseif (!$r->mission_type_id) {
            $cause = "mission_type_id=NULL (mission_id={$r->mission_id})";
        } elseif (!$r->mt_id) {
            $cause = "mission_type introuvable (mission_type_id={$r->mission_type_id})";
        } else {
            $db = strtoupper(trim($r->raw_atc ?? ''));
            if (in_array($db, self::VALID_CODES)) {
                $atc = $db;
            } elseif (in_array(strtoupper(trim($r->type_code ?? '')), self::VALID_CODES)) {
                $atc = strtoupper(trim($r->type_code));
            } else {
                $cause = "audit_type_code='{$r->raw_atc}' ET type_code='{$r->type_code}' invalides"
                       . " (mt_id={$r->mt_id}) — corrigez fruitiva.mission_types.audit_type_code";
            }
        }

        $at = $atc ? ($meta[$atc] ?? null) : null;

        return [
            'id'               => $r->id,
            'code_mission'     => $r->code_mission,
            'libelle'          => $r->libelle,
            'status'           => $r->status,
            'date_debut'       => $r->date_debut,
            'date_fin'         => $r->date_fin,
            'mission_id'       => $r->mission_id       ?? null,
            'mission_type_id'  => $r->mission_type_id  ?? null,
            'type_code'        => $r->type_code        ?? null,
            'type_label'       => $r->type_label       ?? null,
            'entities_list'    => $r->entities_list    ?? '',
            'audit_type_code'  => $atc,
            'audit_type_label' => $at?->label  ?? ($r->raw_atl   ?: null),
            'audit_color'      => $at?->color  ?? ($r->raw_color ?: '#94A3B8'),
            'audit_icon'       => $at?->icon   ?? ($r->raw_icon  ?: 'ti ti-help-circle'),
            '_debug_cause'     => $cause,
        ];
    }

    private function getAuditTypesMeta(): \Illuminate\Support\Collection
    {
        try {
            return DB::table('ddmparam.audit_types')
                ->where('is_active', 1)
                ->get(['id','code','label','color','icon','keywords'])
                ->keyBy('code');
        } catch (\Exception $e) {
            Log::error('[AuditAffectation] ❌ ddmparam.audit_types: ' . $e->getMessage());
            return collect();
        }
    }

    private function getFormsByCodeData(string $code): array
    {
        $forms = DB::table('ddmparam.audit_type_forms as f')
            ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
            ->where('at.code', $code)
            ->where('f.is_active', 1)
            ->orderBy('f.phase_num')->orderBy('f.sort_order')
            ->get(['f.id','f.phase_num','f.phase_label','f.parent_id',
                   'f.code','f.label','f.url_path','f.icon','f.sort_order']);

        if ($forms->isEmpty()) return [];

        $phases = [];
        foreach ($forms->groupBy('phase_num') as $num => $pf) {
            $roots = $pf->filter(fn($r) => is_null($r->parent_id))->values();
            $tree  = [];
            foreach ($roots as $r) {
                $r->children = $pf->filter(fn($c) => $c->parent_id == $r->id)->values();
                $tree[] = $r;
            }
            $phases[] = ['phase_num' => (int) $num, 'phase_label' => $pf->first()->phase_label, 'forms' => $tree];
        }
        usort($phases, fn($a, $b) => $a['phase_num'] <=> $b['phase_num']);
        return $phases;
    }

    private function getAffectationsData(int $missionId): array
    {
        $rows = DB::table('mission_audit_affectations')
            ->where('mission_programmation_id', $missionId)
            ->whereNull('deleted_at')->orderBy('phase_num')->get();
        $result = [];
        foreach ($rows as $row) {
            $entry = (array) $row;
            $entry['auditeurs'] = $this->getAuditeursForAff($row->id);
            $result[$row->audit_form_id] = $entry;
        }
        return $result;
    }

    private function getAuditeursForAff(int $affId): array
    {
        return DB::table('mission_audit_affectation_auditeurs as maaa')
            ->join('auditors as a', 'a.id', '=', 'maaa.auditor_id')
            ->where('maaa.affectation_id', $affId)
            ->orderByDesc('maaa.is_lead')->orderBy('a.last_name')
            ->select([
                'maaa.id as pivot_id',
                'a.id    as auditor_id',
                'a.audit_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                'maaa.role_code', 'maaa.role_label', 'maaa.is_lead',
            ])
            ->get()->toArray();
    }

    private function syncAuditeursForAff(int $affId, array $auditeurs, ?int $by): void
    {
        $newIds = array_column($auditeurs, 'auditor_id');
        if (!empty($newIds)) {
            DB::table('mission_audit_affectation_auditeurs')
                ->where('affectation_id', $affId)->whereNotIn('auditor_id', $newIds)->delete();
        } else {
            DB::table('mission_audit_affectation_auditeurs')->where('affectation_id', $affId)->delete();
        }
        foreach ($auditeurs as $aud) {
            $ex = DB::table('mission_audit_affectation_auditeurs')
                ->where('affectation_id', $affId)->where('auditor_id', $aud['auditor_id'])->first();
            $d = [
                'role_code'  => $aud['role_code']  ?? null,
                'role_label' => $aud['role_label'] ?? null,
                'is_lead'    => (int) ($aud['is_lead'] ?? false),
                'assigned_by'=> $by,
            ];
            if ($ex) {
                DB::table('mission_audit_affectation_auditeurs')->where('id', $ex->id)->update($d);
            } else {
                DB::table('mission_audit_affectation_auditeurs')->insert(array_merge($d, [
                    'affectation_id' => $affId,
                    'auditor_id'     => $aud['auditor_id'],
                    'assigned_at'    => now(),
                ]));
            }
        }
    }

    /**
     * Auditeurs disponibles pour la mission, groupés par entity_id.
     *
     * Retourne : { entity_id → [ {auditeur_id, full_name, role_code, role_libelle, role_niveau} ] }
     *
     * ✅ Requête séparée pour mission_roles (table optionnelle)
     * ✅ Filtrage PHP pour JSON entites — pas de CAST(? AS JSON) MariaDB
     * ✅ Fallback : tous les auditeurs actifs si mission_phase_auditeurs vide
     */
    private function getAuditeursMissionData(int $missionId): array
    {
        try {
            $rows = DB::table('mission_phase_auditeurs as mpa')
                ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_id', $missionId)
                ->select([
                    'a.id as auditeur_id',
                    'a.audit_code',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                    'a.email',
                    'mpa.role as role_code',
                    'mpa.role_id',
                    'mpa.entites',
                ])
                ->get();

            if ($rows->isEmpty()) {
                // Aucun auditeur affecté à cette mission — fallback liste complète
                Log::warning("[AuditAffectation] getAuditeursMission #{$missionId}: aucun auditeur dans mission_phase_auditeurs → fallback");
                return DB::table('auditors')
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->select(['id as auditeur_id', 'audit_code',
                        DB::raw("TRIM(CONCAT(COALESCE(last_name,''), ' ', COALESCE(first_name,''))) as full_name"),
                        'email'])
                    ->orderBy('last_name')
                    ->get()->map(fn($a) => (array)$a + ['role_code' => null, 'role_libelle' => null, 'role_niveau' => 99])
                    ->toArray();
            }

            // Charger libellés rôles séparément (table optionnelle)
            $roleLibelles = [];
            try {
                DB::table('mission_roles')
                    ->select(['id', 'code', 'libelle', 'niveau'])
                    ->get()
                    ->each(function ($r) use (&$roleLibelles) {
                        $roleLibelles[$r->id] = ['code' => $r->code, 'libelle' => $r->libelle, 'niveau' => (int)$r->niveau];
                    });
            } catch (\Exception $e) {
                Log::warning("[AuditAffectation] mission_roles indisponible: {$e->getMessage()}");
            }

            // Grouper par entity_id via filtrage PHP (entites JSON)
            $result = [];
            foreach ($rows as $r) {
                $eids    = json_decode($r->entites ?? '[]', true) ?? [];
                $roleInf = $r->role_id ? ($roleLibelles[$r->role_id] ?? null) : null;

                foreach ($eids as $eid) {
                    $eid = (int) $eid;
                    if (!isset($result[$eid])) $result[$eid] = [];

                    if (!collect($result[$eid])->firstWhere('auditeur_id', $r->auditeur_id)) {
                        $result[$eid][] = [
                            'auditeur_id'  => $r->auditeur_id,
                            'audit_code'   => $r->audit_code,
                            'full_name'    => $r->full_name,
                            'email'        => $r->email,
                            'role_code'    => $r->role_code ?? ($roleInf['code']    ?? null),
                            'role_libelle' => $roleInf['libelle'] ?? null,
                            'role_niveau'  => (int) ($roleInf['niveau'] ?? 99),
                        ];
                    }
                }
            }

            // Trier chaque groupe par niveau de rôle
            foreach ($result as &$group) {
                usort($group, fn($a, $b) => $a['role_niveau'] <=> $b['role_niveau']);
            }
            unset($group);

            Log::info("[AuditAffectation] getAuditeursMission #{$missionId}: "
                . count($result) . " entités, "
                . array_sum(array_map('count', $result)) . " auditeurs total");

            return $result;

        } catch (\Exception $e) {
            Log::error("[AuditAffectation] ❌ getAuditeursMission #{$missionId}: {$e->getMessage()}");
            return [];
        }
    }
}