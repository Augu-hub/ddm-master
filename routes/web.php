<?php

use App\Http\Controllers\Admin\AuditTypeFormsController;
use App\Http\Controllers\Auditor\AnalyseProceduresController;
use App\Http\Controllers\Auditor\AnalyseRisquesController;
use App\Http\Controllers\Auditor\FicheTestController;
use App\Http\Controllers\Auditor\ParametrageAuditPerformanceController;

use App\Http\Controllers\Auditor\ParametrageGrillesVerificationController as GVC;

use App\Http\Controllers\Admin\ReferentielArmpController as RAC;

use App\Http\Controllers\Auditor\ParametrageMarchesController;
use App\Http\Controllers\Auditor\ParametragePiecesObligatoiresController;
use App\Http\Controllers\Auditor\PlanActionController;
use App\Http\Controllers\Auditor\PriseDeConnaissanceController;
use App\Http\Controllers\Auditor\RapportAuditController;
use App\Http\Controllers\Auditor\RapportAuditWordController;
use App\Http\Controllers\Auditor\ReunionOuvertureController;
use App\Http\Controllers\Tenant\AC\ReunionClotureController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auditor\ReferentielMarchesController;
use App\Http\Controllers\Auditor\ReferentielTransactionsController;
use App\Http\Controllers\Auditor\ReferentielConformiteController;
use Inertia\Inertia;
use Illuminate\Http\Request;
/* ====== AJOUTER CES IMPORTS EN HAUT (AVEC LES AUTRES) ====== */
use App\Models\Master\Menu;
use App\Models\Master\Module;
/* ======================================================== */
require __DIR__."/outils_routes.php";
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleEntryController;
use App\Http\Controllers\ErrorController;

// Admin
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserAssignmentController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\TenantsController;

// Menu API
use App\Http\Controllers\NavMenuController;

/* ====== Auth scaffolding ====== */
require __DIR__.'/auth.php';

/* ====== Pages Auth publiques (Blade/Inertia) ====== */
Route::get('/auth/login',    fn () => Inertia::render('auth/login'));
Route::get('/auth/register', fn () => Inertia::render('auth/register'));

/* ======================================================================
|  AUTH + VERIFIED
|====================================================================== */
Route::middleware(['web','auth','verified'])->group(function () {

    /* Accueil = grille de modules autorisés */
    Route::get('/', DashboardController::class)->name('dashboard');

    /* ====== ENTRÉE MODULE (CRITIQUE : doit être AVANT les routes API) ====== */
    Route::get('/m/{code}', [ModuleEntryController::class, 'show'])
        ->where('code', '[-_.a-z0-9]+')
        ->middleware(['bind.module', 'share.module'])
        ->name('modules.shell');

    /* Dashboards fallback si un module n'a pas d'entry_route */
    Route::prefix('dashboards')->group(function () {
        Route::get('/param',     [DashboardController::class, 'dashboardParam'])->name('param.projects.home');
        Route::get('/processus', [DashboardController::class, 'dashboardProcessus'])->name('process.core.home');
        Route::get('/risque',    [DashboardController::class, 'dashboardRisque'])->name('risk.core.home');
        Route::get('/audit',     [DashboardController::class, 'dashboardAudit'])->name('audit.core.home');
    });

    /* ====== API Menu (avec bind.module) ====== */
    Route::prefix('api')->middleware(['bind.module'])->group(function () {
        Route::get('/menu/structure',  [NavMenuController::class, 'structure']);
        Route::get('/menu/visibility', [NavMenuController::class, 'visibility']);
        Route::get('/menu/entities',   [NavMenuController::class, 'getEntities']);
        Route::get('/me/modules',      [NavMenuController::class, 'myModules']);
    });

    /* ====== Admin ====== */



/*
|--------------------------------------------------------------------------
| Référentiel ARMP national (ddmparam) — Super Admin uniquement
|--------------------------------------------------------------------------
| À coller dans routes/web.php, dans le même groupe que les routes
| 'admin.*' existantes (Route::middleware(['web','auth','verified'])
| ->prefix('admin')->name('admin.')), juste à côté du bloc
| 'audit-type-forms' déjà présent.
*/

    Route::prefix('admin')->name('admin.')->group(function () {
        // Services
        Route::get('/services',  [ServiceController::class, 'index'])->name('services.index');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');

        // Affectations
        Route::get('/assign/users-services', [UserAssignmentController::class, 'pageUsersServices'])->name('assign.users-services');
        Route::get('/assign/users-modules',  [UserAssignmentController::class, 'pageUsersModules'])->name('assign.users-modules');
        Route::post('/users/{user}/services/sync', [UserAssignmentController::class, 'syncServices'])->name('users.services.sync');
        Route::post('/users/{user}/modules/sync',  [UserAssignmentController::class, 'syncModules'])->name('users.modules.sync');

        // Permissions / Rôles
        Route::get('/permissions',            [PermissionsController::class,'index'])->name('permissions.index')->middleware('menu.authz:permissions');
        Route::post('/permissions',           [PermissionsController::class,'store'])->name('permissions.store')->middleware('menu.authz:permissions');
        Route::post('/permissions/sync-role', [PermissionsController::class,'syncRole'])->name('permissions.syncRole')->middleware('menu.authz:permissions');
        Route::post('/permissions/sync-user', [PermissionsController::class,'syncUser'])->name('permissions.syncUser')->middleware('menu.authz:permissions');

        Route::get('/roles',            [RolesController::class,'index'])->name('roles.index')->middleware('menu.authz:roles');
        Route::post('/roles',           [RolesController::class,'store'])->name('roles.store')->middleware('menu.authz:roles');
        Route::post('/roles/sync-user', [RolesController::class,'syncUserRoles'])->name('roles.syncUserRoles')->middleware('menu.authz:roles');

        // Tenants
        Route::get('/tenants',                      [TenantsController::class, 'index'])->name('tenants.index')->middleware('menu.authz:tenants');
        Route::post('/tenants',                     [TenantsController::class, 'store'])->name('tenants.store')->middleware('menu.authz:tenants');
        Route::put('/tenants/{tenant}',             [TenantsController::class, 'update'])->name('tenants.update')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::delete('/tenants/{tenant}',          [TenantsController::class, 'destroy'])->name('tenants.destroy')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::post('/tenants/{tenant}/sync-users', [TenantsController::class, 'syncUsers'])->name('tenants.syncUsers')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::post('/tenants/{tenant}/remove-user',[TenantsController::class, 'removeUser'])->name('tenants.removeUser')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::prefix('audit-type-forms')->name('audit-type-forms.')->group(function () {
 
    // ── Vue principale ────────────────────────────────────────────
    Route::get('/', [AuditTypeFormsController::class, 'index'])
        ->name('index');
 
    // ── API JSON (fetch depuis la Vue, pas de rechargement Inertia) ─
    Route::get('api/audit-types',        [AuditTypeFormsController::class, 'apiAuditTypes'])
        ->name('api.audit-types');
 
    Route::get('api/forms/{auditTypeId}', [AuditTypeFormsController::class, 'apiFormsByType'])
        ->name('api.forms-by-type');
 
    // ── CRUD audit_types ─────────────────────────────────────────
    Route::post  ('audit-types',                       [AuditTypeFormsController::class, 'storeAuditType'])
        ->name('audit-types.store');
 
    Route::put   ('audit-types/{id}',                  [AuditTypeFormsController::class, 'updateAuditType'])
        ->name('audit-types.update');
 
    Route::delete('audit-types/{id}',                  [AuditTypeFormsController::class, 'destroyAuditType'])
        ->name('audit-types.destroy');
 
    Route::patch ('audit-types/{id}/toggle-active',    [AuditTypeFormsController::class, 'toggleAuditTypeActive'])
        ->name('audit-types.toggle');
 
    // ── CRUD formulaires ─────────────────────────────────────────
    // ⚠️  Route statique 'forms/reorder' AVANT la route paramétrique 'forms/{id}'
    Route::post  ('forms/reorder',                     [AuditTypeFormsController::class, 'reorderForms'])
        ->name('forms.reorder');
 
    Route::post  ('forms',                             [AuditTypeFormsController::class, 'storeForm'])
        ->name('forms.store');
 
    Route::put   ('forms/{id}',                        [AuditTypeFormsController::class, 'updateForm'])
        ->name('forms.update');
 
    Route::delete('forms/{id}',                        [AuditTypeFormsController::class, 'destroyForm'])
        ->name('forms.destroy');
 
    Route::patch ('forms/{id}/toggle-active',          [AuditTypeFormsController::class, 'toggleFormActive'])
        ->name('forms.toggle');
 
    // ── Gestion des phases ────────────────────────────────────────
    Route::patch ('phase-rename',                      [AuditTypeFormsController::class, 'phaseRename'])
        ->name('phase.rename');
 
    Route::delete('phase/{auditTypeId}/{phaseNum}',    [AuditTypeFormsController::class, 'phaseDelete'])
        ->name('phase.delete');
 
});


Route::prefix('referentiel-armp')->name('referentiel-armp.')->group(function () {

    // ── Vue principale + rechargement complet ────────────────────────
    Route::get('/',        [RAC::class, 'index'])->name('index');
    Route::get('/api/all', [RAC::class, 'apiAll'])->name('api.all');

    // ── Référentiels simples : {entity} ∈ types-entites, sources-financement,
    //    natures-marche, modes-passation, organes, operations, dates-reference
    Route::post  ('/{entity}',      [RAC::class, 'storeSimple'])
        ->where('entity', '[a-z\-]+')->name('simple.store');
    Route::put   ('/{entity}/{id}', [RAC::class, 'updateSimple'])
        ->where(['entity' => '[a-z\-]+', 'id' => '[0-9]+'])->name('simple.update');
    Route::delete('/{entity}/{id}', [RAC::class, 'destroySimple'])
        ->where(['entity' => '[a-z\-]+', 'id' => '[0-9]+'])->name('simple.destroy');

    // ── Seuils AC ──────────────────────────────────────────────────────
    Route::post  ('seuils-ac',      [RAC::class, 'storeSeuilAC'])->name('seuils-ac.store');
    Route::delete('seuils-ac/{id}', [RAC::class, 'destroySeuilAC'])->name('seuils-ac.destroy');

    // ── Délais ─────────────────────────────────────────────────────────
    Route::post  ('delais',      [RAC::class, 'storeDelai'])->name('delais.store');
    Route::put   ('delais/{id}', [RAC::class, 'updateDelai'])->name('delais.update');
    Route::delete('delais/{id}', [RAC::class, 'destroyDelai'])->name('delais.destroy');

    // ── Grilles de vérification ────────────────────────────────────────
    Route::post  ('grilles',      [RAC::class, 'storeGrille'])->name('grilles.store');
    Route::put   ('grilles/{id}', [RAC::class, 'updateGrille'])->name('grilles.update');
    Route::delete('grilles/{id}', [RAC::class, 'destroyGrille'])->name('grilles.destroy');
    Route::post  ('grilles/{id}/ia-analyser', [RAC::class, 'iaAnalyserGrille'])->name('grilles.ia-analyser');

    Route::post  ('grille-organes', [RAC::class, 'storeGrilleOrgane'])->name('grille-organes.store');
    Route::delete('grille-organes', [RAC::class, 'destroyGrilleOrgane'])->name('grille-organes.destroy');

    // ── Items (points de contrôle) ─────────────────────────────────────
    Route::post  ('items',                        [RAC::class, 'storeItem'])->name('items.store');
    Route::put   ('items/{id}',                   [RAC::class, 'updateItem'])->name('items.update');
    Route::delete('items/{id}',                   [RAC::class, 'destroyItem'])->name('items.destroy');
    Route::post  ('items/reorder',                [RAC::class, 'reorderItems'])->name('items.reorder');
    Route::put   ('items/{id}/link-delai',        [RAC::class, 'linkItemDelai'])->name('items.link-delai');
    Route::put   ('items/{id}/link-seuil',        [RAC::class, 'linkItemSeuil'])->name('items.link-seuil');
    Route::get   ('items/{id}/liaisons',          [RAC::class, 'itemLiaisons'])->name('items.liaisons');
    Route::post  ('items/{id}/link-delai-multi',  [RAC::class, 'linkItemDelaiMulti'])->name('items.link-delai-multi');
    Route::delete('items/{id}/unlink-delai-multi',[RAC::class, 'unlinkItemDelaiMulti'])->name('items.unlink-delai-multi');
    Route::post  ('items/{id}/link-operation',    [RAC::class, 'linkItemOperation'])->name('items.link-operation');
    Route::delete('items/{id}/unlink-operation',  [RAC::class, 'unlinkItemOperation'])->name('items.unlink-operation');
    Route::post  ('items/{id}/link-article',      [RAC::class, 'linkItemArticle'])->name('items.link-article');
    Route::delete('items/{id}/unlink-article',    [RAC::class, 'unlinkItemArticle'])->name('items.unlink-article');
    Route::post  ('items/{id}/ia-analyser',       [RAC::class, 'iaAnalyserItem'])->name('items.ia-analyser');

    // ── Testeur d'affectation (couverture par nature+mode) ─────────────
    Route::get('preview-affectation', [RAC::class, 'previewAffectation'])->name('preview-affectation');

    // ── Synchronisation manuelle ────────────────────────────────────────
    Route::post('sync/all',        [RAC::class, 'forceSyncAll'])->name('sync.all');
    Route::post('sync/{tenantId}', [RAC::class, 'forceSyncTenant'])
        ->whereNumber('tenantId')->name('sync.tenant');
});




        });



     
           
});

/* ====== Erreurs publiques ====== */
Route::get('/error/403', [ErrorController::class, 'error403'])->name('error403');
Route::get('/error/404', [ErrorController::class, 'error404'])->name('error404');
Route::get('/error/500', [ErrorController::class, 'error500'])->name('error500');

