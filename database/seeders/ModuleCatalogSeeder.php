<?php
// database/seeders/ModuleCatalogSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Domain\Catalog\Actions\{RegisterService,RegisterModule};
use App\Domain\Catalog\DTOs\{ServiceDTO,ModuleDTO};

class ModuleCatalogSeeder extends Seeder {
  public function run(): void {
    $svc = app(RegisterService::class)->execute(new ServiceDTO('param','Paramétrage','ti ti-settings','Paramétrage','app/Modules/Param'));
    app(RegisterModule::class)->execute(new ModuleDTO(
      service_id:$svc->id, code:'param.projects', name:'Projets', icon:'ti ti-folders',
      route_prefix:'m', entry_route_name:'param.projects.home',
      route_web_file:'app/Modules/Param/Projects/routes/web.php',
      route_api_file:'app/Modules/Param/Projects/routes/api.php',
      sort:10, is_active:true
    ));
  }
}
