<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatrixConfigRequest;
use App\Http\Requests\UpdateMatrixConfigRequest;
use App\Models\RiskCriticalityZone;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiskMatrixConfigController extends Controller
{
    /**
     * Liste toutes les configurations de matrice du tenant.
     */
    public function index(Request $request): Response
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        $configs = RiskMatrixConfig::forTenant($tenantId)
            ->withCount(['impactLevels', 'frequencyLevels', 'criticalityZones'])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($c) => [
                'id'                      => $c->id,
                'name'                    => $c->name,
                'matrix_size'             => $c->matrix_size,
                'matrix_label'            => $c->matrix_label,
                'description'             => $c->description,
                'is_active'               => $c->is_active,
                'max_score'               => $c->max_score,
                'impact_levels_count'     => $c->impact_levels_count,
                'frequency_levels_count'  => $c->frequency_levels_count,
                'criticality_zones_count' => $c->criticality_zones_count,
                'is_complete'             => $c->impact_levels_count   >= $c->matrix_size
                    && $c->frequency_levels_count >= $c->matrix_size
                    && $c->criticality_zones_count > 0,
                'created_at'              => $c->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('dashboards/Risk/Matrix/Matrixconfig', [
            'configs' => $configs,
        ]);
    }

    /**
     * Crée une nouvelle configuration + zones par défaut.
     */
    public function store(StoreMatrixConfigRequest $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

        $config = RiskMatrixConfig::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
            'is_active' => false,
        ]);

        foreach (RiskCriticalityZone::defaultZonesForSize($config->matrix_size) as $zone) {
            $config->criticalityZones()->create([...$zone, 'tenant_id' => $tenantId]);
        }

        return back()->with('success',
            "Configuration « {$config->name} » créée avec {$config->matrix_size}×{$config->matrix_size}."
        );
    }

    /**
     * Mise à jour — nom et description uniquement (taille immuable).
     * Reçoit l'ID en entier pour éviter le route model binding prématuré.
     */
    public function update(UpdateMatrixConfigRequest $request, int $matrix_config): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $config   = $this->findForTenant($matrix_config, $tenantId);

        $config->update($request->validated());

        return back()->with('success', "Configuration « {$config->name} » mise à jour.");
    }

    /**
     * Active cette configuration (désactive toutes les autres du tenant).
     */
    public function activate(Request $request, int $matrix_config): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $config   = $this->findForTenant($matrix_config, $tenantId);

        $impactCount    = $config->impactLevels()->count();
        $frequencyCount = $config->frequencyLevels()->count();

        if ($impactCount < $config->matrix_size || $frequencyCount < $config->matrix_size) {
            return back()->withErrors([
                'activation' =>
                    "La configuration « {$config->name} » n'est pas complète. " .
                    "Définissez {$config->matrix_size} niveaux d'impact " .
                    "et {$config->matrix_size} niveaux de fréquence avant de l'activer.",
            ]);
        }

        $config->activate();

        return back()->with('success',
            "Configuration « {$config->name} » ({$config->matrix_label}) activée."
        );
    }

    /**
     * Suppression (soft delete) — impossible si la config est active.
     */
    public function destroy(Request $request, int $matrix_config): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $config   = $this->findForTenant($matrix_config, $tenantId);

        if ($config->is_active) {
            return back()->withErrors([
                'delete' => "Impossible de supprimer la configuration active. Activez d'abord une autre.",
            ]);
        }

        $name = $config->name;
        $config->delete();

        return back()->with('success', "Configuration « {$name} » supprimée.");
    }

    /**
     * Réinitialise les zones de criticité aux valeurs par défaut.
     */
    public function resetZones(Request $request, int $matrix_config): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $config   = $this->findForTenant($matrix_config, $tenantId);

        $config->criticalityZones()->delete();

        foreach (RiskCriticalityZone::defaultZonesForSize($config->matrix_size) as $zone) {
            $config->criticalityZones()->create([...$zone, 'tenant_id' => $tenantId]);
        }

        return back()->with('success',
            "Zones réinitialisées aux valeurs par défaut ({$config->matrix_label})."
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Recherche manuelle — évite le route model binding implicite qui
     * tente de requêter la connexion tenant AVANT que le middleware l'ait
     * configurée, provoquant "No database selected".
     */
    private function findForTenant(int $id, int $tenantId): RiskMatrixConfig
    {
        $config = RiskMatrixConfig::forTenant($tenantId)->findOrFail($id);

        abort_if($config->tenant_id !== $tenantId, 403, 'Accès non autorisé.');

        return $config;
    }
}
