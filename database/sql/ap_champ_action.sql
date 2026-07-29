-- ============================================================================
-- AUDIT DE PERFORMANCE — SOUS-PHASE 1.4 « CHAMP D'ACTION »
-- (Planification de la mission — maquette 7 volets)
--   1) APPROCHES D'AUDIT (suivant les grands critères / les 4 « E »)
--   2) OBJECTIFS D'AUDIT (suivant les approches)
--   3) QUESTION PRINCIPALE (+ sous-questions)
--   4) ÉTENDUE DE L'AUDIT — périmètre Qui / Quoi / Quand / Où
--   5) LIMITES DE L'AUDIT
--   6) CONCLUSIONS POTENTIELLES
--   7) PERTINENCE DU MANDAT DE L'AUDITEUR
--
-- Volets à lignes variables → colonnes JSON (comme mission_phase_ap_portee).
-- La trame se pré-remplit depuis les PARAMÈTRES d'audit de performance
-- (ap_criteres, ap_approches_audit, ap_perimetre_dimensions) côté contrôleur.
--
-- ▸ Table tenant  : à exécuter sur CHAQUE base tenant (idempotent).
--       ex :  mysql -u root -proot fruitiva < ap_champ_action.sql
-- ▸ Phase centrale: l'INSERT ci-dessous vise EXPLICITEMENT `ddmparam`.
--   PhaseSyncService::ensureMissionAssignments() la provisionne ensuite
--   automatiquement (lazy, cache 5 min) sur les missions d'audit de
--   performance (audit_type_id = 3, is_active = 1).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1) TABLE TENANT — une fiche par assignment
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mission_phase_ap_champ_action` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_phase_assignments.id',
  `mission_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_programmation.id',
  `code` VARCHAR(60) DEFAULT NULL COMMENT 'Auto CA-<mission>-NNN',
  -- 1) Approches d'audit (une ligne par critère / grand « E »)
  --    [{critere_code, critere_nature, approche_code, approche_libelle, justification}]
  `approches` LONGTEXT DEFAULT '[]',
  -- 2) Objectifs d'audit (suivant les approches)
  --    [{objectif, approche_code, critere_code, sous_criteres_source}]
  `objectifs` LONGTEXT DEFAULT '[]',
  -- 3) Question principale + sous-questions (une par ligne)
  --    [{objectif_num, question_principale, sous_questions}]
  `questions` LONGTEXT DEFAULT '[]',
  -- 4) Étendue de l'audit — Qui / Quoi / Quand / Où
  --    [{dimension_code, dimension_libelle, questions_cles, contenu, justification}]
  `etendue` LONGTEXT DEFAULT '[]',
  -- 5) Limites de l'audit  [{limite, raison}]
  `limites` LONGTEXT DEFAULT '[]',
  -- 6) Conclusions potentielles  [{conclusion, lien_objectif}]
  `conclusions` LONGTEXT DEFAULT '[]',
  -- 7) Pertinence du mandat  [{question, reponse, commentaire}]
  `pertinence` LONGTEXT DEFAULT '[]',
  `synthese` TEXT DEFAULT NULL,
  `fait_par` VARCHAR(150) DEFAULT NULL,
  `revue_par` VARCHAR(150) DEFAULT NULL,
  `validation_status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | in_review | validated',
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `submitted_by` BIGINT UNSIGNED DEFAULT NULL,
  `validated_at` TIMESTAMP NULL DEFAULT NULL,
  `validated_by` BIGINT UNSIGNED DEFAULT NULL,
  `validation_note` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apca_assignment` (`assignment_id`),
  KEY `idx_apca_mission` (`mission_id`),
  CONSTRAINT `fk_apca_assignment` FOREIGN KEY (`assignment_id`)
    REFERENCES `mission_phase_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP P1 · Champ d''action (planification : approches, objectifs, étendue…)';

-- ----------------------------------------------------------------------------
-- 2) PHASE CENTRALE — ddmparam.audit_type_forms (AP = audit_type_id 3)
--    Idempotent via la clé unique (audit_type_id, phase_num, code).
-- ----------------------------------------------------------------------------
INSERT INTO `ddmparam`.`audit_type_forms`
  (`audit_type_id`, `phase_num`, `phase_label`, `parent_id`, `code`, `label`,
   `description`, `route_name`, `url_path`, `icon`, `sort_order`, `is_active`,
   `created_at`, `updated_at`)
VALUES
  (3, 1, 'Préparation', NULL, 'champ-action', 'Champ d''action',
   'Planification de la mission : approches d''audit (les 4 « E »), objectifs, questions, étendue Qui/Quoi/Quand/Où, limites, conclusions potentielles et pertinence du mandat.',
   'audit.ap.preparation.champ-action', '/m/audit.core/ap/preparation/champ-action',
   'ti ti-target-arrow', 40, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE
  `label`       = VALUES(`label`),
  `description` = VALUES(`description`),
  `route_name`  = VALUES(`route_name`),
  `url_path`    = VALUES(`url_path`),
  `icon`        = VALUES(`icon`),
  `sort_order`  = VALUES(`sort_order`),
  `is_active`   = 1,
  `updated_at`  = CURRENT_TIMESTAMP;
