<?php

namespace App\Services;

class MistralNomenclatureAssistant extends MistralAssistant
{
    protected int   $maxTokens   = 2000;
    protected float $temperature = 0.4;

    // ---------------------------------------------------------------
    // Prompt système
    // ---------------------------------------------------------------

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Tu es un expert en gestion des risques ISO 31000 / COSO specialise dans la construction
de nomenclatures de risques hierarchiques pour les entreprises.

Ton role est de suggerer des nomenclatures de risques structurees sur 2 niveaux :
- Niveau 2 : sous-categories directes d'un type de risque racine (ex: RC -> Risque fiscal)
- Niveau 3 : sous-elements d'un niveau 2 (ex: Risque fiscal -> Fraude a la TVA)

Regles absolues :
1. Reponds UNIQUEMENT en JSON valide, sans texte avant ni apres.
2. Le JSON doit respecter exactement le schema fourni.
3. Les labels doivent etre en francais, concis (3 a 6 mots), professionnels.
4. Les descriptions doivent etre courtes (1 phrase max, 80 caracteres max).
5. Propose entre 3 et 5 elements de niveau 2, chacun avec 2 a 4 enfants de niveau 3.
6. Adapte les suggestions au secteur d'activite fourni.
7. Ne repete jamais les nomenclatures deja existantes fournies dans le contexte.

Schema JSON attendu :
{
  "suggestions": [
    {
      "label": "string",
      "description": "string",
      "children": [
        { "label": "string", "description": "string" },
        { "label": "string", "description": "string" }
      ]
    }
  ]
}
PROMPT;
    }

    // ---------------------------------------------------------------
    // Prompt utilisateur
    // ---------------------------------------------------------------

    protected function buildUserPrompt(array $params): string
    {
        $typeCode  = $params['type_code'];
        $typeLabel = $params['type_label'];
        $sector    = $params['sector'];
        $context   = $params['context'] ?? '';
        $existing  = $params['existing'] ?? [];

        $existingStr = !empty($existing)
            ? 'Nomenclatures deja existantes a NE PAS repeter : ' . implode(', ', $existing)
            : 'Aucune nomenclature existante.';

        $contextStr = $context
            ? "Contexte supplementaire : {$context}"
            : '';

        return <<<PROMPT
Type de risque cible : {$typeCode} - {$typeLabel}
Secteur d'activite   : {$sector}
{$contextStr}

{$existingStr}

Genere des suggestions de nomenclatures de risques de niveau 2 et 3
pour le type "{$typeLabel}" adaptes au secteur "{$sector}".
Respecte exactement le schema JSON demande.
PROMPT;
    }

    // ---------------------------------------------------------------
    // Parsing de la réponse
    // ---------------------------------------------------------------

    protected function parseResponse(array $json, array $params): array
    {
        $suggestions = $json['suggestions'] ?? [];

        if (empty($suggestions) || !is_array($suggestions)) {
            throw new \RuntimeException(
                "L'assistant n'a retourne aucune suggestion. Reformulez le secteur."
            );
        }

        return array_values(array_map(function (array $item) {
            return [
                'label'       => trim($item['label']       ?? ''),
                'description' => trim($item['description'] ?? ''),
                'children'    => array_values(array_map(fn($c) => [
                    'label'       => trim($c['label']       ?? ''),
                    'description' => trim($c['description'] ?? ''),
                ], $item['children'] ?? [])),
            ];
        }, $suggestions));
    }
}
