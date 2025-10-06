<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionsController extends Controller
{
    private function setTeam(?int $teamId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    }

    public function index(Request $request)
    {
        $teamId = $request->integer('team_id');
        
        // Liste des tenants depuis la base maître
        $tenants = DB::connection('mysql')
            ->table('tenants')
            ->orderBy('id')
            ->get(['id','code','name']);

        $roles = $permissions = collect();
        
        if ($teamId) {
            $this->setTeam($teamId);

            // Récupérer les rôles avec leurs permissions
            $roles = Role::where('team_id', $teamId)
                ->with('permissions:id,name')
                ->orderBy('name')
                ->get(['id','name','guard_name','team_id'])
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'team_id' => $role->team_id,
                        'permission_names' => $role->permissions->pluck('name')->values(),
                    ];
                });

            // Récupérer les permissions avec les rôles associés
            $permissions = Permission::where('team_id', $teamId)
                ->orderBy('name')
                ->get(['id','name','guard_name','team_id'])
                ->map(function ($permission) use ($roles) {
                    $roleIds = $roles->filter(function ($role) use ($permission) {
                        return in_array($permission->name, $role['permission_names']->all());
                    })->pluck('id')->values();
                    
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'team_id' => $permission->team_id,
                        'role_ids' => $roleIds,
                    ];
                });
        }

        // Utilisateurs depuis la base maître
        $users = User::orderBy('name')->get(['id','name','email']);

        return Inertia::render('dashboards/Param/admin/Permissions/index', [
            'tenants'     => $tenants,
            'roles'       => $roles,
            'permissions' => $permissions,
            'users'       => $users,
            'filters'     => ['team_id' => $teamId],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'guard_name' => ['nullable','string','max:50'],
            'team_id'    => ['required','integer', Rule::exists('tenants','id')],
        ]);

        $teamId = (int) $data['team_id'];
        $this->setTeam($teamId);

        Permission::firstOrCreate([
            'name'       => $data['name'],
            'guard_name' => $data['guard_name'] ?: 'web',
            'team_id'    => $teamId,
        ]);

        return back()->with('success', 'Permission créée.');
    }

    /** Toggle permission sur un rôle (attach/detach) */
    public function syncRole(Request $request)
    {
        $data = $request->validate([
            'team_id'         => ['required','integer', Rule::exists('tenants','id')],
            'role_id'         => ['required','integer', Rule::exists('roles','id')],
            'permission_name' => ['required','string','max:255'],
            'attach'          => ['required','boolean'],
        ]);

        $teamId = (int) $data['team_id'];
        $this->setTeam($teamId);

        $role = Role::where('team_id', $teamId)->findOrFail($data['role_id']);
        $permission = Permission::where('team_id', $teamId)
            ->where('name', $data['permission_name'])
            ->firstOrFail();

        if ($data['attach']) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        return back()->with('success', 'Rôle mis à jour.');
    }

    /** Toggle permission sur un utilisateur (attach/detach) */
    public function syncUser(Request $request)
    {
        $data = $request->validate([
            'team_id'         => ['required','integer', Rule::exists('tenants','id')],
            'user_id'         => ['required','integer', Rule::exists('users','id')],
            'permission_name' => ['required','string','max:255'],
            'attach'          => ['required','boolean'],
        ]);

        $teamId = (int) $data['team_id'];
        $this->setTeam($teamId);

        $user = User::findOrFail($data['user_id']);
        $permission = Permission::where('team_id', $teamId)
            ->where('name', $data['permission_name'])
            ->firstOrFail();

        if ($data['attach']) {
            $user->givePermissionTo($permission);
        } else {
            $user->revokePermissionTo($permission);
        }

        return back()->with('success', 'Utilisateur mis à jour.');
    }
}