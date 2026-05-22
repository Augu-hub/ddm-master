<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * 🤖 RISK AI SUGGESTION SERVICE — Mistral AI
 * ════════════════════════════════════════════════════════════════════════════════
 * ✅ Suggestions de noms de risques via Mistral AI
 * ✅ Génération procédure de contrôle
 * ✅ Fallback automatique si API indisponible
 * ✅ Parsing JSON résilient
 * ✅ Les suggestions NE contiennent PAS de code (calculé côté frontend)
 * ════════════════════════════════════════════════════════════════════════════════
 */
class RiskAISuggestionService
{
    private const API_URL     = 'https://api.mistral.ai/v1/chat/completions';
    private const API_KEY_ENV = 'MISTRAL_API_KEY';
    private const API_MODEL   = 'mistral-small-latest';
    private const API_TIMEOUT = 45;
    private const MAX_SUGGESTIONS = 4;

    // ══════════════════════════════════════════════════════════════════════════
    // 🎯 GÉNÉRER MULTIPLES SUGGESTIONS DE RISQUES
    // ══════════════════════════════════════════════════════════════════════════
    /**
     * Retourne 4 propositions de risques sans code (le code est généré côté frontend).
     * Chaque suggestion contient : label, description, control_procedure.
     */
    public function generateMultipleSuggestions(string $processName, string $activityName, string $riskTypeName): array
    {
        try {
            if (empty($processName) || empty($activityName) || empty($riskTypeName)) {
                Log::warning('⚠️ [Risk IA] Paramètres manquants');
                return $this->getFallbackSuggestions();
            }

            $apiKey = env(self::API_KEY_ENV);
            if (empty($apiKey)) {
                Log::info('ℹ️ [Risk IA] Clé Mistral non configurée → fallback');
                return $this->getFallbackSuggestions();
            }

            $prompt = $this->buildSuggestionsPrompt($processName, $activityName, $riskTypeName);

            Log::info('🚀 [Risk IA] Génération suggestions Mistral', [
                'process'   => $processName,
                'activity'  => $activityName,
                'risk_type' => $riskTypeName,
            ]);

            $t0 = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(self::API_TIMEOUT)
            ->post(self::API_URL, [
                'model'       => self::API_MODEL,
                'max_tokens'  => 1200,
                'temperature' => 0.5,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es expert en gestion des risques et audit interne. Réponds UNIQUEMENT en JSON valide, sans Markdown ni balises de code.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $ms = round((microtime(true) - $t0) * 1000, 1);

            if (!$response->successful()) {
                Log::warning('⚠️ [Risk IA] Erreur Mistral', ['status' => $response->status(), 'ms' => $ms]);
                return $this->getFallbackSuggestions();
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? null;

            if (empty($text)) {
                Log::warning('⚠️ [Risk IA] Réponse vide');
                return $this->getFallbackSuggestions();
            }

            $json = $this->parseAIResponse($text);

            if (empty($json['risks']) || !is_array($json['risks'])) {
                Log::warning('⚠️ [Risk IA] JSON invalide', ['preview' => substr($text, 0, 100)]);
                return $this->getFallbackSuggestions();
            }

            $suggestions = $this->formatSuggestions($json['risks']);

            Log::info('✅ [Risk IA] Suggestions OK', ['count' => count($suggestions), 'ms' => $ms]);

            return [
                'success'     => true,
                'suggestions' => $suggestions,
                'mode'        => 'ai',
            ];

        } catch (\Exception $e) {
            Log::error('❌ [Risk IA] Exception', ['error' => $e->getMessage()]);
            return $this->getFallbackSuggestions();
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 🛡️ GÉNÉRER PROCÉDURE DE CONTRÔLE
    // ══════════════════════════════════════════════════════════════════════════
    public function generateControlProcedure(string $riskLabel, string $activityName, string $processName): array
    {
        try {
            if (empty($riskLabel) || empty($activityName) || empty($processName)) {
                return $this->getFallbackControl();
            }

            $apiKey = env(self::API_KEY_ENV);
            if (empty($apiKey)) {
                return $this->getFallbackControl();
            }

            $prompt = $this->buildControlPrompt($riskLabel, $activityName, $processName);

            $t0 = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(self::API_TIMEOUT)
            ->post(self::API_URL, [
                'model'       => self::API_MODEL,
                'max_tokens'  => 600,
                'temperature' => 0.3,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es expert en contrôle interne et audit. Réponds UNIQUEMENT en JSON valide, sans Markdown.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $ms = round((microtime(true) - $t0) * 1000, 1);

            if (!$response->successful()) {
                Log::warning('⚠️ [Risk Control] Erreur Mistral', ['status' => $response->status()]);
                return $this->getFallbackControl();
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? null;

            if (empty($text)) {
                return $this->getFallbackControl();
            }

            $json = $this->parseAIResponse($text);

            if (empty($json['control_procedure'])) {
                return $this->getFallbackControl();
            }

            $procedure = substr(trim((string) $json['control_procedure']), 0, 800);

            Log::info('✅ [Risk Control] Procédure OK', ['ms' => $ms, 'len' => strlen($procedure)]);

            return [
                'success'            => true,
                'control_procedure'  => $procedure,
                'mode'               => 'ai',
            ];

        } catch (\Exception $e) {
            Log::error('❌ [Risk Control] Exception', ['error' => $e->getMessage()]);
            return $this->getFallbackControl();
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PROMPTS
    // ══════════════════════════════════════════════════════════════════════════

    private function buildSuggestionsPrompt(string $processName, string $activityName, string $riskTypeName): string
    {
        return <<<PROMPT
Tu es expert en gestion des risques.

CONTEXTE D'ANALYSE :
- Processus    : $processName
- Activité     : $activityName
- Type de risque : $riskTypeName

MISSION : Génère exactement 4 risques différents, précis et contextualisés.

FORMAT DE RÉPONSE (JSON pur, sans Markdown) :
{
  "risks": [
    {
      "label": "Nom court du risque (max 120 caractères)",
      "description": "Explication du risque en 1-2 phrases (max 250 caractères)",
      "control_procedure": "Procédure de contrôle concrète en 1-2 phrases (max 200 caractères)"
    }
  ]
}

RÈGLES :
- Chaque risque doit être distinct et spécifique au contexte
- NE PAS inclure de codes ou numéros dans les propositions
- Labels clairs, opérationnels, en français professionnel
PROMPT;
    }

    private function buildControlPrompt(string $riskLabel, string $activityName, string $processName): string
    {
        return <<<PROMPT
Expert en contrôle interne et audit.

Risque identifié : $riskLabel
Activité         : $activityName
Processus        : $processName

Génère UNE procédure de contrôle concrète et opérationnelle.

FORMAT (JSON pur) :
{
  "control_procedure": "Description de la procédure (max 250 caractères)"
}
PROMPT;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PARSER JSON RÉSILIENT
    // ══════════════════════════════════════════════════════════════════════════
    private function parseAIResponse(string $content): array
    {
        $content = trim($content);

        // 1. Direct
        $json = json_decode($content, true);
        if (is_array($json)) return $json;

        // 2. Nettoyer balises Markdown
        $clean = preg_replace('/```(?:json)?\s*/i', '', $content);
        $clean = preg_replace('/```\s*/i', '', $clean);
        $json  = json_decode(trim($clean), true);
        if (is_array($json)) return $json;

        // 3. Extraire le bloc JSON
        if (preg_match('/\{[\s\S]*\}/u', $clean, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json)) return $json;
        }

        Log::warning('⚠️ [Risk IA] parseAIResponse échoué', ['preview' => substr($content, 0, 150)]);
        return [];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // FORMAT SUGGESTIONS — sans code (calculé côté frontend)
    // ══════════════════════════════════════════════════════════════════════════
    private function formatSuggestions(array $risks): array
    {
        $suggestions = [];
        foreach ($risks as $idx => $risk) {
            if ($idx >= self::MAX_SUGGESTIONS) break;

            // Risque peut être string (ancienne réponse) ou array
            if (is_string($risk)) {
                $label       = trim($risk);
                $description = '';
                $control     = '';
            } else {
                $label       = trim((string)($risk['label']       ?? ''));
                $description = trim((string)($risk['description'] ?? ''));
                $control     = trim((string)($risk['control_procedure'] ?? ''));
            }

            if (strlen($label) < 5) continue;

            $suggestions[] = [
                'id'                => $idx + 1,
                'label'             => substr($label,       0, 200),
                'description'       => substr($description, 0, 400),
                'control_procedure' => substr($control,     0, 400),
            ];
        }
        return $suggestions;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // FALLBACKS
    // ══════════════════════════════════════════════════════════════════════════
    private function getFallbackSuggestions(): array
    {
        return [
            'success'     => true,
            'suggestions' => [
                [
                    'id'                => 1,
                    'label'             => 'Erreur de saisie ou d\'enregistrement des données',
                    'description'       => 'Risque d\'inexactitude dans la capture ou le traitement des données liées à l\'activité.',
                    'control_procedure' => 'Double vérification systématique des saisies par un second opérateur ou une règle de validation automatique.',
                ],
                [
                    'id'                => 2,
                    'label'             => 'Non-respect du processus ou des procédures établis',
                    'description'       => 'Les étapes définies ne sont pas suivies, entraînant des écarts opérationnels.',
                    'control_procedure' => 'Revue périodique de conformité et sensibilisation des équipes aux procédures en vigueur.',
                ],
                [
                    'id'                => 3,
                    'label'             => 'Absence de validation ou de contrôle indépendant',
                    'description'       => 'Les traitements ne font l\'objet d\'aucune revue par une partie non impliquée dans leur exécution.',
                    'control_procedure' => 'Mise en place d\'un circuit de validation à deux niveaux (opérateur + superviseur).',
                ],
                [
                    'id'                => 4,
                    'label'             => 'Défaut de documentation ou de traçabilité',
                    'description'       => 'Les actions réalisées ne sont pas tracées, rendant l\'audit impossible.',
                    'control_procedure' => 'Obligation d\'enregistrement horodaté de chaque opération dans le système d\'information.',
                ],
            ],
            'mode'        => 'fallback',
        ];
    }

    private function getFallbackControl(): array
    {
        return [
            'success'           => true,
            'control_procedure' => 'Double vérification et validation par un responsable selon les procédures établies.',
            'mode'              => 'fallback',
        ];
    }
}