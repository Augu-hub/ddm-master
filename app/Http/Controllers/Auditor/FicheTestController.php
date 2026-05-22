<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Param\Auditor;
use Inertia\Inertia;

class FicheTestController extends BasePhaseFormController
{
    // ──────────────────────────────────────────────────────────────
    // Configuration
    // ──────────────────────────────────────────────────────────────
    protected string $table       = 'mission_phase_fiche_test';
    protected string $formCode    = 'fiche-test';
    protected string $codePrefix  = 'FT';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/FicheTest';
    protected string $routeEdit   = 'auditor.ac.fiche-test.edit';
    private  string $conn         = 'tenant';

    // ── Tables FRAP / synthèse ───────────────────────────────────
    private string $tableFrap  = 'fiche_observation_frap';
    private string $tableSynth = 'fiche_synthese_frap';
    private string $tableItems = 'fiche_synthese_frap_items';

    // ── Colonnes FRAP autorisées ─────────────────────────────────
    private const FRAP_FILLABLE = [
        'rubrique', 'sous_rubrique', 'objectif_controle', 'test_ref', 'obj_num',
        'niveau_controle_interne', 'fait_constats', 'probleme', 'causes', 'impacts',
        'recommandation', 'commentaires_audite', 'points_forts', 'date_echeance',
        'personne_responsable', 'livrable', 'num_frap', 'statut',
    ];

    // ── Tables outils IFACI (TOUS sauf XIV) ──────────────────────
    private const OUTIL_TABLES = [
        'I'    => 'outil_entretiens',
        'II'   => 'outil_analyse_taches',
        'III'  => 'outil_diagramme_flux',
        'IV'   => 'outil_approche_processus',
        'V'    => 'outil_test_cheminement',
        'VI'   => 'outil_hierarchisation_risques',
        'VII'  => 'outil_referentiel_audit',
        'VIII' => 'outil_cause_effet',
        'IX'   => 'outil_qci',
        'X'    => 'outil_brainstorming',
        'XI'   => 'outil_piste_audit',
        'XII'  => 'outil_circularisation',
        'XIII' => 'outil_audit_analytique',
        // XIV exclu (remplacé par FRAP)
        'XV'   => 'outil_echantillonnage',
    ];

    // ── Tables enfants outils ────────────────────────────────────
    private const OUTIL_CHILDREN = [
        'I'    => [['table' => 'outil_entretien_questions',            'fk' => 'entretien_id']],
        'II'   => [['table' => 'outil_analyse_taches_lignes',          'fk' => 'grille_id']],
        'IV'   => [['table' => 'outil_approche_processus_lignes',      'fk' => 'fiche_id']],
        'V'    => [['table' => 'outil_test_cheminement_etapes',        'fk' => 'test_id']],
        'VI'   => [['table' => 'outil_hierarchisation_risques_lignes', 'fk' => 'fiche_id']],
        'VIII' => [['table' => 'outil_cause_effet_causes',             'fk' => 'diagramme_id']],
        'IX'   => [
            ['table' => 'outil_qci_sections',  'fk' => 'qci_id'],
            ['table' => 'outil_qci_questions', 'fk' => 'qci_id'],
        ],
        'X'    => [['table' => 'outil_brainstorming_idees',            'fk' => 'session_id']],
        'XI'   => [
            ['table' => 'outil_piste_audit_etapes',    'fk' => 'piste_id'],
            ['table' => 'outil_piste_audit_resultats', 'fk' => 'piste_id'],
        ],
        'XII'  => [['table' => 'outil_circularisation_demandes',       'fk' => 'fiche_id']],
        'XIII' => [
            ['table' => 'outil_audit_analytique_lignes', 'fk' => 'procedure_id'],
            ['table' => 'outil_audit_analytique_ecarts', 'fk' => 'procedure_id'],
        ],
        'XV'   => [['table' => 'outil_echantillonnage_elements', 'fk' => 'fiche_id']],
    ];

    // ── Référentiel IFACI (pour affichage) ───────────────────────
    public const OUTILS_IFACI = [
        'I'    => ['code' => 'I',    'label' => "Grille d'Entretien",              'icon' => 'ti-message-question', 'color' => '#1e40af'],
        'II'   => ['code' => 'II',   'label' => "Grille d'Analyse des Tâches",    'icon' => 'ti-layout-list',      'color' => '#065f46'],
        'III'  => ['code' => 'III',  'label' => 'Diagramme de Flux',              'icon' => 'ti-git-branch',       'color' => '#6d28d9'],
        'IV'   => ['code' => 'IV',   'label' => 'Approche Processus',             'icon' => 'ti-sitemap',          'color' => '#b45309'],
        'V'    => ['code' => 'V',    'label' => 'Test de Cheminement',            'icon' => 'ti-route',            'color' => '#be185d'],
        'VI'   => ['code' => 'VI',   'label' => 'Hiérarchisation des Risques',    'icon' => 'ti-alert-triangle',   'color' => '#dc2626'],
        'VII'  => ['code' => 'VII',  'label' => "Référentiel d'Audit",            'icon' => 'ti-table',            'color' => '#0891b2'],
        'VIII' => ['code' => 'VIII', 'label' => 'Cause / Effet (Ishikawa 5M)',    'icon' => 'ti-git-merge',        'color' => '#7c3aed'],
        'IX'   => ['code' => 'IX',   'label' => 'Questionnaire de Contrôle Int.', 'icon' => 'ti-clipboard-check',  'color' => '#0f766e'],
        'X'    => ['code' => 'X',    'label' => 'Brainstorming',                  'icon' => 'ti-bulb',             'color' => '#d97706'],
        'XI'   => ['code' => 'XI',   'label' => "Piste d'Audit",                  'icon' => 'ti-route-scan',       'color' => '#4f46e5'],
        'XII'  => ['code' => 'XII',  'label' => "Circularisation",                'icon' => 'ti-mail-forward',     'color' => '#0369a1'],
        'XIII' => ['code' => 'XIII', 'label' => 'Audit Analytique',               'icon' => 'ti-chart-line',       'color' => '#15803d'],
        'XIV'  => ['code' => 'XIV',  'label' => 'Observation Directe',            'icon' => 'ti-eye',              'color' => '#9333ea'],
        'XV'   => ['code' => 'XV',   'label' => 'Échantillonnage Statistique',    'icon' => 'ti-calculator',       'color' => '#0c4a6e'],
    ];

    // ── Programmes de travail ────────────────────────────────────
    private const PROGRAMMES = [
        ['table' => 'mission_phase_prog_ci',           'code' => 'PTCI',    'label' => 'Contrôle Interne'],
        ['table' => 'mission_phase_prog_conformite',   'code' => 'PTCONF',  'label' => 'Conformité'],
        ['table' => 'mission_phase_prog_marches',      'code' => 'PTMAR',   'label' => 'Marchés'],
        ['table' => 'mission_phase_prog_transactions', 'code' => 'PTTRANS', 'label' => 'Transactions'],
    ];

    protected array $validationRules = [
        'mission_id'    => 'required|integer',
        'assignment_id' => 'required|integer',
    ];

    protected function formData(Request $request, Auditor $auditor): array { return []; }

    // ════════════════════════════════════════════════════════════
    // RÔLE
    // ════════════════════════════════════════════════════════════
    protected function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection($this->conn)
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->first();
        return $row?->role_code ?? 'AJ';
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS FRAP
    // ════════════════════════════════════════════════════════════
    private function loadFraps(int $ficheTestId): array
    {
        return DB::connection($this->conn)
            ->table($this->tableFrap)
            ->where('fiche_test_id', $ficheTestId)
            ->whereNull('deleted_at')
            ->orderBy('rubrique')->orderBy('sous_rubrique')->orderBy('created_at')
            ->get()->toArray();
    }

