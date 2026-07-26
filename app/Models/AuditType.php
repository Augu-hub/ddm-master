<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Référentiel CENTRAL — table `audit_types` de la base `ddmparam`
 * (connexion Laravel `mysql`, qui pointe sur ddmparam par défaut).
 *
 * Codes métier : AC (Conformité) | AF (Fraude) | AP (Performance)
 *                AM (Marchés)    | RP (Revue Perf.) | ES (Éval. Système)
 *
 * ⚠️ Source de vérité pour les champs dénormalisés copiés dans chaque base
 *    tenant (`mission_types.audit_type_label / audit_color / audit_icon`).
 *    Observé par AuditTypeObserver : toute écriture Eloquent déclenche la
 *    synchro automatique vers tous les tenants (cf. AppServiceProvider).
 */
class AuditType extends Model
{
    protected $connection = 'mysql';
    protected $table = 'audit_types';

    protected $fillable = [
        'code',
        'label',
        'short_label',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
        'keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function forms(): HasMany
    {
        return $this->hasMany(AuditTypeForm::class, 'audit_type_id')
            ->orderBy('phase_num')
            ->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }
}
