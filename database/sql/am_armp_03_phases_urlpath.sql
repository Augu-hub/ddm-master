-- ============================================================================
-- AM · ARMP 2026 — FICHIER 03 · Câblage des phases de l'auditeur
--
-- ⚠️ À EXÉCUTER SUR ddmparam :
--     mysql -u root -proot ddmparam < am_armp_03_phases_urlpath.sql
--
-- Toutes les phases AM (audit_type_id = 4) dont `url_path` était NULL sont
-- câblées vers le contrôleur générique de formulaire de phase
-- (DynamicPhaseFormController) : dès qu'un url_path de la forme
--     /m/audit.core/am/{phaseSlug}/{codeSlug}
-- existe, la phase ouvre un formulaire fonctionnel (sections JSON stockées
-- dans la table tenant `mission_phase_form_data`) avec le workflow complet
-- draft → submitted → validated/rejected assuré par les rôles DM/CM.
--
-- Contraintes de routage (routes/web.php, fallback dynamique) :
--   phaseSlug ∈ [a-z0-9\-]+   ·   codeSlug ∈ [a-z0-9\-]+   (minuscules/tirets)
--   phase_num → slug : 1=preparation 2=realisation 3=conclusion 4=suivi 5=recommandations
--
-- L'url_path est lu EN DIRECT depuis ddmparam par AuditorMissionsController::
-- phases() (JOIN mission_phases → ddmparam.audit_type_forms) : aucune resync
-- tenant nécessaire pour que les formulaires s'ouvrent.
-- ============================================================================
SET NAMES utf8mb4;

-- ── Phase 1 · PRÉPARATION ────────────────────────────────────────────────
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/prise-de-connaissance'
  WHERE audit_type_id=4 AND code='analyse-risques-axes' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/organisation-fonctionnement'
  WHERE audit_type_id=4 AND code='fonctionement' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/analyse-documentaire-entite'
  WHERE audit_type_id=4 AND code='ADE' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/mise-en-place-organes'
  WHERE audit_type_id=4 AND code='mise_en_place' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/conformite-plan-passation'
  WHERE audit_type_id=4 AND code='conf' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/presentation-resultats-organisation'
  WHERE audit_type_id=4 AND code='PRA' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/analyse-donnees-globales'
  WHERE audit_type_id=4 AND code='AD' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/preparation/echantillonnage-marches'
  WHERE audit_type_id=4 AND code='ECH' AND (url_path IS NULL OR url_path='');

-- ── Phase 2 · RÉALISATION / VÉRIFICATION DES PROCÉDURES ──────────────────
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/analyse-auditabilite-marches'
  WHERE audit_type_id=4 AND code='Ad' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/verification-materialite'
  WHERE audit_type_id=4 AND code='Vérification de la matérialité' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/appreciation-entite-verifications'
  WHERE audit_type_id=4 AND code='App' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/analyse-ecarts-conformite'
  WHERE audit_type_id=4 AND code='ecart' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/suivi-decisions-recommandations'
  WHERE audit_type_id=4 AND code='suivi' AND (url_path IS NULL OR url_path='');
-- Sous-phases de « Vérification de la procédure de passation » (parent 78)
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/dac-invitation-soumissionner'
  WHERE audit_type_id=4 AND code='DAC' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/regles-information-transparence'
  WHERE audit_type_id=4 AND code='RIT' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/reception-ouverture-attribution'
  WHERE audit_type_id=4 AND code='ROA' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/avis-organes-controle'
  WHERE audit_type_id=4 AND code='AOC' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/realisation/approbation-autorite-competente'
  WHERE audit_type_id=4 AND code='AAC' AND (url_path IS NULL OR url_path='');

-- ── Phase 3 · CONCLUSION ─────────────────────────────────────────────────
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/conclusion/reunion-cloture'
  WHERE audit_type_id=4 AND code='Réunion de clôture' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/conclusion/finaliser-plan-action'
  WHERE audit_type_id=4 AND code='Finaliser le plan d''action' AND (url_path IS NULL OR url_path='');
UPDATE `audit_type_forms` SET `url_path`='/m/audit.core/am/conclusion/finaliser-rapport'
  WHERE audit_type_id=4 AND code='Finaliser le rapport' AND (url_path IS NULL OR url_path='');

-- Vérification : plus aucune phase AM active sans url_path
SELECT id, phase_num, code, label, url_path
FROM `audit_type_forms`
WHERE audit_type_id=4 AND is_active=1 AND (url_path IS NULL OR url_path='')
ORDER BY phase_num, sort_order;

SELECT CONCAT(COUNT(*),' phases AM câblées (url_path non nul)') AS bilan
FROM `audit_type_forms` WHERE audit_type_id=4 AND is_active=1 AND url_path IS NOT NULL AND url_path<>'';
