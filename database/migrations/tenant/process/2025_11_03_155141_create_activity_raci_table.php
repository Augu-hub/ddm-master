<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        Schema::connection('tenant')->create('activity_raci', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('activity_id');
            $t->unsignedBigInteger('function_id');
            $t->enum('role', ['R','A','C','I']);
            $t->timestamps();

            $t->unique(['activity_id','function_id','role']);
            $t->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
            $t->foreign('function_id')->references('id')->on('functions')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('activity_raci');
    }
};
