<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MissionParametrageController extends Controller
{
    // =========================================================================
    // INDEX — Page principale avec les 4 onglets
    // =========================================================================
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'phases');

        return Inertia::render('dashboards/Audit/MissionProgramming/Parametrage', [
            'activeTab' => $tab,
            'phases'           => $this->getPhases(),
            'roles'            => $this->getRoles(),
            'budgetTypes'      => $this->getBudgetTypes(),
            'budgetCategories' => $this->getBudgetCategories(),
        ]);
    }

    // =========================================================================
    // PHASES DE MISSION (mission_codephases)
    // =========================================================================
    public function storePhase(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:mission_codephases,code',
            'libelle'     => 'required|string|max:100',
            'description' => 'nullable|string',
            'ordre'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        DB::table('mission_codephases')->insert([
            'code'        => strtoupper($request->code),
            'libelle'     => $request->libelle,
            'description' => $request->description,
            'ordre'       => $request->ordre,
            'is_active'   => $request->is_active ?? 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'phases'])
            ->with('success', 'Phase créée avec succès');
    }

    public function updatePhase(Request $request, $id)
    {
        $request->validate([
            'code'        => "required|string|max:20|unique:mission_codephases,code,{$id}",
            'libelle'     => 'required|string|max:100',
            'description' => 'nullable|string',
            'ordre'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        DB::table('mission_codephases')->where('id', $id)->update([
            'code'        => strtoupper($request->code),
            'libelle'     => $request->libelle,
            'description' => $request->description,
            'ordre'       => $request->ordre,
            'is_active'   => $request->is_active ?? 1,
            'updated_at'  => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'phases'])
            ->with('success', 'Phase mise à jour');
    }

    public function destroyPhase($id)
    {
        $used = DB::table('mission_phase_auditeurs')->where('phase_id', $id)->exists();
        if ($used) {
            return back()->withErrors(['error' => 'Cette phase est utilisée dans des missions. Désactivez-la plutôt.']);
        }
        DB::table('mission_codephases')->where('id', $id)->delete();
        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'phases'])
            ->with('success', 'Phase supprimée');
    }

    // =========================================================================
    // RÔLES DE MISSION (mission_roles)
    // =========================================================================
    public function storeRole(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:10|unique:mission_roles,code',
            'libelle'     => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_code' => 'nullable|string|max:10|exists:mission_roles,code',
            'niveau'      => 'required|integer|min:0|max:10',
            'max_enfants' => 'nullable|integer|min:1',
            'ordre'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        DB::table('mission_roles')->insert([
            'code'        => strtoupper($request->code),
            'libelle'     => $request->libelle,
            'description' => $request->description,
            'parent_code' => $request->parent_code ? strtoupper($request->parent_code) : null,
            'niveau'      => $request->niveau,
            'max_enfants' => $request->max_enfants,
            'ordre'       => $request->ordre,
            'is_active'   => $request->is_active ?? 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'roles'])
            ->with('success', 'Rôle créé avec succès');
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'code'        => "required|string|max:10|unique:mission_roles,code,{$id}",
            'libelle'     => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_code' => 'nullable|string|max:10',
            'niveau'      => 'required|integer|min:0|max:10',
            'max_enfants' => 'nullable|integer|min:1',
            'ordre'       => 'required|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        // Empêcher un rôle d'être son propre parent
        $current = DB::table('mission_roles')->where('id', $id)->first();
        if ($request->parent_code && strtoupper($request->parent_code) === $current->code) {
            return back()->withErrors(['parent_code' => 'Un rôle ne peut pas être son propre parent']);
        }

        DB::table('mission_roles')->where('id', $id)->update([
            'code'        => strtoupper($request->code),
            'libelle'     => $request->libelle,
            'description' => $request->description,
            'parent_code' => $request->parent_code ? strtoupper($request->parent_code) : null,
            'niveau'      => $request->niveau,
            'max_enfants' => $request->max_enfants,
            'ordre'       => $request->ordre,
            'is_active'   => $request->is_active ?? 1,
            'updated_at'  => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'roles'])
            ->with('success', 'Rôle mis à jour');
    }

    public function destroyRole($id)
    {
        $role = DB::table('mission_roles')->where('id', $id)->first();
        if (!$role) abort(404);

        // Vérifier si utilisé
        $used = DB::table('mission_phase_auditeurs')->where('role_id', $id)->exists();
        if ($used) {
            return back()->withErrors(['error' => 'Ce rôle est utilisé. Désactivez-le plutôt.']);
        }

        // Vérifier si c'est un parent
        $hasChildren = DB::table('mission_roles')->where('parent_code', $role->code)->exists();
        if ($hasChildren) {
            return back()->withErrors(['error' => 'Ce rôle a des sous-rôles. Supprimez-les d\'abord.']);
        }

        DB::table('mission_roles')->where('id', $id)->delete();
        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'roles'])
            ->with('success', 'Rôle supprimé');
    }

    // =========================================================================
    // TYPES DE BUDGET (budget_types)
    // =========================================================================
    public function storeBudgetType(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:budget_types,code',
            'libelle'     => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        DB::table('budget_types')->insert([
            'code'       => strtoupper($request->code),
            'libelle'    => $request->libelle,
            'description'=> $request->description,
            'is_active'  => $request->is_active ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'budget_types'])
            ->with('success', 'Type de budget créé');
    }

    public function updateBudgetType(Request $request, $id)
    {
        $request->validate([
            'code'        => "required|string|max:20|unique:budget_types,code,{$id}",
            'libelle'     => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        DB::table('budget_types')->where('id', $id)->update([
            'code'       => strtoupper($request->code),
            'libelle'    => $request->libelle,
            'description'=> $request->description,
            'is_active'  => $request->is_active ?? 1,
            'updated_at' => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'budget_types'])
            ->with('success', 'Type de budget mis à jour');
    }

    public function destroyBudgetType($id)
    {
        $used = DB::table('mission_budgets')->where('budget_type_id', $id)->exists();
        if ($used) {
            return back()->withErrors(['error' => 'Ce type est utilisé. Désactivez-le plutôt.']);
        }
        DB::table('budget_types')->where('id', $id)->delete();
        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'budget_types'])
            ->with('success', 'Type de budget supprimé');
    }

    // =========================================================================
    // CATÉGORIES DE BUDGET (mission_budget_categories)
    // =========================================================================
    public function storeBudgetCategory(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:mission_budget_categories,code',
            'libelle'        => 'required|string|max:100',
            'description'    => 'nullable|string',
            'montant_defaut' => 'nullable|numeric|min:0',
            'ordre'          => 'required|integer|min:0',
            'is_active'      => 'boolean',
        ]);

        DB::table('mission_budget_categories')->insert([
            'code'           => strtoupper($request->code),
            'libelle'        => $request->libelle,
            'description'    => $request->description,
            'montant_defaut' => $request->montant_defaut ?? 0,
            'ordre'          => $request->ordre,
            'is_active'      => $request->is_active ?? 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'budget_categories'])
            ->with('success', 'Catégorie créée');
    }

    public function updateBudgetCategory(Request $request, $id)
    {
        $request->validate([
            'code'           => "required|string|max:20|unique:mission_budget_categories,code,{$id}",
            'libelle'        => 'required|string|max:100',
            'description'    => 'nullable|string',
            'montant_defaut' => 'nullable|numeric|min:0',
            'ordre'          => 'required|integer|min:0',
            'is_active'      => 'boolean',
        ]);

        DB::table('mission_budget_categories')->where('id', $id)->update([
            'code'           => strtoupper($request->code),
            'libelle'        => $request->libelle,
            'description'    => $request->description,
            'montant_defaut' => $request->montant_defaut ?? 0,
            'ordre'          => $request->ordre,
            'is_active'      => $request->is_active ?? 1,
            'updated_at'     => now(),
        ]);

        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'budget_categories'])
            ->with('success', 'Catégorie mise à jour');
    }

    public function destroyBudgetCategory($id)
    {
        $used = DB::table('mission_budget_lines')->where('category_id', $id)->exists();
        if ($used) {
            return back()->withErrors(['error' => 'Cette catégorie est utilisée. Désactivez-la plutôt.']);
        }
        DB::table('mission_budget_categories')->where('id', $id)->delete();
        return redirect()->route('audit.core.programmation-missions.parametrage', ['tab' => 'budget_categories'])
            ->with('success', 'Catégorie supprimée');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    private function getPhases()
    {
        return DB::table('mission_codephases')
            ->orderBy('ordre')
            ->get()
            ->map(fn($p) => (array) $p);
    }

    private function getRoles()
    {
        $roles = DB::table('mission_roles')
            ->orderBy('ordre')
            ->get()
            ->map(fn($r) => (array) $r);

        // Ajouter le libellé du parent
        $codesMap = $roles->pluck('libelle', 'code')->toArray();
        return $roles->map(function ($r) use ($codesMap) {
            $r['parent_libelle'] = $r['parent_code'] ? ($codesMap[$r['parent_code']] ?? $r['parent_code']) : null;
            return $r;
        });
    }

    private function getBudgetTypes()
    {
        return DB::table('budget_types')
            ->orderBy('id')
            ->get()
            ->map(fn($b) => (array) $b);
    }

    private function getBudgetCategories()
    {
        return DB::table('mission_budget_categories')
            ->orderBy('ordre')
            ->get()
            ->map(fn($c) => (array) $c);
    }
}