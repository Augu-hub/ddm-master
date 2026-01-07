<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralAMDECAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api.mistral.ai/v1';
    protected $model = 'mistral-small-latest';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');
        
        if (!$this->apiKey) {
            Log::error('🚨 MISTRAL_API_KEY NOT CONFIGURED');
        } else {
            Log::info('✅ Mistral API initialized');
        }
    }

    /**
     * 🎯 GÉNÉRER MODES DE DÉFAILLANCE PAR ACTIVITÉ
     * Retourne une liste JSON de modes possibles
     */
    public function suggestFailureModes(array $payload): array
    {
        try {
            $activityName = $payload['activity_name'] ?? '';

            if (!$activityName) {
                Log::warning('⚠️ Activity name empty for failure modes');
                return [];
            }

            $prompt = "Tu es expert AMDEC et en gestion des risques. Pour l'activité: \"$activityName\", génère 12 modes de défaillance typiques et réalistes.\n\n" .
                      "Réponds UNIQUEMENT en JSON valide au format:\n" .
                      "{\n" .
                      "  \"failure_modes\": [\n" .
                      "    \"Mode 1\",\n" .
                      "    \"Mode 2\",\n" .
                      "    ...\n" .
                      "  ]\n" .
                      "}\n\n" .
                      "IMPORTANT:\n" .
                      "- Les modes DOIVENT être concis (5-15 mots max)\n" .
                      "- Les modes DOIVENT être spécifiques à l'activité\n" .
                      "- Les modes DOIVENT être réalistes et courants\n" .
                      "- Pas de doublons\n" .
                      "- Pas d'énumération de lettres/numéros";

            $result = $this->callMistral($prompt, "FAILURE_MODES");
            
            Log::info('🎯 Failure modes result: ' . json_encode($result));

            // Retourner la liste des modes
            if (!empty($result) && isset($result['failure_modes']) && is_array($result['failure_modes'])) {
                $modes = array_filter(
                    $result['failure_modes'],
                    fn($m) => !empty($m) && is_string($m)
                );
                return ['failure_modes' => array_values($modes)];
            }

            Log::warning('⚠️ No failure modes generated: ' . json_encode($result));
            return [];

        } catch (\Exception $e) {
            Log::error('❌ Failure Modes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 💡 PHASE 1 — Suggestions AMDEC (Effects/Causes/Controls)
     */
    public function suggestPhase1(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $activityName = $payload['activity_name'] ?? '';

            if (!$failureMode || !$activityName) {
                Log::warning('⚠️ Missing failure_mode or activity_name');
                return [];
            }

            $prompt = "Tu es expert AMDEC. Activité: \"$activityName\". Mode de défaillance: \"$failureMode\".\n\n" .
                      "Génère JSON VALIDE au format:\n" .
                      "{\n" .
                      "  \"effects\": \"Description concise des effets (1-2 phrases)\",\n" .
                      "  \"causes\": \"Description concise des causes (1-2 phrases)\",\n" .
                      "  \"current_controls\": \"Contrôles/mesures préventives existants (1-2 phrases)\"\n" .
                      "}\n\n" .
                      "IMPORTANT: Réponds UNIQUEMENT en JSON valide, sans explications supplémentaires.";

            $result = $this->callMistral($prompt, "PHASE1");
            
            Log::info('📋 Phase1 result: ' . json_encode($result));
            return $result;

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

            $prompt = "Tu es expert AMDEC. Mode: $failureMode. Effets: $effects. Causes: $causes. Génère JSON: {\"prevention_measures\": \"...\", \"action_responsible\": \"...\"}";

            return $this->callMistral($prompt, "PHASE2");

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

            $prompt = "Tu es expert AMDEC. Mode: $failureMode. Mesures: $preventionMeasures. Génère JSON: {\"efficacy_criterion\": \"...\", \"efficacy_measure\": \"...\"}";

            return $this->callMistral($prompt, "PHASE3");

        } catch (\Exception $e) {
            Log::error('❌ Phase 3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL MISTRAL API
     */
    protected function callMistral(string $prompt, string $phase = ''): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('❌ API KEY vide');
                return [];
            }

            Log::info("🚀 Mistral ($phase) - Prompt: " . substr($prompt, 0, 100) . "...");

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
                        'content' => 'Tu es un expert AMDEC/risques. Réponds UNIQUEMENT en JSON valide et structuré. Pas d\'explications, pas de Markdown, juste le JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.5
            ]);

            Log::info("📥 Status: {$response->status()}");

            if (!$response->successful()) {
                Log::error('❌ Mistral Error: ' . $response->status() . ' - ' . $response->body());
                return [];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            if (!$content) {
                Log::warning('⚠️ Empty content from Mistral');
                return [];
            }

            Log::info("📝 Raw response: " . substr($content, 0, 200));

            $parsed = $this->parseJSON($content);
            
            if ($parsed === null) {
                Log::warning('⚠️ Failed to parse JSON: ' . substr($content, 0, 200));
                return [];
            }

            return $parsed;

        } catch (\Exception $e) {
            Log::error('❌ Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔍 Parser JSON avec retry intelligent
     */
    protected function parseJSON(string $text): ?array
    {
        $text = trim($text);

        // Essai 1: JSON brut
        $json = json_decode($text, true);
        if (is_array($json) && count($json) > 0) {
            Log::info('✅ JSON parsed (raw)');
            return $json;
        }

        // Essai 2: JSON avec markdown backticks
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $m)) {
            $json = json_decode($m[1], true);
            if (is_array($json) && count($json) > 0) {
                Log::info('✅ JSON parsed (markdown)');
                return $json;
            }
        }

        // Essai 3: Extraire JSON entre { }
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json) && count($json) > 0) {
                Log::info('✅ JSON parsed (braces)');
                return $json;
            }
        }

        // Essai 4: Chercher tableau JSON [ ]
        if (preg_match('/\[.*\]/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json) && count($json) > 0) {
                Log::info('✅ JSON parsed (array)');
                return $json;
            }
        }

        Log::warning('❌ Could not parse JSON from: ' . substr($text, 0, 300));
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