<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FunctionAssignmentController extends Controller
{
    /** Catalogue fonctions (renvoie toujours list + éventuellement tree) */
public function list(Request $r)
{
    $projectId = $r->integer('project_id');

    $q = DB::table('functions')
        ->select('id', 'name', 'parent_id', 'project_id');

    // Si un projet est précisé, on filtre dessus.
    // (On ne construit plus d'arborescence, donc pas besoin d'ajouter d'autres cas.)
    if ($projectId) {
        $q->where('project_id', $projectId);
    }

    // Tri simple par nom
    $rows = $q->orderBy('name')->get();

    // Liste plate
    $list = $rows->map(fn($f) => [
        'id'         => (int) $f->id,
        'name'       => $f->name,
        // facultatif si tu veux afficher plus d'infos côté UI :
        // 'parent_id'  => $f->parent_id ? (int) $f->parent_id : null,
        // 'project_id' => (int) $f->project_id,
    ])->values()->all();

    // Forcer l'UI à rester en mode liste (pas d'arborescence)
    return response()->json([
        'list' => $list,
        'tree' => [],
    ]);
}

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

// Affecter des fonctions (replace supprime d'abord)
public function commit(Request $r)
{
    $data = $r->validate([
        'entity_id'      => ['required','integer','exists:entities,id'],
        'function_ids'   => ['required','array','min:1'],
        'function_ids.*' => ['integer','exists:functions,id'],
        'replace'        => ['sometimes','boolean'],
        'project_id'     => ['nullable','integer'],
    ]);

    $entityId = (int) $data['entity_id'];
    $replace  = (bool) ($data['replace'] ?? false);
    $now      = now();

    DB::transaction(function () use ($entityId, $replace, $data, $now) {
        if ($replace) {
            DB::table('function_assignments')->where('entity_id', $entityId)->delete();
        }

        $rows = collect($data['function_ids'])
            ->unique()
            ->values()
            ->map(fn($fid) => [
                'entity_id'   => $entityId,
                'function_id' => (int) $fid,
                'created_at'  => $now,
                'updated_at'  => $now,
            ])->all();

        if ($rows) {
            DB::table('function_assignments')->upsert(
                $rows,
                ['entity_id','function_id'], // doit correspondre à l’unique index
                ['updated_at']
            );
        }
    });

    return response()->json([
        'ok'       => true,
        'inserted' => count(array_unique($data['function_ids'])),
        'replaced' => $replace,
    ]);
}

}