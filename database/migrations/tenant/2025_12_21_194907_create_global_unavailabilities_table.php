<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up(): void
    {
        // ====================================================================
        // TABLE 1: unavailability_types
        // ====================================================================
        Schema::connection('tenant')->create('unavailability_types', function (Blueprint $table) {
            $table->id();
            
            $table->enum('category', ['global', 'auditor'])->index();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->longText('description')->nullable();
            
            $table->string('icon', 10)->default('📝');
            $table->string('color', 7)->default('#667eea');
            
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_custom')->default(false)->index();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['category', 'is_active']);
        });

        // ====================================================================
        // TABLE 2: global_unavailabilities
        // ====================================================================
        Schema::connection('tenant')->create('global_unavailabilities', function (Blueprint $table) {
            $table->id();
            
            $table->string('name', 255);
            $table->string('reason', 255)->nullable();
            $table->string('type', 50)->default('holiday');
            $table->longText('description')->nullable();
            
            $table->date('date_start')->index();
            $table->date('date_end')->index();
            
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['date_start', 'date_end']);
            $table->index(['type', 'is_active']);
            
            // Clé étrangère vers unavailability_types
            $table->foreign('type')
                ->references('code')
                ->on('unavailability_types')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        // ====================================================================
        // TABLE 3: auditor_unavailabilities
        // ====================================================================
        Schema::connection('tenant')->create('auditor_unavailabilities', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('auditor_id')
                ->constrained('auditors')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->string('reason', 255)->nullable();
            $table->string('type', 50)->default('leave');
            $table->longText('description')->nullable();
            $table->longText('notes')->nullable();
            
            $table->date('date_start')->index();
            $table->date('date_end')->index();
            
            $table->boolean('is_approved')->default(false)->index();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('auditors')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('auditor_id');
            $table->index(['auditor_id', 'is_approved']);
            $table->index(['date_start', 'date_end']);
            $table->index(['type', 'is_approved']);
            
            // Clé étrangère vers unavailability_types
            $table->foreign('type')
                ->references('code')
                ->on('unavailability_types')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('auditor_unavailabilities');
        Schema::connection('tenant')->dropIfExists('global_unavailabilities');
        Schema::connection('tenant')->dropIfExists('unavailability_types');
    }
};