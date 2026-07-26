<?php

namespace App\Observers;

use App\Models\AuditType;
use App\Observers\Concerns\DispatchesTenantSync;

class AuditTypeObserver
{
    use DispatchesTenantSync;

    public function saved(AuditType $auditType): void
    {
        // Création OU modification (label, couleur, icône, activation...) :
        // on repropage vers tous les tenants pour corriger les champs
        // dénormalisés dans leur mission_types.
        $this->dispatchSyncToAllTenants();
    }

    public function deleted(AuditType $auditType): void
    {
        // On ne supprime jamais les mission_types tenant correspondants
        // automatiquement (données historiques liées à des missions réelles).
        // On se contente de désactiver côté tenant lors du prochain resync
        // si besoin — géré manuellement via la commande de diagnostic.
    }
}
