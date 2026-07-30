<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class RiskFrequencyCriteriaTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'risk_frequency_criteria_templates';

    protected $fillable = [
        'tenant_id',
        'matrix_config_id',
        'designation',
        'hint',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function matrixConfig(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixConfig::class, 'matrix_config_id');
    }

    /**
     * Les instances de ce critère dans chaque niveau de fréquence.
     * Utilise le modèle existant RiskFrequencyCriterion.
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RiskFrequencyCriterion::class, 'template_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

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
}