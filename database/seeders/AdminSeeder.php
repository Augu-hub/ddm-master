<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};
use App\Models\User;                     // users en base maître
use App\Models\Master\Tenant;            // modèle Tenant (base maître)
use App\Models\Master\Role;              // modèle Role (base maître, scopé tenant_id)

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Paramètres (ENV -> valeurs par défaut)
        $name  = env('ADMIN_NAME', 'Admin');
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $pass  = env('ADMIN_PASSWORD', 'password');

        // 2) Créer/màj l’utilisateur admin (base maître)
        $admin = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($pass)]
        );

        // 3) Pour chaque tenant : rattacher + créer rôle owner + assigner
        foreach (Tenant::cursor() as $t) {
            // 3.1 appartenance user <-> tenant
            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $t->id, 'user_id' => $admin->id],
                ['role_hint' => 'owner', 'created_at'=>now(), 'updated_at'=>now()]
            );

            // 3.2 rôle owner (créé s’il n’existe pas)
            $owner = Role::updateOrCreate(
                ['tenant_id'=>$t->id, 'name'=>'owner'],
                ['guard_name'=>'web']
            );

            // 3.3 assignation du rôle owner à l’admin dans ce tenant
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id'    => $owner->id,
                    'tenant_id'  => $t->id,
                    'model_type' => User::class,
                    'model_id'   => $admin->id,
                ],
                []
            );
        }

        $this->command->info("Admin '$email' prêt, attaché à ".Tenant::count()." tenant(s).");
    }
}
