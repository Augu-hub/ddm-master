<?php
// app/Http/Controllers/System/MenuPermissionController.php
namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuPermissionController extends Controller
{
    public function visibility(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['visibility'=>[]]);

        // ⚠️ récupère le tenant courant depuis la session (adaptable à ton multi-tenant)
        $tenantId = (int) ($request->session()->get('tenant_id') ?? 0);

        // Permissions directes utilisateur
        $direct = DB::table('model_has_permissions')
            ->where('tenant_id', $tenantId)
            ->where('model_type', get_class($user))
            ->where('model_id', $user->id)
            ->pluck('permission_id');

        // Permissions via rôles
        $roleIds = DB::table('model_has_roles')
            ->where('tenant_id', $tenantId)
            ->where('model_type', get_class($user))
            ->where('model_id', $user->id)
            ->pluck('role_id');

        $viaRoles = DB::table('role_has_permissions')
            ->where('tenant_id', $tenantId)
            ->whereIn('role_id', $roleIds)
            ->pluck('permission_id');

        // Noms des permissions
        $permissionIds = $direct->merge($viaRoles)->unique()->values();
        $permissionNames = DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->pluck('name')
            ->all();

        // Menu autorisé: tout menu_key mappé à une permission possédée
        $rows = DB::table('menu_permissions')
            ->whereIn('permission', $permissionNames)
            ->get(['menu_key', 'action']);

        // visibility[menu_key] = true (si restreint et autorisé)
        $visibility = [];
        foreach ($rows as $r) {
            $visibility[$r->menu_key] = true;
        }

        return response()->json(['visibility' => $visibility]);
    }
}
