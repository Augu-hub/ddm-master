<?php
// 2025_09_19_000010_create_process_inputs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('process_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('process_inputs');
    }
};
