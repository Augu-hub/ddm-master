<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlanActionController extends Controller
{
    protected string $conn = 'tenant';

    // ─────────────────────────────────────────────────────────────
    // PAGE PRINCIPALE (Inertia)
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request): \Inertia\Response
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Utilisateur non authentifié');
        }
        $auditor = Auditor::where('user_id', $user->id)->first();
        if (!$auditor) {
            abort(403, 'Aucun auditeur associé à cet utilisateur');
        }

        $missionId = (int) $request->query('mission_id');
        if (!$missionId) {
            abort(422, 'mission_id requis');
        }

        // Infos mission
        $mission = DB::connection($this->conn)
            ->table('mission_programmation')
            ->select('id', 'libelle', 'code_mission')
            ->where('id', $missionId)
            ->first();

        // Récupération des FRAP (sans filtre assignment_id)
        $fraps = DB::connection($this->conn)
            ->table('v_plan_action_priorite')
            ->where('mission_id', $missionId)
            ->orderByRaw("FIELD(priorite,'critique','haute','moyenne','basse')")
            ->orderBy('date_echeance')
            ->get()
            ->toArray();

        // Statistiques par priorité
        $stats = DB::connection($this->conn)
            ->table('fiche_observation_frap as f')
            ->select(
                'f.priorite',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN s.statut = "realise" THEN 1 ELSE 0 END) as realisees'),
                DB::raw('AVG(s.taux_realisation) as taux_moyen'),
                DB::raw('MIN(f.date_echeance) as prochaine_echeance')
            )
            ->leftJoin('plan_action_suivi as s', 's.frap_id', '=', 'f.id')
            ->where('f.mission_id', $missionId)
            ->whereNull('f.deleted_at')
            ->groupBy('f.priorite')
            ->orderByRaw("FIELD(f.priorite,'critique','haute','moyenne','basse')")
            ->get()
            ->toArray();

        return Inertia::render('dashboards/Auditor/Forms/PlanAction', [
            'mission'        => $mission,
            'fraps'          => $fraps,
            'stats'          => $stats,
            'auditorRole'    => $this->getRole($missionId, $auditor->id),
            'auditeurNom'    => trim($auditor->last_name . ' ' . $auditor->first_name),
            'missionId'      => $missionId,
            'backUrl'        => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            'urlApiData'     => route('auditor.ac.plan-action.api.data'),
            'urlApiPriorite' => route('auditor.ac.plan-action.priorite.update', ['frapId' => '__FRAP_ID__']),
            'urlApiSuivi'    => route('auditor.ac.plan-action.suivi.update',    ['frapId' => '__FRAP_ID__']),
            'urlApiDashboard'=> route('auditor.ac.plan-action.dashboard'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // API : Récupérer les FRAP + statistiques (JSON)
    // ─────────────────────────────────────────────────────────────
    public function getData(Request $request): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $missionId = $request->query('mission_id');
        if (!$missionId) {
            return response()->json(['success' => false, 'error' => 'mission_id requis'], 422);
        }

        $fraps = DB::connection($this->conn)
            ->table('v_plan_action_priorite')
            ->where('mission_id', $missionId)
            ->orderByRaw("FIELD(priorite,'critique','haute','moyenne','basse')")
            ->orderBy('date_echeance')
            ->get();

        $stats = DB::connection($this->conn)
            ->table('fiche_observation_frap as f')
            ->select(
                'f.priorite',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN s.statut = "realise" THEN 1 ELSE 0 END) as realisees'),
                DB::raw('AVG(s.taux_realisation) as taux_moyen'),
                DB::raw('MIN(f.date_echeance) as prochaine_echeance')
            )
            ->leftJoin('plan_action_suivi as s', 's.frap_id', '=', 'f.id')
            ->where('f.mission_id', $missionId)
            ->whereNull('f.deleted_at')
            ->groupBy('f.priorite')
            ->get();

        return response()->json(['success' => true, 'fraps' => $fraps, 'stats' => $stats]);
    }

    // ─────────────────────────────────────────────────────────────
    // Mise à jour de la priorité d'une FRAP
    // ─────────────────────────────────────────────────────────────
    public function updatePriorite(Request $request, int $frapId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'priorite' => 'required|in:basse,moyenne,haute,critique'
        ]);

        $frap = DB::connection($this->conn)->table('fiche_observation_frap')
            ->where('id', $frapId)
            ->whereNull('deleted_at')
            ->first();

        if (!$frap) {
            return response()->json(['success' => false, 'error' => 'FRAP introuvable'], 404);
        }

        $role = $this->getRole($frap->mission_id ?? 0, $auditor->id);
        if (!in_array($role, ['DM', 'CM']) && $frap->auditeur_id != $auditor->id) {
            return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        DB::connection($this->conn)->table('fiche_observation_frap')
            ->where('id', $frapId)
            ->update([
                'priorite'   => $validated['priorite'],
                'updated_at' => now()
            ]);

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // Mise à jour du suivi d'une FRAP (statut, taux, commentaire)
    // ─────────────────────────────────────────────────────────────
    public function updateSuivi(Request $request, int $frapId): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'statut'           => 'nullable|in:a_faire,en_cours,realise,non_realise,reporte',
            'taux_realisation' => 'nullable|integer|min:0|max:100',
            'commentaire'      => 'nullable|string',
            'date_verification'=> 'nullable|date',
        ]);

        $frap = DB::connection($this->conn)->table('fiche_observation_frap')
            ->where('id', $frapId)
            ->whereNull('deleted_at')
            ->first();

        if (!$frap) {
            return response()->json(['success' => false, 'error' => 'FRAP introuvable'], 404);
        }

        $role = $this->getRole($frap->mission_id ?? 0, $auditor->id);
        if (!in_array($role, ['DM', 'CM']) && $frap->auditeur_id != $auditor->id) {
            return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        DB::connection($this->conn)->table('plan_action_suivi')
            ->updateOrInsert(
                ['frap_id' => $frapId],
                array_merge($validated, ['updated_at' => now()])
            );

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // Tableau de bord statistique (API)
    // ─────────────────────────────────────────────────────────────
    public function dashboard(Request $request): JsonResponse
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            return response()->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $missionId = $request->query('mission_id');
        if (!$missionId) {
            return response()->json(['success' => false, 'error' => 'mission_id requis'], 422);
        }

        $stats = DB::connection($this->conn)->table('fiche_observation_frap as f')
            ->select(
                'f.priorite',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN s.statut = "realise" THEN 1 ELSE 0 END) as realisees'),
                DB::raw('AVG(s.taux_realisation) as taux_moyen'),
                DB::raw('MIN(f.date_echeance) as prochaine_echeance')
            )
            ->leftJoin('plan_action_suivi as s', 's.frap_id', '=', 'f.id')
            ->where('f.mission_id', $missionId)
            ->whereNull('f.deleted_at')
            ->groupBy('f.priorite')
            ->get();

        return response()->json(['success' => true, 'stats' => $stats]);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────
    private function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection($this->conn)
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->first();

        return $row?->role_code ?? 'AJ';
    }

    private function getAuditor(): ?Auditor
    {
        $user = auth()->user();
        if (!$user) return null;
        return Auditor::where('user_id', $user->id)->first();
    }
}