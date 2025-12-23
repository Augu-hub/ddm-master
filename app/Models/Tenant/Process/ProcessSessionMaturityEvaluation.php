<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessSessionMaturityEvaluation extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'session_id','process_id','criterion_code',
        'level_score','evaluated_by','evaluated_at'
    ];
}
