<?php
// ════════════════════════════════════════════════════════════════════
// ApReunionOuvertureController.php
// AP (Audit de Performance) — Phase 1 Préparation : Réunion d'ouverture
//
// Référentiel central : ddmparam.audit_type_forms id=59
//   code       : reunion-ouverture
//   url_path   : /m/audit.core/ap/preparation/reunion-ouverture
//   route_name : audit.ap.preparation.reunion-ouverture
//
// Déclinaison du contrôleur AC : toute la mécanique (CRUD, workflow
// soumettre/valider, PDF, chat, couleur du type d'audit) est héritée de
// ReunionOuvertureController — seuls changent la page Inertia, la route
// d'édition et le chemin de base des URLs.
//
// Même table `mission_phase_fros` (une fiche par assignment ; les
// assignments AP ont leurs propres ids, aucune collision avec l'AC) et
// même form_code `reunion-ouverture` (c'est aussi le code du formulaire
// AP dans ddmparam → chat et journal de validation restent cohérents).
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor\Ap;

use App\Http\Controllers\Auditor\ReunionOuvertureController;

class ApReunionOuvertureController extends ReunionOuvertureController
{
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AP/ReunionOuverture';
    protected string $routeEdit   = 'auditor.ap.reunion-ouverture.edit';
    protected string $basePath    = 'm/audit.core/ap/preparation/reunion-ouverture';
}
