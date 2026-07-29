<?php
// ════════════════════════════════════════════════════════════════════
// ApMethodologieController.php
// AP — Sous-phase « Méthodologie de vérification » (Planification).
//
// Maquette : 5 volets articulés autour des « lignes directrices d'enquête »
//   1) Lignes directrices d'enquête (objectif/question → ligne → résultat)
//   2) Critères d'audit (sous-critères) retenus pour la mission
//   3) Sources de l'evidence (par ligne directrice)
//   4) Méthode de collecte des données (par ligne directrice)
//   5) Méthodes d'analyse des données (par ligne directrice)
//
// ★ Paramètres AP (module Méthodologie) : critères, sous-critères de preuve,
//   nature de preuve, sources de preuve, méthodes de collecte et d'analyse.
// ★ Trame de la section 1 pré-remplie depuis les questions saisies dans le
//   « Champ d'action » (mission_phase_ap_champ_action) de la même mission.
// ★ Suggestion IA (Mistral) : propose lignes directrices, critères, sources,
//   méthodes de collecte et d'analyse à partir du contexte mission.
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor\Ap;

use App\Http\Controllers\Auditor\BasePhaseFormController;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApMethodologieController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ap_methodologie';
    protected string $formCode    = 'MV';
    protected string $codePrefix  = 'MV';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AP/MethodologieVerification';
    protected string $routeEdit   = 'audit.ap.preparation.methodologie-verification';

    protected array $jsonFields = ['lignes', 'criteres', 'sources', 'collecte', 'analyse'];
    protected array $validationRules = [];

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'synthese'  => $request->input('synthese') ?: null,
            'fait_par'  => $request->input('fait_par') ?: null,
            'revue_par' => $request->input('revue_par') ?: null,
            'lignes'    => $this->asJson($request->input('lignes')),
            'criteres'  => $this->asJson($request->input('criteres')),
            'sources'   => $this->asJson($request->input('sources')),
            'collecte'  => $this->asJson($request->input('collecte')),
            'analyse'   => $this->asJson($request->input('analyse')),
        ];
    }

    private function asJson($v): string
    {
        if (is_string($v)) return $v ?: '[]';
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }

    // ══════════════════════════════════════════════════════════════════

    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $payload = parent::buildPayload($missionId, $assignmentId, $auditor, $form);

        // ── Listes de choix (paramètres AP · module Méthodologie) ──
        $payload['refCriteres']       = $this->paramRows('ap_criteres', ['code', 'nature']);
        $payload['refSousCriteres']   = $this->paramRows('ap_sous_criteres_preuve', ['code', 'libelle']);
        $payload['refNaturePreuve']   = $this->paramRows('ap_nature_preuve', ['code', 'libelle']);
        $payload['refSourcesPreuve']  = $this->paramRows('ap_sources_preuve', ['code', 'libelle']);
        $payload['refMethodesCollecte'] = $this->paramRows('ap_methodes_collecte', ['code', 'libelle']);
        $payload['refMethodesAnalyse']  = $this->paramRows('ap_methodes_analyse', ['code', 'libelle']);

        // ── Trame section 1 : questions du Champ d'action de la même mission ──
        $payload['trameLignes'] = $this->lignesDepuisChampAction($missionId);

        $payload['record']      = $form;
        $payload['formUrl']     = url('/m/audit.core/ap/preparation/methodologie-verification');
        $payload['backUrl']     = url("/m/audit.core/auditor/missions/{$missionId}/phases");
        $payload['chatBaseUrl'] = url("/m/audit.core/missions/{$missionId}/chat/PREPARATION");
        $payload['aiEnabled']   = !empty(config('services.mistral.api_key'));

        $mission = $payload['mission'] ?? null;
        if ($mission && !empty($mission->audit_type_code)) {
            try {
                $at = DB::table('ddmparam.audit_types')
                    ->where('code', strtoupper($mission->audit_type_code))
                    ->first(['color', 'icon', 'label']);
                if ($at) {
                    $mission->audit_color      = $at->color;
                    $mission->audit_icon       = $at->icon;
                    $mission->audit_type_label = $at->label;
                }
            } catch (\Throwable $e) {
            }
        }

        return $payload;
    }

    /** Lecture défensive d'un référentiel ap_* (ne casse jamais la page). */
    private function paramRows(string $table, array $cols): array
    {
        try {
            return DB::table($table)->orderBy('sort')->get($cols)
                ->map(fn ($r) => (array) $r)->toArray();
        } catch (\Throwable $e) {
            Log::warning("[ApMethodologie] Lecture '{$table}' impossible : " . $e->getMessage());
            return [];
        }
    }

    /** Pré-remplit la section 1 avec les questions du Champ d'action. */
    private function lignesDepuisChampAction(int $missionId): array
    {
        try {
            $ca = DB::table('mission_phase_ap_champ_action')
                ->where('mission_id', $missionId)
                ->orderByDesc('id')
                ->value('questions');
            $questions = json_decode($ca ?? '[]', true);
            if (!is_array($questions)) return [];

            return array_values(array_filter(array_map(fn ($q) => [
                'objectif_question' => trim($q['question_principale'] ?? ''),
                'ligne_directrice'  => '',
                'resultat_attendu'  => '',
            ], $questions), fn ($r) => $r['objectif_question'] !== ''));
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // SAVE
    // ══════════════════════════════════════════════════════════════════

    public function save(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $missionId    = (int) $request->input('mission_id', 0);
        $assignmentId = (int) $request->input('assignment_id', 0);
        if (!$missionId || !$assignmentId) {
            return response()->json(['message' => 'Contexte de mission manquant.'], 422);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
        $assignment = DB::table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending') {
            return response()->json(['message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);
        }

        $role     = $this->getRole($missionId, $auditor->id);
        $existing = DB::table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing && !$this->canEdit($existing, $role)) {
            return response()->json(['message' => 'Fiche verrouillée (validée ou soumise).'], 422);
        }

        $data = $this->formData($request, $auditor);

        if ($existing) {
            DB::table($this->table)->where('id', $existing->id)->update($data + ['updated_at' => now()]);
            $id = $existing->id;
        } else {
            $id = DB::table($this->table)->insertGetId($data + [
                'assignment_id'     => $assignmentId,
                'mission_id'        => $missionId,
                'code'              => $this->genCode($missionId),
                'validation_status' => 'draft',
                'created_by'        => $auditor->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'draft_saved', null, 'draft');
        }

        return response()->json([
            'success' => true,
            'record'  => $this->hydrate(DB::table($this->table)->where('id', $id)->first()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // SUGGESTION IA
    // ══════════════════════════════════════════════════════════════════

    public function suggestIA(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $apiKey = config('services.mistral.api_key');
        if (empty($apiKey)) {
            return response()->json(['success' => false, 'error' => 'Service IA non configuré.'], 422);
        }

        $missionId = (int) $request->input('mission_id', 0);
        $mission   = $missionId ? $this->getMission($missionId) : null;

        $ctx  = "Mission : " . ($mission->libelle ?? '—') . ".\n";
        $ctx .= "Entité : " . ($mission->entity_name ?? '—') . ".\n";
        $ctx .= "Objectif : " . ($mission->objectif ?? '—') . ".\n";

        // Questions déjà définies dans le Champ d'action (pour ancrer les lignes)
        $questions = implode("\n", array_map(
            fn ($l) => '- ' . ($l['objectif_question'] ?? ''),
            $this->lignesDepuisChampAction($missionId)
        ));

        $collecte = implode(', ', array_map(fn ($m) => $m['libelle'] ?? '', $this->paramRows('ap_methodes_collecte', ['code', 'libelle'])));
        $analyse  = implode(', ', array_map(fn ($m) => $m['libelle'] ?? '', $this->paramRows('ap_methodes_analyse', ['code', 'libelle'])));
        $sources  = implode(', ', array_map(fn ($m) => $m['libelle'] ?? '', $this->paramRows('ap_sources_preuve', ['code', 'libelle'])));
        $natures  = implode(', ', array_map(fn ($m) => $m['libelle'] ?? '', $this->paramRows('ap_nature_preuve', ['code', 'libelle'])));

        $prompt = <<<PROMPT
Tu es un expert en audit de performance (ISSAI 3000).
{$ctx}
Questions d'audit déjà retenues :
{$questions}

Méthodes de collecte possibles : {$collecte}
Méthodes d'analyse possibles : {$analyse}
Sources de preuve possibles : {$sources}
Natures de preuve possibles : {$natures}

Propose une méthodologie de vérification cohérente. Numérote les lignes
directrices (1, 2, 3…) et référence-les dans les sources/collecte/analyse
via "ligne_num".

Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,
 "lignes":[{"objectif_question":"...","ligne_directrice":"...","resultat_attendu":"..."}],
 "criteres":[{"critere_principal":"Efficacité","sous_critere":"Attentes claires","source_critere":"...","libelle_retenu":"..."}],
 "sources":[{"ligne_num":1,"source_preuve":"L'entité elle-même","nature_preuve":"Faits (preuves chiffrées)","modalites_obtention":"..."}],
 "collecte":[{"ligne_num":1,"methode_collecte":"Collecte de documents","modalites_pratiques":"..."}],
 "analyse":[{"ligne_num":1,"methode_analyse":"Analyse et modélisation statistique","donnees_concernees":"...","resultat_analyse":"..."}]}
PROMPT;

        try {
            return response()->json(array_merge(['success' => true], $this->callMistral($apiKey, $prompt, 2000)));
        } catch (\Throwable $e) {
            Log::error('[ApMethodologie] suggestIA: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function callMistral(string $apiKey, string $prompt, int $maxTokens): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(90)->post('https://api.mistral.ai/v1/chat/completions', [
            'model'       => 'mistral-medium-latest',
            'max_tokens'  => $maxTokens,
            'temperature' => 0.1,
            'messages'    => [
                ['role' => 'system', 'content' => 'Tu réponds UNIQUEMENT avec du JSON valide. Aucun texte, aucun markdown.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ]);

        if (!$response->ok()) {
            throw new \Exception('Mistral HTTP ' . $response->status());
        }

        $content = trim($response->json('choices.0.message.content') ?? '');
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/m', '', $content);
        if (preg_match('/(\{.*\})/s', $content, $m)) {
            $content = $m[1];
        }
        $content = preg_replace_callback(
            '/"([^"]*)"/',
            fn ($mm) => '"' . preg_replace(['/\*\*([^*]+)\*\*/', '/\*([^*]+)\*/'], '$1', $mm[1]) . '"',
            $content
        );

        $decoded = json_decode(trim($content), true);
        if (!is_array($decoded)) {
            throw new \Exception('Réponse IA invalide — JSON attendu. Réessayez.');
        }
        return $decoded;
    }

    // ══════════════════════════════════════════════════════════════════
    // WORKFLOW
    // ══════════════════════════════════════════════════════════════════

    public function soumettreFiche(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Fiche introuvable'], 404);
        if (!$this->canAccess((int) $row->mission_id, (int) $row->assignment_id, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
        if ($row->validation_status !== 'draft') {
            return response()->json(['message' => 'Seul un brouillon peut être soumis.'], 422);
        }
        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        DB::table($this->table)->where('id', $formId)->update([
            'validation_status' => 'in_review', 'submitted_at' => now(),
            'submitted_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    public function validerFiche(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Fiche introuvable'], 404);
        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') {
            return response()->json(['message' => 'La fiche doit être soumise avant validation.'], 422);
        }
        $action = $request->input('action', 'validate');
        $note   = $request->input('note');
        if ($action === 'reject') {
            if (!$note) return response()->json(['message' => 'Motif du rejet obligatoire'], 422);
            DB::table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log((int) $row->assignment_id, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }
        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);
        DB::table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::table('mission_phase_assignments')->where('id', $row->assignment_id)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }
}
