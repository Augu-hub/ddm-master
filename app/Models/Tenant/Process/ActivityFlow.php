<?php // app/Models/Process/ActivityFlow.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ActivityFlow extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_flows';
    protected $fillable = ['activity_id','type','label','format','source','destination','meta'];
    protected $casts = ['meta'=>'array'];
    public function activity(){ return $this->belongsTo(Activity::class); }
}
