<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Audit\PhaseSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncMissionPhases
{
    public function handle($request, Closure $next)
    {
        // Ne synchroniser que pour les routes d'audit avec mission_id
        $missionId = $request->route('mission_id')
            ?? $request->query('mission_id')
            ?? $request->input('mission_id');

        if ($missionId) {
            try {
                // Récupérer le mission_type_id de la mission
                $missionTypeId = DB::table('mission_programmation as mp')
                    ->leftJoin('missions as m', 'mp.mission_id', '=', 'm.id')
                    ->where('mp.id', $missionId)
                    ->value('m.mission_type_id');

                if ($missionTypeId) {
                    // Cache pour éviter de synchroniser à chaque requête
                    $cacheKey = "phase_sync_{$missionTypeId}";
                    $lastSync = Cache::get($cacheKey);

                    if (!$lastSync || $lastSync < now()->subMinutes(5)->timestamp) {
                        PhaseSyncService::syncForMissionType($missionTypeId);
                        Cache::put($cacheKey, now()->timestamp, 300); // 5 minutes
                    }
                }
            } catch (\Exception $e) {
                Log::error('[SyncMiddleware] ' . $e->getMessage());
                // Ne pas bloquer la requête
            }
        }

        return $next($request);
    }
}