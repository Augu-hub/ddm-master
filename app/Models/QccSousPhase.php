<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QccSousPhase extends Model
{
    protected $fillable = [
        'phase_id',
        'libelle',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(QccPhase::class, 'phase_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AnalyseConformiteItem::class, 'sous_phase_id');
    }
}