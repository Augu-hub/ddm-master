<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAMDECAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api.anthropic.com/v1';
    protected $model = 'claude-3-5-sonnet-20241022'; // Dernier modèle Claude

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        
        if (!$this->apiKey) {
            Log::error('🚨 ANTHROPIC_API_KEY NOT CONFIGURED');
        } else {
            Log::info('✅ Claude API initialized: ' . substr($this->apiKey, 0, 15) . '...');
        }
    }

    /**
     * 💡 PHASE 1 — Suggestions avec Claude
     */
    public function suggestPhase1(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $activityName = $payload['activity_name'] ?? '';

            if (!$failureMode || !$activityName) {
                return [];
            }

            $userMessage = <<<PROMPT
Tu es expert AMDEC (Analyse des Modes de Défaillance et de leurs Effets).

**Contexte:**
- Activité: $activityName
- Mode de défaillance: $failureMode

**Génère EXACTEMENT 3 éléments en JSON VALIDE:**

1. **effects** (conséquences possibles du mode)
2. **causes** (origines possibles de ce mode)
3. **current_controls** (mesures de détection actuelles)

**Réponds UNIQUEMENT EN JSON, pas de texte supplémentaire:**

{
  "effects": "...",
  "causes": "...",
  "current_controls": "..."
}
PROMPT;

            return $this->callClaude($userMessage, "PHASE1");

        } catch (\Exception $e) {
            Log::error('❌ Claude Phase 1: ' . $e->getMessage());
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

            $userMessage = <<<PROMPT
Tu es expert en plans d'action correctives AMDEC.

**Données:**
- Mode: $failureMode
- Effets: $effects
- Causes: $causes

**Génère en JSON:**

{
  "prevention_measures": "Actions concrètes pour prévenir...",
  "action_responsible": "Type de fonction responsable..."
}

UNIQUEMENT JSON!
PROMPT;

            return $this->callClaude($userMessage, "PHASE2");

        } catch (\Exception $e) {
            Log::error('❌ Claude Phase 2: ' . $e->getMessage());
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

            $userMessage = <<<PROMPT
Tu es expert en validation d'efficacité corrective.

**Données:**
- Mode: $failureMode
- Mesures: $preventionMeasures

**Génère en JSON:**

{
  "efficacy_criterion": "KPI mesurable...",
  "efficacy_measure": "Comment mesurer/vérifier..."
}

UNIQUEMENT JSON!
PROMPT;

            return $this->callClaude($userMessage, "PHASE3");

        } catch (\Exception $e) {
            Log::error('❌ Claude Phase 3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL CLAUDE API
     */
    protected function callClaude(string $userMessage, string $phase = ''): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('❌ ANTHROPIC_API_KEY vide!');
                return [];
            }

            Log::info("🚀 Appel Claude API ($phase): Génération suggestions...");

            $startTime = microtime(true);

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ])
            ->timeout(60)
            ->post("{$this->baseUrl}/messages", [
                'model' => $this->model,
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'system' => 'Tu es un expert AMDEC français. Réponds UNIQUEMENT en JSON valide. Pas de Markdown, pas de texte supplémentaire.'
            ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("📥 Réponse Claude en {$duration}ms | Status: {$response->status()}");

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? $response->json('error') ?? 'Erreur inconnue';
                Log::error("❌ Claude Error ({$duration}ms): " . json_encode($error));
                return [];
            }

            $responseData = $response->json();
            
            // Claude retourne un array de contenu
            $content = $responseData['content'][0]['text'] ?? '';

            if (!$content) {
                Log::warning("⚠️ Réponse vide de Claude");
                return [];
            }

            Log::info("📝 Réponse brute Claude ({$duration}ms): " . substr($content, 0, 300));

            // 🔍 PARSE JSON
            $json = $this->parseJSON($content);

            if (!is_array($json)) {
                Log::warning("⚠️ Impossible de parser JSON. Réponse: " . $content);
                return [];
            }

            Log::info("✅ Claude suggestion générée en {$duration}ms", $json);
            return $json;

        } catch (\Exception $e) {
            Log::error('❌ Exception callClaude: ' . $e->getMessage());
            Log::error('Stack: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * 🔍 Parser JSON robuste
     */
    protected function parseJSON(string $text): ?array
    {
        // Try direct JSON
        $json = json_decode(trim($text), true);
        if (is_array($json)) {
            Log::debug("✓ JSON trouvé direct");
            return $json;
        }

        // Try markdown code blocks
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $text, $matches)) {
            $json = json_decode(trim($matches[1]), true);
            if (is_array($json)) {
                Log::debug("✓ JSON trouvé dans markdown");
                return $json;
            }
        }

        // Try to extract JSON object
        if (preg_match('/(\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\})/s', $text, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                Log::debug("✓ JSON extrait du texte");
                return $json;
            }
        }

        return null;
    }

    /**
     * 🔒 SÉCURITÉ
     */
    public static function validatePayloadSafety(array $payload): bool
    {
        $forbiddenKeys = ['user_id', 'entity_id', 'function_id', 'database', 'password', 'token'];
        
        foreach ($forbiddenKeys as $key) {
            if (array_key_exists($key, $payload) && !empty($payload[$key])) {
                Log::warning("🚨 TENTATIVE ENVOI DONNÉE SENSIBLE: $key");
                return false;
            }
        }

        return true;
    }
}