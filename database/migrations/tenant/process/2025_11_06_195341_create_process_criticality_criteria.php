<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('process_criticality_criteria', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();   // ex: IMPACT_CLIENT
            $t->string('label', 150);
            $t->unsignedTinyInteger('weight')->default(1); // poids (1..5)
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('process_criticality_criteria');
    }
};

