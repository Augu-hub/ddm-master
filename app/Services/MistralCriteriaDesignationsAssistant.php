<?php

namespace App\Services;

/**
 * Phase 1 — Assistant Mistral pour proposer les désignations (axes) de critères.
 *
 * Retourne uniquement une liste de 6 à 10 désignations candidates,
 * SANS description, SANS association à un niveau.
 *
 * L'utilisateur sélectionne ensuite celles qu'il veut garder, puis
 * MistralCriteriaDescriptionsAssistant génère les descriptions graduées.
 *
 * $params['level_type'] vaut 'impact' ou 'frequency'.
 *
 * Retourne : ['Conditions de travail (RH/QHSE)', 'Pertes financières', ...]
 */
class MistralCriteriaDesignationsAssistant extends MistralAssistant
{
    protected string $model       = 'mistral-large-latest';
    protected float  $temperature = 0.5;
    protected int    $maxTokens   = 800;

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Tu es un expert en gestion des risques spécialisé dans la conception de référentiels d'évaluation selon ISO 31000 et COSO ERM.

Ta mission : proposer une liste d'AXES D'ÉVALUATION (désignations de critères) que l'on pourra ensuite graduer sur une échelle de niveaux.

Un AXE est une dimension transversale d'évaluation (ex : "Impact financier", "Continuité opérationnelle", "Conformité réglementaire").
Le MÊME axe sera ensuite décrit différemment à chaque niveau de l'échelle — ta mission ici est uniquement de lister ces axes.

RÈGLES STRICTES :
- Réponds UNIQUEMENT en JSON valide, sans texte avant ni après.
- Pas de backticks, pas de markdown, pas de commentaires.
- Propose entre 6 et 10 axes pertinents pour le secteur et le type d'évaluation.
- Chaque axe est une désignation COURTE (3 à 10 mots), claire, non ambiguë.
- Les axes couvrent des dimensions DIFFÉRENTES (éviter les redondances).
- Les axes doivent être évaluables de façon OBSERVABLE et MESURABLE.
- N'inclus PAS de description dans cette étape.

FORMAT DE RÉPONSE :
{
  "designations": [
    "Axe 1 court et parlant",
    "Axe 2 court et parlant",
    "..."
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $params): string
    {
        $sector    = trim($params['sector']    ?? 'organisation générique');
        $context   = trim($params['context']   ?? '');
        $levelType = $params['level_type']     ?? 'impact';
        $nbLevels  = (int) ($params['nb_levels'] ?? 5);

        $contextPart = $context ? "\nContexte additionnel : {$context}" : '';

        $typeLabel = $levelType === 'impact'
            ? "GRAVITÉ DES IMPACTS (conséquences financières, opérationnelles, humaines, réglementaires, réputationnelles, environnementales)"
            : "FRÉQUENCE / PROBABILITÉ D'OCCURRENCE (historique sectoriel, conditions déclenchantes, signaux précurseurs, probabilité statistique)";

        $exampleAxes = $levelType === 'impact'
            ? "- Conditions de travail (RH/QHSE)\n- Pertes financières directes\n- Image et réputation\n- Continuité opérationnelle\n- Conformité réglementaire\n- Impact environnemental"
            : "- Probabilité statistique annuelle\n- Antécédents sectoriels documentés\n- Signaux précurseurs détectables\n- Conditions déclenchantes\n- Robustesse des contrôles en place";

        return <<<PROMPT
Propose une liste d'axes d'évaluation pour mesurer la {$typeLabel}.

Secteur d'activité : {$sector}{$contextPart}
Nombre de niveaux de l'échelle cible : {$nbLevels} (info contextuelle uniquement, tu ne rédiges PAS les descriptions ici)

Exemples d'axes à l'esprit (à adapter au secteur, ne pas recopier tels quels) :
{$exampleAxes}

Propose entre 6 et 10 axes pertinents, couvrant des dimensions différentes, adaptés au secteur "{$sector}".
Chaque axe sera ensuite gradué sur les {$nbLevels} niveaux par un second appel — ici, ne rédige QUE les libellés courts.

Retourne le résultat dans la structure JSON "designations".
PROMPT;
    }

    protected function parseResponse(array $json, array $params): array
    {
        $raw = $json['designations'] ?? [];

        if (!is_array($raw)) {
            throw new \RuntimeException("Format de réponse inattendu de l'assistant IA (attendu : tableau 'designations').");
        }

        $result = [];
        foreach ($raw as $item) {
            $label = trim((string) $item);
            if ($label === '') {
                continue;
            }
            // Dédoublonnage casse-insensible
            $key = mb_strtolower($label);
            $result[$key] = $label;
        }

        if (empty($result)) {
            throw new \RuntimeException("L'assistant IA n'a retourné aucune désignation exploitable.");
        }

        return array_values($result);
    }
}
