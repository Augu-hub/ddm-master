<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessContractHistory extends Model
{
    protected $connection = 'tenant';
    protected $table = 'process_contract_histories';
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'user_id',
        'user_name',
        'action',
        'description',
        'old_values',
        'new_values',
        'file_name',
        'file_type',
        'created_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProcessContract::class, 'contract_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created' => '✨ Création',
            'updated_outputs' => '✏️ Modification sorties',
            'updated_indicators' => '📊 Modification indicateurs',
            'file_uploaded' => '📤 Fichier uploadé',
            'file_deleted' => '🗑️ Fichier supprimé',
            'archived' => '📦 Archivé',
            'restored' => '↩️ Restauré',
            default => $this->action
        };
    }
}