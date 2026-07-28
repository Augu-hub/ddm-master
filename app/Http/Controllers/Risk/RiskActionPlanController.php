<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RiskActionPlanController — corrigé sur base du schéma SQL fructivia1 réel.
 *
 * Modèle de données (mis à jour) :
 *  - risk_register           1 risque
 *  - risk_recommendations    1 recommandation PAR risque (contrainte unique risk_id+tenant_id)
 *  - risk_action_plans       N plans d'action PAR recommandation (recommendation_id)
 *                            risk_id est conservé en dénormalisé pour compat des requêtes existantes
 *  - risk_action_tasks       N tâches de suivi PAR plan d'action — c'est CE niveau qui porte
 *                            la notion de progression : risk_action_plans.progress est
 *                            recalculé automatiquement depuis ces tâches (jamais saisi à la main).
 *
 * Tables confirmées en base (voir fructivia.sql + migration_recommendations.sql) :
 *  - risk_recommendations    (id, tenant_id, risk_id, content, created_by, updated_by,
 *                             created_at, updated_at, deleted_at)
 *  - risk_action_plans       (id, tenant_id, code, risk_id, recommendation_id, entity_id, title,
 *                             description, action_plan, priority, status,
 *                             assigned_to, target_date, start_date, completion_date,
 *                             progress, cost_estimate, actual_cost, notes,
 *                             is_auto_generated, source_status,
 *                             created_by, updated_by, created_at, updated_at, deleted_at)
 *  - risk_action_tasks       (id, tenant_id, plan_id, title, description,
 *                             assigned_to, target_date, completion_date, status,
 *                             sort_order, created_by, updated_by, created_at,
 *                             updated_at, deleted_at)
 *  - risk_action_comments    (id, tenant_id, plan_id, user_id, comment,
 *                             is_internal, created_at, updated_at, deleted_at)
 *  - risk_action_logs        (id, tenant_id, plan_id, user_id, action,
 *                             description, old_values, new_values,
 *                             created_at, updated_at, deleted_at)
 *  - risk_action_notifications (id, tenant_id, plan_id, user_id, type,
 *                               message, is_read, read_at, created_at,
 *                               updated_at, deleted_at)
 *  - risk_action_statuses    (id, tenant_id, code, label, description, color,
 *                             icon, sort_order, is_active, auto_create_plan,
 *                             default_priority, created_at, updated_at, deleted_at)
 *  - risk_register           (toutes colonnes — voir schéma)
 *  - risk_register_controls  (id, tenant_id, risk_id, code, label, description,
 *                             control_procedure, type, referential_type,
 *                             referential_ref, referential_url, owner,
 *                             mastery_level_id, periodicite, efficacite,
 *                             next_review_date, status, validated_at,
 *                             created_at, updated_at, deleted_at)
 *  - risk_impact_levels, risk_frequency_levels, risk_criticality_zones,
 *    risk_matrix_configs, risk_nomenclatures, risk_appetite_levels,
 *    risk_mastery_levels, risk_decision_history, entities, activities,
 *    processes, macro_processes, functions, function_assignments, users
 *
 * Routes attendues (routes/web.php) :
 *   GET    /m/risk.core/action-plan               → index
 *   GET    /m/risk.core/action-plan/dashboard     → dashboard
 *   POST   /m/risk.core/action-plan               → store
 *   PUT    /m/risk.core/action-plan/{id}          → update
 *   DELETE /m/risk.core/action-plan/{id}          → destroy
 *   GET    /m/risk.core/action-plan/{id}/tasks    → getTasks
 *   POST   /m/risk.core/action-plan/task          → storeTask
 *   PUT    /m/risk.core/action-plan/task/{id}     → updateTask
 *   DELETE /m/risk.core/action-plan/task/{id}     → deleteTask
 *   GET    /m/risk.core/action-plan/{id}/comments → getComments
 *   POST   /m/risk.core/action-plan/comment       → addComment
 *   GET    /m/risk.core/action-plan/{id}/history  → getHistory
 *   POST   /m/risk.core/recommendation            → saveRecommendation   (NOUVEAU)
 */
class RiskActionPlanController extends Controller
{
    // =========================================================================
    //  HELPERS
    // =========================================================================

    private function tid(): int
    {
        return (int)(session('tenant_id') ?? 1);
    }

