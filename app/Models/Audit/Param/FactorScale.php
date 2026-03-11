<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * 🎯 FACTOR SCALE MODEL v5.0 - ÉCHELLES D'ÉVALUATION
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Gestion des échelles d'évaluation pour les facteurs d'audit:
 * ✅ Min/Max values
 * ✅ Descriptions
 * ✅ Relations avec Factors
 * ✅ Logs détaillées
 * 
 * @package App\Models\Audit\Param
 * @version 5.0
 */
class FactorScale extends Model
{
    use HasFactory;

    protected $table = 'audit_factor_scales';
    protected $keyType = 'int';
    public $timestamps = true;

    // ═════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═════════════════════════════════════════════════════════════════════════════

    protected $fillable = [
        'label',
        'min_value',
        'max_value',
        'description',
        'factor_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'min_value' => 'integer',
        'max_value' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ═════════════════════════════════════════════════════════════════════════════
    // LIFECYCLE HOOKS
    // ═════════════════════════════════════════════════════════════════════════════

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Valider que min < max
            if ($model->min_value >= $model->max_value) {
                throw new \InvalidArgumentException('Min doit être < Max');
            }

            Log::info("FactorScale saving", [
                'label' => $model->label,
                'min' => $model->min_value,
                'max' => $model->max_value
            ]);
        });

        static::created(function ($model) {
            Log::info("✅ FactorScale créée", [
                'id' => $model->id,
                'label' => $model->label
            ]);
        });

        static::updated(function ($model) {
            Log::info("✏️ FactorScale mise à jour", [
                'id' => $model->id,
                'label' => $model->label
            ]);
        });

        static::deleted(function ($model) {
            Log::warning("🗑️ FactorScale supprimée", [
                'id' => $model->id,
                'label' => $model->label
            ]);
        });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ═════════════════════════════════════════════════════════════════════════════

    /**
     * Relation avec Factor
     */
    public function factor()
    {
        return $this->belongsTo(Factor::class);
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // ACCESSEURS
    // ═════════════════════════════════════════════════════════════════════════════

    /**
     * Retourne la plage (Max - Min + 1)
     */
    public function getRangeAttribute()
    {
        return $this->max_value - $this->min_value + 1;
    }

    /**
     * Retourne les niveaux générés
     */
    public function getLevelsCountAttribute()
    {
        return $this->range;
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // MÉTHODES STATIQUES
    // ═════════════════════════════════════════════════════════════════════════════

    /**
     * Valide une échelle
     */
    public static function isValid($label, $minValue, $maxValue)
    {
        return !empty($label) && is_numeric($minValue) && is_numeric($maxValue) && $minValue < $maxValue;
    }

    /**
     * Récupère les stats
     */
    public static function getStatistics()
    {
        $scales = self::all();

        return [
            'total' => $scales->count(),
            'with_factors' => $scales->where('factor_id', '!=', null)->count(),
            'avg_levels' => round($scales->avg('range') ?? 0, 1),
            'total_levels' => $scales->sum('range') ?? 0,
        ];
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═════════════════════════════════════════════════════════════════════════════

    /**
     * Avec facteur associé
     */
    public function scopeWithFactor($query)
    {
        return $query->whereNotNull('factor_id');
    }

    /**
     * Sans facteur
     */
    public function scopeWithoutFactor($query)
    {
        return $query->whereNull('factor_id');
    }

    /**
     * Triés par label
     */
    public function scopeByLabel($query)
    {
        return $query->orderBy('label', 'asc');
    }

    /**
     * Récents d'abord
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Par plage
     */
    public function scopeByRange($query)
    {
        return $query->orderBy(\DB::raw('(max_value - min_value)'), 'desc');
    }
}