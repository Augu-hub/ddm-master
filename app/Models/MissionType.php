<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Référentiel CENTRAL — table `mission_types` de la base `ddmparam` :
 * les 5 phases du cycle de vie d'une mission d'audit
 * (PREPARATION | REALISATION | CONCLUSION | SUIVI | RECOMMANDATION).
 *
 * ⚠️ NE PAS CONFONDRE avec App\Models\Audit\Mission\MissionType, qui est le
 *    modèle TENANT (connexion `tenant`) : les programmes d'audit propres au
 *    client (ex. AGC, AMP…) rattachés à un audit_type_code central.
 *
 *    Observé par MissionTypeObserver : toute écriture Eloquent déclenche la
 *    synchro automatique vers tous les tenants.
 */
class MissionType extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mission_types';

    protected $fillable = [
        'code',
        'label',
        'short_label',
        'description',
        'logo_path',
        'color',
        'icon',
        'is_active',
        'sort_order',
        'audit_type_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function auditType(): BelongsTo
    {
        return $this->belongsTo(AuditType::class, 'audit_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }
}
