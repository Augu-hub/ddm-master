<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RiskEvaluationController — 4 vues séparées
 *
 * Routes (dans routes/web.php) :
 *
 *   Route::prefix('m/risk.core')->name('risk.core.')->group(function () {
 *       Route::prefix('evaluation')->name('evaluation.')->group(function () {
 *           Route::get('inherente',          [RiskEvaluationController::class, 'inherente'])         ->name('inherente');
 *           Route::post('inherente/store',   [RiskEvaluationController::class, 'storeInherente'])    ->name('inherente.store');
 *           Route::get('controle',           [RiskEvaluationController::class, 'controle'])          ->name('controle');
 *           Route::post('controle/store',    [RiskEvaluationController::class, 'storeControle'])     ->name('controle.store');
 *           Route::get('residuelle',         [RiskEvaluationController::class, 'residuelle'])        ->name('residuelle');
 *           Route::post('residuelle/store',  [RiskEvaluationController::class, 'storeResiduelle'])   ->name('residuelle.store');
 *           Route::get('cible',              [RiskEvaluationController::class, 'cible'])             ->name('cible');
 *           Route::post('cible/store',       [RiskEvaluationController::class, 'storeCible'])        ->name('cible.store');
 *           Route::post('decision/store',    [RiskEvaluationController::class, 'storeDecision'])     ->name('decision.store');
 *           Route::get('risk/{id}/actions',  [RiskEvaluationController::class, 'getRiskActions'])    ->name('risk.actions');
 *       });
 *   });
 */
class RiskEvaluationController extends Controller
{
    // =====================================================================
    //  HELPERS PRIVÉS
    // =====================================================================

    private function tid(): int
    {
        return (int)(session('tenant_id') ?? 1);
    }

    // --- Matrices --------------------------------------------------------

