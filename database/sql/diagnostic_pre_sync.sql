-- ============================================================================
-- DIAGNOSTIC (lecture seule) — à lancer sur CHAQUE base tenant AVANT le
-- premier `php artisan tenants:sync-reference` afin de repérer les données
-- déjà incohérentes (elles ne seront jamais supprimées automatiquement,
-- seulement complétées/corrigées sur les champs de référence).
--
-- 💡 Équivalent artisan (recommandé, mêmes contrôles) :
--       php artisan tenants:sync-reference --diagnose
--
-- ✅ PARAMÉTRABLE : modifier UNIQUEMENT les deux SET ci-dessous, puis
--    exécuter tout le script d'un bloc. Aucune autre ligne à adapter.
-- ============================================================================

SET @tenant_db  := 'fruitiva';   -- ← nom de la base TENANT à diagnostiquer
SET @central_db := 'ddmparam';   -- ← base centrale (référentiel)

-- ----------------------------------------------------------------------------
-- 0) Version du schéma mission_phases de ce tenant.
--    ANCIEN schéma (colonne `code` présente) ⇒ lancer d'abord
--    migrate_phases_to_central_ids.sql ; les sections 4 ne sont pertinentes
--    qu'après migration.
-- ----------------------------------------------------------------------------
SELECT IF(COUNT(*) > 0,
          'ANCIEN schéma (colonne `code` présente) → lancer migrate_phases_to_central_ids.sql',
          'Nouveau schéma OK (mission_phases.id = ddmparam.audit_type_forms.id)') AS schema_mission_phases
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @tenant_db
  AND TABLE_NAME   = 'mission_phases'
  AND COLUMN_NAME  = 'code';

-- ----------------------------------------------------------------------------
-- 1) mission_types dont le rattachement audit_type_code ne correspond plus
--    au libellé/couleur/icône réels de la base centrale (ex. bug historique
--    AMP → AF au lieu de AM constaté dans le dump fourni).
--    ⚠️ Comparaisons NULL-safe (<=>) : une valeur NULL d'un côté est bien
--    détectée comme divergence (un simple <> l'ignorerait silencieusement).
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT mt.id, mt.code, mt.label, mt.audit_type_code, ',
  '       mt.audit_type_label AS label_stocke,  ref.label  AS label_attendu, ',
  '       mt.audit_color      AS couleur_stockee, ref.color AS couleur_attendue, ',
  '       mt.audit_icon       AS icone_stockee,   ref.icon  AS icone_attendue ',
  'FROM `', @tenant_db, '`.mission_types mt ',
  'LEFT JOIN `', @central_db, '`.audit_types ref ON ref.code = mt.audit_type_code ',
  'WHERE mt.audit_type_code IS NOT NULL AND mt.audit_type_code <> '''' AND (',
  '      ref.id IS NULL ',
  '   OR NOT (mt.audit_type_label <=> ref.label) ',
  '   OR NOT (mt.audit_color      <=> ref.color) ',
  '   OR NOT (mt.audit_icon       <=> ref.icon))'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 2) mission_types du tenant sans AUCUN audit_type_code renseigné
--    (ne seront pas pris en compte par la synchro tant qu'ils ne sont pas
--    rattachés manuellement à un audit_type existant)
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT id, code, label, ''audit_type_code manquant — à rattacher manuellement'' AS action_requise ',
  'FROM `', @tenant_db, '`.mission_types ',
  'WHERE audit_type_code IS NULL OR audit_type_code = '''''
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 3) missions déjà créées qui n'ont PAS encore de mission_programmation
--    (créées avant la mise en place de la génération automatique)
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT m.id, m.code, m.title, m.status ',
  'FROM `', @tenant_db, '`.missions m ',
  'LEFT JOIN `', @tenant_db, '`.mission_programmation mp ON mp.mission_id = m.id ',
  'WHERE mp.id IS NULL AND m.deleted_at IS NULL'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 4) NOUVEAU SCHÉMA UNIQUEMENT — phases orphelines : mission_phases.id qui ne
--    correspond plus à AUCUN formulaire ACTIF de la base centrale (formulaire
--    supprimé ou désactivé côté central). Elles restent en base (des
--    mission_phase_assignments réels peuvent en dépendre) mais ne sont plus
--    affichables : à arbitrer manuellement.
--    (Sous l'ANCIEN schéma, cette requête n'a pas de sens : migrer d'abord.)
-- ----------------------------------------------------------------------------
SET @sql := CONCAT(
  'SELECT mp.id AS mission_phase_id, mp.mission_type_id, mp.is_mandatory, mp.status, ',
  '       ''id sans formulaire actif dans ', @central_db, '.audit_type_forms'' AS action_requise ',
  'FROM `', @tenant_db, '`.mission_phases mp ',
  'LEFT JOIN `', @central_db, '`.audit_type_forms atf ',
  '       ON atf.id = mp.id AND atf.is_active = 1 ',
  'WHERE atf.id IS NULL'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
