<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MissionTitleAIService
{
    private string $apiKey;
    private string $model    = 'mistral-small-latest';
    private string $endpoint = 'https://api.mistral.ai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.key', env('MISTRAL_API_KEY', ''));
    }

    public function suggestTitles(string $objective, string $type = '', string $entity = '', string $domain = '', int $year = 0): array
    {
        $year = $year ?: (int) date('Y');

        if (empty($this->apiKey)) {
            Log::warning('MissionTitleAI: clé API manquante, utilisation du fallback.');
            return [
                'success'     => true,
                'suggestions' => $this->fallback($objective, $type, $entity, $domain, $year)
            ];
        }

        try {
            $contextParts = array_filter([
                $type   ? "Type : {$type}"      : '',
                $entity ? "Entité(s) : {$entity}" : '',
                $domain ? "Domaine : {$domain}"  : '',
                "Exercice : {$year}",
            ]);
            $context = implode(' | ', $contextParts);

            $prompt = "Génère exactement 4 titres professionnels et distincts pour une mission d'audit interne, en français.\n\n"
                    . "But de la mission : {$objective}\n"
                    . "Contexte : {$context}\n\n"
                    . "Règles :\n"
                    . "- Chaque titre doit faire entre 45 et 110 caractères.\n"
                    . "- Ils doivent refléter différents angles : risque, conformité, performance, contrôle interne.\n"
                    . "- N'utilise pas de guillemets, ni de numérotation dans le titre.\n"
                    . "- Réponds UNIQUEMENT avec un objet JSON valide, sans aucun texte avant/après, au format :\n"
                    . "{\"suggestions\":[\"Titre 1\",\"Titre 2\",\"Titre 3\",\"Titre 4\"]}";

            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->post($this->endpoint, [
                    'model'       => $this->model,
                    'temperature' => 0.8,
                    'max_tokens'  => 400,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Tu es un assistant spécialisé en audit interne. Réponds uniquement en JSON valide.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ]);

            Log::info('Réponse Mistral brute : ' . $response->body());

            if (!$response->successful()) {
                Log::error('MissionTitleAI: erreur HTTP ' . $response->status());
                return $this->fallbackResponse($objective, $type, $entity, $domain, $year);
            }

            $content = $response->json('choices.0.message.content', '');
            $clean = preg_replace(['/^```(?:json)?\s*/i', '/\s*```$/i'], '', trim($content));
            $data = json_decode($clean, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('MissionTitleAI: JSON invalide', ['raw' => $content]);
                return $this->fallbackResponse($objective, $type, $entity, $domain, $year);
            }

            if (!empty($data['suggestions']) && is_array($data['suggestions'])) {
                $suggestions = array_values(array_filter(array_map('trim', array_slice($data['suggestions'], 0, 4))));
                if (count($suggestions) >= 2) {
                    return [
                        'success'     => true,
                        'suggestions' => $suggestions
                    ];
                }
            }

            return $this->fallbackResponse($objective, $type, $entity, $domain, $year);

        } catch (\Throwable $e) {
            Log::error('MissionTitleAI: exception - ' . $e->getMessage());
            return $this->fallbackResponse($objective, $type, $entity, $domain, $year);
        }
    }

    private function fallbackResponse(string $obj, string $type, string $entity, string $domain, int $year): array
    {
        return [
            'success'     => true,
            'suggestions' => $this->fallback($obj, $type, $entity, $domain, $year)
        ];
    }

   
}