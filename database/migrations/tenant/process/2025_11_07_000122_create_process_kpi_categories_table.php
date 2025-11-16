<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void {
        if (!Schema::connection($this->connection)->hasTable('process_kpi_categories')) {
            Schema::connection($this->connection)->create('process_kpi_categories', function (Blueprint $t) {
                $t->id();
                $t->string('code', 10)->unique();
                $t->string('label', 100);
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection($this->connection)->dropIfExists('process_kpi_categories');
    }
};
