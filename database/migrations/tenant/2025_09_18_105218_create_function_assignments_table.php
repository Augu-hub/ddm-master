<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('function_assignments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('entity_id');
            $t->unsignedBigInteger('function_id');
            $t->timestamps();

            $t->unique(['entity_id','function_id'], 'fa_unique');
            $t->index('entity_id');
            $t->index('function_id');
        });
    }
    public function down(): void {
        Schema::dropIfExists('function_assignments');
    }
};
