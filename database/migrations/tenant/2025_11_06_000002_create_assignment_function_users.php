<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // CREATE si absent
        if (! Schema::hasTable('assignment_function_users')) {
            Schema::create('assignment_function_users', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('assignment_id');
                $t->unsignedBigInteger('function_id');
                $t->unsignedBigInteger('entity_id');
                $t->unsignedBigInteger('user_id'); // 🔸 pas de FK cross-db

                $t->string('role_label', 100)->nullable();
                $t->date('start_at')->nullable();
                $t->date('end_at')->nullable();
                $t->boolean('is_primary')->default(false);
                $t->timestamps();

                // Unicité logique
                $t->unique(['assignment_id','function_id','user_id'], 'afu_unique');

                // FKs LOCALES (tenant)
                $t->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
                $t->foreign('function_id')->references('id')->on('functions')->cascadeOnDelete();
                $t->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();

                // Index pratiques
                $t->index(['entity_id','function_id'], 'afu_entity_function_idx');
                $t->index(['user_id'], 'afu_user_id_idx'); // 🔸 index simple, PAS de FK
            });
            return;
        }

        // ALTER si déjà existante (sécurise indexes + supprime FK user si elle existe)
        Schema::table('assignment_function_users', function (Blueprint $t) {
            // s'assurer des colonnes/index
            if (! Schema::hasColumn('assignment_function_users','role_label')) {
                $t->string('role_label', 100)->nullable()->after('user_id');
            }
            try { $t->unique(['assignment_id','function_id','user_id'], 'afu_unique'); } catch (\Throwable $e) {}
            try { $t->index(['entity_id','function_id'], 'afu_entity_function_idx'); } catch (\Throwable $e) {}
            try { $t->index(['user_id'], 'afu_user_id_idx'); } catch (\Throwable $e) {}
        });

        // 🔧 Drop FK user_id si elle existe (nom incertain → on parcourt INFORMATION_SCHEMA)
        try {
            $db = DB::getDatabaseName();
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'assignment_function_users'
                  AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME = 'users'
                LIMIT 1
            ", [$db]);

            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `assignment_function_users` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_function_users');
    }
};
