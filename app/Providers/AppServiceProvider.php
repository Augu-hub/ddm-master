<?php

namespace App\Providers;

use App\Models\AuditType;
use App\Models\AuditTypeForm;
use App\Models\MissionType;
use App\Models\Tenant;
use App\Observers\AuditTypeFormObserver;
use App\Observers\AuditTypeObserver;
use App\Observers\MissionTypeObserver;
use App\Observers\TenantObserver;
use App\Support\TenantManager;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Enregistrer le TenantManager comme singleton
        $this->app->singleton(TenantManager::class, function () {
            return new TenantManager();
        });
    }

    public function boot(): void
    {
        // ══════════════════════════════════════════════════════════════
        //  SYNCHRO AUTOMATIQUE ddmparam ──► bases tenant
        //  Toute écriture Eloquent sur le référentiel central déclenche
        //  un SyncTenantReferenceDataJob par tenant (via la queue).
        //  ⚠️ Les écritures en DB::table() brut (ex: AuditTypeFormsController)
        //     ne passent pas par les observers : le contrôleur dispatche
        //     lui-même la synchro (trait DispatchesTenantSync).
        // ══════════════════════════════════════════════════════════════
        AuditType::observe(AuditTypeObserver::class);
        AuditTypeForm::observe(AuditTypeFormObserver::class);
        MissionType::observe(MissionTypeObserver::class);
        Tenant::observe(TenantObserver::class);

        // Extension du disque tenant_uploads (racine isolée par tenant)
        Storage::extend('tenant_uploads', function ($app, $config) {
            $tenantId = null;

            // Via le TenantManager (source de vérité de la session tenant)
            if ($app->bound(TenantManager::class)) {
                $tenantId = $app->make(TenantManager::class)->currentId();
            }

            // Repli : session directe, sinon espace "global"
            $tenantId = $tenantId ?: session('tenant_id', 'global');

            $root = storage_path("app/tenant_uploads/{$tenantId}");
            if (!is_dir($root)) {
                mkdir($root, 0755, true);
            }

            $adapter = new LocalFilesystemAdapter($root);

            return new FilesystemAdapter(
                new Flysystem($adapter, $config),
                $adapter,
                $config
            );
        });

        // Partage Inertia (si nécessaire)
        Inertia::share([
            // vos variables partagées
        ]);
    }
}
