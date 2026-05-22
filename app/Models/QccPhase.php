<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QccPhase extends Model
{
    protected $fillable = [
        'analyse_conformite_id',
        'ref_article',
        'libelle_norme',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function analyseConformite(): BelongsTo
    {
        return $this->belongsTo(AnalyseConformite::class);
    }

    public function sousPhases(): HasMany
    {
        return $this->hasMany(QccSousPhase::class, 'phase_id')->orderBy('ordre');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AnalyseConformiteItem::class, 'phase_id');
    }
}