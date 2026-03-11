<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class AuditProcessScore extends Model
{
    protected $table = 'audit_process_scores';
    protected $guarded = ['id'];
    protected $casts = [
        'process_id' => 'integer',
        'entity_id' => 'integer',
        'factor_id' => 'integer',
        'score' => 'integer',
        'score_date' => 'date',
        'evaluator_id' => 'integer',
    ];

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

    public function scale()
    {
        return $this->belongsTo(AuditFactorScale::class, 'score', 'value');
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

    public function scopeByFactor($query, $factorId)
    {
        return $query->where('factor_id', $factorId);
    }

    public function scopeCurrentYear($query)
    {
        return $query->whereYear('score_date', now()->year);
    }

    public function scopeScored($query)
    {
        return $query->whereNotNull('score');
    }
}