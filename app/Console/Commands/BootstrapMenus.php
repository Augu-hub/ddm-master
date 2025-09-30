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
        $this->line('<info>DDM Bootstrap</info>');

        if ($this->option('fresh')) {
            $this->warn('-> php artisan migrate:fresh');
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->output->write(Artisan::output());
        } else {
            $this->warn('-> php artisan migrate');
            Artisan::call('migrate', ['--force' => true]);
            $this->output->write(Artisan::output());
        }

        $this->warn('-> Seeding MenuSeeder');
        Artisan::call('db:seed', ['--class' => \Database\Seeders\MenuSeeder::class, '--force' => true]);
        $this->output->write(Artisan::output());

        $this->warn('-> Seeding RolesPermissionsSeeder');
        Artisan::call('db:seed', ['--class' => \Database\Seeders\RolesPermissionsSeeder::class, '--force' => true]);
        $this->output->write(Artisan::output());

        $this->info('✔ Terminé. Connecte-toi avec admin@diaddem.local / admin123 (à changer !)');
        return Command::SUCCESS;
    }
}
