<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Entite;
use App\Models\Param\Projet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EntiteController extends Controller
{
    public function index(Request $request)
    {
        $entities = Entite::with(['project:id,name','parent:id,name,level,code_base'])
            ->orderBy('name')
            ->get();

        $projects = Projet::orderBy('name')->get(['id','name']);

        // Parents proposés (on expose level & code_base pour l’UI)
        $parents  = Entite::orderBy('name')->get(['id','name','project_id','level','code_base']);

        return Inertia::render('dashboards/Param/Entities/index', [
            'entities' => $entities,
            'projects' => $projects,
            'parents'  => $parents,
        ]);
    }

    public function create()
    {
        return redirect()->route('param.entities.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required','exists:tenant.projects,id'],
            'name'       => ['required','string','max:255'],
            'description'=> ['nullable','string'],
            'parent_id'  => ['nullable','exists:tenant.entities,id'],
            'code_base'  => ['nullable','string','max:255'],
            'logo'       => ['nullable','string','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'email'      => ['nullable','email','max:255'],
            'leader'     => ['nullable','string','max:255'],
            'address'    => ['nullable','string'],
        ]);

        // Niveau auto
        $parent = !empty($data['parent_id']) ? Entite::find($data['parent_id']) : null;
        $data['level'] = $parent ? min(($parent->level ?? 0) + 1, 255) : 0;

        // Code base auto si vide
        if (empty($data['code_base'])) {
            $data['code_base'] = Entite::generateCodeBase(
                projectId: $data['project_id'],
                parentId:  $data['parent_id'] ?? null,
                name:      $data['name']
            );
        }

        Entite::create($data);

        return redirect()
            ->route('param.entities.index')
            ->with('success', 'Entité créée avec succès.');
    }

    public function show(Entite $entite)
    {
        return Inertia::render('dashboards/Param/Entities/Show', [
            'entite' => $entite->load('project:id,name','parent:id,name'),
        ]);
    }

    public function edit(Entite $entite)
    {
        $projects = Projet::orderBy('name')->get(['id','name']);
        $parents  = Entite::where('id','<>',$entite->id)
                        ->orderBy('name')
                        ->get(['id','name','project_id','level','code_base']);

        return Inertia::render('dashboards/Param/Entities/Edit', [
            'entite'   => $entite->only([
                'id','project_id','name','description','level','parent_id',
                'code_base','logo','phone','email','leader','address'
            ]),
            'projects' => $projects,
            'parents'  => $parents,
        ]);
    }

    public function update(Request $request, Entite $entite)
    {
        $data = $request->validate([
            'project_id' => ['required','exists:tenant.projects,id'],
            'name'       => ['required','string','max:255'],
            'description'=> ['nullable','string'],
            'parent_id'  => [
                'nullable',
                'exists:tenant.entities,id',
                Rule::notIn([$entite->id]),
            ],
            'code_base'  => ['nullable','string','max:255'],
            'logo'       => ['nullable','string','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'email'      => ['nullable','email','max:255'],
            'leader'     => ['nullable','string','max:255'],
            'address'    => ['nullable','string'],
        ]);

        // Niveau auto
        $parent = !empty($data['parent_id']) ? Entite::find($data['parent_id']) : null;
        $data['level'] = $parent ? min(($parent->level ?? 0) + 1, 255) : 0;

        // Code base auto si vide
        if (empty($data['code_base'])) {
            $data['code_base'] = Entite::generateCodeBase(
                projectId: $data['project_id'],
                parentId:  $data['parent_id'] ?? null,
                name:      $data['name']
            );
        }

        $entite->update($data);

        return redirect()
            ->route('param.entities.index')
            ->with('success', 'Entité mise à jour.');
    }

    public function destroy(Entite $entite)
    {
        $entite->delete();
        return back()->with('success','Entité supprimée.');
    }

public function apiList()
{
    $entities = Entite::with(['project:id,name'])
        ->orderBy('name')
        ->get(['id', 'name', 'code_base', 'level', 'project_id']);

    return response()->json($entities);
}
public function getMenuEntities(Request $request)
    {
        $entities = Entite::where('project_id', auth()->user()->current_project_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'code_base']);

        return response()->json($entities);
    }





}
