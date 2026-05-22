<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Session};
use Inertia\Inertia;

class MissionPhaseAffectationController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // INDEX — rendu Inertia principal
    // ══════════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        try {
            $missionId   = $request->query('mission_id');
            $ddmMeta     = $this->loadDdmMeta();
            $allMissions = $this->loadAllMissions($ddmMeta);

            $mission     = null;
            $entities    = [];
            $phases      = [];
            $assignments = [];
            $auditeurs   = [];
            $forms       = [];
            $currentUser = $this->buildCurrentUser();

            if ($missionId) {
                $mission = $this->getMissionData((int) $missionId, $ddmMeta);

                if ($mission) {
                    $entities = $this->getEntities((int) $missionId);

                    if ($mission->mission_type_id) {
                        $phases = $this->getPhasesForType($mission->mission_type_id);
                    }

                    $assignments = $this->getAssignmentsData((int) $missionId);
                    $auditeurs   = $this->getAuditeursParEntite((int) $missionId);
                    $forms       = $this->loadFormsFromSession(
                        (int) ($mission->mission_type_id ?? 0),
                        $mission->audit_type_code ?? null
                    );

                    Log::info("[PhaseAffectation] ✅ [{$mission->code_mission}]"
                        . " entites="     . count($entities)
                        . " phases_grp="  . count($phases)
                        . " assignments=" . count($assignments)
                        . " forms="       . count($forms));
                }
            }

            return Inertia::render('dashboards/Audit/Mission/Phases/Affectation', [
                'allMissions' => $allMissions,
                'mission'     => $mission,
                'entities'    => $entities,
                'phases'      => $phases,
                'assignments' => $assignments,
                'auditeurs'   => $auditeurs,
                'forms'       => $forms,
                'currentUser' => $currentUser,
            ]);

        } catch (\Exception $e) {
            Log::error('[PhaseAffectation] ❌ FATAL index: ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CURRENT USER — rôle dans la mission
    // ══════════════════════════════════════════════════════════════════════════

    private function buildCurrentUser(): array
    {
        $user = Auth::user();
        if (!$user) return ['id' => null, 'name' => '', 'role_code' => ''];

        $roleCode = '';
        try {
            $auditorId = DB::table('auditors')->where('email', $user->email)->value('id');
            if ($auditorId) {
                $roleCode = DB::table('mission_phase_auditeurs as mpa')
                    ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
                    ->where('mpa.auditeur_id', $auditorId)
                    ->orderByRaw("COALESCE(mr.niveau, 99) ASC")
                    ->value(DB::raw("COALESCE(mr.code, mpa.role, '')")) ?? '';
            }
        } catch (\Exception $e) {
            Log::warning('[PhaseAffectation] buildCurrentUser: ' . $e->getMessage());
        }

        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'role_code' => $roleCode,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API GET — getAssignedPhases
    // ══════════════════════════════════════════════════════════════════════════

    public function getAssignedPhases(int $missionId)
    {
        try {
            // Vérifier que la mission existe
            $mission = DB::table('mission_programmation')->where('id', $missionId)->first();
            if (!$mission) {
                return response()->json(['error' => 'Mission introuvable.'], 404);
            }

            return response()->json([
                'success'     => true,
                'assignments' => $this->getAssignmentsData($missionId),
                'auditeurs'   => $this->getAuditeursParEntite($missionId),
            ]);
        } catch (\Exception $e) {
            Log::error("[PhaseAffectation] getAssignedPhases #{$missionId}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API GET — getPhasesByTypeApi
    // ══════════════════════════════════════════════════════════════════════════

    public function getPhasesByTypeApi(int $typeId)
    {
        try {
            $type = DB::table('mission_types')->where('id', $typeId)->first();
            if (!$type) {
                return response()->json(['error' => 'Type de mission introuvable.'], 404);
            }
            return response()->json([
                'success' => true,
                'type'    => $type,
                'phases'  => $this->getPhasesForType($typeId),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API GET — auditeurs d'une mission (pour refresh depuis Vue)
    // ══════════════════════════════════════════════════════════════════════════

    public function getAuditeursApi(int $missionId)
    {
        try {
            $mission = DB::table('mission_programmation')->where('id', $missionId)->first();
            if (!$mission) {
                return response()->json(['error' => 'Mission introuvable.'], 404);
            }
            return response()->json([
                'success'   => true,
                'auditeurs' => $this->getAuditeursParEntite($missionId),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API PATCH — toggleMandatory
    // ══════════════════════════════════════════════════════════════════════════

    public function toggleMandatory(int $id)
    {
        try {
            $phase = DB::table('mission_phases')->where('id', $id)->first();
            if (!$phase) {
                return response()->json(['error' => 'Phase introuvable.'], 404);
            }

            DB::table('mission_phases')
                ->where('id', $id)
                ->update([
                    'is_mandatory' => !$phase->is_mandatory,
                    'updated_at'   => now(),
                ]);

            return response()->json([
                'success'      => true,
                'is_mandatory' => !$phase->is_mandatory,
                'message'      => "Phase " . (!$phase->is_mandatory ? 'rendue obligatoire' : 'rendue optionnelle') . ".",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API POST — broadcast
    // ══════════════════════════════════════════════════════════════════════════

    public function broadcast(Request $request, int $missionId)
    {
        try {
            // ── 1. Vérifier mission ────────────────────────────────────────
            $mission = DB::table('mission_programmation')->where('id', $missionId)->first();
            if (!$mission) {
                return response()->json(['error' => 'Mission introuvable.'], 404);
            }

            $validated = $request->validate([
                'phase_id'     => 'required|integer',
                'entity_id'    => 'nullable|integer',
                'message'      => 'required|string|max:5000',
                'priority'     => 'nullable|in:normal,urgent,bloquant',
                'recipients'   => 'nullable|array',
                'recipients.*' => 'integer',
            ]);

            // ── 2. Vérifier que la phase appartient bien à cette mission ───
            $phase = DB::table('mission_phases')->where('id', $validated['phase_id'])->first();
            if (!$phase) {
                return response()->json(['error' => 'Phase introuvable.'], 404);
            }

            // ── 3. Vérifier l'entité si fournie ───────────────────────────
            if (!empty($validated['entity_id'])) {
                $entityOk = DB::table('mission_programmation_entity')
                    ->where('mission_programmation_id', $missionId)
                    ->where('entity_id', $validated['entity_id'])
                    ->exists();

                if (!$entityOk) {
                    return response()->json(['error' => "Entité non rattachée à cette mission."], 422);
                }
            }

            // ── 4. Mettre à jour broadcast_note sur l'assignment ──────────
            $assignment = DB::table('mission_phase_assignments')
                ->where('mission_programmation_id', $missionId)
                ->where('mission_phase_id', $validated['phase_id'])
                ->when($validated['entity_id'] ?? null, fn($q, $eid) => $q->where('entity_id', $eid))
                ->first();

            if ($assignment && $this->columnExists('mission_phase_assignments', 'broadcast_note')) {
                DB::table('mission_phase_assignments')
                    ->where('id', $assignment->id)
                    ->update(['broadcast_note' => $validated['message'], 'updated_at' => now()]);
            }

            // ── 5. Log notification si table existe ───────────────────────
            if ($this->tableExists('mission_phase_notifications')) {
                DB::table('mission_phase_notifications')->insert([
                    'mission_id'    => $missionId,
                    'assignment_id' => $assignment?->id,
                    'phase_id'      => $validated['phase_id'],
                    'entity_id'     => $validated['entity_id'] ?? null,
                    'message'       => $validated['message'],
                    'priority'      => $validated['priority'] ?? 'normal',
                    'sent_by'       => Auth::id(),
                    'recipients'    => json_encode($validated['recipients'] ?? []),
                    'sent_at'       => now(),
                    'created_at'    => now(),
                ]);
            }

            $count = count($validated['recipients'] ?? []);
            Log::info("[PhaseAffectation] broadcast #{$missionId} phase={$validated['phase_id']} recipients={$count}");

            return response()->json([
                'success' => true,
                'message' => "Note envoyée à {$count} auditeur(s).",
                'count'   => $count,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[PhaseAffectation] ❌ broadcast: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // API POST — saveAffectation  ← VÉRIFICATIONS COMPLÈTES ICI
    // ══════════════════════════════════════════════════════════════════════════

    public function saveAffectation(Request $request, int $missionId)
    {
        try {
            // ─────────────────────────────────────────────────────────────
            // 1. VALIDATION DES CHAMPS ENTRANTS
            // ─────────────────────────────────────────────────────────────
            $validated = $request->validate([
                'assignments'                   => 'required|array|min:1',
                'assignments.*.phase_id'        => 'required|integer|min:1',
                'assignments.*.entity_id'       => 'required|integer|min:1',
                'assignments.*.checked'         => 'required|boolean',
                'assignments.*.status'          => 'nullable|in:pending,in_progress,completed,skipped',
                'assignments.*.planned_start'   => 'nullable|date',
                'assignments.*.planned_end'     => 'nullable|date|after_or_equal:assignments.*.planned_start',
                'assignments.*.notes'           => 'nullable|string|max:2000',
                'assignments.*.auditeur_ids'    => 'nullable|array',
                'assignments.*.auditeur_ids.*'  => 'integer|min:1',
                'assignments.*.form_url'        => 'nullable|string|max:1000',
                'assignments.*.is_disabled'     => 'nullable|boolean',
                'assignments.*.broadcast_note'  => 'nullable|string|max:5000',
            ]);

            // ─────────────────────────────────────────────────────────────
            // 2. VÉRIFIER QUE LA MISSION EXISTE ET EST ACTIVE
            // ─────────────────────────────────────────────────────────────
            $mission = DB::table('mission_programmation')->where('id', $missionId)->first();
            if (!$mission) {
                return response()->json(['error' => "Mission #{$missionId} introuvable."], 404);
            }

            // Statuts bloquants (mission clôturée = on ne peut plus modifier)
            if (in_array($mission->status, ['terminee', 'annulee', 'cloturee'])) {
                return response()->json([
                    'error' => "La mission est '{$mission->status}' — modification impossible."
                ], 422);
            }

            // ─────────────────────────────────────────────────────────────
            // 3. CHARGER LES ENTITÉS VALIDES DE LA MISSION
            // ─────────────────────────────────────────────────────────────
            $validEntityIds = DB::table('mission_programmation_entity')
                ->where('mission_programmation_id', $missionId)
                ->pluck('entity_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            if (empty($validEntityIds)) {
                return response()->json([
                    'error' => "Aucune entité n'est rattachée à cette mission."
                ], 422);
            }

            // ─────────────────────────────────────────────────────────────
            // 4. CHARGER LES PHASES VALIDES DU TYPE DE MISSION
            //    (via mission → mission_type_id → mission_phases)
            // ─────────────────────────────────────────────────────────────
            $missionTypeId = $this->getMissionTypeId($missionId);
            $validPhaseIds = [];

            if ($missionTypeId) {
                $validPhaseIds = DB::table('mission_phases')
                    ->where('mission_type_id', $missionTypeId)
                    ->whereNotIn('status', ['inactive', 'disabled', 'deleted', 'archived'])
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();
            }

            // ─────────────────────────────────────────────────────────────
            // 5. CHARGER LES AUDITEURS VALIDES DE LA MISSION
            //    (indexés par entité pour validation croisée)
            // ─────────────────────────────────────────────────────────────
            $auditeursParEntite = $this->buildAuditeursByEntity($missionId, $validEntityIds);

            // ─────────────────────────────────────────────────────────────
            // 6. VÉRIFIER COLONNES OPTIONNELLES UNE SEULE FOIS
            // ─────────────────────────────────────────────────────────────
            $hasFormUrl    = $this->columnExists('mission_phase_assignments', 'form_url');
            $hasIsDisabled = $this->columnExists('mission_phase_assignments', 'is_disabled');
            $hasBroadcast  = $this->columnExists('mission_phase_assignments', 'broadcast_note');

            // ─────────────────────────────────────────────────────────────
            // 7. TRAITEMENT ITEM PAR ITEM AVEC VÉRIFICATIONS MÉTIER
            // ─────────────────────────────────────────────────────────────
            DB::beginTransaction();

            $upserted = 0;
            $deleted  = 0;
            $skipped  = 0;
            $errors   = [];

            foreach ($validated['assignments'] as $idx => $item) {
                $phaseId  = (int) $item['phase_id'];
                $entityId = (int) $item['entity_id'];
                $checked  = (bool) $item['checked'];

                // ── a. Entité appartient à la mission ? ──────────────────
                if (!in_array($entityId, $validEntityIds)) {
                    $errors[] = "Ligne #{$idx}: entité #{$entityId} non rattachée à la mission.";
                    $skipped++;
                    continue;
                }

                // ── b. Phase valide pour ce type de mission ? ────────────
                if (!empty($validPhaseIds) && !in_array($phaseId, $validPhaseIds)) {
                    $errors[] = "Ligne #{$idx}: phase #{$phaseId} invalide pour ce type de mission.";
                    $skipped++;
                    continue;
                }

                // ── c. Cohérence des dates ───────────────────────────────
                $pStart = $item['planned_start'] ?? null;
                $pEnd   = $item['planned_end']   ?? null;

                if ($pStart && $pEnd && $pEnd < $pStart) {
                    $errors[] = "Ligne #{$idx}: date_fin ({$pEnd}) < date_debut ({$pStart}) pour phase #{$phaseId}.";
                    // On corrige : on supprime la fin incohérente plutôt que de bloquer tout
                    $pEnd = null;
                }

                // ── d. Dates dans les bornes de l'entité ─────────────────
                $entityBornes = $this->getEntityDates($missionId, $entityId);
                if ($entityBornes && $pStart) {
                    if ($entityBornes['debut'] && $pStart < $entityBornes['debut']) {
                        $errors[] = "Ligne #{$idx}: planned_start ({$pStart}) avant début entité ({$entityBornes['debut']}).";
                        $pStart = $entityBornes['debut']; // force au plancher
                    }
                    if ($entityBornes['fin'] && $pEnd && $pEnd > $entityBornes['fin']) {
                        $errors[] = "Ligne #{$idx}: planned_end ({$pEnd}) dépasse fin entité ({$entityBornes['fin']}).";
                        $pEnd = $entityBornes['fin']; // force au plafond
                    }
                }

                // ── e. Auditeurs valides pour cette entité ? ─────────────
                $auditeurIdsRequested = array_map('intval', $item['auditeur_ids'] ?? []);
                $auditeurIdsValides   = [];

                if (!empty($auditeurIdsRequested)) {
                    $audsDeEntite = $auditeursParEntite[$entityId] ?? [];
                    foreach ($auditeurIdsRequested as $audId) {
                        if (in_array($audId, $audsDeEntite)) {
                            $auditeurIdsValides[] = $audId;
                        } else {
                            $errors[] = "Ligne #{$idx}: auditeur #{$audId} non rattaché à l'entité #{$entityId}.";
                        }
                    }
                }

                // ── f. Phase obligatoire → toujours checked ──────────────
                $isMandatory = (bool) DB::table('mission_phases')
                    ->where('id', $phaseId)
                    ->value('is_mandatory');

                if ($isMandatory) {
                    $checked = true;
                }

                // ─────────────────────────────────────────────────────────
                // g. UPSERT ou DELETE selon checked
                // ─────────────────────────────────────────────────────────
                if ($checked) {
                    $existing = DB::table('mission_phase_assignments')
                        ->where('mission_programmation_id', $missionId)
                        ->where('mission_phase_id', $phaseId)
                        ->where('entity_id', $entityId)
                        ->first();

                    $data = [
                        'status'        => $item['status']  ?? 'pending',
                        'planned_start' => $pStart,
                        'planned_end'   => $pEnd,
                        'notes'         => $item['notes']   ?? null,
                        'updated_at'    => now(),
                    ];

                    if ($hasFormUrl)    $data['form_url']       = $item['form_url']       ?? null;
                    if ($hasIsDisabled) $data['is_disabled']    = (bool) ($item['is_disabled'] ?? false);
                    if ($hasBroadcast)  $data['broadcast_note'] = $item['broadcast_note'] ?? null;

                    if ($existing) {
                        DB::table('mission_phase_assignments')
                            ->where('id', $existing->id)
                            ->update($data);
                        $assignmentId = $existing->id;
                    } else {
                        try {
                            $assignmentId = DB::table('mission_phase_assignments')->insertGetId(
                                array_merge($data, [
                                    'mission_programmation_id' => $missionId,
                                    'mission_phase_id'         => $phaseId,
                                    'entity_id'                => $entityId,
                                    'created_by'               => Auth::id(),
                                    'created_at'               => now(),
                                ])
                            );
                        } catch (\Exception $dup) {
                            // Race condition : relire et mettre à jour
                            $rec = DB::table('mission_phase_assignments')
                                ->where('mission_programmation_id', $missionId)
                                ->where('mission_phase_id', $phaseId)
                                ->where('entity_id', $entityId)
                                ->first();
                            if (!$rec) throw $dup;
                            DB::table('mission_phase_assignments')
                                ->where('id', $rec->id)
                                ->update($data);
                            $assignmentId = $rec->id;
                        }
                    }

                    // Synchroniser les auditeurs (seulement ceux validés)
                    $this->syncAssignmentAuditeurs(
                        $assignmentId,
                        $auditeurIdsValides,
                        $missionId,
                        $entityId
                    );

                    $upserted++;

                } else {
                    // Phase décochée → supprimer l'assignment ET ses auditeurs liés
                    $existing = DB::table('mission_phase_assignments')
                        ->where('mission_programmation_id', $missionId)
                        ->where('mission_phase_id', $phaseId)
                        ->where('entity_id', $entityId)
                        ->first();

                    if ($existing) {
                        // Supprimer les auditeurs liés
                        DB::table('mission_phase_assignment_auditeurs')
                            ->where('assignment_id', $existing->id)
                            ->delete();
                        // Supprimer l'assignment
                        DB::table('mission_phase_assignments')
                            ->where('id', $existing->id)
                            ->delete();
                        $deleted++;
                    }
                }
            }

            DB::commit();

            Log::info("[PhaseAffectation] ✅ COMMIT #{$missionId}"
                . " upserted={$upserted} deleted={$deleted} skipped={$skipped}"
                . " warnings=" . count($errors));

            $response = [
                'success'  => true,
                'upserted' => $upserted,
                'deleted'  => $deleted,
                'skipped'  => $skipped,
                'message'  => "{$upserted} affectation(s) sauvegardée(s), {$deleted} supprimée(s).",
            ];

            // Inclure les warnings s'il y en a (données corrigées ou ignorées)
            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PhaseAffectation] ❌ saveAffectation: ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // FORMS — délégué au UserMenuSessionService
    // ══════════════════════════════════════════════════════════════════════════

    private function loadFormsFromSession(int $missionTypeId, ?string $auditTypeCode): array
    {
        // ── 1. Essayer depuis la session via le service ──────────────────
        // Le service gère le cache TTL et le rechargement auto
        if ($missionTypeId > 0) {
            $forms = \App\Services\Audit\UserMenuSessionService::getFormsByMissionTypeId($missionTypeId);
            if (!empty($forms)) {
                Log::info("[PhaseAffectation] forms depuis session [mission_type_id={$missionTypeId}]: " . count($forms));
                return $forms;
            }
        }

        // ── 2. Essayer par audit_type_code (si mission_type_id pas trouvé) ─
        if ($auditTypeCode) {
            $forms = \App\Services\Audit\UserMenuSessionService::getFormsByAuditTypeCode($auditTypeCode);
            if (!empty($forms)) {
                Log::info("[PhaseAffectation] forms depuis session [audit_type_code={$auditTypeCode}]: " . count($forms));
                return $forms;
            }
        }

        // ── 3. Forcer un rechargement de la session puis réessayer ───────
        Log::info("[PhaseAffectation] Session vide → rechargement forcé depuis ddmparam.");
        \App\Services\Audit\UserMenuSessionService::refresh();

        if ($missionTypeId > 0) {
            $forms = \App\Services\Audit\UserMenuSessionService::getFormsByMissionTypeId($missionTypeId);
            if (!empty($forms)) return $forms;
        }

        if ($auditTypeCode) {
            $forms = \App\Services\Audit\UserMenuSessionService::getFormsByAuditTypeCode($auditTypeCode);
            if (!empty($forms)) return $forms;
        }

        // ── 4. Dernier recours : requête directe ddmparam ────────────────
        if ($auditTypeCode) {
            Log::warning("[PhaseAffectation] Fallback requête directe ddmparam [{$auditTypeCode}].");
            return $this->loadFormsFromDdmparam($auditTypeCode);
        }

        return [];
    }

    /**
     * Fallback requête directe ddmparam (utilisé uniquement si le service échoue).
     */
    private function loadFormsFromDdmparam(string $auditTypeCode): array
    {
        try {
            $rows = DB::table('ddmparam.audit_type_forms as f')
                ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
                ->where('at.code', strtoupper($auditTypeCode))
                ->where('f.is_active', 1)
                ->orderBy('f.phase_num')
                ->orderBy('f.sort_order')
                ->get([
                    'f.id', 'f.phase_num', 'f.phase_label', 'f.parent_id',
                    'f.code', 'f.label', 'f.description',
                    'f.route_name', 'f.url_path', 'f.icon', 'f.sort_order',
                ]);

            // Index id → code pour résolution parent_code
            $byId = $rows->pluck('code', 'id')->toArray();

            $result = [];
            foreach ($rows as $r) {
                $result[$r->code] = [
                    'code'        => $r->code,
                    'label'       => $r->label,
                    'description' => $r->description ?? null,
                    'route_name'  => $r->route_name  ?? null,
                    'url_path'    => $r->url_path    ?? null,
                    'icon'        => $r->icon        ?? 'ti ti-file-description',
                    'phase_num'   => (int) $r->phase_num,
                    'phase_label' => $r->phase_label,
                    'parent_code' => $r->parent_id ? ($byId[$r->parent_id] ?? null) : null,
                    'sort_order'  => (int) $r->sort_order,
                    'level'       => $r->parent_id ? 2 : 1,
                ];
            }
            return $result;

        } catch (\Exception $e) {
            Log::warning("[PhaseAffectation] loadFormsFromDdmparam [{$auditTypeCode}]: " . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS — vérifications métier
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Retourne l'ID du mission_type à partir de mission_programmation.
     * Chemin : mission_programmation → mission → mission_type
     */
    private function getMissionTypeId(int $missionId): ?int
    {
        $row = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->where('mp.id', $missionId)
            ->value('mt.id');

        return $row ? (int) $row : null;
    }

    /**
     * Construit un index [entity_id => [auditeur_id, ...]]
     * pour validation croisée dans saveAffectation.
     */
    private function buildAuditeursByEntity(int $missionId, array $entityIds): array
    {
        try {
            $rows = DB::table('mission_phase_auditeurs')
                ->where('mission_id', $missionId)
                ->select(['auditeur_id', 'entites'])
                ->get();

            $result = [];
            foreach ($rows as $r) {
                $eids = json_decode($r->entites ?? '[]', true) ?? [];
                foreach ($eids as $eid) {
                    $eid = (int) $eid;
                    if (!in_array($eid, $entityIds)) continue;
                    if (!isset($result[$eid])) $result[$eid] = [];
                    $result[$eid][] = (int) $r->auditeur_id;
                }
            }
            return $result;
        } catch (\Exception $e) {
            Log::warning("[PhaseAffectation] buildAuditeursByEntity: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retourne les dates de début/fin d'une entité dans la mission.
     */
    private function getEntityDates(int $missionId, int $entityId): ?array
    {
        try {
            $row = DB::table('mission_programmation_entity')
                ->where('mission_programmation_id', $missionId)
                ->where('entity_id', $entityId)
                ->select(['date_debut', 'date_fin'])
                ->first();

            if (!$row) return null;
            return ['debut' => $row->date_debut, 'fin' => $row->date_fin];
        } catch (\Exception $e) {
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS — chargement données
    // ══════════════════════════════════════════════════════════════════════════

    private function columnExists(string $table, string $column): bool
    {
        try {
            return in_array($column, DB::getSchemaBuilder()->getColumnListing($table));
        } catch (\Exception $e) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function loadDdmMeta(): \Illuminate\Support\Collection
    {
        try {
            return DB::table('ddmparam.audit_types')
                ->where('is_active', 1)
                ->get(['code', 'label', 'color', 'icon'])
                ->keyBy('code');
        } catch (\Exception $e) {
            Log::warning('[PhaseAffectation] ddmparam indisponible: ' . $e->getMessage());
            return collect();
        }
    }

    private function loadAllMissions(\Illuminate\Support\Collection $ddmMeta): \Illuminate\Support\Collection
    {
        $raw = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.status',
                'mp.date_debut', 'mp.date_fin',
                'mt.id    as mission_type_id',
                'mt.code  as type_code',
                'mt.label as type_label',
                'mt.audit_type_code',
            ])
            ->orderBy('mp.date_debut', 'desc')
            ->get();

        $mpIds   = $raw->pluck('id')->toArray();
        $entsMap = empty($mpIds) ? collect() :
            DB::table('mission_programmation_entity as mpe')
                ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
                ->whereIn('mpe.mission_programmation_id', $mpIds)
                ->select([
                    'mpe.mission_programmation_id',
                    DB::raw("GROUP_CONCAT(DISTINCT COALESCE(e.name,'') ORDER BY e.name SEPARATOR ', ') as entities_list"),
                    DB::raw("COUNT(DISTINCT mpe.entity_id) as entity_count"),
                ])
                ->groupBy('mpe.mission_programmation_id')
                ->get()->keyBy('mission_programmation_id');

        // Stats d'avancement par mission
        $assignStats = empty($mpIds) ? collect() :
            DB::table('mission_phase_assignments')
                ->whereIn('mission_programmation_id', $mpIds)
                ->selectRaw('mission_programmation_id,
                             COUNT(*) as total_aff,
                             SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed_aff')
                ->groupBy('mission_programmation_id')
                ->get()->keyBy('mission_programmation_id');

        return $raw->map(function ($r) use ($ddmMeta, $entsMap, $assignStats) {
            $tc  = $r->audit_type_code ?? $r->type_code ?? null;
            $at  = $tc ? ($ddmMeta[$tc] ?? null) : null;
            $em  = $entsMap[$r->id]    ?? null;
            $aff = $assignStats[$r->id] ?? null;

            return [
                'id'                => $r->id,
                'code_mission'      => $r->code_mission,
                'libelle'           => $r->libelle,
                'status'            => $r->status,
                'date_debut'        => $r->date_debut,
                'date_fin'          => $r->date_fin,
                'mission_type_id'   => $r->mission_type_id,
                'type_code'         => $tc,
                'type_label'        => $r->type_label      ?? null,
                'audit_type_code'   => $tc,
                'audit_type_label'  => $at?->label         ?? ($r->type_label ?? null),
                'audit_color'       => $at?->color         ?? '#64748B',
                'audit_icon'        => $at?->icon          ?? 'ti ti-clipboard',
                'entities_list'     => $em?->entities_list ?? '',
                'entity_count'      => (int) ($em?->entity_count  ?? 0),
                // ── Avancement ──
                'total_aff'         => (int) ($aff?->total_aff     ?? 0),
                'completed_aff'     => (int) ($aff?->completed_aff ?? 0),
                'pct_aff'           => $aff && $aff->total_aff > 0
                    ? (int) round($aff->completed_aff / $aff->total_aff * 100)
                    : 0,
            ];
        })->values();
    }

    private function getMissionData(int $id, \Illuminate\Support\Collection $ddmMeta): ?object
    {
        $r = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->where('mp.id', $id)
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.status',
                'mp.date_debut', 'mp.date_fin',
                'mt.id    as mission_type_id',
                'mt.code  as type_code',
                'mt.label as type_label',
                'mt.audit_type_code',
            ])->first();

        if (!$r) return null;

        $tc = $r->audit_type_code ?? $r->type_code ?? null;
        $at = $tc ? ($ddmMeta[$tc] ?? null) : null;
        $r->audit_type_code  = $tc;
        $r->audit_type_label = $at?->label ?? ($r->type_label ?? null);
        $r->audit_color      = $at?->color ?? '#64748B';
        $r->audit_icon       = $at?->icon  ?? 'ti ti-clipboard';
        return $r;
    }

    private function getEntities(int $missionId): array
    {
        $entities = DB::table('mission_programmation_entity as mpe')
            ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->where('mpe.mission_programmation_id', $missionId)
            ->select(['e.id', 'e.name', 'mpe.date_debut', 'mpe.date_fin'])
            ->orderByRaw('ISNULL(mpe.date_debut), mpe.date_debut ASC')
            ->get();

        if ($entities->isEmpty()) return [];

        $audRows = DB::table('mission_phase_auditeurs as mpa')
            ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
            ->where('mpa.mission_id', $missionId)
            ->select([
                'a.id as auditeur_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"),
                'mpa.role as role_code',
                'mpa.role_id',
                'mpa.entites',
                'mpa.parent_auditeur_id',
            ])->get();

        $roleLibelles = $this->loadRoleLibelles();
        $audByEntity  = [];

        foreach ($audRows as $aud) {
            $eids    = json_decode($aud->entites ?? '[]', true) ?? [];
            $roleInf = $aud->role_id ? ($roleLibelles[$aud->role_id] ?? null) : null;
            foreach ($eids as $eid) {
                $eid = (int) $eid;
                if (!isset($audByEntity[$eid])) $audByEntity[$eid] = [];
                if (!collect($audByEntity[$eid])->firstWhere('auditeur_id', $aud->auditeur_id)) {
                    $audByEntity[$eid][] = [
                        'auditeur_id'        => $aud->auditeur_id,
                        'full_name'          => $aud->full_name,
                        'role_code'          => $aud->role_code,
                        'role_id'            => $aud->role_id,
                        'role_libelle'       => $roleInf['libelle'] ?? null,
                        'role_niveau'        => (int) ($roleInf['niveau'] ?? 99),
                        'parent_auditeur_id' => $aud->parent_auditeur_id,
                    ];
                }
            }
        }
        foreach ($audByEntity as &$grp) {
            usort($grp, fn($a, $b) => $a['role_niveau'] <=> $b['role_niveau']);
        }
        unset($grp);

        return $entities->map(fn($e) => [
            'id'         => $e->id,
            'name'       => $e->name,
            'date_debut' => $e->date_debut,
            'date_fin'   => $e->date_fin,
            'auditeurs'  => $audByEntity[$e->id] ?? [],
        ])->toArray();
    }

    private function getAuditeursParEntite(int $missionId): array
    {
        try {
            $rows = DB::table('mission_phase_auditeurs as mpa')
                ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_id', $missionId)
                ->select([
                    'a.id as auditeur_id',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"),
                    'mpa.role as role_code',
                    'mpa.role_id',
                    'mpa.entites',
                ])->get();

            $roleLibelles = $this->loadRoleLibelles();
            $result       = [];

            foreach ($rows as $r) {
                $eids    = json_decode($r->entites ?? '[]', true) ?? [];
                $roleInf = $r->role_id ? ($roleLibelles[$r->role_id] ?? null) : null;
                foreach ($eids as $eid) {
                    $eid = (int) $eid;
                    if (!isset($result[$eid])) $result[$eid] = [];
                    if (!collect($result[$eid])->firstWhere('auditeur_id', $r->auditeur_id)) {
                        $result[$eid][] = [
                            'auditeur_id'  => $r->auditeur_id,
                            'full_name'    => $r->full_name,
                            'role_code'    => $r->role_code,
                            'role_id'      => $r->role_id,
                            'role_libelle' => $roleInf['libelle'] ?? null,
                            'role_niveau'  => (int) ($roleInf['niveau'] ?? 99),
                        ];
                    }
                }
            }
            foreach ($result as &$grp) {
                usort($grp, fn($a, $b) => $a['role_niveau'] <=> $b['role_niveau']);
            }
            unset($grp);
            return $result;

        } catch (\Exception $e) {
            Log::warning("[PhaseAffectation] getAuditeursParEntite #{$missionId}: " . $e->getMessage());
            return [];
        }
    }

    private function loadRoleLibelles(): array
    {
        try {
            $result = [];
            DB::table('mission_roles')->select(['id', 'libelle', 'niveau'])->get()
                ->each(fn($r) => $result[$r->id] = ['libelle' => $r->libelle, 'niveau' => (int) $r->niveau]);
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAssignmentsData(int $missionId): array
    {
        try {
            $hasFormUrl    = $this->columnExists('mission_phase_assignments', 'form_url');
            $hasIsDisabled = $this->columnExists('mission_phase_assignments', 'is_disabled');
            $hasBroadcast  = $this->columnExists('mission_phase_assignments', 'broadcast_note');

            $select = [
                'mpa.id',
                'mpa.mission_phase_id',
                'mpa.entity_id',
                'mpa.status',
                'mpa.planned_start',
                'mpa.planned_end',
                'mpa.notes',
                'mpaa.auditeur_id as aud_id',
                'mpaa.role_code   as aud_role',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as aud_name"),
            ];

            if ($hasFormUrl)    $select[] = 'mpa.form_url';
            if ($hasIsDisabled) $select[] = 'mpa.is_disabled';
            if ($hasBroadcast)  $select[] = 'mpa.broadcast_note';

            $rows = DB::table('mission_phase_assignments as mpa')
                ->leftJoin('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
                ->leftJoin('auditors as a', 'mpaa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_programmation_id', $missionId)
                ->select($select)
                ->get();

        } catch (\Exception $ex) {
            Log::warning("[PhaseAffectation] getAssignmentsData fallback: " . $ex->getMessage());
            $rows = DB::table('mission_phase_assignments as mpa')
                ->leftJoin('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
                ->leftJoin('auditors as a', 'mpaa.auditeur_id', '=', 'a.id')
                ->where('mpa.mission_programmation_id', $missionId)
                ->select([
                    'mpa.id', 'mpa.mission_phase_id', 'mpa.entity_id',
                    'mpa.status', 'mpa.planned_start', 'mpa.planned_end', 'mpa.notes',
                    'mpaa.auditeur_id as aud_id', 'mpaa.role_code as aud_role',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as aud_name"),
                ])->get();
        }

        $result = [];
        foreach ($rows as $r) {
            $key = "{$r->mission_phase_id}_{$r->entity_id}";
            if (!isset($result[$key])) {
                $result[$key] = [
                    'id'             => $r->id,
                    'phase_id'       => $r->mission_phase_id,
                    'entity_id'      => $r->entity_id,
                    'status'         => $r->status,
                    'planned_start'  => $r->planned_start,
                    'planned_end'    => $r->planned_end,
                    'notes'          => $r->notes,
                    'form_url'       => $r->form_url       ?? null,
                    'is_disabled'    => (bool) ($r->is_disabled ?? false),
                    'broadcast_note' => $r->broadcast_note ?? null,
                    'auditeur_ids'   => [],
                    'auditeurs'      => [],
                ];
            }
            if ($r->aud_id) {
                if (!in_array($r->aud_id, $result[$key]['auditeur_ids'])) {
                    $result[$key]['auditeur_ids'][] = $r->aud_id;
                    $result[$key]['auditeurs'][]    = [
                        'auditeur_id' => $r->aud_id,
                        'full_name'   => $r->aud_name,
                        'role_code'   => $r->aud_role,
                    ];
                }
            }
        }
        return $result;
    }

    private function syncAssignmentAuditeurs(
        int $assignmentId,
        array $auditeurIds,
        int $missionId,
        int $entityId
    ): void {
        // Métadonnées des auditeurs (rôle, parent) depuis mission_phase_auditeurs
        $audRows = DB::table('mission_phase_auditeurs as mpa')
            ->where('mpa.mission_id', $missionId)
            ->whereIn('mpa.auditeur_id', $auditeurIds)
            ->select(['mpa.auditeur_id', 'mpa.role', 'mpa.role_id', 'mpa.parent_auditeur_id', 'mpa.entites'])
            ->get();

        $audMeta = $audRows->filter(function ($r) use ($entityId) {
            $eids = json_decode($r->entites ?? '[]', true) ?? [];
            return in_array((int) $entityId, array_map('intval', $eids));
        })->keyBy('auditeur_id');

        // Supprimer ceux qui ne sont plus dans la liste
        DB::table('mission_phase_assignment_auditeurs')
            ->where('assignment_id', $assignmentId)
            ->whereNotIn('auditeur_id', $auditeurIds)
            ->delete();

        // Upsert chaque auditeur validé
        foreach ($auditeurIds as $audId) {
            $meta     = $audMeta[$audId] ?? null;
            $existing = DB::table('mission_phase_assignment_auditeurs')
                ->where('assignment_id', $assignmentId)
                ->where('auditeur_id', $audId)
                ->first();

            $data = [
                'role_code'          => $meta?->role               ?? null,
                'role_id'            => $meta?->role_id            ?? null,
                'parent_auditeur_id' => $meta?->parent_auditeur_id ?? null,
                'date_affectation'   => now()->toDateString(),
                'affecte_par'        => Auth::id(),
                'updated_at'         => now(),
            ];

            if ($existing) {
                DB::table('mission_phase_assignment_auditeurs')
                    ->where('id', $existing->id)
                    ->update($data);
            } else {
                DB::table('mission_phase_assignment_auditeurs')->insert(
                    array_merge($data, [
                        'assignment_id' => $assignmentId,
                        'auditeur_id'   => $audId,
                        'created_at'    => now(),
                    ])
                );
            }
        }
    }

    private function getPhasesForType(int $typeId): array
    {
        // ── Charger les phases depuis mission_phases (table tenant) ──────────
        $all = DB::table('mission_phases')
            ->where('mission_type_id', $typeId)
            ->whereNotIn('status', ['inactive', 'disabled', 'deleted', 'archived'])
            ->orderByRaw("
                CASE COALESCE(phase_type, 'AUTRE')
                    WHEN 'PREPARATION'    THEN 1
                    WHEN 'VERIFICATION'   THEN 2
                    WHEN 'CONCLUSION'     THEN 3
                    WHEN 'SUIVI'          THEN 4
                    WHEN 'RECOMMANDATIONS'THEN 5
                    ELSE 6
                END,
                COALESCE(level, 1),
                COALESCE(code_full, code)
            ")
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'code'         => $p->code         ?? '',
                'code_full'    => $p->code_full    ?? $p->code ?? '',
                'label'        => $p->label        ?? '',
                'phase_type'   => $p->phase_type   ?? 'AUTRE',
                'level'        => (int) ($p->level ?? 1),
                'parent_id'    => $p->parent_id    ?? null,
                'is_mandatory' => (bool) ($p->is_mandatory ?? false),
                'form_code'    => $p->form_code    ?? null,
                'sort_order'   => (int) ($p->sort_order ?? 0),
                'status'       => $p->status       ?? null,
                'children'     => [],
            ])->toArray();

        // ── Détecter les phase_type présents ─────────────────────────────────
        $presentTypes = collect($all)->pluck('phase_type')->unique()->filter()->values()->toArray();
        $allExpected  = ['PREPARATION', 'VERIFICATION', 'CONCLUSION', 'SUIVI', 'RECOMMANDATIONS'];
        $missingTypes = array_diff($allExpected, $presentTypes);

        // ── Fallback ddmparam : compléter les phase_type manquants ───────────
        if (!empty($missingTypes) || empty($all)) {
            $auditTypeCode = $this->getAuditTypeCodeForMissionType($typeId);

            if ($auditTypeCode) {
                $missingPhaseNums = [];
                $phaseNumMap = [
                    'PREPARATION'    => 1,
                    'VERIFICATION'   => 2,
                    'CONCLUSION'     => 3,
                    'SUIVI'          => 4,
                    'RECOMMANDATIONS'=> 5,
                ];

                foreach ($missingTypes as $pt) {
                    if (isset($phaseNumMap[$pt])) {
                        $missingPhaseNums[] = $phaseNumMap[$pt];
                    }
                }

                // Si $all est vide → charger toutes les phases depuis ddmparam
                if (empty($all)) {
                    $missingPhaseNums = [1, 2, 3, 4, 5];
                }

                if (!empty($missingPhaseNums)) {
                    $ddmForms = DB::table('ddmparam.audit_type_forms as f')
                        ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
                        ->where('at.code', $auditTypeCode)
                        ->whereIn('f.phase_num', $missingPhaseNums)
                        ->whereNull('f.parent_id')   // seulement racines
                        ->where('f.is_active', 1)
                        ->orderBy('f.phase_num')
                        ->orderBy('f.sort_order')
                        ->get();

                    // Construire des phases virtuelles depuis ddmparam
                    // On utilise des IDs négatifs pour distinguer des vraies phases
                    $virtualId = -1000;
                    foreach ($ddmForms as $f) {
                        $phaseType = $allExpected[($f->phase_num - 1)] ?? 'AUTRE';
                        $all[] = [
                            'id'           => $virtualId--,     // ID virtuel négatif
                            'code'         => $f->code,
                            'code_full'    => $f->code,
                            'label'        => $f->label,
                            'phase_type'   => $phaseType,
                            'level'        => 1,
                            'parent_id'    => null,
                            'is_mandatory' => false,
                            'form_code'    => $f->code,
                            'sort_order'   => (int) $f->sort_order,
                            'status'       => 'ddmparam',       // marqué pour distincter
                            'children'     => [],
                        ];
                    }

                    Log::info("[PhaseAffectation] getPhasesForType #{$typeId} [{$auditTypeCode}]"
                        . " → " . count($all) . " phases (dont ddmparam fallback pour: "
                        . implode(',', $missingTypes) . ")");
                }
            }
        }

        if (empty($all)) return [];

        // ── Construire l'arborescence parent → enfants ───────────────────────
        $byId = [];
        foreach ($all as &$p) $byId[$p['id']] = &$p;
        unset($p);

        $roots = [];
        foreach ($all as &$p) {
            if ($p['parent_id'] === null) {
                $roots[] = &$p;
            } elseif (isset($byId[$p['parent_id']])) {
                $byId[$p['parent_id']]['children'][] = &$p;
            } else {
                $roots[] = &$p;
            }
        }
        unset($p);

        // ── Grouper par phase_type dans l'ordre canonique ────────────────────
        $groups = [];
        foreach ($roots as &$r) {
            $pt = $r['phase_type'] ?? 'AUTRE';
            if (!isset($groups[$pt])) {
                $groups[$pt] = ['phase_type' => $pt, 'phases' => []];
            }
            $groups[$pt]['phases'][] = &$r;
        }
        unset($r);

        $ordered = [];
        $known   = ['PREPARATION', 'VERIFICATION', 'CONCLUSION', 'SUIVI', 'RECOMMANDATIONS'];
        foreach ($known as $pt) {
            if (isset($groups[$pt])) {
                $ordered[] = $groups[$pt];
                unset($groups[$pt]);
            }
        }
        ksort($groups);
        foreach ($groups as $g) {
            $ordered[] = $g;
        }

        return $ordered;
    }

    /**
     * Retourne audit_type_code pour un mission_type_id donné.
     */
    private function getAuditTypeCodeForMissionType(int $typeId): ?string
    {
        $row = DB::table('mission_types')
            ->where('id', $typeId)
            ->select(['audit_type_code', 'code'])
            ->first();

        if (!$row) return null;

        // Priorité : colonne audit_type_code remplie
        if (!empty($row->audit_type_code)) {
            return $row->audit_type_code;
        }

        // Fallback : mapper depuis le code du mission_type
        $map = [
            'AC' => 'AC', 'AF' => 'AF', 'AP' => 'AP',
            'AM' => 'AM', 'RP' => 'RP', 'ES' => 'ES',
        ];

        return $map[strtoupper($row->code)] ?? null;
    }
}