<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\RiskSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sessions d'évaluation des risques (chantier #3).
 *
 *  - Une session gèle l'état du registre (risk_session_snapshots) pour
 *    permettre la comparaison d'évolution entre campagnes.
 *  - « Actualiser » crée une session enfant d'une session existante (nouveau
 *    cycle) : on repart de l'état gelé du parent comme référence.
 *  - « Comparer » diffuse l'évolution risque par risque (I / R / Cible),
 *    en tenant compte de l'avancement des plans d'action.
 *
 *  Tables : risk_sessions, risk_session_snapshots (cf.
 *  database/sql/risk_sessions_00_schema.sql).
 */
class RiskEvaluationSessionController extends Controller
{
    private function tid(): int
    {
        return (int)(session('tenant_id') ?? 1);
    }

    // =====================================================================
    //  LECTURE DU REGISTRE VIVANT (mise en forme « snapshot »)
    // =====================================================================

    /**
     * Lit le registre courant + son évaluation I/R/Cible + l'avancement des
     * plans d'action, sous la forme exacte d'une ligne de snapshot.
     * Clé = risk_id.
     */
    private function liveRows(): array
    {
        $tid = $this->tid();

        if (!Schema::hasTable('risk_register')) return [];

        $rows = DB::table('risk_register as r')
            ->leftJoin('activities as a',              'a.id',   '=', 'r.activity_id')
            ->leftJoin('processes as p',               'p.id',   '=', 'a.process_id')
            ->leftJoin('macro_processes as mp',        'mp.id',  '=', 'p.macro_process_id')
            ->leftJoin('entities as e',                'e.id',   '=', 'r.entity_id')
            ->leftJoin('risk_impact_levels as il',     'il.id',  '=', 'r.impact_level_id')
            ->leftJoin('risk_frequency_levels as fl',  'fl.id',  '=', 'r.frequency_level_id')
            ->leftJoin('risk_criticality_zones as cz', 'cz.id',  '=', 'r.criticality_zone_id')
            ->leftJoin('risk_impact_levels as ril',    'ril.id', '=', 'r.residual_impact_level_id')
            ->leftJoin('risk_frequency_levels as rfl', 'rfl.id', '=', 'r.residual_frequency_level_id')
            ->leftJoin('risk_criticality_zones as rcz','rcz.id', '=', 'r.residual_criticality_zone_id')
            ->leftJoin('risk_impact_levels as til',    'til.id', '=', 'r.target_impact_level_id')
            ->leftJoin('risk_frequency_levels as tfl', 'tfl.id', '=', 'r.target_frequency_level_id')
            ->leftJoin('risk_criticality_zones as tcz','tcz.id', '=', 'r.target_criticality_zone_id')
            ->where('r.tenant_id', $tid)
            ->whereNull('r.deleted_at')
            ->whereNull('r.moved_to_library_at')
            ->select([
                'r.id as risk_id', 'r.code_risk', 'r.libelle',
                'r.entity_id', 'e.name as entity_name',
                'r.activity_id', 'a.name as activity_name',
                'a.process_id as process_id', 'p.name as process_name',
                'p.macro_process_id as macro_process_id', 'mp.name as macro_process_name',
                'il.score as inh_impact_score', 'fl.score as inh_freq_score',
                'r.criticality_score as inh_criticality',
                'r.criticality_zone_id as inh_zone_id', 'cz.label as inh_zone_label', 'cz.color_code as inh_zone_color',
                'ril.score as res_impact_score', 'rfl.score as res_freq_score',
                'r.residual_criticality_score as res_criticality',
                'r.residual_criticality_zone_id as res_zone_id', 'rcz.label as res_zone_label', 'rcz.color_code as res_zone_color',
                'til.score as tgt_impact_score', 'tfl.score as tgt_freq_score',
                'r.target_criticality_score as tgt_criticality',
                'r.target_criticality_zone_id as tgt_zone_id', 'tcz.label as tgt_zone_label', 'tcz.color_code as tgt_zone_color',
                Schema::hasColumn('risk_register', 'decision') ? 'r.decision' : DB::raw('NULL as decision'),
            ])
            ->get();

        // Avancement des plans d'action par risque
        $plans = [];
        if (Schema::hasTable('risk_action_plans')) {
            $plans = DB::table('risk_action_plans')
                ->where('tenant_id', $tid)
                ->whereNull('deleted_at')
                ->groupBy('risk_id')
                ->selectRaw("risk_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as done,
                    ROUND(AVG(COALESCE(progress,0))) as prog")
                ->get()->keyBy('risk_id');
        }

        $out = [];
        foreach ($rows as $r) {
            $pl = $plans[$r->risk_id] ?? null;
            $out[$r->risk_id] = array_merge((array)$r, [
                'plans_total'    => (int)($pl->total ?? 0),
                'plans_done'     => (int)($pl->done ?? 0),
                'plans_progress' => $pl ? (int)$pl->prog : null,
            ]);
        }
        return $out;
    }

    // =====================================================================
    //  INDEX
    // =====================================================================

    public function index(Request $request): Response
    {
        $tid = $this->tid();

        $sessions = RiskSession::forTenant($tid)
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'id'                => $s->id,
                'code'              => $s->code,
                'name'              => $s->name,
                'year'              => $s->year,
                'status'            => $s->status,
                'is_active'         => $s->is_active,
                'parent_session_id' => $s->parent_session_id,
                'risks_count'       => $s->risks_count,
                'snapshot_at'       => optional($s->snapshot_at)->format('Y-m-d H:i'),
                'closed_at'         => optional($s->closed_at)->format('Y-m-d H:i'),
                'created_at'        => optional($s->created_at)->format('Y-m-d'),
                'notes'             => $s->notes,
            ]);

