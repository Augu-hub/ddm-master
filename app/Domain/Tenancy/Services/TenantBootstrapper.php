<?php

namespace App\Domain\Tenancy\Services;

use Illuminate\Support\Facades\DB;

class TenantBootstrapper
{
    public function ensureSeeded(): void
    {
        $tenantId = app()->bound('currentTenantId') ? app('currentTenantId') : null;
        $flag = "tenant_bootstrapped_{$tenantId}";
        if (session()->get($flag)) return;

        $conn = DB::connection('tenant');

        if (!$conn->table('projects')->exists()) {
            $projectId = $conn->table('projects')->insertGetId([
                'code'       => 'DEF',
                'name'       => 'Premier projet',
                'character'  => 'Défaut',
                'description'=> 'Projet initial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $conn->table('entities')->insert([
                'project_id'  => $projectId,
                'name'        => 'Organisation',
                'description' => 'Entité racine',
                'level'       => 0,
                'parent_id'   => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        session()->put($flag, true);
    }
}
