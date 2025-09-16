<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retourne une vue simple avec du texte
        return Inertia::render('dashboards/Param/Projets/index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Retourne une vue simple avec du texte
        return Inertia::render('Project/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation simple
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Création du projet
        Project::create($validated);

        // Redirection vers la liste
        return redirect()->route('param.projects.index')
            ->with('success', 'Projet créé avec succès!');
    }

    // Les autres méthodes peuvent rester vides pour le moment
    public function show($id) {}
    public function edit($id) {}
    public function update(Request $request, $id) {}
    public function destroy($id) {}
}