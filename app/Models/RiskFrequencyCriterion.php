<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskFrequencyCriterion extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_frequency_criteria';

    protected $fillable = [
        'tenant_id',
        'frequency_level_id',
        'designation',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function frequencyLevel(): BelongsTo
    {
        return $this->belongsTo(RiskFrequencyLevel::class, 'frequency_level_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForLevel(Builder $query, int $levelId): Builder
    {
        return $query->where('frequency_level_id', $levelId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // ─── Sérialisation front ──────────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'designation' => $this->designation,
            'description' => $this->description,
            'sort_order'  => $this->sort_order,
        ];
    }
}
