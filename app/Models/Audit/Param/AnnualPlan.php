<?php

namespace App\Models\Audit\Param;

use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class AnnualPlan extends Model
{
    protected $table = 'audit_annual_plans';
    protected $fillable = ['entity_id', 'fiscal_year', 'strategy', 'total_budget', 'status'];
    protected $casts = ['fiscal_year' => 'integer', 'total_budget' => 'integer'];

    public function entity(): BelongsTo { return $this->belongsTo(Entite::class); }
    public function missions(): HasMany { return $this->hasMany(Mission::class, 'annual_plan_id'); }
}
