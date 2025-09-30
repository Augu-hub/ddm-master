<?php
// 2025_09_19_000003_create_activities_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('activities', function (Blueprint $t) {
      $t->id();
      $t->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
      $t->string('code')->unique();
      $t->string('name');
      $t->text('description')->nullable();
      $t->timestamps();
      $t->index('process_id');
    });
  }
  public function down(): void { Schema::dropIfExists('activities'); }
};
