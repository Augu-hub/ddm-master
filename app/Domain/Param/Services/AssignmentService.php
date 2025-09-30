<?php

namespace App\Domain\Param\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function fetchTreeByProject(?int $projectId = null): array
    {
        if (!$projectId) return [];

        $macros = DB::table('macro_processes')
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id','code','name']);

        if ($macros->isEmpty()) return [];

        $macroIds  = $macros->pluck('id');
        $processes = DB::table('processes')
            ->whereIn('macro_process_id', $macroIds)
            ->orderBy('name')
            ->get(['id','macro_process_id','code','name']);

        $processIds = $processes->pluck('id');
        $activities = DB::table('activities')
            ->whereIn('process_id', $processIds)
            ->orderBy('name')
            ->get(['id','process_id','code','name']);

        $actsByProc   = $activities->groupBy('process_id');
        $procsByMacro = $processes->groupBy('macro_process_id');

        $tree = [];
        foreach ($macros as $m) {
            $mNode = [
                'id'   => (int)$m->id,
                'code' => $m->code,
                'name' => $m->name,
                'children' => [],
            ];

            foreach ($procsByMacro[$m->id] ?? [] as $p) {
                $pNode = [
                    'id'   => (int)$p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'children' => [],
                ];

                foreach ($actsByProc[$p->id] ?? [] as $a) {
                    $pNode['children'][] = [
                        'id'   => (int)$a->id,
                        'code' => $a->code,
                        'name' => $a->name,
                    ];
                }

                $mNode['children'][] = $pNode;
            }

            $tree[] = $mNode;
        }

        return $tree;
    }

    public function currentForEntity(int $entityId, ?int $projectId = null): array
    {
        $base = DB::table('assignments')
            ->where('entity_id', $entityId)
            ->where('mpa_type', 'activity');

        if ($projectId) {
            $base->whereIn('mpa_id', $this->activityIdsByProject($projectId));
        }

        $activityIds = $base->pluck('mpa_id');

        if ($activityIds->isEmpty()) {
            return ['activity_ids' => [], 'activities' => []];
        }

        $activities = DB::table('activities')
            ->whereIn('id', $activityIds)
            ->orderBy('name')
            ->get(['id','name']);

        return [
            'activity_ids' => $activityIds->map(fn($v)=>(int)$v)->values()->all(),
            'activities'   => $activities->map(fn($a)=>['id'=>(int)$a->id,'name'=>$a->name])->all(),
        ];
    }

    public function preview(int $entityId, array $nodes, int $projectId): array
    {
        $activityIds = collect();
        $idsByType = ['macro'=>collect(), 'process'=>collect(), 'activity'=>collect()];

        foreach ($nodes as $n) {
            $idsByType[$n['type']]->push((int)$n['id']);
        }

        // Activités directes (toujours filtrées par projet)
        if ($idsByType['activity']->isNotEmpty()) {
            $activityIds = $activityIds->merge(
                DB::table('activities')
                    ->join('processes','activities.process_id','=','processes.id')
                    ->join('macro_processes','processes.macro_process_id','=','macro_processes.id')
                    ->where('macro_processes.project_id', $projectId)
                    ->whereIn('activities.id', $idsByType['activity'])
                    ->pluck('activities.id')
            );
        }

        // Activités des process
        if ($idsByType['process']->isNotEmpty()) {
            $activityIds = $activityIds->merge(
                DB::table('activities')
                    ->join('processes','activities.process_id','=','processes.id')
                    ->join('macro_processes','processes.macro_process_id','=','macro_processes.id')
                    ->where('macro_processes.project_id', $projectId)
                    ->whereIn('activities.process_id', $idsByType['process'])
                    ->pluck('activities.id')
            );
        }

        // Activités des macros
        if ($idsByType['macro']->isNotEmpty()) {
            $procIds = DB::table('processes')
                ->whereIn('macro_process_id', $idsByType['macro'])
                ->pluck('id');

            if ($procIds->isNotEmpty()) {
                $activityIds = $activityIds->merge(
                    DB::table('activities')
                        ->join('processes','activities.process_id','=','processes.id')
                        ->join('macro_processes','processes.macro_process_id','=','macro_processes.id')
                        ->where('macro_processes.project_id', $projectId)
                        ->whereIn('activities.process_id', $procIds)
                        ->pluck('activities.id')
                );
            }
        }

        $activityIds = $activityIds->unique()->values()->map(fn($v)=>(int)$v);

        return ['count'=>$activityIds->count(), 'activity_ids'=>$activityIds->all()];
    }

    public function commit(int $entityId, array $activityIds, int $projectId, bool $replace = false): array
    {
        $now = now();

        // IDs valides pour le projet
        $validIds = $this->activityIdsByProject($projectId);
        $ids = collect($activityIds)->map(fn($v)=>(int)$v)->unique()->values();

        // Filtrer pour ne garder que les activités du projet
        $ids = $ids->intersect($validIds);

        if ($ids->isEmpty()) {
            return ['inserted'=>0, 'replaced'=>$replace, 'filtered'=>true];
        }

        return DB::transaction(function () use ($entityId, $ids, $replace, $projectId, $now) {
            if ($replace) {
                // Supprimer TOUTES les affectations d'activités de CE projet pour l'entité
                DB::table('assignments')
                    ->where('entity_id', $entityId)
                    ->where('mpa_type', 'activity')
                    ->whereIn('mpa_id', $this->activityIdsByProject($projectId))
                    ->delete();
            }

            $rows = $ids->map(fn($aid)=>[
                'entity_id'  => $entityId,
                'mpa_type'   => 'activity',
                'mpa_id'     => $aid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows) {
                DB::table('assignments')->upsert(
                    $rows,
                    ['entity_id','mpa_type','mpa_id'],
                    ['updated_at']
                );
            }

            return ['inserted'=>count($rows), 'replaced'=>$replace, 'filtered'=>false];
        });
    }

    /** Toutes les activity IDs appartenant à un projet */
    private function activityIdsByProject(int $projectId): Collection
    {
        return DB::table('activities as a')
            ->join('processes as p','a.process_id','=','p.id')
            ->join('macro_processes as m','p.macro_process_id','=','m.id')
            ->where('m.project_id', $projectId)
            ->pluck('a.id')
            ->map(fn($v)=>(int)$v);
    }
}