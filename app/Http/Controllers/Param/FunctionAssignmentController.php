<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FunctionAssignmentController extends Controller
{
    /** Catalogue fonctions – sans notion de projet */
    public function list(Request $r)
    {
        $rows = DB::table('functions')
            ->select('id', 'name', 'parent_id')
            ->orderBy('name')
            ->get();

        $list = $rows->map(fn($f) => ['id'=>(int)$f->id, 'name'=>$f->name])->values()->all();

        return response()->json(['list'=>$list, 'tree'=>[]]);
    }

    /** Fonctions déjà affectées à l’entité (liste simple) */
    public function current(Request $r)
    {
        $r->validate(['entity_id' => ['required','integer','exists:entities,id']]);
        $entityId = (int) $r->integer('entity_id');

        $functions = DB::table('function_assignments')
            ->join('functions','functions.id','=','function_assignments.function_id')
            ->where('function_assignments.entity_id', $entityId)
            ->orderBy('functions.name')
            ->get(['functions.id','functions.name']);

        return response()->json(
            $functions->map(fn($f)=>['id'=>(int)$f->id,'name'=>$f->name])->values()
        );
    }

    /** Affecter des fonctions (replace supprime d'abord) */
    public function commit(Request $r)
    {
        $data = $r->validate([
            'entity_id'      => ['required','integer','exists:entities,id'],
            'function_ids'   => ['required','array','min:1'],
            'function_ids.*' => ['integer','exists:functions,id'],
            'replace'        => ['sometimes','boolean'],
        ]);

        $entityId = (int) $data['entity_id'];
        $replace  = (bool) ($data['replace'] ?? false);
        $now      = now();

        DB::transaction(function () use ($entityId, $replace, $data, $now) {
            if ($replace) {
                DB::table('function_assignments')->where('entity_id', $entityId)->delete();
            }

            $rows = collect($data['function_ids'])->unique()->values()->map(fn($fid)=>[
                'entity_id'=>$entityId, 'function_id'=>(int)$fid, 'created_at'=>$now, 'updated_at'=>$now
            ])->all();

            if ($rows) {
                DB::table('function_assignments')->upsert(
                    $rows,
                    ['entity_id','function_id'],
                    ['updated_at']
                );
            }
        });

        return response()->json([
            'ok'=>true,
            'inserted'=>count(array_unique($data['function_ids'])),
            'replaced'=>$replace,
        ]);
    }

    /* ===================== AJOUTS CI-DESSOUS ===================== */

    /** Recherche d’utilisateurs (pour l’UI) */
    public function users(Request $r)
    {
        $q = trim((string)$r->query('q',''));
        $rows = DB::table('users')
            ->when($q !== '', function($w) use ($q){
                $like = "%".str_replace('%','\\%',$q)."%";
                $w->where(function($qq) use ($like){
                    $qq->where('name','like',$like)->orWhere('email','like',$like);
                });
            })
            ->orderBy('name')->limit(50)->get(['id','name','email']);

        return response()->json([
            'options' => $rows->map(fn($u)=>[
                'value'=>(string)$u->id,
                'text'=> $u->name.' — '.$u->email
            ])->values()
        ]);
    }

    /** Map fonction → user pour une entité (rapide pour préremplir l’UI) */
    public function userMap(Request $r)
    {
        $data = $r->validate([
            'entity_id' => ['required','integer','exists:entities,id'],
        ]);
        $rows = DB::table('function_assignments as fa')
            ->leftJoin('users as u','u.id','=','fa.user_id')
            ->where('fa.entity_id',(int)$data['entity_id'])
            ->get(['fa.function_id','fa.user_id','u.name','u.email']);

        return response()->json([
            'items' => $rows->map(fn($x)=>[
                'function_id'=>(int)$x->function_id,
                'user_id'    => $x->user_id ? (int)$x->user_id : null,
                'name'       => $x->name,
                'email'      => $x->email,
            ])->values()
        ]);
    }

    /** Définir (ou changer) l’utilisateur d’une fonction pour une entité */
    public function setUser(Request $r)
    {
        $data = $r->validate([
            'entity_id'   => ['required','integer','exists:entities,id'],
            'function_id' => ['required','integer','exists:functions,id'],
            'user_id'     => ['nullable','integer','exists:users,id'],
        ]);

        $now = now();
        $rowId = DB::table('function_assignments')
            ->where('entity_id',(int)$data['entity_id'])
            ->where('function_id',(int)$data['function_id'])
            ->value('id');

        if (!$rowId) {
            // crée le lien entité↔fonction si absent
            $rowId = DB::table('function_assignments')->insertGetId([
                'entity_id'=>(int)$data['entity_id'],
                'function_id'=>(int)$data['function_id'],
                'user_id'=>null,
                'created_at'=>$now,
                'updated_at'=>$now,
            ]);
        }

        DB::table('function_assignments')->where('id',$rowId)->update([
            'user_id'   => $data['user_id'] ?? null,
            'updated_at'=> $now,
        ]);

        return response()->json(['ok'=>true]);
    }

    /** Retirer l’utilisateur de la fonction pour l’entité (revient à NULL) */
    public function clearUser(Request $r)
    {
        $data = $r->validate([
            'entity_id'   => ['required','integer','exists:entities,id'],
            'function_id' => ['required','integer','exists:functions,id'],
        ]);

        DB::table('function_assignments')
            ->where('entity_id',(int)$data['entity_id'])
            ->where('function_id',(int)$data['function_id'])
            ->update(['user_id'=>null, 'updated_at'=>now()]);

        return response()->json(['ok'=>true]);
    }
}
