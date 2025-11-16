<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Processus;
use App\Models\Param\ProcessInput;
use App\Models\Param\ProcessOutput;
use App\Models\Param\ProcessResource;
use App\Models\Param\MacroProcessus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProcessusController extends Controller
{
    /**
     * STORE — Création d'un processus avec inputs / outputs / ressources
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'macro_process_id' => ['required', 'integer', 'exists:macro_processes,id'],
            'name'             => ['required', 'string', 'max:255'],
            'inputs'           => ['nullable', 'array'],
            'inputs.*'         => ['string', 'max:255'],
            'outputs'          => ['nullable', 'array'],
            'outputs.*'        => ['string', 'max:255'],
            'resources'        => ['nullable', 'array'],
            'resources.*'      => ['string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($data) {
                $macro = MacroProcessus::lockForUpdate()->findOrFail($data['macro_process_id']);

                // Génération du code
                $code = Processus::nextCodeForMacro($macro);

                // Création du processus
                $process = Processus::create([
                    'macro_process_id' => $macro->id,
                    'name'             => $data['name'],
                    'code'             => $code,
                ]);

                // Données d'entrée
                if (!empty($data['inputs'])) {
                    foreach ($data['inputs'] as $inp) {
                        $trimmed = trim($inp ?? '');
                        if ($trimmed !== '') {
                            ProcessInput::create([
                                'process_id' => $process->id,
                                'label'      => $trimmed,
                            ]);
                        }
                    }
                }

                // Données de sortie
                if (!empty($data['outputs'])) {
                    foreach ($data['outputs'] as $out) {
                        $trimmed = trim($out ?? '');
                        if ($trimmed !== '') {
                            ProcessOutput::create([
                                'process_id' => $process->id,
                                'label'      => $trimmed,
                            ]);
                        }
                    }
                }

                // Ressources
                if (!empty($data['resources'])) {
                    foreach ($data['resources'] as $res) {
                        $trimmed = trim($res ?? '');
                        if ($trimmed !== '') {
                            ProcessResource::create([
                                'process_id' => $process->id,
                                'label'      => $trimmed,
                            ]);
                        }
                    }
                }
            });

            return back()->with('success', 'Processus créé avec succès.');

        } catch (\Exception $e) {
            \Log::error('ProcessusController@store', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Erreur lors de la création du processus.']);
        }
    }

    /**
     * UPDATE — Mise à jour du processus
     */
    public function update(Request $request, Processus $process)
    {
        $data = $request->validate([
            'macro_process_id' => ['required', 'integer', 'exists:macro_processes,id'],
            'code'             => ['required', 'string', 'max:50', Rule::unique('processes', 'code')->ignore($process->id)],
            'name'             => ['required', 'string', 'max:255'],
            'inputs'           => ['nullable', 'array'],
            'inputs.*'         => ['string', 'max:255'],
            'outputs'          => ['nullable', 'array'],
            'outputs.*'        => ['string', 'max:255'],
            'resources'        => ['nullable', 'array'],
            'resources.*'      => ['string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($process, $data) {
                // Mise à jour du processus
                $process->update([
                    'macro_process_id' => $data['macro_process_id'],
                    'code'             => $data['code'],
                    'name'             => $data['name'],
                ]);

                // Suppression des anciennes données
                $process->inputs()->delete();
                $process->outputs()->delete();
                $process->resources()->delete();

                // Réinsertion des entrées
                if (!empty($data['inputs'])) {
                    foreach ($data['inputs'] as $inp) {
                        $trimmed = trim($inp ?? '');
                        if ($trimmed !== '') {
                            $process->inputs()->create(['label' => $trimmed]);
                        }
                    }
                }

                // Réinsertion des sorties
                if (!empty($data['outputs'])) {
                    foreach ($data['outputs'] as $out) {
                        $trimmed = trim($out ?? '');
                        if ($trimmed !== '') {
                            $process->outputs()->create(['label' => $trimmed]);
                        }
                    }
                }

                // Réinsertion des ressources
                if (!empty($data['resources'])) {
                    foreach ($data['resources'] as $res) {
                        $trimmed = trim($res ?? '');
                        if ($trimmed !== '') {
                            $process->resources()->create(['label' => $trimmed]);
                        }
                    }
                }
            });

            return back()->with('success', 'Processus mis à jour.');

        } catch (\Exception $e) {
            \Log::error('ProcessusController@update', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du processus.']);
        }
    }

    /**
     * DESTROY — Suppression du processus
     */
    public function destroy(Processus $process)
    {
        try {
            DB::transaction(function () use ($process) {
                $process->inputs()->delete();
                $process->outputs()->delete();
                $process->resources()->delete();
                $process->delete();
            });

            return back()->with('success', 'Processus supprimé.');

        } catch (\Exception $e) {
            \Log::error('ProcessusController@destroy', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Erreur lors de la suppression du processus.']);
        }
    }
}