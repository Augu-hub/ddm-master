-- ============================================================================
-- AUDIT DES MARCHÉS (AM) — RÉFÉRENTIEL ARMP 2026
-- FICHIER 00 · PARAMÈTRES CENTRAUX (barèmes, pondération, pièces obligatoires)
--
-- ⚠️ À EXÉCUTER SUR LA BASE PRINCIPALE : ddmparam
--      mysql -u root -proot ddmparam < am_armp_00_parametres.sql
--
-- Pourquoi ce fichier : ces 4 tables de paramétrage avaient été créées
-- directement dans le tenant `fruitiva` (paramétrage « à la main »). Elles
-- remontent ici dans la base principale pour devenir un PARAMÉTRAGE UNIQUE
-- (Super Admin) propagé à CHAQUE tenant par ReferentielSyncService, comme
-- les autres tables pm_*. Les données déjà saisies dans fruitiva sont
-- reprises telles quelles (INSERT … SELECT cross-base, même serveur MySQL).
--
-- Source normative : Référentiel d'audit de la passation et de l'exécution
-- des marchés publics — ARMP Bénin, Avril 2026 (§2.3.1, §2.4) et Grilles de
-- vérification, Juin 2026 (annexes A1 à A14, A22, A23).
-- Idempotent : CREATE TABLE IF NOT EXISTS + INSERT … ON DUPLICATE KEY UPDATE.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1) CATÉGORIES DE PIÈCES JUSTIFICATIVES  (annexes A1 / A2 du référentiel)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_pieces_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `libelle` VARCHAR(255) NOT NULL,
  `annexe` VARCHAR(10) DEFAULT NULL COMMENT 'A1 = organes/planification · A2 = marchés',
  `description` TEXT DEFAULT NULL,
  `sort` INT NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catégories de pièces justificatives (Passation / Exécution / Matérialité)';

-- Colonne `annexe` ajoutée après coup sur les installations existantes
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pm_pieces_categories'
      AND COLUMN_NAME = 'annexe') = 0,
  'ALTER TABLE `pm_pieces_categories` ADD COLUMN `annexe` VARCHAR(10) DEFAULT NULL AFTER `libelle`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT INTO `pm_pieces_categories` (`id`,`code`,`libelle`,`annexe`,`description`,`sort`,`actif`) VALUES
 (1,'PASSATION','Pièces à incidence directe — Passation','A2','Pièces dont l''absence entraîne la non-conformité du marché (NCF) au stade de la passation',1,1),
 (2,'EXECUTION','Pièces à incidence directe — Exécution','A2','Pièces dont l''absence entraîne la non-conformité du marché (NCF) au stade de l''exécution',2,1),
 (3,'MATERIALITE','Pièces — Matérialité (vérification terrain)','A2','Preuves de la réalité physique/fonctionnelle des prestations',3,1),
 (4,'ORGANES','Pièces — Organes de passation et de contrôle','A1','Actes de mise en place, d''organisation et de fonctionnement (PRMP, CCMP, SP-PRMP)',4,1),
 (5,'PLANIFICATION','Pièces — Planification (PPM/PPMP)','A1','PPM initial, révisions, validation CCMP et preuves de publication SIGMaP',5,1)
ON DUPLICATE KEY UPDATE
  `libelle`=VALUES(`libelle`), `annexe`=VALUES(`annexe`),
  `description`=VALUES(`description`), `sort`=VALUES(`sort`), `actif`=VALUES(`actif`);

-- ----------------------------------------------------------------------------
-- 2) CATALOGUE DES PIÈCES OBLIGATOIRES
--    incidence = 'directe'        → NCF : son absence rend le marché NON CONFORME
--    incidence = 'sans_incidence' → INSF : insuffisance, sans effet sur la conformité
--    (§ Module 7 : A1 = 20 pièces dont 14 NCF ; A2 = 61 pièces dont 35 NCF)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_pieces_obligatoires` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categorie_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `libelle` VARCHAR(255) NOT NULL,
  `incidence` VARCHAR(20) NOT NULL DEFAULT 'directe' COMMENT 'directe (NCF) | sans_incidence (INSF)',
  `reference_texte` VARCHAR(255) DEFAULT NULL COMMENT 'Base légale (loi 2020-26, décrets 2020-59x/605…)',
  `mode_passation_code` VARCHAR(20) DEFAULT NULL COMMENT 'NULL = toutes procédures',
  `compte_auditabilite` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = entre dans le calcul du taux de complétude (seuil 80 %)',
  `obligatoire` TINYINT(1) NOT NULL DEFAULT 1,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_piece_code` (`code`),
  KEY `idx_piece_categorie` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catalogue des pièces obligatoires par catégorie (paramétrage central)';

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pm_pieces_obligatoires'
      AND COLUMN_NAME = 'reference_texte') = 0,
  'ALTER TABLE `pm_pieces_obligatoires`
     ADD COLUMN `reference_texte` VARCHAR(255) DEFAULT NULL AFTER `incidence`,
     ADD COLUMN `mode_passation_code` VARCHAR(20) DEFAULT NULL AFTER `reference_texte`,
     ADD COLUMN `compte_auditabilite` TINYINT(1) NOT NULL DEFAULT 1 AFTER `mode_passation_code`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2.a — Reprise à l'identique du paramétrage déjà saisi dans le tenant fruitiva
