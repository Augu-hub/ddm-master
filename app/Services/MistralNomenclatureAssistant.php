<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * SERVICE IA — Suggestions de nomenclatures de risques
 * Modèle : Mistral Small (mistral-small-latest)
 *
 * Trois types de suggestions :
 *   1. suggestDomains()   → Domaines (Niveau 1) pour un secteur d'activité
 *   2. suggestFamilies()  → Familles (Niveau 2) pour un domaine donné
 *   3. suggestTypes()     → Types précis (Niveau 3) pour une famille donnée
 *
 * Référentiels utilisés dans les prompts :
 *   ISO 31000, COSO ERM, Basel II/III, FERMA, OHSAS 18001
 * ════════════════════════════════════════════════════════════════════════════
 */
class MistralNomenclatureAssistant
{
    protected string $apiKey;
    protected string $baseUrl  = 'https://api.mistral.ai/v1';
    protected string $model    = 'mistral-small-latest';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key', '');

        if (!$this->apiKey) {
            Log::error('🚨 MISTRAL_API_KEY non configurée (NomenclatureAssistant)');
        } else {
            Log::info('✅ MistralNomenclatureAssistant initialisé');
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // 1. SUGGESTIONS DE DOMAINES (Niveau 1)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Suggère des domaines de risques (Niveau 1) pour un secteur donné.
     *
     * @param  array{sector: string, existing_domains: string[]}  $payload
     * @return array{domains: array{code: string, label: string, description: string}[]}
     */
    public function suggestDomains(array $payload): array
    {
        try {
            $sector   = $payload['sector']           ?? 'industrie agroalimentaire';
            $existing = implode(', ', $payload['existing_domains'] ?? []);

            $prompt = "Tu es expert en gestion des risques (ISO 31000, COSO ERM, FERMA).\n\n"
                . "Secteur d'activité: \"$sector\"\n"
                . ($existing ? "Domaines déjà définis: $existing\n\n" : "\n")
                . "Propose 6 domaines de risques pertinents pour ce secteur, "
                . "en suivant les référentiels ISO 31000 et COSO ERM.\n\n"
                . "Réponds UNIQUEMENT en JSON valide :\n"
                . "{\n"
                . "  \"domains\": [\n"
                . "    {\"code\": \"RC\", \"label\": \"Risque de conformité\", \"description\": \"Non-respect des lois et réglements\"},\n"
                . "    ...\n"
                . "  ]\n"
                . "}\n\n"
                . "RÈGLES:\n"
                . "- Codes courts de 2 lettres majuscules (ex: RC, RF, RI, RO, RS, RM)\n"
                . "- Labels clairs et professionnels\n"
                . "- Descriptions concises (1 phrase)\n"
                . "- Ne pas répéter les domaines déjà définis\n"
                . "- Adaptés au secteur spécifié";

            return $this->callMistral($prompt, 'DOMAIN_SUGGEST');

        } catch (\Exception $e) {
            Log::error('❌ suggestDomains: ' . $e->getMessage());
            return ['domains' => []];
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // 2. SUGGESTIONS DE FAMILLES (Niveau 2)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Suggère des familles de risques (Niveau 2) pour un domaine donné.
     *
     * @param  array{domain_code: string, domain_label: string, sector: string}  $payload
     * @return array{families: array{code: string, label: string, description: string}[]}
     */
    public function suggestFamilies(array $payload): array
    {
        try {
            $domainCode  = $payload['domain_code']  ?? '';
            $domainLabel = $payload['domain_label'] ?? '';
            $sector      = $payload['sector']       ?? 'entreprise';

            $prompt = "Tu es expert en gestion des risques (ISO 31000, COSO ERM, Basel II).\n\n"
                . "Domaine de risque: \"$domainLabel\" (code: $domainCode)\n"
                . "Secteur: $sector\n\n"
                . "Propose 5 à 6 familles (sous-catégories) pour ce domaine.\n\n"
                . "Réponds UNIQUEMENT en JSON valide :\n"
                . "{\n"
                . "  \"families\": [\n"
                . "    {\"code\": \"$domainCode-RH\", \"label\": \"Ressources humaines\", \"description\": \"Risques liés au capital humain\"},\n"
                . "    ...\n"
                . "  ]\n"
                . "}\n\n"
                . "RÈGLES:\n"
                . "- Codes format: {DOMAINE}-{2-3 LETTRES} (ex: RO-RH, RO-PROD)\n"
                . "- Labels professionnels et précis\n"
                . "- Descriptions brèves (1 phrase)\n"
                . "- Familles cohérentes avec le domaine \"$domainLabel\"";

            return $this->callMistral($prompt, 'FAMILY_SUGGEST');

        } catch (\Exception $e) {
            Log::error('❌ suggestFamilies: ' . $e->getMessage());
            return ['families' => []];
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // 3. SUGGESTIONS DE TYPES PRÉCIS (Niveau 3)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Suggère des types précis de risques (Niveau 3) pour une famille donnée.
     *
     * @param  array{family_code: string, family_label: string, domain_label: string, sector: string}  $payload
     * @return array{types: array{code: string, label: string, description: string}[]}
     */
    public function suggestTypes(array $payload): array
    {
        try {
            $familyCode  = $payload['family_code']  ?? '';
            $familyLabel = $payload['family_label'] ?? '';
            $domainLabel = $payload['domain_label'] ?? '';
            $sector      = $payload['sector']       ?? 'entreprise';

            $prompt = "Tu es expert en gestion des risques opérationnels (ISO 31000, Basel II/III).\n\n"
                . "Famille de risques: \"$familyLabel\" (code: $familyCode)\n"
                . "Domaine parent: \"$domainLabel\"\n"
                . "Secteur: $sector\n\n"
                . "Propose 5 à 7 risques précis et concrets pour cette famille.\n\n"
                . "Réponds UNIQUEMENT en JSON valide :\n"
                . "{\n"
                . "  \"types\": [\n"
                . "    {\"code\": \"$familyCode-001\", \"label\": \"Perte de compétences clés\", \"description\": \"Départ non anticipé d'experts ou opérateurs\"},\n"
                . "    ...\n"
                . "  ]\n"
                . "}\n\n"
                . "RÈGLES:\n"
                . "- Codes format: {FAMILLE}-{001, 002, ...}\n"
                . "- Labels: risques concrets et actionnables\n"
                . "- Descriptions: conséquence principale en 1 phrase\n"
                . "- Risques spécifiques à la famille \"$familyLabel\" et au secteur $sector";

            return $this->callMistral($prompt, 'TYPE_SUGGEST');

        } catch (\Exception $e) {
            Log::error('❌ suggestTypes: ' . $e->getMessage());
            return ['types' => []];
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // APPEL API MISTRAL (identique à MistralMPAAssistant)
    // ────────────────────────────────────────────────────────────────────────

    protected function callMistral(string $prompt, string $context = 'GENERIC'): array
    {
        if (!$this->apiKey) {
            Log::error("🚨 [$context] Mistral API key manquante");
            return [];
        }

        Log::info("🤖 [$context] Appel Mistral — " . strlen($prompt) . " chars");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
            'model'       => $this->model,
            'temperature' => 0.7,
            'max_tokens'  => 1200,
            'messages'    => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (!$response->successful()) {
            Log::error("❌ [$context] Mistral HTTP {$response->status()}: {$response->body()}");
            return [];
        }

        $content = $response->json('choices.0.message.content', '');
        Log::info("✅ [$context] Réponse reçue: " . substr($content, 0, 200));

        return $this->parseJson($content, $context);
    }

    protected function parseJson(string $content, string $context): array
    {
        // Nettoyer les balises markdown
        $clean = preg_replace('/```json|```/i', '', $content);
        $clean = trim($clean);

        // Extraire le JSON si entouré de texte
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $clean = $m[0];
        }

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("❌ [$context] JSON invalide: " . json_last_error_msg());
            Log::debug("[$context] Contenu brut: $content");
            return [];
        }

        return $decoded;
    }

    public static function validatePayloadSafety(array $data): bool
    {
        $dangerous = ['DROP', 'DELETE', 'INSERT', 'SELECT', '<script', 'eval(', 'exec('];
        $str = strtoupper(json_encode($data));
        foreach ($dangerous as $kw) {
            if (str_contains(strtoupper($str), strtoupper($kw))) {
                Log::warning('⚠️ Payload suspect détecté', ['data' => $data]);
                return false;
            }
        }
        return true;
    }
}
