<?php

namespace App\Models\Param;

use App\Models\TenantModel;

class FunctionAssignment extends TenantModel
{
    protected $connection = 'tenant';
    protected $table = 'function_assignments';
    protected $fillable = ['entity_id','function_id','user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }public function functionAssignment()
{
    return $this->hasOne(FunctionAssignment::class, 'function_id');
}


}
