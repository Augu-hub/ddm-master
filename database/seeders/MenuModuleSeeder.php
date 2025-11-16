<?php
// database/seeders/MenuModuleSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Domain\Catalog\Actions\RegisterMenu;
use App\Domain\Catalog\DTOs\MenuDTO;
use App\Models\Master\Module;

class MenuModuleSeeder extends Seeder {
  public function run(): void {
    $mod = Module::where('code','param.projects')->firstOrFail();
    app(RegisterMenu::class)->execute(new MenuDTO('param.projects.title','Gestion des projets','title',null,null,null,null,null,1,null,$mod->id,true));
    app(RegisterMenu::class)->execute(new MenuDTO('param.projects.home','Accueil','item','ti ti-home',null,'param.projects.home',null,null,2,null,$mod->id,true));
    app(RegisterMenu::class)->execute(new MenuDTO('param.projects.index','Liste des projets','item','ti ti-list-details',null,'param.projects.projects.index',null,null,3,null,$mod->id,true));
    app(RegisterMenu::class)->execute(new MenuDTO('param.projects.divider1','', 'divider',null,null,null,null,null,4,null,$mod->id,true));
    app(RegisterMenu::class)->execute(new MenuDTO('param.projects.create','Nouveau projet','item','ti ti-plus',null,'param.projects.projects.create',null,null,5,null,$mod->id,true));
  }
}
