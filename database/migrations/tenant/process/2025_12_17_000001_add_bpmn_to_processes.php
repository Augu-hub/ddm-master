<?php

// 2025_12_17_000001_add_bpmn_to_processes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('processes', function (Blueprint $table) {
            $table->longText('bpmn_xml')->nullable(); // Stocke le XML BPMN complet
        });
    }

    public function down(): void {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn('bpmn_xml');
        });
    }
};