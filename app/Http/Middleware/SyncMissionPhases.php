<?php

namespace App\Http\Middleware;

use App\Services\Audit\PhaseSyncService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Provision automatique des phases d'une mission depuis ddmparam.
 *
 * CORRECTIONS : import DB manquant (fatal à la première exécution) et appel
 * de l'ancien PhaseSyncService ancien-schéma. Délègue désormais à
 * PhaseSyncService::ensureMissionAssignments() (nouveau schéma, idempotent,
 * cache 5 min intégré au service).
 *
 * Alias : 'sync.phases' (bootstrap/app.php) — applicable à toute route
 * portant un mission_id (param de route, query ou body). L'appel est aussi
 * fait directement par BuildsMissionMenu / AuditorMissionsController, ce
 * middleware reste utile pour les routes hors de ces chemins.
 */
class SyncMissionPhases
{
    public function handle($request, Closure $next)
    {
        $missionId = $request->route('mission_id')
            ?? $request->route('missionId')
            ?? $request->query('mission_id')
            ?? $request->input('mission_id');

        if ($missionId && is_numeric($missionId)) {
            try {
                PhaseSyncService::ensureMissionAssignments((int) $missionId);
            } catch (\Throwable $e) {
                Log::error('[SyncMissionPhases] ' . $e->getMessage());
                // Ne jamais bloquer la requête
            }
        }

        return $next($request);
    }
}
