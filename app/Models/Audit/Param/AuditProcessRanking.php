<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class AuditProcessRanking extends Model
{
    protected $table = 'audit_process_rankings';
    protected $guarded = ['id'];
    protected $casts = [
        'process_id' => 'integer',
        'entity_id' => 'integer',
        'total_score' => 'float',
        'average_score' => 'float',
        'ranking_position' => 'integer',
        'ranking_percentage' => 'float',
        'number_of_evaluations' => 'integer',
        'last_evaluation_date' => 'date',
        'next_review_date' => 'date',
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

    public function scores(): HasMany
    {
        return $this->hasMany(AuditProcessScore::class, 'process_id', 'process_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AuditProcessScoringHistory::class, 'process_id', 'process_id');
    }

    /**
     * Scopes
     */
    public function scopeForEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating_label', $rating);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority_status', $priority);
    }

    public function scopeOrderedByRank($query)
    {
        return $query->orderBy('ranking_position', 'asc');
    }

    public function scopeOrderedByScore($query)
    {
        return $query->orderBy('average_score', 'desc');
    }

    public function scopeCritical($query)
    {
        return $query->where('priority_status', 'Critique');
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority_status', ['Critique', 'Haute']);
    }

    /**
     * Attributs calculés / Accesseurs
     */
    public function getRatingColor(): string
    {
        return match($this->rating_label) {
            'Excellent' => '#2ecc71',     // Vert
            'Très Bon' => '#3498db',     // Bleu
            'Bon' => '#f39c12',           // Orange
            'Moyen' => '#e67e22',         // Orange foncé
            'Faible' => '#e74c3c',        // Rouge
            default => '#95a5a6',         // Gris
        };
    }

    public function getRatingBadgeClass(): string
    {
        return match($this->rating_label) {
            'Excellent' => 'badge-success',
            'Très Bon' => 'badge-info',
            'Bon' => 'badge-warning',
            'Moyen' => 'badge-orange',
            'Faible' => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function getPriorityBadgeClass(): string
    {
        return match($this->priority_status) {
            'Critique' => 'badge-danger',
            'Haute' => 'badge-warning',
            'Moyenne' => 'badge-info',
            'Basse' => 'badge-success',
            default => 'badge-secondary',
        };
    }

    public function getDaysUntilReview(): ?int
    {
        if (!$this->next_review_date) {
            return null;
        }
        return now()->diffInDays($this->next_review_date);
    }

    public function isOverdue(): bool
    {
        return $this->next_review_date && $this->next_review_date < now()->toDateString();
    }
}
