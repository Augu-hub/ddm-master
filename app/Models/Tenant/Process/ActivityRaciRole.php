<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityRaciRole extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_raci_roles';

    protected $fillable = ['code', 'label', 'description', 'sort'];
    protected $casts = ['sort' => 'integer'];

    public function assignments(): HasMany
    {
        return $this->hasMany(ActivityRaciAssignment::class, 'raci_role_id');
    }
}