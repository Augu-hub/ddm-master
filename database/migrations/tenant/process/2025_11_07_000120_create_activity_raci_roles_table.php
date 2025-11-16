<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void {
        if (!Schema::connection($this->connection)->hasTable('activity_raci_roles')) {
            Schema::connection($this->connection)->create('activity_raci_roles', function (Blueprint $t) {
                $t->id();
                $t->string('code', 10)->unique();
                $t->string('label', 100);
                $t->string('description', 255)->nullable();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection($this->connection)->dropIfExists('activity_raci_roles');
    }
};
