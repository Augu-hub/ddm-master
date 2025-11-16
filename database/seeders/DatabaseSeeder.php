<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Master\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------
        // 0) Utilisateur démo idempotent
        // -------------------------------------------------
        $demo = User::firstOrCreate(
            ['email' => 'demo@user.com'],
            [
                'name' => 'User',
                'password' => Hash::make('password'),
            ]
        );

        // -------------------------------------------------
        // 1) Rôles & Super admin (Spatie)
        //    -> crée le rôle super-admin et permissions de base
        // -------------------------------------------------
        $this->call(SuperAdminSeeder::class);

        // (Option) si ton SuperAdminSeeder assigne le rôle par email :
        // $demo->assignRole('super-admin');

        // -------------------------------------------------
        // 2) Catalogue + Modules + Menus par module
        //    -> crée Services, 4 Modules (param.projects, risk.core, process.core, audit.core)
        //    -> crée les menus rattachés à chaque module (sidebar)
        // -------------------------------------------------
        // Utilise l’un OU l’autre selon tes fichiers disponibles :
        // $this->call(ModuleCatalogSeeder::class);
        // $this->call(MenuModuleSeeder::class);
        $this->call(FourModulesAndMenusSeeder::class);

        // -------------------------------------------------
        // 3) Menus globaux (vitrine / admin / dashboards)
        //    -> compatible avec le schéma (type/route_name/module_id)
        // -------------------------------------------------
        $this->call(GlobalMenuSeeder::class);

        // -------------------------------------------------
        // 4) Permissions de menus côté tenant (si tu as un seeder dédié)
        // -------------------------------------------------
        if (class_exists(TenantMenuPermissionsSeeder::class)) {
            $this->call(TenantMenuPermissionsSeeder::class);
        }

        // -------------------------------------------------
        // 5) Donner l’accès aux 4 modules à l’utilisateur démo
        // -------------------------------------------------
        $moduleCodes = ['param.projects','risk.core','process.core','audit.core'];
        $moduleIds = Module::whereIn('code', $moduleCodes)->pluck('id')->all();

        if (!empty($moduleIds)) {
            // rattache sans dupliquer
            foreach ($moduleIds as $mid) {
                Module::find($mid)?->users()->syncWithoutDetaching([$demo->id]);
            }
        }

        // -------------------------------------------------
        // 6) (Option) Clear cache permissions si Spatie
        // -------------------------------------------------
        if (function_exists('app')) {
            try { app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions(); } catch (\Throwable $e) {}
        }
    }
}
