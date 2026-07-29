<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * DIADDEM — Module audit.core
 * Contrôleur principal des missions de l'auditeur connecté
 *
 * Routes à déclarer dans routes/web.php (groupe middleware ['web','auth','audit.session']) :
 *
 *   Route::prefix('m/audit.core/auditor')->group(function () {
 *       Route::get('missions',                                       [AuditorMissionsController::class, 'index'])
 *            ->name('auditor.missions.index');
 *       Route::get('missions/{id}/phases',                          [AuditorMissionsController::class, 'phases'])
 *            ->name('auditor.missions.phases');
 *       Route::get('missions/{id}/gantt',                           [AuditorMissionsController::class, 'gantt'])
 *            ->name('auditor.missions.gantt');
 *       Route::post('missions/{id}/start',                          [AuditorMissionsController::class, 'start'])
 *            ->name('auditor.missions.start');
 *       Route::post('missions/{id}/phases/{assignment}/start',      [AuditorMissionsController::class, 'startPhase'])
 *            ->name('auditor.missions.phases.start');
 *   });
 *
 * ══════════════════════════════════════════════════════════════════════════════
 */

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Concerns\BuildsMissionMenu;
use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AuditorMissionsController extends Controller
{
    use BuildsMissionMenu;

    // ══════════════════════════════════════════════════════════════════════════
    // HELPER — Construire une URL absolue correcte depuis un url_path
    //
    // PROBLÈME : url('m/audit.core/...') sans slash initial → Laravel coupe le
    // préfixe et génère /reunion-lancement au lieu de /m/audit.core/ac/.../reunion-lancement
    //
    // SOLUTION : forcer le slash initial avec ltrim puis url('/' . $path)
    // ══════════════════════════════════════════════════════════════════════════

    private function buildUrl(string $path, array $params = []): string
    {
        // Garantir le slash initial pour que url() génère le chemin complet
        $base = url('/' . ltrim($path, '/'));

        if (empty($params)) {
            return $base;
        }

        return $base . '?' . http_build_query($params);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Auditeur connecté (depuis session ou email Auth)
    // ══════════════════════════════════════════════════════════════════════════

    private function getConnectedAuditor(): ?Auditor
    {
        $auditorId = Session::get('auditor_id');
        if ($auditorId) {
            $auditor = Auditor::with(['user', 'entity'])->find($auditorId);
            if ($auditor) return $auditor;
        }

        $user = Auth::user();
        if (!$user) return null;

        $auditor = Auditor::with(['user', 'entity'])
            ->where('email', $user->email)
            ->where('status', 'active')
            ->first();

        if ($auditor) Session::put('auditor_id', $auditor->id);
        return $auditor;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // INDEX — Page "Mes Missions"
    // ══════════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $auditor = $this->getConnectedAuditor();
        if (!$auditor) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Compte auditeur introuvable ou inactif.']);
        }

        // ── Affectations de l'auditeur ────────────────────────────────────────
        $affectations = DB::table('mission_phase_auditeurs as mpa')
            ->select([
                'mpa.id as id', 'mpa.mission_id', 'mpa.entites',
                'mp.code_mission', 'mp.libelle', 'mp.objectif',
                'mp.date_debut', 'mp.date_fin', 'mp.lieux', 'mp.status',
                DB::raw("COALESCE(mr.code, mpa.role, '—') as mon_role"),
                DB::raw("COALESCE(mr.libelle, mpa.role, '—') as role_libelle"),
                DB::raw("DATE_FORMAT(mp.date_debut, '%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin, '%d/%m/%Y') as date_fin_fr"),
                DB::raw("DATEDIFF(mp.date_fin, mp.date_debut) + 1 as duree"),
                DB::raw("CASE
                    WHEN mp.status='terminee'    THEN 100
                    WHEN mp.status='annulee'     THEN 0
                    WHEN mp.date_debut>CURDATE() THEN 0
                    WHEN mp.date_fin<CURDATE() AND mp.status!='terminee' THEN 99
                    WHEN DATEDIFF(mp.date_fin,mp.date_debut)=0 THEN 100
                    ELSE ROUND(LEAST(
                        (DATEDIFF(CURDATE(),mp.date_debut) / NULLIF(DATEDIFF(mp.date_fin,mp.date_debut),0)) * 100,
                    99),0)
                END as progression"),
                DB::raw("(SELECT COALESCE(SUM(montant),0) FROM mission_auditeur_budget_lines WHERE affectation_id=mpa.id) as budget_individuel"),
            ])
            ->join('mission_programmation as mp', 'mpa.mission_id', '=', 'mp.id')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.auditeur_id', $auditor->id)
            ->groupBy(
                'mpa.id','mpa.mission_id','mpa.entites','mp.code_mission','mp.libelle','mp.objectif',
                'mp.date_debut','mp.date_fin','mp.lieux','mp.status','mr.code','mpa.role','mr.libelle'
            )
            ->orderByRaw("FIELD(mp.status,'en_cours','planifiee','terminee','annulee')")
            ->orderBy('mp.date_debut', 'asc')
            ->get();

        $missionIds     = $affectations->pluck('mission_id')->unique()->filter()->toArray();
        $affectationIds = $affectations->pluck('id')->toArray();

        // ── Périodes par entité ────────────────────────────────────────────────
        $entityPeriodsParMission = [];
        if (!empty($missionIds)) {
            DB::table('mission_programmation_entity as mpe')
                ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
                ->whereIn('mpe.mission_programmation_id', $missionIds)
                ->select([
                    'mpe.mission_programmation_id as mission_id', 'mpe.entity_id', 'e.name as entity_name',
                    'mpe.date_debut', 'mpe.date_fin',
                    DB::raw("DATE_FORMAT(mpe.date_debut,'%d/%m/%Y') as date_debut_fr"),
                    DB::raw("DATE_FORMAT(mpe.date_fin,'%d/%m/%Y') as date_fin_fr"),
                ])
                ->orderBy('e.name')
                ->get()
                ->each(function ($row) use (&$entityPeriodsParMission) {
                    $entityPeriodsParMission[$row->mission_id][$row->entity_id] = [
                        'entity_id'     => (int)$row->entity_id,
                        'entity_name'   => $row->entity_name,
                        'date_debut'    => $row->date_debut,
                        'date_fin'      => $row->date_fin,
                        'date_debut_fr' => $row->date_debut_fr,
                        'date_fin_fr'   => $row->date_fin_fr,
                    ];
                });
        }

        // ── Lignes expandées par entité ───────────────────────────────────────
        $affectationEntities = [];
        foreach ($affectations as $aff) {
            $entiteIds = json_decode($aff->entites ?? '[]', true);
            if (empty($entiteIds)) $entiteIds = array_keys($entityPeriodsParMission[$aff->mission_id] ?? []);
            if (empty($entiteIds)) {
                $affectationEntities[] = $this->buildAffectationEntity($aff, null, null);
            } else {
                foreach ($entiteIds as $eid) {
                    $ep = $entityPeriodsParMission[$aff->mission_id][(int)$eid] ?? null;
                    $affectationEntities[] = $this->buildAffectationEntity($aff, (int)$eid, $ep);
                }
            }
        }

        // ── Libellé entités ───────────────────────────────────────────────────
        foreach ($affectations as $aff) {
            $entiteIds = json_decode($aff->entites ?? '[]', true);
            if (empty($entiteIds)) {
                $noms = array_column($entityPeriodsParMission[$aff->mission_id] ?? [], 'entity_name');
            } else {
                $noms = [];
                foreach ($entiteIds as $eid) {
                    $name = $entityPeriodsParMission[$aff->mission_id][(int)$eid]['entity_name'] ?? null;
                    if ($name) $noms[] = $name;
                }
            }
            $aff->entities_list = implode(', ', $noms) ?: '—';
        }

        // ── Équipes par mission ───────────────────────────────────────────────
        $equipesParMission = [];
        if (!empty($missionIds)) {
            DB::table('mission_phase_auditeurs as mpa')
                ->select([
                    'mpa.mission_id', 'a.id as auditeur_id', 'a.audit_code', 'a.first_name', 'a.last_name', 'a.avatar',
                    DB::raw("COALESCE(mr.code, mpa.role,'—') as role"),
                    DB::raw("COALESCE(mr.libelle, mpa.role,'—') as role_libelle"),
                ])
                ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
                ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
                ->whereIn('mpa.mission_id', $missionIds)
                ->orderByRaw('COALESCE(mr.niveau,99) ASC')
                ->orderBy('a.last_name')
                ->get()
                ->each(function ($m) use (&$equipesParMission, $auditor) {
                    $mid = $m->mission_id;
                    if (!isset($equipesParMission[$mid])) $equipesParMission[$mid] = ['total' => 0, 'membres' => []];
                    $equipesParMission[$mid]['membres'][] = [
                        'auditeur_id'  => $m->auditeur_id,
                        'audit_code'   => $m->audit_code,
                        'first_name'   => $m->first_name,
                        'last_name'    => $m->last_name,
                        'avatar'       => $m->avatar,
                        'role'         => $m->role,
                        'role_libelle' => $m->role_libelle,
                        'is_me'        => $m->auditeur_id === $auditor->id,
                    ];
                    $equipesParMission[$mid]['total']++;
                });
        }

        // ── Lignes budget ─────────────────────────────────────────────────────
        $budgetLignes = [];
        if (!empty($affectationIds)) {
            DB::table('mission_auditeur_budget_lines as mabl')
                ->leftJoin('mission_budget_categories as mbc', 'mabl.category_id', '=', 'mbc.id')
                ->leftJoin('entities as e', 'mabl.entity_id', '=', 'e.id')
                ->whereIn('mabl.affectation_id', $affectationIds)
                ->select([
                    'mabl.affectation_id', 'mabl.montant', 'e.name as entity_name',
                    DB::raw("COALESCE(mbc.libelle, mabl.custom_label,'Divers') as libelle"),
                ])
                ->orderBy('mabl.id')
                ->get()
                ->each(function ($row) use (&$budgetLignes) {
                    $budgetLignes[$row->affectation_id][] = [
                        'libelle'     => $row->libelle,
                        'montant'     => (float)$row->montant,
                        'entity_name' => $row->entity_name ?? '—',
                    ];
                });
        }

        // ── Stats globales ────────────────────────────────────────────────────
        $stats = [
            'mes_missions'     => $affectations->count(),
            'en_cours'         => $affectations->where('status', 'en_cours')->count(),
            'planifiees'       => $affectations->where('status', 'planifiee')->count(),
            'terminees'        => $affectations->where('status', 'terminee')->count(),
            'annulees'         => $affectations->where('status', 'annulee')->count(),
            'jours_total'      => (int) $affectations->sum('duree'),
            'budget_total'     => (float) $affectations->sum('budget_individuel'),
            'taux_realisation' => $affectations->count() > 0
                ? (int)round($affectations->where('status', 'terminee')->count() / $affectations->count() * 100) : 0,
        ];

        return Inertia::render('dashboards/Auditor/MesMissions', [
            'auditor'             => $this->buildAuditorPayload($auditor),
            'affectations'        => $affectations->values(),
            'affectationEntities' => array_values($affectationEntities),
            'equipesParMission'   => $equipesParMission,
            'budgetLignes'        => $budgetLignes,
            'stats'               => $stats,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PHASES — Page détail des phases d'une mission
    // ══════════════════════════════════════════════════════════════════════════

    public function phases(Request $request, int $missionId)
    {
        $auditor = $this->getConnectedAuditor();
        if (!$auditor) return redirect()->route('login');

        $affectation = DB::table('mission_phase_auditeurs')
            ->where('mission_id', $missionId)
            ->where('auditeur_id', $auditor->id)
            ->first();
        if (!$affectation) abort(403, 'Mission non accessible');

        $mission = $this->getMissionWithColor($missionId);
        if (!$mission) abort(404);

        // Rôle de l'auditeur sur cette mission
        // CORRECTION : COALESCE peut renvoyer NULL (affectation sans rôle) —
        // buildPhasesByType() exige un string → repli '—' (niveau 9).
        $monRole = DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionId)
            ->where('mpa.auditeur_id', $auditor->id)
            ->value(DB::raw("COALESCE(mr.code, mpa.role)")) ?? '—';

        $roleNiveaux = ['DM' => 1, 'CM' => 2, 'AS' => 3, 'AJ' => 4];
        $monNiveau   = $roleNiveaux[$monRole] ?? 9;

        $equipe       = $this->getEquipe($missionId, $auditor->id);
        $entities     = $this->getEntities($missionId);
        $phasesByType = $this->buildPhasesByType($missionId, $auditor->id, $monRole, $monNiveau, $mission->audit_color);
        $markingsData = $this->loadMarkings($missionId, $auditor->id, $monRole, $monNiveau);
        $chatMessages = $this->loadChatMessages($missionId, $auditor->id);

        return Inertia::render('dashboards/Auditor/MissionPhases', [
            'mission'       => $mission,
            'phasesByType'  => $phasesByType,
            'entities'      => $entities,
            'equipe'        => array_values($equipe),
            'markingsData'  => $markingsData,
            'chatMessages'  => $chatMessages,
            // Menu latéral "Mission en cours" (phases depuis ddmparam) —
            // même navigation que sur les pages de formulaire.
            'missionMenu'   => $this->buildMissionMenu($missionId),
            'auditor'       => array_merge($this->buildAuditorPayload($auditor), ['role' => $monRole]),
            // URLs pré-construites avec slash initial garanti → préfixe correct
            'chatBaseUrl'   => $this->buildUrl("m/audit.core/missions/{$missionId}/chat"),
            'missionsUrl'   => $this->buildUrl('m/audit.core/auditor/missions'),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GANTT — API JSON
    // ══════════════════════════════════════════════════════════════════════════

    public function gantt(Request $request, int $missionId)
    {
        $auditor = $this->getConnectedAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        if (!DB::table('mission_phase_auditeurs')
                ->where('mission_id', $missionId)
                ->where('auditeur_id', $auditor->id)
                ->exists())
            return response()->json(['error' => 'Mission non accessible'], 403);

        $mission = $this->getMissionWithColor($missionId);
        if (!$mission) return response()->json(['error' => 'Mission introuvable'], 404);

        // Provision auto des phases depuis ddmparam (idempotent, cache 5 min)
        \App\Services\Audit\PhaseSyncService::ensureMissionAssignments($missionId);

        $monRole   = DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionId)
            ->where('mpa.auditeur_id', $auditor->id)
            ->value(DB::raw("COALESCE(mr.code, mpa.role)")) ?? '—'; // jamais NULL (TypeError sinon)
        $monNiveau = ['DM' => 1, 'CM' => 2, 'AS' => 3, 'AJ' => 4][$monRole] ?? 9;

        $phasesByType = $this->buildPhasesByType($missionId, $auditor->id, $monRole, $monNiveau, $mission->audit_color);
        $entities     = $this->getEntities($missionId);
        $equipe       = $this->getEquipe($missionId, $auditor->id);
        $allPhases    = collect($phasesByType)->flatMap(fn($g) => $g['phases']);

        return response()->json([
            'mission'        => $mission,
            'phases_by_type' => $phasesByType,
            'entities'       => $entities,
            'equipe'         => $equipe,
            'stats'          => [
                'total_phases'    => $allPhases->count(),
                'completed'       => $allPhases->where('phase_status', 'completed')->count(),
                'in_progress'     => $allPhases->where('phase_status', 'in_progress')->count(),
                'pending'         => $allPhases->where('phase_status', 'pending')->count(),
                'skipped'         => $allPhases->where('phase_status', 'skipped')->count(),
                'progression_moy' => $allPhases->count() > 0
                    ? (int)round($allPhases->avg('progression')) : 0,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // START — Démarrer une mission
    // ══════════════════════════════════════════════════════════════════════════

    public function start(Request $request, int $missionId)
    {
        $auditor = $this->getConnectedAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        if (!DB::table('mission_phase_auditeurs')
                ->where('mission_id', $missionId)
                ->where('auditeur_id', $auditor->id)
                ->first())
            return response()->json(['error' => 'Mission non trouvée'], 404);

        DB::table('mission_programmation')
            ->where('id', $missionId)
            ->update(['status' => 'en_cours', 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // START PHASE — Démarrer une phase spécifique
    // ══════════════════════════════════════════════════════════════════════════

    public function startPhase(Request $request, int $missionId, int $assignmentId)
    {
        $auditor = $this->getConnectedAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $assignment = DB::table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment) return response()->json(['error' => 'Phase introuvable'], 404);

        $isAffected = DB::table('mission_phase_assignment_auditeurs')
            ->where('assignment_id', $assignmentId)
            ->where('auditeur_id', $auditor->id)
            ->exists();

        $monRole = DB::table('mission_phase_auditeurs')
            ->where('mission_id', $missionId)
            ->where('auditeur_id', $auditor->id)
            ->value('role');

        if (!$isAffected && !in_array($monRole, ['DM', 'CM'])) {
            return response()->json(['error' => 'Droit insuffisant'], 403);
        }

        DB::table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->update(['status' => 'in_progress', 'actual_start' => now(), 'updated_at' => now()]);

        // Récupérer l'URL du formulaire pour redirection côté Vue
        // ⚠️ NOUVEAU SCHÉMA : mission_phases.id = ddmparam.audit_type_forms.id,
        // donc le code de formulaire se lit directement dans ddmparam via cet id
        // (il n'est plus dupliqué dans mission_phases.form_code).
        $formCode = DB::table('mission_phase_assignments as mpa')
            ->join('ddmparam.audit_type_forms as atf', 'mpa.mission_phase_id', '=', 'atf.id')
            ->where('mpa.id', $assignmentId)
            ->value('atf.code');

        $formUrl = null;
        if ($formCode) {
            try {
                $auditTypeCode = DB::table('mission_programmation as mp')
                    ->join('missions as m',       'mp.mission_id',     '=', 'm.id')
                    ->join('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
                    ->where('mp.id', $missionId)
                    ->value(DB::raw("COALESCE(mt.audit_type_code, mt.code)"));

                $urlPath = DB::table('ddmparam.audit_type_forms as atf')
                    ->join('ddmparam.audit_types as at', 'atf.audit_type_id', '=', 'at.id')
                    ->where('at.code', strtoupper($auditTypeCode ?? ''))
                    ->where('atf.code', $formCode)
                    ->value('atf.url_path');

                if ($urlPath) {
                    // CORRECTION : buildUrl() garantit le slash initial → préfixe correct
                    $formUrl = $this->buildUrl($urlPath, [
                        'mission_id'    => $missionId,
                        'assignment_id' => $assignmentId,
                    ]);
                }
            } catch (\Exception $e) {}
        }

        return response()->json(['success' => true, 'form_url' => $formUrl]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // VALIDATE PHASE — Valider / rejeter le formulaire d'une phase (DM/CM)
    // POST /m/audit.core/auditor/missions/{missionId}/phases/{assignmentId}/validate
    // Body : { action: validate|reject, note?, form_code? }
    // ══════════════════════════════════════════════════════════════════════════

    public function validatePhase(Request $request, int $missionId, int $assignmentId)
    {
        $auditor = $this->getConnectedAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $data = $request->validate([
            'action'    => 'required|in:validate,reject',
            'note'      => 'nullable|string|max:2000',
            'form_code' => 'nullable|string|max:160',
        ]);

        if ($data['action'] === 'reject' && trim((string) ($data['note'] ?? '')) === '') {
            return response()->json(['message' => 'Le motif du rejet est obligatoire.'], 422);
        }

        $assignment = DB::table('mission_phase_assignments')
            ->where('id', $assignmentId)
            ->where('mission_programmation_id', $missionId)
            ->first();
        if (!$assignment) return response()->json(['error' => 'Phase introuvable'], 404);

        // Seuls DM et CM peuvent valider/rejeter un formulaire
        $monRole = DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionId)
            ->where('mpa.auditeur_id', $auditor->id)
            ->value(DB::raw("COALESCE(mr.code, mpa.role)"));

        if (!in_array($monRole, ['DM', 'CM'])) {
            return response()->json(['error' => 'Droit insuffisant — action réservée au DM/CM.'], 403);
        }

        $oldStatus = $assignment->validation_status ?? 'draft';
        if ($oldStatus === 'validated') {
            return response()->json(['message' => 'Formulaire déjà validé — verrouillé.'], 409);
        }

        $newStatus = $data['action'] === 'validate' ? 'validated' : 'rejected';

        $update = [
            'validation_status' => $newStatus,
            'validation_note'   => $data['note'] ?? null,
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ];
        // Une validation définitive clôt la phase
        if ($newStatus === 'validated') {
            $update['status'] = 'completed';
            if ($this->columnExists('mission_phase_assignments', 'actual_end') && empty($assignment->actual_end)) {
                $update['actual_end'] = now();
            }
        }

        DB::table('mission_phase_assignments')->where('id', $assignmentId)->update($update);

        // Journal d'audit des validations (si la table existe)
        if ($this->columnExists('mission_phase_validation_log', 'id')) {
            DB::table('mission_phase_validation_log')->insert([
                'assignment_id' => $assignmentId,
                'form_code'     => $data['form_code'] ?? null,
                'actor_id'      => $auditor->id,
                'actor_role'    => $monRole,
                'action'        => $newStatus,
                'old_status'    => $oldStatus,
                'new_status'    => $newStatus,
                'note'          => $data['note'] ?? null,
                'created_at'    => now(),
            ]);
        }

        return response()->json(['success' => true, 'validation_status' => $newStatus]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Chat messages groupés par assignment_id
    // ══════════════════════════════════════════════════════════════════════════

    private function loadChatMessages(int $missionId, int $auditorId): array
    {
        $assignmentIds = DB::table('mission_phase_assignments')
            ->where('mission_programmation_id', $missionId)
            ->pluck('id')
            ->toArray();

        if (empty($assignmentIds)) return [];

        $messages = DB::table('mission_phase_chat as c')
            ->join('auditors as a', 'c.author_id', '=', 'a.id')
            ->leftJoin('mission_phase_chat_reads as r', function ($j) use ($auditorId) {
                $j->on('r.chat_id', '=', 'c.id')
                  ->where('r.auditeur_id', '=', $auditorId);
            })
            ->where('c.mission_id', $missionId)
            ->where(function ($q) use ($assignmentIds) {
                $q->whereIn('c.assignment_id', $assignmentIds)
                  ->orWhereNull('c.assignment_id');
            })
            ->select([
                'c.id',
                'c.assignment_id',
                'c.phase_type',
                'c.content',
                'c.type',
                'c.priority',
                'c.is_pinned',
                'c.author_id',
                'c.author_role',
                'c.parent_id',
                'c.form_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(
                    COALESCE(LEFT(a.last_name,1),''),
                    COALESCE(LEFT(a.first_name,1),'')
                )) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id = {$auditorId} THEN 1 ELSE 0 END as is_mine"),
                DB::raw("CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read"),
            ])
            ->orderBy('c.created_at', 'asc')
            ->get();

        if ($messages->isEmpty()) return [];

        // Marquer les non-lus comme lus au chargement
        $unreadIds = $messages
            ->filter(fn($m) => !(bool)$m->is_mine && !(bool)$m->is_read)
            ->pluck('id');

        if ($unreadIds->isNotEmpty()) {
            $now     = now();
            $inserts = $unreadIds->map(fn($id) => [
                'chat_id'     => $id,
                'auditeur_id' => $auditorId,
                'read_at'     => $now,
            ])->toArray();
            DB::table('mission_phase_chat_reads')->insertOrIgnore($inserts);
        }

        $result = [];
        foreach ($messages as $msg) {
            if (!$msg->assignment_id) continue;
            $result[$msg->assignment_id][] = [
                'id'              => $msg->id,
                'assignment_id'   => $msg->assignment_id,
                'phase_type'      => $msg->phase_type,
                'content'         => $msg->content,
                'type'            => $msg->type         ?? 'message',
                'priority'        => $msg->priority     ?? 'normal',
                'is_pinned'       => (bool)$msg->is_pinned,
                'author_id'       => $msg->author_id,
                'author_role'     => $msg->author_role  ?? '—',
                'author_name'     => $msg->author_name,
                'author_initials' => $msg->author_initials,
                'parent_id'       => $msg->parent_id,
                'form_code'       => $msg->form_code,
                'created_at_fr'   => $msg->created_at_fr,
                'is_mine'         => (bool)$msg->is_mine,
                'is_read'         => true,
            ];
        }

        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Mission + couleur/icône depuis ddmparam.audit_types
    // ══════════════════════════════════════════════════════════════════════════

    private function getMissionWithColor(int $missionId): ?object
    {
        $mission = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',     '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id', '=', 'mt.id')
            ->where('mp.id', $missionId)
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.objectif',
                'mp.date_debut', 'mp.date_fin', 'mp.lieux', 'mp.status',
                DB::raw("DATE_FORMAT(mp.date_debut,'%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin,'%d/%m/%Y') as date_fin_fr"),
                DB::raw("DATEDIFF(mp.date_fin,mp.date_debut)+1 as duree_totale"),
                'mt.id   as mission_type_id',
                'mt.code as type_code',
                DB::raw("COALESCE(mt.audit_type_code, mt.code) as audit_type_code"),
            ])
            ->first();

        if (!$mission) return null;

        $atColor = '#3B82F6';
        $atIcon  = 'ti ti-clipboard-check';
        $atLabel = null;

        if ($mission->audit_type_code) {
            try {
                $at = DB::table('ddmparam.audit_types')
                    ->where('code', strtoupper($mission->audit_type_code))
                    ->select(['color', 'icon', 'label'])
                    ->first();
                if ($at) {
                    $atColor = $at->color ?: $atColor;
                    $atIcon  = $at->icon  ?: $atIcon;
                    $atLabel = $at->label ?? null;
                }
            } catch (\Exception $e) {
                // ddmparam indisponible — défauts conservés
            }
        }

        $mission->audit_color      = $atColor;
        $mission->audit_icon       = $atIcon;
        $mission->audit_type_label = $atLabel;

        return $mission;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Construire phasesByType
    // ══════════════════════════════════════════════════════════════════════════

    private function buildPhasesByType(
        int    $missionId,
        int    $auditorId,
        string $monRole,
        int    $monNiveau,
        string $missionColor = '#3B82F6'
    ): array {
        // ── Détection colonnes optionnelles ───────────────────────────────────
        $hasActualDates      = $this->columnExists('mission_phase_assignments', 'actual_start');
        $hasOwnerId          = $this->columnExists('mission_phase_assignments', 'owner_id');
        $hasIsDisabled       = $this->columnExists('mission_phase_assignments', 'is_disabled');
        $hasWeight           = $this->columnExists('mission_phases', 'weight');
        $hasValidationStatus = $this->columnExists('mission_phase_assignments', 'validation_status');

        $colorLight = $missionColor . '20';

        // ⚠️ NOUVEAU SCHÉMA : mission_phases.id = ddmparam.audit_type_forms.id.
        // Le contenu (libellé, phase_type, hiérarchie, code de formulaire...)
        // vient DIRECTEMENT de ddmparam via un JOIN sur cet id — il n'est plus
        // dupliqué dans mission_phases, qui ne porte plus que les réglages
        // propres au tenant (is_mandatory, status, weight).
        $select = [
            'mpa.id as assignment_id',
            'mpa.mission_phase_id',
            'mpa.entity_id',
            'mpa.status as phase_status',
            'mpa.planned_start',
            'mpa.planned_end',
            'mpa.notes',
            'atf.code', 'atf.code as code_full', 'atf.label', 'atf.description',
            'atf.phase_num', 'atf.phase_label', 'atf.sort_order',
            DB::raw("CASE atf.phase_num
                WHEN 1 THEN 'PREPARATION' WHEN 2 THEN 'VERIFICATION'
                WHEN 3 THEN 'CONCLUSION'  WHEN 4 THEN 'SUIVI'
                WHEN 5 THEN 'RECOMMANDATIONS' ELSE 'AUTRE'
            END as phase_type"),
            DB::raw("CASE WHEN atf.parent_id IS NULL THEN 1 ELSE 2 END as level"),
            'atf.parent_id',
            'atf.code as form_code',
            'atf.url_path as form_url_path',
            'atf.route_name as form_route',
            'atf.label as form_label',
            'atf.icon as form_icon',
            'e.name as entity_name',
            DB::raw("DATE_FORMAT(mpa.planned_start,'%d/%m/%Y') as planned_start_fr"),
            DB::raw("DATE_FORMAT(mpa.planned_end,'%d/%m/%Y') as planned_end_fr"),
            DB::raw("CASE
                WHEN mpa.planned_start IS NOT NULL AND mpa.planned_end IS NOT NULL
                THEN DATEDIFF(mpa.planned_end,mpa.planned_start)+1
                ELSE NULL
            END as planned_duration"),
            DB::raw("CASE
                WHEN mpa.status='completed'   THEN 100
                WHEN mpa.status='skipped'     THEN 0
                WHEN mpa.status='in_progress' AND mpa.planned_start IS NOT NULL AND mpa.planned_end IS NOT NULL
                    THEN LEAST(ROUND((DATEDIFF(CURDATE(),mpa.planned_start)/NULLIF(DATEDIFF(mpa.planned_end,mpa.planned_start),0))*100,0),99)
                ELSE 0
            END as progression"),
        ];

        if ($hasActualDates) {
            $select[] = 'mpa.actual_start';
            $select[] = 'mpa.actual_end';
            $select[] = DB::raw("DATE_FORMAT(mpa.actual_start,'%d/%m/%Y') as actual_start_fr");
            $select[] = DB::raw("DATE_FORMAT(mpa.actual_end,'%d/%m/%Y') as actual_end_fr");
        }
        if ($hasOwnerId) {
            $select[] = 'mpa.owner_id';
            $select[] = DB::raw("TRIM(CONCAT(COALESCE(own.last_name,''),' ',COALESCE(own.first_name,''))) as owner_name");
            $select[] = DB::raw("COALESCE(omr.code, own_mpa.role,'—') as owner_role");
        }
        if ($hasIsDisabled)       $select[] = 'mpa.is_disabled';
        if ($hasWeight)           $select[] = 'ph.weight';
        if ($hasValidationStatus) $select[] = 'mpa.validation_status';

        $query = DB::table('mission_phase_assignments as mpa')
            ->select($select)
            ->join('ddmparam.audit_type_forms as atf', 'mpa.mission_phase_id', '=', 'atf.id')
            ->leftJoin('entities as e', 'mpa.entity_id', '=', 'e.id')
            ->where('mpa.mission_programmation_id', $missionId)
            // Masquer les phases désactivées côté central (is_active=0) même si
            // un assignment subsiste — même règle que le menu / centralFormIds().
            ->where('atf.is_active', 1);

        // Les réglages tenant (weight) restent dans mission_phases, jointe
        // séparément par id — jamais pour le contenu (label, phase_type...).
        if ($hasWeight) {
            $query->leftJoin('mission_phases as ph', 'ph.id', '=', 'mpa.mission_phase_id');
        }

        if ($hasOwnerId) {
            $query
                ->leftJoin('auditors as own', 'mpa.owner_id', '=', 'own.id')
                ->leftJoin('mission_phase_auditeurs as own_mpa', function ($j) use ($missionId) {
                    $j->on('own_mpa.auditeur_id', '=', 'own.id')
                      ->where('own_mpa.mission_id', '=', $missionId);
                })
                ->leftJoin('mission_roles as omr', 'own_mpa.role_id', '=', 'omr.id');
        }

        // ★ ORDRE OFFICIEL DE LA BASE PRINCIPALE : dans chaque phase,
        // l'affichage suit exactement l'ordre défini par l'admin dans
        // ddmparam (atf.sort_order, réglé par le glisser-déposer de l'écran
        // AuditTypeForms, puis atf.id) — plus de tri par weight/dates qui
        // mélangeait les formulaires.
        $query->orderBy('atf.phase_num')
              ->orderByRaw('CASE WHEN atf.parent_id IS NULL THEN 1 ELSE 2 END')
              ->orderBy('atf.sort_order')
              ->orderBy('atf.id');

        $phases = $query->get();

        // ── Auditeurs affectés par assignment ─────────────────────────────────
        $assignmentIds    = $phases->pluck('assignment_id')->filter()->toArray();
        $audsByAssignment = [];
        if (!empty($assignmentIds)) {
            DB::table('mission_phase_assignment_auditeurs as mpaa')
                ->join('auditors as a', 'mpaa.auditeur_id', '=', 'a.id')
                ->whereIn('mpaa.assignment_id', $assignmentIds)
                ->select([
                    'mpaa.assignment_id',
                    'a.id as auditeur_id',
                    DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"),
                    DB::raw("COALESCE(mpaa.role_code,'—') as role_code"),
                ])
                ->orderBy('mpaa.auditeur_id')
                ->get()
                ->each(function ($r) use (&$audsByAssignment, $auditorId) {
                    $audsByAssignment[$r->assignment_id][] = [
                        'auditeur_id' => $r->auditeur_id,
                        'full_name'   => $r->full_name,
                        'role_code'   => $r->role_code,
                        'initiales'   => mb_strtoupper(
                            collect(explode(' ', trim($r->full_name)))
                                ->filter()->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('')
                        ),
                        'is_me' => $r->auditeur_id === $auditorId,
                    ];
                });
        }

        // ── Tâches ────────────────────────────────────────────────────────────
        $hasTasks      = $this->columnExists('mission_phase_tasks', 'id');
        $tasksParPhase = [];
        if ($hasTasks && !empty($assignmentIds)) {
            DB::table('mission_phase_tasks as mpt')
                ->join('auditors as a', 'mpt.auditeur_id', '=', 'a.id')
                ->whereIn('mpt.assignment_id', $assignmentIds)
                ->where(function ($q) use ($auditorId, $monRole) {
                    if ($monRole === 'DM') { $q->whereRaw('1=1'); return; }
                    $rolesVisibles = match ($monRole) {
                        'CM' => ['CM','AS','AJ'], 'AS' => ['AS','AJ'], default => ['AJ'],
                    };
                    $q->where('mpt.auditeur_id', $auditorId)->orWhereIn('mpt.auditeur_role', $rolesVisibles);
                })
                ->select([
                    'mpt.assignment_id', 'mpt.id as task_id', 'mpt.libelle',
                    'mpt.status as task_status', 'mpt.auditeur_id',
                    'a.last_name', 'a.first_name',
                    DB::raw("COALESCE(mpt.auditeur_role,'—') as auditeur_role"),
                    DB::raw("DATE_FORMAT(mpt.created_at,'%d/%m/%Y') as created_at_fr"),
                ])
                ->orderBy('mpt.created_at', 'asc')
                ->get()
                ->each(function ($t) use (&$tasksParPhase) {
                    $tasksParPhase[$t->assignment_id][] = (array)$t;
                });
        }

        // ── Groupage par phase_num (1..5, ddmparam) ───────────────────────────
        // CORRECTION : l'ancien groupage par liste figée de phase_type perdait
        // silencieusement la phase 5 (RECOMMANDATIONS) et tout futur numéro.
        // Le libellé de groupe est désormais DYNAMIQUE : atf.phase_label
        // (exact pour chaque type d'audit), avec repli sur un libellé standard.
        // phase_type (chaîne) est conservé par groupe ET par phase pour les
        // consommateurs historiques (chat par phase_type notamment).
        $typeLabels = [
            'PREPARATION'     => 'Préparation',
            'VERIFICATION'    => 'Vérification',
            'CONCLUSION'      => 'Conclusion / Clôture',
            'SUIVI'           => 'Suivi des Recommandations',
            'RECOMMANDATIONS' => 'Recommandations',
        ];

        $result  = [];
        $grouped = $phases->groupBy('phase_num')->sortKeys();

        foreach ($grouped as $phaseNum => $group) {
            $first = $group->first();

            $phasesArray = $group->values()->map(function ($ph) use (
                $tasksParPhase, $audsByAssignment, $hasTasks,
                $missionColor, $colorLight, $hasValidationStatus,
                $missionId
            ) {
                $arr = (array) $ph;
                $arr['phase_color']       = $missionColor;
                $arr['phase_color_light'] = $colorLight;
                $arr['is_disabled']       = (bool)($arr['is_disabled'] ?? false);
                $arr['validation_status'] = $hasValidationStatus ? ($arr['validation_status'] ?? 'draft') : 'draft';
                $arr['auditeurs_affectes'] = $audsByAssignment[$ph->assignment_id] ?? [];
                $arr['tasks']              = $hasTasks ? ($tasksParPhase[$ph->assignment_id] ?? []) : [];

                // ── URL formulaire ─────────────────────────────────────────────
                // CORRECTION : buildUrl() force le slash initial sur url_path
                // Ex: 'm/audit.core/ac/preparation/reunion-lancement'
                // → url('/m/audit.core/ac/preparation/reunion-lancement')
                // → http://ddm-master.test/m/audit.core/ac/preparation/reunion-lancement ✓
                // Les champs form_* viennent directement de ddmparam.audit_type_forms
                // (colonnes atf.* du SELECT), plus besoin d'un lookup séparé.
                $formPath        = $arr['form_url_path'] ?? null;
                $arr['form_url'] = $formPath
                    ? $this->buildUrl($formPath, [
                        'mission_id'    => $missionId,
                        'assignment_id' => $ph->assignment_id,
                    ])
                    : null;
                $arr['form_icon'] = $arr['form_icon'] ?? 'ti ti-file-description';
                unset($arr['form_url_path']);

                // ── URL démarrer la phase ──────────────────────────────────────
                // CORRECTION : buildUrl() au lieu de url() directement
                $arr['start_url'] = $this->buildUrl(
                    "m/audit.core/auditor/missions/{$missionId}/phases/{$ph->assignment_id}/start"
                );

                // ── URL valider le formulaire ──────────────────────────────────
                $arr['validate_url'] = $this->buildUrl(
                    "m/audit.core/auditor/missions/{$missionId}/phases/{$ph->assignment_id}/validate"
                );

                return $arr;
            })->toArray();

            $result[] = [
                'phase_num'   => (int) $phaseNum,
                'phase_type'  => $first->phase_type,
                'label'       => $first->phase_label
                    ?: ($typeLabels[$first->phase_type] ?? $first->phase_type),
                'color'       => $missionColor,
                'color_light' => $colorLight,
                'phases'      => $phasesArray,
                'stats'       => [
                    'total'       => $group->count(),
                    'completed'   => $group->where('phase_status', 'completed')->count(),
                    'in_progress' => $group->where('phase_status', 'in_progress')->count(),
                    'pending'     => $group->where('phase_status', 'pending')->count(),
                    'skipped'     => $group->where('phase_status', 'skipped')->count(),
                ],
            ];
        }

        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Marquages par assignment
    // ══════════════════════════════════════════════════════════════════════════

    private function loadMarkings(int $missionId, int $auditorId, string $monRole, int $monNiveau): array
    {
        if (!$this->columnExists('mission_phase_markings', 'id')) return [];

        $assignmentIds = DB::table('mission_phase_assignments')
            ->where('mission_programmation_id', $missionId)
            ->pluck('id')->toArray();
        if (empty($assignmentIds)) return [];

        $result = [];
        DB::table('mission_phase_markings as mpm')
            ->join('auditors as a', 'mpm.author_id', '=', 'a.id')
            ->whereIn('mpm.assignment_id', $assignmentIds)
            ->where(function ($q) use ($auditorId, $monRole) {
                if ($monRole === 'DM') { $q->whereRaw('1=1'); return; }
                $rolesVisibles = match ($monRole) {
                    'CM' => ['CM','AS','AJ'], 'AS' => ['AS','AJ'], default => ['AJ'],
                };
                $q->where('mpm.author_id', $auditorId)->orWhereIn('mpm.author_role', $rolesVisibles);
            })
            ->select([
                'mpm.id', 'mpm.assignment_id', 'mpm.author_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(
                    COALESCE(LEFT(a.last_name,1),''),
                    COALESCE(LEFT(a.first_name,1),'')
                )) as author_initials"),
                DB::raw("COALESCE(mpm.author_role,'—') as author_role"),
                'mpm.content', 'mpm.status',
                DB::raw("DATE_FORMAT(mpm.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN mpm.author_id={$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('mpm.created_at', 'asc')
            ->get()
            ->each(function ($mk) use (&$result) {
                $arr = (array)$mk;
                $arr['is_mine'] = (bool)$arr['is_mine'];
                $result[$mk->assignment_id][] = $arr;
            });

        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Équipe de la mission
    // ══════════════════════════════════════════════════════════════════════════

    private function getEquipe(int $missionId, int $auditorId): array
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionId)
            ->select([
                'a.id as auditeur_id', 'a.audit_code', 'a.first_name', 'a.last_name', 'a.avatar',
                'mpa.parent_auditeur_id',
                DB::raw("COALESCE(mr.code, mpa.role,'—') as role"),
                DB::raw("COALESCE(mr.libelle, mpa.role,'—') as role_libelle"),
                DB::raw("COALESCE(mr.niveau,99) as role_niveau"),
            ])
            ->orderByRaw("COALESCE(mr.niveau,99) ASC")
            ->orderBy('a.last_name')
            ->get()
            ->map(fn($m) => array_merge((array)$m, ['is_me' => $m->auditeur_id === $auditorId]))
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVÉ — Entités de la mission
    // ══════════════════════════════════════════════════════════════════════════

    private function getEntities(int $missionId)
    {
        return DB::table('mission_programmation_entity as mpe')
            ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->where('mpe.mission_programmation_id', $missionId)
            ->select([
                'e.id as entity_id', 'e.name as entity_name',
                'mpe.date_debut', 'mpe.date_fin',
                DB::raw("DATE_FORMAT(mpe.date_debut,'%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mpe.date_fin,'%d/%m/%Y') as date_fin_fr"),
            ])
            ->orderBy('e.name')
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    private function buildAuditorPayload(Auditor $auditor): array
    {
        return [
            'id'          => $auditor->id,
            'audit_code'  => $auditor->audit_code,
            'first_name'  => $auditor->first_name,
            'last_name'   => $auditor->last_name,
            'nom_complet' => strtoupper($auditor->last_name).' '.ucfirst(strtolower($auditor->first_name)),
            'initiales'   => mb_strtoupper(
                mb_substr($auditor->last_name  ?? '', 0, 1).
                mb_substr($auditor->first_name ?? '', 0, 1)
            ),
            'email'  => $auditor->email,
            'avatar' => $auditor->avatar,
            'entity' => $auditor->entity?->name ?? null,
            'status' => $auditor->status,
        ];
    }

    private function buildAffectationEntity($aff, ?int $entityId, ?array $ep): array
    {
        return [
            'id'                   => $aff->id,
            'mission_id'           => $aff->mission_id,
            'entity_id'            => $entityId,
            'entity_name'          => $ep['entity_name']    ?? null,
            'entity_date_debut'    => $ep['date_debut']     ?? $aff->date_debut,
            'entity_date_fin'      => $ep['date_fin']       ?? $aff->date_fin,
            'entity_date_debut_fr' => $ep['date_debut_fr']  ?? $aff->date_debut_fr,
            'entity_date_fin_fr'   => $ep['date_fin_fr']    ?? $aff->date_fin_fr,
            'date_debut'           => $aff->date_debut,
            'date_fin'             => $aff->date_fin,
            'date_debut_fr'        => $aff->date_debut_fr,
            'date_fin_fr'          => $aff->date_fin_fr,
            'code_mission'         => $aff->code_mission,
            'libelle'              => $aff->libelle,
            'objectif'             => $aff->objectif,
            'lieux'                => $aff->lieux,
            'status'               => $aff->status,
            'mon_role'             => $aff->mon_role,
            'role_libelle'         => $aff->role_libelle,
            'duree'                => $aff->duree,
            'progression'          => $aff->progression,
            'budget_individuel'    => (float)$aff->budget_individuel,
        ];
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return in_array($column, DB::getSchemaBuilder()->getColumnListing($table));
        } catch (\Exception $e) {
            return false;
        }
    }
}
