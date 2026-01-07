<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AMDECOpenAIAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * 💡 PHASE 1 — Suggestions UNIQUEMENT sur: Mode, Activité
     * ❌ NE JAMAIS envoyer: ID utilisateur, données sensibles, logs, configs
     */
    public function suggestPhase1(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $activityName = $payload['activity_name'] ?? ''; // NOM SEULEMENT, pas d'ID

            if (!$failureMode || !$activityName) {
                return [];
            }

            // 🔒 DONNÉES MINIMALES ENVOYÉES À OPENAI
            $userMessage = <<<PROMPT
Tu es expert AMDEC (Analyse des Modes de Défaillance).

**Données envoyées:**
- Activité: $activityName
- Mode de défaillance: $failureMode

**Génère 3 éléments UNIQUEMENT:**
1. Effets (conséquences du mode)
2. Causes (origines possibles)
3. Contrôles actuels (mesures de détection)

Réponds en JSON valide:
{
  "effects": "...",
  "causes": "...",
  "current_controls": "..."
}

NE GÉNÉREZ QUE LE JSON.
PROMPT;

            $suggestions = $this->callOpenAI(
                $userMessage,
                "Générer suggestions AMDEC Phase 1"
            );

            return $suggestions;

        } catch (\Exception $e) {
            Log::error('❌ AMDECOpenAIAssistant.suggestPhase1: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 💡 PHASE 2 — Suggestions plan d'action
     * ❌ NE JAMAIS envoyer: données clients, employés, configs système
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

            // 🔒 DONNÉES MINIMALES
            $userMessage = <<<PROMPT
Tu es expert en plans d'action correctives.

**Données envoyées:**
- Mode défaillance: $failureMode
- Effets: $effects
- Causes: $causes

**Génère:**
1. Mesures de prévention (actions concrètes)
2. Responsable suggéré (type de fonction: "Responsable QA", "Manager production", etc.)

Réponds en JSON:
{
  "prevention_measures": "...",
  "action_responsible": "..."
}

NE GÉNÉREZ QUE LE JSON.
PROMPT;

            return $this->callOpenAI($userMessage, "Plan d'action PHASE2");

        } catch (\Exception $e) {
            Log::error('❌ AMDECOpenAIAssistant.suggestPhase2: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 💡 PHASE 3 — Suggestions mesures d'efficacité
     * ❌ NE JAMAIS envoyer: données réelles corrigées
     */
    public function suggestPhase3(array $payload): array
    {
        try {
            $failureMode = $payload['failure_mode'] ?? '';
            $preventionMeasures = $payload['prevention_measures'] ?? '';

            if (!$failureMode) {
                return [];
            }

            // 🔒 DONNÉES MINIMALES
            $userMessage = <<<PROMPT
Tu es expert en validation d'efficacité corrective.

**Données envoyées:**
- Mode défaillance: $failureMode
- Mesures prévention: $preventionMeasures

**Génère:**
1. Critère d'efficacité (KPI mesurable)
2. Mesure d'efficacité (comment vérifier)

Réponds en JSON:
{
  "efficacy_criterion": "...",
  "efficacy_measure": "..."
}

NE GÉNÉREZ QUE LE JSON.
PROMPT;

            return $this->callOpenAI($userMessage, "Efficacité PHASE3");

        } catch (\Exception $e) {
            Log::error('❌ AMDECOpenAIAssistant.suggestPhase3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL OPENAI — SÉCURISÉ
     */
    protected function callOpenAI(string $userMessage, string $description = ''): array
    {
        try {
            Log::info("🤖 Appel OpenAI: $description");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert AMDEC. Réponds UNIQUEMENT en JSON valide. Pas de texte supplémentaire.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Erreur API OpenAI';
                Log::error('❌ OpenAI Error: ' . $error);
                return [];
            }

            $text = $response->json('choices.0.message.content', '');
            
            // 🔍 PARSE JSON
            if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
                $json = json_decode($matches[1], true);
            } else {
                $json = json_decode($text, true);
            }

            if (!is_array($json)) {
                Log::warning('⚠️ Réponse non-JSON: ' . substr($text, 0, 100));
                return [];
            }

            Log::info("✅ OpenAI suggestion générée", $json);
            return $json;

        } catch (\Exception $e) {
            Log::error('❌ Erreur callOpenAI: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔒 SÉCURITÉ — Vérifier aucune donnée sensible
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