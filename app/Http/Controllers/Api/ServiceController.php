<?php
// app/Http/Controllers/Api/ServiceController.php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Domain\Catalog\Actions\RegisterService;
use App\Domain\Catalog\DTOs\ServiceDTO;
use App\Models\Master\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller {
  public function index(){ return ServiceResource::collection(Service::with('modules')->orderBy('name')->get()); }
  public function store(Request $r, RegisterService $reg){
    $d=$r->validate(['code'=>'required|max:50|alpha_dash|unique:services,code','name'=>'required|max:100','icon'=>'nullable|max:100','description'=>'nullable','base_path'=>'nullable|max:255','is_active'=>'boolean']);
    $svc=$reg->execute(new ServiceDTO(...$d)); return new ServiceResource($svc->load('modules'));
  }
  public function show(Service $service){ return new ServiceResource($service->load('modules')); }
  public function update(Request $r, Service $service, RegisterService $reg){
    $d=$r->validate(['code'=>'required|max:50|alpha_dash|unique:services,code,'.$service->id,'name'=>'required|max:100','icon'=>'nullable|max:100','description'=>'nullable','base_path'=>'nullable|max:255','is_active'=>'boolean']);
    $svc=$reg->execute(new ServiceDTO(...$d)); return new ServiceResource($svc->load('modules'));
  }
  public function destroy(Service $service){ $service->delete(); return response()->noContent(); }
}