use App\Http\Controllers\Admin\MenusController;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'verified'])
    ->group(function () {

        // ========================================
        // GESTION DES MENUS
        // ========================================

        Route::get('/menus', [MenusController::class, 'index'])
            ->name('menus.index');

        Route::get('/menus/table', [MenusController::class, 'tableView'])
            ->name('menus.table');

        Route::post('/menus', [MenusController::class, 'store'])
            ->name('menus.store');

        Route::put('/menus/{menu}', [MenusController::class, 'update'])
            ->name('menus.update')
            ->whereNumber('menu');

        Route::delete('/menus/{menu}', [MenusController::class, 'destroy'])
            ->name('menus.destroy')
            ->whereNumber('menu');

        Route::patch('/menus/{menu}/toggle-visibility', [MenusController::class, 'toggleVisibility'])
            ->name('menus.toggleVisibility')
            ->whereNumber('menu');

        Route::post('/menus/batch-update', [MenusController::class, 'batchUpdate'])
            ->name('menus.batchUpdate');

        Route::post('/menus/reorder', [MenusController::class, 'reorder'])
            ->name('menus.reorder');

        // APIs JSON
        Route::get('/menus/api/all', [MenusController::class, 'getAllMenusJson'])
            ->name('menus.api.all');

        Route::get('/menus/api/db-info', [MenusController::class, 'getDatabaseInfoJson'])
            ->name('menus.api.dbinfo');



            
 
    // ── Audit Types (ddmparam) ────────────────────────────────────────────────
  
 

 
    });

/* ════════════════════════════════════════════════════════════════════════════
|  ROUTE SIMPLE - CHARGER MENUS AUDIT
|════════════════════════════════════════════════════════════════════════════ */

Route::get('/api-simple/audit-menus', function () {
    try {
        // 1. Trouver le module AUDIT (code = 'audit.core')
        $auditModule = Module::where('code', 'audit.core')->first();
        
        if (!$auditModule) {
            return response()->json([
                'success' => false,
                'message' => 'AUDIT module not found',
                'data' => []
            ]);
        }
        
        // 2. Charger les menus AUDIT
        $menus = Menu::where('module_id', $auditModule->id)
                     ->whereNull('parent_id')
                     ->with('children')
                     ->orderBy('sort')
                     ->get()
                     ->toArray();
        
        return response()->json([
            'success' => true,
            'message' => 'AUDIT menus loaded',
            'data' => $menus,
            'count' => count($menus)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => []
        ], 500);
    }
})->name('api.audit.menus');

use App\Http\Controllers\Auditor\AuditorDashboardController;
use App\Http\Controllers\Auditor\AuditorMissionsController;
use App\Http\Middleware\EnsureIsAuditor; // Middleware à créer (voir ci-dessous)

// Groupe protégé : utilisateur connecté + auditeur actif
Route::middleware(['auth', 'verified', EnsureIsAuditor::class])
    ->prefix('mon-espace')
    ->name('auditor.')
    ->group(function () {

        // ── Dashboard principal ────────────────────────────────────────────
        Route::get('/dashboard', [AuditorDashboardController::class, 'index'])
            ->name('dashboard');

        // ── Détail d'une mission ───────────────────────────────────────────
        Route::get('/missions/{missionId}', [AuditorDashboardController::class, 'showMission'])
            ->name('mission.show');

        // ── Démarrer une mission ───────────────────────────────────────────
        Route::patch('/programmation-missions/{id}/start', [AuditorMissionsController::class, 'startMission'])
            ->name('missions.start')
            ->where('id', '[0-9]+');

        // ── Liste des missions ─────────────────────────────────────────────
        Route::prefix('missions')->name('missions.')->group(function () {
            Route::get('/', [AuditorMissionsController::class, 'index'])->name('index');
        });

        // ── Gantt d'une mission ────────────────────────────────────────────
        Route::get('missions/{id}/gantt', [AuditorMissionsController::class, 'gantt'])
            ->name('missions.gantt')
            ->where('id', '[0-9]+');
    });


Route::prefix('audit')->name('audit.')->middleware(['auth'])->group(function () {
    // ... autres routes d'audit


    Route::patch('missions/{mission}/start', [AuditorMissionsController::class, 'start'])
        ->name('auditor.missions.start');

    Route::get('missions/{mission}/gantt', [AuditorMissionsController::class, 'gantt'])
        ->name('auditor.missions.gantt');
});

Route::get('missions/{mission}/phases', [AuditorMissionsController::class, 'phases'])
    ->name('auditor.missions.phases');

    Route::get('/auditor/missions', [AuditorMissionsController::class, 'index'])->name('auditor.missions');
 

use App\Http\Controllers\Audit\MissionTypeController;
use App\Http\Controllers\Audit\MissionFormController;

      


// ── Dans votre groupe existant Route::prefix('m/audit.core')… ──

Route::prefix('param')->group(function () {

    // ─────────────────────────────────────────────────────────────
    // MISSION TYPES (phases : Préparation, Réalisation…)
    // Contrôleur existant — inchangé
    // ─────────────────────────────────────────────────────────────
    Route::get('mission-types', [MissionTypeController::class, 'index'])
        ->name('audit.param.mission-types.index');

    Route::post  ('mission-types',                              [MissionTypeController::class, 'store']);
    Route::put   ('mission-types/{id}',                        [MissionTypeController::class, 'update']);
    Route::delete('mission-types/{id}',                        [MissionTypeController::class, 'destroy']);
    Route::post  ('mission-types/{id}/logo',                   [MissionTypeController::class, 'uploadLogo']);

    Route::post  ('mission-types/{typeId}/phases',             [MissionTypeController::class, 'storePhase']);
    Route::post  ('mission-types/{typeId}/phases/{parentId}/sub', [MissionTypeController::class, 'storeSubPhase']);
    Route::put   ('mission-types/{typeId}/phases/{formId}',    [MissionTypeController::class, 'updateForm']);
    Route::delete('mission-types/{typeId}/phases/{formId}',    [MissionTypeController::class, 'destroyForm']);

    // ─────────────────────────────────────────────────────────────
    // MISSION FORMS : page principale (tous audit_types regroupés)
    // ─────────────────────────────────────────────────────────────
    Route::get('mission-forms', [MissionFormController::class, 'index'])
        ->name('audit.param.mission-forms.index');

    // ─────────────────────────────────────────────────────────────
    // CRUD par audit_type_code : ac | af | ap | am | rp | es
    // POST   /param/mission-forms/{typeCode}          → store
    // PUT    /param/mission-forms/{typeCode}/{id}     → update
    // DELETE /param/mission-forms/{typeCode}/{id}     → destroy
    // ─────────────────────────────────────────────────────────────
    Route::post  ('mission-forms/{auditTypeCode}',      [MissionFormController::class, 'store']);
    Route::put   ('mission-forms/{auditTypeCode}/{id}', [MissionFormController::class, 'update']);
    Route::delete('mission-forms/{auditTypeCode}/{id}', [MissionFormController::class, 'destroy']);
});

// ─────────────────────────────────────────────────────────────────
// API JSON (utilisées par d'autres modules : création de mission…)
// ─────────────────────────────────────────────────────────────────
Route::prefix('api')->group(function () {

    // Tous les forms d'un type d'audit pour une phase donnée
    // GET /api/mission-forms/ac/1   → forms AC pour Préparation (id=1)
    Route::get('mission-forms/{auditTypeCode}/{missionTypeId}',
        [MissionFormController::class, 'apiForms']);

    // Tous les forms d'un type d'audit (toutes phases)
    // GET /api/mission-forms/ac
    Route::get('mission-forms/{auditTypeCode}',
        [MissionFormController::class, 'apiAllForms']);
});

use App\Http\Controllers\Audit\MissionPhaseAffectationController;  // controller EXISTANT - INCHANGÉ
use App\Http\Controllers\Audit\MissionAuditAffectationController;   // NOUVEAU controller


// ══════════════════════════════════════════════════════════════════════════════
//  GROUPE PRINCIPAL — préfixe m/audit.core
//  À intégrer dans routes/web.php dans le groupe auth+verified existant
// ══════════════════════════════════════════════════════════════════════════════



