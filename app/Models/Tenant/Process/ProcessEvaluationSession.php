<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessEvaluationSession extends Model
{
    protected $connection = 'tenant';
    protected $fillable = [
        'user_id','entity_id','function_id',
        'name','color','status'
    ];

    public function maturity()
    {
        return $this->hasMany(ProcessSessionMaturityEvaluation::class, 'session_id');
    }

    public function axes()
    {
        return $this->hasMany(ProcessSessionAxisEvaluation::class, 'session_id');
    }
}
