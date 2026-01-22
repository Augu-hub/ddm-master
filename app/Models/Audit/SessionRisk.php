<?php

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MODELS POUR LE MODULE AUDIT - GESTION DES RISQUES
 * ════════════════════════════════════════════════════════════════════════════
 */

// ════════════════════════════════════════════════════════════════════════════
// ️MODEL: RiskType
// ════════════════════════════════════════════════════════════════════════════
namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;


class SessionRisk extends Model
{
    use SoftDeletes;

    protected $table = 'session_risks';
    protected $fillable = [
      'tenant_id', 'audit_session_id', 'risk_id', 'code', 'label',
        'description', 'risk_type_id', 'process_id', 'activity_id',
        'frequency_level_id', 'impact_level_id', 'criticality',
        'frequency_net', 'impact_net', 'control_procedure', 'owner',
        'validation_status', 'validation_comments', 'validated_at',
        'validated_by', 'color', 'created_by'
    ];

    protected $casts = [
        'validated_at' => 'datetime',
  ];

    public function session()
    {
        return $this->belongsTo(AuditSession::class, 'audit_session_id');
    }

    public function risk()
    {
        return $this->belongsTo(Risk::class, 'risk_id');
 }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function validator()
    {
     return $this->belongsTo(\App\Models\User::class, 'validated_by');
}
 public function getStatusLabelAttribute()
 {
     return match ($this->validation_status) {
         'draft' => '✏️ Brouillon',
         'pending' => '⏳ En attente',
         'approved' => '✅ Approuvé',
         'rejected' => '❌ Rejeté',
           default => $this->validation_status,
    };
  }
   public function getColorValueAttribute()
   {
       return match ($this->color) {
           'warning' => '#ffc',
           'success' => '#745',
           'danger' => '#dc3545',
            default => '#6c757d',
     };
  }

    public function scopeApproved($query)
    {
        return $query->where('validation_status', 'approved');
    }

    public function scopeRejected($query)
    {
     return $query->where('validation_status', 'rejected');
  }

    public function scopePending($query)
    {
        return $query->where('validation_status', 'pending');
    }

    public function scopeBySession($query, $sessionId)
    {
     return $query->where('audit_session_id', $sessionId);
  }
}
