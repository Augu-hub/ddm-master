<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MODEL: RiskAppetiteLevel
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Niveau d'appétance au risque défini par chaque tenant.
 * Les codes sont normalisés : APT-0, APT-1, APT-2, APT-3, APT-4 ...
 *
 * Les plages score_min / score_max couvrent la matrice 5×5 (1–25).
 * Exemple de configuration standard :
 *   APT-0 | Nul      | 0   – 0    | #28a745
 *   APT-1 | Faible   | 1   – 5    | #17a2b8
 *   APT-2 | Modéré   | 6   – 12   | #ffc107
 *   APT-3 | Élevé    | 13  – 19   | #fd7e14
 *   APT-4 | Critique | 20  – 25   | #dc3545
 */
class RiskAppetiteLevel extends Model
{
    use SoftDeletes;

    protected $table = 'risk_appetite_levels';

    protected $fillable = [
        'tenant_id',
        'code',
        'label',
        'description',
        'score_min',
        'score_max',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'score_min'  => 'integer',
        'score_max'  => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
        'tenant_id'  => 'integer',
    ];

    // ────────────────────────────────────────────────────────────────────────
    // RELATIONS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Nomenclatures associées directement à ce niveau d'appétance.
     * (hors celles qui héritent via leur ancêtre)
     */
    public function nomenclatures(): HasMany
    {
        return $this->hasMany(RiskNomenclature::class, 'appetite_id');
    }

    // ────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ────────────────────────────────────────────────────────────────────────

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ────────────────────────────────────────────────────────────────────────
    // MÉTHODES UTILITAIRES
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Vérifie si un score de criticité donné tombe dans la plage
     * de ce niveau d'appétance.
     */
    public function matchesScore(int $score): bool
    {
        return $score >= $this->score_min && $score <= $this->score_max;
    }

    /**
     * Retourne le niveau d'appétance correspondant à un score
     * pour un tenant donné.
     */
    public static function resolveForScore(int $tenantId, int $score): ?self
    {
        return static::byTenant($tenantId)
            ->active()
            ->where('score_min', '<=', $score)
            ->where('score_max', '>=', $score)
            ->ordered()
            ->first();
    }
}
