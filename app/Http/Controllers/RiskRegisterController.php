<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\RiskRegister;
use Inertia\Inertia;
use Inertia\Response;

class RiskRegisterController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $tenantId = session('tenant_id') ?? 1;

        $risks = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->with([
                'activity.process.macroProcess',
                'nomenclature',
                'impactLevel',
                'frequencyLevel',
                'criticalityZone',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($r) => $this->formatRisk($r));

        return Inertia::render('dashboards/Risk/RiskRegister/index', [
            'risks'  => $risks,
            'stats'  => $this->getStats($tenantId),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): Response
    {
        $tenantId     = session('tenant_id') ?? 1;
        $fromIncident = session()->pull('risk_from_incident', null);

        return Inertia::render('dashboards/Risk/RiskRegister/form', [
            'risk'            => null,
            'entities'        => $this->getEntities(),
            'nomenclatures'   => $this->getNomenclatures(),
            'impactLevels'    => $this->getImpactLevels($tenantId),
            'frequencyLevels' => $this->getFrequencyLevels($tenantId),
            'fromIncident'    => $fromIncident,
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id): Response
    {
        $tenantId = session('tenant_id') ?? 1;

        $risk = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->with(['activity.process.macroProcess', 'nomenclature', 'impactLevel', 'frequencyLevel', 'criticalityZone'])
            ->findOrFail($id);

        return Inertia::render('dashboards/Risk/RiskRegister/form', [
            'risk'            => $this->formatRisk($risk),
            'entities'        => $this->getEntities(),
            'nomenclatures'   => $this->getNomenclatures(),
            'impactLevels'    => $this->getImpactLevels($tenantId),
            'frequencyLevels' => $this->getFrequencyLevels($tenantId),
            'fromIncident'    => null,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $tenantId  = session('tenant_id') ?? 1;
        $validated = $request->validate($this->rules());

        $risk = RiskRegister::on('tenant')->create([
            'tenant_id'           => $tenantId,
            'code_risk'           => RiskRegister::generateCode($tenantId),
            'libelle'             => $validated['libelle'],
            'description'         => $validated['description'] ?? null,
            'entity_id'           => $validated['entity_id'],
            'activity_id'         => $validated['activity_id'],
            'nomenclature_id'     => $validated['nomenclature_id'],
            'causes'              => $validated['causes'] ?? null,
            'consequences'        => $validated['consequences'] ?? null,
            'controles_existants' => $validated['controles_existants'] ?? null,
            'owner'               => $validated['owner'] ?? null,
            'plan_traitement'     => $validated['plan_traitement'] ?? null,
            'impact_level_id'     => $validated['impact_level_id'] ?? null,
            'frequency_level_id'  => $validated['frequency_level_id'] ?? null,
            'statut'              => $validated['statut'] ?? 'draft',
            'incident_id'         => $validated['incident_id'] ?? null,
            'created_by'          => auth()->id(),
        ]);

        if ($risk->impact_level_id && $risk->frequency_level_id) {
            $risk->computeCriticality();
        }

        if ($risk->incident_id) {
            DB::connection('tenant')
                ->table('risk_incidents')
                ->where('id', $risk->incident_id)
                ->update(['risk_id' => $risk->id]);
        }

        return redirect()
            ->route('risk.core.risks.index')
            ->with('success', "Risque {$risk->code_risk} créé avec succès.");
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $tenantId  = session('tenant_id') ?? 1;
        $validated = $request->validate($this->rules());

        $risk = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->findOrFail($id);

        $risk->update([
            'libelle'             => $validated['libelle'],
            'description'         => $validated['description'] ?? null,
            'entity_id'           => $validated['entity_id'],
            'activity_id'         => $validated['activity_id'],
            'nomenclature_id'     => $validated['nomenclature_id'],
            'causes'              => $validated['causes'] ?? null,
            'consequences'        => $validated['consequences'] ?? null,
            'controles_existants' => $validated['controles_existants'] ?? null,
            'owner'               => $validated['owner'] ?? null,
            'plan_traitement'     => $validated['plan_traitement'] ?? null,
            'impact_level_id'     => $validated['impact_level_id'] ?? null,
            'frequency_level_id'  => $validated['frequency_level_id'] ?? null,
            'statut'              => $validated['statut'] ?? $risk->statut,
        ]);

        if ($risk->impact_level_id && $risk->frequency_level_id) {
            $risk->computeCriticality();
        }

        return redirect()
            ->route('risk.core.risks.index')
            ->with('success', "Risque {$risk->code_risk} mis à jour.");
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->findOrFail($id)
            ->delete();

        return back()->with('success', 'Risque supprimé.');
    }

    // ── Archive / Activate ────────────────────────────────────────────────────

    public function archive(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;
        $risk = RiskRegister::on('tenant')->tenant($tenantId)->findOrFail($id);
        $risk->update(['statut' => 'archive']);
        return back()->with('success', "Risque {$risk->code_risk} archivé.");
    }

    public function activate(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;
        $risk = RiskRegister::on('tenant')->tenant($tenantId)->findOrFail($id);
        $risk->update(['statut' => 'actif']);
        return back()->with('success', "Risque {$risk->code_risk} activé.");
    }

    // ── API : arbre entité ────────────────────────────────────────────────────

    public function entityTree(int $entityId): JsonResponse
    {
        $rows = DB::connection('tenant')
            ->table('activities as a')
            ->join('assignments as asn', function ($join) use ($entityId) {
                $join->on('asn.mpa_id', '=', 'a.id')
                    ->where('asn.mpa_type', '=', 'activity')
                    ->where('asn.entity_id', '=', $entityId);
            })
            ->join('processes as p',        'p.id',  '=', 'a.process_id')
            ->join('macro_processes as mp',  'mp.id', '=', 'p.macro_process_id')
            ->select(
                'mp.id   as macro_id',
                'mp.code as macro_code',
                'mp.name as macro_name',
                'mp.kind as macro_kind',
                'p.id    as process_id',
                'p.code  as process_code',
                'p.name  as process_name',
                'a.id    as activity_id',
                'a.code  as activity_code',
                'a.name  as activity_name'
            )
            ->orderBy('mp.code')
            ->orderBy('p.code')
            ->orderBy('a.code')
            ->get();

        $tree = [];

        foreach ($rows as $row) {
            if (!isset($tree[$row->macro_id])) {
                $tree[$row->macro_id] = [
                    'id'        => $row->macro_id,
                    'code'      => $row->macro_code,
                    'name'      => $row->macro_name,
                    'kind'      => $row->macro_kind,
                    'processes' => [],
                ];
            }
            if (!isset($tree[$row->macro_id]['processes'][$row->process_id])) {
                $tree[$row->macro_id]['processes'][$row->process_id] = [
                    'id'         => $row->process_id,
                    'code'       => $row->process_code,
                    'name'       => $row->process_name,
                    'activities' => [],
                ];
            }
            $tree[$row->macro_id]['processes'][$row->process_id]['activities'][] = [
                'id'   => $row->activity_id,
                'code' => $row->activity_code,
                'name' => $row->activity_name,
            ];
        }

        $result = array_values(array_map(function ($macro) {
            $macro['processes'] = array_values($macro['processes']);
            return $macro;
        }, $tree));

        return response()->json($result);
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function rules(): array
    {
        return [
            'libelle'             => 'required|string|max:255',
            'description'         => 'nullable|string',
            'entity_id'           => 'required|integer',
            'activity_id'         => 'required|integer',
            'nomenclature_id'     => 'required|integer',
            'causes'              => 'nullable|string',
            'consequences'        => 'nullable|string',
            'controles_existants' => 'nullable|string',
            'owner'               => 'nullable|string|max:255',
            'plan_traitement'     => 'nullable|string',
            'impact_level_id'     => 'nullable|integer',
            'frequency_level_id'  => 'nullable|integer',
            'statut'              => 'nullable|in:draft,actif,archive',
            'incident_id'         => 'nullable|integer',
        ];
    }

    private function getStats(int $tenantId): array
    {
        $base = RiskRegister::on('tenant')->tenant($tenantId);
        return [
            'total'          => (clone $base)->count(),
            'total_draft'    => (clone $base)->draft()->count(),
            'total_actif'    => (clone $base)->actif()->count(),
            'total_archive'  => (clone $base)->archive()->count(),
            'from_incidents' => (clone $base)->whereNotNull('incident_id')->count(),
        ];
    }

    private function getEntities(): array
    {
        return DB::connection('tenant')
            ->table('entities')
            ->select('id', 'name', 'code_base', 'level', 'parent_id', 'description')
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    private function getNomenclatures(): array
    {
        return DB::connection('tenant')
            ->table('risk_nomenclatures')
            ->select('id', 'label', 'parent_id', 'level')
            ->orderBy('label')
            ->get()
            ->toArray();
    }

    private function getImpactLevels(int $tenantId): array
    {
        return DB::connection('tenant')
            ->table('risk_impact_levels')
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('score')
            ->get(['id', 'label', 'score', 'color_code', 'description'])
            ->map(fn ($r) => [
                'id'         => $r->id,
                'label'      => $r->label,
                'score'      => $r->score,
                'color'      => $r->color_code,
                'description'=> $r->description,
            ])
            ->toArray();
    }

    private function getFrequencyLevels(int $tenantId): array
    {
        return DB::connection('tenant')
            ->table('risk_frequency_levels')
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('score')
            ->get(['id', 'label', 'score', 'color_code', 'description'])
            ->map(fn ($r) => [
                'id'         => $r->id,
                'label'      => $r->label,
                'score'      => $r->score,
                'color'      => $r->color_code,
                'description'=> $r->description,
            ])
            ->toArray();
    }

    private function formatRisk(RiskRegister $r): array
    {
        $activity  = $r->relationLoaded('activity')        ? $r->activity        : null;
        $process   = $activity?->relationLoaded('process') ? $activity->process   : null;
        $macro     = $process?->relationLoaded('macroProcess') ? $process->macroProcess : null;
        $zone      = $r->relationLoaded('criticalityZone') ? $r->criticalityZone : null;
        $nomen     = $r->relationLoaded('nomenclature')    ? $r->nomenclature    : null;
        $impact    = $r->relationLoaded('impactLevel')     ? $r->impactLevel     : null;
        $frequency = $r->relationLoaded('frequencyLevel')  ? $r->frequencyLevel  : null;

        return [
            'id'                   => $r->id,
            'code_risk'            => $r->code_risk,
            'libelle'              => $r->libelle,
            'description'          => $r->description,
            'entity_id'            => $r->entity_id,
            'activity_id'          => $r->activity_id,
            'process_id'           => $activity?->process_id,
            'macro_process_id'     => $process?->macro_process_id,
            'activity_code'        => $activity?->code,
            'activity_name'        => $activity?->name,
            'process_code'         => $process?->code,
            'process_name'         => $process?->name,
            'macro_process_code'   => $macro?->code,
            'macro_process_name'   => $macro?->name,
            'macro_process_kind'   => $macro?->kind,
            'nomenclature_id'      => $r->nomenclature_id,
            'nomenclature_label'   => $nomen?->label,
            'nomenclature_level'   => $nomen?->level,
            'causes'               => $r->causes,
            'consequences'         => $r->consequences,
            'controles_existants'  => $r->controles_existants,
            'owner'                => $r->owner,
            'plan_traitement'      => $r->plan_traitement,
            'impact_level_id'      => $r->impact_level_id,
            'impact_label'         => $impact?->label,
            'impact_score'         => $impact?->score,
            'impact_color'         => null,
            'frequency_level_id'   => $r->frequency_level_id,
            'frequency_label'      => $frequency?->label,
            'frequency_score'      => $frequency?->score,
            'criticality_score'    => $r->criticality_score,
            'criticality_zone_id'  => $r->criticality_zone_id,
            'zone_label'           => $zone?->label,
            'zone_color'           => null,
            'statut'               => $r->statut,
            'statut_label'         => $r->statut_label,
            'statut_badge'         => $r->statut_badge,
            'incident_id'          => $r->incident_id,
            'created_at'           => $r->created_at?->format('d/m/Y'),
        ];
    }
}
