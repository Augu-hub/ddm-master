<?php
// database/seeders/GlobalMenuSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Master\Module;
use App\Models\User;

class GlobalMenuSeedercopy extends Seeder
{
    private array $moduleCache = [];
    private array $menuIdByKey = [];

    public function run(): void
    {
        DB::transaction(function () {
            if (Schema::hasTable('menu_user'))       DB::table('menu_user')->delete();
            if (Schema::hasTable('menu_permission')) DB::table('menu_permission')->delete();

            $this->insertTree(null, [
                // ===== Global (sans module) =====
                ['key'=>'seed-root','label'=>'DIADDEM','icon'=>'ti ti-database-cog','type'=>'title','sort'=>1],
                ['key'=>'dashboard','label'=>'Tableau de bord','icon'=>'ti ti-layout-dashboard','type'=>'item','url'=>'/','sort'=>2],

                // ===== PARAM =====
                [
                    'key'=>'param','label'=>'Paramétrage','icon'=>'ti ti-adjustments','type'=>'item',
                    'module_code'=>'param.projects','sort'=>10,
                    'children'=>[
                        [
                            'key'=>'param-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item',
                            'url'=>'/m/param.projects?go=1','route_name'=>'param.projects.home','sort'=>1,
                        ],
                        ['key'=>'param-projects','label'=>'Projets','icon'=>'ti ti-folders','type'=>'item','url'=>'/m/param.projects/projects','route_name'=>'param.projects.projects.index','sort'=>10],
                        ['key'=>'param-entities','label'=>'Entités','icon'=>'ti ti-building-community','type'=>'item','url'=>'/m/param.projects/entities','route_name'=>'param.projects.entities.index','sort'=>20],
                        ['key'=>'param-mpa','label'=>'Macro-Processus-Activité','icon'=>'ti ti-sitemap','type'=>'item','url'=>'/m/param.projects/mpa','route_name'=>'param.projects.mpa.index','sort'=>30],
                        ['key'=>'param-processus','label'=>'Processus','icon'=>'ti ti-topology-complex','type'=>'item','url'=>'/m/param.projects/processus','route_name'=>'param.projects.processus.index','sort'=>35],
                        ['key'=>'param-activites','label'=>'Activités','icon'=>'ti ti-list-details','type'=>'item','url'=>'/m/param.projects/activites','route_name'=>'param.projects.activites.index','sort'=>37],
                        ['key'=>'param-fonctions','label'=>'Fonctions','icon'=>'ti ti-components','type'=>'item','url'=>'/m/param.projects/fonctions','route_name'=>'param.projects.fonctions.index','sort'=>40],
                        ['key'=>'param-distribution','label'=>'Répartition MPA / Fonction','icon'=>'ti ti-arrows-shuffle','type'=>'item','url'=>'/m/param.projects/distribution','route_name'=>'param.projects.distribution.index','sort'=>50],
                        ['key'=>'param-func-users','label'=>'Fonctions ↔ Utilisateurs','icon'=>'ti ti-user-check','type'=>'item','url'=>'/m/param.projects/functions/users','route_name'=>'param.projects.functions.users.index','sort'=>60],
                        [
                            'key'=>'param-charts','label'=>'Organigrammes Projet','icon'=>'ti ti-chart-infographic','type'=>'item','sort'=>60,
                            'children'=>[
                                ['key'=>'param-charts-entity','label'=>'Organigramme Entités','icon'=>'ti ti-building-skyscraper','type'=>'item','url'=>'/m/param.projects/charts/entity','route_name'=>'param.projects.charts.entity','sort'=>1],
                                ['key'=>'param-charts-function','label'=>'Organigramme Fonctions','icon'=>'ti ti-hierarchy','type'=>'item','url'=>'/m/param.projects/charts/function','route_name'=>'param.projects.charts.function','sort'=>2],
                                ['key'=>'param-charts-entity-functions','label'=>'Organigrammes Entité','icon'=>'ti ti-chart-infographic','type'=>'item','url'=>'/m/param.projects/charts/entity-functions','route_name'=>'param.projects.charts.entity-functions','sort'=>3],
                            ],
                        ],
                    ],
                ],

                // ===== PROCESS =====
                [
                    'key'=>'process-module','label'=>'Processus','icon'=>'ti ti-flow-branch','type'=>'item',
                    'module_code'=>'process.core','sort'=>20,
                    'children'=>[
                        ['key'=>'process-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/process.core?go=1','route_name'=>'process.core.home','sort'=>1],
                        ['key'=>'process-registry','label'=>'Registre des processus','icon'=>'ti ti-list-details','type'=>'item','url'=>'/m/process.core','route_name'=>'process.core.home','sort'=>10],
                        [
                            'key'=>'process-evaluation','label'=>'Évaluation','icon'=>'ti ti-clipboard-heart','type'=>'item','sort'=>50,
                            'children'=>[
                                ['key'=>'process-eval-criticite','label'=>'Criticité','icon'=>'ti ti-activity-heartbeat','type'=>'item','route_name'=>'process.core.evaluations.index','sort'=>1],
                                ['key'=>'process-eval-amdec','label'=>'AMDEC','icon'=>'ti ti-alert-triangle','type'=>'item','route_name'=>'process.core.evaluations.index','sort'=>2],
                                ['key'=>'process-eval-raci','label'=>'RACI','icon'=>'ti ti-users-group','type'=>'item','route_name'=>'process.core.activities.raci.index','sort'=>3],
                                ['key'=>'process-eval-idea','label'=>'IDEA','icon'=>'ti ti-user-cog','type'=>'item','route_name'=>'process.core.activities.idea.index','sort'=>4],
                            ],
                        ],
                        [
                            'key'=>'process-reports','label'=>'Rapports','icon'=>'ti ti-report-analytics','type'=>'item','sort'=>60,
                            'children'=>[
                                ['key'=>'process-reports-list','label'=>'Tableau des processus','icon'=>'ti ti-table','type'=>'item','route_name'=>'process.core.home','sort'=>1],
                                ['key'=>'process-reports-criticality','label'=>'Processus par criticité','icon'=>'ti ti-flame','type'=>'item','route_name'=>'process.core.home','sort'=>2],
                                ['key'=>'process-reports-maturity','label'=>'Graphiques de maturité','icon'=>'ti ti-chart-bar','type'=>'item','route_name'=>'process.core.home','sort'=>3],
                                ['key'=>'process-reports-motricity','label'=>'Motricité / Transversalité / PS','icon'=>'ti ti-topology-star','type'=>'item','route_name'=>'process.core.home','sort'=>4],
                                ['key'=>'process-reports-raci','label'=>'Matrices RACI','icon'=>'ti ti-layout-grid','type'=>'item','route_name'=>'process.core.home','sort'=>5],
                                ['key'=>'process-reports-idea','label'=>'Matrices IDEA','icon'=>'ti ti-grid-dots','type'=>'item','route_name'=>'process.core.home','sort'=>6],
                            ],
                        ],
                    ],
                ],

                // ===== RISQUES =====
                [
                    'key'=>'risk-module','label'=>'Risques','icon'=>'ti ti-alert-triangle','type'=>'item',
                    'module_code'=>'risk.core','sort'=>30,
                    'children'=>[
                        ['key'=>'risk-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/risk.core?go=1','route_name'=>'risk.core.home','sort'=>1],
                        ['key'=>'risk-registry','label'=>'Registre des risques','icon'=>'ti ti-clipboard-list','type'=>'item','url'=>'/m/risk.core','route_name'=>'risk.core.home','sort'=>10],
                        ['key'=>'risk-models','label'=>'Modèles (cartes, matrices)','icon'=>'ti ti-layout-grid','type'=>'item','route_name'=>'risk.core.models.index','sort'=>20],
                        ['key'=>'risk-reports','label'=>'Rapports & tableaux de bord','icon'=>'ti ti-report-analytics','type'=>'item','route_name'=>'risk.core.reports.index','sort'=>30],
                        ['key'=>'risk-settings','label'=>'Paramètres & appétence','icon'=>'ti ti-adjustments','type'=>'item','route_name'=>'risk.core.settings.index','sort'=>40],
                    ],
                ],

                // ===== AUDIT =====
                [
                    'key'=>'audit-module','label'=>'Audit Interne','icon'=>'ti ti-checklist','type'=>'item',
                    'module_code'=>'audit.core','sort'=>40,
                    'children'=>[
                        ['key'=>'audit-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/audit.core?go=1','route_name'=>'audit.core.home','sort'=>1],
                        ['key'=>'audit-registry','label'=>'Registre (plans, missions)','icon'=>'ti ti-notebook','type'=>'item','url'=>'/m/audit.core','route_name'=>'audit.core.home','sort'=>10],
                        ['key'=>'audit-models','label'=>'Programmes & modèles','icon'=>'ti ti-template','type'=>'item','route_name'=>'audit.core.models.index','sort'=>20],
                        ['key'=>'audit-reports','label'=>'Rapports & suivi reco','icon'=>'ti ti-report-search','type'=>'item','route_name'=>'audit.core.reports.index','sort'=>30],
                        ['key'=>'audit-settings','label'=>'Paramètres','icon'=>'ti ti-settings','type'=>'item','route_name'=>'audit.core.settings.index','sort'=>40],
                    ],
                ],

                // ===== ADMIN =====
                [
                    'key'=>'admin-module','label'=>'Administration','icon'=>'ti ti-settings','type'=>'item',
                    'module_code'=>'admin.core','sort'=>90,
                    'children'=>[
                        ['key'=>'admin-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/admin.core?go=1','route_name'=>'admin.core.home','sort'=>1],
                        ['key'=>'admin-services','label'=>'Services','icon'=>'ti ti-packages','type'=>'item','url'=>'/admin/services','sort'=>10],
                        ['key'=>'admin-modules','label'=>'Modules','icon'=>'ti ti-apps','type'=>'item','url'=>'/admin/modules','sort'=>20],
                        ['key'=>'admin-assign-services','label'=>'Affecter Services → Utilisateur','icon'=>'ti ti-user-plus','type'=>'item','url'=>'/admin/assign/users-services','sort'=>30],
                        ['key'=>'admin-assign-modules','label'=>'Affecter Modules → Utilisateur','icon'=>'ti ti-user-cog','type'=>'item','url'=>'/admin/assign/users-modules','sort'=>40],
                        ['key'=>'admin-permissions','label'=>'Permissions','icon'=>'ti ti-key','type'=>'item','url'=>'/admin/permissions','sort'=>50],
                        ['key'=>'admin-roles','label'=>'Rôles','icon'=>'ti ti-user-shield','type'=>'item','url'=>'/admin/roles','sort'=>60],
                        ['key'=>'admin-users','label'=>'Utilisateurs','icon'=>'ti ti-users','type'=>'item','url'=>'/admin/users','sort'=>70],
                        ['key'=>'admin-menus','label'=>'Menus','icon'=>'ti ti-menu-2','type'=>'item','url'=>'/admin/menus','sort'=>80],
                        ['key'=>'admin-i18n','label'=>'Traductions','icon'=>'ti ti-language','type'=>'item','url'=>'/admin/translations','sort'=>90],
                        ['key'=>'admin-tenants','label'=>'Clients','icon'=>'ti ti-building-store','type'=>'item','url'=>'/admin/tenants','sort'=>100],
                    ],
                ],
            ]);

            $this->command->info('✅ GlobalMenuSeeder: arborescence upsertée.');
        });

        $this->attachSuperAdminToAllModules();
    }

