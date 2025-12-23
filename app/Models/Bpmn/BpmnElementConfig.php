<?php

namespace App\Models\Bpmn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpmnElementConfig extends Model
{
    protected $table = 'bpmn_element_configs';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'bpmn_diagram_id',
        'element_id',
        'element_type',
        'icon_class',
        'custom_properties',
    ];
    
    protected $casts = [
        'custom_properties' => 'array',
    ];
    
    public function diagram(): BelongsTo
    {
        return $this->belongsTo(BpmnDiagram::class);
    }
}