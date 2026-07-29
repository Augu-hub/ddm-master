<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AP — Sous-phase 1.4 « Champ d'action » (Planification).
 * Table tenant : une fiche par assignment. 7 volets à lignes variables
 * stockés en JSON (approches, objectifs, questions, étendue Qui/Quoi/Quand/Où,
 * limites, conclusions, pertinence du mandat).
 *
 * Rejouée automatiquement sur chaque base tenant via :
 *   php artisan tenants:setup            (toutes les bases)
 *   php artisan migrate --database=tenant --path=database/migrations/tenant
 *
 * Idempotente : ne crée la table que si elle n'existe pas déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mission_phase_ap_champ_action')) {
            return;
        }

        Schema::create('mission_phase_ap_champ_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assignment_id')->comment('FK mission_phase_assignments.id');
            $table->unsignedBigInteger('mission_id')->comment('FK mission_programmation.id');
            $table->string('code', 60)->nullable()->comment('Auto CA-<mission>-NNN');

            // 1) Approches d'audit — [{critere_code, critere_nature, approche_code, approche_libelle, justification}]
            $table->longText('approches')->nullable()->default('[]');
            // 2) Objectifs — [{objectif, approche_code, critere_code, sous_criteres_source}]
            $table->longText('objectifs')->nullable()->default('[]');
            // 3) Question principale — [{objectif_num, question_principale, sous_questions}]
            $table->longText('questions')->nullable()->default('[]');
            // 4) Étendue Qui/Quoi/Quand/Où — [{dimension_code, dimension_libelle, questions_cles, contenu, justification}]
            $table->longText('etendue')->nullable()->default('[]');
            // 5) Limites — [{limite, raison}]
            $table->longText('limites')->nullable()->default('[]');
            // 6) Conclusions potentielles — [{conclusion, lien_objectif}]
            $table->longText('conclusions')->nullable()->default('[]');
            // 7) Pertinence du mandat — [{question, reponse, commentaire}]
            $table->longText('pertinence')->nullable()->default('[]');

            $table->text('synthese')->nullable();
            $table->string('fait_par', 150)->nullable();
            $table->string('revue_par', 150)->nullable();

            $table->string('validation_status', 20)->default('draft')->comment('draft | in_review | validated');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->text('validation_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('assignment_id', 'uq_apca_assignment');
            $table->index('mission_id', 'idx_apca_mission');

            $table->foreign('assignment_id', 'fk_apca_assignment')
                ->references('id')->on('mission_phase_assignments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_phase_ap_champ_action');
    }
};
