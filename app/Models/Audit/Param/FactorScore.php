<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactorScore extends Model
{
    protected $table = 'audit_mission_factor_scores';

    protected $fillable = [
        'mission_id',
        'factor_id',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public $timestamps = true;

    /**
     * Relations
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(MissionRequest::class, 'mission_id');
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(Factor::class, 'factor_id');
    }
}