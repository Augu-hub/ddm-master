<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrokxAIAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api.x.ai/v1';

    public function __construct()
    {
        $this->apiKey = config('services.grok.api_key');
        Log::info("🔐 GrokxAIAssistant initialisé avec clé: " . substr($this->apiKey ?? '', 0, 20) . "...");
    }

    /**
     * 💡 PHASE 1 — xAI Grok (ULTRA RAPIDE!)
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

            return $this->callGrok($userMessage, "PHASE1");

        } catch (\Exception $e) {
            Log::error('❌ Grok Phase 1: ' . $e->getMessage());
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

UNIQUEMENT JSON, PAS DE TEXTE SUPPLÉMENTAIRE!
PROMPT;

            return $this->callGrok($userMessage, "PHASE2");

        } catch (\Exception $e) {
            Log::error('❌ Grok Phase 2: ' . $e->getMessage());
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
  "efficacy_criterion": "KPI mesurable pour valider...",
  "efficacy_measure": "Comment mesurer/vérifier..."
}

UNIQUEMENT JSON!
PROMPT;

            return $this->callGrok($userMessage, "PHASE3");

        } catch (\Exception $e) {
            Log::error('❌ Grok Phase 3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL XAI GROK — AVEC HEADER D'AUTH CORRECT!
     */
    protected function callGrok(string $userMessage, string $phase = ''): array
    {
        try {
            Log::info("🚀 Appel xAI Grok ($phase): Génération suggestions...");
            
            // 🔒 VÉRIFIER LA CLÉ API
            if (!$this->apiKey) {
                Log::error("❌ GROK_API_KEY n'est pas configurée!");
                return [];
            }

            Log::info("🔑 Clé API (premiers caractères): " . substr($this->apiKey, 0, 20) . "...");

            $startTime = microtime(true);

            // 📡 HEADERS CORRECTS POUR XAI
            $headers = [
                'Authorization' => 'Bearer ' . trim($this->apiKey),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            Log::info("📡 Envoi requête à: {$this->baseUrl}/chat/completions");

            // 🚀 REQUÊTE HTTP
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => 'grok-latest',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert AMDEC français. Réponds UNIQUEMENT en JSON valide. Pas de Markdown, pas de texte supplémentaire. Toujours du JSON pur.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $userMessage
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 600,
                    'stream' => false
                ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("✅ Réponse reçue en {$duration}ms | Status: " . $response->status());

            // 🔍 VÉRIFIER LE STATUT
            if (!$response->successful()) {
                $status = $response->status();
                $error = $response->json('error.message') 
                    ?? $response->json('error') 
                    ?? $response->json() 
                    ?? $response->body();
                
                Log::error("❌ xAI Grok Error ($status, {$duration}ms): " . json_encode($error));
                return [];
            }

            // 📝 RÉCUPÉRER LE CONTENU
            $text = $response->json('choices.0.message.content', '');
            
            if (!$text) {
                Log::warning("⚠️ Réponse vide de xAI Grok");
                return [];
            }

            Log::info("📝 Réponse brute xAI ({$duration}ms): " . substr($text, 0, 150));

            // 🔍 PARSE JSON
            $json = null;
            
            // Try markdown code blocks first
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
                $json = json_decode(trim($matches[1]), true);
                if (is_array($json)) {
                    Log::info("✓ JSON trouvé dans markdown");
                }
            }
            
            // Try direct JSON
            if (!is_array($json)) {
                $json = json_decode(trim($text), true);
                if (is_array($json)) {
                    Log::info("✓ JSON trouvé direct");
                }
            }
            
            // Try to extract JSON from text
            if (!is_array($json)) {
                if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $text, $matches)) {
                    $json = json_decode($matches[0], true);
                    if (is_array($json)) {
                        Log::info("✓ JSON extrait du texte");
                    }
                }
            }

            // 🔴 SI TOUJOURS PAS DE JSON
            if (!is_array($json)) {
                Log::error("❌ Impossible de parser JSON. Texte complet reçu: " . $text);
                return [];
            }

            Log::info("✅ xAI Grok suggestion générée en {$duration}ms", $json);
            return $json;

        } catch (\Exception $e) {
            Log::error('❌ Erreur callGrok: ' . $e->getMessage() . " | " . $e->getFile() . ":" . $e->getLine());
            return [];
        }
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