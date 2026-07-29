<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ════════════════════════════════════════════════════════════════════════════════════
        // ÉCHELLES D'ÉVALUATION (1-4)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_factor_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->integer('value'); // 1, 2, 3, 4
            $table->string('label'); // Mineur, Important, Considérable, Critique
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['entity_id', 'value']);
            $table->index('entity_id');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // FACTEURS D'AUDIT (Conséquence, Urgence, Perte liquidité, etc)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->integer('order_position');
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['entity_id', 'order_position']);
            $table->index(['entity_id', 'is_active']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // SOURCES DE MISSION (MAD, MBR)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_mission_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->string('code'); // MAD, MBR
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['entity_id', 'code']);
            $table->index('entity_id');
        });

    

        // ════════════════════════════════════════════════════════════════════════════════════
        // ÉVALUATION DES FACTEURS (Processus × Facteur × Année)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_factor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->foreignId('process_id')->constrained('audit_processes')->cascadeOnDelete();
            $table->foreignId('factor_id')->constrained('audit_factors')->cascadeOnDelete();
            $table->integer('score')->default(1); // 1-4
            $table->float('normalized_score')->default(0.25); // score/4
            $table->text('justification')->nullable();
            $table->integer('evaluation_year')->nullable();
            $table->timestamps();
            
            $table->unique(['process_id', 'factor_id', 'evaluation_year']);
            $table->index(['entity_id', 'process_id']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // RÉSUMÉ ÉVALUATION PROCESSUS (Par année)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_process_evaluation_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->foreignId('process_id')->constrained('audit_processes')->cascadeOnDelete();
            $table->float('average_score')->default(0); // Moyenne des facteurs
            $table->string('rating')->default('Mineur');
            $table->integer('audit_frequency')->default(1); // Tous les N ans
            $table->integer('evaluation_year');
            $table->timestamps();
            
            $table->unique(['process_id', 'evaluation_year']);
            $table->index(['entity_id', 'evaluation_year']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // DEMANDES DE MISSIONS (Formulaires MAD)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_mission_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->string('code')->unique(); // FPM-0001-2026
            $table->foreignId('mission_source_id')->constrained('audit_mission_sources')->restrictOnDelete();
            $table->string('mission_type')->nullable();
            $table->string('mission_objective')->nullable();
            $table->text('description')->nullable();
            $table->text('concern')->nullable();
            $table->text('result')->nullable();
            $table->string('audit_scope')->nullable();
            
            // Liens optionnels
            $table->foreignId('related_risk_id')->nullable()->constrained('audit_risks')->setNullOnDelete();
            $table->foreignId('related_process_id')->nullable()->constrained('audit_processes')->setNullOnDelete();
            $table->foreignId('related_function_id')->nullable()->constrained('audit_functions')->setNullOnDelete();
            
            $table->string('frequency')->default('Ponctuelle');
            // id user MASTER (ddmparam), résolu via App\Models\User — pas de FK
            // vers users du tenant (ids master ≠ tenant).
            $table->unsignedBigInteger('requester_id')->index();
            $table->date('requested_date');
            $table->date('proposed_date')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, scheduled
            $table->timestamps();
            
            $table->index(['entity_id', 'mission_source_id', 'status']);
            $table->index(['entity_id', 'requested_date']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // ENTITÉS AUDITÉES PAR DEMANDE DE MISSION
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_mission_request_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->foreignId('mission_request_id')->constrained('audit_mission_requests')->cascadeOnDelete();
            $table->foreignId('audited_entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['mission_request_id', 'audited_entity_id']);
            $table->index('entity_id');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // FACTEURS SCORE POUR MISSIONS À LA DEMANDE (Priorisation)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_mission_request_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->foreignId('mission_request_id')->constrained('audit_mission_requests')->cascadeOnDelete();
            $table->foreignId('factor_id')->constrained('audit_factors')->cascadeOnDelete();
            $table->integer('score')->default(0); // 0-4
            $table->timestamps();
            
            $table->unique(['mission_request_id', 'factor_id']);
            $table->index('entity_id');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // PLANS ANNUELS D'AUDIT
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_annual_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->integer('fiscal_year');
            $table->text('strategy')->nullable();
            $table->integer('total_budget')->nullable();
            $table->string('status')->default('draft'); // draft, approved, in_progress, closed
            $table->timestamps();
            
            $table->unique(['entity_id', 'fiscal_year']);
            $table->index(['entity_id', 'status']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // MISSIONS PLANIFIÉES (ABR)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->string('code')->unique(); // MIS-001-2026
            $table->foreignId('annual_plan_id')->constrained('audit_annual_plans')->cascadeOnDelete();
            $table->foreignId('mission_source_id')->constrained('audit_mission_sources')->restrictOnDelete();
            $table->foreignId('risk_id')->nullable()->constrained('audit_risks')->setNullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained('audit_processes')->setNullOnDelete();
            
            $table->string('mission_type')->nullable();
            $table->string('title');
            $table->text('objective')->nullable();
            $table->integer('priority_rank')->nullable();
            $table->integer('criticality')->nullable();
            
            $table->date('scheduled_start_date')->nullable();
            $table->date('scheduled_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            $table->integer('budget')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
            
            $table->index(['entity_id', 'annual_plan_id']);
            $table->index(['entity_id', 'priority_rank']);
            $table->index(['entity_id', 'status']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // ÉQUIPES DE MISSION
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_mission_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('audit_entities')->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('audit_missions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('function_id')->nullable()->constrained('audit_functions')->setNullOnDelete();
            $table->string('role'); // Lead, Auditor, Support
            $table->timestamps();
            
            $table->unique(['mission_id', 'user_id']);
            $table->index('entity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_mission_teams');
        Schema::dropIfExists('audit_missions');
        Schema::dropIfExists('audit_annual_plans');
        Schema::dropIfExists('audit_mission_request_factors');
        Schema::dropIfExists('audit_mission_request_entities');
        Schema::dropIfExists('audit_mission_requests');
        Schema::dropIfExists('audit_process_evaluation_summaries');
        Schema::dropIfExists('audit_factor_evaluations');
        Schema::dropIfExists('audit_risks');
        Schema::dropIfExists('audit_mission_sources');
        Schema::dropIfExists('audit_factors');
        Schema::dropIfExists('audit_factor_scales');
        Schema::dropIfExists('audit_process_function_mappings');
        Schema::dropIfExists('audit_processes');
        Schema::dropIfExists('audit_functions');
        Schema::dropIfExists('audit_entities');
    }
};