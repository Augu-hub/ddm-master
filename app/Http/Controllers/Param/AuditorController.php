<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use App\Models\Param\Competency;
use App\Models\Param\CompetencyCategory;
use App\Models\Param\Entite;
use App\Models\Tenant\User;
use App\Services\UserSyncService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuditorController extends Controller
{
    protected EmailService $emailService;
    protected UserSyncService $syncService;

    public function __construct(
        EmailService $emailService,
        UserSyncService $syncService
    ) {
        $this->emailService = $emailService;
        $this->syncService = $syncService;
    }

    /**
     * 📋 Liste des auditeurs
     */
    public function index(Request $request)
    {
        Log::info('📋 Chargement liste auditeurs...');

        $query = Auditor::with(['user', 'entity', 'creator', 'competencies.category']);

        // 🔍 Recherche
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // 🏢 Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🏢 Filtrer par entité
        if ($request->filled('entity_id')) {
            $query->byEntity($request->entity_id);
        }

        // 🔀 Tri
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // 📄 Pagination
        $auditors = $query->paginate($request->get('per_page', 15));

        Log::info('✅ Liste auditeurs chargée:', ['count' => $auditors->total()]);

        return Inertia::render('dashboards/Param/Auditors/Index', [
            'auditors' => $auditors,
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'entity_id']),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * ➕ Formulaire de création
     */
    public function create()
    {
        Log::info('🆕 Création auditeur - formulaire');

        return Inertia::render('dashboards/Param/Auditors/Create', [
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'competenciesByCategory' => CompetencyCategory::active()
                ->with(['competencies' => fn($q) => $q->active()])
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * ✅ STORE - Créer auditeur avec User automatique
     * ✅ VALIDATION CORRIGÉE
     */
    public function store(Request $request)
    {
        Log::info('========================================');
        Log::info('🆕 CRÉATION AUDITEUR - DÉBUT');
        Log::info('========================================');

        try {
            // ✅ 1. Valider - VERSION CORRIGÉE
            Log::info('1️⃣ Validation des données...');

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'birthplace' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'audit_experience' => 'nullable|integer|min:0',
                'other_experience' => 'nullable|integer|min:0',
                'gender' => 'nullable|in:M,F',
                'status' => 'required|in:active,inactive,suspended',
                'bio' => 'nullable|string|max:1000',
                'entity_id' => 'nullable|exists:entities,id',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                
                // ✅ COMPÉTENCES - FLEXIBLES
                'competencies' => 'nullable|json', // JSON array ou array direct
                
                // ✅ SEND_EMAIL - ACCEPTE PLUSIEURS FORMATS
                'send_email' => 'nullable|in:0,1,on,off,true,false',
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validation échouée:', $validator->errors()->toArray());
                return back()->withErrors($validator)->withInput();
            }

            Log::info('✅ Données valides');

            // ✅ 2. Vérifier email
            Log::info('2️⃣ Vérification email...');

            if (DB::connection('mysql')->table('users')->where('email', $request->email)->exists()) {
                Log::warning('⚠️ Email existe en MASTER');
                return back()->withErrors(['email' => 'Cet email existe déjà'])->withInput();
            }

            if (Auditor::where('email', $request->email)->exists()) {
                Log::warning('⚠️ Email existe en AUDITORS');
                return back()->withErrors(['email' => 'Cet email est déjà utilisé'])->withInput();
            }

            if (User::where('email', $request->email)->exists()) {
                Log::warning('⚠️ Email existe en USERS');
                return back()->withErrors(['email' => 'Cet email existe dans le système'])->withInput();
            }

            Log::info('✅ Email unique');

            // ✅ 3. Générer codes
            Log::info('3️⃣ Génération des codes...');
            $auditCode = Auditor::generateAuditCode();
            $generatedPassword = Str::random(12);
            $hashedPassword = bcrypt($generatedPassword);
            Log::info('✅ Code généré:', ['audit_code' => $auditCode]);

            // ✅ 4. Tenant ID
            Log::info('4️⃣ Récupération tenant_id...');
            $tenantId = $this->getTenantIdWithFallback();

            if (!$tenantId) {
                Log::error('❌ Tenant ID non trouvé');
                return back()->withErrors(['error' => 'Tenant non trouvé'])->withInput();
            }

            Log::info('✅ Tenant ID:', ['tenant_id' => $tenantId]);

            // ✅ 5. MASTER transaction
            Log::info('5️⃣ Création en MASTER...');

            $masterUserId = null;

            DB::connection('mysql')->transaction(function () use (
                $request, $hashedPassword, $tenantId, &$masterUserId
            ) {
                $masterUserId = DB::connection('mysql')->table('users')->insertGetId([
                    'name' => trim("{$request->last_name} {$request->first_name}"),
                    'email' => $request->email,
                    'password' => $hashedPassword,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('✅ User créé en MASTER:', ['master_user_id' => $masterUserId]);

                DB::connection('mysql')->table('tenant_user')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $masterUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('✅ Relation tenant_user créée');
            });

            // ✅ 6. Créer User en TENANT
            Log::info('6️⃣ Création User en TENANT...');

            $user = User::create([
                'name' => trim("{$request->last_name} {$request->first_name}"),
                'email' => $request->email,
                'password' => $hashedPassword,
                'status' => $request->status ?? 'active',
                'created_by' => Auth::id(),
            ]);

            Log::info('✅ User créé en TENANT:', ['user_id' => $user->id]);

            // ✅ 7. Créer Auditor en TENANT
            Log::info('7️⃣ Création Auditor en TENANT...');

            $auditData = $request->except(['avatar', 'competencies', 'send_email']);
            $auditData['user_id'] = $user->id;
            $auditData['audit_id'] = $auditCode;
            $auditData['audit_code'] = $auditCode;
            $auditData['created_by'] = Auth::id();

            if ($request->hasFile('avatar')) {
                Log::info('📸 Upload avatar...');
                try {
                    $avatarPath = $request->file('avatar')->store('auditors', 'public');
                    $auditData['avatar'] = $avatarPath;
                    Log::info('✅ Avatar uploadé:', ['path' => $avatarPath]);
                } catch (\Exception $e) {
                    Log::error('❌ Erreur avatar:', ['error' => $e->getMessage()]);
                }
            }

            $auditor = Auditor::create($auditData);

            Log::info('✅ Auditor créé en TENANT:', ['auditor_id' => $auditor->id]);

            // ✅ 8. Assigner compétences
            Log::info('8️⃣ Assignment des compétences...');

            // Gérer les compétences (peuvent être JSON ou array)
            $competencies = [];
            if ($request->has('competencies')) {
                $compData = $request->input('competencies');
                
                // Si c'est une string JSON, la décoder
                if (is_string($compData)) {
                    $competencies = json_decode($compData, true) ?? [];
                } else {
                    $competencies = $compData ?? [];
                }
            }

            if (!empty($competencies)) {
                foreach ($competencies as $comp) {
                    // Gérer les cas où le comp peut être un array ou un objet
                    $competencyId = $comp['competency_id'] ?? $comp->competency_id ?? null;
                    $level = $comp['level'] ?? $comp->level ?? 1;
                    $isPrimary = $comp['is_primary'] ?? $comp->is_primary ?? false;
                    
                    if ($competencyId) {
                        $auditor->assignCompetency(
                            (int)$competencyId,
                            (int)$level,
                            (bool)$isPrimary
                        );
                    }
                }
                Log::info('✅ Compétences assignées:', ['count' => count($competencies)]);
            }

            // ✅ 9. Email
            Log::info('9️⃣ Envoi email...');

            // ✅ CORRIGER: Accepter plusieurs formats pour send_email
            $sendEmailValue = $request->input('send_email');
            $sendEmail = in_array($sendEmailValue, ['1', 'on', 'true'], true) || $sendEmailValue === true;
            
            $emailSent = false;

            if ($sendEmail) {
                try {
                    $creatorName = Auth::user()->name ?? 'L\'administrateur';
                    $emailSent = $this->emailService->sendAccountCreatedEmail(
                        $user->email,
                        $user->name,
                        $generatedPassword,
                        $creatorName
                    );

                    if ($emailSent) {
                        Log::info("✅ Email envoyé");
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Erreur email:', ['error' => $e->getMessage()]);
                }
            }

            Log::info('========================================');
            Log::info('✅ CRÉATION RÉUSSIE');
            Log::info('========================================');

            $successMsg = "✅ Auditeur {$auditor->full_name} ({$auditCode}) créé";
            if ($sendEmail && $emailSent) {
                $successMsg .= ' + email envoyé';
            }

            return redirect()->route('param.projects.auditors.index')
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            Log::error('❌ ERREUR CRÉATION AUDITEUR');
            Log::error('Message:', ['error' => $e->getMessage()]);
            Log::error('Trace:', ['trace' => $e->getTraceAsString()]);

            return back()->withErrors(['error' => 'Erreur: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * 👁️ Afficher auditeur
     */
    public function show(Auditor $auditor)
    {
        Log::info('👁️ Affichage auditeur:', ['id' => $auditor->id]);

        $auditor->load(['user', 'entity', 'creator', 'competencies.category']);

        return Inertia::render('dashboards/Param/Auditors/Show', [
            'auditor' => $auditor,
            'competenciesByCategory' => $auditor->competenciesByCategory(),
        ]);
    }

    /**
     * ✏️ Formulaire édition
     */
    public function edit(Auditor $auditor)
    {
        Log::info('✏️ Édition auditeur - formulaire:', ['id' => $auditor->id]);

        $auditor->load(['user', 'entity', 'competencies']);

        return Inertia::render('dashboards/Param/Auditors/Edit', [
            'auditor' => $auditor,
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'competenciesByCategory' => CompetencyCategory::active()
                ->with(['competencies' => fn($q) => $q->active()])
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * 💾 Mettre à jour
     */
    public function update(Request $request, Auditor $auditor)
    {
        Log::info('💾 Mise à jour auditeur:', ['id' => $auditor->id]);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:auditors,email,' . $auditor->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'birthplace' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'audit_experience' => 'nullable|integer|min:0',
            'other_experience' => 'nullable|integer|min:0',
            'gender' => 'nullable|in:M,F',
            'status' => 'required|in:active,inactive,suspended',
            'bio' => 'nullable|string|max:1000',
            'entity_id' => 'nullable|exists:entities,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->except(['avatar']);

        if ($request->hasFile('avatar')) {
            if ($auditor->avatar) {
                Storage::disk('public')->delete($auditor->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('auditors', 'public');
        }

        $data['updated_by'] = Auth::id();

        $auditor->update($data);

        // Mettre à jour User associé
        if ($auditor->user) {
            $auditor->user->update([
                'name' => trim("{$request->last_name} {$request->first_name}"),
                'email' => $request->email,
                'status' => $request->status,
            ]);
        }

        Log::info('✅ Auditeur mis à jour');

        return redirect()->route('param.projects.auditors.show', $auditor->id)
            ->with('success', 'Auditeur mis à jour');
    }

    /**
     * 🗑️ Supprimer
     */
    public function destroy(Auditor $auditor)
    {
        Log::info('🗑️ Suppression auditeur:', ['id' => $auditor->id]);

        if ($auditor->avatar) {
            Storage::disk('public')->delete($auditor->avatar);
        }

        $auditor->delete();

        Log::info('✅ Auditeur supprimé');

        return redirect()->route('param.projects.auditors.index')
            ->with('success', 'Auditeur supprimé');
    }

    /**
     * 🔄 Changer statut
     */
    public function changeStatus(Request $request, Auditor $auditor)
    {
        Log::info('🔄 Changement statut:', ['id' => $auditor->id, 'status' => $request->status]);

        $request->validate(['status' => 'required|in:active,inactive,suspended']);

        $auditor->update(['status' => $request->status]);

        if ($auditor->user) {
            $auditor->user->update(['status' => $request->status]);
        }

        Log::info('✅ Statut mis à jour');

        return back()->with('success', 'Statut mis à jour');
    }

    /**
     * 📊 Assigner compétence
     */
    public function assignCompetency(Request $request, Auditor $auditor)
    {
        Log::info('📊 Assignment compétence...', ['auditor_id' => $auditor->id]);

        $request->validate([
            'competency_id' => 'required|integer|exists:competencies,id',
            'level' => 'required|integer|between:1,5',
            'is_primary' => 'nullable|boolean',
        ]);

        try {
            $competency = Competency::findOrFail($request->competency_id);

            $auditor->assignCompetency(
                $request->competency_id,
                $request->level,
                $request->boolean('is_primary')
            );

            Log::info('✅ Compétence assignée');

            return back()->with('success', "✅ Compétence '{$competency->name}' assignée");

        } catch (\Exception $e) {
            Log::error('❌ Erreur:', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 🔌 Révoquer compétence
     */
    public function revokeCompetency(Request $request, Auditor $auditor)
    {
        Log::info('🔌 Revocation compétence...', ['auditor_id' => $auditor->id]);

        $request->validate(['competency_id' => 'required|integer|exists:competencies,id']);

        try {
            $competency = Competency::findOrFail($request->competency_id);

            $auditor->revokeCompetency($request->competency_id);

            Log::info('✅ Compétence révoquée');

            return back()->with('success', "✅ Compétence '{$competency->name}' révoquée");

        } catch (\Exception $e) {
            Log::error('❌ Erreur:', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 📊 Statistiques
     */
    private function getStatistics(): array
    {
        return [
            'total' => Auditor::count(),
            'active' => Auditor::where('status', 'active')->count(),
            'inactive' => Auditor::where('status', 'inactive')->count(),
            'suspended' => Auditor::where('status', 'suspended')->count(),
            'with_competencies' => Auditor::has('competencies')->count(),
            'recent' => Auditor::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Tenant ID avec fallback
     */
    private function getTenantIdWithFallback()
    {
        if (config('app.tenant_id')) {
            return config('app.tenant_id');
        }

        if (request('tenant_id')) {
            return request('tenant_id');
        }

        if (function_exists('tenant') && tenant()) {
            return tenant()->id;
        }

        $dbName = env('DB_DATABASE');
        $tenant = DB::connection('mysql')->table('tenants')
            ->where('db_name', $dbName)
            ->orWhere('db_name', strtolower($dbName))
            ->orWhere('db_name', strtoupper($dbName))
            ->first();

        return $tenant?->id ?? 1;
    }
}