<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PROG CI AI SUGGESTION SERVICE — VERSION AVEC RETRY RATE LIMIT
 */
class ProgCiAiSuggestionService
{
    private const API_URL     = 'https://api.mistral.ai/v1/chat/completions';
    private const MODEL       = 'mistral-small-latest';   // ou mistral-large-latest si tu as le budget
    private const MAX_TOKENS  = 3500;
    private const TIMEOUT     = 60;
    private const API_KEY_ENV = 'MISTRAL_API_KEY';

    private const MAX_RETRIES = 3;

    public function reformulerObjectif(array $objectif, array $context = []): array
    {
        try {
            $apiKey = env(self::API_KEY_ENV);
            if (empty($apiKey)) {
                Log::warning('[PTCI IA] Clé API manquante → fallback');
                return $this->fallbackParObjectif($objectif);
            }

            $objectif = $this->nettoyerObjectif($objectif);
            $prompt   = $this->construirePrompt($objectif, $context);

            Log::info('[PTCI IA] Appel Mistral', ['objectif_num' => $objectif['num'] ?? '?']);

            $reponse = $this->appelerMistralAvecRetry($prompt, $apiKey);

            if (!$reponse['ok']) {
                Log::warning('[PTCI IA] Échec après retry', ['error' => $reponse['error']]);
                return $this->fallbackParObjectif($objectif);
            }

            $tests = $this->parserReponse($reponse['text'], $objectif['num'] ?? 'O');

            if (empty($tests)) {
                return $this->fallbackParObjectif($objectif);
            }

            return [
                'success' => true,
                'tests'   => $tests,
                'mode'    => 'ai'
            ];

        } catch (\Exception $e) {
            Log::error('[PTCI IA] Exception : ' . $e->getMessage());
            return $this->fallbackParObjectif($objectif);
        }
    }

    /**
     * Appel avec retry automatique sur 429
     */
    private function appelerMistralAvecRetry(string $prompt, string $apiKey): array
    {
        $attempt = 0;

        while ($attempt <= self::MAX_RETRIES) {
            $result = $this->appelerMistral($prompt, $apiKey);

            if ($result['ok']) {
                return $result;
            }

            // Si c'est un rate limit (429), on attend avant de réessayer
            if (str_contains($result['error'] ?? '', '429') || str_contains($result['error'] ?? '', 'Too Many Requests')) {
                $attempt++;
                $delay = pow(2, $attempt) * 1000; // 2s → 4s → 8s
                Log::warning("[PTCI IA] Rate limit 429 détecté → retry {$attempt}/" . self::MAX_RETRIES . " après {$delay}ms");
                usleep($delay * 1000); // délai en microsecondes
                continue;
            }

            // Autre erreur → on arrête
            return $result;
        }

        return ['ok' => false, 'error' => 'Rate limit persistant après ' . self::MAX_RETRIES . ' tentatives'];
    }

    /**
     * Appel brut à Mistral
     */
    private function appelerMistral(string $prompt, string $apiKey): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(self::TIMEOUT)->post(self::API_URL, [
                'model'       => self::MODEL,
                'temperature' => 0.3,
                'max_tokens'  => self::MAX_TOKENS,
                'messages'    => [
                    ['role' => 'system', 'content' => 'Tu réponds uniquement avec du JSON valide. Pas de texte avant ou après.'],
                    ['role' => 'user',   'content' => $prompt]
                ],
            ]);

            if (!$response->successful()) {
                $status = $response->status();
                $body   = substr($response->body(), 0, 300);
                Log::error('[PTCI IA] Erreur HTTP Mistral', ['status' => $status, 'body' => $body]);
                return ['ok' => false, 'error' => "HTTP {$status}"];
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? '';

            return $text 
                ? ['ok' => true, 'text' => $text] 
                : ['ok' => false, 'error' => 'Réponse vide'];

        } catch (\Exception $e) {
            Log::error('[PTCI IA] Exception HTTP', ['message' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // ===================================================================
    // Les méthodes suivantes restent identiques à la version précédente
    // (construirePrompt, parserReponse, nettoyerObjectif, fallback..., etc.)
    // ===================================================================

    private function construirePrompt(array $objectif, array $context): string
    {
        $numObjectif   = $objectif['num'] ?? 'O1';
        $texteObjectif = trim($objectif['objectif'] ?? '');
        $axeRado       = trim($objectif['_axe_rado'] ?? $context['axe_rado'] ?? '');
        $processus     = trim($objectif['_process_name'] ?? $context['processus'] ?? '');
        $risque        = trim($objectif['_risque_libelle'] ?? '');

        $entreprise = $context['entreprise_nom'] ?? 'FRUITIVA';

        return <<<PROMPT
Tu es expert en audit interne.

Contexte entreprise : {$entreprise} (secteur agroalimentaire, Bénin)

Objectif d'audit (réf. {$numObjectif}) :
{$texteObjectif}

Axe : {$axeRado}
Processus : {$processus}


Génère UN SEUL test d'audit adapté.

- libelle : phrase courte (15-20 mots) commençant par Vérifier / Contrôler / S'assurer / Examiner...
- procedures : exactement 3 à 5 étapes, chacune commençant par un verbe d'action.

Réponds UNIQUEMENT avec ce JSON :

{
  "test": {
    "libelle": "...",
    "procedures": ["...", "...", "..."]
  }
}
PROMPT;
    }

    private function parserReponse(string $contenu, string $numObjectif): array
    {
        $contenu = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $contenu));

        $data = json_decode($contenu, true);

        if (!$data && preg_match('/\{.*"test".*\}/s', $contenu, $m)) {
            $data = json_decode($m[0], true);
        }

        if (!$data || empty($data['test']['libelle'])) {
            return [];
        }

        $test = $data['test'];
        $libelle = trim($test['libelle']);
        $procs = array_map('trim', (array)($test['procedures'] ?? []));

        $procedures = [];
        foreach ($procs as $p) {
            $p = preg_replace('/^\d+\.\s*|^[-•*]\s*/', '', $p);
            if (strlen($p) >= 10) $procedures[] = $p;
        }

        if (strlen($libelle) < 15 || count($procedures) < 3) {
            return [];
        }

        return [[
            'ref'        => 'T_' . $numObjectif,
            'libelle'    => $libelle,
            'procedures' => array_slice($procedures, 0, 5)
        ]];
    }

    private function nettoyerObjectif(array $objectif): array
    {
        return $objectif;
    }

    private function fallbackParObjectif(array $objectif): array
    {
        $num = $objectif['num'] ?? 'O';
        return [[
            'ref'        => 'T_' . $num,
            'libelle'    => 'Vérifier la conformité du processus avec les procédures internes',
            'procedures' => [
                'Sélectionner un échantillon représentatif',
                'Vérifier le respect des procédures en vigueur',
                'Identifier les anomalies',
                'Documenter les écarts'
            ]
        ]];
    }
}