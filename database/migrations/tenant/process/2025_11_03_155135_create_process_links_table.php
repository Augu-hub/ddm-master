<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('process_links', function (Blueprint $t) {
      $t->id();
      $t->foreignId('parent_process_id')->constrained('processes')->cascadeOnDelete();
      $t->foreignId('child_process_id')->constrained('processes')->cascadeOnDelete();
      $t->enum('type', ['UPSTREAM','DOWNSTREAM','SUPPORT'])->default('UPSTREAM');
      $t->json('flow')->nullable();
      $t->timestamps();
      $t->unique(['parent_process_id','child_process_id','type']);
    });
  }
  public function down(): void { Schema::dropIfExists('process_links'); }
};
