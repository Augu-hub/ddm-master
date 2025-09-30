<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('tenants', function (Blueprint $t) {
      $t->id();
      $t->string('name');
      $t->string('code')->unique();
      $t->string('db_host')->default('127.0.0.1');
      $t->string('db_name');
      $t->string('db_username');
      $t->string('db_password');
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('tenants'); }
};
