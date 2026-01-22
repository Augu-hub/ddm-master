<?php

namespace App\Models\Audit\Mission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;


class MissionPhaseAssignment extends Model
{
    protected $table = 'mission_phase_assignments';
    protected $fillable = [
        'mission_id', 'mission_phase_id', 'status', 'progress', 'actual_weight',
        'start_date', 'end_date', 'planned_start', 'planned_end',
        'owner_id', 'team_members', 'description', 'findings', 'conclusions', 'attachments'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'planned_start' => 'date',
        'planned_end' => 'date',
        'team_members' => 'json',
        'attachments' => 'json',
        'progress' => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(MissionPhase::class, 'mission_phase_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function isLate(): bool
    {
        return $this->planned_end && now()->isAfter($this->planned_end) && $this->status !== 'completed';
    }

    public function getDaysRemaining(): ?int
    {
        return $this->planned_end ? now()->diffInDays($this->planned_end, false) : null;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => '#6c757d',
            'started' => '#0dcaf0',
            'in_progress' => '#0d6efd',
            'completed' => '#198754',
            'skipped' => '#ffc107',
            'cancelled' => '#dc3545',
            default => '#6c757d'
        };
    }

    public function scopeByMission(Builder $query, int $missionId): Builder
    {
        return $query->where('mission_id', $missionId);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}