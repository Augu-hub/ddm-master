<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\MacroProcessus;
use App\Models\Param\Processus;
use App\Models\Param\ProcessInput;
use App\Models\Param\ProcessOutput;
use App\Models\Param\ProcessResource;
use App\Models\Param\Objectif;
use App\Models\Param\ObjectifInput;
use App\Models\Param\ObjectifOutput;
use App\Models\Param\ObjectifResource;
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

        // ✅ Charger les relations. Un processus a SOIT ses propres inputs/outputs/resources
        // (mode simple) SOIT des objectifs qui ont eux-mêmes leurs inputs/outputs/resources
        // (mode "programme" / has_objectives = true).
        $processes = Processus::with([
            'macro:id,name',
            'inputs:id,process_id,label',
            'outputs:id,process_id,label',
            'resources:id,process_id,label',
            'objectifs:id,process_id,name,description',
            'objectifs.inputs:id,objectif_id,label',
            'objectifs.outputs:id,objectif_id,label',
            'objectifs.resources:id,objectif_id,label',
        ])
            ->orderBy('id', 'asc')
            ->get();

        $activities = Activite::with([
            'process:id,macro_process_id,name',
            'process.macro:id,name',
            'objectif:id,process_id,name',
            'objectif.process:id,macro_process_id,name',
            'objectif.process.macro:id,name',
        ])
            ->orderBy('id', 'asc')
            ->get(['id', 'process_id', 'objectif_id', 'code', 'name', 'description']);

        $objectifs = Objectif::with([
            'inputs:id,objectif_id,label',
            'outputs:id,objectif_id,label',
            'resources:id,objectif_id,label',
        ])
            ->orderBy('id', 'asc')
            ->get(['id', 'process_id', 'name', 'description', 'type', 'kpi', 'kpi_target', 'kpi_frequency']);

        return Inertia::render('dashboards/Param/Mpa/index', [
            'macros' => $macros,
            'processes' => $processes,
            'activities' => $activities,
            'objectifs' => $objectifs,
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

    /**
     * 📋 Données pour un processus en MODE SIMPLE (pas d'objectifs)
     */
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

    /**
     * 🎯 Suggestions d'OBJECTIFS — déclenché dès que la checkbox "programme" est cochée
     */
    public function aiSuggestObjectifs(Request $request, MistralMPAAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'processus_name' => 'required|string|min:3',
                'macro_kind' => 'required|string|in:Direction,Réalisation,Support'
            ]);

            if (!MistralMPAAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données sensibles'], 400);
            }

            $suggestions = $mistral->suggestObjectifs($data);

            if (!is_array($suggestions) || empty($suggestions)) {
                return response()->json(['success' => true, 'objectifs' => []]);
            }

            return response()->json([
                'success' => true,
                'objectifs' => $suggestions['objectifs'] ?? [],
                'source' => 'Mistral'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ aiSuggestObjectifs: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📋 Données d'entrée/sortie/ressources POUR UN OBJECTIF (mode programme)
     */
    public function aiSuggestObjectifData(Request $request, MistralMPAAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'objectif_name' => 'required|string|min:3',
                'processus_name' => 'required|string|min:3',
                'macro_kind' => 'required|string|in:Direction,Réalisation,Support'
            ]);

            if (!MistralMPAAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données sensibles'], 400);
            }

            $suggestions = $mistral->suggestObjectifData($data);

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
            \Log::error('❌ aiSuggestObjectifData: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎨 Activités pour un OBJECTIF (mode programme) — déclenché quand l'utilisateur
     * choisit un objectif dans l'onglet Activités
     */
    public function aiSuggestActivitesForObjectif(Request $request, MistralMPAAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'objectif_name' => 'required|string|min:3',
                'processus_name' => 'required|string|min:3',
                'macro_kind' => 'required|string|in:Direction,Réalisation,Support'
            ]);

            if (!MistralMPAAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données sensibles'], 400);
            }

            $suggestions = $mistral->suggestActivitesForObjectif($data);

            if (!is_array($suggestions) || empty($suggestions)) {
                return response()->json(['success' => true, 'activites' => []]);
            }

            return response()->json([
                'success' => true,
                'activites' => $suggestions['activites'] ?? [],
                'source' => 'Mistral'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ aiSuggestActivitesForObjectif: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎨 Activités mode simple (processus direct, sans objectifs) — inchangé
     */
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
     * ✅ CRÉER PROCESSUS
     * - Mode simple (has_objectives=false) : inputs/outputs/resources rattachés au processus
     * - Mode programme (has_objectives=true, Réalisation uniquement) : le processus n'a PAS
     *   d'inputs/outputs/resources propres ; ils seront saisis par objectif ensuite.
     */
    public function storeProcessus(Request $request)
    {
        try {
            $validated = $request->validate([
                'macro_process_id' => ['required', 'integer', 'exists:macro_processes,id'],
                'name' => ['required', 'string', 'max:255'],
                'has_objectives' => ['nullable', 'boolean'],
                'inputs' => ['nullable', 'array'],
                'inputs.*' => ['string', 'max:255'],
                'outputs' => ['nullable', 'array'],
                'outputs.*' => ['string', 'max:255'],
                'resources' => ['nullable', 'array'],
                'resources.*' => ['string', 'max:255'],
            ]);

            DB::transaction(function () use ($validated) {
                $macro = MacroProcessus::findOrFail($validated['macro_process_id']);
                $count = Processus::where('macro_process_id', $macro->id)->count() + 1;
                $code = 'P' . str_pad($count, 2, '0', STR_PAD_LEFT) . $macro->code;

                $hasObjectives = $macro->kind === 'Réalisation'
                    ? (bool) ($validated['has_objectives'] ?? false)
                    : false;

                $processus = Processus::create([
                    'macro_process_id' => $validated['macro_process_id'],
                    'code' => $code,
                    'name' => $validated['name'],
                    'has_objectives' => $hasObjectives,
                ]);

                // En mode programme, on IGNORE les inputs/outputs/resources envoyés
                // (le front ne devrait plus les envoyer, mais on sécurise côté serveur aussi)
                if (!$hasObjectives) {
                    if (!empty($validated['inputs'])) {
                        foreach ($validated['inputs'] as $label) {
                            $label = trim($label);
                            if (!empty($label)) {
                                ProcessInput::create(['process_id' => $processus->id, 'label' => $label]);
                            }
                        }
                    }

                    if (!empty($validated['outputs'])) {
                        foreach ($validated['outputs'] as $label) {
                            $label = trim($label);
                            if (!empty($label)) {
                                ProcessOutput::create(['process_id' => $processus->id, 'label' => $label]);
                            }
                        }
                    }

                    if (!empty($validated['resources'])) {
                        foreach ($validated['resources'] as $label) {
                            $label = trim($label);
                            if (!empty($label)) {
                                ProcessResource::create(['process_id' => $processus->id, 'label' => $label]);
                            }
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
     * ✅ ÉDITER PROCESSUS
     */
    public function updateProcessus(Request $request, Processus $processus)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'has_objectives' => ['nullable', 'boolean'],
                'inputs' => ['nullable', 'array'],
                'inputs.*' => ['string', 'max:255'],
                'outputs' => ['nullable', 'array'],
                'outputs.*' => ['string', 'max:255'],
                'resources' => ['nullable', 'array'],
                'resources.*' => ['string', 'max:255'],
            ]);

            DB::transaction(function () use ($processus, $validated) {
                $processus->loadMissing('macro');

                $updateData = ['name' => $validated['name']];
                $isRealisation = $processus->macro && $processus->macro->kind === 'Réalisation';

                if ($isRealisation && array_key_exists('has_objectives', $validated)) {
                    $updateData['has_objectives'] = (bool) $validated['has_objectives'];
                }

                $processus->update($updateData);
                $processus->refresh();

                // On ne touche aux inputs/outputs/resources DU PROCESSUS que s'il n'est pas
                // (ou plus) en mode programme.
                if (!$processus->has_objectives) {
                    ProcessInput::where('process_id', $processus->id)->delete();
                    ProcessOutput::where('process_id', $processus->id)->delete();
                    ProcessResource::where('process_id', $processus->id)->delete();

                    if (!empty($validated['inputs'])) {
                        foreach ($validated['inputs'] as $label) {
                            $label = trim($label);
                            if (!empty($label)) {
                                ProcessInput::create(['process_id' => $processus->id, 'label' => $label]);
                            }
                        }
                    }

                    if (!empty($validated['outputs'])) {
                        foreach ($validated['outputs'] as $label) {
                            $label = trim($label);
                            if (!empty($label)) {
                                ProcessOutput::create(['process_id' => $processus->id, 'label' => $label]);
                            }
                        }
                    }

                    if (!empty($validated['resources'])) {
                        foreach ($validated['resources'] as $label) {
                            $label = trim($label);
                            if (!empty($label)) {
                                ProcessResource::create(['process_id' => $processus->id, 'label' => $label]);
                            }
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
                ProcessInput::where('process_id', $processus->id)->delete();
                ProcessOutput::where('process_id', $processus->id)->delete();
                ProcessResource::where('process_id', $processus->id)->delete();

                Activite::where('process_id', $processus->id)->delete();

                $objectifIds = Objectif::where('process_id', $processus->id)->pluck('id');
                if ($objectifIds->isNotEmpty()) {
                    Activite::whereIn('objectif_id', $objectifIds)->delete();
                    ObjectifInput::whereIn('objectif_id', $objectifIds)->delete();
                    ObjectifOutput::whereIn('objectif_id', $objectifIds)->delete();
                    ObjectifResource::whereIn('objectif_id', $objectifIds)->delete();
                    Objectif::whereIn('id', $objectifIds)->delete();
                }

                $processus->delete();
            });

            return back()->with('success', 'Processus supprimé.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur suppression: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 🎯 GESTION OBJECTIFS (mode programme) + leurs propres inputs/outputs/resources
    // ═══════════════════════════════════════════════════════════════════════

    public function storeObjectif(Request $request)
    {
        try {
            $validated = $request->validate([
                'process_id' => ['required', 'integer', 'exists:processes,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'type' => ['nullable', 'string', 'in:strategique,operationnel'],
                'kpi' => ['nullable', 'string', 'max:255'],
                'kpi_target' => ['nullable', 'string', 'max:100'],
                'kpi_frequency' => ['nullable', 'string', 'in:quotidienne,hebdomadaire,mensuelle,trimestrielle,annuelle'],
                'inputs' => ['nullable', 'array'],
                'inputs.*' => ['string', 'max:255'],
                'outputs' => ['nullable', 'array'],
                'outputs.*' => ['string', 'max:255'],
                'resources' => ['nullable', 'array'],
                'resources.*' => ['string', 'max:255'],
            ]);

            $processus = Processus::findOrFail($validated['process_id']);

            if (!$processus->has_objectives) {
                return back()->with('error', "Ce processus n'est pas en mode Objectifs.");
            }

            DB::transaction(function () use ($validated, $processus) {
                $objectif = Objectif::create([
                    'process_id' => $processus->id,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'type' => $validated['type'] ?? 'operationnel',
                    'kpi' => $validated['kpi'] ?? null,
                    'kpi_target' => $validated['kpi_target'] ?? null,
                    'kpi_frequency' => $validated['kpi_frequency'] ?? null,
                ]);

                if (!empty($validated['inputs'])) {
                    foreach ($validated['inputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ObjectifInput::create(['objectif_id' => $objectif->id, 'label' => $label]);
                        }
                    }
                }

                if (!empty($validated['outputs'])) {
                    foreach ($validated['outputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ObjectifOutput::create(['objectif_id' => $objectif->id, 'label' => $label]);
                        }
                    }
                }

                if (!empty($validated['resources'])) {
                    foreach ($validated['resources'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ObjectifResource::create(['objectif_id' => $objectif->id, 'label' => $label]);
                        }
                    }
                }
            });

            return back()->with('success', 'Objectif créé avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ storeObjectif: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function updateObjectif(Request $request, Objectif $objectif)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'type' => ['nullable', 'string', 'in:strategique,operationnel'],
                'kpi' => ['nullable', 'string', 'max:255'],
                'kpi_target' => ['nullable', 'string', 'max:100'],
                'kpi_frequency' => ['nullable', 'string', 'in:quotidienne,hebdomadaire,mensuelle,trimestrielle,annuelle'],
                'inputs' => ['nullable', 'array'],
                'inputs.*' => ['string', 'max:255'],
                'outputs' => ['nullable', 'array'],
                'outputs.*' => ['string', 'max:255'],
                'resources' => ['nullable', 'array'],
                'resources.*' => ['string', 'max:255'],
            ]);

            DB::transaction(function () use ($objectif, $validated) {
                $objectif->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'type' => $validated['type'] ?? 'operationnel',
                    'kpi' => $validated['kpi'] ?? null,
                    'kpi_target' => $validated['kpi_target'] ?? null,
                    'kpi_frequency' => $validated['kpi_frequency'] ?? null,
                ]);

                ObjectifInput::where('objectif_id', $objectif->id)->delete();
                ObjectifOutput::where('objectif_id', $objectif->id)->delete();
                ObjectifResource::where('objectif_id', $objectif->id)->delete();

                if (!empty($validated['inputs'])) {
                    foreach ($validated['inputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ObjectifInput::create(['objectif_id' => $objectif->id, 'label' => $label]);
                        }
                    }
                }

                if (!empty($validated['outputs'])) {
                    foreach ($validated['outputs'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ObjectifOutput::create(['objectif_id' => $objectif->id, 'label' => $label]);
                        }
                    }
                }

                if (!empty($validated['resources'])) {
                    foreach ($validated['resources'] as $label) {
                        $label = trim($label);
                        if (!empty($label)) {
                            ObjectifResource::create(['objectif_id' => $objectif->id, 'label' => $label]);
                        }
                    }
                }
            });

            return back()->with('success', 'Objectif modifié avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ updateObjectif: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroyObjectif(Objectif $objectif)
    {
        try {
            DB::transaction(function () use ($objectif) {
                Activite::where('objectif_id', $objectif->id)->delete();
                ObjectifInput::where('objectif_id', $objectif->id)->delete();
                ObjectifOutput::where('objectif_id', $objectif->id)->delete();
                ObjectifResource::where('objectif_id', $objectif->id)->delete();
                $objectif->delete();
            });

            return back()->with('success', 'Objectif supprimé.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur suppression: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ✏️ GESTION ACTIVITÉ
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ CRÉER ACTIVITÉ
     * Accepte soit process_id (mode simple), soit objectif_id (mode programme).
     */
    public function storeActivite(Request $request)
    {
        try {
            $validated = $request->validate([
                'process_id' => ['nullable', 'integer', 'exists:processes,id', 'required_without:objectif_id'],
                'objectif_id' => ['nullable', 'integer', 'exists:objectifs,id', 'required_without:process_id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            DB::transaction(function () use ($validated) {
                if (!empty($validated['objectif_id'])) {
                    $objectif = Objectif::with('process')->findOrFail($validated['objectif_id']);
                    $process = $objectif->process;

                    $count = Activite::where('objectif_id', $objectif->id)->count() + 1;
                    $code = 'A' . str_pad($count, 2, '0', STR_PAD_LEFT) . $process->code;

                    Activite::create([
                        'process_id' => $process->id,
                        'objectif_id' => $objectif->id,
                        'code' => $code,
                        'name' => $validated['name'],
                        'description' => $validated['description'] ?? '',
                    ]);
                } else {
                    $process = Processus::findOrFail($validated['process_id']);
                    $count = Activite::where('process_id', $process->id)->whereNull('objectif_id')->count() + 1;
                    $code = 'A' . str_pad($count, 2, '0', STR_PAD_LEFT) . $process->code;

                    Activite::create([
                        'process_id' => $process->id,
                        'objectif_id' => null,
                        'code' => $code,
                        'name' => $validated['name'],
                        'description' => $validated['description'] ?? '',
                    ]);
                }
            });

            return back()->with('success', 'Activité créée avec succès.');

        } catch (\Exception $e) {
            \Log::error('❌ storeActivite: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

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