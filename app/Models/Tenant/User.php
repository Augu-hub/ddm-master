<?php

namespace App\Models\Tenant;

use App\Models\Param\Entite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'matricule',
        'status',
        'job_title',
        'bio',
        'entity_id',
        'created_by',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'avatar_url',
        'status_badge',
        'initials',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-générer le matricule si non fourni
        static::creating(function ($user) {
            if (empty($user->matricule)) {
                $user->matricule = self::generateMatricule();
            }
        });
    }

    /**
     * Générer un matricule unique
     */
    public static function generateMatricule(): string
    {
        $prefix = 'USR';
        $year = date('Y');
        $lastUser = self::query()
            ->where('matricule', 'LIKE', "{$prefix}{$year}%")
            ->orderBy('matricule', 'desc')
            ->first();

        if ($lastUser && preg_match('/' . $prefix . $year . '(\d{4})/', $lastUser->matricule, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        return $prefix . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Générer un mot de passe aléatoire sécurisé
     */
    public static function generateRandomPassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()-_=+';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }

    /**
     * Hash le mot de passe avant sauvegarde
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }

    /**
     * Relation avec l'entité
     */
    public function entity()
    {
        return $this->belongsTo(Entite::class);
    }

    /**
     * Relation avec le créateur
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * URL de l'avatar
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=667eea&background=e0e7ff';
    }

    /**
     * Badge de statut
     */
    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'active' => ['text' => 'Actif', 'variant' => 'success'],
            'inactive' => ['text' => 'Inactif', 'variant' => 'secondary'],
            'suspended' => ['text' => 'Suspendu', 'variant' => 'danger'],
            default => ['text' => 'Inconnu', 'variant' => 'light'],
        };
    }

    /**
     * Initiales de l'utilisateur
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Vérifier si l'utilisateur est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Activer l'utilisateur
     */
    public function activate(): bool
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Désactiver l'utilisateur
     */
    public function deactivate(): bool
    {
        $this->status = 'inactive';
        return $this->save();
    }

    /**
     * Suspendre l'utilisateur
     */
    public function suspend(): bool
    {
        $this->status = 'suspended';
        return $this->save();
    }

    /**
     * Mettre à jour la dernière connexion
     */
    public function updateLastLogin(): bool
    {
        $this->last_login_at = now();
        return $this->save();
    }

    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les utilisateurs d'une entité
     */
    public function scopeByEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * Scope pour les utilisateurs créés par un utilisateur spécifique
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}