<?php

namespace App\Services;

use App\Models\Tenant\User;
use App\Models\Param\Fonction;
use App\Models\Param\Entite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserSyncService
{
    /**
     * ✅ CORRIGÉ: Valider les données utilisateur avant création en tenant
     * 
     * Base de données:
     * - MASTER (ddmparam) = users, tenants
     * - TENANT (FRUITIVA) = users, entities, functions, function_assignments
     * 
     * Validation:
     * - Email: MASTER (vérifier pas doublé globally)
     * - Matricule: MASTER (unique globally)
     * - Entity: TENANT (existe dans le tenant)
     * - Created_by: MASTER (user qui crée doit exister en master)
     */
    public function validateUserDataInMaster(array $data): array
    {
        $errors = [];

        Log::info('🔍 Validation données utilisateur...', $data);

        try {
            // ✅ 1. Vérifier l'email (unique en MASTER - ddmparam)
            if (isset($data['email'])) {
                $masterUserExists = \DB::connection('mysql')->table('users')
                    ->where('email', $data['email'])
                    ->exists();

                if ($masterUserExists) {
                    $errors['email'] = 'Cet email existe déjà';
                    Log::warning('❌ Email existe en MASTER:', ['email' => $data['email']]);
                }
            }

            // ✅ 2. Vérifier le matricule (unique en MASTER - ddmparam)
            if (isset($data['matricule']) && !empty($data['matricule'])) {
                $masterMatriculeExists = \DB::connection('mysql')->table('users')
                    ->where('matricule', $data['matricule'])
                    ->exists();

                if ($masterMatriculeExists) {
                    $errors['matricule'] = 'Ce matricule existe déjà';
                    Log::warning('❌ Matricule existe en MASTER:', ['matricule' => $data['matricule']]);
                }
            }

            // ✅ 3. Vérifier l'entité (doit exister en TENANT!)
            // ⚠️ FIX: Chercher en TENANT (Entite model), pas en Master!
            if (isset($data['entity_id']) && !empty($data['entity_id'])) {
                $entityExists = Entite::where('id', $data['entity_id'])->exists();

                if (!$entityExists) {
                    $errors['entity_id'] = 'L\'entité spécifiée n\'existe pas';
                    Log::warning('❌ Entité n\'existe pas en TENANT:', ['entity_id' => $data['entity_id']]);
                }
            }

            // ✅ 4. Vérifier le créateur (doit exister en MASTER - ddmparam)
            if (isset($data['created_by']) && !empty($data['created_by'])) {
                $creatorExists = \DB::connection('mysql')->table('users')
                    ->where('id', $data['created_by'])
                    ->exists();

                if (!$creatorExists) {
                    $errors['created_by'] = 'Le créateur spécifié n\'existe pas';
                    Log::warning('❌ Créateur n\'existe pas en MASTER:', ['created_by' => $data['created_by']]);
                }
            }

            if (empty($errors)) {
                Log::info('✅ Validation réussie');
            }

            return $errors;

        } catch (\Exception $e) {
            Log::error('❌ Erreur validation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getLine()
            ]);
            return ['error' => 'Erreur validation: ' . $e->getMessage()];
        }
    }

    /**
     * ✅ Récupérer les fonctions disponibles pour une entité (en TENANT)
     */
    public function getFunctionsForEntity($entityId)
    {
        Log::info('🔄 Récupération des fonctions pour l\'entité...', ['entity_id' => $entityId]);

        try {
            // ✅ Chercher les fonctions assignées à cette entité en TENANT
            $functions = Fonction::join('function_assignments as fa', 'functions.id', '=', 'fa.function_id')
                ->where('fa.entity_id', $entityId)
                ->select('functions.id', 'functions.name', 'functions.character')
                ->distinct()
                ->orderBy('functions.name')
                ->get();

            Log::info('✅ Fonctions récupérées:', [
                'count' => $functions->count(),
                'entity_id' => $entityId
            ]);

            return $functions;
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération fonctions:', [
                'error' => $e->getMessage(),
                'entity_id' => $entityId
            ]);
            return collect();
        }
    }

    /**
     * ✅ Synchroniser les fonctions: créer les relations user_functions
     */
    public function syncFunctionsFromMaster(User $user, $assignedFunctions = []): bool
    {
        Log::info('🔄 Synchronisation des fonctions...', [
            'user_id' => $user->id,
            'function_count' => count($assignedFunctions)
        ]);

        try {
            DB::transaction(function () use ($user, $assignedFunctions) {
                if (empty($assignedFunctions)) {
                    Log::info('ℹ️ Aucune fonction à assigner');
                    return;
                }

                // Pour chaque fonction à assigner
                foreach ($assignedFunctions as $assignment) {
                    $functionId = $assignment['function_id'] ?? null;
                    $entityId = $assignment['entity_id'] ?? $user->entity_id;
                    $isPrimary = $assignment['is_primary'] ?? false;
                    $roleLabel = $assignment['role_label'] ?? null;

                    if (!$functionId) {
                        Log::warning('⚠️ Function ID manquant');
                        continue;
                    }

                    // ✅ Vérifier que la fonction existe en TENANT
                    $functionExists = Fonction::where('id', $functionId)->exists();

                    if (!$functionExists) {
                        Log::warning('❌ Fonction n\'existe pas en TENANT:', [
                            'function_id' => $functionId
                        ]);
                        continue;
                    }

                    // ✅ Assigner la fonction via la méthode User
                    $user->assignFunction(
                        $functionId,
                        $entityId,
                        $isPrimary,
                        $roleLabel
                    );

                    Log::info('✅ Fonction assignée:', [
                        'user_id' => $user->id,
                        'function_id' => $functionId,
                        'entity_id' => $entityId,
                        'is_primary' => $isPrimary
                    ]);
                }
            });

            Log::info('✅ Synchronisation réussie');
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Erreur synchronisation:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            return false;
        }
    }

    /**
     * ✅ Récupérer les informations complètes d'un utilisateur
     */
    public function getUserWithFunctions(User $user)
    {
        Log::info('📋 Récupération infos utilisateur...', ['user_id' => $user->id]);

        return [
            'user' => $user->load(['entity', 'creator']),
            'functions_by_entity' => $this->getFunctionsGroupedByEntity($user),
            'primary_function' => null,
        ];
    }

    /**
     * ✅ Regrouper les fonctions par entité
     */
    private function getFunctionsGroupedByEntity(User $user): array
    {
        $grouped = [];

        try {
            // Récupérer depuis function_assignments
            $assignments = DB::table('function_assignments')
                ->where('user_id', $user->id)
                ->join('functions', 'function_assignments.function_id', '=', 'functions.id')
                ->select(
                    'functions.id',
                    'functions.name',
                    'functions.character',
                    'function_assignments.entity_id',
                    'function_assignments.is_primary',
                    'function_assignments.role_label',
                    'function_assignments.created_at'
                )
                ->get();

            foreach ($assignments as $assignment) {
                $entityId = $assignment->entity_id;
                if (!isset($grouped[$entityId])) {
                    $grouped[$entityId] = [];
                }

                $grouped[$entityId][] = [
                    'id' => $assignment->id,
                    'name' => $assignment->name,
                    'character' => $assignment->character,
                    'is_primary' => $assignment->is_primary,
                    'role_label' => $assignment->role_label,
                    'assigned_at' => $assignment->created_at,
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Erreur regroupement fonctions:', [
                'error' => $e->getMessage()
            ]);
        }

        return $grouped;
    }
}