<?php

// ════════════════════════════════════════════════════════════════════════════
// 1️⃣1️⃣ MODEL: RiskAssessment
// ════════════════════════════════════════════════════════════════════════════
namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'risk_id', 'project_id', 'code', 'assessment_date',
        'type', 'findings', 'rating', 'risk_score', 'evidence',
        'recommendations', 'assessed_by', 'next_assessment_date', 'status'
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'next_assessment_date' => 'date',
        'risk_score' => 'decimal:2',
    ];

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }
}
