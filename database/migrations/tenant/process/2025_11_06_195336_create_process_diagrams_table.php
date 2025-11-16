<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('process_diagrams', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('process_id');
            $t->string('title', 150)->nullable();
            $t->string('version', 30)->nullable();
            $t->longText('diagram_json')->nullable(); // BPMN/edges/nodes…
            $t->timestamps();

            $t->foreign('process_id')->references('id')->on('processes')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('process_diagrams');
    }
};
