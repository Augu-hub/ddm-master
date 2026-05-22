<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Auditor\Traits\LoadObjectifsRadoTrait;
use App\Models\Param\Auditor;

/**
 * PROGRAMME DE TRAVAIL DE CONFORMITÉ (PT-CONFORMITE)
 *
 * Flux : RADO.axes_audit → objectifs | AMQ/ACONF → tests + procédures
 * Table : mission_phase_prog_conformite
 * Code  : PTCONF
 */
class ProgConformiteController extends BasePhaseFormController
{
    use LoadObjectifsRadoTrait;

    protected string $table       = 'mission_phase_prog_conformite';
    protected string $formCode    = 'prog-conformite';
    protected string $codePrefix  = 'PTCONF';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ProgConformite';
    protected string $routeEdit   = 'auditor.ac.prog-conformite.edit';

    protected array $validationRules = [
        'fait_par'  => 'nullable|string|max:255',
        'revue_par' => 'nullable|string|max:255',
    ];

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

    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select('a.id', 'a.last_name', 'a.first_name', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"))
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => ['id' => $a->id, 'full_name' => trim($a->full_name), 'role_code' => $a->role_code])
            ->toArray();

        $donneesRCI = $this->loadDepuisRadoEtReferentiel($missionId, $assignmentId);

        $formData = null;
        if ($form) {
            $lignesSauvegardees = $this->decodeArr($form->lignes);
            $hasRadoNow    = $donneesRCI['source'] === 'rado+rci';
            $lignesFromRci = !empty($lignesSauvegardees)
                && empty(array_filter($lignesSauvegardees, fn($l) => !empty($l['_rado_id'])));
            if ($hasRadoNow && $lignesFromRci && !empty($donneesRCI['lignes'])) {
                $lignesSauvegardees = [];
                Log::info('[PTCONF] Rechargement forcé depuis RADO');
            }
            $formData = array_merge((array) $form, ['lignes' => $lignesSauvegardees]);
        }

        $mission = DB::connection('tenant')
            ->table('mission_programmation')->where('id', $missionId)
            ->select('id', 'code_mission', 'libelle')->first();

