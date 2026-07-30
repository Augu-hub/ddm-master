<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RiskMatrixConfigController extends Controller
{
    private function tenantId(): int
    {
        return (int) (session('tenant_id') ?? 4);
    }

    public function index(Request $request)
    {
        $tid = $this->tenantId();

        $configs = RiskMatrixConfig::forTenant($tid)
            ->withCount(['impactLevels', 'frequencyLevels', 'criticalityZones'])
            ->with([
                'impactLevels' => fn($q) => $q->ordered(),
                'frequencyLevels' => fn($q) => $q->ordered(),
                'criticalityZones' => fn($q) => $q->ordered(),
                'masteryLevels' => fn($q) => $q->ordered(),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'matrix_size' => $c->matrix_size,
                'matrix_label' => $c->matrix_label,
                'description' => $c->description,
                'is_active' => $c->is_active,
                'max_score' => $c->max_score,
                'impact_levels_count' => $c->impact_levels_count,
                'frequency_levels_count' => $c->frequency_levels_count,
                'criticality_zones_count' => $c->criticality_zones_count,
                'is_complete' => $c->impact_levels_count >= $c->matrix_size 
                    && $c->frequency_levels_count >= $c->matrix_size 
                    && $c->criticality_zones_count > 0,
                'created_at' => $c->created_at?->format('d/m/Y'),
                'impactLevels' => $c->impactLevels,
                'frequencyLevels' => $c->frequencyLevels,
                'criticalityZones' => $c->criticalityZones,
                'masteryLevels' => $c->masteryLevels,
            ]);

        return Inertia::render('dashboards/Risk/MatrixConfig/Index', [
            'configs' => $configs,
        ]);
    }

    public function store(Request $request)
    {
        $tid = $this->tenantId();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'matrix_size' => 'required|in:3,4,5',
            'description' => 'nullable|string',
        ]);

        $exists = RiskMatrixConfig::forTenant($tid)->where('name', $validated['name'])->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'Une configuration avec ce nom existe déjà.']);
        }

        $config = RiskMatrixConfig::create([
            'tenant_id' => $tid,
            'name' => $validated['name'],
            'matrix_size' => $validated['matrix_size'],
            'description' => $validated['description'],
            'is_active' => false,
        ]);

        return redirect()->back()->with('success', 'Configuration créée avec succès.');
    }

    public function update(Request $request, int $id)
    {
        $tid = $this->tenantId();
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $config = RiskMatrixConfig::forTenant($tid)->findOrFail($id);
        $config->update($validated);

        return redirect()->back()->with('success', 'Configuration mise à jour.');
    }

    public function destroy(int $id)
    {
        $tid = $this->tenantId();
        $config = RiskMatrixConfig::forTenant($tid)->findOrFail($id);

        if ($config->is_active) {
            return redirect()->back()->withErrors([
                'delete' => 'Impossible de supprimer la configuration active. Activez-en une autre d\'abord.'
            ]);
        }

        $config->delete();

        return redirect()->back()->with('success', 'Configuration supprimée.');
    }

    public function activate(int $id)
    {
        $tid = $this->tenantId();
        $config = RiskMatrixConfig::forTenant($tid)->findOrFail($id);
        $config->activate();

        return redirect()->back()->with('success', 'Configuration activée.');
    }

    public function storeZone(Request $request, int $configId)
    {
        $tid = $this->tenantId();
        
        $validated = $request->validate([
            'impact_score' => 'required|integer|min:1|max:10',
            'frequency_score' => 'required|integer|min:1|max:10',
            'label' => 'required|string|max:100',
            'color_code' => 'required|string|max:7',
            'description' => 'nullable|string',
        ]);
        
        $score = $validated['impact_score'] * $validated['frequency_score'];
        
        $existing = DB::table('risk_criticality_zones')
            ->where('matrix_config_id', $configId)
            ->where('impact_score', $validated['impact_score'])
            ->where('frequency_score', $validated['frequency_score'])
            ->first();
        
        if ($existing) {
            DB::table('risk_criticality_zones')
                ->where('id', $existing->id)
                ->update([
                    'label' => $validated['label'],
                    'color_code' => $validated['color_code'],
                    'description' => $validated['description'] ?? null,
                    'updated_at' => now(),
                ]);
            $zoneId = $existing->id;
        } else {
            $zoneId = DB::table('risk_criticality_zones')->insertGetId([
                'tenant_id' => $tid,
                'matrix_config_id' => $configId,
                'impact_score' => $validated['impact_score'],
                'frequency_score' => $validated['frequency_score'],
                'score' => $score,
                'label' => $validated['label'],
                'color_code' => $validated['color_code'],
                'description' => $validated['description'] ?? null,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        return response()->json(['success' => true, 'id' => $zoneId]);
    }

    public function updateZone(Request $request, int $id)
    {
        $tid = $this->tenantId();
        
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'color_code' => 'required|string|max:7',
            'description' => 'nullable|string',
        ]);

        DB::table('risk_criticality_zones')
            ->where('id', $id)
            ->where('tenant_id', $tid)
            ->update([
                'label' => $validated['label'],
                'color_code' => $validated['color_code'],
                'description' => $validated['description'],
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }
}