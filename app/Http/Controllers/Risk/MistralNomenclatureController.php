<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MistralNomenclatureController extends Controller
{
    // ---------------------------------------------------------------
    // suggest — génère des facteurs de risque (niveau 2) pour un type
    // Route : POST /nomenclature/mistral/suggest
    // ---------------------------------------------------------------
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'type_code' => ['required', 'string'],
            'sector'    => ['required', 'string', 'min:3'],
            'context'   => ['nullable', 'string'],
        ]);

        $typeLabels = [
            'RC' => 'Risque de Conformité',
            'RF' => 'Risque Financier',
            'RS' => 'Risque Stratégique',
            'RO' => 'Risque Opérationnel',
        ];

        $typeCode  = $request->input('type_code');
        $typeLabel = $typeLabels[$typeCode] ?? $typeCode;
        $sector    = $request->input('sector');
        $context   = $request->input('context', '');

        $prompt = <<<PROMPT
Tu es un expert en gestion des risques d'entreprise, spécialisé dans le secteur : {$sector}.
{$context}

Génère exactement 5 facteurs de risque (niveau 2) pour le type : {$typeLabel} ({$typeCode}).
Adapte au secteur {$sector}. Réponds UNIQUEMENT en JSON valide, sans markdown :
{
  "suggestions": [
    { "label": "...", "description": "..." }
  ],
  "type_label": "{$typeLabel}",
  "type_color": "#000000",
  "sector": "secteur utilisé"
}
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => config('services.mistral.model', 'mistral-small-latest'),
                'temperature' => 0.5,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (! $response->successful()) {
                return response()->json(['message' => 'Erreur Mistral : ' . $response->status()], 502);
            }

            $content = preg_replace('/```json\s*|\s*```/', '', trim(
                $response->json('choices.0.message.content', '')
            ));
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['suggestions'])) {
                return response()->json(['message' => 'Réponse IA invalide.'], 502);
            }

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur IA.'], 502);
        }
    }

    // ---------------------------------------------------------------
    // suggestAppetite — génère la description d'appétence pour un facteur
    //
    // Route : POST /nomenclature/mistral/suggest-appetite
    // Body  : { risk_code, risk_label, appetite_label }
    //
    // Pas de secteur, pas de contexte — uniquement le facteur + le niveau.
    // ---------------------------------------------------------------
    public function suggestAppetite(Request $request): JsonResponse
    {
        $request->validate([
            'risk_code'      => ['required', 'string'],
            'risk_label'     => ['required', 'string'],
            'appetite_label' => ['required', 'string'],
        ]);

        $riskCode      = $request->input('risk_code');
        $riskLabel     = $request->input('risk_label');
        $appetiteLabel = $request->input('appetite_label');

        $prompt = <<<PROMPT
Tu es expert en gestion des risques d'entreprise.

Facteur de risque : {$riskCode} — {$riskLabel}
Niveau d'appétence : {$appetiteLabel}

Rédige une description d'appétence précise et opérationnelle pour CE facteur spécifique
avec CE niveau d'appétence. En 2-3 phrases maximum, en français professionnel.
Indique concrètement ce qui est toléré ou non et les contrôles associés.

Réponds UNIQUEMENT en JSON valide, sans markdown :
{ "description": "..." }
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => config('services.mistral.model', 'mistral-small-latest'),
                'temperature' => 0.3,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (! $response->successful()) {
                return response()->json(['message' => 'Erreur Mistral.'], 502);
            }

            $content = preg_replace('/```json\s*|\s*```/', '', trim(
                $response->json('choices.0.message.content', '')
            ));
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['description'])) {
                return response()->json(['message' => 'Réponse IA invalide.'], 502);
            }

            return response()->json(['description' => $data['description']]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur IA.'], 502);
        }
    }
}