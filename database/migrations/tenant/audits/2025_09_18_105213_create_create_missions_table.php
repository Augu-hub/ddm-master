<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();

            // Code unique généré automatiquement
            $table->string('code', 30)->unique()->nullable()
                ->comment('ex: ASS-ADC-025-2025');

            $table->string('fpm_number', 20)->nullable()
                ->comment('Numéro FPM si existant');

            // Lien vers ta table audit_exercises
            $table->foreignId('audit_exercise_id')
                ->constrained('audit_exercises')
                ->onDelete('restrict')
                ->comment('Exercice budgétaire rattaché');

            // Type de mission (ASS-AAP, ASS-ADC, etc.)
            $table->foreignId('mission_type_id')
                ->constrained('mission_types')
                ->onDelete('restrict')
                ->comment('Type de mission');

            $table->string('title', 255)
                ->comment('Intitulé de la mission');

            $table->text('objective')->nullable()
                ->comment('Objectif principal');

            $table->string('domain', 120)->nullable();

            $table->string('reference_document', 120)->nullable();

            $table->enum('priority', ['basse', 'moyenne', 'haute', 'critique'])
                ->default('moyenne');

            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->integer('planned_duration_days')->default(0);

            $table->enum('status', ['brouillon', 'planifiée', 'en_cours', 'terminée', 'annulée'])
                ->default('brouillon');

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Index utiles
            $table->index(['audit_exercise_id', 'status']);
            $table->index(['mission_type_id', 'status']);
            $table->index('code');
            $table->index('fpm_number');
        });

        // Pivot mission ↔ risques
        Schema::create('mission_risk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('risk_id')->constrained('risks')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mission_id', 'risk_id']);
        });

        // Pivot mission ↔ compétences + niveau requis
        Schema::create('mission_competency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->unsignedInteger('minimum_level')->default(1);
            $table->timestamps();
            $table->unique(['mission_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_competency');
        Schema::dropIfExists('mission_risk');
        Schema::dropIfExists('missions');
    }
};