<?php
// app/Models/Param/CompetencyCategory.php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CompetencyCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'competency_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'order',
        'status',
    ];

    protected $casts = [
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ========================================================================
    // 🔗 ROUTE BINDING
    // ========================================================================

    public function resolveRouteBinding($value, $field = null)
    {
        Log::debug('🔗 CompetencyCategory::resolveRouteBinding', ['value' => $value]);

        if (!is_numeric($value)) {
            return null;
        }

        $this->ensureTenantDatabaseConfigured();

        try {
            return $this->on('tenant')
                ->where($field ?? $this->getRouteKeyName(), '=', $value)
                ->where('deleted_at', null)
                ->firstOrFail();
        } catch (\Exception $e) {
            Log::error('❌ Route binding failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function ensureTenantDatabaseConfigured(): void
    {
        $currentDb = Config::get('database.connections.tenant.database');

        if (!empty($currentDb)) {
            return;
        }

        Log::warning('⚠️ Tenant database not configured, attempting fallback');

        $tenantId = session('tenant_id');
        if (!$tenantId) {
            throw new \Exception('No tenant configured');
        }

        try {
            $tenant = DB::connection('mysql')
                ->table('tenants')
                ->where('id', $tenantId)
                ->first();

            if (!$tenant || empty($tenant->db_name)) {
                throw new \Exception("Tenant {$tenantId} has no database");
            }

            $config = [
                'driver' => 'mysql',
                'host' => $tenant->db_host ?? '127.0.0.1',
                'port' => $tenant->db_port ?? env('DB_PORT', 3306),
                'database' => $tenant->db_name,
                'username' => $tenant->db_username ?? env('DB_USERNAME', 'root'),
                'password' => $tenant->db_password ?? env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ];

            Config::set('database.connections.tenant', $config);
            DB::purge('tenant');
            DB::reconnect('tenant');

            Log::info('✅ Fallback: Tenant database configured');
        } catch (\Exception $e) {
            Log::error('❌ Fallback setup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ========================================================================
    // 🔗 RELATIONS
    // ========================================================================

    public function competencies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Competency::class, 'category_id');
    }

    // ========================================================================
    // 🎯 SCOPES
    // ========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
}
