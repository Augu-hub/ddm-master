<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenceFlow extends Model
{
    protected $table = 'sequence_flows';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = 'tenant';
    }

    protected $fillable = [
        'process_id',
        'source_task_id',       // ID volatile (optionnel)
        'source_task_name',     // Clé principale
        'target_task_id',       // ID volatile (optionnel)
        'target_task_name',     // Clé principale
        'sequence_id',
        'sequence_name',
        'sequence_condition',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processus::class, 'process_id');
    }

    public function scopeByTasks($query, $processId, $sourceName, $targetName)
    {
        return $query->where('process_id', $processId)
                     ->where('source_task_name', $sourceName)
                     ->where('target_task_name', $targetName)
                     ->first();
    }

    public function scopeForProcess($query, $processId)
    {
        return $query->where('process_id', $processId)
                     ->orderBy('source_task_name')
                     ->orderBy('target_task_name');
    }
}
