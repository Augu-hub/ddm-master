<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('roles', function (Blueprint $t) {
      $t->bigIncrements('id');
      $t->unsignedBigInteger('tenant_id'); // scope
      $t->string('name');                  // ex: owner, admin, manager, lecteur
      $t->string('guard_name')->default('web');
      $t->timestamps();

      $t->unique(['tenant_id','name']);
      $t->index('tenant_id');
    });
  }
  public function down(): void { Schema::dropIfExists('roles'); }
};