    private function insertTree(?int $parentId, array $items, ?int $inheritModuleId = null, ?int $inheritServiceId = null): void
    {
        foreach ($items as $sort => $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $moduleId  = $inheritModuleId;
            $serviceId = $inheritServiceId;

            if (!empty($item['module_code'])) {
                [$mId, $sId] = $this->resolveModule($item['module_code']);
                if ($mId)  $moduleId  = $mId;
                if ($sId)  $serviceId = $sId;
                unset($item['module_code']);
            }

            DB::table('menus')->updateOrInsert(
                ['key'=>$item['key']],
                [
                    'label'=>$item['label'],
                    'type'=>$item['type'] ?? 'item',
                    'icon'=>$item['icon'] ?? null,
                    'url'=>$item['url'] ?? null,
                    'route_name'=>$item['route_name'] ?? null,
                    'target'=>$item['target'] ?? null,
                    'parent_id'=>$parentId,
                    'sort'=>is_numeric($item['sort'] ?? null) ? (int)$item['sort'] : (int)$sort,
                    'service_id'=>$serviceId,
                    'module_id'=>$moduleId,
                    'visible'=>$item['visible'] ?? true,
                    'badge_json'=>$item['badge_json'] ?? null,
                    'tooltip_json'=>$item['tooltip_json'] ?? null,
                    'meta_json'=>$item['meta_json'] ?? null,
                    'updated_at'=>now(),
                    'created_at'=>now(),
                ]
            );

            $menuId = (int) DB::table('menus')->where('key',$item['key'])->value('id');
            $this->menuIdByKey[$item['key']] = $menuId;

            if (!empty($children)) {
                $this->insertTree($menuId, $children, $moduleId, $serviceId);
            }
        }
    }

    private function resolveModule(string $code): array
    {
        if (isset($this->moduleCache[$code])) return $this->moduleCache[$code];

        if (!Schema::hasTable('modules')) {
            $this->command->warn("⚠️ Table modules absente — {$code} ignoré.");
            return $this->moduleCache[$code] = [null,null];
        }
        $row = Module::query()->where('code',$code)->select('id','service_id')->first();
        if (!$row) {
            $this->command->warn("ℹ️ Module introuvable: {$code} — liaison module ignorée.");
            return $this->moduleCache[$code] = [null,null];
        }
        return $this->moduleCache[$code] = [(int)$row->id, (int)$row->service_id];
    }

    private function attachSuperAdminToAllModules(): void
    {
        $email = env('GLOBAL_SUPERADMIN_EMAIL','admin@diaddem.local');
        if (!Schema::hasTable('users') || !Schema::hasTable('modules') || !Schema::hasTable('module_user')) {
            $this->command->warn('⚠️ Tables manquantes — liaison super admin ignorée.');
            return;
        }
        $superId = User::where('email',$email)->value('id');
        if (!$superId) {
            $this->command->warn("⚠️ Super admin introuvable: {$email}");
            return;
        }
        $moduleIds = Module::pluck('id')->all();
        if (!$moduleIds) {
            $this->command->warn('ℹ️ Aucun module trouvé — seed des modules requis.');
            return;
        }
        $now = now();
        $rows = array_map(fn($mid)=>[
            'module_id'=>$mid,'user_id'=>$superId,
            'created_at'=>$now,'updated_at'=>$now
        ], $moduleIds);

        DB::table('module_user')->upsert($rows, ['module_id','user_id']);
        $this->command->info("✅ Super admin ({$email}) lié à ".count($moduleIds)." modules.");
    }
}