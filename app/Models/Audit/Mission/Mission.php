<?php

namespace App\Models;

use App\Models\Audit\AuditExercise;
use App\Models\Audit\Mission\MissionType;
use App\Models\Audit\Risk;
use App\Models\Param\Competency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'fpm_number',
        'audit_exercise_id',
        'mission_type_id',
        'title',
        'objective',
        'domain',
        'reference_document',
        'priority',
        'planned_start_date',
        'planned_end_date',
        'planned_duration_days',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date'   => 'date',
    ];

    // Relations
    public function exercise()
    {
        return $this->belongsTo(AuditExercise::class, 'audit_exercise_id');
    }

    public function type()
    {
        return $this->belongsTo(MissionType::class, 'mission_type_id');
    }

    public function risks()
    {
        return $this->belongsToMany(Risk::class, 'mission_risk');
    }

    public function competencies()
    {
        return $this->belongsToMany(Competency::class, 'mission_competency')
            ->withPivot('minimum_level')
            ->orderBy('code');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}