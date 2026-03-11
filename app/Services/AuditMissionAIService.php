<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * AuditMissionAIService — Orchestrateur IA pour Missions d'Audit
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce service orchestre le workflow intelligent :
 * 1. Risques → Analyse → Profil
 * 2. Profil → 3 buts proposés (classés par pertinence)
 * 3. But choisi → Type de mission auto-sélectionné
 * 4. But + Type → Tous les champs générés
 */
class AuditMissionAIService
{
    protected MistralMissionAssistant $mistral;

    public function __construct(MistralMissionAssistant $mistral)
    {
        $this->mistral = $mistral;
    }

    /**
     * ÉTAPE 1 : Analyser les risques et retourner un profil enrichi
     */
    public function analyserRisques(array $riskIds): array
    {
        try {
            if (empty($riskIds)) {
                return [
                    'success' => false,
                    'error' => 'Aucun risque sélectionné'
                ];
            }

            return $this->mistral->analyserRisques($riskIds);

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::analyserRisques: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ÉTAPE 2 : Proposer 3 buts de mission (déjà classés par priorité)
     * À partir du profil de risques uniquement
     */
    public function proposerButs(array $profil): array
    {
        try {
            if (empty($profil)) {
                return [
                    'success' => false,
                    'error' => 'Profil de risques requis'
                ];
            }

            return $this->mistral->proposerButs($profil);

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::proposerButs: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ÉTAPE 3 : Sélectionner automatiquement le type de mission adapté au but choisi
     * Le but a été choisi par l'utilisateur parmi les 3 proposés
     */
    public function selectionnerTypeMission(string $butChoisi, array $profil): array
    {
        try {
            if (empty($butChoisi)) {
                return [
                    'success' => false,
                    'error' => 'Le but de la mission est requis'
                ];
            }

            return $this->mistral->selectionnerTypeMission($butChoisi, $profil);

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::selectionnerTypeMission: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ÉTAPE 4 : Générer tous les champs de la mission
     * Basé sur le but choisi + le type sélectionné + le profil
     */
    public function genererTousLesChamps(string $but, array $typeInfo, array $profil): array
    {
        try {
            if (empty($but)) {
                return [
                    'success' => false,
                    'error' => 'Le but de la mission est requis'
                ];
            }

            if (empty($typeInfo)) {
                return [
                    'success' => false,
                    'error' => 'Les informations du type de mission sont requises'
                ];
            }

            return $this->mistral->genererTousLesChamps($but, $typeInfo, $profil);

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::genererTousLesChamps: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * WORKFLOW COMPLET : Proposition complète en une seule étape
     * Idéal pour le bouton "Générer toute la mission avec l'IA"
     */
    public function propositionComplete(array $riskIds): array
    {
        try {
            if (empty($riskIds)) {
                return [
                    'success' => false,
                    'error' => 'Aucun risque sélectionné'
                ];
            }

            return $this->mistral->propositionComplete($riskIds);

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::propositionComplete: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reformuler un but existant (bouton "Reformuler")
     */
    public function reformulerBut(string $but, array $profil): array
    {
        try {
            if (empty($but)) {
                return [
                    'success' => false,
                    'error' => 'Le but à reformuler est requis'
                ];
            }

            return $this->mistral->reformulerBut($but, $profil);

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::reformulerBut: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Récupérer le profil de risques à partir des IDs (utile pour les appels ultérieurs)
     */
    public function getRiskProfile(array $riskIds): array
    {
        try {
            if (empty($riskIds)) {
                return ['success' => false, 'error' => 'Aucun risque sélectionné'];
            }

            $risks = DB::table('risks')
                ->whereIn('id', $riskIds)
                ->leftJoin('processes', 'risks.process_id', '=', 'processes.id')
                ->select(
                    'risks.id',
                    'risks.code',
                    'risks.label',
                    'risks.frequency_level_id',
                    'risks.impact_level_id',
                    DB::raw('IFNULL(risks.frequency_level_id * risks.impact_level_id, 0) as criticality'),
                    'processes.name as process_name'
                )
                ->get();

            if ($risks->isEmpty()) {
                return ['success' => false, 'error' => 'Risques introuvables'];
            }

            // Calculer les métriques
            $criticalities = $risks->pluck('criticality')->toArray();
            $avgCrit = array_sum($criticalities) / count($criticalities);
            
            $level = 'faible';
            if ($avgCrit >= 12) $level = 'critique';
            elseif ($avgCrit >= 8) $level = 'élevé';
            elseif ($avgCrit >= 5) $level = 'modéré';

            $processNames = $risks->pluck('process_name')->unique()->filter()->values()->toArray();

            return [
                'success' => true,
                'profil' => [
                    'nombre_risques' => $risks->count(),
                    'criticite_moyenne' => round($avgCrit, 2),
                    'niveau_global' => $level,
                    'processus_impactes' => $processNames,
                    'risks' => $risks->toArray()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('❌ AuditMissionAI::getRiskProfile: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}