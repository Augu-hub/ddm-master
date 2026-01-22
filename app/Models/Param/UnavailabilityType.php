<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnavailabilityType extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'unavailability_types';

    protected $fillable = [
        'category',
        'code',
        'name',
        'description',
        'icon',
        'color',
        'is_active',
        'is_custom',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== SCOPES ====================

    /**
     * Scope: Types actifs seulement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Par catégorie (global ou auditor)
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Types prédéfinis
     */
    public function scopeDefault($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Scope: Types personnalisés
     */
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    // ==================== MÉTHODES ====================

    /**
     * Affichage complet du type
     */
    public function getFullNameAttribute()
    {
        return "{$this->icon} {$this->name}";
    }

    /**
     * Vérifier si c'est un type de jours fériés
     */
    public function isGlobal()
    {
        return $this->category === 'global';
    }

    /**
     * Vérifier si c'est un type d'auditeur
     */
    public function isAuditor()
    {
        return $this->category === 'auditor';
    }

    /**
     * Obtenir la couleur pour le badge
     */
    public function getBadgeColor()
    {
        return $this->color ?? '#667eea';
    }

    /**
     * Obtenir l'icon
     */
    public function getIcon()
    {
        return $this->icon ?? '📝';
    }

    /**
     * Vérifier si personnalisé
     */
    public function isCustom()
    {
        return $this->is_custom === true;
    }

    /**
     * Vérifier si prédéfini
     */
    public function isDefault()
    {
        return $this->is_custom === false;
    }
}