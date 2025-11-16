<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
     Schema::create('module_permission', function (Blueprint $t) {
    $t->id();
      $t->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
      $t->foreignId('permission_id')->constrained('permissions');
      $t->unique(['module_id','permission_id']);
    });
  }
  public function down(): void { Schema::connection('mysql')->dropIfExists('module_permission'); }
};
