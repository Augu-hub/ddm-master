<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Process\Evaluations\ProcessEvaluationController;
/*
|--------------------------------------------------------------------------
| ROUTE OFFICIELLE - DIADDEM PROCESS EVALUATIONS
|--------------------------------------------------------------------------
|
| Architecture : Route::prefix('process')->name('process.core.')
| Sous-groupe : prefix('evaluations')->name('evaluations.')
|
| Important : Les noms de route utilisent les slashes "/" pas les points
| Exemple : route('process.core.evaluations.index') → /process/evaluations/
|
*/

// Route::prefix('process')
//     ->name('process.core.')
//     ->group(function () {

//         Route::view('/', 'modules.process.core.home')
//             ->name('home');

//         Route::prefix('evaluations')
//             ->name('evaluations.')
//             ->group(function () {

//                 // PAGE PRINCIPALE
//                 Route::get('/', [ProcessEvaluationController::class, 'index'])
//                     ->name('index');

//                 // RÉCUPÉRER TOUS LES SCORES POUR AFFICHAGE STATS
//                 Route::get('get-all-scores', [ProcessEvaluationController::class, 'getAllScores'])
//                     ->name('get-all-scores');

//                 // SESSIONS PAR PROCESSUS
//                 Route::get('sessions', [ProcessEvaluationController::class, 'getSessions'])
//                     ->name('sessions');

//                 // CRÉER SESSION
//                 Route::post('sessions/create', [ProcessEvaluationController::class, 'createSession'])
//                     ->name('sessions.create');

//                 // CLÔTURER SESSION
//                 Route::post('sessions/close', [ProcessEvaluationController::class, 'closeSession'])
//                     ->name('sessions.close');

//                 // DUPLIQUER SESSION
//                 Route::post('sessions/duplicate', [ProcessEvaluationController::class, 'duplicateSession'])
//                     ->name('sessions.duplicate');

//                 // SUPPRIMER SESSION
//                 Route::post('sessions/delete', [ProcessEvaluationController::class, 'deleteSession'])
//                     ->name('sessions.delete');

//                 // CHARGER ÉVALUATIONS EXISTANTES
//                 Route::get('load', [ProcessEvaluationController::class, 'loadEvaluations'])
//                     ->name('load');

//                 // ENREGISTRER MATURITÉ
//                 Route::post('maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
//                     ->name('maturity.save');

//                 // ENREGISTRER AXES (motricité, transversalité, stratégique)
//                 Route::post('axis/save', [ProcessEvaluationController::class, 'saveAxis'])
//                     ->name('axis.save');
//             });
//     });


/*
|--------------------------------------------------------------------------
| DIADDEM PROCESS EVALUATIONS - ROUTES OFFICIELLES
|--------------------------------------------------------------------------
|
| Architecture complète sans duplication
| Middleware : auth:sanctum, tenant (injecter le contexte locataire)
|
*/


Route::prefix('process')
    ->name('process.core.')
    ->group(function () {
        Route::prefix('evaluations')
            ->name('evaluations.')
            ->group(function () {

                // Alternative : Entrée directe
                Route::get('entry', [ProcessEvaluationController::class, 'index'])
                    ->name('entry.index');

                Route::post('entry/save', [ProcessEvaluationController::class, 'saveScores'])
                    ->name('entry.save');
            });
    });







use App\Http\Controllers\Process\ProcessContractController;

