<?php

namespace App\Observers;

use App\Jobs\SyncTenantReferenceDataJob;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        // ⚠️ ADAPTER ICI si l'application dispose déjà d'une étape de
        // provisioning (migration de la base tenant) déclenchée ailleurs
        // (ex: TenantService::provision(), commande `tenants:migrate`, etc.).
        // La synchro référentiel doit s'exécuter APRÈS que les tables
        // mission_types / mission_phases existent bien côté tenant.
        //
        // Si aucune étape de migration n'est encore automatisée, décommenter :
        //
        // Artisan::call('tenants:migrate', ['--tenant' => $tenant->code]);

        Log::info("🆕 Nouveau tenant créé : {$tenant->code} — synchro référentiel initiale mise en file.");

        SyncTenantReferenceDataJob::dispatch($tenant->id);
    }
}
