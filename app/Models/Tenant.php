<?php

namespace App\Models;

use App\Models\Master\Tenant as MasterTenant;

/**
 * Alias de compatibilité — la définition canonique vit dans
 * App\Models\Master\Tenant (table centrale `ddmparam`.`tenants`).
 *
 * Tout le système de synchro (TenantReferenceSyncService, Jobs, Observers,
 * commandes artisan, middleware ResolveTenant) importe `App\Models\Tenant` :
 * cette classe garantit qu'ils pointent tous sur le MÊME modèle, sans
 * dupliquer la définition.
 *
 * Observé par TenantObserver (cf. AppServiceProvider::boot()) : la création
 * d'un tenant déclenche automatiquement la synchro initiale du référentiel.
 */
class Tenant extends MasterTenant
{
    //
}
