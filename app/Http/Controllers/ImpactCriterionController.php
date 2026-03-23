<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImpactCriterionRequest;
use App\Http\Requests\UpdateImpactCriterionRequest;
use App\Models\RiskImpactCriterion;
use App\Models\RiskImpactLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpactCriterionController extends Controller
{
    /**
     * Crée un critère pour le niveau d'impact donné.
     * Route : POST /impact/{impact_level}/criteria
     */
    public function store(StoreImpactCriterionRequest $request, int $impact_level): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $level    = $this->findLevelForTenant($impact_level, $tenantId);

        RiskImpactCriterion::create([
            ...$request->validated(),
            'tenant_id'       => $tenantId,
            'impact_level_id' => $level->id,
        ]);

        return back()->with('success', "Critère « {$request->designation} » ajouté.");
    }

    /**
     * Met à jour un critère d'impact.
     * Route : PUT /impact/{impact_level}/criteria/{criterion}
     */
    public function update(UpdateImpactCriterionRequest $request, int $impact_level, int $criterion): RedirectResponse
    {
        $tenantId  = (int) (session('tenant_id') ?? 1);
        $this->findLevelForTenant($impact_level, $tenantId);
        $criterion = $this->findCriterionForTenant($criterion, $tenantId);

        $criterion->update($request->validated());

        return back()->with('success', "Critère mis à jour.");
    }

    /**
     * Supprime (soft delete) un critère d'impact.
     * Route : DELETE /impact/{impact_level}/criteria/{criterion}
     */
    public function destroy(Request $request, int $impact_level, int $criterion): RedirectResponse
    {
        $tenantId  = (int) (session('tenant_id') ?? 1);
        $this->findLevelForTenant($impact_level, $tenantId);
        $criterion = $this->findCriterionForTenant($criterion, $tenantId);

        $label = $criterion->designation;
        $criterion->delete();

        return back()->with('success', "Critère « {$label} » supprimé.");
    }

    /**
     * Réordonne les critères d'un niveau d'impact.
     * Route : POST /impact/{impact_level}/criteria/reorder
     */
    public function reorder(Request $request, int $impact_level): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $this->findLevelForTenant($impact_level, $tenantId);

        $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            RiskImpactCriterion::forTenant($tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Ordre des critères mis à jour.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findLevelForTenant(int $id, int $tenantId): RiskImpactLevel
    {
        $level = RiskImpactLevel::forTenant($tenantId)->findOrFail($id);
        abort_if($level->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $level;
    }

    private function findCriterionForTenant(int $id, int $tenantId): RiskImpactCriterion
    {
        $criterion = RiskImpactCriterion::forTenant($tenantId)->findOrFail($id);
        abort_if($criterion->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $criterion;
    }
}
