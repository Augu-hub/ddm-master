<?php

namespace App\Providers;

use App\Models\Master\Module;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Si les routes sont cachées, pas besoin de remonter dynamiquement
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->routes(function () {
            // Routes de base (web + api)
            Route::middleware('web')->group(base_path('routes/web.php'));
            Route::prefix('api')->middleware('api')->group(base_path('routes/api.php'));

            // Routes des modules locaux (built-in) - toujours actifs
            $this->mountBuiltInModuleRoutes();

            // Routes des modules actifs depuis la base de données
            $this->mountActiveModuleRoutes();
        });
    }

    /**
     * Monte les modules "built-in" locaux toujours actifs
     * depuis le dossier routes/modules/
     */
    protected function mountBuiltInModuleRoutes(): void
    {
        $modulesPath = base_path('routes/modules');
        
        if (!File::exists($modulesPath)) {
            Log::info('Built-in modules: dossier routes/modules absent → skip.');
            return;
        }

        // Trouver tous les fichiers .php dans routes/modules/
        $moduleFiles = File::glob($modulesPath . '/*.php');
        
        if (empty($moduleFiles)) {
            Log::info('Built-in modules: aucun fichier de module trouvé dans routes/modules/');
            return;
        }

        foreach ($moduleFiles as $filePath) {
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            
            // Format attendu : {module-code}.php (ex: param-projects.php)
            // Code du module : param-projects (utilisé pour le namespace)
            $moduleCode = $filename;
            
            // Convertir kebab-case en notation pointée pour la génération des noms de routes
            // param-projects → param.projects
            $moduleNamespace = str_replace('-', '.', $moduleCode);
            
            // URL prefix : /m/{module-code}
            $prefix = 'm/' . $moduleCode;

            try {
                Route::middleware(['web', 'auth', 'verified', 'module:' . $moduleNamespace])
                    ->prefix($prefix)
                    ->as($moduleNamespace . '.')
                    ->group($filePath);

                Log::info("Built-in Module [$moduleNamespace]: web routes montées ($prefix) ← routes/modules/$filename.php");
            } catch (\Throwable $e) {
                Log::error("Built-in Module [$moduleNamespace]: erreur lors du chargement → {$e->getMessage()}");
            }
        }
    }

    /**
     * Monte les modules actifs depuis la base de données
     */
    protected function mountActiveModuleRoutes(): void
    {
        // 1) La table "modules" peut ne pas exister au premier boot
        try {
            if (! Schema::hasTable('modules')) {
                Log::notice('Dynamic Modules: table "modules" absente (migrations non exécutées) → skip.');
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('Dynamic Modules: Schema non prêt → skip. '.$e->getMessage());
            return;
        }

        // 2) Charger les modules actifs
        try {
            $modules = Module::query()
                ->select(['code','route_prefix','route_web_file','route_api_file','is_active'])
                ->where('is_active', true)
                ->orderBy('sort')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Dynamic Modules: lecture des modules impossible → skip. '.$e->getMessage());
            return;
        }

        if ($modules->isEmpty()) {
            Log::info('Dynamic Modules: aucun module actif trouvé.');
            return;
        }

        // 3) Choisir le guard API (sanctum si dispo, sinon auth)
        $apiAuth = data_get(config('auth.guards'), 'sanctum') ? 'auth:sanctum' : 'auth';

        foreach ($modules as $m) {
            $code   = trim((string) $m->code);
            if ($code === '') {
                continue;
            }

            // prefix "m" par défaut, sans / de part et d'autre
            $prefix = trim((string) ($m->route_prefix ?: 'm'), " \t\n\r\0\x0B/");

            // Sanitize chemins (éviter leading slash/antislash)
            $webRel = $m->route_web_file ? ltrim((string) $m->route_web_file, "/\\") : null;
            $apiRel = $m->route_api_file ? ltrim((string) $m->route_api_file, "/\\") : null;

            // ===== WEB =====
            if ($webRel) {
                $webPath = base_path($webRel);
                if (File::exists($webPath)) {
                    Route::middleware(['web','auth','verified','module:'.$code])
                        ->prefix($prefix.'/'.$code) // /m/module-code
                        ->as($code.'.')             // module.code.*
                        ->group($webPath);

                    Log::info("Dynamic Module [$code]: web routes montées ($prefix/$code) ← $webRel");
                } else {
                    Log::warning("Dynamic Module [$code]: route_web_file introuvable: {$webPath}");
                }
            }

            // ===== API =====
            if ($apiRel) {
                $apiPath = base_path($apiRel);
                if (File::exists($apiPath)) {
                    Route::middleware(['api', $apiAuth, 'module:'.$code])
                        ->prefix('api/'.$prefix.'/'.$code) // /api/m/module-code
                        ->as('api.'.$code.'.')             // api.module.code.*
                        ->group($apiPath);

                    Log::info("Dynamic Module [$code]: api routes montées (api/$prefix/$code) [guard: ".($apiAuth)."] ← $apiRel");
                } else {
                    Log::warning("Dynamic Module [$code]: route_api_file introuvable: {$apiPath}");
                }
            }
        }
    }
}