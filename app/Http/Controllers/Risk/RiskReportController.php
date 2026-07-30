<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hub « Rapports & Tableau de bord » du module risque (/m/risk.core/reports).
 *
 *  - index()          : tableau de bord (KPIs live du registre + plans) NETTEMENT
 *                       distinct des rapports à générer, + point d'entrée vers les
 *                       rapports liés à la session d'évaluation active.
 *  - ficheRisque()    : fiche risque synthétique imprimable (1 risque).
 *  - planSynthetique(): plan d'action synthétique par processus (imprimable).
 */
class RiskReportController extends Controller
{
    private function tid(): int
    {
        return (int) (session('tenant_id') ?? 1);
    }

    // =====================================================================
    //  HUB / TABLEAU DE BORD
    // =====================================================================

    public function index(Request $request): Response
    {
        $tid = $this->tid();

        // ── Tableau de bord (live) ──────────────────────────────────────────
        $risksBase = fn () => DB::table('risk_register')
            ->where('tenant_id', $tid)->whereNull('deleted_at')->whereNull('moved_to_library_at');

        $configId = DB::table('risk_matrix_configs')->where('tenant_id', $tid)->where('is_active', 1)->value('id');
        $zones = $configId
            ? DB::table('risk_criticality_zones')->where('tenant_id', $tid)->where('matrix_config_id', $configId)
                ->orderBy('sort_order')->get(['id', 'label', 'min_score', 'max_score', 'color_code'])->map(fn ($z) => (array) $z)->all()
            : [];

        // Répartition résiduelle par zone
        $zoneDist = [];
        foreach ($zones as $z) $zoneDist[$z['label']] = 0;
        if (Schema::hasColumn('risk_register', 'residual_criticality_zone_id')) {
            $rows = DB::table('risk_register as r')
                ->leftJoin('risk_criticality_zones as z', 'z.id', '=', 'r.residual_criticality_zone_id')
                ->where('r.tenant_id', $tid)->whereNull('r.deleted_at')->whereNull('r.moved_to_library_at')
                ->whereNotNull('r.residual_criticality_zone_id')
                ->get(['z.label as label']);
            foreach ($rows as $x) if ($x->label !== null && isset($zoneDist[$x->label])) $zoneDist[$x->label]++;
        }

        $plans = ['total' => 0, 'overdue' => 0, 'taux' => 0, 'completed' => 0];
        if (Schema::hasTable('risk_action_plans')) {
            $pb = fn () => DB::table('risk_action_plans')->where('tenant_id', $tid)->whereNull('deleted_at');
            $plans['total']     = ($pb)()->count();
            $plans['completed'] = ($pb)()->where('status', 'completed')->count();
            $plans['overdue']   = ($pb)()->whereNotIn('status', ['completed', 'cancelled'])
                                    ->whereDate('target_date', '<', now()->toDateString())->count();
            $plans['taux']      = ($pb)()->count() ? (int) round(($pb)()->avg('progress')) : 0;
        }

        $incidents = Schema::hasTable('risk_incidents')
            ? DB::table('risk_incidents')->where('tenant_id', $tid)->whereNull('deleted_at')->count() : 0;

        $dashboard = [
            'risks_total'    => ($risksBase)()->count(),
            'risks_evaluated'=> ($risksBase)()->whereNotNull('impact_level_id')->count(),
            'risks_residual' => Schema::hasColumn('risk_register', 'residual_impact_level_id') ? ($risksBase)()->whereNotNull('residual_impact_level_id')->count() : 0,
            'risks_target'   => Schema::hasColumn('risk_register', 'target_impact_level_id') ? ($risksBase)()->whereNotNull('target_impact_level_id')->count() : 0,
            'zones'          => $zones,
            'zoneDist'       => $zoneDist,
            'plans'          => $plans,
            'incidents'      => $incidents,
        ];

        // ── Sessions d'évaluation (pour générer les rapports) ───────────────
        $sessions = [];
        if (Schema::hasTable('risk_sessions')) {
            $sessions = DB::table('risk_sessions')->where('tenant_id', $tid)->whereNull('deleted_at')
                ->orderByDesc('is_active')->orderByDesc('year')->orderByDesc('created_at')
                ->get(['id', 'name', 'year', 'status', 'is_active', 'snapshot_at'])
                ->map(fn ($s) => [
                    'id' => $s->id, 'name' => $s->name, 'year' => $s->year, 'status' => $s->status,
                    'is_active' => (bool) $s->is_active, 'is_frozen' => $s->snapshot_at !== null,
                ])->all();
        }
        $activeSession = collect($sessions)->firstWhere('is_active', true) ?: (collect($sessions)->first() ?: null);

        // Risques (pour le sélecteur de fiche risque)
        $riskList = DB::table('risk_register')->where('tenant_id', $tid)->whereNull('deleted_at')->whereNull('moved_to_library_at')
            ->orderBy('code_risk')->get(['id', 'code_risk', 'libelle'])->map(fn ($r) => (array) $r)->all();

        return Inertia::render('dashboards/Risk/Reports/Index', [
            'dashboard'     => $dashboard,
            'sessions'      => $sessions,
            'activeSession' => $activeSession,
            'riskList'      => $riskList,
        ]);
    }

