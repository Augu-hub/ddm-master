<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Services\Mission\MissionProgrammationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissionController extends Controller
{
    /**
     * Table `missions` – colonnes réelles :
     *   id, code, fpm_number, audit_exercise_id, entity_id, mission_type_id,
     *   title, objective, domain, description (bigint FK nullable),
     *   reference_document, priority (enum: basse|moyenne|haute|critique),
     *   planned_start_date, planned_end_date, planned_duration_days,
     *   status (enum: brouillon|planifiée|en_cours|terminée|annulée),
     *   created_by, updated_by, created_at, updated_at, deleted_at
     *
     * ⚠️  created_by/updated_by sont FK → users.id (connexion DEFAULT).
     *     La table missions est sur la connexion TENANT.
     *     → On passe NULL pour éviter la violation de contrainte FK cross-DB.
     *
     * Pivots :
     *   mission_risk       (id, mission_id, risk_id, created_at, updated_at)
     *   mission_competency (id, mission_id, competency_id, minimum_level, ...)
     *
     * ── NOUVEAU ──────────────────────────────────────────────────────────
     * À la création, la mission déclenche désormais AUTOMATIQUEMENT la
     * génération de sa programmation (`mission_programmation`) et de ses
     * affectations de phase (`mission_phase_assignments`), via
     * MissionProgrammationService::autoGenerate(). Les phases utilisées
     * viennent de `mission_phases`, tenu à jour automatiquement depuis le
     * référentiel central `ddmparam` par TenantReferenceSyncService
     * (cf. app/Observers/*Observer.php + SyncTenantReferenceDataJob).
     *
     * Le résultat est renvoyé en "proposition" modifiable : rien n'est
     * verrouillé tant que l'utilisateur n'a pas appelé confirmProgrammation().
     */
    public function __construct(private MissionProgrammationService $programmationService)
    {
    }

    /** Connexion tenant (entities, processes, risks, missions…) */
    private function tenant()
    {
        return DB::connection('tenant');
    }

    // =========================================================================
    // CREATE — formulaire
    // =========================================================================
    public function create()
    {
        try {
            // ── Exercices audit ──────────────────────────────────────────
            $exercises = $this->tenant()->table('audit_exercises')
                ->where('is_active', 1)
                ->orderByDesc('year')
                ->select('id', 'code', 'name', 'year')
                ->get()
                ->map(fn($e) => [
                    'id'   => (int) $e->id,
                    'code' => $e->code,
                    'name' => $e->name,
                    'year' => (int) $e->year,
                ])->toArray();

            // ── Types d'audit — RÉFÉRENTIEL CENTRAL (ddmparam) ────────────
            // Source de vérité pour label/couleur/icône : jamais la copie
            // dénormalisée du tenant, qui peut être désynchronisée (bug déjà
            // constaté : AMP rattaché à AF au lieu de AM dans fruitiva).
            // ⚠️ CORRECTIF : il n'existe pas de connexion "default" dans ce
            // projet (cf. .env : DB_CONNECTION=mysql). La base centrale
            // ddmparam est accessible via la connexion "mysql".
            $auditTypes = DB::connection('mysql')->table('ddmparam.audit_types')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->select('id', 'code', 'label', 'short_label', 'color', 'icon')
                ->get()
                ->map(fn($at) => [
                    'id'          => (int) $at->id,
                    'code'        => $at->code,
                    'label'       => $at->label,
                    'short_label' => $at->short_label,
                    'color'       => $at->color,
                    'icon'        => $at->icon,
                ])->keyBy('code');

            // ── Types de mission (tenant) enrichis en live depuis ddmparam ─
            // On ne modifie rien en base ici (c'est le rôle du sync job en
            // tâche de fond) : on corrige juste l'AFFICHAGE au cas où la
            // synchro n'est pas encore passée, pour ne jamais montrer une
            // donnée périmée à l'utilisateur.
            $missionTypes = $this->tenant()->table('mission_types')
                ->where('is_active', 1)
                ->orderBy('code')
                ->select('id', 'code', 'label', 'audit_type_code', 'audit_type_label', 'audit_color', 'audit_icon')
                ->get()
                ->map(function ($mt) use ($auditTypes) {
                    $ref = $mt->audit_type_code ? ($auditTypes[$mt->audit_type_code] ?? null) : null;
                    return [
                        'id'               => (int) $mt->id,
                        'code'             => $mt->code,
                        'label'            => $mt->label,
                        'audit_type_code'  => $mt->audit_type_code,
                        // Valeurs central si le référentiel est trouvé, sinon on
                        // retombe sur la copie tenant (mieux que rien).
                        // ⚠️ $ref est un TABLEAU (cf. ->map(fn($at) => [...]) plus
                        // haut), donc accès par clé ['label'], jamais ->label.
                        'audit_type_label' => $ref['label'] ?? $mt->audit_type_label,
                        'audit_color'      => $ref['color'] ?? $mt->audit_color,
                        'audit_icon'       => $ref['icon']  ?? $mt->audit_icon,
                        'is_synced'        => $ref
                            ? ($ref['label'] === $mt->audit_type_label && $ref['color'] === $mt->audit_color && $ref['icon'] === $mt->audit_icon)
                            : false,
                    ];
                })->toArray();

            // ── Entités ──────────────────────────────────────────────────
            $entities = $this->tenant()->table('entities')
                ->orderBy('name')
                ->select('id', 'code_base', 'name')
                ->get()
                ->map(fn($e) => [
                    'id'        => (int) $e->id,
                    'code_base' => $e->code_base,
                    'name'      => $e->name,
                ])->toArray();

            // ── Compétences ──────────────────────────────────────────────
            $competencies = [];
            try {
                $competencies = $this->tenant()->table('competencies')
                    ->orderBy('code')
                    ->select('id', 'code', 'name')
                    ->get()
                    ->map(fn($c) => [
                        'id'   => (int) $c->id,
                        'code' => $c->code,
                        'name' => $c->name,
                    ])->toArray();
            } catch (\Exception $e) {
                Log::warning('MissionController@create: competencies: ' . $e->getMessage());
            }

            // ── Missions FPM (audit_mission_requests) ────────────────────
            $fpmMissions = [];
            try {
                $fpmMissions = $this->tenant()->table('audit_mission_requests as amr')
                    ->leftJoin('entities as e', 'amr.entity_id', '=', 'e.id')
                    ->where('amr.status', 'draft')
                    ->orderByDesc('amr.id')
                    ->select(
                        'amr.id', 'amr.code', 'amr.mission_objective as title',
                        'amr.description', 'amr.start_date', 'amr.end_date',
                        'amr.level', 'amr.coefficient', 'amr.entity_id', 'amr.related_risk_id',
                        'e.code_base as entity_code', 'e.name as entity_name'
                    )
                    ->limit(100)->get()
                    ->map(function ($m) {
                        $riskIds = $m->related_risk_id ? [(int) $m->related_risk_id] : [];
                        return [
                            'id'          => (int) $m->id,
                            'code'        => $m->code,
                            'title'       => $m->title,
                            'objective'   => $m->description,
                            'start_date'  => $m->start_date,
                            'end_date'    => $m->end_date,
                            'level'       => $m->level,
                            'coefficient' => $m->coefficient,
                            'entity_id'   => $m->entity_id ? (int) $m->entity_id : null,
                            'entity_ids'  => $m->entity_id ? [(int) $m->entity_id] : [],
                            'risk_ids'    => $riskIds,
                            'risk_count'  => count($riskIds),
                            'entity'      => $m->entity_id ? [
                                'id'        => (int) $m->entity_id,
                                'code_base' => $m->entity_code,
                                'name'      => $m->entity_name,
                            ] : null,
                        ];
                    })->toArray();
            } catch (\Exception $e) {
                Log::warning('MissionController@create: fpmMissions: ' . $e->getMessage());
            }

            // ── Missions audit (audit_missions) ──────────────────────────
            $auditMissions = [];
            try {
                $auditMissions = $this->tenant()->table('audit_missions as am')
                    ->leftJoin('mission_types as mt', 'am.mission_type_id', '=', 'mt.id')
                    ->orderByDesc('am.created_at')
                    ->select(
                        'am.id', 'am.code', 'am.title', 'am.objective', 'am.but',
                        'am.scheduled_start_date as planned_start_date',
                        'am.scheduled_end_date as planned_end_date',
                        'am.mission_type_id', 'am.priority_rank as priority', 'am.status',
                        'mt.code as type_code', 'mt.label as type_label'
                    )
                    ->limit(100)->get()
                    ->map(function ($m) {
                        $entityIds  = [];
                        $entityList = [];
                        try {
                            if ($this->tenantTableExists('audit_mission_entities')) {
                                $entityIds = $this->tenant()->table('audit_mission_entities')
                                    ->where('audit_mission_id', $m->id)
                                    ->pluck('entity_id')->map(fn($id) => (int)$id)->toArray();
                                if ($entityIds) {
                                    $entityList = $this->tenant()->table('entities')
                                        ->whereIn('id', $entityIds)->select('id','code_base','name')->get()
                                        ->map(fn($e) => ['id'=>(int)$e->id,'code_base'=>$e->code_base,'name'=>$e->name])
                                        ->toArray();
                                }
                            }
                        } catch (\Exception $e) {}

                        $riskIds = [];
                        try {
                            if ($this->tenantTableExists('audit_mission_risks')) {
                                $riskIds = $this->tenant()->table('audit_mission_risks')
                                    ->where('audit_mission_id', $m->id)
                                    ->pluck('risk_id')->map(fn($id) => (int)$id)->toArray();
                            }
                        } catch (\Exception $e) {}

                        $priority = $m->priority;
                        if (is_numeric($priority)) {
                            $priority = [1=>'basse',2=>'moyenne',3=>'haute',4=>'critique'][(int)$priority] ?? 'moyenne';
                        }

                        return [
                            'id'                 => (int) $m->id,
                            'code'               => $m->code,
                            'title'              => $m->title,
                            'objective'          => $m->objective ?: $m->but,
                            'planned_start_date' => $m->planned_start_date,
                            'planned_end_date'   => $m->planned_end_date,
                            'entity_ids'         => $entityIds,
                            'entities'           => $entityList,
                            'mission_type_id'    => $m->mission_type_id ? (int)$m->mission_type_id : null,
                            'priority'           => $priority,
                            'status'             => $m->status,
                            'type_code'          => $m->type_code,
                            'type_label'         => $m->type_label,
                            'risk_ids'           => $riskIds,
                            'risk_count'         => count($riskIds),
                        ];
                    })->toArray();
            } catch (\Exception $e) {
                Log::warning('MissionController@create: auditMissions: ' . $e->getMessage());
            }

            // ── Risques ──────────────────────────────────────────────────
            $risks = $this->tenant()->table('risks')
                ->whereNull('deleted_at')
                ->whereIn('status', ['identified', 'assessed'])
                ->orderBy('code')
                ->select('id', 'code', 'label', 'entity_id', 'process_id')
                ->get()
                ->map(fn($r) => [
                    'id'         => (int) $r->id,
                    'code'       => $r->code,
                    'label'      => $r->label,
                    'entity_id'  => (int) $r->entity_id,
                    'process_id' => (int) $r->process_id,
                ])->toArray();

            // ── Processus ────────────────────────────────────────────────
            $processes = $this->tenant()->table('processes')
                ->select('id', 'code')
                ->get()
                ->map(fn($p) => ['id' => (int)$p->id, 'code' => $p->code])
                ->toArray();

            // ── Assignments ──────────────────────────────────────────────
            $assignments = [];
            try {
                $assignments = $this->tenant()->table('assignments')
                    ->select('entity_id', 'mpa_type', 'mpa_id')
                    ->get()
                    ->map(fn($a) => [
                        'entity_id' => (int) $a->entity_id,
                        'mpa_type'  => $a->mpa_type,
                        'mpa_id'    => (int) $a->mpa_id,
                    ])->toArray();
            } catch (\Exception $e) {}

            // ── Missions créées – historique ─────────────────────────────
            $createdMissions = $this->tenant()->table('missions')
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->limit(50)
                ->select(
                    'id', 'code', 'fpm_number', 'title', 'objective', 'domain',
                    'priority', 'status', 'planned_start_date', 'planned_end_date',
                    'planned_duration_days', 'mission_type_id', 'created_at'
                )
                ->get()
                ->map(function ($m) {
                    $type = $this->tenant()->table('mission_types')
                        ->select('code', 'label')->where('id', $m->mission_type_id)->first();
                    return [
                        'id'                    => (int) $m->id,
                        'code'                  => $m->code,
                        'fpm_number'            => $m->fpm_number,
                        'title'                 => $m->title,
                        'objective'             => $m->objective,
                        'domain'                => $m->domain,
                        'priority'              => $m->priority,
                        'status'                => $m->status,
                        'planned_start_date'    => $m->planned_start_date,
                        'planned_end_date'      => $m->planned_end_date,
                        'planned_duration_days' => (int) $m->planned_duration_days,
                        'type_code'             => $type->code  ?? '?',
                        'type_label'            => $type->label ?? '—',
                        'source'                => $m->fpm_number ? 'SUR DEMANDE' : 'INTERNE',
                        'created_at'            => \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i'),
                    ];
                })->toArray();

            $activeExercise = $exercises[0] ?? null;

            return Inertia::render('dashboards/Audit/MissionA/create', [
                'exercises'       => $exercises,
                'missionTypes'    => $missionTypes,
                'auditTypes'      => $auditTypes->values()->toArray(),
                'entities'        => $entities,
                'competencies'    => $competencies,
                'fpmMissions'     => $fpmMissions,
                'auditMissions'   => $auditMissions,
                'risks'           => $risks,
                'processes'       => $processes,
                'assignments'     => $assignments,
                'createdMissions' => $createdMissions,
                'activeExercise'  => $activeExercise,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ MissionController@create : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire.');
        }
    }

    // =========================================================================
    // STORE — enregistrement dans `missions` (connexion tenant)
    //         + génération AUTOMATIQUE de la programmation et des phases
    // =========================================================================
    /**
     * ⚠️  CORRECTIF FK cross-DB :
     *     created_by/updated_by référencent users.id sur la connexion DEFAULT.
     *     La table `missions` est sur la connexion TENANT (base FRUITIVA).
     *     → On passe NULL pour ces deux champs afin d'éviter
     *       "Cannot add or update a child row: foreign key constraint fails".
     *     Si les utilisateurs sont aussi sur la connexion tenant, remplacer
     *     null par auth()->id().
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'audit_exercise_id'     => 'required|integer',
                'mission_type_id'       => 'required|integer',
                'entity_ids'            => 'required|array|min:1',
                'entity_ids.*'          => 'integer',
                'title'                 => 'required|string|max:255',
                'objective'             => 'nullable|string',
                'domain'                => 'nullable|string|max:120',
                'reference_document'    => 'nullable|string|max:120',
                'priority'              => 'nullable|in:basse,moyenne,haute,critique',
                'planned_start_date'    => 'nullable|date',
                'planned_end_date'      => 'nullable|date|after_or_equal:planned_start_date',
                'planned_duration_days' => 'nullable|integer|min:0',
                'fpm_number'            => 'nullable|string|max:20',
                'risk_ids'              => 'nullable|array',
                'risk_ids.*'            => 'integer',
                'competency_ids'        => 'nullable|array',
                'competency_ids.*'      => 'integer',
            ]);

            // Récupérer type et exercice depuis la connexion tenant
            $type     = $this->tenant()->table('mission_types')->where('id', $validated['mission_type_id'])->first();
            $exercise = $this->tenant()->table('audit_exercises')->where('id', $validated['audit_exercise_id'])->first();

            if (!$type) {
                return back()->withErrors(['mission_type_id' => 'Type de mission invalide.'])->withInput();
            }
            if (!$exercise) {
                return back()->withErrors(['audit_exercise_id' => 'Exercice invalide.'])->withInput();
            }

            // ── Code séquentiel : TYPE-NNN-YY ────────────────────────────
            $yearSuffix = substr((string)($exercise->year ?? date('Y')), -2);
            $count = $this->tenant()->table('missions')
                ->where('mission_type_id', $validated['mission_type_id'])
                ->where('audit_exercise_id', $validated['audit_exercise_id'])
                ->count() + 1;

            $code = $type->code . '-' . str_pad($count, 3, '0', STR_PAD_LEFT) . '-' . $yearSuffix;

            // Anti-collision
            while ($this->tenant()->table('missions')->where('code', $code)->exists()) {
                $count++;
                $code = $type->code . '-' . str_pad($count, 3, '0', STR_PAD_LEFT) . '-' . $yearSuffix;
            }

            // ── Entité principale (conservée pour compat. colonne `entity_id`) ──
            $firstEntityId = (int) $validated['entity_ids'][0];

            // ── Durée ─────────────────────────────────────────────────────
            $duration = (int) ($validated['planned_duration_days'] ?? 0);
            if ($duration === 0
                && !empty($validated['planned_start_date'])
                && !empty($validated['planned_end_date'])
            ) {
                $duration = max(0, (int) ceil(
                    (strtotime($validated['planned_end_date']) - strtotime($validated['planned_start_date'])) / 86400
                ));
            }

            $missionId = null;
            $proposal  = null;

            // Toute l'opération (mission + programmation + phases) dans UNE
            // transaction : soit tout est créé, soit rien ne l'est.
            $this->tenant()->transaction(function () use (
                $validated, $code, $firstEntityId, $duration, &$missionId, &$proposal
            ) {
                // ── Insert dans `missions` ────────────────────────────────
                // created_by = NULL car FK cross-DB (users sur default, missions sur tenant)
                $missionId = $this->tenant()->table('missions')->insertGetId([
                    'code'                  => $code,
                    'fpm_number'            => $validated['fpm_number']        ?? null,
                    'audit_exercise_id'     => $validated['audit_exercise_id'],
                    'mission_type_id'       => $validated['mission_type_id'],
                    'entity_id'             => $firstEntityId,
                    'title'                 => $validated['title'],
                    'objective'             => $validated['objective']          ?? null,
                    'domain'                => $validated['domain']             ?? null,
                    'reference_document'    => $validated['reference_document'] ?? null,
                    'priority'              => $validated['priority']           ?? 'moyenne',
                    'planned_start_date'    => $validated['planned_start_date'] ?? null,
                    'planned_end_date'      => $validated['planned_end_date']   ?? null,
                    'planned_duration_days' => $duration,
                    'status'                => 'brouillon',
                    'created_by'            => null,   // ← NULL : FK cross-DB (users ≠ tenant)
                    'updated_by'            => null,   // ← NULL : même raison
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                // ── Pivots risques (mission_risk) ─────────────────────────
                if (!empty($validated['risk_ids'])) {
                    $this->tenant()->table('mission_risk')->insert(
                        array_map(fn($riskId) => [
                            'mission_id' => $missionId,
                            'risk_id'    => (int) $riskId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], array_unique($validated['risk_ids']))
                    );
                }

                // ── Pivots compétences (mission_competency) ───────────────
                if (!empty($validated['competency_ids'])) {
                    $this->tenant()->table('mission_competency')->insert(
                        array_map(fn($compId) => [
                            'mission_id'    => $missionId,
                            'competency_id' => (int) $compId,
                            'minimum_level' => 1,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ], array_unique($validated['competency_ids']))
                    );
                }

                // ── NOUVEAU : génération automatique de la programmation ──
                // et des affectations de phase, pour TOUTES les entités
                // sélectionnées (et non plus seulement $firstEntityId).
                $proposal = $this->programmationService->autoGenerate(
                    $missionId,
                    $validated['entity_ids']
                );
            });

            Log::info("✅ Mission créée : {$code}", [
                'mission_id'       => $missionId,
                'entity_ids'       => $validated['entity_ids'],
                'risks'            => count($validated['risk_ids'] ?? []),
                'programmation_id' => $proposal['programmation_id'] ?? null,
            ]);

            return redirect()->back()->with('success', "Mission créée avec succès : {$code}")->with('programmation_proposal', $proposal);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('❌ MissionController@store : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage())
                ->withInput();
        }
    }

    // =========================================================================
    // REVIEW — l'utilisateur ajuste la proposition auto-générée
    // =========================================================================
    /**
     * PATCH /missions/{mission}/programmation/{programmation}
     * Body: { adjustments: [{ assignment_id, status, owner_id?, planned_start?, planned_end? }, ...] }
     */
    public function updateProgrammation(Request $request, int $programmationId)
    {
        $validated = $request->validate([
            'adjustments'                  => 'required|array|min:1',
            'adjustments.*.assignment_id'  => 'required|integer',
            'adjustments.*.status'         => 'nullable|in:pending,in_progress,completed,skipped',
            'adjustments.*.owner_id'       => 'nullable|integer',
            'adjustments.*.planned_start'  => 'nullable|date',
            'adjustments.*.planned_end'    => 'nullable|date',
        ]);

        try {
            $this->programmationService->updateProposal($programmationId, $validated['adjustments']);
            return back()->with('success', 'Programmation mise à jour.');
        } catch (\Exception $e) {
            Log::error('❌ MissionController@updateProgrammation : ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la mise à jour de la programmation.');
        }
    }

    // =========================================================================
    // CONFIRM — validation finale : la mission passe en "planifiée"
    // =========================================================================
    public function confirmProgrammation(int $missionId, int $programmationId)
    {
        try {
            $this->programmationService->confirm($missionId, $programmationId);
            return back()->with('success', 'Mission et programmation confirmées.');
        } catch (\Exception $e) {
            Log::error('❌ MissionController@confirmProgrammation : ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la confirmation.');
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Vérifie si une table existe sur la connexion tenant */
    private function tenantTableExists(string $table): bool
    {
        try {
            return !empty($this->tenant()->select("SHOW TABLES LIKE '{$table}'"));
        } catch (\Exception $e) {
            return false;
        }
    }
}
