<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('assignments', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('entity_id');
      $t->enum('mpa_type', ['macro','process','activity']);
      $t->unsignedBigInteger('mpa_id');
      $t->timestamps();

      $t->index(['mpa_type','mpa_id']);
      $t->unique(['entity_id','mpa_type','mpa_id'], 'assignment_unique');
      $t->index('entity_id');
    });
  }
  public function down(): void { Schema::dropIfExists('assignments'); }
};
