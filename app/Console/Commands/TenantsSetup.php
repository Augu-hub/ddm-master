<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Artisan, Config, DB, Schema};
use App\Models\Master\Tenant;

class TenantsSetup extends Command
{
    protected $signature = 'tenants:setup 
        {--tenant= : ID du tenant (sinon tous)} 
        {--fresh : migrate:fresh au lieu de migrate} 
        {--seed  : lancer un seeder tenant (TenantDataSeeder)}';

    protected $description = 'Crée les bases si besoin, migre et seed les bases des tenants';

    public function handle(): int
    {
        $query = Tenant::query();
        if ($id = $this->option('tenant')) {
            $query->whereKey($id);
        }

        $tenants = $query->get();
        if ($tenants->isEmpty()) {
            $this->warn('Aucun tenant trouvé.');
            return self::SUCCESS;
        }

        foreach ($tenants as $t) {
            $this->line(str_repeat('─', 60));
            $this->info("Tenant #{$t->id} [{$t->code}] DB={$t->db_name}");

            // 1) Créer la base si elle n’existe pas (sur la connexion maître "mysql")
            try {
                $charset  = config('database.connections.mysql.charset', 'utf8mb4');
                $collate  = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');
                DB::statement("CREATE DATABASE IF NOT EXISTS `{$t->db_name}` CHARACTER SET {$charset} COLLATE {$collate}");
                $this->info("✔ Base `{$t->db_name}` OK (créée ou déjà existante)");
            } catch (\Throwable $e) {
                $this->error("✖ Impossible de créer la base `{$t->db_name}` : ".$e->getMessage());
                continue; // on passe au tenant suivant
            }

            // 2) Configurer dynamiquement la connexion "tenant"
            Config::set('database.connections.tenant', [
                'driver'    => config('database.connections.mysql.driver', 'mysql'),
                'host'      => $t->db_host,
                'port'      => config('database.connections.mysql.port', 3306),
                'database'  => $t->db_name,
                'username'  => $t->db_username,
                'password'  => $t->db_password,
                'charset'   => config('database.connections.mysql.charset', 'utf8mb4'),
                'collation' => config('database.connections.mysql.collation', 'utf8mb4_unicode_ci'),
                'prefix'    => '',
                'strict'    => false,
            ]);

            DB::purge('tenant');
            try {
                DB::connection('tenant')->getPdo(); // test connexion
                $this->info("✔ Connexion tenant OK");
            } catch (\Throwable $e) {
                $this->error("✖ Connexion tenant KO : ".$e->getMessage());
                continue;
            }

            // 3) Migrations du dossier tenant
            $args = [
                '--database' => 'tenant',
                '--path'     => 'database/migrations/tenant',
                '--force'    => true,
            ];

            try {
                if ($this->option('fresh')) {
                    Artisan::call('migrate:fresh', $args);
                } else {
                    Artisan::call('migrate', $args);
                }
                $this->line(rtrim(Artisan::output()));
            } catch (\Throwable $e) {
                $this->error("✖ Migration échouée : ".$e->getMessage());
                continue;
            }

            // 4) (Optionnel) Seed des données spécifiques au tenant
            if ($this->option('seed')) {
                try {
                    Artisan::call('db:seed', [
                        '--database' => 'tenant',
                        '--class'    => 'TenantDataSeeder', // crée ce seeder si besoin
                        '--force'    => true,
                    ]);
                    $this->line(rtrim(Artisan::output()));
                    $this->info("✔ Seed tenant OK");
                } catch (\Throwable $e) {
                    $this->error("✖ Seed tenant KO : ".$e->getMessage());
                }
            }

            $this->info("✅ Setup terminé pour {$t->code}");
        }

        $this->line(str_repeat('─', 60));
        $this->info("Tous les tenants traités.");
        return self::SUCCESS;
    }
}
