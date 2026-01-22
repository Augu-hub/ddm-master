<?php

namespace App\Models\Param;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Auditor extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'auditors';

    protected $fillable = [
        'user_id',
        'entity_id',
        'created_by',
        'updated_by',
        'audit_id',
        'audit_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'birthplace',
        'gender',
        'address',
        'city',
        'postal_code',
        'country',
        'audit_experience',
        'other_experience',
        'bio',
        'avatar',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'audit_experience' => 'integer',
        'other_experience' => 'integer',
    ];

    protected $appends = [
        'full_name',
        'avatar_url',
        'status_badge',
    ];

    // ========================================================================
    // 🔗 ROUTE BINDING - CRUCIAL POUR LE MULTI-TENANT
    // ========================================================================

    /**
     * Résout le route binding implicite
     * Ceci s'exécute quand Laravel doit récupérer une instance du modèle
     * depuis les paramètres de la route (ex: /auditors/{auditor})
     */
    public function resolveRouteBinding($value, $field = null)
    {
        Log::debug('🔗 Auditor::resolveRouteBinding', ['value' => $value]);

        // Rejeter les valeurs non-numériques
        if (!is_numeric($value)) {
            return null;
        }

        // S'assurer que la database tenant est configurée
        $this->ensureTenantDatabaseConfigured();

        // Faire le binding
        try {
            return $this->on('tenant')
                ->where($field ?? $this->getRouteKeyName(), '=', $value)
                ->where('deleted_at', null)
                ->firstOrFail();
        } catch (\Exception $e) {
            Log::error('❌ Route binding failed', [
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * S'assure que la connexion tenant a une database configurée
     * Fallback au cas où le middleware ne s'est pas exécuté
     */
    protected function ensureTenantDatabaseConfigured(): void
    {
        $currentDb = Config::get('database.connections.tenant.database');

        // Si la database est déjà configurée, ok
        if (!empty($currentDb)) {
            return;
        }

        Log::warning('⚠️ Tenant database not configured in model, attempting fallback');

        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant configured. Please select a tenant first.');
        }

        try {
            $tenant = DB::connection('mysql')->table('tenants')
                ->where('id', $tenantId)
                ->first();

            if (!$tenant || empty($tenant->db_name)) {
                throw new \Exception("Tenant {$tenantId} has no database configured");
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entite::class, 'entity_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Competency::class,
            'auditor_competencies',
            'auditor_id',
            'competency_id'
        )
            ->withPivot(['level', 'certified_date', 'is_primary', 'notes'])
            ->withTimestamps();
    }

    // ========================================================================
    // 📋 ACCESSORS
    // ========================================================================

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name}");
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset("storage/{$this->avatar}");
        }

        $initials = strtoupper(
            substr($this->first_name ?? '', 0, 1) . substr($this->last_name ?? '', 0, 1)
        );

        return "https://ui-avatars.com/api/?name={$initials}&background=667eea&color=ffffff&bold=true&size=128";
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active' => ['text' => 'Actif', 'variant' => 'success'],
            'inactive' => ['text' => 'Inactif', 'variant' => 'secondary'],
            'suspended' => ['text' => 'Suspendu', 'variant' => 'danger'],
            default => ['text' => 'Inconnu', 'variant' => 'light'],
        };
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

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('audit_code', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%");
        });
    }

    public function scopeWithCompetencies($query)
    {
        return $query->with('competencies.category');
    }

    // ========================================================================
    // 🛠️ MÉTHODES UTILITAIRES
    // ========================================================================

    public static function generateAuditCode($prefix = 'AUD'): string
    {
        $lastAuditor = self::orderByDesc('id')->first();
        $nextNumber = ($lastAuditor ? intval(substr($lastAuditor->audit_code, 3)) : 0) + 1;
        return sprintf('%s%04d', $prefix, $nextNumber);
    }

    public function assignCompetency(
        $competencyId,
        $level = 1,
        $isPrimary = false,
        $certifiedDate = null
    ): void {
        $this->competencies()->syncWithoutDetaching([
            $competencyId => [
                'level' => $level,
                'certified_date' => $certifiedDate,
                'is_primary' => $isPrimary,
            ]
        ]);
    }

    public function revokeCompetency($competencyId): void
    {
        $this->competencies()->detach($competencyId);
    }

    public function hasCompetency($competencyId): bool
    {
        return $this->competencies()
            ->where('competency_id', $competencyId)
            ->exists();
    }

    public function getCompetencyLevel($competencyId): ?int
    {
        return $this->competencies()
            ->where('competency_id', $competencyId)
            ->first()
            ?->pivot->level;
    }

    public function competenciesByCategory(): array
    {
        return $this->competencies()
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(function ($competencies) {
                return $competencies->map(function ($comp) {
                    return [
                        'id' => $comp->id,
                        'code' => $comp->code,
                        'name' => $comp->name,
                        'level' => $comp->pivot->level,
                        'is_primary' => $comp->pivot->is_primary,
                        'certified_date' => $comp->pivot->certified_date,
                        'category' => $comp->category->name,
                    ];
                })->toArray();
            })
            ->toArray();
    }

    public function getTotalExperienceAttribute(): int
    {
        return ($this->audit_experience ?? 0) + ($this->other_experience ?? 0);
    }
}