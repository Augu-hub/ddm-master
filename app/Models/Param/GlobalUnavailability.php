<?php

// ========================================================================
// app/Models/Param/GlobalUnavailability.php - Indisponibilités GLOBALES
// ========================================================================

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalUnavailability extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'global_unavailabilities';

    protected $fillable = [
        'name',
        'reason',
        'date_start',
        'date_end',
        'type', // 'holiday', 'vacation', 'maintenance', etc.
        'description',
        'is_active',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ====== SCOPES ======

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date_start', [$startDate, $endDate])
                ->orWhereBetween('date_end', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('date_start', '<=', $startDate)
                        ->where('date_end', '>=', $endDate);
                });
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('reason', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    // ====== ACCESSORS ======

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? ['text' => 'Actif', 'variant' => 'success'] : ['text' => 'Inactif', 'variant' => 'secondary'];
    }

    public function getDurationDaysAttribute()
    {
        return $this->date_end->diffInDays($this->date_start) + 1;
    }

    public function getTypeNameAttribute()
    {
        return match ($this->type) {
            'holiday' => 'Jour Férié',
            'vacation' => 'Congés',
            'maintenance' => 'Maintenance',
            'training' => 'Formation',
            'other' => 'Autre',
            default => ucfirst($this->type),
        };
    }
}

// ========================================================================
// app/Models/Param/AuditorUnavailability.php - Indisponibilités AUDITEUR
// ========================================================================

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditorUnavailability extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'auditor_unavailabilities';

    protected $fillable = [
        'auditor_id',
        'reason',
        'date_start',
        'date_end',
        'type', // 'sick', 'vacation', 'training', 'leave', 'other'
        'description',
        'is_approved',
        'approved_by',
        'approved_at',
        'notes',
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

    // ====== RELATIONS ======

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(Auditor::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Auditor::class, 'approved_by');
    }

    // ====== SCOPES ======

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeByAuditor($query, $auditorId)
    {
        return $query->where('auditor_id', $auditorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date_start', [$startDate, $endDate])
                ->orWhereBetween('date_end', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('date_start', '<=', $startDate)
                        ->where('date_end', '>=', $endDate);
                });
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('reason', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhereHas('auditor', function ($q2) use ($search) {
                    $q2->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                });
        });
    }

    // ====== ACCESSORS ======

    public function getStatusBadgeAttribute()
    {
        if ($this->is_approved) {
            return ['text' => 'Approuvé', 'variant' => 'success'];
        }
        return ['text' => 'En attente', 'variant' => 'warning'];
    }

    public function getDurationDaysAttribute()
    {
        return $this->date_end->diffInDays($this->date_start) + 1;
    }

    public function getTypeNameAttribute()
    {
        return match ($this->type) {
            'sick' => 'Congé maladie',
            'vacation' => 'Congés',
            'training' => 'Formation',
            'leave' => 'Congé',
            'other' => 'Autre',
            default => ucfirst($this->type),
        };
    }
}