<?php
// app/Http/Controllers/Api/ModuleAssignmentController.php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Domain\Catalog\Actions\AssignModulesToUser;
use App\Domain\Catalog\DTOs\ModuleAssignmentDTO;
use App\Models\Master\Module;
use App\Models\User;
use Illuminate\Http\Request;

class ModuleAssignmentController extends Controller {
  public function store(Request $r, AssignModulesToUser $assign){
    $d=$r->validate(['user_id'=>'required|exists:users,id','module_ids'=>'required|array|min:1','module_ids.*'=>'exists:modules,id']);
    $assign->execute(array_map(fn($mid)=>new ModuleAssignmentDTO($mid,$d['user_id']),$d['module_ids']));
    return response()->json(['success'=>true]);
  }
  public function destroy(Request $r){
    $d=$r->validate(['user_id'=>'required|exists:users,id','module_id'=>'required|exists:modules,id']);
    Module::findOrFail($d['module_id'])->users()->detach($d['user_id']);
    return response()->noContent();
  }
}
