<?php

namespace App\Models\Bpmn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpmnDiagramVersion extends Model
{
    protected $table = 'bpmn_diagram_versions';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'bpmn_diagram_id',
        'version_number',
        'bpmn_xml',
        'change_description',
        'task_links_count',
        'sequence_flows_count',
        'created_by',
    ];
    
    protected $casts = [
        'version_number' => 'integer',
        'task_links_count' => 'integer',
        'sequence_flows_count' => 'integer',
    ];
    
    public function diagram(): BelongsTo
    {
        return $this->belongsTo(BpmnDiagram::class);
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}