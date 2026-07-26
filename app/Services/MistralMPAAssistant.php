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
                      "Génère 8 noms de processus typiques et réalistes pour ce macro conforme en passation des marches.\n\n" .
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
     * 📋 Données pour un processus MODE SIMPLE
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
     * 🎯 Suggestions d'OBJECTIFS pour un processus "programme"
     */
    public function suggestObjectifs(array $payload): array
    {
        try {
            $processusName = $payload['processus_name'] ?? '';
            $macroKind = $payload['macro_kind'] ?? '';

            if (!$processusName) {
                Log::warning('⚠️ Missing processus_name');
                return [];
            }

            $prompt = "Tu es consultant senior en gestion de programmes et pilotage de la performance " .
                      "(référentiels PMI/Standard for Program Management, BPMN, ISO 9001).\n\n" .
                      "CONTEXTE\n" .
                      "Le processus suivant est un PROGRAMME (Macro-processus: $macroKind) : \"$processusName\".\n" .
                      "Un programme regroupe plusieurs AXES/VOLETS majeurs, chacun porteur d'un OBJECTIF distinct. " .
                      "Chaque objectif sera ensuite décliné, indépendamment des autres, en ses propres données " .
                      "d'entrée, données de sortie, ressources et activités.\n\n" .
                      "TÂCHE\n" .
                      " chaque phrase complète commançant par un verbe d'action".
                      "Identifie 3 à 5 objectifs qui, ENSEMBLE, couvrent l'intégralité du périmètre de ce " .
                      "programme, sans chevauchement entre eux (chaque objectif doit couvrir un axe distinct, " .
                      "pas une déclinaison d'un autre objectif de la liste).\n\n" .

                      "RÈGLES DE FORMULATION (format SMART)\n" .
                      "- Chaque objectif est un RÉSULTAT MESURABLE à atteindre, jamais une action ou une étape " .
                      "  (interdit: verbes d'action seuls comme \"Collecter\", \"Organiser\" ; " .
                      "  attendu: un état cible, ex. \"Taux de conformité des dossiers ≥ 95%\")\n" .
                      "- Le nom fait 25 à 40 mots, sans jargon interne, compréhensible par un non-spécialiste\n" .
                      "- Si un indicateur chiffré ou un seuil est pertinent, l'inclure directement dans le nom\n" .
                      "- La description () précise le périmètre exact de l'objectif et ce " .
                      "  qui le distingue des autres objectifs de la liste\n" .
                      "- Aucun objectif générique réutilisable pour n'importe quel programme : chaque objectif " .
                      "  doit contenir au moins un élément spécifique au programme \"$processusName\"\n\n" .
                      "EXEMPLE DE QUALITÉ ATTENDUE (programme fictif \"Modernisation du parc informatique\")\n" .
                      "{\n" .
                      "  \"objectifs\": [\n" .
                      "    {\"name\": \"100% des postes migrés vers le nouveau matériel\", " .
                      "\"description\": \"Couvre le remplacement physique de tous les postes de travail obsolètes sur l'ensemble des sites.\"},\n" .
                      "    {\"name\": \"Temps d'arrêt utilisateur réduit de 50%\", " .
                      "\"description\": \"Couvre la fiabilité et la continuité de service pendant et après la migration technique.\"},\n" .
                      "    {\"name\": \"Taux d'adoption des nouveaux outils supérieur à 90%\", " .
                      "\"description\": \"Couvre l'accompagnement au changement et la formation des utilisateurs finaux.\"}\n" .
                      "  ]\n" .
                      "}\n\n" .
                      "FORMAT DE RÉPONSE\n" .
                      "Réponds UNIQUEMENT en JSON valide, sans texte autour, au format:\n" .
                      "{\n" .
                      "  \"objectifs\": [\n" .
                      "    {\"name\": \"...\", \"description\": \"...\"}\n" .
                      "  ]\n" .
                      "}";

            $result = $this->callMistral($prompt, "OBJECTIFS_SUGGEST");
            Log::info('🎯 Objectifs suggestions result: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Objectifs Suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 📋 Données d'entrée/sortie/ressources POUR UN OBJECTIF (mode programme)
     */
    public function suggestObjectifData(array $payload): array
    {
        try {
            $objectifName = $payload['objectif_name'] ?? '';
            $processusName = $payload['processus_name'] ?? '';
            $macroKind = $payload['macro_kind'] ?? '';

            if (!$objectifName || !$processusName) {
                Log::warning('⚠️ Missing objectif_name or processus_name');
                return [];
            }

            $prompt = "Tu es expert en modélisation de processus métier. " .
                      "Processus (programme): \"$processusName\" (Macro: $macroKind).\n" .
                      "Objectif: \"$objectifName\".\n\n" .
                      "Génère les données d'entrée, de sortie et les ressources nécessaires SPÉCIFIQUEMENT " .
                      "pour atteindre CET objectif (pas pour le processus en général).\n\n" .
                      "Réponds UNIQUEMENT en JSON valide au format:\n" .
                      "{\n" .
                      "  \"inputs\": [\"Donnée 1\", \"Donnée 2\", ...],\n" .
                      "  \"outputs\": [\"Résultat 1\", \"Résultat 2\", ...],\n" .
                      "  \"resources\": [\"Ressource 1\", \"Ressource 2\", ...]\n" .
                      "}\n\n" .
                      "IMPORTANT:\n" .
                      "- Les entrées sont les données AVANT d'atteindre l'objectif\n" .
                      "- Les sorties sont les résultats APRÈS atteinte de l'objectif\n" .
                      "- Les ressources sont outils/systèmes/personnel nécessaires à CET objectif\n" .
                      "- Max 5 par catégorie, concis et spécifiques à l'objectif";

            $result = $this->callMistral($prompt, "OBJECTIF_DATA_SUGGEST");
            Log::info('📋 Objectif data suggestions result: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Objectif Data Suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🎨 Activités POUR UN OBJECTIF (mode programme)
     */
    public function suggestActivitesForObjectif(array $payload): array
    {
        try {
            $objectifName = $payload['objectif_name'] ?? '';
            $processusName = $payload['processus_name'] ?? '';
            $macroKind = $payload['macro_kind'] ?? '';

            if (!$objectifName || !$processusName) {
                Log::warning('⚠️ Missing objectif_name or processus_name');
                return [];
            }

            $prompt = "Tu es expert en décomposition d'objectifs en activités opérationnelles (BPMN/ISO9001). " .
                      "Processus: \"$processusName\" (Macro: $macroKind).\n" .
                      "Objectif: \"$objectifName\".\n\n" .
                      "Génère 4-6 activités concrètes qui permettent d'atteindre CET objectif précis.\n\n" .
                      "Réponds UNIQUEMENT en JSON valide au format:\n" .
                      "{\n" .
                      "  \"activites\": [\n" .
                      "    {\"name\": \"Activité 1\", \"description\": \"Description courte\"},\n" .
                      "    ...\n" .
                      "  ]\n" .
                      "}\n\n" .
                      "IMPORTANT:\n" .
                      "- Les activités doivent contribuer directement à l'objectif, pas au processus en général\n" .
                      "- Noms clairs et en verbe (Valider, Approuver, Enregistrer, etc.)\n" .
                      "- Descriptions brèves (1 phrase)\n" .
                      "- Ordre logique d'exécution";

            $result = $this->callMistral($prompt, "ACTIVITES_OBJECTIF_SUGGEST");
            Log::info('🎨 Activites (objectif) suggestions result: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Activites (objectif) Suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🎨 Activités mode simple — inchangé
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

    protected function parseJSON(string $text): ?array
    {
        $text = trim($text);

        $json = json_decode($text, true);
        if (is_array($json) && count($json) > 0) {
            Log::info('✅ JSON parsed (raw)');
            return $json;
        }

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $m)) {
            $json = json_decode($m[1], true);
            if (is_array($json) && count($json) > 0) {
                Log::info('✅ JSON parsed (markdown)');
                return $json;
            }
        }

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json) && count($json) > 0) {
                Log::info('✅ JSON parsed (braces)');
                return $json;
            }
        }

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