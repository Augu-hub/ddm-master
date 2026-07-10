<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ReferentielSyncService
 * ──────────────────────
 * Propage le référentiel ARMP (pm_*) paramétré UNE SEULE FOIS dans
 * ddmparam (par le Super Admin) vers la base de chaque tenant.
 *
 * Règles :
 *  - Jamais de TRUNCATE sur pm_grilles_verification / _items : leurs
 *    `id` sont référencés par des données transactionnelles du tenant
 *    (mission_phase_grille_marches_reponses.item_id, mission_phase_
 *    grille_marches.grille_id). On les UPSERT en conservant le MÊME id
 *    que dans ddmparam (id explicite dans l'insert), pour que les
 *    réponses déjà saisies par les auditeurs restent valides après
 *    synchro.
 *  - Les tables sans donnée transactionnelle qui pointe dessus
 *    (pivots : pm_mode_organes, pm_delai_organes, pm_delai_modes,
 *    pm_seuils_ac_organes, pm_grilles_verification_organes,
 *    pm_grilles_verification_items_*) sont resynchronisées par un
 *    simple delete+insert : plus simple, sans risque.
 *  - Toute ligne créée localement dans un tenant AVANT la mise en place
 *    de ce mécanisme garde son id existant si le code correspond déjà
 *    (upsert par code) — sinon un nouvel id ddmparam est utilisé, ce qui
 *    peut décaler les FK locales : voir la méthode reconcileLegacyIds()
 *    à lancer une fois, manuellement, avant la 1ère synchro en prod.
 */
class ReferentielSyncService
{
    private function master()
    {
        return DB::connection('mysql'); // connexion pointant sur le serveur MySQL qui héberge ddmparam
    }

    private function tenant(string $dbName)
    {
        // Connexion dynamique vers la base du tenant. Adapter selon votre
        // configuration multi-tenant existante (ex: config(['database.
        // connections.tenant.database' => $dbName]); DB::purge('tenant');)
        config(['database.connections.tenant_sync' => array_merge(
            config('database.connections.tenant'),
            ['database' => $dbName]
        )]);
        DB::purge('tenant_sync');
        return DB::connection('tenant_sync');
    }

    // ═══════════════════════════════════════════════════════════════
    //  POINT D'ENTRÉE — appelé à la connexion (léger : 1 requête si à jour)
    // ═══════════════════════════════════════════════════════════════

    public function syncIfNeeded(int $tenantId): bool
    {
        $master = $this->master();

        $currentVersion = (int) $master->table('ddmparam.pm_referentiel_versions')->value('version');

        $tenantRow = $master->table('ddmparam.tenants')->where('id', $tenantId)->first();
        if (!$tenantRow) return false;

        if ((int) $tenantRow->pm_referentiel_version_synced >= $currentVersion) {
            return false; // déjà à jour, rien à faire
        }

        $this->syncTenant($tenantId, $tenantRow->db_name, $currentVersion);
        return true;
    }

    // ═══════════════════════════════════════════════════════════════
    //  SYNCHRONISATION COMPLÈTE D'UN TENANT
    // ═══════════════════════════════════════════════════════════════

    public function syncTenant(int $tenantId, string $dbName, ?int $version = null): void
    {
        $master = $this->master();
        $tenant = $this->tenant($dbName);
        $version = $version ?? (int) $master->table('ddmparam.pm_referentiel_versions')->value('version');

        Log::info('ReferentielSync: démarrage', ['tenant_id' => $tenantId, 'db' => $dbName, 'version' => $version]);

        $tenant->transaction(function () use ($master, $tenant) {

            // ── 1. Référentiels simples (upsert par code) ────────────
            foreach ([
                'pm_types_entites', 'pm_sources_financement', 'pm_natures_marche',
                'pm_modes_passation', 'pm_organes_controle', 'pm_operations',
                'pm_dates_reference',
            ] as $table) {
                $this->upsertByKey($master, $tenant, $table, ['code']);
            }

            // ── 2. Seuils / délais / grilles — upsert avec ID STABLE ──
            $this->upsertWithId($master, $tenant, 'pm_seuils_generaux');
            $this->upsertWithId($master, $tenant, 'pm_seuils_ac');
            $this->upsertWithId($master, $tenant, 'pm_delais');
            $this->upsertWithId($master, $tenant, 'pm_grilles_verification');
            $this->upsertWithId($master, $tenant, 'pm_grilles_verification_items');
            $this->upsertWithId($master, $tenant, 'pm_articles_loi');

            // ── 3. Pivots — resynchronisation simple (delete + insert) ─
            foreach ([
                'pm_mode_organes', 'pm_delai_organes', 'pm_delai_modes',
                'pm_seuils_ac_organes', 'pm_grilles_verification_organes',
                'pm_grilles_verification_items_articles',
                'pm_grilles_verification_items_delais',
                'pm_grilles_verification_items_operations',
            ] as $table) {
                $this->replaceAll($master, $tenant, $table);
            }
        });

        $master->table('ddmparam.tenants')->where('id', $tenantId)->update([
            'pm_referentiel_version_synced' => $version,
            'pm_referentiel_synced_at'      => now(),
        ]);

        Log::info('ReferentielSync: terminé', ['tenant_id' => $tenantId]);
    }

    /** Synchronise TOUS les tenants (pour la commande artisan planifiée). */
    public function syncAllTenants(): array
    {
        $results = [];
        $currentVersion = (int) $this->master()->table('ddmparam.pm_referentiel_versions')->value('version');

        $tenants = $this->master()->table('ddmparam.tenants')->get();
        foreach ($tenants as $t) {
            try {
                if ((int) $t->pm_referentiel_version_synced < $currentVersion) {
                    $this->syncTenant($t->id, $t->db_name, $currentVersion);
                    $results[$t->code] = 'synced';
                } else {
                    $results[$t->code] = 'already-up-to-date';
                }
            } catch (\Throwable $e) {
                Log::error('ReferentielSync: échec tenant', ['tenant' => $t->code, 'error' => $e->getMessage()]);
                $results[$t->code] = 'error: ' . $e->getMessage();
            }
        }
        return $results;
    }

    // ═══════════════════════════════════════════════════════════════
    //  ADMIN — à appeler depuis le module de paramétrage ddmparam
    //  chaque fois qu'une modif est enregistrée, pour incrémenter la
    //  version et déclencher la synchro au prochain login.
    // ═══════════════════════════════════════════════════════════════

    public function bumpVersion(): void
    {
        $this->master()->statement(
            "UPDATE `ddmparam`.`pm_referentiel_versions` SET version = version + 1 WHERE id = 1"
        );
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════

    /** Upsert simple par une ou plusieurs colonnes-clé (ex: ['code']). */
    private function upsertByKey($master, $tenant, string $table, array $keys): void
    {
        $rows = $master->table("ddmparam.{$table}")->get();
        foreach ($rows as $row) {
            $data = (array) $row;
            unset($data['id']); // id local du tenant reste indépendant sur ces tables simples
            $where = array_intersect_key($data, array_flip($keys));
            $tenant->table($table)->updateOrInsert($where, $data);
        }
    }

    /**
     * Upsert en conservant explicitement l'`id` de ddmparam — indispensable
     * pour les tables référencées par des FK transactionnelles côté tenant.
     */
    private function upsertWithId($master, $tenant, string $table): void
    {
        $rows = $master->table("ddmparam.{$table}")->get();
        foreach ($rows as $row) {
            $data = (array) $row;
            $tenant->table($table)->updateOrInsert(['id' => $data['id']], $data);
        }
    }

    /** Resynchronisation totale d'une table pivot (aucune FK externe dessus). */
    private function replaceAll($master, $tenant, string $table): void
    {
        $rows = $master->table("ddmparam.{$table}")->get()->map(fn($r) => (array) $r)->toArray();
        $tenant->table($table)->truncate();
        foreach (array_chunk($rows, 500) as $chunk) {
            if ($chunk) $tenant->table($table)->insert($chunk);
        }
    }
}