<?php
// database/migrations/YYYY_MM_DD_HHMMSS_create_auditors_table.php

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
        Schema::create('auditors', function (Blueprint $table) {
            $table->id();

            // ====== RELATIONS ======
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Utilisateur associé');

            $table->foreignId('entity_id')
                ->nullable()
                ->constrained('entities')
                ->nullOnDelete()
                ->comment('Entité de rattachement');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Créé par');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Modifié par');

            // ====== IDENTITÉ ======
            $table->string('audit_id')->unique()->comment('Code auditeur unique (AUD0001)');
            $table->string('audit_code')->unique()->comment('Code auditeur secondaire');
            $table->string('first_name')->comment('Prénom');
            $table->string('last_name')->comment('Nom de famille');
            $table->string('email')->unique()->comment('Email unique');
            $table->string('phone')->nullable()->comment('Téléphone');

            // ====== INFORMATIONS PERSONNELLES ======
            $table->date('date_of_birth')->nullable()->comment('Date de naissance');
            $table->string('birthplace')->nullable()->comment('Lieu de naissance');
            $table->enum('gender', ['M', 'F'])->nullable()->comment('Genre');

            // ====== ADRESSE ======
            $table->text('address')->nullable()->comment('Adresse complète');
            $table->string('city')->nullable()->comment('Ville');
            $table->string('postal_code')->nullable()->comment('Code postal');
            $table->string('country')->nullable()->comment('Pays');

            // ====== EXPÉRIENCE ======
            $table->integer('audit_experience')->default(0)->comment('Années d\'expérience audit');
            $table->integer('other_experience')->default(0)->comment('Autres années d\'expérience');

            // ====== PROFIL ======
            $table->text('bio')->nullable()->comment('Biographie');
            $table->string('avatar')->nullable()->comment('Photo de profil (chemin)');

            // ====== STATUT ======
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->comment('Statut');

            // ====== TIMESTAMPS ======
            $table->timestamps();
            $table->softDeletes();

            // ====== INDEXES ======
            $table->index('audit_id');
            $table->index('audit_code');
            $table->index('email');
            $table->index('status');
            $table->index('entity_id');
            $table->index('created_by');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['status', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditors');
    }
};