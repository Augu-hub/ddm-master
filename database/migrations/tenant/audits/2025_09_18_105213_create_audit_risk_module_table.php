<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣ RISK_TYPES - Types de risques (RÉFÉRENCE)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('color')->default('secondary');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'code']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 2️⃣ RISK_FREQUENCY_LEVELS - Niveaux de fréquence (RÉFÉRENCE)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_frequency_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code')->unique();
            $table->string('label');
            $table->integer('level');
            $table->text('description')->nullable();
            $table->string('color')->default('secondary');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'code']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 3️⃣ RISK_IMPACT_LEVELS - Niveaux d'impact (RÉFÉRENCE)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_impact_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code')->unique();
            $table->string('label');
            $table->integer('level');
            $table->text('description')->nullable();
            $table->string('color')->default('secondary');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'code']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 4️⃣ RISK_MATRIX - Matrice impact x fréquence (RÉFÉRENCE)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_matrix', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->integer('impact_level');
            $table->integer('frequency_level');
            $table->string('label')->nullable();
            $table->string('qualification')->nullable();
            $table->integer('criticality_score')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('project_id');
            $table->unique(['tenant_id', 'impact_level', 'frequency_level']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 5️⃣ ENTITIES - Entités (SANS tenant_id par défaut)
        // ════════════════════════════════════════════════════════════════════════════════════
      
        // ════════════════════════════════════════════════════════════════════════════════════
        // 8️⃣ AUDIT_EXERCISES - Exercices d'audit
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_exercises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('year')->default(2026);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'planning', 'in_progress', 'closing', 'closed', 'completed'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->text('objectives')->nullable();
            $table->text('scope')->nullable();
            $table->text('methodology')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('total_sessions')->default(0);
            $table->integer('completed_sessions')->default(0);
            $table->integer('total_risks_identified')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('is_active');
            $table->index('year');
            $table->unique(['tenant_id', 'code']);
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 9️⃣ AUDIT_SESSIONS - Sessions de création
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exercise_id');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('created_by');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('year')->default(2026);
            $table->date('session_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'paused', 'closed', 'cancelled'])->default('active');
            $table->boolean('is_validated')->default(false);
            $table->dateTime('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->integer('total_risks_created')->default(0);
            $table->integer('total_risks_validated')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('exercise_id');
            $table->index('entity_id');
            $table->index('status');
            $table->index('is_validated');
            $table->index('year');

            $table->foreign('exercise_id')->references('id')->on('audit_exercises')->onDelete('cascade');
            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('restrict');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 🔟 RISKS - Table principale des risques
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('audit_session_id')->nullable(); // Lien vers session
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->unsignedBigInteger('risk_type_id')->nullable();

            // Identification
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('activity_code')->nullable();

            // Cotations
            $table->unsignedBigInteger('frequency_level_id')->nullable();
            $table->decimal('frequency_net', 3, 1)->nullable();
            $table->unsignedBigInteger('impact_level_id')->nullable();
            $table->decimal('impact_net', 3, 1)->nullable();
            $table->integer('criticality')->nullable();

            // Gestion
            $table->string('owner')->nullable();
            $table->text('control_procedure')->nullable();
            $table->enum('status', ['identified', 'assessed', 'mitigated', 'monitored', 'closed'])->default('identified');
            $table->integer('year')->default(2026);

            // Tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('project_id');
            $table->index('audit_session_id');
            $table->index('entity_id');
            $table->index('process_id');
            $table->index('activity_id');
            $table->index('risk_type_id');
            $table->index('frequency_level_id');
            $table->index('impact_level_id');
            $table->index('status');
            $table->index('year');

            $table->foreign('audit_session_id')->references('id')->on('audit_sessions')->onDelete('set null');
            $table->foreign('risk_type_id')->references('id')->on('risk_types')->onDelete('set null');
            $table->foreign('frequency_level_id')->references('id')->on('risk_frequency_levels')->onDelete('set null');
            $table->foreign('impact_level_id')->references('id')->on('risk_impact_levels')->onDelete('set null');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣1️⃣ SESSION_RISKS - Risques en session (avant validation admin)
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('session_risks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('audit_session_id');
            $table->unsignedBigInteger('risk_id')->nullable();

            // Risk info
            $table->string('code');
            $table->string('label');
            $table->text('description')->nullable();

            // Classification
            $table->unsignedBigInteger('risk_type_id')->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();

            // Cotations
            $table->unsignedBigInteger('frequency_level_id')->nullable();
            $table->unsignedBigInteger('impact_level_id')->nullable();
            $table->integer('criticality')->nullable();
            $table->decimal('frequency_net', 3, 1)->nullable();
            $table->decimal('impact_net', 3, 1)->nullable();

            // Contrôle
            $table->text('control_procedure')->nullable();
            $table->string('owner')->nullable();

            // Status validation
            $table->enum('validation_status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('validation_comments')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();

            // Couleur d'affichage (warning|success|danger)
            $table->string('color')->default('warning');

            // Created in session
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('audit_session_id');
            $table->index('risk_id');
            $table->index('validation_status');
            $table->index('color');

            $table->foreign('audit_session_id')->references('id')->on('audit_sessions')->onDelete('cascade');
            $table->foreign('risk_id')->references('id')->on('risks')->onDelete('set null');
            $table->foreign('risk_type_id')->references('id')->on('risk_types')->onDelete('set null');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣2️⃣ RISK_CONTROLS - Contrôles des risques
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('risk_id');
            $table->unsignedBigInteger('activity_control_id')->nullable();

            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();

            $table->enum('type', ['preventive', 'detective', 'corrective'])->default('preventive');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');

            $table->string('evidence')->nullable();
            $table->unsignedBigInteger('function_id')->nullable();
            $table->string('owner')->nullable();

            $table->enum('status', ['active', 'inactive', 'under_review'])->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('risk_id');
            $table->foreign('risk_id')->references('id')->on('risks')->onDelete('cascade');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣3️⃣ RISK_MITIGATIONS - Plans d'atténuation
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_mitigations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('risk_id');

            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->text('strategy')->nullable();

            $table->text('action_plan')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->date('completion_date')->nullable();

            $table->unsignedBigInteger('residual_frequency_id')->nullable();
            $table->unsignedBigInteger('residual_impact_id')->nullable();
            $table->integer('residual_criticality')->nullable();

            $table->enum('status', ['planned', 'in_progress', 'completed', 'on_hold', 'cancelled'])->default('planned');
            $table->integer('progress')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('risk_id');
            $table->index('status');

            $table->foreign('risk_id')->references('id')->on('risks')->onDelete('cascade');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣4️⃣ RISK_ASSESSMENTS - Évaluations/Audits
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('risk_id');
            $table->unsignedBigInteger('project_id')->nullable();

            $table->string('code')->unique();
            $table->date('assessment_date');
            $table->enum('type', ['initial', 'periodic', 'ad_hoc', 'post_incident'])->default('periodic');

            $table->text('findings')->nullable();
            $table->enum('rating', ['green', 'yellow', 'red'])->default('green');
            $table->decimal('risk_score', 5, 2)->nullable();

            $table->text('evidence')->nullable();
            $table->text('recommendations')->nullable();
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->date('next_assessment_date')->nullable();

            $table->enum('status', ['draft', 'completed', 'approved', 'action_required'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('risk_id');
            $table->index('assessment_date');
            $table->index('rating');

            $table->foreign('risk_id')->references('id')->on('risks')->onDelete('cascade');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣5️⃣ RISK_ACTIVITY_INDICATORS - Indicateurs d'activité
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_activity_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('risk_id')->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->unsignedBigInteger('function_id')->nullable();

            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();

            $table->string('metric_name');
            $table->string('unit')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');

            $table->decimal('target_value', 8, 2)->nullable();
            $table->decimal('min_threshold', 8, 2)->nullable();
            $table->decimal('max_threshold', 8, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('risk_id');

            $table->foreign('risk_id')->references('id')->on('risks')->onDelete('set null');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣6️⃣ RISK_PERFORMANCE_INDICATORS - Indicateurs de performance
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_performance_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('risk_id')->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->unsignedBigInteger('function_id')->nullable();

            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();

            $table->string('kpi_name');
            $table->string('unit')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');

            $table->decimal('target_value', 8, 2)->nullable();
            $table->decimal('min_acceptable', 8, 2)->nullable();
            $table->decimal('ideal_value', 8, 2)->nullable();

            $table->text('calculation_formula')->nullable();
            $table->string('data_source')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('risk_id');

            $table->foreign('risk_id')->references('id')->on('risks')->onDelete('set null');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣7️⃣ RISK_INDICATOR_RECORDS - Enregistrements indicateurs
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_indicator_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('activity_indicator_id')->nullable();
            $table->unsignedBigInteger('performance_indicator_id')->nullable();

            $table->date('record_date');
            $table->decimal('recorded_value', 10, 2);
            $table->decimal('target_value', 10, 2)->nullable();
            $table->decimal('deviation', 10, 2)->nullable();
            $table->string('status')->nullable();

            $table->text('observations')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('record_date');
            $table->index('status');
        });

        // ════════════════════════════════════════════════════════════════════════════════════
        // 1️⃣8️⃣ RISK_AUDIT_LOG - Logs d'audit
        // ════════════════════════════════════════════════════════════════════════════════════
        Schema::create('risk_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');

            $table->string('action');
            $table->text('changes')->nullable();
            $table->text('reason')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        // ⚠️ ORDRE INVERSE - ENFANTS AVANT PARENTS
        Schema::dropIfExists('risk_audit_log');
        Schema::dropIfExists('risk_indicator_records');
        Schema::dropIfExists('risk_performance_indicators');
        Schema::dropIfExists('risk_activity_indicators');
        Schema::dropIfExists('risk_assessments');
        Schema::dropIfExists('risk_mitigations');
        Schema::dropIfExists('risk_controls');
        Schema::dropIfExists('session_risks');
        Schema::dropIfExists('risks');
        Schema::dropIfExists('audit_sessions');
        Schema::dropIfExists('audit_exercises');
        Schema::dropIfExists('risk_matrix');
        Schema::dropIfExists('risk_impact_levels');
        Schema::dropIfExists('risk_frequency_levels');
        Schema::dropIfExists('risk_types');
    }
};