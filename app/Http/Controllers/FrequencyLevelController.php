<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFrequencyLevelRequest;
use App\Http\Requests\UpdateFrequencyLevelRequest;
use App\Models\RiskFrequencyLevel;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FrequencyLevelController extends Controller
{
    /**
     * Liste des niveaux de fréquence pour une config de matrice donnée.
     * Les critères de chaque niveau sont chargés en eager loading (relation ordonnée).
     */
    public function index(Request $request): Response
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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

        $selectedConfigId = $request->integer('config_id')
            ?: optional($matrixConfigs->firstWhere('is_active', true))['id']
                ?: optional($matrixConfigs->first())['id'];

        // Eager-load criteria via la relation (déjà triée par sort_order dans le modèle).
        // On utilise une chaîne simple (pas de closure) pour éviter les conflits
        // de chargement eager avec les relations imbriquées.
        $frequencyLevels = $selectedConfigId
            ? RiskFrequencyLevel::forTenant($tenantId)
                ->forConfig($selectedConfigId)
                ->with('criteria')
                ->ordered()
                ->get()
                ->map(fn ($l) => [
                    'id'          => $l->id,
                    'label'       => $l->label,
                    'score'       => $l->score,
                    'description' => $l->description,
                    'recurrence'  => $l->recurrence,
                    'full_label'  => $l->full_label,
                    'color_code'  => $l->color_code,
                    'sort_order'  => $l->sort_order,
                    'criteria'    => $l->criteria->map(fn ($c) => [
                        'id'          => $c->id,
                        'designation' => $c->designation,
                        'description' => $c->description,
                        'sort_order'  => $c->sort_order,
                    ])->values()->all(),
                ])
            : collect();

        return Inertia::render('dashboards/Risk/Matrix/Frequencylevel', [
            'matrixConfigs'    => $matrixConfigs,
            'selectedConfigId' => $selectedConfigId,
            'frequencyLevels'  => $frequencyLevels,
        ]);
    }

    /**
     * Création d'un nouveau niveau de fréquence.
     */
    public function store(StoreFrequencyLevelRequest $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        $config = RiskMatrixConfig::forTenant($tenantId)
            ->findOrFail($request->integer('matrix_config_id'));

        $existingCount = RiskFrequencyLevel::forConfig($config->id)->count();
        if ($existingCount >= $config->matrix_size) {
            return back()->withErrors([
                'score' => "Cette matrice {$config->matrix_label} ne peut contenir que {$config->matrix_size} niveaux de fréquence.",
            ]);
        }

        RiskFrequencyLevel::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return back()->with('success', "Niveau de fréquence « {$request->label} » créé avec succès.");
    }

    /**
     * Mise à jour d'un niveau de fréquence.
     */
    public function update(UpdateFrequencyLevelRequest $request, int $frequency_level): RedirectResponse
    {
        $tenantId        = (int) (session('tenant_id') ?? 1);
        $frequency_level = $this->findLevelForTenant($frequency_level, $tenantId);

        $frequency_level->update($request->validated());

        return back()->with('success', "Niveau de fréquence « {$frequency_level->label} » mis à jour.");
    }

    /**
     * Suppression (soft delete) d'un niveau de fréquence.
     */
    public function destroy(Request $request, int $frequency_level): RedirectResponse
    {
        $tenantId        = (int) (session('tenant_id') ?? 1);
        $frequency_level = $this->findLevelForTenant($frequency_level, $tenantId);

        $label = $frequency_level->label;
        $frequency_level->delete();

        return back()->with('success', "Niveau de fréquence « {$label} » supprimé.");
    }

    /**
     * Réordonne les niveaux de fréquence (drag & drop).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            RiskFrequencyLevel::forTenant($tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Ordre mis à jour.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findLevelForTenant(int $id, int $tenantId): RiskFrequencyLevel
    {
        $level = RiskFrequencyLevel::forTenant($tenantId)->findOrFail($id);
        abort_if($level->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $level;
    }
}
