<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // On corrige sur la base maître
        DB::setDefaultConnection('mysql');

        // ====== ROLES ======
        if (Schema::connection('mysql')->hasTable('roles')) {
            $cols = $this->cols('roles');

            // a) Renommer tenant_id -> team_id si nécessaire
            if (in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                $this->sql("ALTER TABLE `roles` CHANGE COLUMN `tenant_id` `team_id` BIGINT UNSIGNED NULL");
            }
            // b) Ajouter team_id si aucune des deux n'existe
            $cols = $this->cols('roles');
            if (!in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                $this->sql("ALTER TABLE `roles` ADD `team_id` BIGINT UNSIGNED NULL AFTER `guard_name`");
            }

            // c) Index & unique: (name, guard_name, team_id) unique
            //    On tente de drop l'ancien unique, puis on recrée le bon
            $this->sql("ALTER TABLE `roles` DROP INDEX `roles_name_guard_name_unique`");
            $this->sql("ALTER TABLE `roles` ADD UNIQUE `roles_name_guard_name_team_id_unique` (`name`,`guard_name`,`team_id`)");
            $this->sql("CREATE INDEX `roles_team_id_index` ON `roles` (`team_id`)");
        }

        // ====== PERMISSIONS ======
        if (Schema::connection('mysql')->hasTable('permissions')) {
            $cols = $this->cols('permissions');

            // a) Renommer tenant_id -> team_id si nécessaire
            if (in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                $this->sql("ALTER TABLE `permissions` CHANGE COLUMN `tenant_id` `team_id` BIGINT UNSIGNED NULL");
            }
            // b) Ajouter team_id si aucune des deux n'existe
            $cols = $this->cols('permissions');
            if (!in_array('tenant_id', $cols) && !in_array('team_id', $cols)) {
                $this->sql("ALTER TABLE `permissions` ADD `team_id` BIGINT UNSIGNED NULL AFTER `guard_name`");
            }

            // c) Index & unique: (name, guard_name, team_id) unique
            $this->sql("ALTER TABLE `permissions` DROP INDEX `permissions_name_guard_name_unique`");
            $this->sql("ALTER TABLE `permissions` ADD UNIQUE `permissions_name_guard_name_team_id_unique` (`name`,`guard_name`,`team_id`)");
            $this->sql("CREATE INDEX `permissions_team_id_index` ON `permissions` (`team_id`)");
        }
    }

    public function down(): void
    {
        // Pas de rollback (inutile ici).
    }

    private function cols(string $table): array
    {
        return collect(DB::connection('mysql')->select("SHOW COLUMNS FROM `$table`"))
            ->pluck('Field')->map(fn($v)=>(string)$v)->all();
    }

    private function sql(string $sql): void
    {
        try { DB::connection('mysql')->statement($sql); } catch (\Throwable $e) {}
    }
};
