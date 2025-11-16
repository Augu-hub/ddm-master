<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('activity_controls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('activity_id');
            $t->enum('control_type', ['preventive','detective','corrective'])->default('preventive');
            $t->string('description', 300);
            $t->string('frequency', 60)->nullable();
            $t->string('evidence', 150)->nullable();
            $t->unsignedBigInteger('function_id')->nullable(); // responsable du contrôle
            $t->timestamps();

            $t->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
            $t->foreign('function_id')->references('id')->on('functions')->nullOnDelete();
            $t->index(['activity_id','control_type']);
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('activity_controls');
    }
};
