<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FactorAIService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('MISTRAL_API_KEY');
    }

    public function improveName(string $label, string $description = ''): array
    {
        try {
            if (!$label) {
                return ['success' => false, 'label' => $label, 'description' => $description];
            }

            if (!$this->apiKey) {
                return ['success' => false, 'label' => $label, 'description' => $description];
            }

            Log::info("Improve name");

            $prompt = "Améliore: Label=\"$label\" Description=\"$description\"
JSON: {\"label\": \"label amélioré\", \"description\": \"description améliorée\"}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($this->apiKey),
                'Content-Type' => 'application/json'
            ])
            ->timeout(30)
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-small-latest',
                'messages' => [
                    ['role' => 'system', 'content' => 'JSON only'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.6
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'label' => $label, 'description' => $description];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $json = json_decode($content, true);

            if (!is_array($json) || !isset($json['label'])) {
                return ['success' => false, 'label' => $label, 'description' => $description];
            }

            return [
                'success' => true,
                'label' => trim($json['label']),
                'description' => trim($json['description'] ?? $description)
            ];

        } catch (\Exception $e) {
            Log::error('IA Error: ' . $e->getMessage());
            return ['success' => false, 'label' => $label, 'description' => $description];
        }
    }
}