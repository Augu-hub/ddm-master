<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissionController extends Controller
{
    /**
     * Affiche le formulaire de création d'une mission d'audit.
     *
     * Table `missions` – colonnes réelles :
     *   id, code, fpm_number, audit_exercise_id, entity_id, mission_type_id,
     *   title, objective, domain, description (bigint FK nullable),
     *   reference_document, priority (enum: basse|moyenne|haute|critique),
     *   planned_start_date, planned_end_date, planned_duration_days,
     *   status (enum: brouillon|planifiée|en_cours|terminée|annulée),
     *   created_by, updated_by, created_at, updated_at, deleted_at
     *
     * Pivots :
     *   mission_risk       (id, mission_id, risk_id, created_at, updated_at)
     *   mission_competency (id, mission_id, competency_id, minimum_level, created_at, updated_at)
     */
    public function create()
    {
        try {
            // ── Exercices audit ──────────────────────────────────────────
            $exercises = DB::table('audit_exercises')
                ->where('is_active', 1)
                ->orderByDesc('year')
                ->select('id', 'code', 'name', 'year')
                ->get()
                ->map(fn($e) => [
                    'id'   => (int) $e->id,
                    'code' => $e->code,
                    'name' => $e->name,
                    'year' => (int) $e->year,
                ])
                ->toArray();

            // ── Types de mission ─────────────────────────────────────────
            $missionTypes = DB::table('mission_types')
                ->where('is_active', 1)
                ->orderBy('code')
                ->select('id', 'code', 'label')
                ->get()
                ->map(fn($mt) => [
                    'id'    => (int) $mt->id,
                    'code'  => $mt->code,
                    'label' => $mt->label,
                ])
                ->toArray();

            // ── Entités ──────────────────────────────────────────────────
            $entities = DB::table('entities')
                ->orderBy('name')
                ->select('id', 'code_base', 'name')
                ->get()
                ->map(fn($e) => [
                    'id'        => (int) $e->id,
                    'code_base' => $e->code_base,
                    'name'      => $e->name,
                ])
                ->toArray();

            // ── Compétences ──────────────────────────────────────────────
            $competencies = DB::table('competencies')
                ->orderBy('code')
                ->select('id', 'code', 'name')
                ->get()
                ->map(fn($c) => [
                    'id'   => (int) $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                ])
                ->toArray();

            // ── Missions FPM (audit_mission_requests) ────────────────────
            $fpmMissions = DB::table('audit_mission_requests as amr')
                ->leftJoin('entities as e', 'amr.entity_id', '=', 'e.id')
                ->where('amr.status', 'draft')
                ->orderByDesc('amr.id')
                ->select(
                    'amr.id', 'amr.code', 'amr.mission_objective as title',
                    'amr.description', 'amr.start_date', 'amr.end_date',
                    'amr.level', 'amr.coefficient', 'amr.entity_id', 'amr.related_risk_id',
                    'e.code_base as entity_code', 'e.name as entity_name'
                )
                ->limit(100)
                ->get()
                ->map(function ($m) {
                    $riskIds = $m->related_risk_id ? [(int) $m->related_risk_id] : [];
                    return [
                        'id'          => (int) $m->id,
                        'code'        => $m->code,
                        'title'       => $m->title,
                        'objective'   => $m->description,
                        'start_date'  => $m->start_date,
                        'end_date'    => $m->end_date,
                        'level'       => $m->level,
                        'coefficient' => $m->coefficient,
                        'entity_id'   => $m->entity_id ? (int) $m->entity_id : null,
                        'entity_ids'  => $m->entity_id ? [(int) $m->entity_id] : [],
                        'risk_ids'    => $riskIds,
                        'risk_count'  => count($riskIds),
                        'entity'      => $m->entity_id ? [
                            'id'        => (int) $m->entity_id,
                            'code_base' => $m->entity_code,
                            'name'      => $m->entity_name,
                        ] : null,
                    ];
                })
                ->toArray();

            // ── Missions audit (audit_missions) ──────────────────────────
            $auditMissions = DB::table('audit_missions as am')
                ->leftJoin('mission_types as mt', 'am.mission_type_id', '=', 'mt.id')
                ->orderByDesc('am.created_at')
                ->select(
                    'am.id', 'am.code', 'am.title', 'am.objective', 'am.but',
                    'am.scheduled_start_date as planned_start_date',
                    'am.scheduled_end_date as planned_end_date',
                    'am.mission_type_id', 'am.priority_rank as priority', 'am.status',
                    'mt.code as type_code', 'mt.label as type_label'
                )
                ->limit(100)
                ->get()
                ->map(function ($m) {
                    // Entités via audit_mission_entities
                    $entityIds  = [];
                    $entityList = [];
                    if (DB::select("SHOW TABLES LIKE 'audit_mission_entities'")) {
                        $entityIds = DB::table('audit_mission_entities')
                            ->where('audit_mission_id', $m->id)
                            ->pluck('entity_id')
                            ->map(fn($id) => (int) $id)
                            ->toArray();
                        if ($entityIds) {
                            $entityList = DB::table('entities')
                                ->whereIn('id', $entityIds)
                                ->select('id', 'code_base', 'name')
                                ->get()
                                ->map(fn($e) => [
                                    'id'        => (int) $e->id,
                                    'code_base' => $e->code_base,
                                    'name'      => $e->name,
                                ])
                                ->toArray();
                        }
                    }

                    // Risques via audit_mission_risks
                    $riskIds = [];
                    if (DB::select("SHOW TABLES LIKE 'audit_mission_risks'")) {
                        $riskIds = DB::table('audit_mission_risks')
                            ->where('audit_mission_id', $m->id)
                            ->pluck('risk_id')
                            ->map(fn($id) => (int) $id)
                            ->toArray();
                    }

                    // Priorité numérique → libellé OHADA
                    $priority = $m->priority;
                    if (is_numeric($priority)) {
                        $priority = [1 => 'basse', 2 => 'moyenne', 3 => 'haute', 4 => 'critique'][(int) $priority] ?? 'moyenne';
                    }

                    return [
                        'id'                 => (int) $m->id,
                        'code'               => $m->code,
                        'title'              => $m->title,
                        'objective'          => $m->objective ?: $m->but,
                        'planned_start_date' => $m->planned_start_date,
                        'planned_end_date'   => $m->planned_end_date,
                        'entity_ids'         => $entityIds,
                        'entities'           => $entityList,
                        'mission_type_id'    => $m->mission_type_id ? (int) $m->mission_type_id : null,
                        'priority'           => $priority,
                        'status'             => $m->status,
                        'type_code'          => $m->type_code,
                        'type_label'         => $m->type_label,
                        'risk_ids'           => $riskIds,
                        'risk_count'         => count($riskIds),
                    ];
                })
                ->toArray();

            // ── Risques ──────────────────────────────────────────────────
            $risks = DB::table('risks')
                ->whereNull('deleted_at')
                ->whereIn('status', ['identified', 'assessed'])
                ->orderBy('code')
                ->select('id', 'code', 'label', 'entity_id', 'process_id')
                ->get()
                ->map(fn($r) => [
                    'id'         => (int) $r->id,
                    'code'       => $r->code,
                    'label'      => $r->label,
                    'entity_id'  => (int) $r->entity_id,
                    'process_id' => (int) $r->process_id,
                ])
                ->toArray();

            // ── Processus ────────────────────────────────────────────────
            $processes = DB::table('processes')
                ->select('id', 'code')
                ->get()
                ->map(fn($p) => [
                    'id'   => (int) $p->id,
                    'code' => $p->code,
                ])
                ->toArray();

            // ── Assignments ──────────────────────────────────────────────
            $assignments = DB::table('assignments')
                ->select('entity_id', 'mpa_type', 'mpa_id')
                ->get()
                ->map(fn($a) => [
                    'entity_id' => (int) $a->entity_id,
                    'mpa_type'  => $a->mpa_type,
                    'mpa_id'    => (int) $a->mpa_id,
                ])
                ->toArray();

            // ── Missions créées – historique ─────────────────────────────
            // On lit uniquement les colonnes qui existent dans `missions`
            $createdMissions = DB::table('missions')
                ->whereNull('deleted_at')
                ->where('created_by', auth()->id())
                ->orderByDesc('id')
                ->limit(50)
                ->select(
                    'id', 'code', 'fpm_number', 'title', 'objective', 'domain',
                    'priority', 'status', 'planned_start_date', 'planned_end_date',
                    'planned_duration_days', 'mission_type_id', 'created_at'
                )
                ->get()
                ->map(function ($m) {
                    $type = DB::table('mission_types')
                        ->select('code', 'label')
                        ->find($m->mission_type_id);
                    return [
                        'id'                    => (int) $m->id,
                        'code'                  => $m->code,
                        'fpm_number'            => $m->fpm_number,
                        'title'                 => $m->title,
                        'objective'             => $m->objective,
                        'domain'                => $m->domain,
                        'priority'              => $m->priority,
                        'status'                => $m->status,
                        'planned_start_date'    => $m->planned_start_date,
                        'planned_end_date'      => $m->planned_end_date,
                        'planned_duration_days' => (int) $m->planned_duration_days,
                        'type_code'             => $type->code  ?? '?',
                        'type_label'            => $type->label ?? '—',
                        'source'                => $m->fpm_number ? 'SUR DEMANDE' : 'INTERNE',
                        'created_at'            => \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i'),
                    ];
                })
                ->toArray();

            // ── Exercice actif (le plus récent) ──────────────────────────
            $activeExercise = $exercises[0] ?? null;

            return Inertia::render('dashboards/Audit/MissionA/create', [
                'exercises'       => $exercises,
                'missionTypes'    => $missionTypes,
                'entities'        => $entities,
                'competencies'    => $competencies,
                'fpmMissions'     => $fpmMissions,
                'auditMissions'   => $auditMissions,
                'risks'           => $risks,
                'processes'       => $processes,
                'assignments'     => $assignments,
                'createdMissions' => $createdMissions,
                'activeExercise'  => $activeExercise,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ MissionController@create : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire.');
        }
    }

    /**
     * Enregistre une nouvelle mission.
     *
     * Seules les colonnes réelles de `missions` sont insérées.
     * La colonne `description` est un bigint FK → non exposée en saisie libre.
     * Les champs inexistants (preoccupation, resultat, champ_mission,
     * fonction_processus, procedure) sont supprimés du formulaire.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'audit_exercise_id'     => 'required|exists:audit_exercises,id',
                'mission_type_id'       => 'required|exists:mission_types,id',
                'entity_ids'            => 'required|array|min:1',
                'entity_ids.*'          => 'integer|exists:entities,id',
                'title'                 => 'required|string|max:255',
                'objective'             => 'nullable|string',
                'domain'                => 'nullable|string|max:120',
                'reference_document'    => 'nullable|string|max:120',
                'priority'              => 'nullable|in:basse,moyenne,haute,critique',
                'planned_start_date'    => 'nullable|date',
                'planned_end_date'      => 'nullable|date|after_or_equal:planned_start_date',
                'planned_duration_days' => 'nullable|integer|min:0',
                'fpm_number'            => 'nullable|string|max:20',
                'risk_ids'              => 'nullable|array',
                'risk_ids.*'            => 'integer|exists:risks,id',
                'competency_ids'        => 'nullable|array',
                'competency_ids.*'      => 'integer|exists:competencies,id',
            ]);

            $type     = DB::table('mission_types')->find($validated['mission_type_id']);
            $exercise = DB::table('audit_exercises')->find($validated['audit_exercise_id']);

            if (! $type || ! $exercise) {
                return back()
                    ->withErrors(['mission_type_id' => 'Type de mission ou exercice invalide.'])
                    ->withInput();
            }

            // ── Génération du code séquentiel ────────────────────────────
            $count = DB::table('missions')
                ->where('mission_type_id', $validated['mission_type_id'])
                ->where('audit_exercise_id', $validated['audit_exercise_id'])
                ->count() + 1;

            $yearSuffix = substr((string) $exercise->year, -2);
            $code = $type->code . '-' . str_pad($count, 3, '0', STR_PAD_LEFT) . '-' . $yearSuffix;

            // ── Entité principale ─────────────────────────────────────────
            $firstEntityId = (int) $validated['entity_ids'][0];

            // ── Durée (recalcul sécurisé si absente) ─────────────────────
            $duration = (int) ($validated['planned_duration_days'] ?? 0);
            if ($duration === 0
                && ! empty($validated['planned_start_date'])
                && ! empty($validated['planned_end_date'])
            ) {
                $duration = max(0, (int) ceil(
                    (strtotime($validated['planned_end_date']) - strtotime($validated['planned_start_date'])) / 86400
                ));
            }

            // ── Insert dans missions ──────────────────────────────────────
            $missionId = DB::table('missions')->insertGetId([
                'code'                  => $code,
                'fpm_number'            => $validated['fpm_number']          ?? null,
                'audit_exercise_id'     => $validated['audit_exercise_id'],
                'mission_type_id'       => $validated['mission_type_id'],
                'entity_id'             => $firstEntityId,
                'title'                 => $validated['title'],
                'objective'             => $validated['objective']            ?? null,
                'domain'                => $validated['domain']               ?? null,
                'reference_document'    => $validated['reference_document']   ?? null,
                'priority'              => $validated['priority']             ?? 'moyenne',
                'planned_start_date'    => $validated['planned_start_date']   ?? null,
                'planned_end_date'      => $validated['planned_end_date']     ?? null,
                'planned_duration_days' => $duration,
                // `description` (bigint FK) : non géré ici, reste NULL
                'status'                => 'brouillon',
                'created_by'            => auth()->id(),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // ── Pivots risques (mission_risk) ─────────────────────────────
            if (! empty($validated['risk_ids'])) {
                DB::table('mission_risk')->insert(
                    array_map(fn($riskId) => [
                        'mission_id' => $missionId,
                        'risk_id'    => (int) $riskId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $validated['risk_ids'])
                );
            }

            // ── Pivots compétences (mission_competency) ───────────────────
            if (! empty($validated['competency_ids'])) {
                DB::table('mission_competency')->insert(
                    array_map(fn($compId) => [
                        'mission_id'    => $missionId,
                        'competency_id' => (int) $compId,
                        'minimum_level' => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ], $validated['competency_ids'])
                );
            }

            Log::info("✅ Mission créée : {$code}", [
                'mission_id'  => $missionId,
                'entity_id'   => $firstEntityId,
                'entity_ids'  => $validated['entity_ids'],
                'risks'       => count($validated['risk_ids'] ?? []),
                'competences' => count($validated['competency_ids'] ?? []),
            ]);

            return redirect()->back()->with('success', "Mission créée avec succès : {$code}");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('❌ MissionController@store : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage())
                ->withInput();
        }
    }
}