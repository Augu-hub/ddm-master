<?php

namespace App\Models\Master;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Lire dans la base maître (ddmparam)
    protected $connection = 'mysql';
    protected $table = 'roles';
    protected $guarded = [];
}
