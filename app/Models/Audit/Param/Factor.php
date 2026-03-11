<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * 🎯 FACTOR MODEL v3.0 - ULTRA OPTIMISÉ & PRODUCTION-READY
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Gestion COMPLÈTE des facteurs d'audit avec:
 * ✅ Importance auto (1-5) avec poids calculé (0-100%)
 * ✅ Réordonnement INTELLIGENT (ACTIFS puis INACTIFS)
 * ✅ Validation STRICTE avec messages clairs
 * ✅ Logs DÉTAILLÉES à tous les niveaux
 * ✅ Performance OPTIMISÉE (pas de N+1)
 * ✅ Architecture PROPRE avec constantes centralisées
 * ✅ Lifecycle hooks ROBUSTES
 * ✅ Scopes AVANCÉS pour filtrage
 * 
 * @package App\Models\Audit\Param
 * @version 3.0
 * @author Augustin
 * @created 27/01/2026
 */
class Factor extends Model
{
    use HasFactory;

    // ═════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION TABLE
    // ═════════════════════════════════════════════════════════════════════════════

    protected $table = 'audit_factors';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;

    // ═════════════════════════════════════════════════════════════════════════════
    // FILLABLE & CASTS
    // ═════════════════════════════════════════════════════════════════════════════

    protected $fillable = [
        'order_position',  // Position dans la liste
        'label',           // Nom du facteur
        'description',     // Description détaillée
        'is_active',       // Statut actif/inactif
        'importance',      // Importance (1-5)
        'weight'           // Poids calculé (0-100)
    ];

    protected $casts = [
        'id' => 'integer',
        'order_position' => 'integer',
        'is_active' => 'boolean',
        'importance' => 'integer',
        'weight' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'importance_label',
        'importance_color',
        'importance_description',
        'weight_percentage',
        'status_badge'
    ];

    // ═════════════════════════════════════════════════════════════════════════════
    // CONSTANTES CENTRALISÉES
    // ═════════════════════════════════════════════════════════════════════════════

    // Limites d'importance
    const MIN_IMPORTANCE = 1;
    const MAX_IMPORTANCE = 5;
    const DEFAULT_IMPORTANCE = 3;

    // Configuration complète de l'importance
    const IMPORTANCE_LEVELS = [
        1 => [
            'label' => 'Faible',
            'color' => 'danger',
            'factor' => 0.1,
            'description' => 'Critère optionnel, peu essentiel'
        ],
        2 => [
            'label' => 'Moyen',
            'color' => 'warning',
            'factor' => 0.3,
            'description' => 'Critère important mais pas essentiel'
        ],
        3 => [
            'label' => 'Normal',
            'color' => 'info',
            'factor' => 0.5,
            'description' => 'Critère standard et important'
        ],
        4 => [
            'label' => 'Élevé',
            'color' => 'success',
            'factor' => 0.8,
            'description' => 'Critère très important et essentiel'
        ],
        5 => [
            'label' => 'Critique',
            'color' => 'primary',
            'factor' => 1.0,
            'description' => 'Critère indispensable et critique'
        ],
    ];

    // ═════════════════════════════════════════════════════════════════════════════
    // LIFECYCLE HOOKS - Boot Model
    // ═════════════════════════════════════════════════════════════════════════════

