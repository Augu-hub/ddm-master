<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MODEL: RiskNomenclature
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Hiérarchie à 3 niveaux via auto-référence (parent_id).
 *
 *   Niveau 1 → Domaine     : RO, RF, RI …
 *   Niveau 2 → Famille     : RO-RH, RO-PROD …
 *   Niveau 3 → Type précis : RO-RH-001, RO-PROD-002 …
 *
 * Héritage de l'appétance (Approche B) :
 *   La méthode resolvedAppetite() remonte la hiérarchie jusqu'à trouver
 *   un nœud ayant un appetite_id défini. Résolution :
 *     niveau 3 → niveau 2 → niveau 1 → null
 */
class RiskNomenclature extends Model
{
    use SoftDeletes;

    protected $table = 'risk_nomenclatures';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'appetite_id',
        'code',
        'label',
        'description',
        'level',
        'color',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'parent_id'  => 'integer',
        'appetite_id'=> 'integer',
        'level'      => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    // ────────────────────────────────────────────────────────────────────────
    // RELATIONS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Nœud parent (null si niveau 1 — racine)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(RiskNomenclature::class, 'parent_id');
    }

    /**
     * Enfants directs
     */
    public function children(): HasMany
    {
        return $this->hasMany(RiskNomenclature::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    /**
     * Appétance définie directement sur ce nœud (peut être null)
     */
    public function appetite(): BelongsTo
    {
        return $this->belongsTo(RiskAppetiteLevel::class, 'appetite_id');
    }

    /**
     * Risques rattachés à cette nomenclature
     */
    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class, 'nomenclature_id');
    }

    // ────────────────────────────────────────────────────────────────────────
    // HÉRITAGE D'APPÉTANCE (Approche B)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Retourne l'appétance effective en remontant la hiérarchie.
     *
     * Ordre de résolution :
     *   1. Ce nœud lui-même (appetite_id non null)
     *   2. Son parent direct
     *   3. Le grand-parent (niveau 1)
     *   4. null → aucune appétance définie
     *
     * Les relations parent sont eager-loadées si déjà chargées,
     * sinon on fait des requêtes ascendantes.
     */
    public function resolvedAppetite(): ?RiskAppetiteLevel
    {
        // Ce nœud a une appétance directe → on l'utilise
        if ($this->appetite_id !== null) {
            return $this->appetite ?? RiskAppetiteLevel::find($this->appetite_id);
        }

        // Remontée vers le parent
        $parent = $this->parent ?? ($this->parent_id ? static::find($this->parent_id) : null);

        if ($parent === null) {
            return null; // On est à la racine, rien de défini
        }

        return $parent->resolvedAppetite();
    }

    /**
     * Indique si l'appétance est héritée (non définie directement ici)
     */
    public function isAppetiteInherited(): bool
    {
        return $this->appetite_id === null && $this->resolvedAppetite() !== null;
    }

    /**
     * Retourne le nœud ancêtre qui porte l'appétance effective
     * (utile pour l'affichage "hérite de RO-PROD")
     */
    public function appetiteOwner(): ?self
    {
        if ($this->appetite_id !== null) {
            return $this;
        }

        $parent = $this->parent ?? ($this->parent_id ? static::find($this->parent_id) : null);

        return $parent?->appetiteOwner();
    }

    // ────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ────────────────────────────────────────────────────────────────────────

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    // ────────────────────────────────────────────────────────────────────────
    // MÉTHODES UTILITAIRES
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Retourne l'arbre complet du tenant sous forme de collection imbriquée.
     * Utilisé pour construire le sélecteur hiérarchique côté Vue.
     */
    public static function treeForTenant(int $tenantId): Collection
    {
        $all = static::byTenant($tenantId)
            ->active()
            ->with('appetite')
            ->ordered()
            ->get();

        return static::buildTree($all, null);
    }

    private static function buildTree(Collection $all, ?int $parentId): Collection
    {
        return $all
            ->filter(fn ($n) => $n->parent_id === $parentId)
            ->map(function ($node) use ($all) {
                $node->setRelation('children', static::buildTree($all, $node->id));
                return $node;
            })
            ->values();
    }

    /**
     * Retourne le chemin complet depuis la racine : "RO > RO-PROD > RO-PROD-002"
     */
    public function breadcrumb(string $separator = ' > '): string
    {
        $parts = [$this->label];

        $parent = $this->parent ?? ($this->parent_id ? static::find($this->parent_id) : null);

        while ($parent !== null) {
            array_unshift($parts, $parent->label);
            $parent = $parent->parent ?? ($parent->parent_id ? static::find($parent->parent_id) : null);
        }

        return implode($separator, $parts);
    }

    /**
     * Représentation complète pour l'API / Inertia
     */
    public function toApiArray(): array
    {
        $resolvedAppetite = $this->resolvedAppetite();

        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'label'            => $this->label,
            'description'      => $this->description,
            'level'            => $this->level,
            'parent_id'        => $this->parent_id,
            'color'            => $this->color,
            'icon'             => $this->icon,
            'sort_order'       => $this->sort_order,
            'is_active'        => $this->is_active,

            // Appétance directe (peut être null)
            'appetite_id'      => $this->appetite_id,
            'appetite'         => $this->appetite?->only(['id', 'code', 'label', 'color']),

            // Appétance effective après héritage
            'resolved_appetite'         => $resolvedAppetite?->only(['id', 'code', 'label', 'color']),
            'is_appetite_inherited'     => $this->isAppetiteInherited(),
            'appetite_owner_code'       => $this->appetiteOwner()?->code,
        ];
    }
}
