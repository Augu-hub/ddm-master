<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('process_criticality_levels', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();   // LOW/MED/HIGH/CRIT
            $t->string('label', 100);
            $t->unsignedTinyInteger('rank');    // tri asc
             $t->unsignedTinyInteger('sort')->default(0); // ordre d’affichage (legacy/compat)
            $t->unsignedTinyInteger('weight')->default(0); // ordre d’affichage (legacy/compat)
          
            $t->string('color', 9)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('process_criticality_levels');
    }
};
