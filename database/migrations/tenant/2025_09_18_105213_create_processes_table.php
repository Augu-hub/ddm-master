<?php
// 2025_09_19_000002_create_processes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('processes', function (Blueprint $t) {
      $t->id();
      $t->foreignId('macro_process_id')->constrained('macro_processes')->cascadeOnDelete();
      $t->string('code')->unique();
      $t->string('name');
      $t->timestamps();
      $t->index('macro_process_id');
    });
  }
  public function down(): void { Schema::dropIfExists('processes'); }
};
