<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Process\ProcessContract;
use App\Models\Tenant\Process\ProcessContractHistory;
use App\Models\Tenant\User;
use App\Services\ContractNotificationService;
use App\Services\ContractAISuggestionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class ProcessContractController extends Controller
{
    protected ContractNotificationService $notificationService;
    protected ContractAISuggestionService $aiSuggestionService;

    public function __construct(
        ContractNotificationService $notificationService,
        ContractAISuggestionService $aiSuggestionService
    ) {
        $this->notificationService = $notificationService;
        $this->aiSuggestionService = $aiSuggestionService;
    }

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
                    'contracts' => [],
                    'tenantUsers' => []
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

            $tenantUsers = User::where('entity_id', $link->entity_id)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->toArray();

            return Inertia::render('dashboards/Process/Core/Contracts/Index', [
                'user' => $user,
                'link' => $link,
                'processes' => $processes,
                'contracts' => $contracts,
                'tenantUsers' => $tenantUsers
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur index: ' . $e->getMessage());
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

            // ✅ CRÉER OU CHARGER LE CONTRAT
            $contract = ProcessContract::firstOrCreate(
                [
                    'process_id' => $v['process_id'],
                    'entity_id' => $link->entity_id,
                    'function_id' => $link->function_id,
                ],
                [
                    'status' => 'draft',
                    'user_id' => $user->id,
                    'activity_indicators' => [],
                    'performance_indicators' => []
                ]
            );

            $process = $t->table('processes')->where('id', $v['process_id'])->first();

            if (!$process) {
                return response()->json(['success' => false, 'error' => 'Processus non trouvé'], 404);
            }

            $inputs = $t->table('process_inputs')->where('process_id', $v['process_id'])->select('id', 'label')->get()->toArray();
            $outputs = $t->table('process_outputs')->where('process_id', $v['process_id'])->select('id', 'label')->get()->toArray();
            $resources = $t->table('process_resources')->where('process_id', $v['process_id'])->select('id', 'label')->get()->toArray();

            $functions = $t->table('functions')
                ->whereIn('id', $t->table('function_assignments')->where('entity_id', $link->entity_id)->pluck('function_id'))
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();

            $tenantUsers = User::where('entity_id', $link->entity_id)
                ->select('id', 'name', 'email', 'job_title')
                ->orderBy('name')
                ->get()
                ->toArray();

            return response()->json([
                'success' => true,
                'contract_id' => $contract->id,
                'data' => [
                    'process' => $process,
                    'inputs' => $inputs,
                    'outputs' => $contract->outputs ?? $this->initializeOutputs($outputs),
                    'resources' => $resources,
                    'functions' => $functions,
                    'tenantUsers' => $tenantUsers,
                    'owner' => $contract->owner,
                    'purpose' => $contract->purpose,
                    // ✅ RETOURNER LES ARRAYS D'INDICATEURS
                    'activity_indicators' => $contract->activity_indicators ?? [],
                    'performance_indicators' => $contract->performance_indicators ?? [],
                    'status' => $contract->status,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur load: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getFunctionUsers(Request $request)
    {
        try {
            $v = $request->validate(['function_ids' => 'required|array', 'function_ids.*' => 'integer']);

            $t = $this->t();
            $rows = $t->table('function_assignments as fa')
                ->whereIn('fa.function_id', $v['function_ids'])
                ->select('fa.function_id', 'fa.user_id')
                ->get();

            $userIds = $rows->pluck('user_id')->filter()->unique()->values()->toArray();
            
            $users = !empty($userIds) 
                ? User::whereIn('id', $userIds)->select('id', 'name', 'email', 'job_title')->get()->keyBy('id')->toArray()
                : [];

            $usersMap = [];
            foreach ($rows as $row) {
                $functionId = (int)$row->function_id;
                $userId = $row->user_id ? (int)$row->user_id : null;

                if ($userId && isset($users[$userId])) {
                    $user = $users[$userId];
                    $usersMap[$functionId] = [
                        'id' => $userId,
                        'name' => $user['name'] ?? 'Unknown',
                        'email' => $user['email'] ?? '',
                        'job_title' => $user['job_title'] ?? ''
                    ];
                }
            }

            return response()->json(['success' => true, 'users_map' => $usersMap]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getFunctionUsers: ' . $e->getMessage());
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

            // ✅ SAUVEGARDER LES ARRAYS COMPLETS D'INDICATEURS
            $contract->update([
                'owner' => $v['owner'],
                'purpose' => $v['purpose'],
                'outputs' => $v['outputs'],
                'activity_indicators' => $v['activity_indicators'] ?? [],
                'performance_indicators' => $v['performance_indicators'] ?? [],
                'status' => 'active'
            ]);

            $contract->logHistory('updated_indicators', 
                'Indicateurs mis à jour: ' . count($v['activity_indicators'] ?? []) . 
                ' activité + ' . count($v['performance_indicators'] ?? []) . ' performance');

            return response()->json(['success' => true, 'message' => 'Enregistré']);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur save: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function generateAISuggestions(Request $request)
    {
        try {
            $v = $request->validate([
                'function_id' => 'required|integer',
                'function_name' => 'required|string',
                'process_id' => 'required|integer',
                'process_name' => 'required|string',
                'output_id' => 'required|integer',
                'output_label' => 'required|string',
            ]);

            $result = $this->aiSuggestionService->generateSuggestions(
                $v['function_id'],
                $v['function_name'],
                $v['process_id'],
                $v['process_name'],
                $v['output_id'],
                $v['output_label']
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur génération suggestions IA'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'suggestions' => $result['suggestions'] ?? []
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur generateAISuggestions: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function sendNotification(Request $request)
    {
        try {
            $v = $request->validate([
                'contract_id' => 'required|integer',
                'output_id' => 'required|integer',
                'output_label' => 'required|string',
                'function_id' => 'required|integer',
                'function_name' => 'required|string',
                'user_id' => 'required|integer',
                'user_name' => 'required|string',
                'user_email' => 'required|email',
                'message' => 'nullable|string',
                'expectations' => 'nullable|string',
                'process_code' => 'nullable|string',
                'process_name' => 'nullable|string',
            ]);

            $result = $this->notificationService->sendFunctionNotification($v);

            if ($result['success']) {
                ProcessContractHistory::create([
                    'contract_id' => $v['contract_id'],
                    'action' => 'notification_sent',
                    'action_label' => 'Notification envoyée',
                    'user_name' => Auth::user()->name,
                    'description' => "Notification envoyée à {$v['user_name']} pour la sortie \"{$v['output_label']}\"",
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Notification envoyée',
                    'notification_id' => $result['notification_id'] ?? null,
                ]);
            }

            return response()->json(['success' => false, 'error' => $result['error'] ?? 'Erreur envoi'], 500);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur sendNotification: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getNotifications(Request $request)
    {
        try {
            $user = Auth::user();
            $unreadOnly = $request->get('unread_only', false);
            $result = $this->notificationService->getUserNotifications($user->id, $unreadOnly);

            return response()->json([
                'success' => true,
                'notifications' => $result['notifications'],
                'unread_count' => $result['unread_count']
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getNotifications: ' . $e->getMessage());
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
            $contract->logHistory('file_uploaded', 'Fichier: ' . $fileName);

            return response()->json(['success' => true, 'message' => 'Uploadé', 'path' => $path, 'file_name' => $fileName]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur upload: ' . $e->getMessage());
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
            \Log::error('❌ Erreur download: ' . $e->getMessage());
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
            $contract->logHistory('file_deleted', 'Fichier supprimé');

            return response()->json(['success' => true, 'message' => 'Supprimé']);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur delete: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);

            $contract = ProcessContract::findOrFail($v['contract_id']);
            $t = $this->t();
            $process = $t->table('processes')->find($contract->process_id);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Contrat');

            $sheet->setCellValue('A1', 'CONTRAT D\'INTERFACES');
            $sheet->setCellValue('A2', 'Processus: ' . ($process->code ?? ''));
            $sheet->setCellValue('A3', 'Propriétaire: ' . ($contract->owner ?? ''));

            // ✅ AJOUTER LES INDICATEURS À L'EXPORT
            $row = 5;
            $sheet->setCellValue("A{$row}", '=== INDICATEURS D\'ACTIVITÉ ===');
            $row++;
            foreach ($contract->activity_indicators ?? [] as $indicator) {
                $sheet->setCellValue("A{$row}", $indicator);
                $row++;
            }

            $row++;
            $sheet->setCellValue("A{$row}", '=== INDICATEURS DE PERFORMANCE ===');
            $row++;
            foreach ($contract->performance_indicators ?? [] as $indicator) {
                $sheet->setCellValue("A{$row}", $indicator);
                $row++;
            }

            $row += 2;
            $sheet->setCellValue("A{$row}", 'Sortie');
            $sheet->setCellValue("B{$row}", 'Utilisateur');
            $sheet->setCellValue("C{$row}", 'Attentes');
            $row++;

            foreach ($contract->outputs ?? [] as $output) {
                $sheet->setCellValue("A{$row}", $output['label'] ?? '');
                $sheet->setCellValue("B{$row}", $output['user_name'] ?? '');
                $sheet->setCellValue("C{$row}", $output['expectations'] ?? '');
                $row++;
            }

            $filename = 'Contrat_' . ($process->code ?? 'contrat') . '_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Log::error('❌ Erreur export Excel: '.$e->getMessage());
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

            $html = view('process.core.process.contracts.pdf', [
                'contract' => $contract,
                'process' => $process,
                'outputs' => $contract->outputs ?? [],
                'activity_indicators' => $contract->activity_indicators ?? [],
                'performance_indicators' => $contract->performance_indicators ?? []
            ])->render();

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'Contrat_' . ($process->code ?? 'contrat') . '_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur export PDF: ' . $e->getMessage());
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
                        'created_at' => $h->created_at?->format('d/m/Y H:i:s')
                    ];
                });

            return response()->json(['success' => true, 'histories' => $histories]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur getHistory: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function archive(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);
            $contract = ProcessContract::findOrFail($v['contract_id']);
            
            $contract->update(['status' => 'archived']);
            $contract->logHistory('archived', 'Contrat archivé');

            return response()->json(['success' => true, 'message' => 'Archivé']);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur archive: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function restore(Request $request)
    {
        try {
            $v = $request->validate(['contract_id' => 'required|integer']);
            $contract = ProcessContract::findOrFail($v['contract_id']);
            
            $contract->update(['status' => 'active']);
            $contract->logHistory('restored', 'Contrat restauré');

            return response()->json(['success' => true, 'message' => 'Restauré']);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur restore: ' . $e->getMessage());
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
                'user_id' => null,
                'expectations' => '',
                'actor' => '',
                'actor_function_id' => null,
                'document_path' => null,
                'file_name' => null,
                'aisu' => null
            ];
        }, $outputs);
    }
}