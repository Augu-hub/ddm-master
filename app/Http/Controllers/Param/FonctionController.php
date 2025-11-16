<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Fonction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FonctionController extends Controller
{
    public function index(Request $request)
    {
        // Liste des fonctions (tenant) + utilisateur associé (master)
        $functions = Fonction::with([
                'parent:id,name',
                'user:id,name,email',
            ])
            ->orderBy('name')
            ->get(['id','name','character','parent_id','avatar_path','user_id']);

        // Liste des users (master) pour le <select> côté front
        $users = User::query()
            ->orderBy('name')
            ->get(['id','name','email']);

        return Inertia::render('dashboards/Param/Functions/index', [
            'functions' => $functions,
            'users'     => $users,   // ⬅️ dispo dans ta page pour un <select>
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','string','max:255'],
            'character' => ['nullable','string','max:50'],
            'parent_id' => ['nullable','integer', Rule::exists('tenant.functions','id')],
            'user_id'   => ['nullable','integer', Rule::exists('users','id')], // table master
            'avatar'    => ['nullable','image','max:2048'],
        ]);

        DB::connection('tenant')->transaction(function () use ($request, $data) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('functions/avatars', 'public');
            }

            Fonction::create([
                'name'        => $data['name'],
                'character'   => $data['character'] ?? null,
                'parent_id'   => $data['parent_id'] ?? null,
                'user_id'     => $data['user_id'] ?? null, // ⬅️ lien user
                'avatar_path' => $avatarPath,
            ]);
        });

        // ⚠️ corrige le nom de route (cohérent avec tes web.php: "param.fonctions.index")
        return redirect()
            ->route('param.projects.fonctions.index')
            ->with('success', 'Fonction créée.');
    }

    public function update(Request $request, Fonction $function)
    {
        $data = $request->validate([
            'name'          => ['required','string','max:255'],
            'character'     => ['nullable','string','max:50'],
            'parent_id'     => ['nullable','integer',
                Rule::exists('tenant.functions','id'),
                Rule::notIn([$function->id])
            ],
            'user_id'       => ['nullable','integer', Rule::exists('users','id')],
            'avatar'        => ['nullable','image','max:2048'],
            'remove_avatar' => ['nullable','boolean'],
        ]);

        DB::connection('tenant')->transaction(function () use ($request, $function, $data) {
            if (!empty($data['remove_avatar']) && $function->avatar_path) {
                Storage::disk('public')->delete($function->avatar_path);
                $function->avatar_path = null;
            }
            if ($request->hasFile('avatar')) {
                if ($function->avatar_path) {
                    Storage::disk('public')->delete($function->avatar_path);
                }
                $function->avatar_path = $request->file('avatar')->store('functions/avatars', 'public');
            }

            $function->name      = $data['name'];
            $function->character = $data['character'] ?? null;
            $function->parent_id = $data['parent_id'] ?? null;
            $function->user_id   = $data['user_id'] ?? null; // ⬅️ lien user
            $function->save();
        });

        return redirect()
            ->route('param.projects.fonctions.index')
            ->with('success', 'Fonction mise à jour.');
    }

    public function destroy(Fonction $function)
    {
        DB::connection('tenant')->transaction(function () use ($function) {
            if ($function->avatar_path) {
                Storage::disk('public')->delete($function->avatar_path);
            }
            $function->delete();
        });

        return redirect()
            ->route('param.projects.fonctions.index')
            ->with('success', 'Fonction supprimée.');
    }
}
