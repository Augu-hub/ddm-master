<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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
     * Libellé complet affiché dans l'UI : "Probable — 1 fois / 5 ans"
     */
    public function getFullLabelAttribute(): string
    {
        return $this->recurrence
            ? "{$this->label} — {$this->recurrence}"
            : $this->label;
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
            'recurrence'  => $this->recurrence,
            'full_label'  => $this->full_label,
            'color_code'  => $this->color_code,
            'sort_order'  => $this->sort_order,
        ];
    }
}