Route::prefix('m/audit.core')
    ->middleware(['auth', 'verified'])
    ->group(function () {

    // ══════════════════════════════════════════════════════════════════════════
    //  VUE PRINCIPALE — Affectation phases aux missions
    // ══════════════════════════════════════════════════════════════════════════

    Route::get('affectation-phases-aux-mission',
        [MissionPhaseAffectationController::class, 'index'])
        ->name('audit.core.home.programmation-missions.phases');

    // ══════════════════════════════════════════════════════════════════════════
    //  API — Mission Phase Affectation
    // ══════════════════════════════════════════════════════════════════════════

    // Assignments existants d'une mission
    Route::get('api/programmation-missions/{missionId}/phase-assignments',
        [MissionPhaseAffectationController::class, 'getAssignedPhases'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.phase-assignments');

    // Auditeurs d'une mission (par entité)
    Route::get('api/programmation-missions/{missionId}/auditeurs',
        [MissionPhaseAffectationController::class, 'getAuditeursApi'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.mission.auditeurs-phase');

    // Phases d'un type de mission
    Route::get('api/mission-phases/by-type/{typeId}',
        [MissionPhaseAffectationController::class, 'getPhasesByTypeApi'])
        ->where('typeId', '[0-9]+')
        ->name('audit.core.api.mission-phases.by-type');

    // Sauvegarder les affectations
    Route::post('api/mission-phases/affectation/{missionId}',
        [MissionPhaseAffectationController::class, 'saveAffectation'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.phase-affectation.save');

    // Toggle obligatoire d'une phase
    Route::patch('api/mission-phases/{id}/toggle-mandatory',
        [MissionPhaseAffectationController::class, 'toggleMandatory'])
        ->where('id', '[0-9]+')
        ->name('audit.core.api.phase.toggle-mandatory');

    // Broadcast (message aux auditeurs d'une phase)
    Route::post('api/mission-phases/broadcast/{missionId}',
        [MissionPhaseAffectationController::class, 'broadcast'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.phase.broadcast');

    // Synchronisation forcée des phases depuis ddmparam
    Route::post('api/mission-phases/sync/{missionId}',
        [MissionPhaseAffectationController::class, 'syncPhases'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.phase.sync');

    Route::post('/api/mission-phases/sync-labels/{missionTypeId}',
    [MissionPhaseAffectationController::class, 'syncPhasesLabels']);

    // ══════════════════════════════════════════════════════════════════════════
    //  AUDIT FORMS AFFECTATION (MissionAuditAffectationController)
    // ══════════════════════════════════════════════════════════════════════════

    // VUE
    Route::get('affectation-audit-forms',
        [MissionAuditAffectationController::class, 'index'])
        ->name('audit.core.affectation-audit-forms');

    // API — Formulaires par code audit
    Route::get('api/audit-type-forms/{code}',
        [MissionAuditAffectationController::class, 'getFormsByCode'])
        ->name('audit.core.api.audit-type-forms');

    // API — Affectations d'audit (GET + POST)
    Route::get('api/mission-audit/{missionId}/affectations',
        [MissionAuditAffectationController::class, 'getAffectations'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.mission-audit.affectations.get');

    Route::post('api/mission-audit/{missionId}/affectations',
        [MissionAuditAffectationController::class, 'saveAffectations'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.mission-audit.affectations.save');

    // API — Auditeurs pour audit
    Route::get('api/mission-audit/{missionId}/auditeurs',
        [MissionAuditAffectationController::class, 'getAuditeursMission'])
        ->where('missionId', '[0-9]+')
        ->name('audit.core.api.mission-audit.auditeurs');

    // API — Affectation individuelle (delete + sync auditeurs)
    Route::delete('api/mission-audit/affectation/{id}',
        [MissionAuditAffectationController::class, 'deleteAffectation'])
        ->where('id', '[0-9]+')
        ->name('audit.core.api.mission-audit.affectation.delete');

    Route::post('api/mission-audit/affectation/{id}/auditeurs',
        [MissionAuditAffectationController::class, 'syncAuditeurs'])
        ->where('id', '[0-9]+')
        ->name('audit.core.api.mission-audit.affectation.auditeurs');

});



use App\Http\Controllers\Auditor\MissionPhaseChatController;
use App\Http\Controllers\Auditor\MissionPhaseValidationController;

// ══════════════════════════════════════════════════════════════════════
//  Ces routes s'ajoutent dans routes/web.php (ou le fichier dédié audit)
//  sous le middleware ['web', 'auth', 'audit.session']
// ══════════════════════════════════════════════════════════════════════

Route::prefix('m/audit.core')->middleware(['web','auth','audit.session'])->group(function () {

    // ──────────────────────────────────────────────────────────────────
    // RÉUNION D'OUVERTURE (FRO)
    // Formulaire Inertia : index/edit
    // CRUD : store / update / destroy
    // Actions : soumettre / valider
    // ──────────────────────────────────────────────────────────────────
    Route::prefix('ac/preparation')->group(function () {

        // Affichage du formulaire (sans FRO existant)
        // GET  /m/audit.core/ac/preparation/reunion-ouverture?mission_id=X&assignment_id=Y
      
    });

    // ──────────────────────────────────────────────────────────────────
    // VALIDATION HIÉRARCHIQUE GÉNÉRIQUE (tous formulaires)
    // ──────────────────────────────────────────────────────────────────
    Route::prefix('missions/{mission}/assignments/{assignment}')->group(function () {

        // Statut de validation + historique
        // GET /m/audit.core/missions/{mission}/assignments/{assignment}/validation-status?form_code=xxx
        Route::get('validation-status', [MissionPhaseValidationController::class, 'status'])
            ->name('auditor.validation.status');

        // Soumettre pour validation
        // POST /m/audit.core/missions/{mission}/assignments/{assignment}/soumettre
        // Body : { form_code: 'reunion-ouverture' }
        Route::post('soumettre', [MissionPhaseValidationController::class, 'soumettre'])
            ->name('auditor.validation.soumettre');

        // Valider ou rejeter (CM/DM)
        // POST /m/audit.core/missions/{mission}/assignments/{assignment}/valider
        // Body : { form_code, action: 'validate'|'reject', note? }
        Route::post('valider', [MissionPhaseValidationController::class, 'valider'])
            ->name('auditor.validation.valider');

        // Déverrouiller (DM uniquement, cas exceptionnel)
        // POST /m/audit.core/missions/{mission}/assignments/{assignment}/deverrouiller
        // Body : { form_code, note }
        Route::post('deverrouiller', [MissionPhaseValidationController::class, 'deverrouiller'])
            ->name('auditor.validation.deverrouiller');
    });

    // ──────────────────────────────────────────────────────────────────
    // CHAT PAR TYPE DE PHASE
    // Une bulle par (mission, phase_type)
    // phase_type : PREPARATION | VERIFICATION | CONCLUSION | SUIVI
    // ──────────────────────────────────────────────────────────────────
    Route::prefix('missions/{mission}/chat')->group(function () {

        // Compteurs de non-lus (pour badges TopBar)
        // GET /m/audit.core/missions/{mission}/chat/unread-counts
        Route::get('unread-counts', [MissionPhaseChatController::class, 'unreadCounts'])
            ->name('auditor.chat.unread');

        // Messages d'une bulle
        // GET /m/audit.core/missions/{mission}/chat/{phase_type}
        Route::get('{phase_type}', [MissionPhaseChatController::class, 'index'])
            ->name('auditor.chat.index');

        // Envoyer un message
        // POST /m/audit.core/missions/{mission}/chat/{phase_type}
        // Body : { content, type?, priority?, parent_id?, assignment_id?, form_code? }
        Route::post('{phase_type}', [MissionPhaseChatController::class, 'store'])
            ->name('auditor.chat.store');

        // Marquer tout comme lu
        // POST /m/audit.core/missions/{mission}/chat/{phase_type}/read-all
        Route::post('{phase_type}/read-all', [MissionPhaseChatController::class, 'markAllRead'])
            ->name('auditor.chat.readAll');

        // Épingler un message
        // PATCH /m/audit.core/missions/{mission}/chat/{message}/pin
        Route::patch('{message}/pin', [MissionPhaseChatController::class, 'pin'])
            ->name('auditor.chat.pin');
    });

});




Route::middleware(['web', 'auth', 'audit.session'])
    ->prefix('m/audit.core')
    ->group(function () {

        // ══════════════════════════════════════════════════════════════════
        // AUDITOR — Mes missions & phases
        // ══════════════════════════════════════════════════════════════════

      Route::post('auditor/missions/{missionId}/phases/{assignmentId}/start', 
    [AuditorMissionsController::class, 'startPhase']);
Route::prefix('ac')->group(function () {
    // ── Phase 1 : Preparation ──
    Route::prefix('preparation')->group(function () {
        // reunion-ouverture
        Route::get('reunion-ouverture/{form}/pdf', [ReunionOuvertureController::class, 'pdf'])
    ->name('auditor.ac.reunion-ouverture.pdf');
        Route::get('reunion-ouverture', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'index'])->name('audit.ac.preparation.reunion-ouverture');
        Route::get('reunion-ouverture/{form}/edit', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'edit'])->name('auditor.ac.reunion-ouverture.edit');
        Route::post('reunion-ouverture', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'store'])->name('auditor.ac.reunion-ouverture.store');
        Route::put('reunion-ouverture/{form}', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'update'])->name('auditor.ac.reunion-ouverture.update');
        Route::delete('reunion-ouverture/{form}', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'destroy'])->name('auditor.ac.reunion-ouverture.destroy');
        Route::post('reunion-ouverture/{form}/soumettre', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'soumettre'])->name('auditor.ac.reunion-ouverture.soumettre');
        Route::post('reunion-ouverture/{form}/valider', [App\Http\Controllers\Auditor\ReunionOuvertureController::class, 'valider'])->name('auditor.ac.reunion-ouverture.valider');
        // prise-de-connaissance
     Route::prefix('prise-de-connaissance')->name('auditor.pdc.')->group(function () {
    Route::get('/',                    [PriseDeConnaissanceController::class, 'index'])->name('index');
    Route::post('/',                   [PriseDeConnaissanceController::class, 'store'])->name('store');
    Route::get('/{form}/edit',         [PriseDeConnaissanceController::class, 'edit'])->name('edit');
    Route::put('/{form}',              [PriseDeConnaissanceController::class, 'update'])->name('update');
    Route::delete('/{form}',           [PriseDeConnaissanceController::class, 'destroy'])->name('destroy');
    Route::post('/import-excel',       [PriseDeConnaissanceController::class, 'importExcel'])->name('import');
    Route::post('/{form}/soumettre',   [PriseDeConnaissanceController::class, 'soumettre'])->name('soumettre');
    Route::post('/{form}/valider',     [PriseDeConnaissanceController::class, 'valider'])->name('valider');
    Route::get('/{form}/pdf',          [PriseDeConnaissanceController::class, 'pdf'])->name('pdf');
    });
       
//         // analyse-processus-ci
//         Route::get('analyse-processus-ci', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'index'])->name('audit.ac.preparation.analyse-processus-ci');
//         Route::get('analyse-processus-ci/{form}/edit', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'edit'])->name('auditor.ac.analyse-processus-ci.edit');
//         Route::post('analyse-processus-ci', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'store'])->name('auditor.ac.analyse-processus-ci.store');
//         Route::put('analyse-processus-ci/{form}', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'update'])->name('auditor.ac.analyse-processus-ci.update');
//         Route::delete('analyse-processus-ci/{form}', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'destroy'])->name('auditor.ac.analyse-processus-ci.destroy');
//         Route::post('analyse-processus-ci/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'soumettre'])->name('auditor.ac.analyse-processus-ci.soumettre');
//         Route::post('analyse-processus-ci/{form}/valider', [App\Http\Controllers\Auditor\AnalyseProcessusCiController::class, 'valider'])->name('auditor.ac.analyse-processus-ci.valider');
        //analyse-processus
        Route::get('analyse-processus', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'index'])->name('audit.ac.preparation.analyse-processus');
        Route::get('analyse-processus/{form}/edit', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'edit'])->name('auditor.ac.analyse-processus.edit');
        Route::post('analyse-processus', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'store'])->name('auditor.ac.analyse-processus.store');
        Route::put('analyse-processus/{form}', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'update'])->name('auditor.ac.analyse-processus.update');
        Route::delete('analyse-processus/{form}', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'destroy'])->name('auditor.ac.analyse-processus.destroy');
        Route::post('analyse-processus/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'soumettre'])->name('auditor.ac.analyse-processus.soumettre');
        Route::post('analyse-processus/{form}/valider', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'valider'])->name('auditor.ac.analyse-processus.valider');
//         // analyse-risques


// Routes fixes (avant les routes paramétrées)
Route::get ('analyse-risques',
    [AnalyseRisquesController::class, 'index'])
    ->name('audit.ac.preparation.analyse-risques');
 
Route::post('analyse-risques',
    [AnalyseRisquesController::class, 'store'])
    ->name('auditor.ac.analyse-risques.store');
 
// Affectation processus → auditeur (DM/CM) — crée l'AR si besoin
Route::post('analyse-risques/assign-process',
    [AnalyseRisquesController::class, 'assignProcess'])
    ->name('auditor.ac.analyse-risques.assign-process');
 
// Routes paramétrées {form}
Route::get   ('analyse-risques/{form}/edit',
    [AnalyseRisquesController::class, 'edit'])
    ->name('auditor.ac.analyse-risques.edit');
 
Route::put   ('analyse-risques/{form}',
    [AnalyseRisquesController::class, 'update'])
    ->name('auditor.ac.analyse-risques.update');
 
Route::delete('analyse-risques/{form}',
    [AnalyseRisquesController::class, 'destroy'])
    ->name('auditor.ac.analyse-risques.destroy');
 
Route::post  ('analyse-risques/{form}/soumettre',
    [AnalyseRisquesController::class, 'soumettre'])
    ->name('auditor.ac.analyse-risques.soumettre');
 
Route::post  ('analyse-risques/{form}/valider',
    [AnalyseRisquesController::class, 'valider'])
    ->name('auditor.ac.analyse-risques.valider');
// ── Index & Store ───────────────────────────────────────────────────
Route::get('analyse-procedures', [AnalyseProceduresController::class, 'index'])
    ->name('audit.ac.preparation.analyse-procedures');

Route::post('analyse-procedures', [AnalyseProceduresController::class, 'store'])
    ->name('auditor.ac.analyse-procedures.store');

// ── API sans {form} ─────────────────────────────────────────────────
Route::post('analyse-procedures/ai-suggest',
    [AnalyseProceduresController::class, 'aiSuggest'])
    ->name('auditor.ac.analyse-procedures.ai-suggest');

Route::post('analyse-procedures/import-excel',
    [AnalyseProceduresController::class, 'importExcel'])
    ->name('auditor.ac.analyse-procedures.import-excel');

Route::post('analyse-procedures/analyze-document',
    [AnalyseProceduresController::class, 'analyzeDocument'])
    ->name('auditor.ac.analyse-procedures.analyze-document');

Route::post('analyse-procedures/level-doc-upload',
    [AnalyseProceduresController::class, 'levelDocUpload'])
    ->name('auditor.ac.analyse-procedures.level-doc-upload');

Route::post('analyse-procedures/assign-procedure',
    [AnalyseProceduresController::class, 'assignProcedure'])
    ->name('auditor.ac.analyse-procedures.assign-procedure');

Route::delete('analyse-procedures/delete-doc',
    [AnalyseProceduresController::class, 'deleteDoc'])
    ->name('auditor.ac.analyse-procedures.delete-doc');

// ── Routes paramétrées {form} ────────────────────────────────────────
Route::get('analyse-procedures/{form}/edit',
    [AnalyseProceduresController::class, 'edit'])
    ->name('auditor.ac.preparation.analyse-procedures.edit');

Route::put('analyse-procedures/{form}',
    [AnalyseProceduresController::class, 'update'])
    ->name('auditor.ac.analyse-procedures.update');

Route::delete('analyse-procedures/{form}',
    [AnalyseProceduresController::class, 'destroy'])
    ->name('auditor.ac.analyse-procedures.destroy');

Route::post('analyse-procedures/{form}/soumettre',
    [AnalyseProceduresController::class, 'soumettre'])
    ->name('auditor.ac.analyse-procedures.soumettre');

Route::post('analyse-procedures/{form}/valider',
    [AnalyseProceduresController::class, 'valider'])
    ->name('auditor.ac.analyse-procedures.valider');
        //         // analyse-taches
        Route::get('analyse-taches', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'index'])->name('audit.ac.preparation.analyse-taches');
        Route::get('analyse-taches/{form}/edit', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'edit'])->name('auditor.ac.analyse-taches.edit');
        Route::post('analyse-taches', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'store'])->name('auditor.ac.analyse-taches.store');
        Route::put('analyse-taches/{form}', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'update'])->name('auditor.ac.analyse-taches.update');
        Route::delete('analyse-taches/{form}', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'destroy'])->name('auditor.ac.analyse-taches.destroy');
        Route::post('analyse-taches/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'soumettre'])->name('auditor.ac.analyse-taches.soumettre');
        Route::post('analyse-taches/{form}/valider', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'valider'])->name('auditor.ac.analyse-taches.valider');
        //analyse-ci-qci
        Route::get('analyse-ci-qci', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'index'])->name('audit.ac.preparation.analyse-ci-qci');
        Route::get('analyse-ci-qci/{form}/edit', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'edit'])->name('auditor.ac.analyse-ci-qci.edit');
        Route::post('analyse-ci-qci', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'store'])->name('auditor.ac.analyse-ci-qci.store');
        Route::put('analyse-ci-qci/{form}', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'update'])->name('auditor.ac.analyse-ci-qci.update');
        Route::delete('analyse-ci-qci/{form}', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'destroy'])->name('auditor.ac.analyse-ci-qci.destroy');
        Route::post('analyse-ci-qci/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'soumettre'])->name('auditor.ac.analyse-ci-qci.soumettre');
        Route::post('analyse-ci-qci/{form}/valider', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'valider'])->name('auditor.ac.analyse-ci-qci.valider');
        Route::post('analyse-ci-qci/import-excel',
    [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'importExcel'])
    ->name('auditor.ac.analyse-ci-qci.import-excel');
        
        // analyse-conformite
       
// --- CRUD de base ---
Route::get('analyse-conformite',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'index'])
    ->name('audit.ac.preparation.analyse-conformite');
 
Route::get('analyse-conformite/{form}/edit',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'edit'])
    ->name('auditor.ac.analyse-conformite.edit');
 
Route::post('analyse-conformite',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'store'])
    ->name('auditor.ac.analyse-conformite.store');
 
Route::put('analyse-conformite/{form}',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'update'])
    ->name('auditor.ac.analyse-conformite.update');
 
Route::delete('analyse-conformite/{form}',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'destroy'])
    ->name('auditor.ac.analyse-conformite.destroy');
 
// --- Workflow statuts ---
Route::post('analyse-conformite/{form}/soumettre',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'soumettre'])
    ->name('auditor.ac.analyse-conformite.soumettre');
 
Route::post('analyse-conformite/{form}/valider',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'valider'])
    ->name('auditor.ac.analyse-conformite.valider');
 
