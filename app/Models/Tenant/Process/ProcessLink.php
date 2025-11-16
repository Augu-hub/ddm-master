<?php // app/Models/Process/ProcessLink.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ProcessLink extends Model
{
    protected $connection = 'tenant';
    protected $table = 'process_links';
    protected $fillable = ['parent_process_id','child_process_id','type','flow'];
    protected $casts = ['flow'=>'array'];
    public function parent(){ return $this->belongsTo(Process::class,'parent_process_id'); }
    public function child(){ return $this->belongsTo(Process::class,'child_process_id'); }
}
