<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ════════════════════════════════════════════════════════════════════════════════════
 * 📅 AUDIT EXERCISE MODEL - Exercice d'Audit
 * ════════════════════════════════════════════════════════════════════════════════════
 * 
 * Table: audit_exercises
 * Représente un exercice d'audit complet avec objectives, scope, methodology
 */
class AuditExercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'audit_exercises';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'year',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'objectives',
        'scope',
        'methodology',
        'manager_id',
        'created_by',
        'total_sessions',
        'completed_sessions',
        'total_risks_identified',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Un exercice a plusieurs sessions
     */
    public function auditSessions(): HasMany
    {
        return $this->hasMany(AuditSession::class, 'exercise_id');
    }

    /**
     * ✅ Manager responsable
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * ✅ Créateur
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Exercices actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ Filtre par tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * ✅ Filtre par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * ✅ Filtre par année
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * ✅ Ordonné par date de création
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * ✅ Recherche par code ou nom
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('code', 'like', "%{$term}%")
                     ->orWhere('name', 'like', "%{$term}%");
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // ACCESSORS & MUTATORS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Libellé statut
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => '📝 Brouillon',
            'planning' => '📋 Planification',
            'in_progress' => '🔄 En cours',
            'closing' => '📊 Clôture',
            'closed' => '✔️ Clôturé',
            'completed' => '✅ Complété',
            default => $this->status,
        };
    }

    /**
     * ✅ Badge couleur statut
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'planning' => 'info',
            'in_progress' => 'primary',
            'closing' => 'warning',
            'closed' => 'danger',
            'completed' => 'success',
            default => 'light',
        };
    }

    /**
     * ✅ Taux de progression (%)
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_sessions === 0) {
            return 0;
        }
        return (int) (($this->completed_sessions / $this->total_sessions) * 100);
    }

    /**
     * ✅ Est en cours?
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress' && $this->is_active;
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // MÉTHODES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Mise à jour stats (à appeler après changement session)
     */
    public function updateStats(): void
    {
        $this->update([
            'total_sessions' => $this->auditSessions()->count(),
            'completed_sessions' => $this->auditSessions()
                ->where('status', 'closed')
                ->count(),
            'total_risks_identified' => $this->auditSessions()
                ->sum('total_risks_created'),
        ]);
    }

    /**
     * ✅ Résumé pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'year' => $this->year,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'is_active' => $this->is_active,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'objectives' => $this->objectives,
            'scope' => $this->scope,
            'methodology' => $this->methodology,
            'total_sessions' => $this->total_sessions,
            'completed_sessions' => $this->completed_sessions,
            'progress_percentage' => $this->getProgressPercentage(),
            'total_risks_identified' => $this->total_risks_identified,
            'manager_id' => $this->manager_id,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}