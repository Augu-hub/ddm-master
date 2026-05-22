<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Services\Audit\RapportAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RapportAuditController extends Controller
{
    protected RapportAuditService $rapportService;

    public function __construct(RapportAuditService $rapportService)
    {
        $this->rapportService = $rapportService;
    }

    /**
     * Affiche le rapport HTML
     */
    public function generer(Request $request, int $missionId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            abort(403, 'Aucun auditeur connecté.');
        }

        // Vérifier que l'auditeur a accès à la mission
        $hasAccess = DB::connection('tenant')
            ->table('mission_phase_assignments as mpa')
            ->join('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditor->id)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Vous n’êtes pas autorisé à consulter ce rapport.');
        }

        $data = $this->rapportService->getDonneesRapport($missionId);
        return view('auditor.rapport_audit', $data);
    }

    /**
     * Télécharge le PDF
     */
    public function telechargerPdf(Request $request, int $missionId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $hasAccess = DB::connection('tenant')
            ->table('mission_phase_assignments as mpa')
            ->join('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditor->id)
            ->exists();

        if (!$hasAccess) abort(403);

        $data = $this->rapportService->getDonneesRapport($missionId);
        $pdf = Pdf::loadView('auditor.rapport_audit', $data);
        return $pdf->download("rapport_mission_{$missionId}.pdf");
    }

    /**
     * Récupère l'auditeur connecté via l'utilisateur authentifié
     */
    private function getAuditor()
    {
        $user = auth()->user();
        if (!$user) return null;

        // Relation Eloquent (si définie)
        if (method_exists($user, 'auditor') && $user->auditor) {
            return $user->auditor;
        }

        // Recherche en base de données
        return DB::connection('tenant')
            ->table('auditors')
            ->where('user_id', $user->id)
            ->first();
    }
}