<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use App\Models\RiskActionPlan;
use App\Models\User;
use App\Models\Param\Entite;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ReportSessionProController
 * 
 * Génère le rapport de session professionnelle
 * ═══════════════════════════════════════════════════════════════════════════
 */
class ReportSessionProController extends Controller
{
    /**
     * Affiche le rapport session pro
     * GET /reports/session-pro
     */
    public function index()
    {
        // ✅ CORRIGÉ: Vérifier si tenant_id existe
        $tenantId = auth()->user()->tenant_id ?? null;

        // Récupérer tous les plans d'action avec relations
        $query = RiskActionPlan::with([
            'risk',
            'assignedTo',
            'entity',
        ])->whereNull('deleted_at');

        // ✅ CORRIGÉ: Filtrer par tenant seulement s'il existe
        if ($tenantId) {
            $query = $query->where('tenant_id', $tenantId);
        }

        // ✅ IMPORTANT: Garder les objets Eloquent pour calculateStats()
        $actionPlansObjects = $query->get();

        // Convertir en array pour la vue (seulement pour la transmission)
        $actionPlans = $actionPlansObjects->map(fn($plan) => [
            ...collect($plan)->toArray(),
            'assigned_to_name' => $plan->assignedTo?->name,
            'entity_name' => $plan->entity?->name,
            'code_risk' => $plan->risk?->code_risk,
            'risk_libelle' => $plan->risk?->libelle,
            'process_name' => $plan->risk?->process?->name ?? null,
            'activity_name' => $plan->risk?->activity?->name ?? null,
            'macro_name' => $plan->risk?->macro?->name ?? null,
            'criticality_score' => $plan->risk?->criticality_score,
            'zone_color' => $plan->risk?->zone_color,
            'zone_label' => $plan->risk?->zone_label ?? null,
        ]);

        // ✅ CORRIGÉ: Passer les objets à calculateStats(), pas les arrays
        $stats = $this->calculateStats($actionPlansObjects);

        // ✅ CORRIGÉ: Tous les risques (pas de where tenant_id)
        $allRisks = Risk::all();

        // ✅ CORRIGÉ: Tous les utilisateurs (ne pas filtrer par tenant_id)
        $users = User::all();

        // ✅ UTILISER ENTITE: Récupérer les entités de votre table existante
        $entities = Entite::all();

        return Inertia::render('dashboards/Risk/SessionProReport', [
            'actionPlans' => $actionPlans,
            'allRisks' => $allRisks,
            'stats' => $stats,
            'entities' => $entities,
            'users' => $users,
            'priorities' => config('risk.priorities', [
                ['value' => 'critical', 'label' => 'Critique'],
                ['value' => 'high', 'label' => 'Haute'],
                ['value' => 'medium', 'label' => 'Moyenne'],
                ['value' => 'low', 'label' => 'Basse'],
            ]),
            'statuses' => config('risk.action_statuses', [
                ['value' => 'pending', 'label' => 'En attente', 'color' => '#f1f5f9'],
                ['value' => 'in_progress', 'label' => 'En cours', 'color' => '#dbeafe'],
                ['value' => 'review', 'label' => 'En révision', 'color' => '#ede9fe'],
                ['value' => 'completed', 'label' => 'Complétée', 'color' => '#dcfce7'],
                ['value' => 'cancelled', 'label' => 'Annulée', 'color' => '#fee2e2'],
                ['value' => 'blocked', 'label' => 'Bloquée', 'color' => '#fef3c7'],
            ]),
        ]);
    }

    /**
     * Exporte le rapport en PDF
     * POST /reports/session-pro/export-pdf
     */
    public function exportPdf()
    {
        // ✅ CORRIGÉ: Récupérer tenant_id
        $tenantId = auth()->user()->tenant_id ?? null;

        $query = RiskActionPlan::with('risk', 'assignedTo');

        // ✅ CORRIGÉ: Filtrer par tenant seulement s'il existe
        if ($tenantId) {
            $query = $query->where('tenant_id', $tenantId);
        }

        $actionPlans = $query->get();
        $stats = $this->calculateStats($actionPlans);
        $reportDate = now()->format('d/m/Y');

        // Nécessite: composer require barryvdh/laravel-dompdf
        $pdf = \PDF::loadView('reports.session-pro-pdf', [
            'actionPlans' => $actionPlans,
            'stats' => $stats,
            'reportDate' => $reportDate,
        ]);

        return $pdf->download("rapport-session-pro-{$reportDate}.pdf");
    }

    /**
     * Exporte le rapport en Excel
     * POST /reports/session-pro/export-excel
     */
    public function exportExcel()
    {
        // ✅ CORRIGÉ: Récupérer tenant_id
        $tenantId = auth()->user()->tenant_id ?? null;

        $query = RiskActionPlan::with('risk', 'assignedTo');

        // ✅ CORRIGÉ: Filtrer par tenant seulement s'il existe
        if ($tenantId) {
            $query = $query->where('tenant_id', $tenantId);
        }

        $actionPlans = $query->get();
        $filename = "rapport-session-pro-" . now()->format('d-m-Y') . ".xlsx";

        // Nécessite: composer require maatwebsite/excel
        return \Excel::download(
            new \App\Exports\SessionProExport($actionPlans),
            $filename
        );
    }

    /**
     * Calcule toutes les statistiques nécessaires
     * ✅ CORRIGÉ: Attend des objets Eloquent, pas des arrays
     */
    private function calculateStats($actionPlans)
    {
        $completed = $actionPlans->where('status', 'completed')->count();
        $total = $actionPlans->count();

        // ✅ CORRIGÉ: Utiliser -> pour les objets Eloquent
        $overdue = $actionPlans->filter(function ($p) {
            return $p->status !== 'completed' && 
                   $p->target_date && 
                   now() > $p->target_date;
        })->count();

        $avgProgress = $total > 0 
            ? round($actionPlans->avg('progress') ?? 0)
            : 0;

        return [
            'total' => $total,
            'pending' => $actionPlans->where('status', 'pending')->count(),
            'in_progress' => $actionPlans->where('status', 'in_progress')->count(),
            'completed' => $completed,
            'overdue' => $overdue,
            'critical' => $actionPlans->where('priority', 'critical')->count(),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'avg_progress' => $avgProgress,
        ];
    }
}