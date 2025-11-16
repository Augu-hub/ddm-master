<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->create('process_criticality_norms', function (Blueprint $t) {
            $t->id();

            // Pourcentage borne basse et haute (0 à 100)
            $t->unsignedTinyInteger('min_percent')->default(0);
            $t->unsignedTinyInteger('max_percent')->default(100);

            // Appellation et couleur
            $t->string('color', 30); // ex: vert, bleu, jaune, orange, rouge
            $t->string('alert_label', 100); // ex: "Niveau Vert"
            $t->string('alert_level', 100)->nullable(); // ex: "Faible", "Modéré", etc.

            // Description et actions recommandées
            $t->text('actions')->nullable();

            // Utilisateur ayant modifié la norme
            $t->unsignedBigInteger('user_id')->nullable()->index();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('process_criticality_norms');
    }
};
