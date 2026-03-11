<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Audit\Risk;
use App\Models\Audit\RiskType;
use App\Models\Audit\AuditFrequencyLevel;
use App\Models\Audit\AuditImpactLevel;
use App\Models\Audit\AuditMatrix;
use App\Models\Audit\AuditSession;
use App\Models\Audit\AuditExercise;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════════
 * 🎯 RISK CONTROLLER FINAL V8 - AVEC DONNÉES AUDIT CHARGÉES
 * ════════════════════════════════════════════════════════════════════════════════════
 * 
 * ✅ Utilise audit_frequency_levels, audit_impact_levels, audit_matrix
 * ✅ Données HEX colors pré-chargées depuis migration
 * ✅ Une seule session ACTIVE à la fois
 * ✅ Criticité = frequency_level * impact_level
 * ✅ Entités/Processus/Activités chargées
 * ✅ AI suggestions et control procedure generation
 * ✅ Multi-tenant support
 */
class RiskController extends Controller
{
    private function t()
    {
        return DB::connection('tenant');
    }

    /**
     * GET /dashboard/audit/risk - Dashboard principal
     * 🎯 Affiche la session active + risques + statistiques
     */
    public function index(Request $request)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;

            // ✅ RÉCUPÉRER LA SESSION ACTIVE (UNE SEULE)
            $activeSession = AuditSession::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();

            // ✅ CHARGER LES DONNÉES - AUDIT TABLES AVEC COULEURS HEX
            $riskTypes = RiskType::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            
            // ✅ AUDIT_FREQUENCY_LEVELS (avec couleurs HEX)
            $frequencies = AuditFrequencyLevel::where('tenant_id', $tenantId)
                ->orderBy('level')
                ->get()
                ->map(fn($f) => [
                    'id' => $f->id,
                    'code' => $f->code,
                    'label' => $f->label,
                    'level' => $f->level,
                    'color' => $f->color, // ✅ HEX color #RRGGBB
                    'description' => $f->description,
                ]);
            
            // ✅ AUDIT_IMPACT_LEVELS (avec couleurs HEX)
            $impacts = AuditImpactLevel::where('tenant_id', $tenantId)
                ->orderBy('level')
                ->get()
                ->map(fn($i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'label' => $i->label,
                    'level' => $i->level,
                    'color' => $i->color, // ✅ HEX color #RRGGBB
                    'description' => $i->description,
                ]);

