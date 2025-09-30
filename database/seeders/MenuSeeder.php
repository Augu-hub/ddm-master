<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Param\Menu;


class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Désactive les FK pour gérer l'auto-référence parent_id
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('menus')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->batch(null, [
                // ===== SEEDER EN HAUT =====
                ['key'=>'seed-root','label'=>'DIADDEM','icon'=>'ti ti-database-cog','is_title'=>true],
                // Dashboard
                ['key'=>'dashboard','label'=>'Tableau de bord','icon'=>'ti ti-layout-dashboard','url'=>'/'],

                // PARAM
                ['key'=>'param','label'=>'Paramétrage','icon'=>'ti ti-adjustments','children'=>[
                    ['key'=>'project','label'=>'Projets','icon'=>'ti ti-folders','url'=>'/param/projects'],
                    ['key'=>'entity','label'=>'Entités','icon'=>'ti ti-building-community','url'=>'/param/entities'],
                    ['key'=>'process','label'=>'Macro-Processus-Activité','icon'=>'ti ti-sitemap','url'=>'/param/process'],
                    ['key'=>'function','label'=>'Fonctions','icon'=>'ti ti-components','url'=>'/param/function'],

                    ['key'=>'charts','label'=>'Organigrammes Projet','icon'=>'ti ti-chart-infographic','children'=>[
                        ['key'=>'entity_chart','label'=>'Organigramme Entités','icon'=>'ti ti-building-skyscraper','url'=>'/param/charts/entity'],
                        ['key'=>'function_chart','label'=>'Organigramme Fonctions','icon'=>'ti ti-hierarchy','url'=>'/param/charts/function'],
                    ]],

                    ['key'=>'distribution','label'=>'Répartition MPA/fonction','icon'=>'ti ti-arrows-shuffle','url'=>'/param/distribution'],

                    // rempli dynamiquement en front
                    ['key'=>'charts_entity','label'=>'Organigrammes Entité','icon'=>'ti ti-chart-infographic'],
                ]],

                // ADMINISTRATION
                ['key'=>'builtin','label'=>'Administration','icon'=>'ti ti-settings','children'=>[
                    ['key'=>'permissions','label'=>'Permissions','icon'=>'ti ti-key','url'=>'/admin/permissions'],
                    ['key'=>'roles','label'=>'Rôles','icon'=>'ti ti-user-shield','url'=>'/admin/roles'],
                    ['key'=>'users','label'=>'Utilisateurs','icon'=>'ti ti-users','url'=>'/admin/users'],
                    ['key'=>'menus','label'=>'Menus','icon'=>'ti ti-menu-2','url'=>'/admin/menus'],
                    ['key'=>'i18n','label'=>'Traductions','icon'=>'ti ti-language','url'=>'/admin/translations'],
                    ['key'=>'tenants','label'=>'Clients','icon'=>'ti ti-building-store','url'=>'/admin/tenants'],
                ]],

                // MODULES
                ['key'=>'modules','label'=>'Modules Métier','icon'=>'ti ti-apps','children'=>[
                    ['key'=>'ddm-process','label'=>'DDM Processus','icon'=>'ti ti-flow-branch','url'=>'/modules/processus'],
                    ['key'=>'ddm-risks','label'=>'DDM Risques','icon'=>'ti ti-alert-triangle','url'=>'/modules/risks'],
                    ['key'=>'ddm-audit','label'=>'DDM Audit Interne','icon'=>'ti ti-checklist','url'=>'/modules/audit'],
                ]],

                // ===== BLOCS TEMPLATE =====
                ['key'=>'dashboards','label'=>'Dashboards','icon'=>'ti ti-dashboard','is_title'=>true,'children'=>[
                    ['key'=>'sales','label'=>'Sales','icon'=>'ti ti-dashboard','url'=>'/'],
                    ['key'=>'clinic','label'=>'Clinic','icon'=>'ti ti-building-hospital','url'=>'/dashboards/clinic'],
                    ['key'=>'ewallet','label'=>'eWallet','icon'=>'ti ti-wallet','url'=>'/dashboards/e-wallet'],
                ]],
            ]);
        });
    }

    private function batch(?int $parentId, array $items): void
    {
        foreach ($items as $i => $it) {
            $children = $it['children'] ?? [];
            unset($it['children']);

            $menu = Menu::create([
                'key'        => $it['key'],
                'label'      => $it['label'],
                'icon'       => $it['icon'] ?? null,
                'url'        => $it['url'] ?? null,
                'parent_id'  => $parentId,
                'sort'       => $i,
                'is_title'   => (bool)($it['is_title'] ?? false),
                'is_divider' => (bool)($it['is_divider'] ?? false),
                'visible'    => true,
            ]);

            if ($children) $this->batch($menu->id, $children);
        }
    }
}
