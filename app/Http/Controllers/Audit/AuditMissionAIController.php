<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\MistralMissionAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditMissionAIController extends Controller
{
    protected MistralMissionAssistant $assistant;

    public function __construct(MistralMissionAssistant $assistant)
    {
        $this->assistant = $assistant;
    }

    /**
     * POST /m/audit.core/audit/ai/suggest-type
     */
    public function suggestType(Request $request): JsonResponse
    {
        try {
            $v = $request->validate([
                'risk_ids'   => 'required|array|min:1',
                'risk_ids.*' => 'integer|exists:risks,id',
            ]);
            $result = $this->assistant->suggestMissionType($v['risk_ids']);
            return empty($result)
                ? response()->json(['success' => false, 'error' => 'Impossible de suggérer un type'], 422)
                : response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('❌ AI suggestType: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/audit/ai/suggest-goals
     */
    public function suggestGoals(Request $request): JsonResponse
    {
        try {
            $v = $request->validate([
                'risk_ids'          => 'required|array|min:1',
                'risk_ids.*'        => 'integer|exists:risks,id',
                'mission_type_code' => 'required|string',
            ]);
            $result = $this->assistant->suggestMissionGoals($v['risk_ids'], $v['mission_type_code']);
            return empty($result)
                ? response()->json(['success' => false, 'error' => 'Impossible de générer des buts'], 422)
                : response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('❌ AI suggestGoals: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/audit/ai/generate-fields
     */
    public function generateFields(Request $request): JsonResponse
    {
        try {
            $v = $request->validate([
                'risk_ids'          => 'required|array|min:1',
                'risk_ids.*'        => 'integer|exists:risks,id',
                'mission_type_code' => 'required|string',
                'selected_goal'     => 'required|string|min:10',
            ]);
            $result = $this->assistant->generateMissionFields($v['risk_ids'], $v['mission_type_code'], $v['selected_goal']);
            return empty($result)
                ? response()->json(['success' => false, 'error' => 'Impossible de générer les champs'], 422)
                : response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('❌ AI generateFields: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/audit/ai/revise-goal
     */
    public function reviseGoal(Request $request): JsonResponse
    {
        try {
            $v = $request->validate([
                'current_goal'      => 'required|string|min:5',
                'risk_ids'          => 'required|array|min:1',
                'risk_ids.*'        => 'integer|exists:risks,id',
                'mission_type_code' => 'required|string',
                'instruction'       => 'nullable|string|max:500',
            ]);
            $result = $this->assistant->reviseMissionGoal(
                $v['current_goal'], $v['risk_ids'], $v['mission_type_code'], $v['instruction'] ?? ''
            );
            return empty($result)
                ? response()->json(['success' => false, 'error' => 'Impossible de reformuler'], 422)
                : response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('❌ AI reviseGoal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/audit/ai/full-proposal
     */
    public function fullProposal(Request $request): JsonResponse
    {
        try {
            $v = $request->validate([
                'risk_ids'   => 'required|array|min:1',
                'risk_ids.*' => 'integer|exists:risks,id',
            ]);
            $result = $this->assistant->generateFullMissionProposal($v['risk_ids']);
            return isset($result['error'])
                ? response()->json(['success' => false, 'error' => $result['error']], 422)
                : response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('❌ AI fullProposal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/audit/ai/regenerate-field
     */
    public function regenerateField(Request $request): JsonResponse
    {
        try {
            $v = $request->validate([
                'field_name'        => 'required|string|in:but,description,preoccupation,resultat,champ_mission,fonction_processus,procedure',
                'current_value'     => 'required|string',
                'risk_ids'          => 'required|array|min:1',
                'risk_ids.*'        => 'integer|exists:risks,id',
                'mission_type_code' => 'required|string',
                'mission_goal'      => 'required|string',
                'instruction'       => 'nullable|string|max:500',
            ]);
            $result = $this->assistant->regenerateField(
                $v['field_name'], $v['current_value'], $v['risk_ids'],
                $v['mission_type_code'], $v['mission_goal'], $v['instruction'] ?? ''
            );
            return (empty($result) || isset($result['error']))
                ? response()->json(['success' => false, 'error' => $result['error'] ?? 'Échec régénération'], 422)
                : response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('❌ AI regenerateField: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}