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
        Schema::create('mission_request_shares', function (Blueprint $table) {
            $table->id();
            
            // ✅ Lien unique shareable
            $table->string('share_link')->unique();
            
            // ✅ Qui a généré/partagé le lien
            $table->unsignedBigInteger('shared_by_id');
            $table->foreign('shared_by_id')->references('id')->on('users')->onDelete('cascade');
            
            // ✅ La demande créée via ce lien (nullable)
            $table->unsignedBigInteger('mission_request_id')->nullable();
            $table->foreign('mission_request_id')->references('id')->on('audit_mission_requests')->onDelete('set null');
            
            // ✅ Métadonnées
            $table->string('status')->default('active'); // active, used, expired
            $table->timestamp('shared_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            // ✅ Index
            $table->index('share_link');
            $table->index('shared_by_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_request_shares');
    }
};