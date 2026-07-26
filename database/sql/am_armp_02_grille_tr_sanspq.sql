-- ============================================================================
-- AM · ARMP 2026 — FICHIER 02 · Grille A6-AOO-TR-SANSPQ
-- DAO sans préqualification (Marchés de travaux) — 71 items verbatim.
--   mysql -u root -proot ddmparam < am_armp_02_grille_tr_sanspq.sql
-- ============================================================================
SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS `_tmp_items`;
CREATE TEMPORARY TABLE `_tmp_items` (
  `grille_code` VARCHAR(30) NOT NULL,
  `numero` VARCHAR(10) NOT NULL,
  `libelle` VARCHAR(2000) NOT NULL,
  `sort` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_tmp_items` (`grille_code`,`numero`,`libelle`,`sort`) VALUES
('A6-AOO-TR-SANSPQ','1','Conformité du Dossier d''Appel d''Offres (DAC) utilisé au DAC-type concerné proposé par l''ARMP, conformément à l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',1),
('A6-AOO-TR-SANSPQ','2','Conformité de l''exhaustivité des documents composant le DAC au regard du DAC-type concerné proposé par l''ARMP, conformément aux documents types en vigueur.',2),
('A6-AOO-TR-SANSPQ','3','Conformité de la mention de la référence SIGMaP de l''appel d''offres dans le Dossier d''Appel d''Offres, conformément aux documents types en vigueur.',3),
('A6-AOO-TR-SANSPQ','4','Conformité de l''avis d''appel d''offres aux mentions obligatoires prévues par l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et par le modèle d''avis des documents types en vigueur.',4),
('A6-AOO-TR-SANSPQ','5','Conformité de la cohérence des mentions obligatoires de l''avis d''appel d''offres avec les autres parties du Dossier d''Appel d''Offres, conformément à l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et au modèle d''avis des documents types en vigueur.',5),
('A6-AOO-TR-SANSPQ','6','Conformité de la signature de l''avis d''appel d''offres par la Personne Responsable des Marchés Publics (PRMP) ou son intérimaire, conformément à l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et au modèle d''avis des documents types en vigueur.',6),
('A6-AOO-TR-SANSPQ','7','Conformité du renseignement de toutes les rubriques des Données Particulières de l''Appel d''Offres (DPAO) sur la base des Instructions aux Candidats, conformément aux documents types en vigueur.',7),
('A6-AOO-TR-SANSPQ','8','Conformité de l''insertion dans les Données Particulières de la référence d''identification de l''avis d''appel d''offres (numéro, timbre-date), conformément aux documents types en vigueur.',8),
('A6-AOO-TR-SANSPQ','9','Conformité de l''indication dans les Données Particulières de la date, de l''heure limite et du lieu de dépôt des offres, conformément aux documents types en vigueur.',9),
('A6-AOO-TR-SANSPQ','10','Conformité de l''indication dans les Données Particulières de la date, de l''heure et du lieu d''ouverture des plis, conformément aux documents types en vigueur.',10),
('A6-AOO-TR-SANSPQ','11','Conformité de la description des prestations objet de l''appel d''offres dans les Données Particulières, conformément aux documents types en vigueur.',11),
('A6-AOO-TR-SANSPQ','12','Conformité de l''indication de la source de financement dans les Données Particulières, conformément aux documents types en vigueur.',12),
('A6-AOO-TR-SANSPQ','13','Conformité de l''indication du délai d''exécution du marché dans les Données Particulières, conformément aux documents types en vigueur.',13),
('A6-AOO-TR-SANSPQ','14','Conformité de la prescription des autres documents à joindre à l''offre dans les Données Particulières, conformément à la réglementation et aux documents types en vigueur.',14),
('A6-AOO-TR-SANSPQ','15','Conformité de la prise en compte des variantes de délai ou des variantes techniques, lorsque prévu, dans les Données Particulières, conformément aux documents types en vigueur.',15),
('A6-AOO-TR-SANSPQ','16','Conformité de la fixation dans les Données Particulières des délais au plus tôt et au plus tard en cas de variantes de délai, conformément aux documents types en vigueur.',16),
('A6-AOO-TR-SANSPQ','17','Conformité de la fixation dans les Données Particulières des aspects techniques objet de variantes, en cas de variantes techniques, conformément aux documents types en vigueur.',17),
('A6-AOO-TR-SANSPQ','18','Conformité du délai accordé aux soumissionnaires pour la préparation des offres dans l''avis d''appel d''offres aux dispositions de l''article 54 de la loi n° 2020-26 du 29 septembre 2020.',18),
('A6-AOO-TR-SANSPQ','19','Conformité du montant de la garantie de soumission fixé dans les Données Particulières aux dispositions des articles 49 et 68 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',19),
('A6-AOO-TR-SANSPQ','20','Conformité de l''exigence dans les Données Particulières de la lettre de déclaration de garantie pour les micros, petites et moyennes entreprises béninoises, conformément aux articles 49 et 68 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',20),
('A6-AOO-TR-SANSPQ','21','Conformité de la fixation par lot dans les Données Particulières des critères de qualification, du délai d''exécution et du montant de la garantie de soumission, en cas d''allotissement, conformément à l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',21),
('A6-AOO-TR-SANSPQ','22','Conformité des conditions d''attribution des lots fixées dans les Données Particulières aux dispositions de l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',22),
('A6-AOO-TR-SANSPQ','23','Conformité du délai de validité des offres fixé dans l''avis d''appel d''offres aux dispositions de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',23),
('A6-AOO-TR-SANSPQ','24','Conformité des conditions de présentation des plis fixées dans les Données Particulières aux dispositions de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',24),
('A6-AOO-TR-SANSPQ','25','Conformité des critères techniques (spécifications techniques et fonctionnelles, conditions techniques, environnementales et sociales) dont le non-respect constitue un motif de rejet des offres, tels que fixés dans les Données Particulières, aux dispositions de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur, en veillant à ce qu''ils ne reprennent pas les pièces et documents de conformité technique cités à l''annexe A.',25),
('A6-AOO-TR-SANSPQ','26','Conformité du taux de marge de préférence communautaire fixé dans les Données Particulières aux dispositions de l''article 75 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',26),
('A6-AOO-TR-SANSPQ','27','Conformité du taux de marge de préférence spécifique aux collectivités fixé dans les Données Particulières aux dispositions de l''article 76 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',27),
('A6-AOO-TR-SANSPQ','28','Conformité du taux de marge de préférence spécifique aux micros, petites et moyennes entreprises fixé dans les Données Particulières aux dispositions de l''article 77 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',28),
('A6-AOO-TR-SANSPQ','29','Conformité du taux de marge de préférence applicable à la sous-traitance aux micros, petites et moyennes entreprises fixé dans les Données Particulières aux dispositions de l''article 77 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',29),
('A6-AOO-TR-SANSPQ','30','Conformité des taux de variation des quantités à l''attribution du marché fixés dans les Données Particulières aux dispositions de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',30),
('A6-AOO-TR-SANSPQ','31','Conformité des modalités d''exécution des accords-cadres fixées dans les Données Particulières aux dispositions des articles 40 et 41 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',31),
('A6-AOO-TR-SANSPQ','32','Conformité des critères d''ajustement des délais d''exécution fixés dans les critères d''évaluation et de qualification aux documents types en vigueur.',32),
('A6-AOO-TR-SANSPQ','33','Conformité des critères d''ajustement relatifs aux achats durables fixés dans les critères d''évaluation et de qualification aux documents types en vigueur.',33),
('A6-AOO-TR-SANSPQ','34','Conformité des autres critères d''ajustement spécifiques fixés dans les critères d''évaluation et de qualification aux documents types en vigueur.',34),
('A6-AOO-TR-SANSPQ','35','Conformité des critères d''ajustement des variantes techniques fixés dans les critères d''évaluation et de qualification aux documents types en vigueur.',35),
('A6-AOO-TR-SANSPQ','36','Conformité des critères d''expérience des anciennes entreprises dans les critères d''évaluation et de qualification aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',36),
('A6-AOO-TR-SANSPQ','37','Conformité des critères de chiffre d''affaires des anciennes entreprises dans les critères d''évaluation et de qualification aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',37),
('A6-AOO-TR-SANSPQ','38','Conformité des critères de capacité financière (liquidité requise) dans les critères d''évaluation et de qualification aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',38),
('A6-AOO-TR-SANSPQ','39','Conformité des critères de matériel essentiel requis dans les critères d''évaluation et de qualification à l''objet et à la complexité du marché, conformément aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',39),
('A6-AOO-TR-SANSPQ','40','Conformité des critères de personnel clé requis pour les anciennes entreprises dans les critères d''évaluation et de qualification à l''objet et à la complexité du marché, conformément aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',40),
('A6-AOO-TR-SANSPQ','41','Conformité des expériences requises pour le personnel d''encadrement des entreprises naissantes et de celles n''ayant pas encore trois (03) années d''exercice par rapport à celles des anciennes entreprises, dans les critères d''évaluation et de qualification, conformément aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',41),
('A6-AOO-TR-SANSPQ','42','Conformité des expériences requises pour le personnel clé des entreprises naissantes et de celles n''ayant pas encore trois (03) années d''exercice par rapport à celles des anciennes entreprises, dans les critères d''évaluation et de qualification, conformément aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',42),
('A6-AOO-TR-SANSPQ','43','Conformité du montant du risque professionnel couvert par l''assurance des risques professionnels pour les entreprises naissantes, dans les critères d''évaluation et de qualification, conformément aux articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',43),
('A6-AOO-TR-SANSPQ','44','Conformité de l''exhaustivité et de la conformité des formulaires de soumission du Dossier d''Appel d''Offres à l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',44),
('A6-AOO-TR-SANSPQ','45','Conformité des spécifications techniques des travaux du Dossier d''Appel d''Offres à l''article 46 et aux articles 50 à 52 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur, en veillant à ce qu''elles soient claires, précises et non orientées.',45),
('A6-AOO-TR-SANSPQ','46','Conformité des normes en matière de qualité des matériaux et de construction des ouvrages aux articles 46 et 50 à 52 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',46),
('A6-AOO-TR-SANSPQ','47','Conformité de l''exhaustivité des rubriques du Cahier des Clauses Administratives Particulières (CCAP), renseignées en cohérence avec les Clauses Générales, conformément à l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',47),
('A6-AOO-TR-SANSPQ','48','Conformité des dispositions relatives au règlement des litiges prescrites dans le Cahier des Clauses Administratives Particulières (CCAP) à l''article 120 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',48),
('A6-AOO-TR-SANSPQ','49','Conformité des modalités de calcul de la rémunération des travaux en régie fixées dans le Cahier des Clauses Administratives Particulières (CCAP), conformément aux documents types en vigueur.',49),
('A6-AOO-TR-SANSPQ','50','Conformité du pourcentage maximum des travaux en régie par rapport au montant initial du marché fixé dans le Cahier des Clauses Administratives Particulières (CCAP), conformément aux documents types en vigueur.',50),
('A6-AOO-TR-SANSPQ','51','Conformité du mode de calcul des acomptes sur approvisionnement fixé dans le Cahier des Clauses Administratives Particulières (CCAP), conformément aux documents types en vigueur.',51),
('A6-AOO-TR-SANSPQ','52','Conformité de la fixation dans le CCAP du seuil des intempéries constituant un cas de force majeure, conformément aux documents types en vigueur.',52),
('A6-AOO-TR-SANSPQ','53','Conformité de la fixation dans le CCAP du seuil des intempéries (nombre de jours) entraînant une prolongation des délais d''exécution des travaux, conformément aux documents types en vigueur.',53),
('A6-AOO-TR-SANSPQ','54','Conformité de la fixation dans le CCAP du seuil de prolongation des délais d''exécution ouvrant droit à résiliation du marché, conformément aux documents types en vigueur.',54),
('A6-AOO-TR-SANSPQ','55','Conformité de la fixation dans le CCAP de la durée de la période de mobilisation, conformément aux documents types en vigueur.',55),
('A6-AOO-TR-SANSPQ','56','Conformité de la fixation dans le CCAP du délai de soumission du programme d''exécution, conformément aux documents types en vigueur.',56),
('A6-AOO-TR-SANSPQ','57','Conformité de la formule d''actualisation des prix pour les marchés à prix fermes ou de la formule de révision des prix pour les marchés à prix révisables, prescrite dans le CCAP, aux dispositions de l''article 97 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',57),
('A6-AOO-TR-SANSPQ','58','Conformité du montant de l''avance de démarrage fixé dans le CCAP aux dispositions de l''article 111 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',58),
('A6-AOO-TR-SANSPQ','59','Conformité de la méthode ou du rythme de remboursement de l''avance fixé dans le CCAP aux documents types en vigueur.',59),
('A6-AOO-TR-SANSPQ','60','Conformité du taux de la redevance de régulation fixé dans le CCAP aux dispositions de l''article 99 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',60),
('A6-AOO-TR-SANSPQ','61','Conformité du taux de la garantie de bonne exécution fixé dans le CCAP aux dispositions de l''article 91 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',61),
('A6-AOO-TR-SANSPQ','62','Conformité du montant plafond des pénalités fixé dans le CCAP aux dispositions de l''article 113 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',62),
('A6-AOO-TR-SANSPQ','63','Conformité des opérations préalables à la réception provisoire des travaux, fixées dans le CCAP, aux documents types en vigueur.',63),
('A6-AOO-TR-SANSPQ','64','Conformité des conditions d''entrée en vigueur fixées dans le CCAP aux dispositions de l''article 87 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',64),
('A6-AOO-TR-SANSPQ','65','Conformité de l''exhaustivité des informations renseignées dans le modèle de marché avec l''objet de l''Appel d''Offres, le CCAP et les dispositions des articles 46 et 83 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur.',65),
('A6-AOO-TR-SANSPQ','66','Conformité de l''énumération des pièces contractuelles par ordre de priorité aux dispositions de l''article 83 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',66),
('A6-AOO-TR-SANSPQ','67','Conformité des mentions obligatoires du modèle de contrat aux dispositions de l''article 99 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',67),
('A6-AOO-TR-SANSPQ','68','Conformité des conditions d''entrée en vigueur fixées dans le modèle de contrat aux dispositions de l''article 87 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur.',68),
('A6-AOO-TR-SANSPQ','69','Conformité des signataires prescrits dans le modèle de contrat aux dispositions de l''article 85 de la loi n° 2020-26 du 29 septembre 2020, au décret n° 2020-596 du 23 décembre 2020 et aux documents types en vigueur.',69),
('A6-AOO-TR-SANSPQ','70','Conformité de l''intégrité des parties non modifiables du Dossier d''Appel d''Offres par rapport au dossier-type, conformément aux documents types en vigueur.',70),
('A6-AOO-TR-SANSPQ','71','Conformité de la fixation des assurances requises dans le CCAP, aux exigences des documents types en vigueur à la date sous revue.',71);

INSERT INTO `pm_grilles_verification_items`
  (`grille_id`,`numero`,`libelle_controle`,`type_reponse`,`obligatoire`,`actif`,`sort`)
SELECT g.`id`, t.`numero`, t.`libelle`, 'conformite', 1, 1, t.`sort`
FROM `_tmp_items` t JOIN `pm_grilles_verification` g ON g.`code` = t.`grille_code`
ON DUPLICATE KEY UPDATE `libelle_controle`=VALUES(`libelle_controle`), `sort`=VALUES(`sort`), `actif`=1;

SELECT g.code, COUNT(i.id) nb FROM pm_grilles_verification g
LEFT JOIN pm_grilles_verification_items i ON i.grille_id=g.id
WHERE g.code='A6-AOO-TR-SANSPQ' GROUP BY g.code;
