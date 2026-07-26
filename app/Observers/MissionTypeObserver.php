<?php

namespace App\Observers;

use App\Models\MissionType;
use App\Observers\Concerns\DispatchesTenantSync;

class MissionTypeObserver
{
    use DispatchesTenantSync;

    public function saved(MissionType $missionType): void
    {
        $this->dispatchSyncToAllTenants();
    }

    /**
     * ⚠️ Cas particulier : les relations Many-to-Many (mission_type_audit_types,
     * mission_type_form_audit_types) ne déclenchent PAS les events Eloquent
     * "saved" lors d'un attach()/sync()/detach(). Si ces pivots sont modifiés
     * ailleurs dans le code (ex: $missionType->auditTypes()->sync([...])),
     * il faut déclencher la synchro manuellement juste après, par exemple :
     *
     *   $missionType->auditTypes()->sync($request->audit_type_ids);
     *   app(\App\Services\Tenant\TenantReferenceSyncService::class)->syncAll();
     *
     * ou dispatcher les jobs comme le fait ce trait.
     */
}
