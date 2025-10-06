<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesController extends Controller
{
    private function setTeam(?int $teamId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    }

    public function index(Request $request)
    {
        $teamId = $request->integer('team_id');
        $tenants = DB::table('tenants')->orderBy('id')->get(['id','code','name']);

        $roles = $permissions = collect();
        if ($teamId) {
            $this->setTeam($teamId);

            $roles = Role::query()
                ->where('team_id', $teamId)
                ->with('permissions:id,name')
                ->orderBy('name')
                ->get(['id','name','guard_name','team_id'])
                ->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'guard_name' => $r->guard_name,
                    'team_id' => $r->team_id,
                    'permission_names' => $r->permissions->pluck('name')->values(),
                ]);

            $permissions = Permission::query()
                ->where('team_id', $teamId)
                ->orderBy('name')
                ->get(['id','name','guard_name','team_id']);
        }

        $users = User::orderBy('name')->get(['id','name','email']);

        return Inertia::render('dashboards/Param/admin/Roles/index', [
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

        Role::firstOrCreate([
            'name'       => $data['name'],
            'guard_name' => $data['guard_name'] ?: 'web',
            'team_id'    => $teamId,
        ]);

        return back()->with('success','Rôle créé.');
    }

    /** Toggle rôle sur utilisateur (assign/remove) */
    public function syncUserRoles(Request $request)
    {
        $data = $request->validate([
            'team_id'  => ['required','integer', Rule::exists('tenants','id')],
            'user_id'  => ['required','integer', Rule::exists('users','id')],
            'role_name'  => ['required','string','max:255'],
            'attach'     => ['required','boolean'],
        ]);

        $teamId = (int) $data['team_id'];
        $this->setTeam($teamId);

        $user = User::findOrFail($data['user_id']);
        $role = Role::where('team_id', $teamId)->where('name', $data['role_name'])->firstOrFail();

        if ($data['attach']) {
            $user->assignRole($role);
        } else {
            $user->removeRole($role);
        }

        return back()->with('success','Utilisateur mis à jour.');
    }
}