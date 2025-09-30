<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Projet; 
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projets = Projet::all(); // Récupérer tous les projets
        
        // Passer les projets à la vue
        return Inertia::render('dashboards/Param/Projets/index', [
            'projects' => $projets
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('dashboards/Param/Projets/Create'); // Chemin corrigé
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation avec tous les champs
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:projects,code',
            'name' => 'required|string|max:255|unique:projects,name',
            'character' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        // Création du projet
        Projet::create($validated);

        // Redirection vers la liste
        return redirect()->route('param.projects.index')
            ->with('success', 'Projet créé avec succès!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $projet = Projet::findOrFail($id);
        return Inertia::render('dashboards/Param/Projets/Show', [
            'project' => $projet
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $projet = Projet::findOrFail($id);
        return Inertia::render('dashboards/Param/Projets/Edit', [
            'project' => $projet
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $projet = Projet::findOrFail($id);
        
        // Validation avec ignore pour les règles unique
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:projects,code,'.$id,
            'name' => 'required|string|max:255|unique:projects,name,'.$id,
            'character' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        // Mise à jour du projet
        $projet->update($validated);

        return redirect()->route('param.projects.index')
            ->with('success', 'Projet modifié avec succès!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $projet = Projet::findOrFail($id);
        $projet->delete();

        return redirect()->route('param.projects.index')
            ->with('success', 'Projet supprimé avec succès!');
    }
}