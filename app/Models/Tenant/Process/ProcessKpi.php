<?php // app/Models/Process/ProcessKpi.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ProcessKpi extends Model
{
    protected $connection = 'tenant';
    protected $table = 'process_kpis';
    protected $fillable = ['process_id','name','unit','target','current_value','trend','meta'];
    protected $casts = ['meta'=>'array','target'=>'float','current_value'=>'float'];
    public function process(){ return $this->belongsTo(Process::class); }
}
