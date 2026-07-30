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

        // ── Pages du menu latéral (complètent les entrées grisées) ─────────
        // Planning et Budget = onglets dédiés du dashboard (deep-link ?tab=),
        // Compétences = page dédiée (référentiel compétences + niveaux).
        Route::get('/planning', function () {
            return redirect()->route('auditor.dashboard', ['tab' => 'calendrier']);
        })->name('planning');

        Route::get('/budget', function () {
            return redirect()->route('auditor.dashboard', ['tab' => 'budget']);
        })->name('budget');

        Route::get('/competences', [AuditorDashboardController::class, 'competences'])
            ->name('competences');

        // ── Détail d'une mission ───────────────────────────────────────────
        Route::get('/missions/{missionId}', [AuditorDashboardController::class, 'showMission'])
            ->name('mission.show');

        // ── Démarrer une mission ───────────────────────────────────────────
        // CORRECTION : la méthode s'appelle start() (startMission n'existe pas
        // dans AuditorMissionsController → BadMethodCallException au clic).
        Route::patch('/programmation-missions/{id}/start', [AuditorMissionsController::class, 'start'])
            ->name('missions.start')
            ->where('id', '[0-9]+');

        // ── Liste des missions ─────────────────────────────────────────────
        Route::prefix('missions')->name('missions.')->group(function () {
            Route::get('/', [AuditorMissionsController::class, 'index'])->name('index');
        });

       Route::get('missions/{id}/gantt', [AuditorMissionsController::class, 'gantt'])
->name('missions.gantt');

Route::patch('missions/{id}/start', [AuditorMissionsController::class, 'start'])
->name('missions.start');


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

      // ══════════════════════════════════════════════════════════════════
      // URLS CANONIQUES ESPACE AUDITEUR (m/audit.core/auditor/...)
      // Ce sont les chemins que AuditorMissionsController::buildUrl() et les
      // vues MesMissions/MissionPhases génèrent déjà — ils n'étaient
      // enregistrés nulle part (404 sur "Voir les phases", le Gantt et la
      // validation de formulaire).
      // ══════════════════════════════════════════════════════════════════
      Route::get('auditor/missions', [AuditorMissionsController::class, 'index'])
          ->name('audit.core.auditor.missions.index');

      Route::get('auditor/missions/{missionId}/phases', [AuditorMissionsController::class, 'phases'])
          ->whereNumber('missionId')
          ->name('audit.core.auditor.missions.phases');

      Route::get('auditor/missions/{missionId}/gantt', [AuditorMissionsController::class, 'gantt'])
          ->whereNumber('missionId')
          ->name('audit.core.auditor.missions.gantt');

      Route::post('auditor/missions/{missionId}/start', [AuditorMissionsController::class, 'start'])
          ->whereNumber('missionId')
          ->name('audit.core.auditor.missions.start');

      Route::post('auditor/missions/{missionId}/phases/{assignmentId}/validate',
          [AuditorMissionsController::class, 'validatePhase'])
          ->whereNumber('missionId')->whereNumber('assignmentId')
          ->name('audit.core.auditor.missions.phases.validate');
