<?php


namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskIndicatorRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'activity_indicator_id', 'performance_indicator_id',
        'record_date', 'recorded_value', 'target_value', 'deviation',
        'status', 'observations', 'recorded_by'
    ];

    protected $casts = [
        'record_date' => 'date',
        'recorded_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'deviation' => 'decimal:2',
    ];

    public function activityIndicator(): BelongsTo
    {
        return $this->belongsTo(RiskActivityIndicator::class);
    }

    public function performanceIndicator(): BelongsTo
    {
        return $this->belongsTo(RiskPerformanceIndicator::class);
    }
}
