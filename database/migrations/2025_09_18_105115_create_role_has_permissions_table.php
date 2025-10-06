<?php
// database/migrations/2025_09_30_150500_fix_or_create_role_has_permissions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Si la table n'existe pas, on la crée avec le schéma Spatie standard (sans tenant_id)
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $t) {
                $t->unsignedBigInteger('permission_id');
                $t->unsignedBigInteger('role_id');
                $t->primary(['permission_id','role_id']);
            });

            // FKs après création (pour éviter certains moteurs stricts)
            Schema::table('role_has_permissions', function (Blueprint $t) {
                $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
                $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });

            return; // ✅ tout bon
        }

        // 2) Sinon, on RÉPARE la table existante

        // a) Enlever tenant_id si présent
        if (Schema::hasColumn('role_has_permissions', 'tenant_id')) {
            try { DB::statement('ALTER TABLE `role_has_permissions` DROP FOREIGN KEY `role_has_permissions_tenant_id_foreign`'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE `role_has_permissions` DROP INDEX `role_has_permissions_tenant_id_index`'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE `role_has_permissions` DROP COLUMN `tenant_id`'); } catch (\Throwable $e) {}
        }

        // b) S’assurer des types
        try {
            Schema::table('role_has_permissions', function (Blueprint $t) {
                $t->unsignedBigInteger('permission_id')->change();
                $t->unsignedBigInteger('role_id')->change();
            });
        } catch (\Throwable $e) {}

        // c) (Re)poser la PK composite permission_id + role_id
        try { DB::statement('ALTER TABLE `role_has_permissions` DROP PRIMARY KEY'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE `role_has_permissions` ADD PRIMARY KEY (`permission_id`,`role_id`)'); } catch (\Throwable $e) {}

        // d) (Re)poser les FKs si besoin (ignorera les doublons)
        try {
            Schema::table('role_has_permissions', function (Blueprint $t) {
                $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            });
        } catch (\Throwable $e) {}
        try {
            Schema::table('role_has_permissions', function (Blueprint $t) {
                $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // Pas de retour à un état avec tenant_id (non souhaité)
        // Si tu veux rollback violent : dé-commente la ligne suivante :
        // Schema::dropIfExists('role_has_permissions');
    }
};
