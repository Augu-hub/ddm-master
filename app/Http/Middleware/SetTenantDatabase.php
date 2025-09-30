<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class SetTenantDatabase
{
    public function handle(Request $request, Closure $next)
    {
        // Ne pas appliquer aux routes de sélection de tenant
        if ($request->is('tenants/*') || $request->is('login*') || $request->is('register*')) {
            Log::debug('Skipping tenant middleware for path', ['path' => $request->path()]);
            return $next($request);
        }

        // Si pas d'utilisateur connecté, continuer
        if (!auth()->check()) {
            Log::debug('No auth user, skipping tenant setup');
            return $next($request);
        }

        // Si pas de tenant sélectionné, rediriger
        if (!session()->has('tenant_id')) {
            Log::info('No tenant ID in session, redirecting to select', [
                'user_id' => auth()->id(),
                'path' => $request->path()
            ]);
            
            return redirect()->route('tenants.select')
                ->with('error', 'Veuillez sélectionner un client avant de continuer.');
        }

        $tenantId = (int) session('tenant_id');
        Log::info('Setting up tenant connection', ['tenant_id' => $tenantId]);

        // Configurer la connexion
        try {
            $this->setupTenantConnection($tenantId);
        } catch (\Exception $e) {
            Log::error('Failed to setup tenant connection', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback à la base principale
            $this->fallbackToMainConnection();
            
            return redirect()->route('tenants.select')
                ->with('error', 'Erreur de connexion au client: ' . $e->getMessage());
        }

        return $next($request);
    }

    private function setupTenantConnection($tenantId)
    {
        // 1. Récupérer les infos du tenant depuis la base principale
        $tenant = DB::connection('mysql')->table('tenants')->where('id', $tenantId)->first();
        
        if (!$tenant) {
            throw new \Exception("Tenant {$tenantId} not found in main database");
        }

        Log::info('Tenant found', [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant->name,
            'db_name' => $tenant->db_name
        ]);

        // 2. Vérifier l'accès utilisateur
        $hasAccess = DB::connection('mysql')->table('tenant_user')
            ->where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->exists();

        if (!$hasAccess) {
            throw new \Exception("User " . auth()->id() . " has no access to tenant {$tenantId}");
        }

        // 3. Vérifier que la base de données n'est pas vide
        if (empty($tenant->db_name)) {
            throw new \Exception("Tenant {$tenantId} has no database name configured");
        }

        // 4. Configurer la connexion tenant
        $config = [
            'driver' => 'mysql',
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? env('DB_PORT', 3306),
            'database' => $tenant->db_name, // ← CRUCIAL : clientA ou clientB
            'username' => $tenant->db_username ?? env('DB_USERNAME', 'root'),
            'password' => $tenant->db_password ?? env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ];

        // 5. APPLIQUER LA CONFIGURATION
        Log::info('Applying tenant config', [
            'database' => $config['database'],
            'host' => $config['host']
        ]);

        Config::set('database.connections.tenant', $config);
        
        // 6. PURGER et RECONNECTER
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        // 7. TESTER LA CONNEXION
        $pdo = DB::connection('tenant')->getPdo();
        if (!$pdo) {
            throw new \Exception('Failed to get PDO connection');
        }
        
        // 8. VÉRIFIER LA BASE COURANTE
        $currentDb = $pdo->query('SELECT DATABASE() as db')->fetch(PDO::FETCH_OBJ)->db;
        
        if ($currentDb !== $tenant->db_name) {
            throw new \Exception("Connected to wrong database: {$currentDb} (expected: {$tenant->db_name})");
        }

        // 9. Stocker en session
        session([
            'tenant_db' => $tenant->db_name,
            'tenant_name' => $tenant->name,
            'tenant_id' => $tenantId
        ]);

        // 10. LOG DE SUCCÈS
        Log::info('✅ Tenant connection SUCCESS', [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant->name,
            'database' => $tenant->db_name,
            'connected_to' => $currentDb,
            'user_id' => auth()->id()
        ]);

        // 11. Vérifier que la table projects existe
        $schema = DB::connection('tenant')->getDoctrineSchemaManager();
        $tables = $schema->listTableNames();
        
        Log::info('Tenant tables', [
            'tenant_id' => $tenantId,
            'tables' => $tables,
            'has_projects' => in_array('projects', $tables)
        ]);
    }

    private function fallbackToMainConnection()
    {
        Log::warning('Falling back to main connection');
        $mainConfig = config('database.connections.mysql');
        Config::set('database.connections.tenant', $mainConfig);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}