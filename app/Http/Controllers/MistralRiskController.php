<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralRiskController extends Controller
{
    private const MISTRAL_API_URL = 'https://api.mistral.ai/v1/chat/completions';
    private const MISTRAL_MODEL   = 'mistral-large-latest';
    private const NB_SUGGESTIONS  = 4;

    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'secteur'              => 'required|string|max:255',
            'activite_code'        => 'required|string|max:50',
            'activite_nom'         => 'required|string|max:255',
            'processus_code'       => 'required|string|max:50',
            'processus_nom'        => 'required|string|max:255',
            'macro_processus'      => 'nullable|string|max:255',
            'nomenclature_domaine' => 'nullable|string|max:255',
            'nomenclature_famille' => 'nullable|string|max:255',
            'nomenclature_type'    => 'nullable|string|max:255',
        ]);

        $prompt = $this->buildPrompt($request);

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
                    'Content-Type'  => 'application/json',
                ])
                ->post(self::MISTRAL_API_URL, [
                    'model'       => self::MISTRAL_MODEL,
                    'temperature' => 0.4,
                    'messages'    => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('MistralRisk API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json(['error' => 'Erreur API Mistral'], 502);
            }

            $content = $response->json('choices.0.message.content', '');
            $risks   = $this->parseResponse($content);

            return response()->json(['suggestions' => $risks]);

        } catch (\Exception $e) {
            Log::error('MistralRisk exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Service indisponible'], 503);
        }
    }

    private function systemPrompt(): string
    {
        return 'Tu es un expert en gestion des risques certifie ISO 31000, COSO ERM et Basel II. '
            . 'Tu analyses des processus metier et identifies des risques operationnels, financiers, '
            . 'strategiques et de conformite. '
            . 'Tu reponds UNIQUEMENT en JSON valide, sans texte avant ou apres, sans bloc markdown. '
            . 'Ta reponse est un tableau JSON de risques structures. '
            . 'Chaque risque est precis, actionnable et adapte au contexte fourni. '
            . 'Tes suggestions sont redigees en francais professionnel.';
    }

    private function buildPrompt(Request $request): string
    {
        $n        = self::NB_SUGGESTIONS;
        $secteur  = $request->secteur;
        $actCode  = $request->activite_code;
        $actNom   = $request->activite_nom;
        $procCode = $request->processus_code;
        $procNom  = $request->processus_nom;
        $macro    = $request->macro_processus ?? '';

        $nomCtx = '';
        if ($request->nomenclature_type) {
            $nomCtx = "Type de risque cible : {$request->nomenclature_type}";
            if ($request->nomenclature_famille) $nomCtx .= " (famille : {$request->nomenclature_famille})";
            if ($request->nomenclature_domaine) $nomCtx .= " (domaine : {$request->nomenclature_domaine})";
        } elseif ($request->nomenclature_famille) {
            $nomCtx = "Famille de risque : {$request->nomenclature_famille}";
        } elseif ($request->nomenclature_domaine) {
            $nomCtx = "Domaine de risque : {$request->nomenclature_domaine}";
        }

        return "Contexte de l'organisation :\n"
            . "- Secteur d'activite : {$secteur}\n"
            . "- Macro-processus : {$macro}\n"
            . "- Processus : {$procCode} - {$procNom}\n"
            . "- Activite analysee : {$actCode} - {$actNom}\n"
            . ($nomCtx ? "- {$nomCtx}\n" : '')
            . "\n"
            . "Genere exactement {$n} risques distincts et realistes pour cette activite dans ce secteur.\n"
            . "\n"
            . "Reponds avec ce JSON (tableau de {$n} objets, rien d'autre) :\n"
            . "[\n"
            . "  {\n"
            . '    "libelle": "Intitule concis du risque (max 120 caracteres)",' . "\n"
            . '    "causes": "Description des causes potentielles (2-4 phrases)",' . "\n"
            . '    "consequences": "Description des consequences et impacts (2-4 phrases)",' . "\n"
            . '    "controles_existants": "Controles et dispositifs de maitrise recommandes (2-4 phrases)",' . "\n"
            . '    "plan_traitement": "Plan action et mesures de traitement suggeres (2-4 phrases)"' . "\n"
            . "  }\n"
            . "]\n"
            . "\n"
            . "Consignes :\n"
            . "- Chaque risque doit etre distinct et specifique a l'activite \"{$actNom}\"\n"
            . "- Adapte le vocabulaire au secteur \"{$secteur}\"\n"
            . "- Respecte le cadre ISO 31000 et COSO ERM\n"
            . "- Les libelles commencent par un nom (ex: Rupture de..., Contamination de..., Defaut de...)\n"
            . "- Ne numerote pas les risques";
    }

    private function parseResponse(string $content): array
    {
        $clean = preg_replace('/```json\s*/i', '', $content);
        $clean = preg_replace('/```\s*/i', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (!is_array($decoded)) {
            Log::warning('MistralRisk: reponse non parseable', ['content' => $content]);
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!isset($item['libelle']) || empty(trim($item['libelle']))) {
                return null;
            }
            return [
                'libelle'             => trim($item['libelle'] ?? ''),
                'causes'              => trim($item['causes'] ?? ''),
                'consequences'        => trim($item['consequences'] ?? ''),
                'controles_existants' => trim($item['controles_existants'] ?? ''),
                'plan_traitement'     => trim($item['plan_traitement'] ?? ''),
            ];
        }, $decoded)));
    }
}
