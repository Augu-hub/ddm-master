<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\{DB, Artisan};
use Illuminate\Validation\Rule;
use App\Models\Master\Tenant;
use App\Models\User;

class TenantsController extends Controller
{
    public function index(Request $request)
    {
        // Accès réservé au super admin par email
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        // Eager-load des users pour éviter le N+1
        $tenants = Tenant::with(['users:id,name,email'])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $allUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('dashboards/Param/admin/Tenants/index', [
            'tenants' => $tenants->map(function ($tenant) {
                return [
                    'id'          => $tenant->id,
                    'name'        => $tenant->name,
                    'code'        => $tenant->code,
                    'db_host'     => $tenant->db_host,
                    'db_name'     => $tenant->db_name,
                    'users_count' => $tenant->users_count,
                    'created_at'  => $tenant->created_at?->format('d/m/Y H:i'),
                    'users'       => $tenant->users->map(fn ($u) => [
                        'id' => $u->id, 'name' => $u->name, 'email' => $u->email
                    ]),
                ];
            }),
            'users' => $allUsers,
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'code'      => ['required', 'string', 'max:50',  'unique:tenants,code'],
            'db_name'   => ['required', 'string', 'max:255', 'unique:tenants,db_name'],
            'user_ids'  => ['nullable', 'array'],
            'user_ids.*'=> ['exists:users,id'],
        ]);

        try {
            DB::beginTransaction();

            // 1) Créer l’enregistrement tenant (master)
            $tenant = Tenant::create([
                'name'        => $data['name'],
                'code'        => $data['code'],
                'db_name'     => $data['db_name'],
                'db_host'     => '127.0.0.1',
                'db_username' => 'root',
                'db_password' => 'root',
            ]);

            // 2) Associer des utilisateurs (pivot master)
            if (!empty($data['user_ids'])) {
                $tenant->users()->sync($data['user_ids']);
            }

            // 3) Provisionner la base + migrations (automatique ici)
            $this->provisionTenant($tenant);

            DB::commit();

            return redirect()
                ->route('admin.tenants.index')
                ->with('success', 'Client créé et provisionné avec succès');

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Erreur création client: '.$e->getMessage());
            return back()->withInput()->with('error', 'Erreur: '.$e->getMessage());
        }
    }

    public function update(Request $request, Tenant $tenant)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
            'code'      => ['required', 'string', 'max:50',  Rule::unique('tenants')->ignore($tenant->id)],
            'db_name'   => ['required', 'string', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
            'user_ids'  => ['nullable', 'array'],
            'user_ids.*'=> ['exists:users,id'],
        ]);

        try {
            DB::beginTransaction();

            $oldDbName = $tenant->db_name;

            $tenant->update([
                'name'    => $data['name'],
                'code'    => $data['code'],
                'db_name' => $data['db_name'],
            ]);

            if (isset($data['user_ids'])) {
                $tenant->users()->sync($data['user_ids']);
            }

            if ($oldDbName !== $data['db_name']) {
                $this->renameTenantDatabase($oldDbName, $data['db_name']);
            }

            DB::commit();

            return redirect()->route('admin.tenants.index')
                ->with('success', 'Client mis à jour avec succès');

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Erreur mise à jour client: '.$e->getMessage());
            return back()->withInput()->with('error', 'Erreur: '.$e->getMessage());
        }
    }

    public function destroy(Tenant $tenant)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        if ($tenant->id === 1) {
            return back()->with('error', 'Impossible de supprimer le client principal');
        }

        try {
            DB::beginTransaction();

            $tenant->users()->detach();
            $tenant->delete();
            // Optionnel : $this->dropTenantDatabase($tenant->db_name);

            DB::commit();

            return redirect()->route('admin.tenants.index')
                ->with('success', 'Client supprimé avec succès');

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Erreur suppression client: '.$e->getMessage());
            return back()->with('error', 'Erreur: '.$e->getMessage());
        }
    }

    public function syncUsers(Request $request, Tenant $tenant)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'user_ids'   => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $tenant->users()->sync($data['user_ids']);

        return response()->json(['success' => true, 'message' => 'Utilisateurs associés avec succès']);
    }

    public function removeUser(Request $request, Tenant $tenant)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $tenant->users()->detach($data['user_id']);

        return response()->json(['success' => true, 'message' => 'Utilisateur retiré avec succès']);
    }

    public function getUsers(Tenant $tenant)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $users = $tenant->users()->select('id','name','email')->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Provisionnement complet: crée la base + configure connexion + MIGRE (+ seed optionnel)
     */
    private function provisionTenant(Tenant $tenant): void
    {
        // 1) Créer physiquement la base
        $this->createTenantDatabase($tenant->db_name);

        // 2) Configurer la connexion "tenant"
        config(['database.connections.tenant' => [
            'driver'   => 'mysql',
            'host'     => $tenant->db_host,
            'port'     => env('DB_PORT', '3306'),
            'database' => $tenant->db_name,
            'username' => $tenant->db_username,
            'password' => $tenant->db_password,
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
            'prefix'   => '',
            'strict'   => false,
        ]]);

        DB::purge('tenant');
        DB::connection('tenant')->getPdo(); // test

        // 3) MIGRATIONS du tenant (automatique ici)
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);
        \Log::info("Migrations appliquées sur {$tenant->db_name}", ['output' => Artisan::output()]);

        // 4) (Optionnel) Seed des données de base du tenant
        // Artisan::call('db:seed', [
        //     '--database' => 'tenant',
        //     '--class'    => 'TenantDataSeeder',
        //     '--force'    => true,
        // ]);
    }

    private function createTenantDatabase(string $dbName): void
    {
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        \Log::info("Base de données créée: {$dbName}");
    }

    private function renameTenantDatabase($oldDbName, $newDbName)
    {
        // ⚠ MySQL ne supporte plus RENAME DATABASE.
        // À remplacer si besoin par un export/import ou création+copie table par table.
        DB::statement("RENAME DATABASE `{$oldDbName}` TO `{$newDbName}`");
        \Log::info("Base de données renommée: {$oldDbName} -> {$newDbName}");
    }

    private function dropTenantDatabase($dbName)
    {
        DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
        \Log::info("Base de données supprimée: {$dbName}");
    }

    private function tenantHasData(Tenant $tenant): bool
    {
        try {
            $tables = DB::select("
                SELECT COUNT(*) as table_count
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$tenant->db_name]);

            return (int)($tables[0]->table_count ?? 0) > 0;

        } catch (\Throwable $e) {
            \Log::error("Erreur vérification données tenant {$tenant->id}: ".$e->getMessage());
            return false;
        }
    }
}
