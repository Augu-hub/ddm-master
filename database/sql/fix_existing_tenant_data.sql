-- ============================================================================
-- CORRECTIF DES DONNÉES TENANT (idempotent : ré-exécutable sans risque)
-- À exécuter UNE FOIS sur chaque base tenant existante (ex: fruitiva) avant
-- de laisser tourner les Observers/TenantReferenceSyncService en continu.
--
-- ⚠️ ddmparam et la base tenant doivent être sur le même serveur MySQL/MariaDB
--    (requêtes cross-DB `base`.`table`).
-- ⚠️ Faire un dump de sauvegarde avant exécution :
--       mysqldump fruitiva > backup_fruitiva.sql
--
-- ✅ PARAMÉTRABLE : modifier UNIQUEMENT les deux SET ci-dessous, puis
--    exécuter tout le script d'un bloc. Aucune autre ligne à adapter.
--
-- 💡 Alternative recommandée : `php artisan tenants:sync-reference` applique
--    les mêmes corrections (sections 1-2) automatiquement, par la queue.
--    Ce script reste utile pour corriger SANS passer par l'application.
-- ============================================================================

SET @tenant_db  := 'fruitiva';   -- ← nom de la base TENANT à corriger
SET @central_db := 'ddmparam';   -- ← base centrale (référentiel)

START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 1) BUG HISTORIQUE : mission_types.code = 'AMP' ("Audit des Marchés Publics")
--    rattaché à audit_type_code = 'AF' (Fraude) au lieu de 'AM' (Marchés).
--    Correction ciblée, explicite, avant la correction générique ci-dessous.
--    (Sans effet si déjà corrigé — le dump du 2026-07 l'était déjà.)
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'UPDATE `', @tenant_db, '`.mission_types ',
  'SET audit_type_code = ''AM'' ',
  'WHERE code = ''AMP'' AND audit_type_code = ''AF'''
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 2) RESYNCHRO GÉNÉRIQUE : pour toutes les lignes mission_types déjà
--    rattachées à un audit_type_code valide, on réaligne les champs
--    dénormalisés (label/couleur/icône) sur la valeur réelle de la centrale.
--    Ne touche JAMAIS `code`, `label` (nom métier du tenant), `description`,
--    `is_active`, `sort_order`.
--    ⚠️ Comparaisons NULL-safe (<=>) : l'ancienne version avec <> ratait les
--    lignes dont un champ était NULL (NULL <> x vaut NULL, pas TRUE).
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'UPDATE `', @tenant_db, '`.mission_types mt ',
  'JOIN `', @central_db, '`.audit_types ref ON ref.code = mt.audit_type_code ',
  'SET mt.audit_type_label = ref.label, ',
  '    mt.audit_color      = ref.color, ',
  '    mt.audit_icon       = ref.icon, ',
  '    mt.updated_at       = NOW() ',
  'WHERE NOT (mt.audit_type_label <=> ref.label) ',
  '   OR NOT (mt.audit_color      <=> ref.color) ',
  '   OR NOT (mt.audit_icon       <=> ref.icon)'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 3) mission_types sans audit_type_code renseigné : on ne peut pas deviner
--    automatiquement à leur place (choix métier). On les liste seulement,
--    à traiter manuellement :
--       UPDATE `<tenant>`.mission_types SET audit_type_code='XX' WHERE id=…;
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT id, code, label, ''audit_type_code manquant — à rattacher manuellement'' AS action_requise ',
  'FROM `', @tenant_db, '`.mission_types ',
  'WHERE audit_type_code IS NULL OR audit_type_code = '''''
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 4) NOUVEAU SCHÉMA — phases orphelines : mission_phases.id sans formulaire
--    ACTIF correspondant dans la centrale (supprimé/désactivé côté central).
--    Listées pour arbitrage manuel — pas de suppression automatique (des
--    mission_phase_assignments réels peuvent en dépendre).
--    (L'ancienne version de ce script interrogeait mp.form_code / mp.code_full,
--    colonnes qui n'existent plus depuis migrate_phases_to_central_ids.sql.)
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT mp.id AS mission_phase_id, mp.mission_type_id, mp.status, ',
  '       ''id sans formulaire actif dans ', @central_db, '.audit_type_forms'' AS action_requise ',
  'FROM `', @tenant_db, '`.mission_phases mp ',
  'LEFT JOIN `', @central_db, '`.audit_type_forms atf ',
  '       ON atf.id = mp.id AND atf.is_active = 1 ',
  'WHERE atf.id IS NULL'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

COMMIT;

-- ----------------------------------------------------------------------------
-- 5) Vérification post-correction (doit retourner 0 ligne)
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT mt.id, mt.code, mt.audit_type_code, mt.audit_type_label, ref.label AS label_attendu ',
  'FROM `', @tenant_db, '`.mission_types mt ',
  'JOIN `', @central_db, '`.audit_types ref ON ref.code = mt.audit_type_code ',
  'WHERE NOT (mt.audit_type_label <=> ref.label) ',
  '   OR NOT (mt.audit_color      <=> ref.color) ',
  '   OR NOT (mt.audit_icon       <=> ref.icon)'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
