<?php

namespace App\Models\Bpmn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpmnSequenceFlow extends Model
{
    protected $table = 'bpmn_sequence_flows';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'bpmn_diagram_id',
        'process_id',
        'sequence_id',
        'sequence_name',
        'source_element_id',
        'source_element_name',
        'target_element_id',
        'target_element_name',
        'condition_expression',
    ];
    
    public function diagram(): BelongsTo
    {
        return $this->belongsTo(BpmnDiagram::class);
    }
}