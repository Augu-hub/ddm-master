<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaAMDECAssistant
{
    protected $baseUrl = 'http://localhost:11434/api/generate';
    protected $model = 'llama2'; // Ou 'mistral', 'neural-chat', etc.

    public function __construct()
    {
        Log::info("🚀 Ollama AMDEC Assistant initialisé (modèle: {$this->model})");
    }

    /**
     * 💡 PHASE 1 — Suggestions AVEC OLLAMA (100% GRATUIT!)
     */
    public function suggestPhase1(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $activityName = $payload['activity_name'] ?? '';

            if (!$failureMode || !$activityName) {
                return [];
            }

            $prompt = <<<PROMPT
Tu es expert AMDEC (Analyse des Modes de Défaillance et de leurs Effets).

Contexte:
- Activité: $activityName
- Mode de défaillance: $failureMode

Génère EXACTEMENT 3 éléments en JSON VALIDE:

1. effects (conséquences possibles du mode)
2. causes (origines possibles de ce mode)
3. current_controls (mesures de détection actuelles)

Réponds UNIQUEMENT en JSON, pas de texte supplémentaire:

{
  "effects": "...",
  "causes": "...",
  "current_controls": "..."
}
PROMPT;

            return $this->callOllama($prompt, "PHASE1");

        } catch (\Exception $e) {
            Log::error('❌ Ollama Phase 1: ' . $e->getMessage());
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

            $prompt = <<<PROMPT
Tu es expert en plans d'action correctives AMDEC.

Données:
- Mode: $failureMode
- Effets: $effects
- Causes: $causes

Génère en JSON:

{
  "prevention_measures": "Actions concrètes pour prévenir...",
  "action_responsible": "Type de fonction responsable..."
}

UNIQUEMENT JSON!
PROMPT;

            return $this->callOllama($prompt, "PHASE2");

        } catch (\Exception $e) {
            Log::error('❌ Ollama Phase 2: ' . $e->getMessage());
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

            $prompt = <<<PROMPT
Tu es expert en validation d'efficacité corrective.

Données:
- Mode: $failureMode
- Mesures: $preventionMeasures

Génère en JSON:

{
  "efficacy_criterion": "KPI mesurable...",
  "efficacy_measure": "Comment mesurer/vérifier..."
}

UNIQUEMENT JSON!
PROMPT;

            return $this->callOllama($prompt, "PHASE3");

        } catch (\Exception $e) {
            Log::error('❌ Ollama Phase 3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL OLLAMA — 100% GRATUIT & LOCAL!
     */
    protected function callOllama(string $prompt, string $phase = ''): array
    {
        try {
            Log::info("🚀 Appel Ollama ($phase): Génération suggestions...");

            $startTime = microtime(true);

            // Test de connexion d'abord
            try {
                $testResponse = Http::timeout(5)->get('http://localhost:11434/api/tags');
                if (!$testResponse->successful()) {
                    Log::error('❌ Ollama ne répond pas. Assurez-vous qu\'Ollama est lancé: ollama serve');
                    return [];
                }
            } catch (\Exception $e) {
                Log::error('❌ Ollama non accessible à localhost:11434. Lancez: ollama serve');
                return [];
            }

            // Appel à Ollama
            $response = Http::timeout(120)->post('http://localhost:11434/api/generate', [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'temperature' => 0.3,
            ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("📥 Réponse Ollama en {$duration}ms | Status: {$response->status()}");

            if (!$response->successful()) {
                Log::error("❌ Ollama Error ({$duration}ms): " . $response->body());
                return [];
            }

            $responseData = $response->json();
            $text = $responseData['response'] ?? '';

            if (!$text) {
                Log::warning("⚠️ Réponse vide de Ollama");
                return [];
            }

            Log::info("📝 Réponse brute Ollama ({$duration}ms): " . substr($text, 0, 300));

            // 🔍 PARSE JSON
            $json = $this->parseJSON($text);

            if (!is_array($json)) {
                Log::warning("⚠️ Impossible de parser JSON. Réponse: " . $text);
                return [];
            }

            Log::info("✅ Ollama suggestion générée en {$duration}ms", $json);
            return $json;

        } catch (\Exception $e) {
            Log::error('❌ Exception callOllama: ' . $e->getMessage());
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