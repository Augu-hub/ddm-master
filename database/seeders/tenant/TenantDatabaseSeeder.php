<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantProcessReferenceSeeder::class, // ← références Processus (maturité, criticité, RACI, IDEA, etc.)
        ]);
    }
}
