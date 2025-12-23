<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        // ============== 1. RÔLES IDEA (RÉFÉRENCE) ==============
        if (!Schema::connection($this->connection)->hasTable('activity_idea_roles')) {
            Schema::connection($this->connection)->create('activity_idea_roles', function (Blueprint $t) {
                $t->id();
                $t->string('code', 10)->unique();      // I, D, E, A
                $t->string('label', 100);               // Impliqué, Décide, etc.
                $t->text('description')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });

            // Seeding automatique des rôles IDEA
            \DB::connection('tenant')->table('activity_idea_roles')->insert([
                [
                    'code' => 'I',
                    'label' => 'Impliqué',
                    'description' => 'Personne impliquée dans le processus',
                    'sort' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'D',
                    'label' => 'Décide',
                    'description' => 'Personne qui prend la décision',
                    'sort' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'E',
                    'label' => 'Élabore',
                    'description' => 'Personne qui conçoit/élabore',
                    'sort' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'A',
                    'label' => 'Applique',
                    'description' => 'Personne qui applique/exécute',
                    'sort' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // ============== 2. ASSIGNATIONS IDEA ==============
        if (!Schema::connection($this->connection)->hasTable('activity_idea_session_assignments')) {
            Schema::connection($this->connection)->create('activity_idea_session_assignments', function (Blueprint $t) {
                $t->id();

                // 🔗 RÉFÉRENCE À LA SESSION
                $t->foreignId('session_id')
                    ->constrained('process_evaluation_sessions')
                    ->cascadeOnDelete();

                // 🔗 Relations locales
                $t->foreignId('activity_id')
                    ->constrained('activities')
                    ->cascadeOnDelete();
                
                $t->foreignId('function_id')
                    ->constrained('functions')
                    ->cascadeOnDelete();
                
                // Référence au rôle IDEA (I, D, E, A)
                $t->foreignId('idea_role_id')
                    ->constrained('activity_idea_roles')
                    ->cascadeOnDelete();

                // 👤 Audit
                $t->unsignedBigInteger('created_by_user_id')
                    ->nullable()
                    ->comment('ID utilisateur externe');
                
                $t->string('created_by_user_name', 255)
                    ->nullable()
                    ->comment('Snapshot du nom utilisateur');

                // 🕐 Timestamps
                $t->timestamps();

                // 🔍 CONSTRAINTS & INDEX
                $t->unique(
                    ['session_id', 'activity_id', 'function_id', 'idea_role_id'],
                    'uq_aisa_session_act_func_role'
                );

                $t->index(['session_id', 'activity_id'], 'idx_aisa_session_activity');
                $t->index('function_id', 'idx_aisa_function');
                $t->index('idea_role_id', 'idx_aisa_role');
                $t->index('created_by_user_id', 'idx_aisa_user');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('activity_idea_session_assignments');
        Schema::connection($this->connection)->dropIfExists('activity_idea_roles');
    }
};