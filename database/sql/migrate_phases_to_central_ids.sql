-- ============================================================================
-- MIGRATION — `mission_phases` (tenant) pointe désormais DIRECTEMENT sur les
-- IDs de `ddmparam.audit_type_forms`, au lieu d'avoir ses propres IDs générés
-- localement (auto_increment) avec une copie du contenu (label, phase_type,
-- parent_id, form_code...).
--
-- Après cette migration :
--   - `mission_phases.id` = `ddmparam.audit_type_forms.id` (même valeur, plus
--     d'auto_increment séparé).
--   - `mission_phases` ne garde QUE les colonnes réellement propres au tenant :
--     mission_type_id, is_mandatory, status, weight.
--   - Tout le contenu (label, phase_type, hiérarchie, form_code...) est lu en
--     direct depuis `ddmparam.audit_type_forms` par les contrôleurs.
--
-- ⚠️ FAIS UN BACKUP AVANT : mysqldump fruitiva > backup_avant_migration.sql
-- ⚠️ Adapte `fruitiva` au nom de la base de CHAQUE tenant, à exécuter une
--    fois par tenant.
-- ⚠️ ddmparam et le tenant doivent être sur le même serveur MySQL/MariaDB.
-- ⚠️ EXÉCUTE CE SCRIPT EN UN SEUL BLOC (pas statement par statement dans un
--    client graphique). Si une erreur interrompt l'exécution AVANT le COMMIT,
--    la transaction reste OUVERTE et garde ses verrous — ton appli Laravel se
--    bloque alors en attente de ces verrous jusqu'au timeout (30s, "Maximum
--    execution time exceeded"), exactement le symptôme rencontré si l'appli
--    est testée entre deux étapes du script. Si ça arrive : exécute
--    immédiatement `ROLLBACK;` (ou `COMMIT;` si tout s'est bien passé) dans
--    CETTE MÊME session/onglet SQL avant de retester l'appli. Si tu as perdu
--    la session, identifie et tue la transaction bloquante :
--      SELECT * FROM information_schema.INNODB_TRX;   -- repère trx_mysql_thread_id
--      KILL <thread_id>;                                -- libère les verrous
--
-- ⚠️ NOTE IMPORTANTE SUR LES DDL : en MySQL/MariaDB, chaque instruction DDL
--    (CREATE/ALTER/DROP/RENAME TABLE) déclenche un COMMIT IMPLICITE — elle
--    n'est jamais annulable par un ROLLBACK, même à l'intérieur d'un
--    START TRANSACTION. C'est pour ça que ce script utilise un RENAME TABLE
--    atomique plutôt qu'un DROP TABLE + CREATE TABLE : si quoi que ce soit
--    échoue après la construction de la nouvelle table, l'ANCIENNE table
--    (`mission_phases_old`) reste intacte et l'appli continue de fonctionner
--    sur elle — rien n'est jamais "dans le vide" entre deux étapes.
-- ============================================================================

-- ──────────────────────────────────────────────────────────────────────────
-- ÉTAPE 0 — (informatif) Nom de la contrainte FK existante sur
-- mission_phase_assignments.mission_phase_id. Sa suppression est désormais
-- AUTOMATISÉE à l'étape 5 (SQL dynamique) : plus rien à remplacer à la main.
-- ──────────────────────────────────────────────────────────────────────────
SELECT CONSTRAINT_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'fruitiva'
  AND TABLE_NAME = 'mission_phase_assignments'
  AND COLUMN_NAME = 'mission_phase_id'
  AND REFERENCED_TABLE_NAME = 'mission_phases';

START TRANSACTION;

-- ──────────────────────────────────────────────────────────────────────────
-- 1) Table de correspondance ANCIEN id (tenant) → NOUVEL id (ddmparam),
--    faite par correspondance de form_code (l'ancien lien indirect).
-- ──────────────────────────────────────────────────────────────────────────
CREATE TEMPORARY TABLE _phase_id_map AS
SELECT mp.id AS old_id, atf.id AS new_id
FROM fruitiva.mission_phases mp
JOIN fruitiva.mission_types mt ON mt.id = mp.mission_type_id
JOIN ddmparam.audit_types at   ON at.code = mt.audit_type_code
JOIN ddmparam.audit_type_forms atf ON atf.audit_type_id = at.id AND atf.code = mp.form_code;

-- Vérification : toute ligne ici sans new_id n'a pas pu être remappée
-- (form_code introuvable côté ddmparam) — à examiner AVANT de continuer.
SELECT mp.id, mp.form_code, mp.label
FROM fruitiva.mission_phases mp
LEFT JOIN _phase_id_map map ON map.old_id = mp.id
WHERE map.new_id IS NULL;

-- ──────────────────────────────────────────────────────────────────────────
-- 2) Remapper TOUTES les tables qui référencent mission_phases.id, AVANT de
--    toucher à mission_phases elle-même.
-- ──────────────────────────────────────────────────────────────────────────
UPDATE fruitiva.mission_phase_assignments mpa
JOIN _phase_id_map map ON map.old_id = mpa.mission_phase_id
SET mpa.mission_phase_id = map.new_id;

-- Adapte/complète cette liste si d'autres tables référencent mission_phases.id
-- dans ton schéma (ex: historique, logs...). Le remap ci-dessous pour
-- `mission_phase_notifications` est conditionnel : si la table n'existe pas
-- chez toi (comme sur `fruitiva`), il ne fait rien au lieu de faire échouer
-- tout le script (erreur 1146 "table doesn't exist").
SET @tbl_exists := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'fruitiva' AND TABLE_NAME = 'mission_phase_notifications'
);
SET @sql := IF(
    @tbl_exists > 0,
    'UPDATE fruitiva.mission_phase_notifications n JOIN _phase_id_map map ON map.old_id = n.phase_id SET n.phase_id = map.new_id',
    'SELECT 1' -- no-op si la table n''existe pas
);
PREPARE _stmt FROM @sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- ──────────────────────────────────────────────────────────────────────────
-- 3) Sauvegarder les réglages tenant existants (is_mandatory / status /
--    weight) avec leur NOUVEL id, dans une table PERMANENTE temporaire
--    (pas TEMPORARY : on en a besoin après le COMMIT implicite des DDL
--    qui suivent, une table TEMPORARY ne survivrait pas à un COMMIT).
-- ──────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS fruitiva._phase_settings_backup;
CREATE TABLE fruitiva._phase_settings_backup AS
SELECT map.new_id AS id, mp.mission_type_id, mp.is_mandatory, mp.status,
       COALESCE(mp.weight, 0) AS weight
