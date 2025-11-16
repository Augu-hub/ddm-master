<?php // app/Models/Process/ActivityRaci.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ActivityRaci extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_raci';
    protected $fillable = ['activity_id','actor_id','role','color'];
    public function activity(){ return $this->belongsTo(Activity::class); }
}
