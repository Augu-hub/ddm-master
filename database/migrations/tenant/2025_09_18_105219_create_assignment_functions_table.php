<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('assignment_functions', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('assignment_id');
      $t->unsignedBigInteger('function_id');
      $t->string('role_label')->nullable(); // Propriétaire, Pilote, Contributeur...
      $t->timestamps();

      $t->unique(['assignment_id','function_id'], 'af_unique');
      $t->index('assignment_id');
      $t->index('function_id');
    });
  }
  public function down(): void { Schema::dropIfExists('assignment_functions'); }
};
