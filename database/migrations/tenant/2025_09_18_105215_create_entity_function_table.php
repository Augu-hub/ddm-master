<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('functions', function (Blueprint $t) {
            $t->id();

            // plus de project_id ici
            $t->string('name');
            $t->string('character')->nullable(); // Caract. Fonction

            // auto-référence OK
            $t->foreignId('parent_id')->nullable()
              ->constrained('functions')->nullOnDelete();

            $t->timestamps();

            $t->index('parent_id');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('functions');
    }
};
