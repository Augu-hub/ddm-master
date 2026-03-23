<?php

namespace App\Services;

/**
 * Assistant Mistral pour la génération de critères d'évaluation.
 *
 * Utilisé pour les deux types de niveaux (impact et fréquence).
 * Le paramètre $params['level_type'] vaut 'impact' ou 'frequency'.
 *
 * Retourne un tableau indexé par level_id :
 * [
 *   <level_id> => [
 *     ['designation' => '...', 'description' => '...', 'sort_order' => 0],
 *     ...
 *   ],
 *   ...
 * ]
 */
class MistralCriteriaAssistant extends MistralAssistant
{
    protected string $model       = 'mistral-large-latest';
    protected float  $temperature = 0.4;
    protected int    $maxTokens   = 3000;

    // ─── Contrat MistralAssistant ─────────────────────────────────────────────

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Tu es un expert en gestion des risques spécialisé dans la conception de référentiels d'évaluation des risques selon ISO 31000 et COSO ERM.
Ton rôle est de proposer des critères d'évaluation observables et mesurables pour chaque niveau d'une échelle de risques.

Un critère est un indicateur concret permettant à un évaluateur de décider à quel niveau associer un risque ou un événement.

RÈGLES STRICTES :
- Réponds UNIQUEMENT en JSON valide, sans texte avant ni après.
- Ne jamais inclure de backticks, de balises markdown ou de commentaires.
- Chaque niveau doit avoir entre 2 et 4 critères.
- La "designation" est courte (10-15 mots max), percutante, exprime un seuil ou un observable.
- La "description" est plus détaillée (20-60 mots), donne un exemple concret et mesurable adapté au secteur.
- Les critères doivent couvrir différentes dimensions (financière, opérationnelle, réglementaire, réputation, etc.) selon pertinence.
- Les critères doivent être cohérents entre niveaux : chaque niveau supérieur doit être clairement au-delà du précédent.

FORMAT DE RÉPONSE :
{
  "levels": [
    {
      "level_id": <id>,
      "criteria": [
        {
          "designation": "Libellé court du critère",
          "description": "Description détaillée avec exemple concret et mesurable.",
          "sort_order": 0
        }
      ]
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $params): string
    {
        $sector    = trim($params['sector']    ?? 'organisation générique');
        $context   = trim($params['context']   ?? '');
        $levelType = $params['level_type']     ?? 'impact';
        $levels    = $params['levels']         ?? [];

        $contextPart = $context ? "\nContexte additionnel : {$context}" : '';

        $typeLabel       = $levelType === 'impact' ? "d'impact" : 'de fréquence / probabilité';
        $typeInstruction = $levelType === 'impact'
            ? "Les critères doivent permettre d'évaluer la gravité des conséquences (financières, opérationnelles, humaines, réglementaires, réputationnelles)."
            : "Les critères doivent permettre d'évaluer la probabilité d'occurrence (historique, fréquence observée, conditions déclenchantes, signaux précurseurs).";

        // Construit la liste des niveaux avec leur contexte pour Mistral
        $levelsBlock = '';
        foreach ($levels as $level) {
            $levelsBlock .= "  - level_id: {$level['id']}, score: {$level['score']}, label: \"{$level['label']}\", description: \"{$level['description']}\"\n";
        }

        return <<<PROMPT
Génère des critères d'évaluation {$typeLabel} pour chaque niveau de l'échelle ci-dessous.

Secteur d'activité : {$sector}{$contextPart}

{$typeInstruction}

Niveaux à traiter :
{$levelsBlock}

Pour chaque niveau, propose 2 à 4 critères observables et mesurables, adaptés au secteur "{$sector}" et cohérents avec le score et la description du niveau.
Les critères doivent permettre à un auditeur ou gestionnaire de risques de décider objectivement à quel niveau appartient un risque ou un événement.

Retourne tous les niveaux dans le tableau "levels" avec exactement les level_id fournis.
PROMPT;
    }

    protected function parseResponse(array $json, array $params): array
    {
        $rawLevels = $json['levels'] ?? [];

        if (!is_array($rawLevels)) {
            throw new \RuntimeException("Format de réponse inattendu de l'assistant IA.");
        }

        $result = [];

        foreach ($rawLevels as $levelData) {
            $levelId  = (int) ($levelData['level_id'] ?? 0);
            $criteria = $levelData['criteria'] ?? [];

            if ($levelId <= 0 || !is_array($criteria)) {
                continue;
            }

            $parsed = [];
            foreach ($criteria as $index => $item) {
                $designation = trim((string) ($item['designation'] ?? ''));
                if ($designation === '') {
                    continue;
                }

                $parsed[] = [
                    'designation' => $designation,
                    'description' => trim((string) ($item['description'] ?? '')),
                    'sort_order'  => (int) ($item['sort_order'] ?? $index),
                ];
            }

            if (!empty($parsed)) {
                $result[$levelId] = $parsed;
            }
        }

        return $result;
    }
}
