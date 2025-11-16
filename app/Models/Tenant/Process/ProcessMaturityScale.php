<?php // app/Models/Tenant/Process/ProcessMaturityScale.php
namespace App\Models\Tenant\Process;

use App\Models\Tenant\TenantModel;

class ProcessMaturityScale extends TenantModel
{
    protected $table = 'process_maturity_scales';
    // colonnes possibles : code, label, rank, min_score, max_score, color_hex, sort
}
