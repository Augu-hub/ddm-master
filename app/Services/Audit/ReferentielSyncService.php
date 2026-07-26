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

        // ── 0. Mise à niveau du schéma tenant (tables/colonnes AM 2026) ──
        //     Les paramètres ARMP (barèmes, pondération, conditions, gravité
        //     des écarts) ont été ajoutés dans ddmparam APRÈS le provisioning
        //     initial des tenants : on garantit d'abord que la structure
        //     d'accueil existe, sinon l'upsert échouerait sur colonne absente.
        $this->ensureTenantSchema($tenant);

        $tenant->transaction(function () use ($master, $tenant) {

            // ── 1. Référentiels simples (upsert par code) ────────────
            foreach ([
                'pm_types_entites', 'pm_sources_financement', 'pm_natures_marche',
                'pm_modes_passation', 'pm_organes_controle',
                // Paramétrage AM 2026 (identifié par code, id tenant libre)
                'pm_parametres_audit', 'pm_modalites_appreciation',
                'pm_conditions_conformite', 'pm_pieces_categories',
            ] as $table) {
                $this->upsertByKey($master, $tenant, $table, ['code']);
            }
            // Gabarits de formulaire : clé = form_code
            $this->upsertByKey($master, $tenant, 'pm_form_templates', ['form_code']);
            // pm_pieces_obligatoires : id STABLE — mission_phase pièces
            // (à venir) référenceront piece_id ; pm_operations /
            // pm_dates_reference : id STABLE car pm_delais.operation_id et
            // .date_reference_id pointent sur ces id centraux (sinon FK KO).
            $this->upsertWithId($master, $tenant, 'pm_operations');
            $this->upsertWithId($master, $tenant, 'pm_dates_reference');
            $this->upsertWithId($master, $tenant, 'pm_pieces_obligatoires');

            // ── 2. Seuils / délais / grilles — upsert avec ID STABLE ──
            $this->upsertWithId($master, $tenant, 'pm_seuils_generaux');
            $this->upsertWithId($master, $tenant, 'pm_seuils_ac');
            $this->upsertWithId($master, $tenant, 'pm_delais');
            $this->upsertWithId($master, $tenant, 'pm_grilles_verification');
            $this->upsertWithId($master, $tenant, 'pm_grilles_verification_items');
            $this->upsertWithId($master, $tenant, 'pm_articles_loi');

            // ── 3. Barèmes/appréciations — id stable (référencés à l'écran) ─
            $this->upsertWithId($master, $tenant, 'pm_grille_appreciation_disponibilite');
            $this->upsertWithId($master, $tenant, 'pm_baremes_appreciation');

            // ── 4. Pivots — resynchronisation simple (delete + insert) ─
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
    //  SCHÉMA TENANT — DDL idempotente pour le paramétrage AM 2026
    //  (tables ajoutées après le provisioning initial des tenants)
    // ═══════════════════════════════════════════════════════════════

    private function ensureTenantSchema($tenant): void
    {
        // 4.a — Nouvelles tables de paramétrage (structure identique à ddmparam)
        $tenant->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pm_baremes_appreciation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_bareme` VARCHAR(20) NOT NULL,
  `borne_min` DECIMAL(5,2) DEFAULT NULL,
  `operateur_min` VARCHAR(5) DEFAULT NULL,
  `borne_max` DECIMAL(5,2) DEFAULT NULL,
  `operateur_max` VARCHAR(5) DEFAULT NULL,
  `appreciation` VARCHAR(255) NOT NULL,
  `code_resultat` VARCHAR(30) DEFAULT NULL,
  `est_conforme` TINYINT(1) DEFAULT NULL,
  `couleur` VARCHAR(20) DEFAULT 'gray',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bareme_type` (`type_bareme`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $tenant->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pm_modalites_appreciation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `libelle` VARCHAR(100) NOT NULL,
  `poids` DECIMAL(5,2) NOT NULL,
  `exclu_du_calcul` TINYINT(1) NOT NULL DEFAULT 0,
  `couleur` VARCHAR(20) DEFAULT 'gray',
  `icone` VARCHAR(60) DEFAULT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $tenant->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pm_conditions_conformite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `libelle` VARCHAR(300) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `portee` VARCHAR(20) NOT NULL DEFAULT 'marche',
  `parametre_code` VARCHAR(50) DEFAULT NULL,
  `bloquante` TINYINT(1) NOT NULL DEFAULT 1,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $tenant->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pm_form_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_code` VARCHAR(160) NOT NULL,
  `audit_type_code` VARCHAR(10) DEFAULT NULL,
  `titre` VARCHAR(255) DEFAULT NULL,
  `sections_json` LONGTEXT NOT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpl_form` (`form_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        // 4.b — Colonnes ajoutées sur des tables déjà provisionnées
        $this->addColumnIfMissing($tenant, 'pm_pieces_categories', 'annexe',
            "ADD COLUMN `annexe` VARCHAR(10) DEFAULT NULL AFTER `libelle`");
        $this->addColumnIfMissing($tenant, 'pm_pieces_obligatoires', 'reference_texte',
            "ADD COLUMN `reference_texte` VARCHAR(255) DEFAULT NULL AFTER `incidence`,
             ADD COLUMN `mode_passation_code` VARCHAR(20) DEFAULT NULL AFTER `reference_texte`,
             ADD COLUMN `compte_auditabilite` TINYINT(1) NOT NULL DEFAULT 1 AFTER `mode_passation_code`");
        $this->addColumnIfMissing($tenant, 'pm_grilles_verification_items', 'gravite_ecart',
            "ADD COLUMN `gravite_ecart` VARCHAR(10) DEFAULT NULL AFTER `type_reponse`,
             ADD COLUMN `reference_ecart` VARCHAR(30) DEFAULT NULL AFTER `gravite_ecart`");
        // `preuves` : colonne présente en central mais absente de certains
        // tenants provisionnés avant son ajout (dérive de schéma historique).
        $this->addColumnIfMissing($tenant, 'pm_grilles_verification_items', 'preuves',
            "ADD COLUMN `preuves` TEXT DEFAULT NULL AFTER `seuil_ac_id`");
        // Certains tenants historiques ont `unite` en VARCHAR(20) : on élargit
        $tenant->statement("ALTER TABLE `pm_parametres_audit` MODIFY `unite` VARCHAR(40) DEFAULT '%'");
    }

    /** ALTER conditionnel : n'ajoute la/les colonne(s) que si la 1ʳᵉ manque. */
    private function addColumnIfMissing($tenant, string $table, string $column, string $alterClause): void
    {
        $dbName = $tenant->getDatabaseName();
        $exists = $tenant->selectOne(
            'SELECT COUNT(*) AS n FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$dbName, $table, $column]
        );
        if (!$exists || (int) $exists->n === 0) {
            $tenant->statement("ALTER TABLE `{$table}` {$alterClause}");
        }
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

    /**
     * Resynchronisation totale d'une table pivot (aucune FK externe dessus).
     * ⚠️ On utilise DELETE et non TRUNCATE : TRUNCATE est du DDL et provoque
     *    un COMMIT implicite qui romprait la transaction englobante de
     *    syncTenant() (« There is no active transaction » au commit final).
     */
    private function replaceAll($master, $tenant, string $table): void
    {
        $rows = $master->table("ddmparam.{$table}")->get()->map(fn($r) => (array) $r)->toArray();
        $tenant->table($table)->delete();
        foreach (array_chunk($rows, 500) as $chunk) {
            if ($chunk) $tenant->table($table)->insert($chunk);
        }
    }
}