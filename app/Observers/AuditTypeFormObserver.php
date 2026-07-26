<?php

namespace App\Observers;

use App\Models\AuditTypeForm;
use App\Observers\Concerns\DispatchesTenantSync;

class AuditTypeFormObserver
{
    use DispatchesTenantSync;

    public function saved(AuditTypeForm $form): void
    {
        // Ajout/modif d'une phase ou sous-phase de formulaire ⇒ propagation
        // vers mission_phases de chaque tenant.
        $this->dispatchSyncToAllTenants();
    }

    public function deleted(AuditTypeForm $form): void
    {
        // Idem AuditTypeObserver : pas de suppression en cascade automatique
        // côté tenant (des mission_phase_assignments réels peuvent exister).
    }
}
