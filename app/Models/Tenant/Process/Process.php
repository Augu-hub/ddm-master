<?php // app/Models/Process/Process.php
namespace App\Models\Tenant\Process;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $connection = 'tenant';
    protected $table = 'processes';
    protected $fillable = ['macro_process_id','code','name'];

    public function activities(){ return $this->hasMany(Activity::class); }
    public function evaluations(){ return $this->hasMany(ProcessEvaluation::class); }
    public function kpis(){ return $this->hasMany(ProcessKpi::class); }
    public function diagrams(){ return $this->hasMany(ProcessDiagram::class); }
    public function parentLinks(){ return $this->hasMany(ProcessLink::class,'parent_process_id'); }
    public function childLinks(){ return $this->hasMany(ProcessLink::class,'child_process_id'); }
}
