<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table de relation entre users et functions dans le tenant
     */
    public function up(): void
    {
        Schema::create('user_functions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('function_id');
            $table->unsignedBigInteger('entity_id')->nullable(); // Entité où la fonction s'applique
            
            // Métadonnées
            $table->boolean('is_primary')->default(false); // Fonction principale de l'utilisateur
            $table->string('role_label', 100)->nullable(); // Label du rôle (ex: "Responsable")
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            
            $table->timestamps();
            
            // Indexes et contraintes
            $table->unique(['user_id', 'function_id', 'entity_id'], 'uf_unique_per_entity');
            $table->index(['user_id', 'is_primary'], 'uf_user_primary_idx');
            $table->index(['function_id', 'entity_id'], 'uf_function_entity_idx');
            $table->index(['entity_id'], 'uf_entity_idx');
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('function_id')->references('id')->on('functions')->cascadeOnDelete();
            $table->foreign('entity_id')->references('id')->on('entities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_functions');
    }
};