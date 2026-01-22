<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * 🤖 RISK AI SUGGESTION SERVICE - MODULE VERSION
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Service complet pour:
 * ✅ Générer MULTIPLES suggestions de noms de risques (4 propositions)
 * ✅ Générer procédure de contrôle pour chaque risque
 * ✅ Fallback automatique si API indisponible
 * ✅ Logging détaillé
 * ✅ Parsing JSON résilient
 * 
 * Fichier: app/Modules/Risk/Core/Services/RiskAISuggestionService.php
 * 
 * ════════════════════════════════════════════════════════════════════════════════
 */
class RiskAISuggestionService
{
    private const API_KEY_ENV = 'ANTHROPIC_API_KEY';
    private const API_MODEL = 'claude-3-5-sonnet-20241022';
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_TIMEOUT = 30;
    private const MAX_SUGGESTIONS = 4;

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🎯 GÉNÉRER MULTIPLES SUGGESTIONS DE RISQUES
     * ════════════════════════════════════════════════════════════════════════════
     * 
     * Retourne 4 propositions différentes et spécifiques au contexte
     * 
     * @param string $processName - Nom du processus
     * @param string $activityName - Nom de l'activité
     * @param string $riskTypeName - Type de risque
     * @return array ['success' => bool, 'suggestions' => [...], 'mode' => 'ai'|'fallback']
     */
    public function generateMultipleSuggestions($processName, $activityName, $riskTypeName)
    {
        try {
            if (empty($processName) || empty($activityName) || empty($riskTypeName)) {
                Log::warning('⚠️ [Risk IA] Paramètres invalides');
                return $this->getFallbackSuggestions();
            }

            $apiKey = env(self::API_KEY_ENV);
            if (empty($apiKey)) {
                Log::info('ℹ️  [Risk IA] API non configurée, fallback utilisé');
                return $this->getFallbackSuggestions();
            }

            $prompt = $this->buildSuggestionsPrompt($processName, $activityName, $riskTypeName);

            Log::info('🚀 [Risk IA] Génération suggestions', [
                'process' => $processName,
                'activity' => $activityName,
                'risk_type' => $riskTypeName
            ]);

            $startTime = microtime(true);
            
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ])
            ->timeout(self::API_TIMEOUT)
            ->post(self::API_URL, [
                'model' => self::API_MODEL,
                'max_tokens' => 1000,
                'system' => 'Tu es expert en gestion des risques. Génère 4 propositions différentes. Réponds UNIQUEMENT en JSON valide.',
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (!$response->successful()) {
                Log::warning('⚠️  [Risk IA] Erreur API', ['status' => $response->status(), 'duration' => $duration . 'ms']);
                return $this->getFallbackSuggestions();
            }

            $data = $response->json();
            if (empty($data['content'][0]['text'])) {
                Log::warning('⚠️  [Risk IA] Réponse vide');
                return $this->getFallbackSuggestions();
            }

            $json = $this->parseAIResponse($data['content'][0]['text']);

            if (empty($json['risks']) || !is_array($json['risks'])) {
                Log::warning('⚠️  [Risk IA] Parsing échoué');
                return $this->getFallbackSuggestions();
            }

            $suggestions = $this->formatSuggestions($json['risks']);

            Log::info('✅ [Risk IA] Suggestions générées', ['count' => count($suggestions), 'duration' => $duration . 'ms']);

            return [
                'success' => true,
                'suggestions' => $suggestions,
                'mode' => 'ai'
            ];

        } catch (\Exception $e) {
            Log::error('❌ [Risk IA] Exception', ['error' => $e->getMessage()]);
            return $this->getFallbackSuggestions();
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🛡️ GÉNÉRER PROCÉDURE DE CONTRÔLE
     * ════════════════════════════════════════════════════════════════════════════
     */
    public function generateControlProcedure($riskLabel, $activityName, $processName)
    {
        try {
            if (empty($riskLabel) || empty($activityName) || empty($processName)) {
                Log::warning('⚠️  [Risk Control] Paramètres invalides');
                return $this->getFallbackControl();
            }

            $apiKey = env(self::API_KEY_ENV);
            if (empty($apiKey)) {
                Log::info('ℹ️  [Risk Control] API non configurée, fallback utilisé');
                return $this->getFallbackControl();
            }

            $prompt = $this->buildControlPrompt($riskLabel, $activityName, $processName);

            Log::info('🚀 [Risk Control] Génération procédure', [
                'risk' => substr($riskLabel, 0, 50),
                'activity' => $activityName
            ]);

            $startTime = microtime(true);

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ])
            ->timeout(self::API_TIMEOUT)
            ->post(self::API_URL, [
                'model' => self::API_MODEL,
                'max_tokens' => 500,
                'system' => 'Tu es expert en contrôle interne. Génère des procédures concrètes. Réponds UNIQUEMENT en JSON valide.',
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (!$response->successful()) {
                Log::warning('⚠️  [Risk Control] Erreur API', ['status' => $response->status()]);
                return $this->getFallbackControl();
            }

            $data = $response->json();
            if (empty($data['content'][0]['text'])) {
                Log::warning('⚠️  [Risk Control] Réponse vide');
                return $this->getFallbackControl();
            }

            $json = $this->parseAIResponse($data['content'][0]['text']);

            if (empty($json['control_procedure'])) {
                Log::warning('⚠️  [Risk Control] Parsing échoué');
                return $this->getFallbackControl();
            }

            $procedure = trim((string)$json['control_procedure']);
            $procedure = substr($procedure, 0, 500);

            Log::info('✅ [Risk Control] Procédure générée', ['length' => strlen($procedure), 'duration' => $duration . 'ms']);

            return [
                'success' => true,
                'control_procedure' => $procedure,
                'mode' => 'ai'
            ];

        } catch (\Exception $e) {
            Log::error('❌ [Risk Control] Exception', ['error' => $e->getMessage()]);
            return $this->getFallbackControl();
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 📝 CONSTRUIRE PROMPT - SUGGESTIONS MULTIPLES
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function buildSuggestionsPrompt($processName, $activityName, $riskTypeName)
    {
        return <<<PROMPT
Tu es expert en gestion des risques et AMDEC.

CONTEXTE:
- Processus: $processName
- Activité: $activityName
- Type: $riskTypeName

Génère 4 noms de risques DIFFÉRENTS et SPÉCIFIQUES (max 100 chars chacun).

RÉPONDS UNIQUEMENT EN JSON (pas de Markdown):
{
  "risks": [
    "Proposition 1",
    "Proposition 2",
    "Proposition 3",
    "Proposition 4"
  ]
}
PROMPT;
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🛡️ CONSTRUIRE PROMPT - PROCÉDURE DE CONTRÔLE
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function buildControlPrompt($riskLabel, $activityName, $processName)
    {
        return <<<PROMPT
Tu es expert en contrôle interne et audit.

Risque: $riskLabel
Activité: $activityName
Processus: $processName

Génère UNE procédure de contrôle (max 150 chars).

RÉPONDS UNIQUEMENT EN JSON:
{
  "control_procedure": "Procédure spécifique"
}
PROMPT;
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🔍 PARSER RÉPONSE JSON - RÉSILIENT
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function parseAIResponse($content)
    {
        $content = trim($content);

        // 1️⃣ Direct JSON
        $json = json_decode($content, true);
        if (is_array($json)) return $json;

        // 2️⃣ Nettoyer Markdown
        $cleaned = preg_replace('/```(?:json)?\s*\n?/i', '', $content);
        $cleaned = preg_replace('/```\s*$/i', '', $cleaned);
        $json = json_decode(trim($cleaned), true);
        if (is_array($json)) return $json;

        // 3️⃣ Extraire entre accolades
        if (preg_match('/\{[\s\S]*\}/', $cleaned, $matches)) {
            $json = json_decode($matches[0], true);
            if (is_array($json)) return $json;
        }

        Log::warning('⚠️  [JSON] Parse échoué', ['preview' => substr($content, 0, 100)]);
        return [];
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 📋 FORMATER SUGGESTIONS AVEC CODES AUTO
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function formatSuggestions($risks)
    {
        $suggestions = [];
        $baseCode = $this->getLastRiskCode();

        foreach ($risks as $index => $risk) {
            if ($index >= self::MAX_SUGGESTIONS) break;

            $nextNumber = $baseCode + $index + 1;
            $code = 'RC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $label = trim((string)$risk);
            $label = substr($label, 0, 200);

            if (strlen($label) < 5) continue;

            $suggestions[] = [
                'id' => $index + 1,
                'code' => $code,
                'label' => $label,
                'control_procedure' => ''
            ];
        }

        return $suggestions;
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🔢 RÉCUPÉRER DERNIER NUMÉRO DE CODE
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function getLastRiskCode()
    {
        try {
            $modelClass = 'App\Modules\Risk\Core\Models\Risk';
            if (class_exists($modelClass)) {
                $lastRisk = call_user_func([$modelClass, 'where'], 'code', 'like', 'RC-%')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastRisk && preg_match('/RC-(\d+)/', $lastRisk->code, $matches)) {
                    return intval($matches[1]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('⚠️  [Risk Code] Erreur', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🛡️ FALLBACK SUGGESTIONS
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function getFallbackSuggestions()
    {
        return [
            'success' => true,
            'suggestions' => [
                ['id' => 1, 'code' => 'RC-001', 'label' => 'Erreur de saisie ou d\'enregistrement des données', 'control_procedure' => ''],
                ['id' => 2, 'code' => 'RC-002', 'label' => 'Non-respect du processus ou procédure établis', 'control_procedure' => ''],
                ['id' => 3, 'code' => 'RC-003', 'label' => 'Absence de validation ou contrôle indépendant', 'control_procedure' => ''],
                ['id' => 4, 'code' => 'RC-004', 'label' => 'Défaut de documentation ou traçabilité', 'control_procedure' => '']
            ],
            'mode' => 'fallback'
        ];
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * 🛡️ FALLBACK CONTROL
     * ════════════════════════════════════════════════════════════════════════════
     */
    private function getFallbackControl()
    {
        return [
            'success' => true,
            'control_procedure' => 'Double vérification et validation selon les procédures établies',
            'mode' => 'fallback'
        ];
    }
}