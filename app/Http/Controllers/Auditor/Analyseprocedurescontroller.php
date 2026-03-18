<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Services\ProcedureDocumentAnalysisService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ANALYSE DES PROCÉDURES / TEST DES TÂCHES — table : mission_phase_apt
 * ════════════════════════════════════════════════════════════════════════════════
 *
 * v4 — Nouveau flux procédure/niveaux/documents :
 *   ✅ apt_procedures : une procédure = un objet avec son BPMN propre
 *   ✅ apt_test_levels : chaque niveau a sa matrice, collecte, grille
 *   ✅ apt_level_documents : chaque niveau a ses propres documents
 *   ✅ aiSuggest : génère procédure complète, niveaux, matrice, collecte
 *   ✅ levelDocUpload : upload document pour un niveau spécifique
 *   ✅ sharedProps charge proceduresData avec niveaux + documents
 */
class AnalyseProceduresController extends Controller
{
    private const TABLE      = 'mission_phase_apt';
    private const TBL_PROC   = 'apt_procedures';
    private const TBL_LEVEL  = 'apt_test_levels';
    private const TBL_DOC    = 'apt_level_documents';
    private const TBL_TPL    = 'apt_procedure_templates';

    public function __construct(
        private ProcedureDocumentAnalysisService $aiService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET index
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $missionId    = (int) $request->input('mission_id');
            $assignmentId = (int) $request->input('assignment_id');

            $mission    = $missionId    ? $this->loadMission($missionId)       : null;
            $assignment = $assignmentId ? $this->loadAssignment($assignmentId) : null;

            if ($assignmentId) {
                $existing = DB::table(self::TABLE)->where('assignment_id', $assignmentId)->first();
                if ($existing) {
                    return redirect()->route('auditor.ac.analyse-procedures.edit', $existing->id)
                        ->with(['mission_id' => $missionId, 'assignment_id' => $assignmentId]);
                }
            }

            return Inertia::render('dashboards/Auditor/Forms/AnalyseProcedures', array_merge(
                $this->sharedProps($mission, $assignment, null),
                $this->routeUrls(null)
            ));
        } catch (\Exception $e) {
            Log::error('[APT] index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET edit
    // ─────────────────────────────────────────────────────────────────────────
    public function edit(Request $request, $formId)
    {
        try {
            $form = DB::table(self::TABLE)->where('id', $formId)->firstOrFail();

            $missionId    = (int) $request->input('mission_id',    $form->mission_id    ?? 0);
            $assignmentId = (int) $request->input('assignment_id', $form->assignment_id ?? 0);

            $mission    = $missionId    ? $this->loadMission($missionId)       : null;
            $assignment = $assignmentId ? $this->loadAssignment($assignmentId) : null;

            return Inertia::render('dashboards/Auditor/Forms/AnalyseProcedures', array_merge(
                $this->sharedProps($mission, $assignment, $form),
                $this->routeUrls($formId)
            ));
        } catch (\Exception $e) {
            Log::error('[APT] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function store(Request $request)           { return $this->upsert($request, null); }
    public function update(Request $request, $formId) { return $this->upsert($request, $formId); }

    public function destroy($formId)
    {
        try {
            DB::table(self::TABLE)->where('id', $formId)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /ai-suggest
    // Génère : procédure complète | niveaux | matrice d'un niveau | collecte
    // ─────────────────────────────────────────────────────────────────────────
    public function aiSuggest(Request $request)
    {
        try {
            $validated = $request->validate([
                'type'                 => 'required|in:procedure_complete,levels_only,matrice_niveau,collecte_niveau',
                'prompt'               => 'nullable|string|max:3000',
                'template_code'        => 'nullable|string|max:60',
                'procedure_title'      => 'nullable|string|max:500',
                'procedure_description'=> 'nullable|string|max:2000',
                'niveau_code'          => 'nullable|string|max:30',
                'niveau_libelle'       => 'nullable|string|max:200',
                'niveau_description'   => 'nullable|string',
                'items_matrice'        => 'nullable|array',
                'mission_id'           => 'nullable|integer',
                'mission_title'        => 'nullable|string|max:500',
                'entity_name'          => 'nullable|string|max:300',
            ]);

            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) {
                return response()->json(['success' => false, 'error' => 'Service IA non configuré'], 500);
            }

            $mission = $validated['mission_id'] ? $this->loadMission((int) $validated['mission_id']) : null;
            $missionCtx = $mission
                ? "Mission: {$mission->code} — {$mission->title}. Entité auditée: {$mission->entity_name}."
                : ($validated['mission_title'] ? "Mission: {$validated['mission_title']}. Entité: {$validated['entity_name']}." : '');

            $result = match ($validated['type']) {
                'procedure_complete' => $this->suggestProcedureComplete($apiKey, $validated, $missionCtx),
                'levels_only'        => $this->suggestLevelsOnly($apiKey, $validated, $missionCtx),
                'matrice_niveau'     => $this->suggestMatriceNiveau($apiKey, $validated, $missionCtx),
                'collecte_niveau'    => $this->suggestCollecteNiveau($apiKey, $validated, $missionCtx),
            };

            return response()->json(array_merge(['success' => true], $result));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[APT] aiSuggest: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── Génération procédure complète ────────────────────────────────────────
    private function suggestProcedureComplete(string $apiKey, array $data, string $missionCtx): array
    {
        // Charger le template si demandé
        $templateCtx = '';
        if (!empty($data['template_code'])) {
            $tpl = DB::table(self::TBL_TPL)->where('code', $data['template_code'])->first();
            if ($tpl) {
                $templateCtx = "\nTemplate de base: {$tpl->titre}. Niveaux prédéfinis: " . ($tpl->niveaux_defaut ?? '');
            }
        }

        $prompt = <<<PROMPT
Tu es un expert en audit interne (IIA, COSO 2013, ISO 31000). Tu dois générer une procédure de test complète.

Contexte mission : {$missionCtx}{$templateCtx}

Demande de l'auditeur : {$data['prompt']}

Génère une procédure de test complète au format JSON STRICT. Retourne UNIQUEMENT le JSON ci-dessous sans texte, sans markdown, sans backticks :

{
  "intitule": "Titre de la procédure",
  "ref_procedure": "PROC-XXX",
  "service_dept": "Direction / Département",
  "responsable_proc": "Responsable",
  "description": "Description succincte de la procédure",
  "methode": "jugement",
  "population_totale": null,
  "taille_echantillon": null,
  "bpmn_xml": null,
  "bpmn_synthese": {"titre": "", "description": "", "risques_principaux": []},
  "levels": [
    {
      "code_niveau": "N1",
      "libelle": "Préparation et planification",
      "description": "Objectif de ce niveau",
      "objectif": "Vérifier que...",
      "items_matrice": [
        {"num": "1", "is_section": false, "point_controle": "Vérifier que...", "obj_controle": "OC1", "obj_audit": "OA1", "nature": "fort", "controle_present": null, "preuve": "", "observation": "", "resultat": null}
      ],
      "plan_collecte": [
        {"num": "1", "information": "Document à collecter", "source": "Source", "methode_collecte": "Entretien", "statut": null}
      ]
    }
  ]
}

Génère 3 à 5 niveaux avec 5 à 10 points de contrôle chacun. Utilise les codes OC1-OC7 (Réalité, Exhaustivité, Exactitude, Autorisation, Séparation tâches, Conservation, Conformité).
PROMPT;

        return $this->callMistral($apiKey, $prompt, 4000);
    }

    // ── Génération niveaux uniquement ─────────────────────────────────────────
    private function suggestLevelsOnly(string $apiKey, array $data, string $missionCtx): array
    {
        $procTitle = $data['procedure_title'] ?? '';
        $procDesc  = $data['procedure_description'] ?? '';
        $prompt = <<<PROMPT
Expert en audit interne. Génère des niveaux de test pour la procédure suivante.
{$missionCtx}
Procédure : {$procTitle}
Description : {$procDesc}

Retourne UNIQUEMENT ce JSON sans markdown :
{
  "levels": [
    {"code_niveau": "N1", "libelle": "Libellé niveau", "description": "Description", "objectif": "Objectif", "items_matrice": [], "plan_collecte": []}
  ]
}
Génère 3 à 5 niveaux pertinents pour cette procédure.
PROMPT;

        return $this->callMistral($apiKey, $prompt, 2000);
    }

    // ── Génération matrice d'un niveau ────────────────────────────────────────
    private function suggestMatriceNiveau(string $apiKey, array $data, string $missionCtx): array
    {
        $procTitle  = $data['procedure_title'] ?? '';
        $niveauCode = $data['niveau_code'] ?? '';
        $niveauLib  = $data['niveau_libelle'] ?? '';
        $niveauDesc = $data['niveau_description'] ?? '';
        $prompt = <<<PROMPT
Expert audit interne (IIA, COSO 2013). Génère la matrice de test pour ce niveau de procédure.
{$missionCtx}
Procédure : {$procTitle}
Niveau : {$niveauCode} — {$niveauLib}
Description : {$niveauDesc}

Retourne UNIQUEMENT ce JSON sans markdown :
{
  "items": [
    {"num": "1", "is_section": false, "point_controle": "Vérifier que...", "obj_controle": "OC1", "obj_audit": "OA1", "nature": "fort", "controle_present": null, "preuve": "", "observation": "", "resultat": null}
  ]
}
Génère 6 à 12 points de contrôle pertinents. Tu peux insérer des lignes section {"is_section": true, "section": "NOM SECTION"} pour regrouper.
PROMPT;

        return $this->callMistral($apiKey, $prompt, 2000);
    }

    // ── Génération plan de collecte d'un niveau ───────────────────────────────
    private function suggestCollecteNiveau(string $apiKey, array $data, string $missionCtx): array
    {
        $procTitle  = $data['procedure_title'] ?? '';
        $niveauCode = $data['niveau_code'] ?? '';
        $niveauLib  = $data['niveau_libelle'] ?? '';
        $matriceCtx = !empty($data['items_matrice'])
            ? "\nPoints de contrôle à couvrir: " . implode(', ', array_map(fn($i) => $i['point_controle'] ?? '', array_slice($data['items_matrice'], 0, 10)))
            : '';

        $prompt = <<<PROMPT
Expert audit interne. Génère le plan de collecte pour ce niveau.
{$missionCtx}
Procédure : {$procTitle}
Niveau : {$niveauCode} — {$niveauLib}{$matriceCtx}

Retourne UNIQUEMENT ce JSON sans markdown :
{
  "items": [
    {"num": "1", "information": "Document / donnée à collecter", "source": "Service / Interlocuteur", "methode_collecte": "Entretien / Observation / Documentation", "statut": null}
  ]
}
Génère 5 à 10 éléments à collecter.
PROMPT;

        return $this->callMistral($apiKey, $prompt, 1500);
    }

    // ── Appel API Mistral ─────────────────────────────────────────────────────
    private function callMistral(string $apiKey, string $prompt, int $maxTokens = 2000): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(90)->post('https://api.mistral.ai/v1/chat/completions', [
            'model'       => 'mistral-medium-latest',
            'max_tokens'  => $maxTokens,
            'temperature' => 0.4,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!$response->ok()) {
            throw new \Exception('Mistral API error: ' . $response->status());
        }

        $content = trim($response->json('choices.0.message.content') ?? '');
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $decoded = json_decode(trim($content), true);

        if (!is_array($decoded)) {
            throw new \Exception("Réponse IA invalide — JSON attendu");
        }

        return $decoded;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /level-doc-upload
    // Upload un document pour un niveau spécifique
    // ─────────────────────────────────────────────────────────────────────────
    public function levelDocUpload(Request $request)
    {
        try {
            $request->validate([
                'file'      => 'required|file|max:20480',
                'apt_id'    => 'required|integer',
                'level_id'  => 'nullable|integer',
                'proc_idx'  => 'nullable|integer',
                'level_idx' => 'nullable|integer',
            ]);

            $file   = $request->file('file');
            $aptId  = (int) $request->input('apt_id');
            $levelId = (int) ($request->input('level_id') ?? 0);

            // Vérifier que l'APT existe
            $apt = DB::table(self::TABLE)->where('id', $aptId)->first();
            if (!$apt) {
                return response()->json(['success' => false, 'error' => 'APT non trouvé'], 404);
            }

            $ext  = $file->getClientOriginalExtension();
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $path = $file->store("apt/{$aptId}/levels", 'public');

            $doc = [
                'apt_id'        => $aptId,
                'level_id'      => $levelId ?: null,
                'procedure_id'  => null,
                'name'          => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'url'           => Storage::url($path),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'size_label'    => $this->formatSize($file->getSize()),
                'extension'     => $ext,
                'type_document' => $request->input('type_document', ''),
                'ref_interne'   => $request->input('ref_interne', ''),
                'ai_analyzed'   => 0,
                'uploaded_by'   => Auth::id(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            // Si level_id fourni, insérer en base
            if ($levelId) {
                // Mettre à jour procedure_id
                $level = DB::table(self::TBL_LEVEL)->where('id', $levelId)->first();
                if ($level) {
                    $doc['procedure_id'] = $level->procedure_id;
                    $doc['level_id']     = $levelId;
                    $docId = DB::table(self::TBL_DOC)->insertGetId($doc);
                    $doc['id'] = $docId;
                }
            }

            return response()->json([
                'success'  => true,
                'document' => $doc,
                'message'  => 'Document uploadé',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[APT] levelDocUpload: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /analyze-document (analyse IA d'un document procédure)
    // ─────────────────────────────────────────────────────────────────────────
    public function analyzeDocument(Request $request)
    {
        // Timeout étendu pour l'appel Mistral (60s minimum)
        set_time_limit(120);
        ini_set('max_execution_time', '120');

        try {
            $request->validate([
                'document'        => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg,txt|max:20480',
                'mission_id'      => 'nullable|integer',
                'procedure_title' => 'nullable|string|max:255',
            ]);

            $file     = $request->file('document');
            $mimeType = $file->getMimeType();
            $origName = $file->getClientOriginalName();
            $path     = $file->store('apt/temp', 'public');

            $context = [
                'procedure_title' => $request->input('procedure_title', $origName),
                'mission_id'      => $request->input('mission_id'),
            ];
            if ($context['mission_id']) {
                $mission = $this->loadMission((int) $context['mission_id']);
                if ($mission) {
                    $context['mission_title'] = $mission->title ?? $mission->code;
                    $context['entity_name']   = $mission->entity_name ?? null;
                    $context['mission_code']  = $mission->code ?? null;
                }
            }

            $result = $this->aiService->analyzeDocument($path, $origName, $mimeType, $context);
            Storage::disk('public')->delete($path);

            // ── Normaliser les clés pour que le Vue reçoive les bons noms ────
            // Le service retourne : matrice_b, collecte_c, grille_d, synthese, bpmn_xml, flowchart
            // Le Vue attend     : matrice_b, collecte_c, grille_d, synthese, bpmn_xml (inchangés)
            // Le contrôleur expose aussi itemsMatrice, planCollecte, grilleEntretien (pour Inertia)
            // → ici on retourne la réponse JSON telle quelle, le Vue lit directement ces clés
            // dans analyzeDoc() côté Vue : r.matrice_b / r.collecte_c / r.grille_d

            $result['success']  = $result['success'] ?? true;

            // Alias de commodité pour compatibilité totale avec les deux flux
            // (ancien flux plat + nouveau flux procédures)
            $result['items_matrice']    = $result['matrice_b']   ?? [];
            $result['plan_collecte']    = $result['collecte_c']  ?? [];
            $result['grille_entretien'] = $result['grille_d']    ?? [];

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[APT] analyzeDocument: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /import-excel
    // ─────────────────────────────────────────────────────────────────────────
    public function importExcel(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

            $file    = $request->file('file');
            $section = $request->input('section', 'B');

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());

            $sheetNames = [
                'B' => 'Section B - Matrice Test',
                'C' => 'Section C - Plan Collecte',
                'D' => 'Section D - Grille Entretien',
            ];
            try {
                $sheet = $spreadsheet->getSheetByName($sheetNames[$section] ?? '') ?? $spreadsheet->getActiveSheet();
            } catch (\Exception) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $rows     = $sheet->toArray(null, true, true, true);
            $dataRows = array_filter($rows, fn($r, $k) => $k > 2, ARRAY_FILTER_USE_BOTH);
            $items    = [];
            $val      = fn($row, $col) => trim((string) ($row[$col] ?? ''));

            foreach ($dataRows as $row) {
                $allEmpty = collect($row)->every(fn($v) => empty(trim((string) $v)));
                if ($allEmpty) continue;

                if ($section === 'B') {
                    $num = $val($row, 'A'); $lib = $val($row, 'B');
                    if (empty($num) && !empty($lib) && (strtoupper($lib) === $lib || preg_match('/^[IVX]+\./', $lib))) {
                        $items[] = ['is_section' => true, 'section' => $lib]; continue;
                    }
                    if (empty($lib)) continue;
                    $items[] = ['num' => $num, 'is_section' => false, 'point_controle' => $lib,
                        'obj_controle' => $val($row, 'C'), 'obj_audit' => $val($row, 'D'),
                        'nature' => $this->mapNature($val($row, 'E')), 'controle_present' => $this->mapOuiNon($val($row, 'F')),
                        'preuve' => $val($row, 'G'), 'observation' => $val($row, 'H'), 'resultat' => $this->mapResultat($val($row, 'I'))];
                } elseif ($section === 'C') {
                    if (empty($val($row, 'B'))) continue;
                    $items[] = ['num' => $val($row, 'A'), 'information' => $val($row, 'B'),
                        'source' => $val($row, 'C'), 'methode_collecte' => $val($row, 'D'),
                        'statut' => $this->mapStatutCollecte($val($row, 'E'))];
                } elseif ($section === 'D') {
                    if (empty($val($row, 'B'))) continue;
                    if (empty($val($row, 'A')) && preg_match('/^Axe/i', $val($row, 'B'))) {
                        $items[] = ['is_axe' => true, 'axe' => $val($row, 'B')]; continue;
                    }
                    $items[] = ['num' => $val($row, 'A'), 'is_axe' => false, 'question' => $val($row, 'B'),
                        'obj_audit' => $val($row, 'C'), 'reponse' => $val($row, 'D')];
                }
            }

            return response()->json(['success' => true, 'items' => $items, 'section' => $section, 'count' => count($items)]);

        } catch (\Exception $e) {
            Log::error('[APT] importExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /{form}/ai-reformat
    // ─────────────────────────────────────────────────────────────────────────
    public function aiReformat(Request $request, $formId = null)
    {
        try {
            $validated = $request->validate([
                'section'    => 'required|in:B,C,D,E',
                'items'      => 'required|array',
                'context'    => 'nullable|string|max:2000',
                'mission_id' => 'nullable|integer',
            ]);

            $mission    = $validated['mission_id'] ? $this->loadMission((int) $validated['mission_id']) : null;
            $contextStr = $mission ? "Mission: {$mission->code} — {$mission->title}. " : '';
            if (!empty($validated['context'])) $contextStr .= $validated['context'];

            $sectionLabel = ['B' => 'Matrice de test', 'C' => 'Plan de collecte', 'D' => 'Grille entretien', 'E' => 'Synthèse F/F'][$validated['section']];
            $itemsJson    = json_encode($validated['items'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $prompt = "Tu es un expert en audit interne (IIA, COSO 2013).\nContexte: {$contextStr}\nSection: {$sectionLabel}\n\nDonnées:\n{$itemsJson}\n\nReformule professionnellement en conservant exactement la structure JSON. Retourne UNIQUEMENT le JSON reformulé, sans texte, sans markdown.";

            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) return response()->json(['success' => false, 'error' => 'IA non configurée'], 500);

            $data = $this->callMistral($apiKey, $prompt, 3000);

            // callMistral retourne le JSON décodé — si c'est un tableau direct ou un tableau enveloppé
            $items = is_array($data) && isset($data[0]) ? $data : ($data['items'] ?? $data);

            return response()->json(['success' => true, 'items' => $items, 'section' => $validated['section']]);

        } catch (\Exception $e) {
            Log::error('[APT] aiReformat: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Workflow
    // ─────────────────────────────────────────────────────────────────────────
    public function soumettre(Request $request, $formId)
    {
        try {
            $row = DB::table(self::TABLE)->where('id', $formId)->firstOrFail();
            if ($row->validation_status !== 'draft') {
                return response()->json(['success' => false, 'error' => 'Statut invalide'], 422);
            }
            DB::table(self::TABLE)->where('id', $formId)->update([
                'validation_status' => 'in_review', 'submitted_at' => now(), 'submitted_by' => Auth::id(), 'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'status' => 'in_review']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function valider(Request $request, $formId)
    {
        try {
            $action = $request->input('action', 'validated');
            $update = ['validation_status' => $action, 'updated_at' => now()];
            if ($action === 'validated') { $update['validated_at'] = now(); $update['validated_by'] = Auth::id(); }
            if ($note = $request->input('note')) $update['validation_note'] = $note;
            DB::table(self::TABLE)->where('id', $formId)->update($update);
            return response()->json(['success' => true, 'status' => $action]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // UPSERT — gère aussi les procédures, niveaux, documents
    // ══════════════════════════════════════════════════════════════════════════
    private function upsert(Request $request, $formId)
    {
        try {
            $validated = $request->validate([
                'mission_id'        => 'required|integer',
                'assignment_id'     => 'required|integer',
                'procedures'        => 'nullable|string',   // JSON des procédures sérialisées
                'synthese_ff'       => 'nullable|string',
                'niveau_conformite' => 'nullable|string|max:30',
                'niveau_risque'     => 'nullable|string|max:20',
                'fiabilite_controle'=> 'nullable|string|max:20',
                'suites'            => 'nullable|string|max:30',
                'commentaire_global'=> 'nullable|string',
                'fait_par'          => 'nullable|string|max:255',
                'revue_par'         => 'nullable|string|max:255',
                'approuve_par'      => 'nullable|string|max:255',
                'date_fait'         => 'nullable|date',
                'date_revue'        => 'nullable|date',
                'date_approbation'  => 'nullable|date',
                'deleted_files'     => 'nullable|string',
            ]);

            $missionId    = (int) $validated['mission_id'];
            $assignmentId = (int) $validated['assignment_id'];

            $existing = $formId
                ? DB::table(self::TABLE)->where('id', $formId)->first()
                : DB::table(self::TABLE)->where('assignment_id', $assignmentId)->first();

            // Fichiers globaux
            $savedFiles   = $existing ? (json_decode($existing->attached_files ?? '[]', true) ?: []) : [];
            $deletedPaths = json_decode($validated['deleted_files'] ?? '[]', true) ?: [];
            $savedFiles   = array_values(array_filter($savedFiles, fn($f) => !in_array($f['path'] ?? '', $deletedPaths)));

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("apt/{$missionId}", 'public');
                    $savedFiles[] = ['name' => $file->getClientOriginalName(), 'path' => $path,
                        'url' => Storage::url($path), 'size' => $file->getSize(), 'size_label' => $this->formatSize($file->getSize())];
                }
            }

            $payload = [
                'mission_id'        => $missionId,
                'assignment_id'     => $assignmentId,
                'synthese_ff'       => $validated['synthese_ff'] ?? null,
                'niveau_conformite' => $validated['niveau_conformite'] ?? null,
                'niveau_risque'     => $validated['niveau_risque'] ?? null,
                'fiabilite_controle'=> $validated['fiabilite_controle'] ?? null,
                'suites'            => $validated['suites'] ?? null,
                'commentaire_global'=> $validated['commentaire_global'] ?? null,
                'fait_par'          => $validated['fait_par'] ?? null,
                'revue_par'         => $validated['revue_par'] ?? null,
                'approuve_par'      => $validated['approuve_par'] ?? null,
                'date_fait'         => $validated['date_fait'] ?? null,
                'date_revue'        => $validated['date_revue'] ?? null,
                'date_approbation'  => $validated['date_approbation'] ?? null,
                'attached_files'    => json_encode($savedFiles),
                'updated_at'        => now(),
            ];

            if ($existing) {
                DB::table(self::TABLE)->where('id', $existing->id)->update($payload);
                $aptId = $existing->id;
            } else {
                $payload['code']              = $this->generateCode($missionId);
                $payload['intitule_proc']     = '';
                $payload['validation_status'] = 'draft';
                $payload['created_by']        = Auth::id();
                $payload['created_at']        = now();
                $aptId = DB::table(self::TABLE)->insertGetId($payload);
            }

            // ── Sauvegarder les procédures ────────────────────────────────────
            $proceduresJson = $validated['procedures'] ?? null;
            if ($proceduresJson) {
                $procs = json_decode($proceduresJson, true) ?: [];
                $this->upsertProcedures($aptId, $procs, $missionId, $request);
            }

            $record = DB::table(self::TABLE)->where('id', $aptId)->first();

            return response()->json(['success' => true, 'form' => $record]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[APT] upsert: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── Upsert procédures + niveaux + docs ────────────────────────────────────
    private function upsertProcedures(int $aptId, array $procs, int $missionId, Request $request): void
    {
        // Récupérer IDs existants
        $existingProcIds = DB::table(self::TBL_PROC)->where('apt_id', $aptId)->pluck('id')->toArray();
        $keptProcIds     = [];

        foreach ($procs as $pi => $proc) {
            $procData = [
                'apt_id'                 => $aptId,
                'ordre'                  => $pi + 1,
                'ref_procedure'          => $proc['ref_procedure'] ?? null,
                'intitule'               => $proc['intitule'] ?? '',
                'version_vigueur'        => $proc['version_vigueur'] ?? null,
                'service_dept'           => $proc['service_dept'] ?? null,
                'responsable_proc'       => $proc['responsable_proc'] ?? null,
                'date_entree_vigueur'    => $proc['date_entree_vigueur'] ?? null,
                'date_derniere_revision' => $proc['date_derniere_revision'] ?? null,
                'description'            => $proc['description'] ?? null,
                'population_totale'      => $proc['population_totale'] ?? null,
                'taille_echantillon'     => $proc['taille_echantillon'] ?? null,
                'methode_echantillonnage'=> $proc['methode_echantillonnage'] ?? null,
                'statut'                 => $proc['statut'] ?? 'en_cours',
                'bpmn_xml'               => $proc['bpmn_xml'] ?? null,
                'bpmn_synthese'          => is_array($proc['bpmn_synthese'] ?? null) ? json_encode($proc['bpmn_synthese']) : ($proc['bpmn_synthese'] ?? null),
                'niveau_conformite'      => $proc['niveau_conformite'] ?? null,
                'niveau_risque'          => $proc['niveau_risque'] ?? null,
                'fiabilite_controle'     => $proc['fiabilite_controle'] ?? null,
                'suites'                 => $proc['suites'] ?? null,
                'commentaire'            => $proc['commentaire'] ?? null,
                'updated_at'             => now(),
            ];

            if (!empty($proc['id'])) {
                DB::table(self::TBL_PROC)->where('id', $proc['id'])->update($procData);
                $procId = (int) $proc['id'];
            } else {
                $procData['created_at'] = now();
                $procId = DB::table(self::TBL_PROC)->insertGetId($procData);
            }

            $keptProcIds[] = $procId;

            // ── Niveaux de cette procédure ──────────────────────────────────
            $existingLvlIds = DB::table(self::TBL_LEVEL)->where('procedure_id', $procId)->pluck('id')->toArray();
            $keptLvlIds     = [];

            foreach ($proc['levels'] ?? [] as $li => $level) {
                $lvlData = [
                    'procedure_id'        => $procId,
                    'apt_id'              => $aptId,
                    'ordre'               => $li + 1,
                    'code_niveau'         => $level['code_niveau'] ?? "N{$li}",
                    'libelle_niveau'      => $level['libelle_niveau'] ?? $level['libelle'] ?? '',
                    'description_niveau'  => $level['description_niveau'] ?? $level['description'] ?? null,
                    'objectif_niveau'     => $level['objectif_niveau'] ?? $level['objectif'] ?? null,
                    'statut_niveau'       => $level['statut_niveau'] ?? 'non_commence',
                    'resultat_global'     => $level['resultat_global'] ?? null,
                    'observations'        => $level['observations'] ?? null,
                    'recommandations'     => $level['recommandations'] ?? null,
                    'items_matrice'       => is_array($level['items_matrice'] ?? null) ? json_encode($level['items_matrice']) : ($level['items_matrice'] ?? null),
                    'plan_collecte'       => is_array($level['plan_collecte'] ?? null) ? json_encode($level['plan_collecte']) : ($level['plan_collecte'] ?? null),
                    'grille_entretien'    => is_array($level['grille_entretien'] ?? null) ? json_encode($level['grille_entretien']) : ($level['grille_entretien'] ?? null),
                    'fait_par'            => $level['fait_par'] ?? null,
                    'revue_par'           => $level['revue_par'] ?? null,
                    'date_debut'          => $level['date_debut'] ?? null,
                    'date_fin'            => $level['date_fin'] ?? null,
                    'updated_at'          => now(),
                ];

                if (!empty($level['id'])) {
                    DB::table(self::TBL_LEVEL)->where('id', $level['id'])->update($lvlData);
                    $lvlId = (int) $level['id'];
                } else {
                    $lvlData['created_at'] = now();
                    $lvlId = DB::table(self::TBL_LEVEL)->insertGetId($lvlData);
                }
                $keptLvlIds[] = $lvlId;

                // ── Documents pending du niveau ────────────────────────────
                $pendingKey = "level_docs.{$pi}.{$li}";
                if ($request->hasFile("level_docs.{$pi}.{$li}")) {
                    $docFiles = $request->file("level_docs.{$pi}.{$li}");
                    $metas    = json_decode($request->input("level_doc_meta.{$pi}.{$li}", '[]'), true) ?: [];
                    foreach ((array) $docFiles as $di => $docFile) {
                        $path = $docFile->store("apt/{$aptId}/levels/{$lvlId}", 'public');
                        DB::table(self::TBL_DOC)->insert([
                            'level_id'      => $lvlId,
                            'procedure_id'  => $procId,
                            'apt_id'        => $aptId,
                            'name'          => basename($path),
                            'original_name' => $docFile->getClientOriginalName(),
                            'path'          => $path,
                            'url'           => Storage::url($path),
                            'mime_type'     => $docFile->getMimeType(),
                            'size'          => $docFile->getSize(),
                            'size_label'    => $this->formatSize($docFile->getSize()),
                            'extension'     => $docFile->getClientOriginalExtension(),
                            'type_document' => $metas[$di]['type_document'] ?? '',
                            'ref_interne'   => $metas[$di]['ref_interne'] ?? '',
                            'ai_analyzed'   => 0,
                            'uploaded_by'   => Auth::id(),
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }
            }

            // Supprimer les niveaux retirés
            $toDeleteLvl = array_diff($existingLvlIds, $keptLvlIds);
            if ($toDeleteLvl) DB::table(self::TBL_LEVEL)->whereIn('id', $toDeleteLvl)->delete();
        }

        // Supprimer les procédures retirées
        $toDeleteProc = array_diff($existingProcIds, $keptProcIds);
        if ($toDeleteProc) DB::table(self::TBL_PROC)->whereIn('id', $toDeleteProc)->delete();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS — sharedProps
    // ══════════════════════════════════════════════════════════════════════════
    private function sharedProps($mission, $assignment, $form): array
    {
        $missionId    = $mission?->id;
        $assignmentId = $assignment?->id ?? ($form?->assignment_id);

        [$auditorRole, $currentAuditor] = $this->resolveCurrentAuditor($missionId, $assignmentId);
        $auditeurs  = $this->loadAuditeurs($missionId, $assignment?->phase_id ?? null);
        $safeJson   = function ($v) {
            if (is_array($v)) return $v;
            if (!$v) return [];
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        };

        // Charger les procédures avec leurs niveaux et documents
        $proceduresData = $form ? $this->loadProceduresData($form->id) : [];

        // Templates actifs
        $templates = DB::table(self::TBL_TPL)->where('is_active', 1)->select('id','code','domaine','titre','description','niveaux_defaut')->get();

        // APT list
        $aptList = $missionId
            ? DB::table(self::TABLE)->where('mission_id', $missionId)
                ->select('id','code','validation_status','ref_procedure','intitule_proc')
                ->orderByDesc('created_at')->get()
            : collect();

        return [
            'mission'         => $mission,
            'assignment'      => $assignment,
            'form'            => $form,
            'aptList'         => $aptList,
            'proceduresData'  => $proceduresData,
            'syntheseFF'      => $safeJson($form?->synthese_ff),
            'flowchartData'   => $safeJson($form?->flowchart_data),
            'savedFiles'      => $safeJson($form?->attached_files),
            'auditeurs'       => $auditeurs,
            'auditorRole'     => $auditorRole,
            'currentAuditor'  => $currentAuditor,
            'templates'       => $templates,
            'missionId'       => $missionId,
            'assignmentId'    => $assignmentId,
            'backUrl'         => url()->previous() ?: url('/'),
        ];
    }

    // ── Charger procédures + niveaux + documents ──────────────────────────────
    private function loadProceduresData(int $aptId): array
    {
        try {
            $procs = DB::table(self::TBL_PROC)
                ->where('apt_id', $aptId)
                ->orderBy('ordre')
                ->get()
                ->toArray();

            foreach ($procs as &$proc) {
                $proc = (array) $proc;

                // Décoder bpmn_synthese si JSON
                if (!empty($proc['bpmn_synthese']) && is_string($proc['bpmn_synthese'])) {
                    $proc['bpmn_synthese'] = json_decode($proc['bpmn_synthese'], true);
                }

                $levels = DB::table(self::TBL_LEVEL)
                    ->where('procedure_id', $proc['id'])
                    ->orderBy('ordre')
                    ->get()
                    ->toArray();

                foreach ($levels as &$level) {
                    $level = (array) $level;
                    // Charger les documents du niveau
                    $level['documents'] = DB::table(self::TBL_DOC)
                        ->where('level_id', $level['id'])
                        ->orderBy('created_at')
                        ->get()
                        ->map(fn($d) => (array) $d)
                        ->toArray();
                }

                $proc['levels'] = $levels;
            }

            return $procs;

        } catch (\Exception $e) {
            Log::warning('[APT] loadProceduresData: ' . $e->getMessage());
            return [];
        }
    }

    // ── loadMission ───────────────────────────────────────────────────────────
    private function loadMission(int $missionId): ?object
    {
        try {
            $row = DB::table('missions as m')->leftJoin('entities as e', 'e.id', '=', 'm.entity_id')
                ->where('m.id', $missionId)->select(['m.id','m.code','m.title','m.status','e.name as entity_name'])->first();
            if ($row) return $row;
        } catch (\Exception) {}
        try {
            $row = DB::table('mission_programmation as mp')
                ->leftJoin('missions as m', 'mp.mission_id', '=', 'm.id')
                ->leftJoin('entities as e', 'm.entity_id', '=', 'e.id')
                ->where('mp.id', $missionId)
                ->select(['mp.id', DB::raw('COALESCE(mp.code_mission, m.code) as code'), DB::raw('COALESCE(mp.libelle, m.title) as title'), 'e.name as entity_name'])
                ->first();
            if ($row) return $row;
        } catch (\Exception) {}
        try { return DB::table('missions')->where('id', $missionId)->first(); } catch (\Exception) { return null; }
    }

    // ── loadAssignment ────────────────────────────────────────────────────────
    private function loadAssignment(int $assignmentId): ?object
    {
        try {
            return DB::table('assignments as a')
                ->leftJoin('mission_codephases as cp', 'cp.id', '=', 'a.phase_id')
                ->where('a.id', $assignmentId)->select(['a.*', 'cp.code as phase_code', 'cp.label as phase_label'])->first();
        } catch (\Exception) {
            try { return DB::table('assignments')->where('id', $assignmentId)->first(); } catch (\Exception) { return null; }
        }
    }

    // ── resolveCurrentAuditor ─────────────────────────────────────────────────
    private function resolveCurrentAuditor(?int $missionId, ?int $assignmentId): array
    {
        if (!Auth::check()) return [null, null];
        $userId = Auth::id();
        if ($missionId) {
            try {
                $row = DB::table('mission_phase_auditeurs as mpa')
                    ->join('auditors as a', 'a.id', '=', 'mpa.auditeur_id')
                    ->where('mpa.mission_id', $missionId)->where('a.user_id', $userId)
                    ->select('mpa.role','a.id as auditeur_id','a.last_name','a.first_name','a.audit_code','a.email')->first();
                if ($row) return [$row->role, ['id' => $row->auditeur_id, 'last_name' => $row->last_name, 'first_name' => $row->first_name, 'audit_code' => $row->audit_code, 'email' => $row->email]];
            } catch (\Exception) {}
        }
        try {
            $aud = DB::table('auditors')->where('user_id', $userId)->first();
            if ($aud) return [null, ['id' => $aud->id, 'last_name' => $aud->last_name ?? '', 'first_name' => $aud->first_name ?? '', 'audit_code' => $aud->audit_code ?? '', 'email' => $aud->email ?? '']];
        } catch (\Exception) {}
        $user = Auth::user();
        return [null, ['id' => null, 'last_name' => $user?->name ?? '', 'first_name' => '', 'audit_code' => '', 'email' => $user?->email ?? '']];
    }

    // ── loadAuditeurs ─────────────────────────────────────────────────────────
    private function loadAuditeurs(?int $missionId, ?int $phaseId): array
    {
        if (!$missionId) return [];
        try {
            $q = DB::table('mission_phase_auditeurs as mpa')
                ->join('auditors as a', 'a.id', '=', 'mpa.auditeur_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('mpa.mission_id', $missionId);
            if ($phaseId) $q->where(fn($q2) => $q2->where('mpa.phase_id', $phaseId)->orWhereNull('mpa.phase_id'));
            return $q->select(['mpa.id','mpa.auditeur_id','mpa.role','mpa.entites','mpa.date_affectation','a.audit_code','a.first_name','a.last_name','a.email','u.name as user_name'])
                ->orderByRaw("FIELD(mpa.role,'DM','CM','AS','AJ')")
                ->get()->map(fn($r) => ['id' => $r->id, 'auditeur_id' => $r->auditeur_id, 'role' => $r->role ?? '—',
                    'full_name' => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: ($r->user_name ?? "Auditeur #{$r->auditeur_id}"),
                    'audit_code' => $r->audit_code ?? '', 'email' => $r->email ?? '',
                    'entites' => json_decode($r->entites ?? '[]', true) ?? [], 'date_affectation' => $r->date_affectation ?? null])->toArray();
        } catch (\Exception $e) {
            Log::warning('[APT] loadAuditeurs: ' . $e->getMessage()); return [];
        }
    }

    // ── routeUrls ─────────────────────────────────────────────────────────────
    private function routeUrls($formId): array
    {
        $id = $formId ?? 0;
        return [
            'urlStore'           => route('auditor.ac.analyse-procedures.store'),
            'urlUpdate'          => $formId ? route('auditor.ac.analyse-procedures.update',    $formId) : null,
            'urlDestroy'         => $formId ? route('auditor.ac.analyse-procedures.destroy',   $formId) : null,
            'urlEdit'            => $formId ? route('auditor.ac.analyse-procedures.edit',      $formId) : null,
            'urlSoumettre'       => $formId ? route('auditor.ac.analyse-procedures.soumettre', $formId) : null,
            'urlValider'         => $formId ? route('auditor.ac.analyse-procedures.valider',   $formId) : null,
            'urlImportExcel'     => route('auditor.ac.analyse-procedures.import-excel'),
            'urlAiReformat'      => route('auditor.ac.analyse-procedures.ai-reformat', ['form' => $id]),
            'urlAnalyzeDocument' => route('auditor.ac.analyse-procedures.analyze-document'),
            'urlAiSuggest'       => route('auditor.ac.analyse-procedures.ai-suggest'),
            'urlLevelDocUpload'  => route('auditor.ac.analyse-procedures.level-doc-upload'),
            'urlIndex'           => route('audit.ac.preparation.analyse-procedures'),
        ];
    }

    // ── Mappers Excel ─────────────────────────────────────────────────────────
    private function mapNature(string $v): ?string { $v=mb_strtolower(trim($v)); if(str_contains($v,'fort')) return 'fort'; if(str_contains($v,'faibl')) return 'faible'; return null; }
    private function mapOuiNon(string $v): ?string { $v=mb_strtolower(trim($v)); if(in_array($v,['o','oui','yes','1','true','x'])) return 'oui'; if(in_array($v,['n','non','no','0','false'])) return 'non'; return null; }
    private function mapResultat(string $v): ?string { $v=mb_strtolower(trim($v)); if(str_contains($v,'nc')||str_contains($v,'non conf')) return 'nc'; if(str_contains($v,'pp')||str_contains($v,'part')) return 'pp'; if($v==='c'||str_contains($v,'conforme')) return 'c'; return null; }
    private function mapStatutCollecte(string $v): ?string { $v=mb_strtolower(trim($v)); if(str_contains($v,'collect')||str_contains($v,'à')) return 'a_collecter'; if(str_contains($v,'obten')) return 'obtenu'; if(str_contains($v,'n/a')||$v==='na') return 'na'; return null; }

    // ── generateCode ──────────────────────────────────────────────────────────
    private function generateCode(int $missionId): string
    {
        try {
            $mission = $this->loadMission($missionId);
            $slug    = $mission?->code ? strtoupper(preg_replace('/[^A-Z0-9]/i', '', $mission->code)) : 'M'.$missionId;
            $count   = DB::table(self::TABLE)->where('mission_id', $missionId)->count();
            return 'APT-' . substr($slug, 0, 8) . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        } catch (\Exception) { return 'APT-' . rand(1000, 9999); }
    }

    // ── formatSize ────────────────────────────────────────────────────────────
    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        return round($bytes / 1048576, 1) . ' Mo';
    }
}