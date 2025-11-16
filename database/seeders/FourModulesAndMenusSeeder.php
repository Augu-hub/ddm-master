<?php
// database/seeders/FourModulesAndMenusSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Master\Service;
use App\Models\Master\Module;
use App\Models\Master\Menu;

class FourModulesAndMenusSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Reset minimal menus/pivots (on garde services/modules)
            if (Schema::hasTable('menu_user'))       DB::table('menu_user')->delete();
            if (Schema::hasTable('menu_permission')) DB::table('menu_permission')->delete();
            if (Schema::hasTable('menus'))           DB::table('menus')->delete();

            // 1) Services
            $services = [
                ['code'=>'admin',   'name'=>'Administration',        'icon'=>'ti ti-settings'],
                ['code'=>'govrisk', 'name'=>'Gouvernance & Risques', 'icon'=>'ti ti-alert-triangle'],
                ['code'=>'org',     'name'=>'Organisation',          'icon'=>'ti ti-sitemap'],
                ['code'=>'audit',   'name'=>'Audit Interne',         'icon'=>'ti ti-checklist'],
            ];
            $serviceIds = [];
            foreach ($services as $svc) {
                $m = Service::updateOrCreate(
                    ['code'=>$svc['code']],
                    [
                        'name'=>$svc['name'],
                        'icon'=>$svc['icon'],
                        'description'=>$svc['description'] ?? null,
                        'base_path'=>$svc['base_path'] ?? null,
                        'is_active'=>true,
                    ]
                );
                $serviceIds[$svc['code']] = $m->id;
            }

            // 2) Modules (inclut admin.core)
            $modules = [
                [
                    'service_code'=>'admin',
                    'code'=>'admin.core', 'name'=>'DIADEM ADMIN', 'icon'=>'ti ti-settings',
                    'entry_route_name'=>'admin.core.home',
                    'route_web_file'=>'app/Modules/Admin/Core/routes/web.php',
                    'route_api_file'=>'app/Modules/Admin/Core/routes/api.php',
                    'sort'=>5, 'is_active'=>true,
                ],
                [
                    'service_code'=>'admin',
                    'code'=>'param.projects', 'name'=>'DIADEM PARAM', 'icon'=>'ti ti-adjustments',
                    'entry_route_name'=>'param.projects.home',
                    'route_web_file'=>'app/Modules/Param/Projects/routes/web.php',
                    'route_api_file'=>'app/Modules/Param/Projects/routes/api.php',
                    'sort'=>10, 'is_active'=>true,
                ],
                [
                    'service_code'=>'govrisk',
                    'code'=>'risk.core', 'name'=>'DIADEM RISQUE', 'icon'=>'ti ti-alert-triangle',
                    'entry_route_name'=>'risk.core.home',
                    'route_web_file'=>'app/Modules/Risk/Core/routes/web.php',
                    'route_api_file'=>'app/Modules/Risk/Core/routes/api.php',
                    'sort'=>20, 'is_active'=>true,
                ],
                [
                    'service_code'=>'org',
                    'code'=>'process.core', 'name'=>'DIADEM PROCESSUS', 'icon'=>'ti ti-flow-branch',
                    'entry_route_name'=>'process.core.home',
                    'route_web_file'=>'app/Modules/Process/Core/routes/web.php',
                    'route_api_file'=>'app/Modules/Process/Core/routes/api.php',
                    'sort'=>30, 'is_active'=>true,
                ],
                [
                    'service_code'=>'audit',
                    'code'=>'audit.core', 'name'=>'DIADEM AUDIT', 'icon'=>'ti ti-clipboard-check',
                    'entry_route_name'=>'audit.core.home',
                    'route_web_file'=>'app/Modules/Audit/Core/routes/web.php',
                    'route_api_file'=>'app/Modules/Audit/Core/routes/api.php',
                    'sort'=>40, 'is_active'=>true,
                ],
            ];

            $moduleIds = [];
            foreach ($modules as $mod) {
                $svcId = $serviceIds[$mod['service_code']] ?? null;
                if (!$svcId) throw new \RuntimeException("Service introuvable: {$mod['service_code']}");

                $m = Module::updateOrCreate(
                    ['code'=>$mod['code']],
                    [
                        'service_id'=>$svcId,
                        'name'=>$mod['name'],
                        'icon'=>$mod['icon'] ?? null,
                        'route_prefix'=>'m',
                        'entry_route_name'=>$mod['entry_route_name'] ?? null,
                        'route_web_file'=>$mod['route_web_file'] ?? null,
                        'route_api_file'=>$mod['route_api_file'] ?? null,
                        'sort'=>(int)($mod['sort'] ?? 0),
                        'is_active'=>(bool)($mod['is_active'] ?? true),
                    ]
                );
                $moduleIds[$mod['code']] = $m->id;
            }

            // 3) Menus minimaux (titre + home)
            $menus = [
                // Admin
                ['key'=>'admin.core.title','label'=>'Administration','type'=>'title','module_code'=>'admin.core','sort'=>1],
                ['key'=>'admin.core.home', 'label'=>'Accueil','type'=>'item','icon'=>'ti ti-home','route_name'=>'admin.core.home','module_code'=>'admin.core','sort'=>2],

                // Param
                ['key'=>'param.projects.title','label'=>'Paramétrage','type'=>'title','module_code'=>'param.projects','sort'=>1],
                ['key'=>'param.projects.home', 'label'=>'Accueil','type'=>'item','icon'=>'ti ti-home','route_name'=>'param.projects.home','module_code'=>'param.projects','sort'=>2],

                // Risque
                ['key'=>'risk.core.title','label'=>'Gouvernance & Risques','type'=>'title','module_code'=>'risk.core','sort'=>1],
                ['key'=>'risk.core.home', 'label'=>'Accueil','type'=>'item','icon'=>'ti ti-home','route_name'=>'risk.core.home','module_code'=>'risk.core','sort'=>2],

                // Processus
                ['key'=>'process.core.title','label'=>'Organisation','type'=>'title','module_code'=>'process.core','sort'=>1],
                ['key'=>'process.core.home', 'label'=>'Accueil','type'=>'item','icon'=>'ti ti-home','route_name'=>'process.core.home','module_code'=>'process.core','sort'=>2],

                // Audit
                ['key'=>'audit.core.title','label'=>'Audit interne','type'=>'title','module_code'=>'audit.core','sort'=>1],
                ['key'=>'audit.core.home', 'label'=>'Accueil','type'=>'item','icon'=>'ti ti-home','route_name'=>'audit.core.home','module_code'=>'audit.core','sort'=>2],
            ];

            foreach ($menus as $m) {
                $moduleId = isset($m['module_code']) ? ($moduleIds[$m['module_code']] ?? null) : null;
                Menu::updateOrCreate(
                    ['key'=>$m['key']],
                    [
                        'label'=>$m['label'],
                        'type'=>$m['type'] ?? 'item',
                        'icon'=>$m['icon'] ?? null,
                        'url'=>$m['url'] ?? null,
                        'route_name'=>$m['route_name'] ?? null,
                        'target'=>$m['target'] ?? null,
                        'parent_id'=>$m['parent_id'] ?? null,
                        'sort'=>(int)($m['sort'] ?? 0),
                        'service_id'=>$m['service_id'] ?? null,
                        'module_id'=>$moduleId,
                        'visible'=>true,
                        'badge_json'=>null,
                        'tooltip_json'=>null,
                        'meta_json'=>null,
                    ]
                );
            }
        });

        $this->command->info('✅ FourModulesAndMenusSeeder OK : services/modules/menus.');
    }
}
