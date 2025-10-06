<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Helpers sûrs
        $hasCol = fn(string $table, string $col) => Schema::hasColumn($table, $col);
        $try = function (string $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) { /* ignore */ }
        };

        /* ---------------------------------------------------------
         | ROLES : add tenant_id (nullable) + unique (name,guard,tenant)
         * ---------------------------------------------------------*/
        if (!$hasCol('roles','tenant_id')) {
            Schema::table('roles', function (Blueprint $t) {
                $t->unsignedBigInteger('tenant_id')->nullable()->after('guard_name');
                $t->index('tenant_id', 'roles_tenant_id_idx');
            });
        }
        // drop ancien unique si existant (nom dépend des versions)
        $try('ALTER TABLE roles DROP INDEX roles_name_guard_name_unique');
        $try('ALTER TABLE roles DROP INDEX roles_name_guard_unique');
        // (ré)créer l’unique incluant tenant_id
        $try('CREATE UNIQUE INDEX roles_name_guard_tenant_unique ON roles (name, guard_name, tenant_id)');

        /* ---------------------------------------------------------
         | PERMISSIONS : add tenant_id (nullable) + unique (name,guard,tenant)
         * ---------------------------------------------------------*/
        if (!$hasCol('permissions','tenant_id')) {
            Schema::table('permissions', function (Blueprint $t) {
                $t->unsignedBigInteger('tenant_id')->nullable()->after('guard_name');
                $t->index('tenant_id', 'permissions_tenant_id_idx');
            });
        }
        $try('ALTER TABLE permissions DROP INDEX permissions_name_guard_name_unique');
        $try('ALTER TABLE permissions DROP INDEX permissions_name_guard_unique');
        $try('CREATE UNIQUE INDEX permissions_name_guard_tenant_unique ON permissions (name, guard_name, tenant_id)');

        /* ---------------------------------------------------------
         | MODEL_HAS_ROLES : add tenant_id + PK (role_id, model_id, model_type, tenant_id)
         * ---------------------------------------------------------*/
        if (!$hasCol('model_has_roles','tenant_id')) {
            Schema::table('model_has_roles', function (Blueprint $t) {
                $t->unsignedBigInteger('tenant_id')->nullable()->after('model_type');
            });
        }
        $try('ALTER TABLE model_has_roles DROP PRIMARY KEY');
        $try('ALTER TABLE model_has_roles ADD PRIMARY KEY (role_id, model_id, model_type, tenant_id)');

        /* ---------------------------------------------------------
         | MODEL_HAS_PERMISSIONS : add tenant_id + PK (permission_id, model_id, model_type, tenant_id)
         * ---------------------------------------------------------*/
        if (!$hasCol('model_has_permissions','tenant_id')) {
            Schema::table('model_has_permissions', function (Blueprint $t) {
                $t->unsignedBigInteger('tenant_id')->nullable()->after('model_type');
            });
        }
        $try('ALTER TABLE model_has_permissions DROP PRIMARY KEY');
        $try('ALTER TABLE model_has_permissions ADD PRIMARY KEY (permission_id, model_id, model_type, tenant_id)');

        /* ---------------------------------------------------------
         | ROLE_HAS_PERMISSIONS : SANS tenant_id + PK (permission_id, role_id)
         * ---------------------------------------------------------*/
        if ($hasCol('role_has_permissions','tenant_id')) {
            // supprimer la colonne seulement si elle existe vraiment
            try {
                Schema::table('role_has_permissions', function (Blueprint $t) {
                    $t->dropColumn('tenant_id');
                });
            } catch (\Throwable $e) { /* ignore */ }
        }
        $try('ALTER TABLE role_has_permissions DROP PRIMARY KEY');
        $try('ALTER TABLE role_has_permissions ADD PRIMARY KEY (permission_id, role_id)');
    }

    public function down(): void
    {
        // Rollback minimal (on n’essaie pas de remettre tenant_id sur role_has_permissions)
        // Laisse vide ou ajoute ce dont tu as besoin selon ton historique.
    }
};
