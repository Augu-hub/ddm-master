<?php
// database/migrations/YYYY_MM_DD_HHMMSS_create_competency_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ====== CATÉGORIES DE COMPÉTENCES ======
        Schema::create('competency_categories', function (Blueprint $table) {
            $table->id();
            
            $table->string('code')->unique()->comment('Code unique (GEN, TEC, MGT)');
            $table->string('name')->comment('Nom de la catégorie');
            $table->text('description')->nullable()->comment('Description');
            $table->string('color')->nullable()->comment('Couleur pour l\'UI');
            $table->integer('order')->default(0)->comment('Ordre d\'affichage');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('status');
            $table->index('order');
        });

        // ====== COMPÉTENCES ======
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('competency_categories')
                ->cascadeOnDelete()
                ->comment('Catégorie de compétence');

            $table->string('code')->unique()->comment('Code unique (GEN01, TEC02, etc.)');
            $table->string('name')->comment('Nom de la compétence');
            $table->text('description')->nullable()->comment('Description détaillée');
            $table->integer('level_required')->default(1)->comment('Niveau minimum requis');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('category_id');
            $table->index('status');
        });

        // ====== COMPÉTENCES DES AUDITEURS (PIVOT) ======
        Schema::create('auditor_competencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('auditor_id')
                ->constrained('auditors')
                ->cascadeOnDelete()
                ->comment('Auditeur');

            $table->foreignId('competency_id')
                ->constrained('competencies')
                ->cascadeOnDelete()
                ->comment('Compétence');

            $table->integer('level')->default(1)->comment('Niveau de maîtrise (1-5)');
            $table->date('certified_date')->nullable()->comment('Date de certification');
            $table->boolean('is_primary')->default(false)->comment('Compétence principale');
            $table->text('notes')->nullable()->comment('Notes personnalisées');

            $table->timestamps();

            // Assurer qu\'un auditeur n\'a qu\'une seule fois chaque compétence
            $table->unique(['auditor_id', 'competency_id']);

            $table->index('auditor_id');
            $table->index('competency_id');
            $table->index('level');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditor_competencies');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('competency_categories');
    }
};