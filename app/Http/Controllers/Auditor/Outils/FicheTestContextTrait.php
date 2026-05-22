<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trait FicheTestContextTrait
 *
 * Fournit le contexte de la fiche de test parente à tous les outils IFACI.
 * La table réelle dans le tenant est `mission_phase_fiche_test` (pas `fiche_tests`).
 */
trait FicheTestContextTrait
{
    // ─────────────────────────────────────────────────────────────────
    // TABLE — nom réel dans la BD tenant
    // ─────────────────────────────────────────────────────────────────

    private string $ficheTestTable   = 'mission_phase_fiche_test';
    private string $ficheOutilsTable = 'fiche_test_outils';

    // ─────────────────────────────────────────────────────────────────
    // CONNEXION TENANT
    // ─────────────────────────────────────────────────────────────────

    private function tenantDb(): \Illuminate\Database\Connection
    {
        return DB::connection('tenant');
    }

    // ─────────────────────────────────────────────────────────────────
    // CONTEXTE FICHE TEST
    // ─────────────────────────────────────────────────────────────────

    /**
     * Construit le contexte complet de la fiche de test depuis les query params.
     *
     * Params attendus en query string (passés par FicheTest.vue → ouvrirOutil()) :
     *   fiche_test_id, mission_id, assignment_id, test_ref, obj_num,
     *   proc_idx, procedure_code, libelle_test, libelle_proc, objectif_audit, back
     */
    protected function ficheTestContext(Request $request): array
    {
        $ficheTestId   = $request->query('fiche_test_id');
        $missionId     = $request->query('mission_id');
        $assignmentId  = $request->query('assignment_id');
        $testRef       = $request->query('test_ref');
        $objNum        = $request->query('obj_num');
        $procIdx       = $request->query('proc_idx', 0);
        $procedureCode = $request->query('procedure_code');
        $backUrl       = $request->query('back', '/');

        // Données textuelles passées directement en query
        $libelleTest   = $request->query('libelle_test');
        $libelleProc   = $request->query('libelle_proc');
        $objectifAudit = $request->query('objectif_audit');

        $hasContext = !empty($ficheTestId) && !empty($missionId);

        // Query string complète à ré-appender aux URLs
        $queryString = $hasContext
            ? '?' . http_build_query(array_filter([
                'fiche_test_id'  => $ficheTestId,
                'mission_id'     => $missionId,
                'assignment_id'  => $assignmentId,
                'test_ref'       => $testRef,
                'obj_num'        => $objNum,
                'proc_idx'       => $procIdx,
                'procedure_code' => $procedureCode,
                'libelle_test'   => $libelleTest,
                'libelle_proc'   => $libelleProc,
                'objectif_audit' => $objectifAudit,
                'back'           => $backUrl,
            ]))
            : '';

        // Contexte mission de base
        $missionContext = [];
        if ($missionId) {
            $mission = $this->tenantDb()->table('missions')->where('id', $missionId)->first();
            $missionContext['mission_id']      = $missionId;
            $missionContext['mission_libelle'] = $mission->title ?? $mission->libelle ?? null;
        }
        if ($assignmentId)  $missionContext['assignment_id']  = $assignmentId;
        if ($procedureCode) $missionContext['procedure_code'] = $procedureCode;
        if ($testRef)       $missionContext['test_ref']       = $testRef;
        if ($objNum)        $missionContext['obj_num']        = $objNum;
        if ($ficheTestId)   $missionContext['fiche_test_id']  = $ficheTestId;

        // Libellés textuels prioritaires (passés en query)
        if ($libelleTest)   $missionContext['libelle_test']   = $libelleTest;
        if ($libelleProc)   $missionContext['libelle_proc']   = $libelleProc;
        if ($objectifAudit) $missionContext['objectif_audit'] = $objectifAudit;

        // Fallback BD : récupérer depuis mission_phase_fiche_test si libellés absents
        if ($ficheTestId && (!$objectifAudit || !$libelleTest)) {
            try {
                $ficheTest = $this->tenantDb()
                    ->table($this->ficheTestTable)   // ← 'mission_phase_fiche_test'
                    ->where('id', $ficheTestId)
                    ->first();

                if ($ficheTest) {
                    $programmeData = null;

                    // Chercher d'abord dans outils_data (JSON stocké dans fiche)
                    if (!empty($ficheTest->outils_data)) {
                        $outilsData = is_string($ficheTest->outils_data)
                            ? json_decode($ficheTest->outils_data, true)
                            : (array) $ficheTest->outils_data;

                        foreach ((array) $outilsData as $outil) {
                            if (
                                isset($outil['_key'], $outil['objectif_audit']) &&
                                str_contains($outil['_key'], $testRef ?? '')
                            ) {
                                if (empty($missionContext['objectif_audit'])) {
                                    $missionContext['objectif_audit'] = $outil['objectif_audit'];
                                }
                                break;
                            }
                        }
                    }

                    // Chercher dans resultats (programme de travail JSON)
                    if (!empty($ficheTest->resultats) && $testRef) {
                        $resultats = is_string($ficheTest->resultats)
                            ? json_decode($ficheTest->resultats, true)
                            : (array) $ficheTest->resultats;

                        foreach ((array) $resultats as $r) {
                            if (($r['test_ref'] ?? '') === $testRef) {
                                if (empty($missionContext['libelle_test']) && !empty($r['libelle'])) {
                                    $missionContext['libelle_test'] = $r['libelle'];
                                }
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ne pas bloquer si la table n'est pas accessible
                \Log::warning('[FicheTestContextTrait] ' . $e->getMessage());
            }
        }

        return [
            'has_context'    => $hasContext,
            'fiche_test_id'  => $ficheTestId,
            'mission_id'     => $missionId,
            'assignment_id'  => $assignmentId,
            'test_ref'       => $testRef,
            'obj_num'        => $objNum,
            'proc_idx'       => (int) $procIdx,
            'procedure_code' => $procedureCode,
            'back_url'       => $backUrl,
            'query_string'   => $queryString,
            'missionContext' => $missionContext,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // RECHERCHE D'UN OUTIL EXISTANT POUR CETTE FICHE + TEST
    // ─────────────────────────────────────────────────────────────────

    /**
     * Cherche si un outil de ce type existe déjà pour cette fiche/test/procédure.
     * Utilise fiche_test_outils comme table de liaison.
     */
    protected function findExistingOutilId(
        ?string $ficheTestId,
        string  $outilCode,
        ?string $testRef,
        int     $procIdx = 0
    ): ?int {
        if (!$ficheTestId) return null;

        try {
            $row = $this->tenantDb()
                ->table($this->ficheOutilsTable)
                ->where('fiche_test_id', $ficheTestId)
                ->where('outil_code', $outilCode)
                ->when($testRef, fn ($q) => $q->where('test_ref', $testRef))
                ->where('proc_idx', $procIdx)
                ->where('is_current', 1)
                ->orderByDesc('id')
                ->first();

            return $row?->outil_id;
        } catch (\Exception $e) {
            \Log::warning('[FicheTestContextTrait] findExistingOutilId : ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // ENREGISTREMENT DU LIEN FICHE TEST ↔ OUTIL
    // ─────────────────────────────────────────────────────────────────

    /**
     * Crée ou met à jour l'entrée dans fiche_test_outils après store/update d'un outil.
     */
    protected function saveFicheTestLinkIfPresent(
        Request $request,
        int     $outilId,
        string  $outilCode,
        string  $outilTable
    ): void {
        $ficheTestId  = $request->input('fiche_test_id') ?? $request->query('fiche_test_id');
        $missionId    = $request->input('mission_id')    ?? $request->query('mission_id');
        $assignmentId = $request->input('assignment_id') ?? $request->query('assignment_id');
        $testRef      = $request->input('test_ref')      ?? $request->query('test_ref');
        $objNum       = $request->input('obj_num')       ?? $request->query('obj_num');
        $procIdx      = (int) ($request->input('proc_idx') ?? $request->query('proc_idx', 0));
        $procedureCode= $request->input('procedure_code') ?? $request->query('procedure_code');

        if (!$ficheTestId || !$missionId) return;

        try {
            // Désactiver les versions précédentes
            $this->tenantDb()
                ->table($this->ficheOutilsTable)
                ->where('fiche_test_id', $ficheTestId)
                ->where('outil_code', $outilCode)
                ->where('test_ref', $testRef)
                ->where('proc_idx', $procIdx)
                ->update(['is_current' => 0]);

            // Récupérer le numéro de version suivant
            $lastVersion = $this->tenantDb()
                ->table($this->ficheOutilsTable)
                ->where('fiche_test_id', $ficheTestId)
                ->where('outil_code', $outilCode)
                ->where('test_ref', $testRef)
                ->where('proc_idx', $procIdx)
                ->max('version') ?? 0;

            // Insérer le nouveau lien
            $this->tenantDb()->table($this->ficheOutilsTable)->insert([
                'fiche_test_id'  => $ficheTestId,
                'mission_id'     => $missionId,
                'assignment_id'  => $assignmentId,
                'procedure_code' => $procedureCode ?? $testRef,
                'test_ref'       => $testRef,
                'proc_idx'       => $procIdx,
                'obj_num'        => $objNum,
                'outil_code'     => $outilCode,
                'outil_table'    => $outilTable,
                'outil_id'       => $outilId,
                'version'        => $lastVersion + 1,
                'is_current'     => 1,
                'created_by'     => \Illuminate\Support\Facades\Auth::id(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Exception $e) {
            // Non bloquant — on log et on continue
            \Log::warning('[FicheTestContextTrait] saveFicheTestLinkIfPresent : ' . $e->getMessage());
        }
    }
}