<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IaProposition extends Model
{
    protected $fillable = [
        'item_id',
        'contenu',
        'type',
        'priorite',
        'actions',
        'indicateurs',
        'echeance',
        'generated_by',
        'generated_at',
        'is_accepted',
        'accepted_by',
        'accepted_at',
    ];

    protected $casts = [
        'actions'      => 'array',
        'indicateurs'  => 'array',
        'generated_at' => 'datetime',
        'accepted_at'  => 'datetime',
        'is_accepted'  => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(AnalyseConformiteItem::class, 'item_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function getBadgeTypeAttribute(): string
    {
        return match ($this->type) {
            'alerte'       => '<span class="badge bg-danger">Alerte</span>',
            'amelioration' => '<span class="badge bg-warning text-dark">Amélioration</span>',
            'validation'   => '<span class="badge bg-success">Validation</span>',
            default        => '<span class="badge bg-secondary">' . $this->type . '</span>',
        };
    }

    public function getBadgePrioriteAttribute(): string
    {
        return match ($this->priorite) {
            'haute'   => '<span class="badge bg-danger">Haute</span>',
            'moyenne' => '<span class="badge bg-warning text-dark">Moyenne</span>',
            'faible'  => '<span class="badge bg-success">Faible</span>',
            default   => '<span class="badge bg-secondary">' . $this->priorite . '</span>',
        };
    }
}