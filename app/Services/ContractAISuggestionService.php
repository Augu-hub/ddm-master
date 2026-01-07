<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContractAISuggestionService
{
    /**
     * 🤖 Générer suggestions IA + INDICATEURS AUTO
     * 
     * ✅ Génère les suggestions complètes
     * ✅ Génère les INDICATEURS d'activité et performance
     * ✅ Rempli les attentes automatiquement
     * ✅ Protections complètes
     */
    public function generateSuggestions($functionId, $functionName, $processId, $processName, $outputId, $outputLabel)
    {
        try {
            // 1️⃣ VALIDATION
            if (empty($functionId) || empty($functionName)) {
                Log::warning('⚠️ [IA] Fonction invalide', [
                    'function_id' => $functionId,
                    'function_name' => $functionName
                ]);
                return [
                    'success' => false,
                    'error' => 'Fonction invalide'
                ];
            }

            if (empty($processId) || empty($outputId)) {
                Log::warning('⚠️ [IA] Processus/Sortie invalide', [
                    'process_id' => $processId,
                    'output_id' => $outputId
                ]);
                return [
                    'success' => false,
                    'error' => 'Processus/Sortie invalide'
                ];
            }

            // 2️⃣ VÉRIFIER API KEY
            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) {
                Log::error('❌ [IA] MISTRAL_API_KEY non configurée');
                return [
                    'success' => false,
                    'error' => 'Service IA non configuré'
                ];
            }

            // 3️⃣ DÉTECTER TYPE FONCTION
            $functionType = $this->detectFunctionType($functionName);
            
            // 4️⃣ CONSTRUIRE PROMPT AMÉLIORÉ
            $prompt = $this->buildPromptWithIndicators($functionType, $functionName, $processName, $outputLabel);

            Log::info('🤖 [IA] Génération suggestions + indicateurs', [
                'function' => $functionName,
                'type' => $functionType,
                'process' => $processName,
                'output' => $outputLabel
            ]);

            // 5️⃣ APPELER MISTRAL API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->timeout(30)
            ->retry(2, 1000)
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-small-latest',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en processus métier et gestion. Réponds UNIQUEMENT en JSON valide. Pas de Markdown, pas d\'explications, juste du JSON pur.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.5
            ]);

            // 6️⃣ VÉRIFIER RÉPONSE
            if ($response->failed()) {
                Log::error('❌ [IA] Erreur Mistral API', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return [
                    'success' => true,
                    'suggestions' => $this->getFallbackSuggestions($functionType, $functionName)
                ];
            }

            $data = $response->json();

            // 7️⃣ EXTRAIRE CONTENU
            if (empty($data['choices'][0]['message']['content'])) {
                Log::error('❌ [IA] Réponse invalide', ['response' => json_encode($data)]);
                return [
                    'success' => true,
                    'suggestions' => $this->getFallbackSuggestions($functionType, $functionName)
                ];
            }

            $content = $data['choices'][0]['message']['content'];

            Log::info('📝 [IA] Réponse Mistral', [
                'content_length' => strlen($content),
                'preview' => substr($content, 0, 100)
            ]);

            // 8️⃣ PARSER RÉPONSE JSON (AVEC INDICATEURS)
            $suggestions = $this->parseResponse($content, $functionType, $functionName);

            if (empty($suggestions['expectations'])) {
                Log::warning('⚠️ [IA] Parsing échoué, utilisation fallback');
                $suggestions = $this->getFallbackSuggestions($functionType, $functionName);
            }

            Log::info('✅ [IA] Suggestions + Indicateurs générés', [
                'function' => $functionName,
                'expectations_count' => count($suggestions['expectations'] ?? []),
                'activity_indicators' => count($suggestions['activity_indicators'] ?? []),
                'performance_indicators' => count($suggestions['performance_indicators'] ?? [])
            ]);

            return [
                'success' => true,
                'suggestions' => $suggestions
            ];

        } catch (\Exception $e) {
            Log::error('❌ [IA] Exception', [
                'error' => $e->getMessage(),
                'function' => $functionName,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => true,
                'suggestions' => $this->getFallbackSuggestions('operational', $functionName)
            ];
        }
    }

    /**
     * 🏷️ Déterminer type de fonction (7 types)
     */
    private function detectFunctionType($functionName)
    {
        $name = strtolower($functionName);

        if (preg_match('/(directeur|pdg|président|chief|executive|ceo)/i', $name)) {
            return 'leadership';
        }

        if (preg_match('/(manager|chef|superviseur|head|responsable|coordinatrice)/i', $name)) {
            return 'management';
        }

        if (preg_match('/(coordinateur|chef.*projet|project.*manager|orchestr|scrum)/i', $name)) {
            return 'coordination';
        }

        if (preg_match('/(qa|qualité|quality|contrôle|audit|validation|assurance)/i', $name)) {
            return 'quality';
        }

        if (preg_match('/(support|assistance|help|customer|service|centre.*appel)/i', $name)) {
            return 'support';
        }

        if (preg_match('/(analyste|consultant|analyst|expert|data)/i', $name)) {
            return 'analysis';
        }

        return 'operational';
    }

    /**
     * 📝 Construire prompt AVEC INDICATEURS
     */
    private function buildPromptWithIndicators($type, $functionName, $processName, $outputLabel)
    {
        $basePrompt = <<<PROMPT
Tu es un expert en processus métier et gestion. Analyse la sortie suivante et génère des suggestions COMPLÈTES en JSON.

CONTEXTE:
- Processus: $processName
- Fonction: $functionName (Type: $type)
- Sortie/Donnée: $outputLabel

DEMANDE:
Génère JSON VALIDE (sans Markdown, sans code fence):

{
  "expectations": ["Attente 1", "Attente 2", "Attente 3"],
  "user_recommendations": [
    {
      "user_name": "Titre/Rôle recommandé",
      "job_title": "Titre du poste",
      "user_id": 1
    }
  ],
  "description": "Description claire de la sortie",
  "quality_criteria": ["Critère 1", "Critère 2"],
  "validation_steps": ["Étape 1", "Étape 2"],
  "activity_indicators": ["Indicateur activité 1", "Indicateur activité 2", "Indicateur activité 3"],
  "performance_indicators": ["Indicateur perf 1", "Indicateur perf 2", "Indicateur perf 3"]
}

IMPORTANT:
- JSON VALIDE ET PARSABLE
- expectations: array minimum 2-3 chaînes
- activity_indicators: array 3-4 indicateurs d'activité spécifiques à la fonction
- performance_indicators: array 3-4 indicateurs de performance spécifiques à la fonction
- user_recommendations: array d'objets
- Français obligatoire
- Pas de Markdown
- Pas de code fence
PROMPT;

        // Prompts spécialisés par type
        switch ($type) {
            case 'leadership':
                return $basePrompt . "\nSPÉCIALISATION LEADERSHIP:\n- Focus: Vision stratégique, alignement global, décisions majeures\n- Indicateurs activité: Décisions prises, alignements stratégiques, communications\n- Indicateurs perf: Impact stratégique, alignement objectifs, satisfaction stakeholders";

            case 'management':
                return $basePrompt . "\nSPÉCIALISATION MANAGEMENT:\n- Focus: Supervision opérationnelle, coordination équipes\n- Indicateurs activité: Réunions d'équipe, rapports d'avancement, escalades\n- Indicateurs perf: Atteinte objectifs, satisfaction équipe, productivité";

            case 'coordination':
                return $basePrompt . "\nSPÉCIALISATION COORDINATION:\n- Focus: Synchronisation, orchestration, planification\n- Indicateurs activité: Synchronisations, coordinations cross-team, planifications\n- Indicateurs perf: Respect délais, absence retards, efficacité synchronisation";

            case 'quality':
                return $basePrompt . "\nSPÉCIALISATION QUALITÉ:\n- Focus: Vérification, validation, conformité\n- Indicateurs activité: Tests effectués, audits, validations\n- Indicateurs perf: Taux couverture QA, défauts trouvés, conformité certifications";

            case 'support':
                return $basePrompt . "\nSPÉCIALISATION SUPPORT:\n- Focus: Assistance, réactivité, satisfaction\n- Indicateurs activité: Incidents traités, temps réponse moyen, tickets fermés\n- Indicateurs perf: Satisfaction utilisateurs, MTTR, taux résolution premier niveau";

            case 'analysis':
                return $basePrompt . "\nSPÉCIALISATION ANALYSE:\n- Focus: Analyse détaillée, insight, recommandation\n- Indicateurs activité: Analyses complétées, rapports générés, insights découverts\n- Indicateurs perf: Qualité recommandations, utilisation insights, impact décisions";

            default: // operational
                return $basePrompt . "\nSPÉCIALISATION OPÉRATIONNELLE:\n- Focus: Exécution, détails techniques, conformité\n- Indicateurs activité: Tâches complétées, procédures respectées, erreurs identifiées\n- Indicateurs perf: Qualité exécution, respect standards, traçabilité complète";
        }
    }

    /**
     * 🔍 Parser la réponse Mistral AVEC INDICATEURS
     */
    private function parseResponse($content, $type, $functionName)
    {
        try {
            $content = trim($content);

            // 1️⃣ Essai direct JSON
            $json = json_decode($content, true);
            if (is_array($json) && !empty($json['expectations'])) {
                Log::info('✅ [JSON] Direct parse OK');
                return $this->validateAndCleanJSON($json, $type, $functionName);
            }

            // 2️⃣ Extraire JSON entre accolades
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $json = json_decode($matches[0], true);
                if (is_array($json) && !empty($json['expectations'])) {
                    Log::info('✅ [JSON] Braces extraction OK');
                    return $this->validateAndCleanJSON($json, $type, $functionName);
                }
            }

            // 3️⃣ Fallback
            Log::warning('⚠️ [JSON] Parse échoué, fallback utilisé', [
                'content_preview' => substr($content, 0, 200)
            ]);
            return $this->getFallbackSuggestions($type, $functionName);

        } catch (\Exception $e) {
            Log::warning('⚠️ [JSON] Exception parsing', ['error' => $e->getMessage()]);
            return $this->getFallbackSuggestions($type, $functionName);
        }
    }

    /**
     * ✔️ Valider et nettoyer JSON AVEC INDICATEURS
     */
    private function validateAndCleanJSON($json, $type, $functionName)
    {
        // Nettoyer expectations
        $expectations = array_filter(
            array_map('trim', (array)($json['expectations'] ?? [])),
            function($e) { 
                return !empty($e) && is_string($e) && strlen($e) > 3 && strlen($e) < 300; 
            }
        );
        
        if (count($expectations) < 2) {
            $expectations = $this->getDefaultExpectations($type);
        }

        // Nettoyer activity_indicators ✅
        $activityIndicators = array_filter(
            array_map('trim', (array)($json['activity_indicators'] ?? [])),
            function($e) { 
                return !empty($e) && is_string($e) && strlen($e) > 3 && strlen($e) < 200; 
            }
        );
        
        if (count($activityIndicators) < 2) {
            $activityIndicators = $this->getDefaultActivityIndicators($type);
        }

        // Nettoyer performance_indicators ✅
        $perfIndicators = array_filter(
            array_map('trim', (array)($json['performance_indicators'] ?? [])),
            function($e) { 
                return !empty($e) && is_string($e) && strlen($e) > 3 && strlen($e) < 200; 
            }
        );
        
        if (count($perfIndicators) < 2) {
            $perfIndicators = $this->getDefaultPerformanceIndicators($type);
        }

        // Nettoyer user_recommendations
        $userRecs = [];
        if (!empty($json['user_recommendations']) && is_array($json['user_recommendations'])) {
            foreach ($json['user_recommendations'] as $rec) {
                if (!empty($rec['user_name']) || !empty($rec['job_title'])) {
                    $userRecs[] = [
                        'user_name' => substr((string)($rec['user_name'] ?? 'À déterminer'), 0, 100),
                        'job_title' => substr((string)($rec['job_title'] ?? ucfirst($type)), 0, 100),
                        'user_id' => null
                    ];
                }
            }
        }

        if (empty($userRecs)) {
            $userRecs = [[
                'user_name' => 'À déterminer',
                'job_title' => ucfirst($type),
                'user_id' => null
            ]];
        }

        return [
            'expectations' => array_values($expectations),
            'user_recommendations' => $userRecs,
            'description' => substr((string)($json['description'] ?? ''), 0, 500),
            'quality_criteria' => array_slice(array_filter((array)($json['quality_criteria'] ?? [])), 0, 3),
            'validation_steps' => array_slice(array_filter((array)($json['validation_steps'] ?? [])), 0, 3),
            'activity_indicators' => array_values($activityIndicators),
            'performance_indicators' => array_values($perfIndicators)
        ];
    }

    /**
     * 📋 Attentes par défaut selon type
     */
    private function getDefaultExpectations($type)
    {
        $defaults = [
            'leadership' => [
                'Validation stratégique et approbation de principe',
                'Alignement avec la direction et les objectifs globaux',
                'Responsabilité et accountability sur les résultats'
            ],
            'management' => [
                'Suivi régulier et rapports d\'avancement',
                'Signalement immédiat des anomalies et risques',
                'Support à l\'équipe et gestion des ressources'
            ],
            'coordination' => [
                'Coordination efficace entre les équipes',
                'Respect des délais et jalons définis',
                'Synchronisation des dépendances et interfaces'
            ],
            'quality' => [
                'Validation complète de la qualité et conformité',
                'Checklist QA et critères d\'acceptation',
                'Approbation avant déploiement/livraison'
            ],
            'support' => [
                'Documentation complète et claire',
                'Support utilisateur et assistance rapide',
                'Résolution des incidents et questions'
            ],
            'analysis' => [
                'Analyse détaillée et rapports insights',
                'Métriques et données pour la décision',
                'Recommandations basées sur les faits'
            ],
            'operational' => [
                'Exécution précise et conforme aux procédures',
                'Documentation et tracabilité complète',
                'Respect des normes et standards définis'
            ]
        ];

        return $defaults[$type] ?? $defaults['operational'];
    }

    /**
     * 📊 Indicateurs d'Activité par défaut
     */
    private function getDefaultActivityIndicators($type)
    {
        $defaults = [
            'leadership' => [
                'Décisions stratégiques prises par mois',
                'Alignements stratégiques validés',
                'Réunions de direction mensuelles'
            ],
            'management' => [
                'Réunions d\'équipe hebdomadaires',
                'Rapports d\'avancement réalisés',
                'Escalades de risques gérées'
            ],
            'coordination' => [
                'Synchronisations cross-team effectuées',
                'Planifications de projet complétées',
                'Dépendances inter-équipes gérées'
            ],
            'quality' => [
                'Tests de qualité effectués',
                'Audits de conformité réalisés',
                'Validations QA complétées'
            ],
            'support' => [
                'Incidents traités par jour',
                'Tickets support fermés',
                'Temps moyen de réponse'
            ],
            'analysis' => [
                'Analyses complétées par période',
                'Rapports insights générés',
                'Données analysées mensuellement'
            ],
            'operational' => [
                'Tâches opérationnelles complétées',
                'Procédures exécutées correctement',
                'Erreurs identifiées et corrigées'
            ]
        ];

        return $defaults[$type] ?? $defaults['operational'];
    }

    /**
     * 🎯 Indicateurs de Performance par défaut
     */
    private function getDefaultPerformanceIndicators($type)
    {
        $defaults = [
            'leadership' => [
                'Impact stratégique des décisions',
                'Alignement avec objectifs globaux',
                'Satisfaction stakeholders: 95%'
            ],
            'management' => [
                'Atteinte des objectifs d\'équipe: 90%',
                'Satisfaction de l\'équipe: 85%',
                'Productivité: +15%'
            ],
            'coordination' => [
                'Respect des délais: 98%',
                'Zéro retard de livraison',
                'Efficacité synchronisation: 92%'
            ],
            'quality' => [
                'Taux couverture QA: 95%',
                'Défauts détectés: 15 par sprint',
                'Conformité certifications: 100%'
            ],
            'support' => [
                'Satisfaction clients: 92%',
                'MTTR: < 4 heures',
                'Résolution premier niveau: 80%'
            ],
            'analysis' => [
                'Utilisation des recommandations: 85%',
                'Impact sur les décisions: 70%',
                'Qualité insights: 8.5/10'
            ],
            'operational' => [
                'Qualité exécution: 98%',
                'Respect standards: 100%',
                'Traçabilité complète: 95%'
            ]
        ];

        return $defaults[$type] ?? $defaults['operational'];
    }

    /**
     * 🛡️ Suggestions complètes par défaut (fallback)
     */
    private function getFallbackSuggestions($type, $functionName)
    {
        return [
            'expectations' => $this->getDefaultExpectations($type),
            'user_recommendations' => [
                [
                    'user_name' => ucfirst($type),
                    'job_title' => $functionName,
                    'user_id' => null
                ]
            ],
            'description' => "Sortie/livrable pour la fonction $functionName. À enrichir manuellement avec les détails spécifiques.",
            'quality_criteria' => [],
            'validation_steps' => [],
            'activity_indicators' => $this->getDefaultActivityIndicators($type),
            'performance_indicators' => $this->getDefaultPerformanceIndicators($type)
        ];
    }
}