--       (mêmes id : les réponses d'audit déjà saisies restent valides).
INSERT INTO `pm_pieces_obligatoires`
  (`id`,`categorie_id`,`code`,`libelle`,`incidence`,`obligatoire`,`actif`,`sort`)
SELECT `id`,`categorie_id`,`code`,`libelle`,`incidence`,`obligatoire`,`actif`,`sort`
FROM `fruitiva`.`pm_pieces_obligatoires`
ON DUPLICATE KEY UPDATE
  `categorie_id`=VALUES(`categorie_id`), `libelle`=VALUES(`libelle`),
  `incidence`=VALUES(`incidence`), `obligatoire`=VALUES(`obligatoire`),
  `actif`=VALUES(`actif`), `sort`=VALUES(`sort`);

-- 2.b — Bases légales des pièces reprises (référentiel ARMP / loi 2020-26)
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 24 de la loi n°2020-26'                         WHERE `code`='PPMP_VALIDE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Articles 14 et 15 de la loi n°2020-26'                  WHERE `code` IN ('AVIS_CCMP_DNCMP_DAO','AVIS_CCMP_DNCMP_RAPPORT');
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 46 de la loi n°2020-26'                         WHERE `code`='DAO_TDR';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 53 de la loi n°2020-26'                         WHERE `code`='AVIS_AAC_PUBLIE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 70 de la loi n°2020-26'                         WHERE `code`='PV_OUVERTURE_PLIS';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 72 de la loi n°2020-26 · art. 11 décret 2020-596' WHERE `code`='RAPPORT_EVAL_COE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 78 de la loi n°2020-26'                         WHERE `code`='PV_ATTRIB_PROVISOIRE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Articles 84 et 85 de la loi n°2020-26'                  WHERE `code`='CONTRAT_SIGNE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Articles 22, 84 et 85 de la loi n°2020-26'              WHERE `code`='APPROBATION_AUTORITE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 87 de la loi n°2020-26'                         WHERE `code`='AVIS_ATTRIB_DEFINITIVE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Articles 1er et 100 al. 5 de la loi n°2020-26'          WHERE `code`='OS_DEMARRAGE';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 91 de la loi n°2020-26'                         WHERE `code`='GARANTIE_BONNE_EXEC';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Articles 88 à 90 de la loi n°2020-26'                   WHERE `code` IN ('PV_RECEPTION_PROVISOIRE','PV_RECEPTION_DEFINITIVE');
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 110 de la loi n°2020-26'                        WHERE `code` IN ('DECOMPTES_FACTURES','CERTIF_PAIEMENT');
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 100 de la loi n°2020-26'                        WHERE `code`='AVENANTS_FORMALISES';
UPDATE `pm_pieces_obligatoires` SET `reference_texte`='Article 107 de la loi n°2020-26'                        WHERE `code` IN ('DECISION_RESILIATION','AVIS_DNCMP_RESILIATION');
-- Les pièces de matérialité sont des preuves de terrain : hors calcul d'auditabilité
UPDATE `pm_pieces_obligatoires` SET `compte_auditabilite`=0 WHERE `categorie_id`=3;

-- 2.c — Pièces complémentaires exigées par le référentiel et absentes du
--       paramétrage initial (passation) — id >= 100 pour ne pas heurter
--       l'AUTO_INCREMENT du paramétrage tenant historique.
INSERT INTO `pm_pieces_obligatoires`
  (`id`,`categorie_id`,`code`,`libelle`,`incidence`,`reference_texte`,`mode_passation_code`,`compte_auditabilite`,`obligatoire`,`sort`) VALUES
 (100,1,'REGISTRE_DEPOT_OFFRES','Registre spécial de dépôt des offres (coté et paraphé ARMP)','directe','Article 69 de la loi n°2020-26',NULL,1,1,14),
 (101,1,'RECEPISSES_DEPOT','Récépissés de dépôt délivrés aux soumissionnaires','sans_incidence','Article 69 de la loi n°2020-26',NULL,1,1,15),
 (102,1,'NOTE_SERVICE_COE','Note de service de mise en place de la COE/COE-DRP','directe','Article 12 loi n°2020-26 · art. 10 décret 2020-596',NULL,1,1,16),
 (103,1,'LISTE_PRESENCE_COE','Listes de présence COE / représentant CCMP','sans_incidence','Article 70 de la loi n°2020-26',NULL,1,1,17),
 (104,1,'LETTRES_INFO_SOUMISSIONNAIRES','Lettres d''information des soumissionnaires (retenus et non retenus)','sans_incidence','Article 79 loi n°2020-26 · art. 19 décret 2020-605',NULL,1,1,18),
 (105,1,'ADDENDA_NOTIFIES','Addenda notifiés aux candidats ayant acquis le DAC','sans_incidence','IC 8 des dossiers types',NULL,1,1,19),
 (106,1,'RAPPORT_SPECIAL_GAG','Rapport spécial de la PRMP justifiant le gré à gré','directe','Article 35 al. 3 de la loi n°2020-26','GAG',1,1,20),
 (107,1,'AUTORISATION_GAG','Avis DNCMP / décision du Conseil des ministres (gré à gré)','directe','Article 35 de la loi n°2020-26','GAG',1,1,21),
 (108,1,'ENREGISTREMENT_MARCHE','Preuve d''enregistrement du marché (services des domaines)','directe','Article 86 de la loi n°2020-26',NULL,1,1,22),
 (109,1,'NOTIFICATION_TITULAIRE','Notification du marché approuvé au titulaire','directe','Article 86 de la loi n°2020-26',NULL,1,1,23),
 (110,1,'RESERVATION_CREDITS','Preuve de réservation des crédits avant signature','directe','Article 84 de la loi n°2020-26',NULL,1,1,24),
 (111,1,'DECISION_ARMP_RECOURS','Décision ARMP sur recours et preuve de mise en œuvre','sans_incidence','Articles 79 et suivants de la loi n°2020-26',NULL,0,0,25),
 (112,2,'MISE_EN_DEMEURE','Mise en demeure en cas de dépassement des délais','sans_incidence','Article 113 de la loi n°2020-26',NULL,1,0,11),
 (113,2,'CALCUL_PENALITES','État de calcul et d''application des pénalités de retard','sans_incidence','Articles 113 et 114 de la loi n°2020-26',NULL,1,0,12),
 (114,2,'GARANTIE_AVANCE_DEMARRAGE','Garantie de l''avance de démarrage','directe','Article 111 de la loi n°2020-26',NULL,1,1,13),
 (115,2,'ATTACHEMENTS_DECOMPTES','Attachements signés (RST / maître d''œuvre)','sans_incidence','Documents contractuels (CCAP)',NULL,1,0,14),
 (116,2,'AVIS_DNCMP_AVENANT','Autorisation DNCMP préalable à l''avenant','directe','Article 100 al. 3 de la loi n°2020-26',NULL,1,1,15),
 (117,2,'MAINLEVEE_GARANTIE','Mainlevée / libération de la retenue de garantie','sans_incidence','Article 95 al. 2 de la loi n°2020-26',NULL,1,0,16)
ON DUPLICATE KEY UPDATE
  `categorie_id`=VALUES(`categorie_id`), `libelle`=VALUES(`libelle`),
  `incidence`=VALUES(`incidence`), `reference_texte`=VALUES(`reference_texte`),
  `mode_passation_code`=VALUES(`mode_passation_code`),
  `compte_auditabilite`=VALUES(`compte_auditabilite`),
  `obligatoire`=VALUES(`obligatoire`), `sort`=VALUES(`sort`);

-- 2.d — Pièces A1 (organes + planification), § 3.2.1.1 du référentiel
INSERT INTO `pm_pieces_obligatoires`
  (`id`,`categorie_id`,`code`,`libelle`,`incidence`,`reference_texte`,`mode_passation_code`,`compte_auditabilite`,`obligatoire`,`sort`) VALUES
 (200,4,'ARRETE_PRMP','Arrêté de création/organisation de la PRMP','directe','Décision n°2022-001/ARMP du 31 mars 2022',NULL,1,1,1),
 (201,4,'ARRETE_CCMP','Arrêté de création/organisation de la CCMP','directe','Décision n°2022-001/ARMP du 31 mars 2022',NULL,1,1,2),
 (202,4,'ARRETE_SP_PRMP','Arrêté de création/organisation du SP-PRMP','directe','Décision n°2022-001/ARMP du 31 mars 2022',NULL,1,1,3),
 (203,4,'NOMINATION_PRMP','Acte de nomination de la PRMP','directe','Article 11 de la loi n°2020-26',NULL,1,1,4),
 (204,4,'NOMINATION_CHEF_CCMP','Acte de nomination du Chef de la CCMP','directe','Articles 4 et 5 du décret n°2020-597',NULL,1,1,5),
 (205,4,'NOMINATION_MEMBRES_CCMP','Actes de nomination des autres membres de la CCMP','directe','Article 3 du décret n°2020-597',NULL,1,1,6),
 (206,4,'NOMINATION_CHEF_SP_PRMP','Acte de nomination du Chef du SP-PRMP','directe','Articles 7 et 8 du décret n°2020-596',NULL,1,1,7),
 (207,4,'RAPPORTS_PRMP','Rapports périodiques et annuel de la PRMP','directe','Article 1er, point 11 du décret n°2020-596',NULL,1,1,8),
 (208,4,'RAPPORTS_CCMP','Rapports périodiques et annuel de la CCMP','directe','Article 2, point 7 du décret n°2020-597',NULL,1,1,9),
 (209,4,'PREUVE_TRANSMISSION_ARMP','Preuves de transmission des rapports PRMP/CCMP à l''ARMP','sans_incidence','Décrets n°2020-596 et n°2020-597',NULL,1,0,10),
 (210,4,'PV_REUNIONS_STATUTAIRES','PV des réunions statutaires PRMP/CCMP','sans_incidence','Décrets n°2020-596 et n°2020-597',NULL,1,0,11),
 (211,4,'REGISTRE_TRANSMISSION','Registre/cahier de transmission des phases de passation','directe','Article 1, point 12 du décret n°2020-596',NULL,1,1,12),
 (212,4,'ACTES_REMPLACEMENT','Actes de remplacement des membres PRMP/CCMP','sans_incidence','Art. 8 et 10 décret 2020-596 · art. 6 décret 2020-597',NULL,1,0,13),
 (213,4,'ORGANIGRAMME_AC','Organigramme de l''autorité contractante','sans_incidence',NULL,NULL,0,0,14),
 (220,5,'PPM_INITIAL','Plan de passation des marchés initial','directe','Article 24 de la loi n°2020-26',NULL,1,1,1),
 (221,5,'PV_VALIDATION_PPM','PV de validation du PPM par la CCMP','directe','Art. 24 loi 2020-26 · art. 2 décret 2020-597',NULL,1,1,2),
 (222,5,'PREUVE_PUBLICATION_PPM','Preuve de publication du PPM sur SIGMaP','directe','Article 24 de la loi n°2020-26',NULL,1,1,3),
 (223,5,'PPM_REVISIONS','Révisions successives du PPM','sans_incidence','Article 24 de la loi n°2020-26',NULL,1,0,4),
 (224,5,'VALIDATION_REVISIONS_PPM','Validation préalable des révisions par la CCMP','directe','Article 2 du décret n°2020-597',NULL,1,1,5),
 (225,5,'PUBLICATION_REVISIONS_PPM','Preuve de publication des révisions validées sur SIGMaP','directe','Article 24 de la loi n°2020-26',NULL,1,1,6),
 (226,5,'BUDGET_APPROUVE','Acte d''approbation du budget (point de départ du délai de 10 jours)','sans_incidence','Article 24 de la loi n°2020-26',NULL,1,0,7)
ON DUPLICATE KEY UPDATE
  `categorie_id`=VALUES(`categorie_id`), `libelle`=VALUES(`libelle`),
  `incidence`=VALUES(`incidence`), `reference_texte`=VALUES(`reference_texte`),
  `compte_auditabilite`=VALUES(`compte_auditabilite`),
  `obligatoire`=VALUES(`obligatoire`), `sort`=VALUES(`sort`);

-- ----------------------------------------------------------------------------
-- 3) BARÈME « DISPONIBILITÉ DES PIÈCES OBLIGATOIRES »
--    (§2.4 grille n°1 : X = % d'ABSENCE des pièces)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_grille_appreciation_disponibilite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `borne_min` DECIMAL(5,2) DEFAULT NULL COMMENT 'NULL = pas de borne basse',
  `operateur_min` VARCHAR(5) DEFAULT NULL COMMENT '>|>=  (appliqué à borne_min)',
  `borne_max` DECIMAL(5,2) DEFAULT NULL COMMENT 'NULL = pas de borne haute',
  `operateur_max` VARCHAR(5) DEFAULT NULL COMMENT '<|<=  (appliqué à borne_max)',
  `appreciation` VARCHAR(50) NOT NULL,
  `couleur` VARCHAR(20) DEFAULT 'gray',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Grille §2.4-1 : % d''absence des pièces obligatoires → appréciation';

INSERT INTO `pm_grille_appreciation_disponibilite`
  (`id`,`borne_min`,`operateur_min`,`borne_max`,`operateur_max`,`appreciation`,`couleur`,`actif`,`sort`) VALUES
 (1,NULL,NULL,10.00,'<=','Très satisfaisante','green',1,1),
 (2,10.00,'>',20.00,'<=','Satisfaisante','blue',1,2),
 (3,20.00,'>',40.00,'<=','Moyenne','orange',1,3),
 (4,40.00,'>',60.00,'<=','Faible','orange',1,4),
 (5,60.00,'>',80.00,'<=','Insatisfaisante','red',1,5),
 (6,80.00,'>',NULL,NULL,'Très insatisfaisante','red',1,6)
ON DUPLICATE KEY UPDATE
  `borne_min`=VALUES(`borne_min`), `operateur_min`=VALUES(`operateur_min`),
  `borne_max`=VALUES(`borne_max`), `operateur_max`=VALUES(`operateur_max`),
  `appreciation`=VALUES(`appreciation`), `couleur`=VALUES(`couleur`),
  `actif`=VALUES(`actif`), `sort`=VALUES(`sort`);

-- ----------------------------------------------------------------------------
-- 4) BARÈMES D'APPRÉCIATION GÉNÉRIQUES  (§2.4 grilles n°2, 3, 4 et 5)
--    Une seule table, discriminée par `type_bareme` :
--      CONFORMITE   → performance d'une étape de la procédure (X = % conformité)
--      AUDITABILITE → marché auditable / non auditable (X = taux de complétude)
--      COLLUSION    → indice d'alerte de collusion
--      CONFLIT      → indice d'alerte de conflit d'intérêt
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_baremes_appreciation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_bareme` VARCHAR(20) NOT NULL COMMENT 'CONFORMITE|AUDITABILITE|COLLUSION|CONFLIT',
  `borne_min` DECIMAL(5,2) DEFAULT NULL,
  `operateur_min` VARCHAR(5) DEFAULT NULL COMMENT '>|>=',
  `borne_max` DECIMAL(5,2) DEFAULT NULL,
  `operateur_max` VARCHAR(5) DEFAULT NULL COMMENT '<|<=',
  `appreciation` VARCHAR(255) NOT NULL,
  `code_resultat` VARCHAR(30) DEFAULT NULL COMMENT 'Valeur machine (OK/KO, TRES_SATISFAISANTE…)',
  `est_conforme` TINYINT(1) DEFAULT NULL COMMENT '1 = palier valant conformité (≥ 80 %)',
  `couleur` VARCHAR(20) DEFAULT 'gray',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bareme_type` (`type_bareme`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Barèmes §2.4 : conformité, auditabilité, indices d''alerte';

INSERT INTO `pm_baremes_appreciation`
  (`id`,`type_bareme`,`borne_min`,`operateur_min`,`borne_max`,`operateur_max`,`appreciation`,`code_resultat`,`est_conforme`,`couleur`,`sort`) VALUES
 -- Grille n°2 · performance des étapes de la procédure (X = taux de conformité)
 (1,'CONFORMITE',90.00,'>',100.00,'<=','Très satisfaisante','TRES_SATISFAISANTE',1,'green',1),
 (2,'CONFORMITE',80.00,'>',90.00,'<=','Satisfaisante','SATISFAISANTE',1,'blue',2),
 (3,'CONFORMITE',60.00,'>',80.00,'<=','Moyenne','MOYENNE',0,'orange',3),
 (4,'CONFORMITE',NULL,NULL,60.00,'<','Insatisfaisante','INSATISFAISANTE',0,'red',4),
 -- Grille n°3 · auditabilité (X = taux de complétude documentaire NCF)
 (10,'AUDITABILITE',80.00,'>=',NULL,NULL,'Marché auditable (OK)','OK',1,'green',1),
 (11,'AUDITABILITE',NULL,NULL,80.00,'<','Marché non auditable (KO)','KO',0,'red',2),
 -- Grille n°4 · indice d'alerte de collusion
 (20,'COLLUSION',0.00,'>=',30.00,'<','Absence d''indices de collusion ou présence d''indices isolés (ne permettant pas de conclure à un risque significatif)','AUCUN',NULL,'green',1),
 (21,'COLLUSION',30.00,'>=',60.00,'<','Présence d''indices modérés','MODERE',NULL,'orange',2),
 (22,'COLLUSION',60.00,'>=',100.00,'<=','Présence d''indices nombreux ou récurrents','ELEVE',NULL,'red',3),
 -- Grille n°5 · indice d'alerte de conflit d'intérêt
 (30,'CONFLIT',0.00,'>=',30.00,'<','Absence d''indices de conflit d''intérêt','AUCUN',NULL,'green',1),
 (31,'CONFLIT',30.00,'>=',60.00,'<','Présence d''indices modérés - situation à surveiller','MODERE',NULL,'orange',2),
 (32,'CONFLIT',60.00,'>=',100.00,'<=','Présence d''indices sérieux de conflit d''intérêt - vigilance requise','ELEVE',NULL,'red',3)
ON DUPLICATE KEY UPDATE
  `type_bareme`=VALUES(`type_bareme`), `borne_min`=VALUES(`borne_min`),
  `operateur_min`=VALUES(`operateur_min`), `borne_max`=VALUES(`borne_max`),
  `operateur_max`=VALUES(`operateur_max`), `appreciation`=VALUES(`appreciation`),
  `code_resultat`=VALUES(`code_resultat`), `est_conforme`=VALUES(`est_conforme`),
  `couleur`=VALUES(`couleur`), `sort`=VALUES(`sort`);

-- ----------------------------------------------------------------------------
-- 5) MODALITÉS D'APPRÉCIATION ET PONDÉRATION LINÉAIRE  (§2.4)
--    Note pondérée (%) = Σ(nb × poids) / nb total (hors « Non applicable »)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_modalites_appreciation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL COMMENT 'respecte | partiellement_respecte | non_respecte | na',
  `libelle` VARCHAR(100) NOT NULL,
  `poids` DECIMAL(5,2) NOT NULL COMMENT 'Pondération linéaire : 100 | 50 | 0',
  `exclu_du_calcul` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = N/A, retiré du dénominateur',
  `couleur` VARCHAR(20) DEFAULT 'gray',
  `icone` VARCHAR(60) DEFAULT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Modalités normées d''appréciation + pondération linéaire (§2.4)';

