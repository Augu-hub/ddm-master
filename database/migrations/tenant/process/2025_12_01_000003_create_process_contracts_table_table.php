<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('process_contracts', function (Blueprint $table) {
            $table->id();

            // 🔗 Relation Processus
            $table->foreignId('process_id')
                ->constrained('processes')
                ->cascadeOnDelete();

            // 🏢 Contexte tenant
            $table->foreignId('entity_id')
                ->constrained('entities')
                ->cascadeOnDelete();

            $table->foreignId('function_id')
                ->constrained('functions')
                ->cascadeOnDelete();

            // 👤 Qui a créé/modifié
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID utilisateur externe (non FK)');

            // 📋 Métadonnées du contrat
            $table->string('owner', 255)->nullable()->comment('Propriétaire du processus');
            $table->text('purpose')->nullable()->comment('Finalité du processus');

            // 🔄 Statut
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');

            // ✍️ Données d'entrée (JSON)
            $table->json('inputs')->nullable()->comment('Données d\'entrée du processus');

            // 📤 Données de sortie (JSON - PRINCIPAL)
            // Structure:
            // [
            //   {
            //     id: int,
            //     label: "Client enregistré",
            //     user: "Zone saisie utilisateur",
            //     expectations: "Attentes utilisateurs",
            //     actor: "Qui sait faire",
            //     document_path: "fichier.pdf"
            //   }
            // ]
            $table->json('outputs')->nullable()->comment('Données de sortie avec détails');

            // 🔧 Ressources (JSON)
            $table->json('resources')->nullable()->comment('Ressources du processus');

            // 📊 Indicateurs d'activité et performance
            $table->json('activity_indicators')->nullable()->comment('Indicateurs d\'activité');
            $table->json('performance_indicators')->nullable()->comment('Indicateurs de performance');

            // 📁 Fichiers attachés
            $table->json('attachments')->nullable()->comment('Chemins des fichiers (inputs/outputs/resources)');

            // 🕐 Versioning & Audit
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            // 🔍 Indices
            $table->index('process_id', 'idx_pc_process');
            $table->index('entity_id', 'idx_pc_entity');
            $table->index('function_id', 'idx_pc_function');
            $table->index('user_id', 'idx_pc_user');
            $table->index('status', 'idx_pc_status');

            // 🔒 Unicité
            $table->unique(
                ['process_id', 'entity_id', 'function_id'],
                'unique_pc_process_context'
            );
        });

        // 📜 TABLE D'HISTORIQUE - Garder trace des modifications
        Schema::create('process_contract_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                ->constrained('process_contracts')
                ->cascadeOnDelete();

            // 👤 Qui a modifié
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID utilisateur');
            $table->string('user_name', 255)->nullable();

            // 📝 Type d'action
            $table->enum('action', [
                'created',
                'updated_outputs',
                'updated_indicators',
                'file_uploaded',
                'file_deleted',
                'archived',
                'restored'
            ]);

            // 📋 Description de la modification
            $table->text('description')->nullable();

            // 🔄 Avant/Après (JSON)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // 📁 Fichiers concernés
            $table->string('file_name', 255)->nullable();
            $table->string('file_type')->nullable()->comment('input|output|resource');

            // 🕐 Timestamp
            $table->timestamp('created_at')->nullable();

            // 🔍 Indices
            $table->index('contract_id', 'idx_pch_contract');
            $table->index('user_id', 'idx_pch_user');
            $table->index('action', 'idx_pch_action');
            $table->index('created_at', 'idx_pch_created');
        });
    }

    public function down(): void {
        Schema::dropIfExists('process_contract_histories');
        Schema::dropIfExists('process_contracts');
    }
};