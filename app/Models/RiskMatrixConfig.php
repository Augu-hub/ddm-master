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

    public function masteryLevels(): HasMany
    {
        return $this->hasMany(RiskMasteryLevel::class, 'matrix_config_id')
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

    public function activate(): void
    {
        static::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    public function getMaxScoreAttribute(): int
    {
        return $this->matrix_size * $this->matrix_size;
    }

    public function getMatrixLabelAttribute(): string
    {
        return "{$this->matrix_size}×{$this->matrix_size}";
    }
}