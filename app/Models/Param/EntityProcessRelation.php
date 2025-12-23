<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntityProcessRelation extends Model
{
    use SoftDeletes;

    protected $table = 'entity_process_relations';

    protected $fillable = [
        'entity_id',
        'source_process_id',
        'target_process_id',
        'source_port',
        'target_port',
        'link_type',
        'control_x',
        'control_y',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'control_x' => 'float',
        'control_y' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function entity()
    {
        return $this->belongsTo(Entite::class);
    }

    public function sourceProcess()
    {
        return $this->belongsTo(Processus::class, 'source_process_id');
    }

    public function targetProcess()
    {
        return $this->belongsTo(Processus::class, 'target_process_id');
    }

    /**
     * Scopes
     */
    public function scopeByEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeByLinkType($query, $linkType)
    {
        return $query->where('link_type', $linkType);
    }

    public function scopeFromProcess($query, $processId)
    {
        return $query->where('source_process_id', $processId);
    }

    public function scopeToProcess($query, $processId)
    {
        return $query->where('target_process_id', $processId);
    }

    public function scopeBySourcePort($query, $port)
    {
        return $query->where('source_port', $port);
    }

    public function scopeByTargetPort($query, $port)
    {
        return $query->where('target_port', $port);
    }

    /**
     * Accessors & Mutators
     */
    public function getLinkTypeLabel()
    {
        $labels = [
            'arrow'         => 'Flèche',
            'double_arrow'  => 'Double Flèche',
            'dashed'        => 'Pointillé',
            'loop'          => 'Boucle',
            'custom'        => 'Custom',
        ];

        return $labels[$this->link_type] ?? $this->link_type;
    }

    public function getPortLabel($port)
    {
        $labels = [
            'top'    => 'Haut',
            'bottom' => 'Bas',
            'left'   => 'Gauche',
            'right'  => 'Droite',
        ];

        return $labels[$port] ?? $port;
    }

    /**
     * Autres méthodes utiles
     */
    public function getSourceName()
    {
        return $this->sourceProcess?->name ?? 'N/A';
    }

    public function getTargetName()
    {
        return $this->targetProcess?->name ?? 'N/A';
    }

    public function getSourceCode()
    {
        return $this->sourceProcess?->code ?? 'N/A';
    }

    public function getTargetCode()
    {
        return $this->targetProcess?->code ?? 'N/A';
    }

    public function getControlPoint()
    {
        if ($this->control_x === null || $this->control_y === null) {
            return null;
        }

        return [
            'x' => $this->control_x,
            'y' => $this->control_y,
        ];
    }

    public function setControlPoint($x, $y)
    {
        $this->control_x = $x;
        $this->control_y = $y;
        return $this;
    }

    public function getConnectionInfo()
    {
        return [
            'from' => [
                'process' => $this->getSourceCode(),
                'port'    => $this->getPortLabel($this->source_port),
            ],
            'to' => [
                'process' => $this->getTargetCode(),
                'port'    => $this->getPortLabel($this->target_port),
            ],
            'type' => $this->getLinkTypeLabel(),
        ];
    }
}