<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskMitigation extends Model
{
    use SoftDeletes;

    protected $table = 'risk_mitigations';

    protected $fillable = [
        'tenant_id', 'risk_id', 'code', 'label', 'description', 'strategy',
        'action_plan', 'owner_id', 'start_date', 'target_date', 'completion_date',
        'residual_frequency_id', 'residual_impact_id', 'residual_criticality',
        'status', 'progress'
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'completion_date' => 'date',
        'progress' => 'integer',
    ];

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function residualFrequency(): BelongsTo
    {
        return $this->belongsTo(RiskFrequencyLevel::class, 'residual_frequency_id');
    }

    public function residualImpact(): BelongsTo
    {
        return $this->belongsTo(RiskImpactLevel::class, 'residual_impact_id');
    }
}
