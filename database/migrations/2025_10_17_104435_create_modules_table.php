<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('modules', function (Blueprint $t) {
      $t->id();
      $t->foreignId('service_id')->constrained('services')->cascadeOnDelete();
      $t->string('code')->unique();               // ex: param.projects
      $t->string('name');
      $t->string('icon')->nullable();
      $t->string('route_prefix')->default('m');   // /m/{code}
      $t->string('entry_route_name')->nullable(); // ex: param.projects.home
      $t->string('route_web_file')->nullable();
      $t->string('route_api_file')->nullable();
      $t->unsignedInteger('sort')->default(0);
      $t->boolean('is_active')->default(true);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('modules'); }
};

