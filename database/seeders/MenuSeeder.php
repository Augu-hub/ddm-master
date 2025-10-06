<?php
// database/seeders/MenuSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Master\Tenant;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Vider proprement la table des menus (on ne touche PAS aux permissions)
        if (Schema::hasTable('menus')) {
            DB::table('menus')->delete();
        }

        // 2) Créer le tenant par défaut s'il n'existe pas
        $this->createDefaultTenant();

        // 3) Réinsérer l'arborescence complète des menus
        $this->insertTree(null, [
            // ===== TITRE PRINCIPAL =====
            [
                'key' => 'seed-root',
                'label' => 'DIADDEM',
                'icon' => 'ti ti-database-cog',
                'is_title' => true
            ],

            // ===== TABLEAU DE BORD =====
            [
                'key' => 'dashboard',
                'label' => 'Tableau de bord',
                'icon' => 'ti ti-layout-dashboard',
                'url' => '/'
            ],

            // ===== PARAMÉTRAGE =====
            [
                'key' => 'param',
                'label' => 'Paramétrage',
                'icon' => 'ti ti-adjustments',
                'children' => [
                    ['key' => 'project', 'label' => 'Projets', 'icon' => 'ti ti-folders', 'url' => '/param/projects'],
                    ['key' => 'entity', 'label' => 'Entités', 'icon' => 'ti ti-building-community', 'url' => '/param/entities'],
                    ['key' => 'process', 'label' => 'Macro-Processus-Activité', 'icon' => 'ti ti-sitemap', 'url' => '/param/mpa'],
                    ['key' => 'function', 'label' => 'Fonctions', 'icon' => 'ti ti-components', 'url' => '/param/function'],
                    [
                        'key' => 'charts',
                        'label' => 'Organigrammes Projet',
                        'icon' => 'ti ti-chart-infographic',
                        'children' => [
                            ['key' => 'entity_chart', 'label' => 'Organigramme Entités', 'icon' => 'ti ti-building-skyscraper', 'url' => '/param/charts/entity'],
                            ['key' => 'function_chart', 'label' => 'Organigramme Fonctions', 'icon' => 'ti ti-hierarchy', 'url' => '/param/charts/function'],
                        ]
                    ],
                    ['key' => 'distribution', 'label' => 'Répartition MPA/fonction', 'icon' => 'ti ti-arrows-shuffle', 'url' => '/param/distribution'],
                    ['key' => 'charts_entity', 'label' => 'Organigrammes Entité', 'icon' => 'ti ti-chart-infographic', 'url' => '/param/charts/entity-functions'],
                ]
            ],

            // ===== ADMINISTRATION =====
            [
                'key' => 'builtin',
                'label' => 'Administration',
                'icon' => 'ti ti-settings',
                'children' => [
                    ['key' => 'permissions', 'label' => 'Permissions', 'icon' => 'ti ti-key', 'url' => '/admin/permissions'],
                    ['key' => 'roles', 'label' => 'Rôles', 'icon' => 'ti ti-user-shield', 'url' => '/admin/roles'],
                    ['key' => 'users', 'label' => 'Utilisateurs', 'icon' => 'ti ti-users', 'url' => '/admin/users'],
                    ['key' => 'menus', 'label' => 'Menus', 'icon' => 'ti ti-menu-2', 'url' => '/admin/menus'],
                    ['key' => 'i18n', 'label' => 'Traductions', 'icon' => 'ti ti-language', 'url' => '/admin/translations'],
                    ['key' => 'tenants', 'label' => 'Clients', 'icon' => 'ti ti-building-store', 'url' => '/admin/tenants'],
                ]
            ],

            // ===== MODULES MÉTIER =====
            [
                'key' => 'modules',
                'label' => 'Modules Métier',
                'icon' => 'ti ti-apps',
                'children' => [
                    ['key' => 'ddm-process', 'label' => 'DDM Processus', 'icon' => 'ti ti-flow-branch', 'url' => '/modules/processus'],
                    ['key' => 'ddm-risks', 'label' => 'DDM Risques', 'icon' => 'ti ti-alert-triangle', 'url' => '/modules/risks'],
                    ['key' => 'ddm-audit', 'label' => 'DDM Audit Interne', 'icon' => 'ti ti-checklist', 'url' => '/modules/audit'],
                ]
            ],

            // ===== DASHBOARDS (TITRE + SOUS-MENU) =====
            [
                'key' => 'dashboards',
                'label' => 'Dashboards',
                'icon' => 'ti ti-dashboard',
                'is_title' => true,
                'children' => [
                    ['key' => 'sales', 'label' => 'Sales', 'icon' => 'ti ti-dashboard', 'url' => '/'],
                    ['key' => 'clinic', 'label' => 'Clinic', 'icon' => 'ti ti-building-hospital', 'url' => '/dashboards/clinic'],
                    ['key' => 'ewallet', 'label' => 'eWallet', 'icon' => 'ti ti-wallet', 'url' => '/dashboards/e-wallet'],
                ]
            ],
        ]);

        $this->command->info('MenuSeeder: Arborescence complète des menus créée (sans permissions).');
    }

    /**
     * Créer le tenant par défaut (aucune permission/role ici)
     */
    private function createDefaultTenant(): void
    {
        $defaultTenant = Tenant::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Client Principal',
                'domain' => 'principal.local',
                'database' => 'tenant_principal',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info("Tenant par défaut: {$defaultTenant->name} (ID: {$defaultTenant->id})");
    }

    private function insertTree(?int $parentId, array $items): void
    {
        foreach ($items as $i => $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $menuData = [
                'key'         => $item['key'],
                'label'       => $item['label'],
                'icon'        => $item['icon'] ?? null,
                'url'         => $item['url'] ?? null,
                'parent_id'   => $parentId,
                'sort'        => $i,
                'is_title'    => $item['is_title'] ?? false,
                'is_divider'  => $item['is_divider'] ?? false,
                'visible'     => true,
                'badge_json'  => null,
                'tooltip_json'=> null,
                'meta_json'   => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            $id = DB::table('menus')->insertGetId($menuData);

            if (!empty($children)) {
                $this->insertTree($id, $children);
            }
        }
    }
}
