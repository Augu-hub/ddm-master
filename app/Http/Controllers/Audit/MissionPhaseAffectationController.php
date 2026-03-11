<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Session};
use Inertia\Inertia;

class MissionPhaseAffectationController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // INDEX — rendu Inertia principal
    // ══════════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        try {
            $missionId   = $request->query('mission_id');
            $ddmMeta     = $this->loadDdmMeta();
            $allMissions = $this->loadAllMissions($ddmMeta);

            $mission     = null;
            $entities    = [];
            $phases      = [];
            $assignments = [];
            $auditeurs   = [];
            $forms       = [];
            $currentUser = $this->buildCurrentUser();

            if ($missionId) {
                $mission = $this->getMissionData((int) $missionId, $ddmMeta);

                if ($mission) {
                    $entities = $this->getEntities((int) $missionId);

                    if ($mission->mission_type_id) {
                        $phases = $this->getPhasesForType($mission->mission_type_id);
                    }

                    $assignments = $this->getAssignmentsData((int) $missionId);
                    $auditeurs   = $this->getAuditeursParEntite((int) $missionId);
                    $forms       = $this->loadFormsFromSession(
                        (int) ($mission->mission_type_id ?? 0),
                        $mission->audit_type_code ?? null
                    );

                    Log::info("[PhaseAffectation] ✅ [{$mission->code_mission}]"
                        . " entites="     . count($entities)
                        . " phases_grp="  . count($phases)
                        . " assignments=" . count($assignments)
                        . " forms="       . count($forms));
                }
            }

            return Inertia::render('dashboards/Audit/Mission/Phases/Affectation', [
                'allMissions' => $allMissions,
                'mission'     => $mission,
                'entities'    => $entities,
                'phases'      => $phases,
                'assignments' => $assignments,
                'auditeurs'   => $auditeurs,
                'forms'       => $forms,
                'currentUser' => $currentUser,
            ]);

        } catch (\Exception $e) {
            Log::error('[PhaseAffectation] ❌ FATAL index: ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CURRENT USER — rôle dans la mission
    // ══════════════════════════════════════════════════════════════════════════

    private function buildCurrentUser(): array
    {
        $user = Auth::user();
        if (!$user) return ['id' => null, 'name' => '', 'role_code' => ''];

        // Chercher le rôle de l'utilisateur via son auditeur
        $roleCode = '';
        try {
            $auditor = DB::table('auditors')->where('email', $user->email)->value('id');
            if ($auditor) {
                $roleCode = DB::table('mission_phase_auditeurs as mpa')
                    ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
                    ->where('mpa.auditeur_id', $auditor)
                    ->orderByRaw("COALESCE(mr.niveau, 99) ASC")
                    ->value(DB::raw("COALESCE(mr.code, mpa.role, '')")) ?? '';
            }
        } catch (\Exception $e) {
            Log::warning('[PhaseAffectation] buildCurrentUser: ' . $e->getMessage());
        }

        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'role_code' => $roleCode,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // BROADCAST — API POST
    // ══════════════════════════════════════════════════════════════════════════

    public function broadcast(Request $request, int $missionId)
    {
        try {
            $validated = $request->validate([
                'phase_id'    => 'required|integer',
                'entity_id'   => 'nullable|integer',
                'message'     => 'required|string|max:5000',
                'priority'    => 'nullable|in:normal,urgent,bloquant',
                'recipients'  => 'nullable|array',
                'recipients.*'=> 'integer',
            ]);

            // Stocker le message de broadcast dans la config de l'assignment
            $assignment = DB::table('mission_phase_assignments')
                ->where('mission_programmation_id', $missionId)
                ->where('mission_phase_id', $validated['phase_id'])
                ->when($validated['entity_id'] ?? null, fn($q, $eid) => $q->where('entity_id', $eid))
                ->first();

            if ($assignment) {
                $hasCol = $this->columnExists('mission_phase_assignments', 'broadcast_note');
                if ($hasCol) {
                    DB::table('mission_phase_assignments')
                        ->where('id', $assignment->id)
                        ->update(['broadcast_note' => $validated['message'], 'updated_at' => now()]);
                }
            }

            // Enregistrer dans un log de notifications si la table existe
            if ($this->tableExists('mission_phase_notifications')) {
                DB::table('mission_phase_notifications')->insert([
                    'mission_id'    => $missionId,
                    'assignment_id' => $assignment?->id,
                    'phase_id'      => $validated['phase_id'],
                    'entity_id'     => $validated['entity_id'] ?? null,
                    'message'       => $validated['message'],
                    'priority'      => $validated['priority'] ?? 'normal',
                    'sent_by'       => Auth::id(),
                    'recipients'    => json_encode($validated['recipients'] ?? []),
                    'sent_at'       => now(),
                    'created_at'    => now(),
                ]);
            }

            $count = count($validated['recipients'] ?? []);
            Log::info("[PhaseAffectation] broadcast #{$missionId} phase={$validated['phase_id']} recipients={$count}");

            return response()->json([
                'success' => true,
                'message' => "Note envoyée à {$count} auditeur(s).",
                'count'   => $count,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[PhaseAffectation] ❌ broadcast: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API POST — saveAffectation
    // ══════════════════════════════════════════════════════════════════════════

    public function saveAffectation(Request $request, int $missionId)
    {
        try {
            $validated = $request->validate([
                'assignments'                   => 'required|array',
                'assignments.*.phase_id'        => 'required|integer',
                'assignments.*.entity_id'       => 'required|integer',
                'assignments.*.checked'         => 'required|boolean',
                'assignments.*.status'          => 'nullable|in:pending,in_progress,completed,skipped',
                'assignments.*.planned_start'   => 'nullable|date',
                'assignments.*.planned_end'     => 'nullable|date',
                'assignments.*.notes'           => 'nullable|string|max:2000',
                'assignments.*.auditeur_ids'    => 'nullable|array',
                'assignments.*.auditeur_ids.*'  => 'integer',
                'assignments.*.form_url'        => 'nullable|string|max:1000',
                'assignments.*.is_disabled'     => 'nullable|boolean',
                'assignments.*.broadcast_note'  => 'nullable|string|max:5000',
            ]);

            $mission = DB::table('mission_programmation')->where('id', $missionId)->first();
            if (!$mission) return response()->json(['error' => 'Mission introuvable'], 404);

            $validEntityIds = DB::table('mission_programmation_entity')
                ->where('mission_programmation_id', $missionId)
                ->pluck('entity_id')->toArray();

            // Vérifier colonnes optionnelles une seule fois
            $hasFormUrl      = $this->columnExists('mission_phase_assignments', 'form_url');
            $hasIsDisabled   = $this->columnExists('mission_phase_assignments', 'is_disabled');
            $hasBroadcast    = $this->columnExists('mission_phase_assignments', 'broadcast_note');

            DB::beginTransaction();
            $upserted = 0;
            $deleted  = 0;

            foreach ($validated['assignments'] as $item) {
                $phaseId  = (int) $item['phase_id'];
                $entityId = (int) $item['entity_id'];
                $checked  = (bool) $item['checked'];

                if (!in_array($entityId, $validEntityIds)) continue;

                $isMandatory = (bool) DB::table('mission_phases')
                    ->where('id', $phaseId)->value('is_mandatory');

                if ($checked || $isMandatory) {

                    $existing = DB::table('mission_phase_assignments')
                        ->where('mission_programmation_id', $missionId)
                        ->where('mission_phase_id', $phaseId)
                        ->where('entity_id', $entityId)
                        ->first();

                    $data = [
                        'status'        => $item['status']        ?? 'pending',
                        'planned_start' => $item['planned_start'] ?? null,
                        'planned_end'   => $item['planned_end']   ?? null,
                        'notes'         => $item['notes']         ?? null,
                        'updated_at'    => now(),
                    ];

                    if ($hasFormUrl && array_key_exists('form_url', $item)) {
                        $data['form_url'] = $item['form_url'] ?: null;
                    }
                    if ($hasIsDisabled && array_key_exists('is_disabled', $item)) {
                        $data['is_disabled'] = (bool) ($item['is_disabled'] ?? false);
                    }
                    if ($hasBroadcast && array_key_exists('broadcast_note', $item)) {
                        $data['broadcast_note'] = $item['broadcast_note'] ?: null;
                    }

                    if ($existing) {
                        DB::table('mission_phase_assignments')
                            ->where('id', $existing->id)->update($data);
                        $assignmentId = $existing->id;
                    } else {
                        try {
                            $assignmentId = DB::table('mission_phase_assignments')->insertGetId(
                                array_merge($data, [
                                    'mission_programmation_id' => $missionId,
                                    'mission_phase_id'         => $phaseId,
                                    'entity_id'                => $entityId,
                                    'created_by'               => Auth::id(),
                                    'created_at'               => now(),
                                ])
                            );
                        } catch (\Exception $dup) {
                            $rec = DB::table('mission_phase_assignments')
                                ->where('mission_programmation_id', $missionId)
                                ->where('mission_phase_id', $phaseId)
                                ->where('entity_id', $entityId)->first();
                            if (!$rec) throw $dup;
                            DB::table('mission_phase_assignments')
                                ->where('id', $rec->id)->update($data);
                            $assignmentId = $rec->id;
                        }
                    }

                    if (isset($item['auditeur_ids'])) {
                        $this->syncAssignmentAuditeurs(
                            $assignmentId,
                            $item['auditeur_ids'],
                            $missionId,
                            $entityId
                        );
                    }

                    $upserted++;

                } else {
                    $del = DB::table('mission_phase_assignments')
                        ->where('mission_programmation_id', $missionId)
                        ->where('mission_phase_id', $phaseId)
                        ->where('entity_id', $entityId)->delete();
                    $deleted += $del;
                }
            }

            DB::commit();
            Log::info("[PhaseAffectation] ✅ COMMIT #{$missionId} upserted={$upserted} deleted={$deleted}");

            return response()->json([
                'success'  => true,
                'upserted' => $upserted,
                'deleted'  => $deleted,
                'message'  => "{$upserted} affectation(s) sauvegardée(s), {$deleted} supprimée(s)",
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PhaseAffectation] ❌ saveAffectation: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API GET — getAssignedPhases
    // ══════════════════════════════════════════════════════════════════════════

    public function getAssignedPhases(int $missionId)
    {
        try {
            return response()->json([
                'success'     => true,
                'assignments' => $this->getAssignmentsData($missionId),
                'auditeurs'   => $this->getAuditeursParEntite($missionId),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API GET — getPhasesByTypeApi
    // ══════════════════════════════════════════════════════════════════════════

    public function getPhasesByTypeApi(int $typeId)
    {
        try {
            $type = DB::table('mission_types')->where('id', $typeId)->first();
            if (!$type) return response()->json(['error' => 'Type introuvable'], 404);
            return response()->json([
                'success' => true,
                'type'    => $type,
                'phases'  => $this->getPhasesForType($typeId),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // FORMS DEPUIS SESSION user_menus
    // ══════════════════════════════════════════════════════════════════════════

    private function loadFormsFromSession(int $missionTypeId, ?string $auditTypeCode): array
    {
        $userMenus = Session::get('user_menus', []);

        if (!empty($userMenus) && $missionTypeId > 0) {
            foreach ($userMenus as $menuItem) {
                $mt   = $menuItem['mission_type'] ?? null;
                $mtId = (int) ($mt['id'] ?? 0);
                if (!$mt || $mtId !== $missionTypeId) continue;
                $flat = $this->flattenSessionForms($menuItem['phases'] ?? []);
                Log::info("[PhaseAffectation] forms depuis session [{$mt['code']}]: " . count($flat));
                return $flat;
            }
        }

        if ($auditTypeCode) {
            return $this->loadFormsFromDdmparam($auditTypeCode);
        }

        return [];
    }

    private function flattenSessionForms(array $phases): array
    {
        $result = [];
        usort($phases, fn($a, $b) => (int) ($a['phase_num'] ?? 0) <=> (int) ($b['phase_num'] ?? 0));

        foreach ($phases as $phase) {
            $phaseNum   = (int) ($phase['phase_num']   ?? 0);
            $phaseLabel = (string) ($phase['phase_label'] ?? '');
            $forms      = $phase['forms'] ?? [];
            usort($forms, fn($a, $b) => (int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0));

            foreach ($forms as $form) {
                $code = $form['code'] ?? null;
                if (!$code) continue;

                $result[$code] = [
                    'code'        => $code,
                    'label'       => $form['label']     ?? $code,
                    'url_path'    => $form['url_path']  ?? null,
                    'icon'        => $form['icon']      ?? 'ti ti-file-description',
                    'phase_num'   => $phaseNum,
                    'phase_label' => $phaseLabel,
                    'parent_code' => null,
                    'sort_order'  => (int) ($form['sort_order'] ?? 0),
                    'level'       => 1,
                ];

                $children = $form['children'] ?? [];
                usort($children, fn($a, $b) => (int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0));

                foreach ($children as $child) {
                    $cCode = $child['code'] ?? null;
                    if (!$cCode) continue;
                    $result[$cCode] = [
                        'code'        => $cCode,
                        'label'       => $child['label']    ?? $cCode,
                        'url_path'    => $child['url_path'] ?? null,
                        'icon'        => $child['icon']     ?? 'ti ti-file-description',
                        'phase_num'   => $phaseNum,
                        'phase_label' => $phaseLabel,
                        'parent_code' => $code,
                        'sort_order'  => (int) ($child['sort_order'] ?? 0),
                        'level'       => 2,
                    ];
                }
            }
        }

        return $result;
    }

    private function loadFormsFromDdmparam(string $auditTypeCode): array
    {
        try {
            $rows = DB::table('ddmparam.audit_type_forms as f')
                ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
                ->where('at.code', strtoupper($auditTypeCode))
                ->where('f.is_active', 1)
                ->orderBy('f.phase_num')
                ->orderBy('f.sort_order')
                ->get(['f.id', 'f.phase_num', 'f.phase_label', 'f.parent_id',
                       'f.code', 'f.label', 'f.url_path', 'f.route_name', 'f.icon', 'f.sort_order']);

            $byId   = [];
            foreach ($rows as $r) $byId[$r->id] = $r->code;

            $result = [];
            foreach ($rows as $r) {
                $result[$r->code] = [
                    'code'        => $r->code,
                    'label'       => $r->label,
                    'url_path'    => $r->url_path,
                    'route_name'  => $r->route_name ?? null,
                    'icon'        => $r->icon,
                    'phase_num'   => (int) $r->phase_num,
                    'phase_label' => $r->phase_label,
                    'parent_code' => $r->parent_id ? ($byId[$r->parent_id] ?? null) : null,
                    'sort_order'  => (int) $r->sort_order,
                    'level'       => $r->parent_id ? 2 : 1,
                ];
            }
            return $result;

        } catch (\Exception $e) {
            Log::warning("[PhaseAffectation] loadFormsFromDdmparam [{$auditTypeCode}]: " . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉES — chargement données
    // ══════════════════════════════════════════════════════════════════════════

    private function columnExists(string $table, string $column): bool
    {
        try {
            return in_array($column, DB::getSchemaBuilder()->getColumnListing($table));
        } catch (\Exception $e) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function loadDdmMeta(): \Illuminate\Support\Collection
    {
        try {
            return DB::table('ddmparam.audit_types')
                ->where('is_active', 1)
                ->get(['code', 'label', 'color', 'icon'])
                ->keyBy('code');
        } catch (\Exception $e) {
            Log::warning('[PhaseAffectation] ddmparam indisponible: ' . $e->getMessage());
            return collect();
        }
    }

    private function loadAllMissions(\Illuminate\Support\Collection $ddmMeta): \Illuminate\Support\Collection
    {
        $raw = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.status',
                'mp.date_debut', 'mp.date_fin',
                'mt.id    as mission_type_id',
                'mt.code  as type_code',
                'mt.label as type_label',
                'mt.audit_type_code',
            ])
            ->orderBy('mp.date_debut', 'desc')
            ->get();

        $mpIds   = $raw->pluck('id')->toArray();
        $entsMap = empty($mpIds) ? collect() :
            DB::table('mission_programmation_entity as mpe')
                ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
                ->whereIn('mpe.mission_programmation_id', $mpIds)
                ->select([
                    'mpe.mission_programmation_id',
                    DB::raw("GROUP_CONCAT(DISTINCT COALESCE(e.name,'') ORDER BY e.name SEPARATOR ', ') as entities_list"),
                    DB::raw("COUNT(DISTINCT mpe.entity_id) as entity_count"),
                ])
                ->groupBy('mpe.mission_programmation_id')
                ->get()->keyBy('mission_programmation_id');

        return $raw->map(function ($r) use ($ddmMeta, $entsMap) {
            $tc = $r->audit_type_code ?? $r->type_code ?? null;
            $at = $tc ? ($ddmMeta[$tc] ?? null) : null;
            $em = $entsMap[$r->id] ?? null;
            return [
                'id'               => $r->id,
                'code_mission'     => $r->code_mission,
                'libelle'          => $r->libelle,
                'status'           => $r->status,
                'date_debut'       => $r->date_debut,
                'date_fin'         => $r->date_fin,
                'mission_type_id'  => $r->mission_type_id,
                'type_code'        => $tc,
                'type_label'       => $r->type_label ?? null,
                'audit_type_code'  => $tc,
                'audit_type_label' => $at?->label ?? ($r->type_label ?? null),
                'audit_color'      => $at?->color  ?? '#64748B',
                'audit_icon'       => $at?->icon   ?? 'ti ti-clipboard',
                'entities_list'    => $em?->entities_list ?? '',
                'entity_count'     => (int) ($em?->entity_count ?? 0),
            ];
        })->values();
    }

    private function getMissionData(int $id, \Illuminate\Support\Collection $ddmMeta): ?object
    {
        $r = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->where('mp.id', $id)
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.status',
                'mp.date_debut', 'mp.date_fin',
                'mt.id    as mission_type_id',
                'mt.code  as type_code',
                'mt.label as type_label',
                'mt.audit_type_code',
            ])->first();

        if (!$r) return null;

        $tc = $r->audit_type_code ?? $r->type_code ?? null;
        $at = $tc ? ($ddmMeta[$tc] ?? null) : null;
        $r->audit_type_code  = $tc;
        $r->audit_type_label = $at?->label ?? ($r->type_label ?? null);
        $r->audit_color      = $at?->color ?? '#64748B';
        $r->audit_icon       = $at?->icon  ?? 'ti ti-clipboard';
        return $r;
    }

    private function getEntities(int $missionId): array
    {
        $entities = DB::table('mission_programmation_entity as mpe')
            ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->where('mpe.mission_programmation_id', $missionId)
            ->select(['e.id', 'e.name', 'mpe.date_debut', 'mpe.date_fin'])
            ->orderByRaw('ISNULL(mpe.date_debut), mpe.date_debut ASC')
            ->get();

        if ($entities->isEmpty()) return [];

        $audRows = DB::table('mission_phase_auditeurs as mpa')
            ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
            ->where('mpa.mission_id', $missionId)
            ->select([
                'a.id as auditeur_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"),
                'mpa.role as role_code',
                'mpa.role_id',
                'mpa.entites',
                'mpa.parent_auditeur_id',
            ])->get();

        $roleLibelles = $this->loadRoleLibelles();
        $audByEntity  = [];

        foreach ($audRows as $aud) {
            $eids    = json_decode($aud->entites ?? '[]', true) ?? [];
            $roleInf = $aud->role_id ? ($roleLibelles[$aud->role_id] ?? null) : null;
            foreach ($eids as $eid) {
                $eid = (int) $eid;
                if (!isset($audByEntity[$eid])) $audByEntity[$eid] = [];
                if (!collect($audByEntity[$eid])->firstWhere('auditeur_id', $aud->auditeur_id)) {
                    $audByEntity[$eid][] = [
                        'auditeur_id'        => $aud->auditeur_id,
                        'full_name'          => $aud->full_name,
                        'role_code'          => $aud->role_code,
                        'role_id'            => $aud->role_id,
                        'role_libelle'       => $roleInf['libelle'] ?? null,
                        'role_niveau'        => (int) ($roleInf['niveau'] ?? 99),
                        'parent_auditeur_id' => $aud->parent_auditeur_id,
                    ];
                }
            }
        }
        foreach ($audByEntity as &$grp) {
            usort($grp, fn($a, $b) => $a['role_niveau'] <=> $b['role_niveau']);
        }
        unset($grp);

        return $entities->map(fn($e) => [
            'id'         => $e->id,
            'name'       => $e->name,
            'date_debut' => $e->date_debut,
            'date_fin'   => $e->date_fin,
            'auditeurs'  => $audByEntity[$e->id] ?? [],
        ])->toArray();
    }

    private function getAuditeursParEntite(int $missionId): array
    {
        try {
            $rows = DB::table('mission_phase_auditeurs as mpa')
                ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_id', $missionId)
                ->select([
                    'a.id as auditeur_id',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"),
                    'mpa.role as role_code',
                    'mpa.role_id',
                    'mpa.entites',
                ])->get();

            $roleLibelles = $this->loadRoleLibelles();
            $result       = [];

            foreach ($rows as $r) {
                $eids    = json_decode($r->entites ?? '[]', true) ?? [];
                $roleInf = $r->role_id ? ($roleLibelles[$r->role_id] ?? null) : null;
                foreach ($eids as $eid) {
                    $eid = (int) $eid;
                    if (!isset($result[$eid])) $result[$eid] = [];
                    if (!collect($result[$eid])->firstWhere('auditeur_id', $r->auditeur_id)) {
                        $result[$eid][] = [
                            'auditeur_id'  => $r->auditeur_id,
                            'full_name'    => $r->full_name,
                            'role_code'    => $r->role_code,
                            'role_id'      => $r->role_id,
                            'role_libelle' => $roleInf['libelle'] ?? null,
                            'role_niveau'  => (int) ($roleInf['niveau'] ?? 99),
                        ];
                    }
                }
            }
            foreach ($result as &$grp) {
                usort($grp, fn($a, $b) => $a['role_niveau'] <=> $b['role_niveau']);
            }
            unset($grp);
            return $result;

        } catch (\Exception $e) {
            Log::warning("[PhaseAffectation] getAuditeursParEntite #{$missionId}: " . $e->getMessage());
            return [];
        }
    }

    private function loadRoleLibelles(): array
    {
        try {
            $result = [];
            DB::table('mission_roles')->select(['id', 'libelle', 'niveau'])->get()
                ->each(fn($r) => $result[$r->id] = ['libelle' => $r->libelle, 'niveau' => (int) $r->niveau]);
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAssignmentsData(int $missionId): array
    {
        try {
            // Colonnes optionnelles
            $hasFormUrl    = $this->columnExists('mission_phase_assignments', 'form_url');
            $hasIsDisabled = $this->columnExists('mission_phase_assignments', 'is_disabled');
            $hasBroadcast  = $this->columnExists('mission_phase_assignments', 'broadcast_note');

            $select = [
                'mpa.id',
                'mpa.mission_phase_id',
                'mpa.entity_id',
                'mpa.status',
                'mpa.planned_start',
                'mpa.planned_end',
                'mpa.notes',
                'mpaa.auditeur_id as aud_id',
                'mpaa.role_code   as aud_role',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as aud_name"),
            ];

            if ($hasFormUrl)    $select[] = 'mpa.form_url';
            if ($hasIsDisabled) $select[] = 'mpa.is_disabled';
            if ($hasBroadcast)  $select[] = 'mpa.broadcast_note';

            $rows = DB::table('mission_phase_assignments as mpa')
                ->leftJoin('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
                ->leftJoin('auditors as a', 'mpaa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_programmation_id', $missionId)
                ->select($select)
                ->get();

        } catch (\Exception $ex) {
            // Fallback sans colonnes optionnelles
            Log::warning("[PhaseAffectation] getAssignmentsData fallback: " . $ex->getMessage());
            $rows = DB::table('mission_phase_assignments as mpa')
                ->leftJoin('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
                ->leftJoin('auditors as a', 'mpaa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_programmation_id', $missionId)
                ->select([
                    'mpa.id', 'mpa.mission_phase_id', 'mpa.entity_id',
                    'mpa.status', 'mpa.planned_start', 'mpa.planned_end', 'mpa.notes',
                    'mpaa.auditeur_id as aud_id', 'mpaa.role_code as aud_role',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as aud_name"),
                ])->get();
        }

        $result = [];
        foreach ($rows as $r) {
            $key = "{$r->mission_phase_id}_{$r->entity_id}";
            if (!isset($result[$key])) {
                $result[$key] = [
                    'id'             => $r->id,
                    'phase_id'       => $r->mission_phase_id,
                    'entity_id'      => $r->entity_id,
                    'status'         => $r->status,
                    'planned_start'  => $r->planned_start,
                    'planned_end'    => $r->planned_end,
                    'notes'          => $r->notes,
                    'form_url'       => $r->form_url       ?? null,
                    'is_disabled'    => (bool) ($r->is_disabled ?? false),
                    'broadcast_note' => $r->broadcast_note ?? null,
                    'auditeur_ids'   => [],
                    'auditeurs'      => [],
                ];
            }
            if ($r->aud_id) {
                $result[$key]['auditeur_ids'][] = $r->aud_id;
                $result[$key]['auditeurs'][]    = [
                    'auditeur_id' => $r->aud_id,
                    'full_name'   => $r->aud_name,
                    'role_code'   => $r->aud_role,
                ];
            }
        }
        return $result;
    }

    private function syncAssignmentAuditeurs(
        int $assignmentId,
        array $auditeurIds,
        int $missionId,
        int $entityId
    ): void {
        $audRows = DB::table('mission_phase_auditeurs as mpa')
            ->where('mpa.mission_id', $missionId)
            ->whereIn('mpa.auditeur_id', $auditeurIds)
            ->select(['mpa.auditeur_id', 'mpa.role', 'mpa.role_id', 'mpa.parent_auditeur_id', 'mpa.entites'])
            ->get();

        $audMeta = $audRows->filter(function ($r) use ($entityId) {
            $eids = json_decode($r->entites ?? '[]', true) ?? [];
            return in_array((int) $entityId, array_map('intval', $eids));
        })->keyBy('auditeur_id');

        DB::table('mission_phase_assignment_auditeurs')
            ->where('assignment_id', $assignmentId)
            ->whereNotIn('auditeur_id', $auditeurIds)
            ->delete();

        foreach ($auditeurIds as $audId) {
            $meta     = $audMeta[$audId] ?? null;
            $existing = DB::table('mission_phase_assignment_auditeurs')
                ->where('assignment_id', $assignmentId)
                ->where('auditeur_id', $audId)->first();

            $data = [
                'role_code'          => $meta?->role               ?? null,
                'role_id'            => $meta?->role_id            ?? null,
                'parent_auditeur_id' => $meta?->parent_auditeur_id ?? null,
                'date_affectation'   => now()->toDateString(),
                'affecte_par'        => Auth::id(),
                'updated_at'         => now(),
            ];

            if ($existing) {
                DB::table('mission_phase_assignment_auditeurs')
                    ->where('id', $existing->id)->update($data);
            } else {
                DB::table('mission_phase_assignment_auditeurs')->insert(
                    array_merge($data, [
                        'assignment_id' => $assignmentId,
                        'auditeur_id'   => $audId,
                        'created_at'    => now(),
                    ])
                );
            }
        }
    }

    private function getPhasesForType(int $typeId): array
    {
        $all = DB::table('mission_phases')
            ->where('mission_type_id', $typeId)
            ->where('status', 'active')
            ->orderByRaw("
                CASE phase_type
                    WHEN 'PREPARATION'  THEN 1
                    WHEN 'VERIFICATION' THEN 2
                    WHEN 'CONCLUSION'   THEN 3
                    WHEN 'SUIVI'        THEN 4
                    ELSE 5
                END, level, code_full
            ")
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'code'         => $p->code,
                'code_full'    => $p->code_full,
                'label'        => $p->label,
                'phase_type'   => $p->phase_type   ?? null,
                'level'        => (int) $p->level,
                'parent_id'    => $p->parent_id    ?? null,
                'is_mandatory' => (bool) ($p->is_mandatory ?? false),
                'form_code'    => $p->form_code    ?? null,
                'children'     => [],
            ])->toArray();

        $byId  = [];
        foreach ($all as &$p) $byId[$p['id']] = &$p;
        unset($p);

        $roots = [];
        foreach ($all as &$p) {
            if ($p['parent_id'] === null)          $roots[] = &$p;
            elseif (isset($byId[$p['parent_id']])) $byId[$p['parent_id']]['children'][] = &$p;
            else                                   $roots[] = &$p;
        }
        unset($p);

        $groups = [];
        foreach ($roots as &$r) {
            $pt = $r['phase_type'] ?? 'AUTRE';
            if (!isset($groups[$pt])) $groups[$pt] = ['phase_type' => $pt, 'phases' => []];
            $groups[$pt]['phases'][] = &$r;
        }
        unset($r);

        $ordered = [];
        foreach (['PREPARATION', 'VERIFICATION', 'CONCLUSION', 'SUIVI'] as $pt) {
            if (isset($groups[$pt])) $ordered[] = $groups[$pt];
        }
        foreach ($groups as $pt => $g) {
            if (!in_array($pt, ['PREPARATION', 'VERIFICATION', 'CONCLUSION', 'SUIVI']))
                $ordered[] = $g;
        }
        return $ordered;
    }
}