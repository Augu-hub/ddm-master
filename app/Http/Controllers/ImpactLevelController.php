<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImpactLevelRequest;
use App\Http\Requests\UpdateImpactLevelRequest;
use App\Models\RiskImpactLevel;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImpactLevelController extends Controller
{
    /**
     * Liste des niveaux d'impact pour une config de matrice donnée.
     */
    public function index(Request $request): Response
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        // Configs disponibles pour ce tenant
        $matrixConfigs = RiskMatrixConfig::forTenant($tenantId)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'matrix_size'  => $c->matrix_size,
                'matrix_label' => $c->matrix_label,
                'is_active'    => $c->is_active,
            ]);

        // Config sélectionnée (paramètre GET ou active par défaut)
        $selectedConfigId = $request->integer('config_id')
            ?: optional($matrixConfigs->firstWhere('is_active', true))['id']
                ?: optional($matrixConfigs->first())['id'];

        // Niveaux d'impact de la config sélectionnée
        $impactLevels = $selectedConfigId
            ? RiskImpactLevel::forTenant($tenantId)
                ->forConfig($selectedConfigId)
                ->ordered()
                ->get()
                ->map(fn ($l) => [
                    'id'          => $l->id,
                    'label'       => $l->label,
                    'score'       => $l->score,
                    'description' => $l->description,
                    'color_code'  => $l->color_code,
                    'sort_order'  => $l->sort_order,
                ])
            : collect();

        return Inertia::render('dashboards/Risk/Matrix/Impactlevel', [
            'matrixConfigs'    => $matrixConfigs,
            'selectedConfigId' => $selectedConfigId,
            'impactLevels'     => $impactLevels,
        ]);
    }

    /**
     * Création d'un nouveau niveau d'impact.
     */
    public function store(StoreImpactLevelRequest $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        // Vérifie que la config appartient bien au tenant
        $config = RiskMatrixConfig::forTenant($tenantId)
            ->findOrFail($request->integer('matrix_config_id'));

        // Vérifie qu'on ne dépasse pas la taille de la matrice
        $existingCount = RiskImpactLevel::forConfig($config->id)->count();
        if ($existingCount >= $config->matrix_size) {
            return back()->withErrors([
                'score' => "Cette matrice {$config->matrix_label} ne peut contenir que {$config->matrix_size} niveaux d'impact.",
            ]);
        }

        RiskImpactLevel::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return back()->with('success', "Niveau d'impact « {$request->label} » créé avec succès.");
    }

    /**
     * Mise à jour d'un niveau d'impact.
     */
    public function update(UpdateImpactLevelRequest $request, int $impact_level): RedirectResponse
    {
        $tenantId     = (int) (session('tenant_id') ?? 1);
        $impact_level = $this->findLevelForTenant($impact_level, $tenantId);

        $impact_level->update($request->validated());

        return back()->with('success', "Niveau d'impact « {$impact_level->label} » mis à jour.");
    }

    /**
     * Suppression (soft delete) d'un niveau d'impact.
     */
    public function destroy(Request $request, int $impact_level): RedirectResponse
    {
        $tenantId     = (int) (session('tenant_id') ?? 1);
        $impact_level = $this->findLevelForTenant($impact_level, $tenantId);

        $label = $impact_level->label;
        $impact_level->delete();

        return back()->with('success', "Niveau d'impact « {$label} » supprimé.");
    }

    /**
     * Réordonne les niveaux d'impact (drag & drop).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        $request->validate([
            'items'          => ['required', 'array'],
            'items.*.id'     => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            RiskImpactLevel::forTenant($tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Ordre mis à jour.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findLevelForTenant(int $id, int $tenantId): RiskImpactLevel
    {
        $level = RiskImpactLevel::forTenant($tenantId)->findOrFail($id);
        abort_if($level->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $level;
    }
}
