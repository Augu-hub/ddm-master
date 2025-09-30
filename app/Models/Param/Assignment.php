<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ['entity_id','mpa_type','mpa_id'];

    // pratique pour tracer l’origine (macro/process/activity) déposé,
    // même si on stocke finalement au niveau activité lors du commit "résolu".
}