INSERT INTO `pm_modalites_appreciation`
  (`id`,`code`,`libelle`,`poids`,`exclu_du_calcul`,`couleur`,`icone`,`actif`,`sort`) VALUES
 (1,'respecte','Respecté',100.00,0,'green','ti ti-circle-check',1,1),
 (2,'partiellement_respecte','Partiellement respecté',50.00,0,'orange','ti ti-circle-half-2',1,2),
 (3,'non_respecte','Non respecté',0.00,0,'red','ti ti-circle-x',1,3),
 (4,'na','Non applicable',0.00,1,'gray','ti ti-circle-minus',1,4)
ON DUPLICATE KEY UPDATE
  `libelle`=VALUES(`libelle`), `poids`=VALUES(`poids`),
  `exclu_du_calcul`=VALUES(`exclu_du_calcul`), `couleur`=VALUES(`couleur`),
  `icone`=VALUES(`icone`), `actif`=VALUES(`actif`), `sort`=VALUES(`sort`);

-- ----------------------------------------------------------------------------
-- 6) PARAMÈTRES NUMÉRIQUES DE L'AUDIT  (seuils du référentiel)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_parametres_audit` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `libelle` VARCHAR(255) NOT NULL,
  `valeur` DECIMAL(8,2) NOT NULL,
  `unite` VARCHAR(20) DEFAULT '%',
  `description` TEXT DEFAULT NULL,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Paramètres numériques configurables (seuils, plafonds…) hors grilles';

