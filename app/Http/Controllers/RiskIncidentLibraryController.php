<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiskIncident;
use Inertia\Inertia;
use Inertia\Response;

class RiskIncidentLibraryController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $tenantId = session('tenant_id') ?? 1;

        $incidents = RiskIncident::on('tenant')
            ->tenant($tenantId)
            ->bibliotheque()
            ->orderBy('moved_to_library_at', 'desc')
            ->get()
            ->map(fn ($i) => $this->formatIncident($i));

        return Inertia::render('dashboards/Risk/IncidentLibrary/Index', [
            'incidents' => $incidents,
            'stats'     => $this->getStats($tenantId),
        ]);
    }

    // ── Reactivate ────────────────────────────────────────────────────────────

    public function reactivate(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        $incident = $this->findBibliotheque($tenantId, $id);

        $incident->update([
            'statut'              => 'actif',
            'moved_to_library_at' => null,
        ]);

        return back()->with('success', "L'incident {$incident->code_incident} a été réactivé.");
    }

    // ── Convert to risk ───────────────────────────────────────────────────────

    public function convertToRisk(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        $incident = $this->findBibliotheque($tenantId, $id);

        $incident->update([
            'statut'       => 'converti',
            'converted_at' => now(),
        ]);

        // Payload pré-remplissage pour le futur module risques
        session(['risk_from_incident' => [
            'incident_id'          => $incident->id,
            'code_origine'         => $incident->code_incident,
            'libelle'              => $incident->libelle,
            'description'          => $incident->description,
            'evaluation_monetaire' => $incident->evaluation_monetaire,
            'devise'               => $incident->devise,
        ]]);

        // TODO Phase 2 : return redirect()->route('risk.core.risks.create');
        return back()->with('success', "L'incident {$incident->code_incident} est marqué comme converti.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findBibliotheque(int $tenantId, int $id): RiskIncident
    {
        return RiskIncident::on('tenant')
            ->tenant($tenantId)
            ->bibliotheque()
            ->where('id', $id)
            ->firstOrFail();
    }

    private function getStats(int $tenantId): array
    {
        $base = RiskIncident::on('tenant')->tenant($tenantId);

        return [
            'total_actifs'       => (clone $base)->actif()->count(),
            'total_bibliotheque' => (clone $base)->bibliotheque()->count(),
            'total_convertis'    => (clone $base)->converti()->count(),
            'evaluation_totale'  => (clone $base)->bibliotheque()
                ->whereNotNull('evaluation_monetaire')
                ->sum('evaluation_monetaire'),
        ];
    }

    private function formatIncident(RiskIncident $incident): array
    {
        return [
            'id'                   => $incident->id,
            'code_incident'        => $incident->code_incident,
            'libelle'              => $incident->libelle,
            'description'          => $incident->description,
            'evaluation_monetaire' => $incident->evaluation_monetaire,
            'devise'               => $incident->devise,
            'evaluation_formatee'  => $incident->evaluation_formatee,
            'date_incident'        => $incident->date_incident?->format('Y-m-d'),
            'date_incident_fr'     => $incident->date_incident?->format('d/m/Y'),
            'source'               => $incident->source,
            'statut'               => $incident->statut,
            'moved_to_library_at'  => $incident->moved_to_library_at?->format('d/m/Y'),
            'created_at'           => $incident->created_at?->format('d/m/Y'),
        ];
    }
}
