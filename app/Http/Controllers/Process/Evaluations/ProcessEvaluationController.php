<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;
use FPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProcessEvaluationController extends Controller
{
    private function t()
    {
        return DB::connection('tenant');
    }

    public function index(Request $request)
    {
        try {
            $t = $this->t();
            $user = Auth::user();

            if (!$user) {
                return back()->with('error', 'Utilisateur non authentifié');
            }

            $link = $t->table('function_assignments as fa')
                ->join('entities as e', 'e.id', '=', 'fa.entity_id')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->where('fa.user_id', $user->id)
                ->select('fa.entity_id', 'fa.function_id', 'e.name as entity_name', 'f.name as function_name')
                ->first();

            if (!$link) {
                return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                    'user' => $user,
                    'link' => null,
                    'processes' => [],
                    'sessions' => [],
                    'maturityLevels' => [],
                ]);
            }

            $processIds = $t->table('assignments as a')
                ->join('assignment_functions as af', 'a.id', '=', 'af.assignment_id')
                ->where('af.function_id', $link->function_id)
                ->where('a.entity_id', $link->entity_id)
                ->where('a.mpa_type', 'process')
                ->pluck('a.mpa_id');

            $processes = $t->table('processes')
                ->whereIn('id', $processIds)
                ->orderBy('code')
                ->get();

            $sessions = $t->table('process_evaluation_sessions')
                ->where('entity_id', $link->entity_id)
                ->where('function_id', $link->function_id)
                ->orderByDesc('created_at')
                ->get();

            foreach ($sessions as $session) {
                $evaluatedCount = $t->table('process_session_axis_evaluations')
                    ->where('session_id', $session->id)
                    ->whereNotNull('criticality_score')
                    ->distinct('process_id')
                    ->count('process_id');

                $avgScore = $t->table('process_session_axis_evaluations')
                    ->where('session_id', $session->id)
                    ->whereNotNull('criticality_score')
                    ->avg('criticality_score') ?? 0;

                $session->evaluated_count = $evaluatedCount;
                $session->session_avg_score = round($avgScore, 2);
            }

            $maturityLevels = $t->table('process_maturity_scale_levels as lvl')
                ->join('process_maturity_scales as s', 's.id', '=', 'lvl.scale_id')
                ->select('s.code as scale_code', 's.label as scale_label', 'lvl.level_score', 'lvl.level_label', 'lvl.level_description')
                ->orderBy('s.id')
                ->orderBy('lvl.level_score')
                ->get();

            return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                'user' => $user,
                'link' => $link,
                'processes' => $processes,
                'sessions' => $sessions,
                'maturityLevels' => $maturityLevels,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessEvaluation.index Error', ['message' => $e->getMessage()]);
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function loadEvaluations(Request $request)
    {
        $v = $request->validate(['session_id' => 'required|integer', 'process_id' => 'required|integer']);

        try {
            $t = $this->t();

            $maturityEvals = $t->table('process_session_maturity_evaluations')
                ->where('session_id', $v['session_id'])
                ->where('process_id', $v['process_id'])
                ->get();

            $axisEvals = $t->table('process_session_axis_evaluations')
                ->where('session_id', $v['session_id'])
                ->where('process_id', $v['process_id'])
                ->first();

            $maturityMap = [];
            foreach ($maturityEvals as $eval) {
                $maturityMap[$eval->criterion_code] = $eval->level_score;
            }

            return response()->json(['success' => true, 'maturity' => $maturityMap, 'axes' => $axisEvals ?? (object)[]]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveMaturity(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer',
            'evaluations' => 'required|array',
            'evaluations.*.process_id' => 'required|integer',
            'evaluations.*.criterion_code' => 'required|string',
            'evaluations.*.level_score' => 'required|integer|min:1|max:5',
        ]);

        try {
            $t = $this->t();
            $sessionId = $v['session_id'];

            $session = $t->table('process_evaluation_sessions')->where('id', $sessionId)->first();
            if (!$session || $session->status !== 'open') {
                return response()->json(['error' => 'Session not available'], 403);
            }

            $processId = null;
            foreach ($v['evaluations'] as $eval) {
                $processId = $eval['process_id'];
                $t->table('process_session_maturity_evaluations')->updateOrInsert(
                    ['session_id' => $sessionId, 'process_id' => $processId, 'criterion_code' => $eval['criterion_code']],
                    ['level_score' => $eval['level_score'], 'evaluated_by' => Auth::id(), 'evaluated_at' => now(), 'created_at' => now(), 'updated_at' => now()]
                );
            }

            if ($processId) {
                $this->calculateProcessScores($sessionId, $processId);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveAxis(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer',
            'process_id' => 'required|integer',
            'axis' => 'required|in:motricity,transversality,strategic',
            'score' => 'required|numeric|min:1|max:5',
        ]);

        try {
            $t = $this->t();
            $session = $t->table('process_evaluation_sessions')->where('id', $v['session_id'])->first();

            if (!$session || $session->status !== 'open') {
                return response()->json(['error' => 'Session not available'], 403);
            }

            $field = $v['axis'] . '_score';
            $t->table('process_session_axis_evaluations')->updateOrInsert(
                ['session_id' => $v['session_id'], 'process_id' => $v['process_id']],
                [$field => $v['score'], 'created_at' => now(), 'updated_at' => now()]
            );

            $this->calculateProcessScores($v['session_id'], $v['process_id']);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculateProcessScores($sessionId, $processId)
    {
        try {
            $t = $this->t();

            $maturityAvg = $t->table('process_session_maturity_evaluations')
                ->where('session_id', $sessionId)
                ->where('process_id', $processId)
                ->avg('level_score');

            $t->table('process_session_axis_evaluations')->updateOrInsert(
                ['session_id' => $sessionId, 'process_id' => $processId],
                ['maturity_score' => $maturityAvg ? round($maturityAvg, 2) : null, 'updated_at' => now()]
            );

            $axisEval = $t->table('process_session_axis_evaluations')
                ->where('session_id', $sessionId)
                ->where('process_id', $processId)
                ->first();

            if ($axisEval) {
                $scores = array_filter([
                    $axisEval->maturity_score,
                    $axisEval->motricity_score,
                    $axisEval->transversality_score,
                    $axisEval->strategic_score,
                ]);

                $criticality = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;

                $t->table('process_session_axis_evaluations')
                    ->where('session_id', $sessionId)
                    ->where('process_id', $processId)
                    ->update(['criticality_score' => $criticality, 'updated_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error('CalculateProcessScores Error', ['message' => $e->getMessage()]);
        }
    }

    public function getActiveSession(Request $request)
    {
        try {
            $user = Auth::user();
            $session = $this->t()->table('process_evaluation_sessions')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            return response()->json(['success' => true, 'session' => $session]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 👁️ APERÇU RAPPORT (Preview)
     */
    public function previewReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:single,all',
                'selected_session_id' => 'nullable|integer',
                'selected_processes' => 'required|array|min:1',
            ]);

            $t = $this->t();

            if ($validated['type'] === 'single') {
                $sessions = $t->table('process_evaluation_sessions')
                    ->where('id', $validated['selected_session_id'])
                    ->get();
            } else {
                $sessions = $t->table('process_evaluation_sessions')
                    ->where('status', '!=', 'archived')
                    ->orderByDesc('created_at')
                    ->get();
            }

            if ($sessions->isEmpty()) {
                return response()->json(['error' => 'No sessions found'], 404);
            }

            $reportData = $this->compileReportData($sessions, $validated['selected_processes'], $t);

            $stats = [];
            foreach ($reportData as $sessionId => $processesData) {
                $session = $sessions->find($sessionId);
                $scores = array_column($processesData, 'criticality_score');
                $scores = array_filter($scores, fn($s) => $s > 0);

                $stats[$sessionId] = [
                    'session_name' => $session->name,
                    'session_color' => $session->color,
                    'total_processes' => count($processesData),
                    'evaluated_processes' => count($scores),
                    'avg_score' => count($scores) ? round(array_sum($scores) / count($scores), 2) : 0,
                    'max_score' => count($scores) ? round(max($scores), 2) : 0,
                    'min_score' => count($scores) ? round(min($scores), 2) : 0,
                ];
            }

            $totalEvaluated = collect($stats)->sum('evaluated_processes');
            $totalSlots = count($stats) * count($validated['selected_processes']);
            $completeness = $totalSlots > 0 ? round(($totalEvaluated / $totalSlots) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'preview' => [
                    'type' => $validated['type'],
                    'sessions_count' => $sessions->count(),
                    'processes_count' => count($validated['selected_processes']),
                    'statistics' => $stats,
                    'total_evaluated' => $totalEvaluated,
                    'data_completeness' => $completeness,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Report Preview Error: ' . $e->getMessage());
            return response()->json(['error' => 'Preview error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 📄 EXPORTER EN PDF PROFESSIONNEL
     */
    public function exportReportPDF(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:single,all',
                'selected_session_id' => 'nullable|integer',
                'selected_processes' => 'required|array|min:1',
            ]);

            $t = $this->t();

            if ($validated['type'] === 'single') {
                $sessions = $t->table('process_evaluation_sessions')
                    ->where('id', $validated['selected_session_id'])
                    ->get();
            } else {
                $sessions = $t->table('process_evaluation_sessions')
                    ->where('status', '!=', 'archived')
                    ->orderByDesc('created_at')
                    ->get();
            }

            if ($sessions->isEmpty()) {
                return response()->json(['error' => 'No sessions found'], 404);
            }

            $reportData = $this->compileReportData($sessions, $validated['selected_processes'], $t);
            $pdfPath = $this->generatePDF($sessions, $reportData, $validated, $t);

            if (!file_exists($pdfPath)) {
                return response()->json(['error' => 'PDF not generated'], 500);
            }

            $filename = 'rapport-evaluation-' . now()->format('Y-m-d-Hi') . '.pdf';

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 📊 EXPORTER EN EXCEL PROFESSIONNEL
     */
    public function exportReportExcel(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:single,all',
                'selected_session_id' => 'nullable|integer',
                'selected_processes' => 'required|array|min:1',
            ]);

            $t = $this->t();

            if ($validated['type'] === 'single') {
                $sessions = $t->table('process_evaluation_sessions')
                    ->where('id', $validated['selected_session_id'])
                    ->get();
            } else {
                $sessions = $t->table('process_evaluation_sessions')
                    ->where('status', '!=', 'archived')
                    ->orderByDesc('created_at')
                    ->get();
            }

            if ($sessions->isEmpty()) {
                return response()->json(['error' => 'No sessions found'], 404);
            }

            $reportData = $this->compileReportData($sessions, $validated['selected_processes'], $t);
            $spreadsheet = $this->generateExcel($sessions, $reportData, $validated, $t);

            $writer = new Xlsx($spreadsheet);
            $filename = 'rapport-evaluation-' . now()->format('Y-m-d-Hi') . '.xlsx';
            $path = storage_path('temp/' . $filename);

            @mkdir(dirname($path), 0777, true);
            $writer->save($path);

            if (!file_exists($path)) {
                return response()->json(['error' => 'Excel not generated'], 500);
            }

            return response()->download($path, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Excel Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Export error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🔧 COMPILER LES DONNÉES DU RAPPORT
     */
    private function compileReportData($sessions, $processIds, $t)
    {
        $data = [];

        foreach ($sessions as $session) {
            $data[$session->id] = [];

            foreach ($processIds as $processId) {
                $axisData = $t->table('process_session_axis_evaluations')
                    ->where('session_id', $session->id)
                    ->where('process_id', $processId)
                    ->first();

                $data[$session->id][$processId] = [
                    'maturity_score' => round($axisData->maturity_score ?? 0, 2),
                    'motricity_score' => round($axisData->motricity_score ?? 0, 2),
                    'transversality_score' => round($axisData->transversality_score ?? 0, 2),
                    'strategic_score' => round($axisData->strategic_score ?? 0, 2),
                    'criticality_score' => round($axisData->criticality_score ?? 0, 2),
                ];
            }
        }

        return $data;
    }

    /**
     * 📄 GÉNÉRER PDF
     */
    private function generatePDF($sessions, $reportData, $config, $t)
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(16, 185, 129);
        $pdf->Cell(0, 10, 'RAPPORT D\'EVALUATION DES PROCESSUS', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        $pdf->Cell(0, 4, 'Date: ' . now()->format('d/m/Y H:i'), 0, 1);
        $pdf->Cell(0, 4, 'Utilisateur: ' . Auth::user()->name, 0, 1);
        $pdf->Cell(0, 4, 'Sessions: ' . implode(', ', $sessions->pluck('name')->toArray()), 0, 1);
        $pdf->Cell(0, 4, 'Processus: ' . count($config['selected_processes']), 0, 1);

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, 'SYNTHÈSE DES SCORES', 0, 1);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(16, 185, 129);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(20, 5, 'Session', 1, 0, 'C', true);
        $pdf->Cell(15, 5, 'Proc', 1, 0, 'C', true);
        $pdf->Cell(12, 5, 'Matu', 1, 0, 'C', true);
        $pdf->Cell(12, 5, 'Motr', 1, 0, 'C', true);
        $pdf->Cell(12, 5, 'Tran', 1, 0, 'C', true);
        $pdf->Cell(12, 5, 'Stra', 1, 0, 'C', true);
        $pdf->Cell(12, 5, 'Glob', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($reportData as $sessionId => $processesData) {
            $session = $sessions->find($sessionId);
            foreach ($processesData as $processId => $data) {
                $pdf->Cell(20, 4, substr($session->name, 0, 8), 1, 0, 'L');
                $pdf->Cell(15, 4, $processId, 1, 0, 'L');
                $pdf->Cell(12, 4, $data['maturity_score'], 1, 0, 'R');
                $pdf->Cell(12, 4, $data['motricity_score'], 1, 0, 'R');
                $pdf->Cell(12, 4, $data['transversality_score'], 1, 0, 'R');
                $pdf->Cell(12, 4, $data['strategic_score'], 1, 0, 'R');
                $pdf->Cell(12, 4, $data['criticality_score'], 1, 1, 'R');
            }
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, 'STATISTIQUES', 0, 1);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(16, 185, 129);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(30, 5, 'Session', 1, 0, 'C', true);
        $pdf->Cell(20, 5, 'Moyen', 1, 0, 'C', true);
        $pdf->Cell(20, 5, 'Max', 1, 0, 'C', true);
        $pdf->Cell(20, 5, 'Min', 1, 0, 'C', true);
        $pdf->Cell(20, 5, 'Proc', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($reportData as $sessionId => $processesData) {
            $session = $sessions->find($sessionId);
            $scores = array_column($processesData, 'criticality_score');
            $scores = array_filter($scores, fn($s) => $s > 0);

            $pdf->Cell(30, 4, substr($session->name, 0, 14), 1, 0);
            $pdf->Cell(20, 4, count($scores) ? round(array_sum($scores) / count($scores), 2) : 0, 1, 0, 'R');
            $pdf->Cell(20, 4, count($scores) ? round(max($scores), 2) : 0, 1, 0, 'R');
            $pdf->Cell(20, 4, count($scores) ? round(min($scores), 2) : 0, 1, 0, 'R');
            $pdf->Cell(20, 4, count($scores), 1, 1, 'R');
        }

        $filename = storage_path('temp/rapport-pdf-' . uniqid() . '.pdf');
        @mkdir(dirname($filename), 0777, true);
        $pdf->Output('F', $filename);

        return $filename;
    }

    /**
     * 📊 GÉNÉRER EXCEL
     */
    private function generateExcel($sessions, $reportData, $config, $t)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Résumé');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'RAPPORT D\'EVALUATION DES PROCESSUS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor('10b981');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $row = 3;
        $sheet->setCellValue('A' . $row, 'Date:');
        $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i'));
        $row++;
        $sheet->setCellValue('A' . $row, 'Utilisateur:');
        $sheet->setCellValue('B' . $row, Auth::user()->name);
        $row += 2;

        $headers = ['Session', 'Processus', 'Maturité', 'Motricité', 'Transversalité', 'Stratégique', 'Global'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $idx => $header) {
            $sheet->setCellValue($cols[$idx] . $row, $header);
        }

        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true)->setColor('FFFFFF');
        $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF10b981');

        $row++;

        foreach ($reportData as $sessionId => $processesData) {
            $session = $sessions->find($sessionId);
            foreach ($processesData as $processId => $data) {
                $sheet->setCellValue('A' . $row, $session->name);
                $sheet->setCellValue('B' . $row, $processId);
                $sheet->setCellValue('C' . $row, $data['maturity_score']);
                $sheet->setCellValue('D' . $row, $data['motricity_score']);
                $sheet->setCellValue('E' . $row, $data['transversality_score']);
                $sheet->setCellValue('F' . $row, $data['strategic_score']);
                $sheet->setCellValue('G' . $row, $data['criticality_score']);
                $row++;
            }
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $statsSheet = $spreadsheet->createSheet('Statistiques');
        $statsSheet->setCellValue('A1', 'STATISTIQUES PAR SESSION');
        $statsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->setColor('10b981');

        $row = 3;
        $statsHeaders = ['Session', 'Score Moyen', 'Score Max', 'Score Min', 'Processus'];
        $statsCols = ['A', 'B', 'C', 'D', 'E'];

        foreach ($statsHeaders as $idx => $header) {
            $statsSheet->setCellValue($statsCols[$idx] . $row, $header);
        }

        $statsSheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true)->setColor('FFFFFF');
        $statsSheet->getStyle('A' . $row . ':E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF10b981');

        $row++;

        foreach ($reportData as $sessionId => $processesData) {
            $session = $sessions->find($sessionId);
            $scores = array_column($processesData, 'criticality_score');
            $scores = array_filter($scores, fn($s) => $s > 0);

            $statsSheet->setCellValue('A' . $row, $session->name);
            $statsSheet->setCellValue('B' . $row, count($scores) ? round(array_sum($scores) / count($scores), 2) : 0);
            $statsSheet->setCellValue('C' . $row, count($scores) ? round(max($scores), 2) : 0);
            $statsSheet->setCellValue('D' . $row, count($scores) ? round(min($scores), 2) : 0);
            $statsSheet->setCellValue('E' . $row, count($scores));
            $row++;
        }

        foreach ($statsCols as $col) {
            $statsSheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}