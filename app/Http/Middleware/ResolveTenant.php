<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = session('tenant_id');
        
        if (!$tenantId) {
            // If no tenant in session and user is authenticated, redirect to tenant selection
            if (auth()->check()) {
                return redirect()->route('select.tenant');
            }
            return $next($request);
        }

        // Get tenant configuration
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            // Invalid tenant, clear session and redirect
            session()->forget('tenant_id');
            return redirect()->route('select.tenant');
        }

        // Configure the tenant database connection
        Config::set('database.connections.tenant.database', $tenant->db_name);
        Config::set('database.connections.tenant.host', $tenant->db_host);
        Config::set('database.connections.tenant.username', $tenant->db_username);
        Config::set('database.connections.tenant.password', $tenant->db_password);

        // Purge the existing connection to force reconnection with new settings
        DB::purge('tenant');

        return $next($request);
    }
}