<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhaseSyncService
{
    // ══════════════════════════════════════════════════════════════════════════
    //  Synchronise LA TOTALITÉ des phases pour TOUS les tenants
    //  À exécuter via une commande artisan ou un appel admin
    // ══════════════════════════════════════════════════════════════════════════

    public static function syncAllTenants(): array
    {
        $result = ['updated' => 0, 'created' => 0, 'deleted' => 0, 'errors' => []];

        try {
            // Récupérer tous les mission_types du tenant courant
            $tenantTypes = DB::table('mission_types')->get();

            foreach ($tenantTypes as $tenantType) {
                $typeResult = self::syncForMissionType($tenantType->id);
                $result['updated'] += $typeResult['updated'];
                $result['created'] += $typeResult['created'];
                $result['deleted'] += $typeResult['deleted'];
                if (!empty($typeResult['errors'])) {
                    $result['errors'] = array_merge($result['errors'], $typeResult['errors']);
                }
            }
        } catch (\Exception $e) {
            Log::error('[PhaseSync] Échec syncAllTenants: ' . $e->getMessage());
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Synchronise un mission_type spécifique
    //  INSERT/UPDATE/DELETE pour aligner local_phases sur ddmparam
    // ══════════════════════════════════════════════════════════════════════════

    public static function syncForMissionType(int $missionTypeId): array
    {
        $stats = ['updated' => 0, 'created' => 0, 'deleted' => 0, 'errors' => []];

        try {
            // 1. Récupérer les infos du mission_type local
            $localType = DB::table('mission_types')->where('id', $missionTypeId)->first();
            if (!$localType) {
                throw new \Exception("Mission type #{$missionTypeId} introuvable.");
            }

            $auditTypeCode = $localType->audit_type_code
                ?? self::guessAuditTypeCode($localType->code);

            if (!$auditTypeCode) {
                Log::warning("[PhaseSync] Aucun audit_type_code pour mission_type #{$missionTypeId}");
                return $stats;
            }

            // 2. Récupérer le audit_type depuis ddmparam
            $ddmAuditType = DB::connection('mysql')
                ->table('ddmparam.audit_types')
                ->where('code', $auditTypeCode)
                ->first();

            if (!$ddmAuditType) {
                throw new \Exception("Audit type '{$auditTypeCode}' introuvable dans ddmparam.");
            }

            // 3. Récupérer TOUS les formulaires ddmparam pour cet audit_type
            $ddmForms = DB::connection('mysql')
                ->table('ddmparam.audit_type_forms as f')
                ->where('f.audit_type_id', $ddmAuditType->id)
                ->where('f.is_active', 1)
                ->orderBy('f.phase_num')
                ->orderBy('f.sort_order')
                ->orderBy('f.id')
                ->get();

            if ($ddmForms->isEmpty()) {
                Log::warning("[PhaseSync] Aucun formulaire ddmparam pour audit_type_id={$ddmAuditType->id}");
                return $stats;
            }

            // 4. Construire la map des phases locales existantes (indexées par form_code)
            $localPhases = DB::table('mission_phases')
                ->where('mission_type_id', $missionTypeId)
                ->get()
                ->keyBy('form_code');

            $phaseTypeMap = [
                1 => 'PREPARATION',
                2 => 'VERIFICATION',
                3 => 'CONCLUSION',
                4 => 'SUIVI',
                5 => 'RECOMMANDATIONS',
            ];

            // Map pour résoudre les parent_id après création des parents
            $ddmIdToLocalId = [];

            DB::beginTransaction();

            // 5. Parcourir les formulaires ddmparam
            foreach ($ddmForms as $ddm) {
                $local = $localPhases->get($ddm->code);
                $phaseType = $phaseTypeMap[$ddm->phase_num] ?? 'AUTRE';

                // Déterminer parent_id local
                $localParentId = null;
                if ($ddm->parent_id && isset($ddmIdToLocalId[$ddm->parent_id])) {
                    $localParentId = $ddmIdToLocalId[$ddm->parent_id];
                }

                $data = [
                    'mission_type_id' => $missionTypeId,
                    'code'            => $ddm->code,
                    'code_full'       => $ddm->code,
                    'label'           => $ddm->label,
                    'phase_type'      => $phaseType,
                    'level'           => $ddm->parent_id ? 2 : 1,
                    'parent_id'       => $localParentId,
                    'is_mandatory'    => false,
                    'form_code'       => $ddm->code,
                    'sort_order'      => (int) $ddm->sort_order,
                    'status'          => 'active',
                    'updated_at'      => now(),
                ];

                if ($local) {
                    // UPDATE
                    $modified = false;
                    if ((string)$local->label !== $ddm->label) {
                        $data['label'] = $ddm->label;
                        $modified = true;
                    }
                    if ((int)($local->parent_id ?? 0) !== (int)$localParentId) {
                        $data['parent_id'] = $localParentId;
                        $modified = true;
                    }
                    if ($local->phase_type !== $phaseType) {
                        $data['phase_type'] = $phaseType;
                        $modified = true;
                    }
                    if ((int)($local->sort_order ?? 0) !== (int)$ddm->sort_order) {
                        $data['sort_order'] = $ddm->sort_order;
                        $modified = true;
                    }

                    if ($modified) {
                        DB::table('mission_phases')
                            ->where('id', $local->id)
                            ->update($data);
                        $stats['updated']++;
                    }
                    $ddmIdToLocalId[$ddm->id] = $local->id;

                } else {
                    // CREATE
                    $data['created_at'] = now();
                    $newId = DB::table('mission_phases')->insertGetId($data);
                    $stats['created']++;
                    $ddmIdToLocalId[$ddm->id] = $newId;
                }
            }

            // 6. Supprimer les phases locales qui n'existent plus dans ddmparam
            $ddmCodes = $ddmForms->pluck('code')->toArray();
            $toDelete = DB::table('mission_phases')
                ->where('mission_type_id', $missionTypeId)
                ->whereNotIn('form_code', $ddmCodes)
                ->get();

            foreach ($toDelete as $del) {
                DB::table('mission_phases')->where('id', $del->id)->delete();
                $stats['deleted']++;
            }

            DB::commit();

            Log::info("[PhaseSync] MissionType #{$missionTypeId} [{$auditTypeCode}] : "
                . "C={$stats['created']} U={$stats['updated']} D={$stats['deleted']}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[PhaseSync] Échec syncForMissionType #{$missionTypeId}: " . $e->getMessage());
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Helper : Deviner audit_type_code depuis code du mission_type
    // ══════════════════════════════════════════════════════════════════════════

    private static function guessAuditTypeCode(?string $typeCode): ?string
    {
        $map = [
            'AC' => 'AC', 'AF' => 'AF', 'AP' => 'AP',
            'AM' => 'AM', 'RP' => 'RP', 'ES' => 'ES',
        ];
        return $map[strtoupper($typeCode ?? '')] ?? null;
    }
}