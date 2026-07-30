<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskFrequencyLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_frequency_levels';

    protected $fillable = [
        'tenant_id',
        'matrix_config_id',
        'label',
        'score',
        'description',
        'recurrence',
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
     * Critères d'évaluation associés à ce niveau de fréquence.
     * Triés par sort_order directement dans la relation pour éviter
     * le recours à un closure dans with() (risque de collection vide).
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RiskFrequencyCriterion::class, 'frequency_level_id')
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

    public function getFullLabelAttribute(): string
    {
        return $this->recurrence
            ? "{$this->label} — {$this->recurrence}"
            : $this->label;
    }

    public function toOption(): array
    {
        return [
            'id'          => $this->id,
            'label'       => $this->label,
            'score'       => $this->score,
            'description' => $this->description,
            'recurrence'  => $this->recurrence,
            'full_label'  => $this->full_label,
            'color_code'  => $this->color_code,
            'sort_order'  => $this->sort_order,
        ];
    }
}
