<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class RiskCriticalityZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_criticality_zones';

    protected $fillable = [
        'tenant_id',
        'matrix_config_id',
        'label',
        'min_score',
        'max_score',
        'color_code',
        'sort_order',
    ];

    protected $casts = [
        'min_score'  => 'integer',
        'max_score'  => 'integer',
        'sort_order' => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function matrixConfig(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixConfig::class, 'matrix_config_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForConfig(Builder $query, int $configId): Builder
    {
        return $query->where('matrix_config_id', $configId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeContainingScore(Builder $query, int $score): Builder
    {
        return $query->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }

    // ─── Méthodes métier ──────────────────────────────────────────────────────

    /**
     * Vérifie si un score appartient à cette zone.
     */
    public function contains(int $score): bool
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }

    /**
     * Largeur de la plage (ex: zone 15–25 = amplitude 11).
     */
    public function getRangeAttribute(): int
    {
        return $this->max_score - $this->min_score + 1;
    }

    /**
     * Libellé avec plage pour l'affichage (ex: "Critique [20–25]").
     */
    public function getLabelWithRangeAttribute(): string
    {
        return "{$this->label} [{$this->min_score}–{$this->max_score}]";
    }

    /**
     * Génère les zones par défaut pour une taille de matrice donnée.
     * Utile pour le seeding initial ou le reset d'une config.
     */
    public static function defaultZonesForSize(int $size): array
    {
        return match ($size) {
            3 => [
                ['label' => 'Faible',    'min_score' => 1, 'max_score' => 3,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Modéré',    'min_score' => 4, 'max_score' => 6,  'color_code' => '#eab308', 'sort_order' => 2],
                ['label' => 'Critique',  'min_score' => 7, 'max_score' => 9,  'color_code' => '#ef4444', 'sort_order' => 3],
            ],
            4 => [
                ['label' => 'Faible',    'min_score' => 1,  'max_score' => 4,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Modéré',    'min_score' => 5,  'max_score' => 8,  'color_code' => '#eab308', 'sort_order' => 2],
                ['label' => 'Élevé',     'min_score' => 9,  'max_score' => 12, 'color_code' => '#f97316', 'sort_order' => 3],
                ['label' => 'Critique',  'min_score' => 13, 'max_score' => 16, 'color_code' => '#ef4444', 'sort_order' => 4],
            ],
            5 => [
                ['label' => 'Négligeable', 'min_score' => 1,  'max_score' => 4,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 5,  'max_score' => 9,  'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modéré',      'min_score' => 10, 'max_score' => 14, 'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Élevé',       'min_score' => 15, 'max_score' => 19, 'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 20, 'max_score' => 25, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            default => [],
        };
    }
}
