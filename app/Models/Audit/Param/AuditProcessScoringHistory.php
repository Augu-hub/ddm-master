<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class AuditProcessScoringHistory extends Model
{
    protected $table = 'audit_process_scoring_history';
    protected $guarded = ['id'];
    protected $casts = [
        'process_id' => 'integer',
        'entity_id' => 'integer',
        'factor_id' => 'integer',
        'old_score' => 'integer',
        'new_score' => 'integer',
        'old_average' => 'float',
        'new_average' => 'float',
        'old_ranking' => 'integer',
        'new_ranking' => 'integer',
        'changed_by' => 'integer',
        'change_date' => 'date',
    ];

    protected $timestamps = false;

    /**
     * Relations
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(AuditFactor::class, 'factor_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }

    /**
     * Scopes
     */
    public function scopeForProcess($query, $processId)
    {
        return $query->where('process_id', $processId);
    }

    public function scopeForEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->whereBetween('change_date', [
            now()->subDays($days)->toDateString(),
            now()->toDateString()
        ]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Calculs
     */
    public function getScoreChange(): ?int
    {
        if ($this->old_score === null || $this->new_score === null) {
            return null;
        }
        return $this->new_score - $this->old_score;
    }

    public function getAverageChange(): ?float
    {
        if ($this->old_average === null || $this->new_average === null) {
            return null;
        }
        return round($this->new_average - $this->old_average, 2);
    }

    public function getRankingChange(): ?int
    {
        if ($this->old_ranking === null || $this->new_ranking === null) {
            return null;
        }
        return $this->old_ranking - $this->new_ranking; // Positif = amélioration
    }

    public function getChangeDirection(): string
    {
        $change = $this->getScoreChange();
        if ($change === null) return '—';
        if ($change > 0) return '↑ Hausse';
        if ($change < 0) return '↓ Baisse';
        return '= Inchangé';
    }
}

