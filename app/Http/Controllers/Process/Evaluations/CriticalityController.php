<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CriticalityController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    /* ─────────────────────────────────────────────
     *  Vue d’ensemble : affiche toutes les échelles
     * ───────────────────────────────────────────── */
    public function index()
    {
        $t = $this->t();

        $maturity = $t->table('process_maturity_levels')->orderBy('score')->get();
        $motricity = $t->table('process_motricity_scales')->orderBy('score')->get();
        $transversality = $t->table('process_transversality_scales')->orderBy('score')->get();
        $strategic = $t->table('process_strategic_weight_scales')->orderBy('score')->get();

        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Index', [
            'maturity'       => $maturity,
            'motricity'      => $motricity,
            'transversality' => $transversality,
            'strategic'      => $strategic,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  Maturité
     * ───────────────────────────────────────────── */
    public function maturity()
    {
        $items = $this->t()->table('process_maturity_levels')
            ->orderBy('score')->get(['score','label','description']);

        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Maturity', [
            'items' => $items,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  Motricité
     * ───────────────────────────────────────────── */
    public function motricity()
    {
        $items = $this->t()->table('process_motricity_scales')
            ->orderBy('score')->get(['score','label','description']);

        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Motricity', [
            'items' => $items,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  Transversalité
     * ───────────────────────────────────────────── */
    public function transversality()
    {
        $items = $this->t()->table('process_transversality_scales')
            ->orderBy('score')->get(['score','label','description']);

        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Transversality', [
            'items' => $items,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  Poids stratégique
     * ───────────────────────────────────────────── */
    public function strategic()
    {
        $items = $this->t()->table('process_strategic_weight_scales')
            ->orderBy('score')->get(['score','label','description']);

        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Strategic', [
            'items' => $items,
        ]);
    }

    /* ─────────────────────────────────────────────
     *  MISE À JOUR (depuis les formulaires Vue3)
     * ───────────────────────────────────────────── */
    public function update(Request $request, string $table)
    {
        $allowed = [
            'process_maturity_levels',
            'process_motricity_scales',
            'process_transversality_scales',
            'process_strategic_weight_scales',
        ];

        if (!in_array($table, $allowed)) {
            abort(400, "Table non autorisée.");
        }

        $validated = $request->validate([
            'score'       => 'required|integer|min:1|max:5',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $db = $this->t();
        $db->table($table)->updateOrInsert(['score' => $validated['score']], $validated);

        Log::info("✅ [$table] Score {$validated['score']} mis à jour dans le tenant.");

        return redirect()->back()->with('success', 'Échelle mise à jour avec succès.');
    }
}