-- L'unité peut être une base de calcul (« % de la valeur globale ») : 20 → 40
ALTER TABLE `pm_parametres_audit` MODIFY `unite` VARCHAR(40) DEFAULT '%';

INSERT INTO `pm_parametres_audit` (`id`,`code`,`libelle`,`valeur`,`unite`,`description`,`sort`) VALUES
 (1,'SEUIL_AUDITABILITE','Seuil d''auditabilité',80.00,'%','Taux de complétude documentaire (pièces NCF présentes / attendues) en dessous duquel le marché est déclaré non auditable (§2.4 grille n°3).',1),
 (2,'SEUIL_CONFORMITE','Seuil de conformité d''un marché',80.00,'%','Taux moyen de conformité pondéré à atteindre pour qu''un marché soit déclaré conforme (§2.3.1).',2),
 (3,'POIDS_RESPECTE','Pondération « Respecté »',100.00,'%','Méthode de pondération linéaire (§2.4).',3),
 (4,'POIDS_PARTIEL','Pondération « Partiellement respecté »',50.00,'%','Méthode de pondération linéaire (§2.4).',4),
 (5,'POIDS_NON_RESPECTE','Pondération « Non respecté »',0.00,'%','Méthode de pondération linéaire (§2.4).',5),
 (6,'ECH_TAUX_NOMBRE','Échantillon — % du nombre total de marchés',50.00,'%','Seuil indicatif cumulatif (§3.2.2.1). Tout écart doit être justifié dans le rapport.',6),
 (7,'ECH_TAUX_MONTANT','Échantillon — % du montant total des marchés',50.00,'%','Seuil indicatif cumulatif (§3.2.2.1).',7),
 (8,'ECH_MIN_MARCHES','Échantillon — nombre minimal de marchés',10.00,'marchés','Si le portefeuille compte 10 marchés ou moins, la totalité est auditée (§3.2.2.1).',8),
 (9,'ALERTE_SEUIL_MODERE','Indice d''alerte — seuil « modéré »',30.00,'%','Bas de la plage « indices modérés » pour collusion et conflit d''intérêt (§2.4).',9),
 (10,'ALERTE_SEUIL_ELEVE','Indice d''alerte — seuil « élevé »',60.00,'%','Bas de la plage « indices nombreux/sérieux » (§2.4).',10),
 (11,'ALERTE_POIDS_ELEVEE','Indice d''alerte — poids d''un signal « Élevée »',100.00,'%','Formule : [(nb Élevée×100)+(nb Moyenne×50)+(nb Faible×0)] / nb critères.',11),
 (12,'ALERTE_POIDS_MOYENNE','Indice d''alerte — poids d''un signal « Moyenne »',50.00,'%',NULL,12),
 (13,'ALERTE_POIDS_FAIBLE','Indice d''alerte — poids d''un signal « Faible »',0.00,'%',NULL,13),
 (14,'PLAFOND_AVENANT','Plafond d''un avenant',30.00,'% du marché de base','Article 100 de la loi n°2020-26 : au-delà, nouveau marché ou résiliation.',14),
 (15,'PLAFOND_AVENANT_OS','Plafond régularisable par ordre de service',10.00,'% du marché de base','Article 100 de la loi n°2020-26 : régularisation par avenant.',15),
 (16,'PLAFOND_PENALITES','Plafond des pénalités de retard',10.00,'% du montant TTC','Article 114 de la loi n°2020-26 : au-delà, résiliation du marché.',16),
 (17,'PLAFOND_SOUS_TRAITANCE','Plafond de sous-traitance',40.00,'% de la valeur globale','Article 101 al. 4 de la loi n°2020-26.',17),
 (18,'PLAFOND_GAG','Plafond des marchés de gré à gré',10.00,'% du montant total','Article 35 de la loi n°2020-26.',18),
 (19,'DELAI_PAIEMENT','Délai maximal de paiement',60.00,'jours','Article 110 al. 5 de la loi n°2020-26 (à compter de la réception de la facture).',19),
 (20,'DELAI_GARANTIE_BONNE_EXEC','Délai de constitution de la garantie de bonne exécution',30.00,'jours','Article 91 al. 6 de la loi n°2020-26 (ou avant le premier paiement).',20),
 (21,'DUREE_MAX_AJOURNEMENT','Durée maximale d''ajournement',3.00,'mois','Article 109 al. 3 de la loi n°2020-26 : au-delà, résiliation à la demande du titulaire.',21),
 (22,'DELAI_AVIS_DNCMP_AJOURNEMENT','Délai d''avis DNCMP en cas d''ajournement',1.00,'mois','Article 109 al. 2 de la loi n°2020-26.',22),
 (23,'CORRECTION_MAX_OFFRE','Correction admissible sur le montant d''une offre',10.00,'%','Référentiel §A8 (DRP/DC) : corrections arithmétiques dans la limite de ±10 %.',23),
 (24,'AMI_MIN_CANDIDATS','Manifestation d''intérêt — nombre minimal de présélectionnés',5.00,'candidats','Article 36 de la loi n°2020-26 (5 à 8 candidats).',24),
 (25,'AMI_MAX_CANDIDATS','Manifestation d''intérêt — nombre maximal de présélectionnés',8.00,'candidats','Article 36 de la loi n°2020-26.',25),
 (26,'QUORUM_SIGNATURE_RAPPORT','Quorum de signature du rapport d''évaluation',60.00,'% des membres','Article 11 du décret n°2020-596 : au moins 3/5 des membres de la COE.',26),
 (27,'DC_MIN_EVALUATEURS','Demande de cotations — nombre minimal d''évaluateurs',2.00,'personnes','Article 9 du décret n°2020-605.',27)
