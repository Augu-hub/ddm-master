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

    /**
     * Liste des M/P/A actuellement liés à UNE fonction pour UNE entité
     */
    public function current(Request $r)
    {
        $data = $r->validate([
            'entity_id'   => ['required','integer','exists:entities,id'],
            'function_id' => ['required','integer','exists:functions,id'],
        ]);

        $rows = DB::table('assignment_functions as af')
            ->join('assignments as a','a.id','=','af.assignment_id')
            ->where('af.entity_id', (int)$data['entity_id']) // ✅ filtre direct
            ->where('af.function_id', (int)$data['function_id'])
            ->get(['a.mpa_type','a.mpa_id']);

        if ($rows->isEmpty()) return response()->json([]);

        $byType = [
            'macro'    => $rows->where('mpa_type','macro')->pluck('mpa_id')->unique(),
            'process'  => $rows->where('mpa_type','process')->pluck('mpa_id')->unique(),
            'activity' => $rows->where('mpa_type','activity')->pluck('mpa_id')->unique(),
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

    /**
     * ✅ NOUVEAU – Map globale: pour une entité, renvoie (type:id) → function_id
     * Permet au front d’éviter les multiples appels par fonction.
     */
    public function map(Request $r)
    {
        $data = $r->validate([
            'entity_id' => ['required','integer','exists:entities,id'],
        ]);
        $rows = DB::table('assignment_functions as af')
            ->join('assignments as a','a.id','=','af.assignment_id')
            ->where('af.entity_id', (int)$data['entity_id'])
            ->get(['a.mpa_type','a.mpa_id','af.function_id']);

        // Format: { items: [ {type,id,function_id}, ... ] }
        $items = [];
        foreach ($rows as $r2) {
            $items[] = [
                'type' => $r2->mpa_type,
                'id'   => (int)$r2->mpa_id,
                'function_id' => (int)$r2->function_id,
            ];
        }
        return response()->json(['items'=>$items]);
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

                // ✅ renseigner entity_id pour cohérence et requêtes rapides
                DB::table('assignment_functions')->upsert([
                    [
                        'assignment_id' => $assignmentId,
                        'function_id'   => $functionId,
                        'entity_id'     => $entityId,
                        'role_label'    => $role,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]
                ], ['assignment_id','function_id'], ['entity_id','role_label','updated_at']);
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
                        ->where('entity_id',$entityId) // ✅ évite d’impacter d’autres entités
                        ->delete();
                }
            }
        });

        return response()->json(['ok'=>true]);
    } 
}