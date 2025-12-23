<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Param\Processus;
use App\Models\Param\Activite;
use App\Models\Param\MacroProcess;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ModelingBpmnController extends Controller
{
    // ==================== INDEX ====================
    public function index()
    {
        // Vérifier si la table bpmn_diagrams existe
        $hasBpmnTables = Schema::connection('tenant')->hasTable('bpmn_diagrams');
        
        // Requête de base
        $processes = Processus::on('tenant')
            ->with(['activities'])
            ->withCount(['activities'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Si les tables BPMN existent, ajouter les diagrammes
        if ($hasBpmnTables) {
            // On va ajouter manuellement les comptes de diagrammes
            foreach ($processes as $process) {
                $process->diagrams_count = DB::connection('tenant')
                    ->table('bpmn_diagrams')
                    ->where('process_id', $process->id)
                    ->where('is_current', 1)
                    ->count();
            }
            
            $stats['with_diagrams'] = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('is_current', 1)
                ->distinct('process_id')
                ->count('process_id');
        } else {
            $stats['with_diagrams'] = 0;
        }
        
        $stats = [
            'total_processes' => Processus::on('tenant')->count(),
            'total_activities' => Activite::on('tenant')->count(),
        ];
        
        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Index', [
            'processes' => $processes,
            'stats' => $stats,
            'bpmn_enabled' => $hasBpmnTables,
        ]);
    }

    // ==================== CREATE ====================
    public function create()
    {
        $macroProcesses = MacroProcess::on('tenant')->get();
        
        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Create', [
            'macroProcesses' => $macroProcesses
        ]);
    }

    // ==================== STORE ====================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:tenant.processes,code',
            'macro_process_id' => 'required|exists:tenant.macro_processes,id',
        ]);

        try {
            $process = DB::connection('tenant')->transaction(function () use ($request) {
                return Processus::on('tenant')->create([
                    'code' => $request->code,
                    'name' => $request->name,
                    'macro_process_id' => $request->macro_process_id,
                    'created_by' => Auth::id(),
                ]);
            });

            Log::info('✅ Processus BPMN créé', ['process_id' => $process->id]);

            return redirect()
                ->route('modeling.bpmn.edit', $process->id)
                ->with('success', 'Processus créé avec succès');

        } catch (\Exception $e) {
            Log::error('❌ Erreur création processus BPMN', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    // ==================== EDIT ====================
   public function edit($processId)
{
    try {
        $process = Processus::on('tenant')
            ->with('activities')
            ->findOrFail($processId);

        // Vérifier si les tables BPMN existent
        $hasBpmnTables = Schema::connection('tenant')->hasTable('bpmn_diagrams');
        
        // Données initiales par défaut
        $initialData = [
            'bpmn_xml' => $this->getMinimalBpmnXml($process),
            'task_links' => [],
            'sequence_flows' => [],
            'element_configs' => [],
        ];
        
        $diagram = null;
        
        // Si les tables existent, charger depuis bpmn_diagrams
        if ($hasBpmnTables) {
            $diagram = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('process_id', $processId)
                ->where('is_current', 1)
                ->first();
            
            if ($diagram) {
                $initialData['bpmn_xml'] = $diagram->bpmn_xml;
                
                // Charger les task links
                $taskLinks = DB::connection('tenant')
                    ->table('bpmn_task_links')
                    ->where('bpmn_diagram_id', $diagram->id)
                    ->get()
                    ->map(function ($link) {
                        return [
                            'element_id' => $link->element_id,
                            'element_name' => $link->element_name,
                            'element_type' => $link->element_type,
                            'color_hex' => $link->color_hex,
                            'activity_id' => $link->activity_id,
                            'activity_name' => $link->activity_name,
                            'activity_code' => $link->activity_code,
                        ];
                    })->toArray();
                
                $initialData['task_links'] = $taskLinks;
                
                // Charger les sequence flows
                $sequenceFlows = DB::connection('tenant')
                    ->table('bpmn_sequence_flows')
                    ->where('bpmn_diagram_id', $diagram->id)
                    ->get()
                    ->map(function ($flow) {
                        return [
                            'sequence_id' => $flow->sequence_id,
                            'sequence_name' => $flow->sequence_name,
                            'source_element_id' => $flow->source_element_id,
                            'source_element_name' => $flow->source_element_name,
                            'target_element_id' => $flow->target_element_id,
                            'target_element_name' => $flow->target_element_name,
                            'condition_expression' => $flow->condition_expression,
                        ];
                    })->toArray();
                
                $initialData['sequence_flows'] = $sequenceFlows;
                
                // Charger les configurations d'éléments
                $elementConfigs = DB::connection('tenant')
                    ->table('bpmn_element_configs')
                    ->where('bpmn_diagram_id', $diagram->id)
                    ->get()
                    ->mapWithKeys(function ($config) {
                        return [
                            $config->element_id => [
                                'icon_class' => $config->icon_class,
                                'custom_properties' => json_decode($config->custom_properties ?? '[]', true),
                            ]
                        ];
                    })->toArray();
                
                $initialData['element_configs'] = $elementConfigs;
            } else {
                // Créer un diagramme initial
                $this->createInitialDiagram($process);
                
                // Recharger le diagramme créé
                $diagram = DB::connection('tenant')
                    ->table('bpmn_diagrams')
                    ->where('process_id', $processId)
                    ->where('is_current', 1)
                    ->first();
            }
        }

        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Edit', [
            'process' => [
                'id' => $process->id,
                'code' => $process->code,
                'name' => $process->name,
                'activities_count' => $process->activities->count(),
            ],
            'diagram' => [
                'id' => $diagram->id ?? 1,
                'version' => $diagram->version ?? 1,
                'created_at' => $diagram->created_at ?? now()->format('d/m/Y H:i'),
            ],
            'initial_data' => $initialData,
            'available_activities' => $process->activities,
            'bpmn_enabled' => $hasBpmnTables,
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Erreur chargement éditeur BPMN', [
            'process_id' => $processId,
            'error' => $e->getMessage(),
        ]);
        
        return back()->with('error', 'Erreur lors du chargement de l\'éditeur: ' . $e->getMessage());
    }
}

    // ==================== UPDATE (Compatibilité ancien système) ====================
    public function update(Request $request, $processId)
    {
        try {
            $validated = $request->validate([
                'bpmn_xml' => 'required|string',
                'task_links' => 'nullable|array',
                'sequence_flows' => 'nullable|array'
            ]);

            DB::connection('tenant')->beginTransaction();

            // Ancien système: mettre à jour directement dans processes
            $process = Processus::on('tenant')->findOrFail($processId);
            $process->update(['bpmn_xml' => $validated['bpmn_xml']]);

            // Vérifier si on utilise le nouveau système
            $hasBpmnTables = Schema::connection('tenant')->hasTable('bpmn_diagrams');
            
            if ($hasBpmnTables) {
                // Utiliser le nouveau système
                $diagram = DB::connection('tenant')
                    ->table('bpmn_diagrams')
                    ->where('process_id', $processId)
                    ->where('is_current', 1)
                    ->first();
                
                if (!$diagram) {
                    $diagramId = DB::connection('tenant')->table('bpmn_diagrams')->insertGetId([
                        'process_id' => $processId,
                        'bpmn_xml' => $validated['bpmn_xml'],
                        'version' => 1,
                        'is_current' => 1,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::connection('tenant')->table('bpmn_diagrams')
                        ->where('id', $diagram->id)
                        ->update([
                            'bpmn_xml' => $validated['bpmn_xml'],
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::connection('tenant')->commit();

            Log::info('✅ Mise à jour BPMN réussie', ['process_id' => $processId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sauvegardé',
                'saved_at' => now()->format('H:i:s'),
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            Log::error('❌ Erreur mise à jour BPMN', [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de sauvegarde: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== AUTO-SAVE (Nouveau système) ====================
    public function autoSave(Request $request, $diagramId)
    {
        try {
            // Vérifier si les tables existent
            if (!Schema::connection('tenant')->hasTable('bpmn_diagrams')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Système BPMN non configuré',
                ], 500);
            }

            $validated = $request->validate([
                'bpmn_xml' => 'required|string',
                'task_links' => 'nullable|array',
                'sequence_flows' => 'nullable|array',
                'element_configs' => 'nullable|array',
            ]);

            DB::connection('tenant')->beginTransaction();

            // 1. Mettre à jour le diagramme
            DB::connection('tenant')->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->update([
                    'bpmn_xml' => $validated['bpmn_xml'],
                    'updated_at' => now(),
                ]);

            // 2. Synchroniser les task links
            if (isset($validated['task_links'])) {
                DB::connection('tenant')->table('bpmn_task_links')
                    ->where('bpmn_diagram_id', $diagramId)
                    ->delete();
                
                foreach ($validated['task_links'] as $link) {
                    DB::connection('tenant')->table('bpmn_task_links')->insert([
                        'bpmn_diagram_id' => $diagramId,
                        'process_id' => DB::connection('tenant')->table('bpmn_diagrams')
                            ->where('id', $diagramId)
                            ->value('process_id'),
                        'element_id' => $link['element_id'] ?? '',
                        'element_name' => $link['element_name'] ?? '',
                        'element_type' => $link['element_type'] ?? 'bpmn:Task',
                        'color_hex' => $link['color_hex'] ?? '#3498DB',
                        'activity_id' => $link['activity_id'] ?? 0,
                        'activity_name' => $link['activity_name'] ?? '',
                        'activity_code' => $link['activity_code'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 3. Synchroniser les sequence flows
            if (isset($validated['sequence_flows'])) {
                DB::connection('tenant')->table('bpmn_sequence_flows')
                    ->where('bpmn_diagram_id', $diagramId)
                    ->delete();
                
                foreach ($validated['sequence_flows'] as $flow) {
                    DB::connection('tenant')->table('bpmn_sequence_flows')->insert([
                        'bpmn_diagram_id' => $diagramId,
                        'process_id' => DB::connection('tenant')->table('bpmn_diagrams')
                            ->where('id', $diagramId)
                            ->value('process_id'),
                        'sequence_id' => $flow['sequence_id'] ?? '',
                        'sequence_name' => $flow['sequence_name'] ?? '',
                        'source_element_id' => $flow['source_element_id'] ?? '',
                        'source_element_name' => $flow['source_element_name'] ?? '',
                        'target_element_id' => $flow['target_element_id'] ?? '',
                        'target_element_name' => $flow['target_element_name'] ?? '',
                        'condition_expression' => $flow['condition_expression'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::connection('tenant')->commit();

            Log::info('✅ Auto-save réussi', ['diagram_id' => $diagramId]);

            return response()->json([
                'success' => true,
                'message' => 'Sauvegardé',
                'saved_at' => now()->format('H:i:s'),
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            Log::error('❌ Auto-save échoué', [
                'diagram_id' => $diagramId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== DESTROY ====================
    public function destroy($processId)
    {
        try {
            Processus::on('tenant')->findOrFail($processId)->delete();
            
            Log::info('✅ Processus BPMN supprimé', ['process_id' => $processId]);
            
            return redirect()->route('modeling.bpmn.index')
                ->with('success', 'Processus supprimé avec succès');
                
        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression processus', [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    // ==================== GET ACTIVITIES ====================
    public function getActivities($processId)
    {
        try {
            Processus::on('tenant')->findOrFail($processId);
            $activities = Activite::on('tenant')
                ->where('process_id', $processId)
                ->select('id', 'code', 'name')
                ->orderBy('code')
                ->get();
            
            return response()->json($activities);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération activités', [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([], 500);
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================
    
    private function createInitialDiagram($process)
{
    // Vérifier si la table existe
    if (!Schema::connection('tenant')->hasTable('bpmn_diagrams')) {
        return;
    }

    DB::connection('tenant')->table('bpmn_diagrams')->insert([
        'process_id' => $process->id,
        'bpmn_xml' => $this->getMinimalBpmnXml($process),
        'version' => 1,
        'is_current' => 1,
        'created_by' => Auth::id() ?? 1, // Utilise l'ID utilisateur sans vérifier la FK
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

    private function getMinimalBpmnXml($process = null)
    {
        $processName = $process ? htmlspecialchars($process->name) : 'Nouveau Processus';
        $processId = $process ? 'Process_' . $process->id : 'Process_1';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions 
  xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" 
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  id="Definitions_1" 
  targetNamespace="http://bpmn.io/schema/bpmn"
  exporter="BPMN-Editor" 
  exporterVersion="1.0">
  
  <bpmn:process id="{$processId}" name="{$processName}" isExecutable="false">
    <bpmn:startEvent id="StartEvent_1" name="Début" />
  </bpmn:process>
  
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="{$processId}">
      <bpmndi:BPMNShape id="BPMNShape_StartEvent_1" bpmnElement="StartEvent_1">
        <dc:Bounds x="150" y="100" width="36" height="36" />
      </bpmndi:BPMNShape>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
  
</bpmn:definitions>
XML;
    }
}