<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Mission Phases V4 avec Fiches Complètes
     */
    public function up(): void
    {
        Schema::create('mission_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 200);
            $table->text('description')->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });

        Schema::create('mission_phases', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('code_full', 100);
            $table->string('label', 255);
            
            // ✅ NOUVEAU V4: Description complète fiche
            $table->longText('description')->nullable();
            
            // ✅ NOUVEAU V4: Type phase
            $table->string('phase_type', 50)->nullable();
            
            // ✅ NOUVEAU V4: 4 Logos distincts
            $table->string('logo_preparation', 255)->nullable();
            $table->string('logo_verification', 255)->nullable();
            $table->string('logo_conclusion', 255)->nullable();
            $table->string('logo_suivi', 255)->nullable();
            
            // Hiérarchie
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(1);
            $table->unsignedBigInteger('mission_type_id');
            $table->integer('weight')->default(0);
            $table->boolean('is_decomposable')->default(false);
            $table->string('status', 50)->default('active');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('parent_id')
                ->references('id')
                ->on('mission_phases')
                ->onDelete('cascade');
            $table->foreign('mission_type_id')
                ->references('id')
                ->on('mission_types')
                ->onDelete('cascade');

            // Indexes
            $table->unique(['code_full', 'mission_type_id'], 'uq_code_type');
            $table->index('code_full');
            $table->index('parent_id');
            $table->index('level');
            $table->index('phase_type');
            $table->index('status');
            $table->index('mission_type_id');
        });

        Schema::create('mission_phase_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mission_id');
            $table->unsignedBigInteger('mission_phase_id');
            $table->string('status', 50)->default('pending');
            $table->integer('progress')->default(0);
            $table->integer('actual_weight')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->json('team_members')->nullable();
            $table->longText('description')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('conclusions')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            // Foreign Key
            $table->foreign('mission_phase_id')
                ->references('id')
                ->on('mission_phases')
                ->onDelete('cascade');

            // Indexes
            $table->index('mission_id');
            $table->index('mission_phase_id');
            $table->index('status');
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_phase_assignments');
        Schema::dropIfExists('mission_phases');
        Schema::dropIfExists('mission_types');
    }
};