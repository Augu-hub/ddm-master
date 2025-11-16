<?php // app/Models/Process/ProcessEvaluation.php
namespace App\Models\Process;

use Illuminate\Database\Eloquent\Model;

class ProcessEvaluation extends Model
{
    protected $connection = 'tenant';
    protected $table = 'process_evaluations';
    protected $fillable = ['process_id','maturity','motricity','transversality','strategic','criticality','evaluated_at','user_id'];
    protected $casts = ['criticality'=>'float','evaluated_at'=>'datetime'];
    public function process(){ return $this->belongsTo(Process::class); }
}
