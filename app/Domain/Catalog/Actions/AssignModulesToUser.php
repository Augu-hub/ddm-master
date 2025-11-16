<?php
// app/Domain/Catalog/Actions/AssignModulesToUser.php
namespace App\Domain\Catalog\Actions;
use App\Domain\Catalog\DTOs\ModuleAssignmentDTO;
use App\Models\Master\Module;
use Illuminate\Support\Facades\DB;

class AssignModulesToUser {
  /** @param ModuleAssignmentDTO[] $assignments */
  public function execute(array $assignments): void {
    DB::connection('mysql')->transaction(function() use ($assignments){
      foreach($assignments as $dto){
        $mod = Module::findOrFail($dto->module_id);
        $mod->users()->syncWithoutDetaching([$dto->user_id]);
      }
    });
  }
}
