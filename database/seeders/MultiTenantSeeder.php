<?php



class MultiTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['code'=>'clientA','name'=>'Client A','db_host'=>'127.0.0.1','db_name'=>'clientA','db_username'=>'root','db_password'=>'root'],
            ['code'=>'clientB','name'=>'Client B','db_host'=>'127.0.0.1','db_name'=>'clientB','db_username'=>'root','db_password'=>'root'],
        ];

        foreach ($tenants as $t) {
            DB::table('tenants')->updateOrInsert(
                ['code' => $t['code']],
                [
                    'name'        => $t['name'],
                    'db_host'     => $t['db_host'],
                    'db_name'     => $t['db_name'],
                    'db_username' => $t['db_username'],
                    'db_password' => $t['db_password'],
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }
}
