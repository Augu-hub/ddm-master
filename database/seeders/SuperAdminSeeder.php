<?php
// database/seeders/SuperAdminSeeder.php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Master\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création du compte Super Admin...');

        // Vérifier si le super admin existe déjà
        $superAdmin = User::where('email', 'admin@diaddem.local')->first();

        if (!$superAdmin) {
            // Créer le super admin
            $superAdmin = User::create([
                'name' => 'Super Administrateur',
                'email' => 'admin@diaddem.local',
                'password' => Hash::make('Admin123!'),
                'email_verified_at' => now(),
            ]);

            $this->command->info('✅ Compte Super Admin créé: admin@diaddem.local / Admin123!');
        } else {
            $this->command->info('ℹ️  Compte Super Admin existe déjà');
        }

        // Assigner tous les rôles admin à tous les tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->command->info("Configuration des droits pour le tenant: {$tenant->name}");

            // Définir le tenant courant
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            // Récupérer le rôle admin du tenant
            $adminRole = Role::where('name', 'admin')
                ->where('team_id', $tenant->id)
                ->first();

            if ($adminRole && !$superAdmin->hasRole($adminRole)) {
                $superAdmin->assignRole($adminRole);
                $this->command->info("✅ Rôle admin assigné pour le tenant: {$tenant->name}");
            }

            // Donner aussi toutes les permissions directement
            $permissions = Permission::where('team_id', $tenant->id)->get();
            $superAdmin->givePermissionTo($permissions);
        }

        // Réinitialiser le cache des permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('🎯 Super Admin configuré avec tous les droits sur tous les tenants');
        
        // Afficher un résumé
        $this->displayAdminSummary($superAdmin);
    }

    private function displayAdminSummary(User $admin): void
    {
        $this->command->line('');
        $this->command->info('=== RÉSUMÉ DU COMPTE SUPER ADMIN ===');
        $this->command->line("👤 Nom: {$admin->name}");
        $this->command->line("📧 Email: {$admin->email}");
        $this->command->line("🆔 ID: {$admin->id}");
        $this->command->line("📅 Créé le: {$admin->created_at}");
        
        // Compter les rôles et permissions
        $rolesCount = $admin->roles()->count();
        $permissionsCount = $admin->getAllPermissions()->count();
        
        $this->command->line("🎭 Rôles assignés: {$rolesCount}");
        $this->command->line("🔑 Permissions totales: {$permissionsCount}");
        $this->command->line('');
    }
}