<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\AuditFrequencyLevel;
use App\Models\AuditImpactLevel;
use App\Models\AuditMatrix;
use App\Models\Audit\RiskType;
use App\Models\Audit\AuditExercise;
use App\Models\Audit\AuditSession;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════════
 * ⚙️ SETTINGS CONTROLLER CORRIGÉ
 * ✅ Utilise les nouvelles tables: audit_frequency_levels, audit_impact_levels, audit_matrix
 * ════════════════════════════════════════════════════════════════════════════════════
 */
class SettingsController extends Controller
{
    private function t()
    {
        return DB::connection('tenant');
    }

    /**
     * GET /settings
     * Affiche le dashboard des paramètres
     */
    public function index(Request $request)
    {
        try {
            // ✅ UTILISER LES NOUVELLES TABLES
            $riskTypes = RiskType::orderBy('sort_order')->get();
            
            $frequencies = $this->t()->table('audit_frequency_levels')
                ->orderBy('level')
                ->get();
            
            $impacts = $this->t()->table('audit_impact_levels')
                ->orderBy('level')
                ->get();
            
            $matrix = $this->t()->table('audit_matrix')
                ->orderBy('impact_level')
                ->orderBy('frequency_level')
                ->get();
            
            $entities = $this->t()->table('entities')
                ->orderBy('name')
                ->get();
            
            $processes = $this->t()->table('processes')
                ->orderBy('code')
                ->get();
            
            $activities = $this->t()->table('activities')
                ->orderBy('code')
                ->get();

            $exercises = AuditExercise::orderBy('created_at', 'desc')->get();
            $sessions = AuditSession::with(['exercise', 'entity'])->orderBy('created_at', 'desc')->get();

            Log::info('✅ Settings loaded', [
                'frequencies' => $frequencies->count(),
                'impacts' => $impacts->count(),
                'matrix' => $matrix->count(),
            ]);

            return Inertia::render('dashboards/Audit/Settings/index', [
                'riskTypes' => $riskTypes,
                'frequencies' => $frequencies,
                'impacts' => $impacts,
                'matrix' => $matrix,
                'entities' => $entities,
                'processes' => $processes,
                'activities' => $activities,
                'exercises' => $exercises,
                'sessions' => $sessions,
            ]);

        } catch (\Exception $e) {
            Log::error('Settings index error: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📊 NIVEAUX DE FRÉQUENCE (audit_frequency_levels)
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeFrequency(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:tenant.audit_frequency_levels,code|max:50',
                'level' => 'required|integer|unique:tenant.audit_frequency_levels,level|min:1|max:5',
                'label' => 'required|unique:tenant.audit_frequency_levels,label|max:255',
                'description' => 'nullable|string|max:1000',
                'color' => 'required|string|max:50',
            ]);

            $id = $this->t()->table('audit_frequency_levels')->insertGetId($validated);

            $frequency = $this->t()->table('audit_frequency_levels')->where('id', $id)->first();

            Log::info('Frequency created', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Niveau de fréquence créé',
                'data' => $frequency
            ], 201);

        } catch (\Exception $e) {
            Log::error('Frequency store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateFrequency(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'code' => "required|unique:tenant.audit_frequency_levels,code,{$id}|max:50",
                'level' => "required|integer|unique:tenant.audit_frequency_levels,level,{$id}|min:1|max:5",
                'label' => "required|unique:tenant.audit_frequency_levels,label,{$id}|max:255",
                'description' => 'nullable|string|max:1000',
                'color' => 'required|string|max:50',
            ]);

            $this->t()->table('audit_frequency_levels')
                ->where('id', $id)
                ->update($validated);

            $frequency = $this->t()->table('audit_frequency_levels')->where('id', $id)->first();

