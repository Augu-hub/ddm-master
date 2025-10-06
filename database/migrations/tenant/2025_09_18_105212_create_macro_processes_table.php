<?php
// 2025_09_19_000001_create_macro_processes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('macro_processes', function (Blueprint $t) {
      $t->id();
      $t->string('code')->unique();
      $t->string('name');
      $t->string('character')->nullable();
      $t->string('designation')->nullable();
      $t->enum('kind', ['Direction','Réalisation','Support']);
      $t->timestamps();

      $t->index('kind');
    });
  }

  public function down(): void {
    Schema::dropIfExists('macro_processes');
  }
};
