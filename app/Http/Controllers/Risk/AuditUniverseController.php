<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Audit\Risk;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════════
 * 📋 AUDIT UNIVERSE CONTROLLER — table : audit_universe
 * ════════════════════════════════════════════════════════════════════════════════════
 *
 * Univers d'audit GLOBAL (pas lié à une mission).
 * Identifié par (entity_id + year).
 * UNIQUE KEY uq_universe_entity_year (entity_id, year)
 *
 * ROUTES À ENREGISTRER :
 *   GET  /audit/universe                            → index
 *   POST /api/audit-universe/load-risks             → loadRisks      (entity_id + year)
 *   POST /api/audit-universe/save                   → save
 *   GET  /api/audit-universe/saved                  → getSaved       (?entity_id=&year=)
 *   POST /api/audit-universe/create-risk            → createRisk
 *   PUT  /api/audit-universe/update-risk/{id}       → updateRiskField
 */
class AuditUniverseController extends Controller
{
    private const TABLE = 'audit_universe';

    // ─────────────────────────────────────────────────────────────────────────
    // GET  /audit/universe  — page Inertia
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $entities = DB::table('entities')
                ->select('id', 'code_base', 'name')
                ->orderBy('name')->get();

            $riskTypes = DB::table('risk_types')
                ->select('id', 'code', 'label', 'color')
                ->where('is_active', true)->whereNull('deleted_at')
                ->orderBy('sort_order')->get();

            $frequencies = DB::table('risk_frequency_levels')
                ->select('id', 'level', 'label', 'color')
                ->whereNull('deleted_at')->orderBy('level')->get();

            $impacts = DB::table('risk_impact_levels')
                ->select('id', 'level', 'label', 'color')
                ->whereNull('deleted_at')->orderBy('level')->get();

            $matrix = DB::table('audit_matrix')
                ->select('id', 'frequency_level', 'impact_level', 'qualification')
                ->whereNull('deleted_at')->get();

            $processes = DB::table('processes')
                ->select('id', 'code', 'name')->orderBy('code')->get();

            $activities = DB::table('activities')
                ->select('id', 'code', 'name')->orderBy('code')->get();

            $years = array_reverse(range(date('Y') - 4, date('Y')));

            // URLs passées au front pour correspondre exactement aux routes Laravel
            $urls = [
                'loadRisks'  => route('audit.core.api.audit-universe.load-risks'),
                'save'       => route('audit.core.api.audit-universe.save'),
                'saved'      => route('audit.core.api.audit-universe.saved'),
                'createRisk' => route('audit.core.api.audit-universe.create-risk'),
            ];

