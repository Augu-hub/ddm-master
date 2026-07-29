<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\Param\MissionRequest;
use App\Models\Audit\Param\MissionRequestShare;
use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MissionRequestController extends Controller
{
    /**
     * GET /m/audit.core/api/audit/mission-requests
     * Index - Liste toutes les demandes
     */
    public function index(Request $request)
    {
        try {
            $missionRequests = MissionRequest::with('entity', 'requester', 'filledBy')
                ->latest()
                ->paginate(15);

            return Inertia::render('dashboards/Audit/MissionRequestIndex', [
                'missionRequests' => $missionRequests,
            ]);

        } catch (\Exception $e) {
            Log::error('MissionRequestController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * POST /m/audit.core/api/audit/mission-requests/generate-link
     * ✅ GÉNÈRE UN LIEN DE FORMULAIRE À PARTAGER
     * 
     * Crée un enregistrement dans mission_request_shares
     * pour tracer qui a généré le lien
     */
    public function generateFormLink(Request $request)
    {
        try {
            // Générer un lien unique et sécurisé (32 caractères)
            $shareLink = Str::random(32);
            
            // ✅ CRÉER LE TRAÇAGE: QUI A GÉNÉRÉ LE LIEN
            $share = MissionRequestShare::create([
                'share_link' => $shareLink,
                'shared_by_id' => Auth::id(), // ✅ ID DE L'UTILISATEUR QUI PARTAGE
                'status' => 'active',
            ]);

            Log::info("✅ Lien de formulaire généré par " . Auth::user()->name);

            // ✅ RETOURNER LE LIEN COMPLET DU FORMULAIRE
            // Route: /m/audit.core/api/audit/mission-requests/create?share=ABC123...
            $formLink = url('/m/audit.core/api/audit/mission-requests/create') . '?share=' . $shareLink;

            return response()->json([
                'success' => true,
                'form_link' => $formLink, // URL complète
                'share_id' => $share->id,
                'message' => 'Lien généré avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ generateFormLink: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /m/audit.core/api/audit/mission-requests/create
     * Affiche le formulaire de création
     * 
     * Paramètre optionnel: ?share=ABC123...
     * Si fourni, récupère qui a partagé le lien
     */
    public function create(Request $request)
    {
        try {
            $shareLink = $request->input('share');
            $share = null;
            $sharedByUser = null;
            
            // ✅ SI UN LIEN EST FOURNI, VÉRIFIER QU'IL EXISTE ET EST ACTIF
            if ($shareLink) {
                $share = MissionRequestShare::where('share_link', $shareLink)
                    ->where('status', 'active')
                    ->with('sharedBy')
                    ->firstOrFail();
                
                $sharedByUser = $share->sharedBy;
                
                Log::info("✅ Formulaire accédé via lien partagé par: " . $sharedByUser->name);
            }

            // Charger les données nécessaires pour le formulaire
            $entities = Entite::orderBy('name')->get();
            $processes = Processus::orderBy('name')->get();

            // ✅ GÉNÉRER LE NUMÉRO DE MISSION AUTO-INCRÉMENTÉ
            $lastMission = MissionRequest::latest('id')->first();
            $nextNumber = $lastMission ? ($lastMission->id + 1) : 1;
            $year = date('Y');
            $nextMissionNumber = 'FPMD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT) . '-' . $year;

            return Inertia::render('dashboards/Audit/MissionRequestCreate', [
                'entities' => $entities,
                'processes' => $processes,
                'nextMissionNumber' => $nextMissionNumber,
                'shareLink' => $shareLink, // Passer le lien pour l'inclure dans la requête
                'sharedByUser' => $sharedByUser, // Passer qui a partagé (pour affichage optionnel)
                'auth' => [
                    'user' => $request->user(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('MissionRequestController@create: ' . $e->getMessage());
            return back()->with('error', 'Lien invalide ou expiré: ' . $e->getMessage());
        }
    }

    /**
     * POST /m/audit.core/api/audit/mission-requests
     * Crée une nouvelle demande de mission
     * 
     * ✅ STOCKE QUI A PARTAGÉ LE LIEN DANS mission_source_id
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'mission_objective' => 'required|string|max:500',
                'mission_number' => 'required|string|unique:audit_mission_requests,code',
                'entity_id' => 'required|exists:entities,id',
                'audit_scope' => 'required|string',
                'related_process_id' => 'nullable|exists:processes,id',
                'risk' => 'nullable|string|in:Faible,Moyen,Élevé,Critique',
                'concern' => 'nullable|string',
                'result' => 'nullable|string',
                'procedure' => 'nullable|string',
                'autre' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'frequency' => 'required|string|in:Ponctuelle,Annuelle,Biannuelle,Triennale',
                'requester_email' => 'required|email',
                'requester_motif' => 'required|string',
                'share_link' => 'nullable|string', // ✅ LIEN PARTAGÉ
            ]);

            // ✅ RÉCUPÉRER QUI A PARTAGÉ LE LIEN
            $shareLink = $validated['share_link'] ?? null;
            $sharedById = null; // ID (MASTER) de qui a partagé le lien

            if ($shareLink) {
                $share = MissionRequestShare::where('share_link', $shareLink)
                    ->where('status', 'active')
                    ->firstOrFail();

                $sharedById = $share->shared_by_id; // ✅ QUI A PARTAGÉ

                Log::info("✅ Demande créée via lien partagé par ID: $sharedById");
            }

            // Générer share_code unique pour le remplissage
            $shareCode = strtoupper(Str::random(12));

            // Source d'audit (FK audit_mission_sources) — NE PAS y stocker un id
            // user. Le partageur est tracé via mission_request_shares.shared_by_id.
            $missionSourceId = \DB::table('audit_mission_sources')->orderBy('id')->value('id');

            // ✅ CRÉER LA DEMANDE
            $missionRequest = MissionRequest::create([
                'entity_id' => $validated['entity_id'],
                'code' => $validated['mission_number'],
                'share_code' => $shareCode,
                'mission_source_id' => $missionSourceId, // source d'audit (défaut : 1re dispo)
                'mission_type' => 'standard',
                'mission_objective' => $validated['mission_objective'],
                'audit_scope' => $validated['audit_scope'],
                'related_process_id' => $validated['related_process_id'] ?? null,
                'frequency' => $validated['frequency'],
                'requester_id' => Auth::id(), // ✅ QUI A CRÉÉ LA DEMANDE
                'requester_email' => $validated['requester_email'],
                'requester_motif' => $validated['requester_motif'],
                'requested_date' => now()->format('Y-m-d'),
                'status' => 'draft',
                'risk' => $validated['risk'] ?? null,
                'concern' => $validated['concern'] ?? null,
                'result' => $validated['result'] ?? null,
                'procedure' => $validated['procedure'] ?? null,
                'autre' => $validated['autre'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'filled_by_id' => null,
                'filled_at' => null,
            ]);

            // ✅ MARQUER LE PARTAGE COMME UTILISÉ
            if ($shareLink) {
                $share->markAsUsed($missionRequest->id);
            }

            Log::info("✅ Demande créée: {$missionRequest->code} | Créateur: " . Auth::user()->name . " | Via lien de: " . ($sharedById ? "ID:$sharedById" : "Direct"));

            return response()->json([
                'success' => true,
                'data' => $missionRequest,
                'share_link' => url('/m/audit.core/api/audit/mission-requests/' . $shareCode . '/fill'),
                'message' => 'Demande créée avec succès'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ MissionRequestController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /m/audit.core/api/audit/mission-requests/{shareCode}/fill
     * Page de remplissage (PUBLIQUE - pas d'authentification requise)
     */
    public function fill($shareCode)
    {
        try {
            $missionRequest = MissionRequest::where('share_code', $shareCode)
                ->with('requester', 'entity')
                ->firstOrFail();

            return Inertia::render('dashboards/Audit/MissionRequestFormFill', [
                'missionRequest' => $missionRequest,
                'shareCode' => $shareCode,
            ]);

        } catch (\Exception $e) {
            Log::error('MissionRequestController@fill: ' . $e->getMessage());
            return back()->with('error', 'Lien introuvable ou expiré');
        }
    }

    /**
     * GET /m/audit.core/api/audit/mission-requests/{id}
     * Récupère les détails d'une demande (API)
     */
    public function show($id)
    {
        try {
            $missionRequest = MissionRequest::where('code', $id)
                ->orWhere('id', $id)
                ->with('requester', 'entity', 'filledBy')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $missionRequest,
            ]);

        } catch (\Exception $e) {
            Log::error('MissionRequestController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}