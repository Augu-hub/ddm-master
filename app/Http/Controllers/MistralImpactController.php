<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RiskMatrixConfig;
use App\Services\MistralImpactAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MistralImpactController extends Controller
{
    public function __construct(
        private readonly MistralImpactAssistant $assistant
    ) {}

    /**
     * POST /m/risk.core/impact/mistral/suggest
     *
     * Body JSON :
     *   sector      : string (requis)
     *   context     : string (optionnel)
     *   matrix_size : int 3..10 (requis)
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sector'      => ['required', 'string', 'max:150'],
            'context'     => ['nullable', 'string', 'max:500'],
            'matrix_size' => ['required', 'integer', 'in:3,4,5,6,7,8,9,10'],
        ]);

        $tenantId = (int) (session('tenant_id') ?? 1);
        if ($request->filled('matrix_config_id')) {
            RiskMatrixConfig::forTenant($tenantId)
                ->findOrFail($request->integer('matrix_config_id'));
        }

        try {
            $suggestions = $this->assistant->suggest($validated);

            return response()->json([
                'suggestions' => $suggestions,
                'matrix_size' => $validated['matrix_size'],
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
                'message' => "Une erreur inattendue s'est produite. Veuillez reessayer.",
                'hints'   => [],
            ], 500);
        }
    }

    // --- Helpers ----------------------------------------------------------

    /**
     * Retourne des conseils de reformulation contextuels selon la longueur
     * et la nature du secteur fourni.
     */
    private static function reformulationHints(string $sector): array
    {
        $hints = [
            'Precisez le sous-secteur (ex : "banque commerciale" plutot que "banque")',
            'Ajoutez du contexte : taille, region, reglementation applicable',
            'Verifiez que le secteur est en francais',
        ];

        if (strlen(trim($sector)) < 8) {
            array_unshift(
                $hints,
                'Le secteur indique est trop court — decrivez-le en 2 a 5 mots'
            );
        }

        return $hints;
    }
}
