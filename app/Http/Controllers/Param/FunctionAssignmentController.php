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

        $q = DB::table('functions')->select('id','name','parent_id','project_id');

        // ✅ Si un projet est sélectionné : inclure aussi les fonctions globales (project_id IS NULL)
        if ($projectId) {
            $q->where(function ($w) use ($projectId) {
                $w->where('project_id', $projectId)
                  ->orWhereNull('project_id');
            });
        }

        // Tri : parents d'abord puis nom
        $rows = $q->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                  ->orderBy('name')
                  ->get();

        // Liste plate (toujours)
        $list = $rows->map(fn($f) => ['id' => (int)$f->id, 'name' => $f->name])->values()->all();

        // Détection hiérarchie et construction éventuelle de l'arbre
        $hasHierarchy = $rows->contains(fn($f) => !is_null($f->parent_id));
        $tree = [];

        if ($hasHierarchy) {
            $byParent = $rows->groupBy('parent_id');

            $makeNode = function ($f) use (&$makeNode, $byParent) {
                $children = ($byParent[$f->id] ?? collect())
                    ->map($makeNode)
                    ->values()
                    ->all();

                return [
                    'id' => (int)$f->id,
                    'name' => $f->name,
                    'children' => $children,
                ];
            };

            $tree = ($byParent[null] ?? collect())
                ->map($makeNode)
                ->values()
                ->all();
        }

        // ✅ On renvoie toujours list, et tree si présent
        return response()->json([
            'list' => $list,
            'tree' => $tree,
        ]);
    }

    /** Fonctions déjà affectées à une entité */
    public function current(Request $r)
    {
        $r->validate(['entity_id' => ['required','integer','exists:entities,id']]);
        $entityId = (int) $r->integer('entity_id');

        // ✅ CORRECTION : Utiliser entity_function au lieu de function_assignments
        $functions = DB::table('entity_function')
            ->join('functions','functions.id','=','entity_function.function_id')
            ->where('entity_function.entity_id', $entityId)
            ->orderBy('functions.name')
            ->get(['functions.id','functions.name']);

        // Le frontend accepte un tableau simple
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
            'project_id'     => ['nullable','integer'],
        ]);

        $entityId = (int) $data['entity_id'];
        $replace  = (bool) ($data['replace'] ?? false);
        $now      = now();

        DB::transaction(function () use ($entityId, $replace, $data, $now) {
            if ($replace) {
                // ✅ CORRECTION : Utiliser entity_function
                DB::table('entity_function')->where('entity_id', $entityId)->delete();
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
                // ✅ CORRECTION : Utiliser entity_function
                DB::table('entity_function')->upsert(
                    $rows,
                    ['entity_id','function_id'],
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