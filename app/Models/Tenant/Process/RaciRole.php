<?php // app/Models/Tenant/Process/RaciRole.php
namespace App\Models\Tenant\Process;

use App\Models\Tenant\TenantModel;

class RaciRole extends TenantModel
{
    protected $table = 'activity_raci_roles';
    // colonnes : code, label, description
}
