<?php

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
        // 1. Table des catégories de compétences (parent)
        Schema::create('competency_categories', function (Blueprint $table) {
            $table->id();

            $table->string('code', 255)->unique()->comment('Code unique (GEN, TEC, MGT)');
            $table->string('name', 255)->comment('Nom de la catégorie');
            $table->longText('description')->nullable()->comment('Description');
            $table->string('color', 255)->nullable()->comment('Couleur pour l\'UI (ex: #667eea)');
            $table->integer('order')->default(0)->comment('Ordre d\'affichage');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Indexes supplémentaires
            $table->index('code', 'competency_categories_code_index');
            $table->index('status', 'competency_categories_status_index');
            $table->index('order', 'competency_categories_order_index');
        });

        // 2. Table des compétences (enfant)
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                  ->constrained('competency_categories')
                  ->onDelete('cascade')
                  ->comment('Catégorie de compétence');

            $table->string('code', 255)->unique()->comment('Code unique (GEN01, TEC02, etc.)');
            $table->string('name', 255)->comment('Nom de la compétence');
            $table->longText('description')->nullable()->comment('Description détaillée');
            $table->integer('level_required')->default(1)->comment('Niveau minimum requis');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code', 'competencies_code_index');
            $table->index('category_id', 'competencies_category_id_index');
            $table->index('status', 'competencies_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprime d'abord la table enfant (à cause de la FK)
        Schema::dropIfExists('competencies');
        
        // Puis la table parent
        Schema::dropIfExists('competency_categories');
    }
};