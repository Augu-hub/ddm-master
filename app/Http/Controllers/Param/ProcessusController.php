<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Processus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;            // <-- AJOUT
use App\Models\Param\MacroProcessus;            // <-- vérifie les namespaces

class ProcessusController extends Controller
{
public function store(Request $request)
    {
        $data = $request->validate([
            'macro_process_id' => ['required','exists:macro_processes,id'],
            'name'             => ['required','string','max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $macro = MacroProcessus::whereKey($data['macro_process_id'])
                ->lockForUpdate()->firstOrFail();

            $code = Processus::nextCodeForMacro($macro); // ex: P01D

            Processus::create([
                'macro_process_id' => $macro->id,
                'name'             => $data['name'],
                'code'             => $code,
            ]);
        });

        return back()->with('success','Processus créé (code auto).');
    }


    public function update(Request $request, Process $process)
    {
        $data = $request->validate([
            'macro_process_id' => ['required','exists:macro_processes,id'],
            'code'             => ['required','string','max:50', Rule::unique('processes','code')->ignore($process->id)],
            'name'             => ['required','string','max:255'],
        ]);

        $process->update($data);
        return back()->with('success','Processus mis à jour.');
    }

    public function destroy(Process $process)
    {
        $process->delete();
        return back()->with('success','Processus supprimé.');
    }
}
