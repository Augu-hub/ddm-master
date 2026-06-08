<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MistralRiskController extends Controller
{
    private string $apiUrl   = 'https://api.mistral.ai/v1/chat/completions';
    private string $model    = 'mistral-large-latest';

    private function tenantId(): int
    {
        return (int) (session('tenant_id') ?? 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // POST /risks/mistral/suggest
    // mode = 'factors'  → suggère des facteurs de risque pour une activité
    // mode = 'risks'    → suggère des risques pour un facteur donné
    // ═══════════════════════════════════════════════════════════════════════
    public function suggest(Request $request): JsonResponse
    {
        $mode = $request->input('mode', 'factors');

        return match ($mode) {
            'factors' => $this->suggestFactors($request),
            'risks'   => $this->suggestRisks($request),
            default   => response()->json(['error' => 'Mode inconnu'], 422),
        };
    }

    // ─────────────────────────────────────────────────────────────────────
    // Suggérer les facteurs de risque d'une activité
    // ─────────────────────────────────────────────────────────────────────
    private function suggestFactors(Request $request): JsonResponse
    {
        $tid          = $this->tenantId();
        $activityId   = $request->integer('activity_id');
        $activityName = $request->string('activity_name');
        $processName  = $request->string('process_name');
        $macroName    = $request->string('macro_name');
        $macroKind    = $request->string('macro_kind');

        // Contexte utilisateur connecté (pour personnaliser les suggestions)
        $userName  = auth()->user()?->name ?? 'utilisateur';
        $userRole  = auth()->user()?->role  ?? null;

        // Récupérer les risques déjà existants pour cette activité
        $existingRisks = DB::connection('tenant')
            ->table('risk_register')
            ->where('tenant_id', $tid)
            ->where('activity_id', $activityId)
            ->whereNull('deleted_at')
            ->pluck('libelle')
            ->implode(', ');

        $systemPrompt = <<<PROMPT
Tu es un expert en gestion des risques opérationnels et en cartographie des processus.
Tu analyses des activités d'entreprise et identifies les facteurs de risque majeurs.
Réponds UNIQUEMENT en JSON valide, sans texte avant ou après, sans balises markdown.
PROMPT;

        $existingLine = $existingRisks ? "- Risques déjà identifiés : {$existingRisks}" : "";
        $userPrompt = "Analyse cette activité et identifie ses facteurs de risque :\n\n"
            . "- Macro-processus : {$macroName} (type : {$macroKind})\n"
            . "- Processus : {$processName}\n"
            . "- Activité : {$activityName}\n"
            . "- Responsable connecté : {$userName}\n"
            . ($existingLine ? "{$existingLine}\n" : "")
            . "\nGénère exactement 1 facteurs de risque pour cette activité pas plus de 100 caractères.\n"
            . "Un facteur de risque est la condition propice qui favorise la survenance du risque"
            . "Réponds en JSON strict :\n"
            . '{' . "\n"
            . '  "description": "Une phrase décrivant le contexte de risque global de cette activité",' . "\n"
            . '  "factors": [' . "\n"
            . '    "Facteur 1 - description courte en 9-12 mots",' . "\n"
            
            . '  ]' . "\n"
            . '}';

        return $this->callMistral($systemPrompt, $userPrompt, function ($content) {
            $data = json_decode($content, true);
            return response()->json([
                'description' => $data['description'] ?? '',
                'factors'     => $data['factors']     ?? [],
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Suggérer des risques pour un facteur + activité
    // ─────────────────────────────────────────────────────────────────────
    //une catégorie regroupant plusieurs risques liés
    private function suggestRisks(Request $request): JsonResponse
    {
        $tid          = $this->tenantId();
        $activityId   = $request->integer('activity_id');
        $activityName = $request->string('activity_name');
        $processName  = $request->string('process_name');
        $macroName    = $request->string('macro_name');
        $factorLabel  = $request->string('factor_label');
        $factorDesc   = $request->string('factor_description', '');

        // Risques déjà existants pour ce facteur
        $existingRisks = [];
        if ($request->filled('factor_id')) {
            $existingRisks = DB::connection('tenant')
                ->table('risk_register')
                ->where('tenant_id', $tid)
                ->where('factor_id', $request->integer('factor_id'))
                ->whereNull('deleted_at')
                ->pluck('libelle')
                ->toArray();
        }

        $userName = auth()->user()?->name ?? 'utilisateur';
        $existing = empty($existingRisks) ? '' : implode('; ', $existingRisks);

        $systemPrompt = <<<PROMPT
Tu es un expert en gestion des risques opérationnels pour des entreprises africaines.
Tu génères des risques précis, concrets et actionnables.
Réponds UNIQUEMENT en JSON valide, sans texte avant ou après, sans balises markdown.
PROMPT;

        $factorDescLine = $factorDesc ? "- Description du facteur : {$factorDesc}" : "";
        $existingLine2  = $existing    ? "- Risques déjà enregistrés (ne pas répéter) : {$existing}" : "";
        $userPrompt = "Génère des risques pour ce contexte :\n\n"
            . "- Macro-processus : {$macroName}\n"
            . "- Processus : {$processName}\n"
            . "- Activité : {$activityName}\n"
            . "- Facteur de risque : {$factorLabel}\n"
            . ($factorDescLine ? "{$factorDescLine}\n" : "")
            . ($existingLine2  ? "{$existingLine2}\n"  : "")
            . "- Connecté en tant que : {$userName}\n\n"
            . "Génère 3 à 5 risques distincts pas plus de 100 caractères, précis, liés directement à ce facteur de risque.\n"
            . "Formule chaque risque comme un événement négatif potentiel.\n\n"
            . "Réponds en JSON strict :\n"
            . '{' . "\n"
            . '  "risks": [' . "\n"
            . '    "Libellé risque 1",' . "\n"
            . '    "Libellé risque 2",' . "\n"
            . '    "Libellé risque 3"' . "\n"
            . '  ]' . "\n"
            . '}';

        return $this->callMistral($systemPrompt, $userPrompt, function ($content) {
            $data = json_decode($content, true);
            return response()->json([
                'risks' => $data['risks'] ?? [],
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Appel API Mistral
    // ─────────────────────────────────────────────────────────────────────
    private function callMistral(string $system, string $user, callable $onSuccess): JsonResponse
    {
        $apiKey = config('services.mistral.key') ?? env('MISTRAL_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'Clé API Mistral non configurée.'], 500);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post($this->apiUrl, [
                    'model'       => $this->model,
                    'temperature' => 0.4,
                    'max_tokens'  => 800,
                    'messages'    => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $user],
                    ],
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Erreur API Mistral : ' . $response->status(),
                ], 500);
            }

            $content = $response->json('choices.0.message.content') ?? '';

            // Nettoyer les éventuels blocs markdown ```json ... ```
            $content = preg_replace('/^```json\s*/m', '', $content);
            $content = preg_replace('/^```\s*/m',     '', $content);
            $content = trim($content);

            if (!json_decode($content)) {
                return response()->json(['error' => 'Réponse IA non parseable.'], 500);
            }

            return $onSuccess($content);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}