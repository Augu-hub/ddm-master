<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * AP (audit_type_id = 3) — Chronologie de la Phase 1 « Préparation ».
 *
 * Remet en ordre les sort_order de la nouvelle maquette et désactive les
 * doublons / anciennes phases superseded :
 *   10 reunion-ouverture · 20 PC (+21 PCE, +22 PCP) · 30 PA · 40 champ-action
 *   50 mv (INACTIF, position réservée) · CHA + analyse-* + programme-travail
 *   + indicateurs INACTIFS.
 *
 * Cible : connexion « mysql » = ddmparam (base centrale). Idempotente :
 * UPDATE par clé fonctionnelle (audit_type_id, code) — jamais par id (les id
 * peuvent différer d'un environnement à l'autre). Aucune resync tenant : les
 * menus lisent sort_order/is_active en direct via JOIN sur ddmparam.
 *
 * NB : le down() réactive les anciennes phases mais ne restaure PAS les
 * sort_order historiques (non nécessaires, la chronologie cible est la bonne).
 */
return new class extends Migration
{
    /** [code => [sort_order, is_active]] pour audit_type_id = 3, phase 1. */
    private array $plan = [
        'reunion-ouverture'         => [10, 1],
        'PC'                        => [20, 1],
        'PCE'                       => [21, 1],
        'PCP'                       => [22, 1],
        'PA'                        => [30, 1],
        'champ-action'              => [40, 1],
        'methodologie-verification' => [50, 1], // canonique — vue MethodologieVerification.vue livrée
        'mv'                        => [98, 0], // ancien stub, doublon de methodologie-verification
        'CHA'                       => [99, 0], // doublon stub de champ-action
        'analyse-processus'         => [60, 0], // ancienne génération
        'analyse-forces-faiblesses' => [70, 0],
        'indicateurs-performance'   => [80, 0],
        'programme-travail'         => [90, 0],
    ];

    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('audit_type_forms')) {
            return;
        }

        foreach ($this->plan as $code => [$sort, $active]) {
            DB::table('audit_type_forms')
                ->where('audit_type_id', 3)
                ->where('code', $code)
                ->update([
                    'sort_order' => $sort,
                    'is_active'  => $active,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('audit_type_forms')) {
            return;
        }

        // Réactive toutes les phases touchées (retour à l'état « tout actif »).
        DB::table('audit_type_forms')
            ->where('audit_type_id', 3)
            ->whereIn('code', array_keys($this->plan))
            ->update(['is_active' => 1, 'updated_at' => now()]);
    }
};
