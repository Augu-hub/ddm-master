<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class IAPropositionService
{
    private ?string $apiKey;
    private string  $model  = 'mistral-large-latest';
    private string  $apiUrl = 'https://api.mistral.ai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key') ?: null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    // =========================================================
    //  Générer une proposition pour un item QCC spécifique
    // =========================================================
    public function genererProposition(
        string  $exigence,
        string  $libelle,
        ?string $reponse,
        ?string $forces,
        ?string $faiblesses,
        ?string $objectif,
        ?string $contexte = null
    ): array {
        if (!$this->isConfigured()) {
            return $this->defaultProposition();
        }

        $statutTexte = match ($reponse) {
            'O'     => 'CONFORME',
            'N'     => 'NON CONFORME',
            'SO'    => 'SANS OBJET',
            default => 'NON ÉVALUÉ',
        };

        $contexteBlock = $contexte ? "- **Contexte additionnel**: {$contexte}" : '';

        $systemPrompt = "Tu es un expert en audit qualité et contrôle de conformité. Tu réponds UNIQUEMENT en JSON valide, sans backticks, sans texte avant ou après.";

        $userPrompt = "Analyse cet item QCC et génère une recommandation.\n\n"
            . "## ITEM QCC\n"
            . "- Libellé: {$libelle}\n"
            . "- Exigence normative: {$exigence}\n"
            . "- Statut actuel: {$statutTexte}\n"
            . "- Forces identifiées: " . ($forces ?? 'Aucune') . "\n"
            . "- Faiblesses identifiées: " . ($faiblesses ?? 'Aucune') . "\n"
            . "- Objectif de contrôle: " . ($objectif ?? 'Non défini') . "\n"
            . ($contexteBlock ? $contexteBlock . "\n" : '')
            . "\nRéponds avec ce JSON exact:\n"
            . '{"recommendation":"...","type":"amelioration|validation|alerte","priorite":"haute|moyenne|faible","actions":["..."],"echeance_suggere":"court_terme|moyen_terme|long_terme","indicateurs":["..."]}' . "\n\n"
            . "Règles: type=alerte si NON CONFORME, type=validation si CONFORME sans écart, type=amelioration sinon. priorite=haute si risque élevé.";

        $response = $this->callAPI($systemPrompt, $userPrompt);
        return $this->parseJsonResponse($response, $this->defaultProposition());
    }

    // =========================================================
    //  Générer une synthèse globale du formulaire QCC
    // =========================================================
    public function genererSyntheseGlobale(
        string     $intitule,
        Collection $items,
        ?float     $scoreGlobal
    ): array {
        if (!$this->isConfigured()) {
            return $this->defaultSynthese();
        }

        $resume = $items->map(fn($item) => [
            'libelle'    => $item->libelle_norme,
            'reponse'    => $item->reponse,
            'score'      => $item->score,
            'faiblesses' => $item->faiblesses,
            'forces'     => $item->forces,
        ])->toJson();

        $systemPrompt = "Tu es un expert en audit qualité. Tu réponds UNIQUEMENT en JSON valide, sans backticks, sans texte avant ou après.";

        $userPrompt = "Génère une synthèse globale pour ce QCC.\n\n"
            . "QCC: {$intitule}\n"
            . "Score global: {$scoreGlobal}%\n"
            . "Items: {$resume}\n\n"
            . "Réponds avec ce JSON exact:\n"
            . '{"niveau_maturite":"initial|en_developpement|defini|gere|optimise","synthese_executive":"...","points_forts":["..."],"axes_amelioration":["..."],"risques_critiques":["..."],"plan_action_prioritaire":[{"action":"...","echeance":"...","responsable":"..."}],"prochaine_etape":"..."}';

        $response = $this->callAPI($systemPrompt, $userPrompt, maxTokens: 1500);
        return $this->parseJsonResponse($response, $this->defaultSynthese());
    }

    // =========================================================
    //  Appel API Mistral
    // =========================================================
    private function callAPI(string $systemPrompt, string $userPrompt, int $maxTokens = 800): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post($this->apiUrl, [
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Mistral API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Erreur API Mistral: ' . $response->status());
        }

        // Mistral: choices[0].message.content
        return $response->json('choices.0.message.content', '');
    }

    // =========================================================
    //  Parser la réponse JSON
    // =========================================================
    private function parseJsonResponse(string $text, array $default): array
    {
        $clean = preg_replace('/```json|```/', '', $text);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Mistral JSON parse error', ['text' => $text]);
            return $default;
        }

        return $decoded;
    }

    private function defaultProposition(): array
    {
        return [
            'recommendation'   => 'Analyse non disponible. Vérifiez la clé API Mistral.',
            'type'             => 'amelioration',
            'priorite'         => 'moyenne',
            'actions'          => [],
            'echeance_suggere' => 'moyen_terme',
            'indicateurs'      => [],
        ];
    }

    private function defaultSynthese(): array
    {
        return [
            'niveau_maturite'         => 'en_developpement',
            'synthese_executive'      => 'Synthèse non disponible.',
            'points_forts'            => [],
            'axes_amelioration'       => [],
            'risques_critiques'       => [],
            'plan_action_prioritaire' => [],
            'prochaine_etape'         => '',
        ];
    }
}