    // =====================================================================
    //  FICHE RISQUE SYNTHÉTIQUE
    // =====================================================================

    public function ficheRisque(Request $request, int $riskId): Response
    {
        $tid = $this->tid();

        $r = DB::table('risk_register as r')
            ->leftJoin('activities as a', 'a.id', '=', 'r.activity_id')
            ->leftJoin('processes as p', 'p.id', '=', 'a.process_id')
            ->leftJoin('macro_processes as mp', 'mp.id', '=', 'p.macro_process_id')
            ->leftJoin('entities as e', 'e.id', '=', 'r.entity_id')
            ->leftJoin('risk_nomenclatures as nom', 'nom.id', '=', 'r.nomenclature_id')
            ->leftJoin('risk_impact_levels as il', 'il.id', '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl', 'fl.id', '=', 'r.frequency_level_id')
            ->leftJoin('risk_criticality_zones as cz', 'cz.id', '=', 'r.criticality_zone_id')
            ->leftJoin('risk_impact_levels as ril', 'ril.id', '=', 'r.residual_impact_level_id')
            ->leftJoin('risk_frequency_levels as rfl', 'rfl.id', '=', 'r.residual_frequency_level_id')
            ->leftJoin('risk_criticality_zones as rcz', 'rcz.id', '=', 'r.residual_criticality_zone_id')
            ->leftJoin('risk_impact_levels as til', 'til.id', '=', 'r.target_impact_level_id')
            ->leftJoin('risk_frequency_levels as tfl', 'tfl.id', '=', 'r.target_frequency_level_id')
            ->leftJoin('risk_criticality_zones as tcz', 'tcz.id', '=', 'r.target_criticality_zone_id')
            ->where('r.id', $riskId)->where('r.tenant_id', $tid)->whereNull('r.deleted_at')
            ->select([
                'r.id', 'r.code_risk', 'r.libelle', 'r.causes', 'r.consequences', 'r.consequences_autres_processus',
                'r.controles_existants', 'r.plan_traitement', 'r.owner', 'r.decision', 'r.critere_risque',
                'r.cout_risque', 'r.cout_consequences', 'r.entite_partenaire_impliquee', 'r.vraisemblance_apparition',
                'a.code as activity_code', 'a.name as activity_name',
                'p.code as process_code', 'p.name as process_name',
                'mp.code as macro_code', 'mp.name as macro_name',
                'e.name as entity_name',
                'nom.code as nomenclature_code', 'nom.label as nomenclature_label',
                'il.score as inh_impact', 'il.label as inh_impact_label',
                'fl.score as inh_freq', 'fl.label as inh_freq_label',
                'r.criticality_score as inh_crit', 'cz.label as inh_zone', 'cz.color_code as inh_zone_color',
                'ril.score as res_impact', 'rfl.score as res_freq',
                'r.residual_criticality_score as res_crit', 'rcz.label as res_zone', 'rcz.color_code as res_zone_color',
                'til.score as tgt_impact', 'tfl.score as tgt_freq',
                'r.target_criticality_score as tgt_crit', 'tcz.label as tgt_zone', 'tcz.color_code as tgt_zone_color',
            ])->first();

        abort_if(!$r, 404, 'Risque introuvable');

        // Contrôle / maîtrise
        $control = null;
        if (Schema::hasTable('risk_register_controls')) {
            $control = DB::table('risk_register_controls as c')
                ->leftJoin('risk_mastery_levels as m', 'm.id', '=', 'c.mastery_level_id')
                ->where('c.risk_id', $riskId)->where('c.tenant_id', $tid)->whereNull('c.deleted_at')
                ->select('c.description', 'c.control_procedure', 'c.type', 'c.efficacite', 'c.periodicite',
                    'c.referential_type', 'c.referential_ref', 'm.label as mastery_label', 'm.color_code as mastery_color')
                ->first();
        }

        // Recommandation
        $recommandation = null;
        if (Schema::hasTable('risk_recommendations')) {
            $recommandation = DB::table('risk_recommendations')->where('risk_id', $riskId)->where('tenant_id', $tid)
                ->whereNull('deleted_at')->value('content');
        }

        // Matrice (pour la mini-carte)
        $configId = DB::table('risk_matrix_configs')->where('tenant_id', $tid)->where('is_active', 1)->value('id');
        $matrix = $this->matrixMeta($tid, $configId);

        return Inertia::render('dashboards/Risk/Reports/FicheRisque', [
            'risk'           => (array) $r,
            'control'        => $control ? (array) $control : null,
            'recommandation' => $recommandation,
            'matrix'         => $matrix,
        ]);
    }

