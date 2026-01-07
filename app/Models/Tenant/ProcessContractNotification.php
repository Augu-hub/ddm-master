<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessContractNotification extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'process_contract_notifications';

    protected $fillable = [
        'contract_id',
        'user_id',
        'output_label',
        'function_name',
        'message',
        'expectations',
        'process_name',
        'process_code',
        'read',
        'read_at'
    ];

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relation vers l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation vers le contrat
     */
    public function contract()
    {
        return $this->belongsTo('App\Models\Tenant\Process\ProcessContract', 'contract_id');
    }

    /**
     * Scope: Notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope: Notifications lues
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Marquer comme lue
     */
    public function markAsRead()
    {
        $this->update([
            'read' => true,
            'read_at' => now()
        ]);
        return $this;
    }

    /**
     * Marquer comme non lue
     */
    public function markAsUnread()
    {
        $this->update([
            'read' => false,
            'read_at' => null
        ]);
        return $this;
    }
}