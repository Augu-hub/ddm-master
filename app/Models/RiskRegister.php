<?php

namespace App\Models;

use App\Models\RiskCriticalityZone;
use App\Models\RiskFrequencyLevel;
use App\Models\RiskImpactLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskRegister extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_register';

    protected $fillable = [
        'tenant_id',
        'code_risk',
        'libelle',
        'description',
        'entity_id',
        'activity_id',
        'nomenclature_id',
        'causes',
        'consequences',
        'consequences_autres_processus',
        'cout_consequences',
        'controles_existants',
        'owner',
        'entite_partenaire_impliquee',
        'outils_utilises',
        'vraisemblance_apparition',
        'plan_traitement',
        'critere_risque',
        // ── Évaluation inhérente ──────────────────────────────────────────────
        'impact_level_id',
        'frequency_level_id',
        'criticality_score',
        'criticality_zone_id',
        // ── Évaluation résiduelle ─────────────────────────────────────────────
        'residual_impact_level_id',
        'residual_frequency_level_id',
        'residual_criticality_score',
        'residual_criticality_zone_id',
        // ── Évaluation cible ──────────────────────────────────────────────────
        'target_impact_level_id',
        'target_frequency_level_id',
        'target_criticality_score',
        'target_criticality_zone_id',
        'statut',
        'risque_realise',
        'cout_risque',
        'incident_id',
        'moved_to_library_at',
        'created_by',
    ];

    protected $casts = [
        'criticality_score'          => 'decimal:2',
        'residual_criticality_score' => 'decimal:2',
        'target_criticality_score'   => 'decimal:2',
        'cout_risque'                => 'decimal:2',
        'risque_realise'             => 'boolean',
        'moved_to_library_at'        => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeTenant(Builder $q, int $tenantId): Builder
    {
        return $q->where('tenant_id', $tenantId);
    }

    public function scopeDraft(Builder $q): Builder        { return $q->where('statut', 'draft'); }
    public function scopeActif(Builder $q): Builder        { return $q->where('statut', 'actif'); }
    public function scopeArchive(Builder $q): Builder      { return $q->where('statut', 'archive'); }
    public function scopeBibliotheque(Builder $q): Builder { return $q->whereNotNull('moved_to_library_at'); }
    public function scopeRegistre(Builder $q): Builder     { return $q->whereNull('moved_to_library_at'); }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function activity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Activite::class, 'activity_id');
    }

    public function nomenclature(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskNomenclature::class, 'nomenclature_id');
    }

    public function impactLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskImpactLevel::class, 'impact_level_id');
    }

    public function frequencyLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskFrequencyLevel::class, 'frequency_level_id');
    }

    public function criticalityZone(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskCriticalityZone::class, 'criticality_zone_id');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskIncident::class, 'incident_id');
    }

    // ── Relations évaluation résiduelle ───────────────────────────────────────

    public function residualImpactLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskImpactLevel::class, 'residual_impact_level_id');
    }

    public function residualFrequencyLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskFrequencyLevel::class, 'residual_frequency_level_id');
    }

    public function residualCriticalityZone(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskCriticalityZone::class, 'residual_criticality_zone_id');
    }

    // ── Relations évaluation cible ────────────────────────────────────────────

    public function targetImpactLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskImpactLevel::class, 'target_impact_level_id');
    }

    public function targetFrequencyLevel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskFrequencyLevel::class, 'target_frequency_level_id');
    }

    public function targetCriticalityZone(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RiskCriticalityZone::class, 'target_criticality_zone_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            'draft'   => 'Brouillon',
            'actif'   => 'Actif',
            'archive' => 'Archivé',
            default   => ucfirst($this->statut),
        };
    }

    public function getStatutBadgeAttribute(): string
    {
        return match ($this->statut) {
            'draft'   => 'secondary',
            'actif'   => 'success',
            'archive' => 'dark',
            default   => 'secondary',
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateCode(int $tenantId): string
    {
        $year  = now()->format('Y');
        $count = static::on('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->withTrashed()
            ->count();

        return sprintf('RSK-%s-%04d', $year, $count + 1);
    }

    public function computeCriticality(): void
    {
        $this->computeEvaluation(
            $this->impact_level_id,
            $this->frequency_level_id,
            'criticality_score',
            'criticality_zone_id'
        );
    }

    public function computeResidualCriticality(): void
    {
        $this->computeEvaluation(
            $this->residual_impact_level_id,
            $this->residual_frequency_level_id,
            'residual_criticality_score',
            'residual_criticality_zone_id'
        );
    }

    public function computeTargetCriticality(): void
    {
        $this->computeEvaluation(
            $this->target_impact_level_id,
            $this->target_frequency_level_id,
            'target_criticality_score',
            'target_criticality_zone_id'
        );
    }

    // ── Calcul générique ──────────────────────────────────────────────────────

    private function computeEvaluation(
        ?int $impactId,
        ?int $frequencyId,
        string $scoreField,
        string $zoneField
    ): void {
        if (! $impactId || ! $frequencyId) {
            return;
        }

        $impact    = RiskImpactLevel::on('tenant')->find($impactId);
        $frequency = RiskFrequencyLevel::on('tenant')->find($frequencyId);

        if (! $impact || ! $frequency) {
            return;
        }

        $score = $impact->score * $frequency->score;

        // Chercher la zone dans la même config de matrice que le niveau d'impact
        // (impact et fréquence partagent le même matrix_config_id dans cette config)
        $zone = RiskCriticalityZone::on('tenant')
            ->where('tenant_id', $this->tenant_id)
            ->where('matrix_config_id', $impact->matrix_config_id)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();

        $this->update([
            $scoreField => $score,
            $zoneField  => $zone?->id,
        ]);
    }

    public function nomenclatures(): BelongsToMany
    {
        return $this->belongsToMany(
            RiskNomenclature::class,
            'risk_register_nomenclatures',
            'risk_register_id',
            'risk_nomenclature_id'
        );
    }
}
