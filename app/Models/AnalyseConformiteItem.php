<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyseConformiteItem extends Model
{
    protected $fillable = [
        'analyse_conformite_id',
        'phase_id',
        'sous_phase_id',
        'ref_article',
        'libelle_norme',
        'exigence_norme',
        'reponse',
        'forces',
        'faiblesses',
        'objectif',
        'observations',
        'score',
        'ordre',
    ];

    protected $casts = [
        'score' => 'integer',
        'ordre' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────

    public function analyseConformite(): BelongsTo
    {
        return $this->belongsTo(AnalyseConformite::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(QccPhase::class, 'phase_id');
    }

    public function sousPhase(): BelongsTo
    {
        return $this->belongsTo(QccSousPhase::class, 'sous_phase_id');
    }

    public function propositionsIA(): HasMany
    {
        return $this->hasMany(IaProposition::class, 'item_id');
    }

    // ── Helpers ────────────────────────────────────────────

    public function getScoreCalculeAttribute(): ?int
    {
        return match ($this->reponse) {
            'O'  => 100,
            'N'  => 0,
            'SO' => 50,
            default => null,
        };
    }

    public function getBadgeReponseAttribute(): string
    {
        return match ($this->reponse) {
            'O'  => '<span class="badge bg-success">Conforme</span>',
            'N'  => '<span class="badge bg-danger">Non conforme</span>',
            'SO' => '<span class="badge bg-secondary">Sans objet</span>',
            default => '<span class="badge bg-light text-dark">—</span>',
        };
    }
}