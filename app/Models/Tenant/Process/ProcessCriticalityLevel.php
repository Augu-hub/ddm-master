<?php // app/Models/Tenant/Process/ProcessMaturityScale.php
namespace App\Models\Tenant\Process;

use App\Models\Tenant\TenantModel;

class ProcessCriticalityLevel extends TenantModel
{
    protected $table = 'process_criticality_criteria';
    // colonnes possibles : code, label, rank, min_score, max_score, color_hex, sort
}
