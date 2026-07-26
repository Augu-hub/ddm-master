<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Synchronise le référentiel central (base `ddmparam`, connexion Laravel
 * nommée `mysql` — cf. .env : DB_CONNECTION=mysql / DB_DATABASE=ddmparam)
 * vers les bases tenant.
 *
 * ⚠️ Il n'existe PAS de connexion nommée `default` dans ce projet, et il n'y
 *    a QU'UNE seule connexion Laravel (`mysql`). L'accès à un tenant
 *    arbitraire (ex: pour boucler sur tous les tenants, hors du contexte
 *    d'une requête où `TenantManager` a déjà configuré la connexion `tenant`
 *    pour l'utilisateur courant) se fait par requêtes SQL cross-base sur
 *    cette même connexion `mysql`, exactement comme le fait déjà
 *    `AuthenticatedSessionController::buildUserMenus()` :
 *
 *        DB::connection('mysql')->table(DB::raw("`{$dbName}`.`table`"))
 *
 *    ddmparam et les bases tenant doivent donc être sur le MÊME serveur
 *    MySQL/MariaDB (ce qui est le cas ici, cf. `db_host` dans `tenants`).
 *
 * Règles de propagation :
 *  - Les champs "identité/référence" (audit_type_label, audit_color,
 *    audit_icon) sont TOUJOURS réalignés sur la valeur centrale : c'est la
 *    source de vérité.
 *  - Les champs "opérationnels" propres au tenant (code, label, description,
 *    is_active, sort_order, is_mandatory, weight, status, logo_*) ne sont
 *    JAMAIS écrasés une fois créés.
 *  - Un tenant `mission_types` est identifié de façon stable par
 *    `audit_type_code` (le `code` reste un texte libre choisi par le tenant,
 *    ex: "AGC", "AMP"...).
 */
class TenantReferenceSyncService
{
    /** Base centrale (celle de la connexion `mysql`) — ddmparam par défaut */
    private string $centralDb;

    public function __construct()
    {
        $this->centralDb = (string) config('database.connections.mysql.database', 'ddmparam');
    }

    /** Connexion unique du projet — pointe sur `ddmparam` par défaut */
    private function central()
    {
        return DB::connection('mysql');
    }

    /** Table de la base centrale, préfixée explicitement (`ddmparam`.`x`) */
    private function centralTable(string $table)
    {
        return $this->central()->table(DB::raw("`{$this->centralDb}`.`{$table}`"));
    }

    /** Table tenant arbitraire, via requête cross-DB (voir AuthenticatedSessionController) */
    private function tenantTable(string $dbName, string $table)
    {
        return $this->central()->table(DB::raw("`{$dbName}`.`{$table}`"));
    }

    /** Synchronise TOUS les tenants connus dans ddmparam.tenants */
    public function syncAll(): array
    {
        $report = [];
        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use (&$report) {
            $report[$tenant->code] = $this->syncTenant($tenant);
        });
        return $report;
    }

    /** Synchronise un seul tenant (utilisé par le Job / la commande / l'Observer) */
    public function syncTenant(Tenant $tenant): array
    {
        $result = [
            'mission_types_created' => 0,
            'mission_types_updated' => 0,
            'phases_created'        => 0,
            'phases_updated'        => 0,
            'errors'                => [],
        ];

        if (empty($tenant->db_name)) {
            $result['errors'][] = "Tenant #{$tenant->id} ({$tenant->code}) sans db_name.";
            return $result;
        }

        // Le nom de base est interpolé dans du SQL brut : on n'accepte que des
        // identifiants stricts (défense en profondeur, la valeur vient certes
        // de ddmparam.tenants mais elle est saisie par un humain).
        if (!preg_match('/^[A-Za-z0-9_]+$/', $tenant->db_name)) {
            $result['errors'][] = "Tenant {$tenant->code} : db_name '{$tenant->db_name}' invalide (caractères non autorisés).";
            return $result;
        }

        // La base doit exister sur ce serveur (tenants de test parfois jamais provisionnés)
        $schemaExists = $this->central()->selectOne(
            'SELECT COUNT(*) AS n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$tenant->db_name]
        );
        if (!$schemaExists || (int) $schemaExists->n === 0) {
            $result['errors'][] = "Tenant {$tenant->code} : base '{$tenant->db_name}' introuvable sur ce serveur MySQL.";
            return $result;
        }

        try {
            $this->central()->transaction(function () use ($tenant, &$result) {
                $this->syncMissionTypesForTenant($tenant->db_name, $result);
                $this->syncMissionPhasesForTenant($tenant->db_name, $result);
            });
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("❌ TenantReferenceSyncService::syncTenant [{$tenant->code}] : " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    // =========================================================================
    // audit_types (ddmparam) ──► mission_types (tenant), par audit_type_code
    // =========================================================================
    private function syncMissionTypesForTenant(string $dbName, array &$result): void
    {
        $auditTypes = $this->centralTable('audit_types')
            ->where('is_active', 1)
            ->get(['id', 'code', 'label', 'color', 'icon']);

        foreach ($auditTypes as $at) {
            $existing = $this->tenantTable($dbName, 'mission_types')
                ->where('audit_type_code', $at->code)
                ->first();

            if ($existing) {
                // On corrige uniquement les champs dénormalisés (référence),
                // on ne touche jamais à `code`, `label`, `description`,
                // `is_active`, `sort_order` choisis par le tenant.
                // Écriture (et compteur) seulement si un champ a réellement dérivé.
                $dirty = [];
                if (($existing->audit_type_label ?? null) !== $at->label) {
                    $dirty['audit_type_label'] = $at->label;
                }
                if (($existing->audit_color ?? null) !== $at->color) {
                    $dirty['audit_color'] = $at->color;
                }
                if (($existing->audit_icon ?? null) !== $at->icon) {
                    $dirty['audit_icon'] = $at->icon;
                }

                if ($dirty !== []) {
                    $dirty['updated_at'] = now();
                    $this->tenantTable($dbName, 'mission_types')
                        ->where('id', $existing->id)
                        ->update($dirty);
                    $result['mission_types_updated']++;
                }
            } else {
                // Premier passage pour ce tenant : on crée un type de mission
                // "brouillon" que le tenant pourra ensuite renommer/paramétrer.
                $this->tenantTable($dbName, 'mission_types')->insert([
                    'code'             => $at->code,
                    'label'            => $at->label,
                    'description'      => null,
                    'is_active'        => 1,
                    'sort_order'       => 0,
                    'audit_type_code'  => $at->code,
                    'audit_type_label' => $at->label,
                    'audit_color'      => $at->color,
                    'audit_icon'       => $at->icon,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
                $result['mission_types_created']++;
            }
        }
    }

    // =========================================================================
    // audit_type_forms (ddmparam) ──► mission_phases (tenant)
    //
    // ⚠️ NOUVEAU SCHÉMA : mission_phases.id = ddmparam.audit_type_forms.id
    // directement (plus d'auto_increment séparé, plus de copie de contenu).
    // Le tenant ne stocke plus QUE ses réglages propres : mission_type_id,
    // is_mandatory, status, weight. Tout le contenu (label, phase_type,
    // hiérarchie, form_code) est lu en direct depuis ddmparam par les
    // contrôleurs (MissionPhaseAffectationController, AuditorMissionsController).
    // =========================================================================
    private function syncMissionPhasesForTenant(string $dbName, array &$result): void
    {
        // ⚠️ GARDE-FOU : si `mission_phases` a encore l'ancienne colonne `code`,
        // la migration `migrate_phases_to_central_ids.sql` n'a pas encore été
        // appliquée sur ce tenant. On refuse d'écrire (nouveau format d'id)
        // dans l'ancien schéma — ça violerait ses contraintes (`uq_code_type`...)
        // et pourrait associer par hasard un id ddmparam à une ligne d'un
        // AUTRE type de mission.
        $hasOldColumn = $this->central()
            ->selectOne("SELECT COUNT(*) as n FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'mission_phases' AND COLUMN_NAME = 'code'", [$dbName]);
        if ($hasOldColumn && (int) $hasOldColumn->n > 0) {
            throw new \RuntimeException(
                "Tenant {$dbName} : mission_phases n'a pas encore été migrée vers le nouveau ".
                "schéma (colonne 'code' encore présente). Lance ".
                "database/sql/migrate_phases_to_central_ids.sql sur ce tenant avant de resynchroniser."
            );
        }

        // Un mission_type tenant par audit_type_code
        $tenantMissionTypes = $this->tenantTable($dbName, 'mission_types')
            ->whereNotNull('audit_type_code')
            ->get(['id', 'audit_type_code'])
            ->keyBy('audit_type_code');

        $auditTypesById = $this->centralTable('audit_types')
            ->pluck('code', 'id'); // [id => code]

        $forms = $this->centralTable('audit_type_forms')
            ->where('is_active', 1)
            ->get(['id', 'audit_type_id']);

        // Ids déjà provisionnés côté tenant, pour éviter un aller-retour par ligne
        $existingIds = $this->tenantTable($dbName, 'mission_phases')
            ->pluck('mission_type_id', 'id'); // [id => mission_type_id]

        foreach ($forms as $f) {
            $auditTypeCode = $auditTypesById[$f->audit_type_id] ?? null;
            $missionType   = $auditTypeCode ? ($tenantMissionTypes[$auditTypeCode] ?? null) : null;
            if (!$missionType) {
                continue; // ce tenant n'a pas (encore) ce type de mission
            }

            if ($existingIds->has($f->id)) {
                // Déjà provisionnée : on ne touche JAMAIS is_mandatory/status/weight
                // (réglages du tenant), on s'assure juste que mission_type_id n'a
                // pas dérivé (rattachement à un autre audit_type_code par ex.).
                if ((int) $existingIds[$f->id] !== (int) $missionType->id) {
                    $this->tenantTable($dbName, 'mission_phases')
                        ->where('id', $f->id)
                        ->update(['mission_type_id' => $missionType->id, 'updated_at' => now()]);
                    $result['phases_updated']++;
                }
            } else {
                // Première apparition de cette phase pour ce tenant : on la
                // provisionne avec des réglages par défaut, modifiables ensuite.
                $this->tenantTable($dbName, 'mission_phases')->insert([
                    'id'              => $f->id, // ← = ddmparam.audit_type_forms.id
                    'mission_type_id' => $missionType->id,
                    'is_mandatory'    => 1,
                    'status'          => 'active',
                    'weight'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                $result['phases_created']++;
            }
        }
    }
}
