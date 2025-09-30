<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    // IMPORTANT : la table tenants est dans la base maîtresse (connexion 'mysql')
    protected $connection = 'mysql';

    protected $table = 'tenants';

    protected $fillable = [
        'code', 'name',
        'db_host', 'db_name', 'db_username', 'db_password',
    ];
}
