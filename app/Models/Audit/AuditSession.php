<?php

namespace App\Models\Audit;

use App\Models\Param\Entite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ════════════════════════════════════════════════════════════════════════════════════
 * 🎯 AUDIT SESSION MODEL - Session d'Audit
 * ════════════════════════════════════════════════════════════════════════════════════
 * 
 * Table: audit_sessions
 * Représente une session de création/édition de risques dans un exercice
 * Une seule session ACTIVE par tenant à la fois
 */
class AuditSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'audit_sessions';

    protected $fillable = [
        'tenant_id',
        'exercise_id',
        'entity_id',
        'created_by',
        'code',
        'name',
        'description',
        'year',
        'session_date',
        'start_date',
        'end_date',
        'status',
        'is_validated',
        'validated_at',
        'validated_by',
        'total_risks_created',
        'total_risks_validated',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'session_date' => 'date',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Appartient à un exercice d'audit
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(AuditExercise::class, 'exercise_id');
    }

    /**
     * ✅ Une session a plusieurs risques
     */
    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class, 'audit_session_id');
    }

    /**
     * ✅ Entité auditée
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entite::class, 'entity_id');
    }

    /**
     * ✅ Créateur de session
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ✅ Validateur
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Sessions actives (status = active)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * ✅ Sessions en pause
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * ✅ Sessions clôturées
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * ✅ Sessions validées
     */
    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    /**
     * ✅ Filtre par tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * ✅ Filtre par exercice
     */
    public function scopeByExercise($query, $exerciseId)
    {
        return $query->where('exercise_id', $exerciseId);
    }

    /**
     * ✅ Filtre par entité
     */
    public function scopeByEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * ✅ Ordonner par date de création
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
            'active' => '✅ Actif',
            'paused' => '⏸️ En pause',
            'closed' => '✔️ Clôturé',
            'cancelled' => '❌ Annulé',
            default => $this->status,
        };
    }

    /**
     * ✅ Badge couleur statut
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'paused' => 'warning',
            'closed' => 'info',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * ✅ Est actif?
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * ✅ Est validé?
     */
    public function isValidated(): bool
    {
        return $this->is_validated === true;
    }

    /**
     * ✅ Nom de l'exercice
     */
    public function getExerciseName(): string
    {
        return $this->exercise?->name ?? 'N/A';
    }

    /**
     * ✅ Nom de l'entité
     */
    public function getEntityName(): string
    {
        return $this->entity?->name ?? 'N/A';
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // MÉTHODES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Active cette session (désactive les autres du même tenant)
     */
    public function activate(): self
    {
        // Désactiver les autres sessions du même tenant
        self::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->update(['status' => 'paused']);

        // Activer celle-ci
        $this->update(['status' => 'active']);

        return $this;
    }

    /**
     * ✅ Mettre en pause
     */
    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    /**
     * ✅ Clôturer la session
     */
    public function close(): bool
    {
        return $this->update(['status' => 'closed']);
    }

    /**
     * ✅ Annuler la session
     */
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    /**
     * ✅ Valider la session
     */
    public function validate(): bool
    {
        return $this->update([
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);
    }

    /**
     * ✅ Nombre total de risques
     */
    public function getRisksCount(): int
    {
        return $this->risks()->count();
    }

    /**
     * ✅ Risques critiques (criticality_gross >= 12)
     */
    public function getCriticalRisksCount(): int
    {
        return $this->risks()
            ->where('criticality_gross', '>=', 12)
            ->count();
    }

    /**
     * ✅ Risques élevés (8-11)
     */
    public function getHighRisksCount(): int
    {
        return $this->risks()
            ->whereBetween('criticality_gross', [8, 11])
            ->count();
    }

    /**
     * ✅ Risques moyens (5-7)
     */
    public function getMediumRisksCount(): int
    {
        return $this->risks()
            ->whereBetween('criticality_gross', [5, 7])
            ->count();
    }

    /**
     * ✅ Risques faibles (<5)
     */
    public function getLowRisksCount(): int
    {
        return $this->risks()
            ->where('criticality_gross', '<', 5)
            ->count();
    }

    /**
     * ✅ Criticité moyenne
     */
    public function getAverageCriticality(): float
    {
        return $this->risks()
            ->whereNotNull('criticality_gross')
            ->avg('criticality_gross') ?? 0;
    }

    /**
     * ✅ Mise à jour des compteurs
     */
    public function updateCounters(): void
    {
        $this->update([
            'total_risks_created' => $this->getRisksCount(),
            'total_risks_validated' => $this->risks()
                ->where('status', '!=', 'identified')
                ->count(),
        ]);
    }

    /**
     * ✅ Résumé complet
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'exercise_name' => $this->getExerciseName(),
            'entity_name' => $this->getEntityName(),
            'is_validated' => $this->is_validated,
            'total_risks_created' => $this->total_risks_created,
            'total_risks_validated' => $this->total_risks_validated,
            'critical_risks' => $this->getCriticalRisksCount(),
            'high_risks' => $this->getHighRisksCount(),
            'medium_risks' => $this->getMediumRisksCount(),
            'low_risks' => $this->getLowRisksCount(),
            'average_criticality' => round($this->getAverageCriticality(), 2),
        ];
    }

    /**
     * ✅ Pour affichage rapide
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'exercise_name' => $this->getExerciseName(),
            'entity_name' => $this->getEntityName(),
            'risks_count' => $this->total_risks_created,
            'critical_count' => $this->getCriticalRisksCount(),
        ];
    }
}