<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Process\Contract;
use App\Models\Tenant\Process\ContractElement;
use App\Models\Param\Processus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class ContractsController extends Controller
{
    /**
     * ========== INDEX: LISTER LES CONTRATS ==========
     * GET /m/process.core/contract
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('search', '');
            
            $contracts = Contract::with('process:id,name')
                ->when($search, function ($query) use ($search) {
                    $query->where('title', 'LIKE', "%$search%")
                          ->orWhere('description', 'LIKE', "%$search%");
                })
                ->orderByDesc('id')
                ->paginate(12)
                ->through(function ($contract) {
                    return [
                        'id'         => $contract->id,
                        'title'      => $contract->title,
                        'process'    => $contract->process?->name ?? '—',
                        'elements'   => $contract->elements_count ?? 0,
                        'created_at' => $contract->created_at?->format('d/m/Y H:i'),
                        'updated_at' => $contract->updated_at?->format('d/m/Y H:i'),
                    ];
                });

            return Inertia::render('dashboards/Process/Core/Contracts/Index', [
                'contracts' => $contracts,
                'search'    => $search,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Erreur index contrats', ['message' => $e->getMessage()]);
            return back()->withErrors('Erreur lors du chargement');
        }
    }

    /**
     * ========== CREATE: AFFICHER LE FORMULAIRE DE CRÉATION ==========
     * GET /m/process.core/contract/create
     */
    public function create()
    {
        try {
            $processes = Processus::select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                ]);

            return Inertia::render('dashboards/Process/Core/Contracts/Create', [
                'processes' => $processes,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Erreur create form', ['message' => $e->getMessage()]);
            return back()->withErrors('Erreur lors du chargement');
        }
    }

    /**
     * ========== SHOW: AFFICHER UN CONTRAT ==========
     * GET /m/process.core/contract/{contract}
     */
    public function show(Contract $contract)
    {
        try {
            $contract->load('process', 'elements');

            $rows = $contract->elements->map(fn($el) => [
                'output'      => $el->output,
                'user'        => $el->user,
                'expectation' => $el->expectation,
                'actor_user'  => $el->actor_user,
                'file_name'   => $el->file_name,
                'document'    => $el->file_path ? asset('storage/' . $el->file_path) : null,
            ])->toArray();

            return Inertia::render('dashboards/Process/Core/Contracts/Show', [
                'contract' => [
                    'id'         => $contract->id,
                    'title'      => $contract->title,
                    'created_at' => $contract->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $contract->updated_at?->format('d/m/Y H:i'),
                    'process'    => $contract->process?->name ?? '—',
                    'purpose'    => $contract->process?->purpose ?? '—',
                    'owner'      => auth()->user()?->name ?? '—',
                    'rows'       => $rows,
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Erreur show contrat', ['message' => $e->getMessage()]);
            return back()->withErrors('Contrat non trouvé');
        }
    }

    /**
     * ========== STORE: CRÉER UN CONTRAT ==========
     * POST /m/process.core/contract/store
     */
    public function store(Request $request)
    {
        \Log::info('=== STORE CONTRAT ===', [
            'process_id' => $request->input('process_id'),
            'rows_count' => count($request->input('rows', []))
        ]);

        try {
            // VALIDATION
            $validated = $request->validate([
                'process_id'         => 'required|integer',
                'entity_id'          => 'nullable|integer',
                'function_id'        => 'nullable|integer',
                'rows'               => 'required|array|min:1',
                'rows.*.output'      => 'required|string|max:255',
                'rows.*.user'        => 'nullable|string|max:255',
                'rows.*.expectation' => 'nullable|string',
                'rows.*.actor_user'  => 'nullable|string|max:255',
            ]);

            DB::connection('tenant')->beginTransaction();

            // CRÉER LE CONTRAT
            $contract = Contract::create([
                'process_id'  => $validated['process_id'],
                'entity_id'   => $validated['entity_id'] ?? null,
                'function_id' => $validated['function_id'] ?? null,
                'user_id'     => auth()->id(),
                'title'       => 'Contrat d\'interfaces #' . time(),
            ]);

            \Log::info('Contrat créé', ['id' => $contract->id]);

            // AJOUTER LES ÉLÉMENTS
            foreach ($validated['rows'] as $row) {
                ContractElement::create([
                    'contract_id' => $contract->id,
                    'output'      => $row['output'],
                    'user'        => $row['user'] ?? null,
                    'expectation' => $row['expectation'] ?? null,
                    'function'    => $validated['function_id'] ?? null,
                    'actor_user'  => $row['actor_user'] ?? null,
                    'file_path'   => null,
                    'file_name'   => null,
                ]);
            }

            DB::connection('tenant')->commit();

            \Log::info('Contrat complètement créé', ['id' => $contract->id]);

            return response()->json([
                'success'     => true,
                'message'     => 'Contrat créé avec succès',
                'contract_id' => $contract->id,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Validation échouée', $e->errors());

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Erreur création contrat', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ========== DESTROY: SUPPRIMER UN CONTRAT ==========
     * DELETE /m/process.core/contract/{contract}
     */
    public function destroy(Contract $contract)
    {
        try {
            foreach ($contract->elements as $element) {
                if ($element->file_path && Storage::disk('public')->exists($element->file_path)) {
                    Storage::disk('public')->delete($element->file_path);
                }
            }

            $contract->delete();

            return back()->with('success', 'Contrat supprimé avec succès.');

        } catch (\Throwable $e) {
            \Log::error('Erreur suppression contrat', ['message' => $e->getMessage()]);
            return back()->withErrors('Erreur lors de la suppression');
        }
    }

    /* ============================================
       API ENDPOINTS
       ============================================ */

    /**
     * ========== API: CHARGER LES INTERFACES D'UN PROCESSUS ==========
     * GET /m/process.core/api/process/{processusId}/interfaces
     */
    public function getInterfaces($processusId): JsonResponse
    {
        try {
            $process = Processus::with('activities')
                ->findOrFail($processusId);

            $activities = $process->activities()->get();

            $formatted = $activities->map(function ($activity) {
                return [
                    'id'         => $activity->id,
                    'name'       => $activity->name,
                    'output'     => $activity->name,
                    'code'       => $activity->code ?? '',
                    'function'   => $activity->function ?? '—',
                    'user'       => null,
                    'actor_user' => $activity->actor_user ?? null,
                ];
            })->values()->toArray();

            return response()->json([
                'success'    => true,
                'activities' => $formatted,
                'message'    => 'Données chargées avec succès'
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Erreur API getInterfaces', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success'    => false,
                'message'    => 'Erreur: ' . $e->getMessage(),
                'activities' => []
            ], 500);
        }
    }

    /**
     * ========== API: CHARGER LES DONNÉES EN FONCTION DU PROCESSUS & FONCTION ==========
     * GET /m/process.core/api/process/{processId}/contract-data
     * Query params: entity_id, function_id
     */
    public function getContractData($processId, Request $request): JsonResponse
    {
        try {
            $entityId = $request->query('entity_id');
            $functionId = $request->query('function_id');

            $process = Processus::with('activities')
                ->findOrFail($processId);

            // RÉCUPÉRER LES ACTIVITÉS
            $activities = $process->activities()
                ->when($functionId, function ($q) use ($functionId) {
                    $q->where('function_id', $functionId);
                })
                ->get();

            // FORMATER
            $rows = $activities->map(function ($activity) {
                return [
                    'id'            => $activity->id,
                    'label'         => $activity->name,
                    'type'          => 'output',
                    'user'          => '',
                    'expectation'   => '',
                    'actor'         => $activity->actor_user ?? '—',
                    'document'      => null,
                    'document_name' => null,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'rows'    => $rows,
                'message' => 'Données chargées'
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Erreur API getContractData', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'rows'    => []
            ], 500);
        }
    }

    /**
     * ========== API: CHARGER LES ENTITÉS ==========
     * GET /m/process.core/api/entities
     */
    public function getEntities(): JsonResponse
    {
        try {
            $entities = DB::connection('tenant')
                ->table('entities')
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();

            return response()->json([
                'success'  => true,
                'entities' => $entities
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success'  => false,
                'message'  => $e->getMessage(),
                'entities' => []
            ], 500);
        }
    }

    /**
     * ========== API: CHARGER LES FONCTIONS POUR UNE ENTITÉ ==========
     * GET /m/process.core/api/functions?entity_id={entity_id}
     */
    public function getFunctions(Request $request): JsonResponse
    {
        try {
            $entityId = $request->query('entity_id');

            $functions = DB::connection('tenant')
                ->table('function_assignments')
                ->join('functions', 'functions.id', '=', 'function_assignments.function_id')
                ->where('function_assignments.entity_id', $entityId)
                ->select('functions.id', 'functions.name')
                ->orderBy('functions.name')
                ->distinct()
                ->get()
                ->toArray();

            return response()->json([
                'success'   => true,
                'functions' => $functions
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'functions' => []
            ], 500);
        }
    }
}