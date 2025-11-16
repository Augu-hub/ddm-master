<?php // app/Models/Process/ActivityControl.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ActivityControl extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_controls';
    protected $fillable = ['activity_id','nature','label','frequency','evidence','level','actor_id','effectiveness'];
    protected $casts = ['effectiveness'=>'float'];
    public function activity(){ return $this->belongsTo(Activity::class); }
}
