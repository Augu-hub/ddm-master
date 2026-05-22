<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Auditor\Traits\LoadObjectifsRadoTrait;
use App\Models\Param\Auditor;

/**
 * PROGRAMME DE TRAVAIL DES MARCHÉS (PT-MARCHES)
 *
 * Table : mission_phase_prog_marches
 * Code  : PTMAR
 *
 * Flux : RADO.axes_audit → objectifs (tous statuts)
 *        Référentiel : amq_marches + amq_etapes → tests + procédures
 */
class ProgMarchesController extends BasePhaseFormController
{
    use LoadObjectifsRadoTrait;

    protected string $table       = 'mission_phase_prog_marches';
    protected string $formCode    = 'prog-marches';
    protected string $codePrefix  = 'PTMAR';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ProgMarches';
    protected string $routeEdit   = 'auditor.ac.prog-marches.edit';

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

        $donneesRCI = $this->loadDepuisRadoEtMarches($missionId, $assignmentId);

        $formData = null;
        if ($form) {
            $lignesSauvegardees = $this->decodeArr($form->lignes);
            $hasRadoNow    = $donneesRCI['source'] === 'rado+rci';
            $lignesFromRci = !empty($lignesSauvegardees)
                && empty(array_filter($lignesSauvegardees, fn($l) => !empty($l['_rado_id'])));
            if ($hasRadoNow && $lignesFromRci && !empty($donneesRCI['lignes'])) {
                $lignesSauvegardees = [];
                Log::info('[PTMAR] Rechargement forcé depuis RADO');
            }
            $formData = array_merge((array) $form, ['lignes' => $lignesSauvegardees]);
        }

        $mission = DB::connection('tenant')
            ->table('mission_programmation')->where('id', $missionId)
            ->select('id', 'code_mission', 'libelle')->first();

