<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiskMatrixController extends Controller
{
    /**
     * Affiche la heatmap de la configuration active (ou sélectionnée).
     */
    public function index(Request $request): Response
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        // Toutes les configs pour le sélecteur
        $allConfigs = RiskMatrixConfig::forTenant($tenantId)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'matrix_label' => $c->matrix_label,
                'is_active'    => $c->is_active,
            ]);

        // Config sélectionnée
        $selectedId = $request->integer('config_id')
            ?: optional($allConfigs->firstWhere('is_active', true))['id']
                ?: optional($allConfigs->first())['id'];

        $matrixData = null;

        if ($selectedId) {
            $config = RiskMatrixConfig::forTenant($tenantId)
                ->with([
                    'impactLevels'     => fn ($q) => $q->ordered(),
                    'frequencyLevels'  => fn ($q) => $q->ordered(),
                    'criticalityZones' => fn ($q) => $q->ordered(),
                ])
                ->find($selectedId);

            if ($config) {
                $matrixData = $this->buildMatrixPayload($config);
            }
        }

        return Inertia::render('dashboards/Risk/Matrix/Riskmatrix', [
            'allConfigs'       => $allConfigs,
            'selectedConfigId' => $selectedId,
            'matrixData'       => $matrixData,
        ]);
    }

    // ─── Builder payload ──────────────────────────────────────────────────────

    private function buildMatrixPayload(RiskMatrixConfig $config): array
    {
        $impacts     = $config->impactLevels->values();
        $frequencies = $config->frequencyLevels->values();
        $zones       = $config->criticalityZones->values();

        // Grille : tableau 2D [impact_index][freq_index]
        $cells = [];
        foreach ($impacts as $impact) {
            $row = [];
            foreach ($frequencies as $freq) {
                $score = $impact->score * $freq->score;

                // Résout la zone correspondante
                $zone = $zones->first(
                    fn ($z) => $score >= $z->min_score && $score <= $z->max_score
                );

                $row[] = [
                    'score'            => $score,
                    'impact_score'     => $impact->score,
                    'frequency_score'  => $freq->score,
                    'zone_label'       => $zone?->label,
                    'zone_color'       => $zone?->color_code ?? '#6b7280',
                    'zone_sort'        => $zone?->sort_order ?? 0,
                ];
            }
            $cells[] = $row;
        }

        return [
            'config' => [
                'id'           => $config->id,
                'name'         => $config->name,
                'matrix_size'  => $config->matrix_size,
                'matrix_label' => $config->matrix_label,
                'max_score'    => $config->max_score,
                'is_active'    => $config->is_active,
            ],
            // Axes — impacts du plus grave (haut) au moins grave (bas)
            'impacts'     => $impacts->sortByDesc('score')->values()->map(fn ($l) => [
                'id'          => $l->id,
                'label'       => $l->label,
                'score'       => $l->score,
                'description' => $l->description,
                'color_code'  => $l->color_code,
            ])->values(),
            // Fréquences du moins fréquent (gauche) au plus fréquent (droite)
            'frequencies' => $frequencies->sortBy('score')->values()->map(fn ($l) => [
                'id'          => $l->id,
                'label'       => $l->label,
                'score'       => $l->score,
                'description' => $l->description,
                'recurrence'  => $l->recurrence,
                'color_code'  => $l->color_code,
            ])->values(),
            // Grille (même ordre que impacts : plus grave en premier)
            'cells'  => collect($cells)
                ->sortByDesc(fn ($row) => $row[0]['impact_score'])
                ->values(),
            // Zones pour la légende
            'zones'  => $zones->map(fn ($z) => [
                'id'         => $z->id,
                'label'      => $z->label,
                'min_score'  => $z->min_score,
                'max_score'  => $z->max_score,
                'color_code' => $z->color_code,
                'sort_order' => $z->sort_order,
            ])->values(),
        ];
    }
}
