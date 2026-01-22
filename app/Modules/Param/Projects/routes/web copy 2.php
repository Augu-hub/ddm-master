<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Param\ProjetController;
use App\Http\Controllers\Param\MacroProcessusController;
use App\Http\Controllers\Param\ProcessusController;
use App\Http\Controllers\Param\ActiviteController;
use App\Http\Controllers\Param\EntiteController;
use App\Http\Controllers\Param\FonctionController;
use App\Http\Controllers\Param\DistributionController;
use App\Http\Controllers\Param\ChartsController;
use App\Http\Controllers\Param\MpsRespController;
use App\Http\Controllers\Param\FunctionAssignmentController;
use App\Http\Controllers\Param\EntityFunctionsChartController;
use App\Http\Controllers\Param\FunctionUsersController;
use App\Http\Controllers\Param\EntityProcessRelationsController;  // ← AJOUTER ICI

/* ====== Accueil du module ====== */
Route::get('/', fn () => Inertia::render('dashboards/Param/Entities/index'))
     ->name('home');

/* ====== Projets ====== */
Route::resource('projects', ProjetController::class)->names([
    'index'   => 'projects.index',
    'create'  => 'projects.create',
    'store'   => 'projects.store',
    'show'    => 'projects.show',
    'edit'    => 'projects.edit',
    'update'  => 'projects.update',
    'destroy' => 'projects.destroy',
]);

/* ====== Entités ====== */
Route::resource('entities', EntiteController::class)->names([
    'index'   => 'entities.index',
    'create'  => 'entities.create',
    'store'   => 'entities.store',
    'show'    => 'entities.show',
    'edit'    => 'entities.edit',
    'update'  => 'entities.update',
    'destroy' => 'entities.destroy',
]);

/* ====== MPA (Macro-Processus-Activités) ====== */
Route::resource('mpa', MacroProcessusController::class)->names([
    'index'   => 'mpa.index',
    'create'  => 'mpa.create',
    'store'   => 'mpa.store',
    'show'    => 'mpa.show',
    'edit'    => 'mpa.edit',
    'update'  => 'mpa.update',
    'destroy' => 'mpa.destroy',
]);
Route::post('/param/mpa/ai/suggest-processus', [MacroProcessusController::class, 'aiSuggestProcessus'])
    ->middleware(['web', 'auth'])
    ->name('mpa.ai.suggest-processus');

Route::post('/param/mpa/ai/suggest-data', [MacroProcessusController::class, 'aiSuggestData'])
    ->middleware(['web', 'auth'])
    ->name('mpa.ai.suggest-data');

Route::post('/param/mpa/ai/suggest-activites', [MacroProcessusController::class, 'aiSuggestActivites'])
    ->middleware(['web', 'auth'])
    ->name('mpa.ai.suggest-activites');


Route::prefix('projects')->name('projects.')->group(function () {
    
    // ─────────────────────────────────────────────────────────────────
    // 🤖 IA ENDPOINTS
    // ─────────────────────────────────────────────────────────────────

    
    // ─────────────────────────────────────────────────────────────────
    // 🏢 GESTION MACRO
    // ─────────────────────────────────────────────────────────────────
    

    
    // ─────────────────────────────────────────────────────────────────
    // 📝 GESTION PROCESSUS
    // ─────────────────────────────────────────────────────────────────
    
    Route::post('processus', [MacroProcessusController::class, 'storeProcessus'])
        ->name('processus.store');
    
    Route::put('processus/{processus}', [MacroProcessusController::class, 'updateProcessus'])
        ->name('processus.update');
    
    Route::delete('processus/{processus}', [MacroProcessusController::class, 'destroyProcessus'])
        ->name('processus.destroy');
    
    // ─────────────────────────────────────────────────────────────────
    // 📝 GESTION ACTIVITÉ
    // ─────────────────────────────────────────────────────────────────
    
    Route::post('activites', [MacroProcessusController::class, 'storeActivite'])
        ->name('activites.store');
    
    Route::put('activites/{activite}', [MacroProcessusController::class, 'updateActivite'])
        ->name('activites.update');
    
    Route::delete('activites/{activite}', [MacroProcessusController::class, 'destroyActivite'])
        ->name('activites.destroy');
    
});
/* ✅ Validation des macros par défaut ====== */
Route::post('macro/validate-defaults', [MacroProcessusController::class, 'validateDefaults'])
     ->name('macro.validate');

