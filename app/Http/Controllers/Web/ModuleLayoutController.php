<?php
// app/Http/Controllers/Web/ModuleLayoutController.php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Master\Module;
use Illuminate\Http\Request;

class ModuleLayoutController extends Controller
{
    public function __invoke(Request $r, string $moduleCode)
    {
        $m = Module::where('code', $moduleCode)->where('is_active', true)->firstOrFail();

        $u = $r->user();
        $ok = $m->users()->where('user_id', $u->id)->exists()
            || (method_exists($u, 'hasRole') && $u->hasRole('super-admin'))
            || $u->can($m->code.'.view');

        abort_unless($ok, 403);

        // 1) vers entry_route_name si défini (ex: param.projects.home)
        if ($m->entry_route_name && app('router')->has($m->entry_route_name)) {
            return redirect()->route($m->entry_route_name);
        }

        // 2) fallback: {code}.home si présent dans le fichier routes du module
        $home = "{$m->code}.home";
        if (app('router')->has($home)) {
            return redirect()->route($home);
        }

        // 3) dernier recours: retour au dashboard
        return redirect()->route('dashboard');
    }
}
