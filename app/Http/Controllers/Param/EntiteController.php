<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Entite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EntiteController extends Controller
{
    public function index(Request $request)
    {
        $entities = Entite::with(['parent:id,name,level,code_base'])
            ->orderBy('name')
            ->get();

        // Parents proposés
        $parents  = Entite::orderBy('name')->get(['id','name','level','code_base']);

        return Inertia::render('dashboards/Param/Entities/index', [
            'entities' => $entities,
            'parents'  => $parents,
        ]);
    }

    public function create()
    {
        return redirect()->route('param.projects.entities.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'parent_id'   => ['nullable','exists:tenant.entities,id'],
            'code_base'   => ['nullable','string','max:255'],
            'logo'        => ['nullable','string','max:255'],
            'phone'       => ['nullable','string','max:50'],
            'email'       => ['nullable','email','max:255'],
            'leader'      => ['nullable','string','max:255'],
            'address'     => ['nullable','string'],
        ]);

        $parent = !empty($data['parent_id']) ? Entite::find($data['parent_id']) : null;
        $data['level'] = $parent ? min(($parent->level ?? 0) + 1, 255) : 0;

        if (empty($data['code_base'])) {
            $data['code_base'] = $this->makeCodeBase($data['parent_id'] ?? null, $data['name']);
        }

        Entite::create($data);

        return redirect()
            ->route('param.projects.entities.index')
            ->with('success', 'Entité créée avec succès.');
    }

    public function show(Entite $entite)
    {
        return Inertia::render('dashboards/Param/Entities/Show', [
            'entite' => $entite->load('parent:id,name'),
        ]);
    }

    public function edit(Entite $entite)
    {
        $parents  = Entite::where('id','<>',$entite->id)
                        ->orderBy('name')
                        ->get(['id','name','level','code_base']);

        return Inertia::render('dashboards/Param/Entities/Edit', [
            'entite'   => $entite->only([
                'id','name','description','level','parent_id',
                'code_base','logo','phone','email','leader','address'
            ]),
            'parents'  => $parents,
        ]);
    }

    public function update(Request $request, Entite $entite)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'parent_id'   => [
                'nullable',
                'exists:tenant.entities,id',
                Rule::notIn([$entite->id]),
            ],
            'code_base'   => ['nullable','string','max:255'],
            'logo'        => ['nullable','string','max:255'],
            'phone'       => ['nullable','string','max:50'],
            'email'       => ['nullable','email','max:255'],
            'leader'      => ['nullable','string','max:255'],
            'address'     => ['nullable','string'],
        ]);

        $parent = !empty($data['parent_id']) ? Entite::find($data['parent_id']) : null;
        $data['level'] = $parent ? min(($parent->level ?? 0) + 1, 255) : 0;

        if (empty($data['code_base'])) {
            $data['code_base'] = $this->makeCodeBase($data['parent_id'] ?? null, $data['name']);
        }

        $entite->update($data);

        return redirect()
            ->route('param.projects.entities.index')
            ->with('success', 'Entité mise à jour.');
    }

    public function destroy(Entite $entite)
    {
        $entite->delete();
        return back()->with('success','Entité supprimée.');
    }

    public function apiList()
    {
        $entities = Entite::orderBy('name')
            ->get(['id', 'name', 'code_base', 'level', 'parent_id']);

        return response()->json($entities);
    }

    public function getMenuEntities(Request $request)
    {
        $entities = Entite::orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'code_base']);

        return response()->json($entities);
    }

    /**
     * Génère un code_base à partir du parent ou du nom.
     */
    private function makeCodeBase(?int $parentId, string $name): string
    {
        $prefix = null;

        if ($parentId) {
            $prefix = Entite::whereKey($parentId)->value('code_base');
        }

        if (!$prefix) {
            $raw = Str::of($name)
                ->ascii()
                ->upper()
                ->replaceMatches('/[^A-Z0-9]/', '')
                ->value();
            $base = substr($raw, 0, 3);
            $prefix = $base !== '' ? str_pad($base, 3, 'X') : 'ENT';
        }

        $siblingsCount = Entite::when($parentId, fn($q) => $q->where('parent_id', $parentId),
                                      fn($q) => $q->whereNull('parent_id'))
                               ->count();

        $seq = str_pad((string)($siblingsCount + 1), 2, '0', STR_PAD_LEFT);

        return "{$prefix}-{$seq}";
    }
}