<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * TFFA — Tableau des Forces et Faiblesses Apparentes
 * ════════════════════════════════════════════════════════════════════════
 *
 * Hérite de BasePhaseFormController.
 * Surcharge : store, update, soumettre, valider, destroy, getRole, buildPayload.
 *
 * Format TFFA (11 colonnes Excel) :
 *   FORCES     (col A–D) : F | N° | Libellé | Processus concerné
 *   FAIBLESSES (col E–J) : F | N° | Libellé | Processus | Fonctions | Objectif d'audit
 *
 * 6 domaines : Analyse des Risques | Analyse des Processus |
 *              Répartition des Tâches | Analyse des Procédures |
 *              Contrôle Interne | Contrôle de Conformité
 *
 * Sources BD (loadDonneesDepuisAnalyses) :
 *   1. mission_phase_ar.risques JSON  → domaine analyse_risques
 *   2. mission_phase_ap.processus JSON → domaine analyse_processus
 *   3. mission_phase_apt / apt_procedures → domaine analyse_procedures
 *   4. analyse_conformite_items        → domaine controle_conformite
 *   5. amq_etapes                      → domaine controle_conformite
 */
class AnalyseForcesFaiblessesController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_aff';
    protected string $formCode    = 'analyse-forces-faiblesses';
    protected string $codePrefix  = 'AFF';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseForcesFaiblesses';
    protected string $routeEdit   = 'auditor.ac.analyse-forces-faiblesses.edit';

    protected array $validationRules = [
        'fait_par'  => 'nullable|string|max:255',
        'revue_par' => 'nullable|string|max:255',
    ];

    /** 6 domaines dans l'ordre exact de l'Excel */
    public const DOMAINES = [
        'analyse_risques'     => 'Analyse des Risques',
        'analyse_processus'   => 'Analyse des Processus',
        'repartition_taches'  => 'Répartition des Tâches',
        'analyse_procedures'  => 'Analyse des Procédures',
        'controle_interne'    => 'Contrôle Interne',
        'controle_conformite' => 'Contrôle de Conformité',
    ];

    // ══════════════════════════════════════════════════════════════
    // formData — champs simples (forces/faiblesses/synthese gérés séparément)
    // ══════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'fait_par'  => $request->input('fait_par'),
            'revue_par' => $request->input('revue_par'),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // getRole — surcharge : utilise mission_phase_assignment_auditeurs
    //           (le BasePhaseFormController utilise mission_phase_auditeurs)
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
    // buildPayload — ajoute les données spécifiques AFF
    // ══════════════════════════════════════════════════════════════
    protected function buildPayload(
        int     $missionId,
        int     $assignmentId,
        Auditor $auditor,
        mixed   $form = null
    ): array {
        // Auditeurs de la phase (depuis mission_phase_assignment_auditeurs)
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.audit_code', 'a.last_name', 'a.first_name', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'), COALESCE(LEFT(a.first_name,1),'?'))) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'audit_code' => $a->audit_code,
                'last_name'  => $a->last_name,
                'first_name' => $a->first_name,
                'full_name'  => trim($a->full_name),
                'initials'   => $a->initials,
                'role_code'  => $a->role_code,
                'role_label' => match ($a->role_code) {
                    'DM'    => 'Directeur de Mission',
                    'CM'    => 'Chef de Mission',
                    'AS'    => 'Auditeur Senior',
                    'AJ'    => 'Auditeur Junior',
                    default => $a->role_code ?? '—',
                },
            ])->toArray();

        // Hydratation du formulaire
        $formData = null;
        if ($form) {
            $formData               = (array) $form;
            $formData['forces']     = $this->decodeArr($form->forces     ?? null);
            $formData['faiblesses'] = $this->decodeArr($form->faiblesses ?? null);
            $formData['synthese']   = $this->decodeObj($form->synthese   ?? null);
        }

        // Données issues des analyses BD
        $donneesDB = $this->loadDonneesDepuisAnalyses($missionId, $assignmentId);

        // Liste des AFF de cet assignment
        $affList = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'validation_status', 'fait_par', 'updated_at'])
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        $formId = $form?->id ?? null;
        $base   = url('/m/audit.core/ac/preparation/analyse-forces-faiblesses');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $formData,
                'phaseAuditeurs' => $phaseAuditeurs,
                'affList'        => $affList,
                'domaines'       => self::DOMAINES,
                'donneesDB'      => $donneesDB,
                'currentAuditor' => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'      => $base,
                'urlStore'     => route('auditor.ac.analyse-forces-faiblesses.store'),
                'urlUpdate'    => $formId ? route('auditor.ac.analyse-forces-faiblesses.update',    $formId) : null,
                'urlSoumettre' => $formId ? route('auditor.ac.analyse-forces-faiblesses.soumettre', $formId) : null,
                'urlValider'   => $formId ? route('auditor.ac.analyse-forces-faiblesses.valider',   $formId) : null,
                'urlIndex'     => route('audit.ac.preparation.analyse-forces-faiblesses'),
                'backUrl'      => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // index
    // ══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);

            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id',    0));
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));

            if (!$missionId || !$assignmentId) abort(422, 'Contexte mission manquant.');

            // Si formulaire existant → redirect vers edit
            $existing = DB::connection('tenant')
                ->table($this->table)
                ->where('assignment_id', $assignmentId)
                ->first();

            if ($existing) {
                return redirect()->route($this->routeEdit, $existing->id)
                    ->with('mission_id',    $missionId)
                    ->with('assignment_id', $assignmentId);
            }

            return \Inertia\Inertia::render(
                $this->inertiaPage,
                $this->buildPayload($missionId, $assignmentId, $auditor, null)
            );
        } catch (\Exception $e) {
            Log::error('[AFF] index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // edit
    // ══════════════════════════════════════════════════════════════
    public function edit(Request $request, int $formId)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);

            $form         = DB::connection('tenant')->table($this->table)->where('id', $formId)->firstOrFail();
            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id);

            if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

            return \Inertia\Inertia::render(
                $this->inertiaPage,
                $this->buildPayload($missionId, $assignmentId, $auditor, $form)
            );
        } catch (\Exception $e) {
            Log::error('[AFF] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // store
    // ══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int) $request->input('mission_id',    0);
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId) {
            return response()->json(['success' => false, 'message' => 'Contexte mission manquant.'], 422);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment || $assignment->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);
        }

        // Si formulaire existant → update
        $existing = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->first();

        if ($existing) {
            return $this->update($request, $existing->id);
        }

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            'code'              => $this->genCode($missionId),
            'forces'            => $this->toJson($request->input('forces',     '[]')),
            'faiblesses'        => $this->toJson($request->input('faiblesses', '[]')),
            'synthese'          => $this->toJson($request->input('synthese',   '{}')),
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'form'    => $this->hydrateForm($form),
            'message' => 'TFFA créé.',
        ]);
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

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            [
                'forces'     => $this->toJson($request->input('forces',     '[]')),
                'faiblesses' => $this->toJson($request->input('faiblesses', '[]')),
                'synthese'   => $this->toJson($request->input('synthese',   '{}')),
                'updated_at' => now(),
            ]
        ));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);

        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();

        return response()->json([
            'success' => true,
            'form'    => $this->hydrateForm($updated),
            'message' => 'TFFA mis à jour.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // soumettre
    // ══════════════════════════════════════════════════════════════
    public function soumettre(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
        if ($row->validation_status === 'validated') {
            return response()->json(['error' => 'Formulaire déjà validé'], 422);
        }
        if ($row->validation_status === 'in_review') {
            return response()->json(['error' => 'Formulaire déjà soumis pour validation'], 422);
        }

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
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
    public function valider(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        }
        if ($row->validation_status !== 'in_review') {
            return response()->json(['error' => 'Le formulaire doit être soumis avant validation'], 422);
        }

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif du rejet obligatoire'], 422);

            DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);

            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        // Validation définitive — DM uniquement
        if ($role !== 'DM') {
            return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);
        }

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);

        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);

        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ══════════════════════════════════════════════════════════════
    // destroy
    // ══════════════════════════════════════════════════════════════
    public function destroy(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId = (int) $row->mission_id;
        $role      = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        }
        if ($row->validation_status === 'validated') {
            return response()->json(['error' => 'Un formulaire validé ne peut pas être supprimé'], 403);
        }

        DB::connection('tenant')->table($this->table)->where('id', $formId)->delete();
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // loadDonneesDepuisAnalyses — 5 sources BD
    // ══════════════════════════════════════════════════════════════
    private function loadDonneesDepuisAnalyses(int $missionId, int $assignmentId): array
    {
        $forces     = [];
        $faiblesses = [];

        Log::info("[AFF] ── loadDonneesDepuisAnalyses START missionId={$missionId} assignmentId={$assignmentId}");

        try {
            // ═══════════════════════════════════════════════════════════════
            // SOURCE 1 : mission_phase_ar.risques (JSON) — Analyse des Risques
            // ═══════════════════════════════════════════════════════════════
            $arRow = DB::connection('tenant')
                ->table('mission_phase_ar')
                ->where('assignment_id', $assignmentId)
                ->select('id', 'risques', 'mission_id', 'assignment_id')
                ->first();

            if (!$arRow) {
                $arRow = DB::connection('tenant')
                    ->table('mission_phase_ar')
                    ->where('mission_id', $missionId)
                    ->select('id', 'risques', 'mission_id', 'assignment_id')
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')
                    ->first();

                Log::info("[AFF] SOURCE 1 — fallback par mission_id={$missionId} → AR " . ($arRow ? "id={$arRow->id}" : 'INTROUVABLE'));
            } else {
                Log::info("[AFF] SOURCE 1 — AR trouvé par assignment_id={$assignmentId} id={$arRow->id}");
            }

            if ($arRow) {
                $savedRisks = $this->decodeArr($arRow->risques);
                Log::info("[AFF] SOURCE 1 — AR id={$arRow->id} : " . count($savedRisks) . " risques dans le JSON");

                $riskIds = array_filter(array_map(fn($r) => (int)($r['risk_id'] ?? $r['id'] ?? 0), $savedRisks));
                $risksRef = DB::connection('tenant')
                    ->table('risks')
                    ->whereIn('id', array_values(array_unique($riskIds)))
                    ->select('id', 'code', 'label')
                    ->get()
                    ->keyBy('id');

                foreach ($savedRisks as $idx => $r) {
                    $choix    = $r['choix'] ?? false;
                    $riskCode = $r['code']  ?? ($r['risk_code'] ?? '?');
                    $riskId   = (int) ($r['id'] ?? ($r['risk_id'] ?? 0));

                    $impactNet = (float) ($r['impact_net']    ?? 0);
                    $freqNet   = (float) ($r['frequency_net'] ?? 0);
                    $score     = $impactNet * $freqNet;

                    if (isset($r['glob_resid']) && $r['glob_resid'] !== null && $r['glob_resid'] > 0) {
                        $score = (float) $r['glob_resid'];
                    }

                    if (!$choix) {
                        continue;
                    }

                    $processus     = $r['process_code']      ?? '';
                    $objCtrl       = $r['objectif_controle'] ?? '';
                    $libelleRisque = $risksRef[$riskId]?->label
                        ?? $r['label']
                        ?? $r['risk_label']
                        ?? $r['activity_name']
                        ?? "Risque {$riskCode}";

                    // Forces (score ≤ 8)
                    $forcesTexte = trim($r['forces'] ?? '');
                    if ($forcesTexte !== '' && $score <= 8) {
                        $lignes = $this->splitTexte($forcesTexte);
                        foreach ($lignes as $ligne) {
                            $forces[] = [
                                'domaine'            => 'analyse_risques',
                                'libelle'            => $ligne,
                                'processus_concerne' => $processus,
                                '_source'            => "AR/{$riskCode}",
                                '_risk_score'        => $score,
                            ];
                        }
                    }

                    // Faiblesses (texte renseigné et score ≥ 4)
                    $faibTexte = trim($r['faiblesses'] ?? '');
                    if ($faibTexte !== '' && $score >= 4) {
                        $lignes = $this->splitTexte($faibTexte);
                        foreach ($lignes as $ligne) {
                            $faiblesses[] = [
                                'domaine'            => 'analyse_risques',
                                'libelle'            => $ligne,
                                'processus_concerne' => $processus,
                                'fonctions'          => $r['qualif_controle'] ?? '',
                                'objectif_controle'  => $objCtrl,
                                '_source'            => "AR/{$riskCode}",
                                '_risk_score'        => $score,
                            ];
                        }
                    }

                    // Risque critique sans texte faiblesses (score ≥ 9)
                    if ($faibTexte === '' && $score >= 9) {
                        $faiblesses[] = [
                            'domaine'            => 'analyse_risques',
                            'libelle'            => $libelleRisque,
                            'processus_concerne' => $processus,
                            'fonctions'          => '',
                            'objectif_controle'  => $objCtrl,
                            '_source'            => "AR/{$riskCode}",
                            '_risk_score'        => $score,
                        ];
                    }
                }
            }

            // ═══════════════════════════════════════════════════════════════
            // SOURCE 2 : mission_phase_ap.processus JSON — Analyse des Processus
            // ═══════════════════════════════════════════════════════════════
            
            // Recherche en priorité par assignment_id exact
            $apForms = DB::connection('tenant')
                ->table('mission_phase_ap')
                ->where('assignment_id', $assignmentId)
                ->select('id', 'processus')
                ->get();

            Log::info("[AFF] SOURCE 2 — AP par assignment_id={$assignmentId} : " . $apForms->count() . " trouvée(s)");

            // Fallback : prendre les AP de la même mission si aucun trouvé
            if ($apForms->isEmpty()) {
                $apForms = DB::connection('tenant')
                    ->table('mission_phase_ap')
                    ->where('mission_id', $missionId)
                    ->select('id', 'processus')
                    ->get();
                Log::info("[AFF] SOURCE 2 — fallback par mission_id={$missionId} : " . $apForms->count() . " AP trouvée(s)");
            }

            foreach ($apForms as $apForm) {
                $processusArr = $this->decodeArr($apForm->processus);
                Log::info("[AFF] SOURCE 2 — AP id={$apForm->id} : " . count($processusArr) . " processus");

                foreach ($processusArr as $proc) {
                    $procName = $proc['name'] ?? ($proc['process_name'] ?? $proc['process_code'] ?? $proc['code'] ?? '');
                    $procCode = $proc['code'] ?? ($proc['process_code'] ?? '');

                    // TRAITEMENT DES FORCES
                    $forcesTexte = trim($proc['forces'] ?? '');
                    if ($forcesTexte !== '') {
                        $lignes = $this->splitTexte($forcesTexte);
                        Log::info("[AFF] SOURCE 2 — AP proc={$procCode} forces: '{$forcesTexte}' → " . count($lignes) . " ligne(s)");
                        foreach ($lignes as $ligne) {
                            $forces[] = [
                                'domaine'            => 'analyse_processus',
                                'libelle'            => $ligne,
                                'processus_concerne' => $procName,
                                '_source'            => "AP/{$procCode}",
                            ];
                        }
                    }

                    // TRAITEMENT DES FAIBLESSES
                    $faiblessesTexte = trim($proc['faiblesses'] ?? '');
                    if ($faiblessesTexte !== '') {
                        $lignes = $this->splitTexte($faiblessesTexte);
                        Log::info("[AFF] SOURCE 2 — AP proc={$procCode} faiblesses: '{$faiblessesTexte}' → " . count($lignes) . " ligne(s)");
                        foreach ($lignes as $ligne) {
                            $faiblesses[] = [
                                'domaine'            => 'analyse_processus',
                                'libelle'            => $ligne,
                                'processus_concerne' => $procName,
                                'fonctions'          => '',
                                'objectif_controle'  => $proc['observations'] ?? '',
                                '_source'            => "AP/{$procCode}",
                            ];
                        }
                    }
                }
            }

            // ═══════════════════════════════════════════════════════════════
            // SOURCE 3 : mission_phase_apt + apt_procedures — Analyse des Procédures
            // ═══════════════════════════════════════════════════════════════
            $aptForms = DB::connection('tenant')
                ->table('mission_phase_apt')
                ->where('assignment_id', $assignmentId)
                ->select('id', 'synthese_ff')
                ->get();

            Log::info("[AFF] SOURCE 3 — mission_phase_apt rows : " . $aptForms->count());

            foreach ($aptForms as $aptForm) {
                $syntheseFF = $this->decodeArr($aptForm->synthese_ff);
                
                foreach ($syntheseFF['forces'] ?? [] as $f) {
                    $lib = is_string($f) ? $f : ($f['libelle'] ?? $f['description'] ?? null);
                    if ($lib) {
                        $forces[] = [
                            'domaine'            => 'analyse_procedures',
                            'libelle'            => $lib,
                            'processus_concerne' => is_array($f) ? ($f['processus'] ?? '') : '',
                            '_source'            => 'APT',
                        ];
                    }
                }

                foreach ($syntheseFF['faiblesses'] ?? [] as $w) {
                    $lib = is_string($w) ? $w : ($w['libelle'] ?? $w['description'] ?? null);
                    if ($lib) {
                        $faiblesses[] = [
                            'domaine'            => 'analyse_procedures',
                            'libelle'            => $lib,
                            'processus_concerne' => is_array($w) ? ($w['processus']           ?? '') : '',
                            'fonctions'          => is_array($w) ? ($w['fonctions']            ?? '') : '',
                            'objectif_controle'  => is_array($w) ? ($w['objectif_controle']   ?? '') : '',
                            '_source'            => 'APT',
                        ];
                    }
                }

                $procs = DB::connection('tenant')
                    ->table('apt_procedures')
                    ->where('apt_id', $aptForm->id)
                    ->select('intitule', 'niveau_conformite', 'service_dept', 'responsable_proc', 'commentaire')
                    ->get();

                foreach ($procs as $proc) {
                    $niveau = mb_strtolower(trim($proc->niveau_conformite ?? ''));
                    if (!$niveau || !$proc->intitule) continue;

                    if ($niveau === 'conforme') {
                        $forces[] = [
                            'domaine'            => 'analyse_procedures',
                            'libelle'            => $proc->intitule,
                            'processus_concerne' => $proc->service_dept ?? '',
                            '_source'            => 'APT-proc',
                        ];
                    } elseif (in_array($niveau, ['non_conforme', 'partiellement', 'nc', 'pp'])) {
                        $faiblesses[] = [
                            'domaine'            => 'analyse_procedures',
                            'libelle'            => $proc->intitule,
                            'processus_concerne' => $proc->service_dept     ?? '',
                            'fonctions'          => $proc->responsable_proc ?? '',
                            'objectif_controle'  => $proc->commentaire      ?? '',
                            '_source'            => 'APT-proc',
                        ];
                    }
                }
            }

            // ═══════════════════════════════════════════════════════════════
            // SOURCE 4 : analyse_conformite_items — Contrôle de Conformité
            // ═══════════════════════════════════════════════════════════════
            $aconfForms = DB::connection('tenant')
                ->table('mission_phase_aconf')
                ->where('assignment_id', $assignmentId)
                ->select('id')
                ->get();

            Log::info("[AFF] SOURCE 4 — mission_phase_aconf rows : " . $aconfForms->count());

            foreach ($aconfForms as $aconfForm) {
                $items = DB::connection('tenant')
                    ->table('analyse_conformite_items')
                    ->where('analyse_conformite_id', $aconfForm->id)
                    ->select('ref_article', 'libelle_norme', 'reponse', 'forces', 'faiblesses', 'objectif')
                    ->get();

                foreach ($items as $item) {
                    $reponse = strtoupper(trim($item->reponse ?? ''));

                    if ($reponse === 'O' && !empty($item->forces)) {
                        foreach ($this->splitTexte($item->forces) as $ligne) {
                            $forces[] = [
                                'domaine'            => 'controle_conformite',
                                'libelle'            => $ligne,
                                'processus_concerne' => $item->libelle_norme ?? '',
                                '_source'            => "ACONF/{$item->ref_article}",
                            ];
                        }
                    } elseif ($reponse === 'N' && !empty($item->faiblesses)) {
                        foreach ($this->splitTexte($item->faiblesses) as $ligne) {
                            $faiblesses[] = [
                                'domaine'            => 'controle_conformite',
                                'libelle'            => $ligne,
                                'processus_concerne' => $item->libelle_norme ?? '',
                                'fonctions'          => '',
                                'objectif_controle'  => $item->objectif      ?? '',
                                '_source'            => "ACONF/{$item->ref_article}",
                            ];
                        }
                    }
                }
            }

            // ═══════════════════════════════════════════════════════════════
            // SOURCE 5 : amq_etapes — Contrôle de Conformité (AMQ)
            // ═══════════════════════════════════════════════════════════════
            $amqForms = DB::connection('tenant')
                ->table('mission_phase_amq')
                ->where('assignment_id', $assignmentId)
                ->select('id')
                ->get();

            Log::info("[AFF] SOURCE 5 — mission_phase_amq rows : " . $amqForms->count());

            foreach ($amqForms as $amqForm) {
                $marches = DB::connection('tenant')
                    ->table('amq_marches')
                    ->where('amq_id', $amqForm->id)
                    ->select('id', 'intitule')
                    ->get();

                foreach ($marches as $marche) {
                    $etapes = DB::connection('tenant')
                        ->table('amq_etapes')
                        ->where('marche_id', $marche->id)
                        ->select('libelle', 'statut', 'observation')
                        ->get();

                    foreach ($etapes as $etape) {
                        if ($etape->statut === 'oui') {
                            $forces[] = [
                                'domaine'            => 'controle_conformite',
                                'libelle'            => $etape->libelle,
                                'processus_concerne' => $marche->intitule ?? '',
                                '_source'            => 'AMQ',
                            ];
                        } elseif ($etape->statut === 'non') {
                            $faiblesses[] = [
                                'domaine'            => 'controle_conformite',
                                'libelle'            => $etape->libelle,
                                'processus_concerne' => $marche->intitule ?? '',
                                'fonctions'          => '',
                                'objectif_controle'  => $etape->observation ?? '',
                                '_source'            => 'AMQ',
                            ];
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('[AFF] loadDonneesDepuisAnalyses EXCEPTION: ' . $e->getMessage()
                . ' | file: ' . $e->getFile() . ':' . $e->getLine()
                . ' | trace: ' . $e->getTraceAsString()
            );
        }

        Log::info("[AFF] ── RÉSULTAT FINAL : " . count($forces) . " forces, " . count($faiblesses) . " faiblesses");

        // Numérotation continue globale
        foreach ($forces     as $i => &$f) { $f['num'] = $i + 1; }
        foreach ($faiblesses as $i => &$w) { $w['num'] = $i + 1; }

        return ['forces' => $forces, 'faiblesses' => $faiblesses];
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers privés
    // ══════════════════════════════════════════════════════════════

    /**
     * Découpe un texte en lignes (\n ou ;) en filtrant les vides.
     */
    private function splitTexte(string $texte): array
    {
        return array_values(
            array_filter(
                array_map('trim', preg_split('/[\n;]+/', $texte)),
                fn($l) => $l !== ''
            )
        );
    }

    /**
     * Hydrate un objet formulaire (décode JSON forces/faiblesses/synthese).
     */
    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        $d               = (array) $row;
        $d['forces']     = $this->decodeArr($row->forces     ?? null);
        $d['faiblesses'] = $this->decodeArr($row->faiblesses ?? null);
        $d['synthese']   = $this->decodeObj($row->synthese   ?? null);
        return $d;
    }
    
    private function decodeArr(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }

    private function decodeObj(mixed $v): object
    {
        if (is_object($v)) return $v;
        if (is_array($v))  return (object) $v;
        if (!$v)           return (object) [];
        $d = json_decode($v, true);
        return (object) ($d ?? []);
    }

    private function toJson(mixed $v): string
    {
        if (is_string($v)) {
            json_decode($v);
            if (json_last_error() === JSON_ERROR_NONE) return $v;
        }
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }
}