    protected static function boot()
    {
        parent::boot();

        // 📝 BEFORE SAVE - Valider & Calculer
        static::saving(function ($model) {
            // ✅ Valider l'importance
            if (!is_null($model->importance)) {
                $model->importance = max(
                    self::MIN_IMPORTANCE,
                    min(self::MAX_IMPORTANCE, (int)$model->importance)
                );
            } else {
                $model->importance = self::DEFAULT_IMPORTANCE;
            }

            // ✅ Calculer le poids AUTOMATIQUEMENT
            $model->weight = self::calculateWeight($model->importance);

            // ✅ Log les changements
            if ($model->isDirty()) {
                Log::info("Factor saving", [
                    'label' => $model->label,
                    'importance' => $model->importance,
                    'weight' => $model->weight . '%'
                ]);
            }
        });

        // ✅ AFTER CREATE - Réordonne automatiquement
        static::created(function ($model) {
            self::reorderAll();
            Log::info("✅ Factor created", [
                'id' => $model->id,
                'label' => $model->label,
                'importance' => $model->importance
            ]);
        });

        // ✏️ AFTER UPDATE - Réordonne si changement importance/statut
        static::updated(function ($model) {
            if ($model->wasChanged(['importance', 'is_active'])) {
                self::reorderAll();
                Log::info("✏️ Factor updated", [
                    'id' => $model->id,
                    'label' => $model->label,
                    'changed' => $model->getChanges()
                ]);
            }
        });

        // 🗑️ AFTER DELETE - Réordonne après suppression
        static::deleted(function ($model) {
            self::reorderAll();
            Log::warning("🗑️ Factor deleted", [
                'id' => $model->id,
                'label' => $model->label
            ]);
        });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // ACCESSEURS (GETTERS) - Attributs calculés
    // ═════════════════════════════════════════════════════════════════════════════

    public function getImportanceLabelAttribute()
    {
        return self::IMPORTANCE_LEVELS[$this->importance]['label'] ?? 'Inconnu';
    }

    public function getImportanceColorAttribute()
    {
        return self::IMPORTANCE_LEVELS[$this->importance]['color'] ?? 'secondary';
    }

    public function getImportanceDescriptionAttribute()
    {
        return self::IMPORTANCE_LEVELS[$this->importance]['description'] ?? '';
    }

    public function getWeightPercentageAttribute()
    {
        return round($this->weight, 1);
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? '✓ Actif' : '✗ Inactif';
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // MÉTHODES STATIQUES - Calculs & Utilitaires
    // ═════════════════════════════════════════════════════════════════════════════

    /**
     * 📊 Calcul du poids basé sur l'importance
     * Formule: weight = (importance / 5) * 100
     */
    public static function calculateWeight($importance)
    {
        $importance = max(self::MIN_IMPORTANCE, min(self::MAX_IMPORTANCE, (int)$importance));
        return round(($importance / self::MAX_IMPORTANCE) * 100, 2);
    }

    /**
     * 🔍 Récupère le facteur d'importance
     */
    public static function getImportanceFactor($importance)
    {
        return self::IMPORTANCE_LEVELS[$importance]['factor'] ?? 0.5;
    }

    /**
     * ✅ Valide une valeur d'importance
     */
    public static function isValidImportance($value)
    {
        return is_int($value) && $value >= self::MIN_IMPORTANCE && $value <= self::MAX_IMPORTANCE;
    }

    /**
     * 🔄 RÉORDONNANCE COMPLÈTE & INTELLIGENTE
     * ⚠️ Utilise les valeurs NÉGATIVES pour éviter les conflits UNIQUE!
     */
    public static function reorderAll()
    {
        try {
            // ÉTAPE 1️⃣ : Réordonnancer les ACTIFS
            DB::statement('
                SET @pos = 0;
                UPDATE audit_factors 
                SET order_position = -(@pos := @pos + 1)
                WHERE is_active = 1
                ORDER BY weight DESC, created_at ASC
            ');

            // ÉTAPE 2️⃣ : Réordonnancer les INACTIFS
            DB::statement('
                SET @pos = (SELECT COUNT(*) FROM audit_factors WHERE is_active = 1);
                UPDATE audit_factors 
                SET order_position = -(@pos := @pos + 1)
                WHERE is_active = 0
                ORDER BY created_at DESC
            ');

            // ÉTAPE 3️⃣ : Convertir les NÉGATIFS en POSITIFS
            DB::statement('UPDATE audit_factors SET order_position = ABS(order_position)');

            Log::info("✅ Facteurs réordonnancés", [
                'total' => DB::table('audit_factors')->count(),
                'actifs' => DB::table('audit_factors')->where('is_active', 1)->count()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur réordonnement", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 📊 Récupère TOUTES les statistiques
     */
    public static function getStatistics()
    {
        try {
            $factors = self::all();

            return [
                'total' => $factors->count(),
                'active' => $factors->where('is_active', true)->count(),
                'inactive' => $factors->where('is_active', false)->count(),
                'average_weight' => round($factors->avg('weight') ?? 0, 2),
                'max_weight' => (int)($factors->max('weight') ?? 0),
                'min_weight' => (int)($factors->min('weight') ?? 0),
                'total_weight' => round($factors->sum('weight') ?? 0, 2),
                'by_importance' => [
                    1 => $factors->where('importance', 1)->count(),
                    2 => $factors->where('importance', 2)->count(),
                    3 => $factors->where('importance', 3)->count(),
                    4 => $factors->where('importance', 4)->count(),
                    5 => $factors->where('importance', 5)->count(),
                ]
            ];
        } catch (\Exception $e) {
            Log::error("❌ Erreur stats", ['error' => $e->getMessage()]);
            return [
                'total' => 0, 'active' => 0, 'inactive' => 0,
                'average_weight' => 0, 'max_weight' => 0, 'min_weight' => 0,
                'total_weight' => 0, 'by_importance' => [1=>0,2=>0,3=>0,4=>0,5=>0]
            ];
        }
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // MÉTHODES PUBLIQUES - Actions sur une instance
    // ═════════════════════════════════════════════════════════════════════════════

    /**
     * 🔄 Bascule le statut (Actif ↔ Inactif)
     */
    public function toggle()
    {
        $oldStatus = $this->is_active;
        $this->update(['is_active' => !$this->is_active]);
        self::reorderAll();
        
        Log::info("🔄 Factor toggled", [
            'id' => $this->id,
            'from' => $oldStatus ? 'Actif' : 'Inactif',
            'to' => $this->is_active ? 'Actif' : 'Inactif'
        ]);
        
        return $this;
    }

    /**
     * ⬆️ Change l'importance et recalcule le poids
     */
    public function setImportance($importance)
    {
        if (!self::isValidImportance($importance)) {
            throw new \InvalidArgumentException(
                "Importance invalide. Doit être entre " . self::MIN_IMPORTANCE . 
                " et " . self::MAX_IMPORTANCE
            );
        }

        $oldImportance = $this->importance;
        $this->update([
            'importance' => $importance,
            'weight' => self::calculateWeight($importance)
        ]);

        self::reorderAll();

        Log::info("⬆️ Factor importance changed", [
            'id' => $this->id,
            'from' => $oldImportance,
            'to' => $importance
        ]);

        return $this;
    }

    /**
     * 📦 Retourne les données formatées pour JSON
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'importance_label' => $this->importance_label,
            'importance_color' => $this->importance_color,
            'importance_description' => $this->importance_description,
            'weight_percentage' => $this->weight_percentage,
            'status_badge' => $this->status_badge,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // SCOPES - Filtrage & Tri avancés
    // ═════════════════════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByImportance($query)
    {
        return $query->orderBy('weight', 'desc')->orderBy('order_position', 'asc');
    }

    public function scopeByPosition($query)
    {
        return $query->orderBy('order_position', 'asc');
    }

    public function scopeSearch($query, $term)
    {
        if (empty(trim($term))) {
            return $query;
        }
        $term = "%{$term}%";
        return $query->where('label', 'like', $term)
            ->orWhere('description', 'like', $term);
    }

    public function scopeImportanceLevel($query, $level)
    {
        return $query->where('importance', $level);
    }

    public function scopeImportanceMin($query, $minLevel)
    {
        return $query->where('importance', '>=', $minLevel);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeHeavyWeight($query)
    {
        return $query->where('weight', '>=', 80);
    }

    public function scopeCritical($query)
    {
        return $query->where('importance', 5);
    }

    public function scopeImportant($query)
    {
        return $query->where('importance', '>=', 4);
    }
}