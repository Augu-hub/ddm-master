<?php // app/Models/Process/ActivityIdea.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ActivityIdea extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activity_idea';
    protected $fillable = ['activity_id','actor_id','role','type','color'];
    public function activity(){ return $this->belongsTo(Activity::class); }
}
