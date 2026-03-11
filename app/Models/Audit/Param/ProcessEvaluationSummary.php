<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};




class ProcessEvaluationSummary extends Model
{
    protected $table = 'audit_process_evaluation_summaries';
    protected $fillable = ['entity_id', 'process_id', 'average_score', 'rating', 'audit_frequency', 'evaluation_year'];
    protected $casts = ['average_score' => 'float', 'audit_frequency' => 'integer', 'evaluation_year' => 'integer'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function process(): BelongsTo { return $this->belongsTo(Processus::class); }
}
