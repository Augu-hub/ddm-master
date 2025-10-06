<?php
// database/seeders/RolesPermissionsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Param\Menu; // <<< aligne avec ton MenuSeeder

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (!class_exists(Role::class) || !class_exists(Permission::class)) {
            throw new \RuntimeException(
                "spatie/laravel-permission manquant. Exécute :\n".
                "composer require spatie/laravel-permission\n".
                "php artisan vendor:publish --provider=\"Spatie\\Permission\\PermissionServiceProvider\" --tag=migrations\n".
                "php artisan migrate"
            );
        }

        $guard = 'web';
        $hasTenantColumns = Schema::hasColumn('roles','tenant_id') && Schema::hasColumn('permissions','tenant_id');

        // Si aucun tenant en base master, on en crée un "default"
        if (!DB::table('tenants')->exists()) {
            DB::table('tenants')->insert([
                'code' => 'default', 'name' => 'Default',
                'db_host' => '127.0.0.1', 'db_name' => 'default',
                'db_username' => 'root', 'db_password' => '',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $tenants = DB::table('tenants')->orderBy('id')->get(['id','code','name']);

        $permNames = [
            // PARAM
            'param.projects.view','param.entities.view','param.process.view',
            'param.functions.view','param.distribution.view',
            // CHARTS
            'charts.project.view','charts.entity.view',
            // ADMIN
            'admin.permissions.view','admin.permissions.edit',
            'admin.roles.view','admin.roles.edit',
            'admin.users.view','admin.users.edit',
            'admin.menus.view','admin.menus.edit',
            'admin.translations.view','admin.tenants.view',
        ];

        // mapping menu.key -> permission.name
        $menuToPerm = [
            'project'        => 'param.projects.view',
            'entity'         => 'param.entities.view',
            'process'        => 'param.process.view',
            'function'       => 'param.functions.view',
            'distribution'   => 'param.distribution.view',
            'entity_chart'   => 'charts.project.view',
            'function_chart' => 'charts.project.view',
            'charts_entity'  => 'charts.entity.view',
            'permissions'    => 'admin.permissions.view',
            'roles'          => 'admin.roles.view',
            'users'          => 'admin.users.view',
            'menus'          => 'admin.menus.view',
            'i18n'           => 'admin.translations.view',
            'tenants'        => 'admin.tenants.view',
        ];

        foreach ($tenants as $t) {
            $tenantId = (int) $t->id;

            // Fixe le "team" courant pour Spatie (obligatoire quand teams=true)
            app(PermissionRegistrar::class)->setPermissionsTeamId($hasTenantColumns ? $tenantId : null);

            // Rôles du tenant
            $admin   = Role::query()->firstOrCreate($this->attrs(['name'=>'admin',   'guard_name'=>$guard, 'tenant_id'=>$hasTenantColumns?$tenantId:null]));
            $manager = Role::query()->firstOrCreate($this->attrs(['name'=>'manager', 'guard_name'=>$guard, 'tenant_id'=>$hasTenantColumns?$tenantId:null]));
            $viewer  = Role::query()->firstOrCreate($this->attrs(['name'=>'viewer',  'guard_name'=>$guard, 'tenant_id'=>$hasTenantColumns?$tenantId:null]));

            // Permissions du tenant
            foreach ($permNames as $name) {
                Permission::query()->firstOrCreate($this->attrs(['name'=>$name,'guard_name'=>$guard,'tenant_id'=>$hasTenantColumns?$tenantId:null]));
            }

            // Affectations
            $allIds = Permission::query()
                ->where('guard_name',$guard)
                ->when($hasTenantColumns, fn($q)=>$q->where('tenant_id',$tenantId))
                ->pluck('id')->all();
            $admin->syncPermissions($allIds);

            $managerIds = Permission::query()
                ->whereIn('name', [
                    'param.projects.view','param.entities.view','param.process.view','param.functions.view','param.distribution.view',
                    'charts.project.view','charts.entity.view','admin.users.view',
                ])
                ->where('guard_name',$guard)
                ->when($hasTenantColumns, fn($q)=>$q->where('tenant_id',$tenantId))
                ->pluck('id')->all();
            $manager->syncPermissions($managerIds);

            $viewerIds = Permission::query()
                ->whereIn('name', [
                    'param.projects.view','param.entities.view','param.process.view','param.functions.view',
                    'charts.project.view','charts.entity.view',
                ])
                ->where('guard_name',$guard)
                ->when($hasTenantColumns, fn($q)=>$q->where('tenant_id',$tenantId))
                ->pluck('id')->all();
            $viewer->syncPermissions($viewerIds);

            // Lier menus -> permissions du tenant
            foreach ($menuToPerm as $menuKey => $permName) {
                $menu = Menu::where('key',$menuKey)->first();
                if (!$menu) continue;

                $perm = Permission::query()
                    ->where('name',$permName)
                    ->where('guard_name',$guard)
                    ->when($hasTenantColumns, fn($q)=>$q->where('tenant_id',$tenantId))
                    ->first();

                if ($perm) {
                    DB::table('menu_permission')->updateOrInsert(
                        ['menu_id'=>$menu->id, 'permission_id'=>$perm->id],
                        ['menu_id'=>$menu->id, 'permission_id'=>$perm->id]
                    );
                }
            }

            $this->command?->info("✔ Roles & permissions OK pour tenant {$t->code}");
        }

        // (Optionnel) créer un compte super admin global une fois (bypass via Gate::before)
        if (!User::where('email','admin@diaddem.local')->exists()) {
            $u = User::create([
                'name' => 'Super Admin',
                'email'=> 'admin@diaddem.local',
                'password' => Hash::make('admin123'),
                // si tu as une colonne is_super_admin (bool)
                // 'is_super_admin' => true,
            ]);
            // si tu veux aussi lui donner le rôle "admin" du 1er tenant :
            if ($tenants->count() > 0) {
                $firstTenantId = (int)$tenants->first()->id;
                if ($hasTenantColumns) app(PermissionRegistrar::class)->setPermissionsTeamId($firstTenantId);
                $role = Role::where($this->attrs(['name'=>'admin','guard_name'=>$guard,'tenant_id'=>$hasTenantColumns?$firstTenantId:null]))->first();
                if ($role) $u->assignRole($role);
            }
        }
    }

    private function attrs(array $kv): array
    {
        // supprime les clés null pour firstOrCreate
        return array_filter($kv, fn($v)=>$v!==null);
    }
}
