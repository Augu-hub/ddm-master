<?php

namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ProcessCriticalityNorm extends Model
{
    protected $connection = 'tenant';
    protected $table = 'process_criticality_norms';

    protected $fillable = [
        'min_percent',
        'max_percent',
        'color',
        'alert_label',
        'alert_level',
        'actions',
        'user_id',
    ];
}
