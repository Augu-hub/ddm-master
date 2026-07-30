<?php

namespace App\Services;

class MistralFrequencyAssistant extends MistralAssistant
{
    protected float $temperature = 0.3;

    // ─── Palette et récurrences par défaut ────────────────────────────────────

    private const COLOR_PALETTES = [
        3 => ['#0ea5e9', '#6366f1', '#8b5cf6'],
        4 => ['#0ea5e9', '#6366f1', '#8b5cf6', '#a855f7'],
        5 => ['#0ea5e9', '#6366f1', '#8b5cf6', '#a855f7', '#ec4899'],
    ];

    /** Récurrences de référence proposées en fallback */
    private const DEFAULT_RECURRENCES = [
        1 => '1 fois / 10 ans ou moins',
        2 => '1 fois / 5 à 10 ans',
        3 => '1 fois / 1 à 5 ans',
        4 => '1 fois / an',
        5 => 'Plusieurs fois / an',
    ];

    // ─── Contrat MistralAssistant ─────────────────────────────────────────────

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Tu es un expert en gestion des risques spécialisé dans la conception de référentiels de risques pour des organisations.
Ton rôle est de proposer des niveaux de fréquence / probabilité d'occurrence adaptés au secteur d'activité et à la taille de matrice demandée.

RÈGLES STRICTES :
- Réponds UNIQUEMENT en JSON valide, sans texte avant ni après.
- Ne jamais inclure de backticks, de balises markdown ou de commentaires.
- Les libellés doivent être courts (1-2 mots) et en français (ex: "Rare", "Possible", "Probable", "Fréquent", "Certain").
- Les descriptions doivent expliquer concrètement la probabilité d'occurrence dans le contexte du secteur.
- Le champ "recurrence" est un libellé temporel court, ex: "1 fois / 10 ans", "Plusieurs fois / an".
- Les scores vont de 1 (très rare) à N (très fréquent, N = taille de la matrice).
- Les couleurs doivent utiliser des tons froids (bleu → violet → rose) pour différencier des impacts.
- Le tableau "suggestions" doit contenir EXACTEMENT N éléments.

FORMAT DE RÉPONSE :
{
  "suggestions": [
    {
      "score": 1,
      "label": "Rare",
      "description": "Événement quasi-impossible dans les conditions normales...",
      "recurrence": "1 fois / 10 ans ou moins",
      "color_code": "#0ea5e9",
      "sort_order": 0
    }
  ]
}
PROMPT;
    }

    protected function buildUserPrompt(array $params): string
    {
        $size    = (int) ($params['matrix_size'] ?? 5);
        $sector  = trim($params['sector']  ?? 'organisation générique');
        $context = trim($params['context'] ?? '');

        $contextPart = $context
            ? "\nContexte additionnel : {$context}"
            : '';

        $palette = self::COLOR_PALETTES[$size] ?? self::COLOR_PALETTES[5];
        $paletteStr = implode(', ', $palette);

        // Exemples de récurrences selon la taille pour guider Mistral
        $recurrenceExamples = $this->buildRecurrenceExamples($size);

        return <<<PROMPT
Génère exactement {$size} niveaux de fréquence/probabilité pour une matrice de risques {$size}×{$size}.

Secteur d'activité : {$sector}{$contextPart}

Les niveaux doivent :
- Aller du plus rare (score 1) au plus fréquent (score {$size})
- Couvrir l'ensemble du spectre de probabilité de façon homogène
- Être adaptés aux événements typiques du secteur "{$sector}"
- Inclure des libellés temporels concrets dans "recurrence"
- Utiliser cette palette de couleurs ordonnée : {$paletteStr}

Suggestions de récurrences (à adapter au contexte) :
{$recurrenceExamples}

Retourne EXACTEMENT {$size} éléments dans le tableau "suggestions".
PROMPT;
    }

    protected function parseResponse(array $json, array $params): array
    {
        $size    = (int) ($params['matrix_size'] ?? 5);
        $rawList = $json['suggestions'] ?? $json;
        $palette = self::COLOR_PALETTES[$size] ?? self::COLOR_PALETTES[5];

        if (!is_array($rawList)) {
            throw new \RuntimeException("Format de réponse inattendu de l'assistant IA.");
        }

        $suggestions = [];

        foreach ($rawList as $index => $item) {
            $score = $this->clampScore($item['score'] ?? ($index + 1), 1, $size);

            $suggestions[] = [
                'score'       => $score,
                'label'       => trim((string) ($item['label']       ?? "Niveau {$score}")),
                'description' => trim((string) ($item['description'] ?? '')),
                'recurrence'  => trim((string) ($item['recurrence']  ?? '')),
                'color_code'  => $this->sanitizeColor(
                    (string) ($item['color_code'] ?? ''),
                    $palette[$score - 1] ?? '#6b7280'
                ),
                'sort_order'  => (int) ($item['sort_order'] ?? ($score - 1)),
            ];
        }

        usort($suggestions, fn ($a, $b) => $a['score'] <=> $b['score']);
        $suggestions = $this->deduplicateScores($suggestions, $size);

        return $suggestions;
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    private function buildRecurrenceExamples(int $size): string
    {
        $lines = [];
        for ($i = 1; $i <= $size; $i++) {
            // Mappe les scores 1..N sur les 5 récurrences par défaut
            $key = (int) round(($i - 1) * 4 / ($size - 1)) + 1;
            $rec = self::DEFAULT_RECURRENCES[$key] ?? self::DEFAULT_RECURRENCES[3];
            $lines[] = "  Score {$i} : {$rec}";
        }
        return implode("\n", $lines);
    }

    private function deduplicateScores(array $suggestions, int $size): array
    {
        $byScore = [];
        foreach ($suggestions as $s) {
            $byScore[$s['score']] = $s;
        }

        $palette = self::COLOR_PALETTES[$size] ?? self::COLOR_PALETTES[5];
        $result  = [];

        for ($i = 1; $i <= $size; $i++) {
            $key = (int) round(($i - 1) * 4 / max($size - 1, 1)) + 1;
            $result[] = $byScore[$i] ?? [
                'score'       => $i,
                'label'       => "Niveau {$i}",
                'description' => '',
                'recurrence'  => self::DEFAULT_RECURRENCES[$key] ?? '',
                'color_code'  => $palette[$i - 1] ?? '#6b7280',
                'sort_order'  => $i - 1,
            ];
        }

        return $result;
    }
}
