<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pivots\ModuleUser;

class Module extends Model
{
    protected $connection = 'mysql'; // maître
    protected $table = 'modules';

    protected $fillable = [
        'service_id','code','name','icon','route_prefix','entry_route_name',
        'route_web_file','route_api_file','sort','is_active'
    ];

    public function getRouteKeyName(): string { return 'code'; }

    public function service() { return $this->belongsTo(Service::class); }

    public function permissions()
    {
        return $this->belongsToMany(\Spatie\Permission\Models\Permission::class, 'module_permission');
    }

    public function menus() { return $this->hasMany(Menu::class); }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'module_user', 'module_id', 'user_id')
            ->using(ModuleUser::class)
            ->withTimestamps();
    }
    // app/Models/Master/Module.php

}