ON DUPLICATE KEY UPDATE
  `libelle`=VALUES(`libelle`), `valeur`=VALUES(`valeur`), `unite`=VALUES(`unite`),
  `description`=VALUES(`description`), `sort`=VALUES(`sort`);

-- ----------------------------------------------------------------------------
-- 7) CONDITIONS CUMULATIVES DE CONFORMITÉ D'UN MARCHÉ  (§2.3.1 / Module 12)
--    Un marché n'est conforme que si TOUTES les conditions actives sont
--    satisfaites. Une seule condition en échec ⇒ marché non conforme.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pm_conditions_conformite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `libelle` VARCHAR(300) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `portee` VARCHAR(20) NOT NULL DEFAULT 'marche' COMMENT 'marche | passation | execution',
  `parametre_code` VARCHAR(50) DEFAULT NULL COMMENT 'FK logique → pm_parametres_audit.code',
  `bloquante` TINYINT(1) NOT NULL DEFAULT 1,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `sort` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Conditions cumulatives de conformité d''un marché (§2.3.1)';

INSERT INTO `pm_conditions_conformite`
  (`id`,`code`,`libelle`,`description`,`portee`,`parametre_code`,`bloquante`,`actif`,`sort`) VALUES
 (1,'INSCRIT_PPMP','Inscription au PPMP régulièrement validé et publié','Le marché figure au Plan de Passation des Marchés Publics validé par la CCMP et publié sur SIGMaP (art. 24 loi 2020-26).','marche',NULL,1,1,1),
 (2,'AUDITABLE','Marché auditable (complétude documentaire ≥ 80 %)','Taux de complétude des pièces à incidence directe supérieur ou égal au seuil d''auditabilité.','marche','SEUIL_AUDITABILITE',1,1,2),
 (3,'PIECES_NCF_COMPLETES','Présence de toutes les pièces à incidence directe (NCF)','Aucune pièce dont l''absence entraîne la non-conformité ne manque, même si le marché est jugé auditable.','marche',NULL,1,1,3),
 (4,'CONFORMITE_PASSATION','Taux moyen de conformité pondéré de la passation ≥ 80 %','Moyenne des taux pondérés des rubriques : DAC, information/transparence, réception-ouverture-attribution, avis des organes de contrôle, approbation.','passation','SEUIL_CONFORMITE',1,1,4),
 (5,'CONFORMITE_EXECUTION','Taux de conformité pondéré de l''exécution ≥ 80 %','Gestion administrative générale et particulière de l''exécution (avenants, résiliations).','execution','SEUIL_CONFORMITE',1,1,5),
 (6,'AUCUN_ECART_MAJEUR','Absence d''écart majeur','Un seul écart majeur (annexe A22) rend le marché non conforme, quel que soit le taux obtenu.','marche',NULL,1,1,6),
 (7,'DELAI_DEPOT_OFFRES','Respect du délai réglementaire de préparation et de dépôt des offres','Article 54 de la loi n°2020-26 · article 15 du décret n°2020-605 pour les procédures simplifiées.','passation',NULL,1,1,7),
 (8,'APPROBATION_REGULIERE','Approbation régulière par l''autorité compétente','Un marché non approuvé est juridiquement nul et de nul effet (art. 22, 84 et 85 loi 2020-26).','passation',NULL,1,1,8),
 (9,'MATERIALITE_CONFORME','Matérialité conforme','Les prestations exécutées correspondent aux engagements contractuels et la réalité physique/fonctionnelle est cohérente avec les pièces examinées.','execution',NULL,1,1,9)
