<?php

namespace App\Models\Audit\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class MissionRequestShare extends Model
{
    protected $table = 'mission_request_shares';
    
    protected $fillable = [
        'share_link',
        'shared_by_id',
        'mission_request_id',
        'status',
        'shared_at',
        'used_at',
        'expires_at',
    ];
    
    protected $casts = [
        'shared_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Celui qui a partagé le lien
     */
    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_id');
    }

    /**
     * La demande créée via ce lien
     */
    public function missionRequest(): BelongsTo
    {
        return $this->belongsTo(MissionRequest::class, 'mission_request_id');
    }

    /**
     * Marquer le lien comme utilisé
     */
    public function markAsUsed($missionRequestId = null)
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'mission_request_id' => $missionRequestId,
        ]);
    }
}