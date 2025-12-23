<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Bpmn\BpmnDiagram;
use App\Models\Bpmn\BpmnTaskLink;
use App\Models\Bpmn\BpmnSequenceFlow;
use App\Models\Bpmn\BpmnDiagramVersion;
use App\Models\Bpmn\BpmnElementConfig;
use App\Models\Param\Processus;
use App\Models\Param\Activite;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BpmnEditorController extends Controller
{
    // ==================== INDEX ====================
    public function index()
    {
        $processes = Processus::on('tenant')
            ->with(['latestDiagram' => function($q) {
                $q->select('id', 'process_id', 'version', 'created_at');
            }])
            ->withCount(['activities', 'diagrams'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Index', [
            'processes' => $processes,
            'stats' => [
                'total_processes' => Processus::on('tenant')->count(),
                'with_diagrams' => BpmnDiagram::on('tenant')->distinct('process_id')->count(),
                'total_activities' => Activite::on('tenant')->count(),
            ]
        ]);
    }

    // ==================== ÉDITEUR PRINCIPAL ====================
    public function edit($processId)
    {
        try {
            // Récupérer le processus avec ses activités
            $process = Processus::on('tenant')
                ->with(['activities' => function($q) {
                    $q->select('id', 'code', 'name', 'process_id');
                }])
                ->findOrFail($processId);

            // Récupérer ou créer le diagramme courant
            $diagram = BpmnDiagram::on('tenant')
                ->where('process_id', $processId)
                ->where('is_current', true)
                ->first();

            if (!$diagram) {
                $diagram = $this->createInitialDiagram($process);
            }

            // Charger toutes les données liées
            $data = $this->loadDiagramData($diagram);

            return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Editor', [
                'process' => [
                    'id' => $process->id,
                    'code' => $process->code,
                    'name' => $process->name,
                    'activities_count' => $process->activities->count(),
                ],
                'diagram' => [
                    'id' => $diagram->id,
                    'version' => $diagram->version,
                    'created_at' => $diagram->created_at->format('d/m/Y H:i'),
                ],
                'initial_data' => $data,
                'available_activities' => $process->activities,
            ]);

        } catch (\Exception $e) {
            Log::error('BPMN Editor Load Error', [
                'process_id' => $processId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('bpmn.index')
                ->with('error', 'Erreur lors du chargement de l\'éditeur: ' . $e->getMessage());
        }
    }

    // ==================== CRÉATION DIAGRAMME INITIAL ====================
    private function createInitialDiagram(Processus $process)
    {
        return DB::connection('tenant')->transaction(function () use ($process) {
            // Désactiver les anciens diagrammes
            BpmnDiagram::on('tenant')
                ->where('process_id', $process->id)
                ->update(['is_current' => false]);

            // Créer le nouveau diagramme
            $diagram = BpmnDiagram::on('tenant')->create([
                'process_id' => $process->id,
                'bpmn_xml' => $this->getMinimalBpmnXml($process),
                'version' => 1,
                'is_current' => true,
                'created_by' => Auth::id(),
            ]);

            // Créer la version initiale
            BpmnDiagramVersion::on('tenant')->create([
                'bpmn_diagram_id' => $diagram->id,
                'version_number' => 1,
                'bpmn_xml' => $diagram->bpmn_xml,
                'change_description' => 'Création initiale',
                'created_by' => Auth::id(),
            ]);

            Log::info('Nouveau diagramme créé', [
                'process_id' => $process->id,
                'diagram_id' => $diagram->id
            ]);

            return $diagram;
        });
    }

    // ==================== CHARGEMENT DES DONNÉES ====================
    private function loadDiagramData(BpmnDiagram $diagram)
    {
        return [
            'bpmn_xml' => $diagram->bpmn_xml,
            'task_links' => $diagram->taskLinks->map(function ($link) {
                return [
                    'element_id' => $link->element_id,
                    'element_name' => $link->element_name,
                    'element_type' => $link->element_type,
                    'color_hex' => $link->color_hex,
                    'activity_id' => $link->activity_id,
                    'activity_name' => $link->activity_name,
                    'activity_code' => $link->activity_code,
                ];
            })->toArray(),
            
            'sequence_flows' => $diagram->sequenceFlows->map(function ($flow) {
                return [
                    'sequence_id' => $flow->sequence_id,
                    'sequence_name' => $flow->sequence_name,
                    'source_element_id' => $flow->source_element_id,
                    'source_element_name' => $flow->source_element_name,
                    'target_element_id' => $flow->target_element_id,
                    'target_element_name' => $flow->target_element_name,
                    'condition_expression' => $flow->condition_expression,
                ];
            })->toArray(),
            
            'element_configs' => $diagram->elementConfigs->mapWithKeys(function ($config) {
                return [
                    $config->element_id => [
                        'icon_class' => $config->icon_class,
                        'custom_properties' => $config->custom_properties ?: [],
                    ]
                ];
            })->toArray(),
        ];
    }

    // ==================== SAUVEGARDE AUTO ====================
    public function autoSave(Request $request, $diagramId)
    {
        $request->validate([
            'bpmn_xml' => 'required|string',
            'task_links' => 'array',
            'task_links.*.element_id' => 'required|string',
            'task_links.*.activity_id' => 'required|integer',
            'sequence_flows' => 'array',
            'sequence_flows.*.sequence_id' => 'required|string',
            'element_configs' => 'array',
        ]);

        try {
            DB::connection('tenant')->beginTransaction();

            $diagram = BpmnDiagram::on('tenant')->findOrFail($diagramId);
            $userId = Auth::id();

            // 1. Mettre à jour le XML
            $diagram->update(['bpmn_xml' => $request->bpmn_xml]);

            // 2. Synchroniser les liens tâches-activités
            $this->syncTaskLinks($diagram, $request->task_links);

            // 3. Synchroniser les séquence flows
            $this->syncSequenceFlows($diagram, $request->sequence_flows);

            // 4. Synchroniser les configurations d'éléments (pour les icônes)
            $this->syncElementConfigs($diagram, $request->element_configs);

            // 5. Créer une version si significative
            if ($this->isSignificantChange($diagram, $request->all())) {
                $this->createDiagramVersion($diagram, 'Auto-save', $userId);
            }

            DB::connection('tenant')->commit();

            Log::info('Auto-save réussi', [
                'diagram_id' => $diagramId,
                'changes_count' => count($request->task_links ?? []) + count($request->sequence_flows ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sauvegardé',
                'saved_at' => now()->format('H:i:s'),
                'diagram_version' => $diagram->version,
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            Log::error('Auto-save échoué', [
                'diagram_id' => $diagramId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur de sauvegarde: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== SAUVEGARDE MANUELLE ====================
    public function manualSave(Request $request, $diagramId)
    {
        $request->validate([
            'bpmn_xml' => 'required|string',
            'task_links' => 'array',
            'sequence_flows' => 'array',
            'version_description' => 'nullable|string|max:500',
        ]);

        try {
            DB::connection('tenant')->beginTransaction();

            $diagram = BpmnDiagram::on('tenant')->findOrFail($diagramId);
            $userId = Auth::id();

            // 1. Mettre à jour le diagramme
            $diagram->update([
                'bpmn_xml' => $request->bpmn_xml,
                'version' => $diagram->version + 1,
            ]);

            // 2. Synchroniser toutes les données
            $this->syncTaskLinks($diagram, $request->task_links);
            $this->syncSequenceFlows($diagram, $request->sequence_flows);
            $this->syncElementConfigs($diagram, $request->element_configs ?? []);

            // 3. Créer une version avec description
            $this->createDiagramVersion(
                $diagram, 
                $request->version_description ?: 'Sauvegarde manuelle',
                $userId
            );

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Diagramme sauvegardé avec version ' . $diagram->version,
                'new_version' => $diagram->version,
                'version_date' => now()->format('d/m/Y H:i'),
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== MÉTHODES DE SYNCHRONISATION ====================
    private function syncTaskLinks(BpmnDiagram $diagram, array $taskLinks)
    {
        if (empty($taskLinks)) {
            BpmnTaskLink::on('tenant')
                ->where('bpmn_diagram_id', $diagram->id)
                ->delete();
            return;
        }

        $existingIds = $diagram->taskLinks->pluck('element_id')->toArray();
        $newIds = array_column($taskLinks, 'element_id');

        // Supprimer ceux qui n'existent plus
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            BpmnTaskLink::on('tenant')
                ->where('bpmn_diagram_id', $diagram->id)
                ->whereIn('element_id', $toDelete)
                ->delete();
        }

        // Mettre à jour ou créer
        foreach ($taskLinks as $link) {
            BpmnTaskLink::on('tenant')->updateOrCreate(
                [
                    'bpmn_diagram_id' => $diagram->id,
                    'element_id' => $link['element_id'],
                ],
                [
                    'process_id' => $diagram->process_id,
                    'element_name' => $link['element_name'] ?? '',
                    'element_type' => $link['element_type'] ?? 'bpmn:Task',
                    'color_hex' => $link['color_hex'] ?? '#3498DB',
                    'activity_id' => $link['activity_id'],
                    'activity_name' => $link['activity_name'],
                    'activity_code' => $link['activity_code'],
                ]
            );
        }
    }

    private function syncSequenceFlows(BpmnDiagram $diagram, array $sequenceFlows)
    {
        if (empty($sequenceFlows)) {
            BpmnSequenceFlow::on('tenant')
                ->where('bpmn_diagram_id', $diagram->id)
                ->delete();
            return;
        }

        $existingIds = $diagram->sequenceFlows->pluck('sequence_id')->toArray();
        $newIds = array_column($sequenceFlows, 'sequence_id');

        // Supprimer ceux qui n'existent plus
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            BpmnSequenceFlow::on('tenant')
                ->where('bpmn_diagram_id', $diagram->id)
                ->whereIn('sequence_id', $toDelete)
                ->delete();
        }

        // Mettre à jour ou créer
        foreach ($sequenceFlows as $flow) {
            BpmnSequenceFlow::on('tenant')->updateOrCreate(
                [
                    'bpmn_diagram_id' => $diagram->id,
                    'sequence_id' => $flow['sequence_id'],
                ],
                [
                    'process_id' => $diagram->process_id,
                    'sequence_name' => $flow['sequence_name'] ?? '',
                    'source_element_id' => $flow['source_element_id'],
                    'source_element_name' => $flow['source_element_name'] ?? '',
                    'target_element_id' => $flow['target_element_id'],
                    'target_element_name' => $flow['target_element_name'] ?? '',
                    'condition_expression' => $flow['condition_expression'] ?? null,
                ]
            );
        }
    }

    private function syncElementConfigs(BpmnDiagram $diagram, array $elementConfigs)
    {
        if (empty($elementConfigs)) return;

        foreach ($elementConfigs as $elementId => $config) {
            BpmnElementConfig::on('tenant')->updateOrCreate(
                [
                    'bpmn_diagram_id' => $diagram->id,
                    'element_id' => $elementId,
                ],
                [
                    'element_type' => $config['element_type'] ?? 'bpmn:Task',
                    'icon_class' => $config['icon_class'] ?? null,
                    'custom_properties' => json_encode($config['custom_properties'] ?? []),
                ]
            );
        }
    }

    private function createDiagramVersion(BpmnDiagram $diagram, string $description, int $userId)
    {
        BpmnDiagramVersion::on('tenant')->create([
            'bpmn_diagram_id' => $diagram->id,
            'version_number' => $diagram->version,
            'bpmn_xml' => $diagram->bpmn_xml,
            'change_description' => $description,
            'task_links_count' => $diagram->taskLinks()->count(),
            'sequence_flows_count' => $diagram->sequenceFlows()->count(),
            'created_by' => $userId,
        ]);
    }

    private function isSignificantChange(BpmnDiagram $diagram, array $newData): bool
    {
        // Logique pour déterminer si le changement mérite une nouvelle version
        $oldXml = $diagram->bpmn_xml;
        $newXml = $newData['bpmn_xml'] ?? '';
        
        // Si le XML a changé de plus de 5%
        $similarity = similar_text($oldXml, $newXml);
        $changePercent = 100 - ($similarity / max(strlen($oldXml), strlen($newXml)) * 100);
        
        return $changePercent > 5 || 
               !empty($newData['task_links']) || 
               !empty($newData['sequence_flows']);
    }

    // ==================== GESTION DES VERSIONS ====================
    public function versions($diagramId)
    {
        $diagram = BpmnDiagram::on('tenant')->findOrFail($diagramId);
        
        $versions = BpmnDiagramVersion::on('tenant')
            ->where('bpmn_diagram_id', $diagramId)
            ->with('createdBy:id,name,email')
            ->orderBy('version_number', 'desc')
            ->paginate(15);

        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Versions', [
            'diagram' => $diagram,
            'versions' => $versions,
            'process' => $diagram->process,
        ]);
    }

    public function restoreVersion($versionId)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $version = BpmnDiagramVersion::on('tenant')->findOrFail($versionId);
            $diagram = $version->diagram;

            // Restaurer cette version comme version courante
            $newDiagram = BpmnDiagram::on('tenant')->create([
                'process_id' => $diagram->process_id,
                'bpmn_xml' => $version->bpmn_xml,
                'version' => $diagram->version + 1,
                'is_current' => true,
                'created_by' => Auth::id(),
            ]);

            // Désactiver l'ancien diagramme
            $diagram->update(['is_current' => false]);

            // Copier les données associées si disponibles
            $this->copyVersionData($version, $newDiagram);

            DB::connection('tenant')->commit();

            return redirect()
                ->route('bpmn.editor', $diagram->process_id)
                ->with('success', 'Version restaurée avec succès');

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    private function copyVersionData(BpmnDiagramVersion $version, BpmnDiagram $newDiagram)
    {
        // Cette méthode copie les données depuis la version si elles sont stockées
        // dans le JSON de la version (alternative à des tables séparées)
        if ($version->task_links) {
            // Logique pour recréer les task links
        }
        
        if ($version->sequence_flows) {
            // Logique pour recréer les sequence flows
        }
    }

    // ==================== XML PAR DÉFAUT ====================
    private function getMinimalBpmnXml(Processus $process = null)
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

    // ==================== EXPORT/IMPORT ====================
    public function export($diagramId)
    {
        $diagram = BpmnDiagram::on('tenant')->findOrFail($diagramId);
        
        return response($diagram->bpmn_xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="diagram_' . $diagramId . '.bpmn"',
        ]);
    }

    public function import(Request $request, $processId)
    {
        $request->validate([
            'bpmn_file' => 'required|file|mimes:xml,bpmn',
            'version_description' => 'nullable|string|max:500',
        ]);

        try {
            $xmlContent = file_get_contents($request->file('bpmn_file')->path());
            
            // Valider que c'est du XML BPMN valide
            if (!str_contains($xmlContent, 'bpmn:definitions')) {
                throw new \Exception('Le fichier n\'est pas un fichier BPMN valide');
            }

            DB::connection('tenant')->beginTransaction();

            // Désactiver les anciens diagrammes
            BpmnDiagram::on('tenant')
                ->where('process_id', $processId)
                ->update(['is_current' => false]);

            // Créer le nouveau diagramme
            $diagram = BpmnDiagram::on('tenant')->create([
                'process_id' => $processId,
                'bpmn_xml' => $xmlContent,
                'version' => 1,
                'is_current' => true,
                'created_by' => Auth::id(),
            ]);

            // Créer la version
            BpmnDiagramVersion::on('tenant')->create([
                'bpmn_diagram_id' => $diagram->id,
                'version_number' => 1,
                'bpmn_xml' => $xmlContent,
                'change_description' => $request->version_description ?: 'Import depuis fichier',
                'created_by' => Auth::id(),
            ]);

            DB::connection('tenant')->commit();

            return redirect()
                ->route('bpmn.editor', $processId)
                ->with('success', 'Diagramme importé avec succès');

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->with('error', 'Erreur d\'import: ' . $e->getMessage());
        }
    }

    // ==================== SUPPRESSION ====================
    public function destroyDiagram($diagramId)
    {
        try {
            $diagram = BpmnDiagram::on('tenant')->findOrFail($diagramId);
            $processId = $diagram->process_id;
            
            // Si c'est le diagramme courant, en désigner un autre comme courant
            if ($diagram->is_current) {
                $otherDiagram = BpmnDiagram::on('tenant')
                    ->where('process_id', $processId)
                    ->where('id', '!=', $diagramId)
                    ->orderBy('version', 'desc')
                    ->first();
                
                if ($otherDiagram) {
                    $otherDiagram->update(['is_current' => true]);
                }
            }
            
            $diagram->delete();
            
            return redirect()
                ->route('bpmn.editor', $processId)
                ->with('success', 'Diagramme supprimé');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}