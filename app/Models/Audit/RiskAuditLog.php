<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class RiskAuditLog extends Model
{
    protected $table = 'risk_audit_log';
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'entity_type', 'entity_id', 'action',
        'changes', 'reason', 'user_id', 'user_name', 'ip_address'
    ];
}