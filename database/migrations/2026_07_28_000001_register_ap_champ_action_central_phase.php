<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * AP — Enregistrement CENTRAL de la sous-phase 1.4 « Champ d'action »
 * dans ddmparam.audit_type_forms (connexion par défaut « mysql » = ddmparam).
 *
 * Une fois cette phase enregistrée (audit_type_id = 3, is_active = 1),
 * PhaseSyncService::ensureMissionAssignments() la provisionne automatiquement
 * (lazy) sur les missions d'audit de performance.
 *
 * Idempotente : updateOrInsert sur la clé fonctionnelle (audit_type_id,
 * phase_num, code) — pas de doublon si la ligne existe déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sécurité : ne rien faire si la table centrale n'est pas accessible
        // (ex. migration jouée sur une connexion tenant par erreur).
        if (!DB::getSchemaBuilder()->hasTable('audit_type_forms')) {
            return;
        }

        DB::table('audit_type_forms')->updateOrInsert(
            ['audit_type_id' => 3, 'phase_num' => 1, 'code' => 'champ-action'],
            [
                'phase_label' => 'Préparation',
                'parent_id'   => null,
                'label'       => "Champ d'action",
                'description' => "Planification de la mission : approches d'audit (les 4 « E »), objectifs, questions, étendue Qui/Quoi/Quand/Où, limites, conclusions potentielles et pertinence du mandat.",
                'route_name'  => 'audit.ap.preparation.champ-action',
                'url_path'    => '/m/audit.core/ap/preparation/champ-action',
                'icon'        => 'ti ti-target-arrow',
                'sort_order'  => 40,
                'is_active'   => 1,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasTable('audit_type_forms')) {
            DB::table('audit_type_forms')
                ->where(['audit_type_id' => 3, 'phase_num' => 1, 'code' => 'champ-action'])
                ->delete();
        }
    }
};