// --- Import / Export Excel ---
Route::post('analyse-conformite/importer',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'importer'])
    ->name('auditor.ac.analyse-conformite.importer');
 
Route::get('analyse-conformite/{form}/exporter',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'exporter'])
    ->name('auditor.ac.analyse-conformite.exporter');
 
// --- Propositions IA (par item) ---
Route::post('analyse-conformite/{form}/ia/proposition',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'propositionIA'])
    ->name('auditor.ac.analyse-conformite.ia.proposition');
 
// --- Synthèse IA globale ---
Route::get('analyse-conformite/{form}/ia/synthese',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'propositionIAGlobale'])
    ->name('auditor.ac.analyse-conformite.ia.synthese');
 
// --- Drag & Drop – réorganisation phases ---
Route::post('analyse-conformite/{form}/phases/reordonner',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'reordonnerPhases'])
    ->name('auditor.ac.analyse-conformite.phases.reordonner');
 
// --- Drag & Drop – réorganisation sous-phases ---
Route::post('analyse-conformite/phases/{phase}/sous-phases/reordonner',
    [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'reordonnerSousPhases'])
    ->name('auditor.ac.analyse-conformite.sous-phases.reordonner');
    
    
    //         // analyse-marches
     Route::get('analyse-marches', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'index'])->name('audit.ac.preparation.analyse-marches');
Route::get('analyse-marches/{form}/edit', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'edit'])->name('auditor.ac.analyse-marches.edit');
Route::post('analyse-marches', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'store'])->name('auditor.ac.analyse-marches.store');
Route::put('analyse-marches/{form}', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'update'])->name('auditor.ac.analyse-marches.update');
Route::delete('analyse-marches/{form}', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'destroy'])->name('auditor.ac.analyse-marches.destroy');
Route::post('analyse-marches/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'soumettre'])->name('auditor.ac.analyse-marches.soumettre');
Route::post('analyse-marches/{form}/valider', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'valider'])->name('auditor.ac.analyse-marches.valider');

// ── IA & outils ──────────────────────────────────────────────────────
Route::post('analyse-marches/ai-suggest', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'aiSuggest'])->name('auditor.ac.analyse-marches.ai-suggest');
Route::post('analyse-marches/import-excel', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'importExcel'])->name('auditor.ac.analyse-marches.import-excel');
Route::post('analyse-marches/doc-upload', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'docUpload'])->name('auditor.ac.analyse-marches.doc-upload');
Route::delete('analyse-marches/delete-doc', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'deleteDoc'])->name('auditor.ac.analyse-marches.delete-doc'); 
    
    // analyse-forces-faiblesses
   // ── Analyse Forces & Faiblesses — TFFA (routes utilitaires AVANT {form}/*)  ──────
Route::post('analyse-forces-faiblesses/ai-suggest', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'aiSuggest'])->name('auditor.ac.analyse-forces-faiblesses.ai-suggest');

// ── CRUD + workflow ────────────────────────────────────────────────────────────────
Route::get('analyse-forces-faiblesses', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'index'])->name('audit.ac.preparation.analyse-forces-faiblesses');
Route::get('analyse-forces-faiblesses/{form}/edit', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'edit'])->name('auditor.ac.analyse-forces-faiblesses.edit');
Route::post('analyse-forces-faiblesses', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'store'])->name('auditor.ac.analyse-forces-faiblesses.store');
Route::put('analyse-forces-faiblesses/{form}', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'update'])->name('auditor.ac.analyse-forces-faiblesses.update');
Route::delete('analyse-forces-faiblesses/{form}', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'destroy'])->name('auditor.ac.analyse-forces-faiblesses.destroy');
Route::post('analyse-forces-faiblesses/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'soumettre'])->name('auditor.ac.analyse-forces-faiblesses.soumettre');
Route::post('analyse-forces-faiblesses/{form}/valider', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'valider'])->name('auditor.ac.analyse-forces-faiblesses.valider');
   
    //rapport-orientation

// ── Rapport d'Orientation (RADO) ──────────────────────────────────────────────
// IMPORTANT : la route ai-suggest DOIT être déclarée AVANT {form}/* pour éviter les conflits
Route::post('rapport-orientation/ai-suggest', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'aiSuggest'])->name('auditor.ac.rapport-orientation.ai-suggest');

Route::get('rapport-orientation', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'index'])->name('audit.ac.preparation.rapport-orientation');
Route::post('rapport-orientation', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'store'])->name('auditor.ac.rapport-orientation.store');

Route::get('rapport-orientation/{form}/edit', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'edit'])->name('auditor.ac.rapport-orientation.edit');
Route::put('rapport-orientation/{form}', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'update'])->name('auditor.ac.rapport-orientation.update');
Route::delete('rapport-orientation/{form}', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'destroy'])->name('auditor.ac.rapport-orientation.destroy');
Route::post('rapport-orientation/{form}/soumettre', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'soumettre'])->name('auditor.ac.rapport-orientation.soumettre');
Route::post('rapport-orientation/{form}/valider', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'valider'])->name('auditor.ac.rapport-orientation.valider');

    //         // referentiels-audit
//         Route::get('referentiels-audit', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'index'])->name('audit.ac.preparation.referentiels-audit');
//         Route::get('referentiels-audit/{form}/edit', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'edit'])->name('auditor.ac.referentiels-audit.edit');
//         Route::post('referentiels-audit', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'store'])->name('auditor.ac.referentiels-audit.store');
//         Route::put('referentiels-audit/{form}', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'update'])->name('auditor.ac.referentiels-audit.update');
//         Route::delete('referentiels-audit/{form}', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'destroy'])->name('auditor.ac.referentiels-audit.destroy');
//         Route::post('referentiels-audit/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'soumettre'])->name('auditor.ac.referentiels-audit.soumettre');
//         Route::post('referentiels-audit/{form}/valider', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'valider'])->name('auditor.ac.referentiels-audit.valider');
        // referentiel-ci
        Route::get('referentiel-ci', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'index'])->name('audit.ac.preparation.referentiel-ci');
        Route::get('referentiel-ci/{form}/edit', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'edit'])->name('auditor.ac.referentiel-ci.edit');
        Route::post('referentiel-ci', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'store'])->name('auditor.ac.referentiel-ci.store');
        Route::put('referentiel-ci/{form}', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'update'])->name('auditor.ac.referentiel-ci.update');
        Route::delete('referentiel-ci/{form}', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'destroy'])->name('auditor.ac.referentiel-ci.destroy');
        Route::post('referentiel-ci/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'soumettre'])->name('auditor.ac.referentiel-ci.soumettre');
        Route::post('referentiel-ci/{form}/valider', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'valider'])->name('auditor.ac.referentiel-ci.valider');
        // referentiel-conformite
//         // referentiel-marches
 
// Routes RCM — Référentiel de Contrôle des Marchés Publics
// ════════════════════════════════════════════════════════════════════════

Route::get('referentiel-marches', [ReferentielMarchesController::class, 'index'])
    ->name('audit.ac.preparation.referentiel-marches');
Route::get('referentiel-marches/{form}/edit', [ReferentielMarchesController::class, 'edit'])
    ->name('auditor.ac.referentiel-marches.edit');
Route::post('referentiel-marches', [ReferentielMarchesController::class, 'store'])
    ->name('auditor.ac.referentiel-marches.store');
Route::put('referentiel-marches/{form}', [ReferentielMarchesController::class, 'update'])
    ->name('auditor.ac.referentiel-marches.update');
Route::post('referentiel-marches/{form}/soumettre', [ReferentielMarchesController::class, 'soumettre'])
    ->name('auditor.ac.referentiel-marches.soumettre');
Route::post('referentiel-marches/{form}/valider', [ReferentielMarchesController::class, 'valider'])
    ->name('auditor.ac.referentiel-marches.valider');
Route::delete('referentiel-marches/{form}', [ReferentielMarchesController::class, 'destroy'])
    ->name('auditor.ac.referentiel-marches.destroy');

// Affectations phases (DM/CM)
Route::put('referentiel-marches/{form}/phases', [ReferentielMarchesController::class, 'updatePhases'])
    ->name('auditor.ac.referentiel-marches.phases');

// CRUD critères
Route::post('referentiel-marches/{rcm}/critere', [ReferentielMarchesController::class, 'storeCritere'])
    ->name('auditor.ac.referentiel-marches.critere.store');
Route::put('referentiel-marches/critere/{critere}', [ReferentielMarchesController::class, 'updateCritere'])
    ->name('auditor.ac.referentiel-marches.critere.update');
Route::delete('referentiel-marches/critere/{critere}', [ReferentielMarchesController::class, 'destroyCritere'])
    ->name('auditor.ac.referentiel-marches.critere.destroy');

// Preuves
Route::post('referentiel-marches/{rcm}/upload-preuve', [ReferentielMarchesController::class, 'uploadPreuve'])
    ->name('auditor.ac.referentiel-marches.upload-preuve');
Route::delete('referentiel-marches/{rcm}/delete-preuve', [ReferentielMarchesController::class, 'deletePreuve'])
    ->name('auditor.ac.referentiel-marches.delete-preuve');


// ════════════════════════════════════════════════════════════════════════
// Routes RCT — Référentiel de Contrôle des Transactions Financières
// ════════════════════════════════════════════════════════════════════════

Route::get('referentiel-transactions', [ReferentielTransactionsController::class, 'index'])
    ->name('audit.ac.preparation.referentiel-transactions');
Route::get('referentiel-transactions/{form}/edit', [ReferentielTransactionsController::class, 'edit'])
    ->name('auditor.ac.referentiel-transactions.edit');
Route::post('referentiel-transactions', [ReferentielTransactionsController::class, 'store'])
    ->name('auditor.ac.referentiel-transactions.store');
Route::put('referentiel-transactions/{form}', [ReferentielTransactionsController::class, 'update'])
    ->name('auditor.ac.referentiel-transactions.update');
Route::post('referentiel-transactions/{form}/soumettre', [ReferentielTransactionsController::class, 'soumettre'])
    ->name('auditor.ac.referentiel-transactions.soumettre');
Route::post('referentiel-transactions/{form}/valider', [ReferentielTransactionsController::class, 'valider'])
    ->name('auditor.ac.referentiel-transactions.valider');
Route::delete('referentiel-transactions/{form}', [ReferentielTransactionsController::class, 'destroy'])
    ->name('auditor.ac.referentiel-transactions.destroy');

// Affectations catégories (DM/CM)
Route::put('referentiel-transactions/{form}/categories', [ReferentielTransactionsController::class, 'updateCategories'])
    ->name('auditor.ac.referentiel-transactions.categories');

// Save saisie (note + preuves) — save immédiat
Route::post('referentiel-transactions/{rct}/saisie', [ReferentielTransactionsController::class, 'saveSaisie'])
    ->name('auditor.ac.referentiel-transactions.saisie.save');

// Preuves
Route::post('referentiel-transactions/{rct}/upload-preuve', [ReferentielTransactionsController::class, 'uploadPreuve'])
    ->name('auditor.ac.referentiel-transactions.upload-preuve');
Route::delete('referentiel-transactions/{rct}/delete-preuve', [ReferentielTransactionsController::class, 'deletePreuve'])
    ->name('auditor.ac.referentiel-transactions.delete-preuve');

// IA
Route::post('referentiel-transactions/ia-suggestion', [ReferentielTransactionsController::class, 'iaSuggestion'])
    ->name('auditor.ac.referentiel-transactions.ia-suggestion');
Route::post('referentiel-transactions/{form}/ia-analyse', [ReferentielTransactionsController::class, 'iaAnalyseGlobale'])
    ->name('auditor.ac.referentiel-transactions.ia-analyse');
// ── Formulaire principal ─────────────────────────────────────────────────


 
// ── Formulaire principal ─────────────────────────────────────────────────
Route::get('referentiel-conformite', [ReferentielConformiteController::class, 'index'])
    ->name('audit.ac.preparation.referentiel-conformite');
 
Route::get('referentiel-conformite/{form}/edit', [ReferentielConformiteController::class, 'edit'])
    ->name('auditor.ac.referentiel-conformite.edit');
 
Route::post('referentiel-conformite', [ReferentielConformiteController::class, 'store'])
    ->name('auditor.ac.referentiel-conformite.store');
 
Route::put('referentiel-conformite/{form}', [ReferentielConformiteController::class, 'update'])
    ->name('auditor.ac.referentiel-conformite.update');
 
Route::delete('referentiel-conformite/{form}', [ReferentielConformiteController::class, 'destroy'])
    ->name('auditor.ac.referentiel-conformite.destroy');
 
// ── Workflow ─────────────────────────────────────────────────────────────
Route::post('referentiel-conformite/{form}/soumettre', [ReferentielConformiteController::class, 'soumettre'])
    ->name('auditor.ac.referentiel-conformite.soumettre');
 
Route::post('referentiel-conformite/{form}/valider', [ReferentielConformiteController::class, 'valider'])
    ->name('auditor.ac.referentiel-conformite.valider');
 
// ── Domaines (DM/CM uniquement) ───────────────────────────────────────────
Route::post('referentiel-conformite/{rcc}/domaine', [ReferentielConformiteController::class, 'storeDomaine'])
    ->name('auditor.ac.referentiel-conformite.domaine.store');
 
Route::put('referentiel-conformite/domaine/{domaine}', [ReferentielConformiteController::class, 'updateDomaine'])
    ->name('auditor.ac.referentiel-conformite.domaine.update');
 
Route::delete('referentiel-conformite/domaine/{domaine}', [ReferentielConformiteController::class, 'destroyDomaine'])
    ->name('auditor.ac.referentiel-conformite.domaine.destroy');
 
