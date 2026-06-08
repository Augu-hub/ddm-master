<?php

namespace App\Http\Controllers;

use App\Models\RiskRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RiskAnalysesController extends Controller
{
    private function tenantId(): int
    {
        return (int)(session('tenant_id') ?? 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // INDEX — bibliothèque en vue tableau
    // ═══════════════════════════════════════════════════════════════════════
    public function index(): Response
    {
        $tid = $this->tenantId();

        $risks = RiskRegister::on('tenant')
            ->tenant($tid)
            ->bibliotheque()
            ->with([
                'activity.process.macroProcess',
                'nomenclature',
                'impactLevel',
                'frequencyLevel',
                'criticalityZone',
            ])
            ->orderBy('moved_to_library_at', 'desc')
            ->get()
            ->map(fn($r) => $this->formatRisk($r));

        return Inertia::render('dashboards/Risk/RiskAnalyse/Index', [
            'risks'         => $risks,
            'tree'          => $this->buildTree($risks),
            'stats'         => $this->getStats($tid),
            'nomenclatures' => $this->getNomenclatures(),
            'entities'      => $this->getEntities(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE — remplir les champs d'analyse depuis la bibliothèque
    // ═══════════════════════════════════════════════════════════════════════
    public function update(Request $request, int $id)
    {
        $tid  = $this->tenantId();
        $risk = RiskRegister::on('tenant')
            ->tenant($tid)
            ->bibliotheque()
            ->findOrFail($id);

        $v = $request->validate([
            'causes'                        => 'nullable|string',
            'consequences'                  => 'nullable|string',
            'consequences_autres_processus' => 'nullable|string',
            'cout_consequences'             => 'nullable|string',
            'controles_existants'           => 'nullable|string',
            'entite_partenaire_impliquee'   => 'nullable|string',
            'risque_realise'                => 'nullable|boolean',
            'owner'                         => 'nullable|string|max:255',
            'nomenclature_id'               => 'nullable|integer',
            'plan_traitement'               => 'nullable|string',
        ]);

        $risk->update($v);

        // Retour JSON si requête Ajax (Accept: application/json)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Risque {$risk->code_risk} mis à jour.",
                'risk'    => $this->formatRisk($risk->fresh([
                    'activity.process.macroProcess',
                    'nomenclature',
                    'impactLevel',
                    'frequencyLevel',
                    'criticalityZone',
                ])),
            ]);
        }

        return back()->with('success', "Risque {$risk->code_risk} mis à jour.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REMOVE FROM LIBRARY
    // ═══════════════════════════════════════════════════════════════════════
    public function removeFromLibrary(int $id)
    {
        $tid  = $this->tenantId();
        $risk = RiskRegister::on('tenant')
            ->tenant($tid)
            ->bibliotheque()
            ->findOrFail($id);

        $risk->update(['moved_to_library_at' => null]);

        return back()->with('success', "Risque {$risk->code_risk} retiré de la bibliothèque.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Construit l'arbre macro-processus > processus > activité > risques
     * utilisé par la vue pour le rendu groupé en liste gauche.
     */
    private function buildTree($risks): array
    {
        $tree = [];

        foreach ($risks as $risk) {
            $macroId   = $risk['macro_process_id'] ?? 0;
            $processId = $risk['process_id']        ?? 0;
            $actId     = $risk['activity_id']       ?? 0;

            if (!isset($tree[$macroId])) {
                $tree[$macroId] = [
                    'id'        => $macroId,
                    'code'      => $risk['macro_process_code'] ?? '—',
                    'name'      => $risk['macro_process_name'] ?? 'Sans macro-processus',
                    'kind'      => $risk['macro_process_kind'] ?? null,
                    'processes' => [],
                ];
            }

            if (!isset($tree[$macroId]['processes'][$processId])) {
                $tree[$macroId]['processes'][$processId] = [
                    'id'         => $processId,
                    'code'       => $risk['process_code'] ?? '—',
                    'name'       => $risk['process_name'] ?? 'Sans processus',
                    'activities' => [],
                ];
            }

            if (!isset($tree[$macroId]['processes'][$processId]['activities'][$actId])) {
                $tree[$macroId]['processes'][$processId]['activities'][$actId] = [
                    'id'        => $actId,
                    'code'      => $risk['activity_code'] ?? '—',
                    'name'      => $risk['activity_name'] ?? 'Sans activité',
                    'objective' => $risk['objective']     ?? null,
                    'risks'     => [],
                ];
            }

            $tree[$macroId]['processes'][$processId]['activities'][$actId]['risks'][] = $risk;
        }

        return array_values(array_map(function ($macro) {
            $macro['processes'] = array_values(array_map(function ($process) {
                $process['activities'] = array_values($process['activities']);
                return $process;
            }, $macro['processes']));
            return $macro;
        }, $tree));
    }

    private function getStats(int $tid): array
    {
        $base = RiskRegister::on('tenant')->tenant($tid);
        return [
            'total_registre'     => (clone $base)->registre()->count(),
            'total_bibliotheque' => (clone $base)->bibliotheque()->count(),
            'total_actif'        => (clone $base)->bibliotheque()->actif()->count(),
            'total_draft'        => (clone $base)->bibliotheque()->draft()->count(),
        ];
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

    private function getEntities(): array
    {
        return DB::connection('tenant')
            ->table('entities')
            ->select('id', 'name', 'code_base', 'level', 'parent_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    private function formatRisk(RiskRegister $r): array
    {
        $activity  = $r->relationLoaded('activity')            ? $r->activity            : null;
        $process   = $activity?->relationLoaded('process')     ? $activity->process      : null;
        $macro     = $process?->relationLoaded('macroProcess') ? $process->macroProcess  : null;
        $zone      = $r->relationLoaded('criticalityZone')     ? $r->criticalityZone     : null;
        $nomen     = $r->relationLoaded('nomenclature')        ? $r->nomenclature        : null;
        $impact    = $r->relationLoaded('impactLevel')         ? $r->impactLevel         : null;
        $frequency = $r->relationLoaded('frequencyLevel')      ? $r->frequencyLevel      : null;

        // Objectif : issu du macro-processus (ou processus selon ton modèle)
        $objective = $macro?->objective ?? $process?->objective ?? null;

        return [
            // Identifiants
            'id'                            => $r->id,
            'code_risk'                     => $r->code_risk,
            'libelle'                       => $r->libelle,
            'description'                   => $r->description,

            // Relations arbre
            'entity_id'                     => $r->entity_id,
            'activity_id'                   => $r->activity_id,
            'process_id'                    => $activity?->process_id,
            'macro_process_id'              => $process?->macro_process_id,
            'activity_code'                 => $activity?->code,
            'activity_name'                 => $activity?->name,
            'process_code'                  => $process?->code,
            'process_name'                  => $process?->name,
            'macro_process_code'            => $macro?->code,
            'macro_process_name'            => $macro?->name,
            'macro_process_kind'            => $macro?->kind,

            // Objectif (affiché dans la colonne "Objectifs" du tableau)
            'objective'                     => $objective,

            // Nomenclature
            'nomenclature_id'               => $r->nomenclature_id,
            'nomenclature_label'            => $nomen?->label,

            // Champs d'analyse — colonnes du tableau Excel
            'causes'                        => $r->causes,
            'consequences'                  => $r->consequences,
            'consequences_autres_processus' => $r->consequences_autres_processus,
            'cout_consequences'             => $r->cout_consequences,
            'controles_existants'           => $r->controles_existants,
            'entite_partenaire_impliquee'   => $r->entite_partenaire_impliquee,
            'owner'                         => $r->owner,
            'plan_traitement'               => $r->plan_traitement,
            'risque_realise'                => (bool)$r->risque_realise,

            // Évaluation criticité
            'impact_label'                  => $impact?->label,
            'impact_score'                  => $impact?->score,
            'frequency_label'               => $frequency?->label,
            'frequency_score'               => $frequency?->score,
            'criticality_score'             => $r->criticality_score,
            'zone_label'                    => $zone?->label,
            'zone_color'                    => $zone?->color_code,

            // Statut
            'statut'                        => $r->statut,
            'statut_label'                  => $r->statut_label,
            'statut_badge'                  => $r->statut_badge,
            'moved_to_library_at'           => $r->moved_to_library_at?->format('d/m/Y'),
            'created_at'                    => $r->created_at?->format('d/m/Y'),
        ];
    }
}