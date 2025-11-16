<?php // app/Models/Tenant/TenantModel.php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    protected $connection = 'tenant';
    public $timestamps = true;
    protected $guarded = [];
}