// ══════════════════════════════════════════════════════════════════
// AP — AUDIT DE PERFORMANCE
// Phase 1 Préparation : Réunion d'ouverture (ddmparam.audit_type_forms
// id=59, url_path /m/audit.core/ap/preparation/reunion-ouverture).
// Mêmes verbes/segments que le bloc AC équivalent — la vue pilote tout
// via formUrl, les deux déclinaisons restent donc parfaitement alignées.
// ══════════════════════════════════════════════════════════════════
Route::prefix('ap/preparation')->group(function () {
    // ── Présentation générale du programme (formulaire PC, ddmparam id=128) ──
    // Fiche programme + activités→objectifs→indicateurs + point financier.
    $prog = \App\Http\Controllers\Auditor\Ap\ApProgrammeController::class;
    Route::get('presentation-programme', [$prog, 'index'])
        ->name('audit.ap.preparation.presentation-programme'); // = route_name ddmparam
    Route::post('presentation-programme', [$prog, 'save'])
        ->name('auditor.ap.presentation-programme.save');
    Route::post('presentation-programme/financier', [$prog, 'saveFinancier'])
        ->name('auditor.ap.presentation-programme.financier');
    Route::post('presentation-programme/activites', [$prog, 'saveActivite'])
        ->name('auditor.ap.presentation-programme.activites.save');
    Route::delete('presentation-programme/activites/{id}', [$prog, 'deleteActivite'])
        ->whereNumber('id')->name('auditor.ap.presentation-programme.activites.delete');
    Route::post('presentation-programme/objectifs', [$prog, 'saveObjectif'])
        ->name('auditor.ap.presentation-programme.objectifs.save');
    Route::delete('presentation-programme/objectifs/{id}', [$prog, 'deleteObjectif'])
        ->whereNumber('id')->name('auditor.ap.presentation-programme.objectifs.delete');
    Route::post('presentation-programme/indicateurs', [$prog, 'saveIndicateur'])
        ->name('auditor.ap.presentation-programme.indicateurs.save');
    Route::delete('presentation-programme/indicateurs/{id}', [$prog, 'deleteIndicateur'])
        ->whereNumber('id')->name('auditor.ap.presentation-programme.indicateurs.delete');
    Route::post('presentation-programme/{form}/soumettre', [$prog, 'soumettreFiche'])
        ->whereNumber('form')->name('auditor.ap.presentation-programme.soumettre');
    Route::post('presentation-programme/{form}/valider', [$prog, 'validerFiche'])
        ->whereNumber('form')->name('auditor.ap.presentation-programme.valider');

    // ── Portée de l'audit (formulaire PA, ddmparam id=129) ──
    // 3 volets : importance des sujets · ressources/résultats (cadre logique
    // auto depuis la base) · risques d'audit (scoring brut→net).
    $portee = \App\Http\Controllers\Auditor\Ap\ApPorteeController::class;
    Route::get('portee-audit', [$portee, 'index'])
        ->name('audit.ap.preparation.portee-audit'); // = route_name ddmparam
    Route::post('portee-audit', [$portee, 'save'])
        ->name('auditor.ap.portee-audit.save');
    Route::post('portee-audit/suggest-indicateurs', [$portee, 'suggestIndicateurs'])
        ->name('auditor.ap.portee-audit.suggest-indicateurs');
    Route::post('portee-audit/suggest-cadre', [$portee, 'suggestCadreLogique'])
        ->name('auditor.ap.portee-audit.suggest-cadre');
    Route::post('portee-audit/{form}/soumettre', [$portee, 'soumettreFiche'])
        ->whereNumber('form')->name('auditor.ap.portee-audit.soumettre');
    Route::post('portee-audit/{form}/valider', [$portee, 'validerFiche'])
        ->whereNumber('form')->name('auditor.ap.portee-audit.valider');

    // Suggestion d'indicateurs réutilisée par la fiche Présentation Programme
    Route::post('presentation-programme/suggest-indicateurs', [$portee, 'suggestIndicateurs'])
        ->name('auditor.ap.presentation-programme.suggest-indicateurs');

    // ── Champ d'action (sous-phase 1.4 · Planification, formulaire CA) ──
    // 7 volets : approches (4 « E ») · objectifs · question principale ·
    // étendue Qui/Quoi/Quand/Où · limites · conclusions · pertinence du mandat.
    // Trames pré-remplies depuis les paramètres AP + suggestion IA (Mistral).
    $champ = \App\Http\Controllers\Auditor\Ap\ApChampActionController::class;
    Route::get('champ-action', [$champ, 'index'])
        ->name('audit.ap.preparation.champ-action'); // = route_name ddmparam
    Route::post('champ-action', [$champ, 'save'])
        ->name('auditor.ap.champ-action.save');
    Route::post('champ-action/suggest-ia', [$champ, 'suggestIA'])
        ->name('auditor.ap.champ-action.suggest-ia');
    Route::post('champ-action/{form}/soumettre', [$champ, 'soumettreFiche'])
        ->whereNumber('form')->name('auditor.ap.champ-action.soumettre');
    Route::post('champ-action/{form}/valider', [$champ, 'validerFiche'])
        ->whereNumber('form')->name('auditor.ap.champ-action.valider');

    // ── Méthodologie de vérification (sous-phase Planification, formulaire MV) ──
    // 5 volets : lignes directrices d'enquête · critères (sous-critères) ·
    // sources de l'evidence · méthode de collecte · méthodes d'analyse.
    // Trame section 1 pré-remplie depuis les questions du Champ d'action + IA.
    $metho = \App\Http\Controllers\Auditor\Ap\ApMethodologieController::class;
    Route::get('methodologie-verification', [$metho, 'index'])
        ->name('audit.ap.preparation.methodologie-verification'); // = route_name ddmparam
    Route::post('methodologie-verification', [$metho, 'save'])
        ->name('auditor.ap.methodologie-verification.save');
    Route::post('methodologie-verification/suggest-ia', [$metho, 'suggestIA'])
        ->name('auditor.ap.methodologie-verification.suggest-ia');
    Route::post('methodologie-verification/{form}/soumettre', [$metho, 'soumettreFiche'])
        ->whereNumber('form')->name('auditor.ap.methodologie-verification.soumettre');
    Route::post('methodologie-verification/{form}/valider', [$metho, 'validerFiche'])
        ->whereNumber('form')->name('auditor.ap.methodologie-verification.valider');

    Route::get('reunion-ouverture', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'index'])
        ->name('audit.ap.preparation.reunion-ouverture'); // = route_name ddmparam
    Route::get('reunion-ouverture/{form}/pdf', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'pdf'])
        ->name('auditor.ap.reunion-ouverture.pdf');
    Route::get('reunion-ouverture/{form}/edit', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'edit'])
        ->name('auditor.ap.reunion-ouverture.edit');
    Route::post('reunion-ouverture', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'store'])
        ->name('auditor.ap.reunion-ouverture.store');
    Route::put('reunion-ouverture/{form}', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'update'])
        ->name('auditor.ap.reunion-ouverture.update');
    Route::delete('reunion-ouverture/{form}', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'destroy'])
        ->name('auditor.ap.reunion-ouverture.destroy');
    Route::post('reunion-ouverture/{form}/soumettre', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'soumettre'])
        ->name('auditor.ap.reunion-ouverture.soumettre');
    Route::post('reunion-ouverture/{form}/valider', [\App\Http\Controllers\Auditor\Ap\ApReunionOuvertureController::class, 'valider'])
        ->name('auditor.ap.reunion-ouverture.valider');
});

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
//         // analyse-processus
//         Route::get('analyse-processus', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'index'])->name('audit.ac.preparation.analyse-processus');
//         Route::get('analyse-processus/{form}/edit', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'edit'])->name('auditor.ac.analyse-processus.edit');
//         Route::post('analyse-processus', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'store'])->name('auditor.ac.analyse-processus.store');
//         Route::put('analyse-processus/{form}', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'update'])->name('auditor.ac.analyse-processus.update');
//         Route::delete('analyse-processus/{form}', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'destroy'])->name('auditor.ac.analyse-processus.destroy');
//         Route::post('analyse-processus/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'soumettre'])->name('auditor.ac.analyse-processus.soumettre');
//         Route::post('analyse-processus/{form}/valider', [App\Http\Controllers\Auditor\AnalyseProcessusController::class, 'valider'])->name('auditor.ac.analyse-processus.valider');
//         // analyse-risques
//         Route::get('analyse-risques', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'index'])->name('audit.ac.preparation.analyse-risques');
//         Route::get('analyse-risques/{form}/edit', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'edit'])->name('auditor.ac.analyse-risques.edit');
//         Route::post('analyse-risques', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'store'])->name('auditor.ac.analyse-risques.store');
//         Route::put('analyse-risques/{form}', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'update'])->name('auditor.ac.analyse-risques.update');
//         Route::delete('analyse-risques/{form}', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'destroy'])->name('auditor.ac.analyse-risques.destroy');
//         Route::post('analyse-risques/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'soumettre'])->name('auditor.ac.analyse-risques.soumettre');
//         Route::post('analyse-risques/{form}/valider', [App\Http\Controllers\Auditor\AnalyseRisquesController::class, 'valider'])->name('auditor.ac.analyse-risques.valider');
//         // analyse-procedures
//         Route::get('analyse-procedures', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'index'])->name('audit.ac.preparation.analyse-procedures');
//         Route::get('analyse-procedures/{form}/edit', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'edit'])->name('auditor.ac.analyse-procedures.edit');
//         Route::post('analyse-procedures', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'store'])->name('auditor.ac.analyse-procedures.store');
//         Route::put('analyse-procedures/{form}', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'update'])->name('auditor.ac.analyse-procedures.update');
//         Route::delete('analyse-procedures/{form}', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'destroy'])->name('auditor.ac.analyse-procedures.destroy');
//         Route::post('analyse-procedures/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'soumettre'])->name('auditor.ac.analyse-procedures.soumettre');
//         Route::post('analyse-procedures/{form}/valider', [App\Http\Controllers\Auditor\AnalyseProceduresController::class, 'valider'])->name('auditor.ac.analyse-procedures.valider');
//         // analyse-taches
//         Route::get('analyse-taches', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'index'])->name('audit.ac.preparation.analyse-taches');
//         Route::get('analyse-taches/{form}/edit', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'edit'])->name('auditor.ac.analyse-taches.edit');
//         Route::post('analyse-taches', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'store'])->name('auditor.ac.analyse-taches.store');
//         Route::put('analyse-taches/{form}', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'update'])->name('auditor.ac.analyse-taches.update');
//         Route::delete('analyse-taches/{form}', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'destroy'])->name('auditor.ac.analyse-taches.destroy');
//         Route::post('analyse-taches/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'soumettre'])->name('auditor.ac.analyse-taches.soumettre');
//         Route::post('analyse-taches/{form}/valider', [App\Http\Controllers\Auditor\AnalyseTachesController::class, 'valider'])->name('auditor.ac.analyse-taches.valider');
//         // analyse-ci-qci
//         Route::get('analyse-ci-qci', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'index'])->name('audit.ac.preparation.analyse-ci-qci');
//         Route::get('analyse-ci-qci/{form}/edit', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'edit'])->name('auditor.ac.analyse-ci-qci.edit');
//         Route::post('analyse-ci-qci', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'store'])->name('auditor.ac.analyse-ci-qci.store');
//         Route::put('analyse-ci-qci/{form}', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'update'])->name('auditor.ac.analyse-ci-qci.update');
//         Route::delete('analyse-ci-qci/{form}', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'destroy'])->name('auditor.ac.analyse-ci-qci.destroy');
//         Route::post('analyse-ci-qci/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'soumettre'])->name('auditor.ac.analyse-ci-qci.soumettre');
//         Route::post('analyse-ci-qci/{form}/valider', [App\Http\Controllers\Auditor\AnalyseCiQciController::class, 'valider'])->name('auditor.ac.analyse-ci-qci.valider');
//         // analyse-conformite
//         Route::get('analyse-conformite', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'index'])->name('audit.ac.preparation.analyse-conformite');
//         Route::get('analyse-conformite/{form}/edit', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'edit'])->name('auditor.ac.analyse-conformite.edit');
//         Route::post('analyse-conformite', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'store'])->name('auditor.ac.analyse-conformite.store');
//         Route::put('analyse-conformite/{form}', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'update'])->name('auditor.ac.analyse-conformite.update');
//         Route::delete('analyse-conformite/{form}', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'destroy'])->name('auditor.ac.analyse-conformite.destroy');
//         Route::post('analyse-conformite/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'soumettre'])->name('auditor.ac.analyse-conformite.soumettre');
//         Route::post('analyse-conformite/{form}/valider', [App\Http\Controllers\Auditor\AnalyseConformiteController::class, 'valider'])->name('auditor.ac.analyse-conformite.valider');
//         // analyse-marches
//         Route::get('analyse-marches', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'index'])->name('audit.ac.preparation.analyse-marches');
//         Route::get('analyse-marches/{form}/edit', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'edit'])->name('auditor.ac.analyse-marches.edit');
//         Route::post('analyse-marches', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'store'])->name('auditor.ac.analyse-marches.store');
//         Route::put('analyse-marches/{form}', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'update'])->name('auditor.ac.analyse-marches.update');
//         Route::delete('analyse-marches/{form}', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'destroy'])->name('auditor.ac.analyse-marches.destroy');
//         Route::post('analyse-marches/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'soumettre'])->name('auditor.ac.analyse-marches.soumettre');
//         Route::post('analyse-marches/{form}/valider', [App\Http\Controllers\Auditor\AnalyseMarchesController::class, 'valider'])->name('auditor.ac.analyse-marches.valider');
//         // analyse-forces-faiblesses
//         Route::get('analyse-forces-faiblesses', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'index'])->name('audit.ac.preparation.analyse-forces-faiblesses');
//         Route::get('analyse-forces-faiblesses/{form}/edit', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'edit'])->name('auditor.ac.analyse-forces-faiblesses.edit');
//         Route::post('analyse-forces-faiblesses', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'store'])->name('auditor.ac.analyse-forces-faiblesses.store');
//         Route::put('analyse-forces-faiblesses/{form}', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'update'])->name('auditor.ac.analyse-forces-faiblesses.update');
//         Route::delete('analyse-forces-faiblesses/{form}', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'destroy'])->name('auditor.ac.analyse-forces-faiblesses.destroy');
//         Route::post('analyse-forces-faiblesses/{form}/soumettre', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'soumettre'])->name('auditor.ac.analyse-forces-faiblesses.soumettre');
//         Route::post('analyse-forces-faiblesses/{form}/valider', [App\Http\Controllers\Auditor\AnalyseForcesFaiblessesController::class, 'valider'])->name('auditor.ac.analyse-forces-faiblesses.valider');
//         // rapport-orientation
//         Route::get('rapport-orientation', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'index'])->name('audit.ac.preparation.rapport-orientation');
//         Route::get('rapport-orientation/{form}/edit', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'edit'])->name('auditor.ac.rapport-orientation.edit');
//         Route::post('rapport-orientation', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'store'])->name('auditor.ac.rapport-orientation.store');
//         Route::put('rapport-orientation/{form}', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'update'])->name('auditor.ac.rapport-orientation.update');
//         Route::delete('rapport-orientation/{form}', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'destroy'])->name('auditor.ac.rapport-orientation.destroy');
//         Route::post('rapport-orientation/{form}/soumettre', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'soumettre'])->name('auditor.ac.rapport-orientation.soumettre');
//         Route::post('rapport-orientation/{form}/valider', [App\Http\Controllers\Auditor\RapportOrientationController::class, 'valider'])->name('auditor.ac.rapport-orientation.valider');
//         // referentiels-audit
//         Route::get('referentiels-audit', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'index'])->name('audit.ac.preparation.referentiels-audit');
//         Route::get('referentiels-audit/{form}/edit', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'edit'])->name('auditor.ac.referentiels-audit.edit');
//         Route::post('referentiels-audit', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'store'])->name('auditor.ac.referentiels-audit.store');
//         Route::put('referentiels-audit/{form}', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'update'])->name('auditor.ac.referentiels-audit.update');
//         Route::delete('referentiels-audit/{form}', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'destroy'])->name('auditor.ac.referentiels-audit.destroy');
//         Route::post('referentiels-audit/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'soumettre'])->name('auditor.ac.referentiels-audit.soumettre');
//         Route::post('referentiels-audit/{form}/valider', [App\Http\Controllers\Auditor\ReferentielsAuditController::class, 'valider'])->name('auditor.ac.referentiels-audit.valider');
//         // referentiel-ci
//         Route::get('referentiel-ci', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'index'])->name('audit.ac.preparation.referentiel-ci');
//         Route::get('referentiel-ci/{form}/edit', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'edit'])->name('auditor.ac.referentiel-ci.edit');
//         Route::post('referentiel-ci', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'store'])->name('auditor.ac.referentiel-ci.store');
//         Route::put('referentiel-ci/{form}', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'update'])->name('auditor.ac.referentiel-ci.update');
//         Route::delete('referentiel-ci/{form}', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'destroy'])->name('auditor.ac.referentiel-ci.destroy');
//         Route::post('referentiel-ci/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'soumettre'])->name('auditor.ac.referentiel-ci.soumettre');
//         Route::post('referentiel-ci/{form}/valider', [App\Http\Controllers\Auditor\ReferentielCiController::class, 'valider'])->name('auditor.ac.referentiel-ci.valider');
//         // referentiel-conformite
//         Route::get('referentiel-conformite', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'index'])->name('audit.ac.preparation.referentiel-conformite');
//         Route::get('referentiel-conformite/{form}/edit', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'edit'])->name('auditor.ac.referentiel-conformite.edit');
//         Route::post('referentiel-conformite', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'store'])->name('auditor.ac.referentiel-conformite.store');
//         Route::put('referentiel-conformite/{form}', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'update'])->name('auditor.ac.referentiel-conformite.update');
//         Route::delete('referentiel-conformite/{form}', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'destroy'])->name('auditor.ac.referentiel-conformite.destroy');
//         Route::post('referentiel-conformite/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'soumettre'])->name('auditor.ac.referentiel-conformite.soumettre');
//         Route::post('referentiel-conformite/{form}/valider', [App\Http\Controllers\Auditor\ReferentielConformiteController::class, 'valider'])->name('auditor.ac.referentiel-conformite.valider');
//         // referentiel-marches
//         Route::get('referentiel-marches', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'index'])->name('audit.ac.preparation.referentiel-marches');
//         Route::get('referentiel-marches/{form}/edit', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'edit'])->name('auditor.ac.referentiel-marches.edit');
//         Route::post('referentiel-marches', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'store'])->name('auditor.ac.referentiel-marches.store');
//         Route::put('referentiel-marches/{form}', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'update'])->name('auditor.ac.referentiel-marches.update');
//         Route::delete('referentiel-marches/{form}', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'destroy'])->name('auditor.ac.referentiel-marches.destroy');
//         Route::post('referentiel-marches/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'soumettre'])->name('auditor.ac.referentiel-marches.soumettre');
//         Route::post('referentiel-marches/{form}/valider', [App\Http\Controllers\Auditor\ReferentielMarchesController::class, 'valider'])->name('auditor.ac.referentiel-marches.valider');
//         // referentiel-transactions
//         Route::get('referentiel-transactions', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'index'])->name('audit.ac.preparation.referentiel-transactions');
//         Route::get('referentiel-transactions/{form}/edit', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'edit'])->name('auditor.ac.referentiel-transactions.edit');
//         Route::post('referentiel-transactions', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'store'])->name('auditor.ac.referentiel-transactions.store');
//         Route::put('referentiel-transactions/{form}', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'update'])->name('auditor.ac.referentiel-transactions.update');
//         Route::delete('referentiel-transactions/{form}', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'destroy'])->name('auditor.ac.referentiel-transactions.destroy');
//         Route::post('referentiel-transactions/{form}/soumettre', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'soumettre'])->name('auditor.ac.referentiel-transactions.soumettre');
//         Route::post('referentiel-transactions/{form}/valider', [App\Http\Controllers\Auditor\ReferentielTransactionsController::class, 'valider'])->name('auditor.ac.referentiel-transactions.valider');
//         // programmes-travail
//         Route::get('programmes-travail', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'index'])->name('audit.ac.preparation.programmes-travail');
//         Route::get('programmes-travail/{form}/edit', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'edit'])->name('auditor.ac.programmes-travail.edit');
//         Route::post('programmes-travail', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'store'])->name('auditor.ac.programmes-travail.store');
//         Route::put('programmes-travail/{form}', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'update'])->name('auditor.ac.programmes-travail.update');
//         Route::delete('programmes-travail/{form}', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'destroy'])->name('auditor.ac.programmes-travail.destroy');
//         Route::post('programmes-travail/{form}/soumettre', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'soumettre'])->name('auditor.ac.programmes-travail.soumettre');
//         Route::post('programmes-travail/{form}/valider', [App\Http\Controllers\Auditor\ProgrammesTravailController::class, 'valider'])->name('auditor.ac.programmes-travail.valider');
//         // prog-ci
//         Route::get('prog-ci', [App\Http\Controllers\Auditor\ProgCiController::class, 'index'])->name('audit.ac.preparation.prog-ci');
//         Route::get('prog-ci/{form}/edit', [App\Http\Controllers\Auditor\ProgCiController::class, 'edit'])->name('auditor.ac.prog-ci.edit');
//         Route::post('prog-ci', [App\Http\Controllers\Auditor\ProgCiController::class, 'store'])->name('auditor.ac.prog-ci.store');
//         Route::put('prog-ci/{form}', [App\Http\Controllers\Auditor\ProgCiController::class, 'update'])->name('auditor.ac.prog-ci.update');
//         Route::delete('prog-ci/{form}', [App\Http\Controllers\Auditor\ProgCiController::class, 'destroy'])->name('auditor.ac.prog-ci.destroy');
//         Route::post('prog-ci/{form}/soumettre', [App\Http\Controllers\Auditor\ProgCiController::class, 'soumettre'])->name('auditor.ac.prog-ci.soumettre');
//         Route::post('prog-ci/{form}/valider', [App\Http\Controllers\Auditor\ProgCiController::class, 'valider'])->name('auditor.ac.prog-ci.valider');
//         // prog-conformite
//         Route::get('prog-conformite', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'index'])->name('audit.ac.preparation.prog-conformite');
//         Route::get('prog-conformite/{form}/edit', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'edit'])->name('auditor.ac.prog-conformite.edit');
//         Route::post('prog-conformite', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'store'])->name('auditor.ac.prog-conformite.store');
//         Route::put('prog-conformite/{form}', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'update'])->name('auditor.ac.prog-conformite.update');
//         Route::delete('prog-conformite/{form}', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'destroy'])->name('auditor.ac.prog-conformite.destroy');
//         Route::post('prog-conformite/{form}/soumettre', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'soumettre'])->name('auditor.ac.prog-conformite.soumettre');
//         Route::post('prog-conformite/{form}/valider', [App\Http\Controllers\Auditor\ProgConformiteController::class, 'valider'])->name('auditor.ac.prog-conformite.valider');
//         // prog-marches
//         Route::get('prog-marches', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'index'])->name('audit.ac.preparation.prog-marches');
//         Route::get('prog-marches/{form}/edit', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'edit'])->name('auditor.ac.prog-marches.edit');
//         Route::post('prog-marches', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'store'])->name('auditor.ac.prog-marches.store');
//         Route::put('prog-marches/{form}', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'update'])->name('auditor.ac.prog-marches.update');
//         Route::delete('prog-marches/{form}', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'destroy'])->name('auditor.ac.prog-marches.destroy');
//         Route::post('prog-marches/{form}/soumettre', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'soumettre'])->name('auditor.ac.prog-marches.soumettre');
//         Route::post('prog-marches/{form}/valider', [App\Http\Controllers\Auditor\ProgMarchesController::class, 'valider'])->name('auditor.ac.prog-marches.valider');
//         // prog-transactions
//         Route::get('prog-transactions', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'index'])->name('audit.ac.preparation.prog-transactions');
//         Route::get('prog-transactions/{form}/edit', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'edit'])->name('auditor.ac.prog-transactions.edit');
//         Route::post('prog-transactions', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'store'])->name('auditor.ac.prog-transactions.store');
//         Route::put('prog-transactions/{form}', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'update'])->name('auditor.ac.prog-transactions.update');
//         Route::delete('prog-transactions/{form}', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'destroy'])->name('auditor.ac.prog-transactions.destroy');
//         Route::post('prog-transactions/{form}/soumettre', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'soumettre'])->name('auditor.ac.prog-transactions.soumettre');
//         Route::post('prog-transactions/{form}/valider', [App\Http\Controllers\Auditor\ProgTransactionsController::class, 'valider'])->name('auditor.ac.prog-transactions.valider');
   }); // end preparation
