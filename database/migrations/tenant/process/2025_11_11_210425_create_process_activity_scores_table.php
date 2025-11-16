<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Création si absente
        if (! Schema::hasTable('process_activity_scores')) {
            Schema::create('process_activity_scores', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('process_id');
                $t->unsignedBigInteger('activity_id');
                $t->unsignedBigInteger('evaluated_by')->nullable(); // 🔸 PAS de FK directe cross-db

                // Notes sur les 4 critères
                $t->integer('maturity_score')->nullable();
                $t->integer('motricity_score')->nullable();
                $t->integer('transversality_score')->nullable();
                $t->integer('strategic_score')->nullable();

                // Calcul automatique de la moyenne
                $t->float('activity_average')->nullable();

                $t->timestamp('evaluated_at')->nullable();
                $t->timestamps();

                // FKs locales
                $t->foreign('process_id')->references('id')->on('processes')->cascadeOnDelete();
                $t->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();

                // Index pratique sur evaluated_by
                $t->index(['evaluated_by'], 'pas_evaluated_by_idx'); // 🔸 Index simple, pas de FK
            });
            return;
        }

        // ALTER si la table existe déjà (ajustement sécurisé)
        Schema::table('process_activity_scores', function (Blueprint $t) {
            if (! Schema::hasColumn('process_activity_scores', 'evaluated_by')) {
                $t->unsignedBigInteger('evaluated_by')->nullable()->after('activity_id');
            }
            try { $t->index(['evaluated_by'], 'pas_evaluated_by_idx'); } catch (\Throwable $e) {}
        });

        // 🔧 Drop FK evaluated_by si elle existe (sécurisation)
        try {
            $db = DB::getDatabaseName();
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'process_activity_scores'
                  AND COLUMN_NAME = 'evaluated_by' AND REFERENCED_TABLE_NAME = 'users'
                LIMIT 1
            ", [$db]);

            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `process_activity_scores` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
            // ignorer
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('process_activity_scores');
    }
};
