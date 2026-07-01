<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLES ELOQUENT - Models/RiskAction*.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace App\Models;

use App\Models\Param\Entite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * RiskActionPlan
 * 
 * Modèle pour les plans d'action associés aux risques
 */
class RiskActionPlan extends Model
{
    use SoftDeletes;

    protected $table = 'risk_action_plans';
    protected $fillable = [
        'tenant_id', 'code', 'risk_id', 'entity_id', 'title', 'description',
        'action_plan', 'priority', 'status', 'assigned_to', 'target_date',
        'start_date', 'completion_date', 'progress', 'cost_estimate',
        'actual_cost', 'notes', 'is_auto_generated', 'source_status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'target_date' => 'date',
        'start_date' => 'date',
        'completion_date' => 'date',
        'is_auto_generated' => 'boolean',
        'cost_estimate' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function risk()
    {
        return $this->belongsTo(Risk::class, 'risk_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function entity()
    {
        return $this->belongsTo(Entite::class, 'entity_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tasks()
    {
        return $this->hasMany(RiskActionTask::class, 'plan_id');
    }

    public function comments()
    {
        return $this->hasMany(RiskActionComment::class, 'plan_id');
    }

    public function logs()
    {
        return $this->hasMany(RiskActionLog::class, 'plan_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('target_date', '<', now())
            ->whereNotNull('target_date');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Accessors & Mutators ─────────────────────────────────────────────

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'completed' && 
               $this->target_date && 
               now() > $this->target_date;
    }

    public function getDaysBehindAttribute()
    {
        if (!$this->target_date) return 0;
        $days = now()->diffInDays($this->target_date, false);
        return max(0, $days);
    }

    public function getTasksProgressAttribute()
    {
        if ($this->tasks->isEmpty()) return 0;
        $completed = $this->tasks->where('status', 'completed')->count();
        return round(($completed / $this->tasks->count()) * 100);
    }

    // ─── Methods ───────────────────────────────────────────────────────────

    /**
     * Génère un code unique pour le plan
     */
    public static function generateCode()
    {
        $lastPlan = static::orderBy('id', 'desc')->first();
        $lastNum = $lastPlan ? intval(substr($lastPlan->code, -4)) : 0;
        $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        
        return 'AP-' . now()->year . '-' . $newNum;
    }

    /**
     * Marque le plan comme complété
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completion_date' => now(),
            'progress' => 100,
        ]);

        RiskActionLog::create([
            'tenant_id' => $this->tenant_id,
            'plan_id' => $this->id,
            'action' => 'completed',
            'description' => 'Plan d\'action marqué comme complété',
            'user_id' => auth()->id(),
        ]);
    }
}

/**
 * RiskActionTask
 * 
 * Modèle pour les tâches d'un plan d'action
 */


/**
 * RiskActionComment
 * 
 * Modèle pour les commentaires sur les plans d'action
 */
class RiskActionComment extends Model
{
    protected $table = 'risk_action_comments';
    protected $fillable = [
        'tenant_id', 'plan_id', 'comment', 'is_internal', 'user_id',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(RiskActionPlan::class, 'plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

/**
 * RiskActionLog
 * 
 * Modèle pour l'historique des plans d'action
 */
class RiskActionLog extends Model
{
    public $timestamps = false;
    
    protected $table = 'risk_action_logs';
    protected $fillable = [
        'tenant_id', 'plan_id', 'action', 'description', 'user_id', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(RiskActionPlan::class, 'plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Boot ──────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONFIGURATION - config/risk.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

// config/risk.php

return [
    /*
    |--------------------------------------------------------------------------
    | Risk Management Configuration
    |--------------------------------------------------------------------------
    */

    // Priorités des actions
    'priorities' => [
        [
            'value' => 'critical',
            'label' => 'Critique',
            'color' => '#ef4444',
            'icon' => 'ti ti-alert-circle',
        ],
        [
            'value' => 'high',
            'label' => 'Haute',
            'color' => '#f59e0b',
            'icon' => 'ti ti-alert-triangle',
        ],
        [
            'value' => 'medium',
            'label' => 'Moyenne',
            'color' => '#3b82f6',
            'icon' => 'ti ti-alert',
        ],
        [
            'value' => 'low',
            'label' => 'Basse',
            'color' => '#10b981',
            'icon' => 'ti ti-info-circle',
        ],
    ],

    // Statuts des actions
    'action_statuses' => [
        [
            'value' => 'pending',
            'label' => 'En attente',
            'color' => '#f1f5f9',
            'text_color' => '#475569',
            'icon' => 'ti ti-hourglass-empty',
        ],
        [
            'value' => 'in_progress',
            'label' => 'En cours',
            'color' => '#dbeafe',
            'text_color' => '#1d4ed8',
            'icon' => 'ti ti-player-play',
        ],
        [
            'value' => 'review',
            'label' => 'En révision',
            'color' => '#ede9fe',
            'text_color' => '#7c3aed',
            'icon' => 'ti ti-eye',
        ],
        [
            'value' => 'completed',
            'label' => 'Complétée',
            'color' => '#dcfce7',
            'text_color' => '#16a34a',
            'icon' => 'ti ti-check-circle',
        ],
        [
            'value' => 'cancelled',
            'label' => 'Annulée',
            'color' => '#fee2e2',
            'text_color' => '#dc2626',
            'icon' => 'ti ti-ban',
        ],
        [
            'value' => 'blocked',
            'label' => 'Bloquée',
            'color' => '#fef3c7',
            'text_color' => '#d97706',
            'icon' => 'ti ti-lock',
        ],
    ],

    // Zones de risque (pour le code couleur)
    'risk_zones' => [
        [
            'code' => 'GREEN',
            'label' => 'Acceptable',
            'color' => '#10b981',
            'score_min' => 1,
            'score_max' => 6,
        ],
        [
            'code' => 'YELLOW',
            'label' => 'À surveiller',
            'color' => '#f59e0b',
            'score_min' => 7,
            'score_max' => 12,
        ],
        [
            'code' => 'ORANGE',
            'label' => 'À traiter',
            'color' => '#f97316',
            'score_min' => 13,
            'score_max' => 18,
        ],
        [
            'code' => 'RED',
            'label' => 'Critique',
            'color' => '#ef4444',
            'score_min' => 19,
            'score_max' => 25,
        ],
    ],

    // Décisions de traitement du risque
    'treatment_decisions' => [
        [
            'code' => 'ACCEPTE',
            'label' => 'Accepté',
            'description' => 'Risque accepté tel quel',
            'color' => '#10b981',
            'auto_create_plan' => false,
        ],
        [
            'code' => 'REDUIT',
            'label' => 'Réduit',
            'description' => 'Risque réduit par des mesures de contrôle',
            'color' => '#f59e0b',
            'auto_create_plan' => true,
            'default_priority' => 'high',
        ],
        [
            'code' => 'TRANSFERE',
            'label' => 'Transféré',
            'description' => 'Risque transféré à une autre entité',
            'color' => '#3b82f6',
            'auto_create_plan' => false,
        ],
        [
            'code' => 'REFUSE',
            'label' => 'Refusé',
            'description' => 'Risque inacceptable',
            'color' => '#ef4444',
            'auto_create_plan' => true,
            'default_priority' => 'critical',
        ],
        [
            'code' => 'MITIGE',
            'label' => 'Atténué',
            'description' => 'Risque atténué par des actions correctives',
            'color' => '#8b5cf6',
            'auto_create_plan' => true,
            'default_priority' => 'high',
        ],
    ],

    // Permissions
    'permissions' => [
        'view_action_tracking' => 'Voir le suivi des actions',
        'edit_action_tracking' => 'Modifier le suivi des actions',
        'view_reports' => 'Voir les rapports',
        'export_reports' => 'Exporter les rapports',
    ],

    // Paramètres de rapport
    'report' => [
        'items_per_page' => 50,
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'timezone' => 'UTC',
    ],
];

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ÉVÉNEMENTS - Events/RiskActionPlanEvent.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace App\Events;

use App\Models\RiskActionPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskActionPlanCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public RiskActionPlan $plan)
    {
    }
}

class RiskActionPlanUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public RiskActionPlan $plan, public array $changes)
    {
    }
}

class RiskActionPlanCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public RiskActionPlan $plan)
    {
    }
}

class RiskActionPlanOverdue
{
    use Dispatchable, SerializesModels;

    public function __construct(public RiskActionPlan $plan)
    {
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * LISTENERS - Listeners/SendActionNotifications.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace App\Listeners;

use App\Events\RiskActionPlanOverdue;
use App\Notifications\ActionOverdueNotification;

class SendActionNotifications
{
    public function handle(RiskActionPlanOverdue $event)
    {
        if ($event->plan->assignedTo) {
            $event->plan->assignedTo->notify(
                new ActionOverdueNotification($event->plan)
            );
        }
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MIGRATIONS - database/migrations/create_risk_action_tables.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Pour les tables déjà existantes, vérifiez les migrations correspondantes
// Les tables suivantes doivent exister:
// - risk_action_plans
// - risk_action_tasks
// - risk_action_comments
// - risk_action_logs
// - risk_action_statuses (configuration)

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * POLICIES - Policies/RiskActionPlanPolicy.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace App\Policies;

use App\Models\User;
use App\Models\RiskActionPlan;

class RiskActionPlanPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole(['admin', 'risk_manager']);
    }

    public function view(User $user, RiskActionPlan $plan)
    {
        return $user->tenant_id === $plan->tenant_id &&
               $user->hasRole(['admin', 'risk_manager']);
    }

    public function create(User $user)
    {
        return $user->hasRole(['admin', 'risk_manager']);
    }

    public function update(User $user, RiskActionPlan $plan)
    {
        return $user->tenant_id === $plan->tenant_id &&
               ($user->hasRole('admin') ||
                ($user->id === $plan->assigned_to && $user->hasRole('risk_manager')));
    }

    public function delete(User $user, RiskActionPlan $plan)
    {
        return $user->tenant_id === $plan->tenant_id &&
               $user->hasRole('admin');
    }
}