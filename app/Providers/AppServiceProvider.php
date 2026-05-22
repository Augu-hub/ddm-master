<?php

namespace App\Providers;

use App\Support\TenantManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
        // Extension du disque tenant_uploads
        Storage::extend('tenant_uploads', function ($app, $config) {
            // Récupération de l'ID du tenant (à adapter selon votre implémentation)
            $tenantId = null;
            
            // Méthode 1: si vous avez une classe Tenant avec une méthode current()
            if (function_exists('tenant') && method_exists(tenant(), 'id')) {
                $tenantId = tenant()->id;
            }
            // Méthode 2: via le TenantManager (si vous l'avez)
            elseif (app()->bound(TenantManager::class)) {
                $tenantId = app(TenantManager::class)->getTenantId();
            }
            // Méthode 3: via la session
            else {
                $tenantId = session('tenant_id', 'global');
            }
            
            $root = storage_path("app/tenant_uploads/{$tenantId}");
            if (!is_dir($root)) {
                mkdir($root, 0755, true);
            }
            
            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \Illuminate\Filesystem\Filesystem(),
                $root,
                $config
            );
        });
        
        // Partage Inertia (si nécessaire)
        Inertia::share([
            // vos variables partagées
        ]);
    }
}