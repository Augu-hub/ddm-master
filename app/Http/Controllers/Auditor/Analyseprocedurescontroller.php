<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * ANALYSE DES PROCÉDURES — AnalyseProceduresController
 * ════════════════════════════════════════════════════════════════════════
 *
 * Hérite de BasePhaseFormController (pattern AnalyseTachesController).
 *
 * Flux :
 *  1. DM/CM ouvre la page → crée des procédures → remplit l'Identification
 *     → affecte chaque procédure à un AS/AJ de la phase
 *  2. AS/AJ voit ses procédures assignées → peut modifier l'Identification
 *     (sauf intitulé) → remplit Entretien, Collecte, Analyse, génère Diagramme
 *
 * Tables utilisées :
 *  - mission_phase_apt           → formulaire principal (1 par assignment)
 *  - apt_procedures              → procédures de test
 *  - apt_test_levels             → données d'analyse JSON (N1 par procédure)
 *  - apt_procedure_assignments   → affectation procédure → auditeur
 *  - apt_level_documents         → documents joints
 *  - apt_procedure_templates     → templates IA
 */
class AnalyseProceduresController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_apt';
    protected string $formCode    = 'analyse-procedures';
    protected string $codePrefix  = 'APT';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseProcedures';
    protected string $routeEdit   = 'auditor.ac.preparation.analyse-procedures.edit';

    protected array $validationRules = [
        'fait_par'          => 'nullable|string|max:255',
        'revue_par'         => 'nullable|string|max:255',
        'date_fait'         => 'nullable|date',
        'date_revue'        => 'nullable|date',
        'commentaire_global'=> 'nullable|string',
    ];

    private const TBL_PROC   = 'apt_procedures';
    private const TBL_LEVEL  = 'apt_test_levels';
    private const TBL_DOC    = 'apt_level_documents';
    private const TBL_ASSIGN = 'apt_procedure_assignments';
    private const TBL_TPL    = 'apt_procedure_templates';

    // ══════════════════════════════════════════════════════════════
    // formData — UNIQUEMENT les colonnes qui existent dans mission_phase_apt
    // ══════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'fait_par'           => $request->input('fait_par'),
            'revue_par'          => $request->input('revue_par'),
            'date_fait'          => $request->input('date_fait')  ?: null,
            'date_revue'         => $request->input('date_revue') ?: null,
            'commentaire_global' => $request->input('commentaire_global'),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // buildPayload
    // ══════════════════════════════════════════════════════════════
    protected function buildPayload(
        int     $missionId,
        int     $assignmentId,
        Auditor $auditor,
        mixed   $form = null
    ): array {

        $role = $this->getRole($missionId, $auditor->id);

        // Auditeurs de la phase
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.audit_code', 'a.last_name', 'a.first_name',
                'mpaa.role_code', 'mpaa.parent_auditeur_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'), COALESCE(LEFT(a.first_name,1),'?'))) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->orderBy('a.last_name')
            ->get()
            ->map(fn($a) => [
                'id'                 => $a->id,
                'audit_code'         => $a->audit_code,
                'last_name'          => $a->last_name,
                'first_name'         => $a->first_name,
                'full_name'          => trim($a->full_name),
                'initials'           => $a->initials,
                'role_code'          => $a->role_code,
                'parent_auditeur_id' => $a->parent_auditeur_id,
                'role_label'         => match ($a->role_code) {
                    'DM' => 'Directeur de Mission', 'CM' => 'Chef de Mission',
                    'AS' => 'Auditeur Senior',      'AJ' => 'Auditeur Junior',
                    default => $a->role_code ?? '—',
                },
            ])
            ->toArray();

        // Procédures + affectations
        $proceduresData       = $form ? $this->loadProceduresData($form->id, $auditor->id, $role) : [];
        $procedureAssignments = $form ? $this->loadProcAssignments($form->id) : [];

        // Templates IA
        $templates = DB::connection('tenant')
            ->table(self::TBL_TPL)->where('is_active', 1)
            ->select('id', 'code', 'domaine', 'titre', 'description')->get()->toArray();

        // Liste APT
        $aptList = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'validation_status', 'fait_par', 'updated_at'])
            ->orderByDesc('created_at')->get()->toArray();

        $chatMessages = $this->getChatMessages($assignmentId, $auditor->id, $role);

        $formId = $form?->id;
        $base   = url('/m/audit.core/ac/preparation/analyse-procedures');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'                 => $form,
                'proceduresData'       => $proceduresData,
                'procedureAssignments' => $procedureAssignments,
                'phaseAuditeurs'       => $phaseAuditeurs,
                'templates'            => $templates,
                'aptList'              => $aptList,
                'currentAuditor'       => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'              => $base,
                'urlStore'             => route('auditor.ac.analyse-procedures.store'),
                'urlUpdate'            => $formId ? route('auditor.ac.analyse-procedures.update',    $formId) : null,
                'urlSoumettre'         => $formId ? route('auditor.ac.analyse-procedures.soumettre', $formId) : null,
                'urlValider'           => $formId ? route('auditor.ac.analyse-procedures.valider',   $formId) : null,
                'urlAnalyzeDocument'   => route('auditor.ac.analyse-procedures.analyze-document'),
                'urlAiSuggest'         => route('auditor.ac.analyse-procedures.ai-suggest'),
                'urlAssignProcedure'   => route('auditor.ac.analyse-procedures.assign-procedure'),
                'urlImportExcel'       => route('auditor.ac.analyse-procedures.import-excel'),
                'urlLevelDocUpload'    => route('auditor.ac.analyse-procedures.level-doc-upload'),
                'urlDeleteDoc'         => route('auditor.ac.analyse-procedures.delete-doc'),
                'urlIndex'             => route('audit.ac.preparation.analyse-procedures'),
                'backUrl'              => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
                'chatMessages'         => $chatMessages,
                'chatBaseUrl'          => url('/api/mission-phase-chat'),
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // store
    // ══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int) $request->input('mission_id', 0);
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId) {
            return response()->json(['success' => false, 'message' => 'Contexte mission manquant.'], 422);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);
        }

        $existing = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);

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
        $this->syncProcedures($request, $id, $assignmentId, $missionId, $auditor);

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();
        return response()->json(['success' => true, 'form' => $form, 'message' => 'Analyse créée.']);
    }

    // ══════════════════════════════════════════════════════════════
    // update
    // ══════════════════════════════════════════════════════════════
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

        // DM/CM : peut modifier les champs meta du formulaire
        // AS/AJ : seules les procédures qui lui sont assignées seront modifiées
        if (in_array($role, ['DM', 'CM'])) {
            DB::connection('tenant')->table($this->table)->where('id', $formId)
                ->update(array_merge($this->formData($request, $auditor), ['updated_at' => now()]));
        } else {
            // AS/AJ : seulement updated_at sur le formulaire principal
            DB::connection('tenant')->table($this->table)->where('id', $formId)
                ->update(['updated_at' => now()]);
        }

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $this->syncProcedures($request, $formId, $assignmentId, $missionId, $auditor);

        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $updated, 'message' => 'Analyse mise à jour.']);
    }

    // ══════════════════════════════════════════════════════════════
    // syncProcedures
    // Règle : AS/AJ peut modifier TOUT sur ses procédures assignées
    //         DM/CM peut modifier tout + créer/supprimer
    // ══════════════════════════════════════════════════════════════
    private function syncProcedures(Request $request, int $aptId, int $assignmentId, int $missionId, Auditor $auditor): void
    {
        $raw   = $request->input('procedures', '[]');
        $procs = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);
        if (empty($procs) || !is_array($procs)) return;

        $role   = $this->getRole($missionId, $auditor->id);
        $isDmCm = in_array($role, ['DM', 'CM']);

        $existingIds = DB::connection('tenant')
            ->table(self::TBL_PROC)->where('apt_id', $aptId)->pluck('id')->toArray();
        $keptIds = [];

        foreach ($procs as $pi => $proc) {
            $procId = !empty($proc['id']) ? (int) $proc['id'] : null;

            // AS/AJ : vérifier que la procédure lui est bien assignée
            if (!$isDmCm && $procId) {
                $isAssigned = DB::connection('tenant')
                    ->table(self::TBL_ASSIGN)
                    ->where('apt_id', $aptId)
                    ->where('procedure_id', $procId)
                    ->where('auditeur_id', $auditor->id)
                    ->exists();
                if (!$isAssigned) {
                    if ($procId) $keptIds[] = $procId;
                    continue;
                }
            }

            // ── Données que DM/CM gère (identification) ──
            $baseData = [
                'apt_id'                  => $aptId,
                'ordre'                   => $pi + 1,
                'statut'                  => $proc['statut'] ?? 'en_cours',
                'updated_at'              => now(),
            ];

            // ── Données que AS/AJ peut remplir (analyse) ──
            $analysisData = [
                'niveau_conformite'       => $proc['niveau_conformite']  ?? null,
                'niveau_risque'           => $proc['niveau_risque']       ?? null,
                'fiabilite_controle'      => $proc['fiabilite_controle']  ?? null,
                'suites'                  => $proc['suites']              ?? null,
                'commentaire'             => $proc['commentaire']         ?? null,
                'forces'                  => $this->encodeJson($proc['forces']    ?? null),
                'faiblesses'              => $this->encodeJson($proc['faiblesses'] ?? null),
                'bpmn_xml'                => $proc['bpmn_xml']            ?? null,
                'bpmn_synthese'           => $this->encodeJson($proc['bpmn_synthese'] ?? null),
            ];

            // ── Données identification (DM/CM uniquement) ──
            $idData = [];
            if ($isDmCm) {
                $idData = [
                    'ref_procedure'           => $proc['ref_procedure']          ?? null,
                    'intitule'                => $proc['intitule']               ?? '',
                    'version_vigueur'         => $proc['version_vigueur']        ?? null,
                    'service_dept'            => $proc['service_dept']           ?? null,
                    'responsable_proc'        => $proc['responsable_proc']       ?? null,
                    'date_entree_vigueur'     => $proc['date_entree_vigueur']    ?? null,
                    'date_derniere_revision'  => $proc['date_derniere_revision'] ?? null,
                    'description'             => $proc['description']            ?? null,
                    'population_totale'       => $proc['population_totale']      ?? null,
                    'taille_echantillon'      => $proc['taille_echantillon']     ?? null,
                    'methode_echantillonnage' => $proc['methode_echantillonnage'] ?? null,
                ];
            }

            $procData = array_merge($baseData, $analysisData, $idData);

            if ($procId) {
                DB::connection('tenant')->table(self::TBL_PROC)
                    ->where('id', $procId)
                    ->update($procData);
            } else {
                // Nouvelle procédure : DM/CM seulement
                if (!$isDmCm) continue;
                $procData['ref_procedure']  = $proc['ref_procedure']  ?? null;
                $procData['intitule']       = $proc['intitule']        ?? '';
                $procData['service_dept']   = $proc['service_dept']    ?? null;
                $procData['description']    = $proc['description']     ?? null;
                $procData['created_at']     = now();
                $procId = DB::connection('tenant')->table(self::TBL_PROC)->insertGetId($procData);
                $this->autoAssignFirstAS($aptId, $procId, $assignmentId, $auditor->id);
            }

            $keptIds[] = $procId;
            $this->syncLevel($aptId, $procId, $proc);
        }

        // Supprimer les procédures retirées (DM/CM seulement)
        if ($isDmCm) {
            $toDelete = array_diff($existingIds, $keptIds);
            if (!empty($toDelete)) {
                DB::connection('tenant')->table(self::TBL_LEVEL)->whereIn('procedure_id', $toDelete)->delete();
                DB::connection('tenant')->table(self::TBL_ASSIGN)->whereIn('procedure_id', $toDelete)->delete();
                DB::connection('tenant')->table(self::TBL_DOC)->whereIn('procedure_id', $toDelete)->delete();
                DB::connection('tenant')->table(self::TBL_PROC)->whereIn('id', $toDelete)->delete();
            }
        }
    }

    private function encodeJson(mixed $v): ?string
    {
        if ($v === null) return null;
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        if (is_string($v)) return $v; // déjà JSON
        return null;
    }

    private function syncLevel(int $aptId, int $procId, array $proc): void
    {
        $levels = $proc['levels'] ?? [];
        $level  = !empty($levels) ? $levels[0] : [];

        $lvlData = [
            'apt_id'           => $aptId,
            'libelle_niveau'   => $proc['intitule'] ?? 'Test principal',
            'statut_niveau'    => $proc['statut']   ?? 'en_cours',
            'items_matrice'    => $this->encodeJson($level['items_matrice']    ?? null),
            'plan_collecte'    => $this->encodeJson($level['plan_collecte']    ?? null),
            'grille_entretien' => $this->encodeJson($level['grille_entretien'] ?? null),
            'updated_at'       => now(),
        ];

        $existing = DB::connection('tenant')->table(self::TBL_LEVEL)
            ->where('procedure_id', $procId)->where('code_niveau', 'N1')->first();

        if ($existing) {
            DB::connection('tenant')->table(self::TBL_LEVEL)
                ->where('id', $existing->id)->update($lvlData);
        } else {
            DB::connection('tenant')->table(self::TBL_LEVEL)->insert(array_merge($lvlData, [
                'procedure_id' => $procId,
                'ordre'        => 1,
                'code_niveau'  => 'N1',
                'created_at'   => now(),
            ]));
        }
    }

    private function autoAssignFirstAS(int $aptId, int $procId, int $assignmentId, int $dmCmAuditorId): void
    {
        try {
            $firstAS = DB::connection('tenant')
                ->table('mission_phase_assignment_auditeurs as mpaa')
                ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
                ->where('mpaa.assignment_id', $assignmentId)
                ->whereIn('mpaa.role_code', ['AS', 'AJ'])
                ->orderByRaw("FIELD(mpaa.role_code,'AS','AJ')")
                ->select('a.id as auditeur_id')->first();

            if (!$firstAS) return;

            $exists = DB::connection('tenant')->table(self::TBL_ASSIGN)
                ->where('apt_id', $aptId)->where('procedure_id', $procId)->exists();
            if ($exists) return;

            DB::connection('tenant')->table(self::TBL_ASSIGN)->insert([
                'apt_id'           => $aptId,
                'procedure_id'     => $procId,
                'auditeur_id'      => $firstAS->auditeur_id,
                'affecte_par'      => $dmCmAuditorId,
                'date_affectation' => now()->toDateString(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('[APT] autoAssignFirstAS: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // POST /assign-procedure
    // ══════════════════════════════════════════════════════════════
    public function assignProcedure(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $aptId        = (int) $request->input('apt_id', 0);
        $procedureId  = (int) $request->input('procedure_id', 0);
        $auditeurId   = $request->input('auditeur_id');
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$aptId || !$procedureId || !$assignmentId) {
            return response()->json(['error' => 'Paramètres manquants'], 422);
        }

        $apt = DB::connection('tenant')->table($this->table)
            ->where('id', $aptId)->select('id', 'mission_id', 'assignment_id')->first();
        if (!$apt) return response()->json(['error' => 'APT introuvable'], 404);

        $role = $this->getRole((int) $apt->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent affecter'], 403);
        }

        if ($auditeurId) {
            $inPhase = DB::connection('tenant')
                ->table('mission_phase_assignment_auditeurs')
                ->where('assignment_id', $assignmentId)
                ->where('auditeur_id', (int) $auditeurId)->exists();
            if (!$inPhase) return response()->json(['error' => "Auditeur non affecté à cette phase"], 422);
        }

        DB::connection('tenant')->table(self::TBL_ASSIGN)->updateOrInsert(
            ['apt_id' => $aptId, 'procedure_id' => $procedureId],
            [
                'auditeur_id'      => $auditeurId ? (int) $auditeurId : null,
                'affecte_par'      => $auditor->id,
                'date_affectation' => now()->toDateString(),
                'updated_at'       => now(),
                'created_at'       => now(),
            ]
        );

        return response()->json([
            'success'      => true,
            'procedure_id' => $procedureId,
            'auditeur_id'  => $auditeurId ? (int) $auditeurId : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // POST /analyze-document
    // ══════════════════════════════════════════════════════════════
    public function analyzeDocument(Request $request)
    {
        set_time_limit(120);
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $request->validate([
                'document'   => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg,txt|max:20480',
                'mission_id' => 'nullable|integer',
            ]);

            $file     = $request->file('document');
            $origName = $file->getClientOriginalName();
            $path     = $file->store('apt/temp', 'public');

            $context = ['procedure_title' => $origName, 'mission_id' => $request->input('mission_id')];
            if ($context['mission_id']) {
                $mission = DB::connection('tenant')
                    ->table('mission_programmation as mp')
                    ->leftJoin('missions as m', 'mp.mission_id', '=', 'm.id')
                    ->leftJoin('entities as e', 'm.entity_id', '=', 'e.id')
                    ->where('mp.id', (int) $context['mission_id'])
                    ->select([DB::raw('COALESCE(mp.code_mission, m.code) as code'), DB::raw('COALESCE(mp.libelle, m.title) as title'), 'e.name as entity_name'])
                    ->first();
                if ($mission) {
                    $context['mission_title'] = $mission->title ?? $mission->code;
                    $context['entity_name']   = $mission->entity_name ?? null;
                }
            }

            $result = app(\App\Services\ProcedureDocumentAnalysisService::class)
                ->analyzeDocument($path, $origName, $file->getMimeType(), $context);
            Storage::disk('public')->delete($path);

            $result['success']          = $result['success'] ?? true;
            $result['items_matrice']    = $result['matrice_b']      ?? $result['items_matrice']    ?? [];
            $result['plan_collecte']    = $result['collecte_c']     ?? $result['plan_collecte']    ?? [];
            $result['grille_entretien'] = $result['grille_d']       ?? $result['grille_entretien'] ?? [];

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[APT] analyzeDocument: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // POST /ai-suggest  (procedure_complete | matrice_niveau | collecte_niveau | bpmn_procedure)
    // ══════════════════════════════════════════════════════════════
    public function aiSuggest(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $validated = $request->validate([
                'type'                   => 'required|in:procedure_complete,matrice_niveau,collecte_niveau,bpmn_procedure,forces_faiblesses',
                'prompt'                 => 'nullable|string|max:3000',
                'procedure_title'        => 'nullable|string|max:500',
                'procedure_description'  => 'nullable|string|max:2000',
                'niveau_code'            => 'nullable|string|max:30',
                'niveau_libelle'         => 'nullable|string|max:200',
                'items_matrice'          => 'nullable|array',
                'mission_id'             => 'nullable|integer',
                'mission_title'          => 'nullable|string|max:500',
                'entity_name'            => 'nullable|string|max:300',
            ]);

            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) return response()->json(['success' => false, 'error' => 'Service IA non configuré'], 500);

            $missionCtx = '';
            if (!empty($validated['mission_title'])) {
                $missionCtx = "Mission : {$validated['mission_title']}. Entité : {$validated['entity_name']}.";
            }

            $result = match ($validated['type']) {
                'procedure_complete' => $this->suggestProcedureComplete($apiKey, $validated, $missionCtx),
                'matrice_niveau'     => $this->suggestMatriceNiveau($apiKey, $validated, $missionCtx),
                'collecte_niveau'    => $this->suggestCollecteNiveau($apiKey, $validated, $missionCtx),
                'bpmn_procedure'     => $this->suggestBpmnProcedure($apiKey, $validated, $missionCtx),
                'forces_faiblesses'  => $this->suggestForcesFaiblesses($apiKey, $validated, $missionCtx),
            };

            return response()->json(array_merge(['success' => true], $result));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[APT] aiSuggest: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function suggestProcedureComplete(string $k, array $d, string $ctx): array
    {
        $prompt = "Expert audit interne (IIA, COSO 2013). Génère une procédure de test complète.\n{$ctx}\nDemande : {$d['prompt']}\n\nRetourne UNIQUEMENT ce JSON sans markdown :\n{\"intitule\":\"Titre\",\"ref_procedure\":\"PROC-XXX\",\"service_dept\":\"Direction\",\"responsable_proc\":\"Resp.\",\"description\":\"Description succincte\",\"levels\":[{\"code_niveau\":\"N1\",\"libelle\":\"Test principal\",\"items_matrice\":[{\"num\":\"1\",\"is_section\":false,\"point_controle\":\"Vérifier que...\",\"nature\":\"fort\",\"controle_present\":null,\"preuve\":\"\",\"observation\":\"\",\"resultat\":null}],\"plan_collecte\":[{\"num\":\"1\",\"information\":\"Doc à collecter\",\"source\":\"Service\",\"methode_collecte\":\"Entretien\",\"statut\":null}],\"grille_entretien\":[{\"num\":\"Q1\",\"is_axe\":false,\"question\":\"Question posée…\",\"obj_audit\":\"Objectif\",\"reponse\":\"\"}]}]}\nGénère 6 à 10 points, 5 collectes, 5 questions.";
        return $this->callMistral($k, $prompt, 3500);
    }

    private function suggestMatriceNiveau(string $k, array $d, string $ctx): array
    {
        $prompt = "Expert audit interne. Matrice de test.\n{$ctx}\nProcédure : {$d['procedure_title']}\n\nJSON :\n{\"items\":[{\"num\":\"1\",\"is_section\":false,\"point_controle\":\"Vérifier que...\",\"nature\":\"fort\",\"controle_present\":null,\"preuve\":\"\",\"observation\":\"\",\"resultat\":null}]}\n6 à 10 points.";
        return $this->callMistral($k, $prompt, 2000);
    }

    private function suggestCollecteNiveau(string $k, array $d, string $ctx): array
    {
        $pts = '';
        if (!empty($d['items_matrice']) && is_array($d['items_matrice'])) {
            $pts = implode(', ', array_map(fn($r) => $r['point_controle'] ?? '', array_slice(
                array_filter(array_values($d['items_matrice']), fn($r) => empty($r['is_section'])), 0, 8
            )));
        }
        $extra  = $d['prompt'] ?? '';
        $prompt = "Expert audit. Plan collecte / grille entretien.\n{$ctx}\nProcédure : {$d['procedure_title']}\nPoints : {$pts}\n{$extra}\n\nJSON :\n{\"items\":[{\"num\":\"1\",\"information\":\"Donnée à collecter\",\"source\":\"Service\",\"methode_collecte\":\"Entretien\",\"statut\":null}]}\n5 à 8 éléments.";
        return $this->callMistral($k, $prompt, 1500);
    }

    private function suggestBpmnProcedure(string $k, array $d, string $ctx): array
    {
        $title  = $d['procedure_title'] ?? 'Procédure';
        $desc   = $d['procedure_description'] ?? '';
        $pts    = '';
        if (!empty($d['items_matrice']) && is_array($d['items_matrice'])) {
            $rows = array_filter(array_values($d['items_matrice']), fn($r) => empty($r['is_section']));
            $pts  = implode(', ', array_map(fn($r) => $r['point_controle'] ?? '', array_slice($rows, 0, 10)));
        }
        $prompt = "Tu es expert BPMN 2.0 et audit interne. Génère un diagramme BPMN XML 2.0 valide.\n{$ctx}\nProcédure : {$title}\nDescription : {$desc}\nPoints de contrôle : {$pts}\n\nRetourne UNIQUEMENT ce JSON sans markdown :\n{\"success\":true,\"bpmn_xml\":\"<?xml version=\\\"1.0\\\" encoding=\\\"UTF-8\\\"?><definitions xmlns=\\\"http://www.omg.org/spec/BPMN/20100524/MODEL\\\" xmlns:xsi=\\\"http://www.w3.org/2001/XMLSchema-instance\\\" targetNamespace=\\\"http://bpmn.io/schema/bpmn\\\"><process id=\\\"Process_1\\\" isExecutable=\\\"true\\\">...</process></definitions>\",\"bpmn_synthese\":{\"titre\":\"Titre processus\",\"description\":\"Description\",\"risques_principaux\":[\"Risque 1\",\"Risque 2\"]}}\nInclure : startEvent, 5+ userTask, 2+ exclusiveGateway, endEvent.";
        return $this->callMistral($k, $prompt, 3000);
    }

    private function suggestForcesFaiblesses(string $k, array $d, string $ctx): array
    {
        $title = $d['procedure_title'] ?? 'Procédure';
        $desc  = $d['procedure_description'] ?? '';
        $pts   = '';
        if (!empty($d['points_controle']) && is_array($d['points_controle'])) {
            $pts = implode("\n", array_map(function($p) {
                $res = $p['resultat'] ?? '?';
                $nat = $p['nature']   ?? '';
                return "- {$p['point']} [Nature:{$nat}|Résultat:{$res}|Obs:{$p['obs']}]";
            }, array_slice($d['points_controle'], 0, 15)));
        }

        $prompt = <<<PROMPT
Tu es expert en audit interne (IIA, COSO 2013).
{$ctx}
Analyse la procédure "{$title}" — {$desc}
Points de contrôle évalués :
{$pts}

Identifie les forces (contrôles conformes, dispositifs efficaces) et les faiblesses (non-conformités, risques, lacunes).

Retourne UNIQUEMENT ce JSON sans markdown :
{
  "success": true,
  "forces": [
    "Contrôle de premier niveau opérationnel et documenté",
    "Séparation des tâches correctement appliquée"
  ],
  "faiblesses": [
    "Procédure non mise à jour depuis plus de 12 mois",
    "Absence de traçabilité sur les dérogations accordées"
  ]
}
Génère 2 à 5 forces et 2 à 5 faiblesses, formulées en phrases courtes et précises.
PROMPT;

        return $this->callMistral($k, $prompt, 1000);
    }

    private function callMistral(string $apiKey, string $prompt, int $maxTokens): array
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

        if (!$response->ok()) throw new \Exception('Mistral error: ' . $response->status());

        $content = trim($response->json('choices.0.message.content') ?? '');
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/```\s*$/',            '', $content);
        $decoded = json_decode(trim($content), true);
        if (!is_array($decoded)) throw new \Exception('Réponse IA invalide');
        return $decoded;
    }

    // ══════════════════════════════════════════════════════════════
    // POST /import-excel
    // ══════════════════════════════════════════════════════════════
    public function importExcel(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);
            $file    = $request->file('file');
            $section = $request->input('section', 'B');

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
            $sheetMap    = ['B' => 'Section B - Matrice Test', 'C' => 'Section C - Plan Collecte', 'D' => 'Section D - Grille Entretien'];

            try { $sheet = $spreadsheet->getSheetByName($sheetMap[$section] ?? '') ?? $spreadsheet->getActiveSheet(); }
            catch (\Exception) { $sheet = $spreadsheet->getActiveSheet(); }

            $rows     = $sheet->toArray(null, true, true, true);
            $dataRows = array_filter($rows, fn($r, $k) => $k > 2, ARRAY_FILTER_USE_BOTH);
            $items    = [];
            $val      = fn($row, $col) => trim((string) ($row[$col] ?? ''));

            foreach ($dataRows as $row) {
                if (collect($row)->every(fn($v) => empty(trim((string) $v)))) continue;
                if ($section === 'B') {
                    $num = $val($row, 'A'); $lib = $val($row, 'B');
                    if (empty($num) && !empty($lib) && (strtoupper($lib) === $lib || preg_match('/^[IVX]+\./', $lib))) {
                        $items[] = ['is_section' => true, 'section' => $lib]; continue;
                    }
                    if (empty($lib)) continue;
                    $items[] = ['num' => $num, 'is_section' => false, 'point_controle' => $lib,
                        'nature' => $this->mapNature($val($row,'E')), 'controle_present' => $this->mapOuiNon($val($row,'F')),
                        'preuve' => $val($row,'G'), 'observation' => $val($row,'H'), 'resultat' => $this->mapResultat($val($row,'I'))];
                } elseif ($section === 'C') {
                    if (empty($val($row,'B'))) continue;
                    $items[] = ['num' => $val($row,'A'), 'information' => $val($row,'B'),
                        'source' => $val($row,'C'), 'methode_collecte' => $val($row,'D'),
                        'statut' => $this->mapStatutCollecte($val($row,'E'))];
                } elseif ($section === 'D') {
                    if (empty($val($row,'B'))) continue;
                    if (empty($val($row,'A')) && preg_match('/^Axe/i', $val($row,'B'))) {
                        $items[] = ['is_axe' => true, 'axe' => $val($row,'B')]; continue;
                    }
                    $items[] = ['num' => $val($row,'A'), 'is_axe' => false, 'question' => $val($row,'B'),
                        'obj_audit' => $val($row,'C'), 'reponse' => $val($row,'D')];
                }
            }

            return response()->json(['success' => true, 'items' => $items, 'section' => $section, 'count' => count($items)]);

        } catch (\Exception $e) {
            Log::error('[APT] importExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // POST /level-doc-upload
    // ══════════════════════════════════════════════════════════════
    public function levelDocUpload(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $request->validate([
                'file'         => 'required|file|max:20480',
                'apt_id'       => 'required|integer',
                'procedure_id' => 'nullable|integer',
            ]);

            $file        = $request->file('file');
            $aptId       = (int) $request->input('apt_id');
            $procedureId = (int) ($request->input('procedure_id') ?? 0);

            $apt = DB::connection('tenant')->table($this->table)->where('id', $aptId)->first();
            if (!$apt) return response()->json(['success' => false, 'error' => 'APT non trouvé'], 404);

            $path = $file->store("apt/{$aptId}/docs", 'public');

            $docData = [
                'apt_id'        => $aptId,
                'procedure_id'  => $procedureId ?: null,
                'name'          => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'url'           => Storage::disk('public')->url($path),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'size_label'    => $this->formatSize($file->getSize()),
                'extension'     => $file->getClientOriginalExtension(),
                'ai_analyzed'   => 0,
                'uploaded_by'   => $auditor->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $docId = DB::connection('tenant')->table(self::TBL_DOC)->insertGetId($docData);
            $docData['id'] = $docId;

            return response()->json(['success' => true, 'document' => $docData]);

        } catch (\Exception $e) {
            Log::error('[APT] levelDocUpload: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // soumettre
    // ══════════════════════════════════════════════════════════════
    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['error' => 'Accès refusé'], 403);
        if ($row->validation_status === 'validated')
            return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review')
            return response()->json(['error' => 'Déjà soumis'], 422);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');

        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    // ══════════════════════════════════════════════════════════════
    // valider
    // ══════════════════════════════════════════════════════════════
    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM']))
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review')
            return response()->json(['error' => 'Formulaire non soumis'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $form)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM valide définitivement'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);

        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ══════════════════════════════════════════════════════════════
    // getRole
    // ══════════════════════════════════════════════════════════════
    protected function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->first();
        return $row?->role_code ?? 'AJ';
    }

    // ══════════════════════════════════════════════════════════════
    // getChatMessages
    // ══════════════════════════════════════════════════════════════
    private function getChatMessages(int $assignmentId, int $auditorId, string $role): array
    {
        if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('mission_phase_chat')) return [];

        return DB::connection('tenant')
            ->table('mission_phase_chat as c')
            ->join('auditors as a', 'c.author_id', '=', 'a.id')
            ->where('c.assignment_id', $assignmentId)
            ->where('c.form_code', $this->formCode)
            ->where(function ($q) use ($auditorId, $role) {
                if ($role === 'DM') { $q->whereRaw('1=1'); return; }
                $visible = match ($role) { 'CM' => ['CM','AS','AJ'], 'AS' => ['AS','AJ'], default => ['AJ'] };
                $q->where('c.author_id', $auditorId)->orWhereIn('c.author_role', $visible);
            })
            ->select([
                'c.id', 'c.content', 'c.type', 'c.priority', 'c.is_pinned',
                'c.author_id', 'c.author_role', 'c.parent_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'),COALESCE(LEFT(a.first_name,1),'?'))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id = {$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('c.created_at')
            ->get()
            ->map(fn($m) => tap($m, fn($m) => $m->is_mine = (bool)$m->is_mine))
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════
    // loadProceduresData
    // ══════════════════════════════════════════════════════════════
    private function loadProceduresData(int $aptId, int $auditorId, string $role): array
    {
        try {
            $isDmCm = in_array($role, ['DM', 'CM']);

            // Charger toutes les procédures
            $query = DB::connection('tenant')->table(self::TBL_PROC)
                ->where('apt_id', $aptId)->orderBy('ordre');

            // AS/AJ : ne voient que leurs procédures assignées
            if (!$isDmCm) {
                $assignedProcIds = DB::connection('tenant')->table(self::TBL_ASSIGN)
                    ->where('apt_id', $aptId)->where('auditeur_id', $auditorId)
                    ->pluck('procedure_id')->toArray();
                $query->whereIn('id', $assignedProcIds);
            }

            $procs = $query->get()->toArray();

            foreach ($procs as &$proc) {
                $proc = (array) $proc;

                // Niveau N1
                $level = DB::connection('tenant')->table(self::TBL_LEVEL)
                    ->where('procedure_id', $proc['id'])->where('code_niveau', 'N1')->first();

                $proc['items_matrice']    = $level?->items_matrice    ?? '[]';
                $proc['plan_collecte']    = $level?->plan_collecte    ?? '[]';
                $proc['grille_entretien'] = $level?->grille_entretien ?? '[]';
                $proc['level_id']         = $level?->id;

                // Documents attachés
                $proc['attached_docs'] = DB::connection('tenant')->table(self::TBL_DOC)
                    ->where('apt_id', $aptId)->where('procedure_id', $proc['id'])
                    ->orderBy('created_at')
                    ->get()->map(fn($d) => (array)$d)->toArray();

                // Décoder bpmn_synthese
                if (!empty($proc['bpmn_synthese']) && is_string($proc['bpmn_synthese'])) {
                    $proc['bpmn_synthese'] = json_decode($proc['bpmn_synthese'], true);
                }

                $proc['levels'] = $level ? [(array)$level] : [];
            }

            return $procs;
        } catch (\Exception $e) {
            Log::warning('[APT] loadProceduresData: ' . $e->getMessage());
            return [];
        }
    }

    private function loadProcAssignments(int $aptId): array
    {
        try {
            $map = [];
            DB::connection('tenant')->table(self::TBL_ASSIGN)
                ->where('apt_id', $aptId)->whereNotNull('auditeur_id')
                ->select('procedure_id', 'auditeur_id')->get()
                ->each(fn($r) => $map[(string)$r->procedure_id] = $r->auditeur_id);
            return $map;
        } catch (\Exception $e) {
            Log::warning('[APT] loadProcAssignments: ' . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════
    // DELETE /analyse-procedures/{form}
    // ══════════════════════════════════════════════════════════════
    public function destroy(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM']))
            return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);

        // Cascade manuelle
        $procIds = DB::connection('tenant')->table(self::TBL_PROC)->where('apt_id', $form)->pluck('id');
        if ($procIds->isNotEmpty()) {
            DB::connection('tenant')->table(self::TBL_LEVEL)->whereIn('procedure_id', $procIds)->delete();
            DB::connection('tenant')->table(self::TBL_ASSIGN)->whereIn('procedure_id', $procIds)->delete();
        }
        DB::connection('tenant')->table(self::TBL_DOC)->where('apt_id', $form)->delete();
        DB::connection('tenant')->table(self::TBL_PROC)->where('apt_id', $form)->delete();
        DB::connection('tenant')->table($this->table)->where('id', $form)->delete();

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // DELETE /analyse-procedures/delete-doc
    // Supprime un document joint (DM/CM supprime tous, AS/AJ supprime les siens)
    // ══════════════════════════════════════════════════════════════
    public function deleteDoc(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $docId  = (int) $request->input('doc_id', 0);
        $aptId  = (int) $request->input('apt_id', 0);

        if (!$docId) return response()->json(['error' => 'doc_id manquant'], 422);

        $doc = DB::connection('tenant')->table(self::TBL_DOC)->where('id', $docId)->first();
        if (!$doc) return response()->json(['error' => 'Document introuvable'], 404);

        $apt = DB::connection('tenant')->table($this->table)->where('id', $doc->apt_id)->first();
        if (!$apt) return response()->json(['error' => 'APT introuvable'], 404);

        $role = $this->getRole((int) $apt->mission_id, $auditor->id);

        // AS/AJ : ne peut supprimer que ses propres uploads
        if (!in_array($role, ['DM', 'CM'])) {
            if ((int) $doc->uploaded_by !== $auditor->id) {
                return response()->json(['error' => 'Vous ne pouvez supprimer que vos propres documents'], 403);
            }
        }

        // Supprimer le fichier physique
        if (!empty($doc->path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->path);
        }

        DB::connection('tenant')->table(self::TBL_DOC)->where('id', $docId)->delete();

        return response()->json(['success' => true, 'doc_id' => $docId]);
    }

    // ── Mappers Excel ──────────────────────────────────────────────
    private function mapNature(string $v): ?string         { $v=mb_strtolower(trim($v)); if(str_contains($v,'fort')) return 'fort'; if(str_contains($v,'faibl')) return 'faible'; return null; }
    private function mapOuiNon(string $v): ?string         { $v=mb_strtolower(trim($v)); return in_array($v,['o','oui','yes','1','x'])?'oui':(in_array($v,['n','non','no','0'])?'non':null); }
    private function mapResultat(string $v): ?string       { $v=mb_strtolower(trim($v)); if(str_contains($v,'nc')||str_contains($v,'non conf')) return 'nc'; if(str_contains($v,'pp')||str_contains($v,'part')) return 'pp'; if($v==='c'||str_contains($v,'conforme')) return 'c'; return null; }
    private function mapStatutCollecte(string $v): ?string { $v=mb_strtolower(trim($v)); if(str_contains($v,'à')||str_contains($v,'col')) return 'a_collecter'; if(str_contains($v,'obten')) return 'obtenu'; if(str_contains($v,'n/a')||$v==='na') return 'na'; return null; }
    private function formatSize(int $b): string            { if($b<1024) return $b.' o'; if($b<1048576) return round($b/1024,1).' Ko'; return round($b/1048576,1).' Mo'; }
}