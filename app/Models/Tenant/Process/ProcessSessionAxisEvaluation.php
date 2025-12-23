<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessSessionAxisEvaluation extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'session_id','process_id',
        'maturity_score','motricity_score','transversality_score',
        'strategic_score','criticality_score'
    ];
}