/**
 * ==========================================
 * 📌 ROUTES CONTRATS D'INTERFACES
 * ==========================================
 * 
 * Middleware: auth, verified
 * Base: /process/contracts
 * 
 */

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('process/contracts')->group(function () {
        
        // ========== GESTION CONTRATS ==========
        
      
        Route::get('/', [ProcessContractController::class, 'index'])
            ->name('process.contracts.index');

       
       
        Route::get('/load', [ProcessContractController::class, 'load'])
            ->name('process.contracts.load');

       
        Route::post('/function-users', [ProcessContractController::class, 'getFunctionUsers'])
            ->name('process.contracts.function-users');

       
        Route::post('/save', [ProcessContractController::class, 'save'])
            ->name('process.contracts.save');

       
        Route::post('/upload', [ProcessContractController::class, 'uploadFile'])
            ->name('process.contracts.upload');

       
        Route::get('/download', [ProcessContractController::class, 'downloadFile'])
            ->name('process.contracts.download');

       
        Route::post('/file/delete', [ProcessContractController::class, 'deleteFile'])
            ->name('process.contracts.file.delete');

      
        Route::get('/export-excel', [ProcessContractController::class, 'exportExcel'])
            ->name('process.contracts.export.excel');

       
        Route::get('/export-pdf', [ProcessContractController::class, 'exportPdf'])
            ->name('process.contracts.export.pdf');

        
        Route::get('/history', [ProcessContractController::class, 'getHistory'])
            ->name('process.contracts.history');

       
        Route::get('/function-user', [ProcessContractController::class, 'getFunctionUser'])
            ->name('process.contracts.function.user');

       
        Route::post('/archive', [ProcessContractController::class, 'archive'])
            ->name('process.contracts.archive');

        Route::post('/restore', [ProcessContractController::class, 'restore'])
            ->name('process.contracts.restore');
    });
});

use App\Http\Controllers\Process\ProcessRaciController;



// routes/web.php ou routes/tenant.php

Route::prefix('raci')
    ->as('raci.')  // <- as() au lieu de name() pour éviter les conflits
    ->group(function () {

        Route::get('/', [ProcessRaciController::class, 'index'])
            ->name('index');

        // Charger activités + fonctions
        Route::get('/load-process', [ProcessRaciController::class, 'loadProcess'])
            ->name('load-process'); // Nom simple et clair

        // Charger matrice
        Route::get('/matrix/load', [ProcessRaciController::class, 'loadRaciMatrix'])
            ->name('matrix.load');

        // Sauvegarder matrice
        Route::post('/matrix/save', [ProcessRaciController::class, 'saveRaciMatrix'])
            ->name('matrix.save');

        // Créer session
        Route::post('/session/create', [ProcessRaciController::class, 'createSession'])
            ->name('session.create');

        // Fermer session (optionnel, plus propre que archive)
        Route::post('/session/{session}/close', [ProcessRaciController::class, 'closeSession'])
            ->name('session.close');

        // Archiver
        Route::post('/session/{session}/archive', [ProcessRaciController::class, 'archiveSession'])
            ->name('session.archive');

        // Supprimer
        Route::delete('/session/{session}', [ProcessRaciController::class, 'deleteSession'])
            ->name('session.delete');

        // Export Excel
        Route::get('/export-excel', [ProcessRaciController::class, 'exportExcel'])
            ->name('export.excel');
    });



use App\Http\Controllers\Process\Evaluations\ProcessEvaluationSessionController;


