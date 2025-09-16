<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
// use App\Models\Param\Entity;
// use App\Models\Param\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EntityController extends Controller
{

     public function index()
    {
        // Retourne une vue simple avec du texte
        return Inertia::render('dashboards/Param/Entities/index');
    }
   

    public function create()
    {
        return redirect()->route('param.entities.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => ['required','string','max:20','unique:param_entities,code'],
            'name'       => ['required','string','max:255'],
            'manager'    => ['nullable','string','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'project_id' => ['required','exists:param_projects,id'],
            'parent_id'  => ['nullable','exists:param_entities,id'],
        ]);

        Entity::create($data);

        return redirect()
            ->route('param.entities.index')
            ->with('success', 'Entité créée avec succès.');
    }

    public function show(Entity $entity)
    {
        return Inertia::render('Param/Entities/Show', [
            'entity' => $entity->load('project','parent'),
        ]);
    }

    public function edit(Entity $entity)
    {
        $projects = Project::orderBy('name')->get(['id','name']);
        $parents  = Entity::orderBy('name')->get(['id','code','name','project_id']);

        return Inertia::render('Param/Entities/Edit', [
            'entity'   => $entity->only(['id','code','name','manager','phone','project_id','parent_id']),
            'projects' => $projects,
            'parents'  => $parents,
        ]);
    }

    public function update(Request $request, Entity $entity)
    {
        $data = $request->validate([
            'code'       => ['required','string','max:20', Rule::unique('param_entities','code')->ignore($entity->id)],
            'name'       => ['required','string','max:255'],
            'manager'    => ['nullable','string','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'project_id' => ['required','exists:param_projects,id'],
            'parent_id'  => ['nullable','exists:param_entities,id'],
        ]);

        $entity->update($data);

        return redirect()
            ->route('param.entities.index')
            ->with('success', 'Entité mise à jour.');
    }

    public function destroy(Entity $entity)
    {
        $entity->delete();

        return back()->with('success','Entité supprimée.');
    }
}
