<?php

namespace App\Models\Audit;

use App\Models\Param\Activite;
use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Risk extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'risks';
    
    protected $fillable = [
        'tenant_id',
        'project_id',
        'audit_session_id',
        'entity_id',
        'process_id',
        'activity_id',
        'risk_type_id',
        'code',
        'label',
        'description',
        'activity_code',
        'frequency_level_id',
        'frequency_net',
        'impact_level_id',
        'impact_net',
        'criticality',
        'owner',
        'control_procedure',
        'status',
        'year',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'frequency_net' => 'decimal:1',
        'impact_net' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🔗 Session d'audit parente
     */
    public function auditSession(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class, 'audit_session_id');
    }

    /**
     * 🔗 Type de risque
     */
    public function riskType(): BelongsTo
    {
        return $this->belongsTo(RiskType::class, 'risk_type_id');
    }

    /**
     * 🔗 Niveau de fréquence (nouvelle table: audit_frequency_levels)
     */
    public function frequencyLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AuditFrequencyLevel::class, 'frequency_level_id');
    }

    /**
     * 🔗 Niveau d'impact (nouvelle table: audit_impact_levels)
     */
    public function impactLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AuditImpactLevel::class, 'impact_level_id');
    }

    /**
     * 🔗 Entité
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entite::class, 'entity_id');
    }

    /**
     * 🔗 Processus
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Processus::class, 'process_id');
    }

    /**
     * 🔗 Activité
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activite::class, 'activity_id');
    }

    /**
     * 📚 Contrôles du risque
     */
    public function controls(): HasMany
    {
        return $this->hasMany(RiskControl::class);
    }

    /**
     * 📋 Plans d'atténuation
     */
    public function mitigations(): HasMany
    {
        return $this->hasMany(RiskMitigation::class);
    }

    /**
     * 📊 Évaluations/Audits
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    /**
     * 📈 Indicateurs activité
     */
    public function activityIndicators(): HasMany
    {
        return $this->hasMany(RiskActivityIndicator::class);
    }

    /**
     * 📈 Indicateurs performance
     */
    public function performanceIndicators(): HasMany
    {
        return $this->hasMany(RiskPerformanceIndicator::class);
    }

    /**
     * 📝 Logs d'audit
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(RiskAuditLog::class, 'entity_id')->where('entity_type', 'Risk');
    }

    /**
     * 👤 Créé par
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * 👤 Modifié par
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🔍 Par session d'audit
     */
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('audit_session_id', $sessionId);
    }

    /**
     * 🔍 Par activité
     */
    public function scopeByActivity($query, $activityId)
    {
        return $query->where('activity_id', $activityId);
    }

    /**
     * 🔍 Par processus
     */
    public function scopeByProcess($query, $processId)
    {
        return $query->where('process_id', $processId);
    }

    /**
     * 🔍 Par type de risque
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('risk_type_id', $typeId);
    }

    /**
     * 🔍 Par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 🔴 Risques critiques (score >= 15)
     */
    public function scopeCritical($query)
    {
        return $query->where('criticality', '>=', 15);
    }

    /**
     * 🟠 Risques élevés (12-14)
     */
    public function scopeHigh($query)
    {
        return $query->whereBetween('criticality', [12, 14]);
    }

    /**
     * 🟡 Risques modérés (6-11)
     */
    public function scopeMedium($query)
    {
        return $query->whereBetween('criticality', [6, 11]);
    }

    /**
     * 🟢 Risques faibles (< 6)
     */
    public function scopeLow($query)
    {
        return $query->where('criticality', '<', 6);
    }

    /**
     * 🔎 Recherche texte
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('code', 'like', "%{$term}%")
                     ->orWhere('label', 'like', "%{$term}%")
                     ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * 📅 Par année
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // ACCESSORS & MUTATORS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🎨 Libellé de criticité
     */
    public function getCriticalityLabel(): string
    {
        $c = $this->criticality ?? 0;
        return match (true) {
            $c <= 5 => '🟢 Faible',
            $c <= 11 => '🟡 Moyen',
            $c <= 16 => '🟠 Élevé',
            default => '🔴 Critique',
        };
    }

    /**
     * 🎨 Couleur badge criticité
     */
    public function getCriticalityColor(): string
    {
        $c = $this->criticality ?? 0;
        return match (true) {
            $c <= 5 => 'success',
            $c <= 11 => 'warning',
            $c <= 16 => 'danger',
            default => 'dark',
        };
    }

    /**
     * 🎨 Code couleur HEX pour criticité
     */
    public function getCriticalityHexColor(): string
    {
        $c = $this->criticality ?? 0;
        return match (true) {
            $c <= 5 => '#28a745',      // 🟢 Vert
            $c <= 11 => '#ffc107',     // 🟡 Jaune
            $c <= 16 => '#fd7e14',     // 🟠 Orange
            default => '#dc3545',       // 🔴 Rouge
        };
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // MÉTHODES DE CALCUL
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 📊 Calcule la criticité (fréquence × impact)
     */
    public function calculateCriticality(): int
    {
        if (!$this->frequency_level_id || !$this->impact_level_id) {
            return 0;
        }

        $frequency = \App\Models\AuditFrequencyLevel::find($this->frequency_level_id);
        $impact = \App\Models\AuditImpactLevel::find($this->impact_level_id);

        if (!$frequency || !$impact) {
            return 0;
        }

        return $frequency->level * $impact->level;
    }

    /**
     * 📊 Calcule la criticité nette (après contrôles)
     */
    public function calculateNetCriticality(): ?int
    {
        if (!$this->frequency_net || !$this->impact_net) {
            return null;
        }
        return (int) ceil($this->frequency_net * $this->impact_net);
    }

    /**
     * 📉 Reste d'exposition = Criticité brute - nette
     */
    public function getResidualExposure(): int
    {
        $net = $this->calculateNetCriticality();
        if (!$net) {
            return $this->criticality ?? 0;
        }
        return max(0, ($this->criticality ?? 0) - $net);
    }

    /**
     * 📊 Taux de réduction (%)
     */
    public function getRiskReductionRate(): float
    {
        if (!$this->criticality || $this->criticality === 0) {
            return 0;
        }
        $net = $this->calculateNetCriticality();
        if (!$net) {
            return 0;
        }
        return round((1 - ($net / $this->criticality)) * 100, 2);
    }

    /**
     * ✅ Évalue le risque
     */
    public function evaluate(
        int $frequencyLevelId,
        int $impactLevelId,
        ?float $frequencyNet = null,
        ?float $impactNet = null,
        ?string $justification = null
    ): bool {
        $newCriticality = $frequencyLevelId * $impactLevelId;

        return $this->update([
            'frequency_level_id' => $frequencyLevelId,
            'impact_level_id' => $impactLevelId,
            'frequency_net' => $frequencyNet,
            'impact_net' => $impactNet,
            'criticality' => $newCriticality,
            'status' => 'assessed',
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * 🔄 Change le statut
     */
    public function changeStatus(string $newStatus): bool
    {
        if (!in_array($newStatus, ['identified', 'assessed', 'mitigated', 'monitored', 'closed'])) {
            return false;
        }

        return $this->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * 📋 Contexte complet du risque
     */
    public function getFullContext(): array
    {
        return [
            'risk' => $this,
            'session' => $this->auditSession,
            'entity' => $this->entity,
            'process' => $this->process,
            'activity' => $this->activity,
            'type' => $this->riskType,
            'frequency_level' => $this->frequencyLevel,
            'impact_level' => $this->impactLevel,
            'controls' => $this->controls()->get(),
            'mitigations' => $this->mitigations()->get(),
            'assessments' => $this->assessments()->latest()->limit(5)->get(),
        ];
    }
}