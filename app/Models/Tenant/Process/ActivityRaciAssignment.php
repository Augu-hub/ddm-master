<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRaciAssignment extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_raci_assignments';

    protected $fillable = ['activity_id', 'function_id', 'raci_role_id', 'created_by_user_id'];
    protected $casts = [
        'activity_id' => 'integer',
        'function_id' => 'integer',
        'raci_role_id' => 'integer',
        'created_by_user_id' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function function(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Function::class, 'function_id');
    }

    public function raciRole(): BelongsTo
    {
        return $this->belongsTo(ActivityRaciRole::class, 'raci_role_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }
}