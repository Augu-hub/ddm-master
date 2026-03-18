<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RiskMatrixConfig;
use App\Services\MistralFrequencyAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MistralFrequencyController extends Controller
{
    public function __construct(
        private readonly MistralFrequencyAssistant $assistant
    ) {}

    /**
     * POST /m/risk.core/frequency/mistral/suggest
     *
     * Body JSON :
     *   sector      : string (requis)
     *   context     : string (optionnel)
     *   matrix_size : int 3|4|5 (requis)
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sector'      => ['required', 'string', 'max:150'],
            'context'     => ['nullable', 'string', 'max:500'],
            'matrix_size' => ['required', 'integer', 'in:3,4,5'],
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
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => "Une erreur inattendue s'est produite. Veuillez réessayer.",
            ], 500);
        }
    }
}
