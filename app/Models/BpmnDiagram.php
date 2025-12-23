<?php

namespace App\Models\Bpmn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BpmnDiagram extends Model
{
    protected $table = 'bpmn_diagrams';
    protected $connection = 'tenant';
    
    protected $fillable = [
        'process_id',
        'bpmn_xml',
        'version',
        'is_current',
        'created_by',
    ];
    
    protected $casts = [
        'is_current' => 'boolean',
        'version' => 'integer',
    ];
    
    public function process(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Param\Processus::class);
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    
    public function taskLinks(): HasMany
    {
        return $this->hasMany(BpmnTaskLink::class);
    }
    
    public function sequenceFlows(): HasMany
    {
        return $this->hasMany(BpmnSequenceFlow::class);
    }
    
    public function elementConfigs(): HasMany
    {
        return $this->hasMany(BpmnElementConfig::class);
    }
    
    public function versions(): HasMany
    {
        return $this->hasMany(BpmnDiagramVersion::class);
    }
}