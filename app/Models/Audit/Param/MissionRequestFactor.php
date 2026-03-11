<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class MissionRequestFactor extends Model
{
    protected $table = 'audit_mission_request_factors';
    protected $fillable = ['entity_id', 'mission_request_id', 'factor_id', 'score'];
    protected $casts = ['score' => 'integer'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function missionRequest(): BelongsTo { return $this->belongsTo(MissionRequest::class); }
    public function factor(): BelongsTo { return $this->belongsTo(Factor::class); }
}

