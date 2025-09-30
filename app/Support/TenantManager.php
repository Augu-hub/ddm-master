<?php

namespace App\Support;

use App\Models\Master\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantManager
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function currentId(): ?int
    {
        return $this->tenantId;
    }

    public function currentDatabase(): ?string
    {
        return session('tenant_db');
    }

    public function currentTenant(): ?Tenant
    {
        if (!$this->tenantId) return null;
        return Tenant::find($this->tenantId);
    }

    /**
     * Configure la connexion tenant (utilisé par le middleware)
     */
    public function connectOrFail(): void
    {
        $tenantId = $this->currentId();
        if (!$tenantId) {
            Log::warning('No tenant ID in session, skipping tenant connection');
            return;
        }

        try {
            $tenant = Tenant::findOrFail($tenantId);
            
            // Vérifier l'accès utilisateur
            $userId = auth()->id();
            $hasAccess = DB::table('tenant_user')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->exists();

            if (!$hasAccess) {
                throw new \Exception("User {$userId} has no access to tenant {$tenantId}");
            }

            $this->configureConnection($tenant);
            
            // Stocker le tenant actuel
            app()->instance('currentTenantId', $tenantId);
            app()->instance('currentTenant', $tenant);
            
            Log::info('TenantManager: Connection established', [
                'tenant_id' => $tenantId,
                'database' => $tenant->db_name
            ]);

        } catch (\Exception $e) {
            Log::error('TenantManager: Connection failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            $this->fallbackToMainConnection();
        }
    }

    protected function configureConnection(Tenant $tenant): void
    {
        $config = [
            'driver' => config('database.connections.mysql.driver', 'mysql'),
            'host' => $tenant->db_host ?? '127.0.0.1',
            'port' => $tenant->db_port ?? config('database.connections.mysql.port', 3306),
            'database' => $tenant->db_name,
            'username' => $tenant->db_username ?? config('database.connections.mysql.username', 'root'),
            'password' => $tenant->db_password ?? config('database.connections.mysql.password', ''),
            'unix_socket' => config('database.connections.mysql.unix_socket', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ];

        Config::set('database.connections.tenant', $config);

        // Purge et reconnexion
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        // Test de connexion
        DB::connection('tenant')->getPdo();
        
        // Stocker en session
        session([
            'tenant_db' => $tenant->db_name,
            'tenant_name' => $tenant->name
        ]);
    }

    protected function fallbackToMainConnection(): void
    {
        // Fallback vers la connexion principale
        Config::set('database.connections.tenant', config('database.connections.mysql'));
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Changer de tenant
     */
    public function switchTenant(int $tenantId): bool
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            
            // Vérifier l'accès
            $userId = auth()->id();
            $hasAccess = DB::table('tenant_user')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->exists();

            if (!$hasAccess) {
                return false;
            }

            $this->configureConnection($tenant);
            session(['tenant_id' => $tenantId]);
            app()->instance('currentTenantId', $tenantId);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to switch tenant', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}