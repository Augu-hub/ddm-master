<?php
// app/Domain/Catalog/Services/ModuleCatalog.php
namespace App\Domain\Catalog\Services;
use App\Models\Master\Module;

class ModuleCatalog {
  public function forUser(int $userId) {
    return Module::with('service')->where('is_active',true)
      ->whereHas('users', fn($q)=>$q->where('user_id',$userId))
      ->orderBy('sort')->get();
  }
  public function allActive() { return Module::with('service')->where('is_active',true)->orderBy('sort')->get(); }
}
