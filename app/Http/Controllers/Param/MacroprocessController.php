<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
// use App\Models\Param\Process;
// use App\Models\Param\Macroprocess;
// use App\Models\Param\Entity;
// use App\Models\Param\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MacroprocessController extends Controller
{
       public function index()
    {
        // Retourne une vue simple avec du texte
        return Inertia::render('dashboards/Param/Mpa/index');
    }
   

    public function create()
    {
        // même vue que l’index (form + liste)
        return $this->index();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required','exists:param_projects,id'], // utilisé pour filtrer côté front
            'entity_id'  => ['required','exists:param_entities,id'],
            'macro_id'   => ['required','exists:macroprocesses,id'],
            'code'       => ['required','string','max:50','unique:processes,code'],
            'name'       => ['required','string','max:255'],
            'objective'  => ['nullable','string'],
        ]);

        // on n’enregistre pas project_id dans la table (dérivé via entity), on le retire
        unset($data['project_id']);

        Process::create($data);

        return redirect()->route('param.process.index')
            ->with('success', 'Processus créé.');
    }

    public function show(Process $process)
    {
        return Inertia::render('Param/Process/Show', [
            'process' => $process->load(['macro:id,name','entity:id,name,project_id','entity.project:id,name']),
        ]);
    }

    public function edit(Process $process)
    {
        return Inertia::render('Param/Process/Edit', [
            'process'  => $process->load(['macro:id,name','entity:id,name,project_id']),
            'projects' => Project::orderBy('name')->get(['id','name']),
            'entities' => Entity::orderBy('name')->get(['id','code','name','project_id']),
            'macros'   => Macroprocess::orderBy('code')->get(['id','code','name','character']),
        ]);
    }

    public function update(Request $request, Process $process)
    {
        $data = $request->validate([
            'entity_id'  => ['required','exists:param_entities,id'],
            'macro_id'   => ['required','exists:macroprocesses,id'],
            'code'       => ['required','string','max:50','unique:processes,code,'.$process->id],
            'name'       => ['required','string','max:255'],
            'objective'  => ['nullable','string'],
        ]);

        $process->update($data);

        return redirect()->route('param.process.index')
            ->with('success', 'Processus mis à jour.');
    }

    public function destroy(Process $process)
    {
        $process->delete();

        return back()->with('success', 'Processus supprimé.');
    }
}
