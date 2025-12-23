<?php

namespace App\Models\Bpmn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpmnTaskLink extends Model
{
    protected $table = 'bpmn_task_links';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'bpmn_diagram_id',
        'process_id',
        'element_id',
        'element_name',
        'element_type',
        'color_hex',
        'activity_id',
        'activity_name',
        'activity_code',
    ];
    
    public function diagram(): BelongsTo
    {
        return $this->belongsTo(BpmnDiagram::class);
    }
    
    public function activity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Activite::class);
    }
}