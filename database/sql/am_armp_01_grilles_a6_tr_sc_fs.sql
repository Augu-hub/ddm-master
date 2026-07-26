-- ============================================================================
-- AM · RÉFÉRENTIEL ARMP 2026 — FICHIER 01
-- ITEMS DES GRILLES A6 (DAC) restées vides : Fournitures restreint,
-- Services (sans PQ / avec PQ / restreint), Travaux (sans PQ / avec PQ /
-- restreint). Transcription verbatim des Grilles de vérification (Juin 2026).
--
-- ⚠️ À EXÉCUTER SUR ddmparam :
--     mysql -u root -proot ddmparam < am_armp_01_grilles_a6_tr_sc_fs.sql
--
-- Chaque item est inséré via le CODE de sa grille (indépendant des id).
-- Idempotent : ON DUPLICATE KEY UPDATE sur (grille_id, numero).
-- ============================================================================
SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS `_tmp_items`;
CREATE TEMPORARY TABLE `_tmp_items` (
  `grille_code` VARCHAR(30) NOT NULL,
  `numero` VARCHAR(10) NOT NULL,
  `libelle` VARCHAR(2000) NOT NULL,
  `sort` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────────
-- A6-AOR-FS · Dossier d'appel d'offres restreint (Marchés de fournitures)
-- ────────────────────────────────────────────────────────────────────────
INSERT INTO `_tmp_items` (`grille_code`,`numero`,`libelle`,`sort`) VALUES
('A6-AOR-FS','01','Conformité des mentions obligatoires figurant dans la lettre d''invitation aux exigences de l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et du modèle d''avis des documents types en vigueur à la date sous revue.',1),
('A6-AOR-FS','02','Conformité de la cohérence des mentions obligatoires de la lettre d''invitation avec les autres parties du dossier d''appel d''offres, conformément à l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et au modèle d''avis des documents types en vigueur à la date sous revue.',2),
('A6-AOR-FS','03','Conformité de la désignation des destinataires de la lettre d''invitation aux exigences du modèle d''avis des documents types en vigueur à la date sous revue.',3),
('A6-AOR-FS','04','Conformité de la signature de la lettre d''invitation aux exigences de l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et du modèle d''avis des documents types en vigueur à la date sous revue.',4),
('A6-AOR-FS','05','Conformité de l''exhaustivité des rubriques des données particulières renseignées conformément aux instructions aux candidats, selon les exigences des documents types en vigueur à la date sous revue.',5),
('A6-AOR-FS','06','Conformité de la référence d''identification de l''avis d''appel d''offres aux exigences des documents types en vigueur à la date sous revue.',6),
('A6-AOR-FS','07','Conformité des informations relatives à la date, à l''heure limite et au lieu de dépôt des offres aux exigences des documents types en vigueur à la date sous revue.',7),
('A6-AOR-FS','08','Conformité des informations relatives à la date, à l''heure et au lieu d''ouverture des plis aux exigences des documents types en vigueur à la date sous revue.',8),
('A6-AOR-FS','09','Conformité de la description des prestations objet de l''appel d''offres aux exigences des documents types en vigueur à la date sous revue.',9),
('A6-AOR-FS','10','Conformité de l''indication de la source de financement dans les données particulières aux exigences des documents types en vigueur à la date sous revue.',10),
('A6-AOR-FS','11','Conformité de l''indication du délai d''exécution du marché dans les données particulières aux exigences des documents types en vigueur à la date sous revue.',11),
('A6-AOR-FS','12','Conformité de la prescription des autres documents à joindre à l''offre, aux exigences des documents types en vigueur à la date sous revue.',12),
('A6-AOR-FS','13','Conformité de la fixation de la période d''utilisation des fournitures pour les équipements nécessitant des pièces de rechange, aux exigences des documents types en vigueur à la date sous revue.',13),
('A6-AOR-FS','14','Conformité des précisions sur la prise en compte des variantes de délai ou de variantes techniques, aux exigences des documents types en vigueur à la date sous revue.',14),
('A6-AOR-FS','15','Conformité de la fixation des délais au plus tôt et au plus tard en cas de variantes de délai, aux exigences des documents types en vigueur à la date sous revue.',15),
('A6-AOR-FS','16','Conformité de la fixation des aspects techniques objets de variantes, aux exigences des documents types en vigueur à la date sous revue.',16),
('A6-AOR-FS','17','Conformité de la fixation des conditions de présentation des plis, aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',17),
('A6-AOR-FS','18','Conformité du délai accordé aux soumissionnaires pour la préparation des offres, aux exigences de l''article 54 de la loi n° 2020-26 du 29 septembre 2020.',18),
('A6-AOR-FS','19','Conformité du montant de la garantie de soumission fixé, aux exigences des articles 49 et 68 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',19),
('A6-AOR-FS','20','Conformité de l''exigence de la lettre de déclaration de garantie pour les MPME béninoises, aux exigences des articles 49 et 68 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',20),
('A6-AOR-FS','21','Conformité de la fixation par lot des critères de qualification, du délai d''exécution et du montant de la garantie de soumission, en cas d''allotissement, aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',21),
('A6-AOR-FS','22','Conformité des conditions d''attribution des lots aux exigences de l''article 48 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',22),
('A6-AOR-FS','23','Conformité de la fixation du délai de validité des offres, aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',23),
('A6-AOR-FS','24','Conformité de la fixation des conditions de présentation des plis aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',24),
('A6-AOR-FS','25','Conformité du mode d''évaluation des offres dans les marchés de fournitures aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',25),
('A6-AOR-FS','26','Conformité des critères techniques de rejet des offres aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',26),
('A6-AOR-FS','27','Conformité de la fixation du taux de marge de préférence communautaire aux exigences de l''article 75 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',27),
('A6-AOR-FS','28','Conformité de la fixation du taux de marge de préférence spécifique aux collectivités aux exigences de l''article 76 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',28),
('A6-AOR-FS','29','Conformité de la fixation du taux de marge de préférence spécifique aux MPME aux exigences de l''article 77 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',29),
('A6-AOR-FS','30','Conformité de la fixation du taux de marge de préférence spécifique pour la sous-traitance aux MPME aux exigences de l''article 77 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',30),
('A6-AOR-FS','31','Conformité de la fixation des taux de variation des quantités à l''attribution du marché aux exigences de l''article 49 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',31),
('A6-AOR-FS','32','Conformité des modalités de mise en œuvre en cas d''accord-cadre aux exigences des articles 40 et 41 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',32),
('A6-AOR-FS','33','Conformité des critères d''ajustement des délais d''exécution aux exigences des documents types en vigueur à la date sous revue.',33),
('A6-AOR-FS','34','Conformité des critères d''ajustement relatifs aux achats durables aux exigences des documents types en vigueur à la date sous revue.',34),
('A6-AOR-FS','35','Conformité des autres critères d''ajustement spécifiques aux exigences des documents types en vigueur à la date sous revue.',35),
('A6-AOR-FS','36','Conformité des critères d''ajustement des variantes techniques aux exigences des documents types en vigueur à la date sous revue.',36),
('A6-AOR-FS','37','Conformité des critères d''expérience des anciennes entreprises aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',37),
('A6-AOR-FS','38','Conformité des critères de chiffre d''affaires des anciennes entreprises aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',38),
('A6-AOR-FS','39','Conformité des critères de capacité financière (liquidité requise) aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',39),
('A6-AOR-FS','40','Conformité des critères de matériel essentiel requis aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',40),
('A6-AOR-FS','41','Conformité des critères de personnel clé requis aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',41),
('A6-AOR-FS','42','Conformité des expériences requises pour le personnel d''encadrement des entreprises naissantes aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',42),
('A6-AOR-FS','43','Conformité des expériences requises pour le personnel clé des entreprises naissantes aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',43),
('A6-AOR-FS','44','Conformité du montant du risque professionnel couvert par l''assurance pour les entreprises naissantes aux exigences des articles 49 et 58 à 60 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',44),
('A6-AOR-FS','45','Conformité de l''exigence d''autorisation de fabricant pour les fournitures à impact important en aval, aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',45),
('A6-AOR-FS','46','Conformité de l''exigence de service après-vente pour les fournitures nécessitant un entretien ou une maintenance, aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',46),
('A6-AOR-FS','47','Conformité de l''exhaustivité et de la conformité des formulaires de soumission du DAO aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',47),
('A6-AOR-FS','48','Conformité de la fixation des dates de livraison au plus tôt et au plus tard en cas d''ajustement des délais de livraison, aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',48),
('A6-AOR-FS','49','Conformité de l''exhaustivité de la liste des fournitures, de leur description technique et de leur quantité, aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',49),
('A6-AOR-FS','50','Conformité de la définition de la liste des services connexes, aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',50),
('A6-AOR-FS','51','Conformité des spécifications techniques du DAC en matière de clarté, de précision et de neutralité, aux exigences des articles 46, 50 à 52 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',51),
('A6-AOR-FS','52','Conformité des spécifications techniques indispensables pour l''atteinte des objectifs de la commande publique, aux exigences des articles 46, 50 à 52 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',52),
('A6-AOR-FS','53','Conformité des normes en matière de matériaux et de fabrication des fournitures, aux exigences des articles 46, 50 à 52 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',53),
('A6-AOR-FS','54','Conformité de l''exhaustivité des rubriques renseignées dans le CCAP par référence aux clauses générales, aux exigences de l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',54),
('A6-AOR-FS','55','Conformité des termes du commerce international requis aux Incoterms en vigueur, conformément à l''article 46 de la loi n° 2020-26 du 29 septembre 2020 et aux documents types en vigueur à la date sous revue.',55),
('A6-AOR-FS','56','Conformité des dispositions du règlement des litiges aux exigences de l''article 120 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',56),
('A6-AOR-FS','57','Conformité de la prescription d''une liste exhaustive des documents nécessaires pour la livraison, aux exigences des documents types en vigueur à la date sous revue.',57),
('A6-AOR-FS','58','Conformité de la formule d''actualisation ou de révision des prix prescrite dans le CCAP, aux exigences de l''article 97 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',58),
('A6-AOR-FS','59','Conformité de la fixation du montant de l''avance de démarrage (si requis) dans le CCAP, aux exigences de l''article 111 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',59),
('A6-AOR-FS','60','Conformité de la fixation du taux de la redevance de régulation dans le CCAP, aux exigences de l''article 99 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',60),
('A6-AOR-FS','61','Conformité de la fixation du taux de la garantie de bonne exécution dans le CCAP, aux exigences de l''article 91 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',61),
('A6-AOR-FS','62','Conformité de la fixation du montant plafond des pénalités de retard dans le CCAP, aux exigences de l''article 113 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',62),
('A6-AOR-FS','63','Conformité de l''exhaustivité des informations renseignées dans le modèle de marché, en cohérence avec l''objet de l''appel d''offres, le CCAP et les exigences des articles 46 et 83 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',63),
('A6-AOR-FS','64','Conformité de l''énumération et de l''ordre de priorité des pièces contractuelles, aux exigences de l''article 83 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',64),
('A6-AOR-FS','65','Conformité des mentions obligatoires du modèle de contrat, aux exigences de l''article 99 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',65),
('A6-AOR-FS','66','Conformité des conditions d''entrée en vigueur fixées dans le modèle de contrat, aux exigences de l''article 87 de la loi n° 2020-26 du 29 septembre 2020 et des documents types en vigueur à la date sous revue.',66),
('A6-AOR-FS','67','Conformité des signataires du modèle de contrat aux exigences de l''article 85 de la loi n° 2020-26 du 29 septembre 2020, du décret n° 2020-596 du 23 décembre 2020 et des documents types en vigueur à la date sous revue.',67),
('A6-AOR-FS','68','Conformité du respect de l''intégrité des parties non modifiables du DAO au regard des exigences des documents types en vigueur à la date sous revue.',68);

-- Insertion générique dans le catalogue central
INSERT INTO `pm_grilles_verification_items`
  (`grille_id`,`numero`,`libelle_controle`,`type_reponse`,`obligatoire`,`actif`,`sort`)
SELECT g.`id`, t.`numero`, t.`libelle`, 'conformite', 1, 1, t.`sort`
FROM `_tmp_items` t
JOIN `pm_grilles_verification` g ON g.`code` = t.`grille_code`
ON DUPLICATE KEY UPDATE
  `libelle_controle` = VALUES(`libelle_controle`),
  `sort` = VALUES(`sort`), `actif` = 1;

TRUNCATE `_tmp_items`;

SELECT g.code, COUNT(i.id) AS nb_items
FROM pm_grilles_verification g
LEFT JOIN pm_grilles_verification_items i ON i.grille_id = g.id
WHERE g.code = 'A6-AOR-FS'
GROUP BY g.code;
