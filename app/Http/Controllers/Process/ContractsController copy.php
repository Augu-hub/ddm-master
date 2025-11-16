<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Process\Contract;
use App\Models\Param\Processus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ContractsController extends Controller
{
    /**
     * INDEX — slow search + processus filtrés du user
     * UNIQUEMENT AVEC LES TABLES EXISTANTES :
     * - processes
     * - contracts
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Recherche lente
        $term = trim($request->get('q', ''));

        $contracts = Contract::with(['process', 'creator'])
            ->when($term !== '', function ($q) use ($term) {
                $q->where('title', 'LIKE', "%$term%")
                  ->orWhere('description', 'LIKE', "%$term%");
            })
            ->orderByDesc('id')
            ->paginate(20);

        // Processus liés au user via les contrats uniquement
        $processes = Processus::whereHas('contracts', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        return Inertia::render('dashboards/Process/Core/Contracts/Index', [
            'contracts' => $contracts,
            'processes' => $processes,
            'filters'   => ['q' => $term],
            'users'     => \App\Models\User::select('id','name')->get(),
        ]);
    }

    /**
     * STORE — création d’un contrat
     * ENREGISTRE inputs / outputs / resources en JSON
     * PAS DE TABLE SUPPLÉMENTAIRE
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'process_id'   => 'required|exists:processes,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',

            // Données d’entrée, sortie, ressources (JSON)
            'inputs'       => 'nullable|array',
            'outputs'      => 'nullable|array',
            'resources'    => 'nullable|array',

            // fichiers attachés (JSON)
            'files'        => 'nullable|array',
        ]);

        // Upload des fichiers si présents
        if (!empty($data['files'])) {
            foreach ($data['files'] as $key => $file) {
                if (is_file($file)) {
                    $data['files'][$key] = $file->store('contracts');
                }
            }
        }

        $data['user_id'] = auth()->id();

        Contract::create($data);

        return back()->with('success', 'Contrat créé.');
    }

    /**
     * DELETE — suppression d’un contrat
     * supprime les fichiers JSON associés
     */
    public function destroy(Contract $contract)
    {
        // Supprimer les fichiers attachés
        if (is_array($contract->files)) {
            foreach ($contract->files as $file) {
                if ($file && Storage::exists($file)) {
                    Storage::delete($file);
                }
            }
        }

        $contract->delete();

        return back()->with('success', 'Contrat supprimé.');
    }
}
