<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdreMissionController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $query = DB::table('ordre_missions as om')
            ->select([
                'om.*',
                DB::raw("DATE_FORMAT(om.date_debut, '%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(om.date_fin,   '%d/%m/%Y') as date_fin_fr"),
                DB::raw("GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ', ') as entites_noms"),
                DB::raw("GROUP_CONCAT(DISTINCT CONCAT(a.last_name,' ',a.first_name,' (',COALESCE(oma.role,'—'),')') ORDER BY oma.ordre SEPARATOR '\n') as auditeurs_liste"),
            ])
            ->leftJoin('ordre_mission_entites as ome', 'om.id', '=', 'ome.om_id')
            ->leftJoin('entities as e', 'ome.entity_id', '=', 'e.id')
            ->leftJoin('ordre_mission_auditeurs as oma', 'om.id', '=', 'oma.om_id')
            ->leftJoin('auditors as a', 'oma.auditeur_id', '=', 'a.id')
            ->whereNull('om.deleted_at')
            ->groupBy('om.id');

        if ($s = trim($request->get('search', ''))) {
            $query->where(fn($q) => $q
                ->where('om.reference_om',   'like', "%{$s}%")
                ->orWhere('om.intitule',     'like', "%{$s}%")
                ->orWhere('om.destinataire', 'like', "%{$s}%")
            );
        }
        if ($st = $request->get('status')) {
            $query->where('om.status', $st);
        }

        $ordres = $query->orderBy('om.created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total'     => DB::table('ordre_missions')->whereNull('deleted_at')->count(),
            'brouillon' => DB::table('ordre_missions')->whereNull('deleted_at')->where('status', 'brouillon')->count(),
            'emis'      => DB::table('ordre_missions')->whereNull('deleted_at')->where('status', 'emis')->count(),
            'envoye'    => DB::table('ordre_missions')->whereNull('deleted_at')->where('status', 'envoye')->count(),
        ];

        return Inertia::render('dashboards/Audit/OrdreMission/Index', [
            'ordres'  => $ordres,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create(Request $request)
    {
        return Inertia::render('dashboards/Audit/OrdreMission/Create', $this->getFormData($request));
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $request->validate([
            'intitule'            => 'required|string|max:255',
            'forme_diffusion'     => 'required|in:electronique,papier,les_deux',
            'entites'             => 'required|array|min:1',
            'entites.*.entity_id' => 'required|exists:entities,id',
        ]);

        DB::beginTransaction();
        try {
            $entitesData = $request->input('entites', []);

            // Dates globales = enveloppe de toutes les entites
            $allDebuts = collect($entitesData)->pluck('date_debut')->filter()->sort()->values();
            $allFins   = collect($entitesData)->pluck('date_fin')->filter()->sort()->values();
            $dateDebut = $allDebuts->first() ?: null;
            $dateFin   = $allFins->last()    ?: null;
            $duree     = ($dateDebut && $dateFin)
                ? (int) ceil(abs(strtotime($dateFin) - strtotime($dateDebut)) / 86400) + 1
                : null;

            $reference = $request->input('reference_om') ?: $this->generateReference();
            $action    = $request->input('_action', 'brouillon');

            $omId = DB::table('ordre_missions')->insertGetId([
                'reference_om'          => $reference,
                'mission_prog_id'       => $request->input('mission_prog_id') ?: null,
                'mission_id'            => $request->input('mission_id')       ?: null,
                'intitule'              => $request->input('intitule'),
                'objectif'              => $request->input('objectif'),
                'lieux'                 => $request->input('lieux'),
                'domaine'               => $request->input('domaine'),
                'limite'                => $request->input('limite'),
                'moyen'                 => $request->input('moyen'),
                'budget'                => floatval($request->input('budget', 0)),
                'date_debut'            => $dateDebut,
                'date_fin'              => $dateFin,
                'duree'                 => $duree,
                'phase'                 => $request->input('phase', 'ORMI'),
                'forme_diffusion'       => $request->input('forme_diffusion'),
                'date_limite_diffusion' => $request->input('date_limite_diffusion') ?: null,
                'emetteur'              => $request->input('emetteur'),
                'destinataire'          => $request->input('destinataire'),
                'copie'                 => $request->input('copie'),
                'message_personnalise'  => $request->input('message_personnalise'),
                'status'                => ($action === 'emettre') ? 'emis' : 'brouillon',
                'created_by'            => Auth::id(),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // ── Entités ──────────────────────────────────────────────────
            foreach ($entitesData as $idx => $ent) {
                $entityId = (int) $ent['entity_id'];
                $entDuree = (!empty($ent['date_debut']) && !empty($ent['date_fin']))
                    ? (int) ceil(abs(strtotime($ent['date_fin']) - strtotime($ent['date_debut'])) / 86400) + 1
                    : null;

                $omEntiteId = DB::table('ordre_mission_entites')->insertGetId([
                    'om_id'         => $omId,
                    'entity_id'     => $entityId,
                    'email_contact' => $ent['email_contact'] ?? null,
                    'nom_contact'   => $ent['nom_contact']   ?? null,
                    'date_debut'    => $ent['date_debut']    ?? null,
                    'date_fin'      => $ent['date_fin']      ?? null,
                    'duree'         => $entDuree,
                    'lieux'         => $ent['lieux']         ?? null,
                    'copie'         => $ent['copie']         ?? null,
                    'destinataire'  => $ent['destinataire']  ?? null,
                    'message'       => $ent['message']       ?? null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // Auditeurs spécifiques à l'entité
                foreach (($ent['auditeurs'] ?? []) as $ai => $aud) {
                    if (empty($aud['auditeur_id'])) continue;
                    DB::table('ordre_mission_auditeurs')->insert([
                        'om_id'        => $omId,
                        'om_entite_id' => $omEntiteId,
                        'entity_id'    => $entityId,
                        'auditeur_id'  => (int) $aud['auditeur_id'],
                        'role'         => $aud['role']         ?? null,
                        'role_libelle' => $aud['role_libelle'] ?? null,
                        'ordre'        => $ai,
                        'scope'        => 'entite',
                        'created_at'   => now(),
                    ]);
                }

                // Documents — clé plate : docs_{entityId}[]
                $filesForEntity = $this->extractFilesForEntity($request, $entityId, $idx);
                foreach ($filesForEntity as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $storagePath = "ordres_mission/{$omId}/entite_{$entityId}/"
                        . Str::random(8) . '_' . $file->getClientOriginalName();
                    Storage::disk('public')->put($storagePath, file_get_contents($file->getRealPath()));

                    DB::table('ordre_mission_documents')->insert([
                        'om_id'        => $omId,
                        'om_entite_id' => $omEntiteId,
                        'entity_id'    => $entityId,
                        'nom_fichier'  => $file->getClientOriginalName(),
                        'chemin'       => $storagePath,
                        'taille'       => $file->getSize(),
                        'mime_type'    => $file->getMimeType(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            // ── Auditeurs globaux ────────────────────────────────────────
            foreach (($request->input('auditeurs', [])) as $ai => $aud) {
                if (empty($aud['auditeur_id'])) continue;
                DB::table('ordre_mission_auditeurs')->insert([
                    'om_id'        => $omId,
                    'om_entite_id' => null,
                    'entity_id'    => null,
                    'auditeur_id'  => (int) $aud['auditeur_id'],
                    'role'         => $aud['role']         ?? null,
                    'role_libelle' => $aud['role_libelle'] ?? null,
                    'ordre'        => $ai,
                    'scope'        => 'global',
                    'created_at'   => now(),
                ]);
            }

            DB::commit();
            return redirect()
                ->route('audit.core.ordre-missions.show', $omId)
                ->with('success', 'Ordre de Mission créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création OM', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(int $id)
    {
        $om = $this->getOMComplet($id);
        if (!$om) abort(404);

        $entites   = $this->getEntitesOM($id);
        $auditeurs = $this->getAuditeursOM($id);
        $documents = $this->getDocumentsOM($id);
        $envois    = DB::table('ordre_mission_envois')
            ->where('om_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Grouper auditeurs par entité
        $auditeursByEntite = [];
        foreach ($auditeurs as $aud) {
            $key = $aud->entity_id ?? 'global';
            $auditeursByEntite[$key][] = $aud;
        }

        // Grouper documents par entité
        $documentsByEntite = [];
        foreach ($documents as $doc) {
            $documentsByEntite[$doc->entity_id ?? 'global'][] = $doc;
        }

        return Inertia::render('dashboards/Audit/OrdreMission/Show', [
            'om'                => $om,
            'entites'           => $entites,
            'auditeurs'         => $auditeurs,
            'auditeursByEntite' => $auditeursByEntite,
            'documents'         => $documents,
            'documentsByEntite' => $documentsByEntite,
            'envois'            => $envois,
        ]);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit(Request $request, int $id)
    {
        $om = $this->getOMComplet($id);
        if (!$om) abort(404);

        $data = $this->getFormData($request);
        $data['om']                    = $om;
        $data['entitesSelectionnees']  = $this->getEntitesOM($id);
        $data['auditeursSelectionnes'] = $this->getAuditeursOM($id);
        $data['documentsExistants']    = $this->getDocumentsOM($id);

        return Inertia::render('dashboards/Audit/OrdreMission/Edit', $data);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, int $id)
    {
        $om = DB::table('ordre_missions')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$om) abort(404);

        $request->validate([
            'intitule'                 => 'required|string|max:255',
            'forme_diffusion'          => 'required|in:electronique,papier,les_deux',
            'entites'                  => 'required|array|min:1',
            'entites.*.entity_id'      => 'required|exists:entities,id',
            'entites.*.email_contact'  => 'nullable|email|max:255',
        ]);

        DB::beginTransaction();
        try {
            $entitesData = $request->input('entites', []);
            $allDebuts   = collect($entitesData)->pluck('date_debut')->filter()->sort()->values();
            $allFins     = collect($entitesData)->pluck('date_fin')->filter()->sort()->values();
            $dateDebut   = $allDebuts->first() ?: null;
            $dateFin     = $allFins->last()    ?: null;
            $duree       = ($dateDebut && $dateFin)
                ? (int) ceil(abs(strtotime($dateFin) - strtotime($dateDebut)) / 86400) + 1
                : null;

            DB::table('ordre_missions')->where('id', $id)->update([
                'intitule'              => $request->input('intitule'),
                'objectif'              => $request->input('objectif'),
                'lieux'                 => $request->input('lieux'),
                'domaine'               => $request->input('domaine'),
                'limite'                => $request->input('limite'),
                'moyen'                 => $request->input('moyen'),
                'budget'                => floatval($request->input('budget', 0)),
                'date_debut'            => $dateDebut,
                'date_fin'              => $dateFin,
                'duree'                 => $duree,
                'phase'                 => $request->input('phase', 'ORMI'),
                'forme_diffusion'       => $request->input('forme_diffusion'),
                'date_limite_diffusion' => $request->input('date_limite_diffusion') ?: null,
                'emetteur'              => $request->input('emetteur'),
                'destinataire'          => $request->input('destinataire'),
                'copie'                 => $request->input('copie'),
                'message_personnalise'  => $request->input('message_personnalise'),
                'updated_by'            => Auth::id(),
                'updated_at'            => now(),
            ]);

            // Recréer entités
            DB::table('ordre_mission_entites')->where('om_id', $id)->delete();
            DB::table('ordre_mission_auditeurs')->where('om_id', $id)->delete();

            foreach ($entitesData as $idx => $ent) {
                $entityId = (int) $ent['entity_id'];
                $entDuree = (!empty($ent['date_debut']) && !empty($ent['date_fin']))
                    ? (int) ceil(abs(strtotime($ent['date_fin']) - strtotime($ent['date_debut'])) / 86400) + 1
                    : null;

                $omEntiteId = DB::table('ordre_mission_entites')->insertGetId([
                    'om_id'         => $id,
                    'entity_id'     => $entityId,
                    'email_contact' => $ent['email_contact'] ?? null,
                    'nom_contact'   => $ent['nom_contact']   ?? null,
                    'date_debut'    => $ent['date_debut']    ?? null,
                    'date_fin'      => $ent['date_fin']      ?? null,
                    'duree'         => $entDuree,
                    'lieux'         => $ent['lieux']         ?? null,
                    'copie'         => $ent['copie']         ?? null,
                    'destinataire'  => $ent['destinataire']  ?? null,
                    'message'       => $ent['message']       ?? null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                foreach (($ent['auditeurs'] ?? []) as $ai => $aud) {
                    if (empty($aud['auditeur_id'])) continue;
                    DB::table('ordre_mission_auditeurs')->insert([
                        'om_id'        => $id,
                        'om_entite_id' => $omEntiteId,
                        'entity_id'    => $entityId,
                        'auditeur_id'  => (int) $aud['auditeur_id'],
                        'role'         => $aud['role']         ?? null,
                        'role_libelle' => $aud['role_libelle'] ?? null,
                        'ordre'        => $ai,
                        'scope'        => 'entite',
                        'created_at'   => now(),
                    ]);
                }

                // Nouveaux documents uploadés lors de l'édition
                $filesForEntity = $this->extractFilesForEntity($request, $entityId, $idx);
                foreach ($filesForEntity as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $storagePath = "ordres_mission/{$id}/entite_{$entityId}/"
                        . Str::random(8) . '_' . $file->getClientOriginalName();
                    Storage::disk('public')->put($storagePath, file_get_contents($file->getRealPath()));
                    DB::table('ordre_mission_documents')->insert([
                        'om_id'        => $id,
                        'om_entite_id' => $omEntiteId,
                        'entity_id'    => $entityId,
                        'nom_fichier'  => $file->getClientOriginalName(),
                        'chemin'       => $storagePath,
                        'taille'       => $file->getSize(),
                        'mime_type'    => $file->getMimeType(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            // Auditeurs globaux
            foreach (($request->input('auditeurs', [])) as $ai => $aud) {
                if (empty($aud['auditeur_id'])) continue;
                DB::table('ordre_mission_auditeurs')->insert([
                    'om_id'        => $id,
                    'om_entite_id' => null,
                    'entity_id'    => null,
                    'auditeur_id'  => (int) $aud['auditeur_id'],
                    'role'         => $aud['role']         ?? null,
                    'role_libelle' => $aud['role_libelle'] ?? null,
                    'ordre'        => $ai,
                    'scope'        => 'global',
                    'created_at'   => now(),
                ]);
            }

            DB::commit();
            return redirect()
                ->route('audit.core.ordre-missions.show', $id)
                ->with('success', 'Ordre de Mission mis à jour.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur update OM', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // GENERATE PDF
    // =========================================================================

    public function generatePdf(int $id, ?int $entityId = null)
    {
        $om = $this->getOMComplet($id);
        if (!$om) abort(404);

        $entites   = $this->getEntitesOM($id);
        $auditeurs = $this->getAuditeursOM($id);

        if ($entityId) {
            $entites = $entites->filter(fn($e) => $e->entity_id == $entityId)->values();
        }

        $globaux           = $auditeurs->where('scope', 'global')->values();
        $auditeursByEntite = [];
        foreach ($entites as $ent) {
            $specifiques = $auditeurs->where('scope', 'entite')
                                     ->where('entity_id', $ent->entity_id)
                                     ->values();
            $merged = $specifiques->toBase();
            foreach ($globaux as $g) {
                if (!$merged->contains('auditeur_id', $g->auditeur_id)) {
                    $merged->push($g);
                }
            }
            $auditeursByEntite[$ent->entity_id] = $merged;
        }

        $pdf = Pdf::loadView('pdf.ordre_mission', [
            'om'                => $om,
            'entites'           => $entites,
            'auditeursByEntite' => $auditeursByEntite,
            'auditeurs'         => $globaux,
            'date_fr'           => now()->format('d/m/Y'),
            'lieu'              => $om->lieux ?? 'Cotonou',
        ])->setPaper('A4', 'portrait');

        $filename = "OM_{$om->reference_om}_" . now()->format('Ymd') . ".pdf";
        $pdfPath  = "ordres_mission/{$filename}";
        Storage::disk('public')->put($pdfPath, $pdf->output());

        DB::table('ordre_missions')->where('id', $id)->update([
            'pdf_path'   => $pdfPath,
            'status'     => $om->status === 'brouillon' ? 'emis' : $om->status,
            'updated_at' => now(),
        ]);

        return $pdf->download($filename);
    }

    // =========================================================================
    // SEND EMAILS — Auto-joint PDF + tous documents de chaque entité
    // =========================================================================

    public function sendEmails(Request $request, int $id)
    {
        $request->validate([
            'entity_ids'   => 'nullable|array',
            'entity_ids.*' => 'exists:entities,id',
        ]);

        $om        = $this->getOMComplet($id);
        if (!$om) abort(404);

        $entites   = $this->getEntitesOM($id);
        $auditeurs = $this->getAuditeursOM($id);
        $documents = $this->getDocumentsOM($id);

        // Filtrer les entités cibles
        if ($request->entity_ids) {
            $entites = $entites->filter(fn($e) => in_array($e->entity_id, $request->entity_ids))->values();
        } else {
            $entites = $entites->filter(fn($e) => !empty($e->email_contact))->values();
        }

        if ($entites->isEmpty()) {
            return back()->withErrors(['error' => 'Aucune entité avec un email de contact renseigné.']);
        }

        $globaux = $auditeurs->where('scope', 'global')->values();
        $sent    = 0;
        $errors  = [];

        foreach ($entites as $entite) {

            if (empty($entite->email_contact)) {
                $errors[] = "{$entite->entity_name} : email manquant";
                continue;
            }

            try {
                // ── 1. Fusionner auditeurs : spécifiques entité + globaux ─
                $specifiques = $auditeurs->where('scope', 'entite')
                                         ->where('entity_id', $entite->entity_id)
                                         ->values();
                $audsEntite = $specifiques->toBase();
                foreach ($globaux as $g) {
                    if (!$audsEntite->contains('auditeur_id', $g->auditeur_id)) {
                        $audsEntite->push($g);
                    }
                }

                // ── 2. Documents uploadés pour cette entité ───────────────
                $docsEntite = $documents->where('entity_id', $entite->entity_id)->values();

                // ── 3. Générer le PDF personnalisé pour cette entité ──────
                $pdfContent = Pdf::loadView('pdf.ordre_mission', [
                    'om'                => $om,
                    'entites'           => collect([$entite]),
                    'auditeursByEntite' => [$entite->entity_id => $audsEntite],
                    'auditeurs'         => $audsEntite,
                    'date_fr'           => now()->format('d/m/Y'),
                    'lieu'              => $entite->lieux ?? $om->lieux ?? 'Cotonou',
                ])->setPaper('A4', 'portrait')->output();

                $filenamePdf = "OM_{$om->reference_om}_{$entite->entity_name}.pdf";

                // Sauvegarder le PDF généré pour traçabilité
                $pdfPath = "ordres_mission/{$om->reference_om}/PDF_{$entite->entity_id}_" . now()->format('Ymd') . ".pdf";
                Storage::disk('public')->put($pdfPath, $pdfContent);

                // ── 4. Choisir le message : entité > global > défaut ──────
                $message = !empty($entite->message)
                    ? $entite->message
                    : (!empty($om->message_personnalise)
                        ? $om->message_personnalise
                        : $this->buildDefaultMessage($om, $entite, $audsEntite));

                // ── 5. Construire et envoyer l'email ─────────────────────
                Mail::send([], [], function ($mail) use (
                    $om, $entite, $audsEntite, $docsEntite,
                    $pdfContent, $filenamePdf, $message
                ) {
                    // Destinataire principal
                    $mail->to($entite->email_contact, $entite->nom_contact ?? $entite->entity_name)
                         ->subject("Ordre de Mission {$om->reference_om} — {$om->intitule}");

                    // CC : spécifique entité prioritaire, sinon global
                    $cc = !empty($entite->copie) ? $entite->copie : $om->copie;
                    if ($cc) {
                        foreach (explode(',', $cc) as $addr) {
                            $addr = trim($addr);
                            if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                                $mail->cc($addr);
                            }
                        }
                    }

                    // Corps HTML de l'email
                    $mail->html($this->buildEmailHtml($om, $entite, $audsEntite, $message));

                    // ── PJ 1 : PDF de l'OM (automatique, toujours joint) ─
                    $mail->attachData(
                        $pdfContent,
                        $filenamePdf,
                        ['mime' => 'application/pdf']
                    );

                    // ── PJ 2..N : Documents uploadés pour cette entité ───
                    foreach ($docsEntite as $doc) {
                        $fullPath = Storage::disk('public')->path($doc->chemin);

                        if (!file_exists($fullPath)) {
                            Log::warning('OM sendEmails : fichier introuvable', [
                                'om_id'   => $doc->om_id,
                                'entite'  => $entite->entity_name,
                                'fichier' => $doc->nom_fichier,
                                'chemin'  => $fullPath,
                            ]);
                            continue;
                        }

                        $mail->attach($fullPath, [
                            'as'   => $doc->nom_fichier,
                            'mime' => $doc->mime_type ?? 'application/octet-stream',
                        ]);

                        Log::info('OM sendEmails : document joint', [
                            'entite'  => $entite->entity_name,
                            'fichier' => $doc->nom_fichier,
                            'taille'  => round(($doc->taille ?? 0) / 1024, 1) . ' Ko',
                        ]);
                    }
                });

                // ── 6. Marquer l'entité comme envoyée ────────────────────
                DB::table('ordre_mission_entites')
                    ->where('om_id', $id)
                    ->where('entity_id', $entite->entity_id)
                    ->update([
                        'email_envoye'    => 1,
                        'email_envoye_le' => now(),
                        'updated_at'      => now(),
                    ]);

                // ── 7. Log d'envoi ───────────────────────────────────────
                DB::table('ordre_mission_envois')->insert([
                    'om_id'        => $id,
                    'entity_id'    => $entite->entity_id,
                    'destinataire' => $entite->email_contact,
                    'type'         => 'to',
                    'statut'       => 'envoye',
                    'envoye_le'    => now(),
                    'created_at'   => now(),
                ]);

                $sent++;

                Log::info('OM envoyé avec succès', [
                    'om_ref'  => $om->reference_om,
                    'entite'  => $entite->entity_name,
                    'email'   => $entite->email_contact,
                    'nb_pj'   => $docsEntite->count() + 1,
                ]);

            } catch (\Exception $e) {
                Log::error('Erreur envoi OM', [
                    'om_ref' => $om->reference_om,
                    'entite' => $entite->entity_name,
                    'error'  => $e->getMessage(),
                ]);

                $errors[] = "{$entite->entity_name} : " . $e->getMessage();

                DB::table('ordre_mission_envois')->insert([
                    'om_id'        => $id,
                    'entity_id'    => $entite->entity_id,
                    'destinataire' => $entite->email_contact ?? '',
                    'type'         => 'to',
                    'statut'       => 'echec',
                    'erreur'       => substr($e->getMessage(), 0, 500),
                    'created_at'   => now(),
                ]);
            }
        }

        // ── 8. Mettre à jour le statut global de l'OM ────────────────────
        if ($sent > 0) {
            DB::table('ordre_missions')->where('id', $id)->update([
                'status'     => 'envoye',
                'envoye_le'  => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 9. Retour utilisateur ─────────────────────────────────────────
        if ($sent > 0 && empty($errors)) {
            return back()->with('success', "{$sent} email(s) envoyé(s) avec succès avec toutes les pièces jointes.");
        } elseif ($sent > 0) {
            $errMsg = implode(' | ', array_slice($errors, 0, 3));
            return back()->with('warning', "{$sent} envoyé(s). " . count($errors) . " erreur(s) : {$errMsg}");
        } else {
            $errMsg = implode(' | ', array_slice($errors, 0, 3));
            return back()->withErrors(['error' => "Échec total. Erreurs : {$errMsg}"]);
        }
    }

    // =========================================================================
    // CHARGER ENTITÉS D'UNE MISSION (AJAX)
    // =========================================================================

    public function getMissionEntites(Request $request, int $missionProgId)
    {
        $mission = DB::table('mission_programmation')->where('id', $missionProgId)->first();
        if (!$mission) return response()->json(['error' => 'Mission introuvable'], 404);

        $entites = DB::table('mission_programmation_entity as mpe')
            ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->where('mpe.mission_programmation_id', $missionProgId)
            ->select('e.id as entity_id', 'e.name as entity_name', 'e.code_base', 'mpe.date_debut', 'mpe.date_fin')
            ->orderBy('e.name')
            ->get()
            ->map(fn($e) => [
                'entity_id'     => (int) $e->entity_id,
                'entity_name'   => $e->entity_name,
                'entity_code'   => $e->code_base,
                'date_debut'    => $e->date_debut ?: $mission->date_debut,
                'date_fin'      => $e->date_fin   ?: $mission->date_fin,
                'email_contact' => null,
                'nom_contact'   => null,
            ]);

        $auditeurs = DB::table('mission_phase_auditeurs as mpa')
            ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionProgId)
            ->select('a.id as auditeur_id', 'a.audit_code', 'a.first_name', 'a.last_name', 'mpa.role', 'mr.libelle as role_libelle')
            ->orderByRaw('COALESCE(mr.niveau, 99) ASC')
            ->get();

        return response()->json([
            'mission'   => $mission,
            'entites'   => $entites,
            'auditeurs' => $auditeurs,
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(int $id)
    {
        DB::table('ordre_missions')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()
            ->route('audit.core.ordre-missions.index')
            ->with('success', 'Ordre de Mission supprimé.');
    }

    // =========================================================================
    // HELPERS PRIVÉS
    // =========================================================================

    private function getOMComplet(int $id): ?object
    {
        return DB::table('ordre_missions as om')
            ->select([
                'om.*',
                DB::raw("DATE_FORMAT(om.date_debut,            '%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(om.date_fin,              '%d/%m/%Y') as date_fin_fr"),
                DB::raw("DATE_FORMAT(om.date_limite_diffusion, '%d/%m/%Y') as date_limite_fr"),
                'mp.libelle    as mission_prog_libelle',
                'mp.code_mission as mission_prog_code',
            ])
            ->leftJoin('mission_programmation as mp', 'om.mission_prog_id', '=', 'mp.id')
            ->where('om.id', $id)
            ->whereNull('om.deleted_at')
            ->first();
    }

    private function getEntitesOM(int $id)
    {
        return DB::table('ordre_mission_entites as ome')
            ->join('entities as e', 'ome.entity_id', '=', 'e.id')
            ->where('ome.om_id', $id)
            ->select('ome.*', 'e.name as entity_name', 'e.code_base')
            ->orderBy('e.name')
            ->get();
    }

    private function getAuditeursOM(int $id)
    {
        return DB::table('ordre_mission_auditeurs as oma')
            ->join('auditors as a', 'oma.auditeur_id', '=', 'a.id')
            ->where('oma.om_id', $id)
            ->select('oma.*', 'a.audit_code', 'a.first_name', 'a.last_name', 'a.email')
            ->orderByRaw("CASE WHEN oma.scope = 'global' THEN 0 ELSE 1 END")
            ->orderBy('oma.ordre')
            ->get();
    }

    private function getDocumentsOM(int $id)
    {
        return DB::table('ordre_mission_documents')
            ->where('om_id', $id)
            ->orderBy('entity_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Extrait les fichiers pour une entité depuis la requête.
     * Supporte 3 formats FormData :
     *   1. docs_{entityId}[]         => tableau de fichiers (format principal Vue)
     *   2. docs_{entityId}_0, _1 ... => fichiers individuels
     *   3. entites[idx][documents][] => tableau imbriqué (fallback allFiles)
     */
    private function extractFilesForEntity(Request $request, int $entityId, int $idx): array
    {
        // Format 1 — clé plate tableau (recommandé)
        $key = "docs_{$entityId}";
        if ($request->hasFile($key)) {
            $f = $request->file($key);
            $files = is_array($f) ? $f : [$f];
            if (!empty($files)) return array_values(array_filter($files));
        }

        // Format 2 — clés individuelles numérotées
        $files = [];
        for ($i = 0; $i < 20; $i++) {
            if ($request->hasFile("docs_{$entityId}_{$i}")) {
                $files[] = $request->file("docs_{$entityId}_{$i}");
            }
        }
        if (!empty($files)) return array_values(array_filter($files));

        // Format 3 — imbriqué (fallback)
        $allFiles = $request->allFiles();
        if (isset($allFiles['entites'][$idx]['documents'])) {
            $d = $allFiles['entites'][$idx]['documents'];
            return is_array($d) ? array_values(array_filter($d)) : [$d];
        }

        return [];
    }

    private function getFormData(Request $request): array
    {
        $missions = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id', '=', 'm.id')
            ->leftJoin('mission_budgets as mb', 'mp.id',        '=', 'mb.mission_id')
            ->whereIn('mp.status', ['planifiee', 'en_cours'])
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.date_debut', 'mp.date_fin',
                'mp.objectif', 'mp.lieux',
                DB::raw("COALESCE(mb.montant_fixe,0) + COALESCE(mb.montant_variable,0) as budget"),
                DB::raw("COALESCE(mp.duree,0) as duree"),
            ])
            ->orderBy('mp.date_debut', 'desc')
            ->get();

        return [
            'missions'  => $missions,
            'entites'   => DB::table('entities')->orderBy('name')->select('id', 'name', 'code_base')->get(),
            'auditeurs' => DB::table('auditors')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('last_name')
                ->select('id', 'audit_code', 'first_name', 'last_name', 'email')
                ->get(),
            'roles'  => DB::table('mission_roles')->where('is_active', 1)->orderBy('niveau')->get(),
            'newRef' => $this->generateReference(),
        ];
    }

    private function generateReference(): string
    {
        $year  = date('Y');
        $count = DB::table('ordre_missions')
            ->where('reference_om', 'like', "OM-{$year}-%")
            ->count();
        return "OM-{$year}-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MESSAGE PAR DÉFAUT
    // ──────────────────────────────────────────────────────────────────────────

    private function buildDefaultMessage(object $om, object $entite, $audsEntite): string
    {
        $chef    = is_object($audsEntite) ? $audsEntite->first() : ($audsEntite[0] ?? null);
        $nomChef = $chef
            ? strtoupper($chef->last_name) . ' ' . ucfirst(strtolower($chef->first_name))
            : "l'equipe d'audit";

        return "Conformement au plan d'audit interne valide par la Direction Generale, "
            . "nous vous informons que le service d'audit interne est mandate pour realiser "
            . "sous la responsabilite de {$nomChef} la mission decrite dans le document ci-joint "
            . "aupres de votre entite.\n\n"
            . "Nous vous remercions des dispositions que vous voudrez bien mettre en place "
            . "pour faciliter le travail a l'equipe d'audit et vous prions d'en informer "
            . "les responsables concernes.";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HTML DE L'EMAIL — informations spécifiques à chaque entité
    // ──────────────────────────────────────────────────────────────────────────

    private function buildEmailHtml(object $om, object $entite, $audsEntite, string $message): string
    {
        $today       = now()->format('d/m/Y');
        $lieu        = htmlspecialchars($entite->lieux ?? $om->lieux ?? 'Cotonou');
        $refOm       = htmlspecialchars($om->reference_om);
        $intitule    = htmlspecialchars($om->intitule);
        $entiteNom   = htmlspecialchars($entite->entity_name);
        $messageHtml = nl2br(htmlspecialchars($message));
        $emetteur    = htmlspecialchars($om->emetteur ?? 'Cabinet KEKELI');

        // Dates spécifiques à l'entité, sinon dates globales de l'OM
        $dateDebut = !empty($entite->date_debut)
            ? date('d/m/Y', strtotime($entite->date_debut))
            : (!empty($om->date_debut) ? date('d/m/Y', strtotime($om->date_debut)) : '—');
        $dateFin = !empty($entite->date_fin)
            ? date('d/m/Y', strtotime($entite->date_fin))
            : (!empty($om->date_fin) ? date('d/m/Y', strtotime($om->date_fin)) : '—');
        $duree      = $entite->duree ?? $om->duree ?? null;
        $dureeInline = $duree
            ? "<span style='color:#94A3B8;font-size:12px'> ({$duree} jours)</span>"
            : '';

        // Contact de l'entité
        $contactHtml = $entite->nom_contact
            ? "<div style='color:#475569;font-size:13px;margin-top:3px'>A l'attention de : <strong>"
              . htmlspecialchars($entite->nom_contact) . "</strong></div>"
            : '';

        // Lignes budget / lieu / durée
        $lieuRow = !empty($entite->lieux)
            ? "<tr><td style='color:#64748B;font-size:12px;padding:4px 0;width:40%'>Lieu</td><td style='color:#0F172A;font-size:13px;padding:4px 0'>" . htmlspecialchars($entite->lieux) . "</td></tr>"
            : (!empty($om->lieux)
                ? "<tr><td style='color:#64748B;font-size:12px;padding:4px 0;width:40%'>Lieu</td><td style='color:#0F172A;font-size:13px;padding:4px 0'>" . htmlspecialchars($om->lieux) . "</td></tr>"
                : '');
        $dureeRow  = $duree
            ? "<tr><td style='color:#64748B;font-size:12px;padding:4px 0'>Durée</td><td style='color:#0F172A;font-size:13px;padding:4px 0'>{$duree} jours</td></tr>"
            : '';
        $budgetRow = ($om->budget > 0)
            ? "<tr><td style='color:#64748B;font-size:12px;padding:4px 0'>Budget</td><td style='color:#059669;font-size:13px;font-weight:700;padding:4px 0'>" . number_format($om->budget, 0, ',', ' ') . " FCFA</td></tr>"
            : '';

        // Liste des auditeurs
        $audsCollection = is_object($audsEntite) ? $audsEntite : collect($audsEntite);
        $auditeursList  = '';
        foreach ($audsCollection as $aud) {
            $nom   = htmlspecialchars(strtoupper($aud->last_name) . ' ' . ucfirst(strtolower($aud->first_name)));
            $role  = htmlspecialchars($aud->role_libelle ?? $aud->role ?? '');
            $badge = (isset($aud->scope) && $aud->scope === 'global')
                ? " <span style='background:#F59E0B;color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700'>Global</span>"
                : '';
            $roleSpan = $role
                ? " <span style='color:#1E40AF;font-size:12px'>({$role}){$badge}</span>"
                : $badge;
            $auditeursList .= "<tr>
                <td style='padding:5px 8px;border-bottom:1px solid #ECFDF5'>
                    <strong style='color:#0F172A'>{$nom}</strong>{$roleSpan}
                </td>
            </tr>";
        }
        $equipeBlock = $auditeursList
            ? "<tr><td style='padding:0 32px 18px'>
                <div style='background:#F0FDF4;border-left:4px solid #059669;border-radius:0 8px 8px 0;padding:16px 20px'>
                    <div style='font-size:10px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px'>Equipe d'Audit Affectee</div>
                    <table width='100%' cellpadding='0' cellspacing='0'>{$auditeursList}</table>
                </div>
               </td></tr>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:Arial,Helvetica,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9">
<tr><td align="center" style="padding:28px 16px">
<table width="600" cellpadding="0" cellspacing="0"
    style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.10)">

    <!-- EN-TÊTE -->
    <tr><td style="background:#0F172A;padding:28px 32px">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td>
                <div style="color:#93C5FD;font-size:10px;letter-spacing:2px;text-transform:uppercase;margin-bottom:5px">
                    Cabinet KEKELI — Direction de l'Audit Interne
                </div>
                <div style="color:#ffffff;font-size:19px;font-weight:700;line-height:1.3;margin-bottom:6px">
                    {$intitule}
                </div>
                <div style="color:#60A5FA;font-size:13px">
                    Reference : <strong>{$refOm}</strong>
                </div>
            </td>
            <td align="right" valign="top" width="130">
                <div style="background:#1E3A5F;border:1px solid #2D5A8E;border-radius:8px;padding:10px 14px;text-align:center">
                    <div style="color:#93C5FD;font-size:9px;text-transform:uppercase;letter-spacing:1px">Ordre de Mission</div>
                    <div style="color:#fff;font-size:15px;font-weight:700;font-family:monospace;margin-top:3px">{$refOm}</div>
                    <div style="color:#60A5FA;font-size:10px;margin-top:3px">{$today}</div>
                </div>
            </td>
        </tr></table>
    </td></tr>

    <!-- BANDES BLEUES -->
    <tr><td style="background:#1E40AF;height:4px;padding:0;font-size:0"></td></tr>
    <tr><td style="background:#3B82F6;height:2px;padding:0;font-size:0"></td></tr>

    <!-- DESTINATAIRE -->
    <tr><td style="padding:22px 32px 0">
        <div style="font-size:9px;font-weight:700;color:#1E40AF;text-transform:uppercase;letter-spacing:2px;margin-bottom:5px;padding-bottom:4px;border-bottom:1px solid #DBEAFE">
            Destinataire
        </div>
        <div style="font-size:18px;font-weight:700;color:#0F172A;margin-bottom:3px">{$entiteNom}</div>
        {$contactHtml}
    </td></tr>

    <!-- SÉPARATEUR -->
    <tr><td style="padding:14px 32px">
        <div style="border-top:2px solid #1E40AF"></div>
    </td></tr>

    <!-- MESSAGE PRINCIPAL -->
    <tr><td style="padding:0 32px 18px">
        <div style="color:#334155;font-size:14px;line-height:1.85">{$messageHtml}</div>
    </td></tr>

    <!-- ÉLÉMENTS CARACTÉRISTIQUES -->
    <tr><td style="padding:0 32px 18px">
        <div style="background:#EFF6FF;border-left:4px solid #1E40AF;border-radius:0 8px 8px 0;padding:16px 20px">
            <div style="font-size:10px;font-weight:700;color:#1E40AF;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">
                Elements Caracteristiques
            </div>
            <table width="100%" cellpadding="4" cellspacing="0">
                <tr>
                    <td style="color:#64748B;font-size:12px;padding:4px 0;width:40%">Mission</td>
                    <td style="color:#0F172A;font-size:13px;font-weight:600;padding:4px 0">{$intitule}</td>
                </tr>
                <tr>
                    <td style="color:#64748B;font-size:12px;padding:4px 0">Entite auditee</td>
                    <td style="color:#0F172A;font-size:13px;font-weight:600;padding:4px 0">{$entiteNom}</td>
                </tr>
                <tr>
                    <td style="color:#64748B;font-size:12px;padding:4px 0">Periode</td>
                    <td style="color:#0F172A;font-size:13px;padding:4px 0">
                        <strong>{$dateDebut}</strong> &rarr; <strong>{$dateFin}</strong>
                        {$dureeInline}
                    </td>
                </tr>
                {$lieuRow}
                {$dureeRow}
                {$budgetRow}
            </table>
        </div>
    </td></tr>

    <!-- ÉQUIPE D'AUDIT -->
    {$equipeBlock}

    <!-- NOTE PIÈCES JOINTES -->
    <tr><td style="padding:0 32px 18px">
        <div style="background:#FEF9C3;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:12px;color:#78350F">
            <strong>Pieces jointes :</strong>
            Le PDF officiel de l'Ordre de Mission est joint a ce courriel, ainsi que tous les
            documents associes a votre entite. Veuillez en prendre connaissance et les transmettre
            aux responsables concernes.
        </div>
    </td></tr>

    <!-- SIGNATURE -->
    <tr><td style="padding:0 32px 24px">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="color:#475569;font-size:13px;vertical-align:bottom">
                Fait a {$lieu}, le {$today}
            </td>
            <td align="right" style="vertical-align:bottom;width:220px">
                <div style="font-size:11px;color:#64748B;margin-bottom:24px">
                    Le Directeur de l'Audit Interne
                </div>
                <div style="border-top:1px solid #CBD5E1;padding-top:5px">
                    <div style="font-size:13px;font-weight:700;color:#0F172A">{$emetteur}</div>
                </div>
            </td>
        </tr></table>
    </td></tr>

    <!-- PIED DE PAGE -->
    <tr><td style="background:#0F172A;padding:14px 32px">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="color:#64748B;font-size:10px">
                {$refOm} &mdash; {$entiteNom} &mdash; Document confidentiel
            </td>
            <td align="right" style="color:#60A5FA;font-size:10px">
                Cabinet KEKELI &bull; {$today}
            </td>
        </tr></table>
    </td></tr>

</table>
<div style="text-align:center;color:#94A3B8;font-size:10px;margin-top:14px;padding:0 16px">
    Ce message est confidentiel et destine uniquement au destinataire indique.<br>
    L'Ordre de Mission officiel est le document PDF joint a ce courriel.
</div>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}