FROM fruitiva.mission_phases mp
JOIN _phase_id_map map ON map.old_id = mp.id;

COMMIT;
-- ⚠️ À partir d'ici, tout est fait via des DDL (RENAME/ALTER/CREATE), qui
--    committent automatiquement. Un COMMIT explicite ici garantit que les
--    UPDATE ci-dessus (déjà voulus, sans risque à garder) sont bien actés
--    avant de commencer la bascule de table.

-- ──────────────────────────────────────────────────────────────────────────
-- 4) Construire la NOUVELLE table sous un nom provisoire (l'ANCIENNE table
--    n'est jamais supprimée avant que la nouvelle soit prête et vérifiée).
-- ──────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS fruitiva.mission_phases_new;
CREATE TABLE fruitiva.mission_phases_new (
    id              BIGINT UNSIGNED NOT NULL COMMENT '= ddmparam.audit_type_forms.id',
    mission_type_id BIGINT UNSIGNED NOT NULL,
    is_mandatory    TINYINT(1) NOT NULL DEFAULT 0,
    status          VARCHAR(20) NOT NULL DEFAULT 'active',
    weight          INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_mission_phases_mission_type (mission_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO fruitiva.mission_phases_new (id, mission_type_id, is_mandatory, status, weight, created_at, updated_at)
SELECT id, mission_type_id, is_mandatory, status, weight, NOW(), NOW()
FROM fruitiva._phase_settings_backup;

-- ──────────────────────────────────────────────────────────────────────────
-- 4bis) CONTRÔLE BLOQUANT avant bascule : aucune assignation ne doit pointer
--    vers un id absent de la NOUVELLE table (phase dont le form_code n'a pas
--    pu être remappé à l'étape 1). Si cette requête retourne des lignes,
--    NE PAS CONTINUER : corriger le mapping (ou, décision métier assumée,
--    supprimer ces assignations avec le DELETE commenté) puis re-vérifier.
--    Sinon le ADD CONSTRAINT de l'étape 5 échouerait après la bascule.
-- ──────────────────────────────────────────────────────────────────────────
SELECT mpa.id AS assignment_id, mpa.mission_phase_id,
       'pointe vers un id absent de mission_phases_new — corriger avant l''étape 5' AS action_requise
FROM fruitiva.mission_phase_assignments mpa
LEFT JOIN fruitiva.mission_phases_new n ON n.id = mpa.mission_phase_id
WHERE n.id IS NULL;

-- Option (décision métier, à décommenter UNIQUEMENT si l'arbitrage est fait) :
-- DELETE mpa FROM fruitiva.mission_phase_assignments mpa
-- LEFT JOIN fruitiva.mission_phases_new n ON n.id = mpa.mission_phase_id
-- WHERE n.id IS NULL;

-- ──────────────────────────────────────────────────────────────────────────
-- 5) Retirer la contrainte FK actuelle — AUTOMATISÉ : le nom de la FK est
--    résolu dynamiquement (no-op si aucune FK n'existe), puis bascule
--    atomique des deux tables par RENAME.
-- ──────────────────────────────────────────────────────────────────────────
SET @fk_name := (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'fruitiva'
      AND TABLE_NAME = 'mission_phase_assignments'
      AND COLUMN_NAME = 'mission_phase_id'
      AND REFERENCED_TABLE_NAME = 'mission_phases'
    LIMIT 1
);
SET @sql := IF(
    @fk_name IS NULL,
    'SELECT ''Aucune FK à supprimer'' AS info',
    CONCAT('ALTER TABLE fruitiva.mission_phase_assignments DROP FOREIGN KEY `', @fk_name, '`')
);
PREPARE _stmt FROM @sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

RENAME TABLE
    fruitiva.mission_phases     TO fruitiva.mission_phases_old,
    fruitiva.mission_phases_new TO fruitiva.mission_phases;

ALTER TABLE fruitiva.mission_phase_assignments
    ADD CONSTRAINT fk_mpa_mission_phase
    FOREIGN KEY (mission_phase_id) REFERENCES fruitiva.mission_phases(id);

-- ──────────────────────────────────────────────────────────────────────────
-- 6) Après la migration : lance la synchro pour provisionner les phases
--    manquantes (nouveaux mission_types, nouveaux formulaires ddmparam...)
--    php artisan tenants:sync-reference --tenant=fruitiva
-- ──────────────────────────────────────────────────────────────────────────

-- ──────────────────────────────────────────────────────────────────────────
-- 7) Vérification (doit renvoyer 0 lignes : aucune assignation orpheline,
--    tous les mission_phase_id pointent vers un id ddmparam réel)
-- ──────────────────────────────────────────────────────────────────────────
SELECT mpa.id, mpa.mission_phase_id
FROM fruitiva.mission_phase_assignments mpa
LEFT JOIN ddmparam.audit_type_forms atf ON atf.id = mpa.mission_phase_id
WHERE atf.id IS NULL;

-- ──────────────────────────────────────────────────────────────────────────
-- 8) UNE FOIS l'appli testée et le comportement confirmé correct (pas avant
--    !), supprime les tables de sauvegarde devenues inutiles :
--
--      DROP TABLE fruitiva.mission_phases_old;
--      DROP TABLE fruitiva._phase_settings_backup;
--
--    En cas de souci après bascule, tu peux à tout moment revenir en
--    arrière manuellement tant que `mission_phases_old` existe encore :
--      ALTER TABLE fruitiva.mission_phase_assignments DROP FOREIGN KEY fk_mpa_mission_phase;
--      RENAME TABLE fruitiva.mission_phases TO fruitiva.mission_phases_broken,
--                   fruitiva.mission_phases_old TO fruitiva.mission_phases;
--      -- puis recrée la FK vers l'ancien schéma comme elle était avant.
-- ──────────────────────────────────────────────────────────────────────────
