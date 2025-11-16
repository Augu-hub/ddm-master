<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('services', function (Blueprint $t) {
      $t->id();
      $t->string('code')->unique();
      $t->string('name');
      $t->string('icon')->nullable();
      $t->text('description')->nullable();
      $t->string('base_path')->nullable();
      $t->boolean('is_active')->default(true);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('services'); }
};
