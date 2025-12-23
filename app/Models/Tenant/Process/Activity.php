<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activities';

    protected $fillable = ['process_id', 'code', 'name', 'description'];
    protected $casts = ['process_id' => 'integer'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function raciAssignments(): HasMany
    {
        return $this->hasMany(ActivityRaciAssignment::class, 'activity_id');
    }
}