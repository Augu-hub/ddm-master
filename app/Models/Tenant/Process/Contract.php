<?php


namespace App\Models\Tenant\Process;
use Illuminate\Database\Eloquent\Model;
use App\Models\Param\Processus;
use App\Models\User;

class Contract extends Model
{
    protected $fillable = [
        'user_id',
        'process_id',
        'title',
        'description',
    ];

    public function process()
    {
        return $this->belongsTo(Processus::class, 'process_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function elements()
    {
        return $this->hasMany(ContractElement::class);
    }

   
public function creator()
{
    return $this->belongsTo(User::class, 'user_id');
}

}
