<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Auth, Config, DB, Log};

class TenantServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Configure tenant connection on every request
        $this->app->booted(function () {
            if (Auth::check() && session()->has('tenant_id')) {
                $tenantId = session('tenant_id');
                $tenant = DB::connection('mysql')->table('tenants')->where('id', $tenantId)->first();
                
                if ($tenant && !empty($tenant->db_name)) {
                    $config = [
                        'driver' => 'mysql',
                        'host' => $tenant->db_host,
                        'port' => 3306,
                        'database' => $tenant->db_name,
                        'username' => $tenant->db_username,
                        'password' => $tenant->db_password,
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => '',
                        'prefix_indexes' => true,
                        'strict' => true,
                        'engine' => null,
                    ];

                    Config::set('database.connections.tenant', $config);
                    DB::purge('tenant');
                    
                    Log::info('Tenant configured via ServiceProvider', [
                        'tenant_id' => $tenantId,
                        'database' => $tenant->db_name
                    ]);
                }
            }
        });
    }
}