/* ====== Processus ====== */
Route::resource('processus', ProcessusController::class)->names([
    'index'   => 'processus.index',
    'create'  => 'processus.create',
    'store'   => 'processus.store',
    'show'    => 'processus.show',
    'edit'    => 'processus.edit',
    'update'  => 'processus.update',
    'destroy' => 'processus.destroy',
]);

/* ====== Activités ====== */
Route::resource('activites', ActiviteController::class)->names([
    'index'   => 'activites.index',
    'create'  => 'activites.create',
    'store'   => 'activites.store',
    'show'    => 'activites.show',
    'edit'    => 'activites.edit',
    'update'  => 'activites.update',
    'destroy' => 'activites.destroy',
]);

/* ====== Fonctions ====== */
Route::resource('fonctions', FonctionController::class)->names([
    'index'   => 'fonctions.index',
    'create'  => 'fonctions.create',
    'store'   => 'fonctions.store',
    'show'    => 'fonctions.show',
    'edit'    => 'fonctions.edit',
    'update'  => 'fonctions.update',
    'destroy' => 'fonctions.destroy',
]);

/* ====== Fonctions → Entité ====== */
Route::get('functions/list',    [FunctionAssignmentController::class, 'list'])   ->name('functions.list');
Route::get('functions/current', [FunctionAssignmentController::class, 'current'])->name('functions.current');
Route::post('functions/commit', [FunctionAssignmentController::class, 'commit'])->name('functions.commit');

/* ====== Fonctions ↔ Utilisateurs ====== */
Route::prefix('functions/users')->name('functions.users.')->group(function () {
    Route::get('/',        [FunctionUsersController::class,'index'])->name('index');
    Route::get('search',   [FunctionUsersController::class,'search'])->name('search');
    Route::get('map',      [FunctionUsersController::class,'map'])   ->name('map');
    Route::post('set',     [FunctionUsersController::class,'set'])   ->name('set');
    Route::post('clear',   [FunctionUsersController::class,'clear']) ->name('clear');
});

/* ====== Distribution ====== */
Route::get('distribution', [DistributionController::class, 'index'])->name('distribution.index');
Route::get('distribution/tree',     [DistributionController::class, 'tree'])->name('distribution.tree');
Route::get('distribution/current',  [DistributionController::class, 'current'])->name('distribution.current');
Route::post('distribution/preview', [DistributionController::class, 'preview'])->name('distribution.preview');
Route::post('distribution/commit',  [DistributionController::class, 'commit'])->name('distribution.commit');

/* ====== MPS Responsables ====== */
Route::get('mpsresp/tree',     [MpsRespController::class,'tree'])->name('mpsresp.tree');
Route::get('mpsresp/current',  [MpsRespController::class,'current'])->name('mpsresp.current');
Route::post('mpsresp/assign',  [MpsRespController::class,'assign'])->name('mpsresp.assign');
Route::post('mpsresp/unassign',[MpsRespController::class,'unassign'])->name('mpsresp.unassign');
Route::get('mpsresp/options',  [MpsRespController::class,'options'])->name('mpsresp.options');
Route::get('mpsresp/table',    [MpsRespController::class,'table'])->name('mpsresp.table');
Route::post('mpsresp/save-users',[MpsRespController::class,'saveUsers'])->name('mpsresp.saveUsers');
Route::get('mpsresp/map',      [MpsRespController::class,'map'])->name('mpsresp.map');

/* ====== Charts ====== */
Route::get('charts/entity', [ChartsController::class, 'entity'])->name('charts.entity');
Route::get('charts/function', [ChartsController::class, 'function'])->name('charts.function');
Route::get('charts/entity-functions', [EntityFunctionsChartController::class, 'index'])->name('charts.entity-functions');

/* ====== Param Projects MPS Resp ====== */
Route::prefix('param/projects/mpsresp')->name('param.projects.mpsresp.')->group(function () {
    // (vide pour le moment)
});

