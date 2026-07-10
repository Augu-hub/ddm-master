<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Même pattern que MistralMPAAssistant : lit le libellé d'un point de
 * vérification (grille ARMP) et en extrait :
 *  - les articles de loi cités (numéro + une proposition de titre/contenu)
 *  - les opérations et délais réglementaires potentiellement concernés,
 *    en les confrontant à la liste des opérations déjà paramétrées
 *    (pm_operations) pour éviter que l'IA invente des libellés qui
 *    n'existent pas dans ton référentiel.
 */
class MistralGrillesAssistant
{
    protected $apiKey;
    protected $baseUrl = 'https://api.mistral.ai/v1';
    protected $model = 'mistral-small-latest';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');

        if (!$this->apiKey) {
            Log::error('🚨 MISTRAL_API_KEY NOT CONFIGURED');
        }
    }

    /**
     * Analyse un point de contrôle et retourne :
     * [
     *   'articles' => [ ['numero' => '46', 'texte_reference' => 'Article 46 de la loi n°2020-26...',
     *                     'source_loi' => 'Loi n°2020-26 du 29 septembre 2020',
     *                     'titre' => '...', 'contenu' => '...'], ... ],
     * ]
     *
     * IMPORTANT : les articles ne sont JAMAIS inventés par l'IA. On extrait
     * d'abord par regex, à partir du texte réel du libellé, tous les
     * "article(s) N" et leur loi/décret source (ex: "articles 58 à 60 de la
     * loi n°2020-26", "décret n°2020-596"). Si le texte ne cite aucun
     * article, la liste retournée est vide — l'IA n'est appelée que pour
     * rédiger le titre/contenu des articles déjà détectés dans le texte,
     * jamais pour décider s'il y en a.
     *
     * L'IA NE CHOISIT PLUS D'OPÉRATION NI DE DÉLAI : le rattachement à une
     * opération de pm_operations (et donc au délai qui en découle) reste un
     * choix manuel de l'admin depuis le menu déroulant du tableau — c'est
     * une décision métier qui doit venir de ton référentiel existant, pas
     * d'une devinette par recouvrement de texte.
     *
     * @param string $libelleControle  Le texte du point de contrôle
     */
    public function analyserPointDeControle(string $libelleControle): array
    {
        try {
            $references = $this->extraireReferencesArticles($libelleControle);

            $articles = [];
            foreach ($references as $ref) {
                $articles[] = $this->enrichirArticle($ref, $libelleControle);
            }

            return [
                'articles' => $articles,
            ];
        } catch (\Exception $e) {
            Log::error('❌ MistralGrillesAssistant::analyserPointDeControle - ' . $e->getMessage());
            return ['articles' => []];
        }
    }

    /**
     * Extraction 100% déterministe (regex) des références d'articles citées
     * dans le texte. Gère : "article 46", "articles 58 à 60",
     * "articles 58, 59 et 60", "article 7 bis". Retourne [] si rien n'est cité.
     */
    public function extraireReferencesArticles(string $texte): array
    {
        if (!preg_match_all(
            '/articles?\s+([0-9]+(?:\s*(?:bis|ter))?(?:\s*(?:,|et|à)\s*[0-9]+(?:\s*(?:bis|ter))?)*)/iu',
            $texte,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return [];
        }

        $vus = [];
        $resultats = [];

        foreach ($matches[1] as $match) {
            [$groupe, $offset] = $match;
            $numeros = $this->expandArticleNumbers($groupe);

            // Fenêtre de texte autour de la citation pour repérer la source (loi/décret)
            $fenetre = mb_substr($texte, max(0, $offset - 10), 180);
            $source  = $this->detecterSource($fenetre);

            foreach ($numeros as $numero) {
                $cle = $numero . '|' . ($source ?? '');
                if (isset($vus[$cle])) {
                    continue;
                }
                $vus[$cle] = true;
                $resultats[] = [
                    'numero'          => $numero,
                    'texte_reference' => 'Article ' . $numero . ($source ? ' de la ' . $source : ''),
                    'source_loi'      => $source,
                ];
            }
        }

        return $resultats;
    }

    private function expandArticleNumbers(string $groupe): array
    {
        $groupe = str_replace(' et ', ' , ', $groupe);
        $parts  = preg_split('/\s*,\s*/u', trim($groupe));
        $numeros = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            if (preg_match('/^([0-9]+)\s*à\s*([0-9]+)$/u', $part, $m)) {
                for ($i = (int) $m[1]; $i <= (int) $m[2]; $i++) {
                    $numeros[] = (string) $i;
                }
            } else {
                $numeros[] = $part;
            }
        }

        return array_values(array_unique($numeros));
    }

    private function detecterSource(string $fenetre): ?string
    {
        if (preg_match('/d[ée]cret\s*n[°ºo]?\s*(2020-\d{3})(?:\s*du\s*([0-9]{1,2}\s*\p{L}+\s*2020))?/iu', $fenetre, $m)) {
            $numero = $m[1];
            $date   = $m[2] ?? null;
            return 'Décret n°' . $numero . ($date ? ' du ' . trim($date) : '');
        }
        if (preg_match('/loi\s*n[°ºo]?\s*2020-26/iu', $fenetre)) {
            return 'Loi n°2020-26 du 29 septembre 2020';
        }
        return null;
    }

    /**
     * Demande à l'IA UNIQUEMENT le titre + contenu résumé d'un article déjà
     * identifié par regex — jamais de réinventer le numéro ou la source.
     */
    private function enrichirArticle(array $reference, string $contexte): array
    {
        $prompt = 'Tu es un juriste expert du droit béninois de la commande publique. '
            . "Pour \"{$reference['texte_reference']}\", dans le contexte suivant d'une grille d'audit : "
            . "\"{$contexte}\".\n\n"
            . "Donne un titre court résumant l'objet de cet article, et un contenu de 2-3 phrases "
            . "résumant fidèlement sa teneur réglementaire réelle. Si tu n'es pas certain du contenu "
            . "exact de cet article précis, réponds avec contenu à null plutôt que d'inventer.\n\n"
            . "Réponds UNIQUEMENT en JSON : {\"titre\": \"...\", \"contenu\": \"...\"}";

        $result = $this->callMistral($prompt, 'ARTICLE_ENRICH');

        return array_merge($reference, [
            'titre'   => $result['titre']   ?? null,
            'contenu' => $result['contenu'] ?? null,
        ]);
    }

    /**
     * Analyse en lot tous les items d'une grille en une seule requête,
     * pour limiter le nombre d'appels API (utilisé par "Analyser toute la grille").
     * Retourne un tableau indexé par item_id.
     *
     * @param array $items  [ ['id' => 12, 'numero' => '7', 'libelle_controle' => '...'], ... ]
     */
    public function analyserGrilleComplete(array $items): array
    {
        $resultats = [];
        // Traité un par un pour garder une extraction fiable et traçable ;
        // Mistral small répond vite, le coût reste raisonnable pour ~20-90 items par grille.
        foreach ($items as $item) {
            $resultats[$item['id']] = $this->analyserPointDeControle($item['libelle_controle']);
        }
        return $resultats;
    }

    protected function callMistral(string $prompt, string $context = ''): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('❌ API KEY vide');
                return [];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($this->apiKey),
                'Content-Type'  => 'application/json',
            ])
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'    => $this->model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es un expert juridique en marchés publics béninois. '
                            . 'Réponds UNIQUEMENT en JSON valide et structuré. Pas de markdown, pas d\'explications.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens'  => 1500,
                'temperature' => 0.2, // basse température : on veut de la rigueur juridique, pas de créativité
            ]);

            if (!$response->successful()) {
                Log::error("❌ Mistral Error ($context): " . $response->status() . ' - ' . $response->body());
                return [];
            }

            $content = $response->json()['choices'][0]['message']['content'] ?? '';
            $parsed  = $this->parseJSON($content);

            return $parsed ?? [];
        } catch (\Exception $e) {
            Log::error("❌ Exception ($context): " . $e->getMessage());
            return [];
        }
    }

    protected function parseJSON(string $text): ?array
    {
        $text = trim($text);

        $json = json_decode($text, true);
        if (is_array($json)) return $json;

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $m)) {
            $json = json_decode($m[1], true);
            if (is_array($json)) return $json;
        }

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json)) return $json;
        }

        Log::warning('❌ Could not parse JSON: ' . substr($text, 0, 300));
        return null;
    }
}