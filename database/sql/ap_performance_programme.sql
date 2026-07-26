-- ============================================================================
-- AUDIT DE PERFORMANCE — FICHE « PRÉSENTATION GÉNÉRALE DU PROGRAMME »
-- (maquettes FranckS : rubriques 1-6, structuration en actions/objectifs/
--  indicateurs, fiche activité avec extrants EFE, fiche indicateur,
--  point financier & physique par année)
--
-- À exécuter sur CHAQUE base tenant (idempotent : CREATE TABLE IF NOT EXISTS).
--   ex :  mysql -u root -proot fruitiva < ap_performance_programme.sql
--
-- LIAISON PROGRAMME : l'audit de performance porte sur les PROCESSUS DE
-- RÉALISATION du tenant (= les programmes). La fiche référence
-- `processes.id` (process_id) et les objectifs saisis peuvent être liés aux
-- objectifs déjà en base (`objectifs.id` → source_objectif_id).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1) FICHE PROGRAMME — une par assignment (formulaire PC de la phase Préparation)
--    Rubriques fixes 1/2/4/5/6 de la maquette + rubriques libres en JSON.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mission_phase_ap_programme` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_phase_assignments.id',
  `mission_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_programmation.id',
  `process_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'LE PROGRAMME AUDITÉ = processes.id (processus de réalisation)',
  `code` VARCHAR(60) NOT NULL COMMENT 'Auto APP-<mission>-NNN',
  `objectif_general` TEXT DEFAULT NULL COMMENT 'Bandeau OBJECTIF GENERAL de la mission',
  -- Rubrique 1 · Présentation générale du programme
  `mandats` TEXT DEFAULT NULL,
  `missions_prog` TEXT DEFAULT NULL,
  `objectifs_prog` TEXT DEFAULT NULL,
  -- Rubrique 2 · Résultats escomptés de l'entité par rapport au programme
  `res_economie` TEXT DEFAULT NULL,
  `res_efficacite` TEXT DEFAULT NULL,
  `res_efficience` TEXT DEFAULT NULL,
  `res_qualite` TEXT DEFAULT NULL,
  -- Rubrique 4 · La gouvernance du programme
  `gouv_organisation` TEXT DEFAULT NULL,
  `gouv_rel_interieures` TEXT DEFAULT NULL,
  `gouv_rel_exterieures` TEXT DEFAULT NULL,
  -- Rubrique 5 · Les sources d'information du programme
  `src_legislation` TEXT DEFAULT NULL COMMENT 'Législation, interventions parlementaires, déclarations, décisions',
  `src_rapports_anterieurs` TEXT DEFAULT NULL,
  -- Rubrique 6 · Les ressources du programme
  `ress_humaines` TEXT DEFAULT NULL,
  `ress_financieres` TEXT DEFAULT NULL,
  `ress_techniques` TEXT DEFAULT NULL,
  -- Rubriques/sous-rubriques ajoutées par l'utilisateur (maquette : « insérer
  -- une rubrique / sous-rubrique », numérotation auto côté écran)
  `rubriques_extra` LONGTEXT DEFAULT '[]' COMMENT 'JSON [{titre, sous:[{titre, contenu}]}]',
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
  UNIQUE KEY `uq_app_assignment` (`assignment_id`),
  KEY `idx_app_mission` (`mission_id`),
  KEY `idx_app_process` (`process_id`),
  CONSTRAINT `fk_app_assignment` FOREIGN KEY (`assignment_id`)
    REFERENCES `mission_phase_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP P1 · Présentation générale du programme (fiche PC)';

