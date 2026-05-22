<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyseConformite extends Model
{
    protected $fillable = [
        'intitule_qcc',
        'description',
        'date_audit',
        'auditeur_id',
        'statut',
        'score_global',
        'soumis_le',
        'soumis_par',
        'valide_le',
        'valide_par',
        'created_by',
    ];

    protected $casts = [
        'date_audit'  => 'date',
        'soumis_le'   => 'datetime',
        'valide_le'   => 'datetime',
        'score_global' => 'float',
    ];

    // ── Relations ──────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(AnalyseConformiteItem::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(QccPhase::class)->orderBy('ordre');
    }

    public function auditeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditeur_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function soumisBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'soumis_par');
    }

    public function valideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    // ── Helpers ────────────────────────────────────────────

    public function updateScoreGlobal(): void
    {
        $score = $this->items()
            ->whereNotNull('reponse')
            ->avg('score');

        $this->update(['score_global' => $score ? round($score, 2) : null]);
    }

    public function getBadgeStatutAttribute(): string
    {
        return match ($this->statut) {
            'brouillon' => '<span class="badge bg-secondary">Brouillon</span>',
            'soumis'    => '<span class="badge bg-warning text-dark">Soumis</span>',
            'valide'    => '<span class="badge bg-success">Validé</span>',
            'archive'   => '<span class="badge bg-dark">Archivé</span>',
            default     => '<span class="badge bg-light text-dark">' . $this->statut . '</span>',
        };
    }
}