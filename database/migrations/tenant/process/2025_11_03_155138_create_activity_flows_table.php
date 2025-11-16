<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('activity_flows', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('from_step_id');
            $t->unsignedBigInteger('to_step_id');
            $t->string('condition', 200)->nullable();
            $t->timestamps();

            $t->foreign('from_step_id')->references('id')->on('activity_steps')->cascadeOnDelete();
            $t->foreign('to_step_id')->references('id')->on('activity_steps')->cascadeOnDelete();
            $t->index(['from_step_id','to_step_id']);
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('activity_flows');
    }
};
