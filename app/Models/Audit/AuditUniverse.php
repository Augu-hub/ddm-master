<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditUniverse extends Model
{
    use HasFactory;

    protected $table = 'audit_universes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'entity_id',
        'project_id',
        'year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'year' => 'integer',
    ];

    // Relations
    public function entity()
    {
        return $this->belongsTo(\App\Models\Entity::class);
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function processes()
    {
        return $this->hasMany(Process::class);
    }

    public function risks()
    {
        return $this->hasManyThrough(Risk::class, Activity::class, 'process_id', 'activity_id');
    }
}