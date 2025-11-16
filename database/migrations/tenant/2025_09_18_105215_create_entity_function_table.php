<?php
// database/migrations/tenant/2025_11_05_000100_create_functions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('functions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('character')->nullable();

            // Rattachement (user ID vient de la base "master", pas de FK cross-DB)
            $t->unsignedBigInteger('user_id')->nullable()->index();

            // Auto-référence (dans le tenant)
            $t->foreignId('parent_id')->nullable()
              ->constrained('functions')->nullOnDelete();

            $t->string('avatar_path')->nullable();

            $t->timestamps();
            $t->index('parent_id');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('functions');
    }
};
