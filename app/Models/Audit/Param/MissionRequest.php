<?php

namespace App\Models\Audit\Param;

use App\Models\Audit\AuditExercise;
use App\Models\Param\Entite;
use App\Models\Param\Processus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionRequest extends Model
{
    protected $table = 'audit_mission_requests';
    
    protected $fillable = [
        'entity_id',
        'code',
        'share_code',
        'mission_type',
        'mission_objective',
        'audit_scope',
        'related_process_id',
        'frequency',
        'requester_id',
        'requester_email',
        'requester_motif',
        'requested_date',
        'proposed_date',
        'status',
        'risk',
        'concern',
        'result',
        'procedure',
        'autre',
        'description',
        'start_date',
        'end_date',
        'filled_by_id',
        'filled_by_email',
        'filled_by_name',
        'filled_at',
        'coefficient',
        'level',
        'priority_notes',
        'exercise_id',  // ← NOUVEAU
    ];

    protected $casts = [
        'requested_date' => 'date',
        'proposed_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'filled_at' => 'datetime',
        'coefficient' => 'float',
    ];

    // ===== RELATIONS =====

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processus::class, 'related_process_id');
    }

    public function filledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by_id');
    }

     public function entity1(): BelongsTo
    {
        return $this->belongsTo(Entite::class, 'entity_id');
    }

    /**
     * ✅ NOUVELLE RELATION: Exercice d'audit
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(AuditExercise::class, 'exercise_id');
    }

    /**
     * ✅ RELATION: factorScores
     */
    public function factorScores(): HasMany
    {
        return $this->hasMany(FactorScore::class, 'mission_id');
    }

    // ===== SCOPES =====

    public function scopeEvaluated($query)
    {
        return $query->whereNotNull('coefficient')->where('coefficient', '>', 0);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('coefficient', 'desc')->orderBy('code', 'asc');
    }

    public function scopeByExercise($query, $exerciseId)
    {
        return $query->where('exercise_id', $exerciseId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // ===== HELPERS =====

    public function calculateCoefficient()
    {
        $scores = $this->factorScores()->pluck('score');
        
        if ($scores->isEmpty()) {
            return 0;
        }

        return round($scores->sum() / $scores->count(), 2);
    }

    public function determineLevelFromCoefficient()
    {
        $coeff = $this->coefficient ?? 0;

        if ($coeff >= 3.0) return 'Critique';
        if ($coeff >= 2.0) return 'Considérable';
        if ($coeff >= 1.0) return 'Important';
        return 'Mineur';
    }

    public function updatePriorization()
    {
        $this->coefficient = $this->calculateCoefficient();
        $this->level = $this->determineLevelFromCoefficient();
        $this->save();

        return $this;
    }

    public function getFactorScore($factorId)
    {
        return $this->factorScores()
            ->where('factor_id', $factorId)
            ->first()
            ?->score ?? null;
    }

    public function getAllScoresAsArray()
    {
        return $this->factorScores()
            ->pluck('score', 'factor_id')
            ->toArray();
    }
}