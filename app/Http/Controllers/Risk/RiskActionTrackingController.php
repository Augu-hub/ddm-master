<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;  // ✅ IMPORT MANQUANT - AJOUTÉ!
use App\Models\Risk;
use App\Models\RiskActionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RiskActionTrackingController extends Controller
{
    /**
     * Afficher le dashboard de suivi des plans d'action
     */
    public function index(Request $request)
    {
        // ✅ CORRIGÉ: Ne pas supposer tenant_id dans users
        $tenantId = $request->user()->tenant_id ?? null;

        // Récupérer TOUS les plans d'action (pas filtré par tenant si pas tenant_id)
        if ($tenantId) {
            $actionPlans = RiskActionPlan::with(['risk', 'assignedTo', 'tasks', 'comments', 'logs'])
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->get();

            $allRisks = Risk::where('tenant_id', $tenantId)->get();
        } else {
            // ✅ CORRIGÉ: Charger TOUS les plans si pas de tenant_id
            $actionPlans = RiskActionPlan::with(['risk', 'assignedTo', 'tasks', 'comments', 'logs'])
                ->orderBy('created_at', 'desc')
                ->get();

            $allRisks = Risk::all();
        }

        // ✅ CORRIGÉ: Ne pas filtrer users par tenant_id
        $users = User::all();  // Au lieu de: User::where('tenant_id', $tenantId)->get();

        // Calculer les statistiques
        $stats = $this->calculateStats($actionPlans);

        return Inertia::render('dashboards/Risk/ActionTracking', [
            'actionPlans' => $actionPlans,
            'allRisks' => $allRisks,
            'stats' => $stats,
            'entities' => $this->getEntities(),
            'users' => $users,
            'priorities' => config('risk.priorities', [
                ['value' => 'critical', 'label' => 'Critique'],
                ['value' => 'high', 'label' => 'Haute'],
                ['value' => 'medium', 'label' => 'Moyenne'],
                ['value' => 'low', 'label' => 'Basse'],
            ]),
            'statuses' => config('risk.action_statuses', [
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'in_progress', 'label' => 'En cours'],
                ['value' => 'review', 'label' => 'En révision'],
                ['value' => 'completed', 'label' => 'Complété'],
                ['value' => 'cancelled', 'label' => 'Annulé'],
                ['value' => 'blocked', 'label' => 'Bloqué'],
            ]),
        ]);
    }

    /**
     * Mettre à jour la progression d'un plan
     */
    public function updateProgress(Request $request, $planId)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $plan = RiskActionPlan::findOrFail($planId);
        $plan->update(['progress' => $validated['progress']]);

        return response()->json(['success' => true, 'data' => $plan]);
    }

    /**
     * Récupérer les tâches d'un plan
     */
    public function getTasks($planId)
    {
        $tasks = RiskActionPlan::findOrFail($planId)
            ->tasks()
            ->with('assignedTo')
            ->orderBy('sort_order')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Récupérer les commentaires d'un plan
     */
    public function getComments($planId)
    {
        $comments = RiskActionPlan::findOrFail($planId)
            ->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    /**
     * Récupérer l'historique d'un plan
     */
    public function getHistory($planId)
    {
        $history = RiskActionPlan::findOrFail($planId)
            ->logs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }

    /**
     * Calculer les statistiques
     */
    private function calculateStats($actionPlans)
    {
        return [
            'total' => $actionPlans->count(),
            'completed' => $actionPlans->where('status', 'completed')->count(),
            'in_progress' => $actionPlans->where('status', 'in_progress')->count(),
            'pending' => $actionPlans->where('status', 'pending')->count(),
            'overdue' => $actionPlans->filter(function ($plan) {
                return $plan->target_date && $plan->target_date < now() && $plan->status !== 'completed';
            })->count(),
            'critical' => $actionPlans->where('priority', 'critical')->count(),
            'average_progress' => $actionPlans->avg('progress') ?? 0,
        ];
    }

    /**
     * Récupérer les entités
     */
    private function getEntities()
    {
        // ✅ Adapter selon votre table entities
        // Si vous avez un modèle Entity:
        // return Entity::all();
        
        // Sinon, retourner un tableau vide
        return [];
    }
}