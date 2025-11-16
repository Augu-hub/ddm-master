<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('function_assignments', function (Blueprint $t) {
            $t->id();

            // 🏢 Entité & Fonction (tenant)
            $t->unsignedBigInteger('entity_id');
            $t->unsignedBigInteger('function_id');

            // 👤 Utilisateur (provenant de la base "param/mysql") → pas de FK cross-DB
            $t->unsignedBigInteger('user_id')->nullable();

            $t->timestamps();

            // 🔒 Un seul user par (entité, fonction)
            $t->unique(['entity_id','function_id'], 'fa_unique');

            // ⚡ Index utiles
            $t->index('entity_id',   'fa_entity_idx');
            $t->index('function_id', 'fa_function_idx');
            $t->index('user_id',     'fa_user_idx');

            // 🔗 FKs côté tenant uniquement (OK)
            $t->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();
            $t->foreign('function_id')->references('id')->on('functions')->cascadeOnDelete();

            // ❗ Pas de FK vers users (table dans une autre connexion)
        });
    }

    public function down(): void {
        // Nettoyer d’abord les FKs/Index nommés si besoin
        if (Schema::hasTable('function_assignments')) {
            Schema::table('function_assignments', function (Blueprint $t) {
                // drop FKs (si créées)
                try { $t->dropForeign(['entity_id']); } catch (\Throwable $e) {}
                try { $t->dropForeign(['function_id']); } catch (\Throwable $e) {}

                // drop index nommés (si existants)
                try { $t->dropUnique('fa_unique'); } catch (\Throwable $e) {}
                try { $t->dropIndex('fa_entity_idx'); } catch (\Throwable $e) {}
                try { $t->dropIndex('fa_function_idx'); } catch (\Throwable $e) {}
                try { $t->dropIndex('fa_user_idx'); } catch (\Throwable $e) {}
            });
        }

        Schema::dropIfExists('function_assignments');
    }
};
