<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Domain\Param\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpsResponsableController extends Controller
{
    public function __construct(private AssignmentService $svc) {}

    /**
     * Arbre MPA (Macro→Process→Activité) par projet (même payload que /distribution/tree)
     * GET /param/mpsresp/tree?project_id=...
     */
    public function tree(Request $r)
    {
        $projectId = $r->integer('project_id');
        return response()->json($this->svc->fetchTreeByProject($projectId));
    }

    /**
     * Fonctions du projet (liste ou arbre si parent_id)
     * GET /param/mpsresp/functions?project_id=...
     */
    public function functions(Request $r)
    {
        $projectId = $r->integer('project_id');

        $rows = DB::table('functions')
            ->select('id','name','parent_id','project_id')
            ->when($projectId, fn($q)=>$q->where('project_id',$projectId))
            ->orderBy('name')->get();

        $hasHierarchy = $rows->contains(fn($f)=>!is_null($f->parent_id));
        if (!$hasHierarchy) {
            return response()->json(['list' => $rows->map(fn($f)=>['id'=>$f->id,'name'=>$f->name])->values()]);
        }

        $byParent = $rows->groupBy('parent_id');
        $make = function($f) use (&$make,$byParent){
            $children = ($byParent[$f->id] ?? collect())->map($make)->values()->all();
            return ['id'=>$f->id,'name'=>$f->name,'children'=>$children];
        };
        $roots = ($byParent[null] ?? collect())->map($make)->values()->all();

        return response()->json(['tree' => $roots]);
    }

    /**
     * Lecture des responsables affectés par entité:
     * - byActivity: chaque activité de l'entité avec ses responsables (fonctions)
     * - matrix: lignes à plat Macro/Process/Activité + fonctions (pour un tableau)
     * GET /param/mpsresp/current?entity_id=...
     */
    public function current(Request $r)
    {
        $r->validate(['entity_id'=>['required','integer','exists:entities,id']]);
        $entityId = $r->integer('entity_id');

        // Assignments de type 'activity'
        $q = DB::table('assignments as a')
            ->join('activities as act','act.id','=','a.mpa_id')
            ->join('processes as p','p.id','=','act.process_id')
            ->join('macro_processes as m','m.id','=','p.macro_process_id')
            ->leftJoin('assignment_functions as af','af.assignment_id','=','a.id')
            ->leftJoin('functions as f','f.id','=','af.function_id')
            ->where('a.entity_id',$entityId)
            ->where('a.mpa_type','activity')
            ->orderBy('m.kind')->orderBy('p.code')->orderBy('act.code')
            ->get([
                'a.id as assignment_id',
                'act.id as activity_id','act.code as activity_code','act.name as activity_name',
                'p.id as process_id','p.code as process_code','p.name as process_name',
                'm.id as macro_id','m.code as macro_code','m.name as macro_name','m.kind as macro_kind',
                'f.id as function_id','f.name as function_name',
                'af.role_label',
            ]);

        // byActivity
        $grouped = $q->groupBy('activity_id')->map(function($rows){
            $r0 = $rows->first();
            return [
                'assignment_id' => (int)$r0->assignment_id,
                'activity' => [
                    'id' => (int)$r0->activity_id,
                    'code' => $r0->activity_code,
                    'name' => $r0->activity_name,
                    'process' => [
                        'id' => (int)$r0->process_id,
                        'code'=> $r0->process_code,
                        'name'=> $r0->process_name,
                        'macro'=> [
                            'id' => (int)$r0->macro_id,
                            'code'=> $r0->macro_code,
                            'name'=> $r0->macro_name,
                            'kind'=> $r0->macro_kind,
                        ],
                    ],
                ],
                'functions' => $rows->whereNotNull('function_id')->map(fn($r)=>[
                    'id' => (int)$r->function_id, 'name'=>$r->function_name, 'role_label'=>$r->role_label
                ])->values(),
            ];
        })->values();

        // matrix (table à plat)
        $matrix = $q->map(function($r){
            return [
                'macro'    => $r->macro_code,
                'macro_k'  => $r->macro_kind,
                'process'  => $r->process_code,
                'activity' => $r->activity_code,
                'designation' => $r->activity_name,
                'function' => $r->function_name,
                'role'     => $r->role_label,
            ];
        });

        return response()->json([
            'byActivity' => $grouped,
            'matrix'     => $matrix,
        ]);
    }

    /**
     * Affecter 1 fonction à N activités (les nodes M/P/A sont expand → activités)
     * POST /param/mpsresp/assign
     * { entity_id, function_id, nodes:[{id,type}], role_label?:string, replace?:bool }
     * - replace=true : remplace tous les responsables sur ces activités par cette fonction (+ role_label)
     * - replace=false : ajoute en plus (upsert)
     */
    public function assign(Request $r)
    {
        $data = $r->validate([
            'entity_id'    => ['required','integer','exists:entities,id'],
            'function_id'  => ['required','integer','exists:functions,id'],
            'nodes'        => ['required','array','min:1'],
            'nodes.*.id'   => ['required','integer'],
            'nodes.*.type' => ['required','in:macro,process,activity'],
            'role_label'   => ['nullable','string','max:255'],
            'replace'      => ['sometimes','boolean'],
        ]);

        // Expand → activities
        $activityIds = $this->svc->expandToActivityIds($data['nodes']);
        if ($activityIds->isEmpty()) {
            return response()->json(['ok'=>true, 'updated'=>0]);
        }

        $entityId   = (int)$data['entity_id'];
        $functionId = (int)$data['function_id'];
        $replace    = (bool)($data['replace'] ?? false);
        $roleLabel  = $data['role_label'] ?? null;
        $now        = now();

        DB::transaction(function () use ($entityId,$functionId,$activityIds,$replace,$roleLabel,$now) {
            // S’assurer que l’assignment (entity + activity) existe
            $assignments = DB::table('assignments')
                ->where('entity_id',$entityId)
                ->where('mpa_type','activity')
                ->whereIn('mpa_id',$activityIds)
                ->pluck('id','mpa_id'); // [activity_id => assignment_id]

            // Créer ceux manquants
            $toCreate = $activityIds->reject(fn($aid)=>$assignments->has($aid))
                ->map(fn($aid)=>[
                    'entity_id'=>$entityId,'mpa_type'=>'activity','mpa_id'=>$aid,
                    'created_at'=>$now,'updated_at'=>$now
                ])->values()->all();

            if (!empty($toCreate)) {
                DB::table('assignments')->insert($toCreate);
                // recharger le mapping
                $assignments = DB::table('assignments')
                    ->where('entity_id',$entityId)
                    ->where('mpa_type','activity')
                    ->whereIn('mpa_id',$activityIds)
                    ->pluck('id','mpa_id');
            }

            // Pour chaque assignment, remplacer ou ajouter la fonction
            $rows = [];
            foreach ($assignments as $aid => $assignmentId) {
                if ($replace) {
                    DB::table('assignment_functions')->where('assignment_id',$assignmentId)->delete();
                }
                $rows[] = [
                    'assignment_id' => (int)$assignmentId,
                    'function_id'   => $functionId,
                    'role_label'    => $roleLabel,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            if (!empty($rows)) {
                DB::table('assignment_functions')->upsert(
                    $rows,
                    ['assignment_id','function_id'],
                    ['role_label','updated_at']
                );
            }
        });

        return response()->json(['ok'=>true, 'updated'=>$activityIds->count()]);
    }

    /**
     * Détacher une fonction d’une sélection (M/P/A → expand)
     * POST /param/mpsresp/unassign { entity_id, function_id, nodes:[{id,type}] }
     */
    public function unassign(Request $r)
    {
        $data = $r->validate([
            'entity_id'    => ['required','integer','exists:entities,id'],
            'function_id'  => ['required','integer','exists:functions,id'],
            'nodes'        => ['required','array','min:1'],
            'nodes.*.id'   => ['required','integer'],
            'nodes.*.type' => ['required','in:macro,process,activity'],
        ]);

        $activityIds = $this->svc->expandToActivityIds($data['nodes']);
        if ($activityIds->isEmpty()) return response()->json(['ok'=>true,'deleted'=>0]);

        $entityId   = (int)$data['entity_id'];
        $functionId = (int)$data['function_id'];

        $assignmentIds = DB::table('assignments')
            ->where('entity_id',$entityId)
            ->where('mpa_type','activity')
            ->whereIn('mpa_id',$activityIds)
            ->pluck('id');

        $deleted = 0;
        if ($assignmentIds->isNotEmpty()) {
            $deleted = DB::table('assignment_functions')
                ->whereIn('assignment_id',$assignmentIds)
                ->where('function_id',$functionId)
                ->delete();
        }

        return response()->json(['ok'=>true,'deleted'=>$deleted]);
    }
}
