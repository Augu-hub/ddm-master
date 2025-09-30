<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::connection('tenant')->create('functions', function (Blueprint $t) {
      $t->id();
      $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
      $t->string('name');
      $t->string('character')->nullable();                 // Caract. Fonction
      $t->foreignId('parent_id')->nullable()
        ->constrained('functions')->nullOnDelete();        // Liaison (parent)
      $t->timestamps();

      $t->index(['project_id','parent_id']);
    });
  }

  public function down(): void {
    Schema::connection('tenant')->dropIfExists('functions');
  }
};
