<?php

// ════════════════════════════════════════════════════════════════════════════
// 8️⃣ MODEL: RiskPerformanceIndicator
// ════════════════════════════════════════════════════════════════════════════
namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskPerformanceIndicator extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'risk_id', 'process_id', 'function_id', 'code',
        'label', 'description', 'kpi_name', 'unit', 'frequency',
        'target_value', 'min_acceptable', 'ideal_value',
        'calculation_formula', 'data_source', 'is_active'
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'min_acceptable' => 'decimal:2',
        'ideal_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(RiskIndicatorRecord::class);
    }
}
