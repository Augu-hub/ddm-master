<?php
namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
  protected $connection = 'mysql';
  protected $fillable = [
    'key','label','type','icon','url','route_name','target',
    'parent_id','sort','service_id','module_id','visible',
    'badge_json','tooltip_json','meta_json'
  ];

  protected $casts = [
    'visible'=>'boolean',
    'badge_json'=>'array',
    'tooltip_json'=>'array',
    'meta_json'=>'array',
  ];

  public function parent(){ return $this->belongsTo(Menu::class,'parent_id'); }
  public function children(){ return $this->hasMany(Menu::class,'parent_id')->orderBy('sort'); }

  public function service(){ return $this->belongsTo(Service::class); }
  public function module(){ return $this->belongsTo(Module::class); }

  public function permissions(){ return $this->belongsToMany(\Spatie\Permission\Models\Permission::class,'menu_permission'); }
  public function users(){ return $this->belongsToMany(\App\Models\User::class,'menu_user'); }
}
