<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class RiskMatrixConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_matrix_configs';

    protected $fillable = [
        'tenant_id',
        'name',
        'matrix_size',
        'description',
        'is_active',
    ];

    protected $casts = [
        'matrix_size' => 'integer',
        'is_active'   => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function impactLevels(): HasMany
    {
        return $this->hasMany(RiskImpactLevel::class, 'matrix_config_id')
            ->orderBy('sort_order');
    }

    public function frequencyLevels(): HasMany
    {
        return $this->hasMany(RiskFrequencyLevel::class, 'matrix_config_id')
            ->orderBy('sort_order');
    }

    public function criticalityZones(): HasMany
    {
        return $this->hasMany(RiskCriticalityZone::class, 'matrix_config_id')
            ->orderBy('sort_order');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Méthodes métier ──────────────────────────────────────────────────────

    /**
     * Active cette config et désactive toutes les autres du même tenant.
     */
    public function activate(): void
    {
        static::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    /**
     * Score maximum possible pour cette matrice (ex: 5×5 = 25).
     */
    public function getMaxScoreAttribute(): int
    {
        return $this->matrix_size * $this->matrix_size;
    }

    /**
     * Libellé de la matrice (ex: "5×5").
     */
    public function getMatrixLabelAttribute(): string
    {
        return "{$this->matrix_size}×{$this->matrix_size}";
    }

    /**
     * Résout la zone de criticité correspondant à un score donné.
     */
    public function resolveZone(int $score): ?RiskCriticalityZone
    {
        return $this->criticalityZones
            ->first(fn (RiskCriticalityZone $zone) =>
                $score >= $zone->min_score && $score <= $zone->max_score
            );
    }

    /**
     * Génère la grille Impact × Fréquence avec score et zone.
     * Retourne un tableau 2D [impact_score][freq_score] => ['score', 'zone']
     */
    public function buildMatrix(): array
    {
        $matrix = [];

        foreach ($this->impactLevels as $impact) {
            foreach ($this->frequencyLevels as $freq) {
                $score = $impact->score * $freq->score;
                $matrix[$impact->score][$freq->score] = [
                    'score' => $score,
                    'zone'  => $this->resolveZone($score),
                ];
            }
        }

        return $matrix;
    }
}
