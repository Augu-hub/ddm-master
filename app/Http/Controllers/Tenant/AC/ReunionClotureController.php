<?php

namespace App\Http\Controllers\Tenant\AC;

use App\Http\Controllers\Auditor\BasePhaseFormController;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * ReunionClotureController
 *
 * Hérite de BasePhaseFormController pour récupérer l'auditeur connecté
 * via getAuditor(). Charge TOUTES les données de la MISSION (pas seulement
 * celles de l'auditeur connecté) : toute l'équipe, toutes les FRAPs,
 * toutes les fiches test, toutes les FAR.
 *
 * Tables tenant:
 *   reunion_clotures        → PV principal
 *   rc_ordre_jour           → points ODJ
 *   rc_points_forts         → points forts
 *   rc_far_validations      → FAR (Feuilles d'Audit de Résultats)
 *   rc_suivi_modalites      → modalités de suivi
 *   rc_signatures           → signatures (chef_mission, representant_audite, superviseur)
 *   fiche_observation_frap  → FRAPs source
 *   mission_phase_fiche_test → fiches de test
 *   fiche_test_ia_global    → synthèses IA
 */
class ReunionClotureController extends BasePhaseFormController
{
    // ── Requis par BasePhaseFormController ──────────────────────────────────
    protected string $table       = 'reunion_clotures';
    protected string $formCode    = 'reunion-cloture';
    protected string $codePrefix  = 'RC';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AC/ReunionCloture/Edit';
    protected string $routeEdit   = 'audit.ac.reunion-cloture.edit';
    protected array  $validationRules = [];
    protected function formData(Request $request, Auditor $auditor): array { return []; }

    // ── Tables ───────────────────────────────────────────────────────────────
    private string $conn  = 'tenant';
    private string $tRC   = 'reunion_clotures';
    private string $tODJ  = 'rc_ordre_jour';
    private string $tPF   = 'rc_points_forts';
    private string $tFAR  = 'rc_far_validations';
    private string $tSM   = 'rc_suivi_modalites';
    private string $tSIG  = 'rc_signatures';
    private string $tFT   = 'mission_phase_fiche_test';
    private string $tFRAP = 'fiche_observation_frap';

    /** 4 points ODJ réglementaires MPA 2400 – 2410-1 */
    private const ODJ_DEFAUT = [
        "Rappeler les objectifs de la mission et les objectifs d'audit (cf. Réunion d'ouverture)",
        "Présenter les points forts de l'entité auditée",
        "Faire valider les observations d'audit (FAR) — présentation et discussion des constats",
        "Présenter les modalités de suivi de la mission (plan d'action, délais, rapport)",
    ];

    private const PROGRAMMES = [
        ['table' => 'mission_phase_prog_ci',           'code' => 'PTCI',    'label' => 'Contrôle Interne'],
        ['table' => 'mission_phase_prog_conformite',   'code' => 'PTCONF',  'label' => 'Conformité'],
        ['table' => 'mission_phase_prog_marches',      'code' => 'PTMAR',   'label' => 'Marchés'],
        ['table' => 'mission_phase_prog_transactions', 'code' => 'PTTRANS', 'label' => 'Transactions'],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // UTILITAIRES
    // ─────────────────────────────────────────────────────────────────────────
    private function db() { return DB::connection($this->conn); }

    private function decodeJson(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }

    private static array $_colCache = [];
    private function hasCol(string $table, string $col): bool
    {
        $key = "{$table}.{$col}";
        if (!isset(self::$_colCache[$key])) {
            self::$_colCache[$key] = Schema::connection($this->conn)->hasColumn($table, $col);
        }
        return self::$_colCache[$key];
    }

    private function ok(array $d = []): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $d));
    }

    private function err(string $m, int $s = 422): JsonResponse
    {
        return response()->json(['success' => false, 'error' => $m], $s);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CHARGEMENT COMPLET MISSION
    // Charge TOUT : équipe complète, toutes FRAPs, toutes fiches test,
    // tout programme de travail — indépendamment de l'auditeur connecté.
    // ─────────────────────────────────────────────────────────────────────────
    private function chargerMission(int $missionId): array
    {
        // ── Mission ───────────────────────────────────────────────────────────
        $mission = $this->db()->table('mission_programmation')
            ->where('id', $missionId)
            ->select('id','code_mission','libelle','objectif','date_debut','date_fin','lieux','mission_id','status')
            ->first();

        if (!$mission) throw new \Exception("Mission {$missionId} introuvable.");

        // ── Tous les assignments de la mission ────────────────────────────────
        $allAssignIds = $this->db()
            ->table('mission_phase_assignments')
            ->where('mission_programmation_id', $missionId)
            ->pluck('id')
            ->toArray();

        // ── Équipe COMPLÈTE (tous rôles, tous auditeurs) ──────────────────────
        $equipe = [];
        if (!empty($allAssignIds)) {
            $equipe = $this->db()
                ->table('mission_phase_assignment_auditeurs as mpaa')
                ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
                ->whereIn('mpaa.assignment_id', $allAssignIds)
                ->select(
                    'a.id', 'a.first_name', 'a.last_name', 'a.email', 'a.audit_code',
                    'mpaa.role_code', 'mpaa.assignment_id',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name")
                )
                ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
                ->get()
                ->unique('id')
                ->values()
                ->map(fn($a) => [
                    'id'            => $a->id,
                    'full_name'     => trim($a->full_name),
                    'email'         => $a->email,
                    'audit_code'    => $a->audit_code,
                    'role_code'     => $a->role_code,
                    'role_label'    => match($a->role_code) {
                        'DM' => 'Directeur de Mission',
                        'CM' => 'Chef de Mission',
                        'AS' => 'Auditeur Senior',
                        'AJ' => 'Auditeur Junior',
                        default => $a->role_code,
                    },
                    'assignment_id' => $a->assignment_id,
                ])
                ->toArray();
        }

        // ── FRO (pour pré-remplissage date/lieu/participants) ─────────────────
        $fro = null;
        $froParticipants = [];
        try {
            $fro = $this->db()->table('mission_phase_fros')
                ->where('mission_id', $missionId)
                ->where('phase_code', 'P1')
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                ->orderByDesc('created_at')
                ->first();
            if ($fro) {
                $froParticipants = $this->decodeJson($fro->participants ?? null);
            }
        } catch (\Exception) {}

        // ── TOUTES les fiches de test de la mission ───────────────────────────
        $fichesTest = [];
        $ftIds      = [];
        try {
            $rows = $this->db()
                ->table($this->tFT . ' as ft')
                ->join('auditors as a', 'a.id', '=', 'ft.auditeur_id')
                ->where('ft.mission_id', $missionId)
                ->select(
                    'ft.id', 'ft.code', 'ft.validation_status', 'ft.ia_global_id',
                    'ft.assignment_id', 'ft.auditeur_id',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as auditeur_nom")
                )
                ->orderBy('ft.created_at')
                ->get();

            $fichesTest = $rows->toArray();
            $ftIds      = $rows->pluck('id')->toArray();
        } catch (\Exception) {}

        // ── TOUTES les FRAPs de la mission ────────────────────────────────────
        $fraps = [];
        try {
            $fraps = $this->db()
                ->table($this->tFRAP)
                ->where('mission_id', $missionId)
                ->whereNull('deleted_at')
                ->orderBy('rubrique')
                ->orderBy('sous_rubrique')
                ->orderBy('num_frap')
                ->get()
                ->toArray();
        } catch (\Exception) {}

        // ── IA Globales (current) ─────────────────────────────────────────────
        $iaGlobales = [];
        try {
            if (!empty($ftIds)) {
                $iaGlobales = $this->db()
                    ->table('fiche_test_ia_global')
                    ->whereIn('fiche_test_id', $ftIds)
                    ->where('is_current', 1)
                    ->orderByDesc('score_global')
                    ->get()
                    ->map(fn($r) => [
                        'id'              => $r->id,
                        'fiche_test_id'   => $r->fiche_test_id,
                        'score_global'    => $r->score_global,
                        'conclusion'      => $r->conclusion,
                        'fiabilite'       => $r->fiabilite,
                        'risques_majeurs' => $this->decodeJson($r->risques_majeurs ?? null),
                        'points_forts'    => $this->decodeJson($r->points_forts    ?? null),
                        'recommandations' => $this->decodeJson($r->recommandations ?? null),
                    ])
                    ->toArray();
            }
        } catch (\Exception) {}

        // ── Programme de travail (le premier trouvé avec objectifs) ───────────
        $programmeData = [
            'found' => false, 'objectifs' => [],
            'total_objectifs' => 0, 'total_tests' => 0,
        ];
        foreach (self::PROGRAMMES as $prog) {
            try {
                $pRow = $this->db()->table($prog['table'])
                    ->where('mission_id', $missionId)
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')
                    ->first();
                if (!$pRow) continue;
                $objectifs = array_values(array_filter(
                    $this->decodeJson($pRow->lignes ?? null),
                    fn($o) => !empty($o['tests'])
                ));
                if (!empty($objectifs)) {
                    $programmeData = [
                        'found'           => true,
                        'programme_code'  => $prog['code'],
                        'programme_label' => $prog['label'],
                        'objectifs'       => $objectifs,
                        'total_objectifs' => count($objectifs),
                        'total_tests'     => array_sum(
                            array_map(fn($o) => count($o['tests'] ?? []), $objectifs)
                        ),
                    ];
                    break;
                }
            } catch (\Exception) {}
        }

        // ── Stats niveaux contrôle interne ────────────────────────────────────
        $statsNiveaux = [];
        foreach ($fraps as $f) {
            $niv = is_object($f)
                ? ($f->niveau_controle_interne ?? 'nc')
                : ($f['niveau_controle_interne'] ?? 'nc');
            $statsNiveaux[$niv] = ($statsNiveaux[$niv] ?? 0) + 1;
        }

        $totalFraps   = count($fraps);
        $scoreIaMoyen = !empty($iaGlobales)
            ? round(
                array_sum(array_column($iaGlobales, 'score_global')) / count($iaGlobales),
                1
            )
            : null;

        $fociGrouped = $this->buildFociGrouped($fraps);

        $cm = collect($equipe)->firstWhere('role_code', 'CM');
        $dm = collect($equipe)->firstWhere('role_code', 'DM');

        return compact(
            'mission', 'equipe', 'fro', 'froParticipants',
            'fichesTest', 'fraps', 'iaGlobales', 'programmeData',
            'fociGrouped', 'statsNiveaux', 'totalFraps', 'scoreIaMoyen',
            'cm', 'dm'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GROUPAGE FRAP → arbre objectif/rubrique/sous_rubrique
    // ─────────────────────────────────────────────────────────────────────────
    private function buildFociGrouped(array $fraps): array
    {
        $tree = [];
        foreach ($fraps as $f) {
            $obj  = is_object($f) ? ($f->objectif_controle ?? 'Protection du patrimoine') : ($f['objectif_controle'] ?? 'Protection du patrimoine');
            $rub  = is_object($f) ? ($f->rubrique          ?? 'Sans rubrique')            : ($f['rubrique']          ?? 'Sans rubrique');
            $srub = is_object($f) ? ($f->sous_rubrique     ?? '')                          : ($f['sous_rubrique']     ?? '');
            $tree[$obj][$rub][$srub][] = $f;
        }
        $out = [];
        foreach ($tree as $obj => $rubriques) {
            $ra = [];
            foreach ($rubriques as $rub => $ssrubs) {
                $sa = [];
                foreach ($ssrubs as $srub => $fps) {
                    $sa[] = ['sous_rubrique' => $srub, 'fraps' => $fps];
                }
                $ra[] = ['rubrique' => $rub, 'sous_rubriques' => $sa];
            }
            $out[] = ['objectif_controle' => $obj, 'rubriques' => $ra];
        }
        return $out;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LECTURE COMPLÈTE RÉUNION (avec toutes ses sous-tables)
    // ─────────────────────────────────────────────────────────────────────────
    private function getComplete(int $id): ?object
    {
        try {
            $rc = $this->db()->table($this->tRC)->where('id', $id)->first();
            if (!$rc) return null;

            $rc->ordre_jour      = $this->db()->table($this->tODJ)
                ->where('reunion_id', $id)->orderBy('numero')->get()->toArray();
            $rc->points_forts    = $this->db()->table($this->tPF)
                ->where('reunion_id', $id)->orderBy('numero')->get()->toArray();
            $rc->far_validations = $this->db()->table($this->tFAR)
                ->where('reunion_id', $id)->orderBy('ordre')->get()->toArray();
            $rc->suivi_modalites = $this->db()->table($this->tSM)
                ->where('reunion_id', $id)->orderBy('numero')->get()->toArray();
            $rc->signatures      = $this->db()->table($this->tSIG)
                ->where('reunion_id', $id)->get()->keyBy('role')->toArray();
            $rc->participants    = $this->decodeJson($rc->participants ?? null);

            return $rc;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CODE UNIQUE RC
    // ─────────────────────────────────────────────────────────────────────────
    protected function genCode(int $missionId): string
    {
        $max = (int)($this->db()->table($this->tRC)
            ->where('mission_id', $missionId)
            ->max(DB::raw("CAST(SUBSTRING_INDEX(COALESCE(code,'RC-00-000'),'-',-1) AS UNSIGNED)")) ?? 0);
        return 'RC-' . date('y') . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IMPORT AUTO FRAPS → FAR
    // Remplace les FAR liées à une FRAP (frap_id non null).
    // Les FAR manuelles (frap_id null) sont préservées.
    // ─────────────────────────────────────────────────────────────────────────
    private function importFrapsAsFar(int $reunionId, int $missionId): array
    {
        try {
            $this->db()->table($this->tFAR)
                ->where('reunion_id', $reunionId)
                ->whereNotNull('frap_id')
                ->delete();

            $fraps = $this->db()->table($this->tFRAP)
                ->where('mission_id', $missionId)
                ->whereNull('deleted_at')
                ->orderBy('rubrique')->orderBy('sous_rubrique')->orderBy('num_frap')
                ->get();

            $ordre = (int)($this->db()->table($this->tFAR)
                ->where('reunion_id', $reunionId)->max('ordre') ?? 0);

            foreach ($fraps as $f) {
                $ordre++;
                $this->db()->table($this->tFAR)->insert([
                    'reunion_id'           => $reunionId,
                    'frap_id'              => $f->id,
                    'num_far'              => $f->num_frap
                        ?? ('FAR-' . str_pad($ordre, 3, '0', STR_PAD_LEFT)),
                    'faits'                => $f->fait_constats,
                    'problemes'            => $f->probleme,
                    'causes'               => $f->causes,
                    'impacts'              => $f->impacts,
                    'recommandations'      => $f->recommandation,
                    'date_echeance'        => $f->date_echeance,
                    'personne_responsable' => $f->personne_responsable,
                    'livrable'             => $f->livrable,
                    'ordre'                => $ordre,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        } catch (\Exception) {}

        return $this->db()->table($this->tFAR)
            ->where('reunion_id', $reunionId)
            ->orderBy('ordre')
            ->get()->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IMPORT AUTO POINTS FORTS (depuis field points_forts des FRAPs)
    // ─────────────────────────────────────────────────────────────────────────
    private function importPointsForts(int $reunionId, int $missionId): void
    {
        try {
            $fraps = $this->db()->table($this->tFRAP)
                ->where('mission_id', $missionId)
                ->whereNull('deleted_at')
                ->whereNotNull('points_forts')
                ->where('points_forts', '!=', '')
                ->get();

            $existing = $this->db()->table($this->tPF)
                ->where('reunion_id', $reunionId)
                ->pluck('libelle')
                ->map(fn($s) => strtolower(trim($s)))
                ->toArray();

            $num = (int)($this->db()->table($this->tPF)
                ->where('reunion_id', $reunionId)->max('numero') ?? 0);

            foreach ($fraps as $f) {
                $pf = trim($f->points_forts ?? '');
                if (!$pf || in_array(strtolower($pf), $existing)) continue;
                $num++;
                $this->db()->table($this->tPF)->insert([
                    'reunion_id' => $reunionId,
                    'numero'     => $num,
                    'libelle'    => $pf,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $existing[] = strtolower($pf);
            }
        } catch (\Exception) {}
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BUILD PAYLOAD INERTIA
    // ─────────────────────────────────────────────────────────────────────────
    private function buildInertiaPayload(
        array   $ctx,
        ?object $reunion,
        bool    $isNew,
        int     $missionId,
        Auditor $auditor,
        array   $urls
    ): array {
        return array_merge([
            'reunion'         => $reunion,
            'isNew'           => $isNew,
            'currentAuditeur' => [
                'id'        => $auditor->id,
                'full_name' => trim($auditor->last_name . ' ' . $auditor->first_name),
                'role_code' => $this->getRoleForMission($missionId, $auditor->id),
            ],
            'canManage'      => in_array(
                $this->getRoleForMission($missionId, $auditor->id),
                ['DM', 'CM']
            ),
            'mission'        => $ctx['mission'],
            'equipe'         => $ctx['equipe'],
            'fichesTest'     => $ctx['fichesTest'],
            'fraps'          => $ctx['fraps'],
            'fociGrouped'    => $ctx['fociGrouped'],
            'iaGlobales'     => $ctx['iaGlobales'],
            'programmeData'  => $ctx['programmeData'],
            'statsNiveaux'   => $ctx['statsNiveaux'],
            'totalFraps'     => $ctx['totalFraps'],
            'scoreIaMoyen'   => $ctx['scoreIaMoyen'],
        ], $urls);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RÔLE DE L'AUDITEUR SUR LA MISSION
    // ─────────────────────────────────────────────────────────────────────────
    private function getRoleForMission(int $missionId, int $auditorId): string
    {
        $row = $this->db()
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->first();
        return $row?->role_code ?? 'AJ';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // URLS ROUTES
    // ─────────────────────────────────────────────────────────────────────────
    private function buildUrls(int $missionId, ?int $rcId): array
    {
        return [
            'urlStore'        => route('audit.ac.reunion-cloture.store'),
            'urlUpdate'       => $rcId ? route('audit.ac.reunion-cloture.update',        $rcId) : null,
            'urlSoumettre'    => $rcId ? route('audit.ac.reunion-cloture.soumettre',     $rcId) : null,
            'urlValider'      => $rcId ? route('audit.ac.reunion-cloture.valider',       $rcId) : null,
            'urlFarStore'     => $rcId ? route('audit.ac.reunion-cloture.far.store',     $rcId) : null,
            'urlFarUpdate'    => $rcId ? route('audit.ac.reunion-cloture.far.update',    [$rcId, ':farId']) : null,
            'urlRefreshFraps' => $rcId ? route('audit.ac.reunion-cloture.refresh-fraps', $rcId) : null,
            'urlSignature'    => $rcId ? route('audit.ac.reunion-cloture.signature',     $rcId) : null,
            'backUrl'         => route('audit.ac.reunion-cloture.index', ['mission_id' => $missionId]),
        ];
    }

    // =========================================================================
    // ACTIONS PUBLIQUES
    // =========================================================================

    public function index(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId = (int)($request->input('mission_id') ?? session('mission_id', 0));
        if (!$missionId) abort(422, 'mission_id obligatoire.');

        try {
            $ctx = $this->chargerMission($missionId);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        $existing = $this->db()->table($this->tRC)
            ->where('mission_id', $missionId)
            ->orderByDesc('created_at')
            ->first();

        if ($existing) {
            return redirect()->route('audit.ac.reunion-cloture.edit', $existing->id);
        }

        $urls = $this->buildUrls($missionId, null);
        return Inertia::render(
            $this->inertiaPage,
            $this->buildInertiaPayload($ctx, null, true, $missionId, $auditor, $urls)
        );
    }

    public function edit(Request $request, int $id)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $reunion = $this->getComplete($id);
        if (!$reunion) abort(404, 'Réunion de clôture introuvable.');

        $missionId = (int)$reunion->mission_id;

        try {
            $ctx = $this->chargerMission($missionId);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        $urls = $this->buildUrls($missionId, $id);
        return Inertia::render(
            $this->inertiaPage,
            $this->buildInertiaPayload($ctx, $reunion, false, $missionId, $auditor, $urls)
        );
    }

    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId = (int)($request->input('mission_id') ?? session('mission_id', 0));
        if (!$missionId) return $this->err('mission_id obligatoire', 422);

        try {
            $ctx = $this->chargerMission($missionId);
        } catch (\Exception $e) {
            return $this->err($e->getMessage(), 500);
        }

        try {
            $fro = $ctx['fro'];
            $cm  = $ctx['cm'];
            $dm  = $ctx['dm'];

            $data = [
                'mission_id'       => $missionId,
                'code'             => $this->genCode($missionId),
                'code_mission'     => $ctx['mission']->code_mission ?? null,
                'entite'           => $ctx['mission']->lieux        ?? null,
                'intitule_mission' => $ctx['mission']->libelle      ?? null,
                'norme_mpa'        => 'MPA 2400 – 2410-1',
                'lieu'             => $fro?->lieu         ?? ($ctx['mission']->lieux ?? null),
                'date_reunion'     => $fro?->date_reunion ?? null,
                'heure_debut'      => $fro?->heure_debut  ?? null,
                'heure_fin'        => $fro?->heure_fin    ?? null,
                'preside_par'      => $cm['full_name'] ?? ($dm['full_name'] ?? null),
                'statut'           => 'draft',
                'created_by'       => $auditor->id,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
            if ($this->hasCol($this->tRC, 'assignment_id')) {
                $data['assignment_id'] = null;
            }

            $rcId = $this->db()->table($this->tRC)->insertGetId($data);

            // ODJ réglementaire MPA 2400
            foreach (self::ODJ_DEFAUT as $i => $libelle) {
                $this->db()->table($this->tODJ)->insert([
                    'reunion_id' => $rcId,
                    'numero'     => $i + 1,
                    'libelle'    => $libelle,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Points forts depuis FRO
            if ($fro && !empty($fro->points_generaux)) {
                foreach ($this->decodeJson($fro->points_generaux) as $i => $pg) {
                    $lib = is_array($pg) ? ($pg['libelle'] ?? '') : (string)$pg;
                    if (!$lib) continue;
                    $this->db()->table($this->tPF)->insert([
                        'reunion_id' => $rcId,
                        'numero'     => $i + 1,
                        'libelle'    => $lib,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            // Points forts depuis FRAPs
            $this->importPointsForts($rcId, $missionId);

            // Participants depuis FRO ou équipe complète
            $participants = $ctx['froParticipants'];
            if (empty($participants) && !empty($ctx['equipe'])) {
                $participants = array_map(fn($m) => [
                    'nom'      => $m['full_name'],
                    'fonction' => $m['role_label'],
                    'entite'   => 'Audit Interne',
                    'present'  => true,
                ], $ctx['equipe']);
            }
            if (!empty($participants)) {
                $this->db()->table($this->tRC)->where('id', $rcId)->update([
                    'participants' => json_encode($participants, JSON_UNESCAPED_UNICODE),
                    'updated_at'   => now(),
                ]);
            }

            // Toutes FRAPs → FAR
            $this->importFrapsAsFar($rcId, $missionId);

            return redirect()->route('audit.ac.reunion-cloture.edit', $rcId);

        } catch (\Exception $e) {
            return $this->err($e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $reunion = $this->db()->table($this->tRC)->where('id', $id)->first();
        if (!$reunion) return $this->err('Introuvable', 404);

        $role = $this->getRoleForMission((int)$reunion->mission_id, $auditor->id);

        if ($reunion->statut === 'validated') {
            return $this->err('PV validé — modification impossible', 403);
        }
        if ($reunion->statut === 'in_review' && !in_array($role, ['DM', 'CM'])) {
            return $this->err('En révision — seuls DM/CM peuvent modifier', 403);
        }

        $scalars = [
            'entite','intitule_mission','code_mission','norme_mpa',
            'date_reunion','heure_debut','heure_fin','lieu',
            'preside_par','secretaire_seance',
            'conclusion_generale','observations_finales',
            'date_rapport','delais_plan_action',
        ];
        $upd = array_filter($request->only($scalars), fn($v) => $v !== null);
        $upd['updated_at'] = now();
        $this->db()->table($this->tRC)->where('id', $id)->update($upd);

        if ($request->has('ordre_jour')) {
            $this->db()->table($this->tODJ)->where('reunion_id', $id)->delete();
            foreach ($request->input('ordre_jour', []) as $i => $item) {
                $this->db()->table($this->tODJ)->insert([
                    'reunion_id' => $id, 'numero' => $i + 1,
                    'libelle'    => $item['libelle'] ?? '',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if ($request->has('points_forts')) {
            $this->db()->table($this->tPF)->where('reunion_id', $id)->delete();
            foreach ($request->input('points_forts', []) as $i => $pf) {
                $this->db()->table($this->tPF)->insert([
                    'reunion_id' => $id, 'numero' => $i + 1,
                    'libelle'    => $pf['libelle'] ?? '',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if ($request->has('suivi_modalites')) {
            $this->db()->table($this->tSM)->where('reunion_id', $id)->delete();
            foreach ($request->input('suivi_modalites', []) as $i => $sm) {
                $this->db()->table($this->tSM)->insert([
                    'reunion_id'         => $id, 'numero' => $i + 1,
                    'date_rapport'       => $sm['date_rapport']       ?? null,
                    'delais_mise_oeuvre' => $sm['delais_mise_oeuvre'] ?? null,
                    'modalites_suivi'    => $sm['modalites_suivi']    ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if ($request->has('participants')) {
            $this->db()->table($this->tRC)->where('id', $id)->update([
                'participants' => json_encode(
                    $request->input('participants', []),
                    JSON_UNESCAPED_UNICODE
                ),
                'updated_at' => now(),
            ]);
        }

        return $this->ok(['reunion' => $this->getComplete($id)]);
    }

    public function storeFar(Request $request, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        if (!$this->db()->table($this->tRC)->where('id', $id)->exists()) {
            return $this->err('Réunion introuvable', 404);
        }

        $count = $this->db()->table($this->tFAR)->where('reunion_id', $id)->count();
        $farId = $this->db()->table($this->tFAR)->insertGetId([
            'reunion_id'      => $id,
            'frap_id'         => null,
            'num_far'         => 'FAR-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT),
            'faits'           => '',
            'recommandations' => '',
            'ordre'           => $count + 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        return $this->ok(['far' => $this->db()->table($this->tFAR)->where('id', $farId)->first()]);
    }

    public function updateFar(Request $request, int $id, int $farId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $far = $this->db()->table($this->tFAR)
            ->where('id', $farId)->where('reunion_id', $id)->first();
        if (!$far) return $this->err('FAR introuvable', 404);

        $allowed = [
            'acceptation', 'pertinence', 'faisabilite', 'pratique',
            'appreciation_audite', 'date_echeance', 'personne_responsable', 'livrable',
            'faits', 'problemes', 'causes', 'impacts', 'recommandations',
        ];
        $data = array_filter($request->only($allowed), fn($v) => $v !== null);
        if (empty($data)) return $this->err('Aucune donnée', 422);

        $data['updated_at'] = now();
        $this->db()->table($this->tFAR)->where('id', $farId)->update($data);
        return $this->ok(['far' => $this->db()->table($this->tFAR)->where('id', $farId)->first()]);
    }

    public function refreshFraps(Request $request, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $reunion = $this->db()->table($this->tRC)->where('id', $id)->first();
        if (!$reunion) return $this->err('Réunion introuvable', 404);

        $farList = $this->importFrapsAsFar($id, (int)$reunion->mission_id);
        return $this->ok(['far_list' => $farList, 'count' => count($farList)]);
    }

    public function signature(Request $request, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $role = $request->input('role');
        if (!in_array($role, ['chef_mission', 'representant_audite', 'superviseur'])) {
            return $this->err('Rôle invalide');
        }

        $data = [
            'reunion_id'     => $id,
            'role'           => $role,
            'nom'            => $request->input('nom'),
            'prenom'         => $request->input('prenom'),
            'fonction'       => $request->input('fonction'),
            'date_signature' => $request->input('date_signature'),
            'signature_b64'  => $request->input('signature_b64'),
            'updated_at'     => now(),
        ];

        $ex = $this->db()->table($this->tSIG)
            ->where('reunion_id', $id)->where('role', $role)->first();
        if ($ex) {
            $this->db()->table($this->tSIG)->where('id', $ex->id)->update($data);
        } else {
            $data['created_at'] = now();
            $this->db()->table($this->tSIG)->insert($data);
        }
        return $this->ok();
    }

    public function soumettre(Request $request, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $reunion = $this->db()->table($this->tRC)->where('id', $id)->first();
        if (!$reunion) return $this->err('Introuvable', 404);
        if ($reunion->statut !== 'draft') return $this->err('Statut invalide');

        $upd = ['statut' => 'in_review', 'updated_at' => now()];
        if ($this->hasCol($this->tRC, 'submitted_by')) $upd['submitted_by'] = $auditor->id;
        if ($this->hasCol($this->tRC, 'submitted_at')) $upd['submitted_at'] = now();
        $this->db()->table($this->tRC)->where('id', $id)->update($upd);

        return $this->ok(['statut' => 'in_review']);
    }

    public function valider(Request $request, int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $reunion = $this->db()->table($this->tRC)->where('id', $id)->first();
        if (!$reunion) return $this->err('Introuvable', 404);

        $role = $this->getRoleForMission((int)$reunion->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) {
            return $this->err('Seuls DM/CM peuvent valider', 403);
        }

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'validate') {
            $this->db()->table($this->tRC)->where('id', $id)->update([
                'statut'          => 'validated',
                'validated_by'    => $auditor->id,
                'validated_at'    => now(),
                'validation_note' => $note,
                'updated_at'      => now(),
            ]);
            return $this->ok(['statut' => 'validated']);
        }

        if (!$note) return $this->err('Motif de rejet obligatoire');
        $this->db()->table($this->tRC)->where('id', $id)->update([
            'statut'          => 'draft',
            'validation_note' => $note,
            'updated_at'      => now(),
        ]);
        return $this->ok(['statut' => 'draft']);
    }

    public function destroy(Request $request,int $id): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return $this->err('Non autorisé', 403);

        $reunion = $this->db()->table($this->tRC)->where('id', $id)->first();
        if (!$reunion) return $this->err('Introuvable', 404);

        $role = $this->getRoleForMission((int)$reunion->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) {
            return $this->err('Seuls DM/CM peuvent supprimer', 403);
        }

        foreach ([$this->tFAR, $this->tODJ, $this->tPF, $this->tSM, $this->tSIG] as $t) {
            $this->db()->table($t)->where('reunion_id', $id)->delete();
        }
        $this->db()->table($this->tRC)->where('id', $id)->delete();

        return $this->ok();
    }
}