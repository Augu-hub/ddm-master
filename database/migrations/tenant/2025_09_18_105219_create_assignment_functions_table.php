<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assignment_functions')) {
            Schema::create('assignment_functions', function (Blueprint $t) {
                $t->unsignedBigInteger('assignment_id');
                $t->unsignedBigInteger('function_id');
                $t->unsignedBigInteger('entity_id')->nullable();
                $t->string('role_label', 100)->nullable();
                $t->timestamps();

                $t->unique(['assignment_id','function_id'], 'af_unique');
                $t->index(['entity_id','function_id'], 'af_entity_function_idx');

                $t->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
                $t->foreign('function_id')->references('id')->on('functions')->cascadeOnDelete();
                $t->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();
            });
        } else {
            Schema::table('assignment_functions', function (Blueprint $t) {
                if (!Schema::hasColumn('assignment_functions','entity_id')) {
                    $t->unsignedBigInteger('entity_id')->nullable()->after('function_id');
                    $t->index(['entity_id','function_id'], 'af_entity_function_idx');
                    $t->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('assignment_functions','role_label')) {
                    $t->string('role_label', 100)->nullable()->after('entity_id');
                }
                if (!Schema::hasColumn('assignment_functions','created_at')) {
                    $t->timestamps();
                }
            });

            // Backfill depuis assignments
            DB::statement("
                UPDATE assignment_functions af
                JOIN assignments a ON a.id = af.assignment_id
                SET af.entity_id = a.entity_id
                WHERE af.entity_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignment_functions') && Schema::hasColumn('assignment_functions','entity_id')) {
            Schema::table('assignment_functions', function (Blueprint $t) {
                $t->dropForeign(['entity_id']);
                $t->dropIndex('af_entity_function_idx');
                $t->dropColumn('entity_id');
            });
        }
    }
};
