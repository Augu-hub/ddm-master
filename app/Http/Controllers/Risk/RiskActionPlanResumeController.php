<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use App\Models\RiskActionPlan;
use App\Models\RiskActionTask;
use App\Models\RiskActionComment;
use App\Models\RiskActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * RiskActionPlanResumeController
 * 
 * Gère la reprise (suivi et modification) des plans d'action existants
 * 
 * - Afficher un plan avec toutes ses relations
 * - Modifier le plan et ses propriétés
 * - Ajouter/modifier/supprimer des tâches
 * - Ajouter/supprimer des commentaires
 * - Consulter l'historique complet
 * - Basé sur la structure SQL: risk_action_plans, risk_action_tasks, risk_action_comments, risk_action_logs
 * ═══════════════════════════════════════════════════════════════════════════
 */
class RiskActionPlanResumeController extends Controller
{
    /**
     * Affiche la vue de reprise d'un plan d'action
     * 
     * GET /risk/plan/{planId}
     * 
     * @param int $planId
     * @return \Inertia\Response
     */
    public function show($planId)
    {
        $tenantId = auth()->user()->tenant_id;

        // Récupérer le plan avec toutes ses relations
        $plan = RiskActionPlan::with([
            'risk.macro',
            'risk.process',
            'risk.activity',
            'assignedTo',
            'entity',
            'tasks' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'comments' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            },
            'logs' => function ($query) {
                $query->with('user')->orderBy('created_at', 'desc')->limit(50);
            },
        ])
            ->where('id', $planId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $risk = $plan->risk;

        // Récupérer les utilisateurs pour sélection
        $users = \App\Models\User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        // Formater les données pour le frontend
        $planData = [
            'id' => $plan->id,
            'code' => $plan->code,
            'risk_id' => $plan->risk_id,
            'title' => $plan->title,
            'description' => $plan->description,
            'action_plan' => $plan->action_plan,
            'priority' => $plan->priority,
            'status' => $plan->status,
            'assigned_to' => $plan->assigned_to,
            'assigned_to_name' => $plan->assignedTo?->name,
            'assigned_to_email' => $plan->assignedTo?->email,
            'entity_id' => $plan->entity_id,
            'entity_name' => $plan->entity?->name,
            'target_date' => $plan->target_date?->format('Y-m-d'),
            'start_date' => $plan->start_date?->format('Y-m-d'),
            'completion_date' => $plan->completion_date?->format('Y-m-d'),
            'progress' => $plan->progress,
            'cost_estimate' => $plan->cost_estimate,
            'actual_cost' => $plan->actual_cost,
            'notes' => $plan->notes,
            'is_auto_generated' => $plan->is_auto_generated,
            'source_status' => $plan->source_status,
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at,
        ];

        $riskData = [
            'id' => $risk->id,
            'code_risk' => $risk->code_risk,
            'libelle' => $risk->libelle,
            'macro_id' => $risk->macro_id,
            'process_id' => $risk->process_id,
            'activity_id' => $risk->activity_id,
            'criticality_score' => $risk->criticality_score,
            'zone_color' => $risk->zone_color,
            'description' => $risk->description,
        ];

        // Tâches formatées
        $tasksData = $plan->tasks->map(fn($t) => [
            'id' => $t->id,
            'plan_id' => $t->plan_id,
            'title' => $t->title,
            'description' => $t->description,
            'assigned_to' => $t->assigned_to,
            'assigned_to_name' => $t->assignedToUser?->name,
            'target_date' => $t->target_date?->format('Y-m-d'),
            'completion_date' => $t->completion_date?->format('Y-m-d'),
            'status' => $t->status,
            'sort_order' => $t->sort_order,
            'created_at' => $t->created_at,
        ])->toArray();

        // Commentaires formatés
        $commentsData = $plan->comments->map(fn($c) => [
            'id' => $c->id,
            'plan_id' => $c->plan_id,
            'comment' => $c->comment,
            'is_internal' => $c->is_internal,
            'user_id' => $c->user_id,
            'user_name' => $c->user?->name,
            'user_avatar' => $c->user?->avatar_url,
            'created_at' => $c->created_at,
        ])->toArray();

        // Historique formaté
        $historyData = $plan->logs->map(fn($h) => [
            'id' => $h->id,
            'plan_id' => $h->plan_id,
            'action' => $h->action,
            'description' => $h->description,
            'user_id' => $h->user_id,
            'user_name' => $h->user?->name,
            'created_at' => $h->created_at,
        ])->toArray();

        return Inertia::render('dashboards/Risk/PlanActionResume', [
            'planId' => $planId,
            'plan' => $planData,
            'risk' => $riskData,
            'tasks' => $tasksData,
            'comments' => $commentsData,
            'history' => $historyData,
            'users' => $users,
            'riskZoneColor' => $risk->zone_color ?? '#6b7280',
            'riskZone' => $this->getRiskZone($risk),
        ]);
    }

