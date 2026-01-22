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
use App\Http\Controllers\Param\EntityProcessRelationsController;
use App\Http\Controllers\Param\UsersController;
use App\Http\Controllers\Param\AuditorController;
use App\Http\Controllers\Param\CompetencyController;
use App\Http\Controllers\Param\CompetencyCategoryController;

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
    Route::post('processus', [MacroProcessusController::class, 'storeProcessus'])
        ->name('processus.store');
    Route::put('processus/{processus}', [MacroProcessusController::class, 'updateProcessus'])
        ->name('processus.update');
    Route::delete('processus/{processus}', [MacroProcessusController::class, 'destroyProcessus'])
        ->name('processus.destroy');
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

// ═══════════════════════════════════════════════════════════════════════
// 👥 UTILISATEURS - ROUTES PARAM.PROJECTS.USERS
// ═══════════════════════════════════════════════════════════════════════

/**
 * ⚠️ ORDRE TRÈS IMPORTANT !
 * Les routes SPÉCIFIQUES doivent être AVANT les routes PARAMÉTRÉES
 */

// Routes spécifiques (sans paramètre)
Route::get('users', [UsersController::class, 'index'])
    ->name('users.index');

Route::get('users/create', [UsersController::class, 'create'])
    ->name('users.create');

Route::post('users', [UsersController::class, 'store'])
    ->name('users.store');

Route::get('users/export', [UsersController::class, 'export'])
    ->name('users.export');

// Routes API (avant les paramétrées)
Route::get('users/api/functions-for-entity', [UsersController::class, 'getFunctionsForEntity'])
    ->name('users.functions-for-entity');

// Routes paramétrées (avec {user})
Route::get('users/{user}', [UsersController::class, 'show'])
    ->name('users.show');

Route::get('users/{user}/edit', [UsersController::class, 'edit'])
    ->name('users.edit');

Route::put('users/{user}', [UsersController::class, 'update'])
    ->name('users.update');

Route::delete('users/{user}', [UsersController::class, 'destroy'])
    ->name('users.destroy');

Route::patch('users/{user}/status', [UsersController::class, 'changeStatus'])
    ->name('users.changeStatus');

Route::post('users/{user}/assign-function', [UsersController::class, 'assignFunction'])
    ->name('users.assign-function');

Route::post('users/{user}/revoke-function', [UsersController::class, 'revokeFunction'])
    ->name('users.revoke-function');

// ═══════════════════════════════════════════════════════════════════════
// 🎖️ AUDITEURS - ROUTES PARAM.PROJECTS.AUDITORS
// ═══════════════════════════════════════════════════════════════════════

/**
 * ⚠️ ORDRE TRÈS IMPORTANT !
 * Les routes SPÉCIFIQUES doivent être AVANT les routes PARAMÉTRÉES
 */

// Routes spécifiques (sans paramètre) - EN PREMIER
Route::get('auditors/index', [AuditorController::class, 'index'])
    ->name('auditors.index');

Route::get('auditors/create', [AuditorController::class, 'create'])
    ->name('auditors.create');

Route::post('auditors', [AuditorController::class, 'store'])
    ->name('auditors.store');

// Routes API (avant les paramétrées)
Route::get('auditors/api/competencies-by-category', [AuditorController::class, 'getCompetenciesByCategory'])
    ->name('auditors.competencies.byCategory');

// Routes paramétrées (avec {auditor}) - EN DERNIER
Route::get('auditors/{auditor}', [AuditorController::class, 'show'])
    ->name('auditors.show');

Route::get('auditors/{auditor}/edit', [AuditorController::class, 'edit'])
    ->name('auditors.edit');

Route::put('auditors/{auditor}', [AuditorController::class, 'update'])
    ->name('auditors.update');

Route::delete('auditors/{auditor}', [AuditorController::class, 'destroy'])
    ->name('auditors.destroy');

Route::patch('auditors/{auditor}/status', [AuditorController::class, 'changeStatus'])
    ->name('auditors.changeStatus');

Route::post('auditors/{auditor}/competencies/assign', [AuditorController::class, 'assignCompetency'])
    ->name('auditors.competencies.assign');

