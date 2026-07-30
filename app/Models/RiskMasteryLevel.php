<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskMasteryLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_mastery_levels';

    protected $fillable = [
        'tenant_id',
        'matrix_config_id',
        'zone_id',
        'label',
        'min_score',
        'max_score',
        'color_code',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'sort_order' => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function matrixConfig(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixConfig::class, 'matrix_config_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(RiskCriticalityZone::class, 'zone_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}