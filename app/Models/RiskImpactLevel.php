<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * Score max possible avec ce niveau d'impact (impact × freq_max).
     */
    public function maxCriticality(): int
    {
        return $this->score * $this->matrixConfig->matrix_size;
    }

    /**
     * Retourne une représentation pour le front (utilisée dans les selects Vue).
     */
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
