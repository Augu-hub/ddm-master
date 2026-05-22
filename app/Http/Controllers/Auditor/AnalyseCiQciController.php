<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Param\Auditor;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AnalyseCiQciController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_qci';
    protected string $formCode    = 'analyse-ci-qci';
    protected string $codePrefix  = 'QCI';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseCiQci';
    protected string $routeEdit   = 'auditor.ac.analyse-ci-qci.edit';

    protected array $validationRules = [
        'synthese'  => 'nullable|string',
        'fait_par'  => 'nullable|string|max:255',
        'revue_par' => 'nullable|string|max:255',
    ];

    // ══════════════════════════════════════════════════════════════════
    // formData
    // Vue submit() envoie :
    //   intitule_qci, fait_par, revue_par, date_fait, date_revue,
    //   synthese, items (JSON array), fichiers_joints (JSON)
    // ══════════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        $schema = DB::connection('tenant')->getSchemaBuilder();

        $data = [
            'questions'    => $request->input('questions',    '[]'),
            'reponses'     => $request->input('reponses',     '[]'),
            'observations' => $request->input('observations', '[]'),
            'note_globale' => $request->input('note_globale'),
            'synthese'     => $request->input('synthese'),
            'fait_par'     => $request->input('fait_par'),
            'revue_par'    => $request->input('revue_par'),
        ];

        // Colonnes ajoutées via migration — guard si pas encore présentes
        if ($schema->hasColumn($this->table, 'intitule_qci')) {
            $data['intitule_qci'] = $request->input('intitule_qci');
        }
        if ($schema->hasColumn($this->table, 'date_fait')) {
            $data['date_fait'] = $request->input('date_fait') ?: null;
        }
        if ($schema->hasColumn($this->table, 'date_revue')) {
            $data['date_revue'] = $request->input('date_revue') ?: null;
        }
        if ($schema->hasColumn($this->table, 'fichiers_joints')) {
            $data['fichiers_joints'] = $request->input('fichiers_joints', '[]');
        }

        return $data;
    }

    // ══════════════════════════════════════════════════════════════════
    // buildPayload
    // Props Vue attendues :
    //   mission, assignment, auditeurs, auditorRole, missionId, assignmentId
    //   form, qciList, riskCount, currentAuditor
    //   formUrl, backUrl, chatBaseUrl (corrigé), chatMessages
    // ══════════════════════════════════════════════════════════════════
    protected function buildPayload(
        int     $missionId,
        int     $assignmentId,
        Auditor $auditor,
        mixed   $form = null
    ): array {

        // ── mission_programmation → missions.id réel ──────────────────
        $missionRow    = DB::connection('tenant')
            ->table('mission_programmation')
            ->where('id', $missionId)
            ->select('id', 'mission_id')
            ->first();
        $realMissionId = $missionRow?->mission_id ?? $missionId;

        // ── Risques liés (compteur header) ────────────────────────────
        $riskCount = DB::connection('tenant')
            ->table('mission_risk')
            ->where('mission_id', $realMissionId)
            ->count();

        // ── Liste des QCI (sidebar gauche) ────────────────────────────
        // Guard sur les colonnes ajoutées par migration
        $schema      = DB::connection('tenant')->getSchemaBuilder();
        $hasIntitule = $schema->hasColumn($this->table, 'intitule_qci');

        $selectCols = ['id', 'code', 'validation_status', 'note_globale', 'fait_par', 'updated_at'];
        if ($hasIntitule) {
            $selectCols[] = 'intitule_qci';
        }

        $qciList = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->select($selectCols)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($row) use ($hasIntitule) {
                if (!$hasIntitule) {
                    $row->intitule_qci = null;
                }
                return $row;
            })
            ->toArray();

        // ── Chat ──────────────────────────────────────────────────────
        $role         = $this->getRole($missionId, $auditor->id);
        $chatMessages = $this->getChatMessages($assignmentId, $auditor->id, $role);

        // ── URL chat corrigée ─────────────────────────────────────────
        // Vraie route : /m/audit.core/missions/{realMissionId}/chat/{phase_type}
        // La Vue l'utilise comme : chatBaseUrl + '/PREPARATION' pour POST
        $chatBaseUrl = url("m/audit.core/missions/{$realMissionId}/chat");

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $form,
                'qciList'        => $qciList,
                'riskCount'      => $riskCount,
                'currentAuditor' => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'      => url('/m/audit.core/ac/preparation/analyse-ci-qci'),
                'backUrl'      => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
                'chatMessages' => $chatMessages,
                'chatBaseUrl'  => $chatBaseUrl,
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // getChatMessages — filtrage par rôle (pattern ReunionOuverture)
    // ══════════════════════════════════════════════════════════════════
    private function getChatMessages(int $assignmentId, int $auditorId, string $role): array
    {
        if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('mission_phase_chat')) {
            return [];
        }

        return DB::connection('tenant')
            ->table('mission_phase_chat as c')
            ->join('auditors as a', 'c.author_id', '=', 'a.id')
            ->where('c.assignment_id', $assignmentId)
            ->where('c.form_code', 'analyse-ci-qci')
            ->where(function ($q) use ($auditorId, $role) {
                if ($role === 'DM') { $q->whereRaw('1=1'); return; }
                $visible = match ($role) {
                    'CM'    => ['CM', 'AS', 'AJ'],
                    'AS'    => ['AS', 'AJ'],
                    default => ['AJ'],
                };
                $q->where('c.author_id', $auditorId)
                  ->orWhereIn('c.author_role', $visible);
            })
            ->select([
                'c.id', 'c.content', 'c.type', 'c.priority', 'c.is_pinned',
                'c.author_id', 'c.author_role', 'c.parent_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),''),COALESCE(LEFT(a.first_name,1),''))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id = {$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('c.created_at', 'asc')
            ->get()
            ->map(fn ($m) => tap($m, fn ($m) => $m->is_mine = (bool) $m->is_mine))
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════
    // store — JSON response (Vue : data.form.id, data.form.code)
    // Plusieurs QCI autorisés par assignment
    // ══════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int) $request->input('mission_id', 0);
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId) {
            return response()->json(['success' => false, 'message' => 'Contexte manquant.'], 422);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment || $assignment->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir.'], 422);
        }

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            'code'              => $this->genCode($missionId),
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();
        return response()->json(['success' => true, 'form' => $form, 'message' => 'QCI créé.']);
    }

    // ══════════════════════════════════════════════════════════════════
    // update — JSON response
    // ══════════════════════════════════════════════════════════════════
    public function update(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }
        if (!$this->canEdit($row, $role)) {
            return response()->json(['success' => false, 'message' => match ($row->validation_status) {
                'validated' => 'Formulaire validé — modification impossible.',
                'in_review' => 'Formulaire soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);
        }

        DB::connection('tenant')
            ->table($this->table)
            ->where('id', $formId)
            ->update(array_merge($this->formData($request, $auditor), ['updated_at' => now()]));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);

        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $updated, 'message' => 'QCI mis à jour.']);
    }

    // ══════════════════════════════════════════════════════════════════
    // soumettre
    // ══════════════════════════════════════════════════════════════════
    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
        if ($row->validation_status === 'validated') {
            return response()->json(['error' => 'Déjà validé'], 422);
        }
        if ($row->validation_status === 'in_review') {
            return response()->json(['error' => 'Déjà soumis'], 422);
        }

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');

        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    // ══════════════════════════════════════════════════════════════════
    // importExcel — POST analyse-ci-qci/import-excel
    // Lit le fichier QCI_Template.xlsx et retourne les lignes parsées
    //
    // Structure Excel attendue (même feuille "QCI") :
    //   Ligne 1-6 = en-têtes
    //   Ligne 7+  = données
    //   Col A = N° (vide = section/cat si col B contient titre en majuscules)
    //   Col B = Intitulé / Question
    //   Col C = O/N/SO (ignoré à l'import — l'auditeur remplit dans la Vue)
    //   Col D = Forces
    //   Col E = Faiblesses
    //   Col F = Objectif de contrôle
    // ══════════════════════════════════════════════════════════════════
    public function importExcel(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());

            // Priorité à la feuille "QCI", sinon feuille active
            $sheet = $spreadsheet->getSheetByName('QCI')
                  ?? $spreadsheet->getActiveSheet();

            $items  = [];
            $maxRow = $sheet->getHighestDataRow();

            // Données à partir de la ligne 7 (lignes 1-6 = titre + en-têtes)
            for ($r = 7; $r <= $maxRow; $r++) {
                $num      = trim((string) ($sheet->getCell("A{$r}")->getValue() ?? ''));
                $label    = trim((string) ($sheet->getCell("B{$r}")->getValue() ?? ''));
                $forces   = trim((string) ($sheet->getCell("D{$r}")->getValue() ?? ''));
                $faiblesses = trim((string) ($sheet->getCell("E{$r}")->getValue() ?? ''));
                $objectif = trim((string) ($sheet->getCell("F{$r}")->getValue() ?? ''));

                // Ignorer les lignes complètement vides
                if ($label === '' && $num === '') continue;

                // Détecter catégorie : col A vide ET label en MAJUSCULES ou commence par chiffre.
                $isSection = (
                    $num === '' &&
                    $label !== '' &&
                    (
                        strtoupper($label) === $label ||
                        preg_match('/^\d+\./', $label)
                    )
                ) || (
                    $num !== '' &&
                    !is_numeric($num) &&
                    $label !== ''
                );

                // Si col A contient un numéro de section (ex: "1." ou "2.")
                if (preg_match('/^\d+[\.\)]?\s*$/', $num) && $label !== '') {
                    $isSection = true;
                }

                $items[] = [
                    'type'       => $isSection ? 'cat' : 'item',
                    'label'      => $label,
                    'reponse'    => '',           // toujours vide à l'import
                    'forces'     => $forces,
                    'faiblesses' => $faiblesses,
                    'objectif'   => $objectif,
                ];
            }

            return response()->json([
                'success' => true,
                'items'   => $items,
                'count'   => count($items),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Erreur lecture Excel : ' . $e->getMessage(),
            ], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // valider / rejeter
    // ══════════════════════════════════════════════════════════════════
    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        }
        if ($row->validation_status !== 'in_review') {
            return response()->json(['error' => 'Formulaire non soumis'], 422);
        }

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $form)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') {
            return response()->json(['error' => 'Seul le DM valide définitivement'], 403);
        }

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->update(['validation_status' => 'validated', 'validated_at' => now(),
                      'validated_by' => $auditor->id, 'updated_at' => now()]);

        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }
}