<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessEvaluationController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    /**
     * 📋 PAGE PRINCIPALE - Charge toutes les données
     */
    public function index(Request $request)
    {
        $t = $this->t();
        $user = Auth::user();

        // 🔹 Récupérer entity_id et function_id de l'utilisateur
        $link = $t->table('function_assignments as fa')
            ->join('entities as e', 'e.id', '=', 'fa.entity_id')
            ->join('functions as f', 'f.id', '=', 'fa.function_id')
            ->where('fa.user_id', $user->id)
            ->select('fa.entity_id', 'fa.function_id', 'e.name as entity_name', 'f.name as function_name')
            ->first();

        if (!$link) {
            return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                'user' => $user,
                'link' => null,
                'processes' => [],
                'sessions' => [],
                'maturityLevels' => [],
            ]);
        }

        // 🔹 Récupérer les processus assignés à la fonction
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

        // 🔹 Récupérer les sessions (open + closed + archived)
        $sessions = $t->table('process_evaluation_sessions')
            ->where('entity_id', $link->entity_id)
            ->where('function_id', $link->function_id)
            ->orderByDesc('created_at')
            ->get();

        // 🔹 Calculer les stats pour chaque session
        foreach ($sessions as $session) {
            $evaluationCount = $t->table('process_session_axis_evaluations')
                ->where('session_id', $session->id)
                ->whereNotNull('criticality_score')
                ->count();

            $avgScore = $t->table('process_session_axis_evaluations')
                ->where('session_id', $session->id)
                ->whereNotNull('criticality_score')
                ->avg('criticality_score');

            $session->evaluated_count = $evaluationCount;
            $session->session_avg_score = round($avgScore ?? 0, 2);
        }

        // 🔹 Récupérer les niveaux de maturité
        $maturityLevels = $t->table('process_maturity_scale_levels as lvl')
            ->join('process_maturity_scales as s', 's.id', '=', 'lvl.scale_id')
            ->select('s.code as scale_code', 's.label as scale_label', 'lvl.level_score', 'lvl.level_label', 'lvl.level_description')
            ->orderBy('s.id')
            ->orderBy('lvl.level_score')
            ->get();

        return Inertia::render('ProcessEvaluation/Index', [
            'user' => $user,
            'link' => $link,
            'processes' => $processes,
            'sessions' => $sessions,
            'maturityLevels' => $maturityLevels,
        ]);
    }

    /**
     * ➕ CRÉER UNE NOUVELLE SESSION
     * 
     * Règle: Utilisateur ne peut avoir max 5 sessions non-archivées
     */
    public function createSession(Request $request)
    {
        $v = $request->validate([
            'entity_id' => 'required|integer',
            'function_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $t = $this->t();

        // ✅ Vérifier que l'utilisateur n'a pas plus de 5 sessions non-archivées
        $sessionCount = $t->table('process_evaluation_sessions')
            ->where('entity_id', $v['entity_id'])
            ->where('function_id', $v['function_id'])
            ->whereIn('status', ['open', 'closed'])
            ->count();

        if ($sessionCount >= 5) {
            return response()->json([
                'error' => 'Vous avez atteint le nombre maximum de sessions (5). Archivez-en une pour en créer une nouvelle.'
            ], 422);
        }

        // ✅ Vérifier l'unicité du nom dans ce contexte
        $exists = $t->table('process_evaluation_sessions')
            ->where('entity_id', $v['entity_id'])
            ->where('function_id', $v['function_id'])
            ->where('name', $v['name'])
            ->whereIn('status', ['open', 'closed'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Une session avec ce nom existe déjà.'
            ], 422);
        }

        // ✅ Créer la session
        $sessionId = $t->table('process_evaluation_sessions')->insertGetId([
            'entity_id' => $v['entity_id'],
            'function_id' => $v['function_id'],
            'user_id' => Auth::id(),
            'name' => $v['name'],
            'color' => $v['color'],
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'message' => 'Session créée avec succès'
        ], 201);
    }

    /**
     * 📋 DUPLIQUER UNE SESSION
     * 
     * Copie toutes les évaluations de la session source vers une nouvelle session
     */
    public function duplicateSession(Request $request)
    {
        $v = $request->validate([
            'source_session_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $t = $this->t();

        // ✅ Récupérer la session source
        $sourceSession = $t->table('process_evaluation_sessions')
            ->where('id', $v['source_session_id'])
            ->first();

        if (!$sourceSession) {
            return response()->json([
                'error' => 'Session source non trouvée'
            ], 404);
        }

        // ✅ Vérifier limite de sessions
        $sessionCount = $t->table('process_evaluation_sessions')
            ->where('entity_id', $sourceSession->entity_id)
            ->where('function_id', $sourceSession->function_id)
            ->whereIn('status', ['open', 'closed'])
            ->count();

        if ($sessionCount >= 5) {
            return response()->json([
                'error' => 'Limite de 5 sessions atteinte. Archivez-en une d\'abord.'
            ], 422);
        }

        // ✅ Créer la nouvelle session
        $newSessionId = $t->table('process_evaluation_sessions')->insertGetId([
            'entity_id' => $sourceSession->entity_id,
            'function_id' => $sourceSession->function_id,
            'user_id' => Auth::id(),
            'name' => $v['name'],
            'color' => $sourceSession->color,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ✅ Copier les évaluations de maturité
        $maturityEvals = $t->table('process_session_maturity_evaluations')
            ->where('session_id', $sourceSession->id)
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

        // ✅ Copier les évaluations des axes
        $axisEvals = $t->table('process_session_axis_evaluations')
            ->where('session_id', $sourceSession->id)
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

        return response()->json([
            'success' => true,
            'session_id' => $newSessionId,
            'message' => 'Session dupliquée avec succès'
        ], 201);
    }

    /**
     * 🔒 FERMER UNE SESSION
     */
    public function closeSession(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer'
        ]);

        $t = $this->t();

        $session = $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session non trouvée'], 404);
        }

        if ($session->status !== 'open') {
            return response()->json(['error' => 'Seules les sessions ouvertes peuvent être fermées'], 422);
        }

        $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->update(['status' => 'closed', 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Session fermée'
        ]);
    }

    /**
     * 📁 ARCHIVER UNE SESSION
     */
    public function archiveSession(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer'
        ]);

        $t = $this->t();

        $session = $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session non trouvée'], 404);
        }

        $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->update(['status' => 'archived', 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Session archivée'
        ]);
    }

    /**
     * 🗑️ SUPPRIMER UNE SESSION
     * 
     * Supprime la session ET toutes ses évaluations
     */
    public function deleteSession(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer'
        ]);

        $t = $this->t();

        $session = $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session non trouvée'], 404);
        }

        // ✅ Supprimer les évaluations d'abord
        $t->table('process_session_maturity_evaluations')
            ->where('session_id', $v['session_id'])
            ->delete();

        $t->table('process_session_axis_evaluations')
            ->where('session_id', $v['session_id'])
            ->delete();

        // ✅ Supprimer la session
        $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session supprimée'
        ]);
    }

    /**
     * 📊 CHARGER LES ÉVALUATIONS D'UN PROCESSUS
     */
    public function loadEvaluations(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer',
            'process_id' => 'required|integer'
        ]);

        $t = $this->t();

        // ✅ Charger les évaluations de maturité
        $maturityEvals = $t->table('process_session_maturity_evaluations')
            ->where('session_id', $v['session_id'])
            ->where('process_id', $v['process_id'])
            ->pluck('level_score', 'criterion_code');

        // ✅ Charger les évaluations des axes
        $axisEvals = $t->table('process_session_axis_evaluations')
            ->where('session_id', $v['session_id'])
            ->where('process_id', $v['process_id'])
            ->first();

        return response()->json([
            'maturity' => $maturityEvals ?? [],
            'axes' => $axisEvals ?? [],
        ]);
    }

    /**
     * 💾 ENREGISTRER LA MATURITÉ
     */
    public function saveMaturity(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer',
            'evaluations' => 'required|array',
            'evaluations.*.process_id' => 'required|integer',
            'evaluations.*.criterion_code' => 'required|string',
            'evaluations.*.level_score' => 'required|integer|min:1|max:5',
        ]);

        $t = $this->t();
        $sessionId = $v['session_id'];

        // ✅ Vérifier que la session est ouverte
        $session = $t->table('process_evaluation_sessions')
            ->where('id', $sessionId)
            ->first();

        if (!$session || $session->status !== 'open') {
            return response()->json(['error' => 'Session fermée ou introuvable'], 403);
        }

        // ✅ Enregistrer chaque évaluation
        foreach ($v['evaluations'] as $eval) {
            $t->table('process_session_maturity_evaluations')->updateOrInsert(
                [
                    'session_id' => $sessionId,
                    'process_id' => $eval['process_id'],
                    'criterion_code' => $eval['criterion_code'],
                ],
                [
                    'level_score' => $eval['level_score'],
                    'evaluated_by' => Auth::id(),
                    'evaluated_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // ✅ Recalculer les scores
        $this->calculateProcessScores($sessionId, $eval['process_id']);

        return response()->json([
            'success' => true,
            'message' => 'Maturité enregistrée'
        ]);
    }

    /**
     * 📈 ENREGISTRER UN AXE (motricité, transversalité, stratégique)
     */
    public function saveAxis(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer',
            'process_id' => 'required|integer',
            'axis' => 'required|in:motricity,transversality,strategic',
            'score' => 'required|numeric|min:1|max:5',
        ]);

        $t = $this->t();

        // ✅ Vérifier que la session est ouverte
        $session = $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id'])
            ->first();

        if (!$session || $session->status !== 'open') {
            return response()->json(['error' => 'Session fermée ou introuvable'], 403);
        }

        // ✅ Préparer le champ à mettre à jour
        $field = $v['axis'] . '_score';

        // ✅ Mettre à jour ou créer
        $t->table('process_session_axis_evaluations')->updateOrInsert(
            [
                'session_id' => $v['session_id'],
                'process_id' => $v['process_id'],
            ],
            [
                $field => $v['score'],
                'updated_at' => now(),
            ]
        );

        // ✅ Recalculer la criticité
        $this->calculateProcessScores($v['session_id'], $v['process_id']);

        return response()->json([
            'success' => true,
            'message' => ucfirst($v['axis']) . ' enregistrée'
        ]);
    }

    /**
     * 🧮 CALCULER LES SCORES D'UN PROCESSUS
     */
    private function calculateProcessScores($sessionId, $processId)
    {
        $t = $this->t();

        // ✅ Calculer la moyenne de maturité
        $maturityAvg = $t->table('process_session_maturity_evaluations')
            ->where('session_id', $sessionId)
            ->where('process_id', $processId)
            ->avg('level_score');

        // ✅ Mettre à jour ou créer la ligne axis_evaluations
        $t->table('process_session_axis_evaluations')->updateOrInsert(
            [
                'session_id' => $sessionId,
                'process_id' => $processId,
            ],
            [
                'maturity_score' => $maturityAvg ? round($maturityAvg, 2) : null,
                'updated_at' => now(),
            ]
        );

        // ✅ Recalculer la criticité (moyenne de tous les axes)
        $axisEval = $t->table('process_session_axis_evaluations')
            ->where('session_id', $sessionId)
            ->where('process_id', $processId)
            ->first();

        if ($axisEval) {
            $scores = array_filter([
                $axisEval->maturity_score,
                $axisEval->motricity_score,
                $axisEval->transversality_score,
                $axisEval->strategic_score,
            ]);

            $criticality = !empty($scores) 
                ? round(array_sum($scores) / count($scores), 2) 
                : null;

            $t->table('process_session_axis_evaluations')
                ->where('session_id', $sessionId)
                ->where('process_id', $processId)
                ->update([
                    'criticality_score' => $criticality,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * 📊 CHARGER LES DONNÉES POUR COMPARAISON RADAR
     * 
     * Retourne les données de tous les processus dans une session
     * Pour afficher les radars de comparaison
     */
    public function getSessionRadarData(Request $request)
    {
        $v = $request->validate([
            'session_id' => 'required|integer'
        ]);

        $t = $this->t();

        // ✅ Récupérer toutes les évaluations des axes pour cette session
        $evaluations = $t->table('process_session_axis_evaluations')
            ->where('session_id', $v['session_id'])
            ->get();

        return response()->json([
            'session_id' => $v['session_id'],
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * 📊 COMPARER RADAR ENTRE DEUX SESSIONS
     * 
     * Compare les scores d'un même processus dans deux sessions
     */
    public function compareRadar(Request $request)
    {
        $v = $request->validate([
            'process_id' => 'required|integer',
            'session_id_1' => 'required|integer',
            'session_id_2' => 'required|integer',
        ]);

        $t = $this->t();

        // ✅ Récupérer les données des deux sessions
        $eval1 = $t->table('process_session_axis_evaluations')
            ->where('session_id', $v['session_id_1'])
            ->where('process_id', $v['process_id'])
            ->first();

        $eval2 = $t->table('process_session_axis_evaluations')
            ->where('session_id', $v['session_id_2'])
            ->where('process_id', $v['process_id'])
            ->first();

        // ✅ Récupérer les noms des sessions
        $session1 = $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id_1'])
            ->select('name', 'color')
            ->first();

        $session2 = $t->table('process_evaluation_sessions')
            ->where('id', $v['session_id_2'])
            ->select('name', 'color')
            ->first();

        return response()->json([
            'process_id' => $v['process_id'],
            'session1' => [
                'name' => $session1?->name,
                'color' => $session1?->color,
                'data' => $eval1,
            ],
            'session2' => [
                'name' => $session2?->name,
                'color' => $session2?->color,
                'data' => $eval2,
            ],
        ]);
    }
}