Route::prefix('process/evaluations')
    ->name('.')
    ->group(function () {

        // ========================================================================
        // 📋 GESTION DES SESSIONS (SessionController)
        // ========================================================================
        
       
        // ========================================================================
        // 📊 ÉVALUATIONS - CRITICITÉ / MATURITÉ
        // ========================================================================
        
        Route::prefix('criticality')
            ->name('evaluations.criticality.')
            ->group(function () {
                
                // PAGE PRINCIPALE - ÉVALUATION
                Route::get('/', [ProcessEvaluationController::class, 'index'])
                    ->name('index');

                // CHARGER ÉVALUATIONS EXISTANTES
                Route::get('load', [ProcessEvaluationController::class, 'loadEvaluations'])
                    ->name('load');

                // SAUVEGARDER MATURITÉ (12 critères)
                Route::post('maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
                    ->name('maturity.save');

                // SAUVEGARDER AXE (motricité, transversalité, stratégique)
                Route::post('axis/save', [ProcessEvaluationController::class, 'saveAxis'])
                    ->name('axis.save');

                // RADARS COMPARAISON
                Route::get('radar/session', [ProcessEvaluationController::class, 'getSessionRadarData'])
                    ->name('radar.session');

                Route::get('radar/compare', [ProcessEvaluationController::class, 'compareRadar'])
                    ->name('radar.compare');

                // RAPPORTS
                Route::post('report/generate', [ProcessEvaluationController::class, 'generateReportPreview'])
                    ->name('report.generate');

                Route::post('report/export-pdf', [ProcessEvaluationController::class, 'exportReportPDF'])
                    ->name('report.export-pdf');

                Route::post('report/export-excel', [ProcessEvaluationController::class, 'exportReportExcel'])
                    ->name('report.export-excel');

                Route::post('report/save', [ProcessEvaluationController::class, 'saveReportToDB'])
                    ->name('report.save');
            });

        // ========================================================================
        // PAGE PRINCIPALE ÉVALUATION (pas de sessions ici, juste évaluation)
        // ========================================================================
        
        Route::get('/', [ProcessEvaluationController::class, 'index'])
            ->name('evaluations.index');

        Route::get('load', [ProcessEvaluationController::class, 'loadEvaluations'])
            ->name('evaluations.load');

        Route::post('maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
            ->name('evaluations.maturity.save');

        Route::post('axis/save', [ProcessEvaluationController::class, 'saveAxis'])
            ->name('evaluations.axis.save');

        Route::get('radar/session', [ProcessEvaluationController::class, 'getSessionRadarData'])
            ->name('evaluations.radar.session');

        Route::get('radar/compare', [ProcessEvaluationController::class, 'compareRadar'])
            ->name('evaluations.radar.compare');
    });


Route::prefix('sessions')
    ->name('sessions.')
    ->group(function () {
        
        // PAGE PRINCIPALE
        Route::get('/', [ProcessEvaluationSessionController::class, 'index'])
            ->name('index');

        // CRÉER NOUVELLE SESSION
        Route::post('create', [ProcessEvaluationSessionController::class, 'createSession'])
            ->name('create');

        // DUPLIQUER SESSION
        Route::post('duplicate', [ProcessEvaluationSessionController::class, 'duplicateSession'])
            ->name('duplicate');

        // FERMER SESSION
        Route::post('close', [ProcessEvaluationSessionController::class, 'closeSession'])
            ->name('close');

        // ARCHIVER SESSION
        Route::post('archive', [ProcessEvaluationSessionController::class, 'archiveSession'])
            ->name('archive');

        // SUPPRIMER SESSION
        Route::post('delete', [ProcessEvaluationSessionController::class, 'deleteSession'])
            ->name('delete');

        // ACTIVER SESSION
        Route::post('activate', [ProcessEvaluationSessionController::class, 'activateSession'])
            ->name('activate');

        // RÉCUPÉRER SESSION ACTIVE
        Route::get('active', [ProcessEvaluationSessionController::class, 'getActiveSession'])
            ->name('active');
    });
Route::prefix('raci')
    ->name('raci.')
    ->group(function () {
        
        // PAGE PRINCIPALE - MATRICE RACI
        // route('process.core.raci.index')
        // GET /process/evaluations/raci
        Route::get('/', [ProcessRaciController::class, 'index'])
            ->name('index');

        // CHARGER MATRICE RACI
        // route('process.core.raci.matrix.load')
        // GET /process/evaluations/raci/matrix/load
        Route::get('matrix/load', [ProcessRaciController::class, 'loadMatrix'])
            ->name('matrix.load');

        // ENREGISTRER MATRICE RACI
        // route('process.core.raci.matrix.save')
        // POST /process/evaluations/raci/matrix/save
        Route::post('matrix/save', [ProcessRaciController::class, 'saveMatrix'])
            ->name('matrix.save');

        // EXPORTER EXCEL
        // route('process.core.raci.export-excel')
        // GET /process/evaluations/raci/export-excel
        Route::get('export-excel', [ProcessRaciController::class, 'exportExcel'])
            ->name('export-excel');
    });
// ========================================================================
// 📊 ÉVALUATIONS - CRITICITÉ / MATURITÉ
// ========================================================================

Route::prefix('criticality')
    ->name('evaluations.criticality.')
    ->group(function () {
        
        // PAGE PRINCIPALE
        Route::get('/', [ProcessEvaluationController::class, 'index'])
            ->name('index');

        // CHARGER ÉVALUATIONS EXISTANTES
        Route::get('load', [ProcessEvaluationController::class, 'loadEvaluations'])
            ->name('load');

        // SAUVEGARDER MATURITÉ (12 critères)
        Route::post('maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
            ->name('maturity.save');

        // SAUVEGARDER AXE (motricité, transversalité, stratégique)
        Route::post('axis/save', [ProcessEvaluationController::class, 'saveAxis'])
            ->name('axis.save');

        // RADARS
        Route::get('radar/session', [ProcessEvaluationController::class, 'getSessionRadarData'])
            ->name('radar.session');

        Route::get('radar/compare', [ProcessEvaluationController::class, 'compareRadar'])
            ->name('radar.compare');

        // RAPPORTS
        Route::post('report/generate', [ProcessEvaluationController::class, 'generateReportPreview'])
            ->name('report.generate');

        Route::post('report/export-pdf', [ProcessEvaluationController::class, 'exportReportPDF'])
            ->name('report.export-pdf');

        Route::post('report/export-excel', [ProcessEvaluationController::class, 'exportReportExcel'])
            ->name('report.export-excel');

        Route::post('report/save', [ProcessEvaluationController::class, 'saveReportToDB'])
            ->name('report.save');
    });

use App\Http\Controllers\Process\Evaluations\ProcessIdeaController;

Route::prefix('process/evaluations/idea')
    ->name('process.core.idea.')  // ← FIX: Ajouter le préfixe complet avec le point
    ->group(function () {
        
        // PAGE PRINCIPALE - MATRICE IDEA
        // route('process.core.idea.index')
        // GET /process/evaluations/idea
        Route::get('/', [ProcessIdeaController::class, 'index'])
            ->name('index');

        // CHARGER MATRICE IDEA
        // route('process.core.idea.matrix.load')
        // GET /process/evaluations/idea/matrix/load
        Route::get('matrix/load', [ProcessIdeaController::class, 'loadMatrix'])
            ->name('matrix.load');

        // ENREGISTRER MATRICE IDEA
        // route('process.core.idea.matrix.save')
        // POST /process/evaluations/idea/matrix/save
        Route::post('matrix/save', [ProcessIdeaController::class, 'saveMatrix'])
            ->name('matrix.save');

        // EXPORTER EXCEL
        // route('process.core.idea.export-excel')
        // GET /process/evaluations/idea/export-excel
        Route::get('export-excel', [ProcessIdeaController::class, 'exportExcel'])
            ->name('export-excel');
    });


    Route::prefix('idea')
    ->name('idea.')
    ->group(function () {
        
       
        Route::get('matrix/load', [ProcessIdeaController::class, 'loadMatrix'])
            ->name('matrix.load');

        // ENREGISTRER MATRICE RACI
        // route('process.core.raci.matrix.save')
        // POST /process/evaluations/raci/matrix/save
        Route::post('matrix/save', [ProcessIdeaController::class, 'saveMatrix'])
            ->name('matrix.save');

        // EXPORTER EXCEL
        // route('process.core.raci.export-excel')
        // GET /process/evaluations/raci/export-excel
        Route::get('export-excel', [ProcessIdeaController::class, 'exportExcel'])
            ->name('export-excel');
    });

use App\Http\Controllers\Process\Evaluations\ProcessAMDECController;
  
Route::middleware(['auth', 'verified'])
    ->prefix('process/evaluations/amdec')
    ->name('amdec.')
    ->group(function () {
        
        // 📄 PAGE PRINCIPALE — Affiche la vue Inertia avec les tabs
        Route::get('/', [ProcessAMDECController::class, 'index'])
            ->name('index');

        // 📊 LOAD DATA — Charger les enregistrements PHASE 1/2/3
        // Query params: session_id, process_id
        Route::get('load', [ProcessAMDECController::class, 'loadData'])
            ->name('load');

        // 💾 SAVE RECORD — Dispatcher intelligent
        // Dispatche vers savePhase1 / savePhase2 / savePhase3 selon la phase
        Route::post('save', [ProcessAMDECController::class, 'saveRecord'])
            ->name('save');

        // 🗑️ DELETE RECORD — Soft delete un enregistrement
        Route::delete('delete/{id}', [ProcessAMDECController::class, 'deleteRecord'])
            ->name('delete');

        // 📥 EXPORT EXCEL — Exporter tous les enregistrements
        Route::get('export-excel', [ProcessAMDECController::class, 'exportExcel'])
            ->name('export-excel');

        // ═════════════════════════════════════════════════════════════════════
        // 🌐 RÉFÉRENTIELS API — Gravité, Fréquence, Détectabilité, Standards
        // ═════════════════════════════════════════════════════════════════════

        // 🔴 GRAVITÉ (1-5)
        Route::get('gravities', [ProcessAMDECController::class, 'getGravities'])
            ->name('gravities');

        // 📊 FRÉQUENCE (1-5)
        Route::get('frequencies', [ProcessAMDECController::class, 'getFrequencies'])
            ->name('frequencies');

        // 🔍 DÉTECTABILITÉ (1-5)
        Route::get('detectabilities', [ProcessAMDECController::class, 'getDetectabilities'])
            ->name('detectabilities');

        // 📋 NORMES/STANDARDS (Vert/Bleu/Jaune/Orange/Rouge)
        Route::get('standards', [ProcessAMDECController::class, 'getStandards'])
            ->name('standards');

        // 🌐 FONCTIONS (pour responsables)
        Route::get('functions', [ProcessAMDECController::class, 'getFunctions'])
            ->name('functions');
    });



 use App\Http\Controllers\Process\ModelingBpmnController;

// Routes BPMN
Route::prefix('modeling/bpmn')->group(function () {
    Route::get('/', [ModelingBpmnController::class, 'index'])->name('modeling.bpmn.index');
    Route::get('/create', [ModelingBpmnController::class, 'create'])->name('modeling.bpmn.create');
    Route::post('/', [ModelingBpmnController::class, 'store'])->name('modeling.bpmn.store');
    Route::get('/{processId}/edit', [ModelingBpmnController::class, 'edit'])->name('modeling.bpmn.edit');
    Route::put('/{processId}', [ModelingBpmnController::class, 'update'])->name('modeling.bpmn.update');
    Route::delete('/{processId}', [ModelingBpmnController::class, 'destroy'])->name('modeling.bpmn.destroy');
    
    // Routes spécifiques BPMN
    Route::post('/diagram/{diagramId}/auto-save', [ModelingBpmnController::class, 'autoSave'])->name('bpmn.auto-save');
    Route::post('/diagram/{diagramId}/manual-save', [ModelingBpmnController::class, 'manualSave'])->name('bpmn.manual-save');
    Route::get('/diagram/{diagramId}/export', [ModelingBpmnController::class, 'export'])->name('bpmn.export');
    Route::get('/diagram/{diagramId}/versions', [ModelingBpmnController::class, 'versions'])->name('bpmn.versions');
    Route::get('/process/{processId}/activities', [ModelingBpmnController::class, 'getActivities'])->name('bpmn.activities');
});