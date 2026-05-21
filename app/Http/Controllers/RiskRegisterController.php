<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\RiskRegister;
use App\Models\RiskIncident;
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
            ->with($this->baseRelations())
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
            'risk'         => null,
            'entities'     => $this->getEntities(),
            'nomenclatures'=> $this->getNomenclatures(),
            'fromIncident' => $fromIncident,
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id): Response
    {
        $tenantId = session('tenant_id') ?? 1;

        $risk = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->with($this->baseRelations())
            ->findOrFail($id);

        return Inertia::render('dashboards/Risk/RiskRegister/form', [
            'risk'         => $this->formatRisk($risk),
            'entities'     => $this->getEntities(),
            'nomenclatures'=> $this->getNomenclatures(),
            'fromIncident' => null,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $tenantId  = session('tenant_id') ?? 1;
        $validated = $request->validate($this->rules());

        $risk = RiskRegister::on('tenant')->create([
            'tenant_id'                      => $tenantId,
            'code_risk'                      => RiskRegister::generateCode($tenantId),
            'libelle'                        => $validated['libelle'],
            'description'                    => $validated['description'] ?? null,
            'entity_id'                      => $validated['entity_id'],
            'activity_id'                    => $validated['activity_id'],
            'nomenclature_id'                => $validated['nomenclature_id'],
            'causes'                         => $validated['causes'] ?? null,
            'consequences'                   => $validated['consequences'] ?? null,
            'consequences_autres_processus'  => $validated['consequences_autres_processus'] ?? null,
            'cout_consequences'              => $validated['cout_consequences'] ?? null,
            'controles_existants'            => $validated['controles_existants'] ?? null,
            'owner'                          => $validated['owner'] ?? null,
            'entite_partenaire_impliquee'    => $validated['entite_partenaire_impliquee'] ?? null,
            'outils_utilises'               => $validated['outils_utilises'] ?? null,
            'vraisemblance_apparition'       => $validated['vraisemblance_apparition'] ?? null,
            'plan_traitement'                => $validated['plan_traitement'] ?? null,
            'critere_risque'                 => $validated['critere_risque'] ?? null,
            'statut'                         => $validated['statut'] ?? 'draft',
            'risque_realise'                 => $validated['risque_realise'] ?? false,
            'cout_risque'                    => $validated['cout_risque'] ?? null,
            'incident_id'                    => $validated['incident_id'] ?? null,
            'created_by'                     => auth()->id(),
        ]);

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
            'libelle'                        => $validated['libelle'],
            'description'                    => $validated['description'] ?? null,
            'entity_id'                      => $validated['entity_id'],
            'activity_id'                    => $validated['activity_id'],
            'nomenclature_id'                => $validated['nomenclature_id'],
            'causes'                         => $validated['causes'] ?? null,
            'consequences'                   => $validated['consequences'] ?? null,
            'consequences_autres_processus'  => $validated['consequences_autres_processus'] ?? null,
            'cout_consequences'              => $validated['cout_consequences'] ?? null,
            'controles_existants'            => $validated['controles_existants'] ?? null,
            'owner'                          => $validated['owner'] ?? null,
            'entite_partenaire_impliquee'    => $validated['entite_partenaire_impliquee'] ?? null,
            'outils_utilises'               => $validated['outils_utilises'] ?? null,
            'vraisemblance_apparition'       => $validated['vraisemblance_apparition'] ?? null,
            'plan_traitement'                => $validated['plan_traitement'] ?? null,
            'critere_risque'                 => $validated['critere_risque'] ?? null,
            'statut'                         => $validated['statut'] ?? $risk->statut,
            'risque_realise'                 => $validated['risque_realise'] ?? $risk->risque_realise,
            'cout_risque'                    => $validated['cout_risque'] ?? null,
        ]);

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

    public function moveToLibrary(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;
        $risk = RiskRegister::on('tenant')->tenant($tenantId)->findOrFail($id);

        if ($risk->moved_to_library_at) {
            return back()->with('info', "Ce risque est déjà dans la bibliothèque.");
        }

        $risk->update(['moved_to_library_at' => now()]);

        return back()->with('success', "Risque {$risk->code_risk} transféré dans la bibliothèque.");
    }

    public function removeFromLibrary(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;
        $risk = RiskRegister::on('tenant')->tenant($tenantId)->findOrFail($id);
        $risk->update(['moved_to_library_at' => null]);

        return back()->with('success', "Risque {$risk->code_risk} retiré de la bibliothèque.");
    }

    // ── API : éléments de la bibliothèque (risques + incidents) ──────────────

    public function libraryItems(): JsonResponse
    {
        $tenantId = session('tenant_id') ?? 1;

        // ── Risques en bibliothèque ────────────────────────────────────────
        $risks = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->bibliotheque()
            ->with(['nomenclature', 'activity.process.macroProcess'])
            ->orderBy('moved_to_library_at', 'desc')
            ->get()
            ->map(fn ($r) => [
                '_type'                        => 'risk',
                'id'                           => $r->id,
                'code'                         => $r->code_risk,
                'libelle'                      => $r->libelle,
                'description'                  => $r->description,
                'causes'                       => $r->causes,
                'consequences'                 => $r->consequences,
                'consequences_autres_processus'=> $r->consequences_autres_processus,
                'cout_consequences'            => $r->cout_consequences,
                'controles_existants'          => $r->controles_existants,
                'plan_traitement'              => $r->plan_traitement,
                'critere_risque'               => $r->critere_risque,
                'entite_partenaire_impliquee'  => $r->entite_partenaire_impliquee,
                'outils_utilises'             => $r->outils_utilises,
                'vraisemblance_apparition'     => $r->vraisemblance_apparition,
                'owner'                        => $r->owner,
                'nomenclature_id'              => $r->nomenclature_id,
                'nomenclature_label'           => $r->nomenclature?->label,
                'activity_name'                => $r->activity?->name,
                'process_name'                 => $r->activity?->process?->name,
                'macro_process_name'           => $r->activity?->process?->macroProcess?->name,
                'moved_to_library_at'          => $r->moved_to_library_at?->format('d/m/Y'),
            ]);

        // ── Incidents en bibliothèque ──────────────────────────────────────
        $incidents = RiskIncident::on('tenant')
            ->tenant($tenantId)
            ->bibliotheque()
            ->orderBy('moved_to_library_at', 'desc')
            ->get()
            ->map(fn ($i) => [
                '_type'               => 'incident',
                'id'                  => $i->id,
                'code'                => $i->code_incident,
                'libelle'             => $i->libelle,
                'description'         => $i->description,
                'causes'              => null,
                'consequences'        => null,
                'consequences_autres_processus' => null,
                'cout_consequences'   => null,
                'controles_existants' => null,
                'plan_traitement'     => null,
                'critere_risque'      => null,
                'entite_partenaire_impliquee' => null,
                'outils_utilises'    => null,
                'vraisemblance_apparition' => null,
                'owner'               => null,
                'nomenclature_id'     => null,
                'nomenclature_label'  => null,
                'cout_risque'         => $i->evaluation_monetaire,
                'source'              => $i->source,
                'date_incident'       => $i->date_incident?->format('d/m/Y'),
                'moved_to_library_at' => $i->moved_to_library_at?->format('d/m/Y'),
            ]);

        return response()->json([
            'risks'     => $risks,
            'incidents' => $incidents,
        ]);
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
            'libelle'                        => 'required|string|max:255',
            'description'                    => 'nullable|string',
            'entity_id'                      => 'required|integer',
            'activity_id'                    => 'required|integer',
            'nomenclature_id'                => 'required|integer',
            'causes'                         => 'nullable|string',
            'consequences'                   => 'nullable|string',
            'consequences_autres_processus'  => 'nullable|string',
            'cout_consequences'              => 'nullable|string',
            'controles_existants'            => 'nullable|string',
            'owner'                          => 'nullable|string|max:255',
            'entite_partenaire_impliquee'    => 'nullable|string',
            'outils_utilises'               => 'nullable|string',
            'vraisemblance_apparition'       => 'nullable|string|max:255',
            'plan_traitement'                => 'nullable|string',
            'critere_risque'                 => 'nullable|string',
            'statut'                         => 'nullable|in:draft,actif,archive',
            'risque_realise'                 => 'nullable|boolean',
            'cout_risque'                    => 'nullable|numeric|min:0',
            'incident_id'                    => 'nullable|integer',
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

    private function baseRelations(): array
    {
        return [
            'activity.process.macroProcess',
            'nomenclature',
            'impactLevel',
            'frequencyLevel',
            'criticalityZone',
            'residualImpactLevel',
            'residualFrequencyLevel',
            'residualCriticalityZone',
            'targetImpactLevel',
            'targetFrequencyLevel',
            'targetCriticalityZone',
        ];
    }

    private function getCriticalityZones(int $tenantId): array
    {
        return DB::connection('tenant')
            ->table('risk_criticality_zones')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('min_score')
            ->get(['id', 'label', 'min_score', 'max_score', 'color_code', 'sort_order'])
            ->map(fn ($z) => [
                'id'         => $z->id,
                'label'      => $z->label,
                'min_score'  => $z->min_score,
                'max_score'  => $z->max_score,
                'color_code' => $z->color_code,
            ])
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
        $activity       = $r->relationLoaded('activity')              ? $r->activity              : null;
        $process        = $activity?->relationLoaded('process')        ? $activity->process        : null;
        $macro          = $process?->relationLoaded('macroProcess')    ? $process->macroProcess    : null;
        $nomen          = $r->relationLoaded('nomenclature')           ? $r->nomenclature          : null;
        // Évaluation inhérente
        $impact         = $r->relationLoaded('impactLevel')            ? $r->impactLevel           : null;
        $frequency      = $r->relationLoaded('frequencyLevel')         ? $r->frequencyLevel        : null;
        $zone           = $r->relationLoaded('criticalityZone')        ? $r->criticalityZone       : null;
        // Évaluation résiduelle
        $rImpact        = $r->relationLoaded('residualImpactLevel')    ? $r->residualImpactLevel   : null;
        $rFrequency     = $r->relationLoaded('residualFrequencyLevel') ? $r->residualFrequencyLevel: null;
        $rZone          = $r->relationLoaded('residualCriticalityZone')? $r->residualCriticalityZone: null;
        // Évaluation cible
        $tImpact        = $r->relationLoaded('targetImpactLevel')      ? $r->targetImpactLevel     : null;
        $tFrequency     = $r->relationLoaded('targetFrequencyLevel')   ? $r->targetFrequencyLevel  : null;
        $tZone          = $r->relationLoaded('targetCriticalityZone')  ? $r->targetCriticalityZone : null;

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
            'causes'                        => $r->causes,
            'consequences'                  => $r->consequences,
            'consequences_autres_processus' => $r->consequences_autres_processus,
            'cout_consequences'             => $r->cout_consequences,
            'controles_existants'           => $r->controles_existants,
            'owner'                         => $r->owner,
            'entite_partenaire_impliquee'   => $r->entite_partenaire_impliquee,
            'outils_utilises'              => $r->outils_utilises,
            'vraisemblance_apparition'      => $r->vraisemblance_apparition,
            'plan_traitement'               => $r->plan_traitement,
            'critere_risque'                => $r->critere_risque,
            // ── Évaluation inhérente ──────────────────────────────────────────
            'impact_level_id'              => $r->impact_level_id,
            'impact_label'                 => $impact?->label,
            'impact_score'                 => $impact?->score,
            'impact_color'                 => $impact?->color_code,
            'frequency_level_id'           => $r->frequency_level_id,
            'frequency_label'              => $frequency?->label,
            'frequency_score'              => $frequency?->score,
            'frequency_color'              => $frequency?->color_code,
            'criticality_score'            => $r->criticality_score,
            'criticality_zone_id'          => $r->criticality_zone_id,
            'zone_label'                   => $zone?->label,
            'zone_color'                   => $zone?->color_code,
            // ── Évaluation résiduelle ─────────────────────────────────────────
            'residual_impact_level_id'     => $r->residual_impact_level_id,
            'residual_impact_label'        => $rImpact?->label,
            'residual_impact_score'        => $rImpact?->score,
            'residual_impact_color'        => $rImpact?->color_code,
            'residual_frequency_level_id'  => $r->residual_frequency_level_id,
            'residual_frequency_label'     => $rFrequency?->label,
            'residual_frequency_score'     => $rFrequency?->score,
            'residual_frequency_color'     => $rFrequency?->color_code,
            'residual_criticality_score'   => $r->residual_criticality_score,
            'residual_criticality_zone_id' => $r->residual_criticality_zone_id,
            'residual_zone_label'          => $rZone?->label,
            'residual_zone_color'          => $rZone?->color_code,
            // ── Évaluation cible ──────────────────────────────────────────────
            'target_impact_level_id'       => $r->target_impact_level_id,
            'target_impact_label'          => $tImpact?->label,
            'target_impact_score'          => $tImpact?->score,
            'target_impact_color'          => $tImpact?->color_code,
            'target_frequency_level_id'    => $r->target_frequency_level_id,
            'target_frequency_label'       => $tFrequency?->label,
            'target_frequency_score'       => $tFrequency?->score,
            'target_frequency_color'       => $tFrequency?->color_code,
            'target_criticality_score'     => $r->target_criticality_score,
            'target_criticality_zone_id'   => $r->target_criticality_zone_id,
            'target_zone_label'            => $tZone?->label,
            'target_zone_color'            => $tZone?->color_code,
            'statut'               => $r->statut,
            'statut_label'         => $r->statut_label,
            'statut_badge'         => $r->statut_badge,
            'risque_realise'       => (bool) $r->risque_realise,
            'cout_risque'          => $r->cout_risque,
            'incident_id'          => $r->incident_id,
            'moved_to_library_at'  => $r->moved_to_library_at?->format('d/m/Y'),
            'created_at'           => $r->created_at?->format('d/m/Y'),
        ];
    }
}
