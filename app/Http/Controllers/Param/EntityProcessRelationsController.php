<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\EntityProcessRelation;
use App\Models\Param\Processus;
use App\Models\Param\Entite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntityProcessRelationsController extends Controller
{
    /* LISTE DES ENTITÉS */
    public function entities()
    {
        return inertia('dashboards/Param/Macro/Relations/Entities', [
            'entities' => Entite::on('tenant')
                ->orderBy('name')
                ->get()
        ]);
    }

    /* PAGE RELATIONS POUR ENTITÉ */
    public function index($entityId)
    {
        $entity = Entite::on('tenant')->findOrFail($entityId);

        return inertia('dashboards/Param/Macro/Relations/Index', [
            'entity' => $entity,
            'routes' => [
                'load'      => route('param.projects.macro.relations.load', $entity->id),
                'save'      => route('param.projects.macro.relations.save', $entity->id),
                'processes' => route('param.projects.macro.relations.processes'),
            ]
        ]);
    }

    /* CHARGER RELATIONS */
    public function load($entityId)
    {
        $entity = Entite::on('tenant')->findOrFail($entityId);

        $relations = EntityProcessRelation::on('tenant')
            ->where('entity_id', $entityId)
            ->with('sourceProcess', 'targetProcess')
            ->get();

        return response()->json([
            'relations' => $relations->map(function ($relation) {
                return [
                    'id'                  => $relation->id,
                    'entity_id'           => $relation->entity_id,
                    'source_process_id'   => $relation->source_process_id,
                    'target_process_id'   => $relation->target_process_id,
                    'source_port'         => $relation->source_port ?? 'right',
                    'target_port'         => $relation->target_port ?? 'left',
                    'link_type'           => $relation->link_type ?? 'arrow',
                    'control_x'           => (float) $relation->control_x,
                    'control_y'           => (float) $relation->control_y,
                    'metadata'            => $relation->metadata ?? [],
                    'created_at'          => $relation->created_at,
                    'updated_at'          => $relation->updated_at,
                ];
            })
        ]);
    }

    /* CHARGER PROCESSUS GROUPÉS */
    public function processes()
    {
        $rows = Processus::on('tenant')
            ->with('macro')
            ->orderBy('macro_process_id')
            ->orderBy('code')
            ->get();

        $grouped = [];

        foreach ($rows as $p) {
            $mid = $p->macro_process_id;

            if (!isset($grouped[$mid])) {
                $grouped[$mid] = [
                    'macro' => [
                        'id'   => $p->macro->id,
                        'name' => $p->macro->name,
                        'code' => $p->macro->code,
                        'kind' => $p->macro->kind,
                    ],
                    'items' => []
                ];
            }

            $grouped[$mid]['items'][] = [
                'id'                 => $p->id,
                'code'               => $p->code,
                'name'               => $p->name,
                'macro_process_id'   => $p->macro_process_id,
            ];
        }

        return response()->json([
            'grouped' => $grouped
        ]);
    }

    /* SAUVEGARDER LES RELATIONS */
    public function save(Request $request, $entityId)
    {
        $entity = Entite::on('tenant')->findOrFail($entityId);

        $data = $request->validate([
            'relations'                           => ['required', 'array'],
            'relations.*.source_id'               => ['required', 'integer'],
            'relations.*.target_id'               => ['required', 'integer'],
            'relations.*.source_port'             => ['nullable', 'string', 'in:top,bottom,left,right'],
            'relations.*.target_port'             => ['nullable', 'string', 'in:top,bottom,left,right'],
            'relations.*.link_type'               => ['nullable', 'string', 'in:arrow,double_arrow,dashed,loop,custom'],
            'relations.*.control_x'               => ['nullable', 'numeric'],
            'relations.*.control_y'               => ['nullable', 'numeric'],
        ]);

        DB::connection('tenant')->transaction(function () use ($data, $entityId) {

            foreach ($data['relations'] as $rel) {
                EntityProcessRelation::on('tenant')->updateOrCreate(
                    [
                        'entity_id'         => $entityId,
                        'source_process_id' => $rel['source_id'],
                        'target_process_id' => $rel['target_id'],
                    ],
                    [
                        'source_port'  => $rel['source_port'] ?? 'right',
                        'target_port'  => $rel['target_port'] ?? 'left',
                        'link_type'    => $rel['link_type'] ?? 'arrow',
                        'control_x'    => $rel['control_x'] ?? null,
                        'control_y'    => $rel['control_y'] ?? null,
                        'metadata'     => [
                            'updated_at' => now()->toIso8601String(),
                        ],
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Relations sauvegardées avec succès'
        ]);
    }

    /* SUPPRIMER UNE RELATION */
    public function destroyRelation(Request $request, $entityId)
    {
        $entity = Entite::on('tenant')->findOrFail($entityId);

        $data = $request->validate([
            'source_id' => ['required', 'integer'],
            'target_id' => ['required', 'integer'],
        ]);

        EntityProcessRelation::on('tenant')
            ->where('entity_id', $entityId)
            ->where('source_process_id', $data['source_id'])
            ->where('target_process_id', $data['target_id'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Relation supprimée'
        ]);
    }

    /* OBTENIR LES STATS */
    public function stats($entityId)
    {
        $entity = Entite::on('tenant')->findOrFail($entityId);

        $totalRelations = EntityProcessRelation::on('tenant')
            ->where('entity_id', $entityId)
            ->count();

        $relationsByType = EntityProcessRelation::on('tenant')
            ->where('entity_id', $entityId)
            ->groupBy('link_type')
            ->selectRaw('link_type, count(*) as count')
            ->get()
            ->keyBy('link_type')
            ->map(fn($item) => $item->count)
            ->toArray();

        $relationsByPort = EntityProcessRelation::on('tenant')
            ->where('entity_id', $entityId)
            ->groupBy('source_port')
            ->selectRaw('source_port, count(*) as count')
            ->get()
            ->keyBy('source_port')
            ->map(fn($item) => $item->count)
            ->toArray();

        return response()->json([
            'total_relations'      => $totalRelations,
            'relations_by_type'    => $relationsByType,
            'relations_by_port'    => $relationsByPort,
        ]);
    }

    /* EXPORTER EN JSON */
    public function export($entityId)
    {
        $entity = Entite::on('tenant')->findOrFail($entityId);

        $relations = EntityProcessRelation::on('tenant')
            ->where('entity_id', $entityId)
            ->with('sourceProcess', 'targetProcess')
            ->get();

        $export = [
            'entity' => [
                'id'   => $entity->id,
                'name' => $entity->name,
                'code' => $entity->code ?? null,
            ],
            'export_date'  => now()->toIso8601String(),
            'total_relations' => $relations->count(),
            'relations' => $relations->map(function ($relation) {
                return [
                    'source' => [
                        'id'   => $relation->sourceProcess->id,
                        'code' => $relation->sourceProcess->code,
                        'name' => $relation->sourceProcess->name,
                    ],
                    'target' => [
                        'id'   => $relation->targetProcess->id,
                        'code' => $relation->targetProcess->code,
                        'name' => $relation->targetProcess->name,
                    ],
                    'source_port'  => $relation->source_port,
                    'target_port'  => $relation->target_port,
                    'link_type'    => $relation->link_type,
                    'control'      => [
                        'x' => $relation->control_x,
                        'y' => $relation->control_y,
                    ],
                    'metadata'     => $relation->metadata,
                ];
            })->toArray(),
        ];

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="bpmn_' . $entity->code . '_' . now()->format('Y-m-d') . '.json"');
    }
}