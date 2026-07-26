-- ============================================================================
-- AM · ARMP 2026 — FICHIER 04 · Gabarits de formulaire par phase
--
-- ⚠️ À EXÉCUTER SUR ddmparam :
--     mysql -u root -proot ddmparam < am_armp_04_form_templates.sql
--
-- Chaque phase AM ouvre le formulaire générique (DynamicPhaseFormController).
-- Sans données saisies, il s'affichait vide. Cette table fournit, PAR CODE DE
-- FORMULAIRE, une ossature de sections (rubriques) pré-remplie reflétant la
-- structure normée du référentiel (« Constats → Opinion → Risques →
-- Recommandations », §Module 13). Elle est synchronisée vers les tenants et
-- lue par le contrôleur quand aucune saisie n'existe encore.
--
-- Idempotent : ON DUPLICATE KEY UPDATE.
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pm_form_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_code` VARCHAR(160) NOT NULL COMMENT 'ddmparam.audit_type_forms.code',
  `audit_type_code` VARCHAR(10) DEFAULT NULL COMMENT 'AC|AF|AP|AM|RP|ES',
  `titre` VARCHAR(255) DEFAULT NULL COMMENT 'Titre affiché en tête du formulaire',
  `sections_json` LONGTEXT NOT NULL COMMENT 'JSON [{titre, contenu, aide}]',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpl_form` (`form_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Gabarits de sections pré-remplies par formulaire de phase';

-- Structure « Constats/Opinion/Risques/Recommandations » réutilisée pour la
-- plupart des phases de vérification (§Module 13 du référentiel).
INSERT INTO `pm_form_templates` (`form_code`,`audit_type_code`,`titre`,`sections_json`) VALUES

-- ── PRÉPARATION ──────────────────────────────────────────────────────────
('analyse-risques-axes','AM','Prise de connaissance',
 '[{"titre":"Contexte et présentation de l''entité","contenu":"","aide":"Mandats, missions, activités, environnement"},{"titre":"Cadre juridique et réglementaire applicable","contenu":"","aide":"Loi n°2020-26, décrets d''application, textes ARMP"},{"titre":"Axes d''audit prioritaires et zones de risque","contenu":"","aide":"Modes de passation sensibles, montants élevés, historique d''irrégularités"}]'),

('fonctionement','AM','Analyse de l''organisation et du fonctionnement de l''entité',
 '[{"titre":"Organisation générale de l''autorité contractante","contenu":"","aide":null},{"titre":"Fonctionnement des organes de passation et de contrôle","contenu":"","aide":"PRMP, CCMP, SP-PRMP"},{"titre":"Constats","contenu":"","aide":null}]'),

('ADE','AM','Analyse documentaire de l''entité (A1)',
 '[{"titre":"Pièces obligatoires collectées (annexe A1)","contenu":"","aide":"Organes + planification — voir catalogue pm_pieces_obligatoires"},{"titre":"Taux d''absence des pièces","contenu":"","aide":"% d''absence → barème pm_grille_appreciation_disponibilite"},{"titre":"Appréciation de la disponibilité documentaire","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('mise_en_place','AM','Mise en place et fonctionnement des organes (A4)',
 '[{"titre":"Constats","contenu":"","aide":"Arrêtés PRMP/CCMP/SP-PRMP, nominations, rapports, registres"},{"titre":"Taux de conformité pondéré","contenu":"","aide":"Grille A4 · pondération linéaire (Respecté 100 / Partiel 50 / Non 0)"},{"titre":"Opinion de l''auditeur","contenu":"","aide":"Barème §2.4 : ≥ 80 % = satisfaisant"},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('conf','AM','Conformité du plan de passation des marchés (A5)',
 '[{"titre":"Constats sur le PPM","contenu":"","aide":"Élaboration, exhaustivité, cohérence, révisions"},{"titre":"Validation par la CCMP","contenu":"","aide":"PV de validation (art. 24 loi 2020-26)"},{"titre":"Publication sur SIGMaP","contenu":"","aide":null},{"titre":"Taux de conformité pondéré","contenu":"","aide":"Grille A5"},{"titre":"Opinion de l''auditeur","contenu":"","aide":null},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('PRA','AM','Présentation des résultats de l''analyse organisationnelle et fonctionnelle',
 '[{"titre":"Synthèse des résultats (A4 + A5)","contenu":"","aide":null},{"titre":"Commentaire de l''autorité contractante","contenu":"","aide":"Principe du contradictoire — annexé au rapport"}]'),

('AD','AM','Analyse des données globales des marchés',
 '[{"titre":"Répartition des marchés par mode de passation","contenu":"","aide":"AOO, DRP, DC, SD, ED… nombre et montant, % du total"},{"titre":"Répartition par nature de prestation","contenu":"","aide":"Travaux, fournitures, services courants, prestations intellectuelles"},{"titre":"Opinion de l''auditeur","contenu":"","aide":"Équilibre, régularité, efficience des choix"},{"titre":"Recommandation","contenu":"","aide":null}]'),

('ECH','AM','Échantillonnage des marchés à auditer',
 '[{"titre":"Méthode d''échantillonnage","contenu":"","aide":"Approche combinée : nombre, montants, diversité des modes, niveau de risque"},{"titre":"Respect des seuils indicatifs","contenu":"","aide":"≥ 50 % du nombre (min. 10 marchés) ET ≥ 50 % du montant total (§3.2.2.1)"},{"titre":"Marchés retenus dans l''échantillon","contenu":"","aide":"Inclure les marchés à risque élevé, avenants, recours, seuils communautaires"},{"titre":"Justification des écarts aux seuils","contenu":"","aide":"Tout écart doit être motivé dans le rapport"}]'),

-- ── RÉALISATION / VÉRIFICATION DES PROCÉDURES ────────────────────────────
('Ad','AM','Analyse documentaire et de l''auditabilité des marchés',
 '[{"titre":"Complétude documentaire par marché (A2)","contenu":"","aide":"Pièces à incidence directe (NCF) présentes / attendues"},{"titre":"Taux de complétude et statut d''auditabilité","contenu":"","aide":"Seuil 80 % : ≥ 80 % = auditable (OK), < 80 % = non auditable (KO)"},{"titre":"Marchés auditables / non auditables","contenu":"","aide":null},{"titre":"Opinion et limitation éventuelle","contenu":"","aide":null}]'),

('DAC','AM','Dossier d''appel à concurrence (DAC) et invitation à soumissionner',
 '[{"titre":"Constats","contenu":"","aide":"Grille A6 selon nature/mode/préqualification"},{"titre":"Taux de conformité pondéré","contenu":"","aide":null},{"titre":"Opinion de l''auditeur","contenu":"","aide":null},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('RIT','AM','Règles d''information et de transparence (A7)',
 '[{"titre":"Constats","contenu":"","aide":"Publications, PV, information des soumissionnaires (art. 53, 70, 78, 79, 87)"},{"titre":"Taux de conformité pondéré","contenu":"","aide":null},{"titre":"Opinion de l''auditeur","contenu":"","aide":null},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('ROA','AM','Réception, ouverture et attribution (A8)',
 '[{"titre":"Constats","contenu":"","aide":"Registre, COE, PV d''ouverture, rapport d''évaluation, PV d''attribution"},{"titre":"Taux de conformité pondéré","contenu":"","aide":null},{"titre":"Opinion de l''auditeur","contenu":"","aide":null},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('AOC','AM','Avis des organes de contrôle (A9)',
 '[{"titre":"Constats","contenu":"","aide":"Existence et pertinence des avis DNCMP/CCMP aux étapes prévues (art. 14-15)"},{"titre":"Taux de conformité pondéré","contenu":"","aide":null},{"titre":"Opinion de l''auditeur","contenu":"","aide":null},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('AAC','AM','Approbation par l''autorité compétente (A10)',
 '[{"titre":"Constats","contenu":"","aide":"Acte d''approbation, authentification, enregistrement, notification (art. 22, 84-86)"},{"titre":"Taux de conformité pondéré","contenu":"","aide":null},{"titre":"Opinion de l''auditeur","contenu":"","aide":"Un marché non approuvé est nul et de nul effet"},{"titre":"Risques","contenu":"","aide":null},{"titre":"Recommandations","contenu":"","aide":null}]'),

('Vérification de la matérialité','AM','Vérification de la matérialité des marchés',
 '[{"titre":"Prestations vérifiées (terrain / preuves)","contenu":"","aide":"Photos, PV de réception, bordereaux, attestations bénéficiaires"},{"titre":"Conformité physique/fonctionnelle aux engagements","contenu":"","aide":null},{"titre":"Constats de matérialité (conforme / non conforme)","contenu":"","aide":null},{"titre":"Opinion, risques et recommandations","contenu":"","aide":null}]'),

('App','AM','Appréciation de l''entité sur les vérifications',
 '[{"titre":"Restitution des résultats à l''autorité contractante","contenu":"","aide":null},{"titre":"Commentaire écrit de l''autorité contractante","contenu":"","aide":"Principe du contradictoire — annexé au rapport"}]'),

('ecart','AM','Analyse des écarts de conformité et conditions cumulatives',
 '[{"titre":"Écarts majeurs relevés (annexe A22)","contenu":"","aide":"Un seul écart majeur ⇒ marché non conforme"},{"titre":"Écarts modérés relevés (annexe A23)","contenu":"","aide":"Marché conforme mais risque à signaler"},{"titre":"Conditions cumulatives de conformité","contenu":"","aide":"PPMP, auditabilité, pièces NCF, ≥ 80 %, délais, approbation, matérialité (§2.3.1)"},{"titre":"Conclusion de conformité par marché","contenu":"","aide":"Conforme / Non conforme"}]'),

('suivi','AM','Suivi des décisions ARMP et des recommandations',
 '[{"titre":"Mise en œuvre des décisions ARMP (marchés ayant fait l''objet de recours)","contenu":"","aide":null},{"titre":"Mise en œuvre des recommandations des audits précédents","contenu":"","aide":null},{"titre":"Taux de mise en œuvre","contenu":"","aide":null},{"titre":"Opinion, risques et recommandations","contenu":"","aide":null}]'),

-- ── CONCLUSION ───────────────────────────────────────────────────────────
('Réunion de clôture','AM','Réunion de clôture',
 '[{"titre":"Ordre du jour et participants","contenu":"","aide":null},{"titre":"Principaux constats présentés","contenu":"","aide":null},{"titre":"Réactions de l''autorité contractante","contenu":"","aide":null}]'),

('Finaliser le plan d''action','AM','Finaliser le plan d''action',
 '[{"titre":"Recommandations retenues","contenu":"","aide":null},{"titre":"Responsables et échéances","contenu":"","aide":null},{"titre":"Indicateurs de suivi","contenu":"","aide":null}]'),

('Finaliser le rapport','AM','Finaliser le rapport',
 '[{"titre":"Synthèse générale de conformité","contenu":"","aide":"Marchés conformes / non conformes, motifs"},{"titre":"Constats consolidés","contenu":"","aide":null},{"titre":"Opinion générale de l''auditeur","contenu":"","aide":null},{"titre":"Recommandations et plan d''action","contenu":"","aide":null}]')

ON DUPLICATE KEY UPDATE
  `audit_type_code`=VALUES(`audit_type_code`), `titre`=VALUES(`titre`),
  `sections_json`=VALUES(`sections_json`), `actif`=1;

-- Bump de version pour propager la nouvelle table aux tenants au prochain login
UPDATE `pm_referentiel_versions` SET `version` = `version` + 1 WHERE id = 1;

SELECT CONCAT(COUNT(*),' gabarits AM installés') AS bilan,
       (SELECT version FROM pm_referentiel_versions) AS nouvelle_version
FROM `pm_form_templates` WHERE audit_type_code='AM';
