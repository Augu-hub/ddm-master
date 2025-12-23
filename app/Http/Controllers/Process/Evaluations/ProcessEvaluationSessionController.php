<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessEvaluationSessionController extends Controller
{
    private function t()
    {
        return DB::connection('tenant');
    }

    /**
     * 📋 PAGE PRINCIPALE - GESTION DES SESSIONS
     * GET /process/evaluations/sessions
     */
    public function index(Request $request)
    {
        try {
            $t = $this->t();
            $user = Auth::user();

            if (!$user) {
                return back()->with('error', 'Utilisateur non authentifié');
            }

            Log::info('=== ProcessEvaluationSession.index START ===', ['user_id' => $user->id]);

            // 🔹 Récupérer le contexte entity_id + function_id
            $link = $t->table('function_assignments as fa')
                ->join('entities as e', 'e.id', '=', 'fa.entity_id')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->where('fa.user_id', $user->id)
                ->select('fa.entity_id', 'fa.function_id', 'e.name as entity_name', 'f.name as function_name')
                ->first();

            if (!$link) {
                Log::warning('❌ No function assignment', ['user_id' => $user->id]);
                return Inertia::render('dashboards/Process/Core/Evaluations/Sessions/Index', [
                    'user' => $user,
                    'link' => null,
                    'sessions' => [],
                ]);
            }

            // 🔹 Récupérer toutes les sessions de cet utilisateur
            $sessions = $t->table('process_evaluation_sessions')
                ->where('entity_id', $link->entity_id)
                ->where('function_id', $link->function_id)
                ->orderByDesc('created_at')
                ->get();

            // 🔹 Récupérer les processus disponibles
            $processIds = $t->table('assignments as a')
                ->join('assignment_functions as af', 'a.id', '=', 'af.assignment_id')
                ->where('af.function_id', $link->function_id)
                ->where('a.entity_id', $link->entity_id)
                ->where('a.mpa_type', 'process')
                ->pluck('a.mpa_id');

            $processes = $t->table('processes')
                ->whereIn('id', $processIds)
                ->orderBy('code')
                ->get();

            // 🔹 Augmenter chaque session avec les stats
            foreach ($sessions as $session) {
                $evaluatedCount = $t->table('process_session_axis_evaluations')
                    ->where('session_id', $session->id)
                    ->whereNotNull('criticality_score')
                    ->distinct('process_id')
                    ->count('process_id');

                $avgScore = $t->table('process_session_axis_evaluations')
                    ->where('session_id', $session->id)
                    ->whereNotNull('criticality_score')
                    ->avg('criticality_score') ?? 0;

                $session->evaluated_count = $evaluatedCount;
                $session->session_avg_score = round($avgScore, 2);
                $session->total_processes = count($processes);
            }

            Log::info('=== ProcessEvaluationSession.index DONE ===', ['sessions_count' => count($sessions)]);

            return Inertia::render('dashboards/Process/Core/Evaluations/Sessions/Index', [
                'user' => $user,
                'link' => $link,
                'sessions' => $sessions,
                'processes' => $processes,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ProcessEvaluationSession.index Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * 🟢 CRÉER UNE NOUVELLE SESSION
     * POST /process/evaluations/sessions/create
     */
    public function createSession(Request $request)
    {
        $v = $request->validate([
            'entity_id' => 'required|integer',
            'function_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        try {
            $t = $this->t();

            // Vérifier la limite de 5 sessions actives
            $sessionCount = $t->table('process_evaluation_sessions')
                ->where('entity_id', $v['entity_id'])
                ->where('function_id', $v['function_id'])
                ->whereIn('status', ['open', 'closed'])
                ->count();

            if ($sessionCount >= 5) {
                return response()->json(['error' => 'Limite de 5 sessions actives atteinte'], 422);
            }

            $sessionId = $t->table('process_evaluation_sessions')->insertGetId([
                'entity_id' => $v['entity_id'],
                'function_id' => $v['function_id'],
                'user_id' => Auth::id(),
                'name' => $v['name'],
                'color' => $v['color'],
                'status' => 'open',
                'is_active' => false,  // ⭐ Créée inactive
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('✅ Session créée', ['session_id' => $sessionId]);

            return response()->json(['success' => true, 'session_id' => $sessionId], 201);

        } catch (\Exception $e) {
            Log::error('❌ CreateSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📋 DUPLIQUER UNE SESSION
     * POST /process/evaluations/sessions/duplicate
     */
    public function duplicateSession(Request $request)
    {
        $v = $request->validate([
            'source_session_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        try {
            $t = $this->t();

            $sourceSession = $t->table('process_evaluation_sessions')
                ->where('id', $v['source_session_id'])
                ->first();

            if (!$sourceSession) {
                return response()->json(['error' => 'Session source non trouvée'], 404);
            }

            // Vérifier la limite
            $sessionCount = $t->table('process_evaluation_sessions')
                ->where('entity_id', $sourceSession->entity_id)
                ->where('function_id', $sourceSession->function_id)
                ->whereIn('status', ['open', 'closed'])
                ->count();

            if ($sessionCount >= 5) {
                return response()->json(['error' => 'Limite de 5 sessions atteinte'], 422);
            }

            // Créer nouvelle session (inactive)
            $newSessionId = $t->table('process_evaluation_sessions')->insertGetId([
                'entity_id' => $sourceSession->entity_id,
                'function_id' => $sourceSession->function_id,
                'user_id' => Auth::id(),
                'name' => $v['name'],
                'color' => $sourceSession->color,
                'status' => 'open',
                'is_active' => false,  // ⭐ Créée inactive
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Copier évaluations maturité
            $maturityEvals = $t->table('process_session_maturity_evaluations')
                ->where('session_id', $v['source_session_id'])
                ->get();

            foreach ($maturityEvals as $eval) {
                $t->table('process_session_maturity_evaluations')->insert([
                    'session_id' => $newSessionId,
                    'process_id' => $eval->process_id,
                    'criterion_code' => $eval->criterion_code,
                    'level_score' => $eval->level_score,
                    'evaluated_by' => Auth::id(),
                    'evaluated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Copier évaluations axes
            $axisEvals = $t->table('process_session_axis_evaluations')
                ->where('session_id', $v['source_session_id'])
                ->get();

            foreach ($axisEvals as $eval) {
                $t->table('process_session_axis_evaluations')->insert([
                    'session_id' => $newSessionId,
                    'process_id' => $eval->process_id,
                    'maturity_score' => $eval->maturity_score,
                    'motricity_score' => $eval->motricity_score,
                    'transversality_score' => $eval->transversality_score,
                    'strategic_score' => $eval->strategic_score,
                    'criticality_score' => $eval->criticality_score,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('✅ Session dupliquée', ['new_session_id' => $newSessionId]);

            return response()->json(['success' => true, 'session_id' => $newSessionId], 201);

        } catch (\Exception $e) {
            Log::error('❌ DuplicateSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🔒 FERMER UNE SESSION
     * POST /process/evaluations/sessions/close
     */
    public function closeSession(Request $request)
    {
        $v = $request->validate(['session_id' => 'required|integer']);

        try {
            $this->t()->table('process_evaluation_sessions')
                ->where('id', $v['session_id'])
                ->update(['status' => 'closed', 'is_active' => false, 'updated_at' => now()]);

            Log::info('✅ Session fermée', ['session_id' => $v['session_id']]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('❌ CloseSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📁 ARCHIVER UNE SESSION
     * POST /process/evaluations/sessions/archive
     */
    public function archiveSession(Request $request)
    {
        $v = $request->validate(['session_id' => 'required|integer']);

        try {
            $this->t()->table('process_evaluation_sessions')
                ->where('id', $v['session_id'])
                ->update(['status' => 'archived', 'is_active' => false, 'updated_at' => now()]);

            Log::info('✅ Session archivée', ['session_id' => $v['session_id']]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('❌ ArchiveSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🗑️ SUPPRIMER UNE SESSION
     * POST /process/evaluations/sessions/delete
     */
    public function deleteSession(Request $request)
    {
        $v = $request->validate(['session_id' => 'required|integer']);

        try {
            $t = $this->t();

            // Supprimer les évaluations maturité
            $t->table('process_session_maturity_evaluations')
                ->where('session_id', $v['session_id'])
                ->delete();

            // Supprimer les évaluations axes
            $t->table('process_session_axis_evaluations')
                ->where('session_id', $v['session_id'])
                ->delete();

            // Supprimer la session
            $t->table('process_evaluation_sessions')
                ->where('id', $v['session_id'])
                ->delete();

            Log::info('✅ Session supprimée', ['session_id' => $v['session_id']]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('❌ DeleteSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🟢 ACTIVER UNE SESSION (is_active = true)
     * POST /process/evaluations/sessions/activate
     * 
     * ⭐ Désactive les autres sessions du même user/entity/function
     * ⭐ Active celle-ci dans la BD
     */
    public function activateSession(Request $request)
    {
        $v = $request->validate(['session_id' => 'required|integer']);

        try {
            $t = $this->t();

            // Récupérer la session
            $session = $t->table('process_evaluation_sessions')
                ->where('id', $v['session_id'])
                ->first();

            if (!$session) {
                return response()->json(['error' => 'Session non trouvée'], 404);
            }

            if ($session->status !== 'open') {
                return response()->json(['error' => 'Session non disponible (fermée ou archivée)'], 403);
            }

            // 🔥 IMPORTANT: Désactiver les autres sessions du même contexte
            $t->table('process_evaluation_sessions')
                ->where('entity_id', $session->entity_id)
                ->where('function_id', $session->function_id)
                ->where('id', '!=', $v['session_id'])
                ->update(['is_active' => false, 'updated_at' => now()]);

            // Activer celle-ci
            $t->table('process_evaluation_sessions')
                ->where('id', $v['session_id'])
                ->update(['is_active' => true, 'updated_at' => now()]);

            Log::info('✅ Session activée', ['session_id' => $v['session_id'], 'name' => $session->name]);

            return response()->json([
                'success' => true,
                'session_id' => $v['session_id'],
                'message' => 'Session activée: ' . $session->name
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ActivateSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📊 OBTENIR LA SESSION ACTIVE
     * GET /process/evaluations/sessions/active
     * 
     * Récupère la session actuellement active (is_active = true)
     */
    public function getActiveSession(Request $request)
    {
        try {
            $t = $this->t();
            $user = Auth::user();

            // Récupérer le contexte
            $link = $t->table('function_assignments as fa')
                ->join('entities as e', 'e.id', '=', 'fa.entity_id')
                ->join('functions as f', 'f.id', '=', 'fa.function_id')
                ->where('fa.user_id', $user->id)
                ->select('fa.entity_id', 'fa.function_id')
                ->first();

            if (!$link) {
                return response()->json(['session' => null]);
            }

            // Récupérer la session active
            $session = $t->table('process_evaluation_sessions')
                ->where('entity_id', $link->entity_id)
                ->where('function_id', $link->function_id)
                ->where('is_active', true)
                ->first();

            if (!$session) {
                return response()->json(['session' => null]);
            }

            // Ajouter stats
            $evaluatedCount = $t->table('process_session_axis_evaluations')
                ->where('session_id', $session->id)
                ->whereNotNull('criticality_score')
                ->distinct('process_id')
                ->count('process_id');

            $session->evaluated_count = $evaluatedCount;

            return response()->json(['session' => $session]);

        } catch (\Exception $e) {
            Log::error('❌ GetActiveSession Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}