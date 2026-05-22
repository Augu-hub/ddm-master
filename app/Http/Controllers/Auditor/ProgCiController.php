<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Param\Auditor;
use App\Services\Audit\ProgCiAiSuggestionService;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * PROGRAMME DE TRAVAIL DE CONTRÔLE INTERNE (PTCI)
 *
 * Flux : RADO.axes_audit → objectifs | RCI.criteres → tests + procédures
 */
class ProgCiController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_prog_ci';
    protected string $formCode    = 'prog-ci';
    protected string $codePrefix  = 'PTCI';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ProgCi';
    protected string $routeEdit   = 'auditor.ac.prog-ci.edit';

    protected array $validationRules = [
        'fait_par'  => 'nullable|string|max:255',
        'revue_par' => 'nullable|string|max:255',
    ];

    private ProgCiAiSuggestionService $aiService;

    public function __construct(ProgCiAiSuggestionService $aiService)
    {
        $this->aiService = $aiService;
    }

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
            'fait_par'  => $request->input('fait_par'),
            'revue_par' => $request->input('revue_par'),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // buildPayload
    // ══════════════════════════════════════════════════════════════
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.last_name', 'a.first_name', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => ['id' => $a->id, 'full_name' => trim($a->full_name), 'role_code' => $a->role_code])
            ->toArray();

        // Source de vérité : RADO + RCI recalculés à chaque fois
        $donneesRCI = $this->loadDepuisRadoEtRci($missionId, $assignmentId);

        $formData = null;
        if ($form) {
            $lignesSauvegardees = $this->decodeArr($form->lignes);
            $hasRadoNow    = $donneesRCI['source'] === 'rado+rci';
            $lignesFromRci = !empty($lignesSauvegardees)
                && empty(array_filter($lignesSauvegardees, fn($l) => !empty($l['_rado_id'])));

            // Si RADO maintenant disponible mais lignes sauvegardées viennent du RCI seul → recharger
            if ($hasRadoNow && $lignesFromRci && !empty($donneesRCI['lignes'])) {
                $lignesSauvegardees = [];
                Log::info("[PTCI] Rechargement forcé depuis RADO+RCI");
            }

            $formData = array_merge((array) $form, ['lignes' => $lignesSauvegardees]);
        }

        $mission = DB::connection('tenant')
            ->table('mission_programmation')
            ->where('id', $missionId)
            ->select('id', 'code_mission', 'libelle')
            ->first();

        $formId = $form?->id ?? null;
        $base   = url('/m/audit.core/ac/preparation/prog-ci');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'             => $formData,
                'phaseAuditeurs'   => $phaseAuditeurs,
                'donneesRCI'       => $donneesRCI,
                'missionContext'   => [
                    'mission_id'      => $missionId,
                    'assignment_id'   => $assignmentId,
                    'mission_libelle' => $mission?->libelle      ?? '',
                    'code_mission'    => $mission?->code_mission ?? '',
                    'processus'       => $donneesRCI['processus'] ?? '',
                    'source'          => $donneesRCI['source']    ?? 'none',
                ],
                'urlAiSuggestBase' => $base,
                'urlStore'         => route('auditor.ac.prog-ci.store'),
                'urlUpdate'        => $formId ? route('auditor.ac.prog-ci.update',    $formId) : null,
                'urlSoumettre'     => $formId ? route('auditor.ac.prog-ci.soumettre', $formId) : null,
                'urlValider'       => $formId ? route('auditor.ac.prog-ci.valider',   $formId) : null,
                'urlAiSuggest'     => $formId ? route('auditor.ac.prog-ci.ai-suggest',  $formId) : null,
                'urlAiSynthese'    => $formId ? route('auditor.ac.prog-ci.ai-synthese', $formId) : null,
                'urlUpload'        => $formId ? route('auditor.ac.prog-ci.upload',      $formId) : null,
                'urlBase'          => $base,
                'urlModeleExcel'   => route('auditor.ac.prog-ci.modele-excel'),
                'urlIndex'         => route('audit.ac.preparation.prog-ci'),
                'backUrl'          => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
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

            $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
            if ($existing) {
                return redirect()->route($this->routeEdit, $existing->id)
                    ->with('mission_id', $missionId)->with('assignment_id', $assignmentId);
            }
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, null));
        } catch (\Exception $e) {
            Log::error('[PTCI] index: ' . $e->getMessage());
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

            $form = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
            if (!$form) {
                $missionId    = (int)($request->input('mission_id')    ?? session('mission_id',    0));
                $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));
                if ($missionId && $assignmentId) {
                    return redirect()->route('audit.ac.preparation.prog-ci', [
                        'mission_id' => $missionId, 'assignment_id' => $assignmentId,
                    ]);
                }
                return redirect()->back()->with('error', 'Programme CI introuvable.');
            }

            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id);

            if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, $form));
        } catch (\Exception $e) {
            Log::error('[PTCI] edit: ' . $e->getMessage());
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
                'lignes'            => $this->toJson($request->input('lignes', '[]')),
                'synthese'          => $request->input('synthese'),
                'validation_status' => 'draft',
                'created_by'        => $auditor->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        ));

        $this->log($assignmentId, $auditor->id, $this->getRole($missionId, $auditor->id), 'saved', null, 'draft');
        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();

        return response()->json([
            'success'       => true,
            'form'          => $this->hydrateForm($form),
            'message'       => 'Programme CI créé.',
            'urlAiSuggest'  => route('auditor.ac.prog-ci.ai-suggest',  $id),
            'urlAiSynthese' => route('auditor.ac.prog-ci.ai-synthese', $id),
            'urlUpload'     => route('auditor.ac.prog-ci.upload',      $id),
            'urlUpdate'     => route('auditor.ac.prog-ci.update',      $id),
            'urlSoumettre'  => route('auditor.ac.prog-ci.soumettre',   $id),
            'urlValider'    => route('auditor.ac.prog-ci.valider',     $id),
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
                'validated' => 'Programme CI validé — modification impossible.',
                'in_review' => 'Programme CI soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            ['lignes' => $this->toJson($request->input('lignes', '[]')), 'synthese' => $request->input('synthese'), 'updated_at' => now()]
        ));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'Programme CI mis à jour.']);
    }

    // ══════════════════════════════════════════════════════════════
    // soumettre / valider / destroy — identiques
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
            'validation_status' => 'in_review', 'submitted_at' => now(), 'submitted_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

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
            DB::connection('tenant')->table($this->table)->where('id', $formId)->update(['validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now()]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }
        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);
        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(['validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now()]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update(['validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => $auditor->id, 'updated_at' => now()]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    public function destroy(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);
        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Formulaire validé non supprimable'], 403);
        DB::connection('tenant')->table($this->table)->where('id', $formId)->delete();
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // aiSuggest / aiSynthese / telechargerModele / uploadExcel
    // ══════════════════════════════════════════════════════════════
    public function aiSuggest(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);
        $objectif = (array) $request->input('objectif', []);
        $context  = (array) $request->input('context',  []);
        if (empty($objectif)) return response()->json(['error' => 'Objectif manquant'], 422);
        $mission = DB::connection('tenant')->table('mission_programmation')->where('id', $row->mission_id)->first();
        $context = array_merge($context, [
            'prog_ci_id' => $formId, 'mission_id' => (int) $row->mission_id,
            'mission_libelle' => $mission?->libelle ?? '', 'code_mission' => $mission?->code_mission ?? '',
            'obj_num' => $objectif['num'] ?? '', 'risque_libelle' => $objectif['_risque_libelle'] ?? $context['risque_libelle'] ?? '',
            'processus' => $objectif['_process_name'] ?? $context['processus'] ?? '',
            'type_controle' => $objectif['_type_controle'] ?? '', 'criticite' => $objectif['_criticite'] ?? 0,
            'axe_rado' => $objectif['_axe_rado'] ?? '', 'indicateurs' => $objectif['_indicateurs'] ?? '',
        ]);
        return response()->json($this->aiService->reformulerObjectif($objectif, $context));
    }

    public function aiSynthese(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);
        $lignesRaw = $request->input('lignes');
        $lignes    = $lignesRaw ? (is_array($lignesRaw) ? $lignesRaw : $this->decodeArr($lignesRaw)) : $this->decodeArr($row->lignes);
        if (empty($lignes)) return response()->json(['error' => 'Aucune ligne disponible'], 422);
        $mission = DB::connection('tenant')->table('mission_programmation')->where('id', $row->mission_id)->first();
        return response()->json($this->aiService->genererSynthese($lignes, [
            'prog_ci_id' => $formId, 'mission_id' => (int) $row->mission_id,
            'mission_libelle' => $mission?->libelle ?? '', 'code_mission' => $mission?->code_mission ?? '',
        ]));
    }

    public function telechargerModele()
    {
        foreach ([storage_path('app/public/modeles/ProgCI_Modele_Vide.xlsx'), public_path('files/ProgCI_Modele_Vide.xlsx')] as $path) {
            if (file_exists($path)) return response()->download($path, 'ProgCI_Modele_Vide.xlsx');
        }
        return response()->json(['error' => 'Fichier modèle introuvable.'], 404);
    }

    public function uploadExcel(Request $request, int $formId)
    {
        $auditor = $this->getAuditor(); if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row     = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);
        try {
            $rows = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, true, true);
            $dataStartRow = null;
            foreach ($rows as $idx => $cols) {
                if (preg_match('/^O\d/i', trim((string)($cols['A'] ?? '')))) { $dataStartRow = $idx; break; }
            }
            if (!$dataStartRow) return response()->json(['success' => false, 'error' => 'Colonne A doit commencer par O1, O2…'], 422);
            $objectifs = []; $currentObj = null; $letters = ['a','b','c','d','e','f'];
            foreach ($rows as $idx => $cols) {
                if ($idx < $dataStartRow) continue;
                $refObj  = trim((string)($cols['A'] ?? ''));
                $testLib = trim((string)($cols['D'] ?? ''));
                if ($refObj && preg_match('/^O\d/i', $refObj)) {
                    if ($currentObj) $objectifs[] = $currentObj;
                    $currentObj = ['num' => $refObj, 'objectif' => trim((string)($cols['B'] ?? '')), 'ref_rci' => trim((string)($cols['C'] ?? '')), 'tests' => []];
                }
                if ($currentObj && $testLib) {
                    $nb = count($currentObj['tests']); $ref = $nb === 0 ? 'T_'.$currentObj['num'] : 'T_'.$currentObj['num'].'_'.($letters[$nb] ?? ($nb+1));
                    if ($nb === 1) $currentObj['tests'][0]['ref'] = 'T_'.$currentObj['num'].'_a';
                    $currentObj['tests'][] = ['ref' => $ref, 'libelle' => $testLib, 'procedures' => $this->parseProcedures(trim((string)($cols['E'] ?? ''))), 'taille_echantillon' => trim((string)($cols['F'] ?? '')), 'periode_testee' => trim((string)($cols['G'] ?? '')), 'auditeur' => trim((string)($cols['H'] ?? '')), 'date_debut' => $this->parseDate($cols['I'] ?? ''), 'date_fin' => $this->parseDate($cols['J'] ?? ''), 'lieu' => trim((string)($cols['K'] ?? ''))];
                }
            }
            if ($currentObj) $objectifs[] = $currentObj;
            if (empty($objectifs)) return response()->json(['success' => false, 'error' => 'Aucun objectif trouvé.'], 422);
            return response()->json(['success' => true, 'lignes' => $objectifs, 'total' => count($objectifs), 'message' => count($objectifs).' objectif(s) importé(s).']);
        } catch (\Exception $e) {
            Log::error('[PTCI] uploadExcel: '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erreur: '.$e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // loadDepuisRadoEtRci — VERSION CORRIGÉE
    //
    // Problèmes résolus :
    //  1. Lecture du RADO élargie : cherche par assignment_id ET par mission_id
    //     (le RADO peut être sur un assignment différent de celui du PTCI)
    //  2. Décodage axes_audit robuste : gère string JSON et array natif
    //  3. Log détaillé pour diagnostiquer en cas de problème
    //  4. Le texte exact de chaque objectif RADO est conservé (pas de reformulation)
    // ══════════════════════════════════════════════════════════════
    private function loadDepuisRadoEtRci(int $missionId, int $assignmentId): array
    {
        Log::info("[PTCI] loadDepuisRadoEtRci missionId={$missionId} assignmentId={$assignmentId}");

        $objectifs    = [];
        $processusNom = '';

        try {
            // ── 1. RCI ─────────────────────────────────────────────
            $rciRow = DB::connection('tenant')
                ->table('mission_phase_ref_ci')
                ->where('assignment_id', $assignmentId)
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                ->orderByDesc('updated_at')->first();

            if (!$rciRow) {
                // Fallback : n'importe quel RCI de cette mission
                $rciRow = DB::connection('tenant')
                    ->table('mission_phase_ref_ci')
                    ->where('mission_id', $missionId)
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')->first();
            }

            $rciCriteres = [];
            if ($rciRow) {
                foreach ($this->decodeArr($rciRow->criteres) as $c) {
                    if (array_key_exists('_retenu', $c) && !$c['_retenu']) continue;
                    $rciCriteres[] = $c;
                    if (empty($processusNom) && !empty($c['process_name'])) $processusNom = $c['process_name'];
                }
                Log::info("[PTCI] RCI id={$rciRow->id} : " . count($rciCriteres) . " critères");
            } else {
                Log::warning("[PTCI] Aucun RCI trouvé pour missionId={$missionId}");
            }

            // ── 2. RADO — cherche sur TOUS les assignments de la mission ──
            // Le RADO peut appartenir à un assignment différent (ex : phase préparation
            // vs phase exécution) → on cherche d'abord sur l'assignment courant,
            // puis sur toute la mission.
            $radoRow = DB::connection('tenant')
                ->table('mission_phase_ro')
                ->where('assignment_id', $assignmentId)
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                ->orderByDesc('updated_at')->first();

            if (!$radoRow) {
                $radoRow = DB::connection('tenant')
                    ->table('mission_phase_ro')
                    ->where('mission_id', $missionId)
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')->first();
            }

            if ($radoRow) {
                Log::info("[PTCI] RADO id={$radoRow->id} assignment_id={$radoRow->assignment_id} status={$radoRow->validation_status}");
            } else {
                Log::warning("[PTCI] Aucun RADO trouvé pour missionId={$missionId}");
            }

            $num = 1;

            // ── 3a. RADO disponible ────────────────────────────────
            if ($radoRow) {
                // Décoder axes_audit (JSON stocké en BD)
                $axesAudit = $this->decodeArr($radoRow->axes_audit);
                Log::info("[PTCI] axes_audit brut (type=" . gettype($radoRow->axes_audit) . ") decoded=" . count($axesAudit) . " axes");

                // Décoder objectifs_specifiques (fallback si axes vides)
                $objectifsSpecifiques = $this->decodeArr($radoRow->objectifs_specifiques);
                Log::info("[PTCI] objectifs_specifiques=" . count($objectifsSpecifiques));

                $tousObjectifs = [];
                $textesDeja    = [];

                // ── Extraire depuis axes_audit[].objectifs[] ──────
                foreach ($axesAudit as $axe) {
                    if (!is_array($axe)) continue;

                    $axeLib   = trim($axe['axe']               ?? '');
                    $priorite = trim($axe['priorite']           ?? '');
                    $critEval = trim($axe['criteres_evaluation'] ?? '');

                    // objectifs peut être un tableau d'objets ou de strings
                    $objListe = $axe['objectifs'] ?? [];
                    if (!is_array($objListe)) $objListe = [];

                    Log::info("[PTCI] Axe '{$axeLib}' → " . count($objListe) . " objectifs");

                    foreach ($objListe as $obj) {
                        // Supporte {objectif:"..."} ou {libelle:"..."} ou string direct
                        $txt = '';
                        if (is_array($obj)) {
                            $txt = trim($obj['objectif'] ?? $obj['libelle'] ?? '');
                        } elseif (is_string($obj)) {
                            $txt = trim($obj);
                        }

                        if (strlen($txt) < 4) continue;

                        $cle = mb_strtolower($txt);
                        if (in_array($cle, $textesDeja, true)) continue;
                        $textesDeja[] = $cle;

                        $tousObjectifs[] = [
                            'objectif'      => $txt,   // ← TEXTE EXACT, non reformulé
                            'axe'           => $axeLib,
                            'priorite'      => $priorite,
                            'indicateurs'   => is_array($obj) ? ($obj['indicateurs'] ?? '') : '',
                            'criteres_eval' => $critEval,
                        ];
                    }
                }

                // ── Fallback : objectifs_specifiques ─────────────
                foreach ($objectifsSpecifiques as $os) {
                    $txt = '';
                    if (is_array($os))      $txt = trim($os['objectif'] ?? $os['libelle'] ?? '');
                    elseif (is_string($os)) $txt = trim($os);
                    if (strlen($txt) < 4) continue;
                    $cle = mb_strtolower($txt);
                    if (in_array($cle, $textesDeja, true)) continue;
                    $textesDeja[] = $cle;
                    $tousObjectifs[] = [
                        'objectif'    => $txt,
                        'axe'         => is_array($os) ? ($os['axe']         ?? '') : '',
                        'priorite'    => is_array($os) ? ($os['priorite']    ?? '') : '',
                        'indicateurs' => is_array($os) ? ($os['indicateurs'] ?? '') : '',
                        'criteres_eval' => '',
                    ];
                }

                Log::info("[PTCI] Total objectifs RADO collectés : " . count($tousObjectifs));

                // ── Construire les lignes PTCI ────────────────────
                $rciUtilises = [];
                foreach ($tousObjectifs as $objRado) {
                    $objNum   = 'O' . $num;
                    $rciMatch = !empty($rciCriteres)
                        ? $this->trouverRciPourObjectif($objRado['objectif'], $rciCriteres, $rciUtilises)
                        : [];

                    $matchCode   = $rciMatch['risque_code']         ?? null;
                    $testLibBrut = $rciMatch['description_controle'] ?? '';
                    $procBrut    = $rciMatch['preuve_controle']       ?? '';

                    if ($matchCode) $rciUtilises[] = $matchCode;

                    $procsArr = !empty($procBrut)
                        ? array_values(array_filter(
                            array_map('trim', preg_split('/[\n;]+/', $procBrut)),
                            fn($l) => strlen($l) > 3
                          ))
                        : [];

                    $objectifs[] = [
                        'num'      => $objNum,
                        'objectif' => $objRado['objectif'],   // ← TEXTE EXACT DU RADO
                        'ref_rci'  => $matchCode ?? '',
                        'tests'    => [[
                            'ref'                => 'T_' . $objNum,
                            'libelle'            => $testLibBrut,
                            'procedures'         => $procsArr,
                            'auditeur'           => $rciMatch['responsable_controle']   ?? '',
                            'date_debut'         => '',
                            'date_fin'           => '',
                            'lieu'               => $rciMatch['proprietaire_processus'] ?? '',
                            'taille_echantillon' => '',
                            'periode_testee'     => '',
                        ]],
                        '_source'                => 'RADO+RCI/' . ($matchCode ?? $num),
                        '_rado_id'               => $radoRow->id,
                        '_rci_id'                => $rciRow?->id,
                        '_axe_rado'              => $objRado['axe'],
                        '_priorite'              => $objRado['priorite'],
                        '_indicateurs'           => $objRado['indicateurs'],
                        '_criteres_eval'         => $objRado['criteres_eval'],
                        '_risque_code'           => $rciMatch['risque_code']            ?? '',
                        '_risque_libelle'        => $rciMatch['risque_libelle']          ?? '',
                        '_process_name'          => $rciMatch['process_name']            ?? $processusNom,
                        '_objectif_operationnel' => $rciMatch['objectif_operationnel']   ?? '',
                        '_description_controle'  => $testLibBrut,
                        '_preuve_controle'       => $procBrut,
                        '_type_controle'         => $rciMatch['type_controle']           ?? '',
                        '_criticite'             => (float)($rciMatch['criticite_residuelle'] ?? 0),
                        '_responsable'           => $rciMatch['responsable_controle']    ?? '',
                        '_needs_ai'              => !empty($objRado['objectif']),
                    ];
                    $num++;
                }

                Log::info("[PTCI] " . count($objectifs) . " lignes construites depuis RADO+RCI");
            }

            // ── 3b. Fallback RCI pur (pas de RADO ou RADO sans axes) ──
            if (empty($objectifs) && !empty($rciCriteres)) {
                Log::info("[PTCI] Fallback RCI pur : " . count($rciCriteres) . " critères");

                foreach ($rciCriteres as $c) {
                    $objNum      = 'O' . $num;
                    $testLibBrut = $c['description_controle'] ?? '';
                    $procBrut    = $c['preuve_controle']       ?? '';

                    $objectifs[] = [
                        'num'      => $objNum,
                        'objectif' => $c['objectif_operationnel'] ?? $c['risque_libelle'] ?? '',
                        'ref_rci'  => $c['risque_code'] ?? '',
                        'tests'    => [[
                            'ref'                => 'T_' . $objNum,
                            'libelle'            => $testLibBrut,
                            'procedures'         => !empty($procBrut)
                                ? array_values(array_filter(array_map('trim', preg_split('/[\n;]+/', $procBrut)), fn($l) => strlen($l) > 3))
                                : [],
                            'auditeur'           => $c['responsable_controle']   ?? '',
                            'date_debut'         => '',
                            'date_fin'           => '',
                            'lieu'               => $c['proprietaire_processus'] ?? '',
                            'taille_echantillon' => '',
                            'periode_testee'     => '',
                        ]],
                        '_source'                => 'RCI/' . ($c['risque_code'] ?? $num),
                        '_rado_id'               => null,
                        '_rci_id'                => $rciRow?->id,
                        '_axe_rado'              => '',
                        '_priorite'              => '',
                        '_indicateurs'           => '',
                        '_criteres_eval'         => '',
                        '_risque_code'           => $c['risque_code']           ?? '',
                        '_risque_libelle'        => $c['risque_libelle']         ?? '',
                        '_process_name'          => $c['process_name']           ?? $processusNom,
                        '_objectif_operationnel' => $c['objectif_operationnel']  ?? '',
                        '_description_controle'  => $testLibBrut,
                        '_preuve_controle'       => $procBrut,
                        '_type_controle'         => $c['type_controle']          ?? '',
                        '_criticite'             => (float)($c['criticite_residuelle'] ?? 0),
                        '_responsable'           => $c['responsable_controle']   ?? '',
                        '_needs_ai'              => !empty($testLibBrut),
                    ];
                    $num++;
                }
            }

            if (empty($objectifs)) {
                Log::warning("[PTCI] Aucune ligne générée — RADO et RCI vides ou introuvables");
                return ['lignes' => [], 'total' => 0, 'source' => 'none', 'processus' => '', 'rado_id' => null];
            }

        } catch (\Exception $e) {
            Log::error('[PTCI] loadDepuisRadoEtRci: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return ['lignes' => [], 'total' => 0, 'source' => 'none', 'processus' => '', 'rado_id' => null];
        }

        $src    = !empty($objectifs[0]['_rado_id']) ? 'rado+rci' : 'rci';
        $radoId = $objectifs[0]['_rado_id'] ?? null;

        Log::info("[PTCI] RÉSULTAT FINAL : " . count($objectifs) . " lignes, source={$src}");

        return [
            'lignes'    => $objectifs,
            'total'     => count($objectifs),
            'source'    => $src,
            'processus' => $processusNom,
            'rado_id'   => $radoId,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers privés
    // ══════════════════════════════════════════════════════════════
    private function trouverRciPourObjectif(string $objTxt, array $rciCriteres, array $dejaUtilises): array
    {
        if (empty($rciCriteres)) return [];
        $objLower = mb_strtolower($objTxt);

        // 1. Match exact sur objectif_operationnel
        foreach ($rciCriteres as $c) {
            $code = $c['risque_code'] ?? '';
            if (in_array($code, $dejaUtilises, true)) continue;
            $opLower = mb_strtolower($c['objectif_operationnel'] ?? '');
            if ($opLower && $opLower === $objLower) return $c;
        }

        // 2. Score par mots-clés communs
        $mots = array_values(array_filter(
            explode(' ', preg_replace('/[^a-zàâäéèêëîïôöùûüœ\s]/iu', ' ', $objLower)),
            fn($m) => mb_strlen($m) >= 4
        ));

        $meilleur = null; $meilleurScore = -1;
        foreach ($rciCriteres as $c) {
            $code  = $c['risque_code'] ?? '';
            // Pénaliser les déjà utilisés mais pas les exclure totalement
            $score = in_array($code, $dejaUtilises, true) ? 0 : 50;
            $texte = mb_strtolower(
                ($c['objectif_operationnel'] ?? '') . ' ' .
                ($c['risque_libelle']        ?? '') . ' ' .
                ($c['description_controle']  ?? '') . ' ' .
                ($c['process_name']          ?? '')
            );
            foreach ($mots as $mot) {
                if (mb_strpos($texte, $mot) !== false) $score++;
            }
            if ($score > $meilleurScore) { $meilleurScore = $score; $meilleur = $c; }
        }

        return $meilleur ?? $rciCriteres[0];
    }

    private function parseProcedures(string $text): array
    {
        if (!$text) return [];
        $lines = preg_split('/\n|\r\n|\r|(?:^|\n)\s*[-•·]\s*/m', $text);
        return array_values(array_filter(array_map('trim', $lines), fn($l) => strlen($l) >= 5));
    }

    private function parseDate(mixed $val): string
    {
        if (!$val) return '';
        if (is_numeric($val)) {
            try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$val)->format('Y-m-d'); } catch (\Exception) { return ''; }
        }
        try { return (new \DateTime(trim((string)$val)))->format('Y-m-d'); } catch (\Exception) { return trim((string)$val); }
    }

    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        return array_merge((array)$row, ['lignes' => $this->decodeArr($row->lignes ?? null)]);
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