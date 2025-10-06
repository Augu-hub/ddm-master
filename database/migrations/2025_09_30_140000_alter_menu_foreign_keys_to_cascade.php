<?php
// database/migrations/2025_09_30_140000_alter_menu_foreign_keys_to_cascade.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // menus.parent_id -> menus.id  (CASCADE)
        Schema::table('menus', function (Blueprint $t) {
            // si le FK existe déjà on le drop, puis on recrée en cascade
            try { $t->dropForeign(['parent_id']); } catch (\Throwable $e) {}
            $t->foreign('parent_id')
              ->references('id')->on('menus')
              ->cascadeOnDelete()->cascadeOnUpdate();
        });

        // menu_permission.menu_id -> menus.id  (CASCADE)
        // menu_permission.permission_id -> permissions.id  (CASCADE)
        Schema::table('menu_permission', function (Blueprint $t) {
            try { $t->dropForeign(['menu_id']); } catch (\Throwable $e) {}
            try { $t->dropForeign(['permission_id']); } catch (\Throwable $e) {}

            $t->foreign('menu_id')
              ->references('id')->on('menus')
              ->cascadeOnDelete()->cascadeOnUpdate();

            $t->foreign('permission_id')
              ->references('id')->on('permissions')
              ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        // On remet en RESTRICT (optionnel)
        Schema::table('menus', function (Blueprint $t) {
            try { $t->dropForeign(['parent_id']); } catch (\Throwable $e) {}
            $t->foreign('parent_id')->references('id')->on('menus');
        });

        Schema::table('menu_permission', function (Blueprint $t) {
            try { $t->dropForeign(['menu_id']); } catch (\Throwable $e) {}
            try { $t->dropForeign(['permission_id']); } catch (\Throwable $e) {}

            $t->foreign('menu_id')->references('id')->on('menus');
            $t->foreign('permission_id')->references('id')->on('permissions');
        });
    }
};
