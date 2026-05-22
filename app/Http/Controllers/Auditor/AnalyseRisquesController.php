<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Param\Auditor;

/**
 * ANALYSE DES RISQUES — AnalyseRisquesController
 * Hérite de BasePhaseFormController — pattern identique à APT.
 *
 * Détection rôle :
 *   getAuditor()  → Auditor model via session (hérité de BasePhaseFormController)
 *   getRole()     → mission_phase_assignment_auditeurs.role_code → DM/CM/AS/AJ
 *
 * DM/CM : accès complet + affectation processus (colonne process_assignments)
 * AS/AJ : voit tous les risques, édite seulement ses processus affectés (grisage)
 */
class AnalyseRisquesController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ar';
    protected string $formCode    = 'analyse-risques';
    protected string $codePrefix  = 'AR';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseRisques';
    protected string $routeEdit   = 'auditor.ac.analyse-risques.edit';

    protected array $validationRules = [
        'fait_par'            => 'nullable|string|max:255',
        'revue_par'           => 'nullable|string|max:255',
        'synthese'            => 'nullable|string|max:10000',
        'date_analyse'        => 'nullable|date',
        'risques'             => 'nullable|string',
        'process_assignments' => 'nullable|string',
    ];

    protected function formData(Request $request, Auditor $auditor): array
    {
        $data = [
            'fait_par'  => $request->input('fait_par'),
            'revue_par' => $request->input('revue_par'),
            'synthese'  => $request->input('synthese'),
        ];
        $pa = $request->input('process_assignments');
        if ($pa !== null) {
            $decoded = json_decode($pa, true);
            $data['process_assignments'] = is_array($decoded) ? json_encode($decoded) : '{}';
        }
        return $data;
    }

    // ─────────────────────────────────────────────────────────────
    // GET index
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);

            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id',    0));
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));
            if (!$missionId || !$assignmentId) abort(422, 'Contexte mission manquant.');

            $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
            if ($existing) {
                return redirect()->route($this->routeEdit, $existing->id)
                    ->with('mission_id',    $missionId)
                    ->with('assignment_id', $assignmentId);
            }

            return \Inertia\Inertia::render($this->inertiaPage,
                $this->buildPayload($missionId, $assignmentId, $auditor, null)
            );
        } catch (\Exception $e) {
            Log::error('AR index: ' . $e->getMessage()); return back()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET edit
    // ─────────────────────────────────────────────────────────────
    public function edit(Request $request, int $formId)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);

            $form         = DB::connection('tenant')->table($this->table)->where('id', $formId)->firstOrFail();
            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id);

            return \Inertia\Inertia::render($this->inertiaPage,
                $this->buildPayload($missionId, $assignmentId, $auditor, $form)
            );
        } catch (\Exception $e) {
            Log::error('AR edit: ' . $e->getMessage()); return back()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // POST store
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int)$request->input('mission_id',    0);
        $assignmentId = (int)$request->input('assignment_id', 0);
        if (!$missionId || !$assignmentId) return response()->json(['success'=>false,'message'=>'Contexte manquant.'],422);

        $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'       => $assignmentId,
            'mission_id'          => $missionId,
            'code'                => $this->genArCode($missionId),
            'risques'             => $request->input('risques', '[]'),
            'process_assignments' => '{}',
            'validation_status'   => 'draft',
            'created_by'          => $auditor->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        return response()->json(['success'=>true,'form'=>DB::connection('tenant')->table($this->table)->where('id',$id)->first(),'message'=>'AR créé.']);
    }

    // ─────────────────────────────────────────────────────────────
    // PUT update
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        $data = array_merge($this->formData($request, $auditor), ['updated_at'=>now()]);

        // risques JSON
        if ($request->has('risques')) $data['risques'] = $request->input('risques', '[]');

        // Seuls DM/CM modifient les méta
        if (!in_array($role, ['DM','CM'])) unset($data['fait_par'], $data['revue_par']);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update($data);
        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);

        return response()->json(['success'=>true,'form'=>DB::connection('tenant')->table($this->table)->where('id',$formId)->first(),'message'=>'AR mis à jour.']);
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE destroy
    // ─────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) abort(403);
        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first(); if (!$row) abort(404);
        if (!in_array($this->getRole((int)$row->mission_id, $auditor->id), ['DM','CM']))
            return response()->json(['success'=>false,'error'=>'Seuls DM/CM peuvent supprimer.'],403);
        DB::connection('tenant')->table($this->table)->where('id', $formId)->delete();
        return response()->json(['success'=>true]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST assign-process
    // DM/CM affecte les processus aux auditeurs de la phase.
    // Si aucun AR n'existe encore pour cet assignment, on le crée.
    // Log chaque affectation dans mission_phase_ar_assignment_logs.
    // ─────────────────────────────────────────────────────────────
    public function assignProcess(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non authentifié.'], 401);

        $missionId    = (int) $request->input('mission_id',    0);
        $assignmentId = (int) $request->input('assignment_id', 0);
        $arId         = (int) $request->input('ar_id',         0);

        if (!$missionId || !$assignmentId) {
            return response()->json(['error' => 'mission_id et assignment_id requis.'], 422);
        }

        // Vérifier que c'est bien un DM/CM
        $role = $this->getRole($missionId, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent affecter les processus.'], 403);
        }

        // Nettoyer les affectations reçues
        $raw = $request->input('assignments', []);
        $assignments = is_string($raw) ? (json_decode($raw, true) ?? []) : (array) $raw;

        $cleaned = [];
        foreach ($assignments as $code => $audId) {
            $cleaned[(string) $code] = ($audId !== '' && $audId !== null) ? (int) $audId : null;
        }

        // Trouver ou créer l'AR pour cet assignment
        $arRow = $arId
            ? DB::connection('tenant')->table($this->table)->where('id', $arId)->first()
            : DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();

        if (!$arRow) {
            // Créer un AR vide pour pouvoir stocker les affectations
            $newId = DB::connection('tenant')->table($this->table)->insertGetId([
                'mission_id'          => $missionId,
                'assignment_id'       => $assignmentId,
                'code'                => $this->genArCode($missionId),
                'risques'             => '[]',
                'process_assignments' => json_encode($cleaned),
                'validation_status'   => 'draft',
                'created_by'          => $auditor->id,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $arRow = DB::connection('tenant')->table($this->table)->where('id', $newId)->first();
        } else {
            // Mettre à jour les affectations
            DB::connection('tenant')->table($this->table)
                ->where('id', $arRow->id)
                ->update([
                    'process_assignments' => json_encode($cleaned),
                    'updated_at'          => now(),
                ]);
        }

        // Log de l'affectation
        $this->logAssignment($arRow->id, $assignmentId, $missionId, $auditor->id, $role, $cleaned);

        // Recharger le form complet pour la réponse
        $saved = DB::connection('tenant')->table($this->table)->where('id', $arRow->id)->first();

        return response()->json([
            'success'     => true,
            'ar_id'       => $arRow->id,
            'ar_code'     => $saved->code,
            'assignments' => $cleaned,
            'form'        => $saved,
            'message'     => count(array_filter($cleaned)) . ' processus affecté(s) — enregistré avec succès.',
        ]);
    }

    /**
     * Enregistre un log d'affectation dans mission_phase_ar_assignment_logs.
     * Tente de créer la table si elle n'existe pas encore.
     */
    private function logAssignment(int $arId, int $assignmentId, int $missionId, int $auditorId, string $role, array $assignments): void
    {
        try {
            // Créer la table de log si elle n'existe pas
            if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('mission_phase_ar_assignment_logs')) {
                DB::connection('tenant')->statement("
                    CREATE TABLE IF NOT EXISTS `mission_phase_ar_assignment_logs` (
                        `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `ar_id`         BIGINT UNSIGNED NOT NULL,
                        `assignment_id` BIGINT UNSIGNED NOT NULL,
                        `mission_id`    BIGINT UNSIGNED NOT NULL,
                        `auditor_id`    BIGINT UNSIGNED NOT NULL,
                        `role_code`     VARCHAR(5) NOT NULL DEFAULT 'DM',
                        `assignments`   TEXT NOT NULL,
                        `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_ar_id` (`ar_id`),
                        KEY `idx_assignment_id` (`assignment_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }

            DB::connection('tenant')->table('mission_phase_ar_assignment_logs')->insert([
                'ar_id'         => $arId,
                'assignment_id' => $assignmentId,
                'mission_id'    => $missionId,
                'auditor_id'    => $auditorId,
                'role_code'     => $role,
                'assignments'   => json_encode($assignments, JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('[AR] logAssignment: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // POST soumettre / valider
    // ─────────────────────────────────────────────────────────────
    public function soumettre(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) abort(403);
        try {
            DB::connection('tenant')->table($this->table)->where('id',$formId)->update(['validation_status'=>'in_review','submitted_at'=>now(),'submitted_by'=>$auditor->id,'updated_at'=>now()]);
            return response()->json(['success'=>true,'status'=>'in_review']);
        } catch(\Exception $e){ return response()->json(['success'=>false,'error'=>$e->getMessage()],500); }
    }

    public function valider(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) abort(403);
        $row = DB::connection('tenant')->table($this->table)->where('id',$formId)->first(); if (!$row) abort(404);
        if (!in_array($this->getRole((int)$row->mission_id, $auditor->id), ['DM','CM']))
            return response()->json(['error'=>'Seuls DM/CM peuvent valider.'],403);

        $action = $request->input('action','validated');
        $update = ['validation_status'=>$action,'updated_at'=>now()];
        if ($action==='validated') { $update['validated_at']=now(); $update['validated_by']=$auditor->id; }
        if ($request->input('note')) $update['validation_note'] = $request->input('note');
        DB::connection('tenant')->table($this->table)->where('id',$formId)->update($update);
        return response()->json(['success'=>true,'status'=>$action]);
    }

    // ══════════════════════════════════════════════════════════════
    // buildPayload — pattern identique à APT buildPayload()
    // ══════════════════════════════════════════════════════════════
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        // Rôle via la méthode héritée — MÊME logic qu'APT
        $role = $this->getRole($missionId, $auditor->id);

        // Auditeurs de la phase — MÊME query qu'APT
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.audit_code', 'a.last_name', 'a.first_name',
                'mpaa.role_code', 'mpaa.parent_auditeur_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'), COALESCE(LEFT(a.first_name,1),'?'))) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->orderBy('a.last_name')
            ->get()
            ->map(fn($a) => [
                'id'                 => (int)$a->id,
                'audit_code'         => $a->audit_code,
                'last_name'          => $a->last_name,
                'first_name'         => $a->first_name,
                'full_name'          => trim($a->full_name),
                'initials'           => $a->initials,
                'role_code'          => $a->role_code,
                'parent_auditeur_id' => $a->parent_auditeur_id,
                'role_label'         => match ($a->role_code) {
                    'DM'    => 'Directeur de Mission',
                    'CM'    => 'Chef de Mission',
                    'AS'    => 'Auditeur Senior',
                    'AJ'    => 'Auditeur Junior',
                    default => $a->role_code ?? '—',
                },
            ])
            ->toArray();

        // Affectations processus
        $processAssignments = [];
        if ($form?->process_assignments) {
            $decoded = json_decode($form->process_assignments, true);
            if (is_array($decoded)) $processAssignments = $decoded;
        }

        // Risques (3 couches)
        $mission    = DB::table('missions')->where('id',$missionId)->first()
                   ?? DB::connection('tenant')->table('missions')->where('id',$missionId)->first();
        $assignment = DB::connection('tenant')->table('mission_phase_assignments')->where('id',$assignmentId)->first();
        $entityId   = $this->resolveEntityId($mission, $assignment);
        $activeYear = $this->resolveActiveYear($entityId, $mission);

        [$universeRisks, $activeYear] = $this->loadUniverseRisks($entityId, $activeYear);

        $savedRisks = [];
        if ($form?->risques) {
            $dec = json_decode($form->risques, true);
            if (is_array($dec)) $savedRisks = $dec;
        }

        $matrixRaw = DB::connection('tenant')->table('audit_matrix')
            ->select('id','frequency_level','impact_level','qualification')
            ->whereNull('deleted_at')->get();

        $risksData = $this->mergeRisksWithSaved($universeRisks, $savedRisks, $matrixRaw->all());

        $allProcesses    = DB::connection('tenant')->table('processes') ->select('id','code','name')->orderBy('code')->get();
        $allActivities   = DB::connection('tenant')->table('activities')->select('id','process_id','code','name')->orderBy('code')->get();
        $impactLevels    = DB::connection('tenant')->table('risk_impact_levels')   ->select('id','level','label','color')->whereNull('deleted_at')->orderBy('level')->get();
        $frequencyLevels = DB::connection('tenant')->table('risk_frequency_levels')->select('id','level','label','color')->whereNull('deleted_at')->orderBy('level')->get();
        $riskTypes       = DB::connection('tenant')->table('risk_types')->select('id','code','label','color')->where('is_active',true)->whereNull('deleted_at')->orderBy('sort_order')->get();

        $arList = DB::connection('tenant')->table($this->table)
            ->where('mission_id',$missionId)
            ->select('id','code','validation_status','fait_par')
            ->orderByDesc('created_at')->get();

        $formId = $form?->id;

        // parent::buildPayload() ajoute mission, assignment, missionId, assignmentId, backUrl
        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'                   => $form,
                'risksData'              => $risksData,
                'allProcesses'           => $allProcesses,
                'allActivities'          => $allActivities,
                'impactLevels'           => $impactLevels,
                'frequencyLevels'        => $frequencyLevels,
                'riskTypes'              => $riskTypes,
                'matrix'                 => $matrixRaw,
                'phaseAuditeurs'         => $phaseAuditeurs,
                'processAssignmentsData' => $processAssignments,
                'arList'                 => $arList,
                'riskCount'              => count($risksData),
                'activeYear'             => $activeYear,
                'auditorRole'            => $role,
                'currentAuditor'         => [
                    'id'         => (int)$auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                    'full_name'  => trim($auditor->last_name . ' ' . $auditor->first_name),
                ],
                'urlStore'         => route('auditor.ac.analyse-risques.store'),
                'urlUpdate'        => $formId ? route('auditor.ac.analyse-risques.update',    $formId) : null,
                'urlSoumettre'     => $formId ? route('auditor.ac.analyse-risques.soumettre', $formId) : null,
                'urlValider'       => $formId ? route('auditor.ac.analyse-risques.valider',   $formId) : null,
                'urlAssignProcess' => route('auditor.ac.analyse-risques.assign-process'),
                'backUrl'          => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════

    private function resolveEntityId($mission, $assignment = null): ?int
    {
        foreach (['entity_id','entite_id','structure_id','org_id'] as $col) {
            if ($mission  && !empty(((array)$mission)[$col]))     return (int)((array)$mission)[$col];
        }
        foreach (['entity_id','entite_id','structure_id'] as $col) {
            if ($assignment && !empty(((array)$assignment)[$col])) return (int)((array)$assignment)[$col];
        }
        $eid = DB::connection('tenant')->table('audit_universe')->orderByDesc('year')->value('entity_id');
        return $eid ? (int)$eid : null;
    }

    private function resolveActiveYear(?int $entityId, $mission): int
    {
        if ($entityId) {
            $y = DB::connection('tenant')->table('audit_universe')->where('entity_id',$entityId)->orderByDesc('year')->value('year');
            if ($y) return (int)$y;
        }
        foreach (['year','annee','exercice','fiscal_year'] as $col) {
            if ($mission && !empty(((array)$mission)[$col])) return (int)((array)$mission)[$col];
        }
        return (int)date('Y');
    }

    private function loadUniverseRisks(?int $entityId, int $activeYear): array
    {
        if (!$entityId) return [collect(), $activeYear];

        $universeRow = DB::connection('tenant')->table('audit_universe')
            ->where('entity_id',$entityId)->where('year',$activeYear)->first();
        if (!$universeRow) {
            $universeRow = DB::connection('tenant')->table('audit_universe')
                ->where('entity_id',$entityId)->orderByDesc('year')->first();
            if ($universeRow) $activeYear = (int)$universeRow->year;
        }
        if (!$universeRow?->risques) return [collect(), $activeYear];

        $decoded = json_decode($universeRow->risques, true);
        if (!is_array($decoded)) return [collect(), $activeYear];

        $universeMap = [];
        foreach ($decoded as $entry) {
            $rid = (int)($entry['risk_id'] ?? 0);
            if ($rid > 0) $universeMap[$rid] = $entry;
        }
        if (empty($universeMap)) return [collect(), $activeYear];

        $risks = DB::connection('tenant')->table('risks as r')
            ->leftJoin('risk_frequency_levels as rfl', fn($j)=>$j->on('rfl.id','=','r.frequency_level_id')->whereNull('rfl.deleted_at'))
            ->leftJoin('risk_impact_levels as ril',    fn($j)=>$j->on('ril.id','=','r.impact_level_id')   ->whereNull('ril.deleted_at'))
            ->leftJoin('processes as p',  'p.id', '=', 'r.process_id')
            ->leftJoin('activities as a', 'a.id', '=', 'r.activity_id')
            ->leftJoin('risk_types as rt',fn($j)=>$j->on('rt.id','=','r.risk_type_id')->whereNull('rt.deleted_at'))
            ->whereIn('r.id', array_keys($universeMap))
            ->select([
                'r.id','r.code','r.label','r.description','r.status','r.control_procedure',
                'r.impact_net','r.frequency_net','r.entity_id','r.process_id','r.activity_id','r.risk_type_id','r.year',
                DB::raw('rfl.level AS frequency_level'),DB::raw('rfl.label AS frequency_label'),DB::raw('rfl.color AS frequency_color'),
                DB::raw('ril.level AS impact_level'),   DB::raw('ril.label AS impact_label'),   DB::raw('ril.color AS impact_color'),
                DB::raw('p.code  AS process_code'),     DB::raw('p.name  AS process_name'),
                DB::raw('a.code  AS activity_code'),    DB::raw('a.name  AS activity_name'),
                DB::raw('rt.label AS risk_type_label'), DB::raw('rt.color AS risk_type_color'),
            ])
            ->orderBy('p.code')->orderBy('r.code')
            ->get()
            ->map(function($row) use ($universeMap) {
                $u = $universeMap[(int)$row->id] ?? null;
                $row->frequency_level = $row->frequency_level ?? null;
                $row->frequency_label = $row->frequency_label ?? '-';
                $row->frequency_color = $row->frequency_color ?? 'secondary';
                $row->impact_level    = $row->impact_level    ?? null;
                $row->impact_label    = $row->impact_label    ?? '-';
                $row->impact_color    = $row->impact_color    ?? 'secondary';
                $row->process_code    = $row->process_code    ?? '-';
                $row->process_name    = $row->process_name    ?? '-';
                $row->activity_code   = $row->activity_code   ?? '-';
                $row->activity_name   = $row->activity_name   ?? '-';
                $row->risk_type_label = $row->risk_type_label ?? '-';
                $row->risk_type_color = $row->risk_type_color ?? 'secondary';
                $row->criticality_net   = null;
                $row->qualification_net = null;
                $row->is_evaluated      = false;
                if ($u) {
                    if (isset($u['impact_net'])    && $u['impact_net']    !== null) $row->impact_net    = (int)$u['impact_net'];
                    if (isset($u['frequency_net']) && $u['frequency_net'] !== null) $row->frequency_net = (int)$u['frequency_net'];
                    if (!empty($u['control_procedure'])) $row->control_procedure = $u['control_procedure'];
                    $row->criticality_net   = $u['criticality_net']   ?? null;
                    $row->qualification_net = $u['qualification_net']  ?? null;
                    $row->is_evaluated      = (bool)($u['is_evaluated'] ?? false);
                }
                return $row;
            });

        return [$risks, $activeYear];
    }

    private function mergeRisksWithSaved(\Illuminate\Support\Collection $universeRisks, array $savedRisks, array $matrix=[]): array
    {
        $matrixMap = [];
        foreach ($matrix as $m) $matrixMap["{$m->frequency_level}_{$m->impact_level}"] = (int)$m->qualification;

        $savedMap = [];
        foreach ($savedRisks as $s) { $rid=(int)($s['risk_id']??0); if($rid>0) $savedMap[$rid]=$s; }

        return $universeRisks->map(function($risk) use ($savedMap,$matrixMap) {
            $s = $savedMap[(int)$risk->id] ?? [];
            $imp  = isset($s['impact_net'])    && $s['impact_net']    !== null ? (int)$s['impact_net']    : ($risk->impact_net    !== null?(int)$risk->impact_net:null);
            $freq = isset($s['frequency_net']) && $s['frequency_net'] !== null ? (int)$s['frequency_net'] : ($risk->frequency_net !== null?(int)$risk->frequency_net:null);
            $glob = isset($s['glob_resid'])    && $s['glob_resid']    !== null ? (int)$s['glob_resid']    : ($imp&&$freq?($matrixMap["{$freq}_{$imp}"]??($imp*$freq)):null);
            return [
                'id'=>(int)$risk->id,'code'=>$risk->code,'label'=>$risk->label,'description'=>$risk->description??'',
                'status'=>$risk->status,'year'=>$risk->year,
                'process_code'=>$risk->process_code,'process_name'=>$risk->process_name,'process_id'=>$risk->process_id,
                'activity_code'=>$risk->activity_code,'activity_name'=>$risk->activity_name,'activity_id'=>$risk->activity_id,
                'risk_type_label'=>$risk->risk_type_label,'risk_type_color'=>$risk->risk_type_color,'risk_type_id'=>$risk->risk_type_id,
                'impact_level'=>$risk->impact_level,'impact_label'=>$risk->impact_label,'impact_color'=>$risk->impact_color,
                'frequency_level'=>$risk->frequency_level,'frequency_label'=>$risk->frequency_label,'frequency_color'=>$risk->frequency_color,
                'criticality'=>$risk->criticality??null,'criticality_net'=>$risk->criticality_net,'qualification_net'=>$risk->qualification_net,
                'is_evaluated'=>$risk->is_evaluated,
                'control_procedure'=>$s['control_procedure']??$risk->control_procedure??'',
                'impact_net'=>$imp,'frequency_net'=>$freq,'glob_resid'=>$glob,
                'nature'=>$s['nature']??'','qualif_controle'=>$s['qualif_controle']??'',
                'assertions'=>$s['assertions']??'','forces'=>$s['forces']??'','faiblesses'=>$s['faiblesses']??'',
                'objectif_controle'=>$s['objectif_controle']??'','choix'=>(bool)($s['choix']??false),'_isNew'=>false,
            ];
        })->values()->all();
    }

    private function genArCode(int $missionId): string
    {
        $mission = DB::table('missions')->where('id',$missionId)->first()
                ?? DB::connection('tenant')->table('missions')->where('id',$missionId)->first();
        $slug  = $mission?->code_mission ? strtoupper(preg_replace('/[^A-Z0-9]/i','',$mission->code_mission)) : 'M'.$missionId;
        $count = DB::connection('tenant')->table($this->table)->where('mission_id',$missionId)->count();
        return 'AR-'.substr($slug,0,8).'-'.str_pad($count+1,3,'0',STR_PAD_LEFT);
    }
}