<?php

namespace App\Domain\Param\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    /**
     * Arborescence globale (tous projets confondus).
     */
    public function fetchTree(): array
    {
        $macros = DB::table('macro_processes')
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
                'id'       => (int)$m->id,
                'code'     => $m->code,
                'name'     => $m->name,
                'children' => [],
            ];

            foreach ($procsByMacro[$m->id] ?? [] as $p) {
                $pNode = [
                    'id'       => (int)$p->id,
                    'code'     => $p->code,
                    'name'     => $p->name,
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

    /**
     * Arborescence filtrée par projet (compat).
     */
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
                'id'       => (int)$m->id,
                'code'     => $m->code,
                'name'     => $m->name,
                'children' => [],
            ];

            foreach ($procsByMacro[$m->id] ?? [] as $p) {
                $pNode = [
                    'id'       => (int)$p->id,
                    'code'     => $p->code,
                    'name'     => $p->name,
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

    /**
     * Affectations actuelles pour une entité.
     * Si $projectId est fourni, on restreint aux activités de ce projet. Sinon, tout confondu.
     */
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

    /**
     * Prévisualisation des activités résultantes.
     * - Si $projectId est fourni : on limite aux activités appartenant à ce projet.
     * - Sinon : on prend tout (tous projets confondus).
     */
    public function preview(int $entityId, array $nodes, ?int $projectId = null): array
    {
        $idsByType = ['macro'=>collect(), 'process'=>collect(), 'activity'=>collect()];
        foreach ($nodes as $n) {
            $type = $n['type'] ?? null;
            $id   = isset($n['id']) ? (int)$n['id'] : null;
            if ($id && isset($idsByType[$type])) $idsByType[$type]->push($id);
        }

        $activityIds = collect();

        // 1) Activités directes
        if ($idsByType['activity']->isNotEmpty()) {
            $q = DB::table('activities')->whereIn('activities.id', $idsByType['activity']);
            if ($projectId) {
                $q->join('processes','activities.process_id','=','processes.id')
                  ->join('macro_processes','processes.macro_process_id','=','macro_processes.id')
                  ->where('macro_processes.project_id', $projectId)
                  ->select('activities.id');
            }
            $activityIds = $activityIds->merge(
                $projectId ? $q->pluck('activities.id') : $q->pluck('id')
            );
        }

        // 2) Activités d'une liste de process
        if ($idsByType['process']->isNotEmpty()) {
            $q = DB::table('activities')->whereIn('activities.process_id', $idsByType['process']);
            if ($projectId) {
                $q->join('processes','activities.process_id','=','processes.id')
                  ->join('macro_processes','processes.macro_process_id','=','macro_processes.id')
                  ->where('macro_processes.project_id', $projectId)
                  ->select('activities.id');
            }
            $activityIds = $activityIds->merge(
                $projectId ? $q->pluck('activities.id') : $q->pluck('id')
            );
        }

        // 3) Activités issues des macros
        if ($idsByType['macro']->isNotEmpty()) {
            $procIds = DB::table('processes')
                ->whereIn('macro_process_id', $idsByType['macro'])
                ->pluck('id');

            if ($procIds->isNotEmpty()) {
                $q = DB::table('activities')->whereIn('activities.process_id', $procIds);
                if ($projectId) {
                    $q->join('processes','activities.process_id','=','processes.id')
                      ->join('macro_processes','processes.macro_process_id','=','macro_processes.id')
                      ->where('macro_processes.project_id', $projectId)
                      ->select('activities.id');
                }
                $activityIds = $activityIds->merge(
                    $projectId ? $q->pluck('activities.id') : $q->pluck('id')
                );
            }
        }

        $activityIds = $activityIds->unique()->values()->map(fn($v)=>(int)$v);

        return ['count'=>$activityIds->count(), 'activity_ids'=>$activityIds->all()];
    }

    /**
     * Commit des affectations d'activités.
     * - Si $projectId est fourni : on nettoie/valide au périmètre du projet.
     * - Si $projectId est null : on opère globalement (tous projets).
     *
     * $replace = true :
     *   - avec projet  => supprime d'abord toutes les activités de CE projet pour l'entité
     *   - sans projet  => supprime d'abord TOUTES les activités affectées à l'entité
     */
    public function commit(int $entityId, array $activityIds, ?int $projectId = null, bool $replace = false): array
    {
        $now = now();

        $ids = collect($activityIds)->map(fn($v)=>(int)$v)->unique()->values();

        // Valider les IDs au périmètre attendu
        if ($projectId) {
            $validIds = $this->activityIdsByProject($projectId);
            $ids = $ids->intersect($validIds);
        } else {
            // On conserve uniquement les IDs qui existent réellement
            $valid = DB::table('activities')->whereIn('id', $ids)->pluck('id')->map(fn($v)=>(int)$v);
            $ids   = $ids->intersect($valid);
        }

        if ($ids->isEmpty()) {
            return ['inserted'=>0, 'replaced'=>$replace, 'filtered'=>true];
        }

        return DB::transaction(function () use ($entityId, $ids, $replace, $projectId, $now) {
            if ($replace) {
                $q = DB::table('assignments')
                    ->where('entity_id', $entityId)
                    ->where('mpa_type', 'activity');

                if ($projectId) {
                    $q->whereIn('mpa_id', $this->activityIdsByProject($projectId));
                }

                $q->delete();
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

    /** Toutes les activity IDs appartenant à un projet donné */
    private function activityIdsByProject(int $projectId): Collection
    {
        return DB::table('activities as a')
            ->join('processes as p','a.process_id','=','p.id')
            ->join('macro_processes as m','p.macro_process_id','=','m.id')
            ->where('m.project_id', $projectId)
            ->pluck('a.id')
            ->map(fn($v)=>(int)$v);
    }


    /**
     * Activités déjà affectées à une entité
     */

    /**
     * Résout les nodes en activités distinctes et renvoie un preview (excluant déjà affectées)
     */

    /**
     * Applique l’affectation des activités à l’entité (replace = écrasement)
     */
  
    /**
     * Garantit l’existence d’un assignment et renvoie son ID
     */
    public function ensureAssignment(int $entityId, string $mpaType, int $mpaId): int
    {
        $row = DB::table('assignments')
            ->where('entity_id',$entityId)
            ->where('mpa_type',$mpaType)
            ->where('mpa_id',$mpaId)
            ->first(['id']);
        if ($row) return (int)$row->id;

        $id = DB::table('assignments')->insertGetId([
            'entity_id'=>$entityId,
            'mpa_type'=>$mpaType,
            'mpa_id'=>$mpaId,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
        return (int)$id;
    }

    /** Variante projet */

    
}
