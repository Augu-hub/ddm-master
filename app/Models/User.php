<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// ⬇️ maître
use App\Models\Master\Tenant;
use App\Models\Master\Module;

// ⬇️ pivot maître
use App\Models\Pivots\ModuleUser;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    // ⚠️ base maître
    protected $connection = 'mysql';

    use HasFactory, Notifiable, HasRoles;

    // Spatie (si tu utilises plusieurs guards, ajuste au besoin)
    protected $guard_name = 'web';

    protected $fillable = ['name','email','password'];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Tenants accessibles (base maître)
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_user',
            'user_id',
            'tenant_id'
        )->withPivot('role_hint')->withTimestamps();
    }

    /**
     * Modules accessibles (base maître, pivot module_user)
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'module_user',
            'user_id',
            'module_id'
        )->using(ModuleUser::class)
         ->withTimestamps();
    }

    /**
     * Vérifier l’accès à un tenant donné
     */
    public function hasAccessToTenant($tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Admin global ?
     * (tu peux aussi t’appuyer sur un rôle Spatie si tu préfères)
     */
    public function isGlobalAdmin(): bool
    {
        // Exemple simple par email :
        if ($this->email === 'admin@diaddem.local') return true;

        // Ou via session/roles :
        if (session('is_global_admin')) return true;
        if (method_exists($this, 'hasRole') && $this->hasRole('Super Admin')) return true;

        return false;
    }

    /**
     * Tenant courant (via session)
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
     * Projet courant si tu enregistres un FK dans users (OPTIONNEL)
     * ⚠️ attention : les Projets sont souvent côté tenant. À éviter
     * en cross-connexion, conserve ceci seulement si c’est réellement en mysql.
     */
    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Projet::class, 'current_project_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->whereHas('tenants', fn($q) => $q->where('tenant_id', $tenantId));
    }

    public function scopeWithoutTenants($query)
    {
        return $query->doesntHave('tenants');
    }
}