    // =====================================================================
    //  PLAN D'ACTION SYNTHÉTIQUE (par processus)
    // =====================================================================

    public function planSynthetique(Request $request): Response
    {
        $tid = $this->tid();

        $rows = [];
        if (Schema::hasTable('risk_action_plans')) {
            $rows = DB::table('risk_action_plans as ap')
                ->join('risk_register as r', 'r.id', '=', 'ap.risk_id')
                ->leftJoin('activities as a', 'a.id', '=', 'r.activity_id')
                ->leftJoin('processes as p', 'p.id', '=', 'a.process_id')
                ->leftJoin('risk_criticality_zones as rcz', 'rcz.id', '=', 'r.residual_criticality_zone_id')
                ->leftJoin('users as u', 'u.id', '=', 'ap.assigned_to')
                ->where('ap.tenant_id', $tid)->whereNull('ap.deleted_at')
                ->orderBy('p.code')->orderBy('a.code')->orderByDesc('r.residual_criticality_score')
                ->select([
                    'ap.id', 'ap.code', 'ap.title', 'ap.status', 'ap.progress', 'ap.target_date', 'ap.start_date',
                    'ap.completion_date', 'ap.cost_estimate',
                    'r.id as risk_id', 'r.code_risk', 'r.libelle as risk_libelle', 'r.cout_risque', 'r.decision',
                    'r.residual_criticality_score as nrr', 'rcz.label as res_zone', 'rcz.color_code as res_zone_color',
                    'p.code as process_code', 'p.name as process_name',
                    'a.code as activity_code', 'a.name as activity_name',
                    'u.name as responsable',
                ])->get()->map(fn ($x) => (array) $x)->all();
        }

        // Regroupement processus → activité
        $tree = [];
        foreach ($rows as $x) {
            $pk = $x['process_code'] ?? '—';
            $ak = $x['activity_code'] ?? '—';
            $tree[$pk]['code'] = $x['process_code'];
            $tree[$pk]['name'] = $x['process_name'];
            $tree[$pk]['activities'][$ak]['code'] = $x['activity_code'];
            $tree[$pk]['activities'][$ak]['name'] = $x['activity_name'];
            $tree[$pk]['activities'][$ak]['plans'][] = $x;
        }
        // Normalise en tableaux + totaux
        $processes = [];
        foreach ($tree as $p) {
            $acts = [];
            $pTaux = []; $pCoutR = 0; $pCoutRec = 0;
            foreach ($p['activities'] as $a) {
                $aTaux = [];
                foreach ($a['plans'] as $pl) {
                    $aTaux[] = (int) ($pl['progress'] ?? 0);
                    $pCoutRec += (float) ($pl['cost_estimate'] ?? 0);
                }
                $acts[] = [
                    'code' => $a['code'], 'name' => $a['name'], 'plans' => $a['plans'],
                    'taux' => count($aTaux) ? (int) round(array_sum($aTaux) / count($aTaux)) : 0,
                ];
                $pTaux = array_merge($pTaux, $aTaux);
            }
            $processes[] = [
                'code' => $p['code'], 'name' => $p['name'], 'activities' => $acts,
                'taux' => count($pTaux) ? (int) round(array_sum($pTaux) / count($pTaux)) : 0,
                'cout_recomm' => $pCoutRec,
            ];
        }

        return Inertia::render('dashboards/Risk/Reports/PlanSynthetique', [
            'processes' => $processes,
        ]);
    }