/* ====== Param Projects Functions ====== */
Route::prefix('param/projects/functions')->name('param.projects.functions.')->group(function(){
    Route::get('users/search', [FunctionAssignmentController::class,'users'])   ->name('users');
    Route::get('users/map',    [FunctionAssignmentController::class,'userMap'])->name('userMap');
    Route::post('users/set',   [FunctionAssignmentController::class,'setUser'])->name('setUser');
    Route::post('users/clear', [FunctionAssignmentController::class,'clearUser'])->name('clearUser');
});

/* ====== RELATIONS BPMN ====== */
Route::prefix('macro')->name('macro.')->group(function () {
    // Routes fixes en premier
    Route::get('/relations', [EntityProcessRelationsController::class, 'entities'])
        ->name('relations.entities');

    Route::get('/relations/processes/all', [EntityProcessRelationsController::class, 'processes'])
        ->name('relations.processes');

    // Routes avec paramètres en dernier
    Route::get('/relations/{entity}/load', [EntityProcessRelationsController::class, 'load'])
        ->name('relations.load');

    Route::post('/relations/{entity}/save', [EntityProcessRelationsController::class, 'save'])
        ->name('relations.save');

    Route::get('/relations/{entity}', [EntityProcessRelationsController::class, 'index'])
        ->name('relations.index');
});

/* ====== GRAPHE AUTO ====== */
Route::get('macro/graph/{entity}', function (\App\Models\Param\Entite $entity) {
    return inertia('dashboards/Param/Macro/Graph/Index', [
        'entity' => $entity
    ]);
})->name('macro.graph.index');


use App\Http\Controllers\Param\UsersController;


/**
 * Routes pour la gestion des utilisateurs dans param.projects
 * À inclure dans votre fichier de routes tenant (routes/tenant.php)
 */

   
    // Routes pour les utilisateurs
        Route::prefix('users')->name('users.')->group(function () {
            
            // Liste et création
            Route::get('/', [UsersController::class, 'index'])->name('index');
            Route::get('/create', [UsersController::class, 'create'])->name('create');
            Route::post('/store', [UsersController::class, 'store'])->name('store');
            
            // Export (avant les routes avec {user} pour éviter les conflits)
            Route::get('/export', [UsersController::class, 'export'])->name('export');
            
            // Actions sur un utilisateur spécifique
            Route::get('/{user}', [UsersController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UsersController::class, 'update'])->name('update');
            Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
            
            // Actions supplémentaires
            Route::post('/{user}/change-status', [UsersController::class, 'changeStatus'])->name('change-status');
            Route::post('/{user}/resend-email', [UsersController::class, 'resendWelcomeEmail'])->name('resend-email');
        });



    // ═══════════════════════════════════════════════════════════════════════
    // ⚠️ ORDRE TRÈS IMPORTANT !
    // Les routes SPÉCIFIQUES doivent être AVANT les routes PARAMÉTRÉES
    // ═══════════════════════════════════════════════════════════════════════

    // ─────────────────────────────────────────────────────────────────────
    // 1️⃣ ROUTES SPÉCIFIQUES (sans paramètre)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /param/projects/users
     * Afficher la liste des utilisateurs
     */
    Route::get('users', [UsersController::class, 'index'])
        ->name('users.index');

    /**
     * GET /param/projects/users/create
     * Afficher le formulaire de création
     */
    Route::get('users/create', [UsersController::class, 'create'])
        ->name('users.create');

    /**
     * POST /param/projects/users
     * Enregistrer un nouvel utilisateur
     */
    Route::post('users', [UsersController::class, 'store'])
        ->name('users.store');

    /**
     * GET /param/projects/users/export
     * Exporter les utilisateurs
     */
    Route::get('users/export', [UsersController::class, 'export'])
        ->name('users.export');

    // ─────────────────────────────────────────────────────────────────────
    // 2️⃣ ROUTES API (prefixe api/ pour éviter les conflits)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /param/projects/users/api/functions-for-entity?entity_id=1
     * ✅ IMPORTANT: Placer AVANT les routes {user}
     * Récupérer les fonctions pour une entité
     */
    Route::get('users/api/functions-for-entity', [UsersController::class, 'getFunctionsForEntity'])
        ->name('users.functions-for-entity');

    // ─────────────────────────────────────────────────────────────────────
    // 3️⃣ ROUTES PARAMÉTRÉES (avec paramètre {user})
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /param/projects/users/{user}
     * Afficher les détails d'un utilisateur
     */
    Route::get('users/{user}', [UsersController::class, 'show'])
        ->name('users.show');

    /**
     * GET /param/projects/users/{user}/edit
     * Afficher le formulaire d'édition
     */
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])
        ->name('users.edit');

    /**
     * PUT /param/projects/users/{user}
     * Mettre à jour un utilisateur
     */
    Route::put('users/{user}', [UsersController::class, 'update'])
        ->name('users.update');

    /**
     * DELETE /param/projects/users/{user}
     * Supprimer un utilisateur
     */
    Route::delete('users/{user}', [UsersController::class, 'destroy'])
        ->name('users.destroy');

    /**
     * PATCH /param/projects/users/{user}/status
     * Changer le statut d'un utilisateur
     */
    Route::patch('users/{user}/status', [UsersController::class, 'changeStatus'])
        ->name('users.changeStatus');

    /**
     * POST /param/projects/users/{user}/assign-function
     * Assigner une fonction à un utilisateur
     */
    Route::post('users/{user}/assign-function', [UsersController::class, 'assignFunction'])
        ->name('users.assign-function');

    /**
     * POST /param/projects/users/{user}/revoke-function
     * Révoquer une fonction
     */
    Route::post('users/{user}/revoke-function', [UsersController::class, 'revokeFunction'])
        ->name('users.revoke-function');

