<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * RÉFÉRENTIEL DE CONTRÔLE INTERNE (RCI)
 * ════════════════════════════════════════════════════════════════════════
 *
 * Format Excel « RCI_Audit_Interne.xlsx » — 13 colonnes :
 *   A  N°                       (auto)
 *   B  Processus                → processes.name
 *   C  Activité                 → activities.name
 *   D  Objectif Stratégique     → mission_phase_ap.processus[].objectif_strategique
 *   E  Objectif Opérationnel    → mission_phase_ap.processus[].objectif_operationnel
 *   F  Risque Identifié         → risks.label  (via risk_id du JSON AR)
 *   G  Criticité Résiduelle     → impact_net × frequency_net  (ou glob_resid)
 *   H  Description du Contrôle  → risks.control_procedure nettoyé
 *   I  Type de Contrôle         → r.nature (RM→Préventif, RF→Détectif…)
 *   J  Preuve du Contrôle       → r.qualif_controle
 *   K  Fréquence Contrôle       → risk_controls.frequency (si dispo)
 *   L  Responsable du Contrôle  → functions.name via risks.owner_function_id
 *   M  Propriétaire Processus   → function_assignments (entity_id de la mission)
 *                                  + mission_phase_ap.processus[].proprietaire
 *
 * Logique propriétaire processus :
 *   1. mission_phase_ar → process_assignments JSON {"P02R": 7} → auditeur_id → auditors.full_name
 *   2. mission_phase_ap.processus[].proprietaire (texte libre saisi)
 *   3. function_assignments WHERE entity_id = (entité de l'assignment)
 *      → liste des fonctions de l'entité auditée → champ de sélection dans la vue
 *
 * Table BD : mission_phase_ref_ci
 *   criteres JSON | sources_normatives JSON | synthese | fait_par | revue_par
 * ════════════════════════════════════════════════════════════════════════
 */
class ReferentielCiController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ref_ci';
    protected string $formCode    = 'referentiel-ci';
    protected string $codePrefix  = 'RCI';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ReferentielCi';
    protected string $routeEdit   = 'auditor.ac.referentiel-ci.edit';

    protected array $validationRules = [
        'fait_par'  => 'nullable|string|max:255',
        'revue_par' => 'nullable|string|max:255',
    ];

    private const TYPES_CONTROLE = [
        'RM' => 'Préventif',
        'RF' => 'Détectif',
        'RC' => 'Correctif',
        'RD' => 'Directif',
    ];

    private const FREQUENCES = [
        'Continu', 'Quotidien', 'Hebdomadaire', 'Mensuel',
        'Trimestriel', 'Semestriel', 'Annuel',
    ];

    private const FREQUENCES_MAP = [
        'daily'     => 'Quotidien',
        'weekly'    => 'Hebdomadaire',
        'monthly'   => 'Mensuel',
        'quarterly' => 'Trimestriel',
        'yearly'    => 'Annuel',
    ];

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
    // formData
    // ══════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'fait_par'  => $request->input('fait_par'),
            'revue_par' => $request->input('revue_par'),
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

        // Auditeurs de la phase
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
                    'DM' => 'Directeur de Mission', 'CM' => 'Chef de Mission',
                    'AS' => 'Auditeur Senior',      'AJ' => 'Auditeur Junior',
                    default => $a->role_code ?? '—',
                },
            ])->toArray();

        // Formulaire hydraté
        $formData = null;
        if ($form) {
            $formData = array_merge((array) $form, [
                'criteres'           => $this->decodeArr($form->criteres),
                'sources_normatives' => $this->decodeArr($form->sources_normatives),
            ]);
        }

        // Données BD
        $donneesDB = $this->loadRciDepuisAr($missionId, $assignmentId);

        // Fonctions de l'entité (pour la sélection du propriétaire dans la vue)
        $fonctionsEntite = $this->loadFonctionsEntite($assignmentId);

        $rciList = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'validation_status', 'fait_par', 'updated_at'])
            ->orderByDesc('created_at')->get()->toArray();

        $formId = $form?->id ?? null;
        $base   = url('/m/audit.core/ac/preparation/referentiel-ci');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'            => $formData,
                'phaseAuditeurs'  => $phaseAuditeurs,
                'rciList'         => $rciList,
                'donneesDB'       => $donneesDB,
                'fonctionsEntite' => $fonctionsEntite,
                'typesControle'   => array_values(self::TYPES_CONTROLE),
                'frequences'      => self::FREQUENCES,
                'currentAuditor'  => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'      => $base,
                'urlStore'     => route('auditor.ac.referentiel-ci.store'),
                'urlUpdate'    => $formId ? route('auditor.ac.referentiel-ci.update',    $formId) : null,
                'urlSoumettre' => $formId ? route('auditor.ac.referentiel-ci.soumettre', $formId) : null,
                'urlValider'   => $formId ? route('auditor.ac.referentiel-ci.valider',   $formId) : null,
                'urlIndex'     => route('audit.ac.preparation.referentiel-ci'),
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

            $existing = DB::connection('tenant')
                ->table($this->table)->where('assignment_id', $assignmentId)->first();

            if ($existing) {
                return redirect()->route($this->routeEdit, $existing->id)
                    ->with('mission_id', $missionId)->with('assignment_id', $assignmentId);
            }

            return \Inertia\Inertia::render(
                $this->inertiaPage,
                $this->buildPayload($missionId, $assignmentId, $auditor, null)
            );
        } catch (\Exception $e) {
            Log::error('[RCI] index: ' . $e->getMessage());
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
            Log::error('[RCI] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // loadFonctionsEntite
    // Fonctions affectées à l'entité auditée (via function_assignments)
    // C'est la liste de sélection pour le propriétaire de processus
    // ══════════════════════════════════════════════════════════════
    private function loadFonctionsEntite(int $assignmentId): array
    {
        try {
            // Récupérer l'entity_id de l'assignment
            $assignment = DB::connection('tenant')
                ->table('mission_phase_assignments')
                ->where('id', $assignmentId)
                ->select('entity_id', 'mission_programmation_id')
                ->first();

            // Si pas d'entity_id direct, chercher via mission_programmation_entity
            $entityIds = [];
            if ($assignment?->entity_id) {
                $entityIds[] = (int) $assignment->entity_id;
            } else if ($assignment?->mission_programmation_id) {
                $rows = DB::connection('tenant')
                    ->table('mission_programmation_entity')
                    ->where('mission_programmation_id', $assignment->mission_programmation_id)
                    ->pluck('entity_id')
                    ->toArray();
                $entityIds = array_map('intval', $rows);
            }

            if (empty($entityIds)) {
                // Fallback : retourner toutes les fonctions
                return DB::connection('tenant')
                    ->table('functions')
                    ->select('id', 'name', 'character')
                    ->orderBy('name')
                    ->get()
                    ->map(fn($f) => [
                        'id'        => $f->id,
                        'name'      => $f->name,
                        'character' => $f->character,
                        'entity_id' => null,
                    ])->toArray();
            }

            // function_assignments : entity_id → function_id → functions.name
            // Représente les fonctions/postes existant dans chaque entité auditée
            $fonctions = DB::connection('tenant')
                ->table('function_assignments as fa')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->whereIn('fa.entity_id', $entityIds)
                ->select(
                    'f.id', 'f.name', 'f.character',
                    'fa.entity_id', 'fa.user_id'
                )
                ->orderBy('fa.entity_id')
                ->orderBy('f.name')
                ->get()
                ->map(fn($f) => [
                    'id'        => $f->id,
                    'name'      => $f->name,
                    'character' => $f->character,
                    'entity_id' => $f->entity_id,
                    'user_id'   => $f->user_id,
                ])->toArray();

            Log::info("[RCI] fonctionsEntite : " . count($fonctions) . " fonctions pour entities=" . implode(',', $entityIds));
            return $fonctions;

        } catch (\Exception $e) {
            Log::warning('[RCI] loadFonctionsEntite: ' . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════
    // loadRciDepuisAr — génère les lignes RCI depuis la BD
    // IMPORTANT : utiliser ->get($key) sur les Collections keyBy()
    // ══════════════════════════════════════════════════════════════
    private function loadRciDepuisAr(int $missionId, int $assignmentId): array
    {
        Log::info("[RCI] ── loadRciDepuisAr START missionId={$missionId} assignmentId={$assignmentId}");
        $lignes = [];

        try {
            // ── Référentiels (Collections — accès via ->get(), PAS []) ──
            $risksRef = DB::connection('tenant')
                ->table('risks')
                ->select('id', 'code', 'label', 'owner', 'control_procedure',
                         'owner_function_id', 'process_id', 'activity_id')
                ->get()->keyBy('id');

            $processesRef = DB::connection('tenant')
                ->table('processes')
                ->select('id', 'code', 'name')
                ->get()->keyBy('code');

            $activitiesById = DB::connection('tenant')
                ->table('activities')->select('id', 'code', 'name')->get()->keyBy('id');

            $activitiesByCode = DB::connection('tenant')
                ->table('activities')->select('id', 'code', 'name')->get()->keyBy('code');

            $functionsRef = DB::connection('tenant')
                ->table('functions')->select('id', 'name', 'character')->get()->keyBy('id');

            $auditorsRef = DB::connection('tenant')
                ->table('auditors')
                ->select('id', 'audit_code', 'last_name', 'first_name',
                         DB::raw("TRIM(CONCAT(COALESCE(last_name,''), ' ', COALESCE(first_name,''))) as full_name"))
                ->get()->keyBy('id');

            $riskControls = DB::connection('tenant')
                ->table('risk_controls')
                ->whereNull('deleted_at')
                ->select('risk_id', 'description', 'type', 'frequency', 'evidence', 'owner', 'function_id')
                ->get()->groupBy('risk_id');

            // ── Entité de l'assignment → fonctions propriétaires ──
            $assignment = DB::connection('tenant')
                ->table('mission_phase_assignments')
                ->where('id', $assignmentId)
                ->select('entity_id', 'mission_programmation_id')
                ->first();

            // Récupérer entity_ids
            $entityIds = [];
            if ($assignment?->entity_id) {
                $entityIds[] = (int) $assignment->entity_id;
            } elseif ($assignment?->mission_programmation_id) {
                $entityIds = DB::connection('tenant')
                    ->table('mission_programmation_entity')
                    ->where('mission_programmation_id', $assignment->mission_programmation_id)
                    ->pluck('entity_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();
            }

            // Fonctions affectées à l'entité : entity_id + function_id → functions.name
            // Représente les postes/fonctions réels de l'entité auditée
            $fnByEntityFn = [];
            if (!empty($entityIds)) {
                $fnAssignments = DB::connection('tenant')
                    ->table('function_assignments as fa')
                    ->join('functions as f', 'f.id', '=', 'fa.function_id')
                    ->whereIn('fa.entity_id', $entityIds)
                    ->select('fa.entity_id', 'fa.function_id', 'f.name as fn_name', 'f.character')
                    ->get();

                foreach ($fnAssignments as $fa) {
                    $fnByEntityFn[$fa->entity_id][$fa->function_id] = $fa->fn_name . ' (' . $fa->character . ')';
                }
            }

            // Première entité auditée (pour le propriétaire par défaut)
            $primaryEntityId = $entityIds[0] ?? null;

            // ── SOURCE 1 : mission_phase_ar.risques ──────────────
            $arRow = DB::connection('tenant')
                ->table('mission_phase_ar')
                ->where('assignment_id', $assignmentId)
                ->select('id', 'risques', 'process_assignments')
                ->first();

            if (!$arRow) {
                $arRow = DB::connection('tenant')
                    ->table('mission_phase_ar')
                    ->where('mission_id', $missionId)
                    ->select('id', 'risques', 'process_assignments')
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')
                    ->first();
                Log::info("[RCI] fallback AR par mission_id={$missionId} → " . ($arRow ? "id={$arRow->id}" : 'INTROUVABLE'));
            }

            // process_assignments JSON → {"P02R": auditor_id}
            $processAssignments = $arRow
                ? $this->decodeMap($arRow->process_assignments)
                : [];

            // ── SOURCE 2 : mission_phase_ap.processus ────────────
            $apForms = DB::connection('tenant')
                ->table('mission_phase_ap')
                ->where('assignment_id', $assignmentId)
                ->select('id', 'processus')->get();

            if ($apForms->isEmpty()) {
                $apForms = DB::connection('tenant')
                    ->table('mission_phase_ap')
                    ->where('mission_id', $missionId)
                    ->select('id', 'processus')->get();
            }

            $apByCode = [];
            foreach ($apForms as $apForm) {
                foreach ($this->decodeArr($apForm->processus) as $proc) {
                    $code = $proc['process_code'] ?? ($proc['code'] ?? null);
                    if ($code) $apByCode[$code] = $proc;
                }
            }

            // ── Traitement des risques AR ─────────────────────────
            if ($arRow) {
                $savedRisks = $this->decodeArr($arRow->risques);
                Log::info("[RCI] AR id={$arRow->id} : " . count($savedRisks) . " risques");

                $num = 1;
                foreach ($savedRisks as $r) {
                    $isRetenu = (bool)($r['choix'] ?? false);
                    $riskCode = $r['risk_code'] ?? ($r['code'] ?? '?');
                    $riskId   = (int)($r['risk_id'] ?? ($r['id'] ?? 0));
                    $procCode = $r['process_code'] ?? '';
                    $actCode  = $r['activity_code'] ?? '';
                    $actId    = (int)($r['activity_id'] ?? 0);

                    // Impact / Fréquence / Criticité
                    $impactNet = (float)($r['impact_net']    ?? 0);
                    $freqNet   = (float)($r['frequency_net'] ?? 0);
                    $criticite = $impactNet * $freqNet;
                    if (isset($r['glob_resid']) && (float)$r['glob_resid'] > 0) {
                        $criticite = (float) $r['glob_resid'];
                    }

                    // Référentiels via ->get() (Collection keyBy)
                    $riskRef    = $risksRef->get($riskId);
                    $processObj = $processesRef->get($procCode);
                    $actObj     = $actId > 0
                        ? $activitiesById->get($actId)
                        : $activitiesByCode->get($actCode);

                    $risqueLibelle = $riskRef?->label
                        ?? $r['label'] ?? $r['risk_label']
                        ?? "Risque {$riskCode}";
                    $processNom  = $processObj?->name ?? $procCode;
                    $activityNom = $actObj?->name ?? $r['activity_name'] ?? $actCode;

                    // Description du contrôle
                    $riskCtrlGroup = $riskControls->get($riskId);
                    $riskCtrl      = $riskCtrlGroup?->first();
                    $descCtrl      = '';
                    if ($riskCtrl && !empty($riskCtrl->description)) {
                        $descCtrl = $riskCtrl->description;
                    } elseif (!empty($riskRef?->control_procedure)) {
                        $descCtrl = $this->nettoyerProcedure($riskRef->control_procedure);
                    } elseif (!empty($r['control_procedure'])) {
                        $descCtrl = $this->nettoyerProcedure($r['control_procedure']);
                    }

                    // Type de contrôle
                    $nature   = $r['nature'] ?? ($riskCtrl?->type ?? '');
                    $typeCtrl = self::TYPES_CONTROLE[$nature] ?? $nature;

                    // Preuve
                    $preuve = $r['qualif_controle'] ?? ($riskCtrl?->evidence ?? '');

                    // Fréquence
                    $freqRaw   = $riskCtrl?->frequency ?? '';
                    $frequence = self::FREQUENCES_MAP[$freqRaw] ?? ($freqRaw ?: '');

                    // Responsable du contrôle
                    // Priorité : functions.name (owner_function_id de l'entité) > risks.owner > risk_controls.owner
                    $respCtrl    = '';
                    $ownerFnId   = $riskRef?->owner_function_id;

                    if ($ownerFnId) {
                        // Vérifier que cette fonction est bien affectée à l'entité auditée
                        $fnNom = null;
                        if ($primaryEntityId && isset($fnByEntityFn[$primaryEntityId][$ownerFnId])) {
                            $fnNom = $fnByEntityFn[$primaryEntityId][$ownerFnId];
                        } else {
                            // Fallback sur toutes les entités
                            foreach ($fnByEntityFn as $entFns) {
                                if (isset($entFns[$ownerFnId])) { $fnNom = $entFns[$ownerFnId]; break; }
                            }
                        }
                        $respCtrl = $fnNom ?? ($functionsRef->get($ownerFnId)?->name ?? '');
                    }

                    if (empty($respCtrl) && !empty($riskRef?->owner)) {
                        $respCtrl = $riskRef->owner;
                    }
                    if (empty($respCtrl) && $riskCtrl) {
                        if (!empty($riskCtrl->owner)) {
                            $respCtrl = $riskCtrl->owner;
                        } elseif ($riskCtrl->function_id) {
                            $fnId2 = $riskCtrl->function_id;
                            $fnNom2 = null;
                            if ($primaryEntityId && isset($fnByEntityFn[$primaryEntityId][$fnId2])) {
                                $fnNom2 = $fnByEntityFn[$primaryEntityId][$fnId2];
                            } else {
                                $fnNom2 = $functionsRef->get($fnId2)?->name;
                            }
                            $respCtrl = $fnNom2 ?? '';
                        }
                    }

                    // Propriétaire du processus
                    // Priorité :
                    //   1. AP.processus[].proprietaire (texte saisi dans AP)
                    //   2. process_assignments JSON → auditeur_id → auditors.full_name
                    //   3. function_assignments de l'entité → 1ère fonction affectée
                    $proprietaire    = '';
                    $proprietaireFnId = null;
                    $apProc = $apByCode[$procCode] ?? null;

                    if (!empty($apProc['proprietaire'])) {
                        $proprietaire = $apProc['proprietaire'];
                    } elseif (isset($processAssignments[$procCode])) {
                        $auditorId    = (int) $processAssignments[$procCode];
                        $auditorObj   = $auditorsRef->get($auditorId);
                        $proprietaire = $auditorObj
                            ? trim($auditorObj->full_name)
                            : "Auditeur #{$auditorId}";
                    } elseif ($primaryEntityId && !empty($fnByEntityFn[$primaryEntityId])) {
                        // Proposer la 1ère fonction de l'entité comme propriétaire
                        $firstFnId    = array_key_first($fnByEntityFn[$primaryEntityId]);
                        $proprietaire = $fnByEntityFn[$primaryEntityId][$firstFnId] ?? '';
                        $proprietaireFnId = $firstFnId;
                    }

                    // Objectifs depuis AP
                    $objStrategique  = $apProc['objectif_strategique']  ?? '';
                    $objOperationnel = $apProc['objectif_operationnel'] ?? '';

                    $lignes[] = [
                        'num'                     => $num++,
                        'process_code'            => $procCode,
                        'process_name'            => $processNom,
                        'activity_code'           => $actCode,
                        'activity_name'           => $activityNom,
                        'objectif_strategique'    => $objStrategique,
                        'objectif_operationnel'   => $objOperationnel,
                        'risque_code'             => $riskCode,
                        'risque_libelle'          => $risqueLibelle,
                        'impact_net'              => $impactNet,
                        'frequency_net'           => $freqNet,
                        'criticite_residuelle'    => $criticite,
                        'description_controle'    => $descCtrl,
                        'type_controle'           => $typeCtrl,
                        'preuve_controle'         => $preuve,
                        'frequence_controle'      => $frequence,
                        'responsable_controle'    => $respCtrl,
                        'proprietaire_processus'  => $proprietaire,
                        '_proprietaire_fn_id'     => $proprietaireFnId,
                        '_source'                 => "AR/{$riskCode}",
                        '_risk_id'                => $riskId,
                        '_retenu'                 => $isRetenu,
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('[RCI] loadRciDepuisAr EXCEPTION: ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine());
        }

        Log::info("[RCI] ── RÉSULTAT : " . count($lignes) . " lignes RCI générées");
        return ['lignes' => $lignes, 'total' => count($lignes)];
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

        if (!$missionId || !$assignmentId)
            return response()->json(['success' => false, 'message' => 'Contexte mission manquant.'], 422);
        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending')
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);

        $existing = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'      => $assignmentId,
            'mission_id'         => $missionId,
            'code'               => $this->genCode($missionId),
            'criteres'           => $this->toJson($request->input('criteres',           '[]')),
            'sources_normatives' => $this->toJson($request->input('sources_normatives', '[]')),
            'synthese'           => $request->input('synthese'),
            'validation_status'  => 'draft',
            'created_by'         => $auditor->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($form), 'message' => 'RCI créé.']);
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

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        if (!$this->canEdit($row, $role))
            return response()->json(['success' => false, 'message' => match ($row->validation_status) {
                'validated' => 'RCI validé — modification impossible.',
                'in_review' => 'RCI soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            [
                'criteres'           => $this->toJson($request->input('criteres',           '[]')),
                'sources_normatives' => $this->toJson($request->input('sources_normatives', '[]')),
                'synthese'           => $request->input('synthese'),
                'updated_at'         => now(),
            ]
        ));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'RCI mis à jour.']);
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

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) return response()->json(['error' => 'Accès refusé'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review') return response()->json(['error' => 'Déjà soumis pour validation'], 422);

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

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') return response()->json(['error' => 'Le formulaire doit être soumis avant validation'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif du rejet obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
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
    // destroy
    // ══════════════════════════════════════════════════════════════
    public function destroy(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Un formulaire validé ne peut pas être supprimé'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->delete();
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════
    private function nettoyerProcedure(string $txt): string
    {
        $txt = preg_replace('/🔒.*?\n+/', '', $txt);
        $txt = preg_replace('/ÉTAPES:\s*/', "Étapes :\n", $txt);
        $txt = preg_replace('/Risque:.*?\n/', '', $txt);
        $txt = preg_replace('/Activité:.*?\n/', '', $txt);
        $txt = preg_replace('/Processus:.*?\n/', '', $txt);
        return trim($txt);
    }

    private function decodeMap(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }

    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        return array_merge((array) $row, [
            'criteres'           => $this->decodeArr($row->criteres ?? null),
            'sources_normatives' => $this->decodeArr($row->sources_normatives ?? null),
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
}