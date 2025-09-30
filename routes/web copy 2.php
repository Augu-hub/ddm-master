<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\Param\ProjetController;
use App\Http\Controllers\Param\MacroprocessController;
use App\Http\Controllers\Param\ParamStaticController;
use App\Http\Controllers\Param\EntityController;
use App\Http\Controllers\Param\ProcessusController;
use App\Http\Controllers\Param\ActiviteController;
use App\Http\Controllers\Param\FonctionController;
use App\Http\Controllers\Param\AttributionController;
use App\Http\Controllers\Param\AttributionFonctionController;

use Inertia\Inertia;

require __DIR__ . '/auth.php';

// =============================================================================
// ROUTES PUBLIQUES (sans authentification)
// =============================================================================

// Page d'accueil
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('welcome');

// Routes d'auth statiques (pages publiques)
Route::get('/auth/login', fn() => Inertia::render('Auth/Login'))->name('login');
Route::get('/auth/register', fn() => Inertia::render('Auth/Register'))->name('register');
Route::get('/auth/forgot-password', fn() => Inertia::render('Auth/ForgotPassword'))->name('password.request');
Route::get('/auth/reset-password/{token?}', fn($token = null) => Inertia::render('Auth/ResetPassword', ['token' => $token]))->name('password.reset');
Route::get('/auth/verify-email/{id}/{hash}', fn() => Inertia::render('Auth/VerifyEmail'))->name('verification.notice');
Route::get('/auth/confirm-password', fn() => Inertia::render('Auth/ConfirmPassword'))->name('password.confirm');

