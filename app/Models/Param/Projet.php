<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $connection = 'tenant';     // <— IMPORTANT
    protected $table = 'projects';

    protected $fillable = ['code','name','character','description'];
    public function entities()
    {
        return $this->hasMany(Entite::class);
    }
}
