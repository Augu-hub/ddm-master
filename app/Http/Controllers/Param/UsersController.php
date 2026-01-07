<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Models\Param\Entite;
use App\Models\Param\Fonction;
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

class UsersController extends Controller
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
     * 📋 Liste des utilisateurs
     */
    public function index(Request $request)
    {
        Log::info('📋 Chargement liste utilisateurs...');

        $query = User::with(['entity', 'creator', 'functions']);

        // 🔍 Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        // 🏢 Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🏢 Filtrer par entité
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        // 🔀 Tri
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $users = $query->paginate($request->get('per_page', 15));

        Log::info('✅ Liste utilisateurs chargée:', ['count' => $users->total()]);

        return Inertia::render('dashboards/Param/Users/Index', [
            'users' => $users,
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'entity_id']),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * ➕ Afficher le formulaire de création
     */
    public function create()
    {
        Log::info('🆕 Création utilisateur - formulaire');

        return Inertia::render('dashboards/Param/Users/Create', [
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'fonctions' => Fonction::select('id', 'name', 'character')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * 📥 API Endpoint - Récupérer les fonctions pour une entité
     */
    public function getFunctionsForEntity(Request $request)
    {
        Log::info('📥 API - Récupération fonctions pour entité...', [
            'entity_id' => $request->entity_id
        ]);

        try {
            $entityId = $request->query('entity_id');

            if (!$entityId) {
                Log::warning('⚠️ Entity ID manquant');
                return response()->json([
                    'success' => false,
                    'functions' => [],
                    'error' => 'Entity ID requis'
                ], 400);
            }

            // ✅ OPTIMISÉ: Jointure avec function_assignments
            $functions = Fonction::join('function_assignments as fa', 'functions.id', '=', 'fa.function_id')
                ->where('fa.entity_id', $entityId)
                ->select('functions.id', 'functions.name', 'functions.character')
                ->distinct()
                ->orderBy('functions.name')
                ->get();

            Log::info('✅ Fonctions récupérées:', [
                'entity_id' => $entityId,
                'count' => $functions->count()
            ]);

            return response()->json([
                'success' => true,
                'functions' => $functions->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'character' => $f->character,
                ])->values()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération fonctions:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'functions' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ STORE - Créer utilisateur avec insertion SIMULTANÉE MASTER + TENANT + PIVOT
     */
    public function store(Request $request)
    {
        Log::info('========================================');
        Log::info('🆕 CRÉATION UTILISATEUR - DÉBUT');
        Log::info('========================================');

        try {
            // ✅ 1. Valider les données
            Log::info('1️⃣ Validation des données...');
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'nullable|string|max:20',
                'matricule' => 'nullable|string',
                'status' => 'required|in:active,inactive,suspended',
                'job_title' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:1000',
                'entity_id' => 'nullable|exists:entities,id',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'function_assignments' => 'nullable|array',
                'function_assignments.*.function_id' => 'required_with:function_assignments|integer|exists:functions,id',
                'function_assignments.*.entity_id' => 'nullable|integer|exists:entities,id',
                'function_assignments.*.is_primary' => 'nullable|in:0,1',
                'function_assignments.*.role_label' => 'nullable|string|max:100',
                'send_email' => 'nullable|in:0,1',
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validation échouée:', $validator->errors()->toArray());
                return back()->withErrors($validator)->withInput();
            }

            Log::info('✅ Données valides');

            // ✅ 2. Vérifier email unique (MASTER + TENANT)
            Log::info('2️⃣ Vérification email unique...');

            $emailExistsInMaster = DB::connection('mysql')->table('users')
                ->where('email', $request->email)
                ->exists();

            if ($emailExistsInMaster) {
                Log::warning('⚠️ Email existe en MASTER:', ['email' => $request->email]);
                return back()->withErrors(['email' => 'Cet email existe déjà'])->withInput();
            }

            $emailExistsInTenant = User::where('email', $request->email)->exists();

            if ($emailExistsInTenant) {
                Log::warning('⚠️ Email existe en TENANT:', ['email' => $request->email]);
                return back()->withErrors(['email' => 'Cet email existe déjà'])->withInput();
            }

            Log::info('✅ Email unique (MASTER + TENANT)');

            // ✅ 3. Générer mot de passe
            Log::info('3️⃣ Génération du mot de passe...');
            $generatedPassword = Str::random(12);
            $hashedPassword = bcrypt($generatedPassword);
            Log::info('✅ Mot de passe généré');

            // ✅ 4. Obtenir le tenant_id (avec fallback)
            Log::info('4️⃣ Récupération du tenant_id...');
            $tenantId = $this->getTenantIdWithFallback();

            if (!$tenantId) {
                Log::error('❌ Tenant ID non trouvé');
                return back()->withErrors(['error' => 'Tenant non trouvé'])->withInput();
            }

            Log::info('✅ Tenant ID récupéré:', ['tenant_id' => $tenantId]);

            // ✅ 5. Valider dans la base Master
            Log::info('5️⃣ Validation dans la base Master...');

            $masterErrors = $this->syncService->validateUserDataInMaster([
                'email' => $request->email,
                'matricule' => $request->matricule,
                'entity_id' => $request->entity_id,
                'created_by' => Auth::id(),
            ]);

            if (!empty($masterErrors)) {
                Log::error('❌ Erreur Master:', $masterErrors);
                return back()->withErrors($masterErrors)->withInput();
            }

            Log::info('✅ Validation Master réussie');

            // ✅ 6. BULK INSERT - MASTER (users + tenant_user)
            Log::info('6️⃣ Insertion en MASTER (users + pivot)...');

            $masterUserId = null;

            DB::connection('mysql')->transaction(function () use (
                $request, $hashedPassword, $tenantId, &$masterUserId
            ) {
                // ✅ Insérer en MASTER.users (SEULEMENT ces 3 colonnes)
                $masterUserId = DB::connection('mysql')->table('users')->insertGetId([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $hashedPassword,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('✅ User créé en MASTER:', [
                    'master_user_id' => $masterUserId,
                    'email' => $request->email
                ]);

                // ✅ Créer la relation pivot en MASTER.tenant_user
                DB::connection('mysql')->table('tenant_user')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $masterUserId,
                    'role_hint' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('✅ Relation tenant_user créée:', [
                    'tenant_id' => $tenantId,
                    'user_id' => $masterUserId
                ]);
            });

            // ✅ 7. Créer l'utilisateur en TENANT
            Log::info('7️⃣ Création de l\'utilisateur en TENANT...');

            $data = $request->except(['avatar', 'send_email', 'function_assignments']);
            $data['password'] = $hashedPassword;
            $data['created_by'] = Auth::id();

            if ($request->hasFile('avatar')) {
                Log::info('📸 Upload avatar...');
                try {
                    $avatarPath = $request->file('avatar')->store('avatars', 'public');
                    $data['avatar'] = $avatarPath;
                    Log::info('✅ Avatar uploadé:', ['path' => $avatarPath]);
                } catch (\Exception $e) {
                    Log::error('❌ Erreur upload avatar:', ['error' => $e->getMessage()]);
                }
            }

            $user = User::create($data);

            Log::info('✅ Utilisateur créé en TENANT:', [
                'tenant_user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ]);

            // ✅ 8. Synchroniser les fonctions
            Log::info('8️⃣ Synchronisation des fonctions...');

            $functionAssignments = $request->get('function_assignments', []);
            if (!empty($functionAssignments)) {
                Log::info('📋 Function assignments reçus:', [
                    'count' => count($functionAssignments)
                ]);
                
                foreach ($functionAssignments as &$fa) {
                    if (isset($fa['is_primary'])) {
                        $fa['is_primary'] = $fa['is_primary'] === '1' || $fa['is_primary'] === true;
                    }
                }
                
                $this->syncService->syncFunctionsFromMaster($user, $functionAssignments);
                Log::info('✅ Fonctions synchronisées:', [
                    'count' => count($functionAssignments)
                ]);
            } else {
                Log::info('ℹ️ Aucune fonction à assigner');
            }

            // ✅ 9. Envoyer email de bienvenue
            Log::info('9️⃣ Envoi email...');

            $sendEmail = $request->get('send_email') === '1' || $request->get('send_email') === true || $request->boolean('send_email');
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
                        Log::info("✅ Email envoyé à {$user->email}");
                    } else {
                        Log::warning("⚠️ Échec envoi email à {$user->email}");
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Erreur envoi email:', ['error' => $e->getMessage()]);
                }
            }

            $successMessage = '✅ Utilisateur créé avec succès en MASTER et TENANT';
            if ($sendEmail) {
                $successMessage .= $emailSent ? ' + email envoyé' : ' (email non envoyé)';
            }

            Log::info('========================================');
            Log::info('✅ CRÉATION RÉUSSIE');
            Log::info('========================================');
            Log::info('📊 Résumé:', [
                'master_user_id' => $masterUserId,
                'tenant_user_id' => $user->id,
                'email' => $user->email,
                'functions_count' => count($functionAssignments),
                'email_sent' => $emailSent
            ]);

            return redirect()->route('param.projects.users.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('========================================');
            Log::error('❌ ERREUR CRÉATION UTILISATEUR');
            Log::error('========================================');
            Log::error('Message:', ['error' => $e->getMessage()]);
            Log::error('Trace:', ['trace' => $e->getTraceAsString()]);

            return back()->withErrors(['error' => 'Erreur: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * ✅ Récupérer le tenant_id avec plusieurs fallback
     */
    private function getTenantIdWithFallback()
    {
        // 1️⃣ Essayer config
        if (config('app.tenant_id')) {
            Log::info('📌 Tenant ID depuis config:', ['tenant_id' => config('app.tenant_id')]);
            return config('app.tenant_id');
        }

        // 2️⃣ Essayer request
        if (request('tenant_id')) {
            Log::info('📌 Tenant ID depuis request:', ['tenant_id' => request('tenant_id')]);
            return request('tenant_id');
        }

        // 3️⃣ Essayer middleware tenant-aware
        if (function_exists('tenant') && tenant()) {
            Log::info('📌 Tenant ID depuis middleware:', ['tenant_id' => tenant()->id]);
            return tenant()->id;
        }

        // 4️⃣ Essayer depuis DB_NAME
        $dbName = env('DB_DATABASE');
        Log::info('🔍 Cherchant tenant avec db_name:', ['db_name' => $dbName]);
        
        $tenant = DB::connection('mysql')->table('tenants')
            ->where('db_name', $dbName)
            ->orWhere('db_name', strtolower($dbName))
            ->orWhere('db_name', strtoupper($dbName))
            ->first();

        if ($tenant) {
            Log::info('📌 Tenant ID depuis DB:', ['tenant_id' => $tenant->id, 'db_name' => $dbName]);
            return $tenant->id;
        }

        // 5️⃣ Fallback: Retourner 1 (si un seul tenant)
        Log::warning('⚠️ Utilisation du fallback tenant_id = 1');
        return 1;
    }

    /**
     * 👁️ Afficher les détails d'un utilisateur
     */
    public function show(User $user)
    {
        Log::info('👁️ Affichage utilisateur:', ['id' => $user->id]);

        $userData = $this->syncService->getUserWithFunctions($user);

        return Inertia::render('dashboards/Param/Users/Show', [
            'user' => $userData['user'],
            'functions_by_entity' => $userData['functions_by_entity'],
            'primary_function' => $userData['primary_function'],
        ]);
    }

    /**
     * ✏️ Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        Log::info('✏️ Édition utilisateur - formulaire:', ['id' => $user->id]);

        $user->load(['entity', 'functions']);

        return Inertia::render('dashboards/Param/Users/Edit', [
            'user' => $user,
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'fonctions' => Fonction::select('id', 'name', 'character')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * 💾 Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        Log::info('💾 Mise à jour utilisateur:', ['id' => $user->id]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'matricule' => 'nullable|string|unique:users,matricule,' . $user->id,
            'status' => 'required|in:active,inactive,suspended',
            'job_title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'entity_id' => 'nullable|exists:entities,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->except(['avatar', 'password_confirmation', 'password']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        Log::info('✅ Utilisateur mis à jour');

        return redirect()->route('param.projects.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès');
    }

    /**
     * 🗑️ Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        Log::info('🗑️ Suppression utilisateur:', ['id' => $user->id]);

        if ($user->id === Auth::id()) {
            Log::warning('⚠️ Tentative suppression du propre compte');
            return back()->withErrors(['error' => 'Vous ne pouvez pas supprimer votre propre compte']);
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        Log::info('✅ Utilisateur supprimé');

        return redirect()->route('param.projects.users.index')
            ->with('success', 'Utilisateur supprimé avec succès');
    }

    /**
     * 🔄 Changer le statut d'un utilisateur
     */
    public function changeStatus(Request $request, User $user)
    {
        Log::info('🔄 Changement statut utilisateur:', [
            'id' => $user->id,
            'new_status' => $request->status
        ]);

        $request->validate(['status' => 'required|in:active,inactive,suspended']);

        $user->status = $request->status;
        $user->save();

        Log::info('✅ Statut mis à jour');

        return back()->with('success', 'Statut mis à jour');
    }

    /**
     * 📋 Assigner une fonction à un utilisateur
     */
    public function assignFunction(Request $request, User $user)
    {
        Log::info('📋 Assignment fonction à utilisateur...', [
            'user_id' => $user->id,
            'data' => $request->all()
        ]);

        try {
            $request->validate([
                'function_id' => 'required|integer|exists:functions,id',
                'entity_id' => 'nullable|integer|exists:entities,id',
                'is_primary' => 'nullable|boolean',
                'role_label' => 'nullable|string|max:100',
            ]);

            $fonction = Fonction::findOrFail($request->function_id);

            $user->assignFunction(
                $request->function_id,
                $request->entity_id ?? $user->entity_id,
                $request->is_primary ?? false,
                $request->role_label
            );

            Log::info('✅ Fonction assignée', [
                'fonction' => $fonction->name,
                'function_id' => $request->function_id
            ]);

            return back()->with('success', "✅ Fonction '{$fonction->name}' assignée avec succès");

        } catch (\Exception $e) {
            Log::error('❌ Erreur assignment fonction:', [
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 🔌 Révoquer une fonction
     */
    public function revokeFunction(Request $request, User $user)
    {
        Log::info('🔌 Revocation fonction...', [
            'user_id' => $user->id,
            'function_id' => $request->function_id
        ]);

        try {
            $request->validate([
                'function_id' => 'required|integer|exists:functions,id',
                'entity_id' => 'nullable|integer|exists:entities,id',
            ]);

            $fonction = Fonction::findOrFail($request->function_id);

            $user->revokeFunction(
                $request->function_id,
                $request->entity_id
            );

            Log::info('✅ Fonction révoquée', [
                'fonction' => $fonction->name
            ]);

            return back()->with('success', "✅ Fonction '{$fonction->name}' révoquée");

        } catch (\Exception $e) {
            Log::error('❌ Erreur revocation:', [
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 📊 Statistiques des utilisateurs
     */
    private function getStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
        ];
    }
}