Route::post('auditors/{auditor}/competencies/revoke', [AuditorController::class, 'revokeCompetency'])
    ->name('auditors.competencies.revoke');

// ═══════════════════════════════════════════════════════════════════════
// 🎯 COMPÉTENCES - ROUTES PARAM.PROJECTS.COMPETENCIES
// ═══════════════════════════════════════════════════════════════════════

Route::resource('competencies', CompetencyController::class)->names([
    'index'   => 'competencies.index',
    'create'  => 'competencies.create',
    'store'   => 'competencies.store',
    'show'    => 'competencies.show',
    'edit'    => 'competencies.edit',
    'update'  => 'competencies.update',
    'destroy' => 'competencies.destroy',
]);

// ═══════════════════════════════════════════════════════════════════════
// 📁 CATÉGORIES DE COMPÉTENCES - ROUTES PARAM.PROJECTS.COMPETENCY_CATEGORIES
// ═══════════════════════════════════════════════════════════════════════

Route::resource('competency-categories', CompetencyCategoryController::class)->names([
    'index'   => 'competency-categories.index',
    'create'  => 'competency-categories.create',
    'store'   => 'competency-categories.store',
    'show'    => 'competency-categories.show',
    'edit'    => 'competency-categories.edit',
    'update'  => 'competency-categories.update',
    'destroy' => 'competency-categories.destroy',
]);



use App\Http\Controllers\Param\UnavailabilityController;

// ============================================================================
// ROUTES INDISPONIBILITÉS - VERSION COMPLÈTE AVEC TOUS LES ENDPOINTS
// ============================================================================

Route::prefix('param/projects/unavailabilities')
    ->name('unavailabilities.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        
        // ==================== PAGES ====================
        
        // Index - Affiche le calendrier et les tables
        Route::get('/', [UnavailabilityController::class, 'index'])
            ->name('index');
        
        
        // ==================== INDISPONIBILITÉS GLOBALES ====================
        
        // Créer
        Route::post('store-global', [UnavailabilityController::class, 'storeGlobal'])
            ->name('store-global');
        
        // Mettre à jour
        Route::put('{id}/update-global', [UnavailabilityController::class, 'updateGlobal'])
            ->name('update-global');
        
        // Supprimer
        Route::delete('{id}/destroy-global', [UnavailabilityController::class, 'destroyGlobal'])
            ->name('destroy-global');
        
        
        // ==================== INDISPONIBILITÉS AUDITEURS ====================
        
        // Créer
        Route::post('store-auditor', [UnavailabilityController::class, 'storeAuditor'])
            ->name('store-auditor');
        
        // Mettre à jour
        Route::put('{id}/update-auditor', [UnavailabilityController::class, 'updateAuditor'])
            ->name('update-auditor');
        
        // Approuver
        Route::post('{id}/approve-auditor', [UnavailabilityController::class, 'approveAuditor'])
            ->name('approve-auditor');
        
        // Supprimer
        Route::delete('{id}/destroy-auditor', [UnavailabilityController::class, 'destroyAuditor'])
            ->name('destroy-auditor');
        
        
        // ==================== TYPES PERSONNALISÉS ====================
        
        // Créer un type personnalisé (NOUVEAU - C'EST LA ROUTE MANQUANTE!)
        Route::post('create-type', [UnavailabilityController::class, 'createType'])
            ->name('create-type');
        
        // Obtenir les types par catégorie
        Route::get('types/{category}', [UnavailabilityController::class, 'getTypesByCategory'])
            ->name('types-by-category');
        
        
        // ==================== REQUÊTES API ====================
        
        // Vérifier disponibilité auditeur
        Route::get('check-availability', [UnavailabilityController::class, 'checkAvailability'])
            ->name('check-availability');
        
        // Indisponibilités par période
        Route::get('by-period', [UnavailabilityController::class, 'getByPeriod'])
            ->name('by-period');
        
        // Statistiques
        Route::get('stats', [UnavailabilityController::class, 'getStats'])
            ->name('stats');
        
        // Export CSV
        Route::get('export-csv', [UnavailabilityController::class, 'exportCsv'])
            ->name('export-csv');
    });