-- ----------------------------------------------------------------------------
-- 2) POINT FINANCIER & PHYSIQUE — une ligne par (fiche, année)
--    Taux d''exécution = execution/dotation, calculé à l''affichage.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ap_prog_financier` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `programme_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_phase_ap_programme.id',
  `annee` SMALLINT UNSIGNED NOT NULL,
  `dotation` DECIMAL(18,2) DEFAULT NULL,
  `execution` DECIMAL(18,2) DEFAULT NULL,
  `point_physique` DECIMAL(5,2) DEFAULT NULL COMMENT 'Réalisation physique en %',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apf_prog_annee` (`programme_id`, `annee`),
  CONSTRAINT `fk_apf_programme` FOREIGN KEY (`programme_id`)
    REFERENCES `mission_phase_ap_programme` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP · Point financier (dotation/exécution) et physique par année';

-- ----------------------------------------------------------------------------
-- 3) ACTIVITÉS DU PROGRAMME — rubrique 3 « Structuration en actions »
--    + FICHE ACTIVITÉ (résultat, responsable, budgets, extrants EFE)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ap_prog_activites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `programme_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(30) NOT NULL COMMENT 'Auto AC_01.01, AC_01.02…',
  `intitule` VARCHAR(500) NOT NULL,
  `resultat` TEXT DEFAULT NULL COMMENT 'Résultat activité (ex: La prévention multiple est améliorée)',
  `responsable` VARCHAR(255) DEFAULT NULL,
  `membres` LONGTEXT DEFAULT '[]' COMMENT 'JSON [string] — autres membres de l''équipe',
  `quantite` VARCHAR(100) DEFAULT NULL,
  `budget_global` DECIMAL(18,2) DEFAULT NULL,
  `budget_exercice` DECIMAL(18,2) DEFAULT NULL,
  `commentaires` TEXT DEFAULT NULL,
  -- Tableau extrants de la fiche activité :
  -- éléments Biens / Matériel-équipement / Installations / Informations /
  -- Énergie…, chacun avec Std-Ref EFE (taux, valeur), Entrées (nombre,
  -- valeur), Sorties (nombre, valeur), Observations.
  `extrants` LONGTEXT DEFAULT '[]'
    COMMENT 'JSON [{element, taux_efe, valeur_efe, entrees_nombre, entrees_valeur, sorties_nombre, sorties_valeur, obs}]',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_apa_programme` (`programme_id`),
  CONSTRAINT `fk_apa_programme` FOREIGN KEY (`programme_id`)
    REFERENCES `mission_phase_ap_programme` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP · Activités du programme + fiche activité (extrants EFE)';

-- ----------------------------------------------------------------------------
-- 4) OBJECTIFS PAR ACTIVITÉ — codes OB_01.0X.Y
--    ★ LIAISON BASE : source_objectif_id → objectifs.id (objectifs du
--    processus/programme déjà saisis dans le module Processus du tenant).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ap_prog_objectifs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activite_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(30) NOT NULL COMMENT 'Auto OB_01.01.1…',
  `libelle` TEXT NOT NULL,
  `source_objectif_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Objectif du processus lié (objectifs.id) — NULL si saisi libre',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_apo_activite` (`activiten:_id`),
  KEY `idx_apo_source` (`source_objectif_id`),
  CONSTRAINT `fk_apo_activite` FOREIGN KEY (`activite_id`)
    REFERENCES `ap_prog_activites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP · Objectifs par activité, liables aux objectifs du processus';

-- ----------------------------------------------------------------------------
-- 5) FICHE INDICATEUR — maquette complète, un indicateur par ligne,
--    rattaché à un objectif d''activité.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ap_prog_indicateurs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `objectif_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(30) NOT NULL COMMENT 'Auto IND-NNN',
  `intitule` VARCHAR(500) NOT NULL,
  `service_utilisateur` VARCHAR(255) DEFAULT NULL,
  `unite_mesure` VARCHAR(100) DEFAULT NULL COMMENT '%, Montant, Nombre…',
  `periodicite_mesure` VARCHAR(100) DEFAULT NULL,
  `periodicite_indicateur` VARCHAR(100) DEFAULT NULL COMMENT 'Fréquence de calcul et de parution',
  `dernieres_valeurs` LONGTEXT DEFAULT '[]' COMMENT 'JSON [{annee, valeur}] — 3 dernières valeurs connues',
  `nature_donnees` TEXT DEFAULT NULL COMMENT 'Ratio : distinguer numérateur/dénominateur',
  `mode_collecte` VARCHAR(255) DEFAULT NULL COMMENT 'Combo paramétrable',
  `service_synthese` VARCHAR(255) DEFAULT NULL COMMENT 'Service responsable de la centralisation/production',
  `structure_validation` VARCHAR(255) DEFAULT NULL COMMENT 'Structure responsable de la validation',
  `mode_calcul` TEXT DEFAULT NULL,
  `interpretation` TEXT DEFAULT NULL COMMENT 'Modalités d''interprétation',
  `sens_evolution` ENUM('hausse','baisse') DEFAULT NULL COMMENT 'Sens d''évolution souhaité',
  `limites` TEXT DEFAULT NULL COMMENT 'Limites et biais connus + justification',
  `date_livraison` VARCHAR(150) DEFAULT NULL,
  `plan_amelioration` TEXT DEFAULT NULL,
  `commentaires` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_objectif` (`objectif_id`),
  CONSTRAINT `fk_api_objectif` FOREIGN KEY (`objectif_id`)
    REFERENCES `ap_prog_objectifs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP · Fiches indicateurs de performance par objectif';

-- ============================================================================
-- AP · PHASE 1 — « PORTÉE DE L'AUDIT » (formulaire PA, ddmparam id=129)
-- Maquette : 3 volets dynamiques
--   1) IMPORTANCE DES SUJETS
--   2) ANALYSE DES RESSOURCES ET RÉSULTATS (cadre logique)
--   3) ANALYSE DES RISQUES D'AUDIT (brut → mitigation → net)
-- Les 3 volets sont des tableaux à lignes variables → colonnes JSON.
-- Auto-alimentés depuis le programme audité (processes/objectifs/activities).
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mission_phase_ap_portee` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_phase_assignments.id',
  `mission_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK mission_programmation.id',
  `process_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Programme audité = processes.id',
  -- 1) Importance des sujets
  --    [{sujet, enjeu, poids, niveau_importance(Faible|Moyen|Élevé), justification}]
  `sujets` LONGTEXT DEFAULT '[]',
  -- 2) Analyse des ressources et résultats (cadre logique)
  --    [{composante(Intrants|Activités|Extrants|Résultats|Impact), libelle,
  --      indicateur, valeur, unite, source, observations}]
  `ressources` LONGTEXT DEFAULT '[]',
  -- 3) Analyse des risques d'audit (probabilité/impact 1-3, score = P×I,
  --    niveau auto ; brut puis net après mitigation)
  --    [{libelle, categorie, proba_brute, impact_brut, moyens_mitigation,
  --      proba_nette, impact_nette, responsable, observations}]
  `risques` LONGTEXT DEFAULT '[]',
  `synthese` TEXT DEFAULT NULL,
  `fait_par` VARCHAR(150) DEFAULT NULL,
  `revue_par` VARCHAR(150) DEFAULT NULL,
  `validation_status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `submitted_by` BIGINT UNSIGNED DEFAULT NULL,
  `validated_at` TIMESTAMP NULL DEFAULT NULL,
  `validated_by` BIGINT UNSIGNED DEFAULT NULL,
  `validation_note` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appa_assignment` (`assignment_id`),
  KEY `idx_appa_mission` (`mission_id`),
  KEY `idx_appa_process` (`process_id`),
  CONSTRAINT `fk_appa_assignment` FOREIGN KEY (`assignment_id`)
    REFERENCES `mission_phase_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='AP P1 · Portée de l''audit (importance, ressources/résultats, risques)';
