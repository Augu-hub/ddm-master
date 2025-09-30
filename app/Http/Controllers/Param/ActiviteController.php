<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Activite;
use App\Models\Param\Processus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;  
class ActiviteController extends Controller
{
public function store(Request $request)
    {
        $data = $request->validate([
            'process_id'  => ['required','exists:processes,id'],
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($data) {
            $proc = Processus::whereKey($data['process_id'])
                ->lockForUpdate()->firstOrFail();

            $code = Activite::nextCodeForProcess($proc); // ex: A01P01D

            Activite::create([
                'process_id'  => $proc->id,
                'name'        => $data['name'],
                'code'        => $code,
                'description' => $data['description'] ?? null,
            ]);
        });

        return back()->with('success','Activité créée (code auto).');
    }

    public function update(Request $request, Activite $activity)
    {
        $data = $request->validate([
            'process_id'  => ['required','exists:processes,id'],
            'code'        => ['required','string','max:50', Rule::unique('activities','code')->ignore($activity->id)],
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
        ]);

        $activity->update($data);
        return back()->with('success','Activité mise à jour.');
    }

    public function destroy(Activite $activity)
    {
        $activity->delete();
        return back()->with('success','Activité supprimée.');
    }
}
