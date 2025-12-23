<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 🎯 AMDEC SYSTEM - Analyse des Modes de Défaillance, Effets et Criticité
     * 
     * Flux:
     * PHASE 1: Créer activités + modes de défaillance
     * PHASE 2: Ajouter plan d'action + criticité AVANT
     * PHASE 3: Ajouter résultats + criticité APRÈS
     */
    public function up(): void
    {
        // ======================================================================
        // 📋 TABLES RÉFÉRENTIELLES
        // ======================================================================

        // Gravité 1-5
        Schema::connection('tenant')->create('amdec_gravities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // G1-G5
            $table->string('label', 50);
            $table->text('description');
            $table->tinyInteger('degree')->unique(); // 1-5
            $table->string('color', 7)->default('#CCCCCC');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // Fréquence 1-5
        Schema::connection('tenant')->create('amdec_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // F1-F5
            $table->string('label', 50);
            $table->text('description');
            $table->tinyInteger('degree')->unique(); // 1-5
            $table->string('color', 7)->default('#CCCCCC');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // Détectabilité 1-5
        Schema::connection('tenant')->create('amdec_detectabilities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // D1-D5
            $table->string('label', 50);
            $table->text('description');
            $table->tinyInteger('degree')->unique(); // 1-5
            $table->string('appellation', 100);
            $table->string('color', 7)->default('#CCCCCC');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // Niveaux d'alerte
        Schema::connection('tenant')->create('amdec_standards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // Vert, Bleu, Jaune, Orange, Rouge
            $table->string('color', 7);
            $table->decimal('min_criticality', 5, 2);
            $table->decimal('max_criticality', 5, 2);
            $table->string('alert_label', 50);
            $table->text('actions');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // Cartographie
        Schema::connection('tenant')->create('amdec_cartographies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 10); // SP1-SP7
            $table->tinyInteger('gravity_degree'); // 1-5
            $table->tinyInteger('frequency_degree'); // 1-5
            $table->integer('criticality_score'); // G×F
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#CCCCCC');
            $table->timestamps();
            $table->unique(['gravity_degree', 'frequency_degree']);
        });

        // ======================================================================
        // 📋 TABLE ACTIVITÉS
        // ======================================================================

        Schema::connection('tenant')->create('amdec_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_id')->index();
            $table->string('name'); // "Entrée en relation avec le client"
            $table->text('description')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->unique(['process_id', 'name']);
        });

        // ======================================================================
        // 📋 TABLE PRINCIPALE AMDEC - Tout-en-un Phase 1, 2, 3
        // ======================================================================

        Schema::connection('tenant')->create('amdec_records', function (Blueprint $table) {
            $table->id();

            // Contexte
            $table->unsignedBigInteger('session_id')->index();
            $table->unsignedBigInteger('process_id')->index();
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->unsignedBigInteger('function_id')->index();

            // ====== PHASE 1: IDENTIFICATION ======
            $table->enum('phase', ['PHASE1', 'PHASE2', 'PHASE3'])->default('PHASE1')->index();
            $table->integer('mode_number')->nullable()->index(); // Numéro du mode (1, 2, 3...)

            // Mode de défaillance - CLÉ IMPORTANTE
            $table->string('failure_mode')->index(); // "Informations fausses d'identité"
            $table->text('failure_description')->nullable();
            $table->text('effects')->nullable(); // "Mauvais client"
            $table->text('causes')->nullable(); // "Conflit d'intérêt"
            $table->text('current_controls')->nullable(); // "Contrôle d'identité et de parentalité"

            // ====== PHASE 2: PLAN D'ACTION + CRITICITÉ AVANT ======

            // Criticité AVANT (Phase 2)
            $table->unsignedBigInteger('gravity_before_id')->nullable()->index();
            $table->tinyInteger('gravity_score_before')->nullable(); // 1-5
            $table->unsignedBigInteger('frequency_before_id')->nullable()->index();
            $table->tinyInteger('frequency_score_before')->nullable(); // 1-5
            $table->unsignedBigInteger('detectability_before_id')->nullable()->index();
            $table->tinyInteger('detectability_score_before')->nullable(); // 1-5
            $table->integer('criticality_before')->nullable(); // G×F×D (max 125)
            $table->decimal('criticality_nette_before', 5, 2)->nullable(); // %
            $table->unsignedBigInteger('standard_before_id')->nullable()->index();
            $table->string('alert_level_before', 50)->nullable(); // Vert/Bleu/Jaune/Orange/Rouge

            // Plan d'action
            $table->text('prevention_measures')->nullable(); // "Contrôle d'identité renforcé"
            $table->text('detection_measures')->nullable();
            $table->string('action_responsible')->nullable(); // "Chargé de crédit"
            $table->date('action_deadline')->nullable(); // "30-sept"

            // ====== PHASE 3: ACTIONS RÉALISÉES + CRITICITÉ APRÈS ======

            // Actions réalisées
            $table->text('implemented_prevention_measures')->nullable();
            $table->text('implemented_detection_measures')->nullable();
            $table->date('actual_completion_date')->nullable(); // "30-oct"

            // Criticité APRÈS (Phase 3)
            $table->unsignedBigInteger('gravity_after_id')->nullable()->index();
            $table->tinyInteger('gravity_score_after')->nullable(); // 1-5
            $table->unsignedBigInteger('frequency_after_id')->nullable()->index();
            $table->tinyInteger('frequency_score_after')->nullable(); // 1-5
            $table->unsignedBigInteger('detectability_after_id')->nullable()->index();
            $table->tinyInteger('detectability_score_after')->nullable(); // 1-5
            $table->integer('criticality_after')->nullable(); // G_net×F_net×D_net
            $table->decimal('criticality_nette_after', 5, 2)->nullable(); // %
            $table->unsignedBigInteger('standard_after_id')->nullable()->index();
            $table->string('alert_level_after', 50)->nullable(); // Vert/Bleu/Jaune/Orange/Rouge

            // Résultats
            $table->decimal('improvement_percentage', 5, 2)->nullable(); // (Avant-Après)/Avant×100

            // Status
            $table->enum('action_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending')->index();
            $table->boolean('is_phase2_approved')->default(false);
            $table->boolean('is_closed')->default(false);

            // Traçabilité
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('created_by_user_name')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->string('updated_by_user_name')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->string('closed_by_user_name')->nullable();

            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index(['session_id', 'process_id', 'activity_id'], 'idx_amdec_context');
            $table->index(['phase', 'action_status'], 'idx_amdec_phase_status');
        });

        // ======================================================================
        // 📊 TABLE SUIVI
        // ======================================================================

        Schema::connection('tenant')->create('amdec_phase_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->index();
            $table->unsignedBigInteger('process_id')->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->unsignedBigInteger('function_id')->index();

            // Compteurs
            $table->integer('total_count')->default(0);
            $table->integer('phase1_count')->default(0);
            $table->integer('phase2_count')->default(0);
            $table->integer('phase3_count')->default(0);
            $table->integer('completed_count')->default(0);

            // Statistiques
            $table->decimal('average_criticality_before', 5, 2)->nullable();
            $table->decimal('average_criticality_after', 5, 2)->nullable();
            $table->decimal('average_improvement', 5, 2)->nullable();

            // Distribution alertes
            $table->integer('alert_before_vert')->default(0);
            $table->integer('alert_before_bleu')->default(0);
            $table->integer('alert_before_jaune')->default(0);
            $table->integer('alert_before_orange')->default(0);
            $table->integer('alert_before_rouge')->default(0);

            $table->integer('alert_after_vert')->default(0);
            $table->integer('alert_after_bleu')->default(0);
            $table->integer('alert_after_jaune')->default(0);
            $table->integer('alert_after_orange')->default(0);
            $table->integer('alert_after_rouge')->default(0);

            // Phases
            $table->enum('current_phase', ['PHASE1', 'PHASE2', 'PHASE3', 'CLOSED'])->default('PHASE1')->index();
            $table->timestamp('phase1_started_at')->nullable();
            $table->timestamp('phase2_started_at')->nullable();
            $table->timestamp('phase3_started_at')->nullable();

            $table->enum('status', ['in_progress', 'on_hold', 'completed', 'archived'])->default('in_progress');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['session_id', 'process_id'], 'uniq_amdec_session_process');
        });

        // ======================================================================
        // 🌱 SEED DONNÉES DE BASE
        // ======================================================================

        $this->seedGravities();
        $this->seedFrequencies();
        $this->seedDetectabilities();
        $this->seedStandards();
        $this->seedCartographies();
    }

    private function seedGravities(): void
    {
        DB::connection('tenant')->table('amdec_gravities')->insertOrIgnore([
            ['code' => 'G1', 'label' => 'Mineur', 'degree' => 1, 'color' => '#A4DE6C', 'sort' => 1, 'description' => 'Peu de conséquences', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'G2', 'label' => 'Faible', 'degree' => 2, 'color' => '#FFE135', 'sort' => 2, 'description' => 'Conséquences limitées', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'G3', 'label' => 'Modéré', 'degree' => 3, 'color' => '#FFA500', 'sort' => 3, 'description' => 'Conséquences moyennes', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'G4', 'label' => 'Élevé', 'degree' => 4, 'color' => '#FF6B6B', 'sort' => 4, 'description' => 'Conséquences importantes', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'G5', 'label' => 'Extrêmement élevé', 'degree' => 5, 'color' => '#C41E3A', 'sort' => 5, 'description' => 'Conséquences très graves', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedFrequencies(): void
    {
        DB::connection('tenant')->table('amdec_frequencies')->insertOrIgnore([
            ['code' => 'F1', 'label' => 'Rare', 'degree' => 1, 'color' => '#A4DE6C', 'sort' => 1, 'description' => 'Plus de 5 ans', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'F2', 'label' => 'Occasionnelle', 'degree' => 2, 'color' => '#FFE135', 'sort' => 2, 'description' => '6-12 mois', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'F3', 'label' => 'Fréquente', 'degree' => 3, 'color' => '#FFA500', 'sort' => 3, 'description' => 'Chaque mois', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'F4', 'label' => 'Régulière', 'degree' => 4, 'color' => '#FF6B6B', 'sort' => 4, 'description' => 'Chaque semaine', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'F5', 'label' => 'Inévitable', 'degree' => 5, 'color' => '#C41E3A', 'sort' => 5, 'description' => 'Chaque jour', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedDetectabilities(): void
    {
        DB::connection('tenant')->table('amdec_detectabilities')->insertOrIgnore([
            ['code' => 'D1', 'label' => 'Totale', 'degree' => 1, 'color' => '#A4DE6C', 'sort' => 1, 'appellation' => 'Totale', 'description' => 'Détection certaine', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'D2', 'label' => 'Bonne', 'degree' => 2, 'color' => '#FFE135', 'sort' => 2, 'appellation' => 'Bonne', 'description' => 'Détection très probable', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'D3', 'label' => 'Limitée', 'degree' => 3, 'color' => '#FFA500', 'sort' => 3, 'appellation' => 'Limitée', 'description' => 'Détection possible', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'D4', 'label' => 'Faible maîtrise', 'degree' => 4, 'color' => '#FF6B6B', 'sort' => 4, 'appellation' => 'Faible maîtrise', 'description' => 'Détection improbable', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'D5', 'label' => 'Non maîtrisée', 'degree' => 5, 'color' => '#C41E3A', 'sort' => 5, 'appellation' => 'Non maîtrisée', 'description' => 'Pas de détection', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedStandards(): void
    {
        DB::connection('tenant')->table('amdec_standards')->insertOrIgnore([
            ['name' => 'Vert', 'color' => '#A4DE6C', 'min_criticality' => 0, 'max_criticality' => 20, 'alert_label' => 'Vert', 'actions' => 'Pas d\'action requise', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bleu', 'color' => '#4A90E2', 'min_criticality' => 20.01, 'max_criticality' => 40, 'alert_label' => 'Bleu', 'actions' => 'Suivi recommandé', 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jaune', 'color' => '#FFE135', 'min_criticality' => 40.01, 'max_criticality' => 60, 'alert_label' => 'Jaune', 'actions' => 'Actions correctives', 'sort' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Orange', 'color' => '#FFA500', 'min_criticality' => 60.01, 'max_criticality' => 80, 'alert_label' => 'Orange', 'actions' => 'Actions urgentes', 'sort' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rouge', 'color' => '#C41E3A', 'min_criticality' => 80.01, 'max_criticality' => 100, 'alert_label' => 'Rouge', 'actions' => 'Intervention critique', 'sort' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedCartographies(): void
    {
        DB::connection('tenant')->table('amdec_cartographies')->insertOrIgnore([
            ['name' => 'SP1', 'gravity_degree' => 2, 'frequency_degree' => 3, 'criticality_score' => 6, 'color' => '#FFE135', 'description' => 'SP1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SP2', 'gravity_degree' => 1, 'frequency_degree' => 2, 'criticality_score' => 2, 'color' => '#A4DE6C', 'description' => 'SP2', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SP3', 'gravity_degree' => 2, 'frequency_degree' => 3, 'criticality_score' => 6, 'color' => '#FFE135', 'description' => 'SP3', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SP4', 'gravity_degree' => 5, 'frequency_degree' => 4, 'criticality_score' => 20, 'color' => '#FF6B6B', 'description' => 'SP4', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SP5', 'gravity_degree' => 4, 'frequency_degree' => 3, 'criticality_score' => 12, 'color' => '#FFA500', 'description' => 'SP5', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SP6', 'gravity_degree' => 3, 'frequency_degree' => 2, 'criticality_score' => 6, 'color' => '#FFE135', 'description' => 'SP6', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SP7', 'gravity_degree' => 1, 'frequency_degree' => 3, 'criticality_score' => 3, 'color' => '#A4DE6C', 'description' => 'SP7', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('amdec_phase_tracking');
        Schema::connection('tenant')->dropIfExists('amdec_records');
        Schema::connection('tenant')->dropIfExists('amdec_activities');
        Schema::connection('tenant')->dropIfExists('amdec_cartographies');
        Schema::connection('tenant')->dropIfExists('amdec_standards');
        Schema::connection('tenant')->dropIfExists('amdec_detectabilities');
        Schema::connection('tenant')->dropIfExists('amdec_frequencies');
        Schema::connection('tenant')->dropIfExists('amdec_gravities');
    }
};