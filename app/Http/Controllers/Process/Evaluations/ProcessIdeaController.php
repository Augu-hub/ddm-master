<?php

namespace App\Http\Controllers\Process\Evaluations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessIdeaController
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    /**
     * PAGE INDEX - IDEA MATRIX
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $t = $this->t();

            // ✅ Récupère le lien user/entité/fonction
            $link = $t->table('function_assignments as fa')
                ->join('entities as e', 'e.id', '=', 'fa.entity_id')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->where('fa.user_id', $user->id)
                ->select('fa.entity_id', 'fa.function_id', 'e.name as entity_name', 'f.name as function_name')
                ->first();

            if (!$link) {
                return Inertia::render('dashboards/Process/Core/IDEA/Index', [
                    'user' => ['id' => $user->id, 'name' => $user->name],
                    'link' => null,
                    'processes' => [],
                    'activeSession' => null,
                    'ideaRoles' => []
                ]);
            }

            // ✅ Récupère les processus
            $processes = $t->table('assignments as a')
                ->join('assignment_functions as af', 'a.id', '=', 'af.assignment_id')
                ->join('processes as p', 'p.id', '=', 'a.mpa_id')
                ->where('af.function_id', $link->function_id)
                ->where('a.entity_id', $link->entity_id)
                ->where('a.mpa_type', 'process')
                ->select('p.id', 'p.code', 'p.name')
                ->distinct()
                ->orderBy('p.code')
                ->get();

            // ✅ Récupère la SESSION ACTIVE (is_active = 1)
            $activeSession = $t->table('process_evaluation_sessions')
                ->where('entity_id', $link->entity_id)
                ->where('function_id', $link->function_id)
                ->where('is_active', true)
                ->first();

            if ($activeSession) {
                $activeSession = [
                    'id' => $activeSession->id,
                    'name' => $activeSession->name,
                    'color' => $activeSession->color,
                    'status' => $activeSession->status,
                    'created_at' => $activeSession->created_at
                ];
            }

            // ✅ Récupère les rôles IDEA
            $ideaRoles = $t->table('activity_idea_roles')->orderBy('sort')->get(['id', 'code', 'label', 'description']);

            return Inertia::render('dashboards/Process/Core/IDEA/Index', [
                'user' => ['id' => $user->id, 'name' => $user->name],
                'link' => [
                    'entity_id' => $link->entity_id,
                    'entity_name' => $link->entity_name,
                    'function_id' => $link->function_id,
                    'function_name' => $link->function_name,
                ],
                'processes' => $processes->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name])->values(),
                'activeSession' => $activeSession,
                'ideaRoles' => $ideaRoles->map(fn($r) => ['id' => $r->id, 'code' => $r->code, 'label' => $r->label])->values()
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur ProcessIdea.index: ' . $e->getMessage());
            return Inertia::render('dashboards/Process/Core/IDEA/Index', [
                'user' => ['id' => Auth::user()?->id, 'name' => Auth::user()?->name],
                'link' => null,
                'processes' => [],
                'activeSession' => null,
                'ideaRoles' => []
            ]);
        }
    }

    /**
     * LOAD MATRIX
     */
    public function loadMatrix(Request $request)
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

            // ✅ Récupère les activités du processus
            $activities = $t->table('activities')
                ->where('process_id', $v['process_id'])
                ->orderBy('code')
                ->get(['id', 'code', 'name']);

            if ($activities->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No activities found'], 404);
            }

            // ✅ Récupère les fonctions de l'entité
            $functions = $t->table('functions')
                ->whereIn('id', $t->table('function_assignments')
                    ->where('entity_id', $session->entity_id)
                    ->pluck('function_id'))
                ->orderBy('name')
                ->get(['id', 'name']);

            // ✅ Récupère les assignations IDEA
            $assignments = $t->table('activity_idea_session_assignments as aisa')
                ->join('activity_idea_roles as air', 'air.id', '=', 'aisa.idea_role_id')
                ->where('aisa.session_id', $v['session_id'])
                ->whereIn('aisa.activity_id', $activities->pluck('id'))
                ->select('aisa.activity_id', 'aisa.function_id', 'air.code')
                ->get()
                ->groupBy(['activity_id', 'function_id']);

            // ✅ Construit la matrice
            $matrix = [];
            foreach ($activities as $activity) {
                $row = [
                    'activity_id' => $activity->id,
                    'activity_code' => $activity->code,
                    'activity_name' => $activity->name,
                    'assignments' => []
                ];

                foreach ($functions as $function) {
                    $codes = [];
                    if (isset($assignments[$activity->id][$function->id])) {
                        $codes = $assignments[$activity->id][$function->id]->pluck('code')->toArray();
                    }

                    $row['assignments'][] = [
                        'function_id' => $function->id,
                        'code' => implode(',', $codes)
                    ];
                }

                $matrix[] = $row;
            }

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'functions' => $functions,
                'matrix' => $matrix
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur loadMatrix: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * SAVE MATRIX
     */
    public function saveMatrix(Request $request)
    {
        try {
            $v = $request->validate([
                'session_id' => 'required|integer',
                'process_id' => 'required|integer',
                'assignments' => 'required|array'
            ]);

            $user = Auth::user();
            $t = $this->t();

            $session = $t->table('process_evaluation_sessions')->find($v['session_id']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            // ✅ Supprime les assignations existantes
            $activityIds = $t->table('activities')->where('process_id', $v['process_id'])->pluck('id');
            $t->table('activity_idea_session_assignments')
                ->where('session_id', $v['session_id'])
                ->whereIn('activity_id', $activityIds)
                ->delete();

            // ✅ Insère les nouvelles assignations
            foreach ($v['assignments'] as $assign) {
                $ideaCodes = array_filter(explode(',', $assign['idea_codes'] ?? ''), fn($c) => !empty(trim($c)));

                foreach ($ideaCodes as $code) {
                    $code = trim($code);

                    $ideaRole = $t->table('activity_idea_roles')->where('code', $code)->first();
                    if ($ideaRole) {
                        $t->table('activity_idea_session_assignments')->insert([
                            'session_id' => $v['session_id'],
                            'activity_id' => $assign['activity_id'],
                            'function_id' => $assign['function_id'],
                            'idea_role_id' => $ideaRole->id,
                            'created_by_user_id' => $user->id,
                            'created_by_user_name' => $user->name,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => '✅ Matrice enregistrée']);

        } catch (\Exception $e) {
            \Log::error('Erreur saveMatrix: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * EXPORT EXCEL
     */
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
            $activities = $t->table('activities')
                ->where('process_id', $v['process_id'])
                ->orderBy('code')
                ->get();

            $functions = $t->table('functions')
                ->whereIn('id', $t->table('function_assignments')
                    ->where('entity_id', $session->entity_id)
                    ->pluck('function_id'))
                ->orderBy('name')
                ->get();

            $assignments = $t->table('activity_idea_session_assignments as aisa')
                ->join('activity_idea_roles as air', 'air.id', '=', 'aisa.idea_role_id')
                ->where('aisa.session_id', $v['session_id'])
                ->whereIn('aisa.activity_id', $activities->pluck('id'))
                ->select('aisa.activity_id', 'aisa.function_id', 'air.code')
                ->get()
                ->groupBy(['activity_id', 'function_id']);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('IDEA');

            $headerStyle = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF1F4E78']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ];

            $cellStyle = [
                'font' => ['size' => 9],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ];

            $sheet->mergeCells('A1:' . chr(65 + count($functions)) . '1');
            $sheet->setCellValue('A1', 'MATRICE IDEA — ' . ($process->name ?? ''));
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $row = 3;

            $sheet->setCellValue('A' . $row, 'Activités');
            $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
            $sheet->getColumnDimension('A')->setWidth(35);

            $col = 1;
            foreach ($functions as $func) {
                $colLetter = chr(65 + $col);
                $sheet->setCellValue($colLetter . $row, $func->name);
                $sheet->getStyle($colLetter . $row)->applyFromArray($headerStyle);
                $sheet->getColumnDimension($colLetter)->setWidth(15);
                $col++;
            }

            $row++;

            foreach ($activities as $activity) {
                $sheet->setCellValue('A' . $row, $activity->code . "\n" . $activity->name);
                $sheet->getStyle('A' . $row)->applyFromArray($cellStyle);
                $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);

                $col = 1;
                foreach ($functions as $func) {
                    $colLetter = chr(65 + $col);
                    $codes = [];

                    if (isset($assignments[$activity->id][$func->id])) {
                        $codes = $assignments[$activity->id][$func->id]->pluck('code')->toArray();
                    }

                    $code = implode(' | ', $codes);
                    $sheet->setCellValue($colLetter . $row, $code);
                    $sheet->getStyle($colLetter . $row)->applyFromArray($cellStyle);
                    $col++;
                }

                $sheet->getRowDimension($row)->setRowHeight(25);
                $row++;
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="IDEA_' . ($process->code ?? 'matrix') . '_' . now()->format('Ymd_His') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Log::error('Erreur exportExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}