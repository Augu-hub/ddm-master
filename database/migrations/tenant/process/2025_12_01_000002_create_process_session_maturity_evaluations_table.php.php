<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->create('process_session_maturity_evaluations', function (Blueprint $t) {
            $t->id();

            // 🔹 Références
            $t->unsignedBigInteger('session_id');
            $t->unsignedBigInteger('process_id');
            $t->string('criterion_code');  // CEM1, CEM2, ..., CEM12

            // 🔹 Score (1-5)
            $t->integer('level_score');

            // 🔹 Qui et quand
            $t->unsignedBigInteger('evaluated_by')->nullable();
            $t->timestamp('evaluated_at')->nullable();

            // 🔹 Timestamps
            $t->timestamps();

            // 🔹 Contrainte unique
            $t->unique(
                ['session_id', 'process_id', 'criterion_code'],
                'psm_eval_unique'
            );

            // 🔹 Index
            $t->index(['session_id', 'process_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('process_session_maturity_evaluations');
    }
};