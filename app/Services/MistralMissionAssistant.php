<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MistralMissionAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api.mistral.ai/v1';
    protected $model = 'mistral-small-latest';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');
        
        if (!$this->apiKey) {
            Log::error('🚨 MISTRAL_API_KEY NON CONFIGURÉE');
        } else {
            Log::info('✅ Mistral API initialisé pour les missions d\'audit');
        }
    }

    /**
     * 🎯 PROPOSER DES BUTS DE MISSION
     * Basé sur les risques sélectionnés
     */
    public function proposerButs(array $riskIds): array
    {
        try {
            if (empty($riskIds)) {
                return ['success' => false, 'error' => 'Aucun risque sélectionné'];
            }

            // Récupérer les risques
            $risks = $this->getRisksDetails($riskIds);
            
            if ($risks->isEmpty()) {
                return ['success' => false, 'error' => 'Risques introuvables'];
            }

            // Calculer le profil de risque
            $profil = $this->calculerProfilRisques($risks);

            $prompt = "Tu es un expert en audit interne. À partir des risques suivants, propose TROIS buts de mission d'audit différents mais complémentaires.

Risques identifiés (" . $risks->count() . " risques) :
" . $this->formatRisksForPrompt($risks) . "

Niveau de risque global : " . $profil['niveau'] . "
Processus impactés : " . implode(', ', $profil['processus']) . "

IMPORTANT : Les buts doivent être classés par ordre de priorité (le meilleur en premier).
Chaque but doit être précis, actionnable et directement lié aux risques identifiés.

Réponds UNIQUEMENT en JSON valide au format :
{
  \"suggestions\": [
    {
      \"but\": \"But principal (2-3 phrases claires)\",
      \"justification\": \"Pourquoi ce but est pertinent (1 phrase)\"
    }
  ]
}

Règles :
- But 1 : Le plus critique, couvre les risques majeurs
- But 2 : Complémentaire, aborde des aspects connexes
- But 3 : Prospectif, anticipe des risques potentiels";

            $result = $this->callMistral($prompt, "PROPOSER_BUTS");
            
            if (isset($result['suggestions']) && count($result['suggestions']) >= 3) {
                return [
                    'success' => true,
                    'suggestions' => $result['suggestions']
                ];
            }

            // Fallback
            return $this->getFallbackGoals($profil);

        } catch (\Exception $e) {
            Log::error('❌ proposerButs: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 🏷️ SUGGÉRER TYPE DE MISSION
     * Basé sur le but choisi et les risques
     */
    public function suggererTypeMission(string $but, array $riskIds): array
    {
        try {
            if (empty($but)) {
                return ['success' => false, 'error' => 'But requis'];
            }

            // Récupérer tous les types de mission disponibles
            $missionTypes = DB::table('mission_types')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->select('id', 'code', 'label', 'description')
                ->get();

            if ($missionTypes->isEmpty()) {
                return ['success' => false, 'error' => 'Aucun type de mission configuré'];
            }

            // Récupérer les risques
            $risks = $this->getRisksDetails($riskIds);
            $profil = $this->calculerProfilRisques($risks);

            $typesList = $missionTypes->map(fn($t) => "{$t->code} : {$t->label}")->implode("\n");

            $prompt = "Tu es un expert en typologie des missions d'audit.

Types de mission disponibles :
$typesList

But de la mission : \"$but\"

Niveau de risque global : " . $profil['niveau'] . "
Processus impactés : " . implode(', ', $profil['processus']) . "

Sélectionne le type de mission LE PLUS ADAPTÉ parmi ceux listés ci-dessus.
Réponds UNIQUEMENT avec le code du type sélectionné. Pas d'explications, juste le code.";

            $result = $this->callMistral($prompt, "SUGGERER_TYPE");
            
            if (is_string($result) && !empty($result)) {
                $selectedType = $missionTypes->firstWhere('code', trim($result));
                if ($selectedType) {
                    return [
                        'success' => true,
                        'type' => [
                            'id' => $selectedType->id,
                            'code' => $selectedType->code,
                            'label' => $selectedType->label,
                            'description' => $selectedType->description
                        ]
                    ];
                }
            }

            // Fallback basé sur la criticité
            return $this->getFallbackType($profil['niveau'], $missionTypes);

        } catch (\Exception $e) {
            Log::error('❌ suggererTypeMission: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 📝 GÉNÉRER TOUS LES CHAMPS
     * Basé sur le but, le type et les risques
     */
    public function genererTousLesChamps(string $but, string $typeCode, array $riskIds): array
    {
        try {
            if (empty($but) || empty($typeCode)) {
                return ['success' => false, 'error' => 'But et type requis'];
            }

            // Récupérer les risques
            $risks = $this->getRisksDetails($riskIds);
            $profil = $this->calculerProfilRisques($risks);
            
            // Récupérer le type
            $type = DB::table('mission_types')->where('code', $typeCode)->first();

            $prompt = "Tu es un rédacteur de missions d'audit professionnel.

But de la mission : \"$but\"
Type de mission : {$type->label} ({$typeCode})
Niveau de risque : " . $profil['niveau'] . "
Processus concernés : " . implode(', ', $profil['processus']) . "

Risques identifiés :
" . $this->formatRisksForPrompt($risks) . "

Génère les champs suivants de manière cohérente et professionnelle :

1. DESCRIPTION : Contexte détaillé (2-3 phrases) - Pourquoi cette mission est nécessaire
2. PRÉOCCUPATION : Points d'attention spécifiques (1-2 phrases) - Ce qui inquiète
3. RÉSULTAT : Livrables attendus (1-2 phrases) - Ce que produira la mission
4. CHAMP_MISSION : Périmètre (1-2 phrases) - Ce qui est couvert/exclu
5. FONCTION_PROCESSUS : Processus/fonctions concernés (liste séparée par des virgules)
6. PROCEDURE : Méthodologie (2-3 phrases) - Comment la mission sera menée

Réponds UNIQUEMENT en JSON valide au format :
{
  \"description\": \"...\",
  \"preoccupation\": \"...\",
  \"resultat\": \"...\",
  \"champ_mission\": \"...\",
  \"fonction_processus\": \"...\",
  \"procedure\": \"...\"
}";

            $result = $this->callMistral($prompt, "GENERER_CHAMPS");

            // Vérifier que tous les champs sont présents
            $required = ['description', 'preoccupation', 'resultat', 'champ_mission', 'fonction_processus', 'procedure'];
            $missing = array_diff($required, array_keys($result));
            
            if (empty($missing) && !empty($result)) {
                return [
                    'success' => true,
                    'fields' => $result
                ];
            }

            // Fallback
            return [
                'success' => true,
                'fields' => $this->getFallbackFields($but, $type, $profil)
            ];

        } catch (\Exception $e) {
            Log::error('❌ genererTousLesChamps: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 🚀 PROPOSITION COMPLÈTE (tout-en-un)
     */
    public function propositionComplete(array $riskIds): array
    {
        try {
            if (empty($riskIds)) {
                return ['success' => false, 'error' => 'Aucun risque sélectionné'];
            }

            // Étape 1 : Proposer des buts
            $buts = $this->proposerButs($riskIds);
            if (!$buts['success']) {
                return $buts;
            }

            // Étape 2 : Prendre le meilleur but
            $meilleurBut = $buts['suggestions'][0]['but'];

            // Étape 3 : Suggérer un type
            $type = $this->suggererTypeMission($meilleurBut, $riskIds);
            if (!$type['success']) {
                return $type;
            }

            // Étape 4 : Générer tous les champs
            $champs = $this->genererTousLesChamps($meilleurBut, $type['type']['code'], $riskIds);

            return [
                'success' => true,
                'buts' => $buts['suggestions'],
                'type' => $type['type'],
                'fields' => $champs['fields'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('❌ propositionComplete: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 🔄 REFORMULER UN BUT
     */
    public function reformulerBut(string $butActuel, array $riskIds): array
    {
        try {
            if (empty($butActuel)) {
                return ['success' => false, 'error' => 'But requis'];
            }

            $risks = $this->getRisksDetails($riskIds);

            $prompt = "Améliore le but de mission suivant pour le rendre plus percutant et professionnel.

But actuel : \"$butActuel\"

Risques associés :
" . $this->formatRisksForPrompt($risks) . "

Conserve le sens original mais rends-le plus clair et impactant.
Réponds UNIQUEMENT avec le nouveau but, sans explications ni formatage.";

            $result = $this->callMistral($prompt, "REFORMULER_BUT");

            if (is_string($result) && !empty($result) && strlen($result) > 10) {
                return [
                    'success' => true,
                    'but_reformule' => trim($result)
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de reformuler le but'
            ];

        } catch (\Exception $e) {
            Log::error('❌ reformulerBut: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 🔍 Récupérer les détails des risques
     */
    protected function getRisksDetails(array $riskIds)
    {
        return DB::table('risks')
            ->whereIn('risks.id', $riskIds)
            ->whereNull('risks.deleted_at')
            ->leftJoin('processes', 'risks.process_id', '=', 'processes.id')
            ->select(
                'risks.id',
                'risks.code',
                'risks.label',
                'risks.process_id',
                'risks.frequency_level_id',
                'risks.impact_level_id',
                'risks.status',
                'processes.name as process_name',
                'processes.code as process_code',
                DB::raw('IFNULL(risks.frequency_level_id * risks.impact_level_id, 0) as criticality')
            )
            ->get();
    }

    /**
     * 📊 Calculer le profil de risque
     */
    protected function calculerProfilRisques($risks): array
    {
        $criticalities = $risks->pluck('criticality')->toArray();
        $avgCrit = !empty($criticalities) ? array_sum($criticalities) / count($criticalities) : 0;

        $niveau = 'faible';
        if ($avgCrit >= 12) $niveau = 'critique';
        elseif ($avgCrit >= 8) $niveau = 'élevé';
        elseif ($avgCrit >= 5) $niveau = 'modéré';

        $processus = $risks->pluck('process_name')->unique()->filter()->values()->toArray();

        return [
            'niveau' => $niveau,
            'criticite_moyenne' => round($avgCrit, 2),
            'processus' => $processus,
            'count' => $risks->count()
        ];
    }

    /**
   * 📋 Formater les risques pour le prompt
   */
  protected function formatRisksForPrompt($risks): string
  {
      $formatted = [];
      foreach ($risks as $risk) {
          $formatted[] = "- {$risk->code} : {$risk->label} (Processus: {$risk->process_name}, Criticité: {$risk->criticality})";
      }
      return implode("\n", $formatted);
  }

  /**
   * 🎯 Fallback pour les buts
   */
  protected function getFallbackGoals(array $profil): array
  {
      $niveau = $profil['niveau'] ?? 'modéré';
      
      $fallbacks = [
          'critique' => [
              ['but' => 'Évaluer l\'efficacité du dispositif de maîtrise des risques critiques identifiés et proposer un plan d\'action correctif prioritaire.', 'justification' => 'Criticité maximale nécessitant une intervention immédiate.'],
              ['but' => 'Analyser les causes profondes des défaillances constatées et recommander des mesures de renforcement du contrôle interne.', 'justification' => 'Complémentaire à l\'évaluation initiale.'],
              ['but' => 'Vérifier la conformité des pratiques avec les exigences réglementaires et les standards internes.', 'justification' => 'Aspect prospectif et réglementaire.']
          ],
          'élevé' => [
              ['but' => 'Examiner l\'adéquation des contrôles mis en place face aux risques élevés et identifier les axes d\'amélioration prioritaires.', 'justification' => 'Couvre les risques majeurs.'],
              ['but' => 'Évaluer la performance des processus impactés et proposer des optimisations.', 'justification' => 'Aspect performance complémentaire.'],
              ['but' => 'Analyser la fiabilité des reportings et indicateurs de suivi des risques.', 'justification' => 'Sécurisation du pilotage.']
          ],
          'modéré' => [
              ['but' => 'Évaluer l\'efficience des contrôles existants et identifier les opportunités d\'optimisation.', 'justification' => 'Amélioration continue.'],
              ['but' => 'Analyser la cohérence entre les objectifs opérationnels et le dispositif de maîtrise des risques.', 'justification' => 'Alignement stratégique.'],
              ['but' => 'Vérifier l\'application des procédures et recommandations antérieures.', 'justification' => 'Suivi et conformité.']
          ],
          'faible' => [
              ['but' => 'Réaliser un état des lieux des pratiques de gestion des risques et identifier les bonnes pratiques.', 'justification' => 'Capitalisation.'],
              ['but' => 'Évaluer le niveau de sensibilisation et de formation des équipes aux risques.', 'justification' => 'Aspect humain.'],
              ['but' => 'Proposer des axes de renforcement préventif de la maîtrise des risques.', 'justification' => 'Anticipation.']
          ]
      ];

      return [
          'success' => true,
          'suggestions' => $fallbacks[$niveau] ?? $fallbacks['modéré']
      ];
  }

  /**
   * 🎯 Fallback pour le type de mission
   */
  protected function getFallbackType(string $niveau, $missionTypes): array
  {
      $mapping = [
          'critique' => ['CONF', 'ASSUR', 'AUDIT'],
          'élevé' => ['EVAL', 'AUDIT', 'CONF'],
          'modéré' => ['REVUE', 'CONS', 'EVAL'],
          'faible' => ['SUIVI', 'CONS', 'REVUE']
      ];

      $priorities = $mapping[$niveau] ?? $mapping['modéré'];

      foreach ($priorities as $code) {
          $type = $missionTypes->firstWhere('code', $code);
          if ($type) {
              return [
                  'success' => true,
                  'type' => [
                      'id' => $type->id,
                      'code' => $type->code,
                      'label' => $type->label,
                      'description' => $type->description ?? ''
                  ]
              ];
          }
      }

      // Fallback ultime : premier type disponible
      $first = $missionTypes->first();
      return [
          'success' => true,
          'type' => [
              'id' => $first->id,
              'code' => $first->code,
              'label' => $first->label,
              'description' => $first->description ?? ''
          ]
      ];
  }

  /**
   * 📝 Fallback pour les champs
   */
  protected function getFallbackFields(string $but, $type, array $profil): array
  {
      $processus = implode(', ', array_slice($profil['processus'] ?? ['Processus concernés'], 0, 3));
      
      return [
          'description' => "Mission d'audit visant à " . lcfirst($but) . " Cette mission s'inscrit dans le cadre de l'amélioration continue du dispositif de maîtrise des risques.",
          'preoccupation' => "Les risques identifiés sur les processus " . $processus . " présentent un niveau de criticité " . ($profil['niveau'] ?? 'modéré') . " nécessitant une attention particulière.",
          'resultat' => "Rapport d'audit détaillé incluant les constats, l'analyse des causes et des recommandations actionnables.",
          'champ_mission' => "La mission couvrira les processus et activités directement liés aux risques identifiés, avec un focus sur les points critiques.",
          'fonction_processus' => $processus,
          'procedure' => "La mission sera menée selon une approche par les risques : entretiens, analyse documentaire, tests de contrôles et validation des constats avec les parties prenantes."
      ];
  }

  /**
   * 🔌 APPEL MISTRAL API
   */
  protected function callMistral(string $prompt, string $context = ''): mixed
  {
      try {
          if (!$this->apiKey) {
              Log::error('❌ API KEY vide');
              return [];
          }

          Log::info("🚀 Mistral ($context) - Prompt: " . substr($prompt, 0, 150) . "...");

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
                      'content' => 'Tu es un expert en audit interne et rédaction de missions d\'audit. Réponds UNIQUEMENT en JSON valide et structuré sauf indication contraire. Pas d\'explications, pas de Markdown, juste le format demandé.'
                  ],
                  [
                      'role' => 'user',
                      'content' => $prompt
                  ]
              ],
              'max_tokens' => 2000,
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

          // Essayer de parser en JSON
          $parsed = $this->parseJSON($content);
          
          if ($parsed !== null) {
              return $parsed;
          }

          // Si ce n'est pas du JSON, retourner le contenu brut
          return $content;

      } catch (\Exception $e) {
          Log::error('❌ Exception: ' . $e->getMessage());
          return [];
      }
  }

  /**
   * 🔍 Parser JSON avec retry intelligent
   */
  protected function parseJSON(string $text): mixed
  {
      $text = trim($text);

      // Essai 1: JSON brut
      $json = json_decode($text, true);
      if (is_array($json) || is_string($json)) {
          Log::info('✅ JSON parsed (raw)');
          return $json;
      }

      // Essai 2: JSON avec markdown backticks
      if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $m)) {
          $json = json_decode($m[1], true);
          if (is_array($json) || is_string($json)) {
              Log::info('✅ JSON parsed (markdown)');
              return $json;
          }
      }

      // Essai 3: Extraire JSON entre { }
      if (preg_match('/\{.*\}/s', $text, $m)) {
          $json = json_decode($m[0], true);
          if (is_array($json)) {
              Log::info('✅ JSON parsed (braces)');
              return $json;
          }
      }

      // Essai 4: Chercher un texte simple (pas JSON)
      if (strlen($text) > 10 && strpos($text, '{') === false && strpos($text, '[') === false) {
          return $text;
      }

      Log::warning('❌ Could not parse from: ' . substr($text, 0, 300));
      return null;
  }
}