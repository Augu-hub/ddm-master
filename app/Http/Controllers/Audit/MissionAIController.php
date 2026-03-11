<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\MissionTitleAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MissionAIController extends Controller
{
    public function __construct(protected MissionTitleAIService $svc) {}

    public function suggestTitle(Request $request)
    {
        try {
            $validated = $request->validate([
                'objective' => 'required|string|min:5|max:1000',
                'type'      => 'nullable|string|max:100',
                'entity'    => 'nullable|string|max:100',
                'domain'    => 'nullable|string|max:100',
                'year'      => 'nullable|integer|min:2000|max:2100',
            ]);

            $result = $this->svc->suggestTitles(
                $validated['objective'],
                $validated['type']   ?? '',
                $validated['entity'] ?? '',
                $validated['domain'] ?? '',
                (int) ($validated['year'] ?? date('Y'))
            );

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success'     => false,
                'suggestions' => [],
                'error'       => 'Données invalides'
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MissionAIController::suggestTitle - ' . $e->getMessage());
            return response()->json([
                'success'     => false,
                'suggestions' => [],
                'error'       => 'Erreur serveur'
            ], 500);
        }
    }
}