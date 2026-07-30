-- =====================================================================
--  DDM RISQUE — Colonnes de critère structurées (report du choix inhérent)
-- ---------------------------------------------------------------------
--  À exécuter SUR CHAQUE BASE TENANT qui utilise le module risque.
--  Le critère (ligne) choisi à l'évaluation inhérente est mémorisé de
--  façon structurée, puis verrouillé/récupéré par les étapes suivantes
--  (mise sous contrôle / résiduel / cible) — seul le niveau reste modifiable.
--  Colonnes NULL, additives (aucun impact sur l'existant).
-- =====================================================================

ALTER TABLE `risk_register`
  ADD COLUMN `impact_criterion_id`             BIGINT UNSIGNED NULL,
  ADD COLUMN `frequency_criterion_id`          BIGINT UNSIGNED NULL,
  ADD COLUMN `residual_impact_criterion_id`    BIGINT UNSIGNED NULL,
  ADD COLUMN `residual_frequency_criterion_id` BIGINT UNSIGNED NULL,
  ADD COLUMN `target_impact_criterion_id`      BIGINT UNSIGNED NULL,
  ADD COLUMN `target_frequency_criterion_id`   BIGINT UNSIGNED NULL;

-- NB : si certaines colonnes existent déjà, exécuter les ADD COLUMN un par un.
