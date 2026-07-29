<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * AP — Enregistrement CENTRAL de la sous-phase « Méthodologie de vérification »
 * dans ddmparam.audit_type_forms (connexion par défaut « mysql » = ddmparam).
 *
 * Idempotente : updateOrInsert sur (audit_type_id, phase_num, code).
 * PhaseSyncService la provisionne ensuite (lazy) sur les missions AP.
 *
 * Position chronologique 50 (après « Champ d'action » = 40). ACTIVE
 * (is_active = 1) : la vue Inertia dashboards/Auditor/Forms/AP/
 * MethodologieVerification.vue est livrée et buildée. Doit rester cohérent
 * avec la migration de chronologie 2026_07_28_000002 (juste avant).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('audit_type_forms')) {
            return;
        }

        DB::table('audit_type_forms')->updateOrInsert(
            ['audit_type_id' => 3, 'phase_num' => 1, 'code' => 'methodologie-verification'],
            [
                'phase_label' => 'Préparation',
                'parent_id'   => null,
                'label'       => 'Méthodologie de vérification',
                'description' => "Matrice de vérification (par question d'audit) : critères, informations requises, type/nature/source de preuve, méthodes de collecte et d'analyse ; plan de collecte des éléments probants.",
                'route_name'  => 'audit.ap.preparation.methodologie-verification',
                'url_path'    => '/m/audit.core/ap/preparation/methodologie-verification',
                'icon'        => 'ti ti-flask',
                'sort_order'  => 50,
                'is_active'   => 1, // vue MethodologieVerification.vue livrée
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasTable('audit_type_forms')) {
            DB::table('audit_type_forms')
                ->where(['audit_type_id' => 3, 'phase_num' => 1, 'code' => 'methodologie-verification'])
                ->delete();
        }
    }
};
