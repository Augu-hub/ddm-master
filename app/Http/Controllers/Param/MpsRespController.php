<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Domain\Param\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpsRespController extends Controller
{
    public function __construct(private AssignmentService $svc) {}

    public function tree(Request $r)
    {
        $projectId = $r->integer('project_id');
        return response()->json($this->svc->fetchTreeByProject($projectId));
    }

    public function current(Request $r)
    {
        $data = $r->validate([
            'entity_id'   => ['required','integer','exists:entities,id'],
            'function_id' => ['required','integer','exists:functions,id'],
        ]);
        $entityId   = (int)$data['entity_id'];
        $functionId = (int)$data['function_id'];

        $rows = DB::table('assignment_functions AS af')
            ->join('assignments AS a','a.id','=','af.assignment_id')
            ->where('a.entity_id',$entityId)
            ->where('af.function_id',$functionId)
            ->get(['a.mpa_type','a.mpa_id']);

        if ($rows->isEmpty()) return response()->json([]);

        $byType = [
            'macro'    => $rows->where('mpa_type','macro')->pluck('mpa_id'),
            'process'  => $rows->where('mpa_type','process')->pluck('mpa_id'),
            'activity' => $rows->where('mpa_type','activity')->pluck('mpa_id'),
        ];

        $out = [];
        if ($byType['macro']->isNotEmpty()) {
            foreach (DB::table('macro_processes')->whereIn('id',$byType['macro'])->get(['id','code','name']) as $x) {
                $out[] = ['type'=>'macro','id'=>$x->id,'code'=>$x->code,'name'=>$x->name];
            }
        }
        if ($byType['process']->isNotEmpty()) {
            foreach (DB::table('processes')->whereIn('id',$byType['process'])->get(['id','code','name']) as $x) {
                $out[] = ['type'=>'process','id'=>$x->id,'code'=>$x->code,'name'=>$x->name];
            }
        }
        if ($byType['activity']->isNotEmpty()) {
            foreach (DB::table('activities')->whereIn('id',$byType['activity'])->get(['id','code','name']) as $x) {
                $out[] = ['type'=>'activity','id'=>$x->id,'code'=>$x->code,'name'=>$x->name];
            }
        }

        usort($out, fn($A,$B)=>[$A['type'],$A['name']] <=> [$B['type'],$B['name']]);
        return response()->json($out);
    }

    public function assign(Request $r)
    {
        $data = $r->validate([
            'entity_id'   => ['required','integer','exists:entities,id'],
            'function_id' => ['required','integer','exists:functions,id'],
            'role'        => ['nullable','string','max:100'],
            'nodes'       => ['required','array','min:1'],
            'nodes.*.id'  => ['required','integer'],
            'nodes.*.type'=> ['required','in:macro,process,activity'],
        ]);

        $entityId   = (int)$data['entity_id'];
        $functionId = (int)$data['function_id'];
        $role       = $data['role'] ?? null;
        $now        = now();

        DB::transaction(function () use ($entityId,$functionId,$role,$data,$now) {
            foreach ($data['nodes'] as $n) {
                $assignmentId = app(AssignmentService::class)
                    ->ensureAssignment($entityId, $n['type'], (int)$n['id']);

                DB::table('assignment_functions')->upsert([[
                    'assignment_id' => $assignmentId,
                    'function_id'   => $functionId,
                    'role_label'    => $role,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]], ['assignment_id','function_id'], ['role_label','updated_at']);
            }
        });

        return response()->json(['ok'=>true]);
    }

    public function unassign(Request $r)
    {
        $data = $r->validate([
            'entity_id'   => ['required','integer','exists:entities,id'],
            'function_id' => ['required','integer','exists:functions,id'],
            'nodes'       => ['required','array','min:1'],
            'nodes.*.id'  => ['required','integer'],
            'nodes.*.type'=> ['required','in:macro,process,activity'],
        ]);

        $entityId   = (int)$data['entity_id'];
        $functionId = (int)$data['function_id'];

        DB::transaction(function () use ($entityId,$functionId,$data) {
            foreach ($data['nodes'] as $n) {
                $a = DB::table('assignments')
                    ->where('entity_id',$entityId)
                    ->where('mpa_type',$n['type'])
                    ->where('mpa_id',(int)$n['id'])
                    ->first(['id']);

                if ($a) {
                    DB::table('assignment_functions')
                        ->where('assignment_id',$a->id)
                        ->where('function_id',$functionId)
                        ->delete();
                }
            }
        });

        return response()->json(['ok'=>true]);
    }
}
