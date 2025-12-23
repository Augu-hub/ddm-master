<?php

namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ActivityRaciSession extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_raci_sessions';

    protected $fillable = ['entity_id', 'process_id', 'created_by_user_id', 'name', 'description', 'status', 'color', 'closed_at'];
    protected $casts = [
        'entity_id' => 'integer',
        'process_id' => 'integer',
        'created_by_user_id' => 'integer',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Entity::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ActivityRaciSessionAssignment::class, 'session_id');
    }

    /**
     * Analyser les risques RACI
     */
    public function analyzeRisks()
    {
        $t = DB::connection('tenant');

        // Récupérer tous les assignments de cette session
        $assignments = $t->table('activity_raci_assignments as ara')
            ->join('activities as a', 'a.id', '=', 'ara.activity_id')
            ->join('activity_raci_roles as arr', 'arr.id', '=', 'ara.raci_role_id')
            ->where('a.process_id', $this->process_id)
            ->select('ara.activity_id', 'a.code as activity_code', 'a.name as activity_name', 'arr.code as role_code')
            ->get()
            ->groupBy('activity_id');

        $risks = [
            'no_accountable' => [],
            'multiple_accountable' => [],
            'no_responsible' => [],
            'overlap_ra' => [],
            'excessive_consulted' => [],
            'excessive_informed' => []
        ];

        foreach ($assignments as $activityId => $roles) {
            $rolesArray = $roles->pluck('role_code')->toArray();
            $activityName = $roles[0]->activity_name;

            // Pas de "A"
            if (!in_array('A', $rolesArray)) {
                $risks['no_accountable'][] = $activityName;
            }

            // Plusieurs "A"
            if (count(array_filter($rolesArray, fn($r) => $r === 'A')) > 1) {
                $risks['multiple_accountable'][] = $activityName;
            }

            // Pas de "R"
            if (!in_array('R', $rolesArray)) {
                $risks['no_responsible'][] = $activityName;
            }

            // "R" et "A" pour la même personne (problématique)
            // À implémenter si vous stockez les assignments par personne

            // Trop de "C"
            $countC = count(array_filter($rolesArray, fn($r) => $r === 'C'));
            if ($countC > 3) {
                $risks['excessive_consulted'][] = $activityName;
            }

            // Trop de "I"
            $countI = count(array_filter($rolesArray, fn($r) => $r === 'I'));
            if ($countI > 5) {
                $risks['excessive_informed'][] = $activityName;
            }
        }

        return $risks;
    }

    public function close()
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);
    }

    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    public function restore()
    {
        $this->update([
            'status' => 'active',
            'closed_at' => null
        ]);
    }
}