<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class MissionTeam extends Model
{
    protected $table = 'audit_mission_teams';
    protected $fillable = ['entity_id', 'mission_id', 'user_id', 'function_id', 'role'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function function(): BelongsTo { return $this->belongsTo(AuditFunction::class); }
}