    /**
     * Modifie les propriétés du plan d'action
     * 
     * PUT /m/risk.core/action-plan/{id}
     * 
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $plan = RiskActionPlan::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_plan' => 'required|string',
            'priority' => 'required|in:critical,high,medium,low',
            'status' => 'required|in:pending,in_progress,review,completed,cancelled,blocked',
            'target_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $plan->status;
        $oldProgress = $plan->progress;

        // Enregistrer les modifications
        $changes = [];
        foreach ($validated as $key => $value) {
            if ($plan->{$key} != $value) {
                $changes[$key] = [
                    'old' => $plan->{$key},
                    'new' => $value,
                ];
            }
        }

        // Mettre à jour
        $plan->update($validated + [
            'updated_by' => auth()->id(),
        ]);

        // Si status = completed, enregistrer la date de complétion
        if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
            $plan->update(['completion_date' => now()]);
            $changes['completion_date'] = ['old' => null, 'new' => now()];
        }

        // Enregistrer dans l'historique
        if (count($changes) > 0) {
            $changeDesc = "Plan d'action mis à jour";
            foreach ($changes as $field => $change) {
                $changeDesc .= "\n- {$field}: {$change['old']} → {$change['new']}";
            }

            RiskActionLog::create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->id,
                'action' => 'updated',
                'description' => $changeDesc,
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan mis à jour',
            'data' => $plan,
        ]);
    }

    /**
     * Ajoute une tâche au plan
     * 
     * POST /m/risk.core/action-plan/{planId}/task
     * 
     * @param int $planId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTask($planId, Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $plan = RiskActionPlan::where('id', $planId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'target_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        // Obtenir le prochain ordre
        $maxSort = RiskActionTask::where('plan_id', $planId)->max('sort_order') ?? 0;

        $task = RiskActionTask::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'target_date' => $validated['target_date'] ?? null,
            'status' => $validated['status'],
            'sort_order' => $maxSort + 1,
            'created_by' => auth()->id(),
        ]);

        // Log
        RiskActionLog::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'action' => 'task_added',
            'description' => "Tâche ajoutée: {$task->title}",
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tâche créée',
            'data' => $task->load('assignedToUser'),
        ], 201);
    }

    /**
     * Modifie une tâche
     * 
     * PUT /m/risk.core/action-plan/task/{taskId}
     * 
     * @param int $taskId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTask($taskId, Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $task = RiskActionTask::where('id', $taskId)
            ->whereHas('plan', fn($q) => $q->where('tenant_id', $tenantId))
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'target_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $task->update($validated);

        // Log
        RiskActionLog::create([
            'tenant_id' => $tenantId,
            'plan_id' => $task->plan_id,
            'action' => 'task_updated',
            'description' => "Tâche mise à jour: {$task->title}",
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tâche mise à jour',
            'data' => $task,
        ]);
    }

    /**
     * Bascule l'état d'une tâche (completed/pending)
     * 
     * PUT /m/risk.core/action-plan/task/{taskId}/toggle
     * 
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleTask($taskId)
    {
        $tenantId = auth()->user()->tenant_id;

        $task = RiskActionTask::where('id', $taskId)
            ->whereHas('plan', fn($q) => $q->where('tenant_id', $tenantId))
            ->firstOrFail();

        $oldStatus = $task->status;
        $newStatus = $task->status === 'completed' ? 'pending' : 'completed';
        $completionDate = $newStatus === 'completed' ? now() : null;

        $task->update([
            'status' => $newStatus,
            'completion_date' => $completionDate,
        ]);

        // Log
        RiskActionLog::create([
            'tenant_id' => $tenantId,
            'plan_id' => $task->plan_id,
            'action' => 'task_toggled',
            'description' => "Tâche marquée comme: {$newStatus}",
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tâche mise à jour',
            'data' => $task,
        ]);
    }

    /**
     * Supprime une tâche
     * 
     * DELETE /m/risk.core/action-plan/task/{taskId}
     * 
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTask($taskId)
    {
        $tenantId = auth()->user()->tenant_id;

        $task = RiskActionTask::where('id', $taskId)
            ->whereHas('plan', fn($q) => $q->where('tenant_id', $tenantId))
            ->firstOrFail();

        $taskTitle = $task->title;
        $planId = $task->plan_id;

        $task->delete();

        // Log
        RiskActionLog::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'action' => 'task_deleted',
            'description' => "Tâche supprimée: {$taskTitle}",
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tâche supprimée',
        ]);
    }

    /**
     * Ajoute un commentaire
     * 
     * POST /m/risk.core/action-plan/{planId}/comment
     * 
     * @param int $planId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addComment($planId, Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $plan = RiskActionPlan::where('id', $planId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
            'is_internal' => 'boolean',
        ]);

        $comment = RiskActionComment::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'comment' => $validated['comment'],
            'is_internal' => $validated['is_internal'] ?? false,
            'user_id' => auth()->id(),
        ]);

        // Log
        RiskActionLog::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'action' => 'comment_added',
            'description' => 'Commentaire ajouté',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire ajouté',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Supprime un commentaire
     * 
     * DELETE /m/risk.core/action-plan/comment/{commentId}
     * 
     * @param int $commentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteComment($commentId)
    {
        $tenantId = auth()->user()->tenant_id;

        $comment = RiskActionComment::where('id', $commentId)
            ->whereHas('plan', fn($q) => $q->where('tenant_id', $tenantId))
            ->firstOrFail();

        // Vérifier que c'est l'auteur ou un admin
        if ($comment->user_id !== auth()->id() && !auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $planId = $comment->plan_id;
        $comment->delete();

        // Log
        RiskActionLog::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'action' => 'comment_deleted',
            'description' => 'Commentaire supprimé',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé',
        ]);
    }

    /**
     * Récupère les détails du plan (pour rafraîchissement)
     * 
     * GET /m/risk.core/action-plan/{planId}
     * 
     * @param int $planId
     * @return \Illuminate\Http\JsonResponse
     */
    public function get($planId)
    {
        $tenantId = auth()->user()->tenant_id;

        $plan = RiskActionPlan::with([
            'tasks',
            'comments.user',
            'logs.user',
        ])
            ->where('id', $planId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'tasks' => $plan->tasks->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'assigned_to_name' => $t->assignedToUser?->name,
                    'target_date' => $t->target_date,
                    'status' => $t->status,
                ]),
                'comments' => $plan->comments->map(fn($c) => [
                    'id' => $c->id,
                    'comment' => $c->comment,
                    'is_internal' => $c->is_internal,
                    'user_name' => $c->user?->name,
                    'created_at' => $c->created_at,
                ]),
                'history' => $plan->logs->map(fn($h) => [
                    'id' => $h->id,
                    'action' => $h->action,
                    'description' => $h->description,
                    'user_name' => $h->user?->name,
                    'created_at' => $h->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Détermine la zone de risque
     * 
     * @param \App\Models\Risk $risk
     * @return string
     */
    private function getRiskZone(Risk $risk): string
    {
        $score = $risk->criticality_score ?? 0;

        return match (true) {
            $score <= 6 => 'Zone Verte (Acceptable)',
            $score <= 12 => 'Zone Jaune (Tolérable)',
            $score <= 18 => 'Zone Orange (Réductible)',
            default => 'Zone Rouge (Inacceptable)',
        };
    }
}