// =============================================================================
// ROUTES AUTORISÉES (auth + verified)
// =============================================================================

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard principal
    Route::get('/dashboard', function () {
        return redirect()->route('param.projects.index');
    })->name('dashboard');
    
    // Dashboards variés
    Route::get('/', [DashboardController::class, 'sales'])->name('dashboard-sales');
    Route::get('/dashboards/param', [DashboardController::class, 'salesparam'])->name('dashboard-param');
    Route::get('/dashboards/paramr', [DashboardController::class, 'salesparam'])->name('dashboard-processus');
    Route::get('/dashboards/risque', [DashboardController::class, 'salesparam'])->name('dashboard-risque');
    Route::get('/dashboards/clinic', [DashboardController::class, 'clinic'])->name('dashboard-clinic');
    Route::get('/dashboards/e-wallet', [DashboardController::class, 'wallet'])->name('dashboard-wallet');
    
    // =============================================================================
    // ROUTES DE SÉLECTION DE TENANT (auth seulement, pas de tenant requis)
    // =============================================================================
    
    Route::name('tenants.')->group(function () {
        
        // Page de sélection de tenant
        Route::get('/tenants/select', function () {
            $user = auth()->user();
            
            try {
                // Récupérer les tenants accessibles à l'utilisateur
                $tenants = DB::connection('mysql')->table('tenant_user')
                    ->where('user_id', $user->id)
                    ->join('tenants', 'tenant_user.tenant_id', '=', 'tenants.id')
                    ->select('tenants.*')
                    ->orderBy('tenants.name')
                    ->get();
                    
                // Si aucun tenant, créer un message d'erreur
                if ($tenants->isEmpty()) {
                    return Inertia::render('Tenants/Select', [
                        'tenants' => collect(),
                        'error' => 'Aucun client disponible pour votre compte. Contactez l\'administrateur.'
                    ]);
                }
                
                return Inertia::render('Tenants/Select', [
                    'tenants' => $tenants,
                    'currentTenantId' => session('tenant_id')
                ]);
                
            } catch (\Exception $e) {
                return Inertia::render('Tenants/Select', [
                    'tenants' => collect(),
                    'error' => 'Erreur lors du chargement des clients: ' . $e->getMessage()
                ]);
            }
        })->name('select');
        
        // Changer de tenant
        Route::post('/tenants/switch', function (Request $request) {
            $request->validate([
                'tenant_id' => 'required|integer|exists:tenants,id'
            ]);
            
            $tenantId = $request->tenant_id;
            $userId = auth()->id();
            
            try {
                // Vérifier l'accès au tenant
                $hasAccess = DB::connection('mysql')->table('tenant_user')
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->exists();
                    
                if (!$hasAccess) {
                    return back()->with('error', 'Accès non autorisé à ce client.');
                }
                
                // Définir le tenant en session
                session(['tenant_id' => $tenantId]);
                
                return redirect()->intended(route('dashboard'))
                    ->with('success', 'Client sélectionné avec succès !');
                    
            } catch (\Exception $e) {
                return back()->with('error', 'Erreur lors du changement de client: ' . $e->getMessage());
            }
        })->name('switch');
        
        // Obtenir les infos du tenant courant (API)
        Route::get('/tenants/current', function () {
            $tenantId = session('tenant_id');
            
            if (!$tenantId) {
                return response()->json(['error' => 'Aucun client sélectionné'], 400);
            }
            
            try {
                $tenant = DB::connection('mysql')->table('tenants')->where('id', $tenantId)->first();
                
                return response()->json([
                    'tenant' => $tenant,
                    'database' => config('database.connections.tenant.database', null),
                    'session' => [
                        'tenant_id' => session('tenant_id'),
                        'tenant_db' => session('tenant_db'),
                        'tenant_name' => session('tenant_name')
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json(['error' => 'Erreur lors du chargement du client'], 500);
            }
        })->name('current');
    });
    
    // =============================================================================
    // ROUTES PROTÉGÉES PAR TENANT (auth + verified + tenant)
    // =============================================================================
    
    Route::middleware(['tenant'])->group(function () {
        
        // Routes PARAM principales
        Route::prefix('param')->name('param.')->group(function () {
            
            // Projets
            Route::resource('projects', ProjetController::class)->names('projects');
            
            // Autres ressources param
            Route::resource('process', MacroprocessController::class);
            Route::resource('entities', EntityController::class);
            Route::resource('processus', ProcessusController::class);
            Route::resource('activites', ActiviteController::class);
            Route::resource('fonctions', FonctionController::class);
            Route::resource('attributions', AttributionController::class);
            Route::resource('attribution-fonctions', AttributionFonctionController::class);
        });
        
        // Routes statiques param
        Route::prefix('param')->name('param.')->group(function () {
            Route::get('/fonctions', [ParamStaticController::class, 'fonctions'])
                ->name('functions.index');
            Route::get('/repartitions', [ParamStaticController::class, 'repartitions'])
                ->name('distribution.index');
        });
        
        // Pages d'erreur protégées (optionnel)
        Route::prefix('error')->name('error.')->group(function () {
            Route::get('/400', [ErrorController::class, 'error400'])->name('400');
            Route::get('/401', [ErrorController::class, 'error401'])->name('401');
            Route::get('/403', [ErrorController::class, 'error403'])->name('403');
            Route::get('/404', [ErrorController::class, 'error404'])->name('404');
            Route::get('/404-alt', [ErrorController::class, 'error404Alt'])->name('404-alt');
            Route::get('/408', [ErrorController::class, 'error408'])->name('408');
            Route::get('/500', [ErrorController::class, 'error500'])->name('500');
            Route::get('/501', [ErrorController::class, 'error501'])->name('501');
            Route::get('/502', [ErrorController::class, 'error502'])->name('502');
            Route::get('/503', [ErrorController::class, 'error503'])->name('503');
        });
    });
});

// =============================================================================
// ROUTES DE DEBUG (auth seulement)
// =============================================================================

Route::middleware(['auth'])->group(function () {
    
    Route::prefix('debug')->name('debug.')->group(function () {
        
        // Debug tenant complet (page Inertia)
        Route::get('/tenant', function () {
            $tenantId = session('tenant_id');
            $tenantConfig = config('database.connections.tenant');
            
            try {
                // Test de connexion tenant
                $connection = DB::connection('tenant');
                $currentDb = $connection->getDatabaseName();
                $hasProjectsTable = $connection->getSchemaBuilder()->hasTable('projects');
                $projectsCount = $connection->table('projects')->count();
                
            } catch (\Exception $e) {
                $currentDb = 'connection_failed';
                $hasProjectsTable = false;
                $projectsCount = 0;
            }
            
            return Inertia::render('Debug/Tenant', [
                'user' => auth()->user(),
                'tenant_id' => $tenantId,
                'session_data' => [
                    'tenant_id' => session('tenant_id'),
                    'tenant_db' => session('tenant_db'),
                    'tenant_name' => session('tenant_name')
                ],
                'tenant_config' => $tenantConfig,
                'connection_status' => [
                    'database' => $currentDb,
                    'has_projects_table' => $hasProjectsTable,
                    'projects_count' => $projectsCount,
                    'is_connected' => isset($currentDb) && $currentDb !== 'connection_failed'
                ]
            ]);
        })->name('tenant');
        
        // Debug JSON tenant (API)
        Route::get('/tenant-json', function () {
            $tenantId = session('tenant_id');
            
            if (!$tenantId) {
                return response()->json(['error' => 'No tenant ID in session'], 400);
            }

            try {
                $tenant = DB::connection('mysql')->table('tenants')->where('id', $tenantId)->first();
                $currentDb = DB::connection('tenant')->getDatabaseName();
                $projectsCount = DB::connection('tenant')->table('projects')->count();
                $hasProjectsTable = DB::connection('tenant')->getSchemaBuilder()->hasTable('projects');
                
            } catch (\Exception $e) {
                $tenant = null;
                $currentDb = 'connection_failed';
                $projectsCount = 0;
                $hasProjectsTable = false;
            }
            
            return response()->json([
                'user_id' => auth()->id(),
                'tenant_id' => $tenantId,
                'tenant_record' => $tenant,
                'session_data' => [
                    'tenant_id' => session('tenant_id'),
                    'tenant_db' => session('tenant_db'),
                    'tenant_name' => session('tenant_name')
                ],
                'tenant_connection' => [
                    'config' => config('database.connections.tenant'),
                    'current_database' => $currentDb,
                    'projects_count' => $projectsCount,
                    'has_projects_table' => $hasProjectsTable
                ]
            ]);
        })->name('tenant-json');
        
        // Debug configuration
        Route::get('/tenant-config', function () {
            try {
                return response()->json([
                    'database_connections' => config('database.connections'),
                    'tenant_config' => config('database.connections.tenant'),
                    'session_tenant_id' => session('tenant_id'),
                    'user_tenants' => DB::connection('mysql')->table('tenant_user')
                        ->where('user_id', auth()->id())
                        ->join('tenants', 'tenant_user.tenant_id', '=', 'tenants.id')
                        ->select('tenants.id', 'tenants.name', 'tenants.db_name')
                        ->get()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Debug failed',
                    'message' => $e->getMessage()
                ], 500);
            }
        })->name('tenant-config');
    });
});

// =============================================================================
// Fallback route pour les erreurs 404
// =============================================================================

Route::fallback(function () {
    return Inertia::render('Error', ['statusCode' => 404]);
});

// Test du middleware tenant
Route::get('/test-tenant-middleware', function () {
    try {
        // Vérifier si le middleware est enregistré
        $middleware = config('route.middleware');
        $tenantMiddleware = $middleware['tenant'] ?? 'NOT_FOUND';
        
        // Tester l'instanciation
        $tenantClass = new ReflectionClass(\App\Http\Middleware\SetTenantDatabase::class);
        $instance = $tenantClass->newInstance();
        
        return response()->json([
            'success' => true,
            'middleware_registered' => $tenantMiddleware !== 'NOT_FOUND',
            'tenant_class_exists' => class_exists(\App\Http\Middleware\SetTenantDatabase::class),
            'tenant_instance_created' => $instance instanceof \App\Http\Middleware\SetTenantDatabase,
            'kernel_middleware' => array_keys($middleware),
            'session_tenant_id' => session('tenant_id')
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware(['web', 'auth']);