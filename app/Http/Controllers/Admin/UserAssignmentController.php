<?php
// app/Http/Controllers/Admin/UserAssignmentController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Master\Module;

class UserAssignmentController extends Controller
{
    /** Page d’affectation modules uniquement */
    public function pageUsersModules()
    {
        $users = User::select('id','name','email')
            ->with(['modules:id,code,name'])
            ->orderBy('name')
            ->get();

        $modules = Module::with('service:id,code,name') // ok d’afficher l’info, mais on n’écrit pas dans service_user
            ->select('id','service_id','code','name')
            ->orderBy('name')
            ->get();

        return inertia('dashboards/Param/admin/assign/UsersModules', compact('users','modules'));
    }

    /** Affectation directe de modules (uniquement module_user) */
    public function syncModules(Request $r, User $user)
    {
        $data = $r->validate([
            'module_ids'   => ['required','array','min:1'],
            'module_ids.*' => ['integer','exists:modules,id'],
            'mode'         => ['nullable','in:replace,append,detach_missing'],
        ]);

        $mode = $data['mode'] ?? 'replace';
        $moduleIds = array_values(array_unique($data['module_ids']));

        DB::transaction(function () use ($user, $moduleIds, $mode) {
            if ($mode === 'append') {
                $user->modules()->syncWithoutDetaching($moduleIds);
            } elseif ($mode === 'detach_missing') {
                // détache tout ce qui n’est pas dans la liste (équiv. “replace” strict)
                $user->modules()->sync($moduleIds);
            } else {
                // replace (par défaut)
                $user->modules()->sync($moduleIds);
            }
        });

        return back()->with('success','Modules affectés.');
    }
}
