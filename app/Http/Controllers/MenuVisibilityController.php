<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Gate};
use Spatie\Permission\PermissionRegistrar;

class MenuVisibilityController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        // Super admin global = tout visible
        if ($user && $user->email === 'admin@diaddem.local') {
            $allKeys = DB::connection('mysql')->table('menus')->pluck('key')->all();
            return response()->json(['visibility' => collect($allKeys)->mapWithKeys(fn($k)=>[$k=>true])->all()]);
        }

        // Team/tenant Spatie
        $tenantId = (int)($request->session()->get('tenant_id') ?? 0);
        if (config('permission.teams') && $tenantId > 0) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        } else {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        // On récupère pour chaque KEY les perms mappées (maître)
        $rows = DB::connection('mysql')->table('menus as m')
            ->leftJoin('menu_permission as mp','mp.menu_id','=','m.id')
            ->leftJoin('permissions as p','p.id','=','mp.permission_id')
            ->select('m.key','p.name as perm')
            ->get()
            ->groupBy('key');

        $visibility = [];
        foreach ($rows as $key => $group) {
            $perms = $group->pluck('perm')->filter()->unique()->values()->all();
            // pas de mapping => public (true) pour que les titres/éléments non protégés restent visibles
            $visibility[$key] = empty($perms) ? true : Gate::any($perms);
        }

        return response()->json(['visibility' => $visibility]);
    }
}
