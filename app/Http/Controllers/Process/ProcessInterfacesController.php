<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\Process;
use App\Models\Activity;
use App\Models\ProcessInput;
use App\Models\ProcessOutput;
use App\Models\FunctionAssignment;
use App\Models\Assignment;
use App\Models\AssignmentFunction;

class ProcessInterfacesController extends Controller
{
    public function get($id)
    {
        $process = Process::findOrFail($id);

        // 🔹 ACTIVITÉS DU PROCESS
        $activities = Activity::where('process_id', $id)->get()
        ->map(function($a) {
            // Fonction de l’activité via assignment_functions
            $assign = Assignment::where('mpa_type','activity')
                ->where('mpa_id',$a->id)->first();

            $role = null;
            $user = null;

            if ($assign) {
                $af = AssignmentFunction::where('assignment_id', $assign->id)->first();
                if ($af) {
                    $userAssign = FunctionAssignment::where('function_id',$af->function_id)->first();
                    if ($userAssign && $userAssign->user) {
                        $user = $userAssign->user->name;
                    }
                }
            }

            return [
                'id' => $a->id,
                'activity' => $a->name,
                'actor' => $user ?? '—'
            ];
        });

        // 🔹 INPUTS
        $inputs = ProcessInput::where('process_id', $id)
            ->get()
            ->map(fn($i) => ['id'=>$i->id, 'label'=>$i->label]);

        // 🔹 OUTPUTS
        $outputs = ProcessOutput::where('process_id', $id)
            ->get()
            ->map(fn($o) => ['id'=>$o->id, 'label'=>$o->label]);

        return response()->json([
            'inputs' => $inputs,
            'outputs' => $outputs,
            'activities' => $activities,
        ]);
    }
}
