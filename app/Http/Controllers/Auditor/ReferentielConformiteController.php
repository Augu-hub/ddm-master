<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Param\Auditor;
use App\Services\RccAiGeneratorService;

/**
 * RÉFÉRENTIEL DE CONTRÔLE DE CONFORMITÉ (RCC) — v5
 */
class ReferentielConformiteController extends BasePhaseFormController
{
    protected string $table = 'mission_phase_ref_conformite';
    protected string $formCode = 'referentiel-conformite';
    protected string $codePrefix = 'RCC';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ReferentielConformite';
    protected string $routeEdit = 'auditor.ac.referentiel-conformite.edit';

    protected array $validationRules = [
        'fait_par'       => 'nullable|string|max:255',
        'revue_par'      => 'nullable|string|max:255',
        'entite_auditee' => 'nullable|string|max:255',
        'exercice'       => 'nullable|string|max:20',
        'periode'        => 'nullable|string|max:50',
        'objectif'       => 'nullable|string',
    ];

    private const DISK   = 'public';
    private const ICONS  = ['📋','📊','📄','🏛','⚖','👥','💰','🔍','🛡','📑','🏗','💼','🎯','📐','🔐'];
    private const COLORS = [
        '#1e40af','#7e22ce','#0369a1','#b45309','#15803d',
        '#9f1239','#92400e','#374151','#0f766e','#c2410c',
    ];

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    protected function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->first();
        return $row?->role_code ?? 'AJ';
    }

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'fait_par'       => $request->input('fait_par'),
            'revue_par'      => $request->input('revue_par'),
            'entite_auditee' => $request->input('entite_auditee'),
            'exercice'       => $request->input('exercice'),
            'periode'        => $request->input('periode'),
            'objectif'       => $request->input('objectif'),
        ];
    }

    private function getPhaseAuditeurs(int $assignmentId): array
    {
        return DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.last_name', 'a.first_name', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'),COALESCE(LEFT(a.first_name,1),'?'))) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'full_name'  => trim($a->full_name),
                'initials'   => $a->initials,
                'role_code'  => $a->role_code,
                'role_label' => match ($a->role_code) {
                    'DM' => 'Directeur de Mission',
                    'CM' => 'Chef de Mission',
                    'AS' => 'Auditeur Senior',
                    'AJ' => 'Auditeur Junior',
                    default => $a->role_code,
                },
            ])->toArray();
    }

    /**
     * Récupère les fonctions liées à un assignment via la table assignment_functions.
     *
     * La table assignment_functions.assignment_id référence assignments.id,
     * pas mission_phase_assignments.id.
     * On passe donc par : mission_phase_assignments → assignments → assignment_functions → functions.
     */
    private function getAssignmentFonctions(int $mpaId): array
    {
        try {
            // Résoudre le assignments.id correspondant au mission_phase_assignments.id
            $assignment = DB::connection('tenant')
                ->table('assignments')
                ->where('mission_phase_assignment_id', $mpaId)
                ->first();

            // Si la colonne de liaison s'appelle différemment, on essaie une autre approche :
            // chercher l'assignment dont le code/ref correspond à la phase
            if (!$assignment) {
                // Fallback : chercher directement par l'id (si assignment_id = mpa_id dans certaines configs)
                $assignment = DB::connection('tenant')
                    ->table('assignments')
                    ->where('id', $mpaId)
                    ->first();
            }

            if (!$assignment) {
                Log::info("[RCC] Aucun assignments trouvé pour mpa_id={$mpaId}");
                return [];
            }

            $assignmentId = $assignment->id;

            // Colonnes disponibles dans functions
            $fnCols     = DB::connection('tenant')->getSchemaBuilder()->getColumnListing('functions');
            $hasName    = in_array('name', $fnCols);
            $hasCode    = in_array('code', $fnCols);
            $hasLibelle = in_array('libelle', $fnCols);

            // Colonnes disponibles dans entities
            $entCols    = DB::connection('tenant')->getSchemaBuilder()->getColumnListing('entities');
            $entHasName = in_array('name', $entCols);

            $selectFields = ['f.id', 'af.entity_id', 'af.role_label'];

            if ($hasLibelle) {
                $selectFields[] = DB::raw("COALESCE(f.libelle, CONCAT('Fonction #', f.id)) as libelle");
            } elseif ($hasName) {
                $selectFields[] = DB::raw("COALESCE(f.name, CONCAT('Fonction #', f.id)) as libelle");
            } else {
                $selectFields[] = DB::raw("CONCAT('Fonction #', f.id) as libelle");
            }

            $selectFields[] = $hasCode
                ? DB::raw("COALESCE(f.code, '') as code")
                : DB::raw("'' as code");

            $selectFields[] = $entHasName
                ? DB::raw("COALESCE(e.name, '') as entity_name")
                : DB::raw("'' as entity_name");

            $rows = DB::connection('tenant')
                ->table('assignment_functions as af')
                ->join('functions as f', 'f.id', '=', 'af.function_id')
                ->leftJoin('entities as e', 'e.id', '=', 'af.entity_id')
                ->where('af.assignment_id', $assignmentId)
                ->select($selectFields)
                ->orderBy('f.id')
                ->get();

            // Dédoublonner par function_id
            $seen   = [];
            $result = [];
            foreach ($rows as $r) {
                if (isset($seen[$r->id])) continue;
                $seen[$r->id] = true;
                $result[] = [
                    'id'          => $r->id,
                    'libelle'     => $r->libelle ?? "Fonction #{$r->id}",
                    'code'        => $r->code ?? '',
                    'role_label'  => $r->role_label ?? '',
                    'entity_id'   => $r->entity_id,
                    'entity_name' => $r->entity_name ?? '',
                ];
            }
            return $result;

        } catch (\Exception $e) {
            Log::warning('[RCC] getAssignmentFonctions: ' . $e->getMessage());
            return [];
        }
    }

    private function loadDomaines(int $rccId): array
    {
        return DB::connection('tenant')
            ->table('mission_phase_ref_conformite_domaines')
            ->where('rcc_id', $rccId)
            ->orderBy('ordre')->orderBy('id')
            ->get()
            ->map(fn($d) => [
                'id'           => $d->id,
                'rcc_id'       => $d->rcc_id,
                'code'         => $d->code,
                'libelle'      => $d->libelle,
                'description'  => $d->description,
                'icone'        => $d->icone ?? '📋',
                'couleur'      => $d->couleur ?? '#374151',
                'auditeur_id'  => $d->auditeur_id ? (int)$d->auditeur_id : null,
                'ordre'        => $d->ordre,
                'guide_fichier'=> $this->parseJsonField($d->guide_fichier),
            ])->toArray();
    }

    private function loadCriteres(int $rccId): array
    {
        return DB::connection('tenant')
            ->table('mission_phase_ref_conformite_criteres')
            ->where('rcc_id', $rccId)
            ->orderBy('domaine_id')->orderBy('ordre')->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'id'                     => $c->id,
                'domaine_id'             => $c->domaine_id,
                'rcc_id'                 => $c->rcc_id,
                'ref_controle'           => $c->ref_controle ?? '',
                'ref_reglementaire'      => $c->ref_reglementaire ?? '',
                'intitule_procedure'     => $c->intitule_procedure ?? '',
                'point_controle'         => $c->point_controle ?? '',
                'note_preuves'           => $c->note_preuves ?? '',
                'preuves_fichiers'       => $this->parseJsonField($c->preuves_fichiers, true),
                'auditeur_id'            => $c->auditeur_id ? (int)$c->auditeur_id : null,
                'ordre'                  => $c->ordre,
                'responsable_fonction_id'=> property_exists($c,'responsable_fonction_id') && $c->responsable_fonction_id
                                            ? (int)$c->responsable_fonction_id : null,
                'responsable_libre'      => property_exists($c,'responsable_libre') ? $c->responsable_libre : null,
            ])->toArray();
    }

    private function parseJsonField($field, $asArray = true)
    {
        if (empty($field)) return $asArray ? [] : null;
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return $asArray ? ($decoded ?? []) : $decoded;
        }
        return $asArray ? (array)$field : $field;
    }

    // ──────────────────────────────────────────────────────────────
    // buildPayload
    // ──────────────────────────────────────────────────────────────

    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $role           = $this->getRole($missionId, $auditor->id);
        $phaseAuditeurs = $this->getPhaseAuditeurs($assignmentId);
        $fonctions      = $this->getAssignmentFonctions($assignmentId);

        $domaines = $criteres = [];
        if ($form) {
            $domaines = $this->loadDomaines($form->id);
            $criteres = $this->loadCriteres($form->id);
        }

        $myDomaineIds = array_column(
            array_filter($domaines, fn($d) => (int)($d['auditeur_id'] ?? 0) === $auditor->id),
            'id'
        );

        $mission = DB::connection('tenant')
            ->table('mission_programmation as mp')
            ->where('mp.id', $missionId)
            ->select('mp.id','mp.code_mission','mp.libelle','mp.objectif','mp.date_debut','mp.date_fin')
            ->first();

        $rccList = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->select(['id','code','validation_status','fait_par','updated_at'])
            ->orderByDesc('created_at')
            ->get()->toArray();

        $formId = $form?->id ?? null;

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'            => $form ? (array)$form : null,
                'domaines'        => $domaines,
                'criteres'        => $criteres,
                'fonctions'       => $fonctions,
                'myDomaineIds'    => $myDomaineIds,
                'phaseAuditeurs'  => $phaseAuditeurs,
                'availableIcons'  => self::ICONS,
                'availableColors' => self::COLORS,
                'rccList'         => $rccList,
                'mission'         => $mission ? (array)$mission : null,
                'currentAuditor'  => [
                    'id'         => $auditor->id,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                    'role'       => $role,
                ],
                'canManage'        => in_array($role, ['DM','CM']),
                'urlStore'         => route('auditor.ac.referentiel-conformite.store'),
                'urlUpdate'        => $formId ? route('auditor.ac.referentiel-conformite.update', $formId) : null,
                'urlSoumettre'     => $formId ? route('auditor.ac.referentiel-conformite.soumettre', $formId) : null,
                'urlValider'       => $formId ? route('auditor.ac.referentiel-conformite.valider', $formId) : null,
                'urlStoreDomaine'  => $formId ? route('auditor.ac.referentiel-conformite.domaine.store', $formId) : null,
                'urlUpdateDomaine' => $formId ? route('auditor.ac.referentiel-conformite.domaine.update', ':id') : null,
                'urlDeleteDomaine' => $formId ? route('auditor.ac.referentiel-conformite.domaine.destroy', ':id') : null,
                'urlStoreCritere'  => $formId ? route('auditor.ac.referentiel-conformite.critere.store', $formId) : null,
                'urlUpdateCritere' => $formId ? route('auditor.ac.referentiel-conformite.critere.update', ':id') : null,
                'urlDeleteCritere' => $formId ? route('auditor.ac.referentiel-conformite.critere.destroy', ':id') : null,
                'urlUploadPreuve'  => $formId ? route('auditor.ac.referentiel-conformite.upload-preuve', $formId) : null,
                'urlDeletePreuve'  => $formId ? route('auditor.ac.referentiel-conformite.delete-preuve', $formId) : null,
                'urlUploadGuide'   => $formId ? route('auditor.ac.referentiel-conformite.upload-guide', $formId) : null,
                'urlDeleteGuide'   => $formId ? route('auditor.ac.referentiel-conformite.delete-guide', $formId) : null,
                'urlGenerateAi'    => $formId ? route('auditor.ac.referentiel-conformite.generate-ai', $formId) : null,
                'backUrl'          => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ──────────────────────────────────────────────────────────────
    // PAGES
    // ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        try {
            $auditor      = $this->getAuditor();
            if (!$auditor) abort(403);
            $missionId    = (int)($request->input('mission_id') ?? session('mission_id', 0));
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id', 0));
            if (!$missionId || !$assignmentId) abort(422, 'Contexte mission manquant.');
            $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
            if ($existing) return redirect()->route($this->routeEdit, $existing->id);
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, null));
        } catch (\Exception $e) {
            Log::error('[RCC] index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(Request $request, int $form)
    {
        try {
            $auditor = $this->getAuditor();
            if (!$auditor) abort(403);
            $row = DB::connection('tenant')->table($this->table)->where('id', $form)->firstOrFail();
            $missionId    = (int)($request->input('mission_id') ?? session('mission_id') ?? $row->mission_id);
            $assignmentId = (int)($request->input('assignment_id') ?? session('assignment_id') ?? $row->assignment_id);
            if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
            return \Inertia\Inertia::render($this->inertiaPage, $this->buildPayload($missionId, $assignmentId, $auditor, $row));
        } catch (\Exception $e) {
            Log::error('[RCC] edit: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // FORMULAIRE RCC
    // ──────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);
        $missionId    = (int)$request->input('mission_id', 0);
        $assignmentId = (int)$request->input('assignment_id', 0);
        if (!$missionId || !$assignmentId)
            return response()->json(['success' => false, 'message' => 'Contexte manquant.'], 422);
        $role = $this->getRole($missionId, $auditor->id);
        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        if (!in_array($role, ['DM','CM']))
            return response()->json(['success' => false, 'message' => 'Seuls DM/CM peuvent créer un RCC.'], 403);
        $assignment = DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending')
            return response()->json(['success' => false, 'message' => 'Phase non démarrée.'], 422);
        $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);
        $id = DB::connection('tenant')->table($this->table)->insertGetId(array_merge($this->formData($request, $auditor), [
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            'code'              => $this->genCode($missionId),
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]));
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');
        return response()->json([
            'success'  => true,
            'form'     => (array)DB::connection('tenant')->table($this->table)->find($id),
            'redirect' => route('auditor.ac.referentiel-conformite.edit', $id),
        ]);
    }

    public function update(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);
        $row = DB::connection('tenant')->table($this->table)->find($form);
        if (!$row) abort(404);
        $missionId    = (int)($request->input('mission_id') ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role = $this->getRole($missionId, $auditor->id);
        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        if (!$this->canEdit($row, $role))
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        DB::connection('tenant')->table($this->table)->where('id', $form)
            ->update(array_merge($this->formData($request, $auditor), ['updated_at' => now()]));
        return response()->json(['success' => true, 'form' => (array)DB::connection('tenant')->table($this->table)->find($form)]);
    }

    // ──────────────────────────────────────────────────────────────
    // DOMAINES
    // ──────────────────────────────────────────────────────────────

    public function storeDomaine(Request $request, int $rcc)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row) return response()->json(['error' => 'RCC introuvable'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent créer des domaines'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'RCC validé, modification impossible'], 403);
        $libelle = trim($request->input('libelle', ''));
        if (!$libelle) return response()->json(['error' => 'Libellé obligatoire'], 422);
        $count = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('rcc_id', $rcc)->count();
        $code  = strtoupper(trim($request->input('code', ''))) ?: ('DOM-' . str_pad($count + 1, 2, '0', STR_PAD_LEFT));
        $id    = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->insertGetId([
            'rcc_id'      => $rcc,
            'code'        => $code,
            'libelle'     => $libelle,
            'description' => $request->input('description'),
            'icone'       => $request->input('icone', '📋'),
            'couleur'     => $request->input('couleur', self::COLORS[$count % count(self::COLORS)]),
            'auditeur_id' => $request->input('auditeur_id') ?: null,
            'guide_fichier' => null,
            'ordre'       => $count,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        $d = (array)DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->find($id);
        $d['guide_fichier'] = null;
        return response()->json(['success' => true, 'domaine' => $d, 'message' => "Domaine «{$libelle}» créé."]);
    }

    public function updateDomaine(Request $request, int $domaine)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->find($domaine);
        if (!$row) return response()->json(['error' => 'Domaine introuvable'], 404);
        $rcc  = DB::connection('tenant')->table($this->table)->find($row->rcc_id);
        $role = $this->getRole((int)$rcc->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent modifier'], 403);
        if ($rcc->validation_status === 'validated') return response()->json(['error' => 'RCC validé, modification impossible'], 403);
        $upd = array_filter([
            'libelle'     => $request->input('libelle'),
            'description' => $request->input('description'),
            'icone'       => $request->input('icone'),
            'couleur'     => $request->input('couleur'),
            'auditeur_id' => $request->input('auditeur_id') ?: null,
            'code'        => $request->input('code') ? strtoupper($request->input('code')) : null,
            'updated_at'  => now(),
        ], fn($v) => $v !== null);
        DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaine)->update($upd);
        $d = (array)DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->find($domaine);
        $d['guide_fichier'] = $this->parseJsonField($d['guide_fichier'] ?? null);
        return response()->json(['success' => true, 'domaine' => $d]);
    }

    public function destroyDomaine(Request $request, int $domaine)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->find($domaine);
        if (!$row) return response()->json(['error' => 'Domaine introuvable'], 404);
        $rcc  = DB::connection('tenant')->table($this->table)->find($row->rcc_id);
        $role = $this->getRole((int)$rcc->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        if ($rcc->validation_status === 'validated') return response()->json(['error' => 'RCC validé, suppression impossible'], 403);
        if ($row->guide_fichier) {
            $gf = $this->parseJsonField($row->guide_fichier, false);
            if (!empty($gf['path'])) Storage::disk(self::DISK)->delete($gf['path']);
        }
        foreach (DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('domaine_id', $domaine)->get() as $c) {
            foreach ($this->parseJsonField($c->preuves_fichiers) as $f) {
                if (!empty($f['path'])) Storage::disk(self::DISK)->delete($f['path']);
            }
        }
        DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('domaine_id', $domaine)->delete();
        DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaine)->delete();
        return response()->json(['success' => true, 'message' => 'Domaine supprimé.']);
    }

    // ──────────────────────────────────────────────────────────────
    // GUIDE
    // ──────────────────────────────────────────────────────────────

    public function uploadGuide(Request $request, int $rcc)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $request->validate(['domaine_id' => 'required|integer', 'file' => 'required|file|mimes:pdf,xlsx,xls,docx,doc,txt|max:20480']);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row || $row->validation_status === 'validated') return response()->json(['error' => 'Non autorisé'], 403);
        $domaineId = (int)$request->input('domaine_id');
        $domaine   = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaineId)->where('rcc_id', $rcc)->first();
        if (!$domaine) return response()->json(['error' => 'Domaine introuvable'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM']) && (int)($domaine->auditeur_id ?? 0) !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        if ($domaine->guide_fichier) {
            $old = $this->parseJsonField($domaine->guide_fichier, false);
            if (!empty($old['path'])) Storage::disk(self::DISK)->delete($old['path']);
        }
        $file   = $request->file('file');
        $stored = $file->store("rcc/{$rcc}/guides/{$domaineId}", self::DISK);
        $info   = ['name' => $file->getClientOriginalName(), 'path' => $stored, 'url' => Storage::disk(self::DISK)->url($stored), 'size' => $file->getSize(), 'mime' => $file->getMimeType(), 'uploaded_at' => now()->toISOString(), 'uploaded_by' => $auditor->id];
        DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaineId)->update(['guide_fichier' => json_encode($info, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        return response()->json(['success' => true, 'fichier' => $info, 'message' => "Guide «{$info['name']}» joint."]);
    }

    public function deleteGuide(Request $request, int $rcc)
    {
        $auditor   = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $domaineId = (int)$request->input('domaine_id', 0);
        if (!$domaineId) return response()->json(['error' => 'domaine_id manquant'], 422);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row || $row->validation_status === 'validated') return response()->json(['error' => 'Non autorisé'], 403);
        $domaine = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaineId)->where('rcc_id', $rcc)->first();
        if (!$domaine || !$domaine->guide_fichier) return response()->json(['error' => 'Aucun guide'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM']) && (int)($domaine->auditeur_id ?? 0) !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        $gf = $this->parseJsonField($domaine->guide_fichier, false);
        if (!empty($gf['path'])) Storage::disk(self::DISK)->delete($gf['path']);
        DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaineId)->update(['guide_fichier' => null, 'updated_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Guide supprimé.']);
    }

    // ──────────────────────────────────────────────────────────────
    // CRITÈRES
    // ──────────────────────────────────────────────────────────────

    public function storeCritere(Request $request, int $rcc)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row || $row->validation_status === 'validated') return response()->json(['error' => 'Non autorisé'], 403);
        $role      = $this->getRole((int)$row->mission_id, $auditor->id);
        $domaineId = (int)$request->input('domaine_id', 0);
        $domaine   = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaineId)->where('rcc_id', $rcc)->first();
        if (!$domaine) return response()->json(['error' => 'Domaine introuvable'], 404);
        if (!in_array($role, ['DM','CM']) && (int)($domaine->auditeur_id ?? 0) !== $auditor->id)
            return response()->json(['error' => 'Non affecté à ce domaine'], 403);
        $intitule = trim($request->input('intitule_procedure', '')) ?: 'Nouveau critère';
        $count    = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('domaine_id', $domaineId)->count();
        $id       = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->insertGetId([
            'domaine_id'              => $domaineId,
            'rcc_id'                  => $rcc,
            'ref_controle'            => $request->input('ref_controle') ?? ($domaine->code . '-C' . str_pad($count + 1, 2, '0', STR_PAD_LEFT)),
            'ref_reglementaire'       => $request->input('ref_reglementaire'),
            'intitule_procedure'      => $intitule,
            'point_controle'          => $request->input('point_controle'),
            'note_preuves'            => $request->input('note_preuves'),
            'preuves_fichiers'        => '[]',
            'auditeur_id'             => $auditor->id,
            'ordre'                   => $count,
            'responsable_fonction_id' => $request->input('responsable_fonction_id'),
            'responsable_libre'       => $request->input('responsable_libre'),
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
        $c = (array)DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->find($id);
        $c['preuves_fichiers'] = [];
        return response()->json(['success' => true, 'critere' => $c, 'message' => 'Critère ajouté.']);
    }

    public function updateCritere(Request $request, int $critere)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->find($critere);
        if (!$row) return response()->json(['error' => 'Critère introuvable'], 404);
        $rcc  = DB::connection('tenant')->table($this->table)->find($row->rcc_id);
        $role = $this->getRole((int)$rcc->mission_id, $auditor->id);
        if ($rcc->validation_status === 'validated') return response()->json(['error' => 'RCC validé, modification impossible'], 403);
        if (!in_array($role, ['DM','CM']) && (int)$row->auditeur_id !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('id', $critere)->update([
            'ref_controle'            => $request->input('ref_controle', $row->ref_controle),
            'ref_reglementaire'       => $request->input('ref_reglementaire', $row->ref_reglementaire),
            'intitule_procedure'      => $request->input('intitule_procedure', $row->intitule_procedure),
            'point_controle'          => $request->input('point_controle', $row->point_controle),
            'note_preuves'            => $request->input('note_preuves', $row->note_preuves),
            'responsable_fonction_id' => $request->input('responsable_fonction_id', $row->responsable_fonction_id ?? null),
            'responsable_libre'       => $request->input('responsable_libre', $row->responsable_libre ?? null),
            'updated_at'              => now(),
        ]);
        $upd = (array)DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->find($critere);
        $upd['preuves_fichiers'] = $this->parseJsonField($upd['preuves_fichiers'] ?? null);
        return response()->json(['success' => true, 'critere' => $upd]);
    }

    public function destroyCritere(Request $request, int $critere)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->find($critere);
        if (!$row) return response()->json(['error' => 'Critère introuvable'], 404);
        $rcc  = DB::connection('tenant')->table($this->table)->find($row->rcc_id);
        $role = $this->getRole((int)$rcc->mission_id, $auditor->id);
        if ($rcc->validation_status === 'validated') return response()->json(['error' => 'RCC validé, suppression impossible'], 403);
        if (!in_array($role, ['DM','CM']) && (int)$row->auditeur_id !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        foreach ($this->parseJsonField($row->preuves_fichiers) as $f) {
            if (!empty($f['path'])) Storage::disk(self::DISK)->delete($f['path']);
        }
        DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('id', $critere)->delete();
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // PREUVES
    // ──────────────────────────────────────────────────────────────

    public function uploadPreuve(Request $request, int $rcc)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $request->validate(['critere_id' => 'required|integer', 'file' => 'required|file|mimes:pdf,xlsx,xls,docx,doc,png,jpg,jpeg|max:10240']);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row || $row->validation_status === 'validated') return response()->json(['error' => 'Non autorisé'], 403);
        $critereId = (int)$request->input('critere_id');
        $critere   = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('id', $critereId)->where('rcc_id', $rcc)->first();
        if (!$critere) return response()->json(['error' => 'Critère introuvable'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM']) && (int)$critere->auditeur_id !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        $file     = $request->file('file');
        $stored   = $file->store("rcc/{$rcc}/{$critereId}", self::DISK);
        $info     = ['name' => $file->getClientOriginalName(), 'path' => $stored, 'url' => Storage::disk(self::DISK)->url($stored), 'size' => $file->getSize(), 'mime' => $file->getMimeType(), 'uploaded_at' => now()->toISOString(), 'uploaded_by' => $auditor->id];
        $existing = $this->parseJsonField($critere->preuves_fichiers);
        $existing[] = $info;
        DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('id', $critereId)->update(['preuves_fichiers' => json_encode($existing, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        return response()->json(['success' => true, 'fichier' => $info, 'message' => "Fichier '{$info['name']}' joint."]);
    }

    public function deletePreuve(Request $request, int $rcc)
    {
        $auditor   = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $critereId = (int)$request->input('critere_id');
        $path      = $request->input('path');
        if (!$critereId || !$path) return response()->json(['error' => 'Paramètres manquants'], 422);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row || $row->validation_status === 'validated') return response()->json(['error' => 'Non autorisé'], 403);
        $critere = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('id', $critereId)->where('rcc_id', $rcc)->first();
        if (!$critere) return response()->json(['error' => 'Critère introuvable'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM']) && (int)$critere->auditeur_id !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        Storage::disk(self::DISK)->delete($path);
        $fichiers = array_values(array_filter($this->parseJsonField($critere->preuves_fichiers), fn($f) => $f['path'] !== $path));
        DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('id', $critereId)->update(['preuves_fichiers' => json_encode($fichiers, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // GÉNÉRATION IA
    // ──────────────────────────────────────────────────────────────

    public function generateAi(Request $request, int $rcc)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table($this->table)->find($rcc);
        if (!$row || $row->validation_status === 'validated') return response()->json(['error' => 'Non autorisé'], 403);
        $domaineId = (int)$request->input('domaine_id', 0);
        $domaine   = DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('id', $domaineId)->where('rcc_id', $rcc)->first();
        if (!$domaine) return response()->json(['error' => 'Domaine introuvable'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM']) && (int)($domaine->auditeur_id ?? 0) !== $auditor->id)
            return response()->json(['error' => 'Accès refusé'], 403);
        $nbCriteres = (int)max(1, min(20, $request->input('nb_criteres', 5)));
        $mode       = $request->input('mode', 'review');
        $guideInfo  = null;
        if ($domaine->guide_fichier) {
            $gf  = $this->parseJsonField($domaine->guide_fichier, false);
            $abs = Storage::disk(self::DISK)->path($gf['path'] ?? '');
            if (file_exists($abs)) $guideInfo = ['path' => $abs, 'name' => $gf['name'] ?? '', 'mime' => $gf['mime'] ?? ''];
        }
        $mission = DB::connection('tenant')->table('mission_programmation')->find($row->mission_id);
        $result  = app(RccAiGeneratorService::class)->generateCriteres(
            domaineLibelle: $domaine->libelle,
            domaineCode: $domaine->code,
            domaineDesc: $domaine->description ?? '',
            entiteAuditee: $row->entite_auditee ?? '',
            objectifRcc: $row->objectif ?? '',
            missionLibelle: $mission?->libelle ?? '',
            contextNote: $request->input('context_note', ''),
            nbCriteres: $nbCriteres,
            guideInfo: $guideInfo,
        );
        if (!$result['success']) return response()->json(['success' => false, 'error' => $result['error'] ?? 'Erreur IA'], 500);
        $offset = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('domaine_id', $domaineId)->count();
        foreach ($result['criteres'] as &$c) {
            if (empty($c['ref_controle'])) {
                $offset++;
                $c['ref_controle'] = $domaine->code . '-C' . str_pad($offset, 2, '0', STR_PAD_LEFT);
            }
        }
        unset($c);
        if ($mode === 'auto') {
            $inserted = [];
            foreach ($result['criteres'] as $c) {
                $cnt = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('domaine_id', $domaineId)->count();
                $id  = DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->insertGetId([
                    'domaine_id' => $domaineId, 'rcc_id' => $rcc,
                    'ref_controle' => $c['ref_controle'] ?? $domaine->code . '-C' . str_pad($cnt + 1, 2, '0', STR_PAD_LEFT),
                    'ref_reglementaire' => $c['ref_reglementaire'] ?? null,
                    'intitule_procedure' => $c['intitule_procedure'] ?? 'À compléter',
                    'point_controle' => $c['point_controle'] ?? null,
                    'note_preuves' => null, 'preuves_fichiers' => '[]',
                    'auditeur_id' => $auditor->id, 'ordre' => $cnt,
                    'responsable_fonction_id' => $c['responsable_fonction_id'] ?? null,
                    'responsable_libre' => $c['responsable_libre'] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $arr = (array)DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->find($id);
                $arr['preuves_fichiers'] = [];
                $inserted[] = $arr;
            }
            return response()->json(['success' => true, 'criteres' => $inserted, 'message' => count($inserted) . ' critère(s) insérés.']);
        }
        return response()->json(['success' => true, 'criteres' => $result['criteres'], 'message' => count($result['criteres']) . ' suggestion(s).']);
    }

    // ──────────────────────────────────────────────────────────────
    // WORKFLOW
    // ──────────────────────────────────────────────────────────────

    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table($this->table)->find($form);
        if (!$row) return response()->json(['error' => 'Introuvable'], 404);
        $missionId    = (int)($request->input('mission_id') ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) return response()->json(['error' => 'Accès refusé'], 403);
        if (in_array($row->validation_status, ['validated','in_review'])) return response()->json(['error' => 'Statut incompatible'], 422);
        DB::connection('tenant')->table($this->table)->where('id', $form)->update(['validation_status' => 'in_review', 'submitted_at' => now(), 'submitted_by' => $auditor->id, 'updated_at' => now()]);
        $this->log($assignmentId, $auditor->id, $this->getRole($missionId, $auditor->id), 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table($this->table)->find($form);
        if (!$row) return response()->json(['error' => 'Introuvable'], 404);
        $missionId    = (int)($request->input('mission_id') ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);
        if (!in_array($role, ['DM','CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') return response()->json(['error' => 'Non soumis'], 422);
        $action = $request->input('action', 'validate');
        $note   = $request->input('note');
        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $form)->update(['validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now()]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }
        if ($role !== 'DM') return response()->json(['error' => 'Seul le Directeur de Mission peut valider définitivement'], 403);
        DB::connection('tenant')->table($this->table)->where('id', $form)->update(['validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now()]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update(['validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => $auditor->id, 'updated_at' => now()]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    public function destroy(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);
        $row = DB::connection('tenant')->table($this->table)->find($form);
        if (!$row) return response()->json(['error' => 'Introuvable'], 404);
        $role = $this->getRole((int)$row->mission_id, $auditor->id);
        if (!in_array($role, ['DM','CM']) || $row->validation_status === 'validated')
            return response()->json(['error' => 'Non autorisé'], 403);
        foreach (DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('rcc_id', $form)->get() as $d) {
            if ($d->guide_fichier) { $gf = $this->parseJsonField($d->guide_fichier, false); if (!empty($gf['path'])) Storage::disk(self::DISK)->delete($gf['path']); }
        }
        foreach (DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('rcc_id', $form)->get() as $c) {
            foreach ($this->parseJsonField($c->preuves_fichiers) as $f) { if (!empty($f['path'])) Storage::disk(self::DISK)->delete($f['path']); }
        }
        DB::connection('tenant')->table('mission_phase_ref_conformite_criteres')->where('rcc_id', $form)->delete();
        DB::connection('tenant')->table('mission_phase_ref_conformite_domaines')->where('rcc_id', $form)->delete();
        DB::connection('tenant')->table($this->table)->where('id', $form)->delete();
        $this->log((int)$row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);
        return response()->json(['success' => true]);
    }
}