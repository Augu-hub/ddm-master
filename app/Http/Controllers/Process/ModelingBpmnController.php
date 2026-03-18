<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Param\Processus;
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
        $hasBpmnTables = Schema::connection('tenant')->hasTable('bpmn_diagrams');

        $processes = Processus::on('tenant')
            ->with(['activities'])
            ->withCount(['activities'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        if ($hasBpmnTables) {
            foreach ($processes as $process) {
                $process->diagrams_count = DB::connection('tenant')
                    ->table('bpmn_diagrams')
                    ->where('process_id', $process->id)
                    ->where('is_current', 1)
                    ->count();
            }
            $withDiagrams = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('is_current', 1)
                ->distinct('process_id')
                ->count('process_id');
        } else {
            $withDiagrams = 0;
        }

        $stats = [
            'total_processes'  => Processus::on('tenant')->count(),
            'total_activities' => DB::connection('tenant')->table('activities')->count(),
            'with_diagrams'    => $withDiagrams,
        ];

        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Index', [
            'processes'    => $processes,
            'stats'        => $stats,
            'bpmn_enabled' => $hasBpmnTables,
        ]);
    }

    // ==================== CREATE ====================
    public function create()
    {
        $macroProcesses = MacroProcess::on('tenant')->get();

        return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Create', [
            'macroProcesses' => $macroProcesses,
        ]);
    }

    // ==================== STORE ====================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:tenant.processes,code',
        ]);

        try {
            $process = DB::connection('tenant')->transaction(function () use ($request) {
                return Processus::on('tenant')->create([
                    'code'       => $request->code,
                    'name'       => $request->name,
                    'created_by' => Auth::id(),
                ]);
            });

            Log::info('✅ Processus BPMN créé', ['process_id' => $process->id]);

            return redirect()
                ->route('process.core.modeling.bpmn.edit', $process->id)
                ->with('success', 'Processus créé avec succès');

        } catch (\Exception $e) {
            Log::error('❌ Erreur création processus BPMN', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    // ==================== EDIT ====================
    public function edit($processId)
    {
        // Cast explicite en entier dès l'entrée
        $processId = (int) $processId;

        try {
            // ── 1. Charger les activités EN PREMIER pour détecter le problème tôt ──
            $activitiesRaw = DB::connection('tenant')
                ->table('activities')
                ->where('process_id', $processId)
                ->select('id', 'code', 'name', 'description')
                ->orderBy('code')
                ->get();

            Log::info('📋 Activities query result', [
                'process_id'    => $processId,
                'count'         => $activitiesRaw->count(),
                'first_item'    => $activitiesRaw->first(),
                'sql'           => DB::connection('tenant')
                    ->table('activities')
                    ->where('process_id', $processId)
                    ->toSql(),
            ]);

            // Construire le tableau proprement — array_values garantit l'index 0,1,2...
            $activities = array_values(
                $activitiesRaw->map(fn($a) => [
                    'id'          => (int) $a->id,
                    'code'        => (string) ($a->code        ?? ''),
                    'name'        => (string) ($a->name        ?? ''),
                    'description' => (string) ($a->description ?? ''),
                ])->toArray()
            );

            Log::info('📋 Activities après mapping', [
                'count'   => count($activities),
                'sample'  => array_slice($activities, 0, 2),
            ]);

            // ── 2. Charger le processus ──
            $process = Processus::on('tenant')->findOrFail($processId);

            // ── 3. Tables BPMN ──
            $hasBpmnTables = Schema::connection('tenant')->hasTable('bpmn_diagrams');

            $initialData = [
                'bpmn_xml'        => $this->getMinimalBpmnXml($process),
                'task_links'      => [],
                'sequence_flows'  => [],
                'element_configs' => [],
            ];

            $diagram = null;

            if ($hasBpmnTables) {
                $diagram = DB::connection('tenant')
                    ->table('bpmn_diagrams')
                    ->where('process_id', $processId)
                    ->where('is_current', 1)
                    ->first();

                if (!$diagram) {
                    $this->createInitialDiagram($process);
                    $diagram = DB::connection('tenant')
                        ->table('bpmn_diagrams')
                        ->where('process_id', $processId)
                        ->where('is_current', 1)
                        ->first();
                }

                if ($diagram) {
                    $initialData['bpmn_xml'] = $diagram->bpmn_xml;

                    $initialData['task_links'] = DB::connection('tenant')
                        ->table('bpmn_task_links')
                        ->where('bpmn_diagram_id', $diagram->id)
                        ->get()
                        ->map(fn($l) => [
                            'element_id'    => $l->element_id,
                            'element_name'  => $l->element_name,
                            'element_type'  => $l->element_type,
                            'color_hex'     => $l->color_hex,
                            'activity_id'   => $l->activity_id,
                            'activity_name' => $l->activity_name,
                            'activity_code' => $l->activity_code,
                        ])->values()->toArray();

                    $initialData['sequence_flows'] = DB::connection('tenant')
                        ->table('bpmn_sequence_flows')
                        ->where('bpmn_diagram_id', $diagram->id)
                        ->get()
                        ->map(fn($f) => [
                            'sequence_id'          => $f->sequence_id,
                            'sequence_name'        => $f->sequence_name,
                            'source_element_id'    => $f->source_element_id,
                            'source_element_name'  => $f->source_element_name,
                            'target_element_id'    => $f->target_element_id,
                            'target_element_name'  => $f->target_element_name,
                            'condition_expression' => $f->condition_expression ?? null,
                        ])->values()->toArray();

                    $initialData['element_configs'] = DB::connection('tenant')
                        ->table('bpmn_element_configs')
                        ->where('bpmn_diagram_id', $diagram->id)
                        ->get()
                        ->mapWithKeys(fn($c) => [
                            $c->element_id => [
                                'icon_class'        => $c->icon_class,
                                'custom_properties' => json_decode($c->custom_properties ?? '[]', true),
                            ]
                        ])->toArray();
                }
            }

            // ── 4. Rendu Inertia ──
            Log::info('✅ Rendu Inertia BPMN edit', [
                'process_id'         => $processId,
                'activities_count'   => count($activities),
                'diagram_id'         => $diagram->id ?? null,
            ]);

            return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Edit', [
                'process' => [
                    'id'               => $process->id,
                    'code'             => $process->code,
                    'name'             => $process->name,
                    'activities_count' => count($activities),
                ],
                'diagram' => [
                    'id'         => $diagram->id      ?? 0,
                    'version'    => $diagram->version  ?? 1,
                    'created_at' => $diagram->created_at ?? now()->format('d/m/Y H:i'),
                ],
                'initial_data'         => $initialData,
                'available_activities' => $activities,   // ← tableau PHP pur, array_values
                'bpmn_enabled'         => $hasBpmnTables,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur chargement éditeur BPMN', [
                'process_id' => $processId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement : ' . $e->getMessage());
        }
    }

    // ==================== UPDATE ====================
    public function update(Request $request, $processId)
    {
        try {
            $validated = $request->validate([
                'bpmn_xml'       => 'required|string',
                'task_links'     => 'nullable|array',
                'sequence_flows' => 'nullable|array',
            ]);

            DB::connection('tenant')->beginTransaction();

            $process = Processus::on('tenant')->findOrFail($processId);
            $process->update(['bpmn_xml' => $validated['bpmn_xml']]);

            if (Schema::connection('tenant')->hasTable('bpmn_diagrams')) {
                $diagram = DB::connection('tenant')
                    ->table('bpmn_diagrams')
                    ->where('process_id', $processId)
                    ->where('is_current', 1)
                    ->first();

                if (!$diagram) {
                    DB::connection('tenant')->table('bpmn_diagrams')->insert([
                        'process_id'  => $processId,
                        'bpmn_xml'    => $validated['bpmn_xml'],
                        'version'     => 1,
                        'is_current'  => 1,
                        'created_by'  => Auth::id() ?? 1,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                } else {
                    DB::connection('tenant')->table('bpmn_diagrams')
                        ->where('id', $diagram->id)
                        ->update(['bpmn_xml' => $validated['bpmn_xml'], 'updated_at' => now()]);
                }
            }

            DB::connection('tenant')->commit();

            $diagramRow = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('process_id', $processId)
                ->where('is_current', 1)
                ->first();

            return response()->json([
                'success'    => true,
                'message'    => 'Sauvegardé',
                'saved_at'   => now()->format('H:i:s'),
                'diagram_id' => $diagramRow->id ?? null,
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('❌ Erreur update BPMN', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==================== AUTO-SAVE ====================
    public function autoSave(Request $request, $diagramId)
    {
        try {
            if (!Schema::connection('tenant')->hasTable('bpmn_diagrams')) {
                return response()->json(['success' => false, 'message' => 'Système BPMN non configuré'], 500);
            }

            $validated = $request->validate([
                'bpmn_xml'        => 'required|string',
                'task_links'      => 'nullable|array',
                'sequence_flows'  => 'nullable|array',
                'element_configs' => 'nullable|array',
            ]);

            $diagram = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->first();

            if (!$diagram) {
                return response()->json(['success' => false, 'message' => 'Diagramme introuvable'], 404);
            }

            $processId = $diagram->process_id;

            DB::connection('tenant')->beginTransaction();

            DB::connection('tenant')->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->update(['bpmn_xml' => $validated['bpmn_xml'], 'updated_at' => now()]);

            if (isset($validated['task_links'])) {
                DB::connection('tenant')->table('bpmn_task_links')
                    ->where('bpmn_diagram_id', $diagramId)
                    ->delete();

                $now  = now();
                $rows = [];
                foreach ($validated['task_links'] as $link) {
                    $rows[] = [
                        'bpmn_diagram_id' => $diagramId,
                        'process_id'      => $processId,
                        'element_id'      => $link['element_id']    ?? '',
                        'element_name'    => $link['element_name']   ?? '',
                        'element_type'    => $link['element_type']   ?? 'bpmn:Task',
                        'color_hex'       => $link['color_hex']      ?? '#3498DB',
                        'activity_id'     => $link['activity_id']    ?? 0,
                        'activity_name'   => $link['activity_name']  ?? '',
                        'activity_code'   => $link['activity_code']  ?? '',
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::connection('tenant')->table('bpmn_task_links')->insert($rows);
                }
            }

            if (isset($validated['sequence_flows'])) {
                DB::connection('tenant')->table('bpmn_sequence_flows')
                    ->where('bpmn_diagram_id', $diagramId)
                    ->delete();

                $now  = now();
                $rows = [];
                foreach ($validated['sequence_flows'] as $flow) {
                    $rows[] = [
                        'bpmn_diagram_id'      => $diagramId,
                        'process_id'           => $processId,
                        'sequence_id'          => $flow['sequence_id']          ?? '',
                        'sequence_name'        => $flow['sequence_name']         ?? '',
                        'source_element_id'    => $flow['source_element_id']    ?? '',
                        'source_element_name'  => $flow['source_element_name']  ?? '',
                        'target_element_id'    => $flow['target_element_id']    ?? '',
                        'target_element_name'  => $flow['target_element_name']  ?? '',
                        'condition_expression' => $flow['condition_expression']  ?? null,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::connection('tenant')->table('bpmn_sequence_flows')->insert($rows);
                }
            }

            DB::connection('tenant')->commit();

            Log::info('✅ Auto-save réussi', ['diagram_id' => $diagramId]);

            return response()->json([
                'success'  => true,
                'message'  => 'Sauvegardé automatiquement',
                'saved_at' => now()->format('H:i:s'),
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('❌ Auto-save échoué', ['diagram_id' => $diagramId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==================== MANUAL SAVE ====================
    public function manualSave(Request $request, $diagramId)
    {
        try {
            if (!Schema::connection('tenant')->hasTable('bpmn_diagrams')) {
                return response()->json(['success' => false, 'message' => 'Système BPMN non configuré'], 500);
            }

            $validated = $request->validate([
                'bpmn_xml'            => 'required|string',
                'task_links'          => 'nullable|array',
                'sequence_flows'      => 'nullable|array',
                'element_configs'     => 'nullable|array',
                'version_description' => 'nullable|string|max:500',
            ]);

            $diagram = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->first();

            if (!$diagram) {
                return response()->json(['success' => false, 'message' => 'Diagramme introuvable'], 404);
            }

            DB::connection('tenant')->beginTransaction();

            DB::connection('tenant')->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->update(['is_current' => 0]);

            $newId = DB::connection('tenant')->table('bpmn_diagrams')->insertGetId([
                'process_id'  => $diagram->process_id,
                'bpmn_xml'    => $validated['bpmn_xml'],
                'version'     => $diagram->version + 1,
                'is_current'  => 1,
                'description' => $validated['version_description'] ?? 'Sauvegarde manuelle',
                'created_by'  => Auth::id() ?? 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::connection('tenant')->commit();

            Log::info('✅ Sauvegarde manuelle réussie', ['new_diagram_id' => $newId]);

            return response()->json([
                'success'        => true,
                'message'        => 'Nouvelle version créée',
                'new_diagram_id' => $newId,
            ]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('❌ Sauvegarde manuelle échouée', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==================== DESTROY ====================
    public function destroy($processId)
    {
        try {
            Processus::on('tenant')->findOrFail($processId)->delete();
            return redirect()->route('process.core.modeling.bpmn.index')
                ->with('success', 'Processus supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression processus', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    // ==================== GET ACTIVITIES ====================
    public function getActivities($processId)
    {
        try {
            $activities = DB::connection('tenant')
                ->table('activities')
                ->where('process_id', (int) $processId)
                ->select('id', 'code', 'name')
                ->orderBy('code')
                ->get();
            return response()->json($activities->values());
        } catch (\Exception $e) {
            Log::error('❌ getActivities error', ['error' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    // ==================== VERSIONS ====================
    public function versions($diagramId)
    {
        try {
            $diagram = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->first();

            if (!$diagram) {
                return back()->with('error', 'Diagramme introuvable');
            }

            $versions = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('process_id', $diagram->process_id)
                ->orderBy('version', 'desc')
                ->get();

            return Inertia::render('dashboards/Process/Core/Modeling/Bpmn/Versions', [
                'diagram'  => $diagram,
                'versions' => $versions,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ==================== EXPORT ====================
    public function export($diagramId)
    {
        try {
            $diagram = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->where('id', $diagramId)
                ->first();

            if (!$diagram) {
                abort(404);
            }

            return response($diagram->bpmn_xml, 200, [
                'Content-Type'        => 'application/xml',
                'Content-Disposition' => "attachment; filename=\"diagram_{$diagramId}.bpmn\"",
            ]);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    private function createInitialDiagram($process)
    {
        if (!Schema::connection('tenant')->hasTable('bpmn_diagrams')) return;

        DB::connection('tenant')->table('bpmn_diagrams')->insert([
            'process_id'  => $process->id,
            'bpmn_xml'    => $this->getMinimalBpmnXml($process),
            'version'     => 1,
            'is_current'  => 1,
            'created_by'  => Auth::id() ?? 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function getMinimalBpmnXml($process = null)
    {
        $processName = $process ? htmlspecialchars($process->name) : 'Nouveau Processus';
        $processId   = $process ? 'Process_' . $process->id : 'Process_1';

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
  exporter="DIADDEM-BPMN" 
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