            // ✅ AUDIT_MATRIX (avec couleurs HEX)
            $matrix = AuditMatrix::where('tenant_id', $tenantId)
                ->orderBy('impact_level')
                ->orderBy('frequency_level')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'impact_level' => $m->impact_level,
                    'frequency_level' => $m->frequency_level,
                    'criticality_score' => $m->criticality_score,
                    'label' => $m->label,
                    'qualification' => $m->qualification,
                    'color' => $m->color, // ✅ HEX color #RRGGBB
                ]);

            // ✅ CHARGER ENTITÉS
            $entities = [];
            try {
                $entities = $this->t()->table('entities')
                    ->where('level', '<=', 1)
                    ->orderBy('name')
                    ->get()
                    ->map(fn($e) => (array) $e)
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur chargement entités: ' . $e->getMessage());
            }

            // ✅ CHARGER PROCESSUS
            $processes = [];
            try {
                $processes = $this->t()->table('processes')
                    ->orderBy('code')
                    ->get()
                    ->map(fn($p) => (array) $p)
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur chargement processus: ' . $e->getMessage());
            }

            // ✅ CHARGER ACTIVITÉS
            $activities = [];
            try {
                $activities = $this->t()->table('activities')
                    ->orderBy('code')
                    ->get()
                    ->map(fn($a) => (array) $a)
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('⚠️ Erreur chargement activités: ' . $e->getMessage());
            }

            Log::info('📊 Données chargées', [
                'frequencies_count' => $frequencies->count(),
                'impacts_count' => $impacts->count(),
                'matrix_count' => $matrix->count(),
                'entities_count' => count($entities),
                'processes_count' => count($processes),
                'activities_count' => count($activities),
            ]);

            // ✅ LISTER TOUTES LES SESSIONS (POUR MODAL SWITCH)
            $allSessions = AuditSession::where('tenant_id', $tenantId)
                ->with(['exercise', 'entity'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($session) {
                    $risksCount = Risk::where('audit_session_id', $session->id)->count();
                    
                    // ✅ COMPTER RISQUES CRITIQUES = frequency * impact >= 12
                    $criticalCount = Risk::where('audit_session_id', $session->id)
                        ->whereRaw('(frequency_level_id * impact_level_id) >= 12')
                        ->count();

                    return [
                        'id' => $session->id,
                        'code' => $session->code,
                        'name' => $session->name,
                        'status' => $session->status,
                        'is_active' => $session->status === 'active',
                        'exercise_name' => $session->exercise?->name ?? 'N/A',
                        'entity_name' => $session->entity?->name ?? 'N/A',
                        'start_date' => $session->start_date?->format('Y-m-d'),
                        'end_date' => $session->end_date?->format('Y-m-d'),
                        'risks_count' => $risksCount,
                        'critical_count' => $criticalCount,
                    ];
                });

            // ✅ SI PAS DE SESSION ACTIVE
            if (!$activeSession) {
                return Inertia::render('dashboards/Audit/index', [
                    'error' => '⚠️ Aucune session active',
                    'activeSession' => null,
                    'allSessions' => $allSessions,
                    'entities' => $entities,
                    'processes' => $processes,
                    'activities' => $activities,
                    'riskTypes' => $riskTypes,
                    'frequencies' => $frequencies,
                    'impacts' => $impacts,
                    'matrix' => $matrix,
                    'initialRisks' => [],
                    'statistics' => $this->getEmptyStatistics(),
                ]);
            }

            // ✅ CHARGER RISQUES DE LA SESSION ACTIVE SEULEMENT
            $risks = Risk::where('tenant_id', $tenantId)
                ->where('audit_session_id', $activeSession->id)
                ->with(['riskType'])
                ->orderBy('code')
                ->get()
                ->map(fn($r) => $this->formatRisk($r));

            // ✅ CALCULER STATISTIQUES
            $statistics = $this->computeStatistics($risks);

            Log::info('✅ Risk dashboard loaded', [
                'session_id' => $activeSession->id,
                'session_code' => $activeSession->code,
                'risks_count' => $risks->count(),
                'tenant_id' => $tenantId,
            ]);

            return Inertia::render('dashboards/Audit/index', [
                'activeSession' => [
                    'id' => $activeSession->id,
                    'code' => $activeSession->code,
                    'name' => $activeSession->name,
                    'status' => $activeSession->status,
                    'exercise_name' => $activeSession->exercise?->name ?? 'N/A',
                    'entity_name' => $activeSession->entity?->name ?? 'N/A',
                ],
                'allSessions' => $allSessions,
                'entities' => $entities,
                'processes' => $processes,
                'activities' => $activities,
                'riskTypes' => $riskTypes,
                'frequencies' => $frequencies,
                'impacts' => $impacts,
                'matrix' => $matrix,
                'initialRisks' => $risks->toArray(),
                'statistics' => $statistics,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Dashboard error: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/m/risk/switch-session
     * 🔄 Change la session active
     */
    public function switchSession(Request $request)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;
            
            $validated = $request->validate([
                'session_id' => 'required|exists:audit_sessions,id',
            ]);

            // ✅ DÉSACTIVER AUTRES SESSIONS
            AuditSession::where('tenant_id', $tenantId)
                ->where('id', '!=', $validated['session_id'])
                ->where('status', 'active')
                ->update(['status' => 'paused', 'updated_at' => now()]);

            // ✅ ACTIVER NOUVELLE SESSION
            $newSession = AuditSession::where('tenant_id', $tenantId)
                ->findOrFail($validated['session_id']);
            $newSession->update(['status' => 'active', 'updated_at' => now()]);

            Log::info('✅ Session switched', [
                'session_id' => $newSession->id,
                'code' => $newSession->code,
                'tenant_id' => $tenantId,
            ]);

            return response()->json([
                'success' => true,
                'message' => "✅ Session '{$newSession->code}' activée",
                'session' => [
                    'id' => $newSession->id,
                    'code' => $newSession->code,
                    'name' => $newSession->name,
                    'status' => 'active',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Switch session error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/m/risk/suggest-ai
     * 🤖 Suggestions de libellés par type
     */
    public function suggestAI(Request $request)
    {
        try {
            $validated = $request->validate([
                'process_name' => 'required|string|max:255',
                'activity_name' => 'required|string|max:255',
                'risk_type_name' => 'required|string|max:255',
            ]);

            $suggestions = $this->generateRiskSuggestions(
                $validated['process_name'],
                $validated['activity_name'],
                $validated['risk_type_name']
            );

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);

        } catch (\Exception $e) {
            Log::error('❌ IA suggestions error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/m/risk/suggest-control
     * 🛡️ Génère procédure de contrôle
     */
    public function suggestControl(Request $request)
    {
        try {
            $validated = $request->validate([
                'risk_label' => 'required|string|max:500',
                'activity_name' => 'required|string|max:255',
                'process_name' => 'required|string|max:255',
            ]);

            $controlProcedure = $this->generateControlProcedure(
                $validated['risk_label'],
                $validated['activity_name'],
                $validated['process_name']
            );

            return response()->json([
                'success' => true,
                'control_procedure' => $controlProcedure
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Control procedure error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/m/risk
     * 📝 Crée risque dans SESSION ACTIVE UNIQUEMENT
     */
    public function store(Request $request)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;
            
            $validated = $request->validate([
                'code' => 'nullable|string|unique:risks,code',
                'label' => 'required|string|max:500',
                'description' => 'nullable|string|max:2000',
                'risk_type_id' => 'required|integer|exists:risk_types,id',
                'frequency_level_id' => 'required|integer|exists:audit_frequency_levels,id',
                'frequency_net' => 'nullable|numeric|min:0|max:5',
                'impact_level_id' => 'required|integer|exists:audit_impact_levels,id',
                'impact_net' => 'nullable|numeric|min:0|max:5',
                'entity_id' => 'nullable|integer',
                'process_id' => 'nullable|integer',
                'activity_id' => 'nullable|integer',
                'owner' => 'nullable|string|max:255',
                'control_procedure' => 'nullable|string|max:5000',
                'status' => 'nullable|in:identified,assessed,mitigated,monitored,closed',
            ]);

            // ✅ RÉCUPÉRER SESSION ACTIVE (OBLIGATOIRE)
            $activeSession = AuditSession::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();
            
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'error' => '❌ Aucune session active. Créez ou activez une session d\'audit.',
                ], 422);
            }

            // ✅ GÉNÉRER CODE SI ABSENT
            if (empty($validated['code'])) {
                $validated['code'] = $this->generateIntelligentCode($validated['risk_type_id']);
            }

            // ✅ VÉRIFIER UNICITÉ CODE
            if (Risk::where('code', $validated['code'])->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => "❌ Code '{$validated['code']}' existe déjà",
                ], 422);
            }

            // ✅ CRÉER RISQUE DANS SESSION ACTIVE
            $risk = Risk::create([
                'tenant_id' => $tenantId,
                'audit_session_id' => $activeSession->id, // ✅ SESSION ACTIVE
                'code' => $validated['code'],
                'label' => $validated['label'],
                'description' => $validated['description'] ?? null,
                'risk_type_id' => $validated['risk_type_id'],
                'frequency_level_id' => $validated['frequency_level_id'],
                'frequency_net' => $validated['frequency_net'] ?? null,
                'impact_level_id' => $validated['impact_level_id'],
                'impact_net' => $validated['impact_net'] ?? null,
                'entity_id' => $validated['entity_id'] ?? null,
                'process_id' => $validated['process_id'] ?? null,
                'activity_id' => $validated['activity_id'] ?? null,
                'owner' => $validated['owner'] ?? null,
                'control_procedure' => $validated['control_procedure'] ?? null,
                'status' => $validated['status'] ?? 'identified',
                'year' => now()->year,
                'created_by' => auth()->id(),
            ]);

            Log::info('✅ Risk created', [
                'risk_id' => $risk->id,
                'code' => $risk->code,
                'session_id' => $activeSession->id,
                'tenant_id' => $tenantId,
            ]);

            return response()->json([
                'success' => true,
                'message' => "✅ Risque '{$risk->code}' créé dans {$activeSession->code}",
                'risk' => $this->formatRisk($risk),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('❌ Risk store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/m/risk/{id}
     * ✏️ Modifie risque
     */
    public function update(Request $request, Risk $risk)
    {
        try {
            $validated = $request->validate([
                'code' => "nullable|string|unique:risks,code,{$risk->id}",
                'label' => 'sometimes|string|max:500',
                'description' => 'nullable|string|max:2000',
                'risk_type_id' => 'nullable|integer|exists:risk_types,id',
                'frequency_level_id' => 'nullable|integer|exists:audit_frequency_levels,id',
                'frequency_net' => 'nullable|numeric|min:0|max:5',
                'impact_level_id' => 'nullable|integer|exists:audit_impact_levels,id',
                'impact_net' => 'nullable|numeric|min:0|max:5',
                'owner' => 'nullable|string|max:255',
                'control_procedure' => 'nullable|string|max:5000',
                'status' => 'nullable|in:identified,assessed,mitigated,monitored,closed',
            ]);

            $risk->update(array_merge($validated, [
                'updated_by' => auth()->id(),
            ]));

            Log::info('✅ Risk updated', ['risk_id' => $risk->id]);

            return response()->json([
                'success' => true,
                'message' => "✅ Risque '{$risk->code}' modifié",
                'risk' => $this->formatRisk($risk->fresh()),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Risk update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/m/risk/{id}
     * 🗑️ Supprime risque
     */
    public function destroy(Risk $risk)
    {
        try {
            $riskCode = $risk->code;
            $risk->delete();

            Log::info('✅ Risk deleted', ['code' => $riskCode]);

            return response()->json([
                'success' => true,
                'message' => "✅ Risque '{$riskCode}' supprimé",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Risk destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/m/risk/{id}
     */
    public function show(Risk $risk)
    {
        return response()->json(['success' => true, 'risk' => $this->formatRisk($risk)]);
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // 🔧 UTILITAIRES PRIVÉS
    // ════════════════════════════════════════════════════════════════════════════════════

    private function formatRisk(Risk $risk): array
    {
        // ✅ CALCULER CRITICITÉ = frequency_level_id * impact_level_id
        $criticityGross = ($risk->frequency_level_id && $risk->impact_level_id) 
            ? $risk->frequency_level_id * $risk->impact_level_id 
            : null;

        // ✅ CALCULER CRITICITÉ NETTE = frequency_net * impact_net
        $criticityNet = ($risk->frequency_net && $risk->impact_net) 
            ? (int) ceil($risk->frequency_net * $risk->impact_net) 
            : null;

        return [
            'id' => $risk->id,
            'code' => $risk->code,
            'label' => $risk->label,
            'description' => $risk->description,
            'risk_type_id' => $risk->risk_type_id,
            'frequency_level_id' => $risk->frequency_level_id,
            'frequency_net' => $risk->frequency_net,
            'impact_level_id' => $risk->impact_level_id,
            'impact_net' => $risk->impact_net,
            'criticality_gross' => $criticityGross,
            'criticality_net' => $criticityNet,
            'owner' => $risk->owner,
            'control_procedure' => $risk->control_procedure,
            'status' => $risk->status,
            'entity_id' => $risk->entity_id,
            'process_id' => $risk->process_id,
            'activity_id' => $risk->activity_id,
            'audit_session_id' => $risk->audit_session_id,
            'created_at' => $risk->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function generateRiskSuggestions(string $processName, string $activityName, string $riskTypeName): array
    {
        $typeCode = strtoupper(substr($riskTypeName, 0, 2));

        $suggestions = [
            'FI' => [
                'Erreurs de saisie en comptabilité',
                'Détournement de fonds',
                'Non-rapprochement des comptes bancaires',
                'Fraude aux paiements',
                'Erreurs de facturation',
            ],
            'RC' => [
                'Non-respect RGPD',
                'Violations réglementaires',
                'Manque de documentation obligatoire',
                'Non-conformité aux normes OHADA',
                'Infractions fiscales',
            ],
            'RI' => [
                'Perte de données critiques',
                'Cyberattaque ou intrusion',
                'Panne du système d\'information',
                'Accès non autorisé aux données',
                'Perte de disponibilité des services',
            ],
            'RO' => [
                'Interruption d\'activité',
                'Erreur dans les processus',
                'Défaillance des ressources humaines',
                'Problème de qualité de service',
                'Manque de formation du personnel',
            ],
            'RS' => [
                'Changement de marché non anticipé',
                'Perte de compétitivité',
                'Défaillance de partenaires clés',
                'Perte de réputation',
                'Nouvelles réglementations',
            ],
        ];

        return $suggestions[$typeCode] ?? [];
    }

    private function generateControlProcedure(string $riskLabel, string $activityName, string $processName): string
    {
        return "🔒 PROCÉDURE DE CONTRÔLE\n\n"
            . "Risque: $riskLabel\n"
            . "Activité: $activityName\n"
            . "Processus: $processName\n\n"
            . "ÉTAPES:\n"
            . "1. Identifier les critères d'acceptabilité\n"
            . "2. Exécuter le test de contrôle\n"
            . "3. Documenter les résultats\n"
            . "4. Approuver par le responsable\n"
            . "5. Archiver les preuves";
    }

    private function generateIntelligentCode(?int $riskTypeId): string
    {
        try {
            if (!$riskTypeId) {
                return 'RX-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }

            $riskType = RiskType::find($riskTypeId);
            if (!$riskType || !$riskType->code) {
                return 'RX-001';
            }

            $typeCode = strtoupper(substr($riskType->code, 0, 2));

            $lastRisk = Risk::where('risk_type_id', $riskTypeId)
                ->orderBy('id', 'desc')
                ->first();

            $nextSequence = 1;
            if ($lastRisk && preg_match('/-(\d+)$/', $lastRisk->code, $m)) {
                $nextSequence = intval($m[1]) + 1;
            }

            return $typeCode . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

        } catch (\Exception $e) {
            Log::warning('Generate code error: ' . $e->getMessage());
            return 'RX-' . rand(100, 999);
        }
    }

    private function computeStatistics($risks): array
    {
        if ($risks->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $risksArray = $risks->toArray();
        
        // ✅ CRITICITÉ = frequency_level_id * impact_level_id
        $critical = collect($risksArray)->filter(function ($r) {
            $criticality = ($r['frequency_level_id'] && $r['impact_level_id']) 
                ? $r['frequency_level_id'] * $r['impact_level_id'] 
                : 0;
            return $criticality >= 12;
        })->count();

        $high = collect($risksArray)->filter(function ($r) {
            $criticality = ($r['frequency_level_id'] && $r['impact_level_id']) 
                ? $r['frequency_level_id'] * $r['impact_level_id'] 
                : 0;
            return $criticality >= 8 && $criticality < 12;
        })->count();

        $medium = collect($risksArray)->filter(function ($r) {
            $criticality = ($r['frequency_level_id'] && $r['impact_level_id']) 
                ? $r['frequency_level_id'] * $r['impact_level_id'] 
                : 0;
            return $criticality >= 5 && $criticality < 8;
        })->count();

        $low = collect($risksArray)->filter(function ($r) {
            $criticality = ($r['frequency_level_id'] && $r['impact_level_id']) 
                ? $r['frequency_level_id'] * $r['impact_level_id'] 
                : 0;
            return $criticality < 5;
        })->count();

        // ✅ MOYENNE CRITICITÉ
        $avgCriticality = collect($risksArray)
            ->map(function ($r) {
                return ($r['frequency_level_id'] && $r['impact_level_id']) 
                    ? $r['frequency_level_id'] * $r['impact_level_id'] 
                    : 0;
            })
            ->avg() ?? 0;

        return [
            'total_risks' => collect($risksArray)->count(),
            'critical' => $critical,
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
            'average_criticality' => round($avgCriticality, 2),
        ];
    }

    private function getEmptyStatistics(): array
    {
        return [
            'total_risks' => 0,
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'average_criticality' => 0,
        ];
    }
}