//     // ── Phase 2 : Realisation ──
//     Route::prefix('realisation')->group(function () {
//         // reunion-lancement
//         Route::get('reunion-lancement', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'index'])->name('audit.ac.realisation.reunion-lancement');
//         Route::get('reunion-lancement/{form}/edit', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'edit'])->name('auditor.ac.reunion-lancement.edit');
//         Route::post('reunion-lancement', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'store'])->name('auditor.ac.reunion-lancement.store');
//         Route::put('reunion-lancement/{form}', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'update'])->name('auditor.ac.reunion-lancement.update');
//         Route::delete('reunion-lancement/{form}', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'destroy'])->name('auditor.ac.reunion-lancement.destroy');
//         Route::post('reunion-lancement/{form}/soumettre', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'soumettre'])->name('auditor.ac.reunion-lancement.soumettre');
//         Route::post('reunion-lancement/{form}/valider', [App\Http\Controllers\Auditor\ReunionLancementController::class, 'valider'])->name('auditor.ac.reunion-lancement.valider');
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
//         Route::post('eval-conformite', [App\Http\Controllers\Auditor\EvalConformiteController::class, 'store'])->name('auditor.ac.eval-conformite.store');
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
//         Route::get('test-procedures', [App\Http\Controllers\Auditor\TestProceduresController::class, 'index'])->name('audit.ac.realisation.test-procedures');
//         Route::get('test-procedures/{form}/edit', [App\Http\Controllers\Auditor\TestProceduresController::class, 'edit'])->name('auditor.ac.test-procedures.edit');
//         Route::post('test-procedures', [App\Http\Controllers\Auditor\TestProceduresController::class, 'store'])->name('auditor.ac.test-procedures.store');
//         Route::put('test-procedures/{form}', [App\Http\Controllers\Auditor\TestProceduresController::class, 'update'])->name('auditor.ac.test-procedures.update');
//         Route::delete('test-procedures/{form}', [App\Http\Controllers\Auditor\TestProceduresController::class, 'destroy'])->name('auditor.ac.test-procedures.destroy');
//         Route::post('test-procedures/{form}/soumettre', [App\Http\Controllers\Auditor\TestProceduresController::class, 'soumettre'])->name('auditor.ac.test-procedures.soumettre');
//         Route::post('test-procedures/{form}/valider', [App\Http\Controllers\Auditor\TestProceduresController::class, 'valider'])->name('auditor.ac.test-procedures.valider');
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
//     }); // end realisation
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
// }); // end ac

