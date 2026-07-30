<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gel de l'évaluation (inhérent / résiduel / cible) d'un risque au sein
 * d'une session. Contexte et libellés de zone dénormalisés pour que la
 * comparaison reste stable même si le registre ou la matrice évolue.
 */
class RiskSessionSnapshot extends Model
{
    protected $connection = 'tenant';
    protected $table      = 'risk_session_snapshots';

    protected $fillable = [
        'tenant_id', 'session_id', 'risk_id',
        'code_risk', 'libelle',
        'entity_id', 'entity_name', 'activity_id', 'activity_name',
        'process_id', 'process_name', 'macro_process_id', 'macro_process_name',
        'inh_impact_score', 'inh_freq_score', 'inh_criticality', 'inh_zone_id', 'inh_zone_label', 'inh_zone_color',
        'res_impact_score', 'res_freq_score', 'res_criticality', 'res_zone_id', 'res_zone_label', 'res_zone_color',
        'tgt_impact_score', 'tgt_freq_score', 'tgt_criticality', 'tgt_zone_id', 'tgt_zone_label', 'tgt_zone_color',
        'decision', 'plans_total', 'plans_done', 'plans_progress', 'captured_at',
    ];

    protected $casts = [
        'inh_criticality' => 'float',
        'res_criticality' => 'float',
        'tgt_criticality' => 'float',
        'plans_total'     => 'integer',
        'plans_done'      => 'integer',
        'plans_progress'  => 'integer',
        'captured_at'     => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RiskSession::class, 'session_id');
    }
}
