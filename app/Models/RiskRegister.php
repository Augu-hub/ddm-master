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
        'controles_existants',
        'owner',
        'plan_traitement',
        'impact_level_id',
        'frequency_level_id',
        'criticality_score',
        'criticality_zone_id',
        'statut',
        'incident_id',
        'created_by',
    ];

    protected $casts = [
        'criticality_score' => 'decimal:2',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeTenant(Builder $q, int $tenantId): Builder
    {
        return $q->where('tenant_id', $tenantId);
    }

    public function scopeDraft(Builder $q): Builder   { return $q->where('statut', 'draft'); }
    public function scopeActif(Builder $q): Builder   { return $q->where('statut', 'actif'); }
    public function scopeArchive(Builder $q): Builder { return $q->where('statut', 'archive'); }

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
        if (! $this->impact_level_id || ! $this->frequency_level_id) {
            return;
        }

        $impact    = RiskImpactLevel::on('tenant')->find($this->impact_level_id);
        $frequency = RiskFrequencyLevel::on('tenant')->find($this->frequency_level_id);

        if (! $impact || ! $frequency) {
            return;
        }

        $score = $impact->score * $frequency->score;

        $zone = RiskCriticalityZone::on('tenant')
            ->where('tenant_id', $this->tenant_id)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();

        $this->update([
            'criticality_score'   => $score,
            'criticality_zone_id' => $zone?->id,
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