// // ══════════════════════════════════════════════════════════════════
// // TYPE : AF
// // ══════════════════════════════════════════════════════════════════
// Route::prefix('af')->group(function () {
//     // ── Phase 1 : Preparation ──
  
//     // ── Phase 2 : Realisation ──
//     Route::prefix('realisation')->group(function () {
//         // test-procedures
//         Route::get('test-procedures', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'index'])->name('audit.af.realisation.test-procedures');
//         Route::get('test-procedures/{form}/edit', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'edit'])->name('auditor.af.test-procedures.edit');
//         Route::post('test-procedures', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'store'])->name('auditor.af.test-procedures.store');
//         Route::put('test-procedures/{form}', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'update'])->name('auditor.af.test-procedures.update');
//         Route::delete('test-procedures/{form}', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'destroy'])->name('auditor.af.test-procedures.destroy');
//         Route::post('test-procedures/{form}/soumettre', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'soumettre'])->name('auditor.af.test-procedures.soumettre');
//         Route::post('test-procedures/{form}/valider', [App\Http\Controllers\Auditor\Af\TestProceduresController::class, 'valider'])->name('auditor.af.test-procedures.valider');
//         // controle-transactions
//         Route::get('controle-transactions', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'index'])->name('audit.af.realisation.controle-transactions');
//         Route::get('controle-transactions/{form}/edit', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'edit'])->name('auditor.af.controle-transactions.edit');
//         Route::post('controle-transactions', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'store'])->name('auditor.af.controle-transactions.store');
//         Route::put('controle-transactions/{form}', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'update'])->name('auditor.af.controle-transactions.update');
//         Route::delete('controle-transactions/{form}', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'destroy'])->name('auditor.af.controle-transactions.destroy');
//         Route::post('controle-transactions/{form}/soumettre', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'soumettre'])->name('auditor.af.controle-transactions.soumettre');
//         Route::post('controle-transactions/{form}/valider', [App\Http\Controllers\Auditor\Af\ControleTransactionsController::class, 'valider'])->name('auditor.af.controle-transactions.valider');
//         // feuilles-travail-investigation
//         Route::get('feuilles-travail-investigation', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'index'])->name('audit.af.realisation.feuilles-travail-investigation');
//         Route::get('feuilles-travail-investigation/{form}/edit', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'edit'])->name('auditor.af.feuilles-travail-investigation.edit');
//         Route::post('feuilles-travail-investigation', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'store'])->name('auditor.af.feuilles-travail-investigation.store');
//         Route::put('feuilles-travail-investigation/{form}', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'update'])->name('auditor.af.feuilles-travail-investigation.update');
//         Route::delete('feuilles-travail-investigation/{form}', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'destroy'])->name('auditor.af.feuilles-travail-investigation.destroy');
//         Route::post('feuilles-travail-investigation/{form}/soumettre', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'soumettre'])->name('auditor.af.feuilles-travail-investigation.soumettre');
//         Route::post('feuilles-travail-investigation/{form}/valider', [App\Http\Controllers\Auditor\Af\FeuillesTravailInvestigationController::class, 'valider'])->name('auditor.af.feuilles-travail-investigation.valider');
//         // releve-anomalies
//         Route::get('releve-anomalies', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'index'])->name('audit.af.realisation.releve-anomalies');
//         Route::get('releve-anomalies/{form}/edit', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'edit'])->name('auditor.af.releve-anomalies.edit');
//         Route::post('releve-anomalies', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'store'])->name('auditor.af.releve-anomalies.store');
//         Route::put('releve-anomalies/{form}', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'update'])->name('auditor.af.releve-anomalies.update');
//         Route::delete('releve-anomalies/{form}', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'destroy'])->name('auditor.af.releve-anomalies.destroy');
//         Route::post('releve-anomalies/{form}/soumettre', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'soumettre'])->name('auditor.af.releve-anomalies.soumettre');
//         Route::post('releve-anomalies/{form}/valider', [App\Http\Controllers\Auditor\Af\ReleveAnomaliesController::class, 'valider'])->name('auditor.af.releve-anomalies.valider');
//         // fiche-constat-investigation
//         Route::get('fiche-constat-investigation', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'index'])->name('audit.af.realisation.fiche-constat-investigation');
//         Route::get('fiche-constat-investigation/{form}/edit', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'edit'])->name('auditor.af.fiche-constat-investigation.edit');
//         Route::post('fiche-constat-investigation', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'store'])->name('auditor.af.fiche-constat-investigation.store');
//         Route::put('fiche-constat-investigation/{form}', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'update'])->name('auditor.af.fiche-constat-investigation.update');
//         Route::delete('fiche-constat-investigation/{form}', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'destroy'])->name('auditor.af.fiche-constat-investigation.destroy');
//         Route::post('fiche-constat-investigation/{form}/soumettre', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'soumettre'])->name('auditor.af.fiche-constat-investigation.soumettre');
//         Route::post('fiche-constat-investigation/{form}/valider', [App\Http\Controllers\Auditor\Af\FicheConstatInvestigationController::class, 'valider'])->name('auditor.af.fiche-constat-investigation.valider');
//     }); // end realisation
//     // ── Phase 3 : Conclusion ──
//     Route::prefix('conclusion')->group(function () {
//         // synthese-constats
//         Route::get('synthese-constats', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'index'])->name('audit.af.conclusion.synthese-constats');
//         Route::get('synthese-constats/{form}/edit', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'edit'])->name('auditor.af.synthese-constats.edit');
//         Route::post('synthese-constats', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'store'])->name('auditor.af.synthese-constats.store');
//         Route::put('synthese-constats/{form}', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'update'])->name('auditor.af.synthese-constats.update');
//         Route::delete('synthese-constats/{form}', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'destroy'])->name('auditor.af.synthese-constats.destroy');
//         Route::post('synthese-constats/{form}/soumettre', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'soumettre'])->name('auditor.af.synthese-constats.soumettre');
//         Route::post('synthese-constats/{form}/valider', [App\Http\Controllers\Auditor\Af\SyntheseConstatsController::class, 'valider'])->name('auditor.af.synthese-constats.valider');
//         // rapport-investigation
//         Route::get('rapport-investigation', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'index'])->name('audit.af.conclusion.rapport-investigation');
//         Route::get('rapport-investigation/{form}/edit', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'edit'])->name('auditor.af.rapport-investigation.edit');
//         Route::post('rapport-investigation', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'store'])->name('auditor.af.rapport-investigation.store');
//         Route::put('rapport-investigation/{form}', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'update'])->name('auditor.af.rapport-investigation.update');
//         Route::delete('rapport-investigation/{form}', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'destroy'])->name('auditor.af.rapport-investigation.destroy');
//         Route::post('rapport-investigation/{form}/soumettre', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'soumettre'])->name('auditor.af.rapport-investigation.soumettre');
//         Route::post('rapport-investigation/{form}/valider', [App\Http\Controllers\Auditor\Af\RapportInvestigationController::class, 'valider'])->name('auditor.af.rapport-investigation.valider');
//         // plan-mise-en-oeuvre
//         Route::get('plan-mise-en-oeuvre', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'index'])->name('audit.af.conclusion.plan-mise-en-oeuvre');
//         Route::get('plan-mise-en-oeuvre/{form}/edit', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'edit'])->name('auditor.af.plan-mise-en-oeuvre.edit');
//         Route::post('plan-mise-en-oeuvre', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'store'])->name('auditor.af.plan-mise-en-oeuvre.store');
//         Route::put('plan-mise-en-oeuvre/{form}', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'update'])->name('auditor.af.plan-mise-en-oeuvre.update');
//         Route::delete('plan-mise-en-oeuvre/{form}', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'destroy'])->name('auditor.af.plan-mise-en-oeuvre.destroy');
//         Route::post('plan-mise-en-oeuvre/{form}/soumettre', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'soumettre'])->name('auditor.af.plan-mise-en-oeuvre.soumettre');
//         Route::post('plan-mise-en-oeuvre/{form}/valider', [App\Http\Controllers\Auditor\Af\PlanMiseEnOeuvreController::class, 'valider'])->name('auditor.af.plan-mise-en-oeuvre.valider');
//     }); // end conclusion
//     // ── Phase 5 : Recommandation ──
//     Route::prefix('recommandation')->group(function () {
//         // recommandation-anti-fraude
//         Route::get('recommandation-anti-fraude', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'index'])->name('audit.af.recommandation.recommandation-anti-fraude');
//         Route::get('recommandation-anti-fraude/{form}/edit', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'edit'])->name('auditor.af.recommandation-anti-fraude.edit');
//         Route::post('recommandation-anti-fraude', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'store'])->name('auditor.af.recommandation-anti-fraude.store');
//         Route::put('recommandation-anti-fraude/{form}', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'update'])->name('auditor.af.recommandation-anti-fraude.update');
//         Route::delete('recommandation-anti-fraude/{form}', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'destroy'])->name('auditor.af.recommandation-anti-fraude.destroy');
//         Route::post('recommandation-anti-fraude/{form}/soumettre', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'soumettre'])->name('auditor.af.recommandation-anti-fraude.soumettre');
//         Route::post('recommandation-anti-fraude/{form}/valider', [App\Http\Controllers\Auditor\Af\RecommandationAntiFraudeController::class, 'valider'])->name('auditor.af.recommandation-anti-fraude.valider');
//         // plan-action
//         Route::get('plan-action', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'index'])->name('audit.af.recommandation.plan-action');
//         Route::get('plan-action/{form}/edit', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'edit'])->name('auditor.af.plan-action.edit');
//         Route::post('plan-action', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'store'])->name('auditor.af.plan-action.store');
//         Route::put('plan-action/{form}', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'update'])->name('auditor.af.plan-action.update');
//         Route::delete('plan-action/{form}', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'destroy'])->name('auditor.af.plan-action.destroy');
//         Route::post('plan-action/{form}/soumettre', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'soumettre'])->name('auditor.af.plan-action.soumettre');
//         Route::post('plan-action/{form}/valider', [App\Http\Controllers\Auditor\Af\PlanActionController::class, 'valider'])->name('auditor.af.plan-action.valider');
//     }); // end recommandation
// }); // end af

