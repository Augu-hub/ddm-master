<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ANALYSE DES RISQUES CONTROLLER — table : mission_phase_ar
 * ════════════════════════════════════════════════════════════════════════════════
 *
 * STRATÉGIE DE CHARGEMENT DES RISQUES (3 couches) :
 *
 *   COUCHE 1 — table `risks`
 *     Charge UNIQUEMENT les risques dont l'ID figure dans le JSON de l'univers
 *     (audit_universe.risques[].risk_id).
 *     ⚠ On ne filtre PAS par year ni entity_id dans la table risks.
 *
 *   COUCHE 2 — table `audit_universe`
 *     Fournit impact_net, frequency_net, control_procedure, criticality_net,
 *     qualification_net évalués au niveau global.
 *
 *   COUCHE 3 — table `mission_phase_ar` (colonne risques JSON)
 *     Données spécifiques à cette mission. Priorité maximale.
 *
 * RÉSOLUTION mission_id / assignment_id :
 *   1. Query string  (?mission_id=X&assignment_id=Y)
 *   2. Session flash (->with() depuis redirect)
 *   3. Colonnes de la table mission_phase_ar (en mode edit)
 */
class AnalyseRisquesController extends Controller
{
    private const TABLE = 'mission_phase_ar';

    // ─────────────────────────────────────────────────────────────────────────
    // GET index
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            // Résoudre mission_id / assignment_id (query string OU session flash)
            $missionId    = $request->input('mission_id')    ?? session('mission_id');
            $assignmentId = $request->input('assignment_id') ?? session('assignment_id');

            $mission    = $missionId    ? DB::table('missions')    ->where('id', $missionId)   ->first() : null;
            $assignment = $assignmentId ? DB::table('assignments')->where('id', $assignmentId)->first() : null;

            // UNIQUE KEY uq_ar_assignment → rediriger si déjà existant
            if ($assignmentId) {
                $existing = DB::table(self::TABLE)->where('assignment_id', $assignmentId)->first();
                if ($existing) {
                    return redirect()->route('auditor.ac.analyse-risques.edit', $existing->id)
                        ->with('mission_id',    $missionId)
                        ->with('assignment_id', $assignmentId);
                }
            }

