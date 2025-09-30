<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Projet;
use App\Models\Param\Fonction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FonctionController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->integer('project_id');
        $projects  = Projet::orderBy('name')->get(['id','name']);

        $functions = $projectId
            ? Fonction::with(['parent:id,name,project_id'])
                ->inProject($projectId)
                ->orderBy('name')
                // IMPORTANT: inclure avatar_path pour que l'accessor avatar_url fonctionne
                ->get(['id','project_id','name','character','parent_id','avatar_path'])
            : collect();

        return Inertia::render('dashboards/Param/Functions/index', [
            'projects'  => $projects,
            'functions' => $functions,
            'filters'   => ['project_id' => $projectId],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id'    => ['required','exists:tenant.projects,id'],
            'name'          => ['required','string','max:255'],
            'character'     => ['nullable','string','max:50'],
            'parent_id'     => ['nullable','integer', Rule::exists('tenant.functions','id')],
            'avatar'        => ['nullable','image','max:2048'],    // <— image (2 Mo)
        ]);

        if (!empty($data['parent_id'])) {
            $parent = Fonction::findOrFail($data['parent_id']);
            if ((int)$parent->project_id !== (int)$data['project_id']) {
                return back()->withErrors(['parent_id' => 'Le parent doit appartenir au même projet.'])->withInput();
            }
        }

        DB::connection('tenant')->transaction(function () use ($request, $data) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('functions/avatars', 'public');
            }

            Fonction::create([
                'project_id'  => $data['project_id'],
                'name'        => $data['name'],
                'character'   => $data['character'] ?? null,
                'parent_id'   => $data['parent_id'] ?? null,
                'avatar_path' => $avatarPath,
            ]);
        });

        return redirect()->route('param.function.index', ['project_id' => $data['project_id']])
            ->with('success', 'Fonction créée.');
    }

    public function update(Request $request, Fonction $function)
    {
        $data = $request->validate([
            'project_id'    => ['required','exists:tenant.projects,id'],
            'name'          => ['required','string','max:255'],
            'character'     => ['nullable','string','max:50'],
            'parent_id'     => [
                'nullable','integer',
                Rule::exists('tenant.functions','id'),
                Rule::notIn([$function->id]),
            ],
            'avatar'        => ['nullable','image','max:2048'], // <— fichier
            'remove_avatar' => ['nullable','boolean'],          // <— case pour supprimer
        ]);

        if (!empty($data['parent_id'])) {
            $parent = Fonction::findOrFail($data['parent_id']);
            if ((int)$parent->project_id !== (int)$data['project_id']) {
                return back()->withErrors(['parent_id' => 'Le parent doit appartenir au même projet.'])->withInput();
            }
        }

        DB::connection('tenant')->transaction(function () use ($request, $function, $data) {
            // Supprimer l’ancien avatar si demandé
            if (!empty($data['remove_avatar']) && $function->avatar_path) {
                Storage::disk('public')->delete($function->avatar_path);
                $function->avatar_path = null;
            }

            // Nouveau fichier ?
            if ($request->hasFile('avatar')) {
                if ($function->avatar_path) {
                    Storage::disk('public')->delete($function->avatar_path);
                }
                $function->avatar_path = $request->file('avatar')->store('functions/avatars', 'public');
            }

            $function->project_id = $data['project_id'];
            $function->name       = $data['name'];
            $function->character  = $data['character'] ?? null;
            $function->parent_id  = $data['parent_id'] ?? null;
            $function->save();
        });

        return redirect()->route('param.function.index', ['project_id' => $data['project_id']])
            ->with('success', 'Fonction mise à jour.');
    }

    public function destroy(Request $request, Fonction $function)
    {
        $projectId = $request->integer('project_id');

        DB::connection('tenant')->transaction(function () use ($function) {
            if ($function->avatar_path) {
                Storage::disk('public')->delete($function->avatar_path);
            }
            $function->delete();
        });

        return redirect()->route('param.function.index', ['project_id' => $projectId])
            ->with('success', 'Fonction supprimée.');
    }
}
