<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private function t()
    {
        return DB::connection('tenant');
    }

    public function index()
    {
        return Inertia::render('dashboards/Process/Core/Settings/Index', [
            'routes' => [
                'maturity'    => route('process.core.process.settings.maturity-scales'),
                'criticality' => route('process.core.process.settings.criticality'),
                'raci'        => route('process.core.process.settings.raci-roles'),
                'idea'        => route('process.core.process.settings.idea-axes'),
                'kpi'         => route('process.core.process.settings.kpi-categories'),
                'links'       => route('process.core.process.settings.link-types'),
                'controls'    => route('process.core.process.settings.control-types'),
                'criticalityNorms' => route('process.core.process.settings.criticality-norms'),
            ],
        ]);
    }

    public function maturityScales()
    {
        $items = $this->t()->table('process_maturity_scales')
            ->orderBy('sort')
            ->orderBy('rank')
            ->get(['id', 'code', 'label', 'sort', 'rank', 'min_score', 'max_score', 'color']);

        return Inertia::render('dashboards/Process/Core/Settings/Maturity', compact('items'));
    }

    public function criticality()
    {
        $criteria = $this->t()->table('process_criticality_criteria')
            ->orderBy('code')
            ->get(['id', 'code', 'label', 'weight']);

        $levels = $this->t()->table('process_criticality_levels')
            ->orderBy('weight')
            ->get(['id', 'code', 'label', 'weight', 'color']);

        return Inertia::render('dashboards/Process/Core/Settings/Criticality', compact('criteria', 'levels'));
    }

    public function raciRoles()
    {
        $items = $this->t()->table('activity_raci_roles')
            ->orderBy('code')
            ->get(['id', 'code', 'label', 'description']);

        return Inertia::render('dashboards/Process/Core/Settings/RaciRoles', compact('items'));
    }

    public function ideaAxes()
    {
        $items = $this->t()->table('activity_idea_axes')
            ->orderBy('code')
            ->get(['id', 'code', 'label']);

        return Inertia::render('dashboards/Process/Core/Settings/IdeaAxes', compact('items'));
    }

    public function kpiCategories()
    {
        $items = $this->t()->table('process_kpi_categories')
            ->orderBy('sort')
            ->get(['id', 'code', 'label', 'sort']);

        return Inertia::render('dashboards/Process/Core/Settings/KpiCategories', compact('items'));
    }

    public function linkTypes()
    {
        $items = $this->t()->table('process_link_types')
            ->orderBy('code')
            ->get(['id', 'code', 'label']);

        return Inertia::render('dashboards/Process/Core/Settings/LinkTypes', compact('items'));
    }

    public function controlTypes()
    {
        $items = $this->t()->table('activity_control_types')
            ->orderBy('code')
            ->get(['id', 'code', 'label']);

        return Inertia::render('dashboards/Process/Core/Settings/ControlTypes', compact('items'));
    }

    /**
     * ⚙️ Normes de criticité — affichage
     */
    public function criticalityNorms()
    {
        $items = $this->t()
            ->table('process_criticality_norms')
            ->orderBy('min_percent')
            ->get([
                'id',
                'min_percent',
                'max_percent',
                'color',
                'alert_label',
                'alert_level',
                'actions',
                'user_id',
                'updated_at'
            ]);

        return Inertia::render('dashboards/Process/Core/Settings/CriticalityNorms', [
            'items' => $items
        ]);
    }

    /**
     * 💾 Enregistrement des normes (ajout/modif uniquement)
     */
    public function saveCriticalityNorms(Request $request)
    {
        $data = $request->validate([
            'norms' => 'required|array|min:1',
            'norms.*.min_percent' => 'required|numeric|min:0|max:100',
            'norms.*.max_percent' => 'required|numeric|min:0|max:100',
            'norms.*.color' => 'required|string|max:20',
            'norms.*.alert_label' => 'nullable|string|max:100',
            'norms.*.alert_level' => 'nullable|string|max:100',
            'norms.*.actions' => 'nullable|string',
        ]);

        $norms = $data['norms'];
        $userId = auth()->id();

        // Vérifie la borne finale
        $lastMax = collect($norms)->max('max_percent');
        if ($lastMax < 100) {
            return back()->with('error', '⚠️ La dernière borne doit se terminer à 100 %.');
        }

        $db = $this->t();

        foreach ($norms as $n) {
            $db->table('process_criticality_norms')->updateOrInsert(
                [
                    'min_percent' => $n['min_percent'],
                    'max_percent' => $n['max_percent'],
                ],
                [
                    'color'       => $n['color'],
                    'alert_label' => $n['alert_label'],
                    'alert_level' => $n['alert_level'],
                    'actions'     => $n['actions'],
                    'user_id'     => $userId,
                    'updated_at'  => now(),
                ]
            );
        }

        return back()->with('success', '✅ Normes enregistrées avec succès.');
    }
}
