<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Param\Auditor;
use App\Models\Param\UnavailabilityType;

class AuditorUnavailability extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'auditor_unavailabilities';

    protected $fillable = [
        'auditor_id',
        'reason',
        'type',
        'description',
        'notes',
        'date_start',
        'date_end',
        'is_approved',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec l'auditeur
     */
    public function auditor()
    {
        return $this->belongsTo(Auditor::class, 'auditor_id');
    }

    /**
     * Relation avec le type d'indisponibilité
     */
    public function typeModel()
    {
        return $this->belongsTo(UnavailabilityType::class, 'type', 'code');
    }

    /**
     * Scope: indisponibilités approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: indisponibilités en attente
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope: par période
     */
    public function scopeInPeriod($query, $start, $end)
    {
        return $query->where('date_start', '<=', $end)
                     ->where('date_end', '>=', $start);
    }

    /**
     * Scope: par année
     */
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('date_start', $year);
    }

    /**
     * Scope: par auditeur
     */
    public function scopeForAuditor($query, $auditorId)
    {
        return $query->where('auditor_id', $auditorId);
    }

    /**
     * Calculer le nombre de jours
     */
    public function getDaysCountAttribute()
    {
        if (!$this->date_start || !$this->date_end) {
            return 0;
        }
        
        $start = strtotime($this->date_start);
        $end = strtotime($this->date_end);
        
        return ceil(($end - $start) / (60 * 60 * 24)) + 1;
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute()
    {
        if ($this->typeModel) {
            return $this->typeModel->icon . ' ' . $this->typeModel->name;
        }
        return $this->type;
    }

    /**
     * Obtenir la couleur du type
     */
    public function getTypeColorAttribute()
    {
        return $this->typeModel?->color ?? '#667eea';
    }

    /**
     * Vérifier si l'indisponibilité est en cours
     */
    public function getIsActiveAttribute()
    {
        $now = now()->startOfDay();
        $start = \Carbon\Carbon::parse($this->date_start)->startOfDay();
        $end = \Carbon\Carbon::parse($this->date_end)->endOfDay();
        
        return $now->between($start, $end);
    }

    /**
     * Vérifier si l'indisponibilité est à venir
     */
    public function getIsUpcomingAttribute()
    {
        return now()->startOfDay() < \Carbon\Carbon::parse($this->date_start)->startOfDay();
    }

    /**
     * Vérifier si l'indisponibilité est passée
     */
    public function getIsPastAttribute()
    {
        return now()->startOfDay() > \Carbon\Carbon::parse($this->date_end)->endOfDay();
    }

    /**
     * Obtenir le statut textuel
     */
    public function getStatusLabelAttribute()
    {
        if (!$this->is_approved) {
            return 'En attente';
        }
        
        if ($this->is_past) {
            return 'Terminé';
        }
        
        if ($this->is_active) {
            return 'En cours';
        }
        
        return 'À venir';
    }

    /**
     * Obtenir la classe CSS du statut
     */
    public function getStatusClassAttribute()
    {
        if (!$this->is_approved) {
            return 'warning';
        }
        
        if ($this->is_past) {
            return 'secondary';
        }
        
        if ($this->is_active) {
            return 'success';
        }
        
        return 'info';
    }
}