            Log::info('Frequency updated', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Niveau de fréquence modifié',
                'data' => $frequency
            ]);

        } catch (\Exception $e) {
            Log::error('Frequency update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteFrequency($id)
    {
        try {
            // ✅ Vérifier les risques utilisant ce niveau
            $count = $this->t()->table('risks')->where('frequency_level_id', $id)->count();
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "❌ {$count} risque(s) utilise(nt) ce niveau"
                ], 422);
            }

            $this->t()->table('audit_frequency_levels')->where('id', $id)->delete();

            Log::info('Frequency deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Niveau de fréquence supprimé'
            ]);

        } catch (\Exception $e) {
            Log::error('Frequency delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // ⚡ NIVEAUX D'IMPACT (audit_impact_levels)
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeImpact(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:tenant.audit_impact_levels,code|max:50',
                'level' => 'required|integer|unique:tenant.audit_impact_levels,level|min:1|max:5',
                'label' => 'required|unique:tenant.audit_impact_levels,label|max:255',
                'description' => 'nullable|string|max:1000',
                'color' => 'required|string|max:50',
            ]);

            $id = $this->t()->table('audit_impact_levels')->insertGetId($validated);

            $impact = $this->t()->table('audit_impact_levels')->where('id', $id)->first();

            Log::info('Impact created', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Niveau d\'impact créé',
                'data' => $impact
            ], 201);

        } catch (\Exception $e) {
            Log::error('Impact store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateImpact(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'code' => "required|unique:tenant.audit_impact_levels,code,{$id}|max:50",
                'level' => "required|integer|unique:tenant.audit_impact_levels,level,{$id}|min:1|max:5",
                'label' => "required|unique:tenant.audit_impact_levels,label,{$id}|max:255",
                'description' => 'nullable|string|max:1000',
                'color' => 'required|string|max:50',
            ]);

            $this->t()->table('audit_impact_levels')
                ->where('id', $id)
                ->update($validated);

            $impact = $this->t()->table('audit_impact_levels')->where('id', $id)->first();

            Log::info('Impact updated', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Niveau d\'impact modifié',
                'data' => $impact
            ]);

        } catch (\Exception $e) {
            Log::error('Impact update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteImpact($id)
    {
        try {
            // ✅ Vérifier les risques utilisant ce niveau
            $count = $this->t()->table('risks')->where('impact_level_id', $id)->count();
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "❌ {$count} risque(s) utilise(nt) ce niveau"
                ], 422);
            }

            $this->t()->table('audit_impact_levels')->where('id', $id)->delete();

            Log::info('Impact deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Niveau d\'impact supprimé'
            ]);

        } catch (\Exception $e) {
            Log::error('Impact delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📊 MATRICE RISQUES (audit_matrix)
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeMatrix(Request $request)
    {
        try {
            $validated = $request->validate([
                'impact_level' => 'required|integer|min:1|max:5',
                'frequency_level' => 'required|integer|min:1|max:5',
                'label' => 'nullable|string|max:255',
                'qualification' => 'nullable|string|max:255',
                'criticality_score' => 'nullable|integer',
                'color' => 'nullable|string|max:50',
            ]);

            $id = $this->t()->table('audit_matrix')->insertGetId($validated);

            $entry = $this->t()->table('audit_matrix')->where('id', $id)->first();

            Log::info('Matrix entry created', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Entrée matrice créée',
                'data' => $entry
            ], 201);

        } catch (\Exception $e) {
            Log::error('Matrix store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateMatrix(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'label' => 'nullable|string|max:255',
                'qualification' => 'nullable|string|max:255',
                'criticality_score' => 'nullable|integer',
                'color' => 'nullable|string|max:50',
            ]);

            $this->t()->table('audit_matrix')
                ->where('id', $id)
                ->update($validated);

            $entry = $this->t()->table('audit_matrix')->where('id', $id)->first();

            Log::info('Matrix entry updated', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Entrée matrice modifiée',
                'data' => $entry
            ]);

        } catch (\Exception $e) {
            Log::error('Matrix update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteMatrix($id)
    {
        try {
            $this->t()->table('audit_matrix')->where('id', $id)->delete();

            Log::info('Matrix entry deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Entrée matrice supprimée'
            ]);

        } catch (\Exception $e) {
            Log::error('Matrix delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 🏛️ ENTITÉS
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeEntity(Request $request)
    {
        try {
            $validated = $request->validate([
                'code_base' => 'required|unique:tenant.entities,code_base|max:50',
                'name' => 'required|unique:tenant.entities,name|max:255',
                'description' => 'nullable|string|max:1000',
                'level' => 'required|integer|min:0|max:5',
                'parent_id' => 'nullable|exists:tenant.entities,id',
            ]);

            $id = $this->t()->table('entities')->insertGetId($validated);
            $entity = $this->t()->table('entities')->where('id', $id)->first();

            Log::info('Entity created', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Entité créée',
                'data' => $entity
            ], 201);

        } catch (\Exception $e) {
            Log::error('Entity store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateEntity(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'code_base' => "required|unique:tenant.entities,code_base,{$id}|max:50",
                'name' => "required|unique:tenant.entities,name,{$id}|max:255",
                'description' => 'nullable|string|max:1000',
                'level' => 'required|integer|min:0|max:5',
                'parent_id' => 'nullable|exists:tenant.entities,id',
            ]);

            $this->t()->table('entities')->where('id', $id)->update($validated);
            $entity = $this->t()->table('entities')->where('id', $id)->first();

            Log::info('Entity updated', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Entité modifiée',
                'data' => $entity
            ]);

        } catch (\Exception $e) {
            Log::error('Entity update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteEntity($id)
    {
        try {
            $count = $this->t()->table('risks')->where('entity_id', $id)->count();
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "❌ {$count} risque(s) utilise(nt) cette entité"
                ], 422);
            }

            $this->t()->table('entities')->where('id', $id)->delete();

            Log::info('Entity deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Entité supprimée'
            ]);

        } catch (\Exception $e) {
            Log::error('Entity delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // ⚙️ PROCESSUS
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeProcess(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:tenant.processes,code|max:50',
                'name' => 'required|unique:tenant.processes,name|max:255',
                'description' => 'nullable|string|max:1000',
                'entity_id' => 'required|exists:tenant.entities,id',
            ]);

            $id = $this->t()->table('processes')->insertGetId($validated);
            $process = $this->t()->table('processes')->where('id', $id)->first();

            Log::info('Process created', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Processus créé',
                'data' => $process
            ], 201);

        } catch (\Exception $e) {
            Log::error('Process store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProcess(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'code' => "required|unique:tenant.processes,code,{$id}|max:50",
                'name' => "required|unique:tenant.processes,name,{$id}|max:255",
                'description' => 'nullable|string|max:1000',
                'entity_id' => 'required|exists:tenant.entities,id',
            ]);

            $this->t()->table('processes')->where('id', $id)->update($validated);
            $process = $this->t()->table('processes')->where('id', $id)->first();

            Log::info('Process updated', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Processus modifié',
                'data' => $process
            ]);

        } catch (\Exception $e) {
            Log::error('Process update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteProcess($id)
    {
        try {
            $count = $this->t()->table('risks')->where('process_id', $id)->count();
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "❌ {$count} risque(s) utilise(nt) ce processus"
                ], 422);
            }

            $this->t()->table('processes')->where('id', $id)->delete();

            Log::info('Process deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Processus supprimé'
            ]);

        } catch (\Exception $e) {
            Log::error('Process delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📌 ACTIVITÉS
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeActivity(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:tenant.activities,code|max:50',
                'name' => 'required|unique:tenant.activities,name|max:255',
                'description' => 'nullable|string|max:1000',
                'process_id' => 'required|exists:tenant.processes,id',
            ]);

            $id = $this->t()->table('activities')->insertGetId($validated);
            $activity = $this->t()->table('activities')->where('id', $id)->first();

            Log::info('Activity created', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Activité créée',
                'data' => $activity
            ], 201);

        } catch (\Exception $e) {
            Log::error('Activity store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateActivity(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'code' => "required|unique:tenant.activities,code,{$id}|max:50",
                'name' => "required|unique:tenant.activities,name,{$id}|max:255",
                'description' => 'nullable|string|max:1000',
                'process_id' => 'required|exists:tenant.processes,id',
            ]);

            $this->t()->table('activities')->where('id', $id)->update($validated);
            $activity = $this->t()->table('activities')->where('id', $id)->first();

            Log::info('Activity updated', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Activité modifiée',
                'data' => $activity
            ]);

        } catch (\Exception $e) {
            Log::error('Activity update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteActivity($id)
    {
        try {
            $count = $this->t()->table('risks')->where('activity_id', $id)->count();
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "❌ {$count} risque(s) utilise(nt) cette activité"
                ], 422);
            }

            $this->t()->table('activities')->where('id', $id)->delete();

            Log::info('Activity deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Activité supprimée'
            ]);

        } catch (\Exception $e) {
            Log::error('Activity delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📚 EXERCICES D'AUDIT
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeExercise(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:tenant.audit_exercises,code|max:50',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'year' => 'required|integer|min:2000|max:2100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'objectives' => 'nullable|string|max:2000',
                'scope' => 'nullable|string|max:2000',
                'methodology' => 'nullable|string|max:2000',
                'manager_id' => 'nullable|exists:users,id',
            ]);

            $exercise = AuditExercise::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'year' => $validated['year'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'objectives' => $validated['objectives'] ?? null,
                'scope' => $validated['scope'] ?? null,
                'methodology' => $validated['methodology'] ?? null,
                'manager_id' => $validated['manager_id'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft',
                'is_active' => true,
            ]);

            Log::info('Exercise created', ['exercise_id' => $exercise->id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Exercice créé',
                'data' => $exercise
            ], 201);

        } catch (\Exception $e) {
            Log::error('Exercise store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateExercise(Request $request, $id)
    {
        try {
            $exercise = AuditExercise::findOrFail($id);

            $validated = $request->validate([
                'code' => "required|unique:tenant.audit_exercises,code,{$id}|max:50",
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'objectives' => 'nullable|string|max:2000',
                'scope' => 'nullable|string|max:2000',
                'methodology' => 'nullable|string|max:2000',
                'manager_id' => 'nullable|exists:users,id',
            ]);

            $exercise->update($validated);

            Log::info('Exercise updated', ['exercise_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Exercice modifié',
                'data' => $exercise
            ]);

        } catch (\Exception $e) {
            Log::error('Exercise update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function activateExercise($id)
    {
        try {
            $exercise = AuditExercise::findOrFail($id);
            $exercise->update(['status' => 'in_progress', 'is_active' => true]);

            Log::info('Exercise activated', ['exercise_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '🟢 Exercice activé',
                'data' => $exercise
            ]);

        } catch (\Exception $e) {
            Log::error('Exercise activate error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 🎯 SESSIONS D'AUDIT
    // ════════════════════════════════════════════════════════════════════════════════════

    public function storeSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:tenant.audit_sessions,code|max:50',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'exercise_id' => 'required|exists:tenant.audit_exercises,id',
                'entity_id' => 'required|exists:tenant.entities,id',
                'session_date' => 'required|date',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);

            // Désactiver les autres sessions actives de cet exercice
            AuditSession::where('exercise_id', $validated['exercise_id'])
                ->where('status', 'active')
                ->update(['status' => 'paused']);

            $session = AuditSession::create([
                'exercise_id' => $validated['exercise_id'],
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'entity_id' => $validated['entity_id'],
                'year' => AuditExercise::find($validated['exercise_id'])->year ?? 2026,
                'session_date' => $validated['session_date'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            Log::info('Session created', ['session_id' => $session->id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Session créée et activée',
                'data' => $session
            ], 201);

        } catch (\Exception $e) {
            Log::error('Session store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSession(Request $request, $id)
    {
        try {
            $session = AuditSession::findOrFail($id);

            $validated = $request->validate([
                'code' => "required|unique:tenant.audit_sessions,code,{$id}|max:50",
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
            ]);

            $session->update($validated);

            Log::info('Session updated', ['session_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Session modifiée',
                'data' => $session
            ]);

        } catch (\Exception $e) {
            Log::error('Session update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function closeSession($id)
    {
        try {
            $session = AuditSession::findOrFail($id);
            $session->update([
                'status' => 'closed',
                'is_validated' => true,
                'validated_at' => now(),
                'validated_by' => auth()->id(),
            ]);

            Log::info('Session closed', ['session_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => '✅ Session fermée',
                'data' => $session
            ]);

        } catch (\Exception $e) {
            Log::error('Session close error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📊 STATISTIQUES
    // ════════════════════════════════════════════════════════════════════════════════════

    public function getStats()
    {
        try {
            // ✅ UTILISER LES NOUVELLES TABLES
            $stats = [
                'risk_types' => RiskType::count(),
                'frequencies' => $this->t()->table('audit_frequency_levels')->count(),
                'impacts' => $this->t()->table('audit_impact_levels')->count(),
                'matrix_entries' => $this->t()->table('audit_matrix')->count(),
                'entities' => $this->t()->table('entities')->count(),
                'processes' => $this->t()->table('processes')->count(),
                'activities' => $this->t()->table('activities')->count(),
                'exercises' => AuditExercise::count(),
                'sessions' => AuditSession::count(),
                'total_risks' => $this->t()->table('risks')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Stats error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}