        $live = $this->liveRows();
        $liveStats = [
            'total'         => count($live),
            'with_inherent' => count(array_filter($live, fn ($r) => $r['inh_criticality'] !== null)),
            'with_residual' => count(array_filter($live, fn ($r) => $r['res_criticality'] !== null)),
            'with_target'   => count(array_filter($live, fn ($r) => $r['tgt_criticality'] !== null)),
        ];

        return Inertia::render('dashboards/Risk/EvaluationSessions/Index', [
            'sessions'  => $sessions,
            'liveStats' => $liveStats,
        ]);
    }

    // =====================================================================
    //  CRUD / CYCLE DE VIE
    // =====================================================================

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'year'  => 'nullable|integer|min:2000|max:2100',
            'notes' => 'nullable|string',
        ]);
        $tid = $this->tid();

        $session = RiskSession::create([
            'tenant_id'  => $tid,
            'code'       => $this->nextCode($tid, $data['year'] ?? (int)date('Y')),
            'name'       => $data['name'],
            'year'       => $data['year'] ?? (int)date('Y'),
            'status'     => 'draft',
            'notes'      => $data['notes'] ?? null,
            'created_by' => optional($request->user())->id,
        ]);

        // Première session du tenant → active d'office
        if (RiskSession::forTenant($tid)->count() === 1) {
            $session->activate();
        }

        return back()->with('success', "Session « {$session->name} » créée.");
    }

    /**
     * Actualisation : crée une session enfant à partir d'une session existante.
     * Le parent est d'abord gelé (si non déjà fait) pour servir de référence,
     * puis la nouvelle session devient active. Le travail d'évaluation se
     * poursuit dans le registre vivant (risk_register).
     */
    public function actualiser(Request $request, int $id): RedirectResponse
    {
        $tid    = $this->tid();
        $parent = RiskSession::forTenant($tid)->findOrFail($id);

        // Geler le parent s'il ne l'est pas encore (référence figée)
        if (!$parent->snapshot_at) {
            $this->freeze($parent);
        }

        $year = $request->integer('year') ?: ((int)($parent->year ?? date('Y')) + 1);

        $child = RiskSession::create([
            'tenant_id'         => $tid,
            'code'              => $this->nextCode($tid, $year),
            'name'              => $request->string('name')->toString()
                                    ?: ('Actualisation ' . $year),
            'year'              => $year,
            'status'            => 'active',
            'parent_session_id' => $parent->id,
            'started_at'        => now(),
            'notes'             => $request->string('notes')->toString() ?: null,
            'created_by'        => optional($request->user())->id,
        ]);
        $child->activate();

        return redirect()
            ->route('risk.core.eval-sessions.index')
            ->with('success', "Session actualisée « {$child->name} » (référence : {$parent->name}).");
    }

    public function snapshot(Request $request, int $id): JsonResponse
    {
        $tid     = $this->tid();
        $session = RiskSession::forTenant($tid)->findOrFail($id);
        $count   = $this->freeze($session);

        return response()->json([
            'success'     => true,
            'message'     => "Registre gelé : {$count} risque(s).",
            'risks_count' => $count,
            'snapshot_at' => optional($session->fresh()->snapshot_at)->format('Y-m-d H:i'),
        ]);
    }

    public function activate(int $id): RedirectResponse
    {
        $session = RiskSession::forTenant($this->tid())->findOrFail($id);
        $session->activate();
        return back()->with('success', "Session « {$session->name} » activée.");
    }

    public function close(int $id): RedirectResponse
    {
        $tid     = $this->tid();
        $session = RiskSession::forTenant($tid)->findOrFail($id);

        if (!$session->snapshot_at) {
            $this->freeze($session);
        }
        $session->update(['status' => 'closed', 'closed_at' => now(), 'is_active' => false]);

        return back()->with('success', "Session « {$session->name} » clôturée.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $tid     = $this->tid();
        $session = RiskSession::forTenant($tid)->findOrFail($id);
        DB::connection('tenant')->table('risk_session_snapshots')->where('session_id', $session->id)->delete();
        $session->delete();
        return back()->with('success', 'Session supprimée.');
    }

    // =====================================================================
    //  RAPPORT DE GESTION & CARTOGRAPHIE (lié à la session)
    // =====================================================================

    /** Page rapport de gestion — modèle synthétique, alimenté par la session. */
    public function report(Request $request, int $id): Response
    {
        $tid = $this->tid();
        $session = RiskSession::forTenant($tid)->findOrFail($id);
        $processId = $request->integer('process') ?: null;

        // Liste des processus (pour le filtre « rapport par processus »).
        // NB : la table `processes` est propre au tenant (pas de colonne tenant_id).
        $processes = DB::table('processes')
            ->orderBy('code')->get(['id', 'code', 'name'])
            ->map(fn ($p) => (array) $p)->all();

        return Inertia::render('dashboards/Risk/EvaluationSessions/Report', [
            'session' => [
                'id'          => $session->id,
                'name'        => $session->name,
                'year'        => $session->year,
                'status'      => $session->status,
                'snapshot_at' => optional($session->snapshot_at)->format('Y-m-d H:i'),
                'is_frozen'   => $session->snapshot_at !== null,
                'parent_id'   => $session->parent_session_id,
            ],
            'report'    => $this->assembleReport($session, $processId),
            'content'   => json_decode($session->report_json ?: '{}', true) ?: [],
            'processes' => $processes,
            'processId' => $processId,
        ]);
    }

    /** Enregistre le narratif éditable du rapport. */
    public function saveReport(Request $request, int $id): JsonResponse
    {
        $session = RiskSession::forTenant($this->tid())->findOrFail($id);
        $session->update(['report_json' => json_encode($request->input('content', []), JSON_UNESCAPED_UNICODE)]);

        return response()->json(['success' => true, 'message' => 'Rapport enregistré.']);
    }

    /** Assemble toutes les données dynamiques du rapport pour une session. */
    private function assembleReport(RiskSession $session, ?int $processId = null): array
    {
        $tid = $this->tid();

        // État courant : snapshot de la session si gelée, sinon registre vivant.
        $current = $session->snapshot_at ? $this->rowsForSession($session->id) : $this->liveRows();
        $parent  = $session->parent_session_id ? $this->rowsForSession($session->parent_session_id) : [];

        // Filtre « par processus » (chefs de processus)
        if ($processId) {
            $byProc = fn ($rows) => array_values(array_filter($rows, fn ($r) => (int) ($r['process_id'] ?? 0) === $processId));
            $current = $byProc($current);
            $parent  = $byProc($parent);
        }
        // Contrainte SQL réutilisable : ne garder que les risques du processus.
        $procWhere = fn ($col) => fn ($q) => $q->whereIn($col, fn ($sub) => $sub->select('id')->from('activities')->where('process_id', $processId));

        $classify = function ($r) {
            $z = $r['res_zone_label'] ?? null;
            if (in_array($z, ['Critique', 'Élevé'], true)) return 'eleve';
            if ($z === 'Modéré') return 'moyen';
            if ($z === 'Faible') return 'faible';
            return 'nd';
        };
        $countBy = function ($rows, $k) use ($classify) {
            $n = 0; foreach ($rows as $r) if ($classify($r) === $k) $n++; return $n;
        };
        $avgProg = function ($rows) {
            $v = []; foreach ($rows as $r) { if (($r['plans_total'] ?? 0) > 0 && ($r['plans_progress'] ?? null) !== null) $v[] = $r['plans_progress']; }
            return count($v) ? (int) round(array_sum($v) / count($v)) : null;
        };
        $delta = fn ($a, $b) => ($a === null || $b === null) ? null : $b - $a;

        // 1) Profil de risque global (N-1 = parent, N = courant)
        $profil = [
            'eleve'      => ['n1' => $countBy($parent, 'eleve'), 'n' => $countBy($current, 'eleve')],
            'moyen'      => ['n1' => $countBy($parent, 'moyen'), 'n' => $countBy($current, 'moyen')],
            'faible'     => ['n1' => $countBy($parent, 'faible'), 'n' => $countBy($current, 'faible')],
            'mitigation' => ['n1' => $avgProg($parent), 'n' => $avgProg($current)],
            'total'      => ['n1' => count($parent), 'n' => count($current)],
        ];
        foreach (['eleve', 'moyen', 'faible', 'mitigation'] as $k) {
            $profil[$k]['delta'] = $delta($profil[$k]['n1'], $profil[$k]['n']);
        }

        // 2) Synthèse par catégorie (nomenclature) sur l'état courant du registre
        $catRows = DB::table('risk_register as r')
            ->leftJoin('risk_nomenclatures as n', 'n.id', '=', 'r.nomenclature_id')
            ->leftJoin('risk_criticality_zones as z', 'z.id', '=', 'r.residual_criticality_zone_id')
            ->where('r.tenant_id', $tid)->whereNull('r.deleted_at')->whereNull('r.moved_to_library_at')
            ->when($processId, $procWhere('r.activity_id'))
            ->get(['r.id', 'n.label as cat', 'z.label as zone']);
        $catMap = [];
        foreach ($catRows as $r) {
            $c = $r->cat ?: 'Non catégorisé';
            if (!isset($catMap[$c])) $catMap[$c] = ['categorie' => $c, 'total' => 0, 'eleves' => 0];
            $catMap[$c]['total']++;
            if (in_array($r->zone, ['Critique', 'Élevé'], true)) $catMap[$c]['eleves']++;
        }
        usort($catMap, fn ($a, $b) => $b['eleves'] <=> $a['eleves']);
        $categories = array_values($catMap);

        // 3) Plan de mitigation — actions prioritaires (risques résiduels élevés)
        $actions = [];
        if (Schema::hasTable('risk_action_plans')) {
            $actions = DB::table('risk_action_plans as ap')
                ->join('risk_register as r', 'r.id', '=', 'ap.risk_id')
                ->leftJoin('users as u', 'u.id', '=', 'ap.assigned_to')
                ->leftJoin('risk_criticality_zones as z', 'z.id', '=', 'r.residual_criticality_zone_id')
                ->where('ap.tenant_id', $tid)->whereNull('ap.deleted_at')
                ->whereIn('z.label', ['Critique', 'Élevé'])
                ->whereNotIn('ap.status', ['completed', 'cancelled'])
                ->when($processId, $procWhere('r.activity_id'))
                ->orderByDesc('r.residual_criticality_score')->orderBy('ap.target_date')
                ->limit(10)
                ->get([
                    'ap.code', 'ap.title', 'ap.target_date', 'ap.progress', 'ap.status',
                    'r.code_risk', 'r.libelle as risk_libelle',
                    'u.name as responsable', 'z.label as zone', 'z.color_code as zone_color',
                ])->map(fn ($x) => (array) $x)->all();
        }

        // Suivi global des plans d'action
        $suivi = ['total' => 0, 'done' => 0, 'ongoing' => 0, 'todo' => 0, 'taux' => 0];
        if (Schema::hasTable('risk_action_plans')) {
            $plans = DB::table('risk_action_plans')->where('tenant_id', $tid)->whereNull('deleted_at')
                ->when($processId, fn ($q) => $q->whereIn('risk_id', fn ($s) => $s->select('r2.id')
                    ->from('risk_register as r2')->join('activities as a2', 'a2.id', '=', 'r2.activity_id')
                    ->where('a2.process_id', $processId)))
                ->get(['status', 'progress']);
            $suivi['total']   = $plans->count();
            $suivi['done']    = $plans->where('progress', 100)->count();
            $suivi['ongoing'] = $plans->filter(fn ($p) => ($p->progress ?? 0) >= 50 && ($p->progress ?? 0) < 100)->count();
            $suivi['todo']    = $plans->filter(fn ($p) => ($p->progress ?? 0) < 50)->count();
            $suivi['taux']    = $plans->count() ? (int) round($plans->avg('progress')) : 0;
        }

        // 4) Répartition résiduelle par zone (cartographie synthétique) + légende
        $configId = DB::table('risk_matrix_configs')->where('tenant_id', $tid)->where('is_active', 1)->value('id');
        $zones = $configId ? DB::table('risk_criticality_zones')->where('tenant_id', $tid)->where('matrix_config_id', $configId)
            ->orderBy('sort_order')->get(['label', 'min_score', 'max_score', 'color_code'])->map(fn ($z) => (array) $z)->all() : [];
        $zoneDist = [];
        foreach ($zones as $z) $zoneDist[$z['label']] = 0;
        foreach ($current as $r) { $z = $r['res_zone_label'] ?? null; if ($z !== null && isset($zoneDist[$z])) $zoneDist[$z]++; }

        // 5) KPI
        $incidentsCount = 0;
        if (Schema::hasTable('risk_incidents')) {
            $incidentsCount = DB::table('risk_incidents')->where('tenant_id', $tid)->whereNull('deleted_at')->count();
        }
        $kpi = [
            ['label' => 'Risques résiduels « Élevés »', 'valeur' => $profil['eleve']['n'], 'seuil' => '< 10', 'ok' => $profil['eleve']['n'] < 10],
            ['label' => 'Taux de réalisation du plan de mitigation', 'valeur' => $suivi['taux'] . ' %', 'seuil' => '> 80 %', 'ok' => $suivi['taux'] > 80],
            ['label' => 'Plans d\'action en cours', 'valeur' => $suivi['ongoing'], 'seuil' => '—', 'ok' => true],
            ['label' => 'Incidents recensés', 'valeur' => $incidentsCount, 'seuil' => '—', 'ok' => true],
        ];

        // 6) Incidents majeurs (défensif sur le schéma)
        $incidents = [];
        if (Schema::hasTable('risk_incidents')) {
            $cols = ['id'];
            foreach (['titre', 'libelle', 'description', 'date_incident', 'cout', 'cout_estime', 'montant', 'statut', 'status', 'created_at'] as $c) {
                if (Schema::hasColumn('risk_incidents', $c)) $cols[] = $c;
            }
            $incidents = DB::table('risk_incidents')->where('tenant_id', $tid)->whereNull('deleted_at')
                ->orderByDesc('created_at')->limit(8)->get($cols)->map(fn ($x) => (array) $x)->all();
        }

        // Annexe — critères de cotation (niveaux impact / fréquence)
        $cotation = [
            'impacts'     => $configId ? DB::table('risk_impact_levels')->where('matrix_config_id', $configId)->whereNull('deleted_at')
                                ->orderByDesc('score')->get(['score', 'label', 'color_code', 'description'])->map(fn ($x) => (array) $x)->all() : [],
            'frequencies' => $configId ? DB::table('risk_frequency_levels')->where('matrix_config_id', $configId)->whereNull('deleted_at')
                                ->orderByDesc('score')->get(['score', 'label', 'color_code', 'description'])->map(fn ($x) => (array) $x)->all() : [],
        ];

        $parentMeta = $session->parent_session_id
            ? RiskSession::forTenant($tid)->find($session->parent_session_id)
            : null;

        return [
            'periodeN'    => $session->year,
            'periodeN1'   => $parentMeta?->year,
            'refN'        => $session->name,
            'refN1'       => $parentMeta?->name,
            'profil'      => $profil,
            'categories'  => $categories,
            'actions'     => $actions,
            'suivi'       => $suivi,
            'zones'       => $zones,
            'zoneDist'    => $zoneDist,
            'kpi'         => $kpi,
            'incidents'   => $incidents,
            'cotation'    => $cotation,
        ];
    }

    // =====================================================================
    //  COMPARAISON
    // =====================================================================

    /** Page de comparaison (sélection de 2 sessions + évolution). */
    public function compare(Request $request): Response
    {
        $tid = $this->tid();

        $sessions = RiskSession::forTenant($tid)
            ->orderByDesc('year')->orderByDesc('created_at')
            ->get(['id', 'name', 'year', 'status', 'snapshot_at', 'risks_count'])
            ->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'year'        => $s->year,
                'status'      => $s->status,
                'has_snapshot'=> $s->snapshot_at !== null,
                'risks_count' => $s->risks_count,
            ]);

        return Inertia::render('dashboards/Risk/EvaluationSessions/Compare', [
            'sessions' => $sessions,
            'preA'     => $request->integer('a') ?: null,
            'preB'     => $request->input('b'),
        ]);
    }

    /**
     * Comparaison d'évolution entre deux sessions (ou une session vs le
     * registre vivant si `b` est absent / = 'live').
     * GET /eval-sessions/compare/data?a=..&b=..
     */
    public function compareData(Request $request): JsonResponse
    {
        $tid = $this->tid();
        $aId = $request->integer('a');
        $bParam = $request->input('b');

        $a = $this->rowsForSession($aId);
        $aMeta = RiskSession::forTenant($tid)->find($aId);

        if ($bParam === null || $bParam === '' || $bParam === 'live') {
            $b = $this->liveRows();
            $bMeta = ['id' => 'live', 'name' => 'État actuel (registre vivant)', 'year' => (int)date('Y')];
        } else {
            $b = $this->rowsForSession((int)$bParam);
            $sb = RiskSession::forTenant($tid)->find((int)$bParam);
            $bMeta = $sb ? ['id' => $sb->id, 'name' => $sb->name, 'year' => $sb->year] : null;
        }

        $ids  = array_unique(array_merge(array_keys($a), array_keys($b)));
        $rows = [];
        $sum  = ['new' => 0, 'removed' => 0, 'improved' => 0, 'worsened' => 0, 'stable' => 0];

        foreach ($ids as $rid) {
            $ra = $a[$rid] ?? null;
            $rb = $b[$rid] ?? null;

            $status = 'stable';
            if (!$ra)      $status = 'new';
            elseif (!$rb)  $status = 'removed';
            else {
                $ca = $this->crit($ra, 'res');
                $cb = $this->crit($rb, 'res');
                if ($ca !== null && $cb !== null) {
                    if ($cb < $ca)      $status = 'improved';
                    elseif ($cb > $ca)  $status = 'worsened';
                }
            }
            $sum[$status]++;

            $ref = $rb ?? $ra;
            $rows[] = [
                'risk_id'      => $rid,
                'code_risk'    => $ref['code_risk'] ?? null,
                'libelle'      => $ref['libelle'] ?? null,
                'entity_name'  => $ref['entity_name'] ?? null,
                'process_name' => $ref['process_name'] ?? null,
                'activity_name'=> $ref['activity_name'] ?? null,
                'status'       => $status,
                'a' => $ra ? $this->triplet($ra) : null,
                'b' => $rb ? $this->triplet($rb) : null,
                'delta_res' => ($ra && $rb) ? $this->diff($this->crit($ra,'res'), $this->crit($rb,'res')) : null,
                'delta_inh' => ($ra && $rb) ? $this->diff($this->crit($ra,'inh'), $this->crit($rb,'inh')) : null,
                'delta_tgt' => ($ra && $rb) ? $this->diff($this->crit($ra,'tgt'), $this->crit($rb,'tgt')) : null,
                'plans_b'   => $rb ? ['total' => $rb['plans_total'] ?? 0, 'done' => $rb['plans_done'] ?? 0, 'progress' => $rb['plans_progress'] ?? null] : null,
            ];
        }

        // tri : dégradés d'abord, puis améliorés, puis le reste
        $order = ['worsened' => 0, 'new' => 1, 'removed' => 2, 'improved' => 3, 'stable' => 4];
        usort($rows, fn ($x, $y) => ($order[$x['status']] <=> $order[$y['status']]));

        return response()->json([
            'a'       => $aMeta ? ['id' => $aMeta->id, 'name' => $aMeta->name, 'year' => $aMeta->year] : null,
            'b'       => $bMeta,
            'summary' => $sum,
            'averages'=> [
                'a' => $this->averages($a),
                'b' => $this->averages($b),
            ],
            'rows'    => $rows,
        ]);
    }

    // =====================================================================
    //  HELPERS
    // =====================================================================

    /** Gèle le registre vivant dans la session (upsert par session+risk). */
    private function freeze(RiskSession $session): int
    {
        $tid  = $this->tid();
        $live = $this->liveRows();
        $now  = now();

        $activeConfig = DB::connection('tenant')->table('risk_matrix_configs')
            ->where('tenant_id', $tid)->where('is_active', 1)->value('id');

        DB::connection('tenant')->transaction(function () use ($session, $live, $now, $tid) {
            DB::connection('tenant')->table('risk_session_snapshots')
                ->where('session_id', $session->id)->delete();

            $insert = [];
            foreach ($live as $r) {
                $insert[] = array_merge($r, [
                    'tenant_id'   => $tid,
                    'session_id'  => $session->id,
                    'captured_at' => $now,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
            foreach (array_chunk($insert, 200) as $chunk) {
                DB::connection('tenant')->table('risk_session_snapshots')->insert($chunk);
            }
        });

        $session->update([
            'snapshot_at'      => $now,
            'risks_count'      => count($live),
            'matrix_config_id' => $activeConfig,
        ]);

        return count($live);
    }

    /** Lignes gelées d'une session, indexées par risk_id. */
    private function rowsForSession(?int $sessionId): array
    {
        if (!$sessionId) return [];
        return DB::connection('tenant')->table('risk_session_snapshots')
            ->where('session_id', $sessionId)
            ->get()
            ->keyBy('risk_id')
            ->map(fn ($r) => (array)$r)
            ->toArray();
    }

    private function crit(array $row, string $stage): ?float
    {
        $v = $row[$stage . '_criticality'] ?? null;
        return $v === null || $v === '' ? null : (float)$v;
    }

    private function diff(?float $a, ?float $b): ?float
    {
        if ($a === null || $b === null) return null;
        return round($b - $a, 2);
    }

    /** Renvoie les 3 criticités + libellés/couleurs de zone d'une ligne. */
    private function triplet(array $r): array
    {
        return [
            'inh' => ['crit' => $this->crit($r, 'inh'), 'zone' => $r['inh_zone_label'] ?? null, 'color' => $r['inh_zone_color'] ?? null,
                      'impact' => $r['inh_impact_score'] ?? null, 'freq' => $r['inh_freq_score'] ?? null],
            'res' => ['crit' => $this->crit($r, 'res'), 'zone' => $r['res_zone_label'] ?? null, 'color' => $r['res_zone_color'] ?? null,
                      'impact' => $r['res_impact_score'] ?? null, 'freq' => $r['res_freq_score'] ?? null],
            'tgt' => ['crit' => $this->crit($r, 'tgt'), 'zone' => $r['tgt_zone_label'] ?? null, 'color' => $r['tgt_zone_color'] ?? null,
                      'impact' => $r['tgt_impact_score'] ?? null, 'freq' => $r['tgt_freq_score'] ?? null],
        ];
    }

    private function averages(array $rows): array
    {
        $avg = function (string $stage) use ($rows) {
            $vals = [];
            foreach ($rows as $r) { $c = $this->crit((array)$r, $stage); if ($c !== null) $vals[] = $c; }
            return count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
        };
        return ['inh' => $avg('inh'), 'res' => $avg('res'), 'tgt' => $avg('tgt'), 'count' => count($rows)];
    }

    private function nextCode(int $tid, int $year): string
    {
        $n = RiskSession::forTenant($tid)->where('year', $year)->count() + 1;
        return sprintf('SESS-%d-%02d', $year, $n);
    }
}
