<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════
 * RÉUNION DE LANCEMENT — PHASE DE RÉALISATION
 * ════════════════════════════════════════════════════════════════
 *
 * Table : mission_phase_reunion_lancement
 *
 * Structure du PV (fidèle au fichier Excel) :
 *   - En-tête mission (readonly)
 *   - Informations de la réunion (date, heure, lieu, présidée par)
 *   - Participants
 *   - Méthodologie générale (texte libre)
 *   - Section 5 : Objectifs d'audit & diligences prévues
 *     → Tirés d'UN seul programme de travail actif pour la mission
 *     → Colonnes : Objectif | Étapes/Travaux (tests) | Auditeurs | Période/Lieu
 *                  Observations | Risques/Faiblesses apparentes
 *   - Section 6 : Préoccupations des audités
 *   - Section 8 : Signatures (Chef de mission, Représentant audités, Auditeur senior)
 *
 * Logique de sélection du programme de travail :
 *   On cherche dans cet ordre (premier trouvé = utilisé) :
 *     1. mission_phase_prog_ci          (PTCI)
 *     2. mission_phase_prog_conformite  (PTCONF)
 *     3. mission_phase_prog_marches     (PTMAR)
 *     4. mission_phase_prog_transactions (PTTRANS)
 *   On affiche le badge "Programme X" dans l'en-tête.
 * ════════════════════════════════════════════════════════════════
 */
