<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        // ============== 1. RÔLES RACI (RÉFÉRENCE) ==============
        // Table de référence pour les rôles R, A, C, I
        if (!Schema::connection($this->connection)->hasTable('activity_raci_roles')) {
            Schema::connection($this->connection)->create('activity_raci_roles', function (Blueprint $t) {
                $t->id();
                $t->string('code', 10)->unique();      // R, A, C, I
                $t->string('label', 100);               // Responsible, Accountable, etc.
                $t->text('description')->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });

            // Seeding automatique des rôles RACI
            \DB::connection('tenant')->table('activity_raci_roles')->insert([
                [
                    'code' => 'R',
                    'label' => 'Responsible',
                    'description' => 'La personne qui réalise concrètement le travail',
                    'sort' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'A',
                    'label' => 'Accountable',
                    'description' => 'La personne qui approuve le travail final (1 seul par tâche)',
                    'sort' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'C',
                    'label' => 'Consulted',
                    'description' => 'Les personnes dont l\'avis est requis',
                    'sort' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'I',
                    'label' => 'Informed',
                    'description' => 'Les personnes à tenir au courant',
                    'sort' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // ============== 2. ASSIGNATIONS RACI ==============
        // Table pour stocker les assignations RACI
        // ✅ RÉFÉRENCE À process_evaluation_sessions (EXISTANTE)
        if (!Schema::connection($this->connection)->hasTable('activity_raci_session_assignments')) {
            Schema::connection($this->connection)->create('activity_raci_session_assignments', function (Blueprint $t) {
                $t->id();

                // 🔗 RÉFÉRENCE À LA SESSION EXISTANTE
                // Cette session peut être utilisée par :
                // - Évaluation maturity (process_session_maturity_evaluations)
                // - Évaluation axis (process_session_axis_evaluations)
                // - RACI (activity_raci_session_assignments)
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
                
                // Référence au rôle RACI (R, A, C, I)
                $t->foreignId('raci_role_id')
                    ->constrained('activity_raci_roles')
                    ->cascadeOnDelete();

                // 👤 Audit - Qui a créé cette assignation
                $t->unsignedBigInteger('created_by_user_id')
                    ->nullable()
                    ->comment('ID utilisateur externe');
                
                $t->string('created_by_user_name', 255)
                    ->nullable()
                    ->comment('Snapshot du nom utilisateur');

                // 🕐 Audit timestamps
                $t->timestamps();

                // 🔍 CONSTRAINTS & INDEX
                // Unique: une session ne peut avoir qu'une fois le même (activity, function, role)
                // Exemple: ACT-001 + FUNC-Finance ne peut avoir qu'un seul "R"
                $t->unique(
                    ['session_id', 'activity_id', 'function_id', 'raci_role_id'],
                    'uq_arsa_session_act_func_role'
                );

                // Index pour les requêtes courantes
                $t->index(['session_id', 'activity_id'], 'idx_arsa_session_activity');
                $t->index('function_id', 'idx_arsa_function');
                $t->index('raci_role_id', 'idx_arsa_role');
                $t->index('created_by_user_id', 'idx_arsa_user');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('activity_raci_session_assignments');
        Schema::connection($this->connection)->dropIfExists('activity_raci_roles');
    }
};