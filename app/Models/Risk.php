<?php
 
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLES ELOQUENT - Système de Gestion des Risques
 * 
 * À créer dans: app/Models/
 * Date: 30 Juin 2026
 * ═══════════════════════════════════════════════════════════════════════════
 */
 
// ═══════════════════════════════════════════════════════════════════════════
// Fichier: app/Models/Risk.php
// ═══════════════════════════════════════════════════════════════════════════
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class Risk extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $table = 'risks';
 
    protected $fillable = [
        'tenant_id',
        'code_risk',
        'libelle',
        'description',
        'macro_id',
        'process_id',
        'activity_id',
        'criticality_score',
        'zone_color',
        'risk_status',
        'owner_id',
        'responsible_id',
        'identified_date',
        'review_date',
        'created_by',
        'updated_by',
    ];
 
    protected $casts = [
        'identified_date' => 'date',
        'review_date' => 'date',
    ];
 
    // Relations
    public function actionPlans()
    {
        return $this->hasMany(RiskActionPlan::class, 'risk_id');
    }
 
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
 
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
 
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}