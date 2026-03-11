<?php
// app/Http/Controllers/Param/CompetencyCategoryController.php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\CompetencyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CompetencyCategoryController extends Controller
{
    /**
     * 📋 Liste des catégories
     */
    public function index()
    {
        Log::info('📋 Chargement liste catégories de compétences...');

        $categories = CompetencyCategory::withCount('competencies')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('dashboards/Param/Competencies/CategoriesIndex', [
            'categories' => $categories,
        ]);
    }

    /**
     * ➕ Formulaire de création
     */
    public function create()
    {
        return Inertia::render('dashboards/Param/Competencies/CategoryCreate');
    }

    /**
     * ✅ Enregistrer une catégorie
     */
    public function store(Request $request)
    {
        Log::info('✅ Création catégorie de compétence...');

        $validated = $request->validate([
            'code' => 'required|string|unique:competency_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        CompetencyCategory::create($validated);

        Log::info('✅ Catégorie créée:', ['code' => $validated['code']]);

        return redirect()->route('param.projects.competency-categories.index')
            ->with('success', 'Catégorie créée avec succès');
    }

    /**
     * 👁️ Afficher une catégorie
     */
    public function show(CompetencyCategory $competencyCategory)
    {
        return Inertia::render('dashboards/Param/Competencies/CategoryShow', [
            'category' => $competencyCategory->load('competencies'),
        ]);
    }

    /**
     * ✏️ Formulaire d'édition
     */
    public function edit(CompetencyCategory $competencyCategory)
    {
        return Inertia::render('dashboards/Param/Competencies/CategoryEdit', [
            'category' => $competencyCategory,
        ]);
    }

    /**
     * 💾 Mettre à jour
     */
    public function update(Request $request, CompetencyCategory $competencyCategory)
    {
        Log::info('💾 Mise à jour catégorie:', ['id' => $competencyCategory->id]);

        $validated = $request->validate([
            'code' => 'required|string|unique:competency_categories,code,' . $competencyCategory->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $competencyCategory->update($validated);

        Log::info('✅ Catégorie mise à jour');

        return redirect()->route('param.projects.competency-categories.index')
            ->with('success', 'Catégorie mise à jour avec succès');
    }

    /**
     * 🗑️ Supprimer
     */
    public function destroy(CompetencyCategory $competencyCategory)
    {
        Log::info('🗑️ Suppression catégorie:', ['id' => $competencyCategory->id]);

        if ($competencyCategory->competencies()->exists()) {
            return back()->withErrors([
                'error' => 'Cette catégorie contient des compétences. Supprimez-les d\'abord.'
            ]);
        }

        $competencyCategory->delete();

        Log::info('✅ Catégorie supprimée');

        return redirect()->route('param.projects.competency-categories.index')
            ->with('success', 'Catégorie supprimée avec succès');
    }
}


