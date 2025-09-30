<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('tenant_user', function (Blueprint $t) {
      $t->unsignedBigInteger('tenant_id');
      $t->unsignedBigInteger('user_id');
      $t->string('role_hint')->nullable();
      $t->timestamps();
      $t->primary(['tenant_id','user_id']);
      $t->index('tenant_id');
      $t->index('user_id');
    });
  }
  public function down(): void { Schema::dropIfExists('tenant_user'); }
};
