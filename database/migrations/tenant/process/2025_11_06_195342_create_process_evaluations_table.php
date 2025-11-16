<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('process_evaluations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('process_id');
            $t->enum('model', ['maturity','criticality','amdec','other']);
            $t->unsignedBigInteger('scale_or_level_id')->nullable(); // FK vers scale/level
            $t->unsignedTinyInteger('score')->nullable();
            $t->json('details')->nullable(); // ex: décompo par critères
            $t->date('evaluated_at')->nullable();
            $t->timestamps();
            $t->index(['process_id','model']);

            $t->foreign('process_id')->references('id')->on('processes')->cascadeOnDelete();
            // NB: pas de contrainte FK stricte ici car 2 référentiels possibles; on laisse nullable
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('process_evaluations');
    }
};
