-- ============================================================================
-- AUDIT DE PERFORMANCE (audit_type_id = 3) — CHRONOLOGIE DE LA PHASE 1
-- « PRÉPARATION » : remise en ordre des sort_order + désactivation des
-- doublons / anciennes phases superseded par la nouvelle maquette.
--
-- ▸ Cible : base CENTRALE `ddmparam` uniquement.
--       ex :  mysql -u root -proot ddmparam < ap_phases_chronologie.sql
-- ▸ Idempotent : UPDATE par clé fonctionnelle (audit_type_id, code) — les id
--   pouvant différer d'un environnement à l'autre, on NE cible JAMAIS par id.
-- ▸ Aucune resync tenant nécessaire : les menus lisent label/sort_order/
--   is_active EN DIRECT via JOIN sur ddmparam. Les phases désactivées sont
--   désormais masquées côté affichage (filtre is_active ajouté à
--   BuildsMissionMenu + AuditorMissionsController::buildPhasesByType) et ne
--   sont plus provisionnées (PhaseSyncService::centralFormIds filtre is_active).
--
-- CHRONOLOGIE CIBLE (nouvelle maquette seule) :
--   10  reunion-ouverture          Réunion d'ouverture              [actif]
--   20  PC                         Prise de connaissance            [actif]
--   21    PCE (enfant de PC)       …de l'entité                     [actif]
--   22    PCP (enfant de PC)       …du projet                       [actif]
--   30  PA                         Portée de l'audit                [actif]
--   40  champ-action               Champ d'action                   [actif]
--   50  methodologie-verification  Méthodologie de vérification     [actif — vue MethodologieVerification.vue livrée]
--   --  mv                         Méthodologie (ancien stub)       [INACTIF — doublon de methodologie-verification]
--   --  CHA                        Champ d'action (stub doublon)    [INACTIF]
--   60  analyse-processus          Analyse des processus            [INACTIF — ancienne génération]
--   70  analyse-forces-faiblesses  Analyse forces et faiblesses     [INACTIF — ancienne génération]
--   80  indicateurs-performance    Indicateurs de performance       [INACTIF — ancienne génération]
--   90  programme-travail          Programme de travail             [INACTIF — ancienne génération]
-- ============================================================================

-- ── Phases ACTIVES de la nouvelle maquette ─────────────────────────────────
UPDATE `audit_type_forms` SET `sort_order` = 10, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'reunion-ouverture';

UPDATE `audit_type_forms` SET `sort_order` = 20, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'PC';

UPDATE `audit_type_forms` SET `sort_order` = 21, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'PCE';

UPDATE `audit_type_forms` SET `sort_order` = 22, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'PCP';

UPDATE `audit_type_forms` SET `sort_order` = 30, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'PA';

UPDATE `audit_type_forms` SET `sort_order` = 40, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'champ-action';

-- ── Méthodologie de vérification (code canonique = 'methodologie-verification',
--    aligné sur routes/contrôleur/migrations/vue) : position 50, ACTIVE ──────
UPDATE `audit_type_forms` SET `sort_order` = 50, `is_active` = 1
  WHERE `audit_type_id` = 3 AND `code` = 'methodologie-verification';

-- ── Ancien stub « mv » (doublon de methodologie-verification) : DÉSACTIVÉ ───
UPDATE `audit_type_forms` SET `sort_order` = 98, `is_active` = 0
  WHERE `audit_type_id` = 3 AND `code` = 'mv';

-- ── Doublon stub « CHA » (remplacé par champ-action) : DÉSACTIVÉ ───────────
UPDATE `audit_type_forms` SET `sort_order` = 99, `is_active` = 0
  WHERE `audit_type_id` = 3 AND `code` = 'CHA';

-- ── Anciennes phases (génération précédente) : DÉSACTIVÉES, données conservées
UPDATE `audit_type_forms` SET `sort_order` = 60, `is_active` = 0
  WHERE `audit_type_id` = 3 AND `code` = 'analyse-processus';

UPDATE `audit_type_forms` SET `sort_order` = 70, `is_active` = 0
  WHERE `audit_type_id` = 3 AND `code` = 'analyse-forces-faiblesses';

UPDATE `audit_type_forms` SET `sort_order` = 80, `is_active` = 0
  WHERE `audit_type_id` = 3 AND `code` = 'indicateurs-performance';

UPDATE `audit_type_forms` SET `sort_order` = 90, `is_active` = 0
  WHERE `audit_type_id` = 3 AND `code` = 'programme-travail';

-- ── Contrôle : la chronologie active résultante ────────────────────────────
-- SELECT id, phase_num, code, label, sort_order, is_active
--   FROM audit_type_forms
--  WHERE audit_type_id = 3 AND phase_num = 1
--  ORDER BY sort_order, id;
