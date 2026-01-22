<?php

namespace App\Models\Audit\Mission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;




// ════════════════════════════════════════════════════════════════════════════════════
// 📊 MISSION PHASE - AVEC CODES SÉRIALISÉS
// ════════════════════════════════════════════════════════════════════════════════════

class MissionPhase extends Model
{
    protected $table = 'mission_phases';
    protected $fillable = [
        'code', 'code_full', 'label', 'parent_id', 'level',
        'mission_type_id', 'weight', 'is_decomposable', 'status'
    ];
    protected $casts = ['is_decomposable' => 'boolean'];

    // RELATIONS
    public function missionType(): BelongsTo
    {
        return $this->belongsTo(MissionType::class);
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

    // HELPERS
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this;
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

    // ════════════════════════════════════════════════════════════════════════════════════
    // 🔢 GÉNÉRATION DE CODE INTELLIGENT - SÉRIALISÉ
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * Génère le code full pour une phase
     * Niveaux 1: P1, P2, P3, ... (par type)
     * Niveaux 2+: P1E1, P1E2, P1E1A1, etc.
     */
    public static function generateCodeFull(?MissionPhase $parent, int $sequence): string
    {
        if (!$parent) {
            // Niveau 1: P{sequence}
            return "P{$sequence}";
        }

        // Niveaux 2+: parent_code + lettre + sequence
        $letters = ['E', 'A', 'T', 'S', 'X']; // Lettres par niveau de parent
        $letterIndex = min($parent->level, count($letters) - 1);
        $letter = $letters[$letterIndex];

        return $parent->code_full . $letter . $sequence;
    }

    /**
     * Récupère ou génère le numéro de séquence suivant
     * Compte les frères/sœurs du même parent
     */
    public static function getNextSequenceForParent(?int $parentId, int $typeId): int
    {
        $query = self::where('mission_type_id', $typeId);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->count() + 1;
    }

    // SCOPES
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

class MissionPhaseAssignment extends Model
{
    protected $table = 'mission_phase_assignments';
    protected $fillable = [
        'mission_id', 'mission_phase_id', 'status', 'progress', 'actual_weight',
        'start_date', 'end_date', 'planned_start', 'planned_end',
        'owner_id', 'team_members', 'description', 'findings', 'conclusions', 'attachments'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'planned_start' => 'date',
        'planned_end' => 'date',
        'team_members' => 'json',
        'attachments' => 'json',
        'progress' => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(MissionPhase::class, 'mission_phase_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function isLate(): bool
    {
        return $this->planned_end && now()->isAfter($this->planned_end) && $this->status !== 'completed';
    }

    public function getDaysRemaining(): ?int
    {
        return $this->planned_end ? now()->diffInDays($this->planned_end, false) : null;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => '#6c757d',
            'started' => '#0dcaf0',
            'in_progress' => '#0d6efd',
            'completed' => '#198754',
            'skipped' => '#ffc107',
            'cancelled' => '#dc3545',
            default => '#6c757d'
        };
    }

    public function scopeByMission(Builder $query, int $missionId): Builder
    {
        return $query->where('mission_id', $missionId);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}