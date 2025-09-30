<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Master\Tenant;
use App\Models\Param\Projet;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
 use HasRoles; // ← important
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

public function tenants()
{
    return $this->belongsToMany(Tenant::class, 'tenant_user')
        ->withPivot('role_hint')->withTimestamps();
}
 protected $attributes = [
        'current_project_id' => 1, // Valeur par défaut
    ];

    protected $casts = [
        'current_project_id' => 'integer',
    ];
 

    // Relation avec le projet courant si nécessaire
    public function currentProject()
    {
        return $this->belongsTo(Projet::class, 'current_project_id');
    }
}
