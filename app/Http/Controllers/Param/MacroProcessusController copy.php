<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\MacroProcessus;
use App\Models\Param\Processus;
use App\Models\Param\Activite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MacroProcessusController extends Controller
{
    public function index(Request $request)
    {
        // Plus de project_id ni de liste de projets
        $macros = MacroProcessus::orderBy('id','asc')
            ->get(['id','code','name','character','designation','kind']);

        $processes = Processus::with(['macro:id,name'])
            ->orderBy('id','asc')
            ->get(['id','macro_process_id','code','name']);

        $activities = Activite::with(['process:id,macro_process_id,name','process.macro:id,name'])
            ->orderBy('id','asc')
            ->get(['id','process_id','code','name','description']);

        return Inertia::render('dashboards/Param/Mpa/index', [
            'macros'     => $macros,
            'processes'  => $processes,
            'activities' => $activities,
            // plus de 'projects' ni 'filters'
        ]);
    }

    public function validateDefaults(Request $request)
    {
        // Plus de validation project_id
        $defaults = [
            ['name'=>'Direction',   'character'=>'D', 'designation'=>'Pilotage / Gouvernance', 'kind'=>'Direction'],
            ['name'=>'Réalisation', 'character'=>'R', 'designation'=>'Cœur de métier',        'kind'=>'Réalisation'],
            ['name'=>'Support',     'character'=>'S', 'designation'=>'Soutien / Support',     'kind'=>'Support'],
        ];

        DB::transaction(function () use ($defaults) {
            foreach ($defaults as $row) {
                $exists = MacroProcessus::where('kind',$row['kind'])->exists();
                if (!$exists) {
                    $code = $row['character'] ?? mb_strtoupper(mb_substr($row['kind'],0,1)); // D/R/S
                    MacroProcessus::create([
                        'code'        => $code,
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
            // plus de project_id
            'name'        => ['required','string','max:255'],
            'character'   => ['nullable','string','max:1'],
            'designation' => ['nullable','string','max:255'],
            'kind'        => ['required','in:Direction,Réalisation,Support'],
        ]);

        DB::transaction(function () use ($data) {
            // Code auto: caractère si donné, sinon 1ère lettre du type
            $code = $data['character'] ?? mb_strtoupper(mb_substr($data['kind'],0,1));
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
