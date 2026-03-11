<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\Param\Factor;
use App\Models\Audit\Param\FactorScale;
use App\Services\FactorAIService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactorInputController extends Controller
{
    protected $aiService;

    public function __construct(FactorAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // INDEX
    // ═════════════════════════════════════════════════════════════════════════════
    public function index()
    {
        $factors = Factor::orderBy('order_position', 'asc')->get();
        $scales = FactorScale::orderBy('value', 'asc')->get();

        return Inertia::render('dashboards/Audit/FactorInput', [
            'factors' => $factors,
            'scales' => $scales,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // CRÉER FACTEUR
    // ═════════════════════════════════════════════════════════════════════════════
    public function storeFactor(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean'
        ]);

        $maxPos = Factor::max('order_position') ?? 0;
        $validated['order_position'] = $maxPos + 1;
        $validated['weight'] = $validated['weight'] ?? 25;

        Factor::create($validated);

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Facteur créé avec succès');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // METTRE À JOUR FACTEUR
    // ═════════════════════════════════════════════════════════════════════════════
    public function updateFactor(Request $request, $id)
    {
        $factor = Factor::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean'
        ]);

        $factor->update($validated);

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Facteur mis à jour');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // SUPPRIMER FACTEUR
    // ═════════════════════════════════════════════════════════════════════════════
    public function destroyFactor($id)
    {
        Factor::findOrFail($id)->delete();

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Facteur supprimé');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // TOGGLE STATUT
    // ═════════════════════════════════════════════════════════════════════════════
    public function toggleFactor($id)
    {
        $factor = Factor::findOrFail($id);
        $factor->is_active = !$factor->is_active;
        $factor->save();

        return redirect()->route('audit.core.audit.factors.index');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // RÉORGANISER (FLÈCHES ↑ ↓)
    // ═════════════════════════════════════════════════════════════════════════════
    public function reorderFactors(Request $request)
    {
        $orders = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.position' => 'required|integer'
        ]);

        try {
            DB::beginTransaction();

            // Étape 1: Positions temporaires négatives
            $tempPosition = -1;
            foreach ($orders['orders'] as $item) {
                DB::table('audit_factors')
                    ->where('id', $item['id'])
                    ->update(['order_position' => $tempPosition]);
                $tempPosition--;
            }

            // Étape 2: Positions finales
            foreach ($orders['orders'] as $item) {
                DB::table('audit_factors')
                    ->where('id', $item['id'])
                    ->update(['order_position' => $item['position']]);
            }

            DB::commit();

            return redirect()->route('audit.core.audit.factors.index')
                ->with('success', '✅ Ordre modifié');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reorder error: ' . $e->getMessage());
            return redirect()->back()->with('error', '❌ Erreur: ' . $e->getMessage());
        }
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // AMÉLIORER NOM (IA)
    // ═════════════════════════════════════════════════════════════════════════════
    public function improveFactorName($id)
    {
        $factor = Factor::findOrFail($id);

        $result = $this->aiService->improveName($factor->label, $factor->description);

        if ($result['success']) {
            $factor->update([
                'label' => $result['label'],
                'description' => $result['description']
            ]);
        }

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Nom amélioré par IA');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // CRÉER ÉCHELLE (AVEC COULEUR)
    // ═════════════════════════════════════════════════════════════════════════════
    public function storeScale(Request $request)
    {
        $validated = $request->validate([
            'value' => 'required|integer|min:1|max:5|unique:audit_factor_scales,value',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/' // Validation HEX
        ]);

        FactorScale::create($validated);

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Échelle créée');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // METTRE À JOUR ÉCHELLE (AVEC COULEUR)
    // ═════════════════════════════════════════════════════════════════════════════
    public function updateScale(Request $request, $id)
    {
        $scale = FactorScale::findOrFail($id);

        $validated = $request->validate([
            'value' => 'required|integer|min:1|max:5|unique:audit_factor_scales,value,' . $id,
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
        ]);

        $scale->update($validated);

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Échelle mise à jour');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // SUPPRIMER ÉCHELLE
    // ═════════════════════════════════════════════════════════════════════════════
    public function destroyScale($id)
    {
        FactorScale::findOrFail($id)->delete();

        return redirect()->route('audit.core.audit.factors.index')
            ->with('success', '✅ Échelle supprimée');
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // EXPORTER CSV
    // ═════════════════════════════════════════════════════════════════════════════
    public function exportFactors(Request $request)
    {
        $factors = Factor::orderBy('order_position', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=facteurs.csv'
        ];

        $callback = function () use ($factors) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Position', 'Libellé', 'Description', 'Poids', 'Statut']);

            foreach ($factors as $f) {
                fputcsv($file, [
                    $f->order_position,
                    $f->label,
                    $f->description ?? '',
                    $f->weight ?? 25,
                    $f->is_active ? 'Actif' : 'Inactif'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}