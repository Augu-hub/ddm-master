<?php
// app/Http/Controllers/Api/ModuleController.php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModuleResource;
use App\Domain\Catalog\Actions\RegisterModule;
use App\Domain\Catalog\DTOs\ModuleDTO;
use App\Domain\Catalog\Services\ModuleCatalog;
use App\Models\Master\{Module,Service};
use Illuminate\Http\Request;

class ModuleController extends Controller {
  public function index(Request $r){
    $q=Module::with('service')->orderBy('sort');
    if($r->filled('service')) $q->whereHas('service',fn($x)=>$x->where('code',$r->string('service')));
    return ModuleResource::collection($q->get());
  }
  public function store(Request $r, RegisterModule $reg){
    $d=$r->validate(['service'=>'required|exists:services,code','code'=>'required|max:100|alpha_dash|unique:modules,code','name'=>'required|max:120','icon'=>'nullable|max:100','route_prefix'=>'nullable|max:30','entry_route_name'=>'nullable|max:150','route_web_file'=>'nullable|max:255','route_api_file'=>'nullable|max:255','sort'=>'nullable|integer','is_active'=>'boolean']);
    $svc=Service::where('code',$d['service'])->firstOrFail();
    $mod=$reg->execute(new ModuleDTO($svc->id,$d['code'],$d['name'],$d['icon']??null,$d['route_prefix']??'m',$d['entry_route_name']??null,$d['route_web_file']??null,$d['route_api_file']??null,$d['sort']??0,$d['is_active']??true));
    return new ModuleResource($mod->load('service'));
  }
  public function show(Module $module){ return new ModuleResource($module->load('service')); }
  public function update(Request $r, Module $module, RegisterModule $reg){
    $d=$r->validate(['service'=>'required|exists:services,code','code'=>'required|max:100|alpha_dash|unique:modules,code,'.$module->id,'name'=>'required|max:120','icon'=>'nullable|max:100','route_prefix'=>'nullable|max:30','entry_route_name'=>'nullable|max:150','route_web_file'=>'nullable|max:255','route_api_file'=>'nullable|max:255','sort'=>'nullable|integer','is_active'=>'boolean']);
    $svc=Service::where('code',$d['service'])->firstOrFail();
    $mod=$reg->execute(new ModuleDTO($svc->id,$d['code'],$d['name'],$d['icon']??null,$d['route_prefix']??'m',$d['entry_route_name']??null,$d['route_web_file']??null,$d['route_api_file']??null,$d['sort']??0,$d['is_active']??true));
    return new ModuleResource($mod->load('service'));
  }
  public function destroy(Module $module){ $module->delete(); return response()->noContent(); }

  // menu “modules autorisés” de l’utilisateur
  public function myModules(ModuleCatalog $catalog){
    return ModuleResource::collection($catalog->forUser(auth()->id()));
  }
}
