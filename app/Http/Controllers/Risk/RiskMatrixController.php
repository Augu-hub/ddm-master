<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RiskMatrixController extends Controller
{
    private function tenantId(): int
    {
        return (int) (session('tenant_id') ?? 4);
    }

    public function index(Request $request): Response
    {
        $tid = $this->tenantId();

        $allConfigs = RiskMatrixConfig::forTenant($tid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'matrix_label' => $c->matrix_size . '×' . $c->matrix_size,
                'is_active' => (bool) $c->is_active,
            ]);

        // La matrice ACTIVE s'applique partout : on ignore tout config_id de requête.
        $selectedId = optional($allConfigs->firstWhere('is_active', true))['id']
            ?: optional($allConfigs->first())['id'];

        $matrixData = null;
        $masteryLevels = [];

        if ($selectedId) {
            $config = RiskMatrixConfig::forTenant($tid)
                ->with([
                    'impactLevels' => fn($q) => $q->ordered(),
                    'frequencyLevels' => fn($q) => $q->ordered(),
                    'criticalityZones' => fn($q) => $q->ordered(),
                    'masteryLevels' => fn($q) => $q->ordered(),
                ])
                ->find($selectedId);

            if ($config) {
                $matrixData = $this->buildMatrixPayload($config);
                $masteryLevels = $config->masteryLevels->map(fn($m) => [
                    'id' => $m->id,
                    'label' => $m->label,
                    'color_code' => $m->color_code,
                    'description' => $m->description,
                    'sort_order' => $m->sort_order,
                ])->values();
            }
        }

        return Inertia::render('dashboards/Risk/Matrix/Riskmatrix', [
            'allConfigs' => $allConfigs,
            'selectedConfigId' => $selectedId,
            'matrixData' => $matrixData,
            'masteryLevels' => $masteryLevels,
        ]);
    }

    public function getMatrixData(Request $request)
    {
        $tid = $this->tenantId();
        // Toujours la matrice active (on ignore config_id).
        $configId = RiskMatrixConfig::forTenant($tid)->where('is_active', 1)->value('id')
            ?: RiskMatrixConfig::forTenant($tid)->orderBy('id')->value('id');

        $config = RiskMatrixConfig::forTenant($tid)
            ->with([
                'impactLevels' => fn($q) => $q->ordered(),
                'frequencyLevels' => fn($q) => $q->ordered(),
                'criticalityZones' => fn($q) => $q->ordered(),
                'masteryLevels' => fn($q) => $q->ordered(),
            ])
            ->find($configId);

        if (!$config) {
            return response()->json(['error' => 'Configuration non trouvée'], 404);
        }

        return response()->json([
            'matrixData' => $this->buildMatrixPayload($config),
            'masteryLevels' => $config->masteryLevels->map(fn($m) => [
                'id' => $m->id,
                'label' => $m->label,
                'color_code' => $m->color_code,
                'description' => $m->description,
                'sort_order' => $m->sort_order,
            ])->values(),
        ]);
    }

    private function buildMatrixPayload(RiskMatrixConfig $config): array
    {
        $impacts = $config->impactLevels->sortByDesc('score')->values();
        $frequencies = $config->frequencyLevels->sortBy('score')->values();

        // Zones PAR PLAGE (min_score/max_score) — résolution par le score de la cellule.
        $zones = $config->criticalityZones->sortBy('sort_order')->values();
        $zoneFor = fn ($score) => $zones->first(fn ($z) => $score >= $z->min_score && $score <= $z->max_score);

        $cells = [];
        foreach ($impacts as $impact) {
            $row = [];
            foreach ($frequencies as $freq) {
                $score = $impact->score * $freq->score;
                $zone = $zoneFor($score);

                $row[] = [
                    'score' => $score,
                    'impact_score' => $impact->score,
                    'frequency_score' => $freq->score,
                    'zone_id' => $zone?->id,
                    'zone_label' => $zone?->label,
                    'zone_color' => $zone?->color_code ?? '#e2e8f0',
                    'zone_description' => $zone?->description ?? '',
                ];
            }
            $cells[] = $row;
        }

        return [
            'config' => [
                'id' => $config->id,
                'name' => $config->name,
                'matrix_size' => $config->matrix_size,
                'matrix_label' => $config->matrix_size . '×' . $config->matrix_size,
                'max_score' => $config->matrix_size * $config->matrix_size,
                'is_active' => (bool) $config->is_active,
            ],
            'impacts' => $impacts->map(fn($l) => [
                'id' => $l->id,
                'label' => $l->label,
                'score' => $l->score,
                'description' => $l->description ?? '',
                'color_code' => $l->color_code,
            ])->values(),
            'frequencies' => $frequencies->map(fn($l) => [
                'id' => $l->id,
                'label' => $l->label,
                'score' => $l->score,
                'description' => $l->description ?? '',
                'recurrence' => $l->recurrence ?? '',
                'color_code' => $l->color_code,
            ])->values(),
            'cells' => $cells,
            'zones' => $zones->map(fn($z) => [
                'id' => $z->id,
                'label' => $z->label,
                'min_score' => $z->min_score,
                'max_score' => $z->max_score,
                'color_code' => $z->color_code,
                'description' => $z->description ?? '',
            ])->values(),
        ];
    }
}