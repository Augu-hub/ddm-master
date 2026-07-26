<?php
// ════════════════════════════════════════════════════════════════════
// DynamicPhaseFormController.php
//
// ROUTES AUTOMATIQUES DES FORMULAIRES DE PHASE.
//
// Le référentiel central (ddmparam.audit_type_forms, ~112 formulaires)
// définit pour chaque formulaire un url_path de la forme :
//     /m/audit.core/{type}/{phase}/{code}   (ex: /m/audit.core/ap/realisation/benchmarking)
//
// Jusqu'ici, seuls les formulaires disposant d'un contrôleur + routes
// dédiés (AC pour l'essentiel, AP réunion-ouverture) répondaient — tous
// les autres renvoyaient 404 depuis le menu ou les cartes de phase.
//
// Ce contrôleur est branché sur des routes "fallback" génériques
// enregistrées EN DERNIER (routes/web.php, groupe m/audit.core) :
// toute URL de formulaire sans route dédiée aboutit ici. Le formulaire
// est résolu dans ddmparam, la saisie est stockée en JSON dans la table
// tenant `mission_phase_form_data` (une ligne par assignment+form_code),
// avec le MÊME workflow que les fiches dédiées :
//     draft → submitted → validated | rejected (DM/CM)
// et journalisation dans mission_phase_validation_log.
//
// Dès qu'un écran dédié est développé pour un formulaire, il suffit
// d'enregistrer ses routes AVANT le fallback : il prend la main sans
// toucher à ce contrôleur.
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor;

