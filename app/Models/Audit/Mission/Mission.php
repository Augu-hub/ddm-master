<?php

namespace App\Models\Audit\Mission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mission Model
 * 
 * Table: missions
 * Relation: Many-to-many avec risks via mission_risk
 */
class Mission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'missions';

    protected $fillable = [
        'code',
        'fpm_number',
        'audit_exercise_id',
        'mission_type_id',
        'title',
        'objective',
        'domain',
        'reference_document',
        'priority',
        'planned_start_date',
        'planned_end_date',
        'planned_duration_days',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $dates = [
        'planned_start_date',
        'planned_end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'planned_duration_days' => 'integer',
    ];

    // ✅ RELATIONS

    /**
     * Exercice d'audit (parent)
     */
    public function exercise()
    {
        return $this->belongsTo('App\Models\Audit\AuditExercise', 'audit_exercise_id');
    }

    /**
     * Type de mission (parent)
     */
    public function missionType()
    {
        return $this->belongsTo('App\Models\Audit\Mission\MissionType', 'mission_type_id');
    }

    /**
     * Créateur (utilisateur)
     */
    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * Modifié par (utilisateur)
     */
    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }

    /**
     * RISQUES - Many-to-many via mission_risk
     * 
     * Exemple d'utilisation:
     * $mission->risks()->attach([1, 2, 3]);
     * $mission->risks()->detach(1);
     * $mission->risks()->sync([1, 2, 3]);
     */
    public function risks()
    {
        return $this->belongsToMany(
            'App\Models\Audit\Risk',
            'mission_risk',          // Junction table
            'mission_id',            // Foreign key in junction
            'risk_id'                // Related foreign key
        )->withTimestamps();
    }

    // ✅ SCOPES

    /**
     * Filtrer les missions en brouillon
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'brouillon');
    }

    /**
     * Filtrer les missions planifiées
     */
    public function scopePlanned($query)
    {
        return $query->where('status', 'planifiée');
    }

    /**
     * Filtrer les missions en cours
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'en_cours');
    }

    /**
     * Filtrer par exercice
     */
    public function scopeByExercise($query, $exerciseId)
    {
        return $query->where('audit_exercise_id', $exerciseId);
    }

    /**
     * Filtrer par type
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('mission_type_id', $typeId);
    }

    /**
     * Filtrer par priorité
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // ✅ ACCESSEURS

    /**
     * Label du statut
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'brouillon' => '📝 Brouillon',
            'planifiée' => '📅 Planifiée',
            'en_cours' => '⏳ En cours',
            'terminée' => '✅ Terminée',
            'annulée' => '❌ Annulée',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Label de la priorité
     */
    public function getPriorityLabelAttribute()
    {
        $labels = [
            'basse' => '🟢 Basse',
            'moyenne' => '🟡 Moyenne',
            'haute' => '🟠 Haute',
            'critique' => '🔴 Critique',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }
}