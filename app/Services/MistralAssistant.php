<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class MistralAssistant
{
    protected string $model   = 'mistral-small-latest';
    protected int    $maxTokens = 1500;
    protected float  $temperature = 0.4;

    // ─── Contrat ──────────────────────────────────────────────────────────────

    /**
     * Construit le prompt système de l'assistant.
     */
    abstract protected function systemPrompt(): string;

    /**
     * Construit le prompt utilisateur à partir des paramètres.
     */
    abstract protected function buildUserPrompt(array $params): string;

    /**
     * Valide et normalise la réponse JSON de Mistral.
     * Doit retourner un tableau prêt pour le front.
     */
    abstract protected function parseResponse(array $json, array $params): array;

    // ─── Méthode principale ───────────────────────────────────────────────────

    /**
     * Envoie la requête à Mistral et retourne les suggestions parsées.
     *
     * @throws \RuntimeException en cas d'erreur API ou de parsing
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
            throw new \RuntimeException("L'assistant IA n'a retourné aucun contenu.");
        }

        $json = $this->extractJson($content);

        return $this->parseResponse($json, $params);
    }

    // ─── Appel HTTP ───────────────────────────────────────────────────────────

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

    // ─── Utilitaires JSON ─────────────────────────────────────────────────────

    /**
     * Extrait et décode le JSON depuis la réponse Mistral
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
            "La réponse de l'assistant IA n'est pas au format attendu. Veuillez réessayer."
        );
    }

    /**
     * Assainit une couleur HEX retournée par Mistral.
     * Retourne la couleur par défaut si invalide.
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