    // =====================================================================
    //  PLAN D'ACTION PAR RECOMMANDATION (détail d'un risque)
    // =====================================================================

    public function planParRecommandation(Request $request, int $riskId): Response
    {
        $tid = $this->tid();

        $r = DB::table('risk_register as r')
            ->leftJoin('activities as a', 'a.id', '=', 'r.activity_id')
            ->leftJoin('processes as p', 'p.id', '=', 'a.process_id')
            ->leftJoin('risk_impact_levels as il', 'il.id', '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl', 'fl.id', '=', 'r.frequency_level_id')
            ->leftJoin('risk_criticality_zones as cz', 'cz.id', '=', 'r.criticality_zone_id')
            ->where('r.id', $riskId)->where('r.tenant_id', $tid)->whereNull('r.deleted_at')
            ->select([
                'r.id', 'r.code_risk', 'r.libelle', 'r.owner', 'r.critere_risque', 'r.cout_risque', 'r.decision',
                'il.score as ir', 'fl.score as fr', 'r.criticality_score as ngr',
                'cz.label as zone', 'cz.color_code as zone_color',
                'p.code as process_code', 'p.name as process_name',
                'a.code as activity_code', 'a.name as activity_name',
            ])->first();
        abort_if(!$r, 404, 'Risque introuvable');

        $recommandation = Schema::hasTable('risk_recommendations')
            ? DB::table('risk_recommendations')->where('risk_id', $riskId)->where('tenant_id', $tid)->whereNull('deleted_at')->value('content')
            : null;

        $actions = [];
        if (Schema::hasTable('risk_action_plans')) {
            $actions = DB::table('risk_action_plans as ap')
                ->leftJoin('users as u', 'u.id', '=', 'ap.assigned_to')
                ->where('ap.risk_id', $riskId)->where('ap.tenant_id', $tid)->whereNull('ap.deleted_at')
                ->orderBy('ap.target_date')
                ->select('ap.id', 'ap.code', 'ap.title', 'ap.action_plan', 'ap.cost_estimate',
                    'ap.start_date', 'ap.target_date', 'ap.completion_date', 'ap.status', 'ap.progress',
                    'u.name as responsable')
                ->get()->map(fn ($x) => (array) $x)->all();
        }

        $totals = [
            'cout_risque'  => $r->cout_risque,
            'cout_recomm'  => array_sum(array_map(fn ($a) => (float) ($a['cost_estimate'] ?? 0), $actions)),
            'taux'         => count($actions) ? (int) round(array_sum(array_map(fn ($a) => (int) ($a['progress'] ?? 0), $actions)) / count($actions)) : 0,
            'nb_actions'   => count($actions),
            'nb_done'      => count(array_filter($actions, fn ($a) => ($a['progress'] ?? 0) >= 100)),
            'date_debut'   => collect($actions)->pluck('start_date')->filter()->min(),
            'date_fin'     => collect($actions)->pluck('target_date')->filter()->max(),
        ];

        return Inertia::render('dashboards/Risk/Reports/PlanRecommandation', [
            'risk'           => (array) $r,
            'recommandation' => $recommandation,
            'actions'        => $actions,
            'totals'         => $totals,
        ]);
    }

    // =====================================================================
    //  DIAGRAMME DE GANTT DES RECOMMANDATIONS
    // =====================================================================

