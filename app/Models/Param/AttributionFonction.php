<?php

namespace App\Models\Param;

use App\Models\TenantModel;

class AttributionFonction extends TenantModel
{
    protected $connection = 'tenant';
    protected $table = 'assignment_functions';

    public $incrementing = false; // ✅ pas d'auto-increment
    protected $primaryKey = null; // ✅ évite l’attente d’une clé unique
    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'assignment_id', 'function_id', 'entity_id', 'role_label', 'created_at', 'updated_at'
    ];
}