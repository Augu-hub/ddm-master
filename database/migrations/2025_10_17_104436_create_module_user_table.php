<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('module_user', function (Blueprint $t) {
      $t->id();
      $t->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
      $t->foreignId('user_id')->constrained('users');
      $t->unique(['module_id','user_id']);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('module_user'); }
};