// ── Guide par domaine (NOUVEAU) ───────────────────────────────────────────
Route::post('referentiel-conformite/{rcc}/upload-guide', [ReferentielConformiteController::class, 'uploadGuide'])
    ->name('auditor.ac.referentiel-conformite.upload-guide');
 
Route::delete('referentiel-conformite/{rcc}/delete-guide', [ReferentielConformiteController::class, 'deleteGuide'])
    ->name('auditor.ac.referentiel-conformite.delete-guide');
 
// ── Génération IA (NOUVEAU) ───────────────────────────────────────────────
Route::post('referentiel-conformite/{rcc}/generate-ai', [ReferentielConformiteController::class, 'generateAi'])
    ->name('auditor.ac.referentiel-conformite.generate-ai');
 
// ── Critères (auditeur affecté ou DM/CM) ─────────────────────────────────
Route::post('referentiel-conformite/{rcc}/critere', [ReferentielConformiteController::class, 'storeCritere'])
    ->name('auditor.ac.referentiel-conformite.critere.store');
 
Route::put('referentiel-conformite/critere/{critere}', [ReferentielConformiteController::class, 'updateCritere'])
    ->name('auditor.ac.referentiel-conformite.critere.update');
 
Route::delete('referentiel-conformite/critere/{critere}', [ReferentielConformiteController::class, 'destroyCritere'])
    ->name('auditor.ac.referentiel-conformite.critere.destroy');
 
// ── Preuves par critère ───────────────────────────────────────────────────
Route::post('referentiel-conformite/{rcc}/upload-preuve', [ReferentielConformiteController::class, 'uploadPreuve'])
    ->name('auditor.ac.referentiel-conformite.upload-preuve');
 
Route::delete('referentiel-conformite/{rcc}/delete-preuve', [ReferentielConformiteController::class, 'deletePreuve'])
    ->name('auditor.ac.referentiel-conformite.delete-preuve');
 
    //         // programmes-travail
        // Route::get('programmes-travail', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'index'])->name('audit.ac.preparation.programmes-travail');
        // Route::get('programmes-travail/{form}/edit', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'edit'])->name('auditor.ac.programmes-travail.edit');
        // Route::post('programmes-travail', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'store'])->name('auditor.ac.programmes-travail.store');
        // Route::put('programmes-travail/{form}', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'update'])->name('auditor.ac.programmes-travail.update');
        // Route::delete('programmes-travail/{form}', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'destroy'])->name('auditor.ac.programmes-travail.destroy');
        // Route::post('programmes-travail/{form}/soumettre', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'soumettre'])->name('auditor.ac.programmes-travail.soumettre');
        // Route::post('programmes-travail/{form}/valider', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'valider'])->name('auditor.ac.programmes-travail.valider');
//         // prog-ci

// CRUD
// ── IMPORTANT : routes fixes AVANT les routes paramétrées {form} ──

// Modèle Excel (DOIT être avant {form}/*)
Route::get('prog-ci/modele-excel',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'telechargerModele'])
    ->name('auditor.ac.prog-ci.modele-excel');

// IA sans {form} — si tu en as besoin globalement
// (sinon ces routes sont déjà sur {form}/ai-suggest ci-dessous)

// Index
Route::get('prog-ci',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'index'])
    ->name('audit.ac.preparation.prog-ci');

// Store
Route::post('prog-ci',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'store'])
    ->name('auditor.ac.prog-ci.store');

// ── Routes paramétrées {form} ────────────────────────────────────────
Route::get('prog-ci/{form}/edit',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'edit'])
    ->name('auditor.ac.prog-ci.edit');

Route::put('prog-ci/{form}',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'update'])
    ->name('auditor.ac.prog-ci.update');

Route::delete('prog-ci/{form}',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'destroy'])
    ->name('auditor.ac.prog-ci.destroy');

Route::post('prog-ci/{form}/soumettre',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'soumettre'])
    ->name('auditor.ac.prog-ci.soumettre');

Route::post('prog-ci/{form}/valider',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'valider'])
    ->name('auditor.ac.prog-ci.valider');

Route::post('prog-ci/{form}/ai-suggest',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'aiSuggest'])
    ->name('auditor.ac.prog-ci.ai-suggest');

Route::post('prog-ci/{form}/ai-synthese',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'aiSynthese'])
    ->name('auditor.ac.prog-ci.ai-synthese');

Route::post('prog-ci/{form}/upload',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'uploadExcel'])
    ->name('auditor.ac.prog-ci.upload');
// ── Routes IA ───────────────────────────────────────────────────────────────────
//
// POST prog-ci/{form}/ai-suggest
//   Body : {
//     ligne:   { ref_objectif, objectif_ci, ref_controle_rci,
//                test_controle, procedures_ci,
//                resultat_test, nb_anomalies, taux_conformite, … },
//     champ:   'test_controle' | 'procedures_ci' |
//              'conclusion_auditeur' | 'objectif_ci',
//     context: { processus, risque_libelle, … }
//   }
//   Retour : { success, suggestions: string[], mode: 'ai'|'fallback' }
//
Route::post('prog-ci/{form}/ai-suggest',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'aiSuggest'])
    ->name('auditor.ac.prog-ci.ai-suggest');

//
// POST prog-ci/{form}/ai-synthese
//   Body  : { lignes: [...] }  (optionnel — utilise la BD si vide)
//   Retour : { success, synthese: string, mode: 'ai'|'fallback' }
//
Route::post('prog-ci/{form}/ai-synthese',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'aiSynthese'])
    ->name('auditor.ac.prog-ci.ai-synthese');

//
// POST prog-ci/{form}/ai-accept
//   Body  : { ligne_num: int|null, champ: string, valeur_acceptee: string }
//   Effet : enregistre dans prog_ci_ai_suggestions.accepted = true
//
Route::post('prog-ci/{form}/ai-accept',
    [App\Http\Controllers\Auditor\ProgCiController::class, 'aiAccept'])
    ->name('auditor.ac.prog-ci.ai-accept');      


//prog-conformite
        Route::get('prog-conformite', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'index'])->name('audit.ac.preparation.prog-conformite');
        Route::get('prog-conformite/{form}/edit', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'edit'])->name('auditor.ac.prog-conformite.edit');
        Route::post('prog-conformite', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'store'])->name('auditor.ac.prog-conformite.store');
        Route::put('prog-conformite/{form}', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'update'])->name('auditor.ac.prog-conformite.update');
        Route::delete('prog-conformite/{form}', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'destroy'])->name('auditor.ac.prog-conformite.destroy');
        Route::post('prog-conformite/{form}/soumettre', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'soumettre'])->name('auditor.ac.prog-conformite.soumettre');
        Route::post('prog-conformite/{form}/valider', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'valider'])->name('auditor.ac.prog-conformite.valider');
        // prog-marches
        Route::get('prog-marches', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'index'])->name('audit.ac.preparation.prog-marches');
        Route::get('prog-marches/{form}/edit', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'edit'])->name('auditor.ac.prog-marches.edit');
        Route::post('prog-marches', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'store'])->name('auditor.ac.prog-marches.store');
        Route::put('prog-marches/{form}', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'update'])->name('auditor.ac.prog-marches.update');
        Route::delete('prog-marches/{form}', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'destroy'])->name('auditor.ac.prog-marches.destroy');
        Route::post('prog-marches/{form}/soumettre', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'soumettre'])->name('auditor.ac.prog-marches.soumettre');
        Route::post('prog-marches/{form}/valider', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'valider'])->name('auditor.ac.prog-marches.valider');
        // prog-transactions
        Route::get('prog-transactions', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'index'])->name('audit.ac.preparation.prog-transactions');
        Route::get('prog-transactions/{form}/edit', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'edit'])->name('auditor.ac.prog-transactions.edit');
        Route::post('prog-transactions', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'store'])->name('auditor.ac.prog-transactions.store');
        Route::put('prog-transactions/{form}', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'update'])->name('auditor.ac.prog-transactions.update');
        Route::delete('prog-transactions/{form}', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'destroy'])->name('auditor.ac.prog-transactions.destroy');
        Route::post('prog-transactions/{form}/soumettre', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'soumettre'])->name('auditor.ac.prog-transactions.soumettre');
        Route::post('prog-transactions/{form}/valider', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'valider'])->name('auditor.ac.prog-transactions.valider');
   
//     // ── Phase 2 : Realisation ──