    private function genNumFrap(int $ficheTestId): string
    {
        $count = DB::connection($this->conn)
            ->table($this->tableFrap)
            ->where('fiche_test_id', $ficheTestId)
            ->whereNull('deleted_at')
            ->count();
        return 'FRAP-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    private function checkFicheAccess(int $ficheTestId, Auditor $auditor): array
    {
        $fiche = DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->first();
        if (!$fiche) return ['ok' => false, 'status' => 404, 'message' => 'Fiche introuvable'];

        $role = $this->getRole((int) $fiche->mission_id, $auditor->id);
        if ($fiche->validation_status === 'validated') {
            return ['ok' => false, 'status' => 403, 'message' => 'Fiche validée — modification impossible'];
        }
        if ((int) $fiche->auditeur_id !== $auditor->id && !in_array($role, ['DM', 'CM'])) {
            return ['ok' => false, 'status' => 403, 'message' => 'Accès refusé'];
        }
        return ['ok' => true, 'fiche' => $fiche, 'role' => $role];
    }

    private function nettoyerSynthesesSansItems(int $ficheTestId): void
    {
        $ids = DB::connection($this->conn)
            ->table($this->tableSynth)
            ->where('fiche_test_id', $ficheTestId)
            ->pluck('id');
        foreach ($ids as $sid) {
            $count = DB::connection($this->conn)->table($this->tableItems)->where('synthese_id', $sid)->count();
            if ($count === 0) {
                DB::connection($this->conn)->table($this->tableSynth)->where('id', $sid)->delete();
            }
        }
    }

    // ════════════════════════════════════════════════════════════
    // FRAP – CRUD (JSON)
    // ════════════════════════════════════════════════════════════
    public function getFraps(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $fraps = DB::connection($this->conn)
            ->table($this->tableFrap)
            ->where('fiche_test_id', $ficheTestId)
            ->whereNull('deleted_at')
            ->when($request->filled('rubrique'),      fn($q) => $q->where('rubrique',      $request->rubrique))
            ->when($request->filled('sous_rubrique'), fn($q) => $q->where('sous_rubrique', $request->sous_rubrique))
            ->when($request->filled('test_ref'),      fn($q) => $q->where('test_ref',      $request->test_ref))
            ->when($request->filled('obj_num'),       fn($q) => $q->where('obj_num',       $request->obj_num))
            ->orderBy('rubrique')->orderBy('sous_rubrique')->orderBy('created_at')
            ->get()->toArray();

        return response()->json(['success' => true, 'fraps' => $fraps, 'total' => count($fraps)]);
    }

    public function storeFrap(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $access = $this->checkFicheAccess($ficheTestId, $auditor);
        if (!$access['ok']) return response()->json(['success' => false, 'error' => $access['message']], $access['status']);

        $fiche     = $access['fiche'];
        $validated = $request->validate([
            'rubrique'                => 'nullable|string|max:255',
            'sous_rubrique'           => 'nullable|string|max:255',
            'objectif_controle'       => 'nullable|string|max:255',
            'test_ref'                => 'nullable|string|max:100',
            'obj_num'                 => 'nullable|string|max:50',
            'niveau_controle_interne' => 'nullable|string|max:50',
            'fait_constats'           => 'nullable|string',
            'probleme'                => 'nullable|string',
            'causes'                  => 'nullable|string',
            'impacts'                 => 'nullable|string',
            'recommandation'          => 'nullable|string',
            'commentaires_audite'     => 'nullable|string',
            'points_forts'            => 'nullable|string',
            'date_echeance'           => 'nullable|date',
            'personne_responsable'    => 'nullable|string|max:255',
            'livrable'                => 'nullable|string|max:500',
        ]);

        $id = DB::connection($this->conn)->table($this->tableFrap)->insertGetId(array_merge($validated, [
            'fiche_test_id' => $ficheTestId,
            'mission_id'    => $fiche->mission_id,
            'assignment_id' => $fiche->assignment_id,
            'auditeur_id'   => $auditor->id,
            'code'          => 'FRAP-' . strtoupper(Str::random(8)),
            'num_frap'      => $this->genNumFrap($ficheTestId),
            'statut'        => 'draft',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]));

        $frap = DB::connection($this->conn)->table($this->tableFrap)->where('id', $id)->first();
        return response()->json(['success' => true, 'frap' => $frap], 201);
    }

    public function updateFrap(Request $request, int $ficheTestId, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $frap = DB::connection($this->conn)
            ->table($this->tableFrap)
            ->where('id', $id)
            ->where('fiche_test_id', $ficheTestId)
            ->whereNull('deleted_at')
            ->first();
        if (!$frap) return response()->json(['success' => false, 'error' => 'FRAP introuvable'], 404);

        $access = $this->checkFicheAccess($ficheTestId, $auditor);
        if (!$access['ok']) return response()->json(['success' => false, 'error' => $access['message']], $access['status']);

        $data = array_filter($request->only(self::FRAP_FILLABLE), fn($v) => $v !== null);
        if (empty($data)) return response()->json(['success' => false, 'error' => 'Aucune donnée à mettre à jour'], 422);

        $data['updated_at'] = now();
        DB::connection($this->conn)->table($this->tableFrap)->where('id', $id)->update($data);
        $updated = DB::connection($this->conn)->table($this->tableFrap)->where('id', $id)->first();

        return response()->json(['success' => true, 'frap' => $updated]);
    }

    public function destroyFrap(int $ficheTestId, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $frap = DB::connection($this->conn)
            ->table($this->tableFrap)
            ->where('id', $id)
            ->where('fiche_test_id', $ficheTestId)
            ->whereNull('deleted_at')
            ->first();
        if (!$frap) return response()->json(['success' => false, 'error' => 'FRAP introuvable'], 404);

        $access = $this->checkFicheAccess($ficheTestId, $auditor);
        if (!$access['ok']) return response()->json(['success' => false, 'error' => $access['message']], $access['status']);

        if ((int) $frap->auditeur_id !== $auditor->id && !in_array($access['role'], ['DM', 'CM'])) {
            return response()->json(['success' => false, 'error' => 'Seuls DM/CM ou le propriétaire peuvent supprimer'], 403);
        }

        DB::connection($this->conn)->beginTransaction();
        try {
            DB::connection($this->conn)->table($this->tableFrap)
                ->where('id', $id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            DB::connection($this->conn)->table($this->tableItems)->where('frap_id', $id)->delete();
            $this->nettoyerSynthesesSansItems($ficheTestId);

            DB::connection($this->conn)->commit();
        } catch (\Exception $e) {
            DB::connection($this->conn)->rollBack();
            Log::error('[FT destroyFrap]: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true]);
    }

    // ════════════════════════════════════════════════════════════
    // SYNTHÈSE FOCI (regroupement FRAP)
    // ════════════════════════════════════════════════════════════
    public function genererSynthese(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $fiche = DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->first();
        if (!$fiche) return response()->json(['success' => false, 'error' => 'Fiche introuvable'], 404);

        $role = $this->getRole((int) $fiche->mission_id, $auditor->id);
        if ((int) $fiche->auditeur_id !== $auditor->id && !in_array($role, ['DM', 'CM'])) {
            return response()->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $fraps = DB::connection($this->conn)
            ->table($this->tableFrap)
            ->where('fiche_test_id', $ficheTestId)
            ->whereNull('deleted_at')
            ->orderBy('rubrique')->orderBy('sous_rubrique')->orderBy('created_at')
            ->get();

        if ($fraps->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'Aucune FRAP enregistrée pour cette fiche de test.'], 422);
        }

        // Grouper : objectif_controle → rubrique → sous_rubrique → [fraps]
        $grouped = [];
        foreach ($fraps as $frap) {
            $obj  = $frap->objectif_controle ?? 'Protection du patrimoine';
            $rub  = $frap->rubrique          ?? 'Sans rubrique';
            $srub = $frap->sous_rubrique     ?? '';
            $grouped[$obj][$rub][$srub][] = $frap;
        }

        DB::connection($this->conn)->beginTransaction();
        try {
            // Supprimer l'ancienne synthèse
            $oldIds = DB::connection($this->conn)->table($this->tableSynth)
                ->where('fiche_test_id', $ficheTestId)->pluck('id');
            if ($oldIds->isNotEmpty()) {
                DB::connection($this->conn)->table($this->tableItems)->whereIn('synthese_id', $oldIds)->delete();
                DB::connection($this->conn)->table($this->tableSynth)->where('fiche_test_id', $ficheTestId)->delete();
            }

            // Reconstruire
            $ordreRubrique = 0;
            foreach ($grouped as $objCtrl => $rubriques) {
                $ordreRubrique++;
                $ordreSsRubrique = 0;
                foreach ($rubriques as $rub => $ssrubs) {
                    $ordreSsRubrique++;
                    foreach ($ssrubs as $srub => $fps) {
                        $syntheseId = DB::connection($this->conn)->table($this->tableSynth)->insertGetId([
                            'fiche_test_id'       => $ficheTestId,
                            'mission_id'          => $fiche->mission_id,
                            'assignment_id'       => $fiche->assignment_id,
                            'objectif_controle'   => $objCtrl,
                            'rubrique'            => $rub,
                            'sous_rubrique'       => $srub ?: null,
                            'ordre_rubrique'      => $ordreRubrique,
                            'ordre_sous_rubrique' => $ordreSsRubrique,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                        $ordreFrap = 0;
                        foreach ($fps as $fp) {
                            $ordreFrap++;
                            DB::connection($this->conn)->table($this->tableItems)->insertOrIgnore([
                                'synthese_id' => $syntheseId,
                                'frap_id'     => $fp->id,
                                'ordre'       => $ordreFrap,
                                'created_at'  => now(),
                            ]);
                        }
                    }
                }
            }

            DB::connection($this->conn)->commit();

            return response()->json([
                'success'      => true,
                'fraps'        => $this->loadFraps($ficheTestId),
                'total'        => $fraps->count(),
                'generated_at' => now()->toISOString(),
                'message'      => 'Synthèse FOCI générée avec succès.',
            ]);

        } catch (\Exception $e) {
            DB::connection($this->conn)->rollBack();
            Log::error('[FT genererSynthese]: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    // BUILD PAYLOAD (transmettre les FRAP à la vue)
    // ════════════════════════════════════════════════════════════
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $auditeurNom = trim($auditor->last_name . ' ' . $auditor->first_name);

        $missionRow = DB::connection($this->conn)->table('mission_programmation')
            ->where('id', $missionId)
            ->select('id', 'mission_id', 'code_mission', 'libelle', 'objectif', 'date_debut', 'date_fin', 'lieux')
            ->first();

        $realMissionId = $missionRow?->mission_id ?? $missionId;

        $phaseAuditeurs = DB::connection($this->conn)
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select('a.id', 'a.last_name', 'a.first_name', 'a.audit_code', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"))
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()->map(fn($a) => [
                'id'         => $a->id,
                'full_name'  => trim($a->full_name),
                'role_code'  => $a->role_code,
                'audit_code' => $a->audit_code,
                'is_current' => $a->id === $auditor->id,
            ])->toArray();

        $programmeData = $this->chargerTestsAuditeur($missionId, $assignmentId, $auditeurNom, $auditor->id);
        $outilVI       = $this->chargerDonneesOutilVI($realMissionId);
        $rciLignes     = $this->chargerLignesRCIpourVII($missionId, $assignmentId);

        $ficheData     = null;
        $outilsParTest = [];
        $fraps         = [];

        if ($form) {
            $ficheData = array_merge((array) $form, [
                'resultats'     => $this->decodeArr($form->resultats     ?? null),
                'constats'      => $this->decodeArr($form->constats      ?? null),
                'outils_data'   => $this->decodeArr($form->outils_data   ?? null),
                'media_items'   => $this->decodeArr($form->media_items   ?? null),
                'synthese_data' => $this->decodeArr($form->synthese_data ?? null),
            ]);
            $outilsParTest = $this->chargerOutilsAvecResumeSQL($form->id);
            $fraps         = $this->loadFraps($form->id);
        }

        $formId = $form?->id ?? null;
        $role   = $this->getRole($missionId, $auditor->id);

        // URLs pour la vue
        $urls = [
            'urlIndex'     => route('audit.ac.realisation.fiche-test'),
            'urlStore'     => route('auditor.ac.fiche-test.store'),
            'urlUpdate'    => $formId ? route('auditor.ac.fiche-test.update',    $formId) : null,
            'urlSoumettre' => $formId ? route('auditor.ac.fiche-test.soumettre', $formId) : null,
            'urlValider'   => $formId ? route('auditor.ac.fiche-test.valider',   $formId) : null,
            'urlSaveOutil' => $formId ? route('auditor.ac.fiche-test.save-outil',$formId) : null,
            'urlLoadOutil' => $formId ? route('auditor.ac.fiche-test.load-outil',$formId) : null,
            'urlAutoSave'  => $formId ? route('auditor.ac.fiche-test.auto-save', $formId) : null,
            'urlEmail'     => $formId ? route('auditor.ac.fiche-test.email',     $formId) : null,
            'urlIaGlobal'  => $formId ? route('auditor.ac.fiche-test.ia-global', $formId) : null,
            // FRAP
            'urlFrapIndex'       => $formId ? route('auditor.ac.fiche-test.frap.index',       $formId) : null,
            'urlFrapStore'       => $formId ? route('auditor.ac.fiche-test.frap.store',       $formId) : null,
            'urlFrapUpdateBase'  => $formId ? url("/m/audit.core/auditor/fiche-test/{$formId}/fraps/:id") : null,
            'urlFrapDestroyBase' => $formId ? url("/m/audit.core/auditor/fiche-test/{$formId}/fraps/:id") : null,
            'urlGenererSynthese' => $formId ? route('auditor.ac.fiche-test.synthese-generer', $formId) : null,
        ];

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $ficheData,
                'mission'        => $missionRow ? (array) $missionRow : null,
                'phaseAuditeurs' => $phaseAuditeurs,
                'programmeData'  => $programmeData,
                'outilsIfaci'    => array_values(self::OUTILS_IFACI),
                'outilsParTest'  => $outilsParTest,
                'fraps'          => $fraps,
                'processus'      => $outilVI['processus'],
                'risquesMission' => $outilVI['risques'],
                'rciLignes'      => $rciLignes,
                'auditorRole'    => $role,
                'auditeurNom'    => $auditeurNom,
                'missionContext' => [
                    'mission_id'      => $missionId,
                    'assignment_id'   => $assignmentId,
                    'mission_libelle' => $missionRow?->libelle      ?? '',
                    'code_mission'    => $missionRow?->code_mission ?? '',
                ],
                'backUrl' => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ],
            $urls
        );
    }

    // ════════════════════════════════════════════════════════════
    // VUES INERTIA (index + edit)
    // ════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);

            $missionId    = (int) ($request->input('mission_id')    ?? session('mission_id',    0));
            $assignmentId = (int) ($request->input('assignment_id') ?? session('assignment_id', 0));
            if (!$missionId || !$assignmentId) abort(422, 'Contexte mission manquant.');

            $existing = DB::connection($this->conn)->table($this->table)
                ->where('assignment_id', $assignmentId)
                ->where('auditeur_id',   $auditor->id)
                ->first();

            if ($existing) {
                return redirect()->route($this->routeEdit, [
                    'form'          => $existing->id,
                    'mission_id'    => $missionId,
                    'assignment_id' => $assignmentId,
                ]);
            }

            return Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, null));
        } catch (\Exception $e) {
            Log::error('[FT] index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(Request $request, int $formId)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);

            $form = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
            if (!$form) abort(404, 'Fiche introuvable.');

            if ((int) $form->auditeur_id !== $auditor->id) {
                $role = $this->getRole((int) $form->mission_id, $auditor->id);
                if (!in_array($role, ['DM', 'CM'])) abort(403, 'Accès refusé.');
            }

            $missionId    = (int) ($request->input('mission_id')    ?? $form->mission_id);
            $assignmentId = (int) ($request->input('assignment_id') ?? $form->assignment_id);

            if (!$form->outils_migrated) {
                $this->migrerOutilsLegacy($formId, $missionId, $assignmentId);
            }

            return Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, $form));
        } catch (\Exception $e) {
            Log::error('[FT] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // CRUD JSON (store / update / autoSave / soumettre / valider / destroy)
    // ════════════════════════════════════════════════════════════
    public function store(Request $request): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int) $request->input('mission_id',    0);
        $assignmentId = (int) $request->input('assignment_id', 0);
        if (!$missionId || !$assignmentId) {
            return response()->json(['success' => false, 'message' => 'Contexte mission manquant.'], 422);
        }

        $existing = DB::connection($this->conn)->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->where('auditeur_id',   $auditor->id)
            ->first();
        if ($existing) return $this->update($request, $existing->id);

        $id = DB::connection($this->conn)->table($this->table)->insertGetId([
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            'auditeur_id'       => $auditor->id,
            'code'              => $this->genCode($missionId),
            'resultats'         => $this->toJson($request->input('resultats',     '[]')),
            'constats'          => $this->toJson($request->input('constats',      '[]')),
            'outils_data'       => $this->toJson($request->input('outils_data',   '[]')),
            'media_items'       => $this->toJson($request->input('media_items',   '[]')),
            'synthese_data'     => $this->toJson($request->input('synthese_data', '{}')),
            'outils_migrated'   => 1,
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        $form = DB::connection($this->conn)->table($this->table)->where('id', $id)->first();
        return response()->json([
            'success'            => true,
            'form'               => $this->hydrateForm($form),
            'message'            => 'Fiche de test créée.',
            'urlUpdate'          => route('auditor.ac.fiche-test.update',          $id),
            'urlSoumettre'       => route('auditor.ac.fiche-test.soumettre',       $id),
            'urlValider'         => route('auditor.ac.fiche-test.valider',         $id),
            'urlSaveOutil'       => route('auditor.ac.fiche-test.save-outil',      $id),
            'urlLoadOutil'       => route('auditor.ac.fiche-test.load-outil',      $id),
            'urlAutoSave'        => route('auditor.ac.fiche-test.auto-save',       $id),
            'urlIaGlobal'        => route('auditor.ac.fiche-test.ia-global',       $id),
            'urlEmail'           => route('auditor.ac.fiche-test.email',           $id),
            'urlFrapStore'       => route('auditor.ac.fiche-test.frap.store',      $id),
            'urlFrapUpdateBase'  => url("/m/audit.core/auditor/fiche-test/{$id}/fraps/:id"),
            'urlFrapDestroyBase' => url("/m/audit.core/auditor/fiche-test/{$id}/fraps/:id"),
            'urlGenererSynthese' => route('auditor.ac.fiche-test.synthese-generer',$id),
        ]);
    }

    public function update(Request $request, int $formId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['success' => false, 'message' => 'Formulaire introuvable.'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if ((int) $row->auditeur_id !== $auditor->id && !in_array($role, ['DM', 'CM'])) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }
        if (!$this->canEdit($row, $role)) {
            return response()->json(['success' => false, 'message' => 'Modification non autorisée.'], 403);
        }

        $data = [
            'resultats'   => $this->toJson($request->input('resultats',   '[]')),
            'constats'    => $this->toJson($request->input('constats',    '[]')),
            'outils_data' => $this->toJson($request->input('outils_data', '[]')),
            'media_items' => $this->toJson($request->input('media_items', '[]')),
            'updated_at'  => now(),
        ];
        if ($request->has('synthese_data')) {
            $data['synthese_data'] = $this->toJson($request->input('synthese_data', '{}'));
        }

        DB::connection($this->conn)->table($this->table)->where('id', $formId)->update($data);
        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);

        $updated = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'Fiche mise à jour.']);
    }

    public function autoSaveEndpoint(Request $request, int $formId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
        if (!$row || $row->validation_status === 'validated') return response()->json(['success' => false], 422);

        $missionId = (int) ($request->input('mission_id') ?? $row->mission_id);
        $role      = $this->getRole($missionId, $auditor->id);
        if ((int) $row->auditeur_id !== $auditor->id && !in_array($role, ['DM', 'CM'])) {
            return response()->json(['success' => false], 403);
        }

        $data = ['updated_at' => now()];
        if ($request->has('resultats'))     $data['resultats']     = $this->toJson($request->input('resultats'));
        if ($request->has('constats'))      $data['constats']      = $this->toJson($request->input('constats'));
        if ($request->has('outils_data'))   $data['outils_data']   = $this->toJson($request->input('outils_data'));
        if ($request->has('synthese_data')) $data['synthese_data'] = $this->toJson($request->input('synthese_data'));

        DB::connection($this->conn)->table($this->table)->where('id', $formId)->update($data);
        return response()->json(['success' => true, 'saved_at' => now()->toISOString()]);
    }

    public function soumettre(Request $request, int $formId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);
        if ((int) $row->auditeur_id !== $auditor->id) return response()->json(['error' => 'Seul le propriétaire peut soumettre'], 403);
        if ($row->validation_status !== 'draft') return response()->json(['error' => 'Statut invalide'], 422);

        DB::connection($this->conn)->table($this->table)->where('id', $formId)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    public function valider(Request $request, int $formId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') return response()->json(['error' => 'Doit être soumis avant validation'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif obligatoire'], 422);
            DB::connection($this->conn)->table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log((int) $row->assignment_id, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        DB::connection($this->conn)->table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    public function destroy(Request $request, int $formId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Fiche validée non supprimable'], 403);

        $outilsLies = DB::connection($this->conn)->table('fiche_test_outils')->where('fiche_test_id', $formId)->get();
        foreach ($outilsLies as $ol) {
            $this->supprimerOutilBD($ol->outil_code, $ol->outil_id);
        }
        DB::connection($this->conn)->table('fiche_test_outils')->where('fiche_test_id', $formId)->delete();

        $frapIds = DB::connection($this->conn)->table($this->tableFrap)->where('fiche_test_id', $formId)->pluck('id');
        if ($frapIds->isNotEmpty()) {
            DB::connection($this->conn)->table($this->tableItems)->whereIn('frap_id', $frapIds)->delete();
        }
        DB::connection($this->conn)->table($this->tableSynth)->where('fiche_test_id', $formId)->delete();
        DB::connection($this->conn)->table($this->tableFrap)->where('fiche_test_id', $formId)->delete();
        DB::connection($this->conn)->table($this->table)->where('id', $formId)->delete();

        $this->log((int) $row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);
        return response()->json(['success' => true]);
    }

    // ════════════════════════════════════════════════════════════
    // SAVE / LOAD OUTIL
    // ════════════════════════════════════════════════════════════
    public function saveOutil(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->first();
        if (!$row) return response()->json(['success' => false, 'error' => 'Fiche introuvable'], 404);
        if ($row->validation_status === 'validated') {
            return response()->json(['success' => false, 'error' => 'Fiche validée — modification impossible'], 403);
        }

        $outilCode     = strtoupper(trim($request->input('outil_code', '')));
        $procedureCode = trim($request->input('procedure_code', ''));
        $testRef       = trim($request->input('test_ref', ''));
        $objNum        = trim($request->input('obj_num', ''));
        $procIdx       = (int) $request->input('proc_idx', 0);
        $data          = $request->input('data',     []);
        $children      = $request->input('children', []);

        if (!$outilCode || !isset(self::OUTIL_TABLES[$outilCode])) {
            return response()->json(['success' => false, 'error' => "Code outil invalide : {$outilCode}"], 422);
        }

        $mainTable = self::OUTIL_TABLES[$outilCode];

        DB::connection($this->conn)->beginTransaction();
        try {
            $existing = DB::connection($this->conn)->table('fiche_test_outils')
                ->where('fiche_test_id',  $ficheTestId)
                ->where('outil_code',     $outilCode)
                ->where('procedure_code', $procedureCode)
                ->where('test_ref',       $testRef)
                ->where('proc_idx',       $procIdx)
                ->where('is_current',     1)
                ->first();

            if ($existing) {
                $outilId = $existing->outil_id;
                $fields  = $this->mapOutilFields($outilCode, $data);
                $fields['updated_by']   = $auditor->id;
                $fields['updated_at']   = now();
                $fields['auto_save_at'] = now();
                DB::connection($this->conn)->table($mainTable)->where('id', $outilId)->update($fields);
            } else {
                $outilId = $this->insertOutilRecord($outilCode, $mainTable, $data, [
                    'mission_id'     => $row->mission_id,
                    'assignment_id'  => $row->assignment_id,
                    'procedure_code' => $procedureCode,
                    'test_ref'       => $testRef,
                    'obj_num'        => $objNum,
                    'created_by'     => $auditor->id,
                ]);
                DB::connection($this->conn)->table('fiche_test_outils')->insert([
                    'fiche_test_id'  => $ficheTestId,
                    'mission_id'     => $row->mission_id,
                    'assignment_id'  => $row->assignment_id,
                    'procedure_code' => $procedureCode,
                    'test_ref'       => $testRef,
                    'obj_num'        => $objNum,
                    'proc_idx'       => $procIdx,
                    'outil_code'     => $outilCode,
                    'outil_table'    => $mainTable,
                    'outil_id'       => $outilId,
                    'version'        => 1,
                    'is_current'     => 1,
                    'created_by'     => $auditor->id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            if ($outilId && !empty($children)) $this->syncChildren($outilCode, $outilId, $children);
            $this->autoSaveSnapshot($outilCode, $mainTable, $outilId, $ficheTestId, $procedureCode, $data, $auditor->id);

            DB::connection($this->conn)->commit();

            $saved          = DB::connection($this->conn)->table($mainTable)->where('id', $outilId)->first();
            $childrenLoaded = $this->loadChildren($outilCode, $outilId);
            $resume         = $this->resumeOutil($outilCode, (array) $saved, $childrenLoaded);

            return response()->json([
                'success'    => true,
                'outil_id'   => $outilId,
                'outil_code' => $outilCode,
                'proc_idx'   => $procIdx,
                'record'     => $saved,
                'children'   => $childrenLoaded,
                'resume'     => $resume,
                'message'    => "Outil {$outilCode} sauvegardé.",
            ]);
        } catch (\Exception $e) {
            DB::connection($this->conn)->rollBack();
            Log::error("[FT saveOutil] {$outilCode}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function loadOutil(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $outilCode     = strtoupper(trim($request->query('outil_code', '')));
        $procedureCode = trim($request->query('procedure_code', ''));
        $testRef       = trim($request->query('test_ref', ''));
        $procIdx       = (int) $request->query('proc_idx', 0);

        if (!$outilCode || !isset(self::OUTIL_TABLES[$outilCode])) {
            return response()->json(['success' => false, 'error' => 'Code outil invalide'], 422);
        }

        $liaison = DB::connection($this->conn)->table('fiche_test_outils')
            ->where('fiche_test_id',  $ficheTestId)
            ->where('outil_code',     $outilCode)
            ->where('procedure_code', $procedureCode)
            ->where('test_ref',       $testRef)
            ->where('proc_idx',       $procIdx)
            ->where('is_current',     1)
            ->first();

        if (!$liaison) {
            return response()->json(['success' => true, 'found' => false, 'record' => null, 'children' => []]);
        }

        $mainTable = self::OUTIL_TABLES[$outilCode];
        $record    = DB::connection($this->conn)->table($mainTable)->where('id', $liaison->outil_id)->first();
        $children  = $this->loadChildren($outilCode, $liaison->outil_id);
        $resume    = $this->resumeOutil($outilCode, (array) $record, $children);

        return response()->json([
            'success'   => true,
            'found'     => true,
            'outil_id'  => $liaison->outil_id,
            'proc_idx'  => $procIdx,
            'record'    => $record,
            'children'  => $children,
            'resume'    => $resume,
            'ia_result' => $record?->ia_result ? json_decode($record->ia_result, true) : null,
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // IA GLOBALE
    // ════════════════════════════════════════════════════════════
    public function iaGlobal(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->first();
        if (!$row) return response()->json(['success' => false, 'error' => 'Fiche introuvable'], 404);

        $outilsLies = DB::connection($this->conn)->table('fiche_test_outils')
            ->where('fiche_test_id', $ficheTestId)->where('is_current', 1)->get();

        $syntheses = [];
        foreach ($outilsLies as $ol) {
            if (!isset(self::OUTIL_TABLES[$ol->outil_code])) continue;
            $record = DB::connection($this->conn)->table(self::OUTIL_TABLES[$ol->outil_code])->where('id', $ol->outil_id)->first();
            if (!$record) continue;
            $iaData   = $record->ia_result ? json_decode($record->ia_result, true) : null;
            $children = $this->loadChildren($ol->outil_code, $ol->outil_id);
            $resume   = $this->resumeOutil($ol->outil_code, (array) $record, $children);
            $syntheses[] = [
                'outil_code'  => $ol->outil_code,
                'outil_label' => self::OUTILS_IFACI[$ol->outil_code]['label'] ?? '',
                'test_ref'    => $ol->test_ref,
                'proc_idx'    => $ol->proc_idx ?? 0,
                'ia_score'    => $iaData['score'] ?? null,
                'ia_synthese' => $iaData['synthese'] ?? null,
                'resume'      => $resume,
            ];
        }

        if (empty($syntheses)) return response()->json(['success' => false, 'error' => 'Aucun outil trouvé.'], 422);

        $missionRow = DB::connection($this->conn)->table('mission_programmation')->where('id', $row->mission_id)->first();
        $prompt = "Expert audit interne IFACI. Synthèse de " . count($syntheses) . " outils pour : "
            . ($missionRow?->libelle ?? 'N/A') . "\n"
            . json_encode($syntheses, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\nRetourne UNIQUEMENT JSON : synthese(string), risques_majeurs(array), points_forts(array), recommandations(array), score_global(0-10), conclusion(string), fiabilite(haute|moyenne|faible).";

        try {
            $r = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 2000,
                'system'     => 'Tu es expert audit interne IFACI. Retourne UNIQUEMENT un JSON valide sans markdown.',
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ])->json();

            $text = collect($r['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '{}';
            $text = trim(preg_replace('/^```json\s*|\s*```$/m', '', $text));
            $data = json_decode($text, true) ?? [];

            DB::connection($this->conn)->table('fiche_test_ia_global')
                ->where('fiche_test_id', $ficheTestId)->update(['is_current' => 0, 'updated_at' => now()]);

            $iaGlobalId = DB::connection($this->conn)->table('fiche_test_ia_global')->insertGetId([
                'fiche_test_id'   => $ficheTestId,
                'mission_id'      => $row->mission_id,
                'assignment_id'   => $row->assignment_id,
                'outils_analyses' => json_encode(array_column($syntheses, 'outil_code')),
                'synthese'        => $data['synthese']        ?? '',
                'risques_majeurs' => json_encode($data['risques_majeurs'] ?? []),
                'points_forts'    => json_encode($data['points_forts']    ?? []),
                'recommandations' => json_encode($data['recommandations'] ?? []),
                'score_global'    => $data['score_global']    ?? null,
                'conclusion'      => $data['conclusion']      ?? '',
                'fiabilite'       => $data['fiabilite']       ?? null,
                'generated_by'    => 'claude-sonnet-4-20250514',
                'generated_at'    => now(),
                'is_current'      => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::connection($this->conn)->table($this->table)
                ->where('id', $ficheTestId)->update(['ia_global_id' => $iaGlobalId, 'updated_at' => now()]);

            return response()->json(['success' => true, 'ia_global' => $data, 'ia_global_id' => $iaGlobalId]);
        } catch (\Exception $e) {
            Log::error('[FT iaGlobal]: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    // EMAIL
    // ════════════════════════════════════════════════════════════
    public function envoyerEmail(Request $request, int $ficheTestId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);

        $row = DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->first();
        if (!$row) return response()->json(['success' => false, 'error' => 'Fiche introuvable'], 404);

        $v = $request->validate([
            'type'               => 'required|in:rapport_info,demande_confirmation,relance,validation_finale',
            'destinataire_nom'   => 'required|string|max:255',
            'destinataire_email' => 'required|email|max:255',
            'sujet'              => 'required|string|max:500',
            'corps_html'         => 'required|string',
            'outils_inclus'      => 'nullable|array',
        ]);

        $token   = $v['type'] === 'demande_confirmation' ? Str::random(64) : null;
        $emailId = DB::connection($this->conn)->table('fiche_test_emails')->insertGetId([
            'fiche_test_id'      => $ficheTestId,
            'mission_id'         => $row->mission_id,
            'assignment_id'      => $row->assignment_id,
            'type'               => $v['type'],
            'destinataire_nom'   => $v['destinataire_nom'],
            'destinataire_email' => $v['destinataire_email'],
            'sujet'              => $v['sujet'],
            'corps_html'         => $v['corps_html'],
            'outils_inclus'      => json_encode($v['outils_inclus'] ?? []),
            'token_confirm'      => $token,
            'statut'             => 'pending',
            'sent_by'            => $auditor->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        try {
            $corps = $v['corps_html'];
            if ($token) {
                $confirmUrl = route('audit.confirmation.publique', ['token' => $token]);
                $corps .= "\n\n<p><a href='{$confirmUrl}' style='background:#1e40af;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;'>Confirmer / Répondre</a></p>";
            }
            Mail::html($corps, fn($msg) => $msg->to($v['destinataire_email'], $v['destinataire_nom'])->subject($v['sujet']));
            DB::connection($this->conn)->table('fiche_test_emails')->where('id', $emailId)
                ->update(['statut' => 'sent', 'envoye_at' => now(), 'updated_at' => now()]);
            return response()->json(['success' => true, 'email_id' => $emailId, 'token' => $token, 'message' => 'Email envoyé.']);
        } catch (\Exception $e) {
            DB::connection($this->conn)->table('fiche_test_emails')->where('id', $emailId)
                ->update(['statut' => 'failed', 'error_msg' => $e->getMessage(), 'updated_at' => now()]);
            Log::error('[FT email]: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS OUTILS PRIVÉS
    // ════════════════════════════════════════════════════════════
    private function chargerOutilsAvecResumeSQL(int $ficheTestId): array
    {
        $sql = "
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oe.statut,oe.ia_result,
                JSON_OBJECT('titre',COALESCE(oe.interlocuteur,oe.intitule,'Entretien'),'conclusion',COALESCE(oe.synthese,''),'score',JSON_EXTRACT(oe.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Questions','valeur',(SELECT COUNT(*) FROM outil_entretien_questions WHERE entretien_id=oe.id)))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_entretiens oe ON oe.id=fto.outil_id
            WHERE fto.outil_code='I' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oat.statut,oat.ia_result,
                JSON_OBJECT('titre',COALESCE(oat.processus,oat.intitule,'Analyse des tâches'),'conclusion',COALESCE(oat.observations,''),'score',JSON_EXTRACT(oat.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Tâches','valeur',(SELECT COUNT(*) FROM outil_analyse_taches_lignes WHERE grille_id=oat.id)))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_analyse_taches oat ON oat.id=fto.outil_id
            WHERE fto.outil_code='II' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,odf.statut,odf.ia_result,
                JSON_OBJECT('titre',COALESCE(odf.processus,odf.intitule,'Diagramme de flux'),'conclusion',COALESCE(odf.synthese_validations,''),'score',JSON_EXTRACT(odf.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Activités','valeur',COALESCE(JSON_LENGTH(odf.activites_json),0)),JSON_OBJECT('label','Version','valeur',COALESCE(odf.version,'V1')))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_diagramme_flux odf ON odf.id=fto.outil_id
            WHERE fto.outil_code='III' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oap.statut,oap.ia_result,
                JSON_OBJECT('titre',COALESCE(oap.intitule,'Approche processus'),'conclusion','','score',JSON_EXTRACT(oap.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Processus','valeur',(SELECT COUNT(*) FROM outil_approche_processus_lignes WHERE fiche_id=oap.id)))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_approche_processus oap ON oap.id=fto.outil_id
            WHERE fto.outil_code='IV' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,otc.statut,otc.ia_result,
                JSON_OBJECT('titre',COALESCE(otc.intitule,otc.processus_audite,'Test de cheminement'),'conclusion',COALESCE(otc.conclusion,''),'score',JSON_EXTRACT(otc.ia_result,'$.score'),'resultats',(SELECT JSON_ARRAY(JSON_OBJECT('label','Étapes','valeur',COALESCE(COUNT(*),0)),JSON_OBJECT('label','Conformes','valeur',COALESCE(SUM(CASE WHEN LOWER(otce.conforme_procedure)='oui' THEN 1 ELSE 0 END),0))) FROM outil_test_cheminement_etapes otce WHERE otce.test_id=otc.id)) AS resume_json
            FROM fiche_test_outils fto JOIN outil_test_cheminement otc ON otc.id=fto.outil_id
            WHERE fto.outil_code='V' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,ohr.statut,ohr.ia_result,
                JSON_OBJECT('titre',COALESCE(ohr.perimetre,ohr.intitule,'Analyse des risques'),'conclusion','','score',JSON_EXTRACT(ohr.ia_result,'$.score'),'resultats',(SELECT JSON_ARRAY(JSON_OBJECT('label','Risques','valeur',COALESCE(COUNT(*),0)),JSON_OBJECT('label','Critiques (P×I≥7)','valeur',COALESCE(SUM(CASE WHEN (ohrl.probabilite*ohrl.impact)>=7 THEN 1 ELSE 0 END),0))) FROM outil_hierarchisation_risques_lignes ohrl WHERE ohrl.fiche_id=ohr.id)) AS resume_json
            FROM fiche_test_outils fto JOIN outil_hierarchisation_risques ohr ON ohr.id=fto.outil_id
            WHERE fto.outil_code='VI' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,ora.statut,ora.ia_result,
                JSON_OBJECT('titre',COALESCE(ora.processus,\"Référentiel d'audit\"),'conclusion','','score',JSON_EXTRACT(ora.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Contrôles','valeur',COALESCE(JSON_LENGTH(ora.lignes),0)),JSON_OBJECT('label','Cadre','valeur',COALESCE(ora.cadre_ref,'COSO')))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_referentiel_audit ora ON ora.id=fto.outil_id
            WHERE fto.outil_code='VII' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oce.statut,oce.ia_result,
                JSON_OBJECT('titre',COALESCE(oce.effet_central,oce.intitule,'Cause/Effet'),'conclusion',COALESCE(oce.synthese,''),'score',JSON_EXTRACT(oce.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Causes','valeur',(SELECT COUNT(*) FROM outil_cause_effet_causes WHERE diagramme_id=oce.id)),JSON_OBJECT('label','Prioritaires','valeur',(SELECT COUNT(*) FROM outil_cause_effet_causes WHERE diagramme_id=oce.id AND priorite=1)))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_cause_effet oce ON oce.id=fto.outil_id
            WHERE fto.outil_code='VIII' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oqci.statut,oqci.ia_result,
                JSON_OBJECT('titre',COALESCE(oqci.processus,oqci.intitule,'QCI'),'conclusion',COALESCE(oqci.conclusion,''),'score',JSON_EXTRACT(oqci.ia_result,'$.score'),'resultats',(SELECT JSON_ARRAY(JSON_OBJECT('label','Questions','valeur',COALESCE(COUNT(*),0)),JSON_OBJECT('label','Conformes','valeur',COALESCE(SUM(CASE WHEN LOWER(oqciq.reponse) IN ('oui','o','yes') THEN 1 ELSE 0 END),0))) FROM outil_qci_questions oqciq WHERE oqciq.qci_id=oqci.id)) AS resume_json
            FROM fiche_test_outils fto JOIN outil_qci oqci ON oqci.id=fto.outil_id
            WHERE fto.outil_code='IX' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,ob.statut,ob.ia_result,
                JSON_OBJECT('titre',COALESCE(ob.problematique,ob.intitule,'Brainstorming'),'conclusion',COALESCE(ob.synthese,''),'score',JSON_EXTRACT(ob.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Idées','valeur',(SELECT COUNT(*) FROM outil_brainstorming_idees WHERE session_id=ob.id)),JSON_OBJECT('label','Retenues','valeur',(SELECT COUNT(*) FROM outil_brainstorming_idees WHERE session_id=ob.id AND retenue=1)))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_brainstorming ob ON ob.id=fto.outil_id
            WHERE fto.outil_code='X' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,op.statut,op.ia_result,
                JSON_OBJECT('titre',COALESCE(op.operation_testee,\"Piste d'audit\"),'conclusion',COALESCE(op.conclusion,''),'score',JSON_EXTRACT(op.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Étapes','valeur',(SELECT COUNT(*) FROM outil_piste_audit_etapes WHERE piste_id=op.id)))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_piste_audit op ON op.id=fto.outil_id
            WHERE fto.outil_code='XI' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oc.statut,oc.ia_result,
                JSON_OBJECT('titre','Circularisation','conclusion','','score',JSON_EXTRACT(oc.ia_result,'$.score'),'resultats',(SELECT JSON_ARRAY(JSON_OBJECT('label','Demandes','valeur',COALESCE(COUNT(*),0)),JSON_OBJECT('label','Réponses','valeur',COALESCE(SUM(CASE WHEN ocd.statut_reponse IN ('ok','ecart') THEN 1 ELSE 0 END),0)),JSON_OBJECT('label','Écarts','valeur',COALESCE(SUM(CASE WHEN ocd.statut_reponse='ecart' THEN 1 ELSE 0 END),0))) FROM outil_circularisation_demandes ocd WHERE ocd.fiche_id=oc.id)) AS resume_json
            FROM fiche_test_outils fto JOIN outil_circularisation oc ON oc.id=fto.outil_id
            WHERE fto.outil_code='XII' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oaa.statut,oaa.ia_result,
                JSON_OBJECT('titre',COALESCE(oaa.source_donnees,'Audit analytique'),'conclusion',COALESCE(oaa.conclusion,''),'score',JSON_EXTRACT(oaa.ia_result,'$.score'),'resultats',JSON_ARRAY(JSON_OBJECT('label','Indicateurs','valeur',(SELECT COUNT(*) FROM outil_audit_analytique_lignes WHERE procedure_id=oaa.id)),JSON_OBJECT('label','Période','valeur',COALESCE(oaa.periode,'—')))) AS resume_json
            FROM fiche_test_outils fto JOIN outil_audit_analytique oaa ON oaa.id=fto.outil_id
            WHERE fto.outil_code='XIII' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oo.statut,oo.ia_result,
                JSON_OBJECT('titre',COALESCE(oo.tache_local_observer,oo.objectif_audit,'Observation'),'conclusion',COALESCE(oo.conclusion,''),'score',JSON_EXTRACT(oo.ia_result,'$.score'),'resultats',(SELECT JSON_ARRAY(JSON_OBJECT('label','Points observés','valeur',COALESCE(COUNT(*),0)),JSON_OBJECT('label','Conformes','valeur',COALESCE(SUM(CASE WHEN LOWER(ooc.conforme_referentiel)='oui' THEN 1 ELSE 0 END),0)),JSON_OBJECT('label','Non conformes','valeur',COALESCE(COUNT(*)-SUM(CASE WHEN LOWER(ooc.conforme_referentiel)='oui' THEN 1 ELSE 0 END),0))) FROM outil_observation_constats ooc WHERE ooc.observation_id=oo.id)) AS resume_json
            FROM fiche_test_outils fto JOIN outil_observation oo ON oo.id=fto.outil_id
            WHERE fto.outil_code='XIV' AND fto.is_current=1 AND fto.fiche_test_id=?
            UNION ALL
            SELECT fto.obj_num,fto.test_ref,fto.outil_code,fto.proc_idx,oes.statut,oes.ia_result,
                JSON_OBJECT('titre',COALESCE(oes.population_reference,'Échantillonnage'),'conclusion',COALESCE(oes.conclusion,''),'score',JSON_EXTRACT(oes.ia_result,'$.score'),'resultats',(SELECT JSON_ARRAY(JSON_OBJECT('label','Population','valeur',COALESCE(oes.taille_population,'N/A')),JSON_OBJECT('label','Échantillon','valeur',COALESCE(oes.taille_retenue,'N/A')),JSON_OBJECT('label','Testé','valeur',COALESCE(COUNT(*),0)),JSON_OBJECT('label','Anomalies','valeur',COALESCE(SUM(CASE WHEN LOWER(oese.anomalie_detectee)='oui' THEN 1 ELSE 0 END),0))) FROM outil_echantillonnage_elements oese WHERE oese.fiche_id=oes.id)) AS resume_json
            FROM fiche_test_outils fto JOIN outil_echantillonnage oes ON oes.id=fto.outil_id
            WHERE fto.outil_code='XV' AND fto.is_current=1 AND fto.fiche_test_id=?
        ";

        $params  = array_fill(0, 15, $ficheTestId);
        $results = DB::connection($this->conn)->select($sql, $params);

        $outilsParTest = [];
        foreach ($results as $row) {
            $testKey = ($row->obj_num ?? '') . '::' . $row->test_ref;
            $iaScore = $row->ia_result ? (json_decode($row->ia_result, true)['score'] ?? null) : null;
            $outilsParTest[$testKey][] = [
                'outil_code' => $row->outil_code,
                'proc_idx'   => (int) $row->proc_idx,
                'label'      => self::OUTILS_IFACI[$row->outil_code]['label'] ?? "Outil {$row->outil_code}",
                'color'      => self::OUTILS_IFACI[$row->outil_code]['color'] ?? '#374151',
                'icon'       => self::OUTILS_IFACI[$row->outil_code]['icon']  ?? 'ti-tool',
                'resume'     => json_decode($row->resume_json, true),
                'statut'     => $row->statut ?? 'draft',
                'ia_score'   => $iaScore,
            ];
        }
        return $outilsParTest;
    }

    private function loadChildren(string $outilCode, int $outilId): array
    {
        $orderCols = [
            'outil_entretien_questions'            => 'ordre',
            'outil_analyse_taches_lignes'          => 'ordre',
            'outil_approche_processus_lignes'      => 'ordre',
            'outil_test_cheminement_etapes'        => 'position',
            'outil_hierarchisation_risques_lignes' => 'ordre',
            'outil_cause_effet_causes'             => 'ordre',
            'outil_qci_sections'                   => 'ordre',
            'outil_qci_questions'                  => 'ordre',
            'outil_brainstorming_idees'            => 'ordre',
            'outil_piste_audit_etapes'             => 'position',
            'outil_piste_audit_resultats'          => 'id',
            'outil_circularisation_demandes'       => 'ordre',
            'outil_audit_analytique_lignes'        => 'ordre',
            'outil_audit_analytique_ecarts'        => 'id',
            'outil_observation_constats'           => 'ordre',
            'outil_observation_recommandations'    => 'ordre',
            'outil_echantillonnage_elements'       => 'ordre',
        ];
        $result = [];
        foreach (self::OUTIL_CHILDREN[$outilCode] ?? [] as $def) {
            $result[$def['table']] = DB::connection($this->conn)
                ->table($def['table'])
                ->where($def['fk'], $outilId)
                ->orderBy($orderCols[$def['table']] ?? 'id')
                ->get()->toArray();
        }
        return $result;
    }

    private function resumeOutil(string $code, array $record, array $children): array
    {
        $r     = ['titre' => '', 'resultats' => [], 'conclusion' => '', 'score' => null];
        $score = fn($rec) => isset($rec['ia_result']) && $rec['ia_result']
            ? (json_decode($rec['ia_result'], true)['score'] ?? null) : null;

        switch ($code) {
            case 'I':
                $r['titre']      = $record['interlocuteur'] ?? $record['intitule'] ?? 'Entretien';
                $r['conclusion'] = $record['synthese'] ?? '';
                $r['resultats']  = [['label' => 'Questions', 'valeur' => count($children['outil_entretien_questions'] ?? [])]];
                $r['score']      = $score($record);
                break;
            case 'XIV':
                $r['titre']   = $record['tache_local_observer'] ?? $record['objectif_audit'] ?? 'Observation';
                $constats     = $children['outil_observation_constats'] ?? [];
                $conf         = collect($constats)->filter(fn($c) => strtolower((is_object($c) ? $c->conforme_referentiel : ($c['conforme_referentiel'] ?? '')) ?? '') === 'oui')->count();
                $r['resultats'] = [
                    ['label' => 'Points observés', 'valeur' => count($constats)],
                    ['label' => 'Conformes',        'valeur' => $conf],
                    ['label' => 'Non conformes',    'valeur' => count($constats) - $conf],
                ];
                $r['conclusion'] = $record['conclusion'] ?? '';
                $r['score']      = $score($record);
                break;
            default:
                $r['titre']      = $record['intitule'] ?? $record['processus'] ?? "Outil {$code}";
                $r['conclusion'] = $record['conclusion'] ?? $record['synthese'] ?? '';
                $r['score']      = $score($record);
        }
        return $r;
    }

    private function mapOutilFields(string $code, array $d): array
    {
        return match ($code) {
            'I'    => ['intitule' => $d['objectif_audit'] ?? '', 'objectif' => $d['objectif_audit'] ?? null, 'interlocuteur' => $d['interlocuteurs'] ?? null, 'date_entretien' => $d['date'] ?? null, 'synthese' => $d['synthese'] ?? null, 'sig_auditeur' => $d['sig_auditeur'] ?? null, 'sig_interlocuteur' => $d['sig_interlocuteur'] ?? null],
            'II'   => ['intitule' => $d['processus'] ?? 'Analyse des tâches', 'processus' => $d['processus'] ?? null, 'date_analyse' => $d['date'] ?? null, 'observations' => $d['observations'] ?? null, 'acteurs_json' => json_encode($d['acteurs'] ?? [])],
            'III'  => ['intitule' => $d['processus'] ?? 'Diagramme de flux', 'processus' => $d['processus'] ?? null, 'version' => $d['version'] ?? 'V1', 'description_narrative' => $d['description_narrative'] ?? null, 'synthese_validations' => $d['synthese_validations'] ?? null, 'activites_json' => json_encode($d['activites'] ?? [])],
            'IV'   => ['intitule' => $d['domaine'] ?? 'Approche processus', 'date_analyse' => $d['date'] ?? null],
            'V'    => ['intitule' => $d['transaction'] ?? 'Test de cheminement', 'processus_audite' => $d['processus'] ?? null, 'reference_transaction' => $d['reference'] ?? null, 'date_test' => $d['date_test'] ?? null, 'auditeur' => $d['auditeur'] ?? null, 'synthese_ecarts' => $d['synthese_ecarts'] ?? null, 'conclusion' => $d['conclusion'] ?? null],
            'VI'   => ['intitule' => $d['domaine'] ?? 'Hiérarchisation risques', 'perimetre' => $d['domaine'] ?? null, 'date_analyse' => $d['date'] ?? null, 'echelle' => $d['echelle'] ?? '3'],
            'VII'  => ['processus' => $d['processus'] ?? null, 'cadre_ref' => $d['cadre_ref'] ?? 'COSO', 'date' => $d['date'] ?? null, 'lignes' => json_encode($d['lignes'] ?? []), 'validation_diffuse_le' => $d['validation_diffuse_le'] ?? null, 'validation_reunion_le' => $d['validation_reunion_le'] ?? null, 'validation_valide_par' => $d['validation_valide_par'] ?? null, 'validation_date' => $d['validation_date'] ?? null, 'validation_commentaires' => $d['validation_commentaires'] ?? null],
            'VIII' => ['intitule' => $d['effet'] ?? 'Cause/Effet', 'effet_central' => $d['effet'] ?? null, 'description' => $d['description_effet'] ?? null, 'participants' => $d['participants'] ?? null, 'date_analyse' => $d['date'] ?? null, 'synthese' => $d['synthese'] ?? null],
            'IX'   => ['intitule' => $d['processus'] ?? 'QCI', 'processus' => $d['processus'] ?? null, 'cadre_reference' => $d['cadre_reference'] ?? 'COSO', 'date_qci' => $d['date'] ?? null, 'conclusion' => $d['conclusion'] ?? null],
            'X'    => ['intitule' => $d['sujet'] ?? 'Brainstorming', 'problematique' => $d['sujet'] ?? null, 'animateur' => $d['animateur'] ?? null, 'participants' => $d['participants'] ?? null, 'duree' => $d['duree'] ?? null, 'date_session' => $d['date'] ?? null, 'synthese' => $d['synthese'] ?? null],
            'XI'   => ['operation_testee' => $d['operation_testee'] ?? 'Piste audit', 'identifiant_unique' => $d['identifiant_unique'] ?? null, 'processus' => $d['processus'] ?? null, 'auditeur' => $d['auditeur'] ?? null, 'date_piste' => $d['date'] ?? null, 'conclusion' => $d['conclusion'] ?? null],
            'XII'  => ['date_envoi' => $d['date_envoi'] ?? null, 'date_limite' => $d['date_limite_reponse'] ?? null, 'adresse_reception' => $d['adresse_reception'] ?? null, 'auditeur_responsable' => $d['auditeur_responsable'] ?? null],
            'XIII' => ['auditeur' => $d['auditeur'] ?? null, 'source_donnees' => $d['source_donnees'] ?? null, 'date_procedure' => $d['date'] ?? null, 'periode' => $d['periode'] ?? null, 'conclusion' => $d['conclusion'] ?? null],
            'XIV'  => ['date_observation' => $d['date_observation'] ?? null, 'heure_debut' => $d['heure_debut'] ?? null, 'heure_fin' => $d['heure_fin'] ?? null, 'auditeur' => $d['auditeur'] ?? null, 'localisation' => $d['localisation'] ?? null, 'interlocuteurs_presents' => $d['interlocuteurs_presents'] ?? null, 'objectif_audit' => $d['objectif_audit'] ?? null, 'tache_local_observer' => $d['tache_local_observer'] ?? null, 'elements_verifier' => $d['elements_verifier'] ?? null, 'pieces_attendues' => $d['pieces_attendues'] ?? null, 'intitule_probleme' => $d['intitule_probleme'] ?? null, 'faits_constates' => $d['faits_constates'] ?? null, 'critere_referentiel' => $d['critere_referentiel'] ?? null, 'causes_json' => $d['causes_json'] ?? '[]', 'causes_autres' => $d['causes_autres'] ?? null, 'consequences_json' => $d['consequences_json'] ?? '[]', 'consequences_description' => $d['consequences_description'] ?? null, 'niveau_maitrise' => $d['niveau_controle'] ?? null, 'niveau_controle' => $d['niveau_controle'] ?? null, 'niveau_synthese' => $d['niveau_synthese'] ?? null, 'points_forts' => $d['points_forts'] ?? null, 'conclusion' => $d['conclusion'] ?? null],
            'XV'   => ['auditeur' => $d['auditeur'] ?? null, 'objectif_audit' => $d['objectif_audit'] ?? null, 'population_reference' => $d['population_reference'] ?? null, 'objet_test' => $d['objet_test'] ?? null, 'type_sondage' => $d['type_sondage'] ?? null, 'taille_population' => $d['taille_population'] ?? null, 'niveau_confiance' => $d['niveau_confiance'] ?? 95, 'coefficient_t' => $d['coefficient_t'] ?? null, 'erreur_max' => $d['erreur_max'] ?? null, 'ecart_type_exploratoire' => $d['ecart_type_exploratoire'] ?? null, 'taux_presence_exploratoire' => $d['taux_presence_exploratoire'] ?? null, 'taille_calculee' => $d['taille_calculee'] ?? null, 'taille_retenue' => $d['taille_retenue'] ?? null, 'intervalle_confiance' => $d['intervalle_confiance'] ?? null, 'conclusion' => $d['conclusion'] ?? null],
            default => [],
        };
    }

    private function insertOutilRecord(string $outilCode, string $table, array $data, array $meta): int
    {
        return DB::connection($this->conn)->table($table)->insertGetId(array_merge([
            'mission_id'     => $meta['mission_id'],
            'assignment_id'  => $meta['assignment_id'],
            'procedure_code' => $meta['procedure_code'],
            'test_ref'       => $meta['test_ref'],
            'obj_num'        => $meta['obj_num'] ?? null,
            'statut'         => 'draft',
            'created_by'     => $meta['created_by'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ], $this->mapOutilFields($outilCode, $data)));
    }

    private function syncChildren(string $outilCode, int $outilId, array $children): void
    {
        $orderCols = [
            'outil_entretien_questions'            => 'ordre',
            'outil_analyse_taches_lignes'          => 'ordre',
            'outil_approche_processus_lignes'      => 'ordre',
            'outil_test_cheminement_etapes'        => 'position',
            'outil_hierarchisation_risques_lignes' => 'ordre',
            'outil_cause_effet_causes'             => 'ordre',
            'outil_qci_sections'                   => 'ordre',
            'outil_qci_questions'                  => 'ordre',
            'outil_brainstorming_idees'            => 'ordre',
            'outil_piste_audit_etapes'             => 'position',
            'outil_piste_audit_resultats'          => 'id',
            'outil_circularisation_demandes'       => 'ordre',
            'outil_audit_analytique_lignes'        => 'ordre',
            'outil_audit_analytique_ecarts'        => 'id',
            'outil_observation_constats'           => 'ordre',
            'outil_observation_recommandations'    => 'ordre',
            'outil_echantillonnage_elements'       => 'ordre',
        ];

        foreach (self::OUTIL_CHILDREN[$outilCode] ?? [] as $def) {
            $table = $def['table']; $fk = $def['fk'];
            $rows  = $children[$table] ?? null;
            if ($rows === null) continue;
            DB::connection($this->conn)->table($table)->where($fk, $outilId)->delete();
            foreach ($rows as $idx => $row) {
                if (!is_array($row)) continue;
                $row             = $this->normalizeChildRow($outilCode, $table, $row);
                $row[$fk]        = $outilId;
                $orderCol        = $orderCols[$table] ?? 'ordre';
                $row[$orderCol]  = $row[$orderCol] ?? ($idx + 1);
                $row['created_at'] = now(); $row['updated_at'] = now();
                unset($row['id'], $row['_id']);
                try { DB::connection($this->conn)->table($table)->insert($row); }
                catch (\Exception $e) { Log::error("[FT syncChildren] {$outilCode}/{$table}: " . $e->getMessage()); throw $e; }
            }
        }
    }

    private function normalizeChildRow(string $outilCode, string $table, array $row): array
    {
        return match ($table) {
            'outil_observation_constats'           => ['element_observe' => $row['element_observe'] ?? '', 'conforme_referentiel' => strtolower($row['conforme_referentiel'] ?? ''), 'conforme' => '', 'ecart_constate' => $row['ecart_constate'] ?? null, 'risque_associe' => $row['risque_associe'] ?? null, 'preuve' => $row['preuve'] ?? null],
            'outil_observation_recommandations'    => ['recommandation' => $row['recommandation'] ?? null, 'responsable' => $row['responsable'] ?? null, 'date_prevue' => $row['date_prevue'] ?? null, 'livrable' => $row['livrable'] ?? null, 'commentaire_auditeur' => $row['commentaire_auditeur'] ?? null, 'commentaire_audite' => $row['commentaire_audite'] ?? null],
            'outil_entretien_questions'            => ['type' => $row['type'] ?? 'Ouverte', 'libelle' => $row['question'] ?? ($row['libelle'] ?? ''), 'reponse' => $row['reponse'] ?? null, 'note' => $row['note'] ?? null],
            'outil_test_cheminement_etapes'        => ['position' => $row['position'] ?? 1, 'label' => $row['label'] ?? null, 'document_piece' => $row['document'] ?? ($row['document_piece'] ?? null), 'identifiant' => $row['identifiant'] ?? null, 'date_etape' => $row['date'] ?? ($row['date_etape'] ?? null), 'acteur' => $row['acteur'] ?? null, 'lien_etape_precedente' => $row['lien_precedent'] ?? ($row['lien_etape_precedente'] ?? null), 'present' => strtolower($row['present'] ?? ''), 'controle_applique' => $row['controle'] ?? ($row['controle_applique'] ?? ''), 'conforme_procedure' => $row['conforme'] ?? ($row['conforme_procedure'] ?? ''), 'observation_ecart' => $row['observation'] ?? ($row['observation_ecart'] ?? null), 'preuve_collectee' => $row['preuve'] ?? ($row['preuve_collectee'] ?? null)],
            'outil_hierarchisation_risques_lignes' => ['libelle' => $row['risque'] ?? ($row['libelle'] ?? ''), 'categorie' => $row['type'] ?? ($row['categorie'] ?? null), 'causes' => $row['causes'] ?? null, 'consequences' => $row['consequences'] ?? null, 'probabilite' => (int)($row['probabilite'] ?? 1), 'impact' => (int)($row['impact'] ?? 1), 'traitement' => $row['traitement'] ?? null, 'responsable' => $row['responsable'] ?? null, 'from_mission' => (int)($row['from_mission'] ?? 0), 'risk_id' => $row['risk_id'] ?? null],
            'outil_cause_effet_causes'             => ['categorie' => $row['categorie'] ?? '', 'libelle' => $row['cause_primaire'] ?? ($row['libelle'] ?? ''), 'sous_cause' => $row['cause_secondaire'] ?? ($row['sous_cause'] ?? null), 'detail_preuve' => $row['detail_preuve'] ?? null, 'importance' => [1=>'majeure',2=>'mineure',3=>'potentielle'][(int)($row['priorite']??0)]??'mineure', 'priorite' => (int)($row['priorite']??0), 'action_corrective' => $row['action_corrective'] ?? null],
            'outil_qci_questions'                  => ['section_id' => $row['section_id'] ?? 0, 'libelle' => $row['question'] ?? ($row['libelle'] ?? ''), 'reponse' => $row['reponse'] ?? '', 'commentaire' => $row['constat'] ?? ($row['commentaire'] ?? null), 'risque_si_non' => $row['risque_si_non'] ?? null, 'niveau_risque' => ['critique'=>'Fort','eleve'=>'Fort','modere'=>'Moyen','faible'=>'Faible'][$row['impact']??'']??($row['niveau_risque']??''), 'impact' => $row['impact'] ?? null],
            'outil_brainstorming_idees'            => ['libelle' => $row['idee'] ?? ($row['libelle'] ?? ''), 'emis_par' => $row['emise_par'] ?? ($row['emis_par'] ?? null), 'theme' => $row['theme'] ?? null, 'categorie' => $row['categorie'] ?? null, 'votes' => (int)($row['votes']??0), 'retenue' => (int)($row['retenue']??0), 'a_approfondir' => (int)($row['a_approfondir']??0), 'priorite' => (int)($row['priorite']??0), 'description_approfondie' => $row['description_approfondie'] ?? null, 'faisabilite' => $row['faisabilite'] ?? ''],
            'outil_circularisation_demandes'       => (function() use ($row) { $s=['CONFORME'=>'ok','ÉCART'=>'ecart','SANS_RÉPONSE'=>'sans_reponse',''=>'en_attente']; $f=fn($v)=>($v!==null&&$v!=='')?(float)str_replace([' ',','],['',' .'],$v):null; return ['nom_tiers'=>$row['tiers']??'','element_confirmer'=>$row['element_confirmer']??null,'date_envoi_demande'=>$row['date_envoi']??null,'date_reponse'=>$row['date_reponse']??null,'montant_envoye'=>$f($row['montant_periode']??null),'montant_confirme'=>$f($row['montant_confirme']??null),'ecart'=>$f($row['ecart']??null),'statut_reponse'=>$s[$row['statut']??'']??'en_attente','observation'=>$row['observation']??null,'niveau_fiabilite'=>(int)($row['niveau_fiabilite']??3)]; })(),
            'outil_audit_analytique_lignes'        => (function() use ($row) { $d=fn($v)=>($v!==null&&$v!=='')?(float)str_replace([' ',','],['',' .'],(string)$v):null; return ['indicateur'=>$row['indicateur']??'','description'=>$row['description']??null,'valeur_n1'=>$d($row['valeur_n1']??$row['valeur_n-1']??null),'valeur_n'=>$d($row['valeur_n']??null),'valeur_budget'=>$d($row['budget_prevu']??$row['valeur_budget']??null),'ecart_n_n1'=>$d($row['ecart_n_n1']??null),'ecart_pct_n_n1'=>$d($row['ecart_pct_n_n1']??null),'ecart_n_budg'=>$d($row['ecart_n_budget']??$row['ecart_n_budg']??null),'ecart_pct_budg'=>$d($row['ecart_pct_budg']??null)]; })(),
            'outil_analyse_taches_lignes'          => ['libelle'=>$row['libelle']??($row['tache']??''),'acteur'=>$row['acteur']??null,'frequence'=>$row['frequence']??null,'risque'=>$row['risque']??null,'controle'=>$row['controle']??null,'observation'=>$row['observation']??null,'roles_json'=>isset($row['roles'])?json_encode($row['roles']):($row['roles_json']??null)],
            'outil_approche_processus_lignes'      => ['type'=>$row['type_processus']??($row['type']??''),'libelle'=>$row['nom']??($row['libelle']??''),'finalite'=>$row['finalite']??null,'elements_entrants'=>$row['entrants']??($row['elements_entrants']??null),'elements_sortants'=>$row['sortants']??($row['elements_sortants']??null),'activites_princ'=>$row['activites']??($row['activites_princ']??null),'clients'=>$row['clients']??null,'fournisseurs'=>$row['fournisseurs']??null],
            'outil_echantillonnage_elements'       => ['reference'=>$row['reference']??'','valeur'=>is_numeric($row['valeur']??null)?(float)$row['valeur']:null,'attribut_present'=>strtolower($row['attribut_present']??''),'anomalie_detectee'=>strtolower($row['anomalie_detectee']??'non'),'nature_anomalie'=>$row['nature_anomalie']??null],
            'outil_piste_audit_etapes'             => ['position'=>$row['position']??1,'label'=>$row['label']??null,'document_piece'=>$row['document']??($row['document_piece']??null),'identifiant'=>$row['identifiant']??null,'date_etape'=>$row['date']??($row['date_etape']??null),'acteur'=>$row['acteur']??null,'lien_etape_precedente'=>$row['lien_precedent']??($row['lien_etape_precedente']??null),'present'=>strtolower($row['present']??'')],
            default => $row,
        };
    }

    private function supprimerOutilBD(string $outilCode, int $outilId): void
    {
        foreach (self::OUTIL_CHILDREN[$outilCode] ?? [] as $def) {
            DB::connection($this->conn)->table($def['table'])->where($def['fk'], $outilId)->delete();
        }
        if (isset(self::OUTIL_TABLES[$outilCode])) {
            DB::connection($this->conn)->table(self::OUTIL_TABLES[$outilCode])->where('id', $outilId)->delete();
        }
    }

    private function autoSaveSnapshot(string $code, string $table, int $outilId, int $ficheTestId, string $procedureCode, array $data, int $userId): void
    {
        try {
            DB::connection($this->conn)->table('outil_auto_saves')->insert([
                'outil_code' => $code, 'outil_table' => $table, 'outil_id' => $outilId,
                'fiche_test_id' => $ficheTestId, 'procedure_code' => $procedureCode,
                'snapshot' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'saved_by' => $userId, 'saved_at' => now(),
            ]);
        } catch (\Exception) { /* non bloquant */ }
    }

    private function migrerOutilsLegacy(int $ficheTestId, int $missionId, int $assignmentId): void
    {
        try {
            $row = DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->first();
            if (!$row) return;
            $outilsData = $this->decodeArr($row->outils_data ?? null);
            if (empty($outilsData)) {
                DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->update(['outils_migrated' => 1, 'updated_at' => now()]);
                return;
            }
            foreach ($outilsData as $item) {
                $code = $item['_code'] ?? null; $key = $item['_key'] ?? null;
                if (!$code || !$key || !isset(self::OUTIL_TABLES[$code])) continue;
                $parts = explode('::', $key);
                $procedureCode = $parts[1] ?? ''; $testRef = isset($parts[2]) ? ($parts[1] . '::' . $parts[2]) : ($parts[1] ?? '');
                if (DB::connection($this->conn)->table('fiche_test_outils')->where('fiche_test_id', $ficheTestId)->where('outil_code', $code)->where('test_ref', $testRef)->exists()) continue;
                $mainTable = self::OUTIL_TABLES[$code];
                $outilId   = $this->insertOutilRecord($code, $mainTable, $item, ['mission_id' => $missionId, 'assignment_id' => $assignmentId, 'procedure_code' => $procedureCode, 'test_ref' => $testRef, 'obj_num' => $parts[1] ?? null, 'created_by' => $row->auditeur_id]);
                DB::connection($this->conn)->table('fiche_test_outils')->insert(['fiche_test_id' => $ficheTestId, 'mission_id' => $missionId, 'assignment_id' => $assignmentId, 'procedure_code' => $procedureCode, 'test_ref' => $testRef, 'obj_num' => $parts[1] ?? null, 'proc_idx' => 0, 'outil_code' => $code, 'outil_table' => $mainTable, 'outil_id' => $outilId, 'version' => 1, 'is_current' => 1, 'created_by' => $row->auditeur_id, 'created_at' => now(), 'updated_at' => now()]);
                $childData = [];
                foreach (self::OUTIL_CHILDREN[$code] ?? [] as $def) { if (isset($item[$def['table']]) && is_array($item[$def['table']])) $childData[$def['table']] = $item[$def['table']]; }
                if ($outilId && !empty($childData)) $this->syncChildren($code, $outilId, $childData);
            }
            DB::connection($this->conn)->table($this->table)->where('id', $ficheTestId)->update(['outils_migrated' => 1, 'updated_at' => now()]);
        } catch (\Exception $e) { Log::warning('[FT migrerLegacy]: ' . $e->getMessage()); }
    }

    // ════════════════════════════════════════════════════════════
    // DONNÉES MÉTIER
    // ════════════════════════════════════════════════════════════
    private function chargerTestsAuditeur(int $missionId, int $assignmentId, string $auditeurNom, int $auditorId): array
    {
        foreach (self::PROGRAMMES as $prog) {
            $row = DB::connection($this->conn)->table($prog['table'])->where('assignment_id', $assignmentId)->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")->orderByDesc('updated_at')->first();
            if (!$row) $row = DB::connection($this->conn)->table($prog['table'])->where('mission_id', $missionId)->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")->orderByDesc('updated_at')->first();
            if (!$row) continue;
            $lignes = $this->decodeArr($row->lignes ?? null);
            $mesObjectifs = [];
            foreach ($lignes as $obj) {
                $testsAffecter = [];
                foreach ($obj['tests'] ?? [] as $test) {
                    $at = trim($test['auditeur'] ?? '');
                    if ($at !== '' && (strtolower($at) === strtolower($auditeurNom) || str_contains(strtolower($at), strtolower(explode(' ', $auditeurNom)[0] ?? '')) || str_contains(strtolower($auditeurNom), strtolower(explode(' ', $at)[0] ?? '')))) $testsAffecter[] = $test;
                }
                if (!empty($testsAffecter)) $mesObjectifs[] = array_merge($obj, ['tests' => $testsAffecter]);
            }
            return ['found' => true, 'programme_code' => $prog['code'], 'programme_label' => $prog['label'], 'programme_status' => $row->validation_status, 'objectifs' => $mesObjectifs, 'total_objectifs' => count($mesObjectifs), 'total_tests' => array_sum(array_map(fn($o) => count($o['tests'] ?? []), $mesObjectifs)), 'tous_tests' => array_sum(array_map(fn($o) => count($o['tests'] ?? []), $lignes))];
        }
        return ['found' => false, 'programme_code' => null, 'programme_label' => null, 'objectifs' => [], 'total_objectifs' => 0, 'total_tests' => 0];
    }

    private function chargerDonneesOutilVI(int $realMissionId): array
    {
        $cssToHex = static fn(string $css): string => match(trim(strtolower($css))) {
            'success'=>'#28a745', 'danger'=>'#dc3545', 'warning'=>'#ffc107', 'info'=>'#17a2b8',
            'primary'=>'#0d6efd', 'secondary'=>'#6c757d', 'dark'=>'#343a40', 'light'=>'#f8f9fa',
            default=>str_starts_with($css,'#')?$css:'#6c757d'
        };
        $riskIds = DB::connection($this->conn)->table('mission_risk')->where('mission_id', $realMissionId)->pluck('risk_id')->toArray();
        if (empty($riskIds)) return ['processus' => [], 'risques' => []];
        $risks = DB::connection($this->conn)->table('risks as r')
            ->leftJoin('risk_frequency_levels as rfl', fn($j) => $j->on('rfl.id','=','r.frequency_level_id')->whereNull('rfl.deleted_at'))
            ->leftJoin('risk_impact_levels as ril',    fn($j) => $j->on('ril.id','=','r.impact_level_id')->whereNull('ril.deleted_at'))
            ->leftJoin('risk_types as rt',             fn($j) => $j->on('rt.id','=','r.risk_type_id')->whereNull('rt.deleted_at'))
            ->leftJoin('processes as p','p.id','=','r.process_id')
            ->leftJoin('activities as a','a.id','=','r.activity_id')
            ->whereIn('r.id',$riskIds)->whereNull('r.deleted_at')
            ->select('r.id','r.code','r.label','r.description','r.process_id','r.activity_id','r.criticality',
                DB::raw('rfl.level AS frequency_level'), DB::raw('rfl.label AS frequency_label'), DB::raw('rfl.color AS frequency_color'),
                DB::raw('ril.level AS impact_level'), DB::raw('ril.label AS impact_label'), DB::raw('ril.color AS impact_color'),
                DB::raw('rt.label AS risk_type_label'), DB::raw('rt.color AS risk_type_color'),
                DB::raw('p.name AS process_name'), DB::raw('p.code AS process_code'), DB::raw('a.name AS activity_name'))
            ->orderByDesc('r.criticality')
            ->get()->map(function($r) use ($cssToHex) {
                $fl=(int)($r->frequency_level??0); $il=(int)($r->impact_level??0);
                return [
                    'id'=>$r->id, 'code'=>$r->code, 'label'=>$r->label, 'description'=>$r->description,
                    'criticality'=>($fl&&$il)?($fl*$il):(int)($r->criticality??0),
                    'frequency_level'=>$fl, 'frequency_label'=>$r->frequency_label??'—', 'frequency_color'=>$cssToHex($r->frequency_color??'secondary'),
                    'impact_level'=>$il,   'impact_label'=>$r->impact_label??'—',       'impact_color'=>$cssToHex($r->impact_color??'secondary'),
                    'risk_type_label'=>$r->risk_type_label??'—', 'process_name'=>$r->process_name??'—',
                    'process_code'=>$r->process_code??'—', 'activity_name'=>$r->activity_name??'—'
                ];
            })->toArray();
        return ['processus' => [], 'risques' => $risks];
    }

    private function chargerLignesRCIpourVII(int $missionId, int $assignmentId): array
    {
        try {
            $rciForm = DB::connection($this->conn)->table('mission_phase_ref_ci')
                ->when($assignmentId, fn($q) => $q->where('assignment_id', $assignmentId))
                ->when(!$assignmentId && $missionId, fn($q) => $q->where('mission_id', $missionId))
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")->orderByDesc('updated_at')->first();
            if (!$rciForm && $missionId) {
                $rciForm = DB::connection($this->conn)->table('mission_phase_ref_ci')
                    ->where('mission_id', $missionId)
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")->orderByDesc('updated_at')->first();
            }
            if (!$rciForm) return [];
            $lignes = $this->decodeArr($rciForm->criteres ?? null);
            if (empty($lignes)) return [];
            return array_map(fn(array $l) => [
                'objectif_processus' => $l['objectif_operationnel'] ?? $l['objectif_strategique'] ?? '',
                'risque_associe'     => $l['risque_libelle'] ?? '',
                'controle_cle'       => $l['description_controle'] ?? '',
                'type_controle'      => $l['type_controle'] ?? '',
                'composante_coso'    => $l['composante_coso'] ?? 'Act.Ctrl',
                'conception'         => '',
                'a_tester'           => 'Oui',
                'objectif_audit'     => $l['objectif_operationnel'] ?? '',
                '_from_rci'          => true,
                '_risque_code'       => $l['risque_code'] ?? '',
                '_criticite'         => (float)($l['criticite_residuelle'] ?? 0),
                '_responsable'       => $l['responsable_controle'] ?? '',
                '_process_code'      => $l['process_code'] ?? '',
            ], $lignes);
        } catch (\Exception $e) { Log::warning('[FT-VII] chargerLignesRCI: '.$e->getMessage()); return []; }
    }

    // ════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ════════════════════════════════════════════════════════════
    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        return array_merge((array) $row, [
            'resultats'     => $this->decodeArr($row->resultats     ?? null),
            'constats'      => $this->decodeArr($row->constats      ?? null),
            'outils_data'   => $this->decodeArr($row->outils_data   ?? null),
            'media_items'   => $this->decodeArr($row->media_items   ?? null),
            'synthese_data' => $this->decodeArr($row->synthese_data ?? null),
        ]);
    }

    private function decodeArr(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }

    private function toJson(mixed $v): string
    {
        if (is_string($v)) { json_decode($v); if (json_last_error() === JSON_ERROR_NONE) return $v; }
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }

    protected function genCode(int $missionId): string
    {
        $max = DB::connection($this->conn)->table($this->table)
            ->where('mission_id', $missionId)
            ->max(DB::raw('CAST(SUBSTRING_INDEX(code, \'-\', -1) AS UNSIGNED)')) ?? 0;
        return $this->codePrefix . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    protected function canEdit(object $row, string $role): bool
    {
        if ($row->validation_status === 'validated') return false;
        if ($row->validation_status === 'in_review' && !in_array($role, ['DM', 'CM'])) return false;
        return true;
    }

    protected function log(int $assignmentId, int $auditorId, string $role, string $action, ?string $oldStatus, ?string $newStatus, ?string $note = null): void
    {
        DB::connection($this->conn)->table('mission_phase_validation_log')->insert([
            'assignment_id' => $assignmentId,
            'actor_id'      => $auditorId,
            'actor_role'    => $role,
            'action'        => $action,
            'old_status'    => $oldStatus,
            'new_status'    => $newStatus,
            'note'          => $note,
            'created_at'    => now(),
        ]);
    }
}