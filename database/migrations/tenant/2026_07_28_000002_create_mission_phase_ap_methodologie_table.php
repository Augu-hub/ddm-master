<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AP — Sous-phase « Méthodologie de vérification ».
 * Table tenant : une fiche par assignment. 5 volets (maquette) articulés
 * autour des « lignes directrices d'enquête » (numérotées) :
 *   - lignes   : lignes directrices d'enquête (objectif/question → résultat)
 *   - criteres : critères d'audit et sous-critères retenus
 *   - sources  : sources de l'evidence (par ligne directrice)
 *   - collecte : méthode de collecte des données (par ligne directrice)
 *   - analyse  : méthodes d'analyse des données (par ligne directrice)
 *
 * Rejouée automatiquement sur chaque base tenant via :
 *   php artisan tenants:setup
 *   php artisan migrate --database=tenant --path=database/migrations/tenant
 *
 * Idempotente : ne crée la table que si elle n'existe pas déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mission_phase_ap_methodologie')) {
            return;
        }

        Schema::create('mission_phase_ap_methodologie', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assignment_id')->comment('FK mission_phase_assignments.id');
            $table->unsignedBigInteger('mission_id')->comment('FK mission_programmation.id');
            $table->string('code', 60)->nullable()->comment('Auto MV-<mission>-NNN');

            // 1) Lignes directrices d'enquête (N° = index) :
            //    [{objectif_question, ligne_directrice, resultat_attendu}]
            $table->longText('lignes')->nullable()->default('[]');
            // 2) Critères d'audit (sous-critères) :
            //    [{critere_principal, sous_critere, source_critere, libelle_retenu}]
            $table->longText('criteres')->nullable()->default('[]');
            // 3) Sources de l'evidence (par ligne directrice) :
            //    [{ligne_num, source_preuve, nature_preuve, modalites_obtention}]
            $table->longText('sources')->nullable()->default('[]');
            // 4) Méthode de collecte (par ligne directrice) :
            //    [{ligne_num, methode_collecte, modalites_pratiques}]
            $table->longText('collecte')->nullable()->default('[]');
            // 5) Méthodes d'analyse (par ligne directrice) :
            //    [{ligne_num, methode_analyse, donnees_concernees, resultat_analyse}]
            $table->longText('analyse')->nullable()->default('[]');

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

            $table->unique('assignment_id', 'uq_apmv_assignment');
            $table->index('mission_id', 'idx_apmv_mission');

            $table->foreign('assignment_id', 'fk_apmv_assignment')
                ->references('id')->on('mission_phase_assignments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_phase_ap_methodologie');
    }
};
