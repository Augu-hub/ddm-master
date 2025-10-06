<?php
// database/seeders/GlobalMenuSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GlobalMenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Vider proprement les tables
        if (Schema::hasTable('menu_permission')) {
            DB::table('menu_permission')->delete();
        }
        if (Schema::hasTable('menus')) {
            DB::table('menus')->delete();
        }

        // 2) Réinsérer l'arborescence complète des menus (GLOBALE)
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
                    // PROJETS
                    [
                        'key' => 'project',
                        'label' => 'Projets',
                        'icon' => 'ti ti-folders',
                        'url' => '/param/projects'
                    ],
                    // ENTITÉS
                    [
                        'key' => 'entity',
                        'label' => 'Entités',
                        'icon' => 'ti ti-building-community',
                        'url' => '/param/entities'
                    ],
                    // MACRO-PROCESSUS-ACTIVITÉ
                    [
                        'key' => 'process',
                        'label' => 'Macro-Processus-Activité',
                        'icon' => 'ti ti-sitemap',
                        'url' => '/param/mpa'
                    ],
                    // FONCTIONS
                    [
                        'key' => 'function',
                        'label' => 'Fonctions',
                        'icon' => 'ti ti-components',
                        'url' => '/param/function'
                    ],

                    // ORGANIGRAMMES PROJET (SOUS-MENU)
                    [
                        'key' => 'charts',
                        'label' => 'Organigrammes Projet',
                        'icon' => 'ti ti-chart-infographic',
                        'children' => [
                            [
                                'key' => 'entity_chart',
                                'label' => 'Organigramme Entités',
                                'icon' => 'ti ti-building-skyscraper',
                                'url' => '/param/charts/entity'
                            ],
                            [
                                'key' => 'function_chart',
                                'label' => 'Organigramme Fonctions',
                                'icon' => 'ti ti-hierarchy',
                                'url' => '/param/charts/function'
                            ],
                        ]
                    ],

                    // RÉPARTITION MPA/FONCTION
                    [
                        'key' => 'distribution',
                        'label' => 'Répartition MPA/fonction',
                        'icon' => 'ti ti-arrows-shuffle',
                        'url' => '/param/distribution'
                    ],

                    // ORGANIGRAMMES ENTITÉ (DYNAMIQUE)
                    [
                        'key' => 'charts_entity',
                        'label' => 'Organigrammes Entité',
                        'icon' => 'ti ti-chart-infographic',
                        'url' => '/param/charts/entity-functions'
                    ],
                ]
            ],

            // ===== ADMINISTRATION =====
            [
                'key' => 'builtin',
                'label' => 'Administration',
                'icon' => 'ti ti-settings',
                'children' => [
                    // PERMISSIONS
                    [
                        'key' => 'permissions',
                        'label' => 'Permissions',
                        'icon' => 'ti ti-key',
                        'url' => '/admin/permissions'
                    ],
                    // RÔLES
                    [
                        'key' => 'roles',
                        'label' => 'Rôles',
                        'icon' => 'ti ti-user-shield',
                        'url' => '/admin/roles'
                    ],
                    // UTILISATEURS
                    [
                        'key' => 'users',
                        'label' => 'Utilisateurs',
                        'icon' => 'ti ti-users',
                        'url' => '/admin/users'
                    ],
                    // MENUS
                    [
                        'key' => 'menus',
                        'label' => 'Menus',
                        'icon' => 'ti ti-menu-2',
                        'url' => '/admin/menus'
                    ],
                    // TRADUCTIONS
                    [
                        'key' => 'i18n',
                        'label' => 'Traductions',
                        'icon' => 'ti ti-language',
                        'url' => '/admin/translations'
                    ],
                    // CLIENTS (TENANTS)
                    [
                        'key' => 'tenants',
                        'label' => 'Clients',
                        'icon' => 'ti ti-building-store',
                        'url' => '/admin/tenants'
                    ],
                ]
            ],

            // ===== MODULES MÉTIER =====
            [
                'key' => 'modules',
                'label' => 'Modules Métier',
                'icon' => 'ti ti-apps',
                'children' => [
                    // DDM PROCESSUS
                    [
                        'key' => 'ddm-process',
                        'label' => 'DDM Processus',
                        'icon' => 'ti ti-flow-branch',
                        'url' => '/modules/processus'
                    ],
                    // DDM RISQUES
                    [
                        'key' => 'ddm-risks',
                        'label' => 'DDM Risques',
                        'icon' => 'ti ti-alert-triangle',
                        'url' => '/modules/risks'
                    ],
                    // DDM AUDIT INTERNE
                    [
                        'key' => 'ddm-audit',
                        'label' => 'DDM Audit Interne',
                        'icon' => 'ti ti-checklist',
                        'url' => '/modules/audit'
                    ],
                ]
            ],

            // ===== DASHBOARDS (TITRE + SOUS-MENU) =====
            [
                'key' => 'dashboards',
                'label' => 'Dashboards',
                'icon' => 'ti ti-dashboard',
                'is_title' => true,
                'children' => [
                    // SALES
                    [
                        'key' => 'sales',
                        'label' => 'Sales',
                        'icon' => 'ti ti-dashboard',
                        'url' => '/'
                    ],
                    // CLINIC
                    [
                        'key' => 'clinic',
                        'label' => 'Clinic',
                        'icon' => 'ti ti-building-hospital',
                        'url' => '/dashboards/clinic'
                    ],
                    // EWALLET
                    [
                        'key' => 'ewallet',
                        'label' => 'eWallet',
                        'icon' => 'ti ti-wallet',
                        'url' => '/dashboards/e-wallet'
                    ],
                ]
            ],
        ]);

        $this->command->info('GlobalMenuSeeder: Arborescence complète des menus créée avec succès');
    }

    private function insertTree(?int $parentId, array $items): void
    {
        foreach ($items as $i => $item) {
            // Extraire les enfants avant l'insertion
            $children = $item['children'] ?? [];
            unset($item['children']);

            // Préparer les données pour l'insertion
            $menuData = [
                'key'        => $item['key'],
                'label'      => $item['label'],
                'icon'       => $item['icon'] ?? null,
                'url'        => $item['url'] ?? null,
                'parent_id'  => $parentId,
                'sort'       => $i,
                'is_title'   => $item['is_title'] ?? false,
                'is_divider' => $item['is_divider'] ?? false,
                'visible'    => true,
                'badge_json' => null,
                'tooltip_json' => null,
                'meta_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insérer le menu
            $id = DB::table('menus')->insertGetId($menuData);

            // Insérer récursivement les enfants
            if (!empty($children)) {
                $this->insertTree($id, $children);
            }
        }
    }
}