ON DUPLICATE KEY UPDATE
  `libelle`=VALUES(`libelle`), `description`=VALUES(`description`),
  `portee`=VALUES(`portee`), `parametre_code`=VALUES(`parametre_code`),
  `bloquante`=VALUES(`bloquante`), `actif`=VALUES(`actif`), `sort`=VALUES(`sort`);

-- ----------------------------------------------------------------------------
-- 8) GRAVITÉ DES ÉCARTS SUR LES ITEMS DE GRILLE  (annexes A22 / A23)
--    Ajout de colonnes à pm_grilles_verification_items : un item peut porter
--    un écart MAJEUR (⇒ non-conformité automatique) ou MODÉRÉ (⇒ risque
--    signalé, marché conforme).
-- ----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pm_grilles_verification_items'
      AND COLUMN_NAME = 'gravite_ecart') = 0,
  'ALTER TABLE `pm_grilles_verification_items`
     ADD COLUMN `gravite_ecart` VARCHAR(10) DEFAULT NULL
       COMMENT ''majeur (A22) | modere (A23) | NULL = simple insuffisance'' AFTER `type_reponse`,
     ADD COLUMN `reference_ecart` VARCHAR(30) DEFAULT NULL
       COMMENT ''Référence dans la matrice A22/A23'' AFTER `gravite_ecart`,
     ADD KEY `idx_item_gravite` (`gravite_ecart`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 9) VERSION DU RÉFÉRENTIEL → déclenche la synchro de TOUS les tenants
--    (ReferentielSyncService::syncIfNeeded compare cette version à
--     tenants.pm_referentiel_version_synced au login)
-- ----------------------------------------------------------------------------
INSERT INTO `pm_referentiel_versions` (`id`,`version`) VALUES (1,1)
ON DUPLICATE KEY UPDATE `version` = `version` + 1;

SELECT 'Paramètres ARMP 2026 installés dans ddmparam' AS resultat,
       (SELECT COUNT(*) FROM pm_pieces_obligatoires)      AS pieces,
       (SELECT COUNT(*) FROM pm_baremes_appreciation)     AS baremes,
       (SELECT COUNT(*) FROM pm_modalites_appreciation)   AS modalites,
       (SELECT COUNT(*) FROM pm_parametres_audit)         AS parametres,
       (SELECT COUNT(*) FROM pm_conditions_conformite)    AS conditions,
       (SELECT version FROM pm_referentiel_versions)      AS nouvelle_version;
