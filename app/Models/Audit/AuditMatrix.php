<?php


namespace App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditMatrix extends Model
{
    use SoftDeletes;

    protected $table = 'audit_matrix';
    protected $connection = 'tenant';

    protected $fillable = [
        'impact_level',
        'frequency_level',
        'criticality_score',
        'label',
        'qualification',
        'color',
    ];

    protected $casts = [
        'impact_level' => 'integer',
        'frequency_level' => 'integer',
        'criticality_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════════════════════════════
    // RELATIONS
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🔗 Niveau d'impact associé
     */
    public function impactLevel(): BelongsTo
    {
        return $this->belongsTo(AuditImpactLevel::class, 'impact_level', 'level');
    }

    /**
     * 🔗 Niveau de fréquence associé
     */
    public function frequencyLevel(): BelongsTo
    {
        return $this->belongsTo(AuditFrequencyLevel::class, 'frequency_level', 'level');
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * 🔍 Filtrer par impact
     */
    public function scopeByImpact($query, $level)
    {
        return $query->where('impact_level', $level);
    }

    /**
     * 🔍 Filtrer par fréquence
     */
    public function scopeByFrequency($query, $level)
    {
        return $query->where('frequency_level', $level);
    }

    /**
     * 🔍 Filtrer par score de criticité
     */
    public function scopeByScore($query, $score)
    {
        return $query->where('criticality_score', $score);
    }

    /**
     * 🔴 Cellules critiques (score >= 15)
     */
    public function scopeCritical($query)
    {
        return $query->where('criticality_score', '>=', 15);
    }

    /**
     * 🟠 Cellules élevées (12-14)
     */
    public function scopeHigh($query)
    {
        return $query->whereBetween('criticality_score', [12, 14]);
    }

    /**
     * 🟡 Cellules modérées (6-11)
     */
    public function scopeMedium($query)
    {
        return $query->whereBetween('criticality_score', [6, 11]);
    }

    /**
     * 🟢 Cellules faibles (< 6)
     */
    public function scopeLow($query)
    {
        return $query->where('criticality_score', '<', 6);
    }

    /**
     * 🔍 Ordonner par défaut
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('impact_level')
                     ->orderBy('frequency_level');
    }

    /**
     * 🔍 Actifs seulement
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
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
            $this->criticality_score ?? '-'
        );
    }

    /**
     * 🎨 Couleur texte basée sur luminosité
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
     * 🎯 Libellé de criticité
     */
    public function getCriticalityLabelAttribute(): string
    {
        return match (true) {
            $this->criticality_score <= 5 => '🟢 Faible',
            $this->criticality_score <= 11 => '🟡 Moyen',
            $this->criticality_score <= 16 => '🟠 Élevé',
            default => '🔴 Critique',
        };
    }

    /**
     * 🎨 Couleur de criticité
     */
    public function getCriticalityColorAttribute(): string
    {
        return match (true) {
            $this->criticality_score <= 5 => 'success',
            $this->criticality_score <= 11 => 'warning',
            $this->criticality_score <= 16 => 'danger',
            default => 'dark',
        };
    }

    /**
     * 📝 Libellé formaté
     */
    public function getFormattedLabelAttribute(): string
    {
        return sprintf(
            '%s (Score: %d)',
            $this->label ?? 'N/A',
            $this->criticality_score ?? 0
        );
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // MÉTHODES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * ✅ Obtenir une cellule par coordonnées
     */
    public static function getCell(int $impactLevel, int $frequencyLevel): ?self
    {
        return self::where('impact_level', $impactLevel)
                    ->where('frequency_level', $frequencyLevel)
                    ->first();
    }

    /**
     * ✅ Mettre à jour couleur cellule
     */
    public function updateColor(string $color): bool
    {
        return $this->update(['color' => $color]);
    }

    /**
     * 📊 Obtenir la matrice complète (5×5)
     */
    public static function getCompleteMatrix(): array
    {
        $matrix = [];
        $impacts = AuditImpactLevel::active()->ordered()->pluck('level');
        $frequencies = AuditFrequencyLevel::active()->ordered()->pluck('level');

        foreach ($impacts as $impact) {
            $matrix[$impact] = [];
            foreach ($frequencies as $frequency) {
                $cell = self::getCell($impact, $frequency);
                $matrix[$impact][$frequency] = [
                    'id' => $cell->id ?? null,
                    'score' => $cell->criticality_score ?? ($impact * $frequency),
                    'label' => $cell->label ?? '-',
                    'color' => $cell->color ?? '#e9ecef',
                    'qualification' => $cell->qualification ?? '-',
                ];
            }
        }

        return $matrix;
    }

    /**
     * 📊 Obtenir matrice en JSON pour frontend
     */
    public static function getMatrixForFrontend(): array
    {
        return self::active()
                   ->ordered()
                   ->get()
                   ->map(fn ($cell) => [
                       'id' => $cell->id,
                       'impact_level' => $cell->impact_level,
                       'frequency_level' => $cell->frequency_level,
                       'criticality_score' => $cell->criticality_score,
                       'label' => $cell->label,
                       'qualification' => $cell->qualification,
                       'color' => $cell->color,
                       'criticality_label' => $cell->criticality_label,
                   ])
                   ->toArray();
    }

    /**
     * 🎨 Obtenir palette couleurs
     */
    public static function getColorPalette(): array
    {
        return self::active()
                   ->pluck('color')
                   ->unique()
                   ->toArray();
    }

    /**
     * ✅ Créer ou mettre à jour par coordonnées
     */
    public static function createOrUpdateByCoordinates(
        int $impactLevel,
        int $frequencyLevel,
        array $data
    ): self {
        return self::updateOrCreate(
            [
                'impact_level' => $impactLevel,
                'frequency_level' => $frequencyLevel,
            ],
            $data
        );
    }
}