//     // ── Phase 3 : Conclusion ──
//     Route::prefix('conclusion')->group(function () {
//         // projet-rapport
//         Route::get('projet-rapport', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'index'])->name('audit.ac.conclusion.projet-rapport');
//         Route::get('projet-rapport/{form}/edit', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'edit'])->name('auditor.ac.projet-rapport.edit');
//         Route::post('projet-rapport', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'store'])->name('auditor.ac.projet-rapport.store');
//         Route::put('projet-rapport/{form}', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'update'])->name('auditor.ac.projet-rapport.update');
//         Route::delete('projet-rapport/{form}', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'destroy'])->name('auditor.ac.projet-rapport.destroy');
//         Route::post('projet-rapport/{form}/soumettre', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'soumettre'])->name('auditor.ac.projet-rapport.soumettre');
//         Route::post('projet-rapport/{form}/valider', [App\Http\Controllers\Auditor\ProjetRapportController::class, 'valider'])->name('auditor.ac.projet-rapport.valider');
//         // synthese-constats
//         Route::get('synthese-constats', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'index'])->name('audit.ac.conclusion.synthese-constats');
//         Route::get('synthese-constats/{form}/edit', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'edit'])->name('auditor.ac.synthese-constats.edit');
//         Route::post('synthese-constats', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'store'])->name('auditor.ac.synthese-constats.store');
//         Route::put('synthese-constats/{form}', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'update'])->name('auditor.ac.synthese-constats.update');
//         Route::delete('synthese-constats/{form}', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'destroy'])->name('auditor.ac.synthese-constats.destroy');
//         Route::post('synthese-constats/{form}/soumettre', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'soumettre'])->name('auditor.ac.synthese-constats.soumettre');
//         Route::post('synthese-constats/{form}/valider', [App\Http\Controllers\Auditor\SyntheseConstatsController::class, 'valider'])->name('auditor.ac.synthese-constats.valider');
//         // rapport-conformite
//         Route::get('rapport-conformite', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'index'])->name('audit.ac.conclusion.rapport-conformite');
//         Route::get('rapport-conformite/{form}/edit', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'edit'])->name('auditor.ac.rapport-conformite.edit');
//         Route::post('rapport-conformite', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'store'])->name('auditor.ac.rapport-conformite.store');
//         Route::put('rapport-conformite/{form}', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'update'])->name('auditor.ac.rapport-conformite.update');
//         Route::delete('rapport-conformite/{form}', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'destroy'])->name('auditor.ac.rapport-conformite.destroy');
//         Route::post('rapport-conformite/{form}/soumettre', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'soumettre'])->name('auditor.ac.rapport-conformite.soumettre');
//         Route::post('rapport-conformite/{form}/valider', [App\Http\Controllers\Auditor\RapportConformiteController::class, 'valider'])->name('auditor.ac.rapport-conformite.valider');
//         // plan-mise-en-oeuvre
//         Route::get('plan-mise-en-oeuvre', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'index'])->name('audit.ac.conclusion.plan-mise-en-oeuvre');
//         Route::get('plan-mise-en-oeuvre/{form}/edit', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'edit'])->name('auditor.ac.plan-mise-en-oeuvre.edit');
//         Route::post('plan-mise-en-oeuvre', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'store'])->name('auditor.ac.plan-mise-en-oeuvre.store');
//         Route::put('plan-mise-en-oeuvre/{form}', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'update'])->name('auditor.ac.plan-mise-en-oeuvre.update');
//         Route::delete('plan-mise-en-oeuvre/{form}', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'destroy'])->name('auditor.ac.plan-mise-en-oeuvre.destroy');
//         Route::post('plan-mise-en-oeuvre/{form}/soumettre', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'soumettre'])->name('auditor.ac.plan-mise-en-oeuvre.soumettre');
//         Route::post('plan-mise-en-oeuvre/{form}/valider', [App\Http\Controllers\Auditor\PlanMiseEnOeuvreController::class, 'valider'])->name('auditor.ac.plan-mise-en-oeuvre.valider');
//     }); // end conclusion
//     // ── Phase 4 : Suivi ──
//     Route::prefix('suivi')->group(function () {
//         // plan-suivi
//         Route::get('plan-suivi', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'index'])->name('audit.ac.suivi.plan-suivi');
//         Route::get('plan-suivi/{form}/edit', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'edit'])->name('auditor.ac.plan-suivi.edit');
//         Route::post('plan-suivi', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'store'])->name('auditor.ac.plan-suivi.store');
//         Route::put('plan-suivi/{form}', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'update'])->name('auditor.ac.plan-suivi.update');
//         Route::delete('plan-suivi/{form}', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'destroy'])->name('auditor.ac.plan-suivi.destroy');
//         Route::post('plan-suivi/{form}/soumettre', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'soumettre'])->name('auditor.ac.plan-suivi.soumettre');
//         Route::post('plan-suivi/{form}/valider', [App\Http\Controllers\Auditor\PlanSuiviController::class, 'valider'])->name('auditor.ac.plan-suivi.valider');
//         // fiche-suivi
//         Route::get('fiche-suivi', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'index'])->name('audit.ac.suivi.fiche-suivi');
//         Route::get('fiche-suivi/{form}/edit', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'edit'])->name('auditor.ac.fiche-suivi.edit');
//         Route::post('fiche-suivi', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'store'])->name('auditor.ac.fiche-suivi.store');
//         Route::put('fiche-suivi/{form}', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'update'])->name('auditor.ac.fiche-suivi.update');
//         Route::delete('fiche-suivi/{form}', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'destroy'])->name('auditor.ac.fiche-suivi.destroy');
//         Route::post('fiche-suivi/{form}/soumettre', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'soumettre'])->name('auditor.ac.fiche-suivi.soumettre');
//         Route::post('fiche-suivi/{form}/valider', [App\Http\Controllers\Auditor\FicheSuiviController::class, 'valider'])->name('auditor.ac.fiche-suivi.valider');
//         // rapport-suivi
//         Route::get('rapport-suivi', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'index'])->name('audit.ac.suivi.rapport-suivi');
//         Route::get('rapport-suivi/{form}/edit', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'edit'])->name('auditor.ac.rapport-suivi.edit');
//         Route::post('rapport-suivi', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'store'])->name('auditor.ac.rapport-suivi.store');
//         Route::put('rapport-suivi/{form}', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'update'])->name('auditor.ac.rapport-suivi.update');
//         Route::delete('rapport-suivi/{form}', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'destroy'])->name('auditor.ac.rapport-suivi.destroy');
//         Route::post('rapport-suivi/{form}/soumettre', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'soumettre'])->name('auditor.ac.rapport-suivi.soumettre');
//         Route::post('rapport-suivi/{form}/valider', [App\Http\Controllers\Auditor\RapportSuiviController::class, 'valider'])->name('auditor.ac.rapport-suivi.valider');
//     }); // end suivi
//     // ── Phase 5 : Recommandation ──
//     Route::prefix('recommandation')->group(function () {
//         // fiche-recommandation
//         Route::get('fiche-recommandation', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'index'])->name('audit.ac.recommandation.fiche-recommandation');
//         Route::get('fiche-recommandation/{form}/edit', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'edit'])->name('auditor.ac.fiche-recommandation.edit');
//         Route::post('fiche-recommandation', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'store'])->name('auditor.ac.fiche-recommandation.store');
//         Route::put('fiche-recommandation/{form}', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'update'])->name('auditor.ac.fiche-recommandation.update');
//         Route::delete('fiche-recommandation/{form}', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'destroy'])->name('auditor.ac.fiche-recommandation.destroy');
//         Route::post('fiche-recommandation/{form}/soumettre', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'soumettre'])->name('auditor.ac.fiche-recommandation.soumettre');
//         Route::post('fiche-recommandation/{form}/valider', [App\Http\Controllers\Auditor\FicheRecommandationController::class, 'valider'])->name('auditor.ac.fiche-recommandation.valider');
//         // plan-action
//         Route::get('plan-action', [App\Http\Controllers\Auditor\PlanActionController::class, 'index'])->name('audit.ac.recommandation.plan-action');
//         Route::get('plan-action/{form}/edit', [App\Http\Controllers\Auditor\PlanActionController::class, 'edit'])->name('auditor.ac.plan-action.edit');
//         Route::post('plan-action', [App\Http\Controllers\Auditor\PlanActionController::class, 'store'])->name('auditor.ac.plan-action.store');
//         Route::put('plan-action/{form}', [App\Http\Controllers\Auditor\PlanActionController::class, 'update'])->name('auditor.ac.plan-action.update');
//         Route::delete('plan-action/{form}', [App\Http\Controllers\Auditor\PlanActionController::class, 'destroy'])->name('auditor.ac.plan-action.destroy');
//         Route::post('plan-action/{form}/soumettre', [App\Http\Controllers\Auditor\PlanActionController::class, 'soumettre'])->name('auditor.ac.plan-action.soumettre');
//         Route::post('plan-action/{form}/valider', [App\Http\Controllers\Auditor\PlanActionController::class, 'valider'])->name('auditor.ac.plan-action.valider');
//         // validation-plan
//         Route::get('validation-plan', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'index'])->name('audit.ac.recommandation.validation-plan');
//         Route::get('validation-plan/{form}/edit', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'edit'])->name('auditor.ac.validation-plan.edit');
//         Route::post('validation-plan', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'store'])->name('auditor.ac.validation-plan.store');
//         Route::put('validation-plan/{form}', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'update'])->name('auditor.ac.validation-plan.update');
//         Route::delete('validation-plan/{form}', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'destroy'])->name('auditor.ac.validation-plan.destroy');
//         Route::post('validation-plan/{form}/soumettre', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'soumettre'])->name('auditor.ac.validation-plan.soumettre');
//         Route::post('validation-plan/{form}/valider', [App\Http\Controllers\Auditor\ValidationPlanController::class, 'valider'])->name('auditor.ac.validation-plan.valider');
//     }); // end recommandation
 }); // end ac

  Route::prefix('auditor/fiche-test')->name('auditor.ac.fiche-test.')->group(function () {
        
        Route::get('/',                    [FicheTestController::class, 'index'])->name('index');
        Route::get('/{form}/edit',         [FicheTestController::class, 'edit'])->name('edit');
        Route::post('/',                   [FicheTestController::class, 'store'])->name('store');
        Route::put('/{form}',              [FicheTestController::class, 'update'])->name('update');
        Route::post('/{form}/soumettre',   [FicheTestController::class, 'soumettre'])->name('soumettre');
        Route::post('/{form}/valider',     [FicheTestController::class, 'valider'])->name('valider');
        Route::delete('/{form}',           [FicheTestController::class, 'destroy'])->name('destroy');
        // Actions outils IFACI integrees dans la FicheTest
        //Route::post('/{form}/save-outil',  [FicheTestController::class, 'saveOutil'])->name('save-outil');
        Route::get('/{form}/load-outil',   [FicheTestController::class, 'loadOutil'])->name('load-outil');
        Route::post('/{form}/ia-global',   [FicheTestController::class, 'iaGlobal'])->name('ia-global');
        Route::post('/{form}/email',       [FicheTestController::class, 'envoyerEmail'])->name('email');
        Route::post('/{form}/save-outil',  [FicheTestController::class, 'saveOutil'])->name('save-outil');
    });
   Route::prefix('realisation')->group(function () {
         // reunion-lancement
        Route::get('reunion-lancement', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'index'])->name('audit.ac.realisation.reunion-lancement');
        Route::get('reunion-lancement/{form}/edit', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'edit'])->name('auditor.ac.reunion-lancement.edit');
        Route::post('reunion-lancement', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'store'])->name('auditor.ac.reunion-lancement.store');
        Route::put('reunion-lancement/{form}', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'update'])->name('auditor.ac.reunion-lancement.update');
        Route::delete('reunion-lancement/{form}', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'destroy'])->name('auditor.ac.reunion-lancement.destroy');
        Route::post('reunion-lancement/{form}/soumettre', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'soumettre'])->name('auditor.ac.reunion-lancement.soumettre');
        Route::post('reunion-lancement/{form}/valider', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'valider'])->name('auditor.ac.reunion-lancement.valider');


Route::get('fiche-test',                                      [FicheTestController::class, 'index'])->name('audit.ac.realisation.fiche-test');
Route::get('fiche-test/{form}/edit',                          [FicheTestController::class, 'edit'])->name('auditor.ac.fiche-test.edit');
Route::post('fiche-test',                                     [FicheTestController::class, 'store'])->name('auditor.ac.fiche-test.store');
Route::put('fiche-test/{form}',                               [FicheTestController::class, 'update'])->name('auditor.ac.fiche-test.update');
Route::delete('fiche-test/{form}',                            [FicheTestController::class, 'destroy'])->name('auditor.ac.fiche-test.destroy');
Route::post('fiche-test/{form}/soumettre',                    [FicheTestController::class, 'soumettre'])->name('auditor.ac.fiche-test.soumettre');
Route::post('fiche-test/{form}/valider',                      [FicheTestController::class, 'valider'])->name('auditor.ac.fiche-test.valider');
Route::post('fiche-test/{form}/save-outil',                   [FicheTestController::class, 'saveOutil'])->name('auditor.ac.fiche-test.save-outil');
Route::get('fiche-test/{form}/load-outil',                    [FicheTestController::class, 'loadOutil'])->name('auditor.ac.fiche-test.load-outil');
Route::post('fiche-test/{form}/auto-save',                    [FicheTestController::class, 'autoSaveEndpoint'])->name('auditor.ac.fiche-test.auto-save');
Route::post('fiche-test/{form}/ia-global',                    [FicheTestController::class, 'iaGlobal'])->name('auditor.ac.fiche-test.ia-global');
Route::post('fiche-test/{form}/email',                        [FicheTestController::class, 'envoyerEmail'])->name('auditor.ac.fiche-test.email');
Route::get('fiche-test/{ficheTestId}/fraps',                  [FicheTestController::class, 'getFraps'])->name('auditor.ac.fiche-test.frap.index');
Route::post('fiche-test/{ficheTestId}/fraps',                 [FicheTestController::class, 'storeFrap'])->name('auditor.ac.fiche-test.frap.store');
Route::put('fiche-test/{ficheTestId}/fraps/{id}',             [FicheTestController::class, 'updateFrap'])->name('auditor.ac.fiche-test.frap.update');
Route::delete('fiche-test/{ficheTestId}/fraps/{id}',          [FicheTestController::class, 'destroyFrap'])->name('auditor.ac.fiche-test.frap.destroy');
Route::post('fiche-test/{ficheTestId}/synthese-generer',      [FicheTestController::class, 'genererSynthese'])->name('auditor.ac.fiche-test.synthese-generer');
// ── Confirmation publique email (hors auth) ───────────────────────────────
// Route::get('confirmation/{token}', [FicheTestController::class, 'confirmationPublique'])
//     ->name('audit.confirmation.publique')
//     ->withoutMiddleware(['auth']);


 
});




Route::prefix('reunion-cloture')->name('audit.ac.reunion-cloture.')->group(function () {

    // ── Vues Inertia ─────────────────────────────────────────────────────
    Route::get('/',         [ReunionClotureController::class, 'index'])->name('index');
    Route::get('{id}/edit', [ReunionClotureController::class, 'edit'])->name('edit');

    // ── CRUD principal ────────────────────────────────────────────────────
    Route::post('/',      [ReunionClotureController::class, 'store'])->name('store');
    Route::put('{id}',    [ReunionClotureController::class, 'update'])->name('update');
    Route::delete('{id}', [ReunionClotureController::class, 'destroy'])->name('destroy');

    // ── Workflow statuts ──────────────────────────────────────────────────
    Route::post('{id}/soumettre', [ReunionClotureController::class, 'soumettre'])->name('soumettre');
    Route::post('{id}/valider',   [ReunionClotureController::class, 'valider'])->name('valider');

    // ── FAR ───────────────────────────────────────────────────────────────
    Route::post('{id}/far',        [ReunionClotureController::class, 'storeFar'])->name('far.store');
    Route::put('{id}/far/{farId}', [ReunionClotureController::class, 'updateFar'])->name('far.update');

    // ── Refresh FRAPs → FAR (remplace sync-fraps) ─────────────────────────
    Route::post('{id}/refresh-fraps', [ReunionClotureController::class, 'refreshFraps'])->name('refresh-fraps');

    // ── Signatures ────────────────────────────────────────────────────────
    Route::post('{id}/signature', [ReunionClotureController::class, 'signature'])->name('signature');
});



  
 
 
  
// Debug route (accessible sans auth pour test, mais à sécuriser en prod)
Route::get('reunion-cloture/debug', [ReunionClotureController::class, 'debug'])
    ->name('audit.ac.reunion-cloture.debug');




        // ══════════════════════════════════════════════════════════════════
    // PLAN D'ACTION – API (FRAP, priorité, suivi)
    // ══════════════════════════════════════════════════════════════════

    // ── Plan d'action (Recommandation) ──────────────────────────────

    
// Routes normales avec middleware auth

//         // evaluations
//         Route::get('evaluations', [App\Http\Controllers\Auditor\EvaluationsController::class, 'index'])->name('audit.ac.realisation.evaluations');
//         Route::get('evaluations/{form}/edit', [App\Http\Controllers\Auditor\EvaluationsController::class, 'edit'])->name('auditor.ac.evaluations.edit');
//         Route::post('evaluations', [App\Http\Controllers\Auditor\EvaluationsController::class, 'store'])->name('auditor.ac.evaluations.store');
//         Route::put('evaluations/{form}', [App\Http\Controllers\Auditor\EvaluationsController::class, 'update'])->name('auditor.ac.evaluations.update');
//         Route::delete('evaluations/{form}', [App\Http\Controllers\Auditor\EvaluationsController::class, 'destroy'])->name('auditor.ac.evaluations.destroy');
//         Route::post('evaluations/{form}/soumettre', [App\Http\Controllers\Auditor\EvaluationsController::class, 'soumettre'])->name('auditor.ac.evaluations.soumettre');
//         Route::post('evaluations/{form}/valider', [App\Http\Controllers\Auditor\EvaluationsController::class, 'valider'])->name('auditor.ac.evaluations.valider');
//         // eval-ci-qci
//         Route::get('eval-ci-qci', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'index'])->name('audit.ac.realisation.eval-ci-qci');
//         Route::get('eval-ci-qci/{form}/edit', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'edit'])->name('auditor.ac.eval-ci-qci.edit');
//         Route::post('eval-ci-qci', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'store'])->name('auditor.ac.eval-ci-qci.store');
//         Route::put('eval-ci-qci/{form}', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'update'])->name('auditor.ac.eval-ci-qci.update');
//         Route::delete('eval-ci-qci/{form}', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'destroy'])->name('auditor.ac.eval-ci-qci.destroy');
//         Route::post('eval-ci-qci/{form}/soumettre', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'soumettre'])->name('auditor.ac.eval-ci-qci.soumettre');
//         Route::post('eval-ci-qci/{form}/valider', [App\Http\Controllers\Auditor\EvalCiQciController::class, 'valider'])->name('auditor.ac.eval-ci-qci.valider');
//         // eval-conformite
//         Route::get('eval-conformite', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'index'])->name('audit.ac.realisation.eval-conformite');
//         Route::get('eval-conformite/{form}/edit', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'edit'])->name('auditor.ac.eval-conformite.edit');
//        / ── Plan d'action (suivi des FRAP) ──────────────────────────────
Route::prefix('plan-action')->name('auditor.ac.plan-action.')->group(function () {
    Route::get('/',                 [PlanActionController::class, 'index'])->name('index');
    Route::get('/api/data',         [PlanActionController::class, 'getData'])->name('api.data');
    Route::get('/dashboard',        [PlanActionController::class, 'dashboard'])->name('dashboard');
    Route::put('/frap/{frapId}/priorite', [PlanActionController::class, 'updatePriorite'])->name('priorite.update');
    Route::put('/frap/{frapId}/suivi',    [PlanActionController::class, 'updateSuivi'])->name('suivi.update');
});

