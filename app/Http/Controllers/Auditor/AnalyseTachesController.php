<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Param\Auditor;

class AnalyseTachesController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_at';
    protected string $formCode    = 'analyse-taches';
    protected string $codePrefix  = 'AT';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseTaches';
    protected string $routeEdit   = 'auditor.ac.analyse-taches.edit';

    protected array $validationRules = [
        'synthese'   => 'nullable|string',
        'fait_par'   => 'nullable|string|max:255',
        'revue_par'  => 'nullable|string|max:255',
        'date_fait'  => 'nullable|date',
        'date_revue' => 'nullable|date',
    ];

    // ══════════════════════════════════════════════════════════════════
    // formData — champs persistés dans mission_phase_at
    // ══════════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'raci_data'       => $request->input('raci_data',       '[]'),
            'functions_added' => $request->input('functions_added', '[]'),
            'synthese'        => $request->input('synthese'),
            'fait_par'        => $request->input('fait_par'),
            'revue_par'       => $request->input('revue_par'),
            'date_fait'       => $request->input('date_fait')  ?: null,
            'date_revue'      => $request->input('date_revue') ?: null,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // buildPayload — construit toutes les props envoyées à la Vue
    //
    // Schéma réel (validé sur les SQL fournis) :
    //
    //   mission_phase_assignments
    //     id, mission_programmation_id, mission_phase_id, entity_id, status…
    //
    //   mission_phase_assignment_auditeurs
    //     id, assignment_id, auditeur_id, role_code, role_id,
    //     parent_auditeur_id, date_affectation, affecte_par
    //     UNIQUE (assignment_id, auditeur_id)
    //     FK → mission_phase_assignments(id) CASCADE
    //     FK → auditors(id)                 CASCADE
    //
    //   at_process_assignments  (nouvelle table créée)
    //     id, assignment_id, process_id, auditeur_id, affecte_par,
    //     date_affectation
    //     UNIQUE (assignment_id, process_id)
    //
    // Props retournées à la Vue :
    //   mission, assignment, auditeurs, auditorRole,
    //   missionId, assignmentId, errors, noMission      ← parent
    //   form                → données formulaire AT
    //   processesData       → processus liés mission + activités
    //                         avec TOUS les risques natifs
    //   unlinkedProcesses   → processus hors mission (select ajout manuel)
    //   raciRoles           → [{ id, code, label, description }]
    //   assignmentFunctions → fonctions pré-chargées (assignment)
    //   allFunctions        → toutes les fonctions disponibles
    //   riskCount           → nb risques liés à la mission (header méta)
    //   atList              → liste AT de cet assignment (sidebar)
    //   currentAuditor      → { id, audit_code, last_name, first_name }
    //   phaseAuditeurs      → auditeurs de la phase (mpaa jointure auditors)
    //                         [{ id, audit_code, full_name, initials,
    //                            role_code, role_label,
    //                            parent_auditeur_id }]
    //   processAssignments  → { "process_id" → auditeur_id }
    //                         depuis at_process_assignments
    //   formUrl, backUrl, chatBaseUrl, chatMessages
    // ══════════════════════════════════════════════════════════════════
    protected function buildPayload(
        int     $missionId,      // = mission_programmation_id
        int     $assignmentId,
        Auditor $auditor,
        mixed   $form = null
    ): array {

        // ── mission_programmation → missions.id réel ──────────────────────
        $missionRow    = DB::connection('tenant')
            ->table('mission_programmation')
            ->where('id', $missionId)
            ->select('id', 'mission_id')
            ->first();
        $realMissionId = $missionRow?->mission_id ?? $missionId;

        // ── Risques liés à la mission ─────────────────────────────────────
        $missionRiskIds = DB::connection('tenant')
            ->table('mission_risk')
            ->where('mission_id', $realMissionId)
            ->pluck('risk_id')
            ->toArray();

        $missionRisks = [];
        if (!empty($missionRiskIds)) {
            $missionRisks = DB::connection('tenant')
                ->table('risks')
                ->whereIn('id', $missionRiskIds)
                ->whereNull('deleted_at')
                ->select('id', 'code', 'label', 'process_id', 'activity_id',
                         'criticality', 'frequency_net', 'impact_net')
                ->get()
                ->toArray();
        }

        // Processus liés via les risques de la mission
        $linkedProcessIds = collect($missionRisks)
            ->pluck('process_id')->unique()->filter()->values()->toArray();

        // Activités liées à des risques de la mission → fond jaune RACI
        $missionRiskActivityIds = collect($missionRisks)
            ->pluck('activity_id')->unique()->filter()->values()->toArray();

        // ── Tous les processus ────────────────────────────────────────────
        $allProcesses = DB::connection('tenant')
            ->table('processes')
            ->select('id', 'code', 'name')
            ->orderBy('code')
            ->get()->toArray();

        $linkedProcesses = collect($allProcesses)
            ->whereIn('id', $linkedProcessIds)
            ->values()->toArray();

        $linkedIds = collect($linkedProcesses)->pluck('id')->toArray();

        // ── Toutes les activités ──────────────────────────────────────────
        $allProcessIds    = collect($allProcesses)->pluck('id')->toArray();
        $allActivitiesRaw = !empty($allProcessIds)
            ? DB::connection('tenant')
                ->table('activities')
                ->whereIn('process_id', $allProcessIds)
                ->select('id', 'process_id', 'code', 'name', 'description')
                ->orderBy('code')
                ->get()->toArray()
            : [];

        $allActIds = collect($allActivitiesRaw)->pluck('id')->toArray();

        // ── TOUS les risques natifs de toutes les activités ───────────────
        // Chaque activité expose ses risques propres (activity_id).
        // is_mission_risk = true si le risque est aussi dans mission_risk.
        $allActivityRisks = [];
        if (!empty($allActIds)) {
            $allActivityRisks = DB::connection('tenant')
                ->table('risks')
                ->whereIn('activity_id', $allActIds)
                ->whereNull('deleted_at')
                ->select('id', 'code', 'label', 'activity_id',
                         'criticality', 'frequency_net', 'impact_net')
                ->get()
                ->groupBy('activity_id')
                ->map(fn ($rows) => $rows->map(fn ($r) => [
                    'id'              => $r->id,
                    'code'            => $r->code,
                    'label'           => $r->label,
                    'crit'            => $r->criticality,
                    'freq'            => $r->frequency_net,
                    'impact'          => $r->impact_net,
                    'is_mission_risk' => in_array($r->id, $missionRiskIds),
                ])->values()->toArray())
                ->toArray();
        }

        // ── RACI existant ─────────────────────────────────────────────────
        $existingRaci = [];
        if (!empty($allActIds)) {
            $existingRaci = DB::connection('tenant')
                ->table('activity_raci')
                ->whereIn('activity_id', $allActIds)
                ->select('activity_id', 'function_id', 'role')
                ->get()
                ->groupBy('activity_id')
                ->map(fn ($rows) =>
                    $rows->mapWithKeys(fn ($r) => [(string) $r->function_id => $r->role])->toArray()
                )
                ->toArray();
        }

        // ── Rôles RACI ────────────────────────────────────────────────────
        $raciRoles = DB::connection('tenant')
            ->table('activity_raci_roles')
            ->orderBy('sort')
            ->get(['id', 'code', 'label', 'description'])
            ->toArray();

        // ── Fonctions de l'assignment ─────────────────────────────────────
        $assignmentFunctions = DB::connection('tenant')
            ->table('assignment_functions as af')
            ->join('functions as f', 'f.id', '=', 'af.function_id')
            ->where('af.assignment_id', $assignmentId)
            ->select('f.id', 'f.name', 'f.character')
            ->distinct()
            ->orderBy('f.character')
            ->get()
            ->map(fn ($fn) => [
                'id'        => $fn->id,
                'name'      => $fn->name,
                'character' => $fn->character,
            ])
            ->toArray();

        // ── Toutes les fonctions ──────────────────────────────────────────
        $allFunctions = DB::connection('tenant')
            ->table('functions')
            ->select('id', 'name', 'character')
            ->orderBy('character')
            ->orderBy('name')
            ->get()
            ->map(fn ($fn) => [
                'id'        => $fn->id,
                'name'      => $fn->name,
                'character' => $fn->character,
            ])
            ->toArray();

        // ── AUDITEURS DE LA PHASE ─────────────────────────────────────────
        // Source exacte : mission_phase_assignment_auditeurs
        //   assignment_id → lie à mission_phase_assignments
        //   auditeur_id   → auditors.id  (NB : pas auditor_id)
        //   role_code     → DM / CM / AS / AJ
        //   parent_auditeur_id → supervision hiérarchique (AS → CM, AJ → AS/CM)
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id',
                'a.audit_code',
                'a.last_name',
                'a.first_name',
                'mpaa.role_code',
                'mpaa.parent_auditeur_id',
                'mpaa.date_affectation',
                DB::raw("TRIM(CONCAT(
                    COALESCE(a.last_name,''), ' ',
                    COALESCE(a.first_name,'')
                )) as full_name"),
                DB::raw("UPPER(CONCAT(
                    COALESCE(LEFT(a.last_name,1),''),
                    COALESCE(LEFT(a.first_name,1),'')
                )) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code, 'DM', 'CM', 'AS', 'AJ')")
            ->orderBy('a.last_name')
            ->get()
            ->map(fn ($a) => [
                'id'                 => $a->id,
                'audit_code'         => $a->audit_code,
                'last_name'          => $a->last_name,
                'first_name'         => $a->first_name,
                'full_name'          => trim($a->full_name),
                'initials'           => $a->initials,
                'role_code'          => $a->role_code,
                'parent_auditeur_id' => $a->parent_auditeur_id,
                'date_affectation'   => $a->date_affectation,
                'role_label'         => match ($a->role_code) {
                    'DM'    => 'Directeur de Mission',
                    'CM'    => 'Chef de Mission',
                    'AS'    => 'Auditeur Senior',
                    'AJ'    => 'Auditeur Junior',
                    default => $a->role_code ?? '—',
                },
            ])
            ->toArray();

        // ── AFFECTATIONS PROCESSUS → AUDITEUR ─────────────────────────────
        // Source : at_process_assignments
        // Format Vue : { "process_id" (string) → auditeur_id (int|null) }
        $processAssignments = [];
        if (DB::connection('tenant')->getSchemaBuilder()->hasTable('at_process_assignments')) {
            $rows = DB::connection('tenant')
                ->table('at_process_assignments')
                ->where('assignment_id', $assignmentId)
                ->whereNotNull('auditeur_id')
                ->select('process_id', 'auditeur_id')
                ->get();

            foreach ($rows as $row) {
                $processAssignments[(string) $row->process_id] = $row->auditeur_id;
            }
        }

        // ── Helper buildProcData ──────────────────────────────────────────
        $buildProcData = function (
            array $proc,
            bool  $withMissionRisks
        ) use (
            $missionRisks, $allActivitiesRaw, $existingRaci,
            $missionRiskActivityIds, $allActivityRisks
        ): array {
            $procId           = $proc['id'];
            $procMissionRisks = $withMissionRisks
                ? collect($missionRisks)->where('process_id', $procId)->values()
                : collect();

            $activities = collect($allActivitiesRaw)
                ->where('process_id', $procId)
                ->map(fn ($act) => [
                    'id'             => $act->id,
                    'code'           => $act->code,
                    'name'           => $act->name,
                    'description'    => $act->description ?? '',
                    // Fond jaune = activité liée à un risque de LA MISSION
                    'linked_to_risk' => in_array($act->id, $missionRiskActivityIds),
                    // Tous les risques natifs de l'activité (is_mission_risk distingue)
                    'risks'          => $allActivityRisks[$act->id] ?? [],
                    // RACI pré-chargé { "function_id" → role }
                    'raci'           => $existingRaci[$act->id]  ?? [],
                ])
                ->values()
                ->toArray();

            return [
                'id'         => $procId,
                'code'       => $proc['code'],
                'name'       => $proc['name'],
                'risk_count' => $procMissionRisks->count(),
                'is_linked'  => $withMissionRisks,
                'activities' => $activities,
            ];
        };

        // ── processesData (liés mission) ─────────────────────────────────
        $processesData = collect($linkedProcesses)
            ->map(fn ($p) => $buildProcData((array) $p, true))
            ->values()->toArray();

        // ── unlinkedProcesses ─────────────────────────────────────────────
        $unlinkedProcesses = collect($allProcesses)
            ->filter(fn ($p) => !in_array($p->id, $linkedIds))
            ->map(fn ($p) => $buildProcData((array) $p, false))
            ->values()->toArray();

        // ── atList ────────────────────────────────────────────────────────
        $atList = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'validation_status', 'fait_par', 'updated_at'])
            ->orderByDesc('created_at')
            ->get()->toArray();

        // ── Chat ──────────────────────────────────────────────────────────
        $role         = $this->getRole($missionId, $auditor->id);
        $chatMessages = $this->getChatMessages($assignmentId, $auditor->id, $role);

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'                => $form,
                'processesData'       => $processesData,
                'unlinkedProcesses'   => $unlinkedProcesses,
                'raciRoles'           => $raciRoles,
                'assignmentFunctions' => $assignmentFunctions,
                'allFunctions'        => $allFunctions,
                'riskCount'           => count($missionRiskIds),
                'atList'              => $atList,
                'currentAuditor'      => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'phaseAuditeurs'     => $phaseAuditeurs,
                'processAssignments' => $processAssignments,
                'formUrl'            => url('/m/audit.core/ac/preparation/analyse-taches'),
                'backUrl'            => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
                'chatMessages'       => $chatMessages,
                'chatBaseUrl'        => url('/api/mission-phase-chat'),
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // getRole — rôle de l'auditeur courant dans la phase
    // Lit mission_phase_assignment_auditeurs via mission_phase_assignments
    // ══════════════════════════════════════════════════════════════════
    protected function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code, 'DM', 'CM', 'AS', 'AJ')")
            ->first();

        return $row?->role_code ?? 'AJ';
    }

    // ══════════════════════════════════════════════════════════════════
    // getChatMessages — filtrage par rôle
    // ══════════════════════════════════════════════════════════════════
    private function getChatMessages(int $assignmentId, int $auditorId, string $role): array
    {
        if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('mission_phase_chat')) {
            return [];
        }

        return DB::connection('tenant')
            ->table('mission_phase_chat as c')
            ->join('auditors as a', 'c.author_id', '=', 'a.id')
            ->where('c.assignment_id', $assignmentId)
            ->where('c.form_code', 'analyse-taches')
            ->where(function ($q) use ($auditorId, $role) {
                if ($role === 'DM') { $q->whereRaw('1=1'); return; }
                $visible = match ($role) {
                    'CM'    => ['CM', 'AS', 'AJ'],
                    'AS'    => ['AS', 'AJ'],
                    default => ['AJ'],
                };
                $q->where('c.author_id', $auditorId)
                  ->orWhereIn('c.author_role', $visible);
            })
            ->select([
                'c.id', 'c.content', 'c.type', 'c.priority', 'c.is_pinned',
                'c.author_id', 'c.author_role', 'c.parent_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(
                    COALESCE(LEFT(a.last_name,1),''),
                    COALESCE(LEFT(a.first_name,1),'')
                )) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id = {$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('c.created_at', 'asc')
            ->get()
            ->map(fn ($m) => tap($m, fn ($m) => $m->is_mine = (bool) $m->is_mine))
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════
    // store
    // ══════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int) $request->input('mission_id', 0);
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId) {
            return response()->json(['success' => false, 'message' => 'Contexte de mission manquant.'], 422);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment || $assignment->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Démarrez la phase avant de remplir ce formulaire.',
            ], 422);
        }

        $existing = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->first();

        if ($existing) return $this->update($request, $existing->id);

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            'code'              => $this->genCode($missionId),
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');
        $this->syncActivityRaci($request);

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();

        return response()->json(['success' => true, 'form' => $form, 'message' => 'Analyse créée.']);
    }

    // ══════════════════════════════════════════════════════════════════
    // update
    // ══════════════════════════════════════════════════════════════════
    public function update(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }
        if (!$this->canEdit($row, $role)) {
            return response()->json(['success' => false, 'message' => match ($row->validation_status) {
                'validated' => 'Formulaire validé — modification impossible.',
                'in_review' => 'Formulaire soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);
        }

        DB::connection('tenant')->table($this->table)->where('id', $formId)
            ->update(array_merge($this->formData($request, $auditor), ['updated_at' => now()]));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $this->syncActivityRaci($request);

        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();

        return response()->json(['success' => true, 'form' => $updated, 'message' => 'Analyse mise à jour.']);
    }

    // ══════════════════════════════════════════════════════════════════
    // syncActivityRaci — persiste dans activity_raci
    // raci_data JSON : [{ activity_id, function_id, role }, ...]
    // ══════════════════════════════════════════════════════════════════
    private function syncActivityRaci(Request $request): void
    {
        $raw      = $request->input('raci_data', '[]');
        $raciData = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);

        if (empty($raciData) || !is_array($raciData)) return;

        $activityIds = collect($raciData)->pluck('activity_id')->unique()->filter()->toArray();
        if (empty($activityIds)) return;

        DB::connection('tenant')->transaction(function () use ($activityIds, $raciData) {
            DB::connection('tenant')->table('activity_raci')
                ->whereIn('activity_id', $activityIds)->delete();

            foreach ($raciData as $entry) {
                if (empty($entry['activity_id']) || empty($entry['function_id']) || empty($entry['role'])) continue;
                DB::connection('tenant')->table('activity_raci')->insertOrIgnore([
                    'activity_id' => (int) $entry['activity_id'],
                    'function_id' => (int) $entry['function_id'],
                    'role'        => $entry['role'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // assignProcess — POST /assign-process
    // DM / CM affectent un processus à un auditeur DE LA PHASE.
    // L'auditeur cible doit être dans mission_phase_assignment_auditeurs
    // pour cet assignment_id.
    // Body JSON : { assignment_id, process_id, auditeur_id }
    //   auditeur_id = null → désaffectation (auditeur_id mis à NULL)
    // ══════════════════════════════════════════════════════════════════
    public function assignProcess(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $assignmentId = (int) $request->input('assignment_id', 0);
        $processId    = (int) $request->input('process_id',    0);
        $targetId     = $request->input('auditeur_id'); // null = désaffecter

        if (!$assignmentId || !$processId) {
            return response()->json(['error' => 'Paramètres manquants'], 422);
        }

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->select('id', 'mission_programmation_id')
            ->first();

        if (!$assignment) return response()->json(['error' => 'Assignment introuvable'], 404);

        $role = $this->getRole($assignment->mission_programmation_id, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent affecter des processus'], 403);
        }

        // Vérifier que l'auditeur cible est bien dans cette phase
        if ($targetId) {
            $inPhase = DB::connection('tenant')
                ->table('mission_phase_assignment_auditeurs')
                ->where('assignment_id', $assignmentId)
                ->where('auditeur_id', (int) $targetId)
                ->exists();

            if (!$inPhase) {
                return response()->json([
                    'error' => 'Cet auditeur n\'est pas affecté à cette phase',
                ], 422);
            }
        }

        if ($targetId) {
            // Affectation : upsert
            DB::connection('tenant')->table('at_process_assignments')
                ->updateOrInsert(
                    ['assignment_id' => $assignmentId, 'process_id' => $processId],
                    [
                        'auditeur_id'      => (int) $targetId,
                        'affecte_par'      => $auditor->id,
                        'date_affectation' => now()->toDateString(),
                        'updated_at'       => now(),
                        'created_at'       => now(),
                    ]
                );
        } else {
            // Désaffectation : auditeur_id → NULL (on garde la trace)
            DB::connection('tenant')->table('at_process_assignments')
                ->where('assignment_id', $assignmentId)
                ->where('process_id', $processId)
                ->update([
                    'auditeur_id'      => null,
                    'affecte_par'      => $auditor->id,
                    'date_affectation' => now()->toDateString(),
                    'updated_at'       => now(),
                ]);
        }

        return response()->json([
            'success'     => true,
            'process_id'  => $processId,
            'auditeur_id' => $targetId ? (int) $targetId : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // soumettre
    // ══════════════════════════════════════════════════════════════════
    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['error' => 'Accès refusé'], 403);
        if ($row->validation_status === 'validated')
            return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review')
            return response()->json(['error' => 'Déjà soumis pour validation'], 422);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');

        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    // ══════════════════════════════════════════════════════════════════
    // valider
    // ══════════════════════════════════════════════════════════════════
    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM']))
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review')
            return response()->json(['error' => 'Le formulaire doit être soumis avant validation'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif du rejet obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $form)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM')
            return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->update([
                'validation_status' => 'validated',
                'validated_at'      => now(),
                'validated_by'      => $auditor->id,
                'updated_at'        => now(),
            ]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);

        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }
}