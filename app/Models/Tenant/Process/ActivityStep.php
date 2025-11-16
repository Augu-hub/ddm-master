<?php // app/Models/Process/ActivityStep.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ActivityStep extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_steps';
    protected $fillable = ['activity_id','code','label','description','order','estimated_minutes'];
    public function activity(){ return $this->belongsTo(Activity::class); }
}