// Rapport d'audit global (sans assignment_id)
// Compatibilité anciens appels (query string)
// Route de compatibilité (anciens appels)
Route::get('/rapport', function () {
    $missionId = request()->query('mission_id');
    if (!$missionId) abort(400, 'Mission ID manquant');
    return redirect()->route('auditor.ac.rapport.generer', ['missionId' => $missionId]);
})->name('rapport.legacy');

// Groupe des routes rapport (nouvelles URL)


Route::get('/rapport', function () {
    $missionId = request()->query('mission_id');
    if (!$missionId) abort(400, 'Mission ID manquant');
    return redirect()->route('auditor.ac.rapport.index', ['missionId' => $missionId]);
})->name('rapport.legacy');
 
Route::prefix('rapport')->name('auditor.ac.rapport.')->group(function () {
 
    // Page Inertia → RapportWordModal s'ouvre automatiquement
    Route::get('/mission/{missionId}', [RapportAuditWordController::class, 'index'])
        ->name('index');
 
    // API JSON — données pour l'aperçu (constats, stats, objectifs…)
    Route::post('/mission/{missionId}/data', [RapportAuditWordController::class, 'data'])
        ->name('word.data');
 
    // Génération + téléchargement .docx binaire
    Route::post('/mission/{missionId}/generate', [RapportAuditWordController::class, 'generate'])
        ->name('word.generate');
 
    // Sauvegarde des champs éditables en base
    Route::put('/mission/{missionId}/edits', [RapportAuditWordController::class, 'saveEdits'])
        ->name('word.edits');
});
Route::post('missions/{missionId}/rapport/html', [RapportAuditWordController::class, 'exportHtml'])
     ->name('auditor.ac.rapport.word.html');

Route::prefix('audit.core/ac/rapport')->name('auditor.rapport.')->middleware(['auth', /* vos middlewares */])->group(function () {

 
Route::post('missions/{missionId}/rapport/html', [RapportAuditWordController::class, 'exportHtml'])
     ->name('auditor.ac.rapport.word.html');
    // Génère et télécharge le .html
    Route::post('/mission/{missionId}/export-html',  [RapportAuditWordController::class, 'exportHtml'])->name('export-html');

    // Sauvegarde les champs éditables en base
    Route::put('/mission/{missionId}/save',          [RapportAuditWordController::class, 'save'])      ->name('save');
});
//         Route::put('eval-conformite/{form}', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'update'])->name('auditor.ac.eval-conformite.update');
//         Route::delete('eval-conformite/{form}', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'destroy'])->name('auditor.ac.eval-conformite.destroy');
//         Route::post('eval-conformite/{form}/soumettre', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'soumettre'])->name('auditor.ac.eval-conformite.soumettre');
//         Route::post('eval-conformite/{form}/valider', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'valider'])->name('auditor.ac.eval-conformite.valider');
//         // controle-transactions
//         Route::get('controle-transactions', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'index'])->name('audit.ac.realisation.controle-transactions');
//         Route::get('controle-transactions/{form}/edit', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'edit'])->name('auditor.ac.controle-transactions.edit');
//         Route::post('controle-transactions', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'store'])->name('auditor.ac.controle-transactions.store');
//         Route::put('controle-transactions/{form}', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'update'])->name('auditor.ac.controle-transactions.update');
//         Route::delete('controle-transactions/{form}', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'destroy'])->name('auditor.ac.controle-transactions.destroy');
//         Route::post('controle-transactions/{form}/soumettre', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'soumettre'])->name('auditor.ac.controle-transactions.soumettre');
//         Route::post('controle-transactions/{form}/valider', [App\Http\Controllers\Auditor\ControleTransactionsController::class, 'valider'])->name('auditor.ac.controle-transactions.valider');
//         // test-procedures
        // Route::get('test-procedures', [App\Http\Controllers\Auditor\TestProceduresController::class, 'index'])->name('audit.ac.realisation.test-procedures');
        // Route::get('test-procedures/{form}/edit', [App\Http\Controllers\Auditor\TestProceduresController::class, 'edit'])->name('auditor.ac.test-procedures.edit');
        // Route::post('test-procedures', [App\Http\Controllers\Auditor\TestProceduresController::class, 'store'])->name('auditor.ac.test-procedures.store');
        // Route::put('test-procedures/{form}', [App\Http\Controllers\Auditor\TestProceduresController::class, 'update'])->name('auditor.ac.test-procedures.update');
        // Route::delete('test-procedures/{form}', [App\Http\Controllers\Auditor\TestProceduresController::class, 'destroy'])->name('auditor.ac.test-procedures.destroy');
        // Route::post('test-procedures/{form}/soumettre', [App\Http\Controllers\Auditor\TestProceduresController::class, 'soumettre'])->name('auditor.ac.test-procedures.soumettre');
        // Route::post('test-procedures/{form}/valider', [App\Http\Controllers\Auditor\TestProceduresController::class, 'valider'])->name('auditor.ac.test-procedures.valider');
//         // test-repartition-taches
//         Route::get('test-repartition-taches', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'index'])->name('audit.ac.realisation.test-repartition-taches');
//         Route::get('test-repartition-taches/{form}/edit', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'edit'])->name('auditor.ac.test-repartition-taches.edit');
//         Route::post('test-repartition-taches', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'store'])->name('auditor.ac.test-repartition-taches.store');
//         Route::put('test-repartition-taches/{form}', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'update'])->name('auditor.ac.test-repartition-taches.update');
//         Route::delete('test-repartition-taches/{form}', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'destroy'])->name('auditor.ac.test-repartition-taches.destroy');
//         Route::post('test-repartition-taches/{form}/soumettre', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'soumettre'])->name('auditor.ac.test-repartition-taches.soumettre');
//         Route::post('test-repartition-taches/{form}/valider', [App\Http\Controllers\Auditor\TestRepartitionTachesController::class, 'valider'])->name('auditor.ac.test-repartition-taches.valider');
//         // eval-marches
//         Route::get('eval-marches', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'index'])->name('audit.ac.realisation.eval-marches');
//         Route::get('eval-marches/{form}/edit', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'edit'])->name('auditor.ac.eval-marches.edit');
//         Route::post('eval-marches', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'store'])->name('auditor.ac.eval-marches.store');
//         Route::put('eval-marches/{form}', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'update'])->name('auditor.ac.eval-marches.update');
//         Route::delete('eval-marches/{form}', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'destroy'])->name('auditor.ac.eval-marches.destroy');
//         Route::post('eval-marches/{form}/soumettre', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'soumettre'])->name('auditor.ac.eval-marches.soumettre');
//         Route::post('eval-marches/{form}/valider', [App\Http\Controllers\Auditor\EvalMarchesController::class, 'valider'])->name('auditor.ac.eval-marches.valider');
//         // feuilles-travail
//         Route::get('feuilles-travail', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'index'])->name('audit.ac.realisation.feuilles-travail');
//         Route::get('feuilles-travail/{form}/edit', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'edit'])->name('auditor.ac.feuilles-travail.edit');
//         Route::post('feuilles-travail', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'store'])->name('auditor.ac.feuilles-travail.store');
//         Route::put('feuilles-travail/{form}', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'update'])->name('auditor.ac.feuilles-travail.update');
//         Route::delete('feuilles-travail/{form}', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'destroy'])->name('auditor.ac.feuilles-travail.destroy');
//         Route::post('feuilles-travail/{form}/soumettre', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'soumettre'])->name('auditor.ac.feuilles-travail.soumettre');
//         Route::post('feuilles-travail/{form}/valider', [App\Http\Controllers\Auditor\FeuillesTravailController::class, 'valider'])->name('auditor.ac.feuilles-travail.valider');
//         // constats-observations
//         Route::get('constats-observations', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'index'])->name('audit.ac.realisation.constats-observations');
//         Route::get('constats-observations/{form}/edit', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'edit'])->name('auditor.ac.constats-observations.edit');
//         Route::post('constats-observations', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'store'])->name('auditor.ac.constats-observations.store');
//         Route::put('constats-observations/{form}', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'update'])->name('auditor.ac.constats-observations.update');
//         Route::delete('constats-observations/{form}', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'destroy'])->name('auditor.ac.constats-observations.destroy');
//         Route::post('constats-observations/{form}/soumettre', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'soumettre'])->name('auditor.ac.constats-observations.soumettre');
//         Route::post('constats-observations/{form}/valider', [App\Http\Controllers\Auditor\ConstatsObservationsController::class, 'valider'])->name('auditor.ac.constats-observations.valider');
//         // fiche-constat
//         Route::get('fiche-constat', [App\Http\Controllers\Auditor\FicheConstatController::class, 'index'])->name('audit.ac.realisation.fiche-constat');
//         Route::get('fiche-constat/{form}/edit', [App\Http\Controllers\Auditor\FicheConstatController::class, 'edit'])->name('auditor.ac.fiche-constat.edit');
//         Route::post('fiche-constat', [App\Http\Controllers\Auditor\FicheConstatController::class, 'store'])->name('auditor.ac.fiche-constat.store');
//         Route::put('fiche-constat/{form}', [App\Http\Controllers\Auditor\FicheConstatController::class, 'update'])->name('auditor.ac.fiche-constat.update');
//         Route::delete('fiche-constat/{form}', [App\Http\Controllers\Auditor\FicheConstatController::class, 'destroy'])->name('auditor.ac.fiche-constat.destroy');
//         Route::post('fiche-constat/{form}/soumettre', [App\Http\Controllers\Auditor\FicheConstatController::class, 'soumettre'])->name('auditor.ac.fiche-constat.soumettre');
//         Route::post('fiche-constat/{form}/valider', [App\Http\Controllers\Auditor\FicheConstatController::class, 'valider'])->name('auditor.ac.fiche-constat.valider');
     }); // end realisation

// ═══════════════════════════════════════════════════════════════════════════
//  À AJOUTER dans routes/web.php
//  Dans le groupe : Route::prefix('m/audit.core')->middleware(['web','auth','audit.session'])
// ═══════════════════════════════════════════════════════════════════════════


