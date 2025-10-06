<?php
// app/Models/Master/Tenant.php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

class Tenant extends Model
{
    protected $table = 'tenants';
    
    protected $fillable = [
        'name',
        'code',
        'db_host', 
        'db_name',
        'db_username',
        'db_password'
    ];

    /**
     * Relation many-to-many avec les utilisateurs via tenant_user
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tenant_user',
            'tenant_id',
            'user_id'
        )->withPivot('role_hint')
         ->withTimestamps();
    }

    /**
     * Scope pour les tenants actifs
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Obtenir le nombre d'utilisateurs
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Vérifier si un utilisateur a accès à ce tenant
     */
    public function hasUser($userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Assigner un utilisateur au tenant
     */
    public function assignUser($userId, $roleHint = null)
    {
        if (!$this->hasUser($userId)) {
            $this->users()->attach($userId, ['role_hint' => $roleHint]);
        }
    }

    /**
     * Retirer un utilisateur du tenant
     */
    public function removeUser($userId)
    {
        $this->users()->detach($userId);
    }
}