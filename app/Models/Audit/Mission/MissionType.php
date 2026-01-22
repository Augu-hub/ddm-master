<?php

namespace App\Models\Audit\Mission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;

// ════════════════════════════════════════════════════════════════════════════════════
// 🎯 MISSION TYPE
// ════════════════════════════════════════════════════════════════════════════════════

class MissionType extends Model
{
    protected $table = 'mission_types';
    protected $fillable = ['code', 'label', 'description', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function phases(): HasMany
    {
        return $this->hasMany(MissionPhase::class, 'mission_type_id')->orderBy('weight');
    }

    public function getMainPhases()
    {
        return $this->phases()->where('level', 1)->get();
    }

    public function getHierarchy()
    {
        return $this->getMainPhases()->load('children', 'children.children', 'children.children.children');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

// ════════════════════════════════════════════════════════════════════════════════════
// 📊 MISSION PHASE - AVEC CODES SÉRIALISÉS
// ════════════════════════════════════════════════════════════════════════════════════
