<?php

namespace App\Http\Controllers\Param;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Models\Param\Entite;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UsersController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with(['entity', 'creator']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('matricule', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par entité
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        // Tri
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $users = $query->paginate($request->get('per_page', 15));

        return Inertia::render('dashboards/Param/Users/Index', [
            'users' => $users,
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'entity_id']),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return Inertia::render('dashboards/Param/Users/Create', [
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        Log::info('========================================');
        Log::info('DÉBUT CRÉATION UTILISATEUR');
        Log::info('========================================');
        
        // ✅ CORRECTION : Convertir send_email en boolean
        if ($request->has('send_email')) {
            $request->merge([
                'send_email' => filter_var($request->send_email, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        
        // Log des données reçues
        Log::info('Données reçues:', [
            'all' => $request->all(),
            'files' => $request->allFiles(),
        ]);

        // Validation
        Log::info('Début validation...');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'matricule' => 'nullable|string|unique:users,matricule',
            'status' => 'required|in:active,inactive,suspended',
            'job_title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'entity_id' => 'nullable|exists:entities,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'send_email' => 'boolean',
        ], [
            'name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'matricule.unique' => 'Ce matricule est déjà utilisé',
        ]);

        if ($validator->fails()) {
            Log::error('VALIDATION ÉCHOUÉE:', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }
        
        Log::info('✅ Validation réussie');

        try {
            // Préparation des données
            Log::info('Préparation des données...');
            $data = $request->except(['avatar', 'send_email']);
            Log::info('Données après except:', $data);
            
            // Génération du mot de passe
            Log::info('Génération du mot de passe...');
            $generatedPassword = User::generateRandomPassword();
            $data['password'] = $generatedPassword;
            Log::info('✅ Mot de passe généré (longueur: ' . strlen($generatedPassword) . ')');
            
            // ID du créateur
            $authId = Auth::id();
            Log::info('Auth::id() = ' . $authId);
            $data['created_by'] = $authId;

            // Upload avatar
            if ($request->hasFile('avatar')) {
                Log::info('Upload avatar...');
                try {
                    $avatarPath = $request->file('avatar')->store('avatars', 'public');
                    $data['avatar'] = $avatarPath;
                    Log::info('✅ Avatar uploadé:', ['path' => $avatarPath]);
                } catch (\Exception $e) {
                    Log::error('❌ Erreur upload avatar:', ['error' => $e->getMessage()]);
                }
            } else {
                Log::info('Pas d\'avatar à uploader');
            }

            // Création de l'utilisateur
            Log::info('========================================');
            Log::info('CRÉATION DE L\'UTILISATEUR EN BASE');
            Log::info('========================================');
            Log::info('Données finales à insérer:', $data);
            
            try {
                $user = User::create($data);
                Log::info('✅ UTILISATEUR CRÉÉ EN BASE:', [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'matricule' => $user->matricule,
                ]);
            } catch (\Exception $e) {
                Log::error('❌ ERREUR CRÉATION USER EN BASE:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // Envoi email
            $sendEmail = $request->get('send_email', true);
            Log::info('Envoi email:', ['send_email' => $sendEmail]);
            $emailSent = false;
            
            if ($sendEmail) {
                Log::info('========================================');
                Log::info('ENVOI EMAIL');
                Log::info('========================================');
                
                try {
                    $creatorName = Auth::user()->name ?? 'L\'administrateur';
                    Log::info('Nom du créateur:', ['name' => $creatorName]);
                    Log::info('Email destinataire:', ['email' => $user->email]);
                    Log::info('Nom destinataire:', ['name' => $user->name]);
                    
                    $emailSent = $this->emailService->sendAccountCreatedEmail(
                        $user->email,
                        $user->name,
                        $generatedPassword,
                        $creatorName
                    );

                    if ($emailSent) {
                        Log::info("✅ Email de création de compte envoyé à {$user->email}");
                    } else {
                        Log::warning("⚠️ Échec de l'envoi de l'email à {$user->email}");
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Erreur lors de l'envoi de l'email à {$user->email}:", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                Log::info('Envoi email désactivé par l\'utilisateur');
            }

            // Message de succès
            $successMessage = 'Utilisateur créé avec succès';
            if ($sendEmail) {
                $successMessage .= $emailSent 
                    ? ' et email de bienvenue envoyé' 
                    : ' mais l\'email n\'a pas pu être envoyé';
            }
            Log::info('Message de succès:', ['message' => $successMessage]);

            // Flash password si email non envoyé
            if (!$emailSent) {
                Log::info('Mot de passe mis en session flash');
                session()->flash('generated_password', $generatedPassword);
                session()->flash('user_email', $user->email);
            }

            Log::info('========================================');
            Log::info('✅ CRÉATION TERMINÉE AVEC SUCCÈS');
            Log::info('========================================');

            return redirect()->route('param.projects.users.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('========================================');
            Log::error('❌ ERREUR GLOBALE CRÉATION UTILISATEUR');
            Log::error('========================================');
            Log::error('Message:', ['error' => $e->getMessage()]);
            Log::error('File:', ['file' => $e->getFile()]);
            Log::error('Line:', ['line' => $e->getLine()]);
            Log::error('Trace:', ['trace' => $e->getTraceAsString()]);
            
            return back()->withErrors(['error' => 'Une erreur est survenue lors de la création de l\'utilisateur: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Afficher les détails d'un utilisateur
     */
    public function show(User $user)
    {
        $user->load(['entity', 'creator']);

        return Inertia::render('dashboards/Param/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        $user->load(['entity']);

        return Inertia::render('dashboards/Param/Users/Edit', [
            'user' => $user,
            'entities' => Entite::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
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
        ], [
            'name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'matricule.unique' => 'Ce matricule est déjà utilisé',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->except(['avatar', 'password_confirmation', 'password']);

        // Gérer le mot de passe
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        // Gérer l'upload de l'avatar
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->route('param.projects.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès');
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas supprimer votre propre compte']);
        }

        // Supprimer l'avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('param.projects.users.index')
            ->with('success', 'Utilisateur supprimé avec succès');
    }

    /**
     * Changer le statut d'un utilisateur
     */
    public function changeStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $user->status = $request->status;
        $user->save();

        return back()->with('success', 'Statut mis à jour avec succès');
    }

    /**
     * Renvoyer l'email de bienvenue
     */
    public function resendWelcomeEmail(User $user)
    {
        try {
            // Générer un nouveau mot de passe
            $newPassword = User::generateRandomPassword();
            $user->password = $newPassword;
            $user->save();

            // Envoyer l'email
            $creatorName = Auth::user()->name ?? 'L\'administrateur';
            $emailSent = $this->emailService->sendAccountCreatedEmail(
                $user->email,
                $user->name,
                $newPassword,
                $creatorName
            );

            if ($emailSent) {
                return back()->with('success', 'Email de bienvenue renvoyé avec succès');
            } else {
                return back()->withErrors(['error' => 'Échec de l\'envoi de l\'email']);
            }
        } catch (\Exception $e) {
            Log::error('Erreur renvoi email: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    /**
     * Export des utilisateurs
     */
    public function export(Request $request)
    {
        $query = User::with(['entity']);

        // Appliquer les mêmes filtres que la liste
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        $users = $query->get();

        $filename = 'utilisateurs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'Matricule',
                'Nom',
                'Email',
                'Téléphone',
                'Entité',
                'Poste',
                'Statut',
                'Date de création',
            ], ';');

            // Données
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->matricule,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->entity?->name,
                    $user->job_title,
                    $user->status_badge['text'],
                    $user->created_at->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Statistiques des utilisateurs
     */
    private function getStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'with_entity' => User::whereNotNull('entity_id')->count(),
            'recent' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }
}