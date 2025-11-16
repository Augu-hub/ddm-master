<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ModuleUser extends Pivot
{
    protected $connection = 'mysql'; // maître
    protected $table = 'module_user';
}
