<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralMPAAssistant
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
            Log::info('✅ Mistral API initialized for MPA');
        }
    }

    /**
     * 🎯 GÉNÉRER SUGGESTIONS PROCESSUS
     * En fonction du macro (Direction / Réalisation / Support)
     */
    public function suggestProcessus(array $payload): array
    {
        try {
            $macroKind = $payload['macro_kind'] ?? '';
            $macroName = $payload['macro_name'] ?? '';

            if (!$macroKind || !$macroName) {
                Log::warning('⚠️ Missing macro_kind or macro_name');
                return [];
            }

            $prompt = "Tu es expert en modélisation de processus métier (BPMN/ISO9001). " .
                      "Macro-processus: \"$macroKind\" ($macroName).\n\n" .
                      "Génère 8 noms de processus typiques et réalistes pour ce macro.\n\n" .
                      "Réponds UNIQUEMENT en JSON valide au format:\n" .
                      "{\n" .
                      "  \"processus\": [\n" .
                      "    {\"name\": \"Processus 1\", \"description\": \"Description courte\"},\n" .
                      "    ...\n" .
                      "  ]\n" .
                      "}\n\n" .
                      "IMPORTANT:\n" .
                      "- Les noms doivent être clairs et métier\n" .
                      "- Les descriptions doivent être brèves (1 phrase)\n" .
                      "- Adaptés au macro: Direction=pilotage/gouvernance, Réalisation=cœur métier, Support=soutien";

            $result = $this->callMistral($prompt, "PROCESSUS_SUGGEST");
            
            Log::info('🎯 Processus suggestions result: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Processus Suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 📋 GÉNÉRER SUGGESTIONS DONNÉES (INPUT/OUTPUT/RESOURCES)
     * En fonction du processus
     */
    public function suggestProcessusData(array $payload): array
    {
        try {
            $processusName = $payload['processus_name'] ?? '';
            $macroKind = $payload['macro_kind'] ?? '';

            if (!$processusName) {
                Log::warning('⚠️ Missing processus_name');
                return [];
            }

            $prompt = "Tu es expert en modélisation de processus métier. " .
                      "Processus: \"$processusName\" (Macro: $macroKind).\n\n" .
                      "Génère les données d'entrée, de sortie et les ressources nécessaires.\n\n" .
                      "Réponds UNIQUEMENT en JSON valide au format:\n" .
                      "{\n" .
                      "  \"inputs\": [\"Donnée 1\", \"Donnée 2\", ...],\n" .
                      "  \"outputs\": [\"Résultat 1\", \"Résultat 2\", ...],\n" .
                      "  \"resources\": [\"Ressource 1\", \"Ressource 2\", ...]\n" .
                      "}\n\n" .
                      "IMPORTANT:\n" .
                      "- Les entrées sont les données AVANT le processus\n" .
                      "- Les sorties sont les données APRÈS le processus\n" .
                      "- Les ressources sont outils/systèmes/personnel nécessaires\n" .
                      "- Max 5 par catégorie, concis et spécifiques";

            $result = $this->callMistral($prompt, "DATA_SUGGEST");
            
            Log::info('📋 Data suggestions result: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Data Suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🎨 GÉNÉRER SUGGESTIONS ACTIVITÉS
     * En fonction du processus
     */
    public function suggestActivites(array $payload): array
    {
        try {
            $processusName = $payload['processus_name'] ?? '';
            $macroKind = $payload['macro_kind'] ?? '';

            if (!$processusName) {
                Log::warning('⚠️ Missing processus_name');
                return [];
            }

            $prompt = "Tu es expert en décomposition de processus en activités (BPMN/ISO9001). " .
                      "Processus: \"$processusName\" (Macro: $macroKind).\n\n" .
                      "Génère 6-8 activités principales qui composent ce processus.\n\n" .
                      "Réponds UNIQUEMENT en JSON valide au format:\n" .
                      "{\n" .
                      "  \"activites\": [\n" .
                      "    {\"name\": \"Activité 1\", \"description\": \"Description courte\"},\n" .
                      "    ...\n" .
                      "  ]\n" .
                      "}\n\n" .
                      "IMPORTANT:\n" .
                      "- Les activités doivent être les étapes clés du processus\n" .
                      "- Noms clairs et en verbe (Valider, Approuver, Enregistrer, etc.)\n" .
                      "- Descriptions brèves (1 phrase)\n" .
                      "- Ordre logique d'exécution\n" .
                      "- Spécifiques au processus";

            $result = $this->callMistral($prompt, "ACTIVITES_SUGGEST");
            
            Log::info('🎨 Activites suggestions result: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Activites Suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔌 APPEL MISTRAL API
     */
    protected function callMistral(string $prompt, string $context = ''): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('❌ API KEY vide');
                return [];
            }

            Log::info("🚀 Mistral MPA ($context) - Prompt: " . substr($prompt, 0, 100) . "...");

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
                        'content' => 'Tu es un expert en modélisation de processus métier et architecture d\'entreprise. Réponds UNIQUEMENT en JSON valide et structuré. Pas d\'explications, pas de Markdown, juste le JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.6
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
        $forbidden = ['user_id', 'entity_id', 'database', 'password', 'token', 'secret'];
        foreach ($forbidden as $key) {
            if (!empty($payload[$key])) {
                Log::warning("🚨 SENSITIVE: $key");
                return false;
            }
        }
        return true;
    }
}