    public function gantt(Request $request): Response
    {
        $tid = $this->tid();
        $processFilter = $request->integer('process') ?: null;

        $rows = [];
        if (Schema::hasTable('risk_action_plans')) {
            $rows = DB::table('risk_action_plans as ap')
                ->join('risk_register as r', 'r.id', '=', 'ap.risk_id')
                ->leftJoin('activities as a', 'a.id', '=', 'r.activity_id')
                ->leftJoin('processes as p', 'p.id', '=', 'a.process_id')
                ->leftJoin('users as u', 'u.id', '=', 'ap.assigned_to')
                ->where('ap.tenant_id', $tid)->whereNull('ap.deleted_at')
                ->when($processFilter, fn ($q) => $q->where('a.process_id', $processFilter))
                ->where(fn ($q) => $q->whereNotNull('ap.target_date')->orWhereNotNull('ap.start_date'))
                ->orderBy('p.code')->orderBy('a.code')->orderBy('ap.start_date')
                ->select([
                    'ap.id', 'ap.code', 'ap.title', 'ap.start_date', 'ap.target_date',
                    'ap.completion_date', 'ap.progress', 'ap.status',
                    'r.code_risk', 'r.libelle as risk_libelle',
                    'p.code as process_code', 'p.name as process_name',
                    'a.code as activity_code', 'a.name as activity_name',
                    'u.name as responsable',
                ])->get()->map(fn ($x) => (array) $x)->all();
        }

        // Bornes temporelles → liste de mois (YYYY-MM), plafonné à 36 mois
        $dates = [];
        foreach ($rows as $x) {
            foreach (['start_date', 'target_date', 'completion_date'] as $d) {
                if (!empty($x[$d])) $dates[] = substr($x[$d], 0, 7);
            }
        }
        sort($dates);
        $months = [];
        if ($dates) {
            [$y, $m] = array_map('intval', explode('-', $dates[0]));
            [$ey, $em] = array_map('intval', explode('-', end($dates)));
            $guard = 0;
            while (($y < $ey || ($y === $ey && $m <= $em)) && $guard++ < 36) {
                $months[] = sprintf('%04d-%02d', $y, $m);
                if (++$m > 12) { $m = 1; $y++; }
            }
        }

        // Regroupement processus → activité
        $tree = [];
        foreach ($rows as $x) {
            $pk = $x['process_code'] ?? '—';
            $ak = $x['activity_code'] ?? '—';
            $tree[$pk]['code'] = $x['process_code']; $tree[$pk]['name'] = $x['process_name'];
            $tree[$pk]['activities'][$ak]['code'] = $x['activity_code'];
            $tree[$pk]['activities'][$ak]['name'] = $x['activity_name'];
            $tree[$pk]['activities'][$ak]['plans'][] = $x;
        }
        $processes = [];
        foreach ($tree as $p) {
            $acts = [];
            foreach ($p['activities'] as $a) {
                $acts[] = ['code' => $a['code'], 'name' => $a['name'], 'plans' => $a['plans']];
            }
            $processes[] = ['code' => $p['code'], 'name' => $p['name'], 'activities' => $acts];
        }

        $procList = DB::table('processes')->orderBy('code')->get(['id', 'code', 'name'])->map(fn ($p) => (array) $p)->all();

        return Inertia::render('dashboards/Risk/Reports/Gantt', [
            'processes'     => $processes,
            'months'        => $months,
            'processList'   => $procList,
            'processFilter' => $processFilter,
        ]);
    }

    // =====================================================================
    //  HELPERS
    // =====================================================================

    private function matrixMeta(int $tid, $configId): array
    {
        if (!$configId) return ['impacts' => [], 'frequencies' => [], 'zones' => []];
        $q = fn ($t) => DB::table($t)->where('tenant_id', $tid)->where('matrix_config_id', $configId)->whereNull('deleted_at');
        return [
            'impacts'     => $q('risk_impact_levels')->orderByDesc('score')->get(['score', 'label', 'color_code'])->map(fn ($x) => (array) $x)->all(),
            'frequencies' => $q('risk_frequency_levels')->orderBy('score')->get(['score', 'label', 'color_code'])->map(fn ($x) => (array) $x)->all(),
            'zones'       => DB::table('risk_criticality_zones')->where('tenant_id', $tid)->where('matrix_config_id', $configId)
                                ->orderBy('sort_order')->get(['min_score', 'max_score', 'color_code', 'label'])->map(fn ($x) => (array) $x)->all(),
        ];
    }
}
