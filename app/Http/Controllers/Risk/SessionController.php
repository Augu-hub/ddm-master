<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SessionController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════════
    // PAGE PRINCIPALE
    // GET /m/risk.core/sessions
    // ═══════════════════════════════════════════════════════════════════════
    public function index()
    {
        // Toutes les entités avec parent
        $entities = DB::table('entities as e')
            ->leftJoin('entities as p', 'p.id', '=', 'e.parent_id')
            ->select(
                'e.id', 'e.name', 'e.code_base', 'e.level',
                'e.parent_id', 'e.leader',
                DB::raw("COALESCE(p.name,'') as parent_name")
            )
            ->orderBy('e.level')
            ->orderBy('e.name')
            ->get();

        // Toutes les liaisons avec utilisateurs déjà enregistrés
        // On lit le user depuis function_assignments.user_id
        // (celui qui a été affecté à cette fonction dans cette entité)
        $assignments = DB::table('function_assignments as fa')
            ->join('entities as e',   'e.id', '=', 'fa.entity_id')
            ->join('functions as f',  'f.id', '=', 'fa.function_id')
            ->leftJoin('users as u',  'u.id', '=', 'fa.user_id')
            ->select(
                'fa.id',
                'fa.entity_id',
                'fa.function_id',
                'fa.user_id',
                'fa.is_risk_admin',
                'e.name        as entity_name',
                'e.code_base   as entity_code',
                'f.name        as function_name',
                'f.character   as function_code',
                'f.parent_id   as function_parent_id',
                DB::raw("COALESCE(u.name,'')       as user_name"),
                DB::raw("COALESCE(u.email,'')      as user_email"),
                DB::raw("COALESCE(u.matricule,'')  as user_matricule")
            )
            ->orderBy('e.level')
            ->orderBy('e.name')
            ->orderByDesc('fa.is_risk_admin')
            ->orderBy('f.name')
            ->get();

        // Stats
        $stats = [
            'entities'    => DB::table('entities')->count(),
            'assignments' => DB::table('function_assignments')->count(),
            'with_user'   => DB::table('function_assignments')->whereNotNull('user_id')->count(),
            'admins'      => DB::table('function_assignments')->where('is_risk_admin', 1)->count(),
        ];

        return Inertia::render('dashboards/Risk/Sessions', [
            'entities'    => $entities,
            'assignments' => $assignments,
            'stats'       => $stats,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DÉSIGNER / RETIRER L'ADMIN RISQUE D'UNE ENTITÉ
    // PATCH /m/risk.core/sessions/assignments/{id}/set-admin
    // ═══════════════════════════════════════════════════════════════════════
    public function setAdmin(int $id)
    {
        $assignment = DB::table('function_assignments')->where('id', $id)->first();
        abort_if(!$assignment, 404);

        if (!$assignment->user_id) {
            return back()->withErrors([
                'admin' => 'Cette fonction n\'a pas d\'utilisateur affecté.',
            ]);
        }

        $isCurrentlyAdmin = (bool) $assignment->is_risk_admin;

        // Retire l'admin actuel de cette entité
        DB::table('function_assignments')
            ->where('entity_id', $assignment->entity_id)
            ->where('is_risk_admin', 1)
            ->update(['is_risk_admin' => 0, 'updated_at' => now()]);

        if (!$isCurrentlyAdmin) {
            // Désigne le nouveau
            DB::table('function_assignments')
                ->where('id', $id)
                ->update(['is_risk_admin' => 1, 'updated_at' => now()]);

            $userName = DB::table('users')->where('id', $assignment->user_id)->value('name');
            return back()->with('success', "« $userName » désigné administrateur risque.");
        }

        return back()->with('success', 'Administrateur risque retiré.');
    }
}