use App\Http\Controllers\Param\AuditorController;
use App\Http\Controllers\Param\CompetencyController;
use App\Http\Controllers\Param\CompetencyCategoryController;       

    // Resource auditeurs (CRUD standard)
    Route::resource('auditors', AuditorController::class)->names([
        'index'   => 'auditors.index',
        'create'  => 'auditors.create',
        'store'   => 'auditors.store',
        'show'    => 'auditors.show',
        'edit'    => 'auditors.edit',
        'update'  => 'auditors.update',
        'destroy' => 'auditors.destroy',
    ]);

    // Actions supplémentaires auditeurs
    Route::prefix('auditors')->name('auditors.')->group(function () {
        
        // Changer le statut d'un auditeur
        Route::patch('/{auditor}/status', [AuditorController::class, 'changeStatus'])
            ->name('changeStatus');
        
        // Assigner une compétence à un auditeur
        Route::post('/{auditor}/competencies/assign', [AuditorController::class, 'assignCompetency'])
            ->name('competencies.assign');
        
        // Révoquer une compétence d'un auditeur
        Route::post('/{auditor}/competencies/revoke', [AuditorController::class, 'revokeCompetency'])
            ->name('competencies.revoke');
        
        // API: Récupérer les compétences d'une catégorie (JSON)
        Route::get('/api/competencies-by-category', [AuditorController::class, 'getCompetenciesByCategory'])
            ->name('competencies.byCategory');
    });

    // ============================================================
    // 🎖️ COMPÉTENCES
    // ============================================================
    
    // Resource compétences (CRUD standard)
    Route::resource('competencies', CompetencyController::class)->names([
        'index'   => 'competencies.index',
        'create'  => 'competencies.create',
        'store'   => 'competencies.store',
        'show'    => 'competencies.show',
        'edit'    => 'competencies.edit',
        'update'  => 'competencies.update',
        'destroy' => 'competencies.destroy',
    ]);

    // ============================================================
    // 📁 CATÉGORIES DE COMPÉTENCES
    // ============================================================
    
    // Resource catégories de compétences (CRUD standard)
    Route::resource('competency-categories', CompetencyCategoryController::class)->names([
        'index'   => 'competency-categories.index',
        'create'  => 'competency-categories.create',
        'store'   => 'competency-categories.store',
        'show'    => 'competency-categories.show',
        'edit'    => 'competency-categories.edit',
        'update'  => 'competency-categories.update',
        'destroy' => 'competency-categories.destroy',
    ]);
