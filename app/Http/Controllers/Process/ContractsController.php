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

class ContractsController extends Controller
{
    /** INDEX */
    public function index(Request $request)
    {
        $term = trim($request->get('q', ''));

        $contracts = Contract::with([
            'process',
            'creator',
            'elements.assignedUser'
        ])
        ->when($term !== '', function ($q) use ($term) {
            $q->where('title', 'like', "%$term%")
              ->orWhere('description', 'like', "%$term%")
              ->orWhereHas('elements', fn($qq) =>
                    $qq->where('activity', 'like', "%$term%")
                       ->orWhere('input', 'like', "%$term%")
                       ->orWhere('output', 'like', "%$term%"));
        })
        ->orderByDesc('id')
        ->paginate(20);

        return Inertia::render('dashboards/Process/Core/Contracts/Index', [
            'contracts' => $contracts,
            'processes' => Processus::select('id','name')->get(),
            'users'     => \App\Models\User::select('id','name')->get(),
            'filters'   => ['q' => $term]
        ]);
    }

    /** STORE */
    public function store(Request $request)
    {
        $data = $request->validate([
            'process_id' => 'required|exists:processes,id',
            'title'      => 'required|string|max:255',
            'description'=> 'nullable|string',

            'elements'   => 'array',
            'elements.*.input'   => 'nullable|string',
            'elements.*.activity'=> 'nullable|string',
            'elements.*.output'  => 'nullable|string',
            'elements.*.assigned_user_id' => 'nullable|exists:users,id',
            'elements.*.file' => 'nullable|file|max:5000',
        ]);

        DB::transaction(function () use ($data) {

            $contract = Contract::create([
                'process_id' => $data['process_id'],
                'user_id'    => auth()->id(),
                'title'      => $data['title'],
                'description'=> $data['description'] ?? null
            ]);

            foreach ($data['elements'] as $row) {

                $path = null;
                if (!empty($row['file'])) {
                    $path = $row['file']->store('contracts', 'public');
                }

                ContractElement::create([
                    'contract_id' => $contract->id,
                    'input' => $row['input'] ?? null,
                    'activity' => $row['activity'] ?? null,
                    'output' => $row['output'] ?? null,
                    'assigned_user_id' => $row['assigned_user_id'] ?? null,
                    'file_path' => $path
                ]);
            }
        });

        return back()->with('success', 'Contrat créé.');
    }

    /** DELETE */
    public function destroy(Contract $contract)
    {
        foreach ($contract->elements as $el) {
            if ($el->file_path && Storage::disk('public')->exists($el->file_path)) {
                Storage::disk('public')->delete($el->file_path);
            }
        }

        $contract->delete();

        return back()->with('success', 'Supprimé.');
    }
}
