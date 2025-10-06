<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantDataSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer le nom du tenant depuis la configuration de la base
        $tenantDb = config('database.connections.tenant.database');
        
        // Extraire le nom du tenant du nom de la base
        $tenantName = $this->extractTenantName($tenantDb);

        // Créer uniquement le projet avec le nom du tenant
        DB::table('projects')->insert([
            'code' => strtoupper(substr($tenantName, 0, 3)),
            'name' => $tenantName, // Utiliser uniquement le nom du tenant
            'character' => strtoupper(substr($tenantName, 0, 3)),
            'description' => 'Projet principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Extraire le nom du tenant du nom de la base de données
     */
    private function extractTenantName($dbName): string
    {
        // Si la base commence par "client", on retire ce préfixe
        if (str_starts_with($dbName, 'client')) {
            return ucfirst(substr($dbName, 6));
        }
        
        return ucfirst($dbName);
    }
}