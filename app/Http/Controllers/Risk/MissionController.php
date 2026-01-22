<?php

namespace App\Http\Controllers\Risk;

use App\Models\Mission;
use App\Models\Audit\AuditExercise;
use App\Models\Audit\Mission\MissionType;
use App\Models\Audit\Risk;
use App\Models\Param\Competency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
class MissionController extends Controller
{
    public function create()
    {
        $exercises     = AuditExercise::where('is_active', true)
            ->orderByDesc('year')
            ->get(['id', 'code', 'name', 'year']);

        $missionTypes  = MissionType::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'label']);

        $risks         = Risk::select('id', 'code', 'label')
            ->orderBy('code')
            ->get();

        $competencies  = Competency::where('status', 'active')
            ->with('category')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('dashboards/Audit/MissionA/create', [
            'exercises'     => $exercises,
            'missionTypes'  => $missionTypes,
            'risks'         => $risks,
            'competencies'  => $competencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'audit_exercise_id'   => 'required|exists:audit_exercises,id',
            'mission_type_id'     => 'required|exists:mission_types,id',
            'fpm_number'          => 'nullable|string|max:20',
            'title'               => 'required|string|max:255',
            'objective'           => 'nullable|string',
            'domain'              => 'nullable|string|max:120',
            'reference_document'  => 'nullable|string|max:120',
            'priority'            => 'required|in:basse,moyenne,haute,critique',
            'planned_start_date'  => 'nullable|date',
            'planned_end_date'    => 'nullable|date|after_or_equal:planned_start_date',
            'risk_ids'            => 'array',
            'risk_ids.*'          => 'exists:risks,id',
            'competency_ids'      => 'array',
            'competency_levels'   => 'array',
            'competency_levels.*' => 'integer|min:1|max:5',
        ]);

        // Génération du code mission
        $type   = MissionType::find($validated['mission_type_id']);
        $year   = AuditExercise::find($validated['audit_exercise_id'])->year ?? date('Y');
        $year   = substr($year, -2); // 26 pour 2026

        $count  = Mission::where('mission_type_id', $validated['mission_type_id'])
            ->where('audit_exercise_id', $validated['audit_exercise_id'])
            ->count() + 1;

        $code = $type->code . '-' . str_pad($count, 3, '0', STR_PAD_LEFT) . '-' . $year;

        $mission = Mission::create([
            'code'                 => $code,
            'fpm_number'           => $validated['fpm_number'],
            'audit_exercise_id'    => $validated['audit_exercise_id'],
            'mission_type_id'      => $validated['mission_type_id'],
            'title'                => $validated['title'],
            'objective'            => $validated['objective'],
            'domain'               => $validated['domain'],
            'reference_document'   => $validated['reference_document'],
            'priority'             => $validated['priority'],
            'planned_start_date'   => $validated['planned_start_date'],
            'planned_end_date'     => $validated['planned_end_date'],
            'planned_duration_days'=> $validated['planned_duration_days'] ?? 0,
            'status'               => 'brouillon',
            'created_by'           => auth()->id(),
        ]);

        // Attacher les risques
        if (!empty($validated['risk_ids'])) {
            $mission->risks()->attach($validated['risk_ids']);
        }

        // Attacher les compétences avec niveau
        if (!empty($request->competency_ids)) {
            $syncData = [];
            foreach ($request->competency_ids as $compId) {
                $level = $request->competency_levels[$compId] ?? 1;
                $syncData[$compId] = ['minimum_level' => $level];
            }
            $mission->competencies()->sync($syncData);
        }

        return redirect()->route('missions.index')
            ->with('success', "Mission créée avec succès : {$mission->code}");
    }

    // Tu pourras ajouter index(), show(), edit(), update(), destroy() plus tard
}