class ReunionLancementController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_reunion_lancement';
    protected string $formCode    = 'reunion-lancement';
    protected string $codePrefix  = 'FRL';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ReunionLancement';
    protected string $routeEdit   = 'auditor.ac.reunion-lancement.edit';

    // Tables des programmes de travail — ordre de priorité
    private const PROGRAMMES = [
        ['table' => 'mission_phase_prog_ci',           'code' => 'PTCI',    'label' => 'Contrôle Interne'],
        ['table' => 'mission_phase_prog_conformite',   'code' => 'PTCONF',  'label' => 'Conformité'],
        ['table' => 'mission_phase_prog_marches',      'code' => 'PTMAR',   'label' => 'Marchés'],
        ['table' => 'mission_phase_prog_transactions', 'code' => 'PTTRANS', 'label' => 'Transactions'],
    ];

    protected array $validationRules = [
        'date_reunion'   => 'required|date',
        'lieu'           => 'required|string|max:255',
        'fait_par'       => 'nullable|string|max:255',
        'revue_par'      => 'nullable|string|max:255',
        'presidente_par' => 'nullable|string|max:255',
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

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'date_reunion'   => $request->input('date_reunion'),
            'heure_debut'    => $request->input('heure_debut'),
            'heure_fin'      => $request->input('heure_fin'),
            'lieu'           => $request->input('lieu'),
            'presidente_par' => $request->input('presidente_par'),
            'fait_par'       => $request->input('fait_par'),
            'revue_par'      => $request->input('revue_par'),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // buildPayload
    // ══════════════════════════════════════════════════════════════
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        // ── Auditeurs de la phase ──────────────────────────────────
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.last_name', 'a.first_name', 'a.audit_code', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'full_name' => trim($a->full_name),
                'role_code' => $a->role_code,
                'audit_code'=> $a->audit_code,
            ])
            ->toArray();

        // ── Récupérer UN programme de travail actif ────────────────
        $programmeData = $this->chargerProgrammeTravail($missionId, $assignmentId);

        // ── Statut de tous les programmes (pour affichage dashboard) ─
        $statutProgrammes = $this->getStatutTousProgrammes($missionId, $assignmentId);

        // ── Formulaire hydraté ────────────────────────────────────
        $formData = null;
        if ($form) {
            $formData = array_merge((array) $form, [
                'participants'   => $this->decodeArr($form->participants),
                'objectifs'      => $this->decodeArr($form->objectifs),
                'preoccupations' => $this->decodeArr($form->preoccupations),
                'signatures'     => $this->decodeArr($form->signatures),
                'media_items'    => $this->decodeArr($form->media_items ?? null),
            ]);
        }

        // ── Mission ───────────────────────────────────────────────
        $mission = DB::connection('tenant')
            ->table('mission_programmation as mp')
            ->leftJoin('mission_programmation_entity as mpe', 'mpe.mission_programmation_id', '=', 'mp.id')
            ->leftJoin('entities as e', 'e.id', '=', 'mpe.entity_id')
            ->where('mp.id', $missionId)
            ->select(
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.objectif',
                'mp.date_debut', 'mp.date_fin', 'mp.lieux',
                'e.name as entity_name', 'e.code_base'
            )
            ->first();

        $formId = $form?->id ?? null;

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'              => $formData,
                'mission'           => $mission ? (array) $mission : null,
                'phaseAuditeurs'    => $phaseAuditeurs,
                'programmeData'     => $programmeData,
                'statutProgrammes'  => $statutProgrammes,
                'missionContext'    => [
                    'mission_id'      => $missionId,
                    'assignment_id'   => $assignmentId,
                    'mission_libelle' => $mission?->libelle      ?? '',
                    'code_mission'    => $mission?->code_mission ?? '',
                ],
                'urlIndex'     => route('audit.ac.realisation.reunion-lancement'),
                'urlStore'     => route('auditor.ac.reunion-lancement.store'),
                'urlUpdate'    => $formId ? route('auditor.ac.reunion-lancement.update',    $formId) : null,
                'urlSoumettre' => $formId ? route('auditor.ac.reunion-lancement.soumettre', $formId) : null,
                'urlValider'   => $formId ? route('auditor.ac.reunion-lancement.valider',   $formId) : null,
                'backUrl'      => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // chargerProgrammeParCode
    // Appelé en AJAX depuis la Vue quand l'auditeur clique sur un badge programme.
    // Retourne les objectifs + tests + procédures du programme demandé.
    // ══════════════════════════════════════════════════════════════
    private function chargerProgrammeParCode(string $code, int $missionId, int $assignmentId): array
    {
        $tableMap = [
            'PTCI'    => ['table' => 'mission_phase_prog_ci',           'label' => 'Contrôle Interne'],
            'PTCONF'  => ['table' => 'mission_phase_prog_conformite',   'label' => 'Conformité'],
            'PTMAR'   => ['table' => 'mission_phase_prog_marches',      'label' => 'Marchés'],
            'PTTRANS' => ['table' => 'mission_phase_prog_transactions', 'label' => 'Transactions'],
        ];

        if (!isset($tableMap[$code])) {
            return ['error' => "Programme inconnu : {$code}", 'objectifs' => []];
        }

        $prog = $tableMap[$code];

        // Chercher par assignment_id d'abord, puis par mission_id
        $row = DB::connection('tenant')
            ->table($prog['table'])
            ->where('assignment_id', $assignmentId)
            ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
            ->orderByDesc('updated_at')
            ->first();

        if (!$row) {
            $row = DB::connection('tenant')
                ->table($prog['table'])
                ->where('mission_id', $missionId)
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                ->orderByDesc('updated_at')
                ->first();
        }

        if (!$row) {
            return [
                'error'    => "Programme {$code} introuvable.",
                'objectifs' => [],
                'programmeData' => ['found' => false, 'programme_code' => $code],
            ];
        }

        $lignes = $this->decodeArr($row->lignes ?? null);

        // Construire les objectifs dans le format attendu par la Vue
        $objectifs = [];
        foreach ($lignes as $obj) {
            $tests = $obj['tests'] ?? [];

            foreach ($tests as $test) {
                $objectifs[] = [
                    'num'               => $obj['num']     ?? '',
                    'objectif'          => $obj['objectif'] ?? '',
                    'ref_rci'           => $obj['ref_rci']  ?? '',
                    'tests'             => [
                        [
                            'libelle'            => $test['libelle']            ?? '',
                            'procedures'         => $test['procedures']         ?? [],
                            'auditeur'           => $test['auditeur']           ?? '',
                            'periode_testee'     => $test['periode_testee']     ?? '',
                            'lieu'               => $test['lieu']               ?? '',
                            'taille_echantillon' => $test['taille_echantillon'] ?? '',
                            'date_debut'         => $test['date_debut']         ?? '',
                            'date_fin'           => $test['date_fin']           ?? '',
                        ]
                    ],
                    '_risque_libelle'   => $obj['_risque_libelle']  ?? '',
                    '_axe_rado'         => $obj['_axe_rado']        ?? '',
                    '_priorite'         => $obj['_priorite']        ?? '',
                    '_source'           => $code,
                ];
            }

            // Objectif sans test
            if (empty($tests)) {
                $objectifs[] = [
                    'num'               => $obj['num']     ?? '',
                    'objectif'          => $obj['objectif'] ?? '',
                    'ref_rci'           => $obj['ref_rci']  ?? '',
                    'tests'             => [['libelle'=>'','procedures'=>[],'auditeur'=>'','periode_testee'=>'','lieu'=>'']],
                    '_risque_libelle'   => $obj['_risque_libelle'] ?? '',
                    '_axe_rado'         => $obj['_axe_rado']  ?? '',
                    '_priorite'         => $obj['_priorite']  ?? '',
                    '_source'           => $code,
                ];
            }
        }

        Log::info("[FRL] chargerProgrammeParCode {$code} → " . count($objectifs) . " objectifs");

        return [
            'objectifs' => $objectifs,
            'programmeData' => [
                'found'            => true,
                'programme_code'   => $code,
                'programme_label'  => $prog['label'],
                'programme_status' => $row->validation_status,
                'total_objectifs'  => count($lignes),
                'total_tests'      => count($objectifs),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // chargerProgrammeTravail
    // Cherche le PREMIER programme de travail qui existe pour la mission.
    // Retourne ses objectifs + tests pour pré-remplir la section 5.
    // ══════════════════════════════════════════════════════════════
    private function chargerProgrammeTravail(int $missionId, int $assignmentId): array
    {
        foreach (self::PROGRAMMES as $prog) {
            // Chercher par assignment_id d'abord, puis par mission_id
            $row = DB::connection('tenant')
                ->table($prog['table'])
                ->where('assignment_id', $assignmentId)
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                ->orderByDesc('updated_at')
                ->first();

            if (!$row) {
                $row = DB::connection('tenant')
                    ->table($prog['table'])
                    ->where('mission_id', $missionId)
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')
                    ->first();
            }

            if (!$row) continue;

            Log::info("[FRL] Programme trouvé : {$prog['code']} id={$row->id} status={$row->validation_status}");

            // Décoder les lignes du programme
            $lignes = $this->decodeArr($row->lignes ?? null);

            // Construire les objectifs pour la section 5 du PV
            $objectifs = [];
            foreach ($lignes as $obj) {
                // Récupérer tous les tests de l'objectif
                $tests = $obj['tests'] ?? [];

                // Pour chaque test, créer une ligne dans le tableau section 5
                foreach ($tests as $test) {
                    $objectifs[] = [
                        'objectif'              => $obj['objectif']  ?? '',
                        'ref'                   => $obj['num']       ?? '',
                        'ref_rci'               => $obj['ref_rci']   ?? '',
                        'etapes_travaux'        => $test['libelle']  ?? '',    // col C = Tests/Étapes
                        'procedures'            => $test['procedures'] ?? [],
                        'auditeurs'             => $test['auditeur']   ?? '',   // col D
                        'periode_lieu'          => trim(($test['periode_testee'] ?? '') . ' ' . ($test['lieu'] ?? '')),
                        'observations'          => '',                           // col F — saisie libre
                        'risques_faiblesses'    => $obj['_risque_libelle'] ?? ($obj['_indicateurs'] ?? ''), // col G
                        // Champs additionnels pour enrichissement
                        '_axe_rado'             => $obj['_axe_rado']  ?? '',
                        '_priorite'             => $obj['_priorite']  ?? '',
                        '_test_ref'             => $test['ref']       ?? '',
                        '_taille_echantillon'   => $test['taille_echantillon'] ?? '',
                        '_date_debut'           => $test['date_debut'] ?? '',
                        '_date_fin'             => $test['date_fin']   ?? '',
                    ];
                }

                // Si l'objectif n'a pas de tests, l'ajouter quand même (ligne vide)
                if (empty($tests)) {
                    $objectifs[] = [
                        'objectif'           => $obj['objectif'] ?? '',
                        'ref'                => $obj['num']      ?? '',
                        'ref_rci'            => $obj['ref_rci']  ?? '',
                        'etapes_travaux'     => '',
                        'procedures'         => [],
                        'auditeurs'          => '',
                        'periode_lieu'       => '',
                        'observations'       => '',
                        'risques_faiblesses' => $obj['_risque_libelle'] ?? '',
                        '_axe_rado'          => $obj['_axe_rado'] ?? '',
                        '_priorite'          => $obj['_priorite'] ?? '',
                        '_test_ref'          => '',
                        '_taille_echantillon'=> '',
                        '_date_debut'        => '',
                        '_date_fin'          => '',
                    ];
                }
            }

            return [
                'found'             => true,
                'programme_code'    => $prog['code'],
                'programme_label'   => $prog['label'],
                'programme_table'   => $prog['table'],
                'programme_id'      => $row->id,
                'programme_status'  => $row->validation_status,
                'objectifs'         => $objectifs,
                'total_objectifs'   => count($lignes),
                'total_tests'       => count($objectifs),
            ];
        }

        Log::warning("[FRL] Aucun programme de travail trouvé pour missionId={$missionId}");
        return [
            'found'           => false,
            'programme_code'  => null,
            'programme_label' => null,
            'objectifs'       => [],
            'total_objectifs' => 0,
            'total_tests'     => 0,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // getStatutTousProgrammes
    // Vérifie lesquels des 4 programmes existent pour la mission
    // (pour afficher les badges dans le dashboard)
    // ══════════════════════════════════════════════════════════════
    private function getStatutTousProgrammes(int $missionId, int $assignmentId): array
    {
        $statuts = [];
        foreach (self::PROGRAMMES as $prog) {
            $row = DB::connection('tenant')
                ->table($prog['table'])
                ->where('assignment_id', $assignmentId)
                ->select('id', 'code', 'validation_status')
                ->first();

            if (!$row) {
                $row = DB::connection('tenant')
                    ->table($prog['table'])
                    ->where('mission_id', $missionId)
                    ->select('id', 'code', 'validation_status')
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->first();
            }

            $statuts[] = [
                'code'    => $prog['code'],
                'label'   => $prog['label'],
                'exists'  => $row !== null,
                'status'  => $row?->validation_status ?? null,
                'form_id' => $row?->id ?? null,
                'form_code'=> $row?->code ?? null,
            ];
        }
        return $statuts;
    }

    // ══════════════════════════════════════════════════════════════
    // index
    // ══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        try {
            $auditor      = $this->getAuditor(); if (!$auditor) abort(403);
            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id',    0));
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));
            if (!$missionId || !$assignmentId) abort(422, 'Contexte mission manquant.');

            // ── Chargement dynamique d'un programme (appel AJAX depuis la Vue) ──
            // GET ?load_programme=PTCI&json=1 → retourne JSON {objectifs, programmeData}
            if ($request->input('json') === '1' && $request->has('load_programme')) {
                $codeProg = strtoupper(trim($request->input('load_programme', '')));
                $programme = $this->chargerProgrammeParCode($codeProg, $missionId, $assignmentId);
                return response()->json($programme);
            }

            $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
            if ($existing) {
                return redirect()->route($this->routeEdit, [
                    'form'          => $existing->id,
                    'mission_id'    => $missionId,
                    'assignment_id' => $assignmentId,
                ]);
            }
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, null));
        } catch (\Exception $e) {
            Log::error('[FRL] index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // edit
    // ══════════════════════════════════════════════════════════════
    public function edit(Request $request, int $formId)
    {
        try {
            $auditor = $this->getAuditor(); if (!$auditor) abort(403);
            $form    = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();

            if (!$form) {
                $missionId    = (int)($request->input('mission_id')    ?? session('mission_id',    0));
                $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));
                if ($missionId && $assignmentId) {
                    return redirect()->route('audit.ac.realisation.reunion-lancement', [
                        'mission_id' => $missionId, 'assignment_id' => $assignmentId,
                    ]);
                }
                return redirect()->back()->with('error', 'Réunion de lancement introuvable.');
            }

            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id);

            if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, $form));
        } catch (\Exception $e) {
            Log::error('[FRL] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // store
    // ══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $auditor      = $this->getAuditor(); if (!$auditor) abort(403);
        $missionId    = (int) $request->input('mission_id',    0);
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId)
            return response()->json(['success' => false, 'message' => 'Contexte mission manquant.'], 422);
        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $assignment = DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending')
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);

        $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);

        $id = DB::connection('tenant')->table($this->table)->insertGetId(array_merge(
            $this->formData($request, $auditor),
            [
                'assignment_id'     => $assignmentId,
                'mission_id'        => $missionId,
                'code'              => $this->genCode($missionId),
                'methodologie'      => $request->input('methodologie'),
                'participants'      => $this->toJson($request->input('participants',   '[]')),
                'objectifs'         => $this->toJson($request->input('objectifs',      '[]')),
                'preoccupations'    => $this->toJson($request->input('preoccupations', '[]')),
                'signatures'        => $this->toJson($request->input('signatures',     '{}')),
                'media_items'       => $this->toJson($request->input('media_items',    '[]')),
                'documents_ref'     => $this->toJson($request->input('documents_ref',   '[]')),
                'validation_status' => 'draft',
                'created_by'        => $auditor->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        ));

        $this->log($assignmentId, $auditor->id, $this->getRole($missionId, $auditor->id), 'saved', null, 'draft');
        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();

        return response()->json([
            'success'      => true,
            'form'         => $this->hydrateForm($form),
            'message'      => 'Réunion de lancement créée.',
            'urlUpdate'    => route('auditor.ac.reunion-lancement.update',    $id),
            'urlSoumettre' => route('auditor.ac.reunion-lancement.soumettre', $id),
            'urlValider'   => route('auditor.ac.reunion-lancement.valider',   $id),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // update
    // ══════════════════════════════════════════════════════════════
    public function update(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) abort(403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['success' => false, 'message' => 'Formulaire introuvable.'], 404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        if (!$this->canEdit($row, $role))
            return response()->json(['success' => false, 'message' => match ($row->validation_status) {
                'validated' => 'PV validé — modification impossible.',
                'in_review' => 'PV soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            [
                'methodologie'   => $request->input('methodologie'),
                'participants'   => $this->toJson($request->input('participants',   '[]')),
                'objectifs'      => $this->toJson($request->input('objectifs',      '[]')),
                'preoccupations' => $this->toJson($request->input('preoccupations', '[]')),
                'signatures'     => $this->toJson($request->input('signatures',     '{}')),
                'media_items'    => $this->toJson($request->input('media_items',    '[]')),
                'documents_ref'  => $this->toJson($request->input('documents_ref',   '[]')),
                'updated_at'     => now(),
            ]
        ));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'PV mis à jour.']);
    }

    // ══════════════════════════════════════════════════════════════
    // soumettre
    // ══════════════════════════════════════════════════════════════
    public function soumettre(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) return response()->json(['error' => 'Accès refusé'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review') return response()->json(['error' => 'Déjà soumis'], 422);

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
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') return response()->json(['error' => 'Doit être soumis avant validation'], 422);

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

        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);

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
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);
        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'PV validé non supprimable'], 403);
        DB::connection('tenant')->table($this->table)->where('id', $formId)->delete();
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════
    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        return array_merge((array) $row, [
            'participants'   => $this->decodeArr($row->participants   ?? null),
            'objectifs'      => $this->decodeArr($row->objectifs      ?? null),
            'preoccupations' => $this->decodeArr($row->preoccupations ?? null),
            'signatures'     => $this->decodeArr($row->signatures     ?? null),
            'media_items'    => $this->decodeArr($row->media_items    ?? null),
            'documents_ref'  => $this->decodeArr($row->documents_ref  ?? null),
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