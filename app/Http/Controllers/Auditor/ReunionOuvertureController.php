<?php
// ════════════════════════════════════════════════════════════════════
// ReunionOuvertureController.php
// AC — Phase P1 : Réunion d'ouverture
//
// Ce contrôleur étend BasePhaseFormController.
// Il surcharge UNIQUEMENT buildPayload() pour ajouter :
//   - fro          : alias de 'form' (sémantique Vue)
//   - fros         : liste des FRO de l'assignment
//   - chatMessages : messages chat
//   - formUrl      : URL construite côté PHP (pattern MissionPhases)
//   - backUrl      : URL retour phases
//   - chatBaseUrl  : URL API chat
//
// getMission() est héritée du Base (corrigée : m.entity_id ≠ mp.entity_id).
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor;

use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReunionOuvertureController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_fros';
    protected string $formCode    = 'reunion-ouverture';
    protected string $codePrefix  = 'FRO';
    protected string $codeColumn  = 'code_fro';   // override : table a code_fro et non code
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ReunionOuverture';
    protected string $routeEdit   = 'auditor.ac.reunion-ouverture.edit';

    protected array $jsonFields = [
        'ordre_du_jour',
        'participants',
        'points_generaux',
        'preoccupations',
    ];

    protected array $validationRules = [
        'date_reunion' => 'required|date',
        'lieu'         => 'required|string|max:255',
    ];

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'date_reunion'     => $request->input('date_reunion'),
            'heure_debut'      => $request->input('heure_debut') ?: null,
            'heure_fin'        => $request->input('heure_fin')   ?: null,
            'lieu'             => $request->input('lieu'),
            'fait_par'         => $request->input('fait_par'),
            'revue_par'        => $request->input('revue_par'),
            'ordre_du_jour'    => $request->input('ordre_du_jour', '[]'),
            'participants'     => $request->input('participants', '[]'),
            'points_generaux'  => $request->input('points_generaux', '[]'),
            'preoccupations'   => $request->input('preoccupations', '[]'),
        ];
    }

    // Code FRO sémantique (override genCode du Base)
    protected function genCode(int $missionId): string
    {
        $code   = DB::table('mission_programmation')->where('id', $missionId)->value('code_mission') ?? 'MSN';
        $prefix = 'FRO-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $code), 0, 6));
        $n      = DB::table('mission_phase_fros')->where('mission_id', $missionId)->count();
        return $prefix . '-' . str_pad($n + 1, 3, '0', STR_PAD_LEFT);
    }

    // Payload étendu : URLs construites PHP + fros + chat + alias 'fro'
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $role = $this->getRole($missionId, $auditor->id);

        $fros = DB::table('mission_phase_fros as f')
            ->join('mission_programmation as mp', 'f.mission_id', '=', 'mp.id')
            ->where('f.assignment_id', $assignmentId)
            ->select([
                'f.id', 'f.code_fro', 'f.date_reunion', 'f.lieu',
                'f.validation_status as status',
                'mp.code_mission',
                DB::raw("DATE_FORMAT(f.date_reunion,'%d/%m/%Y') as date_reunion_fr"),
            ])
            ->orderBy('f.created_at', 'desc')
            ->get()->toArray();

        $chatMessages = $this->getChatMessages($assignmentId, $auditor->id, $role);

        return array_merge(parent::buildPayload($missionId, $assignmentId, $auditor, $form), [
            'fro'          => $form,   // alias sémantique Vue
            'fros'         => $fros,
            'chatMessages' => $chatMessages,
            // Auditeur connecté — auto-remplir fait_par + dashboard reprise
            'currentAuditor' => [
                'id'         => $auditor->id,
                'audit_code' => $auditor->audit_code,
                'last_name'  => $auditor->last_name,
                'first_name' => $auditor->first_name,
                'email'      => $auditor->email ?? null,
            ],
            // URLs construites PHP — jamais reconstruites côté JS
            'formUrl'      => url('/m/audit.core/ac/preparation/reunion-ouverture'),
            'backUrl'      => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            'chatBaseUrl'  => url('/api/mission-phase-chat'),
        ]);
    }

    private function getChatMessages(int $assignmentId, int $auditorId, string $role): array
    {
        if (!DB::getSchemaBuilder()->hasTable('mission_phase_chat')) return [];

        return DB::table('mission_phase_chat as c')
            ->join('auditors as a', 'c.author_id', '=', 'a.id')
            ->where('c.assignment_id', $assignmentId)
            ->where('c.form_code', 'reunion-ouverture')
            ->where(function ($q) use ($auditorId, $role) {
                if ($role === 'DM') { $q->whereRaw('1=1'); return; }
                $visible = match ($role) {
                    'CM'    => ['CM', 'AS', 'AJ'],
                    'AS'    => ['AS', 'AJ'],
                    default => ['AJ'],
                };
                $q->where('c.author_id', $auditorId)->orWhereIn('c.author_role', $visible);
            })
            ->select([
                'c.id', 'c.content', 'c.type', 'c.priority', 'c.is_pinned',
                'c.author_id', 'c.author_role', 'c.parent_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),''),COALESCE(LEFT(a.first_name,1),''))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id = {$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('c.created_at', 'asc')
            ->get()
            ->map(fn ($m) => tap($m, fn ($m) => $m->is_mine = (bool) $m->is_mine))
            ->toArray();
    }

    public function soumettre(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $fro = DB::table('mission_phase_fros')->where('id', $formId)->first();
        if (!$fro) return response()->json(['error' => 'FRO introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $fro->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $fro->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) return response()->json(['error' => 'Accès refusé'], 403);
        if ($fro->validation_status === 'validated') return response()->json(['error' => 'Déjà validé'], 422);
        if ($fro->validation_status === 'in_review')  return response()->json(['error' => 'Déjà soumis'], 422);

        DB::table('mission_phase_fros')->where('id', $formId)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    public function valider(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $fro = DB::table('mission_phase_fros')->where('id', $formId)->first();
        if (!$fro) return response()->json(['error' => 'FRO introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $fro->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $fro->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($fro->validation_status !== 'in_review') return response()->json(['error' => 'Le formulaire doit être soumis avant validation'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif du rejet obligatoire'], 422);
            DB::table('mission_phase_fros')->where('id', $formId)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);

        DB::table('mission_phase_fros')->where('id', $formId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);

        DB::table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ────────────────────────────────────────────────────────────────
    // PDF — Télécharger la fiche FRO en PDF (DomPDF)
    // GET  reunion-ouverture/{form}/pdf?download=1
    // Accessible : statut validated → tous les auditeurs de la mission
    //              statut draft/in_review → DM et CM uniquement
    // ────────────────────────────────────────────────────────────────
    public function pdf(Request $request, int $formId): \Illuminate\Http\Response
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $fro = DB::table('mission_phase_fros')->where('id', $formId)->first();
        if (!$fro) abort(404);

        $missionId    = $fro->mission_id;
        $assignmentId = $fro->assignment_id;
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        $role = $this->getRole($missionId, $auditor->id);
        if ($fro->validation_status !== 'validated' && !in_array($role, ['DM', 'CM'])) {
            abort(403, 'Seules les fiches validées peuvent être téléchargées.');
        }

        // Décoder les champs JSON
        $ordreduJour    = json_decode($fro->ordre_du_jour    ?? '[]', true) ?? [];
        $participants   = json_decode($fro->participants      ?? '[]', true) ?? [];
        $pointsGeneraux = json_decode($fro->points_generaux  ?? '[]', true) ?? [];
        $preoccupations = json_decode($fro->preoccupations   ?? '[]', true) ?? [];

        // Mission + entité
        $mission = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m', 'mp.mission_id', '=', 'm.id')
            ->leftJoin('entities as e', 'm.entity_id',   '=', 'e.id')
            ->where('mp.id', $missionId)
            ->select(['mp.id', 'mp.code_mission', 'mp.libelle', 'mp.date_debut', 'mp.date_fin', 'e.name as entity_name'])
            ->first();

        // Nom du validateur
        $validatedBy = null;
        if ($fro->validated_by) {
            $va = DB::table('auditors')->where('id', $fro->validated_by)->first();
            $validatedBy = $va ? trim(($va->last_name ?? '') . ' ' . ($va->first_name ?? '')) : null;
        }

        $pdf = Pdf::loadView('pdf.fro-pdf', compact(
            'fro', 'mission', 'ordreduJour', 'participants',
            'pointsGeneraux', 'preoccupations', 'validatedBy'
        ))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 150,
            'chroot'               => base_path(),
        ]);

        $filename = 'FRO-' . ($fro->code_fro ?? $formId) . '.pdf';
        return $request->boolean('download', true)
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

}