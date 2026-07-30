-- =====================================================================
--  DDM RISQUE — Sessions d'évaluation & snapshots (chantier #3)
-- ---------------------------------------------------------------------
--  À exécuter SUR CHAQUE BASE TENANT (ex. fructivia1) qui utilise le
--  module risque. Idempotent (CREATE TABLE IF NOT EXISTS).
--
--  Modèle :
--   - risk_sessions            = campagne / session d'évaluation des risques.
--                                C'est la table cible du `session_id` déjà
--                                présent dans risk_entity_assignments*.
--   - risk_session_snapshots   = gel de l'évaluation (inhérent / résiduel /
--                                cible) de chaque risque au sein d'une session.
--                                Contexte + libellés de zone DÉNORMALISÉS pour
--                                que la comparaison reste stable même si le
--                                registre ou la config de matrice évolue ensuite.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `risk_sessions` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`          BIGINT UNSIGNED NOT NULL,
  `code`               VARCHAR(50)  NULL,
  `name`               VARCHAR(255) NOT NULL,
  `year`               SMALLINT UNSIGNED NULL,
  `status`             ENUM('draft','active','closed','archived') NOT NULL DEFAULT 'draft',
  `is_active`          TINYINT(1) NOT NULL DEFAULT 0,
  `parent_session_id`  BIGINT UNSIGNED NULL COMMENT 'Session dont celle-ci est l''actualisation',
  `matrix_config_id`   BIGINT UNSIGNED NULL,
  `started_at`         TIMESTAMP NULL,
  `closed_at`          TIMESTAMP NULL,
  `snapshot_at`        TIMESTAMP NULL COMMENT 'Dernier gel du registre dans cette session',
  `risks_count`        INT NOT NULL DEFAULT 0,
  `notes`              TEXT NULL,
  `report_json`        LONGTEXT NULL COMMENT 'Narratif éditable du rapport de gestion lié à la session',
  `created_by`         BIGINT UNSIGNED NULL,
  `created_at`         TIMESTAMP NULL,
  `updated_at`         TIMESTAMP NULL,
  `deleted_at`         TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rs_tenant_idx`   (`tenant_id`),
  KEY `rs_status_idx`   (`status`),
  KEY `rs_parent_idx`   (`parent_session_id`),
  KEY `rs_active_idx`   (`tenant_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la table risk_sessions préexistait sans report_json (ajout idempotent manuel) :
--   ALTER TABLE `risk_sessions` ADD COLUMN `report_json` LONGTEXT NULL AFTER `notes`;

CREATE TABLE IF NOT EXISTS `risk_session_snapshots` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`          BIGINT UNSIGNED NOT NULL,
  `session_id`         BIGINT UNSIGNED NOT NULL,
  `risk_id`            BIGINT UNSIGNED NOT NULL,
  -- Identité + contexte dénormalisés (comparaison stable dans le temps)
  `code_risk`          VARCHAR(50)  NULL,
  `libelle`            VARCHAR(255) NULL,
  `entity_id`          BIGINT UNSIGNED NULL,
  `entity_name`        VARCHAR(255) NULL,
  `activity_id`        BIGINT UNSIGNED NULL,
  `activity_name`      VARCHAR(255) NULL,
  `process_id`         BIGINT UNSIGNED NULL,
  `process_name`       VARCHAR(255) NULL,
  `macro_process_id`   BIGINT UNSIGNED NULL,
  `macro_process_name` VARCHAR(255) NULL,
  -- Inhérent
  `inh_impact_score`   TINYINT UNSIGNED NULL,
  `inh_freq_score`     TINYINT UNSIGNED NULL,
  `inh_criticality`    DECIMAL(8,2) NULL,
  `inh_zone_id`        BIGINT UNSIGNED NULL,
  `inh_zone_label`     VARCHAR(100) NULL,
  `inh_zone_color`     VARCHAR(20)  NULL,
  -- Résiduel
  `res_impact_score`   TINYINT UNSIGNED NULL,
  `res_freq_score`     TINYINT UNSIGNED NULL,
  `res_criticality`    DECIMAL(8,2) NULL,
  `res_zone_id`        BIGINT UNSIGNED NULL,
  `res_zone_label`     VARCHAR(100) NULL,
  `res_zone_color`     VARCHAR(20)  NULL,
  -- Cible
  `tgt_impact_score`   TINYINT UNSIGNED NULL,
  `tgt_freq_score`     TINYINT UNSIGNED NULL,
  `tgt_criticality`    DECIMAL(8,2) NULL,
  `tgt_zone_id`        BIGINT UNSIGNED NULL,
  `tgt_zone_label`     VARCHAR(100) NULL,
  `tgt_zone_color`     VARCHAR(20)  NULL,
  -- Décision + suivi plans d'action à l'instant du gel
  `decision`           VARCHAR(50) NULL,
  `plans_total`        INT NOT NULL DEFAULT 0,
  `plans_done`         INT NOT NULL DEFAULT 0,
  `plans_progress`     TINYINT UNSIGNED NULL COMMENT 'Avancement moyen 0-100',
  `captured_at`        TIMESTAMP NULL,
  `created_at`         TIMESTAMP NULL,
  `updated_at`         TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rss_session_risk_uq` (`session_id`, `risk_id`),
  KEY `rss_tenant_idx`  (`tenant_id`),
  KEY `rss_session_idx` (`session_id`),
  KEY `rss_risk_idx`    (`risk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
