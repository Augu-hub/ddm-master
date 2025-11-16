<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Tenant\Process\Contract;

class ContractElement extends Model
{
    protected $fillable = [
        'contract_id',
        'type',       // input, output, resource
        'label',
        'assigned_user_id',
        'file_path'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }



public function process()
{
    return $this->belongsTo(Processus::class, 'process_id');
}



}
