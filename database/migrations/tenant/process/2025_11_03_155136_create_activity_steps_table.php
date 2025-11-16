<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('activity_steps', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('activity_id');
            $t->unsignedSmallInteger('position')->default(1);
            $t->string('title', 150);
            $t->text('description')->nullable();
            $t->unsignedBigInteger('function_id')->nullable(); // qui porte l’étape
            $t->timestamps();

            $t->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
            $t->foreign('function_id')->references('id')->on('functions')->nullOnDelete();
            $t->index(['activity_id','position']);
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('activity_steps');
    }
};
