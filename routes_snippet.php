<?php
// À ajouter dans routes/web.php, dans le groupe déjà existant pour audit.core.missions

use App\Http\Controllers\Risk\MissionController;

Route::patch('/m/audit.core/missions/{missionId}/programmation/{programmationId}', [MissionController::class, 'updateProgrammation'])
    ->name('audit.core.missions.programmation.update');

Route::post('/m/audit.core/missions/{missionId}/programmation/{programmationId}/confirm', [MissionController::class, 'confirmProgrammation'])
    ->name('audit.core.missions.programmation.confirm');