            return Inertia::render('dashboards/Auditor/Forms/AnalyseRisques', array_merge(
                $this->sharedProps($mission, $assignment, null),
                ['formUrl' => route('auditor.ac.analyse-risques.store')]
            ));

        } catch (\Exception $e) {
            Log::error('AR index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET edit
    // ─────────────────────────────────────────────────────────────────────────
    public function edit(Request $request, $formId)
    {
        try {
            $form = DB::table(self::TABLE)->where('id', $formId)->firstOrFail();

            // Résoudre mission_id / assignment_id
            // Priorité : query string > session flash > colonnes du form AR
            $missionId  = $request->input('mission_id')    ?? session('mission_id')    ?? $form->mission_id;
            $assignId   = $request->input('assignment_id') ?? session('assignment_id') ?? $form->assignment_id;

            $mission    = $missionId ? DB::table('missions')    ->where('id', $missionId)->first() : null;
            $assignment = $assignId  ? DB::table('assignments')->where('id', $assignId) ->first() : null;

            // Si la mission n'est toujours pas trouvée, essayer via le form AR directement
            if (!$mission && $form->mission_id) {
                $mission   = DB::table('missions')->where('id', $form->mission_id)->first();
                $missionId = $form->mission_id;
            }

            return Inertia::render('dashboards/Auditor/Forms/AnalyseRisques', array_merge(
                $this->sharedProps($mission, $assignment, $form),
                [
                    'formUrl' => route('auditor.ac.analyse-risques.store'),
                    'editUrl' => route('auditor.ac.analyse-risques.update', $formId),
                ]
            ));

        } catch (\Exception $e) {
            Log::error('AR edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function store(Request $request)           { return $this->upsert($request, null); }
    public function update(Request $request, $formId) { return $this->upsert($request, $formId); }

    public function destroy($formId)
    {
        try {
            DB::table(self::TABLE)->where('id', $formId)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function soumettre(Request $request, $formId)
    {
        try {
            DB::table(self::TABLE)->where('id', $formId)->update([
                'validation_status' => 'in_review',
                'submitted_at'      => now(),
                'submitted_by'      => auth()->id(),
                'updated_at'        => now(),
            ]);
            return response()->json(['success' => true, 'status' => 'in_review', 'message' => 'Analyse soumise']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function valider(Request $request, $formId)
    {
        try {
            $action = $request->input('action', 'validated');
            $note   = $request->input('note');
            $update = ['validation_status' => $action, 'updated_at' => now()];
            if ($action === 'validated') {
                $update['validated_at'] = now();
                $update['validated_by'] = auth()->id();
            }
            if ($note) $update['validation_note'] = $note;
            DB::table(self::TABLE)->where('id', $formId)->update($update);
            return response()->json([
                'success' => true,
                'status'  => $action,
                'message' => match ($action) {
                    'validated' => 'Validé ✓',
                    'rejected'  => 'Rejeté',
                    default     => 'Mis à jour',
                },
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    private function sharedProps($mission, $assignment, $form): array
    {
        $missionId    = $mission?->id;
        $assignmentId = $assignment?->id;

        // ── Résoudre entity_id depuis la mission ──────────────────────────────
        // La colonne peut s'appeler entity_id, entite_id, structure_id...
        // On tente les noms les plus courants.
        $entityId = $this->resolveEntityId($mission, $assignment);

        // Log diagnostic complet
        Log::info('AR sharedProps - début', [
            'mission_id'    => $missionId,
            'assignment_id' => $assignmentId,
            'mission_found' => $mission ? 'oui' : 'NON ← PROBLÈME',
            'mission_cols'  => $mission ? array_keys((array) $mission) : [],
            'entity_id'     => $entityId,
        ]);

        // ── 1. Résoudre l'année + charger l'univers ───────────────────────────
        $activeYear  = $this->resolveActiveYear($entityId, $mission);
        $universeRow = $entityId
            ? DB::table('audit_universe')
                ->where('entity_id', $entityId)
                ->where('year', $activeYear)
                ->first()
            : null;

        // Fallback : si pas d'univers pour l'année exacte, prendre le plus récent
        if (!$universeRow && $entityId) {
            $universeRow = DB::table('audit_universe')
                ->where('entity_id', $entityId)
                ->orderByDesc('year')
                ->first();
            if ($universeRow) {
                $activeYear = (int) $universeRow->year;
                Log::info('AR: fallback univers le plus récent', ['year' => $activeYear, 'id' => $universeRow->id]);
            }
        }

        // ── 2. Parser le JSON de l'univers → map (int)risk_id => données ──────
        $universeMap = [];
        if ($universeRow && $universeRow->risques) {
            $decoded = json_decode($universeRow->risques, true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    $rid = (int) ($entry['risk_id'] ?? 0);
                    if ($rid > 0) $universeMap[$rid] = $entry;
                }
            }
        }

        // ── 3. IDs des risques présents dans l'univers ────────────────────────
        $riskIdsInUniverse = array_keys($universeMap);

        // ── 4. Couche 1 — charger les risques depuis la table risks par IDs ───
        $universeRisks = !empty($riskIdsInUniverse)
            ? $this->loadRisksByIds($riskIdsInUniverse)
            : collect();

        // ── 5. Couche 2 — appliquer les évaluations de l'univers ─────────────
        $universeRisks = $universeRisks->map(function ($row) use ($universeMap) {
            $u = $universeMap[(int) $row->id] ?? null;
            if ($u) {
                $row->impact_net        = (isset($u['impact_net'])    && $u['impact_net']    !== null) ? (int) $u['impact_net']    : $row->impact_net;
                $row->frequency_net     = (isset($u['frequency_net']) && $u['frequency_net'] !== null) ? (int) $u['frequency_net'] : $row->frequency_net;
                $row->control_procedure = $u['control_procedure']  ?? $row->control_procedure;
                $row->criticality_net   = $u['criticality_net']    ?? null;
                $row->qualification_net = $u['qualification_net']  ?? null;
                $row->is_evaluated      = (bool) ($u['is_evaluated'] ?? false);
            }
            return $row;
        });

        // ── 6. Couche 3 — données AR sauvegardées ────────────────────────────
        $savedRisks = [];
        if ($form && $form->risques) {
            $decoded = json_decode($form->risques, true);
            if (is_array($decoded)) $savedRisks = $decoded;
        }

        // ── 7. Matrice ────────────────────────────────────────────────────────
        $matrixRaw = DB::table('audit_matrix')
            ->select('id', 'frequency_level', 'impact_level', 'qualification')
            ->whereNull('deleted_at')
            ->get();

        // ── 8. Fusion finale ──────────────────────────────────────────────────
        $risksData = $this->mergeRisksWithSaved($universeRisks, $savedRisks, $matrixRaw->all());

        // ── 9. Référentiels ───────────────────────────────────────────────────
        $allProcesses  = DB::table('processes') ->select('id', 'code', 'name')->orderBy('code')->get();
        $allActivities = DB::table('activities')->select('id', 'process_id', 'code', 'name', 'description')->orderBy('code')->get();

        $assignmentFunctions = $assignmentId
            ? DB::table('assignment_functions')->where('assignment_id', $assignmentId)->select('id', 'character', 'name')->get()
            : collect();

        $impactLevels    = DB::table('risk_impact_levels')   ->select('id', 'level', 'label', 'color')->whereNull('deleted_at')->orderBy('level')->get();
        $frequencyLevels = DB::table('risk_frequency_levels')->select('id', 'level', 'label', 'color')->whereNull('deleted_at')->orderBy('level')->get();
        $riskTypes       = DB::table('risk_types')->select('id', 'code', 'label', 'color')->where('is_active', true)->whereNull('deleted_at')->orderBy('sort_order')->get();

        $arList = $missionId
            ? DB::table(self::TABLE)->where('mission_id', $missionId)->select('id', 'code', 'validation_status')->orderByDesc('created_at')->get()
            : collect();

        $auditorRole    = null;
        $currentAuditor = null;
        if ($assignmentId && auth()->check()) {
            $auditor = DB::table('assignment_auditors')
                ->where('assignment_id', $assignmentId)
                ->where('user_id', auth()->id())
                ->first();
            $auditorRole    = $auditor?->role;
            $currentAuditor = auth()->user();
        }

        Log::info('AR sharedProps - résultat', [
            'entity_id'         => $entityId,
            'activeYear'        => $activeYear,
            'universe_id'       => $universeRow?->id,
            'risk_ids_universe' => $riskIdsInUniverse,
            'risks_loaded'      => $universeRisks->count(),
            'risks_merged'      => count($risksData),
        ]);

        return [
            'mission'             => $mission,
            'assignment'          => $assignment,
            'form'                => $form,
            'arList'              => $arList,
            'risksData'           => $risksData,
            'allProcesses'        => $allProcesses,
            'allActivities'       => $allActivities,
            'assignmentFunctions' => $assignmentFunctions,
            'impactLevels'        => $impactLevels,
            'frequencyLevels'     => $frequencyLevels,
            'riskTypes'           => $riskTypes,
            'matrix'              => $matrixRaw,
            'auditorRole'         => $auditorRole,
            'currentAuditor'      => $currentAuditor,
            'missionId'           => $missionId,
            'assignmentId'        => $assignmentId,
            'riskCount'           => count($risksData),
            'activeYear'          => $activeYear,
            'backUrl'             => url()->previous(),
        ];
    }

    /**
     * Résoudre entity_id depuis plusieurs sources.
     *
     * Priorité :
     *   1. missions (colonnes: entity_id, entite_id, structure_id...)
     *   2. assignments.entity_id
     *   3. mission_risk → risks.entity_id (premier risque lié)
     *   4. audit_universe.entity_id (fallback absolu)
     */
    private function resolveEntityId($mission, $assignment = null): ?int
    {
        // ── 1. Depuis la mission ──────────────────────────────────────────────
        if ($mission) {
            $missionArr = (array) $mission;
            foreach (['entity_id', 'entite_id', 'structure_id', 'org_id', 'organisation_id'] as $col) {
                if (!empty($missionArr[$col])) {
                    Log::info('AR entity_id: depuis missions.' . $col, ['value' => $missionArr[$col]]);
                    return (int) $missionArr[$col];
                }
            }
            Log::warning('AR: entity_id absent dans missions', [
                'mission_id'     => $mission->id ?? null,
                'colonnes_dispo' => array_keys($missionArr),
            ]);
        }

        // ── 2. Depuis l'assignment ────────────────────────────────────────────
        if ($assignment) {
            $assignArr = (array) $assignment;
            foreach (['entity_id', 'entite_id', 'structure_id'] as $col) {
                if (!empty($assignArr[$col])) {
                    Log::info('AR entity_id: depuis assignments.' . $col, ['value' => $assignArr[$col]]);
                    return (int) $assignArr[$col];
                }
            }
        }

        // ── 3. Depuis mission_risk → risks.entity_id ──────────────────────────
        if ($mission?->id) {
            $eid = DB::table('mission_risk as mr')
                ->join('risks as r', 'r.id', '=', 'mr.risk_id')
                ->where('mr.mission_id', $mission->id)
                ->whereNotNull('r.entity_id')
                ->value('r.entity_id');
            if ($eid) {
                Log::info('AR entity_id: depuis mission_risk→risks', ['value' => $eid]);
                return (int) $eid;
            }
        }

        // ── 4. Fallback : dernier univers d'audit toutes entités ──────────────
        $eid = DB::table('audit_universe')->orderByDesc('year')->value('entity_id');
        if ($eid) {
            Log::info('AR entity_id: fallback audit_universe', ['value' => $eid]);
            return (int) $eid;
        }

        Log::error('AR entity_id: INTROUVABLE — aucune source valide');
        return null;
    }

    /**
     * Résoudre l'année active.
     * 1. audit_universe.year MAX pour l'entité
     * 2. mission->year
     * 3. année courante
     */
    private function resolveActiveYear(?int $entityId, $mission): int
    {
        if ($entityId) {
            $latestYear = DB::table('audit_universe')
                ->where('entity_id', $entityId)
                ->orderByDesc('year')
                ->value('year');
            if ($latestYear) return (int) $latestYear;
        }

        // Tenter plusieurs noms de colonne year
        if ($mission) {
            $missionArr = (array) $mission;
            foreach (['year', 'annee', 'exercice', 'fiscal_year'] as $col) {
                if (!empty($missionArr[$col])) return (int) $missionArr[$col];
            }
        }

        return (int) date('Y');
    }

    /**
     * COUCHE 1 — Charge les risques par liste d'IDs.
     * Filtre : whereIn(r.id, $riskIds) UNIQUEMENT.
     *
     * @param int[] $riskIds
     */
    private function loadRisksByIds(array $riskIds): \Illuminate\Support\Collection
    {
        if (empty($riskIds)) return collect();

        return DB::table('risks as r')
            ->leftJoin('risk_frequency_levels as rfl', function ($j) {
                $j->on('rfl.id', '=', 'r.frequency_level_id')->whereNull('rfl.deleted_at');
            })
            ->leftJoin('risk_impact_levels as ril', function ($j) {
                $j->on('ril.id', '=', 'r.impact_level_id')->whereNull('ril.deleted_at');
            })
            ->leftJoin('processes as p',   'p.id',  '=', 'r.process_id')
            ->leftJoin('activities as a',  'a.id',  '=', 'r.activity_id')
            ->leftJoin('risk_types as rt', function ($j) {
                $j->on('rt.id', '=', 'r.risk_type_id')->whereNull('rt.deleted_at');
            })
            ->whereIn('r.id', $riskIds)
            ->select([
                'r.id', 'r.code', 'r.label', 'r.description', 'r.status', 'r.owner',
                'r.criticality', 'r.control_procedure', 'r.impact_net', 'r.frequency_net',
                'r.entity_id', 'r.process_id', 'r.activity_id', 'r.risk_type_id', 'r.year',
                DB::raw('rfl.level AS frequency_level'),
                DB::raw('rfl.label AS frequency_label'),
                DB::raw('rfl.color AS frequency_color'),
                DB::raw('ril.level AS impact_level'),
                DB::raw('ril.label AS impact_label'),
                DB::raw('ril.color AS impact_color'),
                DB::raw('p.code   AS process_code'),
                DB::raw('p.name   AS process_name'),
                DB::raw('a.code   AS activity_code'),
                DB::raw('a.name   AS activity_name'),
                DB::raw('rt.label AS risk_type_label'),
                DB::raw('rt.color AS risk_type_color'),
            ])
            ->orderBy('p.code')
            ->orderBy('r.code')
            ->get()
            ->map(function ($row) {
                $row->frequency_level     = $row->frequency_level  ?? null;
                $row->frequency_label     = $row->frequency_label  ?? '-';
                $row->frequency_color     = $row->frequency_color  ?? 'secondary';
                $row->impact_level        = $row->impact_level     ?? null;
                $row->impact_label        = $row->impact_label     ?? '-';
                $row->impact_color        = $row->impact_color     ?? 'secondary';
                $row->process_code        = $row->process_code     ?? '-';
                $row->process_name        = $row->process_name     ?? '-';
                $row->activity_code       = $row->activity_code    ?? '-';
                $row->activity_name       = $row->activity_name    ?? '-';
                $row->risk_type_label     = $row->risk_type_label  ?? '-';
                $row->risk_type_color     = $row->risk_type_color  ?? 'secondary';
                $row->impact_net          = $row->impact_net       ?? null;
                $row->frequency_net       = $row->frequency_net    ?? null;
                $row->criticality_net     = null;
                $row->qualification_net   = null;
                $row->control_nature_code = null;
                $row->is_evaluated        = false;
                return $row;
            });
    }

    /**
     * Calcul glob résiduel depuis la matrice.
     */
    private function computeGlobResid($impNet, $freqNet, array $matrixMap): ?int
    {
        if ($impNet === null || $freqNet === null) return null;
        $i = (int) $impNet;
        $f = (int) $freqNet;
        if ($i <= 0 || $f <= 0) return null;
        $key = "{$f}_{$i}";
        return isset($matrixMap[$key]) ? (int) $matrixMap[$key] : ($i * $f);
    }

    /**
     * Fusion finale AR > univers > risks.
     */
    private function mergeRisksWithSaved(
        \Illuminate\Support\Collection $universeRisks,
        array $savedRisks,
        array $matrix = []
    ): array {
        $matrixMap = [];
        foreach ($matrix as $m) {
            $matrixMap["{$m->frequency_level}_{$m->impact_level}"] = (int) $m->qualification;
        }

        $savedMap = [];
        foreach ($savedRisks as $s) {
            $rid = (int) ($s['risk_id'] ?? 0);
            if ($rid > 0) $savedMap[$rid] = $s;
        }

        return $universeRisks->map(function ($risk) use ($savedMap, $matrixMap) {
            $s = $savedMap[(int) $risk->id] ?? [];

            $impNet = (isset($s['impact_net']) && $s['impact_net'] !== null)
                ? (int) $s['impact_net']
                : ($risk->impact_net !== null ? (int) $risk->impact_net : null);

            $freqNet = (isset($s['frequency_net']) && $s['frequency_net'] !== null)
                ? (int) $s['frequency_net']
                : ($risk->frequency_net !== null ? (int) $risk->frequency_net : null);

            $globResid = (isset($s['glob_resid']) && $s['glob_resid'] !== null)
                ? (int) $s['glob_resid']
                : $this->computeGlobResid($impNet, $freqNet, $matrixMap);

            return [
                'id'                => $risk->id,
                'code'              => $risk->code,
                'label'             => $risk->label,
                'description'       => $risk->description ?? '',
                'status'            => $risk->status,
                'year'              => $risk->year,
                'process_code'      => $risk->process_code,
                'process_name'      => $risk->process_name,
                'process_id'        => $risk->process_id,
                'activity_code'     => $risk->activity_code,
                'activity_name'     => $risk->activity_name,
                'activity_id'       => $risk->activity_id,
                'risk_type_label'   => $risk->risk_type_label,
                'risk_type_color'   => $risk->risk_type_color,
                'risk_type_id'      => $risk->risk_type_id,
                'impact_level'      => $risk->impact_level,
                'impact_label'      => $risk->impact_label,
                'impact_color'      => $risk->impact_color,
                'frequency_level'   => $risk->frequency_level,
                'frequency_label'   => $risk->frequency_label,
                'frequency_color'   => $risk->frequency_color,
                'criticality'       => $risk->criticality,
                'criticality_net'   => $risk->criticality_net,
                'qualification_net' => $risk->qualification_net,
                'is_evaluated'      => $risk->is_evaluated,
                'control_procedure' => $s['control_procedure'] ?? $risk->control_procedure ?? '',
                'impact_net'        => $impNet,
                'frequency_net'     => $freqNet,
                'glob_resid'        => $globResid,
                'nature'            => $s['nature']            ?? '',
                'qualif_controle'   => $s['qualif_controle']   ?? '',
                'assertions'        => $s['assertions']        ?? '',
                'forces'            => $s['forces']            ?? '',
                'faiblesses'        => $s['faiblesses']        ?? '',
                'objectif_controle' => $s['objectif_controle'] ?? '',
                'choix'             => (bool) ($s['choix']     ?? false),
                '_isNew'            => false,
            ];
        })->values()->all();
    }

    private function upsert(Request $request, $formId)
    {
        try {
            $validated = $request->validate([
                'mission_id'    => 'required|integer',
                'assignment_id' => 'required|integer',
                'risques'       => 'nullable|string',
                'synthese'      => 'nullable|string|max:10000',
                'fait_par'      => 'nullable|string|max:255',
                'revue_par'     => 'nullable|string|max:255',
                'date_analyse'  => 'nullable|date',
            ]);

            $existing = $formId
                ? DB::table(self::TABLE)->where('id', $formId)->first()
                : DB::table(self::TABLE)->where('assignment_id', $validated['assignment_id'])->first();

            $payload = [
                'mission_id'    => $validated['mission_id'],
                'assignment_id' => $validated['assignment_id'],
                'risques'       => $validated['risques']      ?? '[]',
                'carte_risques' => '[]',
                'synthese'      => $validated['synthese']     ?? null,
                'date_analyse'  => $validated['date_analyse'] ?? now()->toDateString(),
                'fait_par'      => $validated['fait_par']     ?? null,
                'revue_par'     => $validated['revue_par']    ?? null,
                'updated_at'    => now(),
            ];

            if ($existing) {
                DB::table(self::TABLE)->where('id', $existing->id)->update($payload);
                $id = $existing->id;
            } else {
                $payload['code']              = $this->generateArCode($validated['mission_id']);
                $payload['validation_status'] = 'draft';
                $payload['created_by']        = auth()->id();
                $payload['created_at']        = now();
                $id = DB::table(self::TABLE)->insertGetId($payload);
            }

            return response()->json([
                'success' => true,
                'form'    => DB::table(self::TABLE)->where('id', $id)->first(),
                'message' => 'Analyse enregistrée',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('AR upsert: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function generateArCode(int $missionId): string
    {
        try {
            $mission = DB::table('missions')->where('id', $missionId)->first();
            $slug    = $mission?->code_mission
                ? strtoupper(preg_replace('/[^A-Z0-9]/i', '', $mission->code_mission))
                : 'M' . $missionId;
            $count = DB::table(self::TABLE)->where('mission_id', $missionId)->count();
            return 'AR-' . substr($slug, 0, 8) . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            return 'AR-' . rand(1000, 9999);
        }
    }
}