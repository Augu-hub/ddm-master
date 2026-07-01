<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use App\Models\RiskActionPlan;
use App\Models\RiskActionTask;
use App\Models\RiskActionComment;
use App\Models\RiskActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;




/**
 * ═══════════════════════════════════════════════════════════════════════════
 * RiskActionUpdateController
 * 
 * Gère les mises à jour des plans d'action
 * ═══════════════════════════════════════════════════════════════════════════
 */
class RiskActionUpdateController extends Controller
{
    /**
     * Met à jour un plan d'action complet
     * PUT /action-plan/{id}
     */
    public function update($id, Request $request)
    {
        $plan = RiskActionPlan::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_plan' => 'nullable|string',
            'priority' => 'required|in:critical,high,medium,low',
            'status' => 'required|in:pending,in_progress,review,completed,cancelled,blocked',
            'assigned_to' => 'nullable|exists:users,id',
            'target_date' => 'required|date',
            'start_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'cost_estimate' => 'nullable|numeric',
            'actual_cost' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $plan->status;
        $plan->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        // Enregistrer les changements
        $changes = array_diff_assoc($validated, $plan->getOriginal());
        
        if (count($changes) > 0) {
            RiskActionLog::create([
                'tenant_id' => auth()->user()->tenant_id,
                'plan_id' => $plan->id,
                'action' => 'status_changed',
                'description' => $this->formatChanges($changes, $oldStatus, $validated['status'] ?? $oldStatus),
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan d\'action mis à jour',
            'data' => $plan,
        ]);
    }

    /**
     * Formate les changements pour l'historique
     */
    private function formatChanges($changes, $oldStatus, $newStatus)
    {
        $description = "Plan d'action mis à jour:";
        
        foreach ($changes as $field => $value) {
            $description .= " $field=";
            $description .= is_array($value) ? json_encode($value) : $value;
            $description .= ",";
        }

        if ($oldStatus !== $newStatus) {
            $description .= " Statut: $oldStatus → $newStatus";
        }

        return $description;
    }

    /**
     * Ajoute une tâche à un plan
     * POST /action-plan/{planId}/task
     */
    public function addTask($planId, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'target_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $task = RiskActionTask::create([
            'tenant_id' => auth()->user()->tenant_id,
            'plan_id' => $planId,
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tâche créée',
            'data' => $task,
        ]);
    }

    /**
     * Ajoute un commentaire à un plan
     * POST /action-plan/{planId}/comment
     */
    public function addComment($planId, Request $request)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'is_internal' => 'boolean',
        ]);

        $comment = RiskActionComment::create([
            'tenant_id' => auth()->user()->tenant_id,
            'plan_id' => $planId,
            'comment' => $validated['comment'],
            'is_internal' => $validated['is_internal'] ?? false,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire ajouté',
            'data' => $comment,
        ]);
    }
}