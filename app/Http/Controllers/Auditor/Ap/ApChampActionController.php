<?php
// ════════════════════════════════════════════════════════════════════
// ApChampActionController.php
// AP — Sous-phase 1.4 « Champ d'action » (Planification de la mission).
//
// Maquette : 7 volets dynamiques
//   1) Approches d'audit (suivant les grands critères / les 4 « E »)
//   2) Objectifs d'audit (suivant les approches)
//   3) Question principale (+ sous-questions)
//   4) Étendue de l'audit — périmètre Qui / Quoi / Quand / Où
//   5) Limites de l'audit
//   6) Conclusions potentielles
//   7) Pertinence du mandat de l'auditeur
//
// ★ Alimentation depuis les PARAMÈTRES d'audit de performance :
//   - ap_criteres            → trame du volet 1 (une ligne par « E »)
//   - ap_approches_audit     → liste des approches sélectionnables
//   - ap_perimetre_dimensions→ trame du volet 4 (Qui/Quoi/Quand/Où + questions clés)
// ★ Suggestion IA (Mistral) : propose objectifs, questions, étendue, limites
//   et conclusions à partir du contexte mission.
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor\Ap;

use App\Http\Controllers\Auditor\BasePhaseFormController;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApChampActionController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ap_champ_action';
    protected string $formCode    = 'CA';
    protected string $codePrefix  = 'CA';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AP/ChampAction';
    protected string $routeEdit   = 'audit.ap.preparation.champ-action';

    protected array $jsonFields = [
        'approches', 'objectifs', 'questions', 'etendue',
        'limites', 'conclusions', 'pertinence',
    ];
    protected array $validationRules = [];

    // Questions type du volet 7 (pertinence du mandat) — pré-remplies.
    private const PERTINENCE_QUESTIONS = [
        "Le sujet entre-t-il dans le champ du mandat légal de la Chambre / de l'ISC ?",
        "Le sujet a-t-il déjà fait l'objet d'un audit de performance récent ?",
        "D'autres travaux (audit, évaluation) sont-ils en cours ou prévus sur ce sujet ?",
        "Existe-t-il un risque de conflit avec d'autres missions ou institutions ?",
    ];

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'synthese'    => $request->input('synthese') ?: null,
            'fait_par'    => $request->input('fait_par') ?: null,
            'revue_par'   => $request->input('revue_par') ?: null,
            'approches'   => $this->asJson($request->input('approches')),
            'objectifs'   => $this->asJson($request->input('objectifs')),
            'questions'   => $this->asJson($request->input('questions')),
            'etendue'     => $this->asJson($request->input('etendue')),
            'limites'     => $this->asJson($request->input('limites')),
            'conclusions' => $this->asJson($request->input('conclusions')),
            'pertinence'  => $this->asJson($request->input('pertinence')),
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

        // ── Paramètres d'audit de performance (trames + listes de choix) ──
        $payload['criteres']   = $this->paramRows('ap_criteres', ['code', 'nature', 'but', 'beneficiaire_code']);
        $payload['approches']  = $this->paramRows('ap_approches_audit', ['code', 'libelle', 'description']);
        $payload['dimensions'] = $this->paramRows('ap_perimetre_dimensions', ['code', 'libelle', 'questions_cles']);

        // Trames pré-remplies (le front ne les applique que si la fiche est vide)
        $payload['trameApproches'] = array_map(fn ($c) => [
            'critere_code'     => $c['code'] ?? '',
            'critere_nature'   => $c['nature'] ?? '',
            'approche_code'    => '',
            'approche_libelle' => '',
            'justification'    => '',
        ], $payload['criteres']);

        $payload['trameEtendue'] = array_map(fn ($d) => [
            'dimension_code'    => $d['code'] ?? '',
            'dimension_libelle' => $d['libelle'] ?? '',
            'questions_cles'    => $d['questions_cles'] ?? '',
            'contenu'           => '',
            'justification'     => '',
        ], $payload['dimensions']);

        $payload['tramePertinence'] = array_map(fn ($q) => [
            'question'   => $q,
            'reponse'    => '',
            'commentaire' => '',
        ], self::PERTINENCE_QUESTIONS);

        $payload['record']      = $form;
        $payload['formUrl']     = url('/m/audit.core/ap/preparation/champ-action');
        $payload['backUrl']     = url("/m/audit.core/auditor/missions/{$missionId}/phases");
        $payload['chatBaseUrl'] = url("/m/audit.core/missions/{$missionId}/chat/PREPARATION");
        $payload['aiEnabled']   = !empty(config('services.mistral.api_key'));

        // Couleur / libellé du type d'audit (bandeau)
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
            Log::warning("[ApChampAction] Lecture '{$table}' impossible : " . $e->getMessage());
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
    // SUGGESTION IA — propose le champ d'action depuis le contexte mission
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

        $ctx  = "Mission : " . ($mission->libelle ?? $request->input('mission_title', '—')) . ".\n";
        $ctx .= "Entité : " . ($mission->entity_name ?? '—') . ".\n";
        $ctx .= "Objectif de la mission : " . ($mission->objectif ?? $request->input('mission_objectif', '—')) . ".\n";
        $ctx .= "Lieux : " . ($mission->lieux ?? '—') . ".\n";
        if (!empty($mission->date_debut_fr)) {
            $ctx .= "Période : {$mission->date_debut_fr} → {$mission->date_fin_fr}.\n";
        }

        $criteres  = implode(', ', array_map(fn ($c) => ($c['nature'] ?? $c['code'] ?? ''), $this->paramRows('ap_criteres', ['code', 'nature'])));
        $approches = implode(', ', array_map(fn ($a) => ($a['libelle'] ?? '') . " ({$a['code']})", $this->paramRows('ap_approches_audit', ['code', 'libelle'])));

        $prompt = <<<PROMPT
Tu es un expert en audit de performance (ISSAI 300/3000).
{$ctx}
Critères d'évaluation disponibles (les « E ») : {$criteres}
Approches d'audit possibles : {$approches}

Propose un « champ d'action » (planification) complet et cohérent pour cette mission.

Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,
 "approches":[{"critere_nature":"Efficacité","approche_libelle":"Résultat","justification":"..."}],
 "objectifs":[{"objectif":"...","approche_libelle":"Résultat","critere_nature":"Efficacité","sous_criteres_source":"..."}],
 "questions":[{"question_principale":"...","sous_questions":"- ...\\n- ..."}],
 "etendue":[{"dimension_libelle":"Qui","contenu":"...","justification":"..."},{"dimension_libelle":"Quoi","contenu":"...","justification":"..."},{"dimension_libelle":"Quand","contenu":"...","justification":"..."},{"dimension_libelle":"Où","contenu":"...","justification":"..."}],
 "limites":[{"limite":"...","raison":"..."}],
 "conclusions":[{"conclusion":"...","lien_objectif":"Objectif n°1"}]}
PROMPT;

        try {
            return response()->json(array_merge(['success' => true], $this->callMistral($apiKey, $prompt, 1600)));
        } catch (\Throwable $e) {
            Log::error('[ApChampAction] suggestIA: ' . $e->getMessage());
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
