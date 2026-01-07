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
        Schema::connection('tenant')->create('process_contract_notifications', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('user_id');
            
            // Données de notification
            $table->string('output_label')->nullable();
            $table->string('function_name')->nullable();
            $table->text('message')->nullable();
            $table->longText('expectations')->nullable();
            $table->string('process_name')->nullable();
            $table->string('process_code')->nullable();
            
            // Statut lecture
            $table->boolean('read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('contract_id');
            $table->index('read');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('process_contract_notifications');
    }
};