<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_elements', function (Blueprint $table) {
            $table->id();

            // Relation avec le contrat
            $table->foreignId('contract_id')
                  ->constrained('contracts')
                  ->cascadeOnDelete();

            // type: input / output / resource
            $table->string('type', 20);

            // Libellé de l’élément
            $table->string('label');

            // Assignment user
            $table->foreignId('assigned_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Document attaché
            $table->string('file_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_elements');
    }
};
