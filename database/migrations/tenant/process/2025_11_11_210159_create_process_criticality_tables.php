<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        /* ════════════════════════════════════════════════
         * 1️⃣ TABLE DES CRITÈRES DE MATURITÉ
         * ════════════════════════════════════════════════ */
        Schema::create('process_maturity_scales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Niveaux par critère
        Schema::create('process_maturity_scale_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scale_id')->constrained('process_maturity_scales')->cascadeOnDelete();
            $table->integer('level_score');
            $table->string('level_label');
            $table->text('level_description')->nullable();
            $table->timestamps();
        });

        /* ════════════════════════════════════════════════
         * 2️⃣ TABLE DES ÉCHELLES DE MOTRICITÉ
         * ════════════════════════════════════════════════ */
        Schema::create('process_motricity_scales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('process_motricity_scale_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scale_id')->constrained('process_motricity_scales')->cascadeOnDelete();
            $table->integer('level_score');
            $table->string('level_label');
            $table->text('level_description')->nullable();
            $table->timestamps();
        });

        /* ════════════════════════════════════════════════
         * 3️⃣ TABLE DES ÉCHELLES DE TRANSVERSALITÉ
         * ════════════════════════════════════════════════ */
        Schema::create('process_transversality_scales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('process_transversality_scale_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scale_id')->constrained('process_transversality_scales')->cascadeOnDelete();
            $table->integer('level_score');
            $table->string('level_label');
            $table->text('level_description')->nullable();
            $table->timestamps();
        });

        /* ════════════════════════════════════════════════
         * 4️⃣ TABLE DES ÉCHELLES DE POIDS STRATÉGIQUE
         * ════════════════════════════════════════════════ */
        Schema::create('process_strategic_weight_scales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('process_strategic_weight_scale_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scale_id')->constrained('process_strategic_weight_scales')->cascadeOnDelete();
            $table->integer('level_score');
            $table->string('level_label');
            $table->text('level_description')->nullable();
            $table->timestamps();
        });

        /* ════════════════════════════════════════════════
         * 5️⃣ TABLE DES NORMES DE CRITICITÉ
       

        /* ════════════════════════════════════════════════
         * 6️⃣ TABLE D’ÉVALUATION GLOBALE DES PROCESSUS
         * ════════════════════════════════════════════════ */
        Schema::create('process_criticality_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->integer('maturity_score')->nullable();
            $table->integer('motricity_score')->nullable();
            $table->integer('transversality_score')->nullable();
            $table->integer('strategic_score')->nullable();
            $table->float('criticality_score')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_criticality_evaluations');
        Schema::dropIfExists('process_criticality_norms');
        Schema::dropIfExists('process_strategic_weight_scale_levels');
        Schema::dropIfExists('process_strategic_weight_scales');
        Schema::dropIfExists('process_transversality_scale_levels');
        Schema::dropIfExists('process_transversality_scales');
        Schema::dropIfExists('process_motricity_scale_levels');
        Schema::dropIfExists('process_motricity_scales');
        Schema::dropIfExists('process_maturity_scale_levels');
        Schema::dropIfExists('process_maturity_scales');
    }
};