            return Inertia::render('dashboards/Audit/universe', [
                'entities'    => $entities,
                'processes'   => $processes,
                'activities'  => $activities,
                'riskTypes'   => $riskTypes,
                'frequencies' => $frequencies,
                'impacts'     => $impacts,
                'matrix'      => $matrix,
                'years'       => $years,
                'urls'        => $urls,
            ]);

        } catch (\Exception $e) {
            Log::error('Universe index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/audit-universe/load-risks
    // Charge les risques filtrés par entity_id + year (1 requête JOIN)
    // puis fusionne avec audit_universe sauvegardé
    // ─────────────────────────────────────────────────────────────────────────
    public function loadRisks(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_id' => 'required|integer|exists:entities,id',
                'year'      => 'required|integer|min:2000|max:2100',
            ]);

            // 1. Risques de la table risks filtrés par entity_id ET year
            $risks = $this->fetchRisksFromDb($validated['entity_id'], $validated['year']);

            // 2. Fusionner avec les évaluations sauvegardées dans audit_universe
            $saved = DB::table(self::TABLE)
                ->where('entity_id', $validated['entity_id'])
                ->where('year', $validated['year'])
                ->first();

            $universeId = null;
            if ($saved) {
                $universeId = $saved->id;
                $savedMap   = collect(json_decode($saved->risques ?? '[]', true))
                    ->keyBy('risk_id')->all();

                $risks = $risks->map(function ($risk) use ($savedMap) {
                    $s = $savedMap[$risk->id] ?? null;
                    if ($s) {
                        $risk->impact_net           = $s['impact_net']           ?? $risk->impact_net;
                        $risk->frequency_net        = $s['frequency_net']        ?? $risk->frequency_net;
                        $risk->criticality_net      = $s['criticality_net']      ?? null;
                        $risk->qualification_net    = $s['qualification_net']    ?? null;
                        $risk->control_procedure    = $s['control_procedure']    ?? $risk->control_procedure;
                        $risk->control_nature_code  = $s['control_nature_code']  ?? null;
                        $risk->is_evaluated         = (bool)($s['is_evaluated']  ?? false);
                    }
                    return $risk;
                });
            }

            return response()->json([
                'success'      => true,
                'risks'        => $risks->values(),
                'colors'       => $this->getColorPalette(),
                'universe_id'  => $universeId,
                'synthese'     => $saved?->synthese   ?? '',
                'fait_par'     => $saved?->fait_par   ?? '',
                'date_analyse' => $saved?->date_analyse ?? '',
            ]);

        } catch (\Exception $e) {
            Log::error('loadRisks: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/audit-universe/saved?entity_id=X&year=Y
    // Charger l'univers sauvegardé (pour pré-remplir les champs meta)
    // ─────────────────────────────────────────────────────────────────────────
    public function getSaved(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_id' => 'required|integer',
                'year'      => 'required|integer',
            ]);

            $record = DB::table(self::TABLE)
                ->where('entity_id', $validated['entity_id'])
                ->where('year',      $validated['year'])
                ->first();

            if ($record) {
                $record->risques_parsed = json_decode($record->risques ?? '[]', true);
            }

            return response()->json(['success' => true, 'record' => $record]);
        } catch (\Exception $e) {
            Log::error('getSaved: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/audit-universe/save
    // Upsert sur (entity_id, year) — sans mission_id
    // ─────────────────────────────────────────────────────────────────────────
    public function save(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_id'    => 'required|integer|exists:entities,id',
                'year'         => 'required|integer|min:2000|max:2100',
                'risques'      => 'nullable|string',
                'synthese'     => 'nullable|string|max:10000',
                'fait_par'     => 'nullable|string|max:255',
                'date_analyse' => 'nullable|date',
            ]);

            $existing = DB::table(self::TABLE)
                ->where('entity_id', $validated['entity_id'])
                ->where('year',      $validated['year'])
                ->first();

            $payload = [
                'entity_id'    => $validated['entity_id'],
                'year'         => $validated['year'],
                'risques'      => $validated['risques']      ?? '[]',
                'synthese'     => $validated['synthese']     ?? null,
                'fait_par'     => $validated['fait_par']     ?? null,
                'date_analyse' => $validated['date_analyse'] ?? now()->toDateString(),
                'updated_at'   => now(),
            ];

            if ($existing) {
                DB::table(self::TABLE)->where('id', $existing->id)->update($payload);
                $id = $existing->id;
                $msg = 'Univers mis à jour';
            } else {
                $payload['created_by'] = auth()->id();
                $payload['created_at'] = now();
                $id  = DB::table(self::TABLE)->insertGetId($payload);
                $msg = 'Univers créé';
            }

            $record = DB::table(self::TABLE)->where('id', $id)->first();

            return response()->json(['success' => true, 'record' => $record, 'message' => $msg]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Universe save: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/audit-universe/update-risk/{id}
    // Mettre à jour un champ d'un risque dans la table risks
    // ─────────────────────────────────────────────────────────────────────────
    public function updateRiskField(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'field' => 'required|string|in:impact_net,frequency_net,is_evaluated,control_procedure',
                'value' => 'nullable',
            ]);

            $risk = Risk::findOrFail($id);
            $risk->update([$validated['field'] => $validated['value'], 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Mis à jour', 'risk' => $risk]);
        } catch (\Exception $e) {
            Log::error('updateRiskField: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/audit-universe/create-risk
    // Créer un nouveau risque dans la table risks (avec entity + year)
    // ─────────────────────────────────────────────────────────────────────────
    public function createRisk(Request $request)
    {
        try {
            $entityId = $request->input('entity_id') ?? session('audit_entity_id');
            $year     = $request->input('year')      ?? session('audit_year');

            if (!$entityId || !$year) {
                return response()->json(['success' => false, 'error' => 'entity_id et year requis'], 400);
            }

            $validated = $request->validate([
                'label'              => 'required|string|max:500',
                'description'        => 'nullable|string|max:2000',
                'risk_type_id'       => 'nullable|integer|exists:risk_types,id',
                'frequency_level_id' => 'nullable|integer|exists:risk_frequency_levels,id',
                'impact_level_id'    => 'nullable|integer|exists:risk_impact_levels,id',
                'activity_id'        => 'nullable|integer|exists:activities,id',
                'process_id'         => 'nullable|integer|exists:processes,id',
                'control_procedure'  => 'nullable|string|max:5000',
            ]);

            // Criticité brute
            $criticality = null;
            if (!empty($validated['frequency_level_id']) && !empty($validated['impact_level_id'])) {
                $freq   = DB::table('risk_frequency_levels')->where('id', $validated['frequency_level_id'])->whereNull('deleted_at')->first();
                $impact = DB::table('risk_impact_levels')  ->where('id', $validated['impact_level_id'])->whereNull('deleted_at')->first();
                if ($freq && $impact) $criticality = $freq->level * $impact->level;
            }

            $risk = Risk::create([
                'code'               => $this->generateRiskCode($validated['risk_type_id'] ?? null),
                'label'              => $validated['label'],
                'description'        => $validated['description']        ?? null,
                'risk_type_id'       => $validated['risk_type_id']       ?? null,
                'frequency_level_id' => $validated['frequency_level_id'] ?? null,
                'impact_level_id'    => $validated['impact_level_id']    ?? null,
                'criticality'        => $criticality,
                'entity_id'          => $entityId,
                'process_id'         => $validated['process_id']         ?? null,
                'activity_id'        => $validated['activity_id']        ?? null,
                'control_procedure'  => $validated['control_procedure']  ?? null,
                'status'             => 'identified',
                'year'               => $year,
                'created_by'         => auth()->id(),
                'tenant_id'          => tenant('id') ?? 1,
            ]);

            Log::info('Risk created', ['id' => $risk->id, 'code' => $risk->code]);

            // Retourner le risque enrichi avec JOIN
            $enriched = $this->fetchRisksFromDb($entityId, $year, $risk->id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Risque créé',
                'risk'    => $enriched ?? $risk,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('createRisk: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // MÉTHODE STATIQUE — appelée par AnalyseRisquesController
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Retourne la map des risques évalués dans l'univers pour entity+year.
     * Clé = risk_id, valeur = données sauvegardées.
     */
    public static function getUniverseMap(int $entityId, int $year): array
    {
        $saved = DB::table('audit_universe')
            ->where('entity_id', $entityId)
            ->where('year', $year)
            ->value('risques');

        if (!$saved) return [];

        return collect(json_decode($saved, true))->keyBy('risk_id')->all();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * UNE SEULE requête JOIN — filtre par entity_id ET year.
     * $year  = filtre sur r.year (obligatoire)
     * $riskId = filtre optionnel sur un seul risque (après création)
     */
    private function fetchRisksFromDb(int $entityId, int $year, ?int $riskId = null): \Illuminate\Support\Collection
    {
        $query = DB::table('risks as r')
            ->leftJoin('risk_frequency_levels as rfl', function ($j) {
                $j->on('rfl.id', '=', 'r.frequency_level_id')->whereNull('rfl.deleted_at');
            })
            ->leftJoin('risk_impact_levels as ril', function ($j) {
                $j->on('ril.id', '=', 'r.impact_level_id')->whereNull('ril.deleted_at');
            })
            ->leftJoin('processes as p',  'p.id', '=', 'r.process_id')
            ->leftJoin('activities as a', 'a.id', '=', 'r.activity_id')
            ->leftJoin('risk_types as rt', function ($j) {
                $j->on('rt.id', '=', 'r.risk_type_id')->whereNull('rt.deleted_at');
            })
            ->where('r.entity_id', $entityId)
            ->where('r.year', $year)          // ← FILTRE PAR ANNÉE
            ->select([
                'r.id',
                'r.code',
                'r.label',
                'r.description',
                'r.status',
                'r.owner',
                'r.criticality',
                'r.control_procedure',
                'r.impact_net',
                'r.frequency_net',
                'r.entity_id',
                'r.process_id',
                'r.activity_id',
                'r.risk_type_id',
                'r.year',
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
            ->orderBy('r.code');

        if ($riskId) $query->where('r.id', $riskId);

        return $query->get()->map(function ($row) {
            $row->frequency_level     = $row->frequency_level     ?? null;
            $row->frequency_label     = $row->frequency_label     ?? '-';
            $row->frequency_color     = $row->frequency_color     ?? 'secondary';
            $row->impact_level        = $row->impact_level        ?? null;
            $row->impact_label        = $row->impact_label        ?? '-';
            $row->impact_color        = $row->impact_color        ?? 'secondary';
            $row->process_code        = $row->process_code        ?? '-';
            $row->process_name        = $row->process_name        ?? '-';
            $row->activity_code       = $row->activity_code       ?? '-';
            $row->activity_name       = $row->activity_name       ?? '-';
            $row->risk_type_label     = $row->risk_type_label     ?? '-';
            $row->risk_type_color     = $row->risk_type_color     ?? 'secondary';
            $row->impact_net          = $row->impact_net          ?? null;
            $row->frequency_net       = $row->frequency_net       ?? null;
            $row->criticality_net     = null;
            $row->qualification_net   = null;
            $row->control_nature_code = null;
            $row->is_evaluated        = false;
            return $row;
        });
    }

    private function getColorPalette(): array
    {
        return [
            'danger'    => '#dc3545',
            'warning'   => '#ffc107',
            'info'      => '#0dcaf0',
            'success'   => '#28a745',
            'secondary' => '#6c757d',
            'primary'   => '#0d6efd',
        ];
    }

    private function generateRiskCode(?int $riskTypeId): string
    {
        try {
            if (!$riskTypeId) return 'RX-' . rand(100, 999);
            $type = DB::table('risk_types')->where('id', $riskTypeId)->whereNull('deleted_at')->first();
            if (!$type?->code) return 'RX-' . rand(100, 999);
            $prefix = strtoupper(substr($type->code, 0, 2));
            $last   = Risk::where('risk_type_id', $riskTypeId)->orderBy('code', 'desc')->first();
            $seq    = 1;
            if ($last && preg_match('/-(\d+)$/', $last->code, $m)) $seq = intval($m[1]) + 1;
            return $prefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            return 'RX-' . rand(100, 999);
        }
    }
}