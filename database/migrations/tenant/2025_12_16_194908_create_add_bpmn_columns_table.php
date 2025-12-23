<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ajouter la colonne bpmn_xml à la table processes
        Schema::table('processes', function (Blueprint $table) {
            if (!Schema::hasColumn('processes', 'bpmn_xml')) {
                $table->longText('bpmn_xml')->nullable()->after('name');
            }
        });

        // 2. Créer la table task_activity_links
        Schema::create('task_activity_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')
                  ->constrained('processes')
                  ->onDelete('cascade');

            // Identifiants de la tâche BPMN
            $table->string('task_id')->nullable();           // ID volatile généré par bpmn-js
            $table->string('task_name');                     // Clé stable (nom ou code activité)

            // Lien vers l'activité
            $table->foreignId('activity_id')
                  ->constrained('activites')
                  ->onDelete('cascade');

            // Cache pour affichage rapide et résilience
            $table->string('activity_name');
            $table->string('activity_code');

            // Propriétés visuelles
            $table->string('task_color', 7)->default('#3498DB'); // #RRGGBB

            $table->timestamps();

            // Contraintes d'unicité et index
            $table->unique(['process_id', 'task_name']);
            $table->index('process_id');
            $table->index('activity_id');
            $table->index('task_name');
        });

        // 3. Créer la table sequence_flows
        Schema::create('sequence_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')
                  ->constrained('processes')
                  ->onDelete('cascade');

            // Tâche source
            $table->string('source_task_id')->nullable();
            $table->string('source_task_name');

            // Tâche cible
            $table->string('target_task_id')->nullable();
            $table->string('target_task_name');

            // Propriétés du flow
            $table->string('sequence_id')->nullable();
            $table->string('sequence_name')->nullable();
            $table->text('sequence_condition')->nullable();

            $table->timestamps();

            // Une seule connexion dirigée entre deux tâches par processus
            $table->unique(['process_id', 'source_task_name', 'target_task_name']);

            // Index utiles
            $table->index('process_id');
            $table->index('source_task_name');
            $table->index('target_task_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_flows');
        Schema::dropIfExists('task_activity_links');

        Schema::table('processes', function (Blueprint $table) {
            if (Schema::hasColumn('processes', 'bpmn_xml')) {
                $table->dropColumn('bpmn_xml');
            }
        });
    }
};