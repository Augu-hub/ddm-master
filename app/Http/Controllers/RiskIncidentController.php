<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiskIncident;
use Inertia\Inertia;
use Inertia\Response;

class RiskIncidentController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $tenantId = session('tenant_id') ?? 1;

        $incidents = RiskIncident::on('tenant')
            ->tenant($tenantId)
            ->actif()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($i) => $this->formatIncident($i));

        return Inertia::render('dashboards/Risk/Incident/Index', [
            'incidents' => $incidents,
            'devises'   => $this->getDevises(),
            'stats'     => $this->getStats($tenantId),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $tenantId = session('tenant_id') ?? 1;

        $validated = $request->validate($this->rules());

        RiskIncident::on('tenant')->create([
            'tenant_id'            => $tenantId,
            'code_incident'        => RiskIncident::generateCode($tenantId),
            'libelle'              => $validated['libelle'],
            'description'          => $validated['description'] ?? null,
            'evaluation_monetaire' => $validated['evaluation_monetaire'] ?? null,
            'devise'               => $validated['devise'] ?? 'XOF',
            'date_incident'        => $validated['date_incident'] ?? null,
            'source'               => $validated['source'] ?? null,
            'statut'               => 'actif',
            'created_by'           => auth()->id(),
        ]);

        return back()->with('success', 'Incident créé avec succès.');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        $incident = $this->findActif($tenantId, $id);

        $validated = $request->validate($this->rules());

        $incident->update([
            'libelle'              => $validated['libelle'],
            'description'          => $validated['description'] ?? null,
            'evaluation_monetaire' => $validated['evaluation_monetaire'] ?? null,
            'devise'               => $validated['devise'] ?? $incident->devise,
            'date_incident'        => $validated['date_incident'] ?? null,
            'source'               => $validated['source'] ?? null,
        ]);

        return back()->with('success', 'Incident mis à jour.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        RiskIncident::on('tenant')
            ->tenant($tenantId)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Incident supprimé.');
    }

    // ── Move to library ───────────────────────────────────────────────────────

    public function moveToLibrary(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        $incident = $this->findActif($tenantId, $id);

        $incident->update([
            'statut'              => 'bibliotheque',
            'moved_to_library_at' => now(),
        ]);

        return back()->with('success', "L'incident {$incident->code_incident} a été déplacé vers la bibliothèque.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findActif(int $tenantId, int $id): RiskIncident
    {
        return RiskIncident::on('tenant')
            ->tenant($tenantId)
            ->actif()
            ->where('id', $id)
            ->firstOrFail();
    }

    private function rules(): array
    {
        return [
            'libelle'              => 'required|string|max:255',
            'description'          => 'nullable|string',
            'evaluation_monetaire' => 'nullable|numeric|min:0',
            'devise'               => 'nullable|string|max:10',
            'date_incident'        => 'nullable|date',
            'source'               => 'nullable|string|max:255',
        ];
    }

    private function getStats(int $tenantId): array
    {
        $base = RiskIncident::on('tenant')->tenant($tenantId);

        return [
            'total_actifs'       => (clone $base)->actif()->count(),
            'total_bibliotheque' => (clone $base)->bibliotheque()->count(),
            'total_convertis'    => (clone $base)->converti()->count(),
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
            'source'               => $incident->source,
            'statut'               => $incident->statut,
            'statut_label'         => $incident->statut_label,
            'statut_badge'         => $incident->statut_badge,
            'created_at'           => $incident->created_at?->format('d/m/Y'),
        ];
    }

    private function getDevises(): array
    {
        return [
            ['code' => 'XOF', 'label' => 'Franc CFA (XOF)'],
            ['code' => 'EUR', 'label' => 'Euro (EUR)'],
            ['code' => 'USD', 'label' => 'Dollar US (USD)'],
            ['code' => 'GBP', 'label' => 'Livre sterling (GBP)'],
        ];
    }
}