        $formId = $form?->id ?? null;
        $base   = url('/m/audit.core/ac/preparation/prog-marches');

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
                'urlStore'     => route('auditor.ac.prog-marches.store'),
                'urlUpdate'    => $formId ? route('auditor.ac.prog-marches.update',    $formId) : null,
                'urlSoumettre' => $formId ? route('auditor.ac.prog-marches.soumettre', $formId) : null,
                'urlValider'   => $formId ? route('auditor.ac.prog-marches.valider',   $formId) : null,
                'urlBase'      => $base,
                'urlIndex'     => route('audit.ac.preparation.prog-marches'),
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
            Log::error('[PTMAR] index: ' . $e->getMessage());
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
                    return redirect()->route('audit.ac.preparation.prog-marches',
                        ['mission_id' => $missionId, 'assignment_id' => $assignmentId]);
                }
                return redirect()->back()->with('error', 'Programme Marchés introuvable.');
            }
            $missionId    = (int)($request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id);
            if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, $form));
        } catch (\Exception $e) {
            Log::error('[PTMAR] edit: ' . $e->getMessage());
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
                'assignment_id'     => $assignmentId, 'mission_id' => $missionId,
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
            'message'      => 'Programme Marchés créé.',
            'urlUpdate'    => route('auditor.ac.prog-marches.update',    $id),
            'urlSoumettre' => route('auditor.ac.prog-marches.soumettre', $id),
            'urlValider'   => route('auditor.ac.prog-marches.valider',   $id),
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
                'validated' => 'Programme Marchés validé — modification impossible.',
                'in_review' => 'Programme Marchés soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);
        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            ['lignes' => $this->toJson($request->input('lignes', '[]')), 'updated_at' => now()]
        ));
        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'Programme Marchés mis à jour.']);
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
    // loadDepuisRadoEtMarches — VERSION CORRIGÉE
    // ══════════════════════════════════════════════════════════════
    private function loadDepuisRadoEtMarches(int $missionId, int $assignmentId): array
    {
        Log::info("[PTMAR] loadDepuisRadoEtMarches missionId={$missionId} assignmentId={$assignmentId}");
        $objectifs    = [];
        $processusNom = '';

        try {
            // ── 1. Référentiel AMQ ────────────────────────────────
            $refMarches = $this->loadEtapesMarches($missionId, $assignmentId);
            Log::info('[PTMAR] Étapes AMQ : ' . count($refMarches));

            // ── 2. Objectifs RADO via trait ───────────────────────
            $radoData      = $this->chargerObjectifsRadoPourProgramme($missionId, $assignmentId);
            $tousObjectifs = $radoData['objectifs'];
            $radoId        = $radoData['rado_id'];
            Log::info('[PTMAR] ' . count($tousObjectifs) . ' objectifs RADO collectés');

            $num = 1;

            // ── 3a. RADO disponible ───────────────────────────────
            if (!empty($tousObjectifs)) {
                $refUtilises = [];
                foreach ($tousObjectifs as $objRado) {
                    $objNum   = 'O' . $num;
                    $refMatch = !empty($refMarches)
                        ? $this->trouverRefPourObjectif($objRado['objectif'], $refMarches, $refUtilises)
                        : [];

                    $matchCode   = $refMatch['code'] ?? null;
                    $testLibBrut = $refMatch['libelle']     ?? '';
                    $procBrut    = $refMatch['observation'] ?? '';
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
                            'auditeur'           => $refMatch['responsable']     ?? '',
                            'date_debut'         => '',
                            'date_fin'           => '',
                            'lieu'               => $refMatch['marche_intitule'] ?? '',
                            'taille_echantillon' => '',
                            'periode_testee'     => '',
                        ]],
                        '_source'                => 'RADO+AMQ/' . ($matchCode ?? $num),
                        '_rado_id'               => $radoId,
                        '_rci_id'                => $refMatch['form_id'] ?? null,
                        '_axe_rado'              => $objRado['axe']           ?? '',
                        '_priorite'              => $objRado['priorite']      ?? '',
                        '_indicateurs'           => $objRado['indicateurs']   ?? '',
                        '_criteres_eval'         => $objRado['criteres_eval'] ?? '',
                        '_risque_code'           => $matchCode ?? '',
                        '_risque_libelle'        => $refMatch['marche_intitule'] ?? '',
                        '_process_name'          => $processusNom,
                        '_objectif_operationnel' => $objRado['objectif'],
                        '_description_controle'  => $testLibBrut,
                        '_preuve_controle'       => $procBrut,
                        '_type_controle'         => 'marche',
                        '_criticite'             => 0,
                        '_responsable'           => $refMatch['responsable'] ?? '',
                        '_needs_ai'              => true,
                    ];
                    $num++;
                }
                Log::info('[PTMAR] ' . count($objectifs) . ' lignes construites depuis RADO');
            }

            // ── 3b. Fallback AMQ pur ──────────────────────────────
            if (empty($objectifs) && !empty($refMarches)) {
                Log::info('[PTMAR] Fallback AMQ pur : ' . count($refMarches) . ' étapes');
                foreach ($refMarches as $c) {
                    $objNum = 'O' . $num;
                    $objectifs[] = [
                        'num'      => $objNum,
                        'objectif' => 'Vérifier : ' . ($c['libelle'] ?? $c['marche_intitule'] ?? ''),
                        'ref_rci'  => $c['code'] ?? '',
                        'tests'    => [[
                            'ref'                => 'T_' . $objNum,
                            'libelle'            => $c['libelle']     ?? '',
                            'procedures'         => $this->splitProcedures($c['observation'] ?? ''),
                            'auditeur'           => $c['responsable'] ?? '',
                            'date_debut'         => '',
                            'date_fin'           => '',
                            'lieu'               => $c['marche_intitule'] ?? '',
                            'taille_echantillon' => '',
                            'periode_testee'     => '',
                        ]],
                        '_source'                => 'AMQ/' . ($c['code'] ?? $num),
                        '_rado_id'               => null, '_rci_id' => $c['form_id'] ?? null,
                        '_axe_rado'              => '', '_priorite' => '', '_indicateurs' => '', '_criteres_eval' => '',
                        '_risque_code'           => $c['code']            ?? '',
                        '_risque_libelle'        => $c['marche_intitule'] ?? '',
                        '_process_name'          => $processusNom,
                        '_objectif_operationnel' => '',
                        '_description_controle'  => $c['libelle']     ?? '',
                        '_preuve_controle'       => $c['observation'] ?? '',
                        '_type_controle'         => 'marche',
                        '_criticite'             => 0,
                        '_responsable'           => $c['responsable'] ?? '',
                        '_needs_ai'              => true,
                    ];
                    $num++;
                }
            }

            if (empty($objectifs)) {
                Log::warning('[PTMAR] Aucune ligne générée');
                return ['lignes' => [], 'total' => 0, 'source' => 'none', 'processus' => '', 'rado_id' => null];
            }

        } catch (\Exception $e) {
            Log::error('[PTMAR] loadDepuisRadoEtMarches: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return ['lignes' => [], 'total' => 0, 'source' => 'none', 'processus' => '', 'rado_id' => null];
        }

        $src    = !empty($objectifs[0]['_rado_id']) ? 'rado+rci' : 'rci';
        $radoId = $objectifs[0]['_rado_id'] ?? null;
        Log::info('[PTMAR] RÉSULTAT : ' . count($objectifs) . " lignes ({$src})");

        return ['lignes' => $objectifs, 'total' => count($objectifs), 'source' => $src, 'processus' => $processusNom, 'rado_id' => $radoId];
    }

    private function loadEtapesMarches(int $missionId, int $assignmentId): array
    {
        $etapes   = [];
        $amqForms = DB::connection('tenant')->table('mission_phase_amq')
            ->where('assignment_id', $assignmentId)->select('id')->get();
        if ($amqForms->isEmpty()) {
            $amqForms = DB::connection('tenant')->table('mission_phase_amq')
                ->where('mission_id', $missionId)->select('id')->get();
        }
        foreach ($amqForms as $amq) {
            $marches = DB::connection('tenant')->table('amq_marches')
                ->where('amq_id', $amq->id)->select('id', 'intitule', 'reference', 'montant')->get();
            foreach ($marches as $marche) {
                $lignes = DB::connection('tenant')->table('amq_etapes')
                    ->where('marche_id', $marche->id)
                    ->select('libelle', 'statut', 'observation', 'responsable')->get();
                foreach ($lignes as $etape) {
                    $etapes[] = [
                        'code'            => ($marche->reference ?? 'AMQ') . '_' . $marche->id . '_' . count($etapes),
                        'marche_intitule' => $marche->intitule   ?? '',
                        'marche_ref'      => $marche->reference  ?? '',
                        'libelle'         => $etape->libelle     ?? '',
                        'observation'     => $etape->observation ?? '',
                        'responsable'     => $etape->responsable ?? '',
                        'statut'          => $etape->statut      ?? '',
                        'form_id'         => $amq->id,
                    ];
                }
            }
        }
        return $etapes;
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
            $texte = mb_strtolower(($c['libelle'] ?? '') . ' ' . ($c['marche_intitule'] ?? '') . ' ' . ($c['observation'] ?? ''));
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