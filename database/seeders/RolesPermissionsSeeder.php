<?php
// database/seeders/RolesPermissionsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\System\Menu;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (!class_exists(Role::class) || !class_exists(Permission::class)) {
            throw new \RuntimeException("spatie/laravel-permission n'est pas installé : composer require spatie/laravel-permission && php artisan vendor:publish --provider=\"Spatie\\Permission\\PermissionServiceProvider\" --tag=migrations && php artisan migrate");
        }

        $guard = 'web';
        $hasTenant = Schema::hasColumn('roles','tenant_id') && Schema::hasColumn('permissions','tenant_id');
        $tenantId  = $hasTenant ? $this->resolveTenantId() : null;

        // --- Rôles (firstOrCreate avec tenant si présent)
        $admin   = Role::query()->firstOrCreate(
            array_filter(['name'=>'admin','guard_name'=>$guard,'tenant_id'=>$tenantId], fn($v)=>$v!==null)
        );
        $manager = Role::query()->firstOrCreate(
            array_filter(['name'=>'manager','guard_name'=>$guard,'tenant_id'=>$tenantId], fn($v)=>$v!==null)
        );
        $viewer  = Role::query()->firstOrCreate(
            array_filter(['name'=>'viewer','guard_name'=>$guard,'tenant_id'=>$tenantId], fn($v)=>$v!==null)
        );

        // --- Permissions
        $permNames = [
            'param.projects.view','param.entities.view','param.process.view',
            'param.functions.view','param.distribution.view',
            'charts.project.view','charts.entity.view',
            'admin.permissions.view','admin.permissions.edit',
            'admin.roles.view','admin.roles.edit',
            'admin.users.view','admin.users.edit',
            'admin.menus.view','admin.menus.edit',
            'admin.translations.view','admin.tenants.view',
        ];
        foreach ($permNames as $name) {
            Permission::query()->firstOrCreate(
                array_filter(['name'=>$name,'guard_name'=>$guard,'tenant_id'=>$tenantId], fn($v)=>$v!==null)
            );
        }

        // --- Attributions de base
        $allPerms = Permission::query()
            ->where('guard_name',$guard)
            ->when($hasTenant, fn($q)=>$q->where('tenant_id',$tenantId))
            ->pluck('id')->all();

        $admin->syncPermissions($allPerms);

        $managerPerms = Permission::query()
            ->whereIn('name', [
                'param.projects.view','param.entities.view','param.process.view','param.functions.view','param.distribution.view',
                'charts.project.view','charts.entity.view','admin.users.view',
            ])
            ->where('guard_name',$guard)
            ->when($hasTenant, fn($q)=>$q->where('tenant_id',$tenantId))
            ->pluck('id')->all();
        $manager->syncPermissions($managerPerms);

        $viewerPerms = Permission::query()
            ->whereIn('name', [
                'param.projects.view','param.entities.view','param.process.view','param.functions.view',
                'charts.project.view','charts.entity.view',
            ])
            ->where('guard_name',$guard)
            ->when($hasTenant, fn($q)=>$q->where('tenant_id',$tenantId))
            ->pluck('id')->all();
        $viewer->syncPermissions($viewerPerms);

        // --- Lier menus -> permissions (menu_permission)
        $map = [
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

        foreach ($map as $menuKey => $permName) {
            $menu = Menu::where('key',$menuKey)->first();
            $perm = Permission::query()
                ->where('name',$permName)
                ->where('guard_name',$guard)
                ->when($hasTenant, fn($q)=>$q->where('tenant_id',$tenantId))
                ->first();

            if ($menu && $perm) {
                // éviter doublons
                DB::table('menu_permission')->updateOrInsert(
                    ['menu_id'=>$menu->id,'permission_id'=>$perm->id],
                    ['menu_id'=>$menu->id,'permission_id'=>$perm->id]
                );
            }
        }

        // --- Admin par défaut
        if (!User::where('email','admin@diaddem.local')->exists()) {
            $u = User::create([
                'name' => 'Super Admin',
                'email'=> 'admin@diaddem.local',
                'password' => Hash::make('admin123'),
            ]);
            $u->assignRole($admin);
        }
    }

    private function resolveTenantId(): int
    {
        // Essaie de récupérer un tenant existant ; sinon 1 par défaut
        $id = DB::table('tenants')->orderBy('id')->value('id');
        return (int)($id ?? 1);
    }
}
