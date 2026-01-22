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

// ====================================================
// app/Http/Controllers/Param/CompetencyController.php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Competency;
use App\Models\Param\CompetencyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CompetencyController extends Controller
{
    /**
     * 📋 Liste des compétences
     */
    public function index(Request $request)
    {
        Log::info('📋 Chargement liste compétences...');

        $query = Competency::with('category');

        // Filtrer par catégorie
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'LIKE', "%{$request->search}%")
                  ->orWhere('name', 'LIKE', "%{$request->search}%");
            });
        }

        $competencies = $query->orderBy('category_id')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('dashboards/Param/Competencies/Index', [
            'competencies' => $competencies,
            'categories' => CompetencyCategory::active()
                ->ordered()
                ->get(),
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    /**
     * ➕ Formulaire de création
     */
    public function create()
    {
        return Inertia::render('dashboards/Param/Competencies/Create', [
            'categories' => CompetencyCategory::active()
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * ✅ Enregistrer une compétence
     */
    public function store(Request $request)
    {
        Log::info('✅ Création compétence...');

        $validated = $request->validate([
            'category_id' => 'required|exists:competency_categories,id',
            'code' => 'required|string|unique:competencies,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_required' => 'nullable|integer|between:1,5',
            'status' => 'required|in:active,inactive',
        ]);

        Competency::create($validated);

        Log::info('✅ Compétence créée:', ['code' => $validated['code']]);

        return redirect()->route('param.projects.competencies.index')
            ->with('success', 'Compétence créée avec succès');
    }

    /**
     * 👁️ Afficher une compétence
     */
    public function show(Competency $competency)
    {
        return Inertia::render('dashboards/Param/Competencies/Show', [
            'competency' => $competency->load('category'),
        ]);
    }

    /**
     * ✏️ Formulaire d'édition
     */
    public function edit(Competency $competency)
    {
        return Inertia::render('dashboards/Param/Competencies/Edit', [
            'competency' => $competency->load('category'),
            'categories' => CompetencyCategory::active()
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * 💾 Mettre à jour
     */
    public function update(Request $request, Competency $competency)
    {
        Log::info('💾 Mise à jour compétence:', ['id' => $competency->id]);

        $validated = $request->validate([
            'category_id' => 'required|exists:competency_categories,id',
            'code' => 'required|string|unique:competencies,code,' . $competency->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_required' => 'nullable|integer|between:1,5',
            'status' => 'required|in:active,inactive',
        ]);

        $competency->update($validated);

        Log::info('✅ Compétence mise à jour');

        return redirect()->route('param.projects.competencies.index')
            ->with('success', 'Compétence mise à jour avec succès');
    }

    /**
     * 🗑️ Supprimer
     */
    public function destroy(Competency $competency)
    {
        Log::info('🗑️ Suppression compétence:', ['id' => $competency->id]);

        if ($competency->auditors()->exists()) {
            return back()->withErrors([
                'error' => 'Cette compétence est assignée à des auditeurs. Supprimez les assignations d\'abord.'
            ]);
        }

        $competency->delete();

        Log::info('✅ Compétence supprimée');

        return redirect()->route('param.projects.competencies.index')
            ->with('success', 'Compétence supprimée avec succès');
    }
}