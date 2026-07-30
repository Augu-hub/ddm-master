<?php

namespace App\Services;

/**
 * Phase 2 — Assistant Mistral pour générer les DESCRIPTIONS graduées
 * de chaque désignation sur l'ensemble des niveaux.
 *
 * Reçoit en entrée :
 *  - la liste des désignations retenues par l'utilisateur (phase 1)
 *  - la liste des niveaux de la matrice (avec id, score, label, description)
 *
 * Retourne une MATRICE : pour chaque désignation × chaque niveau, une description.
 *
 * Structure retournée :
 * [
 *   [
 *     'designation' => 'Pertes financières directes',
 *     'levels' => [
 *       ['level_id' => 11, 'description' => 'Perte < 1 000 USD / incident'],
 *       ['level_id' => 12, 'description' => '1 000 à 10 000 USD / incident'],
 *       ...
 *     ],
 *   ],
 *   ...
 * ]
 */
class MistralCriteriaDescriptionsAssistant extends MistralAssistant
{
    protected string $model       = 'mistral-large-latest';
    protected float  $temperature = 0.4;
    protected int    $maxTokens   = 4000;

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Tu es un expert en gestion des risques spécialisé dans la conception de référentiels d'évaluation selon ISO 31000 et COSO ERM.

Ta mission : pour chaque AXE D'ÉVALUATION (désignation) fourni, rédiger une DESCRIPTION GRADUÉE à chaque niveau de l'échelle.

PRINCIPE CLÉ :
- La désignation reste STRICTEMENT IDENTIQUE pour tous les niveaux.
- Seule la description change d'un niveau à l'autre, en gradation croissante.
- Niveau 1 (score le plus bas) = manifestation la plus faible du critère.
- Niveau N (score le plus haut) = manifestation la plus forte du critère.

RÈGLES STRICTES :
- Réponds UNIQUEMENT en JSON valide, sans texte avant ni après.
- Pas de backticks, pas de markdown, pas de commentaires.
- Chaque description est CONCRÈTE, OBSERVABLE et MESURABLE (20 à 60 mots).
- Donne si possible un exemple chiffré ou un seuil adapté au secteur.
- Utilise EXACTEMENT les level_id fournis dans la requête (ne les invente pas).
- Utilise EXACTEMENT les désignations fournies (ne les reformule pas).
- Chaque axe doit avoir une description pour CHACUN des niveaux (aucun niveau omis).

FORMAT DE RÉPONSE :
{
  "matrix": [
    {
      "designation": "<exactement la désignation fournie>",
      "levels": [
        { "level_id": <id>, "description": "Description concrète pour ce niveau." },
        { "level_id": <id>, "description": "..." }
      ]
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $params): string
    {
        $sector       = trim($params['sector']    ?? 'organisation générique');
        $context      = trim($params['context']   ?? '');
        $levelType    = $params['level_type']     ?? 'impact';
        $designations = $params['designations']   ?? [];
        $levels       = $params['levels']         ?? [];

        $contextPart = $context ? "\nContexte additionnel : {$context}" : '';

        $typeLabel = $levelType === 'impact'
            ? "la GRAVITÉ DES IMPACTS"
            : "la FRÉQUENCE / PROBABILITÉ D'OCCURRENCE";

        $typeInstruction = $levelType === 'impact'
            ? "Les descriptions graduent la sévérité des conséquences, du niveau le plus bénin au plus grave."
            : "Les descriptions graduent la probabilité d'occurrence, du plus rare au plus fréquent.";

        // Bloc niveaux
        $levelsBlock = '';
        foreach ($levels as $level) {
            $levelsBlock .= sprintf(
                "  - level_id: %d | score: %d | label: %s | description niveau: %s\n",
                (int) $level['id'],
                (int) $level['score'],
                addslashes($level['label'] ?? ''),
                addslashes($level['description'] ?? '')
            );
        }

        // Bloc désignations
        $designationsBlock = '';
        foreach ($designations as $d) {
            $designationsBlock .= "  - " . trim((string) $d) . "\n";
        }

        $nbLevels       = count($levels);
        $nbDesignations = count($designations);
        $nbCells        = $nbLevels * $nbDesignations;

        return <<<PROMPT
Pour {$typeLabel} dans le secteur "{$sector}", rédige une matrice de descriptions graduées.{$contextPart}

{$typeInstruction}

DÉSIGNATIONS À TRAITER (à utiliser TELLES QUELLES, sans reformulation) :
{$designationsBlock}

NIVEAUX DE L'ÉCHELLE (du plus bas au plus haut) :
{$levelsBlock}

Pour chaque désignation, produis une description par niveau en respectant l'ordre croissant des scores.
Nombre total de cellules attendues : {$nbDesignations} désignations × {$nbLevels} niveaux = {$nbCells} descriptions.

Contraintes :
- La désignation doit rester rigoureusement identique d'un niveau à l'autre.
- Les descriptions doivent former une progression COHÉRENTE et MONOTONE (jamais régresser).
- Adapte les exemples et seuils chiffrés au secteur "{$sector}".
- Chaque description : 20 à 60 mots, observable, mesurable, actionnable pour un évaluateur.

Retourne la matrice dans la structure JSON "matrix".
PROMPT;
    }

    protected function parseResponse(array $json, array $params): array
    {
        $rawMatrix = $json['matrix'] ?? [];

        if (!is_array($rawMatrix)) {
            throw new \RuntimeException("Format de réponse inattendu de l'assistant IA (attendu : tableau 'matrix').");
        }

        // Liste blanche des level_id autorisés (anti-hallucination)
        $allowedLevelIds = array_map(
            fn ($l) => (int) ($l['id'] ?? 0),
            $params['levels'] ?? []
        );
        $allowedLevelIds = array_filter($allowedLevelIds, fn ($id) => $id > 0);

        $result = [];

        foreach ($rawMatrix as $row) {
            $designation = trim((string) ($row['designation'] ?? ''));
            $rawLevels   = $row['levels'] ?? [];

            if ($designation === '' || !is_array($rawLevels)) {
                continue;
            }

            $cells = [];
            foreach ($rawLevels as $cell) {
                $levelId     = (int) ($cell['level_id']    ?? 0);
                $description = trim((string) ($cell['description'] ?? ''));

                if ($levelId <= 0 || $description === '') {
                    continue;
                }

                // Filtre les level_id hallucinés
                if (!in_array($levelId, $allowedLevelIds, true)) {
                    continue;
                }

                $cells[] = [
                    'level_id'    => $levelId,
                    'description' => $description,
                ];
            }

            if (!empty($cells)) {
                $result[] = [
                    'designation' => $designation,
                    'levels'      => $cells,
                ];
            }
        }

        if (empty($result)) {
            throw new \RuntimeException("L'assistant IA n'a retourné aucune cellule exploitable.");
        }

        return $result;
    }
}
