<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CriticalityController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    public function index()
    {
        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Index', [
            'routes' => [
                'maturity'       => route('process.core.process.evaluations.criticality.maturity'),
                'motricity'      => route('process.core.process.evaluations.criticality.motricity'),
                'transversality' => route('process.core.process.evaluations.criticality.transversality'),
                'strategic'      => route('process.core.process.evaluations.criticality.strategic'),
                'norms'          => route('process.core.process.evaluations.criticality.norms'),
            ],
        ]);
    }

    private function getScaleWithLevels($tableName, $levelsTableName)
    {
        return $this->t()->table($tableName)
            ->orderBy('id')
            ->get(['id', 'code', 'label', 'description', 'created_at', 'updated_at'])
            ->map(function ($scale) use ($levelsTableName) {
                $scale->levels = $this->t()->table($levelsTableName)
                    ->where('scale_id', $scale->id)
                    ->orderBy('level_score')
                    ->get(['id', 'level_score', 'level_label', 'level_description'])
                    ->toArray();
                return $scale;
            });
    }

    private function getDefaultLevelsLabels($type = 'generic')
    {
        $labels = [
            'maturity' => [
                1 => 'Fonctionnement de base',
                2 => 'Défini',
                3 => 'Maîtrisé',
                4 => 'Optimisé',
                5 => 'Amélioration permanente',
            ],
            'motricity' => [
                1 => 'Très faible',
                2 => 'Faible',
                3 => 'Moyen',
                4 => 'Fort',
                5 => 'Très fort',
            ],
            'transversality' => [
                1 => 'Isolé',
                2 => 'Limité',
                3 => 'Modéré',
                4 => 'Étendu',
                5 => 'Transversal total',
            ],
            'strategic' => [
                1 => 'Non stratégique',
                2 => 'Peu stratégique',
                3 => 'Stratégique',
                4 => 'Très stratégique',
                5 => 'Critique stratégique',
            ],
        ];

        return $labels[$type] ?? $labels['generic'];
    }

    private function createDefaultLevels($scaleId, $levelsTableName, $type = 'generic')
    {
        $defaultLabels = $this->getDefaultLevelsLabels($type);

        $db = $this->t();
        for ($i = 1; $i <= 5; $i++) {
            $db->table($levelsTableName)->insert([
                'scale_id'           => $scaleId,
                'level_score'        => $i,
                'level_label'        => $defaultLabels[$i],
                'level_description'  => '',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    /* MATURITÉ */
    public function maturity()
    {
        $items = $this->getScaleWithLevels('process_maturity_scales', 'process_maturity_scale_levels');
        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Maturity', compact('items'));
    }

    public function saveMaturity(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer',
            'code'        => 'required|string|max:50',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $db = $this->t();
        $data = [
            'code'        => $validated['code'],
            'label'       => $validated['label'],
            'description' => $validated['description'],
            'updated_at'  => now(),
        ];

        if (!empty($validated['id'])) {
            $db->table('process_maturity_scales')->where('id', $validated['id'])->update($data);
        } else {
            $data['created_at'] = now();
            $scaleId = $db->table('process_maturity_scales')->insertGetId($data);
            $this->createDefaultLevels($scaleId, 'process_maturity_scale_levels', 'maturity');
        }

        return back()->with('success', '✅ Enregistré.');
    }

    public function saveMaturityLevels(Request $request, $id)
    {
        $validated = $request->validate([
            'levels'                      => 'required|array',
            'levels.*.id'                 => 'nullable|integer',
            'levels.*.level_score'        => 'required|integer|min:0|max:10',
            'levels.*.level_label'        => 'required|string|max:255',
            'levels.*.level_description'  => 'nullable|string|max:1000',
        ]);

        $db = $this->t();

        foreach ($validated['levels'] as $level) {
            $levelData = [
                'scale_id'           => $id,
                'level_score'        => $level['level_score'],
                'level_label'        => $level['level_label'],
                'level_description'  => $level['level_description'] ?? null,
                'updated_at'         => now(),
            ];

            if (!empty($level['id'])) {
                $db->table('process_maturity_scale_levels')->where('id', $level['id'])->update($levelData);
            } else {
                $levelData['created_at'] = now();
                $db->table('process_maturity_scale_levels')->insert($levelData);
            }
        }

        return back()->with('success', '✅ Niveaux enregistrés.');
    }

    public function deleteMaturity($id)
    {
        $this->t()->table('process_maturity_scales')->where('id', $id)->delete();
        return back()->with('success', '🗑️ Supprimé.');
    }

    /* MOTRICITÉ */
    public function motricity()
    {
        $items = $this->getScaleWithLevels('process_motricity_scales', 'process_motricity_scale_levels');
        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Motricity', compact('items'));
    }

    public function saveMotricity(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer',
            'code'        => 'required|string|max:50',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $db = $this->t();
        $data = [
            'code'        => $validated['code'],
            'label'       => $validated['label'],
            'description' => $validated['description'],
            'updated_at'  => now(),
        ];

        if (!empty($validated['id'])) {
            $db->table('process_motricity_scales')->where('id', $validated['id'])->update($data);
        } else {
            $data['created_at'] = now();
            $scaleId = $db->table('process_motricity_scales')->insertGetId($data);
            $this->createDefaultLevels($scaleId, 'process_motricity_scale_levels', 'motricity');
        }

        return back()->with('success', '✅ Enregistré.');
    }

    public function saveMotricityLevels(Request $request, $id)
    {
        $validated = $request->validate([
            'levels'                      => 'required|array',
            'levels.*.id'                 => 'nullable|integer',
            'levels.*.level_score'        => 'required|integer|min:0|max:10',
            'levels.*.level_label'        => 'required|string|max:255',
            'levels.*.level_description'  => 'nullable|string|max:1000',
        ]);

        $db = $this->t();

        foreach ($validated['levels'] as $level) {
            $levelData = [
                'scale_id'           => $id,
                'level_score'        => $level['level_score'],
                'level_label'        => $level['level_label'],
                'level_description'  => $level['level_description'] ?? null,
                'updated_at'         => now(),
            ];

            if (!empty($level['id'])) {
                $db->table('process_motricity_scale_levels')->where('id', $level['id'])->update($levelData);
            } else {
                $levelData['created_at'] = now();
                $db->table('process_motricity_scale_levels')->insert($levelData);
            }
        }

        return back()->with('success', '✅ Niveaux enregistrés.');
    }

    public function deleteMotricity($id)
    {
        $this->t()->table('process_motricity_scales')->where('id', $id)->delete();
        return back()->with('success', '🗑️ Supprimé.');
    }

    /* TRANSVERSALITÉ */
    public function transversality()
    {
        $items = $this->getScaleWithLevels('process_transversality_scales', 'process_transversality_scale_levels');
        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Transversality', compact('items'));
    }

    public function saveTransversality(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer',
            'code'        => 'required|string|max:50',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $db = $this->t();
        $data = [
            'code'        => $validated['code'],
            'label'       => $validated['label'],
            'description' => $validated['description'],
            'updated_at'  => now(),
        ];

        if (!empty($validated['id'])) {
            $db->table('process_transversality_scales')->where('id', $validated['id'])->update($data);
        } else {
            $data['created_at'] = now();
            $scaleId = $db->table('process_transversality_scales')->insertGetId($data);
            $this->createDefaultLevels($scaleId, 'process_transversality_scale_levels', 'transversality');
        }

        return back()->with('success', '✅ Enregistré.');
    }

    public function saveTransversalityLevels(Request $request, $id)
    {
        $validated = $request->validate([
            'levels'                      => 'required|array',
            'levels.*.id'                 => 'nullable|integer',
            'levels.*.level_score'        => 'required|integer|min:0|max:10',
            'levels.*.level_label'        => 'required|string|max:255',
            'levels.*.level_description'  => 'nullable|string|max:1000',
        ]);

        $db = $this->t();

        foreach ($validated['levels'] as $level) {
            $levelData = [
                'scale_id'           => $id,
                'level_score'        => $level['level_score'],
                'level_label'        => $level['level_label'],
                'level_description'  => $level['level_description'] ?? null,
                'updated_at'         => now(),
            ];

            if (!empty($level['id'])) {
                $db->table('process_transversality_scale_levels')->where('id', $level['id'])->update($levelData);
            } else {
                $levelData['created_at'] = now();
                $db->table('process_transversality_scale_levels')->insert($levelData);
            }
        }

        return back()->with('success', '✅ Niveaux enregistrés.');
    }

    public function deleteTransversality($id)
    {
        $this->t()->table('process_transversality_scales')->where('id', $id)->delete();
        return back()->with('success', '🗑️ Supprimé.');
    }

    /* STRATÉGIQUE */
    public function strategic()
    {
        $items = $this->getScaleWithLevels('process_strategic_weight_scales', 'process_strategic_weight_scale_levels');
        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Strategic', compact('items'));
    }

    public function saveStrategic(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer',
            'code'        => 'required|string|max:50',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $db = $this->t();
        $data = [
            'code'        => $validated['code'],
            'label'       => $validated['label'],
            'description' => $validated['description'],
            'updated_at'  => now(),
        ];

        if (!empty($validated['id'])) {
            $db->table('process_strategic_weight_scales')->where('id', $validated['id'])->update($data);
        } else {
            $data['created_at'] = now();
            $scaleId = $db->table('process_strategic_weight_scales')->insertGetId($data);
            $this->createDefaultLevels($scaleId, 'process_strategic_weight_scale_levels', 'strategic');
        }

        return back()->with('success', '✅ Enregistré.');
    }

    public function saveStrategicLevels(Request $request, $id)
    {
        $validated = $request->validate([
            'levels'                      => 'required|array',
            'levels.*.id'                 => 'nullable|integer',
            'levels.*.level_score'        => 'required|integer|min:0|max:10',
            'levels.*.level_label'        => 'required|string|max:255',
            'levels.*.level_description'  => 'nullable|string|max:1000',
        ]);

        $db = $this->t();

        foreach ($validated['levels'] as $level) {
            $levelData = [
                'scale_id'           => $id,
                'level_score'        => $level['level_score'],
                'level_label'        => $level['level_label'],
                'level_description'  => $level['level_description'] ?? null,
                'updated_at'         => now(),
            ];

            if (!empty($level['id'])) {
                $db->table('process_strategic_weight_scale_levels')->where('id', $level['id'])->update($levelData);
            } else {
                $levelData['created_at'] = now();
                $db->table('process_strategic_weight_scale_levels')->insert($levelData);
            }
        }

        return back()->with('success', '✅ Niveaux enregistrés.');
    }

    public function deleteStrategic($id)
    {
        $this->t()->table('process_strategic_weight_scales')->where('id', $id)->delete();
        return back()->with('success', '🗑️ Supprimé.');
    }

    /* NORMES */
    public function norms()
    {
        $items = $this->t()->table('process_criticality_norms')->orderBy('min_percent')->get();
        return Inertia::render('dashboards/Process/Core/Evaluations/Criticality/Norms', compact('items'));
    }

    public function saveNorms(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer',
            'min_percent' => 'required|numeric|min:0|max:100',
            'max_percent' => 'required|numeric|min:0|max:100',
            'color'       => 'required|string|max:20',
            'alert_label' => 'nullable|string|max:100',
            'alert_level' => 'nullable|string|max:100',
            'actions'     => 'nullable|string|max:1000',
        ]);

        $db = $this->t();
        $data = [
            'min_percent' => $validated['min_percent'],
            'max_percent' => $validated['max_percent'],
            'color'       => $validated['color'],
            'alert_label' => $validated['alert_label'],
            'alert_level' => $validated['alert_level'],
            'actions'     => $validated['actions'],
            'updated_at'  => now(),
        ];

        if (!empty($validated['id'])) {
            $db->table('process_criticality_norms')->where('id', $validated['id'])->update($data);
        } else {
            $data['created_at'] = now();
            $db->table('process_criticality_norms')->insert($data);
        }

        return back()->with('success', '✅ Enregistré.');
    }

    public function deleteNorm($id)
    {
        $this->t()->table('process_criticality_norms')->where('id', $id)->delete();
        return back()->with('success', '🗑️ Supprimé.');
    }
}