use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DynamicPhaseFormController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_form_data';
    protected string $formCode    = '';   // résolu dynamiquement par requête
    protected string $codePrefix  = 'DYN';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/GenericPhaseForm';
    protected string $routeEdit   = 'audit.dynamic.form';

    // ══════════════════════════════════════════════════════════════════
    // ÉCRANS DÉDIÉS
    //
    // Par défaut toute phase ouvre le formulaire générique par sections
    // (GenericPhaseForm). Certaines phases « premières » de l'audit des
    // marchés (AM) disposent d'un écran dédié, plus riche, alimenté par le
    // paramétrage central déjà synchronisé dans le tenant (pièces
    // obligatoires, seuils, grille d'appréciation…). La clé est le CODE du
    // formulaire (ddmparam.audit_type_forms.code) ; ajouter une entrée ici
    // suffit à basculer la phase vers sa vue dédiée sans toucher au routage.
    // ══════════════════════════════════════════════════════════════════
    private const DEDICATED_PAGES = [
        'ADE'              => 'dashboards/Auditor/Forms/AM/AnalyseDocumentaire',
        // Phases adossées à une grille de vérification ARMP déjà transcrite :
        // toutes rendues par le même écran générique, paramétré par la grille.
        'mise_en_place'    => 'dashboards/Auditor/Forms/AM/GrilleVerification',
        'conf'             => 'dashboards/Auditor/Forms/AM/GrilleVerification',
        'RIT'              => 'dashboards/Auditor/Forms/AM/GrilleVerification',
        'AOC'              => 'dashboards/Auditor/Forms/AM/GrilleVerification',
        'AAC'              => 'dashboards/Auditor/Forms/AM/GrilleVerification',
        'releve-anomalies' => 'dashboards/Auditor/Forms/AM/GrilleVerification',
    ];

    /** Phase (code ddmparam) → code de la grille de vérification à charger. */
    private const GRILLE_BY_FORM = [
        'mise_en_place'    => 'A4',   // Mise en place & fonctionnement des organes
        'conf'             => 'A5',   // Plan de passation des marchés (PPM)
        'RIT'              => 'A7',   // Règles d'information et de transparence
        'AOC'              => 'A9',   // Avis des organes de contrôle aux étapes prévues
        'AAC'              => 'A10',  // Approbation par l'autorité compétente
        'releve-anomalies' => 'A11',  // Gestion administrative de l'exécution
    ];

    private function dedicatedPageFor(object $meta): string
    {
        return self::DEDICATED_PAGES[$meta->code] ?? $this->inertiaPage;
    }

    /**
     * Données de référence (lecture seule) injectées dans l'écran dédié,
     * lues sur le tenant courant. Silencieux si le tenant n'est pas encore
     * resynchronisé (tables pm_* absentes) → l'écran s'affiche sans réf.
     */
    private function refDataFor(object $meta): array
    {
        if (isset(self::GRILLE_BY_FORM[$meta->code])) {
            return $this->grilleRefData(self::GRILLE_BY_FORM[$meta->code]);
        }
        return match ($meta->code) {
            'ADE'   => $this->piecesRefData(),
            default => [],
        };
    }

    /**
     * Grille de vérification (items + modalités de pondération + seuils),
     * lue sur le tenant. La pondération est linéaire :
     * Respecté=100 / Partiellement=50 / Non=0 / N-A exclu du dénominateur.
     */
    private function grilleRefData(string $grilleCode): array
    {
        try {
            $grille = DB::table('pm_grilles_verification')
                ->where('code', $grilleCode)->first();
            if (!$grille) return ['grille' => null, 'items' => [], 'modalites' => [], 'seuils' => []];

            $items = DB::table('pm_grilles_verification_items')
                ->where('grille_id', $grille->id)->where('actif', 1)
                ->orderByRaw('CAST(numero AS UNSIGNED)')->orderBy('sort')->orderBy('id')
                ->get();

            $modalites = DB::table('pm_modalites_appreciation')
                ->where('actif', 1)->orderBy('sort')->orderBy('id')->get();

            $seuils = DB::table('pm_parametres_audit')
                ->whereIn('code', ['SEUIL_CONFORMITE', 'SEUIL_AUDITABILITE'])
                ->pluck('valeur', 'code');
        } catch (\Throwable $e) {
            return ['grille' => null, 'items' => [], 'modalites' => [], 'seuils' => []];
        }

        return [
            'grille' => [
                'code'        => $grille->code,
                'intitule'    => $grille->intitule,
                'description' => $grille->description ?? null,
            ],
            'items' => $items->map(fn ($it) => [
                'id'              => (int) $it->id,
                'numero'          => $it->numero,
                'libelle'         => $it->libelle_controle,
                'gravite_ecart'   => $it->gravite_ecart,       // majeur | modere | null
                'reference_ecart' => $it->reference_ecart,
                'preuves'         => $it->preuves,
                'obligatoire'     => (bool) $it->obligatoire,
            ])->values()->all(),
            'modalites' => $modalites->map(fn ($m) => [
                'code'            => $m->code,
                'libelle'         => $m->libelle,
                'poids'           => (float) $m->poids,
                'exclu_du_calcul' => (bool) $m->exclu_du_calcul,
                'couleur'         => $m->couleur,
                'icone'           => $m->icone,
            ])->values()->all(),
            'seuils' => [
                'conformite'   => (float) ($seuils['SEUIL_CONFORMITE']   ?? 80),
                'auditabilite' => (float) ($seuils['SEUIL_AUDITABILITE'] ?? 80),
            ],
        ];
    }

    /** Pièces obligatoires groupées par catégorie + grille de disponibilité. */
    private function piecesRefData(): array
    {
        try {
            $cats = DB::table('pm_pieces_categories')
                ->where('actif', 1)->orderBy('sort')->orderBy('id')->get();

            $pieces = DB::table('pm_pieces_obligatoires')
                ->where('actif', 1)->orderBy('sort')->orderBy('id')->get();

            $grille = DB::table('pm_grille_appreciation_disponibilite')
                ->where('actif', 1)->orderBy('sort')->orderBy('id')->get();
        } catch (\Throwable $e) {
            return ['categories' => [], 'grilleDispo' => []];
        }

        $piecesByCat = [];
        foreach ($pieces as $p) {
            $piecesByCat[$p->categorie_id][] = [
                'id'                  => (int) $p->id,
                'code'                => $p->code,
                'libelle'             => $p->libelle,
                'incidence'           => $p->incidence,             // directe | sans_incidence
                'reference_texte'     => $p->reference_texte,
                'mode_passation_code' => $p->mode_passation_code,
                'compte_auditabilite' => (bool) $p->compte_auditabilite,
                'obligatoire'         => (bool) $p->obligatoire,
            ];
        }

        $categories = [];
        foreach ($cats as $c) {
            $list = $piecesByCat[$c->id] ?? [];
            if (!$list) continue;   // n'affiche pas les catégories vides
            $categories[] = [
                'id'          => (int) $c->id,
                'code'        => $c->code,
                'libelle'     => $c->libelle,
                'annexe'      => $c->annexe,
                'description' => $c->description,
                'pieces'      => $list,
            ];
        }

        return [
            'categories'  => $categories,
            'grilleDispo' => $grille->map(fn ($g) => [
                'borne_min'     => (float) $g->borne_min,
                'operateur_min' => $g->operateur_min,
                'borne_max'     => (float) $g->borne_max,
                'operateur_max' => $g->operateur_max,
                'appreciation'  => $g->appreciation,
                'couleur'       => $g->couleur,
            ])->values()->all(),
        ];
    }

    /** Non utilisé (persistance JSON maison) — requis par la classe de base. */
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [];
    }

    // ══════════════════════════════════════════════════════════════════
    // RÉSOLUTION — retrouve le formulaire ddmparam depuis l'URL
    // ══════════════════════════════════════════════════════════════════

    private function resolveForm(string $auditType, string $phaseSlug, string $formCode): ?object
    {
        $urlPath = "/m/audit.core/{$auditType}/{$phaseSlug}/{$formCode}";

        $q = DB::table('ddmparam.audit_type_forms as f')
            ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
            ->where('at.code', strtoupper($auditType))
            ->where('f.is_active', 1)
            ->select([
                'f.id', 'f.code', 'f.label', 'f.description', 'f.norme',
                'f.phase_num', 'f.phase_label', 'f.icon', 'f.url_path',
                'at.code  as audit_type_code',
                'at.label as audit_type_label',
                'at.color as audit_color',
                'at.icon  as audit_icon',
            ]);

        // 1. Correspondance stricte par url_path (gère un même code présent
        //    dans plusieurs phases du même type d'audit)
        $form = (clone $q)->where('f.url_path', $urlPath)->first();
        if ($form) return $form;

        // 2. Repli par code seul (url_path absent/légèrement différent)
        return $q->where('f.code', $formCode)->orderBy('f.phase_num')->first();
    }

    // ══════════════════════════════════════════════════════════════════
    // SHOW — GET /m/audit.core/{type}/{phase}/{code}?mission_id&assignment_id
    // (nommé show — index(Request) de la classe de base a une autre signature)
    // ══════════════════════════════════════════════════════════════════

    public function show(Request $request, string $auditType, string $phaseSlug, string $formCode)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return redirect()->route('login');

        $meta = $this->resolveForm($auditType, $phaseSlug, $formCode);
        if (!$meta) {
            abort(404, "Formulaire '{$formCode}' introuvable dans le référentiel " . strtoupper($auditType) . '.');
        }

        $this->formCode = $meta->code;

        $missionId    = (int) ($request->mission_id    ?? 0);
        $assignmentId = (int) ($request->assignment_id ?? 0);

        // Page à rendre : dédiée si le code y est mappé, sinon form générique.
        // Les données de référence (pièces, seuils…) ne dépendent que du form,
        // on les calcule une fois pour les deux branches (avec/sans mission).
        $page    = $this->dedicatedPageFor($meta);
        $refData = $this->refDataFor($meta);

        $base = [
            'formMeta' => $meta,
            'formUrl'  => url("/m/audit.core/{$auditType}/{$phaseSlug}/{$formCode}"),
            'refData'  => $refData,
        ];

        if (!$missionId || !$assignmentId) {
            return Inertia::render($page, array_merge($base, [
                'noMission' => true, 'mission' => null, 'assignment' => null,
                'auditeurs' => [], 'record' => null, 'auditorRole' => null,
                'missionId' => null, 'assignmentId' => null, 'errors' => [],
                'missionMenu' => [], 'backUrl' => url('/m/audit.core/auditor/missions'),
                'chatBaseUrl' => '',
            ]));
        }

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        $assignment = $this->getAssignment($assignmentId, $missionId);

        $payload = array_merge(
            $this->buildPayload($missionId, $assignmentId, $auditor),
            $base,
            [
                'record'      => $this->loadRecord($assignmentId, $meta->code),
                'backUrl'     => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
                'chatBaseUrl' => url("/m/audit.core/missions/{$missionId}/chat/" . $this->phaseTypeOf((int) $meta->phase_num)),
            ]
        );

        if ($assignment && $assignment->phase_status === 'pending') {
            $payload['phaseNotStarted'] = true;
        }

        // La couleur du type d'audit vient directement du référentiel
        if ($payload['mission']) {
            $payload['mission']->audit_color      = $meta->audit_color;
            $payload['mission']->audit_icon       = $meta->audit_icon;
            $payload['mission']->audit_type_label = $meta->audit_type_label;
        }

        return Inertia::render($page, $payload);
    }

    // ══════════════════════════════════════════════════════════════════
    // SAVE — POST /m/audit.core/{type}/{phase}/{code}  (upsert draft)
    // ══════════════════════════════════════════════════════════════════

    public function save(Request $request, string $auditType, string $phaseSlug, string $formCode)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $meta = $this->resolveForm($auditType, $phaseSlug, $formCode);
        if (!$meta) return response()->json(['error' => 'Formulaire introuvable'], 404);
        $this->formCode = $meta->code;

        $data = $request->validate([
            'mission_id'            => 'required|integer',
            'assignment_id'         => 'required|integer',
            'sections'              => 'nullable|array|max:100',
            'sections.*.titre'      => 'nullable|string|max:255',
            'sections.*.contenu'    => 'nullable|string|max:20000',
            'notes'                 => 'nullable|string|max:20000',
            // Écrans dédiés : bloc libre (réponses de checklist, tableaux…).
            // Le form générique ne l'envoie pas ; il reste alors null.
            'data'                  => 'nullable|array',
        ]);

        $missionId    = (int) $data['mission_id'];
        $assignmentId = (int) $data['assignment_id'];

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $assignment = DB::table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending') {
            return response()->json(['message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);
        }

        $role     = $this->getRole($missionId, $auditor->id);
        $existing = DB::table($this->table)
            ->where('assignment_id', $assignmentId)
            ->where('form_code', $meta->code)
            ->first();

        if ($existing) {
            if ($existing->status === 'validated') {
                return response()->json(['message' => 'Formulaire validé — modification impossible.'], 422);
            }
            if ($existing->status === 'submitted' && !in_array($role, ['DM', 'CM'])) {
                return response()->json(['message' => 'Formulaire soumis — seuls CM/DM peuvent modifier.'], 422);
            }
        }

        $json = json_encode([
            'sections' => array_values($data['sections'] ?? []),
            'notes'    => $data['notes'] ?? '',
            'data'     => $data['data'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        if ($existing) {
            DB::table($this->table)->where('id', $existing->id)->update([
                'data_json'  => $json,
                'version'    => (int) $existing->version + 1,
                'updated_at' => now(),
            ]);
            $id = $existing->id;
        } else {
            $id = DB::table($this->table)->insertGetId([
                'assignment_id' => $assignmentId,
                'mission_id'    => $missionId,
                'form_code'     => $meta->code,
                'entity_id'     => $assignment->entity_id ?? null,
                'data_json'     => $json,
                'version'       => 1,
                'status'        => 'draft',
                'created_by'    => $auditor->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'draft_saved', null, 'draft');
        }

        return response()->json([
            'success' => true,
            'record'  => $this->hydrateRecord(DB::table($this->table)->where('id', $id)->first()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // WORKFLOW — soumettre / valider / rejeter
    // ══════════════════════════════════════════════════════════════════

    public function soumettreForm(Request $request, string $auditType, string $phaseSlug, string $formCode)
    {
        [$auditor, $meta, $row, $err] = $this->loadForWorkflow($request, $auditType, $phaseSlug, $formCode);
        if ($err) return $err;

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Seul un brouillon peut être soumis.'], 422);
        }

        $role = $this->getRole((int) $row->mission_id, $auditor->id);

        DB::table($this->table)->where('id', $row->id)->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => $auditor->id,
            'updated_at'   => now(),
        ]);

        $this->log((int) $row->assignment_id, $auditor->id, $role, 'submitted', 'draft', 'submitted');
        return response()->json(['success' => true, 'status' => 'submitted']);
    }

    public function validerForm(Request $request, string $auditType, string $phaseSlug, string $formCode)
    {
        [$auditor, $meta, $row, $err] = $this->loadForWorkflow($request, $auditType, $phaseSlug, $formCode);
        if ($err) return $err;

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        }
        if ($row->status !== 'submitted') {
            return response()->json(['message' => 'Le formulaire doit être soumis avant validation.'], 422);
        }

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['message' => 'Motif du rejet obligatoire'], 422);

            DB::table($this->table)->where('id', $row->id)->update([
                'status'          => 'draft',
                'validation_note' => $note,
                'updated_at'      => now(),
            ]);
            $this->log((int) $row->assignment_id, $auditor->id, $role, 'rejected', 'submitted', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') {
            return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);
        }

        DB::table($this->table)->where('id', $row->id)->update([
            'status'          => 'validated',
            'validated_at'    => now(),
            'validated_by'    => $auditor->id,
            'validation_note' => $note,
            'updated_at'      => now(),
        ]);

        DB::table('mission_phase_assignments')->where('id', $row->assignment_id)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log((int) $row->assignment_id, $auditor->id, $role, 'validated', 'submitted', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════

    /** Charge (auditor, meta, row) pour un endpoint de workflow, avec contrôles. */
    private function loadForWorkflow(Request $request, string $auditType, string $phaseSlug, string $formCode): array
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return [null, null, null, response()->json(['error' => 'Non autorisé'], 403)];

        $meta = $this->resolveForm($auditType, $phaseSlug, $formCode);
        if (!$meta) return [null, null, null, response()->json(['error' => 'Formulaire introuvable'], 404)];
        $this->formCode = $meta->code;

        $assignmentId = (int) $request->input('assignment_id', 0);
        $row = DB::table($this->table)
            ->where('assignment_id', $assignmentId)
            ->where('form_code', $meta->code)
            ->first();
        if (!$row) return [null, null, null, response()->json(['error' => 'Aucune saisie à traiter — enregistrez d\'abord.'], 404)];

        if (!$this->canAccess((int) $row->mission_id, (int) $row->assignment_id, $auditor)) {
            return [null, null, null, response()->json(['error' => 'Accès refusé'], 403)];
        }

        return [$auditor, $meta, $row, null];
    }

    private function loadRecord(int $assignmentId, string $formCode): ?object
    {
        $row = DB::table($this->table)
            ->where('assignment_id', $assignmentId)
            ->where('form_code', $formCode)
            ->first();

        if ($row) return $this->hydrateRecord($row);

        // Aucune saisie encore : on pré-remplit à partir du gabarit central
        // (pm_form_templates, synchronisé depuis ddmparam) pour que la phase
        // s'ouvre déjà structurée selon les rubriques du référentiel, plutôt
        // que vide. Ce n'est qu'un brouillon d'affichage (id/status null) :
        // rien n'est écrit tant que l'auditeur n'enregistre pas.
        return $this->templateRecord($formCode);
    }

    /** Brouillon d'affichage bâti sur pm_form_templates (ou null si aucun). */
    private function templateRecord(string $formCode): ?object
    {
        // La table gabarit peut ne pas exister sur un tenant pas encore
        // resynchronisé : on échoue silencieusement vers un formulaire vide.
        try {
            $tpl = DB::table('pm_form_templates')
                ->where('form_code', $formCode)->where('actif', 1)->first();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$tpl) return null;

        $sections = json_decode($tpl->sections_json ?? '[]', true) ?: [];

        return (object) [
            'id'              => null,
            'status'          => 'draft',
            'version'         => 0,
            'sections'        => array_values($sections),
            'notes'           => '',
            'data'            => null,
            'validation_note' => null,
            'is_template'     => true,   // le front peut afficher « modèle pré-rempli »
        ];
    }

    private function hydrateRecord(object $row): object
    {
        $decoded = json_decode($row->data_json ?? '{}', true) ?: [];
        $row->sections = array_values($decoded['sections'] ?? []);
        $row->notes    = (string) ($decoded['notes'] ?? '');
        $row->data     = $decoded['data'] ?? null;   // bloc dédié (checklist…)
        unset($row->data_json);
        return $row;
    }

    private function phaseTypeOf(int $phaseNum): string
    {
        return [
            1 => 'PREPARATION', 2 => 'VERIFICATION', 3 => 'CONCLUSION',
            4 => 'SUIVI',       5 => 'RECOMMANDATIONS',
        ][$phaseNum] ?? 'PREPARATION';
    }
}
