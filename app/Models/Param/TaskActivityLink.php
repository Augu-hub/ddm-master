<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivityLink extends Model
{
    protected $table = 'task_activity_links';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = 'tenant';
    }

    protected $fillable = [
        'process_id',
        'task_id',           // ID volatile (optionnel)
        'task_name',         // Clé principale
        'activity_id',
        'activity_name',
        'activity_code',
        'task_color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processus::class, 'process_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activite::class, 'activity_id');
    }

    public function scopeByTaskName($query, $processId, $taskName)
    {
        return $query->where('process_id', $processId)
                     ->where('task_name', $taskName);
    }

    public function scopeForProcess($query, $processId)
    {
        return $query->where('process_id', $processId)
                     ->with('activity')
                     ->orderBy('task_name');
    }
}
