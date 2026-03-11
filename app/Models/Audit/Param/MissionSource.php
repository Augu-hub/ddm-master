<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};


class MissionSource extends Model
{
    protected $table = 'audit_mission_sources';
    protected $fillable = ['entity_id', 'code', 'label', 'description'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function missionRequests(): HasMany { return $this->hasMany(MissionRequest::class, 'mission_source_id'); }
    public function missions(): HasMany { return $this->hasMany(Mission::class, 'mission_source_id'); }
}

