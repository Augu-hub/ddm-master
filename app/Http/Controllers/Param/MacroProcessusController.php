<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\MacroProcessus;
use App\Models\Param\Processus;
use App\Models\Param\Activite;
use App\Models\Param\Projet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;  
class MacroProcessusController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->integer('project_id');

        $projects = Projet::orderBy('name')->get(['id','name']);

        $macros = MacroProcessus::when($projectId, fn($q)=>$q->where('project_id',$projectId))
            ->orderBy('id','asc')
            ->get(['id','project_id','code','name','character','designation','kind']);

        $processes = Processus::with(['macro:id,project_id,name'])
            ->orderBy('id','asc')->get(['id','macro_process_id','code','name']);

        $activities = Activite::with(['process:id,macro_process_id,name','process.macro:id,project_id'])
            ->orderBy('id','asc')->get(['id','process_id','code','name','description']);

        return Inertia::render('dashboards/Param/Mpa/index', [
            'projects'   => $projects,
            'macros'     => $macros,
            'processes'  => $processes,
            'activities' => $activities,
            'filters'    => ['project_id'=>$projectId],
        ]);
    }


public function validateDefaults(Request $request)
{
    $data = $request->validate(['project_id' => ['required','exists:projects,id']]);

    $defaults = [
        ['name'=>'Direction',   'character'=>'D', 'designation'=>'Pilotage / Gouvernance', 'kind'=>'Direction'],
        ['name'=>'Réalisation', 'character'=>'R', 'designation'=>'Cœur de métier',        'kind'=>'Réalisation'],
        ['name'=>'Support',     'character'=>'S', 'designation'=>'Soutien / Support',     'kind'=>'Support'],
    ];

    DB::transaction(function () use ($data, $defaults) {
        foreach ($defaults as $row) {
            $exists = MacroProcessus::where('project_id',$data['project_id'])
                ->where('kind',$row['kind'])->exists();
            if (!$exists) {
                $code = MacroProcessus::nextCodeForProjectKind($data['project_id'], $row['kind'], $row['character']); // 'D'/'R'/'S'
                MacroProcessus::create([
                    'project_id'  => $data['project_id'],
                    'code'        => $code, // ex: D
                    'name'        => $row['name'],
                    'character'   => $row['character'],
                    'designation' => $row['designation'],
                    'kind'        => $row['kind'],
                ]);
            }
        }
    });

    return back()->with('success','Macro-processus par défaut validés.');
}

public function store(Request $request)
{
    $data = $request->validate([
        'project_id'  => ['required','exists:projects,id'],
        'name'        => ['required','string','max:255'],
        'character'   => ['nullable','string','max:1'],
        'designation' => ['nullable','string','max:255'],
        'kind'        => ['required','in:Direction,Réalisation,Support'],
    ]);

    DB::transaction(function () use ($data) {
        $code = MacroProcessus::nextCodeForProjectKind(
            $data['project_id'], $data['kind'], $data['character'] ?? null
        ); // ex: 'D'
        MacroProcessus::create($data + ['code' => $code]);
    });

    return back()->with('success','Macro-processus créé (code auto).');
}

public function update(Request $request, MacroProcessus $macro)
{
    $data = $request->validate(['name' => ['required','string','max:255']]);
    $macro->update($data);
    return back()->with('success','Macro renommé.');
}


    public function destroy(MacroProcessus $macro)
    {
        $macro->delete();
        return back()->with('success','Macro-processus supprimé.');
    }
}