    private function getMatrixConfigs(): \Illuminate\Support\Collection
    {
        return RiskMatrixConfig::forTenant($this->tid())
            ->orderByDesc('is_active')->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'matrix_size'  => $c->matrix_size,
                'matrix_label' => $c->matrix_size . 'x' . $c->matrix_size,
                'is_active'    => (bool)$c->is_active,
            ]);
    }

    private function resolveConfigId(Request $request, $configs): ?int
    {
        // La matrice ACTIVE s'applique partout : on ignore tout config_id de requête.
        return optional($configs->firstWhere('is_active', true))['id']
            ?: optional($configs->first())['id'];
    }

    private function buildMatrixPayload(RiskMatrixConfig $config): array
    {
        $tid = $this->tid();

        $impacts     = $config->impactLevels->sortByDesc('score')->values();
        $frequencies = $config->frequencyLevels->sortBy('score')->values();
        $zones       = $config->criticalityZones->sortBy('sort_order')->values();

        // Criteres impact par niveau
        $impCritRows = DB::table('risk_impact_criteria')
            ->where('tenant_id', $tid)
            ->whereIn('impact_level_id', $impacts->pluck('id'))
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();
        $critByLevel = $impCritRows->groupBy('impact_level_id');

        // Criteres frequence par niveau
        $freqCritRows = DB::table('risk_frequency_criteria')
            ->where('tenant_id', $tid)
            ->whereIn('frequency_level_id', $frequencies->pluck('id'))
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();
        $freqCritByLevel = $freqCritRows->groupBy('frequency_level_id');

        // Cellules
        $cells = [];
        foreach ($impacts as $imp) {
            $row = [];
            foreach ($frequencies as $freq) {
                $score = $imp->score * $freq->score;
                $zone  = $zones->first(fn($z) => $score >= $z->min_score && $score <= $z->max_score);
                $row[] = [
                    'score'           => $score,
                    'impact_score'    => $imp->score,
                    'frequency_score' => $freq->score,
                    'zone_id'         => $zone?->id,
                    'zone_label'      => $zone?->label,
                    'zone_color'      => $zone?->color_code,
                ];
            }
            $cells[] = $row;
        }

        return [
            'config' => [
                'id'           => $config->id,
                'name'         => $config->name,
                'matrix_size'  => $config->matrix_size,
                'matrix_label' => $config->matrix_size . 'x' . $config->matrix_size,
                'max_score'    => $config->matrix_size * $config->matrix_size,
            ],
            'impacts' => $impacts->map(fn($l) => [
                'id'          => $l->id,
                'label'       => $l->label,
                'score'       => $l->score,
                'color_code'  => $l->color_code,
                'description' => $l->description,
                'criteria'    => ($critByLevel->get($l->id) ?? collect())
                    ->map(fn($c) => [
                        'id'          => $c->id,
                        'template_id' => $c->template_id ?? null,
                        'designation' => $c->designation,
                        'description' => $c->description,
                    ])->values()->all(),
            ])->values(),
            'frequencies' => $frequencies->map(fn($l) => [
                'id'          => $l->id,
                'label'       => $l->label,
                'score'       => $l->score,
                'color_code'  => $l->color_code,
                'recurrence'  => $l->recurrence ?? null,
                'description' => $l->description,
                'criteria'    => ($freqCritByLevel->get($l->id) ?? collect())
                    ->map(fn($c) => [
                        'id'          => $c->id,
                        'template_id' => $c->template_id ?? null,
                        'designation' => $c->designation,
                        'description' => $c->description,
                    ])->values()->all(),
            ])->values(),
            'cells' => collect($cells)->values(),
            'zones' => $zones->map(fn($z) => [
                'id'         => $z->id,
                'label'      => $z->label,
                'min_score'  => $z->min_score,
                'max_score'  => $z->max_score,
                'color_code' => $z->color_code,
                'sort_order' => $z->sort_order,
            ])->values(),
        ];
    }

    // --- Risques ---------------------------------------------------------

    /**
     * Charge tous les risques + contexte hiérarchique + évaluations.
     * Toutes les colonnes/tables optionnelles sont vérifiées via Schema
     * avant d'être ajoutées à la requête (compat multi-environnements).
     */
    private function loadRisks(): \Illuminate\Support\Collection
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_register')) {
            return collect();
        }

        $hasDecisionCol   = Schema::hasColumn('risk_register', 'decision');
        $hasActionStatuses= Schema::hasTable('risk_action_statuses');
        $hasProcessObj    = Schema::hasColumn('processes', 'objective');

        $query = DB::table('risk_register as r')
            ->leftJoin('activities as a',              'a.id',   '=', 'r.activity_id')
            ->leftJoin('processes as p',               'p.id',   '=', 'a.process_id')
            ->leftJoin('macro_processes as mp',        'mp.id',  '=', 'p.macro_process_id')
            ->leftJoin('entities as e',                'e.id',   '=', 'r.entity_id')
            // Inherent
            ->leftJoin('risk_impact_levels as il',     'il.id',  '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl',  'fl.id',  '=', 'r.frequency_level_id')
            ->leftJoin('risk_criticality_zones as cz', 'cz.id',  '=', 'r.criticality_zone_id')
            // Residuel
            ->leftJoin('risk_impact_levels as ril',    'ril.id', '=', 'r.residual_impact_level_id')
            ->leftJoin('risk_frequency_levels as rfl', 'rfl.id', '=', 'r.residual_frequency_level_id')
            ->leftJoin('risk_criticality_zones as rcz','rcz.id', '=', 'r.residual_criticality_zone_id')
            // Cible
            ->leftJoin('risk_impact_levels as til',    'til.id', '=', 'r.target_impact_level_id')
            ->leftJoin('risk_frequency_levels as tfl', 'tfl.id', '=', 'r.target_frequency_level_id')
            ->leftJoin('risk_criticality_zones as tcz','tcz.id', '=', 'r.target_criticality_zone_id')
            ->where('r.tenant_id', $tid)
            ->whereNull('r.deleted_at')
            ->whereNull('r.moved_to_library_at');

        // Join conditionnel sur risk_action_statuses (table peut ne pas exister)
        if ($hasDecisionCol && $hasActionStatuses) {
            $query->leftJoin('risk_action_statuses as ras', 'ras.code', '=', 'r.decision');
        }

        $select = [
            // Identite
            'r.id', 'r.code_risk', 'r.libelle', 'r.statut', 'r.activity_id',
            // Contexte
            'r.entity_id', 'e.name as entity_name',
            'a.code as activity_code', 'a.name as activity_name', 'a.process_id as process_id',
            'p.code as process_code', 'p.name as process_name',
            'p.macro_process_id as macro_process_id',
            'mp.code as macro_process_code', 'mp.name as macro_process_name', 'mp.kind as macro_process_kind',
            // Inherent
            'r.impact_level_id', 'il.score as impact_score', 'il.label as impact_label', 'il.color_code as impact_color',
            'r.frequency_level_id', 'fl.score as frequency_score', 'fl.label as frequency_label', 'fl.color_code as frequency_color',
            'r.criticality_score', 'r.criticality_zone_id as zone_id', 'cz.label as zone_label', 'cz.color_code as zone_color',
            // Residuel
            'r.residual_impact_level_id', 'ril.score as residual_impact_score', 'ril.label as residual_impact_label',
            'r.residual_frequency_level_id', 'rfl.score as residual_frequency_score', 'rfl.label as residual_frequency_label',
            'r.residual_criticality_score as residual_criticality',
            'r.residual_criticality_zone_id as residual_zone_id', 'rcz.label as residual_zone_label', 'rcz.color_code as residual_zone_color',
            // Cible
            'r.target_impact_level_id', 'til.score as target_impact_score', 'til.label as target_impact_label',
            'r.target_frequency_level_id', 'tfl.score as target_frequency_score', 'tfl.label as target_frequency_label',
            'r.target_criticality_score as target_criticality',
            'r.target_criticality_zone_id as target_zone_id', 'tcz.label as target_zone_label', 'tcz.color_code as target_zone_color',
            // Divers
            'r.controles_existants', 'r.plan_traitement', 'r.owner',
            'r.causes', 'r.consequences', 'r.entite_partenaire_impliquee',
            DB::raw('IF(r.target_impact_level_id IS NOT NULL, 1, 0) as has_target'),
            DB::raw('IF(r.residual_impact_level_id IS NOT NULL, 1, 0) as has_residual'),
        ];

        if (Schema::hasColumn('risk_register', 'target_date')) $select[] = 'r.target_date';
        if (Schema::hasColumn('risk_register', 'action_plan')) $select[] = 'r.action_plan';
        if ($hasProcessObj) $select[] = 'p.objective as process_objective';
        // Critères structurés (ligne choisie à l'inhérent) — verrouillés dans les étapes suivantes.
        foreach (['impact_criterion_id', 'frequency_criterion_id'] as $cc) {
            if (Schema::hasColumn('risk_register', $cc)) $select[] = 'r.' . $cc;
        }

        if ($hasDecisionCol) {
            $select[] = 'r.decision';
            $select[] = DB::raw('IF(r.decision IS NOT NULL, 1, 0) as has_decision');
            if ($hasActionStatuses) {
                $select[] = 'ras.label as decision_label';
                $select[] = 'ras.color as decision_color';
                $select[] = 'ras.auto_create_plan as decision_auto_create_plan';
                $select[] = 'ras.default_priority as decision_default_priority';
            }
        } else {
            $select[] = DB::raw('NULL as decision');
            $select[] = DB::raw('0 as has_decision');
        }

        return $query->select($select)
            ->orderBy('mp.id')->orderBy('p.id')->orderBy('a.id')->orderBy('r.id')
            ->get()
            ->map(fn($row) => (array)$row);
    }

    // --- Controles -------------------------------------------------------

    private function loadControls(): array
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_register_controls')) {
            \Log::warning('[loadControls] table risk_register_controls absente, tenant='.$tid);
            return [];
        }

        $cols = ['risk_id', 'id as control_id', 'description', 'type', 'owner', 'status'];
        foreach ([
            'control_procedure', 'referential_type', 'referential_ref', 'referential_url',
            'validated_at', 'mastery_level_id', 'periodicite', 'efficacite', 'next_review_date',
        ] as $col) {
            if (Schema::hasColumn('risk_register_controls', $col)) $cols[] = $col;
        }

        $result = [];
        $rows = DB::table('risk_register_controls')
            ->where('tenant_id', $tid)
            ->whereNull('deleted_at')
            ->select($cols)
            ->get();
        // NB : ne PAS utiliser ->each(fn(...)=> $result[...]=...) : l'arrow function
        // capture $result par VALEUR → les affectations étaient perdues (has_control
        // toujours faux → page résiduelle vide). foreach = affectation réelle.
        foreach ($rows as $row) {
            $result[$row->risk_id] = $row;
        }

        \Log::info('[loadControls] tenant='.$tid.' lignes lues', [
            'count'         => count($result),
            'risk_ids'      => array_keys($result),
            'sample_risk16' => $result[16] ?? null,
        ]);

        return $result;
    }

    // --- Facteurs / Appetences -------------------------------------------

    private function loadFactors(\Illuminate\Support\Collection $risks): array
    {
        $riskIds = $risks->pluck('id')->toArray();

        if (empty($riskIds) || !Schema::hasTable('risk_register_nomenclatures')) {
            return [];
        }

        return DB::table('risk_register_nomenclatures as rn')
            ->join('risk_nomenclatures as n',        'n.id',   '=', 'rn.risk_nomenclature_id')
            ->leftJoin('risk_appetite_levels as apt', 'apt.id', '=', 'n.appetite_id')
            ->whereIn('rn.risk_register_id', $riskIds)
            ->whereNull('n.deleted_at')
            ->select(
                'rn.risk_register_id as risk_id',
                'n.id as factor_id', 'n.code as factor_code', 'n.label as factor_label',
                'apt.id as appetite_id', 'apt.label as appetite_label', 'apt.color as appetite_color',
                'apt.score_min', 'apt.score_max'
            )
            ->get()
            ->groupBy('risk_id')
            ->map(fn($items) => $items->values()->toArray())
            ->toArray();
    }

    // --- Templates criteres impact (avec appetences) ---------------------

    private function getImpactCriteriaTemplates(?int $configId): array
    {
        $tid = $this->tid();

        if (!$configId || !Schema::hasTable('risk_impact_criteria_templates')) {
            return [];
        }

        $templates = DB::table('risk_impact_criteria_templates')
            ->where('tenant_id', $tid)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        if ($templates->isEmpty()) return [];

        $aptIds  = $templates->pluck('appetite_id')->filter()->unique();
        $aptMap  = $aptIds->isNotEmpty()
            ? DB::table('risk_appetite_levels')->whereIn('id', $aptIds)->get(['id','label','color','score_min','score_max'])->keyBy('id')
            : collect();

        return $templates->map(fn($t) => [
            'id'                 => $t->id,
            'designation'        => $t->designation,
            'hint'               => $t->hint ?? null,
            'sort_order'         => $t->sort_order,
            'appetite_id'        => $t->appetite_id ?? null,
            'appetite_label'     => $t->appetite_id ? ($aptMap->get($t->appetite_id)?->label) : null,
            'appetite_color'     => $t->appetite_id ? ($aptMap->get($t->appetite_id)?->color) : null,
            'appetite_score_max' => $t->appetite_id ? ($aptMap->get($t->appetite_id)?->score_max) : null,
        ])->toArray();
    }

    // --- Templates criteres frequence ------------------------------------

    private function getFrequencyCriteriaTemplates(?int $configId): array
    {
        $tid = $this->tid();

        if (!$configId || !Schema::hasTable('risk_frequency_criteria_templates')) {
            return [];
        }

        $templates = DB::table('risk_frequency_criteria_templates')
            ->where('tenant_id', $tid)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        if ($templates->isEmpty()) return [];

        $criteria = DB::table('risk_frequency_criteria')
            ->where('tenant_id', $tid)
            ->whereIn('template_id', $templates->pluck('id'))
            ->whereNull('deleted_at')
            ->get();
        $byTpl = $criteria->groupBy('template_id');

        return $templates->map(fn($t) => [
            'id'          => $t->id,
            'designation' => $t->designation,
            'hint'        => $t->hint ?? null,
            'sort_order'  => $t->sort_order,
            'levels'      => ($byTpl->get($t->id) ?? collect())->map(fn($c) => [
                'frequency_level_id' => $c->frequency_level_id,
                'description'        => $c->description,
            ])->values()->all(),
        ])->toArray();
    }

    // --- Niveaux de maitrise ---------------------------------------------

    private function loadMasteryLevels(): array
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_mastery_levels')) return [];

        $cols = ['id', 'label', 'color_code', 'description'];
        if (Schema::hasColumn('risk_mastery_levels', 'min_score')) $cols[] = 'min_score';
        if (Schema::hasColumn('risk_mastery_levels', 'max_score')) $cols[] = 'max_score';
        if (Schema::hasColumn('risk_mastery_levels', 'sort_order')) $cols[] = 'sort_order';

        $q = DB::table('risk_mastery_levels')
            ->where('tenant_id', $tid)
            ->whereNull('deleted_at');

        $q->orderBy(Schema::hasColumn('risk_mastery_levels', 'sort_order') ? 'sort_order' : 'id');

        return $q->get($cols)->map(fn($row) => [
            'id'          => $row->id,
            'label'       => $row->label,
            'score'       => $row->min_score ?? $row->max_score ?? null,
            'min_score'   => $row->min_score ?? null,
            'max_score'   => $row->max_score ?? null,
            'color_code'  => $row->color_code,
            'description' => $row->description,
        ])->toArray();
    }

    // --- Appetences actives ----------------------------------------------

    private function loadAppetites(): array
    {
        return DB::table('risk_appetite_levels')
            ->where('tenant_id', $this->tid())
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id','code','label','color','description','score_min','score_max'])
            ->toArray();
    }

    // --- Referentiels (liste statique) -----------------------------------

    private function loadReferentials(): array
    {
        return [
            ['code' => 'ISO9001',  'label' => 'ISO 9001'],
            ['code' => 'ISO22000', 'label' => 'ISO 22000'],
            ['code' => 'ISO27001', 'label' => 'ISO 27001'],
            ['code' => 'ISO45001', 'label' => 'ISO 45001'],
            ['code' => 'ISO14001', 'label' => 'ISO 14001'],
            ['code' => 'COSO',     'label' => 'COSO'],
            ['code' => 'IIA',      'label' => 'IIA'],
            ['code' => 'SYSCOA',   'label' => 'SYSCOA / OHADA'],
            ['code' => 'INTERN',   'label' => 'Procédure interne'],
            ['code' => 'NONE',     'label' => 'Aucun'],
        ];
    }

    // --- Arbre hierarchique ----------------------------------------------

    private function buildTree(\Illuminate\Support\Collection $risks): array
    {
        $tree = [];

        foreach ($risks as $r) {
            $mId = $r['macro_process_id'] ?? 0;
            $pId = $r['process_id']       ?? 0;
            $aId = $r['activity_id']      ?? 0;

            if (!isset($tree[$mId])) {
                $tree[$mId] = [
                    'id'        => $mId,
                    'code'      => $r['macro_process_code'] ?? '—',
                    'name'      => $r['macro_process_name'] ?? 'Sans macro-processus',
                    'kind'      => $r['macro_process_kind'] ?? null,
                    'processes' => [],
                ];
            }
            if (!isset($tree[$mId]['processes'][$pId])) {
                $tree[$mId]['processes'][$pId] = [
                    'id'         => $pId,
                    'code'       => $r['process_code']      ?? '—',
                    'name'       => $r['process_name']      ?? '—',
                    'objective'  => $r['process_objective'] ?? null,
                    'activities' => [],
                ];
            }
            if (!isset($tree[$mId]['processes'][$pId]['activities'][$aId])) {
                $tree[$mId]['processes'][$pId]['activities'][$aId] = [
                    'id'    => $aId,
                    'code'  => $r['activity_code'] ?? '—',
                    'name'  => $r['activity_name'] ?? '—',
                    'risks' => [],
                ];
            }
            $tree[$mId]['processes'][$pId]['activities'][$aId]['risks'][] = $r;
        }

        return array_values(array_map(function ($m) {
            $m['processes'] = array_values(array_map(function ($p) {
                $p['activities'] = array_values($p['activities']);
                return $p;
            }, $m['processes']));
            return $m;
        }, $tree));
    }

    // --- Statistiques ----------------------------------------------------

    private function getStats(): array
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_register')) {
            return ['total'=>0,'with_inherent'=>0,'with_control'=>0,'with_residual'=>0,'with_target'=>0,'with_decision'=>0,'with_plan'=>0];
        }

        $base = fn() => DB::table('risk_register')
            ->where('tenant_id', $tid)
            ->whereNull('deleted_at')
            ->whereNull('moved_to_library_at');

        $withControl = 0;
        if (Schema::hasTable('risk_register_controls')) {
            $withControl = DB::table('risk_register_controls as rc')
                ->join('risk_register as r', 'r.id', '=', 'rc.risk_id')
                ->where('rc.tenant_id', $tid)
                ->whereNull('rc.deleted_at')
                ->whereNull('r.deleted_at')
                ->whereNull('r.moved_to_library_at')
                ->distinct('rc.risk_id')
                ->count('rc.risk_id');
        }

        $withPlan = 0;
        if (Schema::hasTable('risk_action_plans')) {
            $withPlan = DB::table('risk_action_plans')
                ->where('tenant_id', $tid)
                ->whereNull('deleted_at')
                ->distinct('risk_id')
                ->count('risk_id');
        }

        $withDecision = 0;
        if (Schema::hasColumn('risk_register', 'decision')) {
            $withDecision = ($base)()->whereNotNull('decision')->count();
        }

        return [
            'total'         => ($base)()->count(),
            'with_inherent' => ($base)()->whereNotNull('criticality_score')->count(),
            'with_control'  => $withControl,
            'with_residual' => ($base)()->whereNotNull('residual_impact_level_id')->count(),
            'with_target'   => ($base)()->whereNotNull('target_impact_level_id')->count(),
            'with_decision' => $withDecision,
            'with_plan'     => $withPlan,
        ];
    }

    // --- Payload commun (matrice + risques enrichis) ---------------------

    private function commonPayload(Request $request): array
    {
        $tid     = $this->tid();
        $configs = $this->getMatrixConfigs();
        $cId     = $this->resolveConfigId($request, $configs);

        $matrixData = null;
        if ($cId) {
            $config = RiskMatrixConfig::forTenant($tid)
                ->with([
                    'impactLevels'     => fn($q) => $q->ordered(),
                    'frequencyLevels'  => fn($q) => $q->ordered(),
                    'criticalityZones' => fn($q) => $q->ordered(),
                ])
                ->find($cId);
            if ($config) {
                $matrixData = $this->buildMatrixPayload($config);
            }
        }

        $risks    = $this->loadRisks();
        $controls = $this->loadControls();
        $factors  = $this->loadFactors($risks);

        $risks = $risks->map(function ($r) use ($controls, $factors) {
            $ctrl = $controls[$r['id']] ?? null;
            return array_merge($r, [
                'control_id'        => $ctrl?->control_id,
                'control_procedure' => $ctrl?->control_procedure ?? $ctrl?->description,
                'referential_type'  => $ctrl?->referential_type  ?? null,
                'referential_ref'   => $ctrl?->referential_ref   ?? null,
                'referential_url'   => $ctrl?->referential_url   ?? null,
                'control_type'      => $ctrl?->type              ?? null,
                'control_status'    => $ctrl?->status            ?? null,
                'mastery_level_id'  => $ctrl?->mastery_level_id  ?? null,
                'periodicite'       => $ctrl?->periodicite       ?? null,
                'efficacite'        => $ctrl?->efficacite        ?? null,
                'next_review_date'  => $ctrl?->next_review_date  ?? null,
                'has_control'       => !is_null($ctrl),
                'factors'           => array_values($factors[$r['id']] ?? []),
                'action_plan'       => $r['action_plan'] ?? $r['plan_traitement'] ?? null,
            ]);
        });

        $r16 = $risks->firstWhere('id', 16);
        \Log::info('[commonPayload] risque 16 apres fusion controls', [
            'has_control'       => $r16['has_control'] ?? null,
            'control_procedure' => $r16['control_procedure'] ?? null,
            'owner'             => $r16['owner'] ?? null,
        ]);

        return [
            'risks'                      => $risks,
            'tree'                       => $this->buildTree($risks),
            'stats'                      => $this->getStats(),
            'matrixConfigs'              => $configs,
            'matrixData'                 => $matrixData,
            'selectedConfigId'           => $cId,
            'referentials'               => $this->loadReferentials(),
            'appetites'                  => $this->loadAppetites(),
            'masteryLevels'              => $this->loadMasteryLevels(),
            'criteriaTemplates'          => $this->getImpactCriteriaTemplates($cId),
            'frequencyCriteriaTemplates' => $this->getFrequencyCriteriaTemplates($cId),
            'activeSession'              => $this->activeSession(),
            'stepProgress'               => $this->stepProgress(),
        ];
    }

    /** Session d'évaluation active (affichée en permanence sur les écrans). */
    private function activeSession(): ?array
    {
        if (!Schema::hasTable('risk_sessions')) return null;

        $s = DB::table('risk_sessions')
            ->where('tenant_id', $this->tid())->whereNull('deleted_at')
            ->orderByDesc('is_active')->orderByDesc('year')->orderByDesc('created_at')
            ->first(['id', 'name', 'year', 'status', 'is_active', 'snapshot_at']);

        return $s ? [
            'id'        => $s->id,
            'name'      => $s->name,
            'year'      => $s->year,
            'status'    => $s->status,
            'is_active' => (bool) $s->is_active,
            'is_frozen' => $s->snapshot_at !== null,
        ] : null;
    }

    /**
     * Avancement des 4 étapes séquentielles (pour le stepper et le gating).
     * Chaque compteur ne considère que les risques prêts pour l'étape :
     * contrôle nécessite l'inhérent, résiduel nécessite le contrôle, etc.
     */
    private function stepProgress(): array
    {
        $tid = $this->tid();
        if (!Schema::hasTable('risk_register')) {
            return ['total' => 0, 'inherent' => 0, 'controle' => 0, 'residuel' => 0, 'cible' => 0];
        }
        $base = fn () => DB::table('risk_register')->where('tenant_id', $tid)
            ->whereNull('deleted_at')->whereNull('moved_to_library_at');

        $controle = 0;
        if (Schema::hasTable('risk_register_controls')) {
            $controle = DB::table('risk_register_controls as rc')
                ->join('risk_register as r', 'r.id', '=', 'rc.risk_id')
                ->where('rc.tenant_id', $tid)->whereNull('rc.deleted_at')
                ->whereNull('r.deleted_at')->whereNull('r.moved_to_library_at')
                ->distinct('rc.risk_id')->count('rc.risk_id');
        }

        return [
            'total'    => ($base)()->count(),
            'inherent' => ($base)()->whereNotNull('criticality_score')->count(),
            'controle' => $controle,
            'residuel' => ($base)()->whereNotNull('residual_impact_level_id')->count(),
            'cible'    => ($base)()->whereNotNull('target_impact_level_id')->count(),
        ];
    }

    // --- Utilitaires resolution BD ---------------------------------------

    private function resolveZone(int $score): ?object
    {
        return DB::table('risk_criticality_zones')
            ->where('tenant_id', $this->tid())
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }

    private function resolveImpactLevel(int $score): ?object
    {
        return DB::table('risk_impact_levels')
            ->where('tenant_id', $this->tid())
            ->where('score', $score)
            ->first();
    }

    private function resolveFrequencyLevel(int $score): ?object
    {
        return DB::table('risk_frequency_levels')
            ->where('tenant_id', $this->tid())
            ->where('score', $score)
            ->first();
    }

    // =====================================================================
    //  VUES GET — 4 PAGES SEPAREES
    // =====================================================================

    /** GET /evaluation/inherente — Tous les risques */
    public function inherente(Request $request): Response
    {
        return Inertia::render(
            'dashboards/Risk/Evaluation/EvaluationInherente',
            $this->commonPayload($request)
        );
    }

    /** GET /evaluation/controle — Risques avec inherent */
    public function controle(Request $request): Response
    {
        return Inertia::render(
            'dashboards/Risk/Evaluation/EvaluationControle',
            $this->commonPayload($request)
        );
    }

    /** GET /evaluation/residuelle — Risques sous controle */
    public function residuelle(Request $request): Response
    {
        return Inertia::render(
            'dashboards/Risk/Evaluation/EvaluationResiduelle',
            $this->commonPayload($request)
        );
    }

    /** GET /evaluation/cible — Risques avec residuel */
    public function cible(Request $request): Response
    {
        return Inertia::render(
            'dashboards/Risk/Evaluation/EvaluationCible',
            $this->commonPayload($request)
        );
    }

    /**
     * GET /evaluation/cartographie — Cartographie de synthèse.
     *
     * Rend la matrice inhérente / résiduelle / cible + synthèse, filtrable
     * par activité, processus, série d'éléments sélectionnés ou entité.
     * Réutilise l'intégralité du payload d'évaluation (risques déjà enrichis
     * des trois évaluations I/R/Cible) et y ajoute la liste des entités.
     */
    public function cartographie(Request $request): Response
    {
        $payload = $this->commonPayload($request);

        // Mode « cartographie d'une session gelée » : on remplace les risques
        // vivants par les valeurs figées du snapshot de la session demandée.
        $sessionMeta = null;
        $sessionId   = $request->integer('session_id');
        if ($sessionId && Schema::hasTable('risk_session_snapshots')) {
            $snapRisks = $this->risksFromSnapshot($sessionId);
            if (!empty($snapRisks)) {
                $payload['risks'] = collect($snapRisks);
                $payload['tree']  = $this->buildTree(collect($snapRisks));
            }
            $s = DB::table('risk_sessions')->where('id', $sessionId)->first();
            $sessionMeta = $s ? ['id' => $s->id, 'name' => $s->name, 'year' => $s->year, 'snapshot_at' => $s->snapshot_at] : null;
        }

        // Liste des entités présentes dans le registre (pour le filtre carto).
        $entities = collect($payload['risks'])
            ->filter(fn ($r) => !empty($r['entity_id']))
            ->groupBy('entity_id')
            ->map(fn ($group, $id) => [
                'id'    => (int) $id,
                'name'  => $group->first()['entity_name'] ?? ('Entité #' . $id),
                'count' => $group->count(),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        return Inertia::render(
            'dashboards/Risk/Evaluation/Cartographie',
            array_merge($payload, ['entities' => $entities, 'sessionMeta' => $sessionMeta])
        );
    }

    /**
     * Convertit les lignes gelées d'une session (risk_session_snapshots) en la
     * forme « risque » attendue par Cartographie.vue (mêmes clés que loadRisks).
     */
    private function risksFromSnapshot(int $sessionId): array
    {
        return DB::table('risk_session_snapshots')
            ->where('session_id', $sessionId)
            ->where('tenant_id', $this->tid())
            ->get()
            ->map(fn ($s) => [
                'id'                 => $s->risk_id,
                'code_risk'          => $s->code_risk,
                'libelle'            => $s->libelle,
                'statut'             => 'actif',
                'entity_id'          => $s->entity_id,
                'entity_name'        => $s->entity_name,
                'activity_id'        => $s->activity_id,
                'activity_code'      => null,
                'activity_name'      => $s->activity_name,
                'process_id'         => $s->process_id,
                'process_code'       => null,
                'process_name'       => $s->process_name,
                'macro_process_id'   => $s->macro_process_id,
                'macro_process_code' => null,
                'macro_process_name' => $s->macro_process_name,
                'macro_process_kind' => null,
                // Inhérent
                'impact_score'       => $s->inh_impact_score,
                'frequency_score'    => $s->inh_freq_score,
                'criticality_score'  => $s->inh_criticality,
                'zone_id'            => $s->inh_zone_id,
                'zone_label'         => $s->inh_zone_label,
                'zone_color'         => $s->inh_zone_color,
                // Résiduel
                'residual_impact_score'    => $s->res_impact_score,
                'residual_frequency_score' => $s->res_freq_score,
                'residual_criticality'     => $s->res_criticality,
                'residual_zone_id'         => $s->res_zone_id,
                'residual_zone_label'      => $s->res_zone_label,
                'residual_zone_color'      => $s->res_zone_color,
                // Cible
                'target_impact_score'    => $s->tgt_impact_score,
                'target_frequency_score' => $s->tgt_freq_score,
                'target_criticality'     => $s->tgt_criticality,
                'target_zone_id'         => $s->tgt_zone_id,
                'target_zone_label'      => $s->tgt_zone_label,
                'target_zone_color'      => $s->tgt_zone_color,
                'decision'           => $s->decision,
            ])
            ->all();
    }

    // =====================================================================
    //  STORE INHERENT
    // =====================================================================

    /** POST /evaluation/inherente/store */
    public function storeInherente(Request $request): JsonResponse
    {
        $tid = $this->tid();
        $v   = $request->validate([
            'risk_id'                  => 'required|integer',
            'impact_score'             => 'nullable|integer|min:1',
            'impact_criterion_label'   => 'nullable|string|max:255',
            'impact_criterion_id'      => 'nullable|integer',
            'frequency_score'          => 'nullable|integer|min:1',
            'frequency_criterion_label'=> 'nullable|string|max:255',
            'frequency_criterion_id'   => 'nullable|integer',
        ]);

        if (empty($v['impact_score']) && empty($v['frequency_score'])) {
            return response()->json(['success' => false, 'message' => 'Impact ou fréquence requis.'], 422);
        }

        $risk = DB::table('risk_register as r')
            ->leftJoin('risk_impact_levels as il',   'il.id', '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl', 'fl.id', '=', 'r.frequency_level_id')
            ->where('r.id', $v['risk_id'])
            ->where('r.tenant_id', $tid)
            ->whereNull('r.deleted_at')
            ->select('r.*', 'il.score as cur_impact', 'fl.score as cur_freq')
            ->first();
        abort_if(!$risk, 404, 'Risque introuvable.');

        // Si une dimension n'est pas envoyee, on garde la valeur deja en base (sauvegarde partielle).
        $impactScore = $v['impact_score'] ?? $risk->cur_impact ?? null;
        $freqScore   = $v['frequency_score'] ?? $risk->cur_freq ?? null;

        $impLvl  = $impactScore ? $this->resolveImpactLevel($impactScore) : null;
        $freqLvl = $freqScore   ? $this->resolveFrequencyLevel($freqScore) : null;

        // Le score global et la zone ne se calculent que si les DEUX dimensions sont connues.
        $score = ($impactScore && $freqScore) ? $impactScore * $freqScore : null;
        $zone  = $score ? $this->resolveZone($score) : null;

        $update = [
            'impact_level_id'     => $impLvl?->id,
            'frequency_level_id'  => $freqLvl?->id,
            'criticality_score'   => $score,
            'criticality_zone_id' => $zone?->id,
            'updated_at'          => now(),
        ];

        // Critère structuré (ligne choisie) — récupéré ensuite (verrouillé) par les autres étapes.
        if (array_key_exists('impact_criterion_id', $v) && Schema::hasColumn('risk_register', 'impact_criterion_id')) {
            $update['impact_criterion_id'] = $v['impact_criterion_id'] ?: null;
        }
        if (array_key_exists('frequency_criterion_id', $v) && Schema::hasColumn('risk_register', 'frequency_criterion_id')) {
            $update['frequency_criterion_id'] = $v['frequency_criterion_id'] ?: null;
        }

        // Memorise le critere precis retenu (niveau + critere = cellule cliquee), si fourni.
        if (Schema::hasColumn('risk_register', 'critere_risque')) {
            $bits = [];
            if (!empty($v['impact_criterion_label']))    $bits[] = 'Impact: ' . $v['impact_criterion_label'];
            if (!empty($v['frequency_criterion_label'])) $bits[] = 'Fréquence: ' . $v['frequency_criterion_label'];
            if (!empty($bits)) {
                // On fusionne avec ce qui existe deja pour ne pas perdre l'autre dimension
                // si on edite impact puis frequency separement.
                $existing = $risk->critere_risque ?? '';
                $lines = array_filter(array_map('trim', explode("\n", $existing)));
                foreach ($bits as $bit) {
                    $prefix = explode(':', $bit)[0];
                    $lines = array_filter($lines, fn($l) => !str_starts_with($l, $prefix . ':'));
                    $lines[] = $bit;
                }
                $update['critere_risque'] = implode("\n", $lines);
            }
        }

        DB::table('risk_register')->where('id', $v['risk_id'])->update($update);

        return response()->json(['success' => true, 'risk' => [
            'id'                 => $v['risk_id'],
            'impact_score'       => $impactScore,
            'impact_label'       => $impLvl?->label,
            'impact_color'       => $impLvl?->color_code,
            'impact_level_id'    => $impLvl?->id,
            'frequency_score'    => $freqScore,
            'frequency_label'    => $freqLvl?->label,
            'frequency_color'    => $freqLvl?->color_code,
            'frequency_level_id' => $freqLvl?->id,
            'impact_criterion_id'    => $v['impact_criterion_id'] ?? null,
            'frequency_criterion_id' => $v['frequency_criterion_id'] ?? null,
            'criticality_score'  => $score,
            'zone_id'            => $zone?->id,
            'zone_label'         => $zone?->label,
            'zone_color'         => $zone?->color_code,
        ]]);
    }

    // =====================================================================
    //  STORE CONTROLE
    // =====================================================================

    /** POST /evaluation/controle/store */
    public function storeControle(Request $request): JsonResponse
    {
        $tid = $this->tid();
        $v   = $request->validate([
            'risk_id'            => 'required|integer',
            'control_procedure'  => 'nullable|string',
            'control_type'       => 'nullable|string|max:50',
            'referential_type'   => 'nullable|string|max:50',
            'referential_ref'    => 'nullable|string|max:255',
            'referential_url'    => 'nullable|string|max:500',
            'referential_manual' => 'nullable|string|max:255',
            'owner'              => 'nullable|string|max:150',
            'mastery_level_id'   => 'nullable|integer',
            'periodicite'        => 'nullable|string|max:50',
            'efficacite'         => 'nullable|integer|min:0|max:100',
            'control_status'     => 'nullable|string|max:50',
            'next_review_date'   => 'nullable|date',
        ]);

        \Log::info('[storeControle] tenant='.$tid.' payload reçu', $v);

        $risk = DB::table('risk_register')
            ->where('id', $v['risk_id'])
            ->where('tenant_id', $tid)
            ->whereNull('deleted_at')
            ->first();

        if (!$risk) {
            \Log::warning('[storeControle] risque introuvable', ['risk_id' => $v['risk_id'], 'tenant_id' => $tid]);
            return response()->json(['success' => false, 'message' => 'Risque introuvable.'], 404);
        }

        if (!Schema::hasTable('risk_register_controls')) {
            \Log::error('[storeControle] table risk_register_controls absente');
            return response()->json([
                'success' => false,
                'message' => "La table 'risk_register_controls' n'existe pas. Lancez la migration.",
            ], 500);
        }

        try {
            // Referentiel : liste OU saisie manuelle
            $referentialType = $v['referential_type'] ?? null;
            if (empty($referentialType) && !empty($v['referential_manual'])) {
                $referentialType = $v['referential_manual'];
            }

            $norm = fn($val) => (is_string($val) && trim($val) === '') ? null : $val;
            $columns = Schema::getColumnListing('risk_register_controls');
            \Log::info('[storeControle] colonnes detectees', $columns);

            $candidate = [
                'control_procedure' => $norm($v['control_procedure'] ?? null),
                'referential_type'  => $norm($referentialType),
                'referential_ref'   => $norm($v['referential_ref']   ?? null),
                'referential_url'   => $norm($v['referential_url']   ?? null),
                'type'              => $norm($v['control_type']      ?? null),
                'mastery_level_id'  => $v['mastery_level_id']  ?? null,
                'periodicite'       => $norm($v['periodicite']       ?? null),
                'efficacite'        => $v['efficacite']        ?? null,
                'next_review_date'  => $v['next_review_date']  ?? null,
                'owner'             => $norm($v['owner'] ?? null),
                'status'            => $norm($v['control_status'] ?? null),
                'validated_at'      => now(),
            ];

            $data = ['updated_at' => now()];
            foreach ($candidate as $col => $value) {
                if (in_array($col, $columns, true)) {
                    $data[$col] = $value;
                }
            }
            if (!in_array('control_procedure', $columns, true) && in_array('description', $columns, true)) {
                $data['description'] = $norm($v['control_procedure'] ?? null);
            }

            \Log::info('[storeControle] data prete a etre ecrite', $data);

            $existing = DB::table('risk_register_controls')
                ->where('tenant_id', $tid)
                ->where('risk_id', $v['risk_id'])
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                \Log::info('[storeControle] ligne existante trouvee, UPDATE', ['id' => $existing->id]);
                $affected = DB::table('risk_register_controls')->where('id', $existing->id)->update($data);
                \Log::info('[storeControle] resultat UPDATE', ['rows_affected' => $affected]);
                $ctrlId = $existing->id;
            } else {
                $insertData = array_merge($data, [
                    'tenant_id'  => $tid,
                    'risk_id'    => $v['risk_id'],
                    'code'       => 'CTRL-' . str_pad((DB::table('risk_register_controls')->max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT),
                    'label'      => 'Controle — ' . ($risk->code_risk ?? 'risque'),
                    'created_at' => now(),
                ]);
                if (!isset($insertData['status'])) $insertData['status'] = 'active';
                \Log::info('[storeControle] aucune ligne existante, INSERT', $insertData);
                $ctrlId = DB::table('risk_register_controls')->insertGetId($insertData);
                \Log::info('[storeControle] resultat INSERT', ['new_id' => $ctrlId]);
            }

            $saved = DB::table('risk_register_controls')->where('id', $ctrlId)->first();
            \Log::info('[storeControle] relecture post-ecriture', (array) $saved);

            return response()->json([
                'success'    => true,
                'control_id' => $ctrlId,
                'risk_id'    => $v['risk_id'],
                'saved'      => $saved,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[storeControle] EXCEPTION', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================================
    //  STORE RESIDUEL
    // =====================================================================

    /** POST /evaluation/residuelle/store */
    public function storeResiduelle(Request $request): JsonResponse
    {
        $tid = $this->tid();
        $v   = $request->validate([
            'risk_id'                   => 'required|integer',
            'impact_score'              => 'required|integer|min:1',
            'impact_criterion_label'    => 'nullable|string|max:255',
            'frequency_score'           => 'required|integer|min:1',
            'frequency_criterion_label' => 'nullable|string|max:255',
        ]);

        $risk = DB::table('risk_register as r')
            ->leftJoin('risk_impact_levels as il',    'il.id', '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl',  'fl.id', '=', 'r.frequency_level_id')
            ->where('r.id', $v['risk_id'])->where('r.tenant_id', $tid)->whereNull('r.deleted_at')
            ->select('r.*', 'il.score as inh_impact', 'fl.score as inh_freq')
            ->first();
        abort_if(!$risk, 404, 'Risque introuvable.');

        // Contrainte : residuel <= inherent
        if ($risk->inh_impact && $v['impact_score'] > $risk->inh_impact) {
            return response()->json(['success' => false,
                'message' => "Impact residuel ({$v['impact_score']}) > inherent ({$risk->inh_impact})."], 422);
        }
        if ($risk->inh_freq && $v['frequency_score'] > $risk->inh_freq) {
            return response()->json(['success' => false,
                'message' => "Frequence residuelle ({$v['frequency_score']}) > inherente ({$risk->inh_freq})."], 422);
        }

        $score   = $v['impact_score'] * $v['frequency_score'];
        $impLvl  = $this->resolveImpactLevel($v['impact_score']);
        $freqLvl = $this->resolveFrequencyLevel($v['frequency_score']);
        $zone    = $this->resolveZone($score);

        $update = [
            'residual_impact_level_id'     => $impLvl?->id,
            'residual_frequency_level_id'  => $freqLvl?->id,
            'residual_criticality_score'   => $score,
            'residual_criticality_zone_id' => $zone?->id,
            'updated_at'                   => now(),
        ];

        // Memorise le critere precis retenu (niveau + critere = cellule cliquee), si fourni.
        if (Schema::hasColumn('risk_register', 'critere_risque')) {
            $bits = [];
            if (!empty($v['impact_criterion_label']))    $bits[] = 'Résiduel Impact: ' . $v['impact_criterion_label'];
            if (!empty($v['frequency_criterion_label'])) $bits[] = 'Résiduel Fréquence: ' . $v['frequency_criterion_label'];
            if (!empty($bits)) {
                $existing = $risk->critere_risque ?? '';
                $lines = array_filter(array_map('trim', explode("\n", $existing)));
                foreach ($bits as $bit) {
                    $prefix = explode(':', $bit)[0];
                    $lines = array_filter($lines, fn($l) => !str_starts_with($l, $prefix . ':'));
                    $lines[] = $bit;
                }
                $update['critere_risque'] = implode("\n", $lines);
            }
        }

        DB::table('risk_register')->where('id', $v['risk_id'])->update($update);

        return response()->json(['success' => true, 'risk' => [
            'id'                          => $v['risk_id'],
            'residual_impact_score'        => $v['impact_score'],
            'residual_impact_label'        => $impLvl?->label,
            'residual_impact_level_id'     => $impLvl?->id,
            'residual_frequency_score'     => $v['frequency_score'],
            'residual_frequency_label'     => $freqLvl?->label,
            'residual_frequency_level_id'  => $freqLvl?->id,
            'residual_criticality'         => $score,
            'residual_zone_id'             => $zone?->id,
            'residual_zone_label'          => $zone?->label,
            'residual_zone_color'          => $zone?->color_code,
            'has_residual'                 => true,
        ]]);
    }

    // =====================================================================
    //  STORE CIBLE
    // =====================================================================

    /** POST /evaluation/cible/store */
    public function storeCible(Request $request): JsonResponse
    {
        $tid = $this->tid();
        $v   = $request->validate([
            'risk_id'         => 'required|integer',
            'impact_score'    => 'required|integer|min:1',
            'frequency_score' => 'required|integer|min:1',
            'target_date'     => 'nullable|date',
            'action_plan'     => 'nullable|string',
        ]);

        $risk = DB::table('risk_register as r')
            ->leftJoin('risk_impact_levels as il',     'il.id',  '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl',   'fl.id',  '=', 'r.frequency_level_id')
            ->leftJoin('risk_impact_levels as ril',    'ril.id', '=', 'r.residual_impact_level_id')
            ->leftJoin('risk_frequency_levels as rfl',  'rfl.id', '=', 'r.residual_frequency_level_id')
            ->where('r.id', $v['risk_id'])->where('r.tenant_id', $tid)->whereNull('r.deleted_at')
            ->select('r.*', 'il.score as inh_impact', 'fl.score as inh_freq',
                           'ril.score as res_impact', 'rfl.score as res_freq')
            ->first();
        abort_if(!$risk, 404, 'Risque introuvable.');

        // Contrainte : cible <= residuel (ou inherent si pas de residuel)
        $refImpact = $risk->res_impact ?? $risk->inh_impact ?? null;
        $refFreq   = $risk->res_freq   ?? $risk->inh_freq   ?? null;

        if ($refImpact && $v['impact_score'] > $refImpact) {
            return response()->json(['success' => false,
                'message' => "Impact cible ({$v['impact_score']}) > reference ({$refImpact})."], 422);
        }
        if ($refFreq && $v['frequency_score'] > $refFreq) {
            return response()->json(['success' => false,
                'message' => "Frequence cible ({$v['frequency_score']}) > reference ({$refFreq})."], 422);
        }

        $score   = $v['impact_score'] * $v['frequency_score'];
        $impLvl  = $this->resolveImpactLevel($v['impact_score']);
        $freqLvl = $this->resolveFrequencyLevel($v['frequency_score']);
        $zone    = $this->resolveZone($score);

        $update = [
            'target_impact_level_id'     => $impLvl?->id,
            'target_frequency_level_id'  => $freqLvl?->id,
            'target_criticality_score'   => $score,
            'target_criticality_zone_id' => $zone?->id,
            'updated_at'                 => now(),
        ];
        if (Schema::hasColumn('risk_register', 'target_date'))  $update['target_date'] = $v['target_date'] ?? null;
        if (Schema::hasColumn('risk_register', 'action_plan'))  $update['action_plan'] = $v['action_plan'] ?? null;
        else                                                       $update['plan_traitement'] = $v['action_plan'] ?? null;

        DB::table('risk_register')->where('id', $v['risk_id'])->update($update);

        // Creation automatique du plan d'action (si infra dispo)
        $this->autoCreateActionPlan($v['risk_id']);

        return response()->json(['success' => true, 'risk' => [
            'id'                         => $v['risk_id'],
            'target_impact_score'         => $v['impact_score'],
            'target_impact_label'         => $impLvl?->label,
            'target_impact_level_id'      => $impLvl?->id,
            'target_frequency_score'      => $v['frequency_score'],
            'target_frequency_label'      => $freqLvl?->label,
            'target_frequency_level_id'   => $freqLvl?->id,
            'target_criticality'          => $score,
            'target_zone_id'              => $zone?->id,
            'target_zone_label'           => $zone?->label,
            'target_zone_color'           => $zone?->color_code,
            'has_target'                  => true,
            'target_date'                 => $v['target_date'] ?? null,
            'action_plan'                 => $v['action_plan'] ?? null,
        ]]);
    }

    // =====================================================================
    //  STORE DECISION
    // =====================================================================

    /** POST /evaluation/decision/store */
    public function storeDecision(Request $request): JsonResponse
    {
        $tid = $this->tid();
        $v   = $request->validate([
            'risk_id'  => 'required|integer',
            'decision' => 'required|string|max:50',
        ]);

        if (!Schema::hasColumn('risk_register', 'decision')) {
            return response()->json(['success' => false, 'message' => "La colonne 'decision' n'existe pas encore sur risk_register. Lancez la migration."], 422);
        }

        $risk = DB::table('risk_register')
            ->where('id', $v['risk_id'])->where('tenant_id', $tid)->whereNull('deleted_at')->first();
        abort_if(!$risk, 404, 'Risque introuvable.');

        // Mapping codes francais → codes BD
        $decisionMap = [
            'accepter'   => 'ACCEPTE',
            'reduire'    => 'REDUIT',
            'transferer' => 'TRANSFERE',
            'refuser'    => 'REFUSE',
            'mitiger'    => 'MITIGE',
            'controler'  => 'CONTROLE',
        ];
        $decisionCode = $decisionMap[$v['decision']] ?? strtoupper($v['decision']);

        DB::table('risk_register')->where('id', $v['risk_id'])->update([
            'decision'   => $decisionCode,
            'updated_at' => now(),
        ]);

        // Historique (si table dispo)
        if (Schema::hasTable('risk_decision_history')) {
            DB::table('risk_decision_history')->insert([
                'tenant_id'         => $tid,
                'risk_id'           => $v['risk_id'],
                'decision'          => $decisionCode,
                'previous_decision' => $risk->decision ?? null,
                'decided_by'        => auth()->id(),
                'decided_at'        => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Creation automatique du plan (si infra dispo)
        $this->autoCreateActionPlan($v['risk_id']);

        return response()->json(['success' => true, 'risk_id' => $v['risk_id'], 'decision' => $decisionCode]);
    }

    // =====================================================================
    //  CREATION AUTOMATIQUE PLAN D'ACTION
    // =====================================================================

    private function autoCreateActionPlan(int $riskId): void
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_action_plans')) return;
        if (!Schema::hasColumn('risk_register', 'decision')) return;

        $risk = DB::table('risk_register')
            ->where('id', $riskId)->where('tenant_id', $tid)->whereNull('deleted_at')->first();

        if (!$risk || empty($risk->decision)) return;

        // Ne pas creer en doublon
        if (DB::table('risk_action_plans')->where('risk_id', $riskId)->where('tenant_id', $tid)->whereNull('deleted_at')->exists()) return;

        $autoCreate      = true;
        $defaultPriority = 'medium';
        $statusLabel     = $risk->decision;

        if (Schema::hasTable('risk_action_statuses')) {
            $statusConfig = DB::table('risk_action_statuses')
                ->where('code', $risk->decision)->where('tenant_id', $tid)->where('is_active', 1)->first();
            if (!$statusConfig || !$statusConfig->auto_create_plan) return;
            $defaultPriority = $statusConfig->default_priority ?? 'medium';
            $statusLabel     = $statusConfig->label ?? $risk->decision;
        }

        $year = now()->format('Y');
        $last = DB::table('risk_action_plans')->where('tenant_id', $tid)->where('code', 'LIKE', "AP-{$year}-%")->orderByDesc('id')->first();
        $num  = $last ? (intval(substr($last->code, -4)) + 1) : 1;
        $code = "AP-{$year}-" . str_pad($num, 4, '0', STR_PAD_LEFT);

        $insertData = [
            'tenant_id'   => $tid,
            'code'        => $code,
            'risk_id'     => $riskId,
            'title'       => "Plan d'action - " . ($risk->libelle ?? 'Risque'),
            'description' => "Genere automatiquement suite a la decision {$statusLabel}.",
            'priority'    => $defaultPriority,
            'status'      => 'pending',
            'assigned_to' => null,
            'target_date' => now()->addDays(30)->toDateString(),
            'progress'    => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        if (Schema::hasColumn('risk_action_plans', 'entity_id'))         $insertData['entity_id'] = $risk->entity_id ?? null;
        if (Schema::hasColumn('risk_action_plans', 'is_auto_generated')) $insertData['is_auto_generated'] = 1;
        if (Schema::hasColumn('risk_action_plans', 'source_status'))     $insertData['source_status'] = $risk->decision;

        $planId = DB::table('risk_action_plans')->insertGetId($insertData);

        if (Schema::hasTable('risk_action_logs')) {
            DB::table('risk_action_logs')->insert([
                'tenant_id'   => $tid,
                'plan_id'     => $planId,
                'user_id'     => auth()->id(),
                'action'      => 'auto_created',
                'description' => "Plan cree automatiquement pour la decision {$statusLabel}.",
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    // =====================================================================
    //  API UTILITAIRES
    // =====================================================================

    /** GET /evaluation/risk/{id}/actions */
    public function getRiskActions(int $riskId): JsonResponse
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_action_plans')) {
            return response()->json(['success' => true, 'actions' => []]);
        }

        $actions = DB::table('risk_action_plans as ap')
            ->leftJoin('users as u', 'u.id', '=', 'ap.assigned_to')
            ->where('ap.risk_id', $riskId)
            ->where('ap.tenant_id', $tid)
            ->whereNull('ap.deleted_at')
            ->orderByRaw("FIELD(ap.priority,'critical','high','medium','low')")
            ->orderBy('ap.target_date')
            ->select('ap.*', 'u.name as assigned_to_name', 'u.email as assigned_to_email')
            ->get()
            ->toArray();

        return response()->json(['success' => true, 'actions' => $actions]);
    }
}