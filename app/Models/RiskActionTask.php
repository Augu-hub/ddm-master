<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLES ELOQUENT - Models/RiskAction*.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RiskActionTask extends Model
{
    use SoftDeletes;

    protected $table = 'risk_action_tasks';
    protected $fillable = [
        'tenant_id', 'plan_id', 'title', 'description', 'assigned_to',
        'target_date', 'completion_date', 'status', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completion_date' => 'date',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(RiskActionPlan::class, 'plan_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('target_date', '<', now())
            ->whereNotNull('target_date');
    }
}