        $formId = $form?->id ?? null;
        $base   = url('/m/audit.core/ac/preparation/prog-conformite');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $formData,
                'phaseAuditeurs' => $phaseAuditeurs,
                'donneesRCI'     => $donneesRCI,
                'missionContext' => [
                    'mission_id'      => $missionId,
                    'assignment_id'   => $assignmentId,
                    'mission_libelle' => $mission?->libelle      ?? '',
                    'code_mission'    => $mission?->code_mission ?? '',
                    'processus'       => $donneesRCI['processus'] ?? '',
                    'source'          => $donneesRCI['source']    ?? 'none',
                ],
                'urlStore'     => route('auditor.ac.prog-conformite.store'),
                'urlUpdate'    => $formId ? route('auditor.ac.prog-conformite.update',    $formId) : null,
                'urlSoumettre' => $formId ? route('auditor.ac.prog-conformite.soumettre', $formId) : null,
                'urlValider'   => $formId ? route('auditor.ac.prog-conformite.valider',   $formId) : null,
                'urlBase'      => $base,
                'urlIndex'     => route('audit.ac.preparation.prog-conformite'),
                'backUrl'      => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

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
            Log::error('[PTCONF] index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(Request $request, int $formId)
    {
        try {
            $auditor = $this->getAuditor(); if (!$auditor) abort(403);
            $form    = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
            if (!$form) {
                $missionId    = (int)($request->input('mission_id')    ?? session('mission_id',    0));
                $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));
                if ($missionId && $assignmentId) {
                    return redirect()->route('audit.ac.preparation.prog-conformite',
                        ['mission_id' => $missionId, 'assignment_id' => $assignmentId]);
                }
                return redirect()->back()->with('error', 'Programme Conformité introuvable.');
            }
            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id);
            if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, $form));
        } catch (\Exception $e) {
            Log::error('[PTCONF] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

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
                'assignment_id' => $assignmentId, 'mission_id' => $missionId,
                'code'              => $this->genCode($missionId),
                'lignes'            => $this->toJson($request->input('lignes', '[]')),
                'validation_status' => 'draft',
                'created_by'        => $auditor->id,
                'created_at'        => now(), 'updated_at' => now(),
            ]
        ));
        $this->log($assignmentId, $auditor->id, $this->getRole($missionId, $auditor->id), 'saved', null, 'draft');
        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();
        return response()->json([
            'success'      => true, 'form' => $this->hydrateForm($form),
            'message'      => 'Programme Conformité créé.',
            'urlUpdate'    => route('auditor.ac.prog-conformite.update',    $id),
            'urlSoumettre' => route('auditor.ac.prog-conformite.soumettre', $id),
            'urlValider'   => route('auditor.ac.prog-conformite.valider',   $id),
        ]);
    }

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
                'validated' => 'Programme Conformité validé — modification impossible.',
                'in_review' => 'Programme Conformité soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);
        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            ['lignes' => $this->toJson($request->input('lignes', '[]')), 'updated_at' => now()]
        ));
        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'Programme Conformité mis à jour.']);
    }

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
        DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => $auditor->id, 'updated_at' => now(),
        ]);
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
    // loadDepuisRadoEtReferentiel — VERSION CORRIGÉE
    // Utilise LoadObjectifsRadoTrait::chargerObjectifsRadoPourProgramme()
    // ══════════════════════════════════════════════════════════════
    private function loadDepuisRadoEtReferentiel(int $missionId, int $assignmentId): array
    {
        Log::info("[PTCONF] loadDepuisRadoEtReferentiel missionId={$missionId} assignmentId={$assignmentId}");
        $objectifs    = [];
        $processusNom = '';

        try {
            // ── 1. Référentiel Conformité (ACONF + AMQ) ───────────
            $refCriteres = $this->loadCriteresConformite($missionId, $assignmentId);
            Log::info('[PTCONF] Référentiel conformité : ' . count($refCriteres) . ' critères');

            // ── 2. Objectifs RADO via trait ───────────────────────
            $radoData      = $this->chargerObjectifsRadoPourProgramme($missionId, $assignmentId);
            $tousObjectifs = $radoData['objectifs'];
            $radoId        = $radoData['rado_id'];
            Log::info('[PTCONF] ' . count($tousObjectifs) . ' objectifs RADO collectés');

            $num = 1;

            // ── 3a. RADO disponible ───────────────────────────────
            if (!empty($tousObjectifs)) {
                $refUtilises = [];
                foreach ($tousObjectifs as $objRado) {
                    $objNum   = 'O' . $num;
                    $refMatch = !empty($refCriteres)
                        ? $this->trouverRefPourObjectif($objRado['objectif'], $refCriteres, $refUtilises)
                        : [];

                    $matchCode   = $refMatch['code'] ?? ($refMatch['risque_code'] ?? ($refMatch['risk_code'] ?? null));
                    $testLibBrut = $refMatch['description_controle'] ?? $refMatch['libelle_test'] ?? $refMatch['libelle'] ?? '';
                    $procBrut    = $refMatch['preuve_controle']       ?? $refMatch['procedures']   ?? $refMatch['observation'] ?? '';
                    $procsArr    = $this->splitProcedures($procBrut);

                    if ($matchCode) $refUtilises[] = $matchCode;

                    $objectifs[] = [
                        'num'      => $objNum,
                        'objectif' => $objRado['objectif'],   // ← TEXTE EXACT DU RADO
                        'ref_rci'  => $matchCode ?? '',
                        'tests'    => [[
                            'ref'                => 'T_' . $objNum,
                            'libelle'            => $testLibBrut,
                            'procedures'         => $procsArr,
                            'auditeur'           => $refMatch['responsable']        ?? ($refMatch['responsable_controle'] ?? ''),
                            'date_debut'         => '',
                            'date_fin'           => '',
                            'lieu'               => $refMatch['lieu']               ?? ($refMatch['processus'] ?? ($refMatch['marche_intitule'] ?? '')),
                            'taille_echantillon' => '',
                            'periode_testee'     => '',
                        ]],
                        '_source'                => 'RADO+CONF/' . ($matchCode ?? $num),
                        '_rado_id'               => $radoId,
                        '_rci_id'                => $refMatch['form_id'] ?? null,
                        '_axe_rado'              => $objRado['axe']           ?? '',
                        '_priorite'              => $objRado['priorite']      ?? '',
                        '_indicateurs'           => $objRado['indicateurs']   ?? '',
                        '_criteres_eval'         => $objRado['criteres_eval'] ?? '',
                        '_risque_code'           => $matchCode ?? '',
                        '_risque_libelle'        => $refMatch['libelle_norme'] ?? ($refMatch['libelle'] ?? ''),
                        '_process_name'          => $refMatch['process_name']  ?? $processusNom,
                        '_objectif_operationnel' => $objRado['objectif'],
                        '_description_controle'  => $testLibBrut,
                        '_preuve_controle'       => $procBrut,
                        '_type_controle'         => $refMatch['type']  ?? ($refMatch['type_controle'] ?? 'conformite'),
                        '_criticite'             => (float)($refMatch['criticite_residuelle'] ?? ($refMatch['score'] ?? 0)),
                        '_responsable'           => $refMatch['responsable'] ?? ($refMatch['responsable_controle'] ?? ''),
                        '_needs_ai'              => true,
                    ];
                    $num++;
                }
                Log::info('[PTCONF] ' . count($objectifs) . ' lignes construites depuis RADO');
            }

            // ── 3b. Fallback référentiel seul ─────────────────────
            if (empty($objectifs) && !empty($refCriteres)) {
                Log::info('[PTCONF] Fallback référentiel pur : ' . count($refCriteres) . ' critères');
                foreach ($refCriteres as $c) {
                    $objNum      = 'O' . $num;
                    $codeLabel   = $c['code'] ?? $num;
                    $testLibBrut = $c['libelle_test'] ?? $c['libelle'] ?? '';
                    $procBrut    = $c['procedures']   ?? '';
                    $objectifs[] = [
                        'num'      => $objNum,
                        'objectif' => $c['objectif'] ?? $c['libelle_norme'] ?? "Vérifier conformité ({$codeLabel})",
                        'ref_rci'  => $c['code'] ?? '',
                        'tests'    => [[
                            'ref'                => 'T_' . $objNum,
                            'libelle'            => $testLibBrut,
                            'procedures'         => $this->splitProcedures($procBrut),
                            'auditeur'           => $c['responsable'] ?? '',
                            'date_debut'         => '',
                            'date_fin'           => '',
                            'lieu'               => $c['lieu'] ?? '',
                            'taille_echantillon' => '',
                            'periode_testee'     => '',
                        ]],
                        '_source'                => 'CONF/' . ($c['code'] ?? $num),
                        '_rado_id'               => null,
                        '_rci_id'                => $c['form_id'] ?? null,
                        '_axe_rado'              => '', '_priorite' => '', '_indicateurs' => '', '_criteres_eval' => '',
                        '_risque_code'           => $c['code']          ?? '',
                        '_risque_libelle'        => $c['libelle_norme'] ?? '',
                        '_process_name'          => $processusNom,
                        '_objectif_operationnel' => $c['objectif']      ?? '',
                        '_description_controle'  => $testLibBrut,
                        '_preuve_controle'       => $procBrut,
                        '_type_controle'         => $c['type'] ?? 'conformite',
                        '_criticite'             => 0,
                        '_responsable'           => $c['responsable'] ?? '',
                        '_needs_ai'              => !empty($testLibBrut),
                    ];
                    $num++;
                }
            }

            if (empty($objectifs)) {
                Log::warning('[PTCONF] Aucune ligne générée');
                return ['lignes' => [], 'total' => 0, 'source' => 'none', 'processus' => '', 'rado_id' => null];
            }

        } catch (\Exception $e) {
            Log::error('[PTCONF] loadDepuisRadoEtReferentiel: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return ['lignes' => [], 'total' => 0, 'source' => 'none', 'processus' => '', 'rado_id' => null];
        }

        $src    = !empty($objectifs[0]['_rado_id']) ? 'rado+rci' : 'rci';
        $radoId = $objectifs[0]['_rado_id'] ?? null;
        Log::info('[PTCONF] RÉSULTAT : ' . count($objectifs) . " lignes ({$src})");

        return ['lignes' => $objectifs, 'total' => count($objectifs), 'source' => $src, 'processus' => $processusNom, 'rado_id' => $radoId];
    }

    // ── Référentiel : ACONF + AMQ ─────────────────────────────────
    private function loadCriteresConformite(int $missionId, int $assignmentId): array
    {
        $criteres = [];

        // Source 1 : mission_phase_aconf → analyse_conformite_items
        $aconfForms = DB::connection('tenant')->table('mission_phase_aconf')
            ->where('assignment_id', $assignmentId)->select('id')->get();
        if ($aconfForms->isEmpty()) {
            $aconfForms = DB::connection('tenant')->table('mission_phase_aconf')
                ->where('mission_id', $missionId)->select('id')->get();
        }
        foreach ($aconfForms as $aconf) {
            $items = DB::connection('tenant')->table('analyse_conformite_items')
                ->where('analyse_conformite_id', $aconf->id)
                ->select('ref_article', 'libelle_norme', 'reponse', 'forces', 'faiblesses', 'objectif')->get();
            foreach ($items as $item) {
                $criteres[] = [
                    'code'          => $item->ref_article   ?? '',
                    'libelle_norme' => $item->libelle_norme ?? '',
                    'libelle_test'  => $item->faiblesses    ?? $item->objectif ?? '',
                    'procedures'    => $item->forces        ?? '',
                    'objectif'      => $item->objectif      ?? '',
                    'responsable'   => '',
                    'lieu'          => '',
                    'type'          => 'conformite',
                    'form_id'       => $aconf->id,
                ];
            }
        }

        // Source 2 : mission_phase_amq → amq_marches → amq_etapes
        $amqForms = DB::connection('tenant')->table('mission_phase_amq')
            ->where('assignment_id', $assignmentId)->select('id')->get();
        if ($amqForms->isEmpty()) {
            $amqForms = DB::connection('tenant')->table('mission_phase_amq')
                ->where('mission_id', $missionId)->select('id')->get();
        }
        foreach ($amqForms as $amq) {
            $marches = DB::connection('tenant')->table('amq_marches')
                ->where('amq_id', $amq->id)->select('id', 'intitule', 'reference')->get();
            foreach ($marches as $marche) {
                $etapes = DB::connection('tenant')->table('amq_etapes')
                    ->where('marche_id', $marche->id)
                    ->select('libelle', 'statut', 'observation', 'responsable')->get();
                foreach ($etapes as $etape) {
                    $criteres[] = [
                        'code'          => ($marche->reference ?? 'AMQ') . '/' . $marche->id,
                        'libelle_norme' => $marche->intitule   ?? '',
                        'libelle_test'  => $etape->libelle     ?? '',
                        'procedures'    => $etape->observation ?? '',
                        'objectif'      => $etape->libelle     ?? '',
                        'responsable'   => $etape->responsable ?? '',
                        'lieu'          => '',
                        'type'          => 'marche',
                        'form_id'       => $amq->id,
                    ];
                }
            }
        }

        return $criteres;
    }

    private function trouverRefPourObjectif(string $objTxt, array $criteres, array $dejaUtilises): array
    {
        if (empty($criteres)) return [];
        $objLower = mb_strtolower($objTxt);
        $mots = array_values(array_filter(
            explode(' ', preg_replace('/[^a-zàâäéèêëîïôöùûüœ\s]/iu', ' ', $objLower)),
            fn($m) => mb_strlen($m) >= 4
        ));
        $meilleur = null; $meilleurScore = -1;
        foreach ($criteres as $c) {
            $code  = $c['code'] ?? '';
            $score = in_array($code, $dejaUtilises, true) ? 0 : 50;
            $texte = mb_strtolower(($c['objectif'] ?? '') . ' ' . ($c['libelle_norme'] ?? '') . ' ' . ($c['libelle_test'] ?? ''));
            foreach ($mots as $mot) { if (mb_strpos($texte, $mot) !== false) $score++; }
            if ($score > $meilleurScore) { $meilleurScore = $score; $meilleur = $c; }
        }
        return $meilleur ?? $criteres[0];
    }

    private function splitProcedures(string $text): array
    {
        if (!$text) return [];
        $lines = preg_split('/\n|\r\n|\r|(?:^|\n)\s*[-•·]\s*/m', $text);
        return array_values(array_filter(array_map('trim', $lines), fn($l) => strlen($l) >= 5));
    }

    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        return array_merge((array) $row, ['lignes' => $this->decodeArr($row->lignes ?? null)]);
    }

    private function toJson(mixed $v): string
    {
        if (is_string($v)) { json_decode($v); if (json_last_error() === JSON_ERROR_NONE) return $v; }
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }
}