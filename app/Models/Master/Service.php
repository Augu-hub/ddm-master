<?php
// app/Models/Master/Service.php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;

class Service extends Model {
  protected $connection='mysql';
  protected $fillable=['code','name','icon','description','base_path','is_active'];
  public function getRouteKeyName(): string { return 'code'; }
  public function modules(){ return $this->hasMany(Module::class); }
  public function menus(){ return $this->hasMany(Menu::class); }
}
