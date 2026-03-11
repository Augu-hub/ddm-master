<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

/**
 * Gère la validation hiérarchique des formulaires de phase.
 *
 * Cycle :  draft → in_review → validated
 *                          ↘  rejected → draft (correction)
 *
 * Qui peut quoi :
 *   AJ  → soumet son formulaire (draft → in_review)
 *   AS  → soumet + valide intermédiaire les AJ de son équipe
 *   CM  → valide intermédiaire (in_review → validated ou rejected)
 *   DM  → valide définitivement + peut déverrouiller si erreur
 */
class MissionPhaseValidationController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    private function getAuditor(): ?Auditor
    {
        $id = Session::get('auditor_id');
        if ($id) { $a = Auditor::find($id); if ($a) return $a; }
        $user = Auth::user();
        if (!$user) return null;
        $a = Auditor::where('email',$user->email)->where('status','active')->first();
        if ($a) Session::put('auditor_id',$a->id);
        return $a;
    }

    private function getRole(int $missionId, int $auditorId): string
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')
            ->where('mpa.mission_id',$missionId)
            ->where('mpa.auditeur_id',$auditorId)
            ->value(DB::raw("COALESCE(mr.code,mpa.role)")) ?? '—';
    }

    private function canAccess(int $missionId, int $assignmentId, Auditor $auditor): bool
    {
        $inMission = DB::table('mission_phase_auditeurs')
            ->where('mission_id',$missionId)->where('auditeur_id',$auditor->id)->exists();
        if (!$inMission) return false;
        $role = $this->getRole($missionId,$auditor->id);
        if (in_array($role,['DM','CM','AS'])) return true;
        return DB::table('mission_phase_assignment_auditeurs')
            ->where('assignment_id',$assignmentId)->where('auditeur_id',$auditor->id)->exists();
    }

    private function logAction(int $assignmentId, ?string $formCode, int $actorId, string $role, string $action, ?string $old, ?string $new, ?string $note = null): void
    {
        if (!DB::getSchemaBuilder()->hasTable('mission_phase_validation_log')) return;
        DB::table('mission_phase_validation_log')->insert([
            'assignment_id' => $assignmentId,
            'form_code'     => $formCode,
            'actor_id'      => $actorId,
            'actor_role'    => $role,
            'action'        => $action,
            'old_status'    => $old,
            'new_status'    => $new,
            'note'          => $note,
            'created_at'    => now(),
        ]);
    }

    /**
     * Résout la table et la clé primaire du formulaire selon form_code.
     * Retourne ['table' => '...', 'pk' => '...', 'key_col' => '...', 'key_val' => ...]
     */
    private function resolveForm(string $formCode, int $assignmentId): ?array
    {
        return match($formCode) {
            'reunion-ouverture' => [
                'table'   => 'mission_phase_fros',
                'where'   => ['assignment_id' => $assignmentId],
            ],
            default => [
                'table'   => 'mission_phase_form_data',
                'where'   => ['assignment_id' => $assignmentId, 'form_code' => $formCode],
            ],
        };
    }

    private function getFormRow(string $formCode, int $assignmentId): ?object
    {
        $r = $this->resolveForm($formCode, $assignmentId);
        if (!$r) return null;
        $q = DB::table($r['table']);
        foreach ($r['where'] as $col => $val) $q->where($col,$val);
        return $q->first();
    }

    private function updateFormRow(string $formCode, int $assignmentId, array $data): void
    {
        $r = $this->resolveForm($formCode, $assignmentId);
        if (!$r) return;
        $q = DB::table($r['table']);
        foreach ($r['where'] as $col => $val) $q->where($col,$val);
        $q->update(array_merge($data, ['updated_at' => now()]));
    }

    // ══════════════════════════════════════════════════════════
    // POST /api/missions/{mission}/assignments/{assignment}/soumettre
    //   AJ/AS → soumet le formulaire (draft → in_review)
    // ══════════════════════════════════════════════════════════

    public function soumettre(Request $request, int $missionId, int $assignmentId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);
        if (!$this->canAccess($missionId,$assignmentId,$auditor)) {
            return response()->json(['error'=>'Accès refusé'], 403);
        }

        $formCode = $request->input('form_code','reunion-ouverture');
        $row = $this->getFormRow($formCode, $assignmentId);
        if (!$row) return response()->json(['error'=>'Formulaire introuvable'], 404);

        if ($row->validation_status === 'validated') {
            return response()->json(['error'=>'Formulaire déjà validé définitivement'], 422);
        }
        if ($row->validation_status === 'in_review') {
            return response()->json(['error'=>'Déjà soumis, en attente de validation'], 422);
        }

        $role = $this->getRole($missionId,$auditor->id);

        $this->updateFormRow($formCode,$assignmentId,[
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
        ]);

        $this->logAction($assignmentId,$formCode,$auditor->id,$role,'submitted','draft','in_review');

        return response()->json(['success'=>true,'status'=>'in_review','role'=>$role]);
    }

    // ══════════════════════════════════════════════════════════
    // POST /api/missions/{mission}/assignments/{assignment}/valider
    //   CM/DM → valide ou rejette le formulaire
    //
    //   Body : {
    //     form_code: 'reunion-ouverture',
    //     action:    'validate' | 'reject',
    //     note:      '...' (obligatoire si reject)
    //   }
    // ══════════════════════════════════════════════════════════

    public function valider(Request $request, int $missionId, int $assignmentId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);

        $role = $this->getRole($missionId,$auditor->id);
        if (!in_array($role,['DM','CM'])) {
            return response()->json(['error'=>'Seuls CM/DM peuvent valider'], 403);
        }

        $request->validate([
            'form_code' => 'required|string',
            'action'    => 'required|in:validate,reject',
            'note'      => 'nullable|string|max:2000',
        ]);

        $formCode = $request->input('form_code');
        $action   = $request->input('action');
        $note     = $request->input('note');

        $row = $this->getFormRow($formCode, $assignmentId);
        if (!$row) return response()->json(['error'=>'Formulaire introuvable'], 404);

        if ($row->validation_status === 'validated') {
            return response()->json(['error'=>'Formulaire déjà validé définitivement'], 422);
        }
        if ($row->validation_status !== 'in_review') {
            return response()->json(['error'=>'Le formulaire doit être soumis avant validation (status: '.$row->validation_status.')'], 422);
        }

        // ── REJET ────────────────────────────────────────────
        if ($action === 'reject') {
            if (!$note) return response()->json(['error'=>'Motif de rejet obligatoire'], 422);

            $this->updateFormRow($formCode,$assignmentId,[
                'validation_status' => 'draft',   // retour en draft pour correction
                'validation_note'   => $note,
                'submitted_at'      => null,
                'submitted_by'      => null,
            ]);

            $this->logAction($assignmentId,$formCode,$auditor->id,$role,'rejected','in_review','draft',$note);

            return response()->json([
                'success' => true,
                'action'  => 'rejected',
                'status'  => 'draft',
                'note'    => $note,
            ]);
        }

        // ── VALIDATION ────────────────────────────────────────
        // Seul DM peut valider définitivement
        if ($role !== 'DM') {
            return response()->json(['error'=>'Seul le DM peut valider définitivement'], 403);
        }

        $this->updateFormRow($formCode,$assignmentId,[
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
        ]);

        // Mettre aussi à jour l'assignment global
        DB::table('mission_phase_assignments')->where('id',$assignmentId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->logAction($assignmentId,$formCode,$auditor->id,$role,'validated','in_review','validated',$note);

        return response()->json([
            'success' => true,
            'action'  => 'validated',
            'status'  => 'validated',
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // POST /api/missions/{mission}/assignments/{assignment}/deverrouiller
    //   DM uniquement → repasse validated → draft (correction exceptionnelle)
    // ══════════════════════════════════════════════════════════

    public function deverrouiller(Request $request, int $missionId, int $assignmentId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);

        $role = $this->getRole($missionId,$auditor->id);
        if ($role !== 'DM') {
            return response()->json(['error'=>'Seul le DM peut déverrouiller'], 403);
        }

        $request->validate([
            'form_code' => 'required|string',
            'note'      => 'required|string|max:2000',
        ]);

        $formCode = $request->input('form_code');
        $row = $this->getFormRow($formCode,$assignmentId);
        if (!$row) return response()->json(['error'=>'Formulaire introuvable'], 404);

        $this->updateFormRow($formCode,$assignmentId,[
            'validation_status' => 'draft',
            'validated_at'      => null,
            'validated_by'      => null,
            'validation_note'   => $request->input('note'),
        ]);

        DB::table('mission_phase_assignments')->where('id',$assignmentId)->update([
            'validation_status' => 'draft',
            'validated_at'      => null,
            'validated_by'      => null,
            'updated_at'        => now(),
        ]);

        $this->logAction($assignmentId,$formCode,$auditor->id,$role,'unlocked','validated','draft',$request->input('note'));

        return response()->json(['success'=>true,'status'=>'draft']);
    }

    // ══════════════════════════════════════════════════════════
    // GET /api/missions/{mission}/assignments/{assignment}/validation-status
    //   Retourne le statut de validation du formulaire + historique
    // ══════════════════════════════════════════════════════════

    public function status(Request $request, int $missionId, int $assignmentId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);
        if (!$this->canAccess($missionId,$assignmentId,$auditor)) {
            return response()->json(['error'=>'Accès refusé'], 403);
        }

        $formCode = $request->input('form_code','reunion-ouverture');
        $row = $this->getFormRow($formCode,$assignmentId);

        // Historique des validations
        $history = DB::table('mission_phase_validation_log as l')
            ->join('auditors as a','l.actor_id','=','a.id')
            ->where('l.assignment_id',$assignmentId)
            ->where('l.form_code',$formCode)
            ->select([
                'l.action','l.old_status','l.new_status','l.note',
                'l.actor_role',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as actor_name"),
                DB::raw("DATE_FORMAT(l.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
            ])
            ->orderBy('l.created_at','desc')
            ->get();

        $myRole = $this->getRole($missionId,$auditor->id);

        return response()->json([
            'status'   => $row?->validation_status ?? 'draft',
            'note'     => $row?->validation_note,
            'history'  => $history,
            'my_role'  => $myRole,
            // Ce que l'auditeur peut faire avec ce statut
            'can_edit'   => $this->computeCanEdit($row?->validation_status ?? 'draft', $myRole),
            'can_submit' => $this->computeCanSubmit($row?->validation_status ?? 'draft', $myRole),
            'can_validate' => in_array($myRole,['DM','CM']) && ($row?->validation_status === 'in_review'),
            'can_unlock'   => $myRole === 'DM' && ($row?->validation_status === 'validated'),
        ]);
    }

    private function computeCanEdit(?string $status, string $role): bool
    {
        if ($status === 'validated') return false;
        if ($status === 'in_review' && !in_array($role,['CM','DM'])) return false;
        return true;
    }

    private function computeCanSubmit(?string $status, string $role): bool
    {
        if (in_array($status,['in_review','validated'])) return false;
        return true; // draft ou rejected → peut soumettre
    }
}