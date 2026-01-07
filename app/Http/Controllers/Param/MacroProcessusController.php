<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\MacroProcessus;
use App\Models\Param\Processus;
use App\Models\Param\ProcessInput;
use App\Models\Param\ProcessOutput;
use App\Models\Param\ProcessResource;
use App\Models\Param\Activite;
use App\Services\MistralMPAAssistant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MacroProcessusController extends Controller
{
    public function index(Request $request)
    {
        $macros = MacroProcessus::orderBy('id', 'asc')
            ->get(['id', 'code', 'name', 'character', 'designation', 'kind']);

        // ✅ Charger les relations pour inputs/outputs/resources
        $processes = Processus::with([
            'macro:id,name',
            'inputs:id,process_id,label',
            'outputs:id,process_id,label',
            'resources:id,process_id,label'
        ])
            ->orderBy('id', 'asc')
            ->get();

        $activities = Activite::with(['process:id,macro_process_id,name', 'process.macro:id,name'])
            ->orderBy('id', 'asc')
            ->get(['id', 'process_id', 'code', 'name', 'description']);

        return Inertia::render('dashboards/Param/Mpa/index', [
            'macros' => $macros,
            'processes' => $processes,
            'activities' => $activities,
        ]);
    }

    public function validateDefaults(Request $request)
    {
        $defaults = [
            ['name' => 'Direction', 'character' => 'D', 'designation' => 'Pilotage / Gouvernance', 'kind' => 'Direction'],
            ['name' => 'Réalisation', 'character' => 'R', 'designation' => 'Cœur de métier', 'kind' => 'Réalisation'],
            ['name' => 'Support', 'character' => 'S', 'designation' => 'Soutien / Support', 'kind' => 'Support'],
        ];

        DB::transaction(function () use ($defaults) {
            foreach ($defaults as $row) {
                $exists = MacroProcessus::where('kind', $row['kind'])->exists();
                if (!$exists) {
                    $code = $row['character'] ?? mb_strtoupper(mb_substr($row['kind'], 0, 1));
                    MacroProcessus::create([
                        'code' => $code,
                        'name' => $row['name'],
                        'character' => $row['character'],
                        'designation' => $row['designation'],
                        'kind' => $row['kind'],
                    ]);
                }
            }
        });

        return back()->with('success', 'Macro-processus par défaut validés.');
    }

    public function update(Request $request, MacroProcessus $macro)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $macro->update($data);
        return back()->with('success', 'Macro renommé.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 🤖 IA ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════

    public function aiSuggestProcessus(Request $request, MistralMPAAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'macro_kind' => 'required|string|in:Direction,Réalisation,Support',
                'macro_name' => 'required|string|min:3'
            ]);

            if (!MistralMPAAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données sensibles'], 400);
            }

            $suggestions = $mistral->suggestProcessus($data);

            if (!is_array($suggestions) || empty($suggestions)) {
                return response()->json(['success' => true, 'processus' => []]);
            }

            return response()->json([
                'success' => true,
                'processus' => $suggestions['processus'] ?? [],
                'source' => 'Mistral'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ aiSuggestProcessus: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiSuggestData(Request $request, MistralMPAAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'processus_name' => 'required|string|min:3',
                'macro_kind' => 'required|string|in:Direction,Réalisation,Support'
            ]);

            if (!MistralMPAAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données sensibles'], 400);
            }

            $suggestions = $mistral->suggestProcessusData($data);

            if (!is_array($suggestions) || empty($suggestions)) {
                return response()->json([
                    'success' => true,
                    'inputs' => [],
                    'outputs' => [],
                    'resources' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'inputs' => $suggestions['inputs'] ?? [],
                'outputs' => $suggestions['outputs'] ?? [],
                'resources' => $suggestions['resources'] ?? [],
                'source' => 'Mistral'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ aiSuggestData: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiSuggestActivites(Request $request, MistralMPAAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'processus_name' => 'required|string|min:3',
                'macro_kind' => 'required|string|in:Direction,Réalisation,Support'
            ]);

            if (!MistralMPAAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données sensibles'], 400);
            }

            $suggestions = $mistral->suggestActivites($data);

            if (!is_array($suggestions) || empty($suggestions)) {
                return response()->json(['success' => true, 'activites' => []]);
            }

            return response()->json([
                'success' => true,
                'activites' => $suggestions['activites'] ?? [],
                'source' => 'Mistral'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ aiSuggestActivites: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ✏️ GESTION PROCESSUS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ CRÉER PROCESSUS AVEC DONNÉES
     */
    public function storeProcessus(Request $request)
    {
        try {
            $validated = $request->validate([
                'macro_process_id' => ['required', 'integer', 'exists:macro_processes,id'],
                'name' => ['required', 'string', 'max:255'],
                'inputs' => ['nullable', 'array'],
                'inputs.*' => ['string', 'max:255'],
                'outputs' => ['nullable', 'array'],
                'outputs.*' => ['string', 'max:255'],
                'resources' => ['nullable', 'array'],
                'resources.*' => ['string', 'max:255'],
            ]);

            DB::transaction(function () use ($validated) {
                // 1️⃣ Créer le processus
                $macro = MacroProcessus::findOrFail($validated['macro_process_id']);
                $count = Processus::where('macro_process_id', $macro->id)->count() + 1;
                $code = 'P' . str_pad($count, 2, '0', STR_PAD_LEFT) . $macro->code;

                $processus = Processus::create([
                    'macro_process_id' => $validated['macro_process_id'],
                    'code' => $code,
                    'name' => $validated['name'],
                ]);

                // 2️⃣ Ajouter les données d'entrée
                if (!empty($validated['inputs'])) {
                    foreach ($validated['inputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ProcessInput::create([
                                'process_id' => $processus->id,
                                'label' => $label
                            ]);
                        }
                    }
                }

                // 3️⃣ Ajouter les données de sortie
                if (!empty($validated['outputs'])) {
                    foreach ($validated['outputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ProcessOutput::create([
                                'process_id' => $processus->id,
                                'label' => $label
                            ]);
                        }
                    }
                }

                // 4️⃣ Ajouter les ressources
                if (!empty($validated['resources'])) {
                    foreach ($validated['resources'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ProcessResource::create([
                                'process_id' => $processus->id,
                                'label' => $label
                            ]);
                        }
                    }
                }
            });

            return back()->with('success', 'Processus créé avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ storeProcessus: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ÉDITER PROCESSUS ET SES DONNÉES
     */
    public function updateProcessus(Request $request, Processus $processus)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'inputs' => ['nullable', 'array'],
                'inputs.*' => ['string', 'max:255'],
                'outputs' => ['nullable', 'array'],
                'outputs.*' => ['string', 'max:255'],
                'resources' => ['nullable', 'array'],
                'resources.*' => ['string', 'max:255'],
            ]);

            DB::transaction(function () use ($processus, $validated) {
                // 1️⃣ Mettre à jour le nom
                $processus->update(['name' => $validated['name']]);

                // 2️⃣ Supprimer les anciennes données
                ProcessInput::where('process_id', $processus->id)->delete();
                ProcessOutput::where('process_id', $processus->id)->delete();
                ProcessResource::where('process_id', $processus->id)->delete();

                // 3️⃣ Ajouter les nouvelles données d'entrée
                if (!empty($validated['inputs'])) {
                    foreach ($validated['inputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ProcessInput::create([
                                'process_id' => $processus->id,
                                'label' => $label
                            ]);
                        }
                    }
                }

                // 4️⃣ Ajouter les nouvelles données de sortie
                if (!empty($validated['outputs'])) {
                    foreach ($validated['outputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ProcessOutput::create([
                                'process_id' => $processus->id,
                                'label' => $label
                            ]);
                        }
                    }
                }

                // 5️⃣ Ajouter les nouvelles ressources
                if (!empty($validated['resources'])) {
                    foreach ($validated['resources'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ProcessResource::create([
                                'process_id' => $processus->id,
                                'label' => $label
                            ]);
                        }
                    }
                }
            });

            return back()->with('success', 'Processus modifié avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ updateProcessus: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * 🗑️ Supprimer un processus
     */
    public function destroyProcessus(Processus $processus)
    {
        try {
            DB::transaction(function () use ($processus) {
                // Supprimer les données associées
                ProcessInput::where('process_id', $processus->id)->delete();
                ProcessOutput::where('process_id', $processus->id)->delete();
                ProcessResource::where('process_id', $processus->id)->delete();
                
                // Supprimer les activités associées
                Activite::where('process_id', $processus->id)->delete();
                
                // Supprimer le processus
                $processus->delete();
            });

            return back()->with('success', 'Processus supprimé.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur suppression: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ✏️ GESTION ACTIVITÉ
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ CRÉER ACTIVITÉ
     */
    public function storeActivite(Request $request)
    {
        try {
            $validated = $request->validate([
                'process_id' => ['required', 'integer', 'exists:processes,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            DB::transaction(function () use ($validated) {
                $process = Processus::findOrFail($validated['process_id']);
                $count = Activite::where('process_id', $process->id)->count() + 1;
                $code = 'A' . str_pad($count, 2, '0', STR_PAD_LEFT) . $process->code;

                Activite::create([
                    'process_id' => $validated['process_id'],
                    'code' => $code,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? '',
                ]);
            });

            return back()->with('success', 'Activité créée avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ storeActivite: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ÉDITER ACTIVITÉ
     */
    public function updateActivite(Request $request, Activite $activite)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            $activite->update($validated);
            return back()->with('success', 'Activité modifiée avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ updateActivite: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * 🗑️ Supprimer une activité
     */
    public function destroyActivite(Activite $activite)
    {
        try {
            $activite->delete();
            return back()->with('success', 'Activité supprimée.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur suppression: ' . $e->getMessage());
        }
    }
}