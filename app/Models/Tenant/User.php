<?php

namespace App\Models\Tenant;

use App\Models\Param\Fonction;
use App\Models\Param\Entite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class User extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $connection = 'tenant';

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
     * ✅ Relation : Entité de rattachement
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    /**
     * ✅ Relation : Créateur (autre User)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * ✅ Relation : Fonctions attribuées
     * ✅ CORRECTION : Utiliser Fonction (avec accent) de App\Models\Param
     */
    public function functions(): BelongsToMany
    {
        return $this->belongsToMany(
            Fonction::class,  // ✅ App\Models\Param\Fonction (avec accent)
            'user_functions',
            'user_id',
            'function_id'
        )
            ->withPivot('entity_id', 'is_primary', 'role_label', 'assigned_at', 'revoked_at')
            ->wherePivotNull('revoked_at') // Seulement les fonctions actives
            ->withTimestamps();
    }

    /**
     * ✅ Fonctions actives par entité
     */
    public function functionsByEntity($entityId = null): BelongsToMany
    {
        $query = $this->functions();

        if ($entityId) {
            $query->wherePivot('entity_id', $entityId);
        }

        return $query;
    }

    /**
     * ✅ Fonction principale
     */
    public function primaryFunction()
    {
        return $this->functions()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * ✅ Fonction principale pour une entité donnée
     */
    public function primaryFunctionForEntity($entityId)
    {
        return $this->functionsByEntity($entityId)
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * ✅ Assigner une fonction à l'utilisateur
     */
    public function assignFunction(
        $functionId,
        $entityId = null,
        $isPrimary = false,
        $roleLabel = null
    ): void {
        $this->functions()->syncWithoutDetaching([
            $functionId => [
                'entity_id' => $entityId,
                'is_primary' => $isPrimary,
                'role_label' => $roleLabel,
                'assigned_at' => now(),
            ]
        ]);

        // Si on assigne en tant que primaire, retirer les autres primaires pour cette entité
        if ($isPrimary && $entityId) {
            $this->functions()
                ->wherePivot('entity_id', $entityId)
                ->wherePivot('function_id', '!=', $functionId)
                ->wherePivot('is_primary', true)
                ->update(['user_functions.is_primary' => false]);
        }
    }

    /**
     * ✅ Révoquer une fonction
     */
    public function revokeFunction($functionId, $entityId = null): void
    {
        if ($entityId) {
            \DB::table('user_functions')
                ->where('user_id', $this->id)
                ->where('function_id', $functionId)
                ->where('entity_id', $entityId)
                ->update(['revoked_at' => now()]);
        } else {
            \DB::table('user_functions')
                ->where('user_id', $this->id)
                ->where('function_id', $functionId)
                ->update(['revoked_at' => now()]);
        }
    }

    /**
     * ✅ Générer un matricule unique
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
     * ✅ Générer un mot de passe aléatoire sécurisé
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
     * ✅ Hash le mot de passe avant sauvegarde
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }

    /**
     * ✅ URL de l'avatar
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=667eea&background=e0e7ff';
    }

    /**
     * ✅ Badge de statut
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active' => ['text' => 'Actif', 'variant' => 'success'],
            'inactive' => ['text' => 'Inactif', 'variant' => 'secondary'],
            'suspended' => ['text' => 'Suspendu', 'variant' => 'danger'],
            default => ['text' => 'Inconnu', 'variant' => 'light'],
        };
    }

    /**
     * ✅ Initiales de l'utilisateur
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
     * ✅ Vérifier si l'utilisateur est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * ✅ Activer l'utilisateur
     */
    public function activate(): bool
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * ✅ Désactiver l'utilisateur
     */
    public function deactivate(): bool
    {
        $this->status = 'inactive';
        return $this->save();
    }

    /**
     * ✅ Suspendre l'utilisateur
     */
    public function suspend(): bool
    {
        $this->status = 'suspended';
        return $this->save();
    }

    /**
     * ✅ Mettre à jour la dernière connexion
     */
    public function updateLastLogin(): bool
    {
        $this->last_login_at = now();
        return $this->save();
    }

    /**
     * ✅ Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeWithFunction($query, $functionId, $entityId = null)
    {
        return $query->whereHas('functions', function ($q) use ($functionId, $entityId) {
            $q->where('function_id', $functionId);
            if ($entityId) {
                $q->where('entity_id', $entityId);
            }
        });
    }
}