<?php

namespace App\Observers\Concerns;

use App\Jobs\SyncTenantReferenceDataJob;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * À utiliser dans les Observers des modèles "référentiel central" (ddmparam) :
 * AuditType, MissionType, AuditTypeForm — ET dans les contrôleurs qui écrivent
 * le référentiel en DB::table() brut (les events Eloquent n'y existent pas),
 * ex : Admin\AuditTypeFormsController.
 *
 * Dispatche un Job de synchro PAR tenant à chaque écriture, pour que la
 * modification centrale se propage automatiquement, sans bloquer la requête
 * HTTP en cours (le tout part sur la queue). Grâce à ShouldBeUnique sur le
 * job, les dispatches répétés d'une même rafale de modifications ne créent
 * qu'un seul job par tenant.
 */
trait DispatchesTenantSync
{
    protected function dispatchSyncToAllTenants(): void
    {
        try {
            Tenant::query()->pluck('id')->each(function (int $tenantId) {
                SyncTenantReferenceDataJob::dispatch($tenantId);
            });
        } catch (\Throwable $e) {
            // Une queue indisponible ne doit jamais faire échouer l'écriture
            // métier déjà réalisée — on trace, l'admin pourra relancer :
            // php artisan tenants:sync-reference
            Log::error('DispatchesTenantSync: mise en file de la synchro impossible : ' . $e->getMessage());
        }
    }
}
