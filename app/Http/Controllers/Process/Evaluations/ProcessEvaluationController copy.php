<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessEvaluationController extends Controller
{
    /**
     * Connexion locataire (tenant)
     */
    protected function t()
    {
        return DB::connection('tenant');
    }

    /**
     * Page principale : évaluation des processus selon la fonction de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $t = $this->t();
        $user = Auth::user();

        // Étape active (maturité, motricité, transversalité, stratégique)
        $step = $request->get('step', 'maturity');

        // 🔹 1. Récupération de la fonction liée à l'utilisateur
        $link = $t->table('function_assignments as fa')
            ->join('entities as e', 'e.id', '=', 'fa.entity_id')
            ->join('functions as f', 'f.id', '=', 'fa.function_id')
            ->where('fa.user_id', $user->id)
            ->select(
                'fa.id',
                'fa.entity_id',
                'fa.function_id',
                'e.name as entity_name',
                'f.name as function_name'
            )
            ->first();

        // Si aucun rattachement trouvé
        if (!$link) {
            return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                'user' => $user,
                'link' => null,
                'macroProcesses' => [],
                'processes' => [],
                'activities' => [],
                'maturityLevels' => [],
                'motricityScales' => [],
                'transversalityScales' => [],
                'strategicScales' => [],
                'message' => 'Aucune fonction ni entité n’est associée à votre compte.',
                'step' => $step,
            ]);
        }

        // 🔹 2. Processus rattachés à la fonction (via assignments + assignment_functions)
        $assignments = $t->table('assignments as a')
            ->join('assignment_functions as af', 'af.assignment_id', '=', 'a.id')
            ->where('a.entity_id', $link->entity_id)
            ->where('af.function_id', $link->function_id)
            ->where('a.mpa_type', 'process')
            ->select('a.id', 'a.mpa_id', 'a.mpa_type')
            ->get();

        if ($assignments->isEmpty()) {
            return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                'user' => $user,
                'link' => $link,
                'macroProcesses' => [],
                'processes' => [],
                'activities' => [],
                'message' => 'Aucun processus n’est rattaché à votre fonction actuelle.',
                'step' => $step,
            ]);
        }

        $processIds = $assignments->pluck('mpa_id')->unique();

        // 🔹 3. Récupération des processus filtrés
        $processes = $t->table('processes as p')
            ->whereIn('p.id', $processIds)
            ->select('p.id', 'p.code', 'p.name', 'p.macro_process_id')
            ->orderBy('p.code')
            ->get();

        // 🔹 4. Macro-processus correspondants
        $macroProcesses = $t->table('macro_processes as m')
            ->whereIn('m.id', $processes->pluck('macro_process_id')->unique())
            ->select('m.id', 'm.code', 'm.name')
            ->orderBy('m.code')
            ->get();

        // 🔹 5. Activités rattachées à ces processus
        $activities = $t->table('activities as a')
            ->whereIn('a.process_id', $processIds)
            ->select('a.id', 'a.code', 'a.name', 'a.process_id')
            ->orderBy('a.code')
            ->get();

        // 🔹 6. Tables de référence (sélecteurs)
        $maturityLevels = $t->table('process_maturity_levels')
            ->select('id', 'score', 'label')
            ->orderBy('score')
            ->get();

        $motricityScales = $t->table('process_motricity_scales')
            ->select('id', 'score', 'label')
            ->orderBy('score')
            ->get();

        $transversalityScales = $t->table('process_transversality_scales')
            ->select('id', 'score', 'label')
            ->orderBy('score')
            ->get();

        $strategicScales = $t->table('process_strategic_weight_scales')
            ->select('id', 'score', 'label')
            ->orderBy('score')
            ->get();

        // ✅ Rendu de la vue Inertia
        return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
            'user' => $user,
            'link' => $link,
            'macroProcesses' => $macroProcesses,
            'processes' => $processes,
            'activities' => $activities,
            'maturityLevels' => $maturityLevels,
            'motricityScales' => $motricityScales,
            'transversalityScales' => $transversalityScales,
            'strategicScales' => $strategicScales,
            'step' => $step,
        ]);
    }

    /**
     * Sauvegarde d’une note d’évaluation sur une activité.
     */
    public function saveScores(Request $request)
    {
        $t = $this->t();
        $user = Auth::user();

        $validated = $request->validate([
            'activity_id' => 'required|integer|exists:activities,id',
            'maturity_score' => 'nullable|numeric|min:0|max:5',
            'motricity_score' => 'nullable|numeric|min:0|max:5',
            'transversality_score' => 'nullable|numeric|min:0|max:5',
            'strategic_score' => 'nullable|numeric|min:0|max:5',
        ]);

        // 🔹 Sauvegarde (table générique process_activity_scores)
        $exists = $t->table('process_activity_scores')
            ->where('activity_id', $validated['activity_id'])
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            $t->table('process_activity_scores')
                ->where('activity_id', $validated['activity_id'])
                ->where('user_id', $user->id)
                ->update([
                    'maturity_score' => $validated['maturity_score'],
                    'motricity_score' => $validated['motricity_score'],
                    'transversality_score' => $validated['transversality_score'],
                    'strategic_score' => $validated['strategic_score'],
                    'updated_at' => now(),
                ]);
        } else {
            $t->table('process_activity_scores')->insert([
                'activity_id' => $validated['activity_id'],
                'user_id' => $user->id,
                'maturity_score' => $validated['maturity_score'],
                'motricity_score' => $validated['motricity_score'],
                'transversality_score' => $validated['transversality_score'],
                'strategic_score' => $validated['strategic_score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Évaluation enregistrée avec succès.');
    }
}
