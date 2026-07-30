<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskImpactLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_impact_levels';

    protected $fillable = [
        'tenant_id',
        'matrix_config_id',
        'label',
        'score',
        'description',
        'color_code',
        'sort_order',
    ];

    protected $casts = [
        'score'      => 'integer',
        'sort_order' => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function matrixConfig(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixConfig::class, 'matrix_config_id');
    }

    /**
     * Critères d'évaluation associés à ce niveau d'impact.
     * Triés par sort_order directement dans la relation pour éviter
     * le recours à un closure dans with() (risque de collection vide).
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RiskImpactCriterion::class, 'impact_level_id')
            ->orderBy('sort_order')
            ->orderBy('id');
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
        return $query->orderBy('sort_order')->orderBy('score');
    }

    // ─── Méthodes métier ──────────────────────────────────────────────────────

    public function maxCriticality(): int
    {
        return $this->score * $this->matrixConfig->matrix_size;
    }

    public function toOption(): array
    {
        return [
            'id'          => $this->id,
            'label'       => $this->label,
            'score'       => $this->score,
            'description' => $this->description,
            'color_code'  => $this->color_code,
            'sort_order'  => $this->sort_order,
        ];
    }
}
