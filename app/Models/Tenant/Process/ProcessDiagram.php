<?php // app/Models/Process/ProcessDiagram.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ProcessDiagram extends Model
{
    protected $connection = 'tenant';
    protected $table = 'process_diagrams';
    protected $fillable = ['process_id','bpmn_xml','elements','version','created_by'];
    protected $casts = ['elements'=>'array'];
    public function process(){ return $this->belongsTo(Process::class); }
}
