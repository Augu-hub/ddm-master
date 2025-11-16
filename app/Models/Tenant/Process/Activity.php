<?php // app/Models/Process/Activity.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $connection = 'tenant';
    protected $table = 'activities';
    protected $fillable = ['process_id','code','name','description'];

    public function process(){ return $this->belongsTo(Process::class); }
    public function steps(){ return $this->hasMany(ActivityStep::class); }
    public function flows(){ return $this->hasMany(ActivityFlow::class); }
    public function controls(){ return $this->hasMany(ActivityControl::class); }
    public function raci(){ return $this->hasMany(ActivityRaci::class); }
    public function idea(){ return $this->hasMany(ActivityIdea::class); }
}
