<?php

namespace App\Models\Master;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Lire dans la base maître (ddmparam)
    protected $connection = 'mysql';
    protected $table = 'permissions';
    protected $guarded = [];
}
