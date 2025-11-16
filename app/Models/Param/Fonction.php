<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Fonction extends Model
{
    /** Connexion locataire */
    protected $connection = 'tenant';

    protected $table = 'functions';

    /** Champs modifiables */
    protected $fillable = [
        'name',
        'character',
        'avatar_path',
        'parent_id',
        'user_id',     // ✅ ajouté
    ];

    /** Accessors auto-ajoutés */
    protected $appends = ['avatar_url'];

    /* =======================
     * Relations hiérarchiques
     * ======================= */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /* =======================
     * Relation utilisateur (master DB)
     * ======================= */
    public function user(): BelongsTo
    {
        // User est sur la connexion par défaut (master)
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /* =======================
     * Relation entités (pivot tenant: entity_function)
     * ======================= */
    public function entities(): BelongsToMany
    {
        // Pivot: entity_function(function_id, entity_id)
        return $this->belongsToMany(
            \App\Models\Param\Entite::class,
            'entity_function',
            'function_id',
            'entity_id'
        )->withTimestamps();
    }

    /* =======================
     * Accessors
     * ======================= */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar_path) return null;
        return Storage::disk('public')->url($this->avatar_path);
    }

    /* =======================
     * Scopes utiles
     * ======================= */

    // ❌ Ancien scopeInProject supprimé (plus de project_id).
    // Exemple de scopes alternatifs si besoin :

    /** Racines (sans parent) */
    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    /** Filtrer par utilisateur rattaché */
    public function scopeForUser($q, $userId)
    {
        return $q->where('user_id', $userId);
    }
}