    private function generateCode(): string
    {
        $tid  = $this->tid();
        $year = now()->format('Y');
        $last = DB::table('risk_action_plans')
            ->where('tenant_id', $tid)
            ->where('code', 'LIKE', "AP-{$year}-%")
            ->orderByDesc('id')
            ->value('code');

        $num = $last ? (intval(substr($last, -4)) + 1) : 1;
        return "AP-{$year}-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    private function logAction(
        int $planId,
        string $action,
        string $description,
        ?string $oldValues = null,
        ?string $newValues = null
    ): void {
        if (!Schema::hasTable('risk_action_logs')) return;

        DB::table('risk_action_logs')->insert([
            'tenant_id'   => $this->tid(),
            'plan_id'     => $planId,
            'user_id'     => auth()->id(),
            'action'      => $action,
            'description' => $description,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function notify(int $planId, int $userId, string $type, string $message): void
    {
        if (!Schema::hasTable('risk_action_notifications')) return;

        DB::table('risk_action_notifications')->insert([
            'tenant_id'  => $this->tid(),
            'plan_id'    => $planId,
            'user_id'    => $userId,
            'type'       => $type,
            'message'    => $message,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getActionPlan(int $id): ?object
    {
        return DB::table('risk_action_plans as ap')
            ->leftJoin('risk_register as r',    'r.id',  '=', 'ap.risk_id')
            ->leftJoin('entities as e',          'e.id',  '=', 'ap.entity_id')
            ->leftJoin('users as u',             'u.id',  '=', 'ap.assigned_to')
            ->leftJoin('users as cb',            'cb.id', '=', 'ap.created_by')
            ->where('ap.id', $id)
            ->whereNull('ap.deleted_at')
            ->select(
                'ap.*',
                'r.code_risk', 'r.libelle as risk_libelle',
                'r.criticality_score', 'r.residual_criticality_score',
                'r.target_criticality_score', 'r.decision',
                'e.name as entity_name', 'e.code_base',
                'u.name as assigned_to_name', 'u.email as assigned_to_email',
                'cb.name as created_by_name'
            )
            ->first();
    }

    /**
     * Retourne l'id de la recommandation d'un risque, en la créant (vide) si elle
     * n'existe pas encore. Garantit "1 risque = 1 recommandation, N plans d'action dedans".
     */
    private function getOrCreateRecommendation(int $riskId): int
    {
        $tid = $this->tid();

        $existing = DB::table('risk_recommendations')
            ->where('risk_id', $riskId)->where('tenant_id', $tid)->whereNull('deleted_at')
            ->first();

        if ($existing) return (int)$existing->id;

        return (int)DB::table('risk_recommendations')->insertGetId([
            'tenant_id'  => $tid,
            'risk_id'    => $riskId,
            'content'    => null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    //  CHARGEMENT DES DONNÉES
    // =========================================================================

    private function loadActionPlans(array $filters = []): array
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_action_plans')) return [];

        $hasReco = Schema::hasTable('risk_recommendations');

        $query = DB::table('risk_action_plans as ap')
            ->leftJoin('risk_register as r',             'r.id',   '=', 'ap.risk_id')
            ->leftJoin('activities as a',                'a.id',   '=', 'r.activity_id')
            ->leftJoin('processes as p',                 'p.id',   '=', 'a.process_id')
            ->leftJoin('macro_processes as mp',          'mp.id',  '=', 'p.macro_process_id')
            ->leftJoin('entities as e',                  'e.id',   '=', 'ap.entity_id')
            ->leftJoin('users as u',                     'u.id',   '=', 'ap.assigned_to')
            ->leftJoin('users as cb',                    'cb.id',  '=', 'ap.created_by')
            // Inhérent
            ->leftJoin('risk_impact_levels as il',       'il.id',  '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl',    'fl.id',  '=', 'r.frequency_level_id')
            ->leftJoin('risk_criticality_zones as cz',   'cz.id',  '=', 'r.criticality_zone_id')
            // Résiduel (INT dans ce schéma → CAST)
            ->leftJoin('risk_impact_levels as ril',      'ril.id', '=', DB::raw('CAST(r.residual_impact_level_id AS UNSIGNED)'))
            ->leftJoin('risk_frequency_levels as rfl',   'rfl.id', '=', DB::raw('CAST(r.residual_frequency_level_id AS UNSIGNED)'))
            ->leftJoin('risk_criticality_zones as rcz',  'rcz.id', '=', DB::raw('CAST(r.residual_criticality_zone_id AS UNSIGNED)'))
            // Cible
            ->leftJoin('risk_impact_levels as til',      'til.id', '=', DB::raw('CAST(r.target_impact_level_id AS UNSIGNED)'))
            ->leftJoin('risk_frequency_levels as tfl',   'tfl.id', '=', DB::raw('CAST(r.target_frequency_level_id AS UNSIGNED)'))
            ->leftJoin('risk_criticality_zones as tcz',  'tcz.id', '=', DB::raw('CAST(r.target_criticality_zone_id AS UNSIGNED)'))
            // Nomenclature
            ->leftJoin('risk_nomenclatures as nom',      'nom.id', '=', 'r.nomenclature_id')
            // Contrôle
            ->leftJoin('risk_register_controls as ctrl', function ($j) use ($tid) {
                $j->on('ctrl.risk_id', '=', 'r.id')
                  ->where('ctrl.tenant_id', $tid)
                  ->whereNull('ctrl.deleted_at');
            })
            ->where('ap.tenant_id', $tid)
            ->whereNull('ap.deleted_at');

        if ($hasReco) {
            $query->leftJoin('risk_recommendations as rec', 'rec.id', '=', 'ap.recommendation_id');
        }

        if (!empty($filters['status']))    $query->where('ap.status',   $filters['status']);
        if (!empty($filters['priority']))  $query->where('ap.priority', $filters['priority']);
        if (!empty($filters['risk_id']))   $query->where('ap.risk_id',  (int)$filters['risk_id']);
        if (!empty($filters['entity_id'])) $query->where('ap.entity_id',(int)$filters['entity_id']);

        $hasRecoCol = Schema::hasColumn('risk_action_plans', 'recommendation_id');

        return $query
            ->orderByRaw("FIELD(ap.priority,'critical','high','medium','low')")
            ->orderBy('ap.target_date')
            ->select([
                // Plan
                'ap.id', 'ap.code', 'ap.risk_id',
                $hasRecoCol ? 'ap.recommendation_id' : DB::raw('NULL as recommendation_id'),
                'ap.entity_id',
                'ap.title', 'ap.description', 'ap.action_plan',
                'ap.priority', 'ap.status', 'ap.assigned_to',
                'ap.target_date', 'ap.start_date', 'ap.completion_date',
                'ap.progress', 'ap.cost_estimate', 'ap.actual_cost',
                'ap.notes', 'ap.is_auto_generated', 'ap.source_status',
                'ap.created_by', 'ap.created_at', 'ap.updated_at',
                // Recommandation
                $hasReco ? 'rec.content as recommendation_content' : DB::raw('NULL as recommendation_content'),
                $hasReco ? 'rec.updated_at as recommendation_updated_at' : DB::raw('NULL as recommendation_updated_at'),
                // Risque
                'r.code_risk', 'r.libelle as risk_libelle',
                'r.causes', 'r.consequences', 'r.consequences_autres_processus',
                'r.controles_existants', 'r.plan_traitement',
                'r.owner as risk_owner', 'r.statut as risk_statut',
                'r.decision', 'r.risque_realise', 'r.cout_risque',
                'r.vraisemblance_apparition', 'r.critere_risque',
                // Hiérarchie
                'a.code as activity_code', 'a.name as activity_name',
                'p.code as process_code', 'p.name as process_name',
                'mp.code as macro_code', 'mp.name as macro_name', 'mp.kind as macro_kind',
                // Entité
                'e.name as entity_name', 'e.code_base as entity_code',
                // Utilisateurs
                'u.name as assigned_to_name', 'u.email as assigned_to_email',
                'cb.name as created_by_name',
                // Nomenclature
                'nom.code as nomenclature_code', 'nom.label as nomenclature_label',
                'nom.level as nomenclature_level', 'nom.type_code as nomenclature_type',
                // Inhérent
                'r.impact_level_id', 'il.label as impact_label',
                'il.score as impact_score', 'il.color_code as impact_color',
                'r.frequency_level_id', 'fl.label as frequency_label',
                'fl.score as frequency_score', 'fl.color_code as frequency_color',
                'fl.recurrence as frequency_recurrence',
                'r.criticality_score', 'r.criticality_zone_id as zone_id',
                'cz.label as zone_label', 'cz.color_code as zone_color',
                // Résiduel
                'r.residual_impact_level_id', 'ril.label as residual_impact_label',
                'ril.score as residual_impact_score',
                'r.residual_frequency_level_id', 'rfl.label as residual_frequency_label',
                'rfl.score as residual_frequency_score',
                'r.residual_criticality_score', 'r.residual_criticality_zone_id as residual_zone_id',
                'rcz.label as residual_zone_label', 'rcz.color_code as residual_zone_color',
                // Cible
                'r.target_impact_level_id', 'til.label as target_impact_label',
                'til.score as target_impact_score',
                'r.target_frequency_level_id', 'tfl.label as target_frequency_label',
                'tfl.score as target_frequency_score',
                'r.target_criticality_score', 'r.target_criticality_zone_id as target_zone_id',
                'tcz.label as target_zone_label', 'tcz.color_code as target_zone_color',
                'r.target_date as risk_target_date', 'r.action_plan as risk_action_plan',
                // Contrôle
                'ctrl.id as control_id', 'ctrl.code as control_code',
                'ctrl.type as control_type', 'ctrl.status as control_status',
                'ctrl.owner as control_owner', 'ctrl.efficacite as control_efficacite',
                'ctrl.periodicite as control_periodicite',
                'ctrl.referential_type', 'ctrl.referential_ref',
                'ctrl.next_review_date as control_next_review',
                // Drapeaux
                DB::raw('IF(r.impact_level_id IS NOT NULL, 1, 0) as has_inherent'),
                DB::raw('IF(ctrl.id IS NOT NULL, 1, 0) as has_control'),
                DB::raw('IF(r.residual_impact_level_id IS NOT NULL, 1, 0) as has_residual'),
                DB::raw('IF(r.target_impact_level_id IS NOT NULL, 1, 0) as has_target'),
                DB::raw('IF(r.decision IS NOT NULL, 1, 0) as has_decision'),
                DB::raw('IF(r.risque_realise = 1, 1, 0) as is_realized')
            ])
            ->get()
            ->map(fn($row) => (array)$row)
            ->toArray();
    }

    private function loadAllRisks(): array
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_register')) return [];

        return DB::table('risk_register as r')
            ->leftJoin('activities as a',              'a.id',  '=', 'r.activity_id')
            ->leftJoin('processes as p',               'p.id',  '=', 'a.process_id')
            ->leftJoin('macro_processes as mp',        'mp.id', '=', 'p.macro_process_id')
            ->leftJoin('risk_impact_levels as il',     'il.id', '=', 'r.impact_level_id')
            ->leftJoin('risk_criticality_zones as cz', 'cz.id', '=', 'r.criticality_zone_id')
            ->leftJoin('risk_nomenclatures as nom',    'nom.id','=', 'r.nomenclature_id')
            ->where('r.tenant_id', $tid)
            ->whereNull('r.deleted_at')
            ->whereNull('r.moved_to_library_at')
            ->select(
                'r.id', 'r.code_risk', 'r.libelle', 'r.statut', 'r.decision',
                'r.impact_level_id', 'il.score as impact_score',
                'r.criticality_score', 'cz.label as zone_label', 'cz.color_code as zone_color',
                'r.residual_criticality_score', 'r.target_criticality_score',
                'a.code as activity_code', 'a.name as activity_name',
                'p.code as process_code', 'p.name as process_name',
                'mp.code as macro_code', 'mp.name as macro_name',
                'nom.code as nomenclature_code', 'nom.label as nomenclature_label'
            )
            ->orderBy('r.id')
            ->get()
            ->map(fn($row) => (array)$row)
            ->toArray();
    }

    /**
     * Une recommandation par risque (id, risk_id, content, updated_at).
     * Utilisée côté front pour afficher/éditer la recommandation même quand
     * un risque n'a pas encore de plan d'action.
     */
    private function loadRecommendations(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_recommendations')) return [];

        return DB::table('risk_recommendations')
            ->where('tenant_id', $tid)->whereNull('deleted_at')
            ->select('id', 'risk_id', 'content', 'updated_at')
            ->get()
            ->map(fn($row) => (array)$row)
            ->toArray();
    }

    private function getStats(): array
    {
        $tid = $this->tid();

        $empty = [
            'total' => 0, 'pending' => 0, 'in_progress' => 0, 'review' => 0,
            'completed' => 0, 'cancelled' => 0, 'blocked' => 0, 'overdue' => 0,
            'high_priority' => 0, 'critical' => 0,
            'risks_total' => 0, 'risks_evaluated' => 0, 'risks_controlled' => 0,
            'risks_with_residual' => 0, 'risks_with_target' => 0,
        ];

        if (!Schema::hasTable('risk_action_plans')) return $empty;

        $base = fn() => DB::table('risk_action_plans')
            ->where('tenant_id', $tid)->whereNull('deleted_at');

        $risksBase = DB::table('risk_register')
            ->where('tenant_id', $tid)->whereNull('deleted_at')->whereNull('moved_to_library_at');

        $risksControlled = 0;
        if (Schema::hasTable('risk_register_controls')) {
            $risksControlled = DB::table('risk_register_controls as rc')
                ->join('risk_register as r', 'r.id', '=', 'rc.risk_id')
                ->where('rc.tenant_id', $tid)
                ->whereNull('rc.deleted_at')->whereNull('r.deleted_at')->whereNull('r.moved_to_library_at')
                ->distinct('rc.risk_id')->count('rc.risk_id');
        }

        return [
            'total'               => ($base)()->count(),
            'pending'             => ($base)()->where('status', 'pending')->count(),
            'in_progress'         => ($base)()->where('status', 'in_progress')->count(),
            'review'              => ($base)()->where('status', 'review')->count(),
            'completed'           => ($base)()->where('status', 'completed')->count(),
            'cancelled'           => ($base)()->where('status', 'cancelled')->count(),
            'blocked'             => ($base)()->where('status', 'blocked')->count(),
            'overdue'             => ($base)()->whereNotIn('status', ['completed','cancelled'])
                                              ->where('target_date', '<', now()->toDateString())->count(),
            'high_priority'       => ($base)()->where('priority','high')
                                              ->whereNotIn('status',['completed','cancelled'])->count(),
            'critical'            => ($base)()->where('priority','critical')
                                              ->whereNotIn('status',['completed','cancelled'])->count(),
            'risks_total'         => (clone $risksBase)->count(),
            'risks_evaluated'     => (clone $risksBase)->whereNotNull('impact_level_id')->count(),
            'risks_controlled'    => $risksControlled,
            'risks_with_residual' => (clone $risksBase)->whereNotNull('residual_impact_level_id')->count(),
            'risks_with_target'   => (clone $risksBase)->whereNotNull('target_impact_level_id')->count(),
        ];
    }

    // entities n'a PAS de deleted_at dans ce schéma
    private function getEntities(): array
    {
        if (!Schema::hasTable('entities')) return [];
        return DB::table('entities')
            ->select('id', 'name', 'code_base', 'level', 'parent_id')
            ->orderBy('level')->orderBy('name')->get()->toArray();
    }

    private function getUsers(): array
    {
        if (!Schema::hasTable('users')) return [];
        return DB::table('users')->where('status', 'active')
            ->select('id', 'name', 'email', 'job_title')->orderBy('name')->get()->toArray();
    }

    private function getDecisionStatuses(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_statuses')) return [];
        return DB::table('risk_action_statuses')
            ->where('tenant_id', $tid)->where('is_active', 1)->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get(['id','code','label','color','icon','sort_order','auto_create_plan','default_priority'])
            ->toArray();
    }

    private function getPriorities(): array
    {
        return [
            ['value' => 'critical', 'label' => 'Critique', 'color' => '#ef4444'],
            ['value' => 'high',     'label' => 'Haute',    'color' => '#f97316'],
            ['value' => 'medium',   'label' => 'Moyenne',  'color' => '#eab308'],
            ['value' => 'low',      'label' => 'Basse',    'color' => '#22c55e'],
        ];
    }

    private function getStatuses(): array
    {
        return [
            ['value' => 'pending',     'label' => 'À faire',  'color' => '#94a3b8'],
            ['value' => 'in_progress', 'label' => 'En cours', 'color' => '#3b82f6'],
            ['value' => 'review',      'label' => 'En revue', 'color' => '#8b5cf6'],
            ['value' => 'completed',   'label' => 'Terminé',  'color' => '#22c55e'],
            ['value' => 'cancelled',   'label' => 'Annulé',   'color' => '#ef4444'],
            ['value' => 'blocked',     'label' => 'Bloqué',   'color' => '#dc2626'],
        ];
    }

    // =========================================================================
    //  VUES INERTIA
    // =========================================================================

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'risk_id', 'entity_id']);

        return Inertia::render('dashboards/Risk/ActionPlan/Index', [
            'actionPlans'      => $this->loadActionPlans($filters),
            'allRisks'         => $this->loadAllRisks(),
            'recommendations'  => $this->loadRecommendations(),
            'stats'            => $this->getStats(),
            'entities'         => $this->getEntities(),
            'users'            => $this->getUsers(),
            'priorities'       => $this->getPriorities(),
            'statuses'         => $this->getStatuses(),
            'decisionStatuses' => $this->getDecisionStatuses(),
            'filters'          => $filters,
        ]);
    }

    /**
     * Suivi des plans d'action (dashboard opérationnel) — sert ActionTracking.vue.
     * Réutilise les chargeurs défensifs (données 100 % dynamiques du tenant) et
     * ajoute le compte de tâches par plan (colonne « Tâches » + résumé).
     */
    public function tracking(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'risk_id', 'entity_id']);
        $plans   = $this->loadActionPlans($filters);
        $counts  = $this->loadTaskCounts();

        foreach ($plans as &$p) {
            $c = $counts[$p['id']] ?? ['total' => 0, 'completed' => 0];
            $p['tasks_total']     = $c['total'];
            $p['tasks_completed'] = $c['completed'];
        }
        unset($p);

        return Inertia::render('dashboards/Risk/ActionTracking', [
            'actionPlans' => $plans,
            'allRisks'    => $this->loadAllRisks(),
            'stats'       => $this->getStats(),
            'priorities'  => $this->getPriorities(),
            'statuses'    => $this->getStatuses(),
        ]);
    }

    /** Compte des tâches (total / terminées) par plan d'action. */
    private function loadTaskCounts(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_tasks')) return [];

        return DB::table('risk_action_tasks')
            ->where('tenant_id', $tid)->whereNull('deleted_at')
            ->groupBy('plan_id')
            ->selectRaw("plan_id, COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed")
            ->get()
            ->keyBy('plan_id')
            ->map(fn ($r) => ['total' => (int) $r->total, 'completed' => (int) $r->completed])
            ->toArray();
    }

    public function dashboard(Request $request): Response
    {
        return Inertia::render('dashboards/Risk/ActionPlan/Dashboard', [
            'stats'             => $this->getStats(),
            'trends'            => $this->getTrends(),
            'byEntity'          => $this->getStatsByEntity(),
            'byPriority'        => $this->getStatsByPriority(),
            'upcomingDeadlines' => $this->getUpcomingDeadlines(),
        ]);
    }

    // =========================================================================
    //  RECOMMANDATION (1 par risque)
    // =========================================================================

    public function saveRecommendation(Request $request): JsonResponse
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_recommendations')) {
            return response()->json(['success' => false, 'message' => "Table risk_recommendations inexistante — exécutez migration_recommendations.sql"], 500);
        }

        $v = $request->validate([
            'risk_id' => 'required|integer|exists:risk_register,id',
            'content' => 'nullable|string',
        ]);

        $existing = DB::table('risk_recommendations')
            ->where('risk_id', $v['risk_id'])->where('tenant_id', $tid)->whereNull('deleted_at')
            ->first();

        if ($existing) {
            DB::table('risk_recommendations')->where('id', $existing->id)->update([
                'content'    => $v['content'],
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
            $id = $existing->id;
        } else {
            $id = DB::table('risk_recommendations')->insertGetId([
                'tenant_id'  => $tid,
                'risk_id'    => $v['risk_id'],
                'content'    => $v['content'],
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recommandation enregistrée avec succès',
            'id'      => $id,
        ]);
    }

    // =========================================================================
    //  CRUD — PLANS D'ACTION (rattachés à la recommandation du risque)
    // =========================================================================

    public function store(Request $request): JsonResponse
    {
        $tid = $this->tid();

        $v = $request->validate([
            'risk_id'         => 'required|integer|exists:risk_register,id',
            'entity_id'       => 'nullable|integer|exists:entities,id',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'action_plan'     => 'nullable|string',
            'priority'        => 'required|in:critical,high,medium,low',
            'status'          => 'required|in:pending,in_progress,review,completed,cancelled,blocked',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'target_date'     => 'required|date',
            'start_date'      => 'nullable|date',
            'completion_date' => 'nullable|date',
            'cost_estimate'   => 'nullable|numeric|min:0',
            'actual_cost'     => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);
        // NB : 'progress' n'est volontairement pas accepté ici — la progression
        // n'existe qu'au niveau du suivi (tâches), jamais saisie manuellement.

        // Un risque = une recommandation. On la crée si elle n'existe pas encore,
        // et tout nouveau plan d'action y est automatiquement rattaché.
        // (Les deux vérifications Schema sont nécessaires tant que
        // migration_recommendations.sql n'a pas été exécutée sur cette base.)
        $extra = [
            'tenant_id'  => $tid,
            'code'       => $this->generateCode(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasTable('risk_recommendations') && Schema::hasColumn('risk_action_plans', 'recommendation_id')) {
            $extra['recommendation_id'] = $this->getOrCreateRecommendation((int)$v['risk_id']);
        }

        $id = DB::table('risk_action_plans')->insertGetId(array_merge($v, $extra));

        $this->logAction($id, 'created', "Plan d'action créé");

        if (!empty($v['assigned_to'])) {
            $this->notify($id, $v['assigned_to'], 'plan_created',
                "Vous avez été assigné au plan d'action : " . $v['title']);
        }

        return response()->json([
            'success' => true,
            'message' => "Plan d'action créé avec succès",
            'id'      => $id,
            'plan'    => $this->getActionPlan($id),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tid  = $this->tid();
        $plan = DB::table('risk_action_plans')
            ->where('id', $id)->where('tenant_id', $tid)->whereNull('deleted_at')->first();

        if (!$plan) return response()->json(['success' => false, 'message' => "Plan d'action introuvable"], 404);

        $v = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'description'     => 'nullable|string',
            'action_plan'     => 'nullable|string',
            'priority'        => 'sometimes|in:critical,high,medium,low',
            'status'          => 'sometimes|in:pending,in_progress,review,completed,cancelled,blocked',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'entity_id'       => 'nullable|integer|exists:entities,id',
            'target_date'     => 'nullable|date',
            'start_date'      => 'nullable|date',
            'completion_date' => 'nullable|date',
            'cost_estimate'   => 'nullable|numeric|min:0',
            'actual_cost'     => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);
        // 'progress' toujours exclu du payload accepté : recalculé uniquement
        // par updatePlanProgress() à partir des tâches de suivi.

        if (isset($v['status']) && $v['status'] === 'completed' && $plan->status !== 'completed') {
            $v['completion_date'] = $v['completion_date'] ?? now()->toDateString();
        }

        $old = json_encode((array)$plan);
        $v['updated_at'] = now();
        $v['updated_by'] = auth()->id();

        DB::table('risk_action_plans')->where('id', $id)->update($v);
        $this->logAction($id, 'updated', "Plan d'action mis à jour", $old, json_encode($v));

        if (isset($v['status']) && $v['status'] !== $plan->status && $plan->assigned_to) {
            $this->notify($id, $plan->assigned_to, 'status_changed',
                "Statut du plan d'action changé : {$plan->status} → {$v['status']}");
        }

        return response()->json([
            'success' => true,
            'message' => "Plan d'action mis à jour avec succès",
            'plan'    => $this->getActionPlan($id),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tid  = $this->tid();
        $plan = DB::table('risk_action_plans')
            ->where('id', $id)->where('tenant_id', $tid)->whereNull('deleted_at')->first();

        if (!$plan) return response()->json(['success' => false, 'message' => "Plan d'action introuvable"], 404);

        if (Schema::hasTable('risk_action_tasks')) {
            DB::table('risk_action_tasks')
                ->where('plan_id', $id)->where('tenant_id', $tid)->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }

        DB::table('risk_action_plans')->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        $this->logAction($id, 'deleted', "Plan d'action supprimé");

        return response()->json(['success' => true, 'message' => "Plan d'action supprimé avec succès"]);
    }

    // =========================================================================
    //  TÂCHES DE SUIVI — c'est ce niveau qui porte la progression
    // =========================================================================

    public function getTasks(int $planId): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_tasks'))
            return response()->json(['success' => true, 'tasks' => []]);

        $tasks = DB::table('risk_action_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.assigned_to')
            ->where('t.plan_id', $planId)->where('t.tenant_id', $tid)->whereNull('t.deleted_at')
            ->orderBy('t.sort_order')->orderBy('t.created_at')
            ->select('t.*', 'u.name as assigned_to_name', 'u.email as assigned_to_email')
            ->get()->map(fn($r) => (array)$r)->toArray();

        return response()->json(['success' => true, 'tasks' => $tasks]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_tasks'))
            return response()->json(['success' => false, 'message' => "Table risk_action_tasks inexistante"], 500);

        $v = $request->validate([
            'plan_id'     => 'required|integer|exists:risk_action_plans,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'target_date' => 'nullable|date',
            'status'      => 'required|in:pending,in_progress,completed,cancelled',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $id = DB::table('risk_action_tasks')->insertGetId(array_merge($v, [
            'tenant_id'  => $tid,
            'created_by' => auth()->id(),
            'sort_order' => $v['sort_order'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->logAction($v['plan_id'], 'task_added', "Suivi ajouté : " . $v['title']);
        $this->updatePlanProgress($v['plan_id']);

        return response()->json(['success' => true, 'message' => 'Suivi créé avec succès', 'id' => $id]);
    }

    public function updateTask(Request $request, int $id): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_tasks'))
            return response()->json(['success' => false, 'message' => "Table risk_action_tasks inexistante"], 500);

        $task = DB::table('risk_action_tasks')
            ->where('id', $id)->where('tenant_id', $tid)->whereNull('deleted_at')->first();
        if (!$task) return response()->json(['success' => false, 'message' => 'Suivi introuvable'], 404);

        $v = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'description'     => 'nullable|string',
            'assigned_to'     => 'nullable|integer|exists:users,id',
            'target_date'     => 'nullable|date',
            'status'          => 'sometimes|in:pending,in_progress,completed,cancelled',
            'sort_order'      => 'nullable|integer|min:0',
            'completion_date' => 'nullable|date',
        ]);

        if (isset($v['status']) && $v['status'] === 'completed')
            $v['completion_date'] = $v['completion_date'] ?? now()->toDateString();

        $v['updated_at'] = now();
        $v['updated_by'] = auth()->id();

        DB::table('risk_action_tasks')->where('id', $id)->update($v);
        $this->updatePlanProgress($task->plan_id);
        $this->logAction($task->plan_id, 'task_updated', "Suivi mis à jour : " . ($v['title'] ?? $task->title));

        return response()->json(['success' => true, 'message' => 'Suivi mis à jour avec succès']);
    }

    public function deleteTask(int $id): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_tasks'))
            return response()->json(['success' => false, 'message' => "Table risk_action_tasks inexistante"], 500);

        $task = DB::table('risk_action_tasks')
            ->where('id', $id)->where('tenant_id', $tid)->whereNull('deleted_at')->first();
        if (!$task) return response()->json(['success' => false, 'message' => 'Suivi introuvable'], 404);

        DB::table('risk_action_tasks')->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        $this->updatePlanProgress($task->plan_id);
        $this->logAction($task->plan_id, 'task_deleted', "Suivi supprimé : " . $task->title);

        return response()->json(['success' => true, 'message' => 'Suivi supprimé avec succès']);
    }

    /**
     * La progression (risk_action_plans.progress) est TOUJOURS calculée ici,
     * à partir des tâches de suivi terminées. Elle n'est jamais un champ
     * saisissable dans le formulaire de plan d'action (voir store()/update()).
     */
    private function updatePlanProgress(int $planId): void
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_tasks')) return;

        $tasks = DB::table('risk_action_tasks')
            ->where('plan_id', $planId)->where('tenant_id', $tid)->whereNull('deleted_at')
            ->get(['status']);

        if ($tasks->isEmpty()) return;

        $total    = $tasks->count();
        $done     = $tasks->where('status', 'completed')->count();
        $progress = (int)round(($done / $total) * 100);
        $update   = ['progress' => $progress, 'updated_at' => now()];

        if ($progress === 100) {
            $plan = DB::table('risk_action_plans')
                ->where('id', $planId)->where('tenant_id', $tid)->first();
            if ($plan && !in_array($plan->status, ['completed', 'cancelled'])) {
                $update['status']          = 'completed';
                $update['completion_date'] = now()->toDateString();
            }
        }

        DB::table('risk_action_plans')->where('id', $planId)->where('tenant_id', $tid)->update($update);
    }

    // =========================================================================
    //  COMMENTAIRES
    // =========================================================================

    public function getComments(int $planId): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_comments'))
            return response()->json(['success' => true, 'comments' => []]);

        $comments = DB::table('risk_action_comments as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.plan_id', $planId)->where('c.tenant_id', $tid)->whereNull('c.deleted_at')
            ->orderByDesc('c.created_at')
            ->select('c.*', 'u.name as user_name', 'u.email as user_email')
            ->get()->map(fn($r) => (array)$r)->toArray();

        return response()->json(['success' => true, 'comments' => $comments]);
    }

    public function addComment(Request $request): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_comments'))
            return response()->json(['success' => false, 'message' => "Table risk_action_comments inexistante"], 500);

        $v = $request->validate([
            'plan_id'     => 'required|integer|exists:risk_action_plans,id',
            'comment'     => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        $id = DB::table('risk_action_comments')->insertGetId([
            'tenant_id'   => $tid,
            'plan_id'     => $v['plan_id'],
            'user_id'     => auth()->id(),
            'comment'     => $v['comment'],
            'is_internal' => $v['is_internal'] ?? false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->logAction($v['plan_id'], 'comment_added', 'Commentaire ajouté');

        return response()->json(['success' => true, 'message' => 'Commentaire ajouté avec succès', 'id' => $id]);
    }

    // =========================================================================
    //  HISTORIQUE
    // =========================================================================

    public function getHistory(int $planId): JsonResponse
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_logs'))
            return response()->json(['success' => true, 'history' => []]);

        $history = DB::table('risk_action_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.plan_id', $planId)->where('l.tenant_id', $tid)->whereNull('l.deleted_at')
            ->orderByDesc('l.created_at')
            ->select('l.*', 'u.name as user_name', 'u.email as user_email')
            ->get()->map(fn($r) => (array)$r)->toArray();

        return response()->json(['success' => true, 'history' => $history]);
    }

    // =========================================================================
    //  STATISTIQUES DASHBOARD
    // =========================================================================

    private function getTrends(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_plans')) return [];

        return DB::table('risk_action_plans')
            ->where('tenant_id', $tid)->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('month')->orderBy('month')->get()->toArray();
    }

    private function getStatsByEntity(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_plans') || !Schema::hasTable('entities')) return [];

        // entities n'a PAS de deleted_at
        return DB::table('risk_action_plans as ap')
            ->leftJoin('entities as e', 'e.id', '=', 'ap.entity_id')
            ->where('ap.tenant_id', $tid)->whereNull('ap.deleted_at')
            ->select('e.id', 'e.name', 'e.code_base')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN ap.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN ap.priority = 'critical' THEN 1 ELSE 0 END) as critical")
            ->selectRaw("SUM(CASE WHEN ap.priority = 'high' THEN 1 ELSE 0 END) as high")
            ->groupBy('e.id', 'e.name', 'e.code_base')->get()->toArray();
    }

    private function getStatsByPriority(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_plans')) return [];

        return DB::table('risk_action_plans')
            ->where('tenant_id', $tid)->whereNull('deleted_at')
            ->select('priority')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('priority')->get()->toArray();
    }

    private function getUpcomingDeadlines(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_action_plans')) return [];

        return DB::table('risk_action_plans as ap')
            ->leftJoin('risk_register as r', 'r.id', '=', 'ap.risk_id')
            ->leftJoin('entities as e',       'e.id', '=', 'ap.entity_id')
            ->leftJoin('users as u',          'u.id', '=', 'ap.assigned_to')
            ->where('ap.tenant_id', $tid)->whereNull('ap.deleted_at')
            ->whereNotIn('ap.status', ['completed', 'cancelled'])
            ->where('ap.target_date', '>=', now()->toDateString())
            ->where('ap.target_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('ap.target_date')->limit(15)
            ->select(
                'ap.id', 'ap.title', 'ap.target_date', 'ap.priority', 'ap.status', 'ap.progress',
                'r.code_risk', 'e.name as entity_name', 'u.name as assigned_to_name'
            )
            ->get()->toArray();
    }
}