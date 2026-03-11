<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class MissionPhaseChatController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    private function getAuditor(): ?Auditor
    {
        $id = Session::get('auditor_id');
        if ($id) {
            $a = Auditor::find($id);
            if ($a) return $a;
        }
        $user = Auth::user();
        if (!$user) return null;
        $a = Auditor::where('email', $user->email)->where('status','active')->first();
        if ($a) Session::put('auditor_id', $a->id);
        return $a;
    }

    private function getRole(int $missionId, int $auditorId): string
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')
            ->where('mpa.mission_id', $missionId)
            ->where('mpa.auditeur_id', $auditorId)
            ->value(DB::raw("COALESCE(mr.code, mpa.role)")) ?? '—';
    }

    /** Vérifie que l'auditeur appartient à la mission */
    private function inMission(int $missionId, int $auditorId): bool
    {
        return DB::table('mission_phase_auditeurs')
            ->where('mission_id', $missionId)
            ->where('auditeur_id', $auditorId)
            ->exists();
    }

    /** Compte les non-lus pour un auditeur sur une mission+phase_type */
    private function countUnread(int $missionId, string $phaseType, int $auditorId): int
    {
        return (int) DB::table('mission_phase_chat as c')
            ->leftJoin('mission_phase_chat_reads as r', function($j) use ($auditorId) {
                $j->on('r.chat_id','=','c.id')
                  ->where('r.auditeur_id','=',$auditorId);
            })
            ->where('c.mission_id', $missionId)
            ->where('c.phase_type', $phaseType)
            ->where('c.author_id', '<>', $auditorId)
            ->whereNull('r.id')
            ->count();
    }

    // ══════════════════════════════════════════════════════════
    // GET /api/missions/{mission}/chat/{phase_type}
    //   Charge tous les messages d'une bulle (phase_type)
    //   avec threads et compteur non-lus
    // ══════════════════════════════════════════════════════════

    public function index(Request $request, int $missionId, string $phaseType)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);
        if (!$this->inMission($missionId, $auditor->id)) {
            return response()->json(['error'=>'Accès refusé'], 403);
        }

        $role = $this->getRole($missionId, $auditor->id);

        // Filtrage visibilité hiérarchique
        // DM voit tout ; les autres voient leurs msgs + ceux des niveaux au-dessus
        $visibleRoles = match($role) {
            'DM'    => ['DM','CM','AS','AJ'],
            'CM'    => ['DM','CM','AS','AJ'],
            'AS'    => ['DM','CM','AS','AJ'],
            default => ['DM','CM','AS','AJ'],  // tous voient tout dans la bulle
        };

        $messages = DB::table('mission_phase_chat as c')
            ->join('auditors as a','c.author_id','=','a.id')
            ->leftJoin('mission_phase_chat_reads as r', function($j) use ($auditor) {
                $j->on('r.chat_id','=','c.id')
                  ->where('r.auditeur_id','=',$auditor->id);
            })
            ->where('c.mission_id', $missionId)
            ->where('c.phase_type', strtoupper($phaseType))
            ->whereIn('c.author_role', $visibleRoles)
            ->select([
                'c.id','c.content','c.type','c.priority','c.is_pinned',
                'c.author_id','c.author_role','c.parent_id',
                'c.assignment_id','c.form_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),''),COALESCE(LEFT(a.first_name,1),''))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("c.created_at as created_at_raw"),
                DB::raw("CASE WHEN c.author_id={$auditor->id} THEN 1 ELSE 0 END as is_mine"),
                DB::raw("CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read"),
            ])
            ->orderBy('c.created_at','asc')
            ->get()
            ->map(function($m) {
                $m->is_mine = (bool)$m->is_mine;
                $m->is_read = (bool)$m->is_read;
                $m->is_pinned = (bool)$m->is_pinned;
                return $m;
            });

        // Marquer automatiquement comme lus
        $unreadIds = $messages->where('is_mine', false)->where('is_read', false)->pluck('id');
        if ($unreadIds->isNotEmpty()) {
            $now = now();
            $inserts = $unreadIds->map(fn($id) => [
                'chat_id'     => $id,
                'auditeur_id' => $auditor->id,
                'read_at'     => $now,
            ])->toArray();
            DB::table('mission_phase_chat_reads')->insertOrIgnore($inserts);
        }

        return response()->json([
            'messages'  => $messages,
            'unread'    => $unreadIds->count(),
            'phase_type'=> strtoupper($phaseType),
            'my_role'   => $role,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // POST /api/missions/{mission}/chat/{phase_type}
    //   Envoie un message dans la bulle
    // ══════════════════════════════════════════════════════════

    public function store(Request $request, int $missionId, string $phaseType)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);
        if (!$this->inMission($missionId, $auditor->id)) {
            return response()->json(['error'=>'Accès refusé'], 403);
        }

        $request->validate([
            'content'       => 'required|string|max:5000',
            'type'          => 'nullable|in:message,instruction,correction,validation,rejet,info',
            'priority'      => 'nullable|in:normal,urgent,bloquant',
            'parent_id'     => 'nullable|integer|exists:mission_phase_chat,id',
            'assignment_id' => 'nullable|integer',
            'form_code'     => 'nullable|string|max:160',
        ]);

        $role = $this->getRole($missionId, $auditor->id);

        // Règle : seuls DM/CM peuvent envoyer des messages de type instruction/correction/rejet
        $type = $request->input('type','message');
        if (in_array($type,['instruction','correction','rejet']) && !in_array($role,['DM','CM'])) {
            $type = 'message';
        }

        $id = DB::table('mission_phase_chat')->insertGetId([
            'mission_id'    => $missionId,
            'phase_type'    => strtoupper($phaseType),
            'assignment_id' => $request->input('assignment_id'),
            'form_code'     => $request->input('form_code'),
            'author_id'     => $auditor->id,
            'author_role'   => $role,
            'parent_id'     => $request->input('parent_id'),
            'content'       => $request->input('content'),
            'type'          => $type,
            'priority'      => $request->input('priority','normal'),
            'is_pinned'     => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Récupérer le message complet
        $message = DB::table('mission_phase_chat as c')
            ->join('auditors as a','c.author_id','=','a.id')
            ->where('c.id', $id)
            ->select([
                'c.id','c.content','c.type','c.priority','c.is_pinned',
                'c.author_id','c.author_role','c.parent_id',
                'c.assignment_id','c.form_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),''),COALESCE(LEFT(a.first_name,1),''))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
            ])
            ->first();

        $message->is_mine   = true;
        $message->is_read   = true;
        $message->is_pinned = false;

        return response()->json(['success'=>true,'message'=>$message], 201);
    }

    // ══════════════════════════════════════════════════════════
    // PATCH /api/missions/{mission}/chat/{message}/pin
    //   Épingler/désépingler un message (CM/DM uniquement)
    // ══════════════════════════════════════════════════════════

    public function pin(Request $request, int $missionId, int $messageId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);

        $role = $this->getRole($missionId, $auditor->id);
        if (!in_array($role, ['DM','CM'])) {
            return response()->json(['error'=>'Seuls DM/CM peuvent épingler'], 403);
        }

        $msg = DB::table('mission_phase_chat')->where('id',$messageId)->first();
        if (!$msg || $msg->mission_id !== $missionId) {
            return response()->json(['error'=>'Message introuvable'], 404);
        }

        $newPin = $msg->is_pinned ? 0 : 1;
        DB::table('mission_phase_chat')->where('id',$messageId)
            ->update(['is_pinned'=>$newPin,'updated_at'=>now()]);

        return response()->json(['success'=>true,'is_pinned'=>(bool)$newPin]);
    }

    // ══════════════════════════════════════════════════════════
    // GET /api/missions/{mission}/chat/unread-counts
    //   Retourne le nombre de non-lus par phase_type
    //   Utilisé par la TopBar pour afficher les badges
    // ══════════════════════════════════════════════════════════

    public function unreadCounts(Request $request, int $missionId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);
        if (!$this->inMission($missionId, $auditor->id)) {
            return response()->json(['error'=>'Accès refusé'], 403);
        }

        $types = ['PREPARATION','VERIFICATION','CONCLUSION','SUIVI'];
        $counts = [];
        foreach ($types as $type) {
            $counts[$type] = $this->countUnread($missionId, $type, $auditor->id);
        }

        return response()->json([
            'counts' => $counts,
            'total'  => array_sum($counts),
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // POST /api/missions/{mission}/chat/{phase_type}/read-all
    //   Marque tous les messages d'une bulle comme lus
    // ══════════════════════════════════════════════════════════

    public function markAllRead(Request $request, int $missionId, string $phaseType)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error'=>'Non autorisé'], 403);

        $ids = DB::table('mission_phase_chat')
            ->where('mission_id', $missionId)
            //->where('phase_type', strtoupper($phaseType))
            ->where('author_id', '<>', $auditor->id)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            $now     = now();
            $inserts = $ids->map(fn($id) => [
                'chat_id'     => $id,
                'auditeur_id' => $auditor->id,
                'read_at'     => $now,
            ])->toArray();
            DB::table('mission_phase_chat_reads')->insertOrIgnore($inserts);
        }

        return response()->json(['success'=>true,'marked'=>$ids->count()]);
    }
}