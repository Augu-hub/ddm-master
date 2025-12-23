<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Master\Module;
use App\Models\User;

class GlobalMenuSeeder extends Seeder
{
    private array $moduleCache = [];
    private array $menuIdByKey = [];

    public function run(): void
    {
        DB::transaction(function () {
            // Nettoyage des liaisons
            if (Schema::hasTable('menu_user'))       DB::table('menu_user')->delete();
            if (Schema::hasTable('menu_permission')) DB::table('menu_permission')->delete();

            $this->insertTree(null, [
                // ===== Racine =====
                ['key'=>'seed-root','label'=>'DIADDEM','icon'=>'ti ti-database-cog','type'=>'title','sort'=>1],
                ['key'=>'dashboard','label'=>'Tableau de bord','icon'=>'ti ti-layout-dashboard','type'=>'item','url'=>'/','sort'=>2],

                // ═══════════════════════════════════════════════════════════
                // ===== PARAMÉTRAGE =====
                // ═══════════════════════════════════════════════════════════
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
                        
                        // ✅ NOUVEAU : Gestion des utilisateurs
                        [
                            'key' => 'param-users',
                            'label' => 'Utilisateurs',
                            'icon' => 'ti ti-users',
                            'type' => 'item',
                            'url' => '/m/param.projects/users',
                            'route_name' => 'param.projects.users.index',
                            'sort' => 65
                        ],

                        // Organigrammes
                        [
                            'key'=>'param-charts','label'=>'Organigrammes Projet','icon'=>'ti ti-chart-infographic','type'=>'item','sort'=>66,
                            'children'=>[
                                ['key'=>'param-charts-entity','label'=>'Organigramme Entités','icon'=>'ti ti-building-skyscraper','type'=>'item','url'=>'/m/param.projects/charts/entity','route_name'=>'param.projects.charts.entity','sort'=>1],
                                ['key'=>'param-charts-function','label'=>'Organigramme Fonctions','icon'=>'ti ti-hierarchy','type'=>'item','url'=>'/m/param.projects/charts/function','route_name'=>'param.projects.charts.function','sort'=>2],
                                ['key'=>'param-charts-entity-functions','label'=>'Organigrammes Entité','icon'=>'ti ti-chart-infographic','type'=>'item','url'=>'/m/param.projects/charts/entity-functions','route_name'=>'param.projects.charts.entity-functions','sort'=>3],
                            ],
                        ],

                        [
                            'key'=>'param-macro-relations',
                            'label'=>'Relations Macro-Processus',
                            'icon'=>'ti ti-git-branch',
                            'type'=>'item',
                            'url'=>'/m/param.projects/macro/relations',
                            'route_name'=>'param.projects.macro.relations.index',
                            'sort'=>70
                        ],

                        [
                            'key'=>'param-macro-graph',
                            'label'=>'Graphe Macro-Processus',
                            'icon'=>'ti ti-chart-bubble',
                            'type'=>'item',
                            'url'=>'/m/param.projects/macro/graph',
                            'route_name'=>'param.projects.macro.graph.index',
                            'sort'=>71
                        ],
                    ],
                ],

                // ═══════════════════════════════════════════════════════════
                // ===== PROCESSUS =====
                // ═══════════════════════════════════════════════════════════
                [
                    'key'         => 'process-module',
                    'label'       => 'Processus',
                    'icon'        => 'ti ti-flow-branch',
                    'type'        => 'item',
                    'module_code' => 'process.core',
                    'sort'        => 20,
                    'children'    => [

                        // ════════════════════════════════
                        //  ACCUEIL / REGISTRE
                        // ════════════════════════════════
                        ['key'=>'process-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/process.core?go=1','route_name'=>'process.core.home','sort'=>1],
                        ['key'=>'process-registry','label'=>'Registre des processus','icon'=>'ti ti-list-details','type'=>'item','url'=>'/m/process.core/process','route_name'=>'process.core.process.index','sort'=>10],
                        ['key'=>'process-contracts','label'=>'Contrats d\'interface','icon'=>'ti ti-arrows-exchange-2','type'=>'item','url'=>'/m/process.core/process/contracts','route_name'=>'process.core.process.contracts.index','sort'=>15],

                        // ════════════════════════════════
                        //  RÉFÉRENTIELS
                        // ════════════════════════════════
                        [
                            'key'      => 'param-process-refs',
                            'label'    => 'Référentiels Processus',
                            'icon'     => 'ti ti-settings-cog',
                            'type'     => 'item',
                            'sort'     => 25,
                            'children' => [

                                ['key'=>'proc-ref-overview','label'=>'Aperçu général','icon'=>'ti ti-database-cog','type'=>'item','url'=>'/m/process.core/process/settings','route_name'=>'process.core.process.settings.index','sort'=>1],
                                ['key'=>'proc-ref-maturity-scales','label'=>'Critères de maturité','icon'=>'ti ti-list-details','type'=>'item','url'=>'/m/process.core/process/settings/maturity-scales','route_name'=>'process.core.process.settings.maturity-scales','sort'=>10],
                                ['key'=>'proc-ref-maturity-levels','label'=>'Niveaux de maturité','icon'=>'ti ti-stairs-up','type'=>'item','url'=>'/m/process.core/process/settings/maturity-levels','route_name'=>'process.core.process.settings.maturity-levels','sort'=>11],
                                ['key'=>'proc-ref-motricity','label'=>'Motricité','icon'=>'ti ti-topology-star','type'=>'item','url'=>'/m/process.core/process/settings/motricity-scales','route_name'=>'process.core.process.settings.motricity-scales','sort'=>20],
                                ['key'=>'proc-ref-transversality','label'=>'Transversalité','icon'=>'ti ti-arrows-join-2','type'=>'item','url'=>'/m/process.core/process/settings/transversality-scales','route_name'=>'process.core.process.settings.transversality-scales','sort'=>30],
                                ['key'=>'proc-ref-strategic','label'=>'Poids stratégique','icon'=>'ti ti-target-arrow','type'=>'item','url'=>'/m/process.core/process/settings/strategic-weight-scales','route_name'=>'process.core.process.settings.strategic-weight-scales','sort'=>40],
                                ['key'=>'proc-ref-raci','label'=>'Rôles RACI','icon'=>'ti ti-users-group','type'=>'item','url'=>'/m/process.core/process/settings/raci-roles','route_name'=>'process.core.process.settings.raci-roles','sort'=>50],
                                ['key'=>'proc-ref-idea','label'=>'Axes IDEA','icon'=>'ti ti-bulb','type'=>'item','url'=>'/m/process.core/process/settings/idea-axes','route_name'=>'process.core.process.settings.idea-axes','sort'=>60],
                                ['key'=>'proc-ref-kpi','label'=>'Catégories KPI','icon'=>'ti ti-graph','type'=>'item','url'=>'/m/process.core/process/settings/kpi-categories','route_name'=>'process.core.process.settings.kpi-categories','sort'=>70],
                                ['key'=>'proc-ref-links','label'=>'Types de liens (processus)','icon'=>'ti ti-link','type'=>'item','url'=>'/m/process.core/process/settings/link-types','route_name'=>'process.core.process.settings.link-types','sort'=>80],
                                ['key'=>'proc-ref-controls','label'=>'Types de contrôles (activité)','icon'=>'ti ti-shield-check','type'=>'item','url'=>'/m/process.core/process/settings/control-types','route_name'=>'process.core.process.settings.control-types','sort'=>90],
                                [
                                    'key'        => 'proc-ref-criticality-norms',
                                    'label'      => 'Normes de criticité',
                                    'icon'       => 'ti ti-color-swatch',
                                    'type'       => 'item',
                                    'url'        => '/m/process.core/process/settings/criticality-norms',
                                    'route_name' => 'process.core.process.settings.criticality-norms',
                                    'sort'       => 95,
                                ],
                            ],
                        ],

                        // ════════════════════════════════
                        //  SESSIONS D'ÉVALUATION
                        // ════════════════════════════════
                        [
                            'key' => 'process-sessions',
                            'label' => 'Sessions d\'évaluation',
                            'icon' => 'ti ti-calendar-event',
                            'type' => 'item',
                            'sort' => 30,
                            'url' => '/m/process.core/evaluations/sessions',
                            'route_name' => 'process.core.sessions.index',
                        ],

                        // ════════════════════════════════
                        //  ÉVALUATIONS
                        // ════════════════════════════════
                        [
                            'key' => 'process-evaluations',
                            'label' => 'Évaluations',
                            'icon' => 'ti ti-clipboard-heart',
                            'type' => 'item',
                            'sort' => 40,
                            'children' => [
                                
                                // ============================================================
                                // 📊 ÉVALUATION CRITICITÉ
                                // ============================================================
                                [
                                    'key' => 'process-eval-criticality',
                                    'label' => 'Évaluation de la Criticité',
                                    'icon' => 'ti ti-flame',
                                    'type' => 'item',
                                    'sort' => 1,
                                    'children' => [
                                        [
                                            'key' => 'process-eval-criticality-overview',
                                            'label' => 'Vue d\'ensemble',
                                            'icon' => 'ti ti-list-search',
                                            'type' => 'item',
                                            'url' => '/m/process.core/evaluations/criticality',
                                            'route_name' => 'process.core.evaluations.criticality.index',
                                            'sort' => 1
                                        ],
                                        [
                                            'key' => 'process-eval-maturity',
                                            'label' => 'Maturité',
                                            'icon' => 'ti ti-chart-bar',
                                            'type' => 'item',
                                            'url' => '/m/process.core/evaluations/criticality?section=maturity',
                                            'route_name' => 'process.core.evaluations.criticality.maturity',
                                            'sort' => 2
                                        ],
                                        [
                                            'key' => 'process-eval-motricity',
                                            'label' => 'Motricité',
                                            'icon' => 'ti ti-topology-star',
                                            'type' => 'item',
                                            'url' => '/m/process.core/evaluations/criticality?section=motricity',
                                            'route_name' => 'process.core.evaluations.criticality.motricity',
                                            'sort' => 3
                                        ],
                                        [
                                            'key' => 'process-eval-transversality',
                                            'label' => 'Transversalité',
                                            'icon' => 'ti ti-arrows-join-2',
                                            'type' => 'item',
                                            'url' => '/m/process.core/evaluations/criticality?section=transversality',
                                            'route_name' => 'process.core.evaluations.criticality.transversality',
                                            'sort' => 4
                                        ],
                                        [
                                            'key' => 'process-eval-strategic',
                                            'label' => 'Poids Stratégique',
                                            'icon' => 'ti ti-target-arrow',
                                            'type' => 'item',
                                            'url' => '/m/process.core/evaluations/criticality?section=strategic',
                                            'route_name' => 'process.core.evaluations.criticality.strategic',
                                            'sort' => 5
                                        ],
                                    ]
                                ],

                                // ============================================================
                                // 👥 MATRICE RACI
                                // ============================================================
                                [
                                    'key' => 'process-eval-raci',
                                    'label' => 'Matrice RACI',
                                    'icon' => 'ti ti-users-group',
                                    'type' => 'item',
                                    'url' => '/m/process.core/evaluations/raci',
                                    'route_name' => 'process.core.raci.index',
                                    'sort' => 2
                                ],

                                // ============================================================
                                // ⚠️ AMDEC
                                // ============================================================
                                [
                                    'key' => 'process-eval-amdec',
                                    'label' => 'AMDEC',
                                    'icon' => 'ti ti-alert-triangle',
                                    'type' => 'item',
                                    'url' => '/m/process.core/evaluations/amdec',
                                    'route_name' => 'process.core.evaluations.amdec.index',
                                    'sort' => 3
                                ],

                                // ============================================================
                                // 💡 IDEA
                                // ============================================================
                                [
                                    'key' => 'process-eval-idea',
                                    'label' => 'IDEA',
                                    'icon' => 'ti ti-bulb',
                                    'type' => 'item',
                                    'url' => '/m/process.core/evaluations/idea',
                                    'route_name' => 'process.core.evaluations.idea.index',
                                    'sort' => 4
                                ],
                            ]
                        ],

                        // ════════════════════════════════
                        //  RAPPORTS / ANALYSES
                        // ════════════════════════════════
                        [
                            'key'      => 'process-reports',
                            'label'    => 'Rapports & Analyses',
                            'icon'     => 'ti ti-report-analytics',
                            'type'     => 'item',
                            'sort'     => 50,
                            'children' => [
                                ['key'=>'process-reports-list','label'=>'Tableau des processus','icon'=>'ti ti-table','type'=>'item','url'=>'/m/process.core/reports/list','route_name'=>'process.core.reports.list','sort'=>1],
                                ['key'=>'process-reports-criticality','label'=>'Processus par criticité','icon'=>'ti ti-flame','type'=>'item','url'=>'/m/process.core/reports/criticality','route_name'=>'process.core.reports.criticality','sort'=>2],
                                ['key'=>'process-reports-maturity','label'=>'Graphiques de maturité','icon'=>'ti ti-chart-bar','type'=>'item','url'=>'/m/process.core/reports/maturity','route_name'=>'process.core.reports.maturity','sort'=>3],
                                ['key'=>'process-reports-motricity','label'=>'Motricité / Transversalité / PS','icon'=>'ti ti-topology-star','type'=>'item','url'=>'/m/process.core/reports/motricity','route_name'=>'process.core.reports.motricity','sort'=>4],
                                ['key'=>'process-reports-raci','label'=>'Matrices RACI','icon'=>'ti ti-layout-grid','type'=>'item','url'=>'/m/process.core/reports/raci','route_name'=>'process.core.reports.raci','sort'=>5],
                                ['key'=>'process-reports-idea','label'=>'Matrices IDEA','icon'=>'ti ti-grid-dots','type'=>'item','url'=>'/m/process.core/reports/idea','route_name'=>'process.core.reports.idea','sort'=>6],
                            ],
                        ],

                        // ════════════════════════════════
                        //  ✅ NOUVEAU : MODÉLISATION
                        // ════════════════════════════════
                        [
                            'key'      => 'process-modeling',
                            'label'    => 'Modélisation',
                            'icon'     => 'ti ti-hierarchy-3',
                            'type'     => 'item',
                            'sort'     => 60,
                            'children' => [
                                [
                                    'key' => 'process-modeling-simple',
                                    'label' => 'Diagramme Simplet',
                                    'icon' => 'ti ti-vector-triangle',
                                    'type' => 'item',
                                    'url' => '/m/process.core/modeling/simple',
                                    'route_name' => 'process.core.modeling.simple',
                                    'sort' => 1
                                ],
                                [
                                    'key' => 'process-modeling-bpmn',
                                    'label' => 'Modélisation BPMN',
                                    'icon' => 'ti ti-brain',
                                    'type' => 'item',
                                    'url' => '/m/process.core/modeling/bpmn',
                                    'route_name' => 'process.core.modeling.bpmn',
                                    'sort' => 2
                                ],
                            ],
                        ],
                    ],
                ],

                // ═══════════════════════════════════════════════════════════
                // ===== RISQUES =====
                // ═══════════════════════════════════════════════════════════
                [
                    'key'=>'risk-module','label'=>'Risques','icon'=>'ti ti-alert-triangle','type'=>'item',
                    'module_code'=>'risk.core','sort'=>30,
                    'children'=>[
                        ['key'=>'risk-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/risk.core?go=1','route_name'=>'risk.core.home','sort'=>1],
                        ['key'=>'risk-registry','label'=>'Registre des risques','icon'=>'ti ti-clipboard-list','type'=>'item','url'=>'/m/risk.core/registry','route_name'=>'risk.core.registry.index','sort'=>10],
                        ['key'=>'risk-models','label'=>'Modèles (cartes, matrices)','icon'=>'ti ti-layout-grid','type'=>'item','url'=>'/m/risk.core/models','route_name'=>'risk.core.models.index','sort'=>20],
                        ['key'=>'risk-reports','label'=>'Rapports & tableaux de bord','icon'=>'ti ti-report-analytics','type'=>'item','url'=>'/m/risk.core/reports','route_name'=>'risk.core.reports.index','sort'=>30],
                        ['key'=>'risk-settings','label'=>'Paramètres & appétence','icon'=>'ti ti-adjustments','type'=>'item','url'=>'/m/risk.core/settings','route_name'=>'risk.core.settings.index','sort'=>40],
                    ],
                ],

                // ═══════════════════════════════════════════════════════════
                // ===== AUDIT =====
                // ═══════════════════════════════════════════════════════════
                [
                    'key'=>'audit-module','label'=>'Audit Interne','icon'=>'ti ti-checklist','type'=>'item',
                    'module_code'=>'audit.core','sort'=>40,
                    'children'=>[
                        ['key'=>'audit-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/audit.core?go=1','route_name'=>'audit.core.home','sort'=>1],
                        ['key'=>'audit-registry','label'=>'Registre (plans, missions)','icon'=>'ti ti-notebook','type'=>'item','url'=>'/m/audit.core/registry','route_name'=>'audit.core.registry.index','sort'=>10],
                        ['key'=>'audit-models','label'=>'Programmes & modèles','icon'=>'ti ti-template','type'=>'item','url'=>'/m/audit.core/models','route_name'=>'audit.core.models.index','sort'=>20],
                        ['key'=>'audit-reports','label'=>'Rapports & suivi reco','icon'=>'ti ti-report-search','type'=>'item','url'=>'/m/audit.core/reports','route_name'=>'audit.core.reports.index','sort'=>30],
                        ['key'=>'audit-settings','label'=>'Paramètres','icon'=>'ti ti-settings','type'=>'item','url'=>'/m/audit.core/settings','route_name'=>'audit.core.settings.index','sort'=>40],
                    ],
                ],

                // ═══════════════════════════════════════════════════════════
                // ===== ADMINISTRATION =====
                // ═══════════════════════════════════════════════════════════
                [
                    'key'=>'admin-module','label'=>'Administration','icon'=>'ti ti-settings','type'=>'item',
                    'module_code'=>'admin.core','sort'=>90,
                    'children'=>[
                        ['key'=>'admin-home','label'=>'Accueil','icon'=>'ti ti-home','type'=>'item','url'=>'/m/admin.core?go=1','route_name'=>'admin.core.home','sort'=>1],
                        ['key'=>'admin-services','label'=>'Services','icon'=>'ti ti-packages','type'=>'item','url'=>'/admin/services','route_name'=>'admin.services.index','sort'=>10],
                        ['key'=>'admin-modules','label'=>'Modules','icon'=>'ti ti-apps','type'=>'item','url'=>'/admin/modules','route_name'=>'admin.modules.index','sort'=>20],
                        ['key'=>'admin-assign-services','label'=>'Affecter Services → Utilisateur','icon'=>'ti ti-user-plus','type'=>'item','url'=>'/admin/assign/users-services','route_name'=>'admin.assign.services','sort'=>30],
                        ['key'=>'admin-assign-modules','label'=>'Affecter Modules → Utilisateur','icon'=>'ti ti-user-cog','type'=>'item','url'=>'/admin/assign/users-modules','route_name'=>'admin.assign.modules','sort'=>40],
                        ['key'=>'admin-permissions','label'=>'Permissions','icon'=>'ti ti-key','type'=>'item','url'=>'/admin/permissions','route_name'=>'admin.permissions.index','sort'=>50],
                        ['key'=>'admin-roles','label'=>'Rôles','icon'=>'ti ti-user-shield','type'=>'item','url'=>'/admin/roles','route_name'=>'admin.roles.index','sort'=>60],
                        ['key'=>'admin-users','label'=>'Utilisateurs','icon'=>'ti ti-users','type'=>'item','url'=>'/admin/users','route_name'=>'admin.users.index','sort'=>70],
                        ['key'=>'admin-menus','label'=>'Menus','icon'=>'ti ti-menu-2','type'=>'item','url'=>'/admin/menus','route_name'=>'admin.menus.index','sort'=>80],
                        ['key'=>'admin-i18n','label'=>'Traductions','icon'=>'ti ti-language','type'=>'item','url'=>'/admin/translations','route_name'=>'admin.translations.index','sort'=>90],
                        ['key'=>'admin-tenants','label'=>'Clients','icon'=>'ti ti-building-store','type'=>'item','url'=>'/admin/tenants','route_name'=>'admin.tenants.index','sort'=>100],
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