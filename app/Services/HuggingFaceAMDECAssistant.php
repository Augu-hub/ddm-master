<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceAMDECAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api-inference.huggingface.co/v1';
    protected $model = 'mistralai/Mistral-7B-Instruct-v0.2';

    public function __construct()
    {
        $this->apiKey = config('services.huggingface.api_key');
        
        if (!$this->apiKey) {
            Log::error('🚨 HUGGINGFACE_API_KEY NOT CONFIGURED');
        } else {
            Log::info('✅ HuggingFace API initialized');
        }
    }

    /**
     * 💡 PHASE 1 — Suggestions AMDEC
     */
    public function suggestPhase1(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $activityName = $payload['activity_name'] ?? '';

            if (!$failureMode || !$activityName) {
                return [];
            }

            $prompt = "Tu es expert AMDEC. Activité: $activityName. Mode: $failureMode. Génère JSON avec: effects, causes, current_controls.";

            return $this->callHuggingFace($prompt, "PHASE1");

        } catch (\Exception $e) {
            Log::error('❌ Phase 1: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 💡 PHASE 2 — Plan d'action
     */
    public function suggestPhase2(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $effects = $payload['effects'] ?? '';
            $causes = $payload['causes'] ?? '';

            if (!$failureMode) {
                return [];
            }

            $prompt = "Tu es expert AMDEC. Mode: $failureMode. Effets: $effects. Causes: $causes. Génère JSON avec: prevention_measures, action_responsible.";

            return $this->callHuggingFace($prompt, "PHASE2");

        } catch (\Exception $e) {
            Log::error('❌ Phase 2: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 💡 PHASE 3 — Efficacité
     */
    public function suggestPhase3(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $preventionMeasures = $payload['prevention_measures'] ?? '';

            if (!$failureMode) {
                return [];
            }

            $prompt = "Tu es expert AMDEC. Mode: $failureMode. Mesures: $preventionMeasures. Génère JSON avec: efficacy_criterion, efficacy_measure.";

            return $this->callHuggingFace($prompt, "PHASE3");

        } catch (\Exception $e) {
            Log::error('❌ Phase 3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL HUGGINGFACE
     */
    protected function callHuggingFace(string $prompt, string $phase = ''): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('❌ API KEY vide');
                return [];
            }

            Log::info("🚀 HuggingFace ($phase)");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($this->apiKey),
                'Content-Type' => 'application/json'
            ])
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Réponds UNIQUEMENT en JSON valide.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3
            ]);

            if (!$response->successful()) {
                Log::error('❌ HF Error: ' . $response->status());
                return [];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            if (!$content) {
                return [];
            }

            return $this->parseJSON($content) ?? [];

        } catch (\Exception $e) {
            Log::error('❌ Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔍 Parser JSON
     */
    protected function parseJSON(string $text): ?array
    {
        $text = trim($text);

        $json = json_decode($text, true);
        if (is_array($json)) {
            return $json;
        }

        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $m)) {
            $json = json_decode($m[1], true);
            if (is_array($json)) return $json;
        }

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json)) return $json;
        }

        return null;
    }

    /**
     * 🔒 SÉCURITÉ
     */
    public static function validatePayloadSafety(array $payload): bool
    {
        $forbidden = ['user_id', 'entity_id', 'function_id', 'database', 'password', 'token'];
        foreach ($forbidden as $key) {
            if (!empty($payload[$key])) {
                Log::warning("🚨 SENSITIVE: $key");
                return false;
            }
        }
        return true;
    }
}