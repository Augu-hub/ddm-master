<?php

namespace App\Services\Mission;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Une fois une `missions` créée (MissionController@store), ce service :
 *  1. Va chercher AUTOMATIQUEMENT dans le référentiel du tenant
 *     (mission_phases, déjà tenu à jour par TenantReferenceSyncService)
 *     les phases applicables au mission_type de la mission.
 *  2. Crée une `mission_programmation` (le "dossier d'exécution") + une
 *     `mission_phase_assignments` par (phase × entité), en statut
 *     "pending" / "draft".
 *  3. Ne verrouille rien : tout est renvoyé sous forme de PROPOSITION que
 *     l'utilisateur peut encore modifier (ajouter/retirer une phase,
 *     changer l'auditeur responsable, les dates) via `updateProposal()`
 *     avant la confirmation finale `confirm()`.
 */
class MissionProgrammationService
{
    private function tenant()
    {
        return DB::connection('tenant');
    }

    // =========================================================================
    // 1) GÉNÉRATION AUTOMATIQUE À LA CRÉATION DE LA MISSION
    // =========================================================================
    public function autoGenerate(int $missionId, array $entityIds): array
    {
        $mission = $this->tenant()->table('missions')->where('id', $missionId)->first();
        if (!$mission) {
            throw new \RuntimeException("Mission #{$missionId} introuvable.");
        }

        return $this->tenant()->transaction(function () use ($mission, $entityIds) {

            // ── mission_programmation : le dossier d'exécution ──────────────
            $programmationId = $this->tenant()->table('mission_programmation')->insertGetId([
                'code_mission' => $mission->code . '-PROG',
                'libelle'      => $mission->title,
                'numero_fpm'   => $mission->fpm_number,
                'objectif'     => $mission->objective,
                'mission_id'   => $mission->id,
                'date_debut'   => $mission->planned_start_date ?? now()->toDateString(),
                'date_fin'     => $mission->planned_end_date   ?? now()->addMonth()->toDateString(),
                'status'       => 'planifiee',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // ── entités concernées (préserve TOUTES les entités sélectionnées,
            //     pas seulement la première comme le fait `missions.entity_id`) ──
            foreach (array_unique($entityIds) as $entityId) {
                $this->tenant()->table('mission_programmation_entity')->insert([
                    'mission_programmation_id' => $programmationId,
                    'entity_id'                => $entityId,
                    'date_debut'               => $mission->planned_start_date,
                    'date_fin'                 => $mission->planned_end_date,
                ]);
            }

            // ── phases applicables au type de mission, récupérées automatiquement ──
            $phases = $this->tenant()->table('mission_phases')
                ->where('mission_type_id', $mission->mission_type_id)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get();

            $assignments = [];
            foreach ($phases as $phase) {
                // Par défaut : les phases obligatoires sont pré-cochées ; les
                // phases facultatives sont générées aussi mais marquées
                // "skipped" pour que l'utilisateur les réactive lui-même s'il
                // les veut (au lieu de deviner à sa place).
                $defaultStatus = $phase->is_mandatory ? 'pending' : 'skipped';

                foreach (array_unique($entityIds) as $entityId) {
                    $assignmentId = $this->tenant()->table('mission_phase_assignments')->insertGetId([
                        'mission_programmation_id' => $programmationId,
                        'mission_phase_id'         => $phase->id,
                        'entity_id'                => $entityId,
                        'status'                   => $defaultStatus,
                        'validation_status'        => 'draft',
                        'planned_start'            => $mission->planned_start_date,
                        'planned_end'              => $mission->planned_end_date,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);

                    $assignments[] = [
                        'id'            => $assignmentId,
                        'phase_id'      => $phase->id,
                        'phase_label'   => $phase->label,
                        'phase_type'    => $phase->phase_type,
                        'is_mandatory'  => (bool) $phase->is_mandatory,
                        'entity_id'     => $entityId,
                        'status'        => $defaultStatus,
                    ];
                }
            }

            Log::info("✅ Programmation auto-générée pour mission {$mission->code}", [
                'programmation_id' => $programmationId,
                'phases'           => count($phases),
                'assignments'      => count($assignments),
            ]);

            return [
                'programmation_id' => $programmationId,
                'mission_code'     => $mission->code,
                'assignments'      => $assignments,
            ];
        });
    }

    // =========================================================================
    // 2) L'UTILISATEUR AJUSTE LA PROPOSITION (activer/désactiver une phase,
    //    changer le responsable, les dates...) AVANT de confirmer
    // =========================================================================
    public function updateProposal(int $programmationId, array $adjustments): void
    {
        // $adjustments = [['assignment_id' => 12, 'status' => 'pending', 'owner_id' => 4, ...], ...]
        $this->tenant()->transaction(function () use ($programmationId, $adjustments) {
            foreach ($adjustments as $adj) {
                $this->tenant()->table('mission_phase_assignments')
                    ->where('id', $adj['assignment_id'])
                    ->where('mission_programmation_id', $programmationId)
                    ->update(array_filter([
                        'status'       => $adj['status']       ?? null,
                        'owner_id'     => $adj['owner_id']     ?? null,
                        'planned_start'=> $adj['planned_start']?? null,
                        'planned_end'  => $adj['planned_end']  ?? null,
                        'updated_at'   => now(),
                    ], fn($v) => !is_null($v)));
            }
        });
    }

    // =========================================================================
    // 3) CONFIRMATION FINALE : la mission passe en "planifiée"
    // =========================================================================
    public function confirm(int $missionId, int $programmationId): void
    {
        $this->tenant()->transaction(function () use ($missionId, $programmationId) {
            $this->tenant()->table('missions')->where('id', $missionId)->update([
                'status'     => 'planifiée',
                'updated_at' => now(),
            ]);
            $this->tenant()->table('mission_programmation')->where('id', $programmationId)->update([
                'status'     => 'planifiee',
                'updated_at' => now(),
            ]);
        });
    }
}
