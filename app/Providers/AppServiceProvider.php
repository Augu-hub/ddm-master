<?php

namespace App\Providers;

use App\Support\TenantManager;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Models\Param\Entite;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Enregistrer le TenantManager comme singleton
        $this->app->singleton(TenantManager::class, function () {
            return new TenantManager();
        });
    }

  public function boot()
    {
        // ⛔️ SUPPRIMEZ ou COMENTEZ ce code s'il existe
        /*
        Inertia::share([
            'entities' => function () {
                // ANCIEN CODE - À SUPPRIMER
            },
        ]);
        */
        
        // ✅ OU remplacez-le par ceci :
        Inertia::share([
          
        ]);
    }
}