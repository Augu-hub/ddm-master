<?php

namespace App\Models\Audit\Mission;

use App\Models\Param\Entite;
use App\Models\Param\Risk;
use App\Models\Param\Processus;
use App\Models\Param\Competency;
use App\Models\Audit\AuditExercise;
use App\Models\Audit\Mission\MissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuditMission extends Model
{
    use HasFactory;

    /**
     * Nom de la table
     */
    protected $table = 'audit_missions';

    /**
     * Les attributs assignables en masse
     */
    protected $fillable = [
        'entity_id',
        'code',
        'audit_exercise_id',
        'mission_type_id',
        'annual_plan_id',
        'mission_source_id',
        'risk_id',
        'process_id',
        'title',
        'objective',
        'but',
        'description',
        'preoccupation',
        'resultat',
        'champ_mission',
        'fonction_processus',
        'procedure',
        'domain',
        'reference_document',
        'priority',
        'priority_rank',
        'criticality',
        'scheduled_start_date',
        'scheduled_end_date',
        'planned_start_date',
        'planned_end_date',
        'planned_duration_days',
        'actual_start_date',
        'actual_end_date',
        'budget',
        'status',
        'findings',
        'recommendations',
        'fpm_reference',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Les attributs à caster
     */
    protected $casts = [
        'scheduled_start_date' => 'date',
        'scheduled_end_date' => 'date',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'priority_rank' => 'integer',
        'criticality' => 'integer',
        'budget' => 'decimal:2',
        'planned_duration_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les valeurs par défaut
     */
    protected $attributes = [
        'status' => 'draft',
        'priority' => 'moyenne',
        'criticality' => 0,
        'priority_rank' => 0,
    ];

    /**
     * Relation avec l'entité
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entite::class, 'entity_id');
    }

    /**
     * Relation avec l'exercice d'audit
     */
    public function auditExercise(): BelongsTo
    {
        return $this->belongsTo(AuditExercise::class, 'audit_exercise_id');
    }

    /**
     * Relation avec le type de mission
     */
    public function missionType(): BelongsTo
    {
        return $this->belongsTo(MissionType::class, 'mission_type_id');
    }

    /**
     * Relation avec le risque principal
     */
    public function mainRisk(): BelongsTo
    {
        return $this->belongsTo(Risk::class, 'risk_id');
    }

    /**
     * Relation avec le processus
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Processus::class, 'process_id');
    }

    /**
     * Relation avec les risques (plusieurs)
     */
    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'audit_mission_risks')
                    ->withPivot(['created_at', 'updated_at'])
                    ->withTimestamps();
    }

    /**
     * Relation avec les compétences
     */
    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class, 'audit_mission_competencies')
                    ->withPivot(['created_at', 'updated_at'])
                    ->withTimestamps();
    }

    /**
     * Relation avec le créateur
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relation avec le modificateur
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Scopes pour filtrer par statut
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Générer un code de mission automatique
     */
    public static function generateCode($missionTypeCode, $exerciseYear, $entityCode = null): string
    {
        $year = substr($exerciseYear, -2);
        
        // Compter les missions existantes pour ce type et année
        $count = self::where('code', 'like', "{$missionTypeCode}-%{$year}")
                     ->count();
        
        $nextNumber = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        $code = "{$missionTypeCode}-{$nextNumber}-{$year}";
        
        // Ajouter l'entité si fournie
        if ($entityCode) {
            $code = "{$entityCode}-{$code}";
        }
        
        return $code;
    }

    /**
     * Calculer la durée prévue
     */
    public function calculatePlannedDuration(): int
    {
        if (!$this->planned_start_date || !$this->planned_end_date) {
            return 0;
        }
        
        $start = $this->planned_start_date;
        $end = $this->planned_end_date;
        
        return $start->diffInDays($end);
    }

    /**
     * Vérifier si la mission est en retard
     */
    public function isOverdue(): bool
    {
        if (!$this->planned_end_date || $this->status === 'completed') {
            return false;
        }
        
        return now()->greaterThan($this->planned_end_date);
    }

    /**
     * Obtenir le niveau de priorité sous forme de badge HTML
     */
    public function getPriorityBadge(): string
    {
        $badges = [
            'basse' => '<span class="badge bg-success">Basse</span>',
            'moyenne' => '<span class="badge bg-warning">Moyenne</span>',
            'haute' => '<span class="badge bg-orange">Haute</span>',
            'critique' => '<span class="badge bg-danger">Critique</span>',
        ];
        
        return $badges[$this->priority] ?? '<span class="badge bg-secondary">Inconnue</span>';
    }

    /**
     * Obtenir le statut sous forme de badge HTML
     */
    public function getStatusBadge(): string
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Brouillon</span>',
            'scheduled' => '<span class="badge bg-primary">Planifié</span>',
            'approved' => '<span class="badge bg-success">Approuvé</span>',
            'in_progress' => '<span class="badge bg-info">En cours</span>',
            'completed' => '<span class="badge bg-success">Terminé</span>',
            'cancelled' => '<span class="badge bg-danger">Annulé</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-light text-dark">Inconnu</span>';
    }

    /**
     * Formater les dates pour l'affichage
     */
    public function getFormattedDates(): string
    {
        $start = $this->planned_start_date 
            ? $this->planned_start_date->format('d/m/Y') 
            : 'Non défini';
        
        $end = $this->planned_end_date 
            ? $this->planned_end_date->format('d/m/Y') 
            : 'Non défini';
        
        return "{$start} - {$end}";
    }
}