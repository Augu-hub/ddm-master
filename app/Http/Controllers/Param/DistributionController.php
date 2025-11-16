<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Domain\Param\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DistributionController extends Controller
{
    public function __construct(private AssignmentService $svc) {}

    public function index(Request $request)
    {
        $entities = DB::table('entities')->orderBy('name')->get(['id','name']);

        return Inertia::render('dashboards/Param/Distribution/index', [
            'entities' => $entities,
            'routes' => [
                'mpa' => [
                    'tree'    => route('param.projects.distribution.tree'),
                    'current' => route('param.projects.distribution.current'),
                    'preview' => route('param.projects.distribution.preview'),
                    'commit'  => route('param.projects.distribution.commit'),
                ],
                'funcs' => [
                    'list'    => route('param.projects.functions.list'),
                    'current' => route('param.projects.functions.current'),
                    'commit'  => route('param.projects.functions.commit'),
                ],
                'mpsresp' => [
                    'tree'     => route('param.projects.mpsresp.tree'),
                    'current'  => route('param.projects.mpsresp.current'),
                    'map'      => route('param.projects.mpsresp.map'),   // ✅ nouveau pour le front
                    'assign'   => route('param.projects.mpsresp.assign'),
                    'unassign' => route('param.projects.mpsresp.unassign'),
                ],
            ]
        ]);
    }

    public function tree(Request $r)
    {
        return response()->json($this->svc->fetchTree());
    }

    public function current(Request $r)
    {
        $r->validate(['entity_id'=>['required','integer','exists:entities,id']]);
        $entityId = (int)$r->integer('entity_id');
        return response()->json($this->svc->currentForEntity($entityId));
    }

    public function preview(Request $r)
    {
        $data = $r->validate([
            'entity_id'      => ['required','integer','exists:entities,id'],
            'nodes'          => ['required','array','min:1'],
            'nodes.*.id'     => ['required','integer'],
            'nodes.*.type'   => ['required','in:macro,process,activity'],
        ]);

        return response()->json(
            $this->svc->preview((int)$data['entity_id'], $data['nodes'])
        );
    }

    public function commit(Request $r)
    {
        $data = $r->validate([
            'entity_id'       => ['required','integer','exists:entities,id'],
            'activity_ids'    => ['required','array','min:1'],
            'activity_ids.*'  => ['integer','exists:activities,id'],
            'replace'         => ['sometimes','boolean'],
        ]);

        $res = $this->svc->commit(
            (int)$data['entity_id'],
            array_map('intval', $data['activity_ids']),
            /* projectId */ null,
            (bool)($data['replace'] ?? false)
        );

        return response()->json(['ok'=>true] + $res);
    }
}