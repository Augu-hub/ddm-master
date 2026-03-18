<?php

namespace App\Services;

class MistralImpactAssistant extends MistralAssistant
{
    protected float $temperature = 0.3; // Réponses plus déterministes pour les référentiels

    // ─── Palette couleurs par défaut selon position (score ascendant) ─────────

    private const COLOR_PALETTES = [
        3 => ['#22c55e', '#eab308', '#ef4444'],
        4 => ['#22c55e', '#84cc16', '#f97316', '#ef4444'],
        5 => ['#22c55e', '#84cc16', '#eab308', '#f97316', '#ef4444'],
    ];

    // ─── Contrat MistralAssistant ─────────────────────────────────────────────

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Tu es un expert en gestion des risques spécialisé dans la conception de référentiels de risques pour des organisations.
Ton rôle est de proposer des niveaux d'impact adaptés au secteur d'activité et à la taille de matrice demandée.

RÈGLES STRICTES :
- Réponds UNIQUEMENT en JSON valide, sans texte avant ni après.
- Ne jamais inclure de backticks, de balises markdown ou de commentaires.
- Les libellés doivent être courts (1-3 mots), percutants et en français.
- Les descriptions doivent être concrètes, mesurables et adaptées au secteur.
- Les scores vont de 1 (impact minimal) à N (impact maximal, N = taille de la matrice).
- Les couleurs doivent suivre un dégradé vert → rouge selon la gravité.
- Le tableau "suggestions" doit contenir EXACTEMENT N éléments (N = matrix_size).

FORMAT DE RÉPONSE :
{
  "suggestions": [
    {
      "score": 1,
      "label": "Négligeable",
      "description": "Description concrète et mesurable...",
      "color_code": "#22c55e",
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

        return <<<PROMPT
Génère exactement {$size} niveaux d'impact pour une matrice de risques {$size}×{$size}.

Secteur d'activité : {$sector}{$contextPart}

Les niveaux doivent :
- Aller du moins grave (score 1) au plus grave (score {$size})
- Être adaptés aux enjeux spécifiques du secteur "{$sector}"
- Avoir des descriptions concrètes avec des exemples de conséquences mesurables
- Utiliser cette palette de couleurs ordonnée : {$paletteStr}

Retourne EXACTEMENT {$size} éléments dans le tableau "suggestions".
PROMPT;
    }

    protected function parseResponse(array $json, array $params): array
    {
        $size        = (int) ($params['matrix_size'] ?? 5);
        $rawList     = $json['suggestions'] ?? $json;
        $palette     = self::COLOR_PALETTES[$size] ?? self::COLOR_PALETTES[5];

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
                'color_code'  => $this->sanitizeColor(
                    (string) ($item['color_code'] ?? ''),
                    $palette[$score - 1] ?? '#6b7280'
                ),
                'sort_order'  => (int) ($item['sort_order'] ?? ($score - 1)),
            ];
        }

        // Trie par score croissant et garantit l'unicité des scores
        usort($suggestions, fn ($a, $b) => $a['score'] <=> $b['score']);
        $suggestions = $this->deduplicateScores($suggestions, $size);

        return $suggestions;
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Garantit que chaque score de 1 à N est présent exactement une fois.
     * Complète les trous si Mistral en a sauté.
     */
    private function deduplicateScores(array $suggestions, int $size): array
    {
        $byScore = [];
        foreach ($suggestions as $s) {
            $byScore[$s['score']] = $s;
        }

        $palette = self::COLOR_PALETTES[$size] ?? self::COLOR_PALETTES[5];
        $result  = [];

        for ($i = 1; $i <= $size; $i++) {
            $result[] = $byScore[$i] ?? [
                'score'       => $i,
                'label'       => "Niveau {$i}",
                'description' => '',
                'color_code'  => $palette[$i - 1] ?? '#6b7280',
                'sort_order'  => $i - 1,
            ];
        }

        return $result;
    }
}
