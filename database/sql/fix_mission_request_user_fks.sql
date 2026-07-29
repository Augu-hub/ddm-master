-- ============================================================================
-- CORRECTIF · Demandes de mission (flux « mission-requests »)
--   Supprime les FK tenant qui pointent vers `users` DU TENANT alors que les
--   colonnes stockent des ids de la table `users` CENTRALE (ddmparam).
--
--   Contexte : l'authentification se fait sur la base MASTER (App\Models\User,
--   connexion `mysql`/ddmparam). Les ids master ≠ ids tenant (même e-mail,
--   id différent), et l'utilisateur connecté n'existe pas forcément côté
--   tenant → violation FK à la création d'un lien / d'une demande :
--     SQLSTATE[23000] 1452 … mission_request_shares_shared_by_id_foreign
--
--   Les relations Eloquent (sharedBy / requester / filledBy) résolvent déjà
--   ces colonnes via App\Models\User (MASTER) : on ne garde donc PAS de FK
--   base-de-données vers le tenant. On conserve colonnes + index.
--
-- ▸ À exécuter sur CHAQUE base tenant (idempotent, MariaDB 10.0.2+) :
--       mysql -u root -proot fruitiva < database/sql/fix_mission_request_user_fks.sql
-- ============================================================================

-- 1) mission_request_shares.shared_by_id → users (tenant)
ALTER TABLE `mission_request_shares`
  DROP FOREIGN KEY IF EXISTS `mission_request_shares_shared_by_id_foreign`;

-- 2) audit_mission_requests.requester_id → users (tenant)
ALTER TABLE `audit_mission_requests`
  DROP FOREIGN KEY IF EXISTS `fk_req_user`;

-- 3) audit_mission_requests.filled_by_id → users (tenant)
ALTER TABLE `audit_mission_requests`
  DROP FOREIGN KEY IF EXISTS `fk_req_filled_by`;

-- NB : la FK `fk_req_source` (mission_source_id → audit_mission_sources) est
--      LÉGITIME et conservée. Le contrôleur ne doit plus y écrire un id user
--      (corrigé dans MissionRequestController::store()).
