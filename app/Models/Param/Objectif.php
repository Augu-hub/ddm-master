<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Objectif extends Model
{
    protected $connection = 'tenant';     // <— IMPORTANT (même connexion que Processus/Activite)

    protected $table = 'objectifs';

    protected $fillable = [
        'process_id',
        'name',
        'description',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processus::class, 'process_id');
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class, 'objectif_id');
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ObjectifInput::class, 'objectif_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ObjectifOutput::class, 'objectif_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ObjectifResource::class, 'objectif_id');
    }
}