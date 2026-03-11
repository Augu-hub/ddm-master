<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditImpactLevel extends Model
{
    use SoftDeletes;

    protected $table = 'audit_impact_levels';
    protected $connection = 'tenant';

    protected $fillable = [
        'code',
        'level',
        'label',
        'description',
        'color',
    ];

    protected $casts = [
        'level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🔗 Risques utilisant ce niveau d'impact
     */
    public function risks(): HasMany
    {
        return $this->hasMany(\App\Models\Audit\Risk::class, 'impact_level_id');
    }

    /**
     * 🔗 Entrées matrice utilisant ce niveau
     */
    public function matrixEntries(): HasMany
    {
        return $this->hasMany(AuditMatrix::class, 'impact_level');
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🔍 Filtrer par niveau
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * 🔍 Filtrer par code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * 🔍 Actifs seulement
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * 🔍 Ordonner par niveau
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level');
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🎨 Badge HTML avec couleur
     */
    public function getBadgeAttribute(): string
    {
        return sprintf(
            '<span class="badge" style="background-color: %s; color: %s;">%s</span>',
            $this->color,
            $this->getTextColor(),
            $this->label
        );
    }

    /**
     * 🎨 Couleur texte basée sur luminosité du fond
     */
    public function getTextColor(): string
    {
        if ($this->color === null) {
            return '#000';
        }

        // Convertir HEX en RGB
        $hex = str_replace('#', '', $this->color);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculer la luminosité
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }

    /**
     * 📝 Libellé formaté avec emoji
     */
    public function getFormattedLabelAttribute(): string
    {
        return match ($this->level) {
            1 => "🟢 {$this->label}",
            2 => "🔵 {$this->label}",
            3 => "🟡 {$this->label}",
            4 => "🟠 {$this->label}",
            5 => "🔴 {$this->label}",
            default => $this->label,
        };
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // MÉTHODES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Créer ou mettre à jour
     */
    public static function createOrUpdate(array $data): self
    {
        return self::updateOrCreate(
            ['code' => $data['code']],
            $data
        );
    }

    /**
     * 📊 Obtenir tous les niveaux formatés
     */
    public static function getFormattedLevels(): array
    {
        return self::active()
            ->ordered()
            ->get()
            ->mapWithKeys(fn ($level) => [
                $level->id => $level->formatted_label
            ])
            ->toArray();
    }

    /**
     * 🎨 Obtenir toutes les couleurs
     */
    public static function getColors(): array
    {
        return self::active()
            ->pluck('color', 'code')
            ->toArray();
    }
}