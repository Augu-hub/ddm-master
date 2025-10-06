<?php
// database/migrations/2025_09_30_180000_fix_spatie_pivots_team_id.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Toujours sur la base maître
        DB::setDefaultConnection('mysql');

        // ===== model_has_permissions =====
        if (Schema::connection('mysql')->hasTable('model_has_permissions')) {
            $cols = $this->columns('model_has_permissions');
            // Renommer tenant_id -> team_id si nécessaire
            if (in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                // Drop PK si elle inclut tenant_id
                $this->trySql("ALTER TABLE `model_has_permissions` DROP PRIMARY KEY");
                // Renommer la colonne
                $this->trySql("ALTER TABLE `model_has_permissions` CHANGE COLUMN `tenant_id` `team_id` BIGINT UNSIGNED NULL");
                // Recréer la PK conforme Spatie (avec team_id)
                $this->trySql(
                    "ALTER TABLE `model_has_permissions`
                     ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`,`team_id`)"
                );
                // Index utilitaires (ne casse rien si déjà là)
                $this->trySql("CREATE INDEX `mhp_model_type_model_id_index` ON `model_has_permissions` (`model_type`,`model_id`)");
                $this->trySql("CREATE INDEX `mhp_team_id_index` ON `model_has_permissions` (`team_id`)");
            } elseif (!in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                // Si aucune des deux n'existe, on crée team_id + PK attendue
                $this->trySql("ALTER TABLE `model_has_permissions` ADD `team_id` BIGINT UNSIGNED NULL AFTER `model_type`");
                $this->trySql("ALTER TABLE `model_has_permissions` DROP PRIMARY KEY");
                $this->trySql(
                    "ALTER TABLE `model_has_permissions`
                     ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`,`team_id`)"
                );
                $this->trySql("CREATE INDEX `mhp_model_type_model_id_index` ON `model_has_permissions` (`model_type`,`model_id`)");
                $this->trySql("CREATE INDEX `mhp_team_id_index` ON `model_has_permissions` (`team_id`)");
            }
        }

        // ===== model_has_roles =====
        if (Schema::connection('mysql')->hasTable('model_has_roles')) {
            $cols = $this->columns('model_has_roles');
            if (in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                $this->trySql("ALTER TABLE `model_has_roles` DROP PRIMARY KEY");
                $this->trySql("ALTER TABLE `model_has_roles` CHANGE COLUMN `tenant_id` `team_id` BIGINT UNSIGNED NULL");
                $this->trySql(
                    "ALTER TABLE `model_has_roles`
                     ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`,`team_id`)"
                );
                $this->trySql("CREATE INDEX `mhr_model_type_model_id_index` ON `model_has_roles` (`model_type`,`model_id`)");
                $this->trySql("CREATE INDEX `mhr_team_id_index` ON `model_has_roles` (`team_id`)");
            } elseif (!in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                $this->trySql("ALTER TABLE `model_has_roles` ADD `team_id` BIGINT UNSIGNED NULL AFTER `model_type`");
                $this->trySql("ALTER TABLE `model_has_roles` DROP PRIMARY KEY");
                $this->trySql(
                    "ALTER TABLE `model_has_roles`
                     ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`,`team_id`)"
                );
                $this->trySql("CREATE INDEX `mhr_model_type_model_id_index` ON `model_has_roles` (`model_type`,`model_id`)");
                $this->trySql("CREATE INDEX `mhr_team_id_index` ON `model_has_roles` (`team_id`)");
            }
        }

        // NB: role_has_permissions NE DOIT PAS avoir team/tenant_id (laisse tel quel)
    }

    public function down(): void
    {
        // pas de rollback (inutile ici)
    }

    private function columns(string $table): array
    {
        return collect(DB::connection('mysql')->select("SHOW COLUMNS FROM `$table`"))
            ->pluck('Field')->map(fn($v)=>(string)$v)->all();
    }

    private function trySql(string $sql): void
    {
        try { DB::connection('mysql')->statement($sql); } catch (\Throwable $e) {}
    }
};
