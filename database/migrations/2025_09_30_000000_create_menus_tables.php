<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('label');
            $t->string('icon')->nullable();
            $t->string('url')->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('menus');
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('is_title')->default(false);
            $t->boolean('is_divider')->default(false);
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
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_permission');
        Schema::dropIfExists('menus');
    }
};
