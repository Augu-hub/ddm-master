<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Master\Tenant;
use App\Models\Param\Projet;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation many-to-many avec les tenants
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class, 
            'tenant_user',
            'user_id',
            'tenant_id'
        )->withPivot('role_hint')
         ->withTimestamps();
    }

    /**
     * Vérifier si l'utilisateur a accès à un tenant spécifique
     */
    public function hasAccessToTenant($tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Vérifier si l'utilisateur est admin global
     */
    public function isGlobalAdmin(): bool
    {
        return $this->email === 'admin@diaddem.local';
    }

    /**
     * Obtenir le tenant actuel de l'utilisateur
     */
    public function getCurrentTenantAttribute()
    {
        $tenantId = session('tenant_id');
        if ($tenantId) {
            return $this->tenants()->where('tenant_id', $tenantId)->first();
        }
        return $this->tenants()->first();
    }

    /**
     * Relation avec le projet courant si nécessaire
     */
    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(Projet::class, 'current_project_id');
    }

    /**
     * Scope pour les utilisateurs ayant accès à un tenant spécifique
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->whereHas('tenants', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope pour les utilisateurs sans tenant
     */
    public function scopeWithoutTenants($query)
    {
        return $query->doesntHave('tenants');
    }
}