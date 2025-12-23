<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProcessAMDECController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    /**
     * 📋 PHASE 1 — Créer mode défaillance
     */
    public function savePhase1(Request $request)
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|integer',
                'process_id' => 'required|integer',
                'activity_id' => 'required|integer',
                'failure_mode' => 'required|string|max:255',
                'failure_description' => 'nullable|string|max:500',
                'effects' => 'nullable|string',
                'causes' => 'nullable|string',
                'gravity_before_id' => 'required|integer',
                'frequency_before_id' => 'required|integer',
                'detectability_before_id' => 'required|integer',
                'detection_measures' => 'nullable|string',
            ]);

            $user = Auth::user();
            $t = $this->t();

            $session = $t->table('process_evaluation_sessions')->find($validated['session_id']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            $gravity = $t->table('amdec_gravities')->find($validated['gravity_before_id']);
            $frequency = $t->table('amdec_frequencies')->find($validated['frequency_before_id']);
            $detectability = $t->table('amdec_detectabilities')->find($validated['detectability_before_id']);

            if (!$gravity || !$frequency || !$detectability) {
                return response()->json(['success' => false, 'message' => 'Invalid referential'], 400);
            }

            $criticityBefore = $gravity->degree * $frequency->degree * $detectability->degree;
            $criticityNetteBefore = ($criticityBefore / 125) * 100;

            $standardBefore = $t->table('amdec_standards')
                ->where('min_criticality', '<=', $criticityNetteBefore)
                ->where('max_criticality', '>=', $criticityNetteBefore)
                ->first();

            $recordId = $t->table('amdec_records')->insertGetId([
                'session_id' => $validated['session_id'],
                'process_id' => $validated['process_id'],
                'activity_id' => $validated['activity_id'],
                'entity_id' => $session->entity_id,
                'function_id' => $session->function_id,
                'phase' => 'PHASE1',
                'failure_mode' => $validated['failure_mode'],
                'failure_description' => $validated['failure_description'],
                'effects' => $validated['effects'],
                'causes' => $validated['causes'],
                'gravity_before_id' => $validated['gravity_before_id'],
                'frequency_before_id' => $validated['frequency_before_id'],
                'detectability_before_id' => $validated['detectability_before_id'],
                'detection_measures' => $validated['detection_measures'],
                'criticality_before' => $criticityBefore,
                'criticality_nette_before' => $criticityNetteBefore,
                'standard_before_id' => $standardBefore?->id,
                'created_by_user_id' => $user->id,
                'created_by_user_name' => $user->name,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Mode défaillance créé (PHASE 1)',
                'record_id' => $recordId,
                'record' => $this->recordToArray($t->table('amdec_records')->find($recordId))
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur savePhase1: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🛡️ PHASE 2 — Plan d'action
     * ✅ Stocke action_responsible_function_id
     */
    public function savePhase2(Request $request)
    {
        try {
            $validated = $request->validate([
                'amdec_record_id' => 'required|integer',
                'action_description' => 'nullable|string',
                'action_deadline' => 'nullable|date',
                'action_responsible_function_id' => 'nullable|integer',
                'action_status' => 'nullable|in:pending,in_progress,completed,cancelled',
            ]);

            $user = Auth::user();
            $t = $this->t();

            $record = $t->table('amdec_records')->find($validated['amdec_record_id']);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Record not found'], 404);
            }

            if (!$record->failure_mode || !$record->criticality_nette_before) {
                return response()->json([
                    'success' => false,
                    'message' => 'PHASE 1 incomplète: données manquantes'
                ], 400);
            }

            // ✅ Récupérer nom fonction si sélectionnée
            $functionName = null;
            if ($validated['action_responsible_function_id']) {
                $func = $t->table('functions')->find($validated['action_responsible_function_id']);
                $functionName = $func?->name;
            }

            // ✅ UPDATE le record
            $updateData = [
                'phase' => 'PHASE2',
                'action_description' => $validated['action_description'],
                'action_responsible_function_id' => $validated['action_responsible_function_id'],
                'action_deadline' => $validated['action_deadline'],
                'action_status' => $validated['action_status'] ?? 'pending',
                'updated_by_user_id' => $user->id,
                'updated_by_user_name' => $user->name,
                'updated_at' => now()
            ];

            // Ajouter action_responsible si colonne existe
            if (\Schema::connection('tenant')->hasColumn('amdec_records', 'action_responsible')) {
                $updateData['action_responsible'] = $functionName;
            }

            $t->table('amdec_records')
                ->where('id', $validated['amdec_record_id'])
                ->update($updateData);

            $updatedRecord = $t->table('amdec_records')->find($validated['amdec_record_id']);

            return response()->json([
                'success' => true,
                'message' => '✅ Plan d\'action enregistré (PHASE 2)',
                'record' => $this->recordToArray($updatedRecord)
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur savePhase2: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ PHASE 3 — Évaluation post-correction
     */
    public function savePhase3(Request $request)
    {
        try {
            $validated = $request->validate([
                'amdec_record_id' => 'required|integer',
                'gravity_after_id' => 'required|integer',
                'frequency_after_id' => 'required|integer',
                'detectability_after_id' => 'required|integer',
            ]);

            $user = Auth::user();
            $t = $this->t();

            $record = $t->table('amdec_records')->find($validated['amdec_record_id']);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Record not found'], 404);
            }

            if (!$record->criticality_nette_before) {
                return response()->json([
                    'success' => false,
                    'message' => 'PHASE 1 incomplète: données manquantes'
                ], 400);
            }

            $gravity = $t->table('amdec_gravities')->find($validated['gravity_after_id']);
            $frequency = $t->table('amdec_frequencies')->find($validated['frequency_after_id']);
            $detectability = $t->table('amdec_detectabilities')->find($validated['detectability_after_id']);

            if (!$gravity || !$frequency || !$detectability) {
                return response()->json(['success' => false, 'message' => 'Invalid referential'], 400);
            }

            $criticityAfter = $gravity->degree * $frequency->degree * $detectability->degree;
            $criticityNetteAfter = ($criticityAfter / 125) * 100;

            $standardAfter = $t->table('amdec_standards')
                ->where('min_criticality', '<=', $criticityNetteAfter)
                ->where('max_criticality', '>=', $criticityNetteAfter)
                ->first();

            $improvementPercentage = null;
            if ($record->criticality_nette_before && $record->criticality_nette_before > 0) {
                $improvementPercentage = (($record->criticality_nette_before - $criticityNetteAfter) / $record->criticality_nette_before) * 100;
            }

            $t->table('amdec_records')
                ->where('id', $validated['amdec_record_id'])
                ->update([
                    'phase' => 'PHASE3',
                    'gravity_after_id' => $validated['gravity_after_id'],
                    'frequency_after_id' => $validated['frequency_after_id'],
                    'detectability_after_id' => $validated['detectability_after_id'],
                    'criticality_after' => $criticityAfter,
                    'criticality_nette_after' => $criticityNetteAfter,
                    'standard_after_id' => $standardAfter?->id,
                    'improvement_percentage' => $improvementPercentage,
                    'updated_by_user_id' => $user->id,
                    'updated_by_user_name' => $user->name,
                    'updated_at' => now()
                ]);

            $updatedRecord = $t->table('amdec_records')->find($validated['amdec_record_id']);

            return response()->json([
                'success' => true,
                'message' => '✅ Évaluation post-correction enregistrée (PHASE 3)',
                'record' => $this->recordToArray($updatedRecord),
                'calculations' => [
                    'criticality_before' => $record->criticality_nette_before,
                    'criticality_after' => round($criticityNetteAfter, 2),
                    'improvement_percentage' => round($improvementPercentage, 2),
                    'status' => $improvementPercentage > 0 ? '✅ Amélioration' : '⚠️ Aucune amélioration'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur savePhase3: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📄 INDEX — PAGE AMDEC PRINCIPALE
     * ✅ Structure tenant correcte
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $t = $this->t();

            // ✅ Récupérer le lien user-entity-function (tenant)
            $link = $t->table('function_assignments as fa')
                ->where('fa.user_id', $user->id)
                ->first(['entity_id', 'function_id']);

            if (!$link) {
                return Inertia::render('dashboards/Process/Core/AMDEC/Index', [
                    'user' => ['id' => $user->id, 'name' => $user->name],
                    'link' => null,
                    'processes' => [],
                    'activeSession' => null,
                    'referentials' => ['gravities' => [], 'frequencies' => [], 'detectabilities' => []],
                    'standards' => [],
                    'functions' => []
                ]);
            }

            // ✅ Charger processus (structure tenant)
            $processes = $t->table('processes')
                ->orderBy('name')
                ->select('id', 'name')
                ->get();

            // ✅ Charger session active
            $activeSession = $t->table('process_evaluation_sessions')
                ->where('entity_id', $link->entity_id)
                ->where('function_id', $link->function_id)
                ->where('is_active', true)
                ->first();

            if ($activeSession) {
                $activeSession = [
                    'id' => $activeSession->id,
                    'name' => $activeSession->name,
                    'color' => $activeSession->color ?? '#007bff',
                    'status' => $activeSession->status,
                    'created_at' => $activeSession->created_at
                ];
            }

            // ✅ Charger référentiels (sans colonne 'code')
            $gravities = $t->table('amdec_gravities')
                ->orderBy('sort')
                ->get(['id', 'label', 'degree', 'color', 'description']);

            $frequencies = $t->table('amdec_frequencies')
                ->orderBy('sort')
                ->get(['id', 'label', 'degree', 'color', 'description']);

            $detectabilities = $t->table('amdec_detectabilities')
                ->orderBy('sort')
                ->get(['id', 'label', 'degree', 'color']);

            $standards = $t->table('amdec_standards')
                ->orderBy('sort')
                ->get(['id', 'name', 'color', 'min_criticality', 'max_criticality']);

            // ✅ Charger fonctions (SANS colonne 'code')
            $functions = $t->table('functions')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return Inertia::render('dashboards/Process/Core/AMDEC/Index', [
                'user' => ['id' => $user->id, 'name' => $user->name],
                'link' => [
                    'entity_id' => $link->entity_id,
                    'function_id' => $link->function_id,
                ],
                'processes' => $processes->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                'activeSession' => $activeSession,
                'referentials' => [
                    'gravities' => $gravities->map(fn($g) => ['id' => $g->id, 'label' => $g->label, 'degree' => $g->degree, 'color' => $g->color, 'description' => $g->description])->values(),
                    'frequencies' => $frequencies->map(fn($f) => ['id' => $f->id, 'label' => $f->label, 'degree' => $f->degree, 'color' => $f->color, 'description' => $f->description])->values(),
                    'detectabilities' => $detectabilities->map(fn($d) => ['id' => $d->id, 'label' => $d->label, 'degree' => $d->degree, 'color' => $d->color])->values(),
                ],
                'standards' => $standards->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'color' => $s->color, 'min_criticality' => $s->min_criticality, 'max_criticality' => $s->max_criticality])->values(),
                'functions' => $functions->map(fn($f) => ['id' => $f->id, 'name' => $f->name])->values()
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur ProcessAMDEC.index: ' . $e->getMessage());
            return Inertia::render('dashboards/Process/Core/AMDEC/Index', [
                'user' => ['id' => Auth::user()?->id, 'name' => Auth::user()?->name],
                'link' => null,
                'processes' => [],
                'activeSession' => null,
                'referentials' => ['gravities' => [], 'frequencies' => [], 'detectabilities' => []],
                'standards' => [],
                'functions' => []
            ]);
        }
    }

    /**
     * 📊 LOAD DATA — Charger données PHASE 1/2/3
     */
    public function loadData(Request $request)
    {
        try {
            $v = $request->validate([
                'session_id' => 'required|integer',
                'process_id' => 'required|integer'
            ]);

            $t = $this->t();

            $session = $t->table('process_evaluation_sessions')->find($v['session_id']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            $activities = $t->table('activities')
                ->where('process_id', $v['process_id'])
                ->orderBy('code')
                ->get(['id', 'code', 'name']);

            if ($activities->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'activities' => [],
                    'records' => ['PHASE1' => [], 'PHASE2' => [], 'PHASE3' => []],
                    'statistics' => null
                ]);
            }

            $allRecords = $t->table('amdec_records')
                ->where('session_id', $v['session_id'])
                ->where('process_id', $v['process_id'])
                ->whereNull('deleted_at')
                ->orderBy('activity_id')
                ->orderBy('phase')
                ->get();

            $recordsByPhase = [
                'PHASE1' => [],
                'PHASE2' => [],
                'PHASE3' => []
            ];

            foreach ($allRecords as $record) {
                $phase = $record->phase ?? 'PHASE1';
                $recordsByPhase[$phase][] = $this->recordToArray($record);
            }

            $statistics = $this->calculateStatistics($allRecords);

            return response()->json([
                'success' => true,
                'activities' => $activities->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name])->values(),
                'records' => $recordsByPhase,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur loadData: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 💾 SAVE RECORD — DISPATCHER INTELLIGENT
     */
    public function saveRecord(Request $request)
    {
        try {
            $phase = $request->input('phase', 'PHASE1');

            if ($phase === 'PHASE1') {
                return $this->savePhase1($request);
            } elseif ($phase === 'PHASE2') {
                return $this->savePhase2($request);
            } elseif ($phase === 'PHASE3') {
                return $this->savePhase3($request);
            } else {
                return response()->json(['success' => false, 'message' => 'Phase invalide'], 400);
            }

        } catch (\Exception $e) {
            \Log::error('❌ Erreur saveRecord (dispatcher): ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🗑️ DELETE RECORD — Soft delete
     */
    public function deleteRecord($id)
    {
        try {
            $user = Auth::user();
            $t = $this->t();
            
            $t->table('amdec_records')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_by_user_id' => $user->id,
                    'updated_by_user_name' => $user->name
                ]);

            return response()->json(['success' => true, 'message' => '✅ Enregistrement supprimé']);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur deleteRecord: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🌐 Récupérer liste des fonctions (SANS code)
     */
    public function getFunctions()
    {
        try {
            $functions = $this->t()
                ->table('functions')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json(['success' => true, 'data' => $functions]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getFunctions: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getGravities()
    {
        try {
            $gravities = $this->t()
                ->table('amdec_gravities')
                ->orderBy('sort')
                ->get(['id', 'label', 'degree', 'color', 'description']);

            return response()->json(['success' => true, 'data' => $gravities]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getGravities: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getFrequencies()
    {
        try {
            $frequencies = $this->t()
                ->table('amdec_frequencies')
                ->orderBy('sort')
                ->get(['id', 'label', 'degree', 'color', 'description']);

            return response()->json(['success' => true, 'data' => $frequencies]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getFrequencies: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getDetectabilities()
    {
        try {
            $detectabilities = $this->t()
                ->table('amdec_detectabilities')
                ->orderBy('sort')
                ->get(['id', 'label', 'degree', 'color']);

            return response()->json(['success' => true, 'data' => $detectabilities]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getDetectabilities: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getStandards()
    {
        try {
            $standards = $this->t()
                ->table('amdec_standards')
                ->orderBy('sort')
                ->get(['id', 'name', 'color', 'min_criticality', 'max_criticality']);

            return response()->json(['success' => true, 'data' => $standards]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getStandards: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function calculateStatistics($records)
    {
        $total = count($records);
        $phase1Count = $records->where('phase', 'PHASE1')->count();
        $phase2Count = $records->where('phase', 'PHASE2')->count();
        $phase3Count = $records->where('phase', 'PHASE3')->count();

        $highRiskBefore = $records->where('criticality_nette_before', '>=', 60)->count();
        $avgCritBefore = $records->avg('criticality_nette_before') ?? 0;
        $avgCritAfter = $records->avg('criticality_nette_after') ?? 0;
        $avgImprovement = $records->avg('improvement_percentage') ?? 0;

        return [
            'total_records' => $total,
            'phase1_count' => $phase1Count,
            'phase2_count' => $phase2Count,
            'phase3_count' => $phase3Count,
            'high_risk_before' => $highRiskBefore,
            'average_criticality_nette_before' => round($avgCritBefore, 1),
            'average_criticality_nette_after' => round($avgCritAfter, 1),
            'average_improvement' => round($avgImprovement, 1),
        ];
    }

    public function exportExcel(Request $request)
    {
        try {
            $v = $request->validate([
                'session_id' => 'required|integer',
                'process_id' => 'required|integer'
            ]);

            $t = $this->t();

            $session = $t->table('process_evaluation_sessions')->find($v['session_id']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            $process = $t->table('processes')->find($v['process_id']);
            $records = $t->table('amdec_records')
                ->where('session_id', $v['session_id'])
                ->where('process_id', $v['process_id'])
                ->whereNull('deleted_at')
                ->orderBy('phase')
                ->orderBy('activity_id')
                ->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('AMDEC');

            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];

            $cellStyle = [
                'font' => ['size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'wrapText' => true
            ];

            $sheet->mergeCells('A1:Z1');
            $sheet->setCellValue('A1', 'AMDEC — ' . ($process->name ?? 'Export'));
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $row = 3;

            $headers = [
                'Phase', 'Activité', 'Mode', 'Effets', 'Gravité Av.', 'Causes', 'Fréquence Av.',
                'Contrôles', 'Détectabilité Av.', 'Crit. Av. (%)', 'Plan d\'action', 'Responsable',
                'Délai', 'Statut', 'Critère Efficacité', 'Mesure Efficacité', 'Gravité Ap.',
                'Fréquence Ap.', 'Détectabilité Ap.', 'Crit. Ap. (%)', 'Amélioration (%)'
            ];

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
                $sheet->getColumnDimension($col)->setWidth(15);
                $col++;
            }

            $row++;

            foreach ($records as $record) {
                $sheet->setCellValue('A' . $row, $record->phase);
                $sheet->setCellValue('B' . $row, $record->activity_id);
                $sheet->setCellValue('C' . $row, $record->failure_mode);
                $sheet->setCellValue('D' . $row, $record->effects);
                $sheet->setCellValue('E' . $row, $record->gravity_before_id);
                $sheet->setCellValue('F' . $row, $record->causes);
                $sheet->setCellValue('G' . $row, $record->frequency_before_id);
                $sheet->setCellValue('H' . $row, $record->detection_measures);
                $sheet->setCellValue('I' . $row, $record->detectability_before_id);
                $sheet->setCellValue('J' . $row, $record->criticality_nette_before);
                $sheet->setCellValue('K' . $row, $record->action_description);
                $sheet->setCellValue('L' . $row, $record->action_responsible);
                $sheet->setCellValue('M' . $row, $record->action_deadline);
                $sheet->setCellValue('N' . $row, $record->action_status);
                $sheet->setCellValue('O' . $row, $record->efficacy_criterion ?? '');
                $sheet->setCellValue('P' . $row, $record->efficacy_measure ?? '');
                $sheet->setCellValue('Q' . $row, $record->gravity_after_id);
                $sheet->setCellValue('R' . $row, $record->frequency_after_id);
                $sheet->setCellValue('S' . $row, $record->detectability_after_id);
                $sheet->setCellValue('T' . $row, $record->criticality_nette_after);
                $sheet->setCellValue('U' . $row, $record->improvement_percentage);

                $sheet->getStyle('A' . $row . ':U' . $row)->applyFromArray($cellStyle);
                $sheet->getRowDimension($row)->setRowHeight(25);

                $row++;
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="AMDEC_export_' . now()->format('Ymd_His') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Log::error('❌ Erreur exportExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function recordToArray($record)
    {
        $data = [
            'id' => $record->id ?? null,
            'activity_id' => $record->activity_id ?? null,
            'phase' => $record->phase ?? 'PHASE1',
            'failure_mode' => $record->failure_mode ?? null,
            'failure_description' => $record->failure_description ?? null,
            'effects' => $record->effects ?? null,
            'causes' => $record->causes ?? null,
            'detection_measures' => $record->detection_measures ?? null,
            'gravity_before_id' => $record->gravity_before_id ?? null,
            'frequency_before_id' => $record->frequency_before_id ?? null,
            'detectability_before_id' => $record->detectability_before_id ?? null,
            'criticality_before' => $record->criticality_before ?? null,
            'criticality_nette_before' => $record->criticality_nette_before ?? null,
            'standard_before_id' => $record->standard_before_id ?? null,
            'action_description' => $record->action_description ?? null,
            'action_responsible' => $record->action_responsible ?? null,
            'action_responsible_function_id' => $record->action_responsible_function_id ?? null,
            'action_deadline' => $record->action_deadline ?? null,
            'action_status' => $record->action_status ?? null,
            'gravity_after_id' => $record->gravity_after_id ?? null,
            'frequency_after_id' => $record->frequency_after_id ?? null,
            'detectability_after_id' => $record->detectability_after_id ?? null,
            'criticality_after' => $record->criticality_after ?? null,
            'criticality_nette_after' => $record->criticality_nette_after ?? null,
            'standard_after_id' => $record->standard_after_id ?? null,
            'improvement_percentage' => $record->improvement_percentage ?? null,
        ];

        if (property_exists($record, 'efficacy_criterion')) {
            $data['efficacy_criterion'] = $record->efficacy_criterion ?? null;
        }
        if (property_exists($record, 'efficacy_measure')) {
            $data['efficacy_measure'] = $record->efficacy_measure ?? null;
        }

        return $data;
    }
}