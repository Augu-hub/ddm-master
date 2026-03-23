<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class MistralAssistant
{
    protected string $model      = 'mistral-small-latest';
    protected int    $maxTokens  = 1500;
    protected float  $temperature = 0.4;

    // --- Contrat ----------------------------------------------------------

    abstract protected function systemPrompt(): string;
    abstract protected function buildUserPrompt(array $params): string;
    abstract protected function parseResponse(array $json, array $params): array;

    // --- Methode principale -----------------------------------------------

    /**
     * Envoie la requete a Mistral et retourne les suggestions parsees.
     *
     * @throws \RuntimeException en cas d'erreur API, de refus ou de parsing
     */
    public function suggest(array $params): array
    {
        $response = $this->callApi($params);

        if ($response->failed()) {
            Log::error('Mistral API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'class'  => static::class,
            ]);
            throw new \RuntimeException(
                "L'assistant IA est temporairement indisponible. (HTTP {$response->status()})"
            );
        }

        $content = $response->json('choices.0.message.content', '');

        if (empty($content)) {
            throw new \RuntimeException("L'assistant IA n'a retourne aucun contenu.");
        }

        // Detecte une reponse en langage naturel (refus, hors-sujet) avant de parser
        if ($refusalMsg = $this->detectRefusal($content)) {
            Log::info('Mistral refusal detected', [
                'class'   => static::class,
                'preview' => substr($content, 0, 200),
            ]);
            throw new \RuntimeException($refusalMsg);
        }

        $json = $this->extractJson($content);

        return $this->parseResponse($json, $params);
    }

    // --- Appel HTTP -------------------------------------------------------

    protected function callApi(array $params): Response
    {
        return Http::withToken(config('services.mistral.api_key'))
            ->timeout(30)
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => $this->model,
                'max_tokens'  => $this->maxTokens,
                'temperature' => $this->temperature,
                'messages'    => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user',   'content' => $this->buildUserPrompt($params)],
                ],
            ]);
    }

    // --- Detection de refus -----------------------------------------------

    /**
     * Detecte si Mistral a repondu en texte libre (refus, incomprehension)
     * plutot qu'en JSON structure.
     *
     * Retourne un message d'erreur actionnable, ou null si la reponse semble valide.
     */
    protected function detectRefusal(string $content): ?string
    {
        $trimmed = trim($content);
        $lower   = strtolower($trimmed);

        // Si ca commence par { ou [ c'est probablement du JSON valide
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            return null;
        }

        // Patterns de refus explicite de l'IA
        $refusalPatterns = [
            'je ne suis pas en mesure',
            'je ne peux pas',
            'je suis desole',
            'je suis désolé',
            'il m\'est impossible',
            'en tant qu\'assistant',
            'en tant qu\'ia',
            'en tant que modele',
            'en tant que modèle',
            'as an ai',
            'i cannot',
            'i\'m sorry',
            'je ne comprends pas',
            'cette demande depasse',
            'cette demande dépasse',
            'je n\'ai pas',
            'je ne dispose pas',
            'je ne suis pas capable',
        ];

        foreach ($refusalPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return "L'assistant IA n'a pas pu generer de suggestions pour ce secteur. "
                    . "Reformulez en precisant davantage le domaine d'activite "
                    . "(ex : \"banque commerciale\" plutot que \"finance\").";
            }
        }

        // Reponse longue en prose sans aucune structure JSON = hors-sujet
        if (!str_contains($trimmed, '{') && !str_contains($trimmed, '[') && strlen($trimmed) > 80) {
            return "L'assistant IA a fourni une reponse hors format attendu. "
                . "Essayez de preciser le secteur d'activite et d'ajouter du contexte.";
        }

        return null;
    }

    // --- Utilitaires JSON -------------------------------------------------

    /**
     * Extrait et decode le JSON depuis la reponse Mistral
     * (qui peut contenir du texte autour du bloc JSON).
     */
    protected function extractJson(string $content): array
    {
        // Tentative directe
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Extraction bloc ```json ... ```
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $content, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Extraction premier { ... } ou [ ... ]
        if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        Log::warning('Mistral JSON parse failed', [
            'content' => substr($content, 0, 500),
            'class'   => static::class,
        ]);

        throw new \RuntimeException(
            "La reponse de l'assistant IA n'est pas au format attendu. Veuillez reessayer."
        );
    }

    /**
     * Assainit une couleur HEX retournee par Mistral.
     */
    protected function sanitizeColor(string $color, string $default = '#6b7280'): string
    {
        $color = trim($color);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : $default;
    }

    /**
     * Clamp un score entre min et max.
     */
    protected function clampScore(mixed $score, int $min, int $max): int
    {
        return max($min, min($max, (int) $score));
    }
}
