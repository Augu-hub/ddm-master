<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Audit\Risk;
use App\Models\Audit\RiskType;
use App\Models\Audit\AuditFrequencyLevel;
use App\Models\Audit\AuditImpactLevel;
use App\Models\Audit\AuditMatrix;
use App\Models\Audit\AuditSession;
use App\Services\RiskAISuggestionService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * RISK CONTROLLER V13
 * ════════════════════════════════════════════════════════════════════════════════
 * ✅ Pas de filtre tenant_id — toutes les données sont accessibles
 * ✅ Codification hiérarchique : MP_CODE.PR_CODE.AC_CODE-NNN
 * ✅ Suggestions IA via Mistral (sans code)
 * ════════════════════════════════════════════════════════════════════════════════
 */
class RiskController extends Controller
{
    public function __construct(
        private RiskAISuggestionService $aiService
    ) {}

    private function t()
    {
        return DB::connection('tenant');
    }

    private array $_frequencies = [];
    private array $_impacts     = [];
    private array $_matrix      = [];

    private function loadLevels(): void
    {
        if (empty($this->_frequencies)) {
            $this->_frequencies = AuditFrequencyLevel::orderBy('level')->get()->keyBy('id')->toArray();
        }
        if (empty($this->_impacts)) {
            $this->_impacts = AuditImpactLevel::orderBy('level')->get()->keyBy('id')->toArray();
        }
        if (empty($this->_matrix)) {
            $this->_matrix = AuditMatrix::all()
                ->keyBy(fn($m) => "{$m->impact_level}_{$m->frequency_level}")
                ->toArray();
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET — Dashboard principal
    // ══════════════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        try {
            $activeSession = AuditSession::where('status', 'active')->first();

            $frequencies = AuditFrequencyLevel::orderBy('level')->get()
                ->map(fn($f) => ['id'=>$f->id,'code'=>$f->code,'label'=>$f->label,'level'=>$f->level,'color'=>$f->color,'description'=>$f->description])
                ->toArray();

            $impacts = AuditImpactLevel::orderBy('level')->get()
                ->map(fn($i) => ['id'=>$i->id,'code'=>$i->code,'label'=>$i->label,'level'=>$i->level,'color'=>$i->color,'description'=>$i->description])
                ->toArray();

            $riskTypes = RiskType::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn($t) => ['id'=>$t->id,'code'=>$t->code,'label'=>$t->label,'color'=>$t->color??'#6c757d'])
                ->toArray();

            $matrix = AuditMatrix::orderBy('impact_level')->orderBy('frequency_level')->get()
                ->map(fn($m) => ['id'=>$m->id,'impact_level'=>$m->impact_level,'frequency_level'=>$m->frequency_level,
                    'criticality_score'=>$m->criticality_score,'label'=>$m->label,'qualification'=>$m->qualification,'color'=>$m->color])
                ->toArray();

            $entities        = $this->loadTenantTable('entities',   fn($q) => $q->orderBy('name'));
            $processes       = $this->loadTenantTable('processes',  fn($q) => $q->orderBy('code'));
            $activities      = $this->loadTenantTable('activities', fn($q) => $q->orderBy('code'));
            $macroProcesses  = $this->loadMacroProcesses();
            $entityFunctions = $this->loadEntityFunctions();

            $allSessions = AuditSession::orderBy('created_at', 'desc')->get()
                ->map(fn($s) => [
                    'id'            => $s->id,
                    'code'          => $s->code,
                    'name'          => $s->name,
                    'status'        => $s->status,
                    'is_active'     => $s->status === 'active',
                    'exercise_name' => optional($s->exercise)->name ?? 'N/A',
                    'entity_name'   => optional($s->entity)->name  ?? 'N/A',
                    'risks_count'   => Risk::where('audit_session_id', $s->id)->count(),
                ]);

            $this->loadLevels();

            $risksQuery = Risk::whereNull('deleted_at');
            if ($activeSession) {
                $risksQuery->where(function ($q) use ($activeSession) {
                    $q->where('audit_session_id', $activeSession->id)
                      ->orWhereNull('audit_session_id');
                });
            }

            $risks = $risksQuery->orderBy('code')->get()
                ->map(fn($r) => $this->formatRisk($r));

            return Inertia::render('dashboards/Audit/index', [
                'allSessions'     => $allSessions,
                'entities'        => $entities,
                'macroProcesses'  => $macroProcesses,
                'processes'       => $processes,
                'activities'      => $activities,
                'riskTypes'       => $riskTypes,
                'frequencies'     => $frequencies,
                'impacts'         => $impacts,
                'matrix'          => $matrix,
                'entityFunctions' => $entityFunctions,
                'activeSession'   => $activeSession ? [
                    'id'            => $activeSession->id,
                    'code'          => $activeSession->code,
                    'name'          => $activeSession->name,
                    'status'        => $activeSession->status,
                    'exercise_name' => optional($activeSession->exercise)->name ?? 'N/A',
                    'entity_name'   => optional($activeSession->entity)->name   ?? 'N/A',
                ] : null,
                'initialRisks' => $risks->values()->toArray(),
                'statistics'   => $this->computeStatistics($risks),
            ]);

        } catch (\Exception $e) {
            Log::error('[Risk] index: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST switch-session
    // ══════════════════════════════════════════════════════════════════════════
    public function switchSession(Request $request)
    {
        try {
            $validated  = $request->validate(['session_id' => 'required|integer']);
            $newSession = AuditSession::findOrFail($validated['session_id']);

            AuditSession::where('id', '!=', $newSession->id)
                ->where('status', 'active')
                ->update(['status' => 'paused', 'updated_at' => now()]);

            $newSession->update(['status' => 'active', 'updated_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => "Session '{$newSession->code}' activée",
                'session' => ['id' => $newSession->id, 'code' => $newSession->code, 'status' => 'active'],
            ]);
        } catch (\Exception $e) {
            Log::error('[Risk] switchSession: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST suggest-ai
    // ══════════════════════════════════════════════════════════════════════════
    public function suggestAI(Request $request)
    {
        try {
            $v = $request->validate([
                'process_name'   => 'required|string|max:255',
                'activity_name'  => 'required|string|max:255',
                'risk_type_name' => 'required|string|max:255',
            ]);
            $result = $this->aiService->generateMultipleSuggestions(
                $v['process_name'], $v['activity_name'], $v['risk_type_name']
            );
            return response()->json([
                'success'     => true,
                'suggestions' => $result['suggestions'] ?? [],
                'mode'        => $result['mode'] ?? 'fallback',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // POST suggest-control
    public function suggestControl(Request $request)
    {
        try {
            $v = $request->validate([
                'risk_label'    => 'required|string|max:500',
                'activity_name' => 'required|string|max:255',
                'process_name'  => 'required|string|max:255',
            ]);
            $result = $this->aiService->generateControlProcedure(
                $v['risk_label'], $v['activity_name'], $v['process_name']
            );
            return response()->json([
                'success'           => true,
                'control_procedure' => $result['control_procedure'] ?? '',
                'mode'              => $result['mode'] ?? 'fallback',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST / — Créer
    // ══════════════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        try {
            $v = $request->validate([
                'label'              => 'required|string|max:500',
                'description'        => 'nullable|string|max:2000',
                'risk_type_id'       => 'required|integer',
                'frequency_level_id' => 'required|integer',
                'frequency_net'      => 'nullable|numeric|min:0|max:5',
                'impact_level_id'    => 'required|integer',
                'impact_net'         => 'nullable|numeric|min:0|max:5',
                'entity_id'          => 'nullable|integer',
                'process_id'         => 'nullable|integer',
                'activity_id'        => 'nullable|integer',
                'owner_function_id'  => 'nullable|integer',
                'owner'              => 'nullable|string|max:255',
                'control_procedure'  => 'nullable|string|max:5000',
                'status'             => 'nullable|in:identified,assessed,mitigated,monitored,closed',
            ]);

            $activeSession = AuditSession::where('status', 'active')->first();
            $freqLevel     = AuditFrequencyLevel::find($v['frequency_level_id']);
            $impLevel      = AuditImpactLevel::find($v['impact_level_id']);

            // Validation net ≤ brut
            $errors = [];
            if (!empty($v['frequency_net']) && $freqLevel && $v['frequency_net'] > $freqLevel->level)
                $errors['frequency_net'] = ["Fréquence nette ≤ fréquence brute ({$freqLevel->level})"];
            if (!empty($v['impact_net']) && $impLevel && $v['impact_net'] > $impLevel->level)
                $errors['impact_net'] = ["Impact net ≤ impact brut ({$impLevel->level})"];
            if (!empty($errors)) return response()->json(['success' => false, 'errors' => $errors], 422);

            // Code hiérarchique
            $code = $this->generateHierarchicalCode(
                $v['process_id']  ?? null,
                $v['activity_id'] ?? null,
                $v['risk_type_id']
            );

            // Owner depuis fonction
            $ownerName = $v['owner'] ?? null;
            if (!empty($v['owner_function_id']) && empty($ownerName)) {
                try {
                    $func = $this->t()->table('functions')->where('id', $v['owner_function_id'])->first();
                    if ($func) $ownerName = $func->character ? "{$func->name} ({$func->character})" : $func->name;
                } catch (\Exception $e) {}
            }

            $data = [
                'audit_session_id'   => $activeSession?->id,
                'code'               => $code,
                'label'              => $v['label'],
                'description'        => $v['description']       ?? null,
                'risk_type_id'       => $v['risk_type_id'],
                'frequency_level_id' => $v['frequency_level_id'],
                'frequency_net'      => $v['frequency_net']     ?? null,
                'impact_level_id'    => $v['impact_level_id'],
                'impact_net'         => $v['impact_net']        ?? null,
                'criticality'        => ($freqLevel && $impLevel) ? $freqLevel->level * $impLevel->level : null,
                'entity_id'          => $v['entity_id']         ?? null,
                'process_id'         => $v['process_id']        ?? null,
                'activity_id'        => $v['activity_id']       ?? null,
                'owner'              => $ownerName,
                'control_procedure'  => $v['control_procedure'] ?? null,
                'status'             => $v['status']            ?? 'identified',
                'year'               => now()->year,
                'created_by'         => auth()->id(),
            ];

            if ($this->hasColumn('risks', 'owner_function_id'))
                $data['owner_function_id'] = $v['owner_function_id'] ?? null;

            $this->loadLevels();
            $risk = Risk::create($data);

            return response()->json([
                'success' => true,
                'message' => "Risque '{$risk->code}' créé",
                'risk'    => $this->formatRisk($risk),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[Risk] store: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PUT /{risk} — Modifier
    // ══════════════════════════════════════════════════════════════════════════
    public function update(Request $request, Risk $risk)
    {
        try {
            $v = $request->validate([
                'label'              => 'sometimes|required|string|max:500',
                'description'        => 'nullable|string|max:2000',
                'risk_type_id'       => 'nullable|integer',
                'frequency_level_id' => 'nullable|integer',
                'frequency_net'      => 'nullable|numeric|min:0|max:5',
                'impact_level_id'    => 'nullable|integer',
                'impact_net'         => 'nullable|numeric|min:0|max:5',
                'entity_id'          => 'nullable|integer',
                'process_id'         => 'nullable|integer',
                'activity_id'        => 'nullable|integer',
                'owner_function_id'  => 'nullable|integer',
                'owner'              => 'nullable|string|max:255',
                'control_procedure'  => 'nullable|string|max:5000',
                'status'             => 'nullable|in:identified,assessed,mitigated,monitored,closed',
            ]);

            $freqLevelId = $v['frequency_level_id'] ?? $risk->frequency_level_id;
            $impLevelId  = $v['impact_level_id']    ?? $risk->impact_level_id;
            $freqLevel   = $freqLevelId ? AuditFrequencyLevel::find($freqLevelId) : null;
            $impLevel    = $impLevelId  ? AuditImpactLevel::find($impLevelId)    : null;

            $errors = [];
            if (($v['frequency_net'] ?? null) !== null && $freqLevel && $v['frequency_net'] > $freqLevel->level)
                $errors['frequency_net'] = ["Fréquence nette ≤ fréquence brute ({$freqLevel->level})"];
            if (($v['impact_net'] ?? null) !== null && $impLevel && $v['impact_net'] > $impLevel->level)
                $errors['impact_net'] = ["Impact net ≤ impact brut ({$impLevel->level})"];
            if (!empty($errors)) return response()->json(['success' => false, 'errors' => $errors], 422);

            if (isset($v['frequency_level_id']) || isset($v['impact_level_id'])) {
                $v['criticality'] = ($freqLevel && $impLevel) ? $freqLevel->level * $impLevel->level : $risk->criticality;
            }

            // Recalculer code si hiérarchie change
            $processChanged  = isset($v['process_id'])   && $v['process_id']  !== $risk->process_id;
            $activityChanged = isset($v['activity_id'])  && $v['activity_id'] !== $risk->activity_id;
            $typeChanged     = isset($v['risk_type_id']) && $v['risk_type_id'] !== $risk->risk_type_id;
            if ($processChanged || $activityChanged || $typeChanged) {
                $v['code'] = $this->generateHierarchicalCode(
                    $v['process_id']   ?? $risk->process_id,
                    $v['activity_id']  ?? $risk->activity_id,
                    $v['risk_type_id'] ?? $risk->risk_type_id,
                    $risk->id
                );
            }

            if (!empty($v['owner_function_id']) && empty($v['owner'])) {
                try {
                    $func = $this->t()->table('functions')->where('id', $v['owner_function_id'])->first();
                    if ($func) $v['owner'] = $func->character ? "{$func->name} ({$func->character})" : $func->name;
                } catch (\Exception $e) {}
            }

            $updateData = array_filter($v, fn($val) => $val !== null);
            if (!$this->hasColumn('risks', 'owner_function_id')) unset($updateData['owner_function_id']);

            $risk->update(array_merge($updateData, ['updated_by' => auth()->id()]));
            $this->loadLevels();

            return response()->json([
                'success' => true,
                'message' => "Risque '{$risk->code}' modifié",
                'risk'    => $this->formatRisk($risk->fresh()),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[Risk] update: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Risk $risk)
    {
        try {
            $code = $risk->code; $risk->delete();
            return response()->json(['success' => true, 'message' => "Risque '{$code}' supprimé"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Risk $risk)
    {
        $this->loadLevels();
        return response()->json(['success' => true, 'risk' => $this->formatRisk($risk)]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    private function loadMacroProcesses(): array
    {
        try {
            return $this->t()->table('macro_processes')->orderBy('code')->get()
                ->map(fn($r) => (array) $r)->toArray();
        } catch (\Exception $e) {
            Log::warning('[Risk] loadMacroProcesses: ' . $e->getMessage());
            return [];
        }
    }

    private function loadEntityFunctions(): array
    {
        try {
            $rows = $this->t()
                ->table('function_assignments as fa')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->select('fa.entity_id', 'f.id as function_id', 'f.name', 'f.character', 'f.parent_id')
                ->orderBy('f.name')->get();

            $result = [];
            foreach ($rows as $row) {
                $result[$row->entity_id][] = [
                    'id'        => $row->function_id,
                    'name'      => $row->name,
                    'character' => $row->character,
                    'parent_id' => $row->parent_id,
                    'label'     => $row->character ? "{$row->name} ({$row->character})" : $row->name,
                ];
            }
            return $result;
        } catch (\Exception $e) {
            Log::warning('[Risk] loadEntityFunctions: ' . $e->getMessage());
            return [];
        }
    }

    private function loadTenantTable(string $table, callable $cb = null): array
    {
        try {
            $q = $this->t()->table($table);
            if ($cb) $cb($q);
            return $q->get()->map(fn($r) => (array) $r)->toArray();
        } catch (\Exception $e) {
            Log::warning("[Risk] loadTenantTable({$table}): " . $e->getMessage());
            return [];
        }
    }

    private function hasColumn(string $table, string $col): bool
    {
        try { return DB::getSchemaBuilder()->hasColumn($table, $col); }
        catch (\Exception $e) { return false; }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CODIFICATION HIÉRARCHIQUE : MP_CODE.PR_CODE.AC_CODE-NNN
    // ══════════════════════════════════════════════════════════════════════════
    private function generateHierarchicalCode(
        ?int $processId,
        ?int $activityId,
        ?int $riskTypeId,
        ?int $excludeRiskId = null
    ): string {
        try {
            $macroCode = '';
            $procCode  = '';
            $actCode   = '';

            if ($processId) {
                $proc = $this->t()->table('processes')->where('id', $processId)->first();
                if ($proc) {
                    $procCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $proc->code ?? ''));
                    if (!empty($proc->macro_process_id)) {
                        try {
                            $macro = $this->t()->table('macro_processes')->where('id', $proc->macro_process_id)->first();
                            if ($macro) $macroCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $macro->code ?? ''));
                        } catch (\Exception $e) {}
                    }
                }
            }

            if ($activityId) {
                $act = $this->t()->table('activities')->where('id', $activityId)->first();
                if ($act) $actCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $act->code ?? ''));
            }

            $parts = array_filter([$macroCode, $procCode, $actCode]);
            if (empty($parts)) {
                $riskType = $riskTypeId ? RiskType::find($riskTypeId) : null;
                $prefix   = strtoupper(substr($riskType?->code ?? 'RQ', 0, 3));
            } else {
                $prefix = implode('.', $parts);
            }

            // Séquence max existante pour ce préfixe
            $query = Risk::whereNull('deleted_at')->where('code', 'like', $prefix . '-%');
            if ($excludeRiskId) $query->where('id', '!=', $excludeRiskId);

            $maxSeq = 0;
            $query->orderBy('id', 'desc')->cursor()->each(function ($r) use (&$maxSeq, $prefix) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $r->code, $m)) {
                    $n = (int) $m[1];
                    if ($n > $maxSeq) $maxSeq = $n;
                }
            });

            $code = $prefix . '-' . str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);

            // Anti-collision
            if (Risk::where('code', $code)->whereNull('deleted_at')
                ->when($excludeRiskId, fn($q) => $q->where('id', '!=', $excludeRiskId))
                ->exists()) {
                $code = $prefix . '-' . str_pad($maxSeq + 2, 3, '0', STR_PAD_LEFT);
            }

            return $code;

        } catch (\Exception $e) {
            Log::warning('[Risk] generateHierarchicalCode: ' . $e->getMessage());
            return 'RQ-' . str_pad((Risk::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // FORMAT RISK
    // ══════════════════════════════════════════════════════════════════════════
    private function formatRisk(Risk $risk): array
    {
        if (empty($this->_frequencies)) $this->loadLevels();

        $freqRow    = $risk->frequency_level_id ? ($this->_frequencies[$risk->frequency_level_id] ?? null) : null;
        $impRow     = $risk->impact_level_id    ? ($this->_impacts[$risk->impact_level_id]         ?? null) : null;
        $critGross  = ($freqRow && $impRow) ? $freqRow['level'] * $impRow['level'] : ($risk->criticality ?? null);
        $matrixKey  = ($impRow && $freqRow) ? "{$impRow['level']}_{$freqRow['level']}" : null;
        $matrixCell = $matrixKey ? ($this->_matrix[$matrixKey] ?? null) : null;
        $critNet    = ($risk->frequency_net && $risk->impact_net)
            ? round((float)$risk->frequency_net * (float)$risk->impact_net, 1) : null;

        $codeHierarchy = $this->parseCodeHierarchy($risk->code);

        $ownerFunctionId   = null;
        $ownerFunctionName = null;
        if ($this->hasColumn('risks', 'owner_function_id') && $risk->owner_function_id) {
            $ownerFunctionId = $risk->owner_function_id;
            try {
                $func = $this->t()->table('functions')->where('id', $ownerFunctionId)->first();
                if ($func) $ownerFunctionName = $func->character ? "{$func->name} ({$func->character})" : $func->name;
            } catch (\Exception $e) {}
        }

        return [
            'id'                   => $risk->id,
            'code'                 => $risk->code,
            'code_hierarchy'       => $codeHierarchy,
            'label'                => $risk->label,
            'description'          => $risk->description,
            'risk_type_id'         => $risk->risk_type_id,
            'frequency_level_id'   => $risk->frequency_level_id,
            'frequency_level'      => $freqRow['level']  ?? null,
            'frequency_label'      => $freqRow['label']  ?? null,
            'frequency_color'      => $freqRow['color']  ?? null,
            'impact_level_id'      => $risk->impact_level_id,
            'impact_level'         => $impRow['level']   ?? null,
            'impact_label'         => $impRow['label']   ?? null,
            'impact_color'         => $impRow['color']   ?? null,
            'criticality_gross'    => $critGross,
            'matrix_label'         => $matrixCell['label']         ?? null,
            'matrix_qualification' => $matrixCell['qualification'] ?? null,
            'matrix_color'         => $matrixCell['color']         ?? null,
            'frequency_net'        => $risk->frequency_net ? (float)$risk->frequency_net : null,
            'impact_net'           => $risk->impact_net   ? (float)$risk->impact_net    : null,
            'criticality_net'      => $critNet,
            'owner'                => $risk->owner,
            'owner_function_id'    => $ownerFunctionId,
            'owner_function_name'  => $ownerFunctionName,
            'control_procedure'    => $risk->control_procedure,
            'status'               => $risk->status ?? 'identified',
            'entity_id'            => $risk->entity_id,
            'process_id'           => $risk->process_id,
            'activity_id'          => $risk->activity_id,
            'audit_session_id'     => $risk->audit_session_id,
            'created_at'           => $risk->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function parseCodeHierarchy(string $code): array
    {
        if (!preg_match('/^(.+)-(\d+)$/', $code, $m)) {
            return ['prefix' => $code, 'sequence' => '', 'parts' => []];
        }
        return [
            'prefix'   => $m[1],
            'sequence' => $m[2],
            'parts'    => explode('.', $m[1]),
        ];
    }

    private function computeStatistics($risks): array
    {
        $arr = is_array($risks) ? $risks : $risks->toArray();
        if (empty($arr)) return ['total_risks'=>0,'critical'=>0,'high'=>0,'medium'=>0,'low'=>0,'average_criticality'=>0];
        $c = fn($r) => $r['criticality_gross'] ?? 0;
        return [
            'total_risks'         => count($arr),
            'critical'            => count(array_filter($arr, fn($r) => $c($r) >= 15)),
            'high'                => count(array_filter($arr, fn($r) => $c($r) >= 9 && $c($r) < 15)),
            'medium'              => count(array_filter($arr, fn($r) => $c($r) >= 4 && $c($r) < 9)),
            'low'                 => count(array_filter($arr, fn($r) => $c($r) < 4)),
            'average_criticality' => round(array_sum(array_map($c, $arr)) / count($arr), 2),
        ];
    }
}