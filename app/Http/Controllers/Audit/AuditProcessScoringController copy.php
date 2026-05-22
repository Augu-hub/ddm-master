<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\AuditMissionAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuditProcessScoringController extends Controller
{
    protected AuditMissionAIService $aiService;

    public function __construct(AuditMissionAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    protected function ai(): AuditMissionAIService
    {
        return $this->aiService;
    }

    // =========================================================================
    // INDEX
    // =========================================================================
    public function index(Request $request)
    {
        try {
            $tenantId    = auth()->user()->tenant_id ?? 0;
            $currentYear = (int) date('Y');  // pour filtrer les risques par année

            /* ---- ENTITÉS ---- */
            $entities = DB::table('entities')
                ->orderBy('name')
                ->select('id', 'name', 'code_base')
                ->get()
                ->map(fn($e) => [
                    'id'        => (int) $e->id,
                    'name'      => $e->name,
                    'code_base' => $e->code_base,
                ])->toArray();

            /* ---- FACTEURS ---- */
            $factors = DB::table('audit_factors')
                ->where('is_active', 1)
                ->orderBy('order_position')
                ->select('id', 'label', 'order_position')
                ->get()
                ->map(fn($f) => ['id' => (int)$f->id, 'label' => $f->label])->toArray();

            /* ---- ÉCHELLES ---- */
            $scales = DB::table('audit_factor_scales')
                ->orderBy('value')
                ->select('id', 'value', 'label')
                ->get()
                ->map(fn($s) => ['id' => (int)$s->id, 'value' => (int)$s->value, 'label' => $s->label])->toArray();

            /* ---- TYPES DE MISSION ---- */
            $missionTypes = DB::table('mission_types')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->select('id', 'code', 'label')
                ->get()
                ->map(fn($mt) => ['id' => (int)$mt->id, 'code' => $mt->code, 'label' => $mt->label])->toArray();

            /* ====================================================
             * RISQUES — jointure directe via risks.entity_id
             * Filtrés par l'année courante (risks.year)
             * ==================================================== */
            $allRisks = DB::table('risks')
                ->where('risks.tenant_id', $tenantId)
                ->whereNull('risks.deleted_at')
                ->whereNotNull('risks.entity_id')
                ->where('risks.year', $currentYear)       // ← filtre sur l'année
                ->leftJoin('processes', 'risks.process_id', '=', 'processes.id')
                ->select(
                    'risks.id',
                    'risks.code',
                    'risks.label',
                    'risks.entity_id',
                    'risks.process_id',
                    'risks.year',
                    'risks.status',
                    'processes.code as process_code',
                    'processes.name as process_name',
                    DB::raw('IFNULL(risks.frequency_level_id * risks.impact_level_id, 0) as criticality_gross')
                )
                ->orderBy('criticality_gross', 'desc')
                ->get()
                ->map(fn($r) => [
                    'id'                => (int) $r->id,
                    'code'              => $r->code,
                    'label'             => $r->label,
                    'entity_id'         => (int) $r->entity_id,
                    'process_id'        => (int) $r->process_id,
                    'year'              => (int) $r->year,
                    'process_code'      => $r->process_code,
                    'process_name'      => $r->process_name,
                    'criticality_gross' => (int) $r->criticality_gross,
                    'status'            => $r->status,
                ])->toArray();

            /* ====================================================
             * PROCESSUS — déduits depuis les risques chargés
             * ==================================================== */
            $processIds = array_unique(array_column($allRisks, 'process_id'));
            $processes  = [];

            if (!empty($processIds)) {
                $rankings = DB::table('audit_process_rankings')
                    ->whereIn('process_id', $processIds)
                    ->select('process_id', 'entity_id', 'average_score', 'ranking_position')
                    ->get()
                    ->groupBy('process_id');

                $processes = DB::table('processes')
                    ->whereIn('id', $processIds)
                    ->select('id', 'code', 'name')
                    ->orderBy('code')
                    ->get()
                    ->map(function ($p) use ($rankings) {
                        $rks = $rankings->get($p->id, collect());
                        $avg = $rks->avg('average_score') ?? 0;
                        $pos = $rks->sortByDesc('average_score')->first()?->ranking_position ?? 0;
                        return [
                            'process_id'       => (int) $p->id,
                            'code'             => $p->code,
                            'name'             => $p->name,
                            'average_score'    => round((float) $avg, 2),
                            'ranking_position' => (int) $pos,
                        ];
                    })->toArray();
            }

            /* ====================================================
             * PONDÉRATION — clé `${entity_id}_${process_id}`
             * ==================================================== */
            $ponderation = [];
            $entityIds   = array_unique(array_column($allRisks, 'entity_id'));

            foreach ($entityIds as $eid) {
                $procIdsForEntity = array_unique(
                    array_column(
                        array_filter($allRisks, fn($r) => $r['entity_id'] === $eid),
                        'process_id'
                    )
                );
                foreach ($procIdsForEntity as $pid) {
                    $ponderation["{$eid}_{$pid}"] = ['year_2024' => 0, 'year_2025' => 0, 'year_2026' => 0];
                }
            }

            if (!empty($entityIds) && !empty($processIds)) {
                $rows = DB::table('audit_process_year_selection')
                    ->whereIn('entity_id',  $entityIds)
                    ->whereIn('process_id', $processIds)
                    ->where('is_selected',  1)
                    ->select('entity_id', 'process_id', 'year')
                    ->get();

                foreach ($rows as $row) {
                    $key = "{$row->entity_id}_{$row->process_id}";
                    if (isset($ponderation[$key])) {
                        $ponderation[$key]["year_{$row->year}"] = 1;
                    }
                }
            }

            /* ====================================================
             * SCORES — clé `${process_id}_${entity_id}`
             * ==================================================== */
            $scores = [];
            if (!empty($entityIds) && !empty($processIds)) {
                DB::table('audit_process_scores')
                    ->whereIn('entity_id',  $entityIds)
                    ->whereIn('process_id', $processIds)
                    ->select('process_id', 'entity_id', 'factor_id', 'score')
                    ->get()
                    ->each(function ($row) use (&$scores) {
                        $key = "{$row->process_id}_{$row->entity_id}";
                        if (!isset($scores[$key])) $scores[$key] = ['scores' => []];
                        $scores[$key]['scores'][$row->factor_id] = (int) $row->score;
                    });
            }

            return Inertia::render('dashboards/Audit/AuditProcessScoring', [
                'entities'         => $entities,
                'selectedEntityId' => null,
                'processes'        => $processes,
                'risks'            => $allRisks,
                'ponderation'      => $ponderation,
                'factors'          => $factors,
                'scales'           => $scales,
                'scores'           => $scores,
                'missionTypes'     => $missionTypes,
                'currentYear'      => $currentYear,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ index: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // =========================================================================
    // RISQUES PAR ENTITÉS (missions multi-entités)
    // Jointure directe via risks.entity_id + filtre year
    // =========================================================================
    public function getRisksByEntities(Request $request)
    {
        $entityIds   = $request->input('entity_ids', []);
        $year        = (int) $request->input('year', date('Y'));

        if (empty($entityIds)) {
            return response()->json(['success' => true, 'risks_by_entity' => []]);
        }

        $tenantId = auth()->user()->tenant_id ?? 0;

        $risks = DB::table('risks')
            ->whereIn('risks.entity_id', $entityIds)
            ->where('risks.tenant_id',   $tenantId)
            ->whereNull('risks.deleted_at')
            ->where('risks.year',        $year)           // ← filtre sur l'année
            ->whereIn('risks.status', ['identified', 'assessed'])
            ->leftJoin('processes', 'risks.process_id', '=', 'processes.id')
            ->select(
                'risks.id',
                'risks.code',
                'risks.label',
                'risks.entity_id',
                'risks.process_id',
                'risks.year',
                'risks.status',
                'processes.code as process_code',
                DB::raw('IFNULL(risks.frequency_level_id * risks.impact_level_id, 0) as criticality_gross')
            )
            ->orderBy('criticality_gross', 'desc')
            ->get();

        $processIds = $risks->pluck('process_id')->unique()->values()->toArray();
        $rankings   = DB::table('audit_process_rankings')
            ->whereIn('process_id', $processIds)
            ->whereIn('entity_id',  $entityIds)
            ->select('process_id', 'entity_id', 'average_score')
            ->get()
            ->groupBy('entity_id');

        $entityNames = DB::table('entities')
            ->whereIn('id', $entityIds)
            ->pluck('name', 'id');

        $risksByEntity = [];
        foreach ($entityIds as $entityId) {
            $rankingsForEntity = $rankings->get($entityId, collect());

            $risksForEntity = $risks
                ->filter(fn($r) => $r->entity_id == $entityId)
                ->map(function ($r) use ($rankingsForEntity) {
                    $rk = $rankingsForEntity->firstWhere('process_id', $r->process_id);
                    return [
                        'id'                    => (int) $r->id,
                        'code'                  => $r->code,
                        'label'                 => $r->label,
                        'entity_id'             => (int) $r->entity_id,
                        'process_id'            => (int) $r->process_id,
                        'process_code'          => $r->process_code,
                        'criticality_gross'     => (int) $r->criticality_gross,
                        'status'                => $r->status,
                        'year'                  => (int) $r->year,
                        'process_average_score' => $rk ? (float) $rk->average_score : 0,
                    ];
                })->values();

            $risksByEntity[$entityId] = [
                'entity_id'   => (int) $entityId,
                'entity_name' => $entityNames[$entityId] ?? "Entité #{$entityId}",
                'risks'       => $risksForEntity,
            ];
        }

        return response()->json(['success' => true, 'risks_by_entity' => $risksByEntity]);
    }

    // =========================================================================
    // PONDÉRATION
    // =========================================================================
    public function savePonderation(Request $request)
    {
        try {
            $v = $request->validate([
                'entity_id'   => 'required|integer',
                'process_id'  => 'required|integer',
                'year'        => 'required|integer|in:2024,2025,2026',
                'is_selected' => 'required|integer|in:0,1',
            ]);

            if ((int) $v['is_selected'] === 1) {
                DB::table('audit_process_year_selection')->updateOrInsert(
                    ['entity_id' => $v['entity_id'], 'process_id' => $v['process_id'], 'year' => $v['year']],
                    ['is_selected' => 1, 'updated_at' => now()]
                );
            } else {
                DB::table('audit_process_year_selection')
                    ->where('entity_id',  $v['entity_id'])
                    ->where('process_id', $v['process_id'])
                    ->where('year',       $v['year'])
                    ->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('❌ savePonderation: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SCORES
    // =========================================================================
    public function updateScore(Request $request, $processId, $entityId)
    {
        $v = $request->validate([
            'factor_scores'   => 'required|array',
            'factor_scores.*' => 'nullable|integer|min:1|max:5',
        ]);
        try {
            DB::transaction(function () use ($processId, $entityId, $v) {
                foreach ($v['factor_scores'] as $factorId => $score) {
                    if ($score === null) continue;
                    DB::table('audit_process_scores')->updateOrInsert(
                        ['process_id' => (int)$processId, 'factor_id' => (int)$factorId, 'entity_id' => (int)$entityId],
                        ['score' => (int)$score, 'score_date' => now()->toDateString(), 'updated_at' => now()]
                    );
                }
            });
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    // =========================================================================
    // MISSIONS : créer
    // =========================================================================
    public function storeMission(Request $request)
    {
        try {
            $v = $request->validate([
                'entity_ids'          => 'required|array',
                'entity_ids.*'        => 'integer',
                'process_id'          => 'nullable|integer',
                'selected_risk_ids'   => 'nullable|array',
                'selected_risk_ids.*' => 'integer',
                'type_mission'        => 'required|string',
                'but'                 => 'required|string',
                'description'         => 'nullable|string',
                'preoccupation'       => 'nullable|string',
                'resultat'            => 'nullable|string',
                'champ_mission'       => 'nullable|string',
                'fonction_processus'  => 'nullable|string',
                'procedure'           => 'nullable|string',
                'proposition_date'    => 'nullable|date',
            ]);

            $entityIds = $v['entity_ids'];
            if (empty($entityIds)) return response()->json(['success' => false, 'error' => 'Aucune entité'], 400);

            $missionType = DB::table('mission_types')->where('code', $v['type_mission'])->first();
            if (!$missionType) return response()->json(['success' => false, 'error' => 'Type introuvable'], 400);

            $missionSource = $this->getOrCreateMissionSource($entityIds[0]);
            $annualPlan    = $this->getOrCreateAnnualPlan($entityIds[0]);
            $missionNumber = $this->generateMissionNumber();

            $this->ensureAuditMissionEntitiesTable();
            $this->createAuditMissionRisksTable();

            $missionId = DB::transaction(function () use ($v, $missionNumber, $annualPlan, $missionType, $missionSource, $entityIds) {
                // entity_id = première entité (colonne FK obligatoire sur audit_missions)
                $id = DB::table('audit_missions')->insertGetId([
                    'code'                => $missionNumber,
                    'entity_id'           => $entityIds[0],   // ← FK obligatoire
                    'annual_plan_id'      => $annualPlan->id,
                    'mission_source_id'   => $missionSource->id,
                    'process_id'          => $v['process_id'] ?? null,
                    'mission_type_id'     => $missionType->id,
                    'title'               => 'Mission ' . $missionNumber,
                    'risk_id'             => !empty($v['selected_risk_ids']) ? $v['selected_risk_ids'][0] : null,
                    'but'                 => $v['but'],
                    'description'         => $v['description']        ?? null,
                    'preoccupation'       => $v['preoccupation']      ?? null,
                    'resultat'            => $v['resultat']           ?? null,
                    'champ_mission'       => $v['champ_mission']      ?? null,
                    'fonction_processus'  => $v['fonction_processus'] ?? null,
                    'procedure'           => $v['procedure']          ?? null,
                    'priority_rank'       => 1,
                    'scheduled_start_date'=> $v['proposition_date']   ?? null,
                    'status'              => 'proposed',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                foreach ($entityIds as $eid) {
                    DB::table('audit_mission_entities')->insertOrIgnore([
                        'audit_mission_id' => $id, 'entity_id' => $eid,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                if (!empty($v['selected_risk_ids'])) {
                    $this->associateAuditRisksToMission($id, $v['selected_risk_ids']);
                }

                return $id;
            });

            return response()->json(['success' => true, 'mission_id' => $missionId, 'mission_code' => $missionNumber]);

        } catch (\Exception $e) {
            Log::error('❌ storeMission: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MISSIONS : liste
    // =========================================================================
    public function getMissions(Request $request)
    {
        try {
            $entityIds = $request->input('entity_ids', []);
            $entityId  = $request->input('entity_id');
            if ($entityId && empty($entityIds)) $entityIds = [$entityId];

            $query = DB::table('audit_missions as am')
                ->leftJoin('processes as p',       'am.process_id',      '=', 'p.id')
                ->leftJoin('mission_types as mt',  'am.mission_type_id', '=', 'mt.id')
                ->select(
                    'am.id','am.code','am.process_id','am.but','am.description','am.preoccupation',
                    'am.resultat','am.champ_mission','am.fonction_processus','am.procedure',
                    'am.status','am.created_at','am.title',
                    'p.code as process_code','p.name as process_name',
                    'mt.label as type_mission_label','mt.code as type_code'
                )
                ->orderBy('am.created_at', 'desc');

            if (!empty($entityIds)) {
                $missionIds = DB::table('audit_mission_entities')
                    ->whereIn('entity_id', $entityIds)
                    ->pluck('audit_mission_id')->unique()->toArray();
                if (empty($missionIds)) return response()->json(['success' => true, 'missions' => []]);
                $query->whereIn('am.id', $missionIds);
            }

            $tableRisksExists = !empty(DB::select("SHOW TABLES LIKE 'audit_mission_risks'"));

            $missions = $query->get()->map(function ($m) use ($tableRisksExists) {
                $entities = DB::table('audit_mission_entities as ame')
                    ->join('entities as e', 'ame.entity_id', '=', 'e.id')
                    ->where('ame.audit_mission_id', $m->id)
                    ->select('e.id','e.name','e.code_base')->get()->toArray();

                $risks = [];
                if ($tableRisksExists) {
                    $risks = DB::table('audit_mission_risks as amr')
                        ->join('risks as r', 'amr.risk_id', '=', 'r.id')
                        ->where('amr.audit_mission_id', $m->id)
                        ->select('r.id','r.code','r.label')->get()->toArray();
                }

                return [
                    'id'                => $m->id,
                    'numero'            => $m->code,
                    'processCode'       => $m->process_code,
                    'processName'       => $m->process_name,
                    'type_mission'      => $m->type_mission_label ?? 'N/A',
                    'type_code'         => $m->type_code,
                    'but'               => $m->but,
                    'description'       => $m->description,
                    'preoccupation'     => $m->preoccupation,
                    'resultat'          => $m->resultat,
                    'champ_mission'     => $m->champ_mission,
                    'fonction_processus'=> $m->fonction_processus,
                    'procedure'         => $m->procedure,
                    'entities'          => $entities,
                    'risks'             => $risks,
                    'status'            => $m->status,
                    'title'             => $m->title,
                    'created_at'        => $m->created_at,
                ];
            })->toArray();

            return response()->json(['success' => true, 'missions' => $missions]);

        } catch (\Exception $e) {
            Log::error('❌ getMissions: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MISSIONS : supprimer
    // =========================================================================
    public function deleteMission($id)
    {
        try {
            DB::transaction(function () use ($id) {
                DB::table('audit_mission_entities')->where('audit_mission_id', $id)->delete();
                if (!empty(DB::select("SHOW TABLES LIKE 'audit_mission_risks'"))) {
                    DB::table('audit_mission_risks')->where('audit_mission_id', $id)->delete();
                }
                DB::table('audit_missions')->where('id', $id)->delete();
            });
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    // =========================================================================
    // IA — AuditMissionAIService (App\Services\AuditMissionAIService)
    // Signatures réelles du service :
    //   proposerButs(array $profil)
    //   selectionnerTypeMission(string $but, array $profil)
    //   genererTousLesChamps(string $but, array $typeInfo, array $profil)
    //   propositionComplete(array $riskIds)
    //   reformulerBut(string $but, array $profil)
    // Le service attend un $profil (pas des riskIds bruts) sauf propositionComplete.
    // On utilise getRiskProfile() pour construire le profil avant chaque appel.
    // =========================================================================

    /**
     * Construire le profil depuis les risk_ids (helper privé).
     */
    private function buildProfil(array $riskIds): array
    {
        $result = $this->ai()->getRiskProfile($riskIds);
        if (!$result['success']) {
            throw new \RuntimeException($result['error'] ?? 'Impossible de construire le profil');
        }
        return $result['profil'];
    }

    /**
     * Proposer 3 buts de mission à partir des risques sélectionnés.
     */
    public function aiSuggestGoals(Request $request)
    {
        try {
            $v      = $request->validate(['risk_ids' => 'required|array', 'risk_ids.*' => 'integer']);
            $profil = $this->buildProfil($v['risk_ids']);
            return response()->json($this->ai()->proposerButs($profil));
        } catch (\Exception $e) {
            Log::error('❌ aiSuggestGoals: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Suggérer le type de mission adapté au but choisi.
     */
    public function aiSuggestType(Request $request)
    {
        try {
            $v = $request->validate([
                'risk_ids'      => 'required|array', 'risk_ids.*' => 'integer',
                'selected_goal' => 'required|string',
            ]);
            $profil = $this->buildProfil($v['risk_ids']);
            return response()->json($this->ai()->selectionnerTypeMission($v['selected_goal'], $profil));
        } catch (\Exception $e) {
            Log::error('❌ aiSuggestType: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Générer tous les champs de la mission (description, préoccupation, etc.).
     * typeInfo = { code, label } récupéré depuis mission_types.
     */
    public function aiGenerateFields(Request $request)
    {
        try {
            $v = $request->validate([
                'risk_ids'      => 'required|array', 'risk_ids.*' => 'integer',
                'selected_goal' => 'required|string',
                'type_code'     => 'required|string',
            ]);
            $profil = $this->buildProfil($v['risk_ids']);

            // Récupérer les infos du type de mission pour passer à genererTousLesChamps
            $missionType = DB::table('mission_types')
                ->where('code', $v['type_code'])
                ->select('id', 'code', 'label', 'description')
                ->first();

            $typeInfo = $missionType
                ? ['code' => $missionType->code, 'label' => $missionType->label, 'description' => $missionType->description]
                : ['code' => $v['type_code'], 'label' => $v['type_code'], 'description' => ''];

            return response()->json($this->ai()->genererTousLesChamps($v['selected_goal'], $typeInfo, $profil));
        } catch (\Exception $e) {
            Log::error('❌ aiGenerateFields: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proposition complète en une seule étape (bouton "Générer toute la mission").
     * propositionComplete() accepte directement les riskIds.
     */
    public function aiFullProposal(Request $request)
    {
        try {
            $v = $request->validate(['risk_ids' => 'required|array', 'risk_ids.*' => 'integer']);
            return response()->json($this->ai()->propositionComplete($v['risk_ids']));
        } catch (\Exception $e) {
            Log::error('❌ aiFullProposal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reformuler un but existant.
     */
    public function aiReviseGoal(Request $request)
    {
        try {
            $v = $request->validate([
                'risk_ids'     => 'required|array', 'risk_ids.*' => 'integer',
                'current_goal' => 'required|string',
            ]);
            $profil = $this->buildProfil($v['risk_ids']);
            return response()->json($this->ai()->reformulerBut($v['current_goal'], $profil));
        } catch (\Exception $e) {
            Log::error('❌ aiReviseGoal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVÉ
    // =========================================================================
    private function getOrCreateMissionSource(int $entityId)
    {
        $s = DB::table('audit_mission_sources')->where('entity_id', $entityId)->where('code', 'INTERNAL')->first();
        if (!$s) {
            $id = DB::table('audit_mission_sources')->insertGetId([
                'entity_id' => $entityId, 'code' => 'INTERNAL', 'label' => 'Interne',
                'description' => 'Mission interne', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $s = (object)['id' => $id];
        }
        return $s;
    }

    private function getOrCreateAnnualPlan(int $entityId)
    {
        $year = (int)date('Y');
        $p = DB::table('audit_annual_plans')->where('entity_id', $entityId)->where('fiscal_year', $year)->first();
        if (!$p) {
            $id = DB::table('audit_annual_plans')->insertGetId([
                'entity_id' => $entityId, 'fiscal_year' => $year,
                'strategy' => "Plan d'audit {$year}", 'total_budget' => 0,
                'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $p = (object)['id' => $id];
        }
        return $p;
    }

    private function generateMissionNumber(): string
    {
        $prefix = 'FPM'; $year = now()->year;
        $last = DB::table('audit_missions')->where('code', 'like', "{$prefix}%-{$year}")->orderBy('code', 'desc')->first();
        $seq = 1;
        if ($last && preg_match('/-(\d+)-/', $last->code, $m)) $seq = (int)$m[1] + 1;
        return "{$prefix}-" . str_pad($seq, 4, '0', STR_PAD_LEFT) . "-{$year}";
    }

    private function createAuditMissionRisksTable(): void
    {
        if (!empty(DB::select("SHOW TABLES LIKE 'audit_mission_risks'"))) return;
        DB::statement("CREATE TABLE `audit_mission_risks` (`id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, `audit_mission_id` bigint UNSIGNED NOT NULL, `risk_id` bigint UNSIGNED NOT NULL, `created_at` timestamp NULL DEFAULT NULL, `updated_at` timestamp NULL DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_amr` (`audit_mission_id`,`risk_id`), CONSTRAINT `fk_amr_mission` FOREIGN KEY (`audit_mission_id`) REFERENCES `audit_missions`(`id`) ON DELETE CASCADE, CONSTRAINT `fk_amr_risk` FOREIGN KEY (`risk_id`) REFERENCES `risks`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureAuditMissionEntitiesTable(): void
    {
        if (!empty(DB::select("SHOW TABLES LIKE 'audit_mission_entities'"))) return;
        DB::statement("CREATE TABLE `audit_mission_entities` (`id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, `audit_mission_id` bigint UNSIGNED NOT NULL, `entity_id` bigint UNSIGNED NOT NULL, `created_at` timestamp NULL DEFAULT NULL, `updated_at` timestamp NULL DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_ame` (`audit_mission_id`,`entity_id`), CONSTRAINT `fk_ame_mission` FOREIGN KEY (`audit_mission_id`) REFERENCES `audit_missions`(`id`) ON DELETE CASCADE, CONSTRAINT `fk_ame_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function associateAuditRisksToMission(int $missionId, array $riskIds): void
    {
        $rows = array_map(fn($rid) => [
            'audit_mission_id' => $missionId, 'risk_id' => $rid, 'created_at' => now(), 'updated_at' => now(),
        ], array_unique($riskIds));
        DB::table('audit_mission_risks')->insertOrIgnore($rows);
    }
}