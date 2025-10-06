<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BootstrapMenus extends Command
{
    protected $signature = 'ddm:bootstrap {--fresh : Reset la base avant}';
    protected $description = 'Crée les tables de menus, rôles/permissions et seed le tout (menus + ACL + admin)';

    public function handle(): int
    {
        $this->line('<info>=== DDM Bootstrap ===</info>');

        // Options de migration
        if ($this->option('fresh')) {
            $this->warn('-> Migration complète (fresh)');
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $this->warn('-> Migration standard');
            Artisan::call('migrate', ['--force' => true]);
        }
        $this->output->write(Artisan::output());

        // Seeders dans l'ordre logique
        $seeders = [
            
            'GlobalMenuSeeder' => 'Structure des menus',
            'TenantMenuPermissionsSeeder' => 'Permissions par tenant',
            'SuperAdminSeeder' => 'Compte super admin'
        ];

        foreach ($seeders as $seeder => $description) {
            $this->warn("-> {$description}");
            try {
                Artisan::call('db:seed', [
                    '--class' => "Database\\Seeders\\{$seeder}",
                    '--force' => true
                ]);
                $this->output->write(Artisan::output());
            } catch (\Exception $e) {
                $this->error("❌ Erreur avec {$seeder}: " . $e->getMessage());
            }
        }

        // Affichage des informations de connexion
        $this->info('✅ Bootstrap terminé avec succès !');
        $this->line('');
        $this->line('=== INFORMATIONS DE CONNEXION ===');
        $this->line('📧 Email: <comment>admin@diaddem.local</comment>');
        $this->line('🔑 Mot de passe: <comment>Admin123!</comment>');
        $this->line('👤 Rôle: <comment>Super Administrateur</comment>');
        $this->line('');
        $this->warn('⚠️  IMPORTANT: Changez le mot de passe après la première connexion !');
        $this->line('');

        return Command::SUCCESS;
    }
}