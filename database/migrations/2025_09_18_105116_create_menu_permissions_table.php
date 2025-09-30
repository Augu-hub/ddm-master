<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('menu_permissions', function (Blueprint $t) {
      $t->id();
      $t->string('menu_key');        // ex: project, entity, process, ...
      $t->string('permission');      // ex: param.projects.view
      $t->string('action')->nullable(); // view|create|edit|delete (optionnel)
      $t->timestamps();

      $t->unique(['menu_key','permission','action'], 'menu_perm_unique');
      $t->index('menu_key');
    });
  }
  public function down(): void { Schema::dropIfExists('menu_permissions'); }
};
