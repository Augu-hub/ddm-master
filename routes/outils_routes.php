<?php

/*
|─────────────────────────────────────────────────────────────────────────────
|  ROUTES OUTILS IFACI I - XV + FICHE-TEST
|  Fichier : routes/outils.php
|─────────────────────────────────────────────────────────────────────────────
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auditor\FicheTestController;
use App\Http\Controllers\Auditor\Outils\OutilEntretienController;
use App\Http\Controllers\Auditor\Outils\OutilAnalyseTachesController;
use App\Http\Controllers\Auditor\Outils\OutilDiagrammeFluxController;
use App\Http\Controllers\Auditor\Outils\OutilApprocheProcessusController;
use App\Http\Controllers\Auditor\Outils\OutilTestCheminementController;
use App\Http\Controllers\Auditor\Outils\OutilHierarchisationRisquesController;
use App\Http\Controllers\Auditor\Outils\OutilReferentielAuditController;
use App\Http\Controllers\Auditor\Outils\OutilCauseEffetController;
use App\Http\Controllers\Auditor\Outils\OutilQCIController;
use App\Http\Controllers\Auditor\Outils\OutilBrainstormingController;
use App\Http\Controllers\Auditor\Outils\OutilPisteAuditController;
use App\Http\Controllers\Auditor\Outils\OutilCircularisationController;
use App\Http\Controllers\Auditor\Outils\OutilAuditAnalytiqueController;
use App\Http\Controllers\Auditor\Outils\OutilObservationController;
use App\Http\Controllers\Auditor\Outils\OutilEchantillonnageController;

// ─────────────────────────────────────────────────────────────────────
// ROUTES PUBLIQUES (sans auth) — confirmation via token email
// ─────────────────────────────────────────────────────────────────────
Route::get('/audit/confirmation/{token}',  [FicheTestController::class, 'confirmationPublique'])->name('audit.confirmation.publique');
Route::post('/audit/confirmation/{token}', [FicheTestController::class, 'confirmationSoumettre'])->name('audit.confirmation.soumettre');

Route::get('/public/entretien/validate/{token}', [OutilEntretienController::class, 'validateByToken'])
    ->name('public.outil-entretien.validate');

Route::get('/public/outil-hierarchisation-risques/validate/{token}', [OutilHierarchisationRisquesController::class, 'validateByToken'])
    ->name('public.outil-hierarchisation-risques.validate');

// ─────────────────────────────────────────────────────────────────────
// GROUPE AUTH
// ─────────────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // ═══════════════════════════════════════════════════════════
    // TEST EMAIL (route indépendante)
    // ═══════════════════════════════════════════════════════════
    Route::post('/test-email', function (Illuminate\Http\Request $request) {
        $email = $request->input('email', config('mail.from.address'));
        try {
            Illuminate\Support\Facades\Mail::raw(
                "Ceci est un email de test depuis DIADDEM.\nDate : " . now(),
                function ($message) use ($email) {
                    $message->to($email)
                            ->subject("Test SMTP - " . now());
                }
            );
            return response()->json(['success' => true, 'message' => "Email envoyé à $email"]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Test email failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    })->name('test.email');

    // ═══════════════════════════════════════════════════════════
    // I — ENTRETIEN
    // ═══════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════
// I — ENTRETIEN
// ═══════════════════════════════════════════════════════════

// Préfixe CANONIQUE (ac) – avec noms de route
Route::prefix('auditor/ac/outil-entretien')->name('auditor.ac.outil-entretien.')->group(function () {
    Route::get('/',        [OutilEntretienController::class, 'index'])->name('index');
    Route::get('/create',  [OutilEntretienController::class, 'create'])->name('create');
    Route::post('/',       [OutilEntretienController::class, 'store'])->name('store');
    Route::get('/{id}',    [OutilEntretienController::class, 'edit'])->name('edit');
    Route::match(['PUT','POST'], '/{id}', [OutilEntretienController::class, 'update'])->name('update');
    Route::delete('/{id}', [OutilEntretienController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/soumettre',            [OutilEntretienController::class, 'soumettre'])->name('soumettre');
    Route::post('/{id}/valider',              [OutilEntretienController::class, 'valider'])->name('valider');
    Route::post('/{id}/ia',                   [OutilEntretienController::class, 'ia'])->name('ia');
    Route::post('/{id}/send-validation-email',[OutilEntretienController::class, 'sendValidationEmail'])->name('send-validation-email');
    // Documents
    Route::post('/{id}/documents',                           [OutilEntretienController::class, 'uploadDocument'])->name('upload-doc');
    Route::get('/{id}/documents',                            [OutilEntretienController::class, 'getDocuments'])->name('documents');
    Route::delete('/{entretienId}/documents/{docId}',        [OutilEntretienController::class, 'deleteDocument'])->name('delete-doc');
    Route::post('/{entretienId}/documents/{docId}/validate', [OutilEntretienController::class, 'validateDocument'])->name('validate-doc');
    Route::get('/{entretienId}/documents/{docId}/download',  [OutilEntretienController::class, 'downloadDocument'])->name('download-doc');
    Route::get('/{entretienId}/documents/{docId}/preview',   [OutilEntretienController::class, 'previewDocument'])->name('preview-doc');
    Route::get('/{entretienId}/documents/{docId}/edit',      [OutilEntretienController::class, 'editDocument'])->name('edit-doc');
    Route::match(['PUT','POST'], '/{entretienId}/documents/{docId}/save', [OutilEntretienController::class, 'saveDocument'])->name('save-doc');
    Route::get('/{entretienId}/documents/{docId}/load-excel',[OutilEntretienController::class, 'loadExcelDocument'])->name('load-excel-doc');
    Route::post('/{entretienId}/documents/{docId}/save-excel',[OutilEntretienController::class,'saveExcelDocument'])->name('save-excel-doc');
});

// Préfixe ALIAS (utilisé par FicheTest)
Route::prefix('auditor/outils/entretien')->group(function () {
    Route::get('/',          [OutilEntretienController::class, 'index']);
    Route::post('/',         [OutilEntretienController::class, 'store']);
    Route::get('/{id}/edit', [OutilEntretienController::class, 'edit']);
    Route::match(['PUT','POST'], '/{id}', [OutilEntretienController::class, 'update']);
    Route::delete('/{id}',   [OutilEntretienController::class, 'destroy']);
    Route::post('/{id}/soumettre',        [OutilEntretienController::class, 'soumettre']);
    Route::post('/{id}/valider',          [OutilEntretienController::class, 'valider']);
    Route::post('/{id}/ia',               [OutilEntretienController::class, 'ia']);
    Route::post('/{id}/send-validation-email', [OutilEntretienController::class, 'sendValidationEmail']);
    // Documents
    Route::post('/{id}/documents',                 [OutilEntretienController::class, 'uploadDocument']);
    Route::get('/{id}/documents',                  [OutilEntretienController::class, 'getDocuments']);
    Route::delete('/{entretienId}/documents/{docId}', [OutilEntretienController::class, 'deleteDocument']);
    Route::post('/{entretienId}/documents/{docId}/validate', [OutilEntretienController::class, 'validateDocument']);
    Route::get('/{entretienId}/documents/{docId}/download',  [OutilEntretienController::class, 'downloadDocument']);
    Route::get('/{entretienId}/documents/{docId}/preview',   [OutilEntretienController::class, 'previewDocument']);
    Route::get('/{entretienId}/documents/{docId}/edit',      [OutilEntretienController::class, 'editDocument']);
    Route::match(['PUT','POST'], '/{entretienId}/documents/{docId}/save', [OutilEntretienController::class, 'saveDocument']);
    Route::get('/{entretienId}/documents/{docId}/load-excel', [OutilEntretienController::class, 'loadExcelDocument']);
    Route::post('/{entretienId}/documents/{docId}/save-excel',[OutilEntretienController::class, 'saveExcelDocument']);
});

    // ═══════════════════════════════════════════════════════════
    // VI — HIÉRARCHISATION DES RISQUES
    // ═══════════════════════════════════════════════════════════

    // Préfixe CANONIQUE (ac)
    Route::prefix('auditor/ac/outil-hierarchisation-risques')->name('auditor.ac.outil-hierarchisation-risques.')->group(function () {
        Route::get('/',    [OutilHierarchisationRisquesController::class, 'index'])->name('index');
        Route::post('/',   [OutilHierarchisationRisquesController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [OutilHierarchisationRisquesController::class, 'edit'])->name('edit');
        Route::match(['PUT','POST'], '/{id}', [OutilHierarchisationRisquesController::class, 'update'])->name('update');
        Route::delete('/{id}',               [OutilHierarchisationRisquesController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/soumettre',        [OutilHierarchisationRisquesController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',          [OutilHierarchisationRisquesController::class, 'valider'])->name('valider');
        Route::post('/{id}/ia',               [OutilHierarchisationRisquesController::class, 'ia'])->name('ia');
        Route::post('/{id}/send-validation-email', [OutilHierarchisationRisquesController::class, 'sendValidationEmail'])->name('send-validation-email');
        Route::post('/{id}/upload-doc',                          [OutilHierarchisationRisquesController::class, 'uploadDocument'])->name('upload-doc');
        Route::get('/{ficheId}/download-doc/{docId}',            [OutilHierarchisationRisquesController::class, 'downloadDocument'])->name('download-doc');
        Route::post('/{ficheId}/validate-doc/{docId}',           [OutilHierarchisationRisquesController::class, 'validateDocument'])->name('validate-doc');
        Route::delete('/{ficheId}/delete-doc/{docId}',           [OutilHierarchisationRisquesController::class, 'deleteDocument'])->name('delete-doc');
        Route::get('/{ficheId}/edit-doc/{docId}',                [OutilHierarchisationRisquesController::class, 'editDocument'])->name('edit-doc');
        Route::post('/{ficheId}/save-doc/{docId}',               [OutilHierarchisationRisquesController::class, 'saveDocument'])->name('save-doc');
        Route::get('/{ficheId}/load-excel-doc/{docId}',          [OutilHierarchisationRisquesController::class, 'loadExcelDocument'])->name('load-excel-doc');
        Route::post('/{ficheId}/save-excel-doc/{docId}',         [OutilHierarchisationRisquesController::class, 'saveExcelDocument'])->name('save-excel-doc');
    });

    // Préfixe ALIAS (utilisé par FicheTest)
    Route::prefix('auditor/outils/hierarchisation-risques')->group(function () {
        Route::get('/',          [OutilHierarchisationRisquesController::class, 'index']);
        Route::post('/',         [OutilHierarchisationRisquesController::class, 'store']);
        Route::get('/{id}/edit', [OutilHierarchisationRisquesController::class, 'edit']);
        Route::match(['PUT','POST'], '/{id}', [OutilHierarchisationRisquesController::class, 'update']);
        Route::delete('/{id}',               [OutilHierarchisationRisquesController::class, 'destroy']);
        Route::post('/{id}/soumettre',        [OutilHierarchisationRisquesController::class, 'soumettre']);
        Route::post('/{id}/valider',          [OutilHierarchisationRisquesController::class, 'valider']);
        Route::post('/{id}/ia',               [OutilHierarchisationRisquesController::class, 'ia']);
        Route::post('/{id}/upload-doc',                 [OutilHierarchisationRisquesController::class, 'uploadDocument']);
        Route::get('/{ficheId}/download-doc/{docId}',   [OutilHierarchisationRisquesController::class, 'downloadDocument']);
        Route::post('/{ficheId}/validate-doc/{docId}',  [OutilHierarchisationRisquesController::class, 'validateDocument']);
        Route::delete('/{ficheId}/delete-doc/{docId}',  [OutilHierarchisationRisquesController::class, 'deleteDocument']);
        Route::get('/{ficheId}/edit-doc/{docId}',       [OutilHierarchisationRisquesController::class, 'editDocument']);
        Route::post('/{ficheId}/save-doc/{docId}',      [OutilHierarchisationRisquesController::class, 'saveDocument']);
        Route::get('/{ficheId}/load-excel-doc/{docId}', [OutilHierarchisationRisquesController::class, 'loadExcelDocument']);
        Route::post('/{ficheId}/save-excel-doc/{docId}',[OutilHierarchisationRisquesController::class, 'saveExcelDocument']);
        Route::post('/{id}/send-validation-email', [OutilHierarchisationRisquesController::class, 'sendValidationEmail']);
    });

    // ═══════════════════════════════════════════════════════════
    // II — ANALYSE TÂCHES
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/analyse-taches')->name('auditor.ac.outil-analyse-taches.')->group(function () {
        Route::get('/',          [OutilAnalyseTachesController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilAnalyseTachesController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilAnalyseTachesController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilAnalyseTachesController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilAnalyseTachesController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilAnalyseTachesController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilAnalyseTachesController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilAnalyseTachesController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // III — DIAGRAMME DE FLUX
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/diagramme-flux')->name('auditor.ac.outil-diagramme-flux.')->group(function () {
        Route::get('/',          [OutilDiagrammeFluxController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilDiagrammeFluxController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilDiagrammeFluxController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilDiagrammeFluxController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilDiagrammeFluxController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilDiagrammeFluxController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilDiagrammeFluxController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilDiagrammeFluxController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // IV — APPROCHE PROCESSUS
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/approche-processus')->name('auditor.ac.outil-approche-processus.')->group(function () {
        Route::get('/',          [OutilApprocheProcessusController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilApprocheProcessusController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilApprocheProcessusController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilApprocheProcessusController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilApprocheProcessusController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilApprocheProcessusController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilApprocheProcessusController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilApprocheProcessusController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // V — TEST DE CHEMINEMENT
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/test-cheminement')->name('auditor.ac.outil-test-cheminement.')->group(function () {
        Route::get('/',          [OutilTestCheminementController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilTestCheminementController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilTestCheminementController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilTestCheminementController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilTestCheminementController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilTestCheminementController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilTestCheminementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilTestCheminementController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // VII — RÉFÉRENTIEL D'AUDIT
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/referentiel-audit')->name('auditor.outils.referentiel-audit.')->group(function () {
        Route::get('/',          [OutilReferentielAuditController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilReferentielAuditController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilReferentielAuditController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilReferentielAuditController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilReferentielAuditController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilReferentielAuditController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilReferentielAuditController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilReferentielAuditController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // VIII — CAUSE-EFFET
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/cause-effet')->name('auditor.outils.cause-effet.')->group(function () {
        Route::get('/',          [OutilCauseEffetController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilCauseEffetController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilCauseEffetController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilCauseEffetController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre',    [OutilCauseEffetController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',      [OutilCauseEffetController::class, 'valider'])->name('valider');
        Route::delete('/{id}',            [OutilCauseEffetController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',           [OutilCauseEffetController::class, 'ia'])->name('ia');
        Route::post('/{id}/import-causes',[OutilCauseEffetController::class, 'importCauses'])->name('import-causes');
    });

    // ═══════════════════════════════════════════════════════════
    // IX — QCI
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/qci')->name('auditor.outils.qci.')->group(function () {
        Route::get('/',          [OutilQCIController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilQCIController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilQCIController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilQCIController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilQCIController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilQCIController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilQCIController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilQCIController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // X — BRAINSTORMING
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/brainstorming')->name('auditor.outils.brainstorming.')->group(function () {
        Route::get('/',          [OutilBrainstormingController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilBrainstormingController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilBrainstormingController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilBrainstormingController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilBrainstormingController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilBrainstormingController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilBrainstormingController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilBrainstormingController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // XI — PISTE D'AUDIT
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/piste-audit')->name('auditor.outils.piste-audit.')->group(function () {
        Route::get('/',          [OutilPisteAuditController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilPisteAuditController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilPisteAuditController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilPisteAuditController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilPisteAuditController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilPisteAuditController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilPisteAuditController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilPisteAuditController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // XII — CIRCULARISATION
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/circularisation')->name('auditor.outils.circularisation.')->group(function () {
        Route::get('/',          [OutilCircularisationController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilCircularisationController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilCircularisationController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilCircularisationController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilCircularisationController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilCircularisationController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilCircularisationController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilCircularisationController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // XIII — AUDIT ANALYTIQUE
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/audit-analytique')->name('auditor.outils.audit-analytique.')->group(function () {
        Route::get('/',          [OutilAuditAnalytiqueController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilAuditAnalytiqueController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilAuditAnalytiqueController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilAuditAnalytiqueController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilAuditAnalytiqueController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilAuditAnalytiqueController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilAuditAnalytiqueController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilAuditAnalytiqueController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // XIV — OBSERVATION
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/observation')->name('auditor.outils.observation.')->group(function () {
        Route::get('/',          [OutilObservationController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilObservationController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilObservationController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilObservationController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilObservationController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilObservationController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilObservationController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilObservationController::class, 'ia'])->name('ia');
    });

    // ═══════════════════════════════════════════════════════════
    // XV — ÉCHANTILLONNAGE
    // ═══════════════════════════════════════════════════════════
    Route::prefix('auditor/outils/echantillonnage')->name('auditor.outils.echantillonnage.')->group(function () {
        Route::get('/',          [OutilEchantillonnageController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [OutilEchantillonnageController::class, 'edit'])->name('edit');
        Route::post('/',         [OutilEchantillonnageController::class, 'store'])->name('store');
        Route::put('/{id}',      [OutilEchantillonnageController::class, 'update'])->name('update');
        Route::post('/{id}/soumettre', [OutilEchantillonnageController::class, 'soumettre'])->name('soumettre');
        Route::post('/{id}/valider',   [OutilEchantillonnageController::class, 'valider'])->name('valider');
        Route::delete('/{id}',         [OutilEchantillonnageController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ia',        [OutilEchantillonnageController::class, 'ia'])->name('ia');
    });

}); // end auth group