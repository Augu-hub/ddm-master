<?php

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MODELS POUR LE MODULE AUDIT - GESTION DES RISQUES
 * ════════════════════════════════════════════════════════════════════════════
 */

// ════════════════════════════════════════════════════════════════════════════
// 1️⃣ MODEL: RiskType
// ════════════════════════════════════════════════════════════════════════════
namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskType extends Model
{
    use SoftDeletes;

    protected $table = 'risk_types';

    protected $fillable = [
        'tenant_id',
        'code',
        'label',
        'description',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'tenant_id' => 'integer',
    ];

    // Relations
    public function risks()
    {
        return $this->hasMany(Risk::class, 'risk_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
