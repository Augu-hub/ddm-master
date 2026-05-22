<?php
// ══════════════════════════════════════════════════════════════════════
// FICHIER UNIQUE — 3 SERVICES IA (un par programme de travail)
// Chacun dans son namespace. Sauvegarder chaque classe dans son propre
// fichier : ProgConformiteAiService.php / ProgMarchesAiService.php / ProgTransactionsAiService.php
// ══════════════════════════════════════════════════════════════════════

namespace App\Services\Audit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ════════════════════════════════════════════════════════════════════
// TRAIT commun aux 3 services IA
// ════════════════════════════════════════════════════════════════════
trait ProgAiTrait
{
    private const API_URL    = 'https://api.mistral.ai/v1/chat/completions';
    private const MODEL      = 'mistral-small-latest';
    private const MAX_TOKENS = 1500;
    private const TIMEOUT    = 60;
    private const MAX_RETRY  = 3;

    private function callMistral(string $prompt, string $apiKey): array
    {
        $attempt = 0;
        while ($attempt <= self::MAX_RETRY) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])->timeout(self::TIMEOUT)->post(self::API_URL, [
                    'model'       => self::MODEL,
                    'temperature' => 0.3,
                    'max_tokens'  => self::MAX_TOKENS,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Tu réponds UNIQUEMENT avec du JSON valide. Aucun texte avant ou après.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ]);

                if (!$response->successful()) {
                    $status = $response->status();
                    if ($status === 429) {
                        $attempt++;
                        usleep(pow(2, $attempt) * 1000000);
                        continue;
                    }
                    return ['ok' => false, 'error' => "HTTP {$status}"];
                }

                $text = $response->json('choices.0.message.content') ?? '';
                return $text ? ['ok' => true, 'text' => $text] : ['ok' => false, 'error' => 'Réponse vide'];

            } catch (\Exception $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }
        return ['ok' => false, 'error' => 'Rate limit persistant'];
    }

    private function parseJson(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
        $data = json_decode($text, true);
        if (is_array($data)) return $data;
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) return $data;
        }
        return [];
    }

    private function fallback(string $diligenceTexte, string $ref): array
    {
        return [
            'success'    => true,
            'diligence'  => $diligenceTexte ?: 'Vérifier la conformité aux exigences',
            'procedures' => [
                'Sélectionner un échantillon représentatif des éléments concernés',
                'Vérifier la conformité aux dispositions applicables',
                'Documenter les écarts constatés',
                'Évaluer l\'impact des non-conformités identifiées',
            ],
            'mode' => 'fallback',
        ];
    }
}

// ════════════════════════════════════════════════════════════════════
// SERVICE IA — CONFORMITÉ
// ════════════════════════════════════════════════════════════════════

class ProgTransactionsAiService
{
    use ProgAiTrait;

    // Assertions d'audit (cycle des transactions)
    private const ASSERTIONS = [
        'Exhaustivité'   => 'Toutes les transactions ont été enregistrées',
        'Existence'      => 'Les transactions enregistrées sont réelles',
        'Évaluation'     => 'Les transactions sont correctement évaluées',
        'Classification' => 'Les transactions sont correctement classifiées',
        'Période'        => 'Les transactions sont enregistrées sur la bonne période',
        'Séparation'     => 'Les tâches incompatibles sont séparées',
    ];

    public function suggererDiligence(array $diligence, array $context = []): array
    {
        $apiKey = env('MISTRAL_API_KEY');
        if (empty($apiKey)) return $this->fallback($diligence['diligence'] ?? '', $diligence['ref'] ?? 'D');

        $ref       = $diligence['ref']                    ?? 'D';
        $nature    = $diligence['nature_transaction']      ?? '';
        $assertion = $diligence['assertion']               ?? 'Exhaustivité';
        $dilTxt    = $diligence['diligence']               ?? '';
        $mission   = $context['mission_libelle']           ?? '';
        $defAssertion = self::ASSERTIONS[$assertion] ?? $assertion;

        $prompt = <<<PROMPT
Tu es expert en audit financier et de transactions.

Mission : {$mission}
Nature de la transaction : {$nature}
Assertion à tester : {$assertion} — {$defAssertion}
Objectif de la diligence : {$dilTxt}

Génère une diligence d'audit précise pour tester l'assertion "{$assertion}" sur les transactions "{$nature}".

Réponds UNIQUEMENT avec ce JSON :
{
  "diligence": "Vérifier / S'assurer / Contrôler… (phrase 10-20 mots)",
  "procedures": [
    "Sélectionner un échantillon de {$nature} sur la période auditée…",
    "Vérifier l'existence / la comptabilisation de…",
    "Rapprocher les pièces justificatives avec…",
    "Extrapoler les résultats à l'ensemble des transactions…"
  ]
}
PROMPT;

        $result = $this->callMistral($prompt, $apiKey);
        if (!$result['ok']) return $this->fallback($dilTxt, $ref);

        $data = $this->parseJson($result['text']);
        if (empty($data['diligence'])) return $this->fallback($dilTxt, $ref);

        return [
            'success'   => true,
            'diligence' => $data['diligence'],
            'procedures'=> array_values(array_filter((array)($data['procedures'] ?? []), fn($p) => strlen(trim($p)) >= 10)),
            'mode'      => 'ai',
        ];
    }
}