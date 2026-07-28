<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Session (campagne) d'évaluation des risques.
 *
 * C'est la table cible du `session_id` déjà présent dans
 * risk_entity_assignments*. Une session gèle l'état du registre
 * (risk_session_snapshots) pour permettre la comparaison d'évolution.
 */
class RiskSession extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_sessions';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'year', 'status', 'is_active',
        'parent_session_id', 'matrix_config_id', 'started_at', 'closed_at',
        'snapshot_at', 'risks_count', 'notes', 'report_json', 'created_by',
    ];

    protected $casts = [
        'year'        => 'integer',
        'is_active'   => 'boolean',
        'risks_count' => 'integer',
        'started_at'  => 'datetime',
        'closed_at'   => 'datetime',
        'snapshot_at' => 'datetime',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(RiskSessionSnapshot::class, 'session_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RiskSession::class, 'parent_session_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RiskSession::class, 'parent_session_id');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active cette session et désactive les autres du même tenant. */
    public function activate(): void
    {
        static::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true, 'status' => 'active']);
    }
}
