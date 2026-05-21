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

RÈGLE FONDAMENTALE SUR LES DÉSIGNATIONS :
La "designation" représente une CATÉGORIE D'ÉVALUATION (ex : "Conditions de Travail y compris RH et QHSE", "Impact Financier").
La même désignation DOIT apparaître à chaque niveau de l'échelle sans modification.
Seule la "description" change d'un niveau à l'autre pour refléter ce que cette catégorie signifie à ce niveau précis.

RÈGLES STRICTES :
- Réponds UNIQUEMENT en JSON valide, sans texte avant ni après.
- Ne jamais inclure de backticks, de balises markdown ou de commentaires.
- Définis d'abord 3 à 5 catégories (designations) pertinentes pour le secteur donné.
- Chaque niveau doit contenir exactement les mêmes catégories (mêmes designations, même sort_order).
- La "designation" est courte (10-15 mots max), exprime une dimension d'évaluation (financière, humaine, opérationnelle, réglementaire, réputation…).
- La "description" est détaillée (20-60 mots), décrit concrètement ce que cette catégorie représente À CE NIVEAU PRÉCIS, avec des seuils ou exemples mesurables adaptés au secteur.
- Les descriptions doivent être cohérentes entre niveaux : chaque niveau supérieur doit être clairement au-delà du précédent pour la même catégorie.

FORMAT DE RÉPONSE :
{
  "categories": ["Désignation 1", "Désignation 2", "Désignation 3"],
  "levels": [
    {
      "level_id": <id>,
      "criteria": [
        {
          "designation": "Désignation 1",
          "description": "Ce que Désignation 1 signifie concrètement à CE niveau.",
          "sort_order": 0
        },
        {
          "designation": "Désignation 2",
          "description": "Ce que Désignation 2 signifie concrètement à CE niveau.",
          "sort_order": 1
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
            ? "Les catégories doivent couvrir les dimensions pertinentes pour évaluer la gravité des conséquences (ex : conditions de travail / RH / QHSE, impact financier, continuité opérationnelle, conformité réglementaire, atteinte à la réputation)."
            : "Les catégories doivent couvrir les dimensions pertinentes pour évaluer la probabilité d'occurrence (ex : historique d'occurrence, fréquence observée, conditions déclenchantes, signaux précurseurs, niveau de maîtrise existant).";

        // Construit la liste des niveaux avec leur contexte pour Mistral
        $levelsBlock = '';
        foreach ($levels as $level) {
            $levelsBlock .= "  - level_id: {$level['id']}, score: {$level['score']}, label: \"{$level['label']}\", description: \"{$level['description']}\"\n";
        }

        return <<<PROMPT
Génère des critères d'évaluation {$typeLabel} pour le secteur suivant.

Secteur d'activité : {$sector}{$contextPart}

{$typeInstruction}

ÉTAPE 1 — Choisis 3 à 5 catégories (designations) adaptées au secteur "{$sector}". Ces désignations seront IDENTIQUES pour tous les niveaux.

ÉTAPE 2 — Pour chacun des niveaux ci-dessous, décris ce que chaque catégorie représente concrètement à ce niveau, avec des seuils ou des exemples mesurables.

Niveaux à traiter :
{$levelsBlock}

RAPPEL IMPORTANT :
- Les "designation" dans le tableau "criteria" de chaque niveau doivent être EXACTEMENT les mêmes que celles listées dans "categories".
- Seules les "description" changent d'un niveau à l'autre.
- Le "sort_order" doit être identique pour la même catégorie dans tous les niveaux.

Retourne tous les niveaux dans le tableau "levels" avec exactement les level_id fournis.
PROMPT;
    }

    protected function parseResponse(array $json, array $params): array
    {
        $rawLevels = $json['levels'] ?? [];

        if (!is_array($rawLevels)) {
            throw new \RuntimeException("Format de réponse inattendu de l'assistant IA.");
        }

        // Collecte toutes les désignations attendues (depuis "categories" si présent,
        // sinon on les déduit du premier niveau valide)
        $canonicalDesignations = [];
        if (!empty($json['categories']) && is_array($json['categories'])) {
            $canonicalDesignations = array_values(array_filter(
                array_map('trim', $json['categories']),
                fn($d) => $d !== ''
            ));
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
                // Si on n'a pas encore les désignations canoniques, on les extrait du 1er niveau
                if (empty($canonicalDesignations)) {
                    $canonicalDesignations = array_column($parsed, 'designation');
                }

                // Réordonne les critères du niveau selon l'ordre canonique des désignations
                // et remplace la désignation par la version canonique (normalisation de casse/espaces)
                $indexed = [];
                foreach ($parsed as $criterion) {
                    $indexed[$criterion['designation']] = $criterion;
                }

                $normalized = [];
                foreach ($canonicalDesignations as $sortIdx => $canonDesignation) {
                    // Cherche une correspondance exacte ou insensible à la casse
                    $match = $indexed[$canonDesignation]
                        ?? collect($indexed)->first(fn($c) => strcasecmp($c['designation'], $canonDesignation) === 0);

                    if ($match) {
                        $normalized[] = [
                            'designation' => $canonDesignation,
                            'description' => $match['description'],
                            'sort_order'  => $sortIdx,
                        ];
                    }
                }

                $result[$levelId] = !empty($normalized) ? $normalized : $parsed;
            }
        }

        return $result;
    }
}
