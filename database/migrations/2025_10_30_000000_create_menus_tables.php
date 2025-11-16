<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('menus', function (Blueprint $t) {
      $t->id();
      $t->string('key')->unique();                // ex: param.projects.sidebar
      $t->string('label');                        // libellé visible
      $t->string('type')->default('item');        // item|title|divider
      $t->string('icon')->nullable();             // icône de l’item
      $t->string('url')->nullable();              // si lien direct
      $t->string('route_name')->nullable();       // si on pointe une route nommée
      $t->string('target')->nullable();           // _self|_blank…

      // Arborescence
      $t->foreignId('parent_id')->nullable()->constrained('menus')->nullOnDelete();
      $t->unsignedInteger('sort')->default(0);

      // Rattachements
      $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
      $t->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();

      // Affichage & meta
      $t->boolean('visible')->default(true);
      $t->json('badge_json')->nullable();
      $t->json('tooltip_json')->nullable();
      $t->json('meta_json')->nullable();
      $t->timestamps();
    });

    Schema::create('menu_permission', function (Blueprint $t) {
      $t->id();
      $t->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
      $t->foreignId('permission_id')->constrained('permissions');
      $t->unique(['menu_id','permission_id']);
    });

    // (Optionnel) rattacher des menus explicitement à des utilisateurs
    Schema::create('menu_user', function (Blueprint $t) {
      $t->id();
      $t->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
      $t->foreignId('user_id')->constrained('users');
      $t->unique(['menu_id','user_id']);
      $t->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('menu_user');
    Schema::dropIfExists('menu_permission');
    Schema::dropIfExists('menus');
  }
};

