<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('role_has_permissions', function (Blueprint $t) {
      $t->unsignedBigInteger('permission_id');
      $t->unsignedBigInteger('role_id');
      $t->unsignedBigInteger('tenant_id'); // scope
      $t->primary(['permission_id','role_id','tenant_id'], 'rhp_primary');

      $t->index('tenant_id');
      $t->index(['role_id','tenant_id'], 'rhp_role_tenant_idx');
      $t->index(['permission_id','tenant_id'], 'rhp_perm_tenant_idx');
    });
  }
  public function down(): void { Schema::dropIfExists('role_has_permissions'); }
};
