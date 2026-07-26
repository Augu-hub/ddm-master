<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectifResource extends Model
{
    protected $connection = 'tenant';
    protected $table = 'objectif_resources';
    protected $fillable = ['objectif_id', 'label'];

    public function objectif(): BelongsTo
    {
        return $this->belongsTo(Objectif::class, 'objectif_id');
    }
}