<?php




// ════════════════════════════════════════════════════════════════════════════
// 6️⃣ MODEL: RiskControl
// ════════════════════════════════════════════════════════════════════════════
namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskControl extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'risk_id', 'activity_control_id', 'code', 'label',
        'description', 'type', 'frequency', 'evidence', 'function_id',
        'owner', 'status', 'effective_from', 'effective_to'
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }
}
