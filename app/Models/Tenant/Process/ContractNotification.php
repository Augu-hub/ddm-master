<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;

class ContractNotification extends Model
{
    protected $connection = 'tenant';
    protected $table = 'contract_notifications';

    protected $fillable = [
        'contract_id',
        'output_id',
        'output_label',
        'function_id',
        'function_name',
        'user_id',
        'user_name',
        'user_email',
        'sender_id',
        'sender_name',
        'message',
        'expectations',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec le contrat
     */
    public function contract()
    {
        return $this->belongsTo(ProcessContract::class, 'contract_id');
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope pour les notifications d'un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les notifications récentes (7 derniers jours)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }
}