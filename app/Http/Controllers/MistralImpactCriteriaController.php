<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RiskImpactLevel;
use App\Models\RiskMatrixConfig;
use App\Services\MistralCriteriaAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MistralImpactCriteriaController extends Controller
{
    public function __construct(
        private readonly MistralCriteriaAssistant $assistant
    ) {}

    /**
     * POST /m/risk.core/impact/criteria/mistral/suggest
     *
     * Body JSON :
     *   sector          : string (requis)
     *   context         : string (optionnel)
     *   matrix_config_id : int (requis)
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sector'           => ['required', 'string', 'max:150'],
            'context'          => ['nullable', 'string', 'max:500'],
            'matrix_config_id' => ['required', 'integer'],
        ]);

        $tenantId = (int) (session('tenant_id') ?? 1);

        // Vérifie que la config appartient au tenant
        RiskMatrixConfig::forTenant($tenantId)
            ->findOrFail($validated['matrix_config_id']);

        // Charge tous les niveaux d'impact de la config pour contextualiser Mistral
        $levels = RiskImpactLevel::forTenant($tenantId)
            ->forConfig($validated['matrix_config_id'])
            ->ordered()
            ->get()
            ->map(fn ($l) => [
                'id'          => $l->id,
                'score'       => $l->score,
                'label'       => $l->label,
                'description' => $l->description ?? '',
            ])
            ->all();

        if (empty($levels)) {
            return response()->json([
                'message' => 'Aucun niveau d\'impact défini pour cette configuration. Créez d\'abord vos niveaux.',
                'hints'   => [],
            ], 422);
        }

        try {
            $suggestions = $this->assistant->suggest([
                'sector'     => $validated['sector'],
                'context'    => $validated['context'] ?? '',
                'level_type' => 'impact',
                'levels'     => $levels,
            ]);

            return response()->json([
                'suggestions' => $suggestions,
                'sector'      => $validated['sector'],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'hints'   => self::reformulationHints($validated['sector']),
            ], 422);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => "Une erreur inattendue s'est produite. Veuillez réessayer.",
                'hints'   => [],
            ], 500);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static function reformulationHints(string $sector): array
    {
        $hints = [
            'Précisez le sous-secteur (ex : "banque commerciale" plutôt que "banque")',
            'Ajoutez du contexte : taille, région, réglementation applicable',
            'Vérifiez que le secteur est en français',
        ];

        if (strlen(trim($sector)) < 8) {
            array_unshift(
                $hints,
                'Le secteur indiqué est trop court — décrivez-le en 2 à 5 mots'
            );
        }

        return $hints;
    }
}
