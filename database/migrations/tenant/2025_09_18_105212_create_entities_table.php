<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('entities', function (Blueprint $t) {
      $t->id();
      $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
      $t->string('name');
      $t->text('description')->nullable();
      $t->unsignedTinyInteger('level')->default(0); // 0=entité, 1=sous-entité
      $t->foreignId('parent_id')->nullable()->constrained('entities')->nullOnDelete();
      $t->string('code_base')->nullable();
      $t->string('logo')->nullable();
      $t->string('phone')->nullable();
      $t->string('email')->nullable();
      $t->string('leader')->nullable();
      $t->text('address')->nullable();
      $t->timestamps();

      $t->index(['project_id','level']);
      $t->index('parent_id');
    });
  }
  public function down(): void { Schema::dropIfExists('entities'); }
};