// // ══════════════════════════════════════════════════════════════════
// // TYPE : AP
// // ══════════════════════════════════════════════════════════════════
// Route::prefix('ap')->group(function () {
//     // ── Phase 1 : Preparation ──
//     Route::prefix('preparation')->group(function () {
//         // reunion-ouverture
//         Route::get('reunion-ouverture', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'index'])->name('audit.ap.preparation.reunion-ouverture');
//         Route::get('reunion-ouverture/{form}/edit', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'edit'])->name('auditor.ap.reunion-ouverture.edit');
//         Route::post('reunion-ouverture', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'store'])->name('auditor.ap.reunion-ouverture.store');
//         Route::put('reunion-ouverture/{form}', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'update'])->name('auditor.ap.reunion-ouverture.update');
//         Route::delete('reunion-ouverture/{form}', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'destroy'])->name('auditor.ap.reunion-ouverture.destroy');
//         Route::post('reunion-ouverture/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'soumettre'])->name('auditor.ap.reunion-ouverture.soumettre');
//         Route::post('reunion-ouverture/{form}/valider', [App\Http\Controllers\Auditor\Ap\ReunionOuvertureController::class, 'valider'])->name('auditor.ap.reunion-ouverture.valider');
//         // analyse-processus
//         Route::get('analyse-processus', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'index'])->name('audit.ap.preparation.analyse-processus');
//         Route::get('analyse-processus/{form}/edit', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'edit'])->name('auditor.ap.analyse-processus.edit');
//         Route::post('analyse-processus', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'store'])->name('auditor.ap.analyse-processus.store');
//         Route::put('analyse-processus/{form}', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'update'])->name('auditor.ap.analyse-processus.update');
//         Route::delete('analyse-processus/{form}', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'destroy'])->name('auditor.ap.analyse-processus.destroy');
//         Route::post('analyse-processus/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'soumettre'])->name('auditor.ap.analyse-processus.soumettre');
//         Route::post('analyse-processus/{form}/valider', [App\Http\Controllers\Auditor\Ap\AnalyseProcessusController::class, 'valider'])->name('auditor.ap.analyse-processus.valider');
//         // analyse-forces-faiblesses
//         Route::get('analyse-forces-faiblesses', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'index'])->name('audit.ap.preparation.analyse-forces-faiblesses');
//         Route::get('analyse-forces-faiblesses/{form}/edit', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'edit'])->name('auditor.ap.analyse-forces-faiblesses.edit');
//         Route::post('analyse-forces-faiblesses', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'store'])->name('auditor.ap.analyse-forces-faiblesses.store');
//         Route::put('analyse-forces-faiblesses/{form}', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'update'])->name('auditor.ap.analyse-forces-faiblesses.update');
//         Route::delete('analyse-forces-faiblesses/{form}', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'destroy'])->name('auditor.ap.analyse-forces-faiblesses.destroy');
//         Route::post('analyse-forces-faiblesses/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'soumettre'])->name('auditor.ap.analyse-forces-faiblesses.soumettre');
//         Route::post('analyse-forces-faiblesses/{form}/valider', [App\Http\Controllers\Auditor\Ap\AnalyseForcesFaiblessesController::class, 'valider'])->name('auditor.ap.analyse-forces-faiblesses.valider');
//         // indicateurs-performance
//         Route::get('indicateurs-performance', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'index'])->name('audit.ap.preparation.indicateurs-performance');
//         Route::get('indicateurs-performance/{form}/edit', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'edit'])->name('auditor.ap.indicateurs-performance.edit');
//         Route::post('indicateurs-performance', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'store'])->name('auditor.ap.indicateurs-performance.store');
//         Route::put('indicateurs-performance/{form}', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'update'])->name('auditor.ap.indicateurs-performance.update');
//         Route::delete('indicateurs-performance/{form}', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'destroy'])->name('auditor.ap.indicateurs-performance.destroy');
//         Route::post('indicateurs-performance/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'soumettre'])->name('auditor.ap.indicateurs-performance.soumettre');
//         Route::post('indicateurs-performance/{form}/valider', [App\Http\Controllers\Auditor\Ap\IndicateursPerformanceController::class, 'valider'])->name('auditor.ap.indicateurs-performance.valider');
//         // programme-travail
//         Route::get('programme-travail', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'index'])->name('audit.ap.preparation.programme-travail');
//         Route::get('programme-travail/{form}/edit', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'edit'])->name('auditor.ap.programme-travail.edit');
//         Route::post('programme-travail', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'store'])->name('auditor.ap.programme-travail.store');
//         Route::put('programme-travail/{form}', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'update'])->name('auditor.ap.programme-travail.update');
//         Route::delete('programme-travail/{form}', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'destroy'])->name('auditor.ap.programme-travail.destroy');
//         Route::post('programme-travail/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'soumettre'])->name('auditor.ap.programme-travail.soumettre');
//         Route::post('programme-travail/{form}/valider', [App\Http\Controllers\Auditor\Ap\ProgrammeTravailController::class, 'valider'])->name('auditor.ap.programme-travail.valider');
//     }); // end preparation
//     // ── Phase 2 : Realisation ──
//     Route::prefix('realisation')->group(function () {
//         // evaluations-performance
//         Route::get('evaluations-performance', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'index'])->name('audit.ap.realisation.evaluations-performance');
//         Route::get('evaluations-performance/{form}/edit', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'edit'])->name('auditor.ap.evaluations-performance.edit');
//         Route::post('evaluations-performance', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'store'])->name('auditor.ap.evaluations-performance.store');
//         Route::put('evaluations-performance/{form}', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'update'])->name('auditor.ap.evaluations-performance.update');
//         Route::delete('evaluations-performance/{form}', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'destroy'])->name('auditor.ap.evaluations-performance.destroy');
//         Route::post('evaluations-performance/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'soumettre'])->name('auditor.ap.evaluations-performance.soumettre');
//         Route::post('evaluations-performance/{form}/valider', [App\Http\Controllers\Auditor\Ap\EvaluationsPerformanceController::class, 'valider'])->name('auditor.ap.evaluations-performance.valider');
//         // benchmarking
//         Route::get('benchmarking', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'index'])->name('audit.ap.realisation.benchmarking');
//         Route::get('benchmarking/{form}/edit', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'edit'])->name('auditor.ap.benchmarking.edit');
//         Route::post('benchmarking', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'store'])->name('auditor.ap.benchmarking.store');
//         Route::put('benchmarking/{form}', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'update'])->name('auditor.ap.benchmarking.update');
//         Route::delete('benchmarking/{form}', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'destroy'])->name('auditor.ap.benchmarking.destroy');
//         Route::post('benchmarking/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'soumettre'])->name('auditor.ap.benchmarking.soumettre');
//         Route::post('benchmarking/{form}/valider', [App\Http\Controllers\Auditor\Ap\BenchmarkingController::class, 'valider'])->name('auditor.ap.benchmarking.valider');
//         // feuilles-travail
//         Route::get('feuilles-travail', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'index'])->name('audit.ap.realisation.feuilles-travail');
//         Route::get('feuilles-travail/{form}/edit', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'edit'])->name('auditor.ap.feuilles-travail.edit');
//         Route::post('feuilles-travail', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'store'])->name('auditor.ap.feuilles-travail.store');
//         Route::put('feuilles-travail/{form}', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'update'])->name('auditor.ap.feuilles-travail.update');
//         Route::delete('feuilles-travail/{form}', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'destroy'])->name('auditor.ap.feuilles-travail.destroy');
//         Route::post('feuilles-travail/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'soumettre'])->name('auditor.ap.feuilles-travail.soumettre');
//         Route::post('feuilles-travail/{form}/valider', [App\Http\Controllers\Auditor\Ap\FeuillesTravailController::class, 'valider'])->name('auditor.ap.feuilles-travail.valider');
//         // constats-ecarts
//         Route::get('constats-ecarts', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'index'])->name('audit.ap.realisation.constats-ecarts');
//         Route::get('constats-ecarts/{form}/edit', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'edit'])->name('auditor.ap.constats-ecarts.edit');
//         Route::post('constats-ecarts', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'store'])->name('auditor.ap.constats-ecarts.store');
//         Route::put('constats-ecarts/{form}', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'update'])->name('auditor.ap.constats-ecarts.update');
//         Route::delete('constats-ecarts/{form}', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'destroy'])->name('auditor.ap.constats-ecarts.destroy');
//         Route::post('constats-ecarts/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'soumettre'])->name('auditor.ap.constats-ecarts.soumettre');
//         Route::post('constats-ecarts/{form}/valider', [App\Http\Controllers\Auditor\Ap\ConstatsEcartsController::class, 'valider'])->name('auditor.ap.constats-ecarts.valider');
//     }); // end realisation
//     // ── Phase 3 : Conclusion ──
//     Route::prefix('conclusion')->group(function () {
//         // rapport-performance
//         Route::get('rapport-performance', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'index'])->name('audit.ap.conclusion.rapport-performance');
//         Route::get('rapport-performance/{form}/edit', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'edit'])->name('auditor.ap.rapport-performance.edit');
//         Route::post('rapport-performance', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'store'])->name('auditor.ap.rapport-performance.store');
//         Route::put('rapport-performance/{form}', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'update'])->name('auditor.ap.rapport-performance.update');
//         Route::delete('rapport-performance/{form}', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'destroy'])->name('auditor.ap.rapport-performance.destroy');
//         Route::post('rapport-performance/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'soumettre'])->name('auditor.ap.rapport-performance.soumettre');
//         Route::post('rapport-performance/{form}/valider', [App\Http\Controllers\Auditor\Ap\RapportPerformanceController::class, 'valider'])->name('auditor.ap.rapport-performance.valider');
//         // plan-amelioration
//         Route::get('plan-amelioration', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'index'])->name('audit.ap.conclusion.plan-amelioration');
//         Route::get('plan-amelioration/{form}/edit', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'edit'])->name('auditor.ap.plan-amelioration.edit');
//         Route::post('plan-amelioration', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'store'])->name('auditor.ap.plan-amelioration.store');
//         Route::put('plan-amelioration/{form}', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'update'])->name('auditor.ap.plan-amelioration.update');
//         Route::delete('plan-amelioration/{form}', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'destroy'])->name('auditor.ap.plan-amelioration.destroy');
//         Route::post('plan-amelioration/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'soumettre'])->name('auditor.ap.plan-amelioration.soumettre');
//         Route::post('plan-amelioration/{form}/valider', [App\Http\Controllers\Auditor\Ap\PlanAmeliorationController::class, 'valider'])->name('auditor.ap.plan-amelioration.valider');
//     }); // end conclusion
//     // ── Phase 4 : Suivi ──
//     Route::prefix('suivi')->group(function () {
//         // suivi-indicateurs
//         Route::get('suivi-indicateurs', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'index'])->name('audit.ap.suivi.suivi-indicateurs');
//         Route::get('suivi-indicateurs/{form}/edit', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'edit'])->name('auditor.ap.suivi-indicateurs.edit');
//         Route::post('suivi-indicateurs', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'store'])->name('auditor.ap.suivi-indicateurs.store');
//         Route::put('suivi-indicateurs/{form}', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'update'])->name('auditor.ap.suivi-indicateurs.update');
//         Route::delete('suivi-indicateurs/{form}', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'destroy'])->name('auditor.ap.suivi-indicateurs.destroy');
//         Route::post('suivi-indicateurs/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'soumettre'])->name('auditor.ap.suivi-indicateurs.soumettre');
//         Route::post('suivi-indicateurs/{form}/valider', [App\Http\Controllers\Auditor\Ap\SuiviIndicateursController::class, 'valider'])->name('auditor.ap.suivi-indicateurs.valider');
//     }); // end suivi
//     // ── Phase 5 : Recommandation ──
//     Route::prefix('recommandation')->group(function () {
//         // fiche-recommandation
//         Route::get('fiche-recommandation', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'index'])->name('audit.ap.recommandation.fiche-recommandation');
//         Route::get('fiche-recommandation/{form}/edit', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'edit'])->name('auditor.ap.fiche-recommandation.edit');
//         Route::post('fiche-recommandation', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'store'])->name('auditor.ap.fiche-recommandation.store');
//         Route::put('fiche-recommandation/{form}', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'update'])->name('auditor.ap.fiche-recommandation.update');
//         Route::delete('fiche-recommandation/{form}', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'destroy'])->name('auditor.ap.fiche-recommandation.destroy');
//         Route::post('fiche-recommandation/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'soumettre'])->name('auditor.ap.fiche-recommandation.soumettre');
//         Route::post('fiche-recommandation/{form}/valider', [App\Http\Controllers\Auditor\Ap\FicheRecommandationController::class, 'valider'])->name('auditor.ap.fiche-recommandation.valider');
//         // plan-action
//         Route::get('plan-action', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'index'])->name('audit.ap.recommandation.plan-action');
//         Route::get('plan-action/{form}/edit', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'edit'])->name('auditor.ap.plan-action.edit');
//         Route::post('plan-action', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'store'])->name('auditor.ap.plan-action.store');
//         Route::put('plan-action/{form}', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'update'])->name('auditor.ap.plan-action.update');
//         Route::delete('plan-action/{form}', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'destroy'])->name('auditor.ap.plan-action.destroy');
//         Route::post('plan-action/{form}/soumettre', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'soumettre'])->name('auditor.ap.plan-action.soumettre');
//         Route::post('plan-action/{form}/valider', [App\Http\Controllers\Auditor\Ap\PlanActionController::class, 'valider'])->name('auditor.ap.plan-action.valider');
//     }); // end recommandation
// }); // end ap


 });
 });


