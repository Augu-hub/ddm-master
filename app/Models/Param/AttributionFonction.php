<?php

namespace App\Models\Param;

use App\Models\TenantModel;

class AttributionFonction extends TenantModel
{
    protected $table = 'assignment_functions';
    protected $fillable = ['assignment_id','function_id','role_label'];
}
