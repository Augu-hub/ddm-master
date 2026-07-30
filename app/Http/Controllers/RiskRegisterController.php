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
    private function tenantId(): int
    {
        return (int)(session('tenant_id') ?? 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // INDEX
    // ═══════════════════════════════════════════════════════════════════════
    public function index(): Response
    {
        $tid  = $this->tenantId();
        $user = auth()->user();

        // Risques du registre : hors supprimés ET hors bibliothèque
        $risks = RiskRegister::on('tenant')
            ->tenant($tid)
            ->with($this->baseRelations())
            ->whereNull('deleted_at')
            ->whereNull('moved_to_library_at')
            ->orderBy('activity_id')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($r) => $this->formatRisk($r));

        // IDs des activités assignées à l'utilisateur connecté
        // Un utilisateur appartient à une entité (users.entity_id)
        // On récupère les activités assignées à son entité
        $userActivityIds = $this->getUserActivityIds($user);

        return Inertia::render('dashboards/Risk/RiskRegister/index', [
            'risks'            => $risks,
            'stats'            => $this->getStats($tid),
            'entities'         => $this->getEntities(),
            'nomenclatures'    => $this->getNomenclatures(),
            'libraryIncidents' => $this->getLibraryIncidents($tid),
            'userActivityIds'  => $userActivityIds,
            'isRiskAdmin'      => $this->isRiskAdmin($user),
            'currentUser'      => [
                'id'        => $user?->id,
                'name'      => $user?->name,
                'entity_id' => $user?->entity_id,
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CREATE / EDIT
    // ═══════════════════════════════════════════════════════════════════════
    public function create(): Response
    {
        $tid          = $this->tenantId();
        $fromIncident = session()->pull('risk_from_incident', null);

        return Inertia::render('dashboards/Risk/RiskRegister/form', [
            'risk'          => null,
            'entities'      => $this->getEntities(),
            'nomenclatures' => $this->getNomenclatures(),
            'fromIncident'  => $fromIncident,
        ]);
    }

    public function edit(int $id): Response
    {
        $tid  = $this->tenantId();
        $risk = RiskRegister::on('tenant')
            ->tenant($tid)->with($this->baseRelations())->findOrFail($id);

        return Inertia::render('dashboards/Risk/RiskRegister/form', [
            'risk'          => $this->formatRisk($risk),
            'entities'      => $this->getEntities(),
            'nomenclatures' => $this->getNomenclatures(),
            'fromIncident'  => null,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STORE
    // ═══════════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $tid = $this->tenantId();
        $v   = $request->validate($this->rules());

        $factorId = $v['factor_id'] ?? null;

        // Générer le code : [PROC_CODE]-[ACT_CODE]-R01
        $code = $this->generateRiskCode($tid, (int)$v['activity_id'], $factorId);

        $risk = RiskRegister::on('tenant')->create([
            'tenant_id'                     => $tid,
            'code_risk'                     => $code,
            'libelle'                       => $v['libelle'],
            'description'                   => $v['description']                   ?? null,
            'entity_id'                     => $v['entity_id'],
            'activity_id'                   => $v['activity_id'],
            'factor_id'                     => $factorId,
            'nomenclature_id'               => $v['nomenclature_id'] ?? $this->getDefaultNomenclatureId($tid),
            'causes'                        => $v['causes']                        ?? null,
            'consequences'                  => $v['consequences']                  ?? null,
            'consequences_autres_processus' => $v['consequences_autres_processus'] ?? null,
            'cout_consequences'             => $v['cout_consequences']             ?? null,
            'controles_existants'           => $v['controles_existants']           ?? null,
            'owner'                         => $v['owner']                         ?? null,
            'entite_partenaire_impliquee'   => $v['entite_partenaire_impliquee']   ?? null,
            'outils_utilises'               => $v['outils_utilises']               ?? null,
            'vraisemblance_apparition'      => $v['vraisemblance_apparition']      ?? null,
            'plan_traitement'               => $v['plan_traitement']               ?? null,
            'critere_risque'                => $v['critere_risque']                ?? null,
            'statut'                        => $v['statut']                        ?? 'draft',
            'risque_realise'                => $v['risque_realise']                ?? false,
            'cout_risque'                   => $v['cout_risque']                   ?? null,
            'incident_id'                   => $v['incident_id']                   ?? null,
            'created_by'                    => auth()->id(),
        ]);

        if ($risk->incident_id) {
            DB::connection('tenant')->table('risk_incidents')
                ->where('id', $risk->incident_id)
                ->update(['risk_id' => $risk->id]);
        }

        return redirect()
            ->route('risk.core.risks.index')
            ->with('success', "Risque {$risk->code_risk} créé.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE
    // ═══════════════════════════════════════════════════════════════════════
    public function update(Request $request, int $id)
    {
        $tid  = $this->tenantId();
        $v    = $request->validate($this->rules());
        $risk = RiskRegister::on('tenant')->tenant($tid)->findOrFail($id);

        $risk->update([
            'libelle'                       => $v['libelle'],
            'description'                   => $v['description']                   ?? null,
            'entity_id'                     => $v['entity_id'],
            'activity_id'                   => $v['activity_id'],
            'factor_id'                     => $v['factor_id']                     ?? $risk->factor_id,
            'nomenclature_id'               => $v['nomenclature_id'] ?? $this->getDefaultNomenclatureId($tid),
            'causes'                        => $v['causes']                        ?? null,
            'consequences'                  => $v['consequences']                  ?? null,
            'consequences_autres_processus' => $v['consequences_autres_processus'] ?? null,
            'cout_consequences'             => $v['cout_consequences']             ?? null,
            'controles_existants'           => $v['controles_existants']           ?? null,
            'owner'                         => $v['owner']                         ?? null,
            'entite_partenaire_impliquee'   => $v['entite_partenaire_impliquee']   ?? null,
            'outils_utilises'               => $v['outils_utilises']               ?? null,
            'vraisemblance_apparition'      => $v['vraisemblance_apparition']      ?? null,
            'plan_traitement'               => $v['plan_traitement']               ?? null,
            'critere_risque'                => $v['critere_risque']                ?? null,
            'statut'                        => $v['statut']                        ?? $risk->statut,
            'risque_realise'                => $v['risque_realise']                ?? $risk->risque_realise,
            'cout_risque'                   => $v['cout_risque']                   ?? null,
        ]);

        return redirect()
            ->route('risk.core.risks.index')
            ->with('success', "Risque {$risk->code_risk} mis à jour.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DESTROY / ARCHIVE / ACTIVATE / LIBRARY
    // ═══════════════════════════════════════════════════════════════════════
    public function destroy(int $id)
    {
        RiskRegister::on('tenant')->tenant($this->tenantId())->findOrFail($id)->delete();
        return back()->with('success', 'Risque supprimé.');
    }

    public function archive(int $id)
    {
        $risk = RiskRegister::on('tenant')->tenant($this->tenantId())->findOrFail($id);
        $risk->update(['statut' => 'archive']);
        return back()->with('success', "Risque {$risk->code_risk} archivé.");
    }

    public function activate(int $id)
    {
        $risk = RiskRegister::on('tenant')->tenant($this->tenantId())->findOrFail($id);
        $risk->update(['statut' => 'actif']);
        return back()->with('success', "Risque {$risk->code_risk} activé.");
    }

    public function moveToLibrary(int $id)
    {
        $risk = RiskRegister::on('tenant')->tenant($this->tenantId())->findOrFail($id);
        if ($risk->moved_to_library_at) return back()->with('info', 'Déjà en bibliothèque.');
        $risk->update(['moved_to_library_at' => now()]);
        return back()->with('success', "Risque {$risk->code_risk} transféré en bibliothèque.");
    }

    public function removeFromLibrary(int $id)
    {
        $risk = RiskRegister::on('tenant')->tenant($this->tenantId())->findOrFail($id);
        $risk->update(['moved_to_library_at' => null]);
        return back()->with('success', "Risque {$risk->code_risk} retiré de la bibliothèque.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API : arbre entité — TOUS les processus/activités de l'entité
    // ═══════════════════════════════════════════════════════════════════════
    public function entityTree(int $entityId): JsonResponse
    {
        // Toutes les activités liées à l'entité via assignments
        $rows = DB::connection('tenant')
            ->table('activities as a')
            ->join('assignments as asn', function ($j) use ($entityId) {
                $j->on('asn.mpa_id', '=', 'a.id')
                    ->where('asn.mpa_type', '=', 'activity')
                    ->where('asn.entity_id', '=', $entityId);
            })
            ->join('processes as p',        'p.id',  '=', 'a.process_id')
            ->join('macro_processes as mp',  'mp.id', '=', 'p.macro_process_id')
            ->select(
                'mp.id as macro_id', 'mp.code as macro_code',
                'mp.name as macro_name', 'mp.kind as macro_kind',
                'p.id as process_id',   'p.code as process_code', 'p.name as process_name',
                'a.id as activity_id',  'a.code as activity_code', 'a.name as activity_name'
            )
            ->orderBy('mp.code')->orderBy('p.code')->orderBy('a.code')
            ->get();

        $tree = [];
        foreach ($rows as $row) {
            if (!isset($tree[$row->macro_id])) {
                $tree[$row->macro_id] = [
                    'id' => $row->macro_id, 'code' => $row->macro_code,
                    'name' => $row->macro_name, 'kind' => $row->macro_kind,
                    'processes' => [],
                ];
            }
            if (!isset($tree[$row->macro_id]['processes'][$row->process_id])) {
                $tree[$row->macro_id]['processes'][$row->process_id] = [
                    'id' => $row->process_id, 'code' => $row->process_code,
                    'name' => $row->process_name, 'activities' => [],
                ];
            }
            $tree[$row->macro_id]['processes'][$row->process_id]['activities'][] = [
                'id'   => $row->activity_id,
                'code' => $row->activity_code,
                'name' => $row->activity_name,
            ];
        }

        return response()->json(
            array_values(array_map(function ($macro) {
                $macro['processes'] = array_values($macro['processes']);
                return $macro;
            }, $tree))
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API : charger les facteurs pour plusieurs activités
    // POST /risks/factors-for-activities
    // ═══════════════════════════════════════════════════════════════════════
    public function factorsForActivities(Request $request): JsonResponse
    {
        $tid    = $this->tenantId();
        $actIds = $request->input('activity_ids', []);

        $rows = DB::connection('tenant')
            ->table('risk_factors')
            ->where('tenant_id', $tid)
            ->whereIn('activity_id', $actIds)
            ->whereNull('deleted_at')
            ->orderBy('activity_id')
            ->orderBy('sort_order')
            ->get(['id', 'activity_id', 'code', 'label', 'description', 'sort_order', 'is_ia']);

        $grouped = [];
        foreach ($rows as $row) {
            // Un seul facteur par activité — on prend le premier (sort_order ASC)
            if (!isset($grouped[$row->activity_id])) {
                $grouped[$row->activity_id] = [
                    'id'          => $row->id,
                    'activity_id' => $row->activity_id,   // essentiel pour le filtrage Vue
                    'code'        => $row->code,
                    'label'       => $row->label,
                    'description' => $row->description,
                    'sort_order'  => $row->sort_order,
                    'is_ia'       => (bool)$row->is_ia,
                ];
            }
        }

        return response()->json($grouped);
        // Retourne { activityId: { id, code, label, ... } } — null si pas de facteur
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API : créer/remplacer le facteur d'une activité
    // POST /risks/factors
    // ═══════════════════════════════════════════════════════════════════════
    public function storeFactor(Request $request): JsonResponse
    {
        $tid = $this->tenantId();
        $v   = $request->validate([
            'activity_id' => 'required|integer',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_ia'       => 'nullable|boolean',
        ]);

        $actId = (int)$v['activity_id'];

        // Soft-delete l'ancien facteur si existant (1 seul facteur par activité)
        DB::connection('tenant')->table('risk_factors')
            ->where('tenant_id', $tid)
            ->where('activity_id', $actId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // Code facteur : F01-[ACT_CODE]
        $actCode = strtoupper(
            DB::connection('tenant')->table('activities')->where('id', $actId)->value('code') ?? 'A00'
        );
        $code = 'F01-' . $actCode;

        $id = DB::connection('tenant')->table('risk_factors')->insertGetId([
            'tenant_id'   => $tid,
            'activity_id' => $actId,
            'code'        => $code,
            'label'       => $v['label'],
            'description' => $v['description'] ?? null,
            'sort_order'  => 1,
            'is_ia'       => (int)($v['is_ia'] ?? 0),
            'created_by'  => auth()->id(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json([
            'factor' => [
                'id'          => $id,
                'activity_id' => $actId,   // essentiel pour le filtrage Vue
                'code'        => $code,
                'label'       => $v['label'],
                'description' => $v['description'] ?? null,
                'sort_order'  => 1,
                'is_ia'       => (bool)($v['is_ia'] ?? false),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API : bibliothèque
    // ═══════════════════════════════════════════════════════════════════════
    public function libraryItems(): JsonResponse
    {
        $tid = $this->tenantId();
        $risks = RiskRegister::on('tenant')->tenant($tid)->bibliotheque()
            ->with(['nomenclature', 'activity.process.macroProcess'])
            ->orderByDesc('moved_to_library_at')->get()
            ->map(fn($r) => ['_type' => 'risk', 'id' => $r->id,
                'code' => $r->code_risk, 'libelle' => $r->libelle]);

        $incidents = RiskIncident::on('tenant')->tenant($tid)->bibliotheque()
            ->orderByDesc('moved_to_library_at')->get()
            ->map(fn($i) => ['_type' => 'incident', 'id' => $i->id,
                'code' => $i->code_incident, 'libelle' => $i->libelle,
                'description' => $i->description]);

        return response()->json(['risks' => $risks, 'incidents' => $incidents]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE
    // ═══════════════════════════════════════════════════════════════════════

    private function generateRiskCode(int $tid, int $activityId, ?int $factorId): string
    {
        $row = DB::connection('tenant')
            ->table('activities as a')
            ->join('processes as p', 'p.id', '=', 'a.process_id')
            ->where('a.id', $activityId)
            ->select('p.code as p_code', 'a.code as a_code')
            ->first();

        $pCode = strtoupper($row?->p_code ?? 'P00');
        $aCode = strtoupper($row?->a_code  ?? 'A00');

        // Extraire le préfixe unique de l'activité :
        // Cas normal   : A01P03R → retirer P03R en fin → A01
        // Cas exception: A01P03S avec process P01S → P01S absent → matcher A01 au début
        $aPrefix = preg_replace('/' . preg_quote($pCode, '/') . '$/', '', $aCode);

        if ($aPrefix === $aCode) {
            // Le code process n'est pas à la fin du code activité
            // → extraire le segment Axx au début (ex: A01P03S → A01)
            if (preg_match('/^(A\d+)/', $aCode, $m)) {
                $aPrefix = $m[1];
            } else {
                $aPrefix = substr($aCode, 0, 3);
            }
        }

        // Préfixe final : P03RA01 | P02RA03 | P01SA01
        $prefix = $pCode . $aPrefix;

        // Numéro séquentiel unique par activité
        $seq = DB::connection('tenant')->table('risk_register')
            ->where('tenant_id', $tid)
            ->where('activity_id', $activityId)
            ->whereNull('deleted_at')
            ->count() + 1;

        $candidate = $prefix . '-R' . str_pad($seq, 2, '0', STR_PAD_LEFT);

        while (DB::connection('tenant')->table('risk_register')
            ->where('tenant_id', $tid)->where('code_risk', $candidate)->exists()) {
            $seq++;
            $candidate = $prefix . '-R' . str_pad($seq, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    private function isRiskAdmin($user): bool
    {
        if (!$user) return false;
        // is_risk_admin dans function_assignments
        return DB::connection('tenant')
            ->table('function_assignments')
            ->where('user_id', $user->id)
            ->where('is_risk_admin', 1)
            ->exists();
    }

    private function getUserActivityIds($user): array
    {
        if (!$user) return [];

        // Si l'utilisateur a une entité directe → toutes les activités de son entité
        if ($user->entity_id) {
            return DB::connection('tenant')
                ->table('assignments')
                ->where('entity_id', $user->entity_id)
                ->where('mpa_type', 'activity')
                ->pluck('mpa_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        }

        // Sinon chercher via function_assignments (user_id)
        $entityIds = DB::connection('tenant')
            ->table('function_assignments')
            ->where('user_id', $user->id)
            ->pluck('entity_id')
            ->toArray();

        if (empty($entityIds)) return [];

        return DB::connection('tenant')
            ->table('assignments')
            ->whereIn('entity_id', $entityIds)
            ->where('mpa_type', 'activity')
            ->pluck('mpa_id')
            ->map(fn($id) => (int)$id)
            ->unique()
            ->toArray();
    }

    private function getDefaultNomenclatureId(int $tid): int
    {
        // Retourne le premier nomenclature disponible pour éviter la contrainte NOT NULL
        $id = DB::connection('tenant')
            ->table('risk_nomenclatures')
            ->orderBy('id')
            ->value('id');
        return (int)($id ?? 1);
    }

    private function getLibraryIncidents(int $tid): array
    {
        return RiskIncident::on('tenant')->tenant($tid)->bibliotheque()
            ->orderByDesc('moved_to_library_at')
            ->get(['id', 'code_incident', 'libelle', 'description', 'source'])
            ->map(fn($i) => [
                'id'            => $i->id,
                'code_incident' => $i->code_incident,
                'libelle'       => $i->libelle,
                'description'   => $i->description,
                'source'        => $i->source,
            ])->toArray();
    }

    private function getStats(int $tid): array
    {
        $base = RiskRegister::on('tenant')->tenant($tid);
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
        return DB::connection('tenant')->table('entities')
            ->select('id', 'name', 'code_base', 'level', 'parent_id')
            ->orderBy('level')->orderBy('name')->get()->toArray();
    }

    private function getNomenclatures(): array
    {
        return DB::connection('tenant')->table('risk_nomenclatures')
            ->select('id', 'label', 'parent_id', 'level')
            ->orderBy('label')->get()->toArray();
    }

    private function baseRelations(): array
    {
        return [
            'activity.process.macroProcess',
            'nomenclature',
            'impactLevel', 'frequencyLevel', 'criticalityZone',
            'residualImpactLevel', 'residualFrequencyLevel', 'residualCriticalityZone',
            'targetImpactLevel',   'targetFrequencyLevel',   'targetCriticalityZone',
        ];
    }

    private function rules(): array
    {
        return [
            'libelle'                       => 'required|string|max:255',
            'description'                   => 'nullable|string',
            'entity_id'                     => 'required|integer',
            'activity_id'                   => 'required|integer',
            'factor_id'                     => 'nullable|integer',
            'nomenclature_id'               => 'nullable|integer|exists:risk_nomenclatures,id',
            'causes'                        => 'nullable|string',
            'consequences'                  => 'nullable|string',
            'consequences_autres_processus' => 'nullable|string',
            'cout_consequences'             => 'nullable|string',
            'controles_existants'           => 'nullable|string',
            'owner'                         => 'nullable|string|max:255',
            'entite_partenaire_impliquee'   => 'nullable|string',
            'outils_utilises'               => 'nullable|string',
            'vraisemblance_apparition'      => 'nullable|string|max:255',
            'plan_traitement'               => 'nullable|string',
            'critere_risque'                => 'nullable|string',
            'statut'                        => 'nullable|in:draft,actif,archive',
            'risque_realise'                => 'nullable|boolean',
            'cout_risque'                   => 'nullable|numeric|min:0',
            'incident_id'                   => 'nullable|integer',
        ];
    }

    private function formatRisk(RiskRegister $r): array
    {
        $activity  = $r->relationLoaded('activity')            ? $r->activity            : null;
        $process   = $activity?->relationLoaded('process')     ? $activity->process      : null;
        $macro     = $process?->relationLoaded('macroProcess') ? $process->macroProcess  : null;
        $nomen     = $r->relationLoaded('nomenclature')        ? $r->nomenclature        : null;
        $impact    = $r->relationLoaded('impactLevel')         ? $r->impactLevel         : null;
        $frequency = $r->relationLoaded('frequencyLevel')      ? $r->frequencyLevel      : null;
        $zone      = $r->relationLoaded('criticalityZone')     ? $r->criticalityZone     : null;

        return [
            'id'                   => $r->id,
            'code_risk'            => $r->code_risk,
            'libelle'              => $r->libelle,
            'description'          => $r->description,
            'entity_id'            => $r->entity_id,
            'activity_id'          => $r->activity_id,
            'factor_id'            => $r->factor_id,
            'process_id'           => $activity?->process_id,
            'activity_code'        => $activity?->code,
            'activity_name'        => $activity?->name,
            'process_code'         => $process?->code,
            'process_name'         => $process?->name,
            'macro_process_name'   => $macro?->name,
            'macro_process_kind'   => $macro?->kind,
            'nomenclature_id'      => $r->nomenclature_id,
            'nomenclature_label'   => $nomen?->label,
            'causes'                        => $r->causes,
            'consequences'                  => $r->consequences,
            'consequences_autres_processus' => $r->consequences_autres_processus,
            'entite_partenaire_impliquee'   => $r->entite_partenaire_impliquee,
            'controles_existants'           => $r->controles_existants,
            'plan_traitement'               => $r->plan_traitement,
            'owner'                         => $r->owner,
            'impact_label'         => $impact?->label,
            'frequency_label'      => $frequency?->label,
            'criticality_score'    => $r->criticality_score,
            'zone_label'           => $zone?->label,
            'zone_color'           => $zone?->color_code,
            'statut'               => $r->statut,
            'statut_label'         => $r->statut_label,
            'statut_badge'         => $r->statut_badge,
            'incident_id'          => $r->incident_id,
            'moved_to_library_at'  => $r->moved_to_library_at?->format('d/m/Y'),
            'created_at'           => $r->created_at?->format('d/m/Y'),
        ];
    }
}