<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectifOutput extends Model
{
    protected $connection = 'tenant';
    protected $table = 'objectif_outputs';
    protected $fillable = ['objectif_id', 'label'];

    public function objectif(): BelongsTo
    {
        return $this->belongsTo(Objectif::class, 'objectif_id');
    }
}