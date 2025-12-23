<?php

namespace App\Http\Controllers\Process;

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

class ProcessRaciController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    /**
     * PAGE INDEX - RACI MATRIX
     * ✅ Affiche la session active auto (is_active = 1)
     * ✅ Sélection du processus
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
                return Inertia::render('dashboards/Process/Core/RACI/Index', [
                    'user' => ['id' => $user->id, 'name' => $user->name],
                    'link' => null,
                    'processes' => [],
                    'activeSession' => null,
                    'raciRoles' => []
                ]);
            }

            // ✅ Récupère les processus pour l'entité/fonction
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

            // ✅ Récupère les rôles RACI
            $raciRoles = $t->table('activity_raci_roles')->orderBy('sort')->get(['id', 'code', 'label', 'description']);

            return Inertia::render('dashboards/Process/Core/RACI/Index', [
                'user' => ['id' => $user->id, 'name' => $user->name],
                'link' => [
                    'entity_id' => $link->entity_id,
                    'entity_name' => $link->entity_name,
                    'function_id' => $link->function_id,
                    'function_name' => $link->function_name,
                ],
                'processes' => $processes->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name])->values(),
                'activeSession' => $activeSession,
                'raciRoles' => $raciRoles->map(fn($r) => ['id' => $r->id, 'code' => $r->code, 'label' => $r->label])->values()
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur ProcessRaci.index: ' . $e->getMessage());
            return Inertia::render('dashboards/Process/Core/RACI/Index', [
                'user' => ['id' => Auth::user()?->id, 'name' => Auth::user()?->name],
                'link' => null,
                'processes' => [],
                'activeSession' => null,
                'raciRoles' => []
            ]);
        }
    }

    /**
     * LOAD MATRIX
     * Charge la matrice RACI pour une session + processus
     */
    public function loadMatrix(Request $request)
    {
        try {
            $v = $request->validate([
                'session_id' => 'required|integer',
                'process_id' => 'required|integer'
            ]);

            $t = $this->t();

            // ✅ Vérifie que la session existe
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

            // ✅ Récupère les assignations RACI (table: activity_raci_session_assignments)
            $assignments = $t->table('activity_raci_session_assignments as arsa')
                ->join('activity_raci_roles as arr', 'arr.id', '=', 'arsa.raci_role_id')
                ->where('arsa.session_id', $v['session_id'])
                ->whereIn('arsa.activity_id', $activities->pluck('id'))
                ->select('arsa.activity_id', 'arsa.function_id', 'arr.code')
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
            \Log::error('Erreur loadMatrix: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * SAVE MATRIX
     * Enregistre les assignations RACI
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

            // ✅ Vérifie que la session existe
            $session = $t->table('process_evaluation_sessions')->find($v['session_id']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            // ✅ Supprime les assignations existantes pour cette session + process
            $activityIds = $t->table('activities')->where('process_id', $v['process_id'])->pluck('id');
            $t->table('activity_raci_session_assignments')
                ->where('session_id', $v['session_id'])
                ->whereIn('activity_id', $activityIds)
                ->delete();

            // ✅ Insère les nouvelles assignations
            foreach ($v['assignments'] as $assign) {
                $raciCodes = array_filter(explode(',', $assign['raci_codes'] ?? ''), fn($c) => !empty(trim($c)));

                foreach ($raciCodes as $code) {
                    $code = trim($code);

                    $raciRole = $t->table('activity_raci_roles')->where('code', $code)->first();
                    if ($raciRole) {
                        $t->table('activity_raci_session_assignments')->insert([
                            'session_id' => $v['session_id'],
                            'activity_id' => $assign['activity_id'],
                            'function_id' => $assign['function_id'],
                            'raci_role_id' => $raciRole->id,
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
     * Exporte la matrice RACI en Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $v = $request->validate([
                'session_id' => 'required|integer',
                'process_id' => 'required|integer'
            ]);

            $t = $this->t();

            // ✅ Récupère la session
            $session = $t->table('process_evaluation_sessions')->find($v['session_id']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            // ✅ Récupère données
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

            $assignments = $t->table('activity_raci_session_assignments as arsa')
                ->join('activity_raci_roles as arr', 'arr.id', '=', 'arsa.raci_role_id')
                ->where('arsa.session_id', $v['session_id'])
                ->whereIn('arsa.activity_id', $activities->pluck('id'))
                ->select('arsa.activity_id', 'arsa.function_id', 'arr.code')
                ->get()
                ->groupBy(['activity_id', 'function_id']);

            // ✅ Crée le spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('RACI');

            // Styles
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];

            $cellStyle = [
                'font' => ['size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];

            // Titre
            $sheet->mergeCells('A1:' . chr(65 + count($functions)) . '1');
            $sheet->setCellValue('A1', 'MATRICE RACI — ' . ($process->name ?? ''));
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $row = 3;

            // En-têtes colonnes
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

            // Données
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

                    $cellFillStyle = $cellStyle;
                    if (in_array('R', $codes) && in_array('A', $codes)) {
                        $cellFillStyle['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFF9E6']];
                    } elseif (!empty($codes)) {
                        $cellFillStyle['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF0FFF0']];
                    }

                    $sheet->getStyle($colLetter . $row)->applyFromArray($cellFillStyle);
                    $col++;
                }

                $sheet->getRowDimension($row)->setRowHeight(25);
                $row++;
            }

            // Télécharge
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="RACI_' . ($process->code ?? 'matrix') . '_' . now()->format('Ymd_His') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Log::error('Erreur exportExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}