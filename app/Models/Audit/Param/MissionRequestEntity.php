<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};


class MissionRequestEntity extends Model
{
    protected $table = 'audit_mission_request_entities';
    protected $fillable = ['entity_id', 'mission_request_id', 'audited_entity_id'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function missionRequest(): BelongsTo { return $this->belongsTo(MissionRequest::class); }
    public function auditedEntity(): BelongsTo { return $this->belongsTo(Entite::class, 'audited_entity_id'); }
}
