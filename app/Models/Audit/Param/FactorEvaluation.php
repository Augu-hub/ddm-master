<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class FactorEvaluation extends Model
{
    protected $table = 'audit_factor_evaluations';
    protected $fillable = ['entity_id', 'process_id', 'factor_id', 'score', 'normalized_score', 'justification', 'evaluation_year'];
    protected $casts = ['score' => 'integer', 'normalized_score' => 'float', 'evaluation_year' => 'integer'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function process(): BelongsTo { return $this->belongsTo(Processus::class); }
    public function factor(): BelongsTo { return $this->belongsTo(Factor::class); }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($eval) {
            $eval->normalized_score = $eval->score ? round($eval->score / 4, 2) : 0;
        });
    }
}