Route::prefix('param-marches')
    ->name('audit.param-marches.')
    ->group(function () {

    // ── Vue principale ────────────────────────────────────────────────
    Route::get('/', [ParametrageMarchesController::class, 'index'])->name('index');

    // ── API JSON globale ──────────────────────────────────────────────
    Route::get('/api/all', [ParametrageMarchesController::class, 'apiAll'])->name('api.all');

    // ── Seed / Reset ──────────────────────────────────────────────────
    Route::post('/seed',  [ParametrageMarchesController::class, 'seed']) ->name('seed');
    Route::post('/reset', [ParametrageMarchesController::class, 'reset'])->name('reset');

    // ── Détection automatique du mode de passation ────────────────────
    Route::post('/detecter-mode', [ParametrageMarchesController::class, 'detecterModePassation'])->name('detecter-mode');

    // ── TYPES D'ENTITÉS / AC ──────────────────────────────────────────
    Route::post  ('/types-entites',      [ParametrageMarchesController::class, 'storeTypeEntite'])  ->name('types-entites.store');
    Route::put   ('/types-entites/{id}', [ParametrageMarchesController::class, 'updateTypeEntite']) ->name('types-entites.update');
    Route::delete('/types-entites/{id}', [ParametrageMarchesController::class, 'destroyTypeEntite'])->name('types-entites.destroy');

    // ── SOURCES DE FINANCEMENT ────────────────────────────────────────
    Route::post  ('/sources-financement',      [ParametrageMarchesController::class, 'storeSourceFinancement'])  ->name('sources-financement.store');
    Route::put   ('/sources-financement/{id}', [ParametrageMarchesController::class, 'updateSourceFinancement']) ->name('sources-financement.update');
    Route::delete('/sources-financement/{id}', [ParametrageMarchesController::class, 'destroySourceFinancement'])->name('sources-financement.destroy');

    // ── NATURES DE MARCHÉ ─────────────────────────────────────────────
    Route::post  ('/natures-marche',      [ParametrageMarchesController::class, 'storeNatureMarche'])  ->name('natures-marche.store');
    Route::put   ('/natures-marche/{id}', [ParametrageMarchesController::class, 'updateNatureMarche']) ->name('natures-marche.update');
    Route::delete('/natures-marche/{id}', [ParametrageMarchesController::class, 'destroyNatureMarche'])->name('natures-marche.destroy');

    // ── MODES DE PASSATION ────────────────────────────────────────────
    Route::post  ('/modes-passation',      [ParametrageMarchesController::class, 'storeModePassation'])  ->name('modes-passation.store');
    Route::put   ('/modes-passation/{id}', [ParametrageMarchesController::class, 'updateModePassation']) ->name('modes-passation.update');
    Route::delete('/modes-passation/{id}', [ParametrageMarchesController::class, 'destroyModePassation'])->name('modes-passation.destroy');

    // ── ORGANES DE CONTRÔLE ───────────────────────────────────────────
    Route::post  ('/organes',      [ParametrageMarchesController::class, 'storeOrgane'])  ->name('organes.store');
    Route::put   ('/organes/{id}', [ParametrageMarchesController::class, 'updateOrgane']) ->name('organes.update');
    Route::delete('/organes/{id}', [ParametrageMarchesController::class, 'destroyOrgane'])->name('organes.destroy');

    // ── ORGANES LIÉS AUX MODES PM (pivot pm_mode_organes) ────────────
    // POST   body: { mode_passation_code, organe_code }
    // DELETE body: { mode_passation_code, organe_code }
    Route::post  ('/mode-organes', [ParametrageMarchesController::class, 'storeModeOrgane']) ->name('mode-organes.store');
    Route::delete('/mode-organes', [ParametrageMarchesController::class, 'destroyModeOrgane'])->name('mode-organes.destroy');

    // ── SEUILS GÉNÉRAUX ───────────────────────────────────────────────
    Route::post  ('/seuils-generaux',      [ParametrageMarchesController::class, 'storeSeuilGeneral'])  ->name('seuils-generaux.store');
    Route::put   ('/seuils-generaux/{id}', [ParametrageMarchesController::class, 'updateSeuilGeneral']) ->name('seuils-generaux.update');
    Route::delete('/seuils-generaux/{id}', [ParametrageMarchesController::class, 'destroySeuilGeneral'])->name('seuils-generaux.destroy');

    // ── SEUILS PAR AC (pm_seuils_ac) ─────────────────────────────────
    Route::post  ('/seuils-ac',      [ParametrageMarchesController::class, 'storeSeuilAC'])  ->name('seuils-ac.store');
    Route::put   ('/seuils-ac/{id}', [ParametrageMarchesController::class, 'updateSeuilAC']) ->name('seuils-ac.update');
    Route::delete('/seuils-ac/{id}', [ParametrageMarchesController::class, 'destroySeuilAC'])->name('seuils-ac.destroy');

    // ── ORGANES LIÉS AUX PLAGES DE SEUIL AC (pm_seuils_ac_organes) ───
    // POST   body: { seuil_ac_id, organe_code }
    // DELETE body: { seuil_ac_id, organe_code }
    Route::post  ('/seuils-ac-organes', [ParametrageMarchesController::class, 'storeSeuilAcOrgane']) ->name('seuils-ac-organes.store');
    Route::delete('/seuils-ac-organes', [ParametrageMarchesController::class, 'destroySeuilAcOrgane'])->name('seuils-ac-organes.destroy');

    // ── OPÉRATIONS (pm_operations) ────────────────────────────────────
    Route::post  ('/operations',      [ParametrageMarchesController::class, 'storeOperation'])  ->name('operations.store');
    Route::put   ('/operations/{id}', [ParametrageMarchesController::class, 'updateOperation']) ->name('operations.update');
    Route::delete('/operations/{id}', [ParametrageMarchesController::class, 'destroyOperation'])->name('operations.destroy');

    // ── DATES DE RÉFÉRENCE (pm_dates_reference) ───────────────────────
    Route::post  ('/dates-reference',      [ParametrageMarchesController::class, 'storeDateReference'])  ->name('dates-reference.store');
    Route::put   ('/dates-reference/{id}', [ParametrageMarchesController::class, 'updateDateReference']) ->name('dates-reference.update');
    Route::delete('/dates-reference/{id}', [ParametrageMarchesController::class, 'destroyDateReference'])->name('dates-reference.destroy');

    // ── DÉLAIS (pm_delais) ────────────────────────────────────────────
    Route::post  ('/delais',      [ParametrageMarchesController::class, 'storeDelai'])  ->name('delais.store');
    Route::put   ('/delais/{id}', [ParametrageMarchesController::class, 'updateDelai']) ->name('delais.update');
    Route::delete('/delais/{id}', [ParametrageMarchesController::class, 'destroyDelai'])->name('delais.destroy');

    // ── ORGANES LIÉS AUX DÉLAIS (pm_delai_organes) ───────────────────
    // POST   body: { delai_id, organe_code }
    // DELETE body: { delai_id, organe_code }
    Route::post  ('/delai-organes', [ParametrageMarchesController::class, 'storeDelaiOrgane']) ->name('delai-organes.store');
    Route::delete('/delai-organes', [ParametrageMarchesController::class, 'destroyDelaiOrgane'])->name('delai-organes.destroy');

    // ── MODES DE PASSATION LIÉS AUX DÉLAIS (pm_delai_modes) — NOUVEAU ─
    // Permet d'associer PLUSIEURS modes de passation existants
    // (AOO, AOR, DRP, DC, SD, GAG, ACC…) à un même délai.
    // POST   body: { delai_id, mode_passation_code }
    // DELETE body: { delai_id, mode_passation_code }
    Route::post  ('/delai-modes', [ParametrageMarchesController::class, 'storeDelaiMode']) ->name('delai-modes.store');
    Route::delete('/delai-modes', [ParametrageMarchesController::class, 'destroyDelaiMode'])->name('delai-modes.destroy');
});



Route::prefix('pieces-obligatoires')
    ->name('audit.pieces-obligatoires.')
    ->group(function () {

    // ── Vue principale + API JSON globale ─────────────────────────────
    Route::get('/',      [ParametragePiecesObligatoiresController::class, 'index']) ->name('index');
    Route::get('/api/all', [ParametragePiecesObligatoiresController::class, 'apiAll'])->name('api.all');

    // ── Seed / Reset ───────────────────────────────────────────────────
    Route::post('/seed',  [ParametragePiecesObligatoiresController::class, 'seed']) ->name('seed');
    Route::post('/reset', [ParametragePiecesObligatoiresController::class, 'reset'])->name('reset');

    // ── CATÉGORIES (pm_pieces_categories) ──────────────────────────────
    Route::post  ('/categories',      [ParametragePiecesObligatoiresController::class, 'storeCategorie'])  ->name('categories.store');
    Route::put   ('/categories/{id}', [ParametragePiecesObligatoiresController::class, 'updateCategorie']) ->name('categories.update');
    Route::delete('/categories/{id}', [ParametragePiecesObligatoiresController::class, 'destroyCategorie'])->name('categories.destroy');

    // ── PIÈCES OBLIGATOIRES (pm_pieces_obligatoires) ───────────────────
    Route::post  ('/pieces',      [ParametragePiecesObligatoiresController::class, 'storePiece'])  ->name('pieces.store');
    Route::put   ('/pieces/{id}', [ParametragePiecesObligatoiresController::class, 'updatePiece']) ->name('pieces.update');
    Route::delete('/pieces/{id}', [ParametragePiecesObligatoiresController::class, 'destroyPiece'])->name('pieces.destroy');
    Route::post  ('/pieces/reorder', [ParametragePiecesObligatoiresController::class, 'reorderPieces'])->name('pieces.reorder');

    // ── GRILLE D'APPRÉCIATION (pm_grille_appreciation_disponibilite) ───
    Route::post  ('/grille-appreciation',      [ParametragePiecesObligatoiresController::class, 'storeGrilleAppreciation'])  ->name('grille-appreciation.store');
    Route::put   ('/grille-appreciation/{id}', [ParametragePiecesObligatoiresController::class, 'updateGrilleAppreciation']) ->name('grille-appreciation.update');
    Route::delete('/grille-appreciation/{id}', [ParametragePiecesObligatoiresController::class, 'destroyGrilleAppreciation'])->name('grille-appreciation.destroy');
    // Résolution auto (appelée par le module mission)
    Route::post  ('/grille-appreciation/resoudre', [ParametragePiecesObligatoiresController::class, 'resoudreAppreciation'])->name('grille-appreciation.resoudre');

    // ── PARAMÈTRES D'AUDIT (pm_parametres_audit) ───────────────────────
    Route::post  ('/parametres-audit',      [ParametragePiecesObligatoiresController::class, 'storeParametreAudit'])  ->name('parametres-audit.store');
    Route::put   ('/parametres-audit/{id}', [ParametragePiecesObligatoiresController::class, 'updateParametreAudit']) ->name('parametres-audit.update');
    Route::delete('/parametres-audit/{id}', [ParametragePiecesObligatoiresController::class, 'destroyParametreAudit'])->name('parametres-audit.destroy');
});



/*
|--------------------------------------------------------------------------
| Routes du module "Grilles de vérification" (audit.core)
|--------------------------------------------------------------------------
| A inclure dans le groupe de routes existant du module audit.core, sous
| le même middleware / préfixe que le reste de ParametrageMarches
| (adapter le prefix et le middleware au reste de tes routes existantes,
| ex: Route::middleware(['auth','tenant'])->prefix('m/audit.core')->group(...)).
*/

Route::prefix('grilles-verification')->group(function () {

    // Vue principale + rechargement complet
    Route::get('/', [GVC::class, 'index'])->name('grilles-verification.index');
    Route::get('/api/all', [GVC::class, 'apiAll'])->name('grilles-verification.api-all');

    // Grilles
    Route::post('/grilles', [GVC::class, 'storeGrille'])->name('grilles-verification.grilles.store');
    Route::put('/grilles/{id}', [GVC::class, 'updateGrille'])->name('grilles-verification.grilles.update');
    Route::delete('/grilles/{id}', [GVC::class, 'destroyGrille'])->name('grilles-verification.grilles.destroy');

    // Organes <-> Grille
    Route::post('/grille-organes', [GVC::class, 'storeGrilleOrgane'])->name('grilles-verification.grille-organes.store');
    Route::delete('/grille-organes', [GVC::class, 'destroyGrilleOrgane'])->name('grilles-verification.grille-organes.destroy');

    // Items (points de contrôle)
    Route::post('/items', [GVC::class, 'storeItem'])->name('grilles-verification.items.store');
    Route::put('/items/{id}', [GVC::class, 'updateItem'])->name('grilles-verification.items.update');
    Route::delete('/items/{id}', [GVC::class, 'destroyItem'])->name('grilles-verification.items.destroy');
    Route::post('/items/reorder', [GVC::class, 'reorderItems'])->name('grilles-verification.items.reorder');

    // Liaisons item <-> délai / seuil (legacy 1-1)
    Route::put('/items/{id}/link-delai', [GVC::class, 'linkItemDelai'])->name('grilles-verification.items.link-delai');
    Route::put('/items/{id}/link-seuil', [GVC::class, 'linkItemSeuil'])->name('grilles-verification.items.link-seuil');

    // Liaisons M2M item <-> délais multiples
    Route::post('/items/{id}/link-delai-multi', [GVC::class, 'linkItemDelaiMulti'])->name('grilles-verification.items.link-delai-multi');
    Route::delete('/items/{id}/unlink-delai-multi', [GVC::class, 'unlinkItemDelaiMulti'])->name('grilles-verification.items.unlink-delai-multi');

    // Liaisons M2M item <-> opérations
    Route::post('/items/{id}/link-operation', [GVC::class, 'linkItemOperation'])->name('grilles-verification.items.link-operation');
    Route::delete('/items/{id}/unlink-operation', [GVC::class, 'unlinkItemOperation'])->name('grilles-verification.items.unlink-operation');

    // Liaisons M2M item <-> articles de loi
    Route::post('/items/{id}/link-article', [GVC::class, 'linkItemArticle'])->name('grilles-verification.items.link-article');
    Route::delete('/items/{id}/unlink-article', [GVC::class, 'unlinkItemArticle'])->name('grilles-verification.items.unlink-article');
    Route::get('/items/{id}/liaisons', [GVC::class, 'itemLiaisons'])->name('grilles-verification.items.liaisons');

    // Analyse IA (Mistral)
    Route::post('/items/{id}/ia-analyser', [GVC::class, 'iaAnalyserItem'])->name('grilles-verification.items.ia-analyser');
    Route::post('/grilles/{grilleId}/ia-analyser', [GVC::class, 'iaAnalyserGrille'])->name('grilles-verification.grilles.ia-analyser');

    // Résolution automatique pour une mission d'audit (appelé par le module mission)
    Route::post('/resolve-for-marche', [GVC::class, 'resolveForMarche'])->name('grilles-verification.resolve-for-marche');

    // Seed / Reset
    Route::post('/seed', [GVC::class, 'seed'])->name('grilles-verification.seed');
    Route::post('/reset', [GVC::class, 'reset'])->name('grilles-verification.reset');
});
Route::prefix('param-perf')
    ->name('param-perf.')
    ->group(function () {
 
        // Vue principale (Inertia) + endpoint JSON pour reload complet
        Route::get('/', [ParametrageAuditPerformanceController::class, 'index'])
            ->name('index');
 
        Route::get('/api/all', [ParametrageAuditPerformanceController::class, 'apiAll'])
            ->name('api.all');
 
        // Seed / Reset — routes STATIQUES déclarées AVANT le wildcard
        // /{entity} ci-dessous, sinon Laravel matcherait POST /seed comme
        // un appel à store('seed').
        Route::post('/seed', [ParametrageAuditPerformanceController::class, 'seed'])
            ->name('seed');
 
        Route::post('/reset', [ParametrageAuditPerformanceController::class, 'reset'])
            ->name('reset');
 
        // CRUD générique sur les 13 référentiels ap_* — {entity} accepte
        // les slugs à tirets (ex: niveaux-risque, facteurs-selection-theme)
        Route::post('/{entity}', [ParametrageAuditPerformanceController::class, 'store'])
            ->where('entity', '[a-z\-]+')
            ->name('store');
 
        Route::put('/{entity}/{id}', [ParametrageAuditPerformanceController::class, 'update'])
            ->where(['entity' => '[a-z\-]+', 'id' => '[0-9]+'])
            ->name('update');
 
        Route::delete('/{entity}/{id}', [ParametrageAuditPerformanceController::class, 'destroy'])
            ->where(['entity' => '[a-z\-]+', 'id' => '[0-9]+'])
            ->name('destroy');
    });

});

