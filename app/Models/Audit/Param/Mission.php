<?php

namespace App\Models\Audit\Param;
use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class Mission extends Model
{
    protected $table = 'audit_missions';
    protected $fillable = [
        'entity_id', 'code', 'annual_plan_id', 'mission_source_id', 'risk_id', 'process_id',
        'mission_type', 'title', 'objective', 'priority_rank', 'criticality',
        'scheduled_start_date', 'scheduled_end_date', 'actual_start_date', 'actual_end_date',
        'budget', 'status', 'findings', 'recommendations'
    ];
    protected $casts = [
        'scheduled_start_date' => 'date',
        'scheduled_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'integer',
        'priority_rank' => 'integer',
        'criticality' => 'integer'
    ];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function annualPlan(): BelongsTo { return $this->belongsTo(AnnualPlan::class); }
    public function source(): BelongsTo { return $this->belongsTo(MissionSource::class, 'mission_source_id'); }
    public function risk(): BelongsTo { return $this->belongsTo(Risk::class); }
    public function process(): BelongsTo { return $this->belongsTo(Processus::class); }
    public function teams(): HasMany { return $this->hasMany(MissionTeam::class, 'mission_id'); }
}

