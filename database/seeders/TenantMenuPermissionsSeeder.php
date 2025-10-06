<?php
// database/seeders/TenantMenuPermissionsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Master\Tenant;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantMenuPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer tous les tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->command->info("Configuration des permissions pour le tenant: {$tenant->name}");

            // Définir le tenant courant pour Spatie
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            // 1. Créer les permissions pour ce tenant
            $this->createTenantPermissions($tenant);

            // 2. Lier les menus aux permissions de ce tenant
            $this->linkMenusToTenantPermissions($tenant);

            $this->command->info("✓ Permissions configurées pour {$tenant->name}");
        }
    }

    private function createTenantPermissions(Tenant $tenant): void
    {
        $permissions = [
            // Permissions de paramétrage
            'param.projects.view',
            'param.entities.view', 
            'param.process.view',
            'param.functions.view',
            'param.distribution.view',
            
            // Permissions de charts
            'charts.project.view',
            'charts.entity.view',
            
            // Permissions d'administration
            'admin.permissions.view',
            'admin.permissions.edit',
            'admin.roles.view',
            'admin.roles.edit',
            'admin.users.view',
            'admin.users.edit',
            'admin.menus.view',
            'admin.menus.edit',
            'admin.translations.view',
            'admin.tenants.view',
            
            // Permissions des modules métier
            'modules.process.view',
            'modules.risks.view',
            'modules.audit.view',

            // Permission globale
            'global_admin',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
                'team_id' => $tenant->id
            ]);
        }

        // Créer les rôles par défaut
        $this->createDefaultRoles($tenant);
    }

    private function createDefaultRoles(Tenant $tenant): void
    {
        // Rôle Admin - Toutes les permissions
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            'team_id' => $tenant->id
        ]);
        $adminRole->givePermissionTo(Permission::where('team_id', $tenant->id)->get());

        // Rôle Manager - Permissions limitées
        $managerRole = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web',
            'team_id' => $tenant->id
        ]);
        $managerPermissions = [
            'param.projects.view', 'param.entities.view', 'param.process.view', 
            'param.functions.view', 'param.distribution.view', 'charts.project.view', 
            'charts.entity.view', 'admin.users.view'
        ];
        $managerRole->givePermissionTo($managerPermissions);

        // Rôle Viewer - Permissions en lecture seule
        $viewerRole = Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => 'web',
            'team_id' => $tenant->id
        ]);
        $viewerPermissions = [
            'param.projects.view', 'param.entities.view', 'param.process.view', 
            'param.functions.view', 'charts.project.view', 'charts.entity.view'
        ];
        $viewerRole->givePermissionTo($viewerPermissions);
    }

    private function linkMenusToTenantPermissions(Tenant $tenant): void
    {
        // Mapping des menus vers les permissions
        $menuPermissionsMapping = [
            // PARAMÉTRAGE
            'project'       => 'param.projects.view',
            'entity'        => 'param.entities.view',
            'process'       => 'param.process.view',
            'function'      => 'param.functions.view',
            'entity_chart'  => 'charts.project.view',
            'function_chart'=> 'charts.project.view',
            'distribution'  => 'param.distribution.view',
            'charts_entity' => 'charts.entity.view',

            // ADMINISTRATION
            'permissions'   => 'admin.permissions.view',
            'roles'         => 'admin.roles.view',
            'users'         => 'admin.users.view',
            'menus'         => 'admin.menus.view',
            'i18n'          => 'admin.translations.view',
            'tenants'       => 'admin.tenants.view',

            // MODULES MÉTIER
            'ddm-process'   => 'modules.process.view',
            'ddm-risks'     => 'modules.risks.view',
            'ddm-audit'     => 'modules.audit.view',
        ];

        // Récupérer tous les menus (globaux)
        $menus = DB::table('menus')->get()->keyBy('key');

        // Récupérer les permissions de ce tenant
        $permissions = Permission::where('team_id', $tenant->id)->get()->keyBy('name');

        // Créer les associations menu-permission pour ce tenant
        $associationsCreated = 0;
        
        foreach ($menuPermissionsMapping as $menuKey => $permissionName) {
            if (isset($menus[$menuKey]) && isset($permissions[$permissionName])) {
                // Vérifier si l'association n'existe pas déjà
                $exists = DB::table('menu_permission')
                    ->where('menu_id', $menus[$menuKey]->id)
                    ->where('permission_id', $permissions[$permissionName]->id)
                    ->exists();

                if (!$exists) {
                    DB::table('menu_permission')->insert([
                        'menu_id' => $menus[$menuKey]->id,
                        'permission_id' => $permissions[$permissionName]->id,
                    ]);
                    $associationsCreated++;
                }
            }
        }

        $this->command->info("  - {$associationsCreated} associations menu-permission créées pour {$tenant->name}");
    }
}