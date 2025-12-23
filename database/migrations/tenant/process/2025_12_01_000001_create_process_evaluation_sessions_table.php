<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->create('process_evaluation_sessions', function (Blueprint $t) {
            $t->id();

            // 🔹 Contexte
            $t->unsignedBigInteger('entity_id');
            $t->unsignedBigInteger('function_id');
            $t->unsignedBigInteger('user_id')->nullable();

            // 🔹 Propriétés
            $t->string('name');
            $t->string('color')->default('#667eea');

            // 🔹 Statut
            $t->enum('status', ['open', 'closed', 'archived'])->default('open');

            // 🔹 Session active
            $t->boolean('is_active')->default(false);

            $t->timestamps();

            // 🔹 Index — noms courts pour éviter erreur 1059
            $t->index(['entity_id', 'function_id', 'status'], 'pes_entity_fn_status');
            $t->index(['entity_id', 'function_id', 'is_active'], 'pes_entity_fn_active');
            $t->index('created_at', 'pes_created_at');
            $t->index('user_id', 'pes_user');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('process_evaluation_sessions');
    }
};
