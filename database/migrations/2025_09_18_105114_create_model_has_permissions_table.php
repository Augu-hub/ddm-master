<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('model_has_permissions', function (Blueprint $t) {
      $t->unsignedBigInteger('permission_id');
      $t->unsignedBigInteger('tenant_id'); // scope
      $t->string('model_type');            // App\Models\User
      $t->unsignedBigInteger('model_id'); // users.id

      $t->primary(['permission_id','model_id','model_type','tenant_id'], 'mhp_primary');

      $t->index(['model_id','model_type'], 'mhp_model_idx');
      $t->index(['tenant_id','model_id','model_type'], 'mhp_tenant_model_idx');
    });
  }
  public function down(): void { Schema::dropIfExists('model_has_permissions'); }
};
