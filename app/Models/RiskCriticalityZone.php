<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class RiskCriticalityZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table      = 'risk_criticality_zones';

    protected $fillable = [
        'tenant_id',
        'matrix_config_id',
        'label',
        'min_score',
        'max_score',
        'color_code',
        'sort_order',
    ];

    protected $casts = [
        'min_score'  => 'integer',
        'max_score'  => 'integer',
        'sort_order' => 'integer',
    ];

    // --- Relations ------------------------------------------------------------

    public function matrixConfig(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixConfig::class, 'matrix_config_id');
    }

    // --- Scopes ---------------------------------------------------------------

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForConfig(Builder $query, int $configId): Builder
    {
        return $query->where('matrix_config_id', $configId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeContainingScore(Builder $query, int $score): Builder
    {
        return $query->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }

    // --- Methodes metier ------------------------------------------------------

    /**
     * Verifie si un score appartient a cette zone.
     */
    public function contains(int $score): bool
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }

    /**
     * Largeur de la plage (ex: zone 15-25 = amplitude 11).
     */
    public function getRangeAttribute(): int
    {
        return $this->max_score - $this->min_score + 1;
    }

    /**
     * Libelle avec plage pour l'affichage (ex: "Critique [20-25]").
     */
    public function getLabelWithRangeAttribute(): string
    {
        return "{$this->label} [{$this->min_score}-{$this->max_score}]";
    }

    /**
     * Genere les zones par defaut pour une taille de matrice donnee.
     * Couvre les tailles 3 a 10 avec 5 zones proportionnelles.
     * Utile pour le seeding initial ou le reset d'une config.
     */
    public static function defaultZonesForSize(int $size): array
    {
        return match ($size) {
            3 => [
                ['label' => 'Faible',    'min_score' => 1, 'max_score' => 3,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Modere',    'min_score' => 4, 'max_score' => 6,  'color_code' => '#eab308', 'sort_order' => 2],
                ['label' => 'Critique',  'min_score' => 7, 'max_score' => 9,  'color_code' => '#ef4444', 'sort_order' => 3],
            ],
            4 => [
                ['label' => 'Faible',    'min_score' => 1,  'max_score' => 4,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Modere',    'min_score' => 5,  'max_score' => 8,  'color_code' => '#eab308', 'sort_order' => 2],
                ['label' => 'Eleve',     'min_score' => 9,  'max_score' => 12, 'color_code' => '#f97316', 'sort_order' => 3],
                ['label' => 'Critique',  'min_score' => 13, 'max_score' => 16, 'color_code' => '#ef4444', 'sort_order' => 4],
            ],
            5 => [
                ['label' => 'Negligeable', 'min_score' => 1,  'max_score' => 4,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 5,  'max_score' => 9,  'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modere',      'min_score' => 10, 'max_score' => 14, 'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Eleve',       'min_score' => 15, 'max_score' => 19, 'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 20, 'max_score' => 25, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            // --- Tailles etendues (6 a 10) : 5 zones proportionnelles ----------
            // Score min = 1 (1x1), score max = size * size
            // Les plages divisent le maximum en 5 segments sensiblement egaux.
            6 => [
                // max = 36  | segments : 1-6 / 7-13 / 14-22 / 23-29 / 30-36
                ['label' => 'Negligeable', 'min_score' => 1,  'max_score' => 6,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 7,  'max_score' => 13, 'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modere',      'min_score' => 14, 'max_score' => 22, 'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Eleve',       'min_score' => 23, 'max_score' => 29, 'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 30, 'max_score' => 36, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            7 => [
                // max = 49  | segments : 1-9 / 10-19 / 20-29 / 30-39 / 40-49
                ['label' => 'Negligeable', 'min_score' => 1,  'max_score' => 9,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 10, 'max_score' => 19, 'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modere',      'min_score' => 20, 'max_score' => 29, 'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Eleve',       'min_score' => 30, 'max_score' => 39, 'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 40, 'max_score' => 49, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            8 => [
                // max = 64  | segments : 1-12 / 13-25 / 26-38 / 39-51 / 52-64
                ['label' => 'Negligeable', 'min_score' => 1,  'max_score' => 12, 'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 13, 'max_score' => 25, 'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modere',      'min_score' => 26, 'max_score' => 38, 'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Eleve',       'min_score' => 39, 'max_score' => 51, 'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 52, 'max_score' => 64, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            9 => [
                // max = 81  | segments : 1-16 / 17-32 / 33-48 / 49-64 / 65-81
                ['label' => 'Negligeable', 'min_score' => 1,  'max_score' => 16, 'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 17, 'max_score' => 32, 'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modere',      'min_score' => 33, 'max_score' => 48, 'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Eleve',       'min_score' => 49, 'max_score' => 64, 'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 65, 'max_score' => 81, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            10 => [
                // max = 100 | segments : 1-20 / 21-40 / 41-60 / 61-80 / 81-100
                ['label' => 'Negligeable', 'min_score' => 1,  'max_score' => 20,  'color_code' => '#22c55e', 'sort_order' => 1],
                ['label' => 'Faible',      'min_score' => 21, 'max_score' => 40,  'color_code' => '#84cc16', 'sort_order' => 2],
                ['label' => 'Modere',      'min_score' => 41, 'max_score' => 60,  'color_code' => '#eab308', 'sort_order' => 3],
                ['label' => 'Eleve',       'min_score' => 61, 'max_score' => 80,  'color_code' => '#f97316', 'sort_order' => 4],
                ['label' => 'Critique',    'min_score' => 81, 'max_score' => 100, 'color_code' => '#ef4444', 'sort_order' => 5],
            ],
            default => [],
        };
    }
}
