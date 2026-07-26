<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  PhaseSyncService — v2 (NOUVEAU SCHÉMA)
 *
 *  ⚠️ Réécrit intégralement : l'ancienne version écrivait dans
 *  `mission_phases` avec les colonnes de l'ANCIEN schéma (code, label,
 *  phase_type, form_code…) qui n'existent plus depuis la migration
 *  `migrate_phases_to_central_ids.sql` → crash SQL garanti.
 *
 *  Nouveau contrat :
 *   - mission_phases.id = ddmparam.audit_type_forms.id (id partagé) ;
 *     le tenant ne stocke QUE ses réglages (mission_type_id, is_mandatory,
 *     status, weight) — le contenu est toujours lu dans ddmparam.
 *
 *  Deux responsabilités :
 *   1. syncForMissionType()        : provisionne les lignes mission_phases
 *      d'un programme d'audit tenant depuis le référentiel central.
 *   2. ensureMissionAssignments()  : LE CŒUR — garantit qu'une mission a un
 *      `mission_phase_assignment` pour CHAQUE formulaire actif ddmparam de
 *      son type d'audit (obligatoires → pending, optionnels → skipped).
 *      Idempotent, mis en cache 5 min, appelé automatiquement à l'ouverture
 *      d'une mission (BuildsMissionMenu / phases / gantt) : les phases
 *      apparaissent d'elles-mêmes, y compris pour les missions créées avant
 *      la génération auto ou après un ajout de formulaire côté admin.
 * ══════════════════════════════════════════════════════════════════════════
 */
class PhaseSyncService
{
    // ══════════════════════════════════════════════════════════════════════
    //  1) mission_phases (tenant) ← ddmparam, pour un mission_type
    // ══════════════════════════════════════════════════════════════════════

    public static function syncForMissionType(int $missionTypeId): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => []];

        try {
            $localType = DB::table('mission_types')->where('id', $missionTypeId)->first();
            if (!$localType) {
                throw new \Exception("Mission type #{$missionTypeId} introuvable.");
            }

            $auditTypeCode = $localType->audit_type_code
                ?: self::guessAuditTypeCode($localType->code);
            if (!$auditTypeCode) {
                Log::warning("[PhaseSync] Aucun audit_type_code pour mission_type #{$missionTypeId}");
                return $stats;
            }

            $formIds = self::centralFormIds($auditTypeCode);
            if ($formIds === []) return $stats;

            // Lignes tenant existantes pour ces ids centraux
            $existing = DB::table('mission_phases')
                ->whereIn('id', $formIds)
                ->pluck('mission_type_id', 'id');

            $now = now();
            foreach ($formIds as $fid) {
                if (!$existing->has($fid)) {
                    DB::table('mission_phases')->insert([
                        'id'              => $fid,   // = ddmparam.audit_type_forms.id
                        'mission_type_id' => $missionTypeId,
                        'is_mandatory'    => 1,
                        'status'          => 'active',
                        'weight'          => 0,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                    $stats['created']++;
                } elseif ((int) $existing[$fid] !== $missionTypeId) {
                    // Rattachement qui a dérivé (ex: fusion de programmes)
                    DB::table('mission_phases')->where('id', $fid)
                        ->update(['mission_type_id' => $missionTypeId, 'updated_at' => $now]);
                    $stats['updated']++;
                }
            }

            if ($stats['created'] || $stats['updated']) {
                Log::info("[PhaseSync] mission_type #{$missionTypeId} [{$auditTypeCode}] : "
                    . "+{$stats['created']} phase(s), {$stats['updated']} réalignée(s).");
            }
        } catch (\Throwable $e) {
            Log::error("[PhaseSync] syncForMissionType #{$missionTypeId}: " . $e->getMessage());
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  2) mission_phase_assignments ← ddmparam, pour UNE mission
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Garantit qu'une mission possède un assignment par formulaire actif
     * ddmparam de son type d'audit. Retourne le nombre créé.
     */
    public static function ensureMissionAssignments(int $missionId): int
    {
        // Anti-rafale : au plus une vraie passe toutes les 5 min par mission
        $cacheKey = "mission_assign_sync_{$missionId}";
        if (Cache::get($cacheKey)) return 0;

        try {
            // Mission → programme d'audit tenant → type d'audit central
            $m = DB::table('mission_programmation as mp')
                ->leftJoin('missions as ms', 'mp.mission_id', '=', 'ms.id')
                ->leftJoin('mission_types as mt', 'ms.mission_type_id', '=', 'mt.id')
                ->where('mp.id', $missionId)
                ->selectRaw('mt.id as mission_type_id, COALESCE(mt.audit_type_code, mt.code) as audit_type_code')
                ->first();

            if (!$m || !$m->audit_type_code || !$m->mission_type_id) {
                Cache::put($cacheKey, 1, 300);
                return 0;
            }

            $formIds = self::centralFormIds($m->audit_type_code);
            if ($formIds === []) {
                Cache::put($cacheKey, 1, 300);
                return 0;
            }

            // 1. Provisionner les réglages tenant manquants (mission_phases)
            $settings = DB::table('mission_phases')
                ->whereIn('id', $formIds)
                ->pluck('is_mandatory', 'id');

            $now = now();
            foreach ($formIds as $fid) {
                if (!$settings->has($fid)) {
                    DB::table('mission_phases')->insert([
                        'id'              => $fid,
                        'mission_type_id' => (int) $m->mission_type_id,
                        'is_mandatory'    => 1,
                        'status'          => 'active',
                        'weight'          => 0,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                    $settings[$fid] = 1;
                }
            }

            // 2. Créer les assignments manquants pour CETTE mission
            $existing = DB::table('mission_phase_assignments')
                ->where('mission_programmation_id', $missionId)
                ->pluck('mission_phase_id')
                ->all();

            $missing = array_values(array_diff($formIds, $existing));
            if ($missing !== []) {
                $rows = array_map(fn (int $fid) => [
                    'mission_programmation_id' => $missionId,
                    'mission_phase_id'         => $fid,
                    'entity_id'                => null, // toutes entités
                    // Obligatoire → à faire ; optionnel → ignorée par défaut
                    'status'                   => ((int) ($settings[$fid] ?? 1)) === 1 ? 'pending' : 'skipped',
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ], $missing);

                DB::table('mission_phase_assignments')->insert($rows);

                Log::info("[PhaseSync] Mission #{$missionId} [{$m->audit_type_code}] : "
                    . count($missing) . ' phase(s) provisionnée(s) automatiquement depuis ddmparam.');
            }

            Cache::put($cacheKey, 1, 300);
            return count($missing);
        } catch (\Throwable $e) {
            Log::error("[PhaseSync] ensureMissionAssignments #{$missionId}: " . $e->getMessage());
            return 0;
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Helpers
    // ══════════════════════════════════════════════════════════════════════

    /** Ids des formulaires actifs ddmparam pour un code de type d'audit. */
    private static function centralFormIds(string $auditTypeCode): array
    {
        $type = DB::connection('mysql')
            ->table('ddmparam.audit_types')
            ->where('code', strtoupper($auditTypeCode))
            ->first(['id']);
        if (!$type) return [];

        return DB::connection('mysql')
            ->table('ddmparam.audit_type_forms')
            ->where('audit_type_id', $type->id)
            ->where('is_active', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private static function guessAuditTypeCode(?string $typeCode): ?string
    {
        $map = [
            'AC' => 'AC', 'AF' => 'AF', 'AP' => 'AP',
            'AM' => 'AM', 'RP' => 'RP', 'ES' => 'ES',
        ];
        return $map[strtoupper($typeCode ?? '')] ?? null;
    }
}
