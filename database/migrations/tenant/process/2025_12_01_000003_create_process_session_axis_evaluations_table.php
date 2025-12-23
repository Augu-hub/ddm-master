<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->create('process_session_axis_evaluations', function (Blueprint $t) {
            $t->id();

            // 🔹 Références
            $t->unsignedBigInteger('session_id');
            $t->unsignedBigInteger('process_id');

            // 🔹 Scores des 4 axes (null si non évalué)
            $t->float('maturity_score')->nullable();        // Moyenne des 12 critères de maturité
            $t->float('motricity_score')->nullable();       // Score motricité (1-5)
            $t->float('transversality_score')->nullable();  // Score transversalité (1-5)
            $t->float('strategic_score')->nullable();       // Score stratégique (1-5)

            // 🔹 Score critique (moyenne de tous les axes non-null)
            $t->float('criticality_score')->nullable();

            // 🔹 Timestamps
            $t->timestamps();

            // 🔹 Contrainte unique: une évaluation par processus par session
            $t->unique(['session_id', 'process_id']);

            // 🔹 Index
            $t->index(['session_id']);
            $t->index(['process_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('process_session_axis_evaluations');
    }
};