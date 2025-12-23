<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Process\ProcessContract;
use App\Models\Tenant\Process\ProcessContractHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Barryvdh\DomPDF\Facade\Pdf;

class ProcessContractController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $t = $this->t();

            $link = $t->table('function_assignments as fa')
                ->join('entities as e', 'e.id', '=', 'fa.entity_id')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->where('fa.user_id', $user->id)
                ->select('fa.entity_id', 'fa.function_id', 'e.name as entity_name', 'f.name as function_name')
                ->first();

            if (!$link) {
                return Inertia::render('dashboards/Process/Core/Contracts/Index', [
                    'user' => $user,
                    'link' => null,
                    'processes' => [],
                    'contracts' => []
                ]);
            }

            $processIds = $t->table('assignment_functions as af')
                ->join('assignments as a', 'a.id', '=', 'af.assignment_id')
                ->where('af.function_id', $link->function_id)
                ->where('a.entity_id', $link->entity_id)
                ->where('a.mpa_type', 'process')
                ->pluck('a.mpa_id');

            $processes = $t->table('processes')
                ->whereIn('id', $processIds)
                ->orderBy('code')
                ->get();

            $contracts = ProcessContract::where('entity_id', $link->entity_id)
                ->where('function_id', $link->function_id)
                ->with('process')
                ->orderByDesc('updated_at')
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'process_id' => $c->process_id,
                        'process_code' => $c->process?->code,
                        'process_name' => $c->process?->name,
                        'owner' => $c->owner,
                        'status' => $c->status,
                        'outputs_count' => count($c->outputs ?? []),
                        'updated_at' => $c->updated_at?->format('d/m/Y H:i'),
                    ];
                });

            return Inertia::render('dashboards/Process/Core/Contracts/Index', [
                'user' => $user,
                'link' => $link,
                'processes' => $processes,
                'contracts' => $contracts
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur index: ' . $e->getMessage());
            return back()->withError('Erreur');
        }
    }

    public function load(Request $request)
    {
        try {
            $v = $request->validate(['process_id' => 'required|integer']);

            $user = Auth::user();
            $t = $this->t();

            $link = $t->table('function_assignments as fa')
                ->where('fa.user_id', $user->id)
                ->first(['entity_id', 'function_id']);

            if (!$link) {
                return response()->json(['success' => false, 'message' => 'Pas d\'accès'], 403);
            }

            $contract = ProcessContract::firstOrCreate(
                [
                    'process_id' => $v['process_id'],
                    'entity_id' => $link->entity_id,
                    'function_id' => $link->function_id,
                ],
                [
                    'status' => 'draft',
                    'user_id' => $user->id
                ]
            );

            $process = $t->table('processes')->where('id', $v['process_id'])->first();

            if (!$process) {
                return response()->json(['success' => false, 'error' => 'Processus non trouvé'], 404);
            }

            $inputs = $t->table('process_inputs')
                ->where('process_id', $v['process_id'])
                ->select('id', 'label')
                ->get()
                ->toArray();

            $outputs = $t->table('process_outputs')
                ->where('process_id', $v['process_id'])
                ->select('id', 'label')
                ->get()
                ->toArray();

            $resources = $t->table('process_resources')
                ->where('process_id', $v['process_id'])
                ->select('id', 'label')
                ->get()
                ->toArray();

            $functions = $t->table('functions')
                ->whereIn('id', $t->table('function_assignments')
                    ->where('entity_id', $link->entity_id)
                    ->pluck('function_id'))
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();

            $contractData = [
                'process' => $process,
                'inputs' => $inputs,
                'outputs' => $contract->outputs ?? $this->initializeOutputs($outputs),
                'resources' => $resources,
                'functions' => $functions,
                'owner' => $contract->owner,
                'purpose' => $contract->purpose,
                'activity_indicators' => $contract->activity_indicators ?? [],
                'performance_indicators' => $contract->performance_indicators ?? [],
                'status' => $contract->status,
            ];

            return response()->json([
                'success' => true,
                'contract_id' => $contract->id,
                'data' => $contractData
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur load: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getFunctionUsers(Request $request)
    {
        try {
            $v = $request->validate([
                'function_ids' => 'required|array',
                'function_ids.*' => 'integer'
            ]);

            $t = $this->t();
            $paramDb = DB::connection('mysql');

            $rows = $t->table('function_assignments as fa')
                ->whereIn('fa.function_id', $v['function_ids'])
                ->select('fa.function_id', 'fa.user_id')
                ->get();

            $userIds = $rows->pluck('user_id')->filter()->unique()->values()->toArray();
            $users = [];
            
            if (!empty($userIds)) {
                $users = $paramDb->table('users')
                    ->whereIn('id', $userIds)
                    ->select('id', 'name', 'email')
                    ->get()
                    ->keyBy('id')
                    ->toArray();
            }

            $usersMap = [];
            foreach ($rows as $row) {
                $functionId = (int)$row->function_id;
                $userId = $row->user_id ? (int)$row->user_id : null;

                if ($userId && isset($users[$userId])) {
                    $user = $users[$userId];
                    $usersMap[$functionId] = [
                        'id' => $userId,
                        'name' => $user->name ?? 'Unknown',
                        'email' => $user->email ?? ''
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'users_map' => $usersMap
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur getFunctionUsers: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $v = $request->validate([
                'contract_id' => 'required|integer',
                'owner' => 'nullable|string|max:255',
                'purpose' => 'nullable|string',
                'outputs' => 'required|array',
                'activity_indicators' => 'nullable|array',
                'performance_indicators' => 'nullable|array'
            ]);

            $user = Auth::user();
            $contract = ProcessContract::findOrFail($v['contract_id']);

            if ($contract->user_id !== $user->id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $contract->update([
                'owner' => $v['owner'],
                'purpose' => $v['purpose'],
                'outputs' => $v['outputs'],
                'activity_indicators' => $v['activity_indicators'] ?? [],
                'performance_indicators' => $v['performance_indicators'] ?? [],
                'status' => 'active'
            ]);

            $contract->logHistory('updated_outputs', 'Contrat mis à jour');

            return response()->json(['success' => true, 'message' => 'Enregistré']);
        } catch (\Exception $e) {
            \Log::error('Erreur save: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function uploadFile(Request $request)
    {
        try {
            $v = $request->validate([
                'contract_id' => 'required|integer',
                'output_id' => 'required|integer',
                'file' => 'required|file|max:10240'
            ]);

            $user = Auth::user();
            $contract = ProcessContract::findOrFail($v['contract_id']);

            if ($contract->user_id !== $user->id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $path = $request->file('file')->store("contracts/{$contract->id}/outputs", 'private');
            $fileName = $request->file('file')->getClientOriginalName();

            $outputs = $contract->outputs ?? [];
            foreach ($outputs as &$output) {
                if ($output['id'] == $v['output_id']) {
                    $output['document_path'] = $path;
                    $output['file_name'] = $fileName;
                    break;
                }
            }

            $contract->update(['outputs' => $outputs]);
            $contract->logHistory('file_uploaded', 'Fichier uploadé: ' . $fileName);

            return response()->json(['success' => true, 'message' => 'Uploadé', 'path' => $path, 'file_name' => $fileName]);
        } catch (\Exception $e) {
            \Log::error('Erreur upload: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function downloadFile(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer', 'output_id' => 'required|integer']);
            $contract = ProcessContract::findOrFail($v['contract_id']);
            $output = collect($contract->outputs)->firstWhere('id', $v['output_id']);

            if (!$output || !isset($output['document_path'])) {
                return response()->json(['success' => false, 'message' => 'Fichier non trouvé'], 404);
            }

            return Storage::disk('private')->download($output['document_path'], $output['file_name'] ?? 'download');
        } catch (\Exception $e) {
            \Log::error('Erreur download: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteFile(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer', 'output_id' => 'required|integer']);
            $user = Auth::user();
            $contract = ProcessContract::findOrFail($v['contract_id']);

            if ($contract->user_id !== $user->id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $outputs = $contract->outputs ?? [];
            $deletedFileName = null;

            foreach ($outputs as &$output) {
                if ($output['id'] == $v['output_id']) {
                    if (isset($output['document_path'])) {
                        Storage::disk('private')->delete($output['document_path']);
                        $deletedFileName = $output['file_name'] ?? null;
                        unset($output['document_path']);
                        unset($output['file_name']);
                    }
                    break;
                }
            }

            $contract->update(['outputs' => $outputs]);
            $contract->logHistory('file_deleted', 'Fichier supprimé: ' . ($deletedFileName ?? ''));

            return response()->json(['success' => true, 'message' => 'Supprimé']);
        } catch (\Exception $e) {
            \Log::error('Erreur delete: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📊 EXPORT EXCEL - DESIGN PROFESSIONNEL PREMIUM
     */
    /**
 * 📊 EXPORT EXCEL PRO - AUTO-AJUSTE LES ESPACES + DESIGN PROFESSIONNEL
 */
/**
 * 📊 EXPORT EXCEL — VERSION PREMIUM PRO AVEC COULEURS
 */
/**
 * 📊 EXPORT EXCEL — VERSION PREMIUM PRO AVEC COULEURS
 */
public function exportExcel(Request $request)
{
    try {
        $v = $request->validate(['contract_id' => 'required|integer']);

        $contract = ProcessContract::findOrFail($v['contract_id']);
        $t = $this->t();
        $process = $t->table('processes')->find($contract->process_id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contrat Interfaces');

        /* =======================================================
         *  🎨 COULEURS PROFESSIONNELLES
         * ======================================================= */
        $colorPrimaryDark = 'FF1F4E78';    // Bleu foncé (headers)
        $colorPrimaryLight = 'FFD9E8F5';   // Bleu très clair (alternance)
        $colorSecondary = 'FFF2F2F2';      // Gris clair (données)
        $colorBorder = 'FF000000';         // Noir
        $colorWhiteText = 'FFFFFFFF';      // Blanc
        $colorBlackText = 'FF000000';      // Noir

        /* =======================================================
         *  🎨 STYLES AVEC COULEURS
         * ======================================================= */
        $borderThin = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Header principal - Bleu foncé + texte blanc
        $headerMain = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $colorWhiteText]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorPrimaryDark]]
        ];

        // Headers de section - Bleu clair + texte noir
        $headerSection = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $colorBlackText]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorSecondary]]
        ];

        // Headers du tableau - Bleu foncé + texte blanc
        $tableHeader = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $colorWhiteText]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorPrimaryDark]]
        ];

        // Cellules normales - Blanc avec alternance
        $cellNormal = [
            'font' => ['size' => 10, 'color' => ['rgb' => $colorBlackText]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true]
        ];

        // Cellules alternées - Bleu très clair
        $cellAlternate = $cellNormal + [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorPrimaryLight]]
        ];

        /* =======================================================
         * LARGEURS COLONNES
         * ======================================================= */
        $widths = [
            'A' => 40,
            'B' => 30,
            'C' => 35,
            'D' => 28,
            'E' => 30
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $row = 1;

        /* =======================================================
         *  🔷 TITRE PRINCIPAL - BLEU FONCÉ
         * ======================================================= */
        $sheet->mergeCells("A1:E1");
        $sheet->setCellValue("A1", "CONTRAT D'INTERFACES");
        $sheet->getStyle("A1:E1")->applyFromArray($headerMain + $borderThin);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $row++;

        /* =======================================================
         *  🔷 ENTÊTES INFORMATIONS - FOND GRIS
         * ======================================================= */
        // Processus
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", "Processus : " . ($process->code ?? '') . " — " . ($process->name ?? ''));
        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->setCellValue("D{$row}", "Propriétaire : " . ($contract->owner ?? ''));
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerSection + $borderThin);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row++;

        // Finalité
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", "Finalité : " . ($contract->purpose ?? ''));
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerSection + $cellNormal + $borderThin);
        $sheet->getRowDimension($row)->setRowHeight(35);
        $row++;

        // Dates
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", "Date création : " . now()->format('d/m/Y'));
        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->setCellValue("D{$row}", "Date mise à jour : " . ($contract->updated_at?->format('d/m/Y') ?? 'Création'));
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerSection + $cellNormal + $borderThin);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row += 2;

        /* =======================================================
         *  🔷 TABLEAU — EN-TÊTE (BLEU FONCÉ)
         * ======================================================= */
        $headers = [
            "A{$row}" => "Données de sortie",
            "B{$row}" => "Utilisateur (Zone saisie)",
            "C{$row}" => "Attentes utilisateurs",
            "D{$row}" => "Acteur (Qui sait faire ?)",
            "E{$row}" => "Documents attachés"
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($tableHeader + $borderThin);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;

        /* =======================================================
         *  🔷 LIGNES DU TABLEAU - ALTERNANCE COULEUR
         * ======================================================= */
        $rowIndex = 0;
        foreach ($contract->outputs ?? [] as $output) {
            $sheet->setCellValue("A{$row}", $output['label'] ?? '');
            $sheet->setCellValue("B{$row}", $output['user_name'] ?? '');
            $sheet->setCellValue("C{$row}", $output['expectations'] ?? '');
            // Afficher la FONCTION de l'acteur, pas son nom
            $sheet->setCellValue("D{$row}", $output['actor_function'] ?? '');
            $sheet->setCellValue("E{$row}", $output['file_name'] ?? '');

            // Alternance: pair = blanc, impair = bleu clair
            if ($rowIndex % 2 == 0) {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($cellNormal + $borderThin);
            } else {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($cellAlternate + $borderThin);
            }

            $sheet->getRowDimension($row)->setRowHeight(25);
            $row++;
            $rowIndex++;
        }

        $row++;

        /* =======================================================
         * 🔷 INDICATEURS - BLEU CLAIR
         * ======================================================= */
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", "Indicateurs d'activité :");
        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->setCellValue("D{$row}", "Indicateurs de performances :");
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerSection + $borderThin);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row++;

        $max = max(count($contract->activity_indicators ?? []), count($contract->performance_indicators ?? []));

        for ($i = 0; $i < $max; $i++) {
            $act = $contract->activity_indicators[$i] ?? '';
            $perf = $contract->performance_indicators[$i] ?? '';

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", $act ? ($i+1).". $act" : "");

            $sheet->mergeCells("D{$row}:E{$row}");
            $sheet->setCellValue("D{$row}", $perf ? ($i+1).". $perf" : "");

            // Alternance pour indicateurs aussi
            if ($i % 2 == 0) {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($cellNormal + $borderThin);
            } else {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($cellAlternate + $borderThin);
            }

            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        /* =======================================================
         * 🔷 EXPORT
         * ======================================================= */
        $filename = 'Contrat_Interfaces_' . ($process->code ?? 'CPEC') . '_' . now()->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    } catch (\Exception $e) {
        \Log::error('Erreur export Excel: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
    }
}

    public function exportPdf(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);
            $contract = ProcessContract::findOrFail($v['contract_id']);
            $t = $this->t();
            $process = $t->table('processes')->find($contract->process_id);

            $html = view('process.contracts.pdf', [
                'contract' => $contract,
                'process' => $process,
                'outputs' => $contract->outputs ?? []
            ])->render();

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'Contrat_' . ($process->code ?? 'contrat') . '_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Erreur export PDF: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getHistory(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);

            $histories = ProcessContractHistory::where('contract_id', $v['contract_id'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'action' => $h->action,
                        'action_label' => $h->action_label,
                        'user_name' => $h->user_name,
                        'description' => $h->description,
                        'file_name' => $h->file_name,
                        'created_at' => $h->created_at?->format('d/m/Y H:i:s')
                    ];
                });

            return response()->json(['success' => true, 'histories' => $histories]);
        } catch (\Exception $e) {
            \Log::error('Erreur getHistory: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getFunctionUser(Request $request)
    {
        try {
            $v = $request->validate(['function_id' => 'required|integer']);
            $t = $this->t();

            $user = $t->table('function_assignments as fa')
                ->where('fa.function_id', $v['function_id'])
                ->select('fa.user_id')
                ->first();

            if (!$user || !$user->user_id) {
                return response()->json(['success' => true, 'user' => null]);
            }

            $paramDb = DB::connection('mysql');
            $userData = $paramDb->table('users')->find($user->user_id, ['id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $userData->id,
                    'name' => $userData->name,
                    'email' => $userData->email
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur getFunctionUser: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function archive(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);
            $contract = ProcessContract::findOrFail($v['contract_id']);
            $contract->archive();

            return response()->json(['success' => true, 'message' => 'Archivé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function restore(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);
            $contract = ProcessContract::findOrFail($v['contract_id']);
            $contract->restore();

            return response()->json(['success' => true, 'message' => 'Restauré']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function initializeOutputs($outputs): array
    {
        if (is_object($outputs)) {
            $outputs = json_decode(json_encode($outputs), true);
        }

        if (!is_array($outputs)) {
            return [];
        }

        return array_map(function($o) {
            $id = is_array($o) ? ($o['id'] ?? null) : ($o->id ?? null);
            $label = is_array($o) ? ($o['label'] ?? '') : ($o->label ?? '');

            return [
                'id' => $id,
                'label' => $label,
                'user_name' => '',
                'expectations' => '',
                'actor' => '',
                'actor_function_id' => null,
                'document_path' => null,
                'file_name' => null
            ];
        }, $outputs);
    }
}