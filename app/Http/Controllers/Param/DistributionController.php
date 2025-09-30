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
        $projects = DB::table('projects')->orderBy('name')->get(['id','name']);
        $entities = DB::table('entities')->orderBy('name')->get(['id','name','project_id']);

        return Inertia::render('dashboards/Param/Distribution/index', [
            'projects' => $projects,
            'entities' => $entities,
            'routes' => [
                'mpa' => [
                    'tree'    => route('param.distribution.tree'),
                    'current' => route('param.distribution.current'),
                    'preview' => route('param.distribution.preview'),
                    'commit'  => route('param.distribution.commit'),
                ],
                'funcs' => [
                    'list'    => route('param.functions.list'),
                    'current' => route('param.functions.current'),
                    'commit'  => route('param.functions.commit'),
                ],
                'mpsresp' => [
                    'tree'     => route('param.mpsresp.tree'),
                    'current'  => route('param.mpsresp.current'),
                    'assign'   => route('param.mpsresp.assign'),
                    'unassign' => route('param.mpsresp.unassign'),
                ],
            ]
        ]);
    }

    public function tree(Request $r)
    {
        $projectId = (int) $r->integer('project_id');
        return response()->json($this->svc->fetchTreeByProject($projectId));
    }

    public function current(Request $r)
    {
        $r->validate(['entity_id'=>['required','integer','exists:entities,id']]);
        $entityId = (int) $r->integer('entity_id');
        $projectId = $r->has('project_id') ? (int) $r->integer('project_id') : null;

        return response()->json($this->svc->currentForEntity($entityId, $projectId));
    }

    public function preview(Request $r)
    {
        $data = $r->validate([
            'entity_id'      => ['required','integer','exists:entities,id'],
            'nodes'          => ['required','array','min:1'],
            'nodes.*.id'     => ['required','integer'],
            'nodes.*.type'   => ['required','in:macro,process,activity'],
            'project_id'     => ['required','integer','exists:projects,id'], // Maintenant requis
        ]);

        return response()->json(
            $this->svc->preview((int)$data['entity_id'], $data['nodes'], (int)$data['project_id'])
        );
    }

    public function commit(Request $r)
    {
        $data = $r->validate([
            'entity_id'       => ['required','integer','exists:entities,id'],
            'activity_ids'    => ['required','array','min:1'],
            'activity_ids.*'  => ['integer','exists:activities,id'],
            'replace'         => ['sometimes','boolean'],
            'project_id'      => ['required','integer','exists:projects,id'], // Maintenant requis
        ]);

        $res = $this->svc->commit(
            (int)$data['entity_id'],
            $data['activity_ids'],
            (int)$data['project_id'], // Maintenant toujours présent
            (bool)($data['replace'] ?? false)
        );

        return response()->json(['ok'=>true] + $res);
    }
}