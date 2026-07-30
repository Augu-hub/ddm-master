<?php

namespace App\Models;

use App\Enums\NomenclatureType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskNomenclature extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_nomenclatures';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'appetite_id',
        'level',
        'type_code',
        'code',
        'label',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'level'     => 'integer',
        'parent_id' => 'integer',
        'is_active' => 'boolean',
    ];

    // --- Relations ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    public function childrenDeep(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('is_active', true)
            ->with('childrenDeep')
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    public function riskRegisters(): BelongsToMany
    {
        return $this->belongsToMany(
            RiskRegister::class,
            'risk_register_nomenclatures',
            'risk_nomenclature_id',
            'risk_register_id'
        );
    }

    // --- Helpers ---

    public function isRoot(): bool
    {
        return $this->level === 1;
    }

    public function canHaveChildren(): bool
    {
        return $this->level < 3;
    }

    public function typeEnum(): ?NomenclatureType
    {
        return NomenclatureType::tryFrom($this->type_code ?? '');
    }

    // Couleur : priorité à la valeur DB, fallback enum
    public function resolvedColor(): string
    {
        if ($this->color) return $this->color;
        return $this->typeEnum()?->color() ?? '#6c757d';
    }

    // Icône : priorité à la valeur DB, fallback enum
    public function resolvedIcon(): string
    {
        if ($this->icon) return $this->icon;
        return $this->typeEnum()?->icon() ?? 'ti ti-folder';
    }

    // --- Scopes ---

    public function scopeRoots($query)
    {
        return $query->where('level', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
