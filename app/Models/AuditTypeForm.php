<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Référentiel CENTRAL — table `audit_type_forms` de la base `ddmparam` :
 * les formulaires/étapes de chaque type d'audit, organisés par phase
 * (phase_num 1=Préparation … 5=Recommandations) et hiérarchisés par parent_id.
 *
 * ⚠️ Depuis la migration `migrate_phases_to_central_ids.sql`, les tenants
 *    référencent DIRECTEMENT `audit_type_forms.id` comme clé primaire de leur
 *    table `mission_phases` (plus de copie du contenu côté tenant).
 *    Observé par AuditTypeFormObserver : toute écriture Eloquent déclenche
 *    la synchro automatique vers tous les tenants.
 */
class AuditTypeForm extends Model
{
    protected $connection = 'mysql';
    protected $table = 'audit_type_forms';

    protected $fillable = [
        'audit_type_id',
        'phase_num',
        'phase_label',
        'norme',
        'parent_id',
        'code',
        'label',
        'description',
        'route_name',
        'url_path',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'phase_num' => 'integer',
        'sort_order' => 'integer',
    ];

    public function auditType(): BelongsTo
    {
        return $this->belongsTo(AuditType::class, 'audit_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }
}
