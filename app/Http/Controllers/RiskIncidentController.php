<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiskIncident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RiskIncidentController extends Controller
{
    private function tenantId(): int
    {
        return (int) (session('tenant_id') ?? 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // INDEX — liste des incidents actifs
    // ═══════════════════════════════════════════════════════════════════════
    public function index(): Response
    {
        $tid = $this->tenantId();

        // Actifs ET bibliothèque — la Vue différencie par couleur
        $incidents = RiskIncident::on('tenant')
            ->tenant($tid)
            ->whereIn('statut', ['actif', 'bibliotheque'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($i) => $this->formatIncident($i));

        // Liens de dénonciation existants pour cet tenant
        $reporterLinks = DB::table('risk_incident_reporter_links')
            ->where('tenant_id', $tid)
            ->where('is_active', 1)
            ->leftJoin('entities', 'risk_incident_reporter_links.entity_id', '=', 'entities.id')
            ->select('risk_incident_reporter_links.*', 'entities.name as entity_name')
            ->get()
            ->map(fn ($l) => (array) $l);

        return Inertia::render('dashboards/Risk/Incident/Index', [
            'incidents'     => $incidents,
            'devises'       => $this->getDevises(),
            'stats'         => $this->getStats($tid),
            'processes'     => $this->getProcesses(),
            'activities'    => $this->getActivities(),
            'entities'      => $this->getEntities(),
            'reporterLinks' => $reporterLinks,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STORE
    // ═══════════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $tid = $this->tenantId();
        $v   = $request->validate($this->rules());

        RiskIncident::on('tenant')->create([
            'tenant_id'            => $tid,
            'entity_id'            => $v['entity_id']            ?? null,
            'process_id'           => $v['process_id']           ?? null,
            'activity_id'          => $v['activity_id']          ?? null,
            'code_incident'        => RiskIncident::generateCode($tid),
            'libelle'              => $v['libelle'],
            'description'          => $v['description']          ?? null,
            'evaluation_monetaire' => $v['evaluation_monetaire'] ?? null,
            'devise'               => $v['devise']               ?? 'XOF',
            'date_incident'        => $v['date_incident']        ?? null,
            'source'               => $v['source']               ?? null,
            'statut'               => 'actif',
            'created_by'           => auth()->id(),
        ]);

        return back()->with('success', 'Incident créé avec succès.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE
    // ═══════════════════════════════════════════════════════════════════════
    public function update(Request $request, int $id)
    {
        $tid      = $this->tenantId();
        $incident = $this->findActif($tid, $id);
        $v        = $request->validate($this->rules());

        $incident->update([
            'entity_id'            => $v['entity_id']            ?? null,
            'process_id'           => $v['process_id']           ?? null,
            'activity_id'          => $v['activity_id']          ?? null,
            'libelle'              => $v['libelle'],
            'description'          => $v['description']          ?? null,
            'evaluation_monetaire' => $v['evaluation_monetaire'] ?? null,
            'devise'               => $v['devise']               ?? $incident->devise,
            'date_incident'        => $v['date_incident']        ?? null,
            'source'               => $v['source']               ?? null,
        ]);

        return back()->with('success', 'Incident mis à jour.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DESTROY
    // ═══════════════════════════════════════════════════════════════════════
    public function destroy(int $id)
    {
        $tid = $this->tenantId();

        RiskIncident::on('tenant')
            ->tenant($tid)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Incident supprimé.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MOVE TO LIBRARY
    // ═══════════════════════════════════════════════════════════════════════
    public function moveToLibrary(int $id)
    {
        $tid      = $this->tenantId();
        $incident = $this->findActif($tid, $id);

        $incident->update([
            'statut'              => 'bibliotheque',
            'moved_to_library_at' => now(),
        ]);

        return back()->with('success', "L'incident {$incident->code_incident} a été déplacé vers la bibliothèque.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GENERATE REPORTER LINK — génère un lien public de dénonciation
    // POST /incidents/reporter-link
    // ═══════════════════════════════════════════════════════════════════════
    public function generateReporterLink(Request $request)
    {
        $tid = $this->tenantId();

        $v = $request->validate([
            'entity_id' => 'required|integer',
            'label'     => 'nullable|string|max:150',
            'expires_at'=> 'nullable|date|after:today',
        ]);

        // Désactiver l'ancien lien pour cette entité si existant
        DB::table('risk_incident_reporter_links')
            ->where('tenant_id', $tid)
            ->where('entity_id', $v['entity_id'])
            ->update(['is_active' => 0, 'updated_at' => now()]);

        $token = Str::uuid()->toString();

        DB::table('risk_incident_reporter_links')->insert([
            'tenant_id'  => $tid,
            'entity_id'  => $v['entity_id'],
            'token'      => $token,
            'label'      => $v['label'] ?? null,
            'is_active'  => 1,
            'expires_at' => $v['expires_at'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Lien de dénonciation généré.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC REPORTER — page publique de dénonciation (hors auth)
    // GET /report/{token}
    // ═══════════════════════════════════════════════════════════════════════
    public function reporterPage(string $token)
    {
        $link = DB::table('risk_incident_reporter_links')
            ->where('token', $token)
            ->where('is_active', 1)
            ->first();

        abort_if(!$link, 404, 'Lien invalide ou expiré.');

        if ($link->expires_at && now()->isAfter($link->expires_at)) {
            abort(410, 'Ce lien de dénonciation a expiré.');
        }

        $entity = DB::table('entities')->where('id', $link->entity_id)->first();

        return Inertia::render('dashboards/Risk/Incident/PublicReporter', [
            'token'       => $token,
            'entityName'  => $entity?->name ?? 'Entité',
            'label'       => $link->label,
            // submitUrl : URL complète incluant le préfixe m/risk.core
            'submitUrl'   => url('m/risk.core/report/' . $token),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STORE PUBLIC REPORT — soumission depuis la page publique
    // POST /report/{token}
    // ═══════════════════════════════════════════════════════════════════════
    public function storePublicReport(Request $request, string $token)
    {
        $link = DB::table('risk_incident_reporter_links')
            ->where('token', $token)
            ->where('is_active', 1)
            ->first();

        abort_if(!$link, 404);

        if ($link->expires_at && now()->isAfter($link->expires_at)) {
            abort(410, 'Lien expiré.');
        }

        $v = $request->validate([
            'libelle'              => 'required|string|max:255',
            'description'          => 'nullable|string',
            'processus_libelle'    => 'nullable|string|max:255',  // saisie libre
            'activite_libelle'     => 'nullable|string|max:255',  // saisie libre
            'date_incident'        => 'nullable|date',
            'evaluation_monetaire' => 'nullable|numeric|min:0',
            'devise'               => 'nullable|string|max:10',
            'reporter_name'        => 'nullable|string|max:255',
            'reporter_email'       => 'nullable|email|max:255',
        ]);

        $code = 'INC-EXT-' . strtoupper(Str::random(8));

        // Construire la description enrichie avec processus/activité saisis librement
        $descriptionComplete = $v['description'] ?? '';
        if (!empty($v['processus_libelle'])) {
            $descriptionComplete .= "\n\nProcessus : " . $v['processus_libelle'];
        }
        if (!empty($v['activite_libelle'])) {
            $descriptionComplete .= "\nActivité : " . $v['activite_libelle'];
        }

        DB::table('risk_incidents')->insert([
            'tenant_id'            => $link->tenant_id,
            'entity_id'            => $link->entity_id,
            'code_incident'        => $code,
            'libelle'              => $v['libelle'],
            'description'          => trim($descriptionComplete) ?: null,
            'evaluation_monetaire' => $v['evaluation_monetaire'] ?? null,
            'devise'               => $v['devise']               ?? 'XOF',
            'date_incident'        => $v['date_incident']        ?? null,
            'source'               => 'Dénonciation externe'
                . (!empty($v['processus_libelle']) ? ' — ' . $v['processus_libelle'] : ''),
            'reporter_token'       => $token,
            'reporter_name'        => $v['reporter_name']        ?? null,
            'reporter_email'       => $v['reporter_email']       ?? null,
            'is_external'          => 1,
            'statut'               => 'actif',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Retourne JSON car la page publique utilise fetch() natif
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['code' => $code, 'message' => "Signalement enregistré."]);
        }
        return back()->with([
            'success' => "Votre signalement a bien été enregistré. Merci.",
            'code'    => $code,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE
    // ═══════════════════════════════════════════════════════════════════════

    private function findActif(int $tid, int $id): RiskIncident
    {
        return RiskIncident::on('tenant')->tenant($tid)->actif()->where('id', $id)->firstOrFail();
    }

    private function rules(): array
    {
        return [
            'libelle'              => 'required|string|max:255',
            'description'          => 'nullable|string',
            'entity_id'            => 'nullable|integer',
            'process_id'           => 'nullable|integer',
            'activity_id'          => 'nullable|integer',
            'evaluation_monetaire' => 'nullable|numeric|min:0',
            'devise'               => 'nullable|string|max:10',
            'date_incident'        => 'nullable|date',
            'source'               => 'nullable|string|max:255',
        ];
    }

    private function formatIncident(RiskIncident $incident): array
    {
        return [
            'id'                   => $incident->id,
            'code_incident'        => $incident->code_incident,
            'libelle'              => $incident->libelle,
            'description'          => $incident->description,
            'entity_id'            => $incident->entity_id,
            'process_id'           => $incident->process_id,
            'activity_id'          => $incident->activity_id,
            'evaluation_monetaire' => $incident->evaluation_monetaire,
            'devise'               => $incident->devise,
            'evaluation_formatee'  => $incident->evaluation_formatee,
            'date_incident'        => $incident->date_incident?->format('Y-m-d'),
            'source'               => $incident->source,
            'is_external'          => (bool) ($incident->is_external ?? false),
            'statut'               => $incident->statut,
            'statut_label'         => $incident->statut_label,
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

    private function getProcesses(): array
    {
        return DB::table('processes')->orderBy('code')->get(['id', 'code', 'name'])->map(fn ($p) => (array) $p)->toArray();
    }

    private function getActivities(): array
    {
        return DB::table('activities')->orderBy('code')->get(['id', 'code', 'name', 'process_id'])->map(fn ($a) => (array) $a)->toArray();
    }

    private function getEntities(): array
    {
        return DB::table('entities')->orderBy('name')->get(['id', 'name', 'level'])->map(fn ($e) => (array) $e)->toArray();
    }

    private function getStats(int $tid): array
    {
        $base = RiskIncident::on('tenant')->tenant($tid);
        return [
            'total_actifs'       => (clone $base)->actif()->count(),
            'total_bibliotheque' => (clone $base)->bibliotheque()->count(),
            'total_convertis'    => (clone $base)->converti()->count(),
        ];
    }
}

/* ============================================================
   NOTE — Modèle RiskIncident
   Vérifiez que ces scopes existent dans App\Models\RiskIncident :

   public function scopeActif($query)
   {
       return $query->where('statut', 'actif');
   }

   public function scopeBibliotheque($query)
   {
       return $query->where('statut', 'bibliotheque');
   }

   public function scopeConverti($query)
   {
       return $query->where('statut', 'converti');
   }

   public function scopeTenant($query, $tenantId)
   {
       return $query->where('tenant_id', $tenantId);
   }

   Si ces scopes manquent, les incidents transférés
   resteront visibles dans la liste.
============================================================ */