<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFrequencyCriterionRequest;
use App\Http\Requests\UpdateFrequencyCriterionRequest;
use App\Models\RiskFrequencyCriterion;
use App\Models\RiskFrequencyLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FrequencyCriterionController extends Controller
{
    /**
     * Crée un critère pour le niveau de fréquence donné.
     * Route : POST /frequency/{frequency_level}/criteria
     */
    public function store(StoreFrequencyCriterionRequest $request, int $frequency_level): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $level    = $this->findLevelForTenant($frequency_level, $tenantId);

        RiskFrequencyCriterion::create([
            ...$request->validated(),
            'tenant_id'          => $tenantId,
            'frequency_level_id' => $level->id,
        ]);

        return back()->with('success', "Critère « {$request->designation} » ajouté.");
    }

    /**
     * Met à jour un critère de fréquence.
     * Route : PUT /frequency/{frequency_level}/criteria/{criterion}
     */
    public function update(UpdateFrequencyCriterionRequest $request, int $frequency_level, int $criterion): RedirectResponse
    {
        $tenantId  = (int) (session('tenant_id') ?? 1);
        $this->findLevelForTenant($frequency_level, $tenantId);
        $criterion = $this->findCriterionForTenant($criterion, $tenantId);

        $criterion->update($request->validated());

        return back()->with('success', "Critère mis à jour.");
    }

    /**
     * Supprime (soft delete) un critère de fréquence.
     * Route : DELETE /frequency/{frequency_level}/criteria/{criterion}
     */
    public function destroy(Request $request, int $frequency_level, int $criterion): RedirectResponse
    {
        $tenantId  = (int) (session('tenant_id') ?? 1);
        $this->findLevelForTenant($frequency_level, $tenantId);
        $criterion = $this->findCriterionForTenant($criterion, $tenantId);

        $label = $criterion->designation;
        $criterion->delete();

        return back()->with('success', "Critère « {$label} » supprimé.");
    }

    /**
     * Réordonne les critères d'un niveau de fréquence.
     * Route : POST /frequency/{frequency_level}/criteria/reorder
     */
    public function reorder(Request $request, int $frequency_level): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $this->findLevelForTenant($frequency_level, $tenantId);

        $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            RiskFrequencyCriterion::forTenant($tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Ordre des critères mis à jour.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findLevelForTenant(int $id, int $tenantId): RiskFrequencyLevel
    {
        $level = RiskFrequencyLevel::forTenant($tenantId)->findOrFail($id);
        abort_if($level->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $level;
    }

    private function findCriterionForTenant(int $id, int $tenantId): RiskFrequencyCriterion
    {
        $criterion = RiskFrequencyCriterion::forTenant($tenantId)->findOrFail($id);
        abort_if($criterion->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $criterion;
    }
}
