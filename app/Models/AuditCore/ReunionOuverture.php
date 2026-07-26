<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReunionOuvertureController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════

    private function getAuditor(): ?Auditor
    {
        $id = Session::get('auditor_id');
        if ($id) {
            $a = Auditor::with(['user','entity'])->find($id);
            if ($a) return $a;
        }
        $user = Auth::user();
        if (!$user) return null;
        $a = Auditor::with(['user','entity'])
            ->where('email', $user->email)->where('status','active')->first();
        if ($a) Session::put('auditor_id', $a->id);
        return $a;
    }

    /** Rôle de l'auditeur sur la mission */
    private function getRole(int $missionId, int $auditorId): string
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')
            ->where('mpa.mission_id', $missionId)
            ->where('mpa.auditeur_id', $auditorId)
            ->value(DB::raw("COALESCE(mr.code, mpa.role)")) ?? '—';
    }

    /**
     * Vérifie l'accès à un assignment.
     * DM/CM → accès total à la mission
     * AS/AJ → doit être dans mission_phase_assignment_auditeurs pour cet assignment
     */
    private function canAccess(int $missionId, int $assignmentId, Auditor $auditor): bool
    {
        // D'abord l'auditeur doit être dans la mission
        $inMission = DB::table('mission_phase_auditeurs')
            ->where('mission_id', $missionId)
            ->where('auditeur_id', $auditor->id)
            ->exists();
        if (!$inMission) return false;

        $role = $this->getRole($missionId, $auditor->id);
        if (in_array($role, ['DM','CM'])) return true;

        // AS/AJ : doit être affecté à l'assignment précis
        return DB::table('mission_phase_assignment_auditeurs')
            ->where('assignment_id', $assignmentId)
            ->where('auditeur_id', $auditor->id)
            ->exists();
    }

    /**
     * Vérifie si l'auditeur peut MODIFIER le formulaire.
     * Règles :
     *  - validated → personne ne peut modifier
     *  - in_review  → CM et DM uniquement
     *  - draft      → tous les auditeurs affectés à l'assignment
     */
    private function canEdit(object $fro, string $role): bool
    {
        if ($fro->validation_status === 'validated') return false;
        if ($fro->validation_status === 'in_review' && !in_array($role, ['CM','DM'])) return false;
        return true;
    }

    /** Génère un code FRO unique */
    private function genCode(int $missionId): string
    {
        $code  = DB::table('mission_programmation')->where('id',$missionId)->value('code_mission') ?? 'MSN';
        $prefix = 'FRO-'.strtoupper(substr(preg_replace('/[^A-Z0-9]/i','',$code),0,6));
        $n = DB::table('mission_phase_fros')->where('mission_id',$missionId)->count();
        return $prefix.'-'.str_pad($n+1,3,'0',STR_PAD_LEFT);
    }

    /** Journalise une action de validation */
    private function log(int $assignmentId, string $formCode, int $actorId, string $role, string $action, ?string $old, ?string $new, ?string $note = null): void
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

    /** Hydrate un FRO (décode les JSON) */
    private function hydrate(object $fro): object
    {
        $fro->ordre_du_jour   = json_decode($fro->ordre_du_jour   ?? '[]', true);
        $fro->participants    = json_decode($fro->participants    ?? '[]', true);
        $fro->points_generaux = json_decode($fro->points_generaux ?? '[]', true);
        $fro->preoccupations  = json_decode($fro->preoccupations  ?? '[]', true);
        return $fro;
    }

    /** Charge la mission */
    private function getMission(int $missionId): ?object
    {
        return DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',      'mp.mission_id',     '=','m.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id','=','mt.id')
            ->leftJoin('entities as e',       'mp.entity_id',     '=','e.id')
            ->where('mp.id', $missionId)
            ->select([
                'mp.id','mp.code_mission','mp.libelle','mp.objectif',
                'mp.date_debut','mp.date_fin','mp.lieux','mp.status',
                DB::raw("DATE_FORMAT(mp.date_debut,'%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin,'%d/%m/%Y') as date_fin_fr"),
                'e.name as entity_name',
                DB::raw("COALESCE(mt.audit_type_code, mt.code) as audit_type_code"),
            ])
            ->first();
    }

    /** Charge l'assignment */
    private function getAssignment(int $assignmentId, int $missionId): ?object
    {
        // ⚠️ NOUVEAU SCHÉMA : le contenu (code/label/form_code) se lit dans
        // ddmparam.audit_type_forms (id partagé avec mission_phases).
        return DB::table('mission_phase_assignments as mpa')
            ->join('ddmparam.audit_type_forms as atf','mpa.mission_phase_id','=','atf.id')
            ->where('mpa.id', $assignmentId)
            ->where('mpa.mission_programmation_id', $missionId)
            ->select([
                'mpa.id','mpa.status as phase_status',
                'mpa.validation_status','mpa.planned_start','mpa.planned_end',
                'atf.code as phase_code','atf.label as phase_label','atf.code as form_code',
            ])
            ->first();
    }

    /** Auditeurs de la mission pour le tableau */
    private function getAuditeurs(int $missionId): array
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->join('auditors as a','mpa.auditeur_id','=','a.id')
            ->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')
            ->where('mpa.mission_id', $missionId)
            ->select([
                'a.id','a.audit_code as code',
                'a.last_name as nom','a.first_name as prenom',
                DB::raw("COALESCE(mr.code, mpa.role, '—') as grade"),
            ])
            ->orderByRaw("COALESCE(mr.niveau,99) ASC")
            ->orderBy('a.last_name')
            ->get()->toArray();
    }

    /** Messages chat pour cet assignment + formulaire */
    private function getChatMessages(int $assignmentId, int $auditorId, string $role): array
    {
        if (!DB::getSchemaBuilder()->hasTable('mission_phase_chat')) return [];

        $visible = match($role) {
            'DM'    => ['DM','CM','AS','AJ'],
            'CM'    => ['CM','AS','AJ'],
            'AS'    => ['AS','AJ'],
            default => ['AJ'],
        };

        return DB::table('mission_phase_chat as c')
            ->join('auditors as a','c.author_id','=','a.id')
            ->where('c.assignment_id', $assignmentId)
            ->where('c.form_code', 'reunion-ouverture')
            ->where(function($q) use ($auditorId,$visible,$role) {
                if ($role === 'DM') { $q->whereRaw('1=1'); return; }
                $q->where('c.author_id',$auditorId)
                  ->orWhereIn('c.author_role',$visible);
            })
            ->select([
                'c.id','c.content','c.type','c.priority','c.is_pinned',
                'c.author_id','c.author_role','c.parent_id',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),''),COALESCE(LEFT(a.first_name,1),''))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id={$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('c.created_at','asc')
            ->get()
            ->map(fn($m) => tap($m, fn($m) => $m->is_mine = (bool)$m->is_mine))
            ->toArray();
    }

    /** Payload Inertia commun */
    private function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, ?object $fro = null): array
    {
        $role      = $this->getRole($missionId, $auditor->id);
        $mission   = $this->getMission($missionId);
        $assignment= $this->getAssignment($assignmentId, $missionId);
        $auditeurs = $this->getAuditeurs($missionId);
        $chat      = $this->getChatMessages($assignmentId, $auditor->id, $role);

        $fros = DB::table('mission_phase_fros as f')
            ->join('mission_programmation as mp','f.mission_id','=','mp.id')
            ->where('f.mission_id', $missionId)
            ->select([
                'f.id','f.code_fro','f.date_reunion','f.lieu','f.validation_status as status',
                'mp.code_mission',
                DB::raw("DATE_FORMAT(f.date_reunion,'%d/%m/%Y') as date_reunion_fr"),
            ])
            ->orderBy('f.created_at','desc')
            ->get()->toArray();

        return [
            'mission'      => $mission,
            'assignment'   => $assignment,
            'auditeurs'    => $auditeurs,
            'fros'         => $fros,
            'fro'          => $fro,
            'chatMessages' => $chat,
            'auditorRole'  => $role,
            'noMission'    => false,
            'missionId'    => $missionId,
            'assignmentId' => $assignmentId,
            'errors'       => [],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // INDEX  GET /m/audit.core/ac/preparation/reunion-ouverture
    // ══════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return redirect()->route('login');

        $missionId    = (int)($request->mission_id    ?? 0);
        $assignmentId = (int)($request->assignment_id ?? 0);

        if (!$missionId || !$assignmentId) {
            return Inertia::render('dashboards/Auditor/Forms/ReunionOuverture', [
                'noMission'=>true,'mission'=>null,'assignment'=>null,
                'auditeurs'=>[],'fros'=>[],'fro'=>null,'chatMessages'=>[],
                'auditorRole'=>null,'missionId'=>null,'assignmentId'=>null,'errors'=>[],
            ]);
        }

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        // Vérifier que la phase est démarrée
        $assignment = $this->getAssignment($assignmentId, $missionId);
        if ($assignment && $assignment->phase_status === 'pending') {
            return Inertia::render('dashboards/Auditor/Forms/ReunionOuverture', [
                'noMission'  => false,
                'phaseNotStarted' => true,
                'mission'    => $this->getMission($missionId),
                'assignment' => $assignment,
                'auditeurs'  => $this->getAuditeurs($missionId),
                'fros'=>[],'fro'=>null,'chatMessages'=>[],
                'auditorRole'=> $this->getRole($missionId,$auditor->id),
                'missionId'=>$missionId,'assignmentId'=>$assignmentId,'errors'=>[],
            ]);
        }

        $fro = DB::table('mission_phase_fros')
            ->where('assignment_id', $assignmentId)
            ->where('mission_id', $missionId)
            ->first();
        if ($fro) $fro = $this->hydrate($fro);

        return Inertia::render('dashboards/Auditor/Forms/ReunionOuverture',
            $this->buildPayload($missionId, $assignmentId, $auditor, $fro)
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // EDIT  GET /m/audit.core/ac/preparation/reunion-ouverture/{fro}/edit
    // ══════════════════════════════════════════════════════════════════

    public function edit(Request $request, int $froId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return redirect()->route('login');

        $fro = DB::table('mission_phase_fros')->where('id',$froId)->first();
        if (!$fro) abort(404);

        $missionId    = (int)($request->mission_id    ?? $fro->mission_id);
        $assignmentId = (int)($request->assignment_id ?? $fro->assignment_id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        $fro = $this->hydrate($fro);

        return Inertia::render('dashboards/Auditor/Forms/ReunionOuverture',
            $this->buildPayload($missionId, $assignmentId, $auditor, $fro)
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // STORE  POST /m/audit.core/ac/preparation/reunion-ouverture
    // ══════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int)$request->input('mission_id',0);
        $assignmentId = (int)$request->input('assignment_id',0);

        if (!$missionId || !$assignmentId) {
            return back()->withErrors(['mission_id'=>'Contexte de mission manquant.']);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        // Phase doit être démarrée
        $assignment = DB::table('mission_phase_assignments')->where('id',$assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending') {
            return back()->withErrors(['assignment'=>'Démarrez la phase avant de remplir ce formulaire.']);
        }

        // Rediriger vers update si FRO existe déjà
        $existing = DB::table('mission_phase_fros')
            ->where('assignment_id',$assignmentId)->first();
        if ($existing) {
            $request->merge(['mission_id'=>$missionId,'assignment_id'=>$assignmentId]);
            return $this->update($request, $existing->id);
        }

        $request->validate([
            'date_reunion' => 'required|date',
            'lieu'         => 'required|string|max:255',
        ]);

        $code = $this->genCode($missionId);
        $audioPath = $this->handleAudio($request, null, $missionId, $code);

        $froId = DB::table('mission_phase_fros')->insertGetId([
            'assignment_id'      => $assignmentId,
            'mission_id'         => $missionId,
            'code_fro'           => $code,
            'phase_code'         => $request->input('phase_code','P1'),
            'date_reunion'       => $request->input('date_reunion'),
            'heure_debut'        => $request->input('heure_debut') ?: null,
            'heure_fin'          => $request->input('heure_fin')   ?: null,
            'lieu'               => $request->input('lieu'),
            'fait_par'           => $request->input('fait_par'),
            'revue_par'          => $request->input('revue_par'),
            'fichier_audio_path' => $audioPath,
            'ordre_du_jour'      => $request->input('ordre_du_jour','[]'),
            'participants'       => $request->input('participants','[]'),
            'points_generaux'    => $request->input('points_generaux','[]'),
            'preoccupations'     => $request->input('preoccupations','[]'),
            'validation_status'  => 'draft',
            'created_by'         => $auditor->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId,'reunion-ouverture',$auditor->id,$role,'saved',null,'draft');

        $fro = $this->hydrate(DB::table('mission_phase_fros')->where('id',$froId)->first());

        if ($request->wantsJson()) {
            return response()->json(['success'=>true,'fro'=>$fro]);
        }
        return redirect()->route('auditor.fro.edit', [
            'fro'           => $froId,
            'mission_id'    => $missionId,
            'assignment_id' => $assignmentId,
        ])->with('success','FRO créé avec succès.');
    }

    // ══════════════════════════════════════════════════════════════════
    // UPDATE  PUT /m/audit.core/ac/preparation/reunion-ouverture/{fro}
    // ══════════════════════════════════════════════════════════════════

    public function update(Request $request, int $froId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $fro = DB::table('mission_phase_fros')->where('id',$froId)->first();
        if (!$fro) abort(404);

        $missionId    = (int)($request->input('mission_id')    ?? $fro->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $fro->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
        if (!$this->canEdit($fro, $role)) {
            return back()->withErrors(['status' => match($fro->validation_status) {
                'validated' => 'Formulaire validé définitivement — modification impossible.',
                'in_review' => 'Formulaire soumis pour validation — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }]);
        }

        $request->validate([
            'date_reunion' => 'required|date',
            'lieu'         => 'required|string|max:255',
        ]);

        $audioPath = $this->handleAudio($request, $fro->fichier_audio_path, $missionId, $fro->code_fro);

        DB::table('mission_phase_fros')->where('id',$froId)->update([
            'phase_code'         => $request->input('phase_code',$fro->phase_code),
            'date_reunion'       => $request->input('date_reunion'),
            'heure_debut'        => $request->input('heure_debut') ?: null,
            'heure_fin'          => $request->input('heure_fin')   ?: null,
            'lieu'               => $request->input('lieu'),
            'fait_par'           => $request->input('fait_par'),
            'revue_par'          => $request->input('revue_par'),
            'fichier_audio_path' => $audioPath,
            'ordre_du_jour'      => $request->input('ordre_du_jour','[]'),
            'participants'       => $request->input('participants','[]'),
            'points_generaux'    => $request->input('points_generaux','[]'),
            'preoccupations'     => $request->input('preoccupations','[]'),
            'updated_at'         => now(),
        ]);

        $this->log($assignmentId,'reunion-ouverture',$auditor->id,$role,'saved',$fro->validation_status,$fro->validation_status);

        $updated = $this->hydrate(DB::table('mission_phase_fros')->where('id',$froId)->first());
        if ($request->wantsJson()) {
            return response()->json(['success'=>true,'fro'=>$updated]);
        }
        return back()->with('success','FRO mis à jour.');
    }

    // ══════════════════════════════════════════════════════════════════
    // DESTROY  DELETE /m/audit.core/ac/preparation/reunion-ouverture/{fro}
    // ══════════════════════════════════════════════════════════════════

    public function destroy(Request $request, int $froId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $fro = DB::table('mission_phase_fros')->where('id',$froId)->first();
        if (!$fro) abort(404);

        $missionId    = (int)($request->input('mission_id')    ?? $fro->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $fro->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        if ($fro->validation_status === 'validated') {
            return back()->withErrors(['status'=>'Un formulaire validé ne peut pas être supprimé.']);
        }
        // Seuls DM ou le créateur (en draft) peuvent supprimer
        if ($fro->validation_status !== 'draft' && !in_array($role, ['DM'])) {
            return back()->withErrors(['status'=>'Suppression non autorisée à ce stade.']);
        }

        if ($fro->fichier_audio_path) {
            Storage::disk('public')->delete($fro->fichier_audio_path);
        }
        DB::table('mission_phase_fros')->where('id',$froId)->delete();
        $this->log($assignmentId,'reunion-ouverture',$auditor->id,$role,'deleted',$fro->validation_status,null);

        if ($request->wantsJson()) {
            return response()->json(['success'=>true]);
        }
        return back()->with('success','FRO supprimé.');
    }

    // ══════════════════════════════════════════════════════════════════
    // SOUMETTRE  POST /m/audit.core/ac/preparation/reunion-ouverture/{fro}/soumettre
    //   AJ/AS → in_review (pour validation CM/DM)
    // ══════════════════════════════════════════════════════════════════

    public function soumettre(Request $request, int $froId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'],403);

        $fro = DB::table('mission_phase_fros')->where('id',$froId)->first();
        if (!$fro) return response()->json(['error'=>'FRO introuvable'],404);

        $missionId    = (int)($request->input('mission_id')    ?? $fro->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $fro->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error'=>'Accès refusé'],403);
        }
        if ($fro->validation_status === 'validated') {
            return response()->json(['error'=>'Déjà validé'],422);
        }
        if ($fro->validation_status === 'in_review') {
            return response()->json(['error'=>'Déjà soumis pour validation'],422);
        }

        DB::table('mission_phase_fros')->where('id',$froId)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId,'reunion-ouverture',$auditor->id,$role,'submitted','draft','in_review');

        return response()->json(['success'=>true,'status'=>'in_review']);
    }

    // ══════════════════════════════════════════════════════════════════
    // VALIDER  POST /m/audit.core/ac/preparation/reunion-ouverture/{fro}/valider
    //   CM peut valider intermédiaire, DM valide définitivement
    // ══════════════════════════════════════════════════════════════════

    public function valider(Request $request, int $froId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'],403);

        $fro = DB::table('mission_phase_fros')->where('id',$froId)->first();
        if (!$fro) return response()->json(['error'=>'FRO introuvable'],404);

        $missionId    = (int)($request->input('mission_id')    ?? $fro->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $fro->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM','CM'])) {
            return response()->json(['error'=>'Seuls DM/CM peuvent valider'],403);
        }
        if ($fro->validation_status !== 'in_review') {
            return response()->json(['error'=>'Le formulaire doit être soumis avant validation'],422);
        }

        $action = $request->input('action','validate'); // validate | reject
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error'=>'Motif du rejet obligatoire'],422);

            DB::table('mission_phase_fros')->where('id',$froId)->update([
                'validation_status' => 'draft',  // retour en draft pour correction
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId,'reunion-ouverture',$auditor->id,$role,'rejected','in_review','draft',$note);

            return response()->json(['success'=>true,'status'=>'draft','action'=>'rejected']);
        }

        // Validation définitive — uniquement DM
        if ($role !== 'DM') {
            return response()->json(['error'=>'Seul le DM peut valider définitivement'],403);
        }

        DB::table('mission_phase_fros')->where('id',$froId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);

        // Mettre à jour le statut sur l'assignment également
        DB::table('mission_phase_assignments')->where('id',$assignmentId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId,'reunion-ouverture',$auditor->id,$role,'validated','in_review','validated',$note);

        return response()->json(['success'=>true,'status'=>'validated','action'=>'validated']);
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER — Gestion du fichier audio
    // ══════════════════════════════════════════════════════════════════

    private function handleAudio(Request $request, ?string $oldPath, int $missionId, string $code): ?string
    {
        if (!$request->hasFile('audio_file')) return $oldPath;
        if ($oldPath) Storage::disk('public')->delete($oldPath);
        $ext = $request->file('audio_file')->getClientOriginalExtension() ?: 'webm';
        return $request->file('audio_file')
            ->storeAs("fro-audio/{$missionId}", "{$code}.{$ext}", 'public');
    }
}