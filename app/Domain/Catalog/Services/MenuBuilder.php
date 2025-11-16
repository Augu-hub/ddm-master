<?php
// app/Domain/Catalog/Services/MenuBuilder.php
namespace App\Domain\Catalog\Services;
use App\Models\Master\Menu;

class MenuBuilder {
  public function forModule(int $moduleId, int $userId){
    $roots = Menu::with('children','permissions','users')
      ->whereNull('parent_id')->where('module_id',$moduleId)->where('visible',true)->orderBy('sort')->get();
    return $this->filterVisible($roots, $userId);
  }

  protected function filterVisible($menus, int $userId){
    return $menus->filter(function($m) use($userId){
      if ($m->permissions()->exists()){
        foreach($m->permissions as $p){ if(auth()->user()?->can($p->name)) return true; }
        return false;
      }
      if ($m->users()->exists()){
        return $m->users->contains('id', $userId);
      }
      return true;
    })->map(function($m) use($userId){
      $m->setRelation('children', $this->filterVisible($m->children, $userId));
      return $m;
    })->values();
  }
}
