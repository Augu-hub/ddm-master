<?php

namespace App\Models\Audit\Mission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;

// ════════════════════════════════════════════════════════════════════════════════════
// 📊 MISSION PHASE
// ⚠️  Table dans la base TENANT (fruitiva) — connexion 'tenant'
//     PAS dans ddmparam (connexion 'param')
// ════════════════════════════════════════════════════════════════════════════════════

class MissionPhase extends Model
{
    // ── CORRECTION CLEF — évite l'erreur ddmparam.mission_phases ─────────
    protected $connection = 'tenant';

    protected $table = 'mission_phases';

    protected $fillable = [
        'code', 'code_full', 'label', 'description', 'phase_type',
        'logo_preparation', 'logo_verification', 'logo_conclusion', 'logo_suivi',
        'parent_id', 'level', 'mission_type_id',
        'weight', 'is_decomposable', 'status',
    ];

    protected $casts = [
        'is_decomposable' => 'boolean',
        'level'           => 'integer',
        'weight'          => 'integer',
        'mission_type_id' => 'integer',
        'parent_id'       => 'integer',
    ];

    // ════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ════════════════════════════════════════════════════════════════════════

    public function missionType(): BelongsTo
    {
        return $this->belongsTo(MissionType::class, 'mission_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('weight');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MissionPhaseAssignment::class, 'mission_phase_id');
    }

    // ════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════════

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $current   = $this;
        while ($current->parent) {
            $current = $current->parent;
            array_unshift($ancestors, $current);
        }
        return $ancestors;
    }

    public function getPath(): array
    {
        return array_merge($this->getAncestors(), [$this]);
    }

    public function getPathLabel(): string
    {
        return implode(' → ', array_map(fn($p) => $p->label, $this->getPath()));
    }

    public function getAverageProgress(): int
    {
        if ($this->isLeaf()) return 0;
        $progresses = $this->children()->with('assignments')->get()
            ->map(fn($child) => $child->assignments()->avg('progress') ?? 0);
        return (int) $progresses->avg();
    }

    public function getMissionCount(): int
    {
        return $this->assignments()->count();
    }

    // ════════════════════════════════════════════════════════════════════════
    // GÉNÉRATION CODE — compatible format fruitiva
    // Niveau 1 : P1, P2, P3, P4
    // Niveau 2 : P1.E1, P1.E2, P2.E12 …
    // ════════════════════════════════════════════════════════════════════════

    public static function generateCodeFull(?MissionPhase $parent, int $sequence): string
    {
        if (!$parent) return "P{$sequence}";

        $letters     = ['E', 'A', 'T', 'S', 'X'];
        $letterIndex = min($parent->level, count($letters) - 1);

        return $parent->code_full . $letters[$letterIndex] . $sequence;
    }

    public static function getNextSequenceForParent(?int $parentId, int $typeId): int
    {
        $query = self::where('mission_type_id', $typeId);
        $parentId ? $query->where('parent_id', $parentId) : $query->whereNull('parent_id');
        return $query->count() + 1;
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════════════

    public function scopeMainPhases(Builder $query): Builder
    {
        return $query->where('level', 1)->whereNull('parent_id');
    }

    public function scopeByType(Builder $query, int $typeId): Builder
    {
        return $query->where('mission_type_id', $typeId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}