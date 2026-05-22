<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;

/**
 * RapportAuditService
 *
 * Construit toutes les données nécessaires au rapport d'audit interne
 * depuis la base de données tenant (mission_programmation + tables liées).
 *
 * Structure du rapport :
 *   Section 1 – Résumé Exécutif (opinion, stats, plan d'actions, points forts)
 *   Section 2 – Tableau 3 colonnes par Objectif > Test/Critère > FRAP
 *   Section 3 – Annexes (objectifs, équipe, destinataires)
 */
class RapportAuditService
{
    // ------------------------------------------------------------------
    //  Point d'entrée principal
    // ------------------------------------------------------------------

    public function getDonneesRapport(int $missionId): array
    {
        $mission   = $this->getMission($missionId);
        $entity    = $this->getEntity($missionId);
        $equipe    = $this->getEquipeAudit($missionId);
        $constats  = $this->getConstats($missionId);
        $progLines = $this->getProgrammeLines($missionId);   // objectifs du prog CI

        $statsConstats = $this->buildStats($constats);
        $opinion       = $this->buildOpinion($constats);
        $planActions   = $this->buildPlanActions($constats);
        $pointsForts   = $this->buildPointsForts($constats);

        // Tableau 3 colonnes : objectifs > tests/critères > constats
        $tableauObjectifs = $this->buildTableauObjectifs($progLines, $constats);

        // Annexes
        $objectifsSpecifiques = $this->buildObjectifsSpecifiques($progLines);
        $criteresCI           = $this->getCriteresCI($missionId);
        $destinataires        = $this->getDestinataires($missionId);

        return compact(
            'mission',
            'entity',
            'equipe',
            'constats',
            'statsConstats',
            'opinion',
            'planActions',
            'pointsForts',
            'tableauObjectifs',
            'objectifsSpecifiques',
            'criteresCI',
            'destinataires',
        );
    }

    // ------------------------------------------------------------------
    //  Données de base
    // ------------------------------------------------------------------

    private function getMission(int $missionId): object
    {
        return DB::connection('tenant')
            ->table('mission_programmation')
            ->where('id', $missionId)
            ->first() ?? (object)[
                'id'          => $missionId,
                'libelle'     => 'Mission inconnue',
                'objectif'    => null,
                'lieux'       => null,
                'date_debut'  => now()->format('Y-m-d'),
                'date_fin'    => now()->format('Y-m-d'),
                'code_mission'=> 'N/A',
                'numero_fpm'  => null,
            ];
    }

    private function getEntity(int $missionId): ?object
    {
        return DB::connection('tenant')
            ->table('mission_programmation_entity as mpe')
            ->join('entities as e', 'e.id', '=', 'mpe.entity_id')
            ->where('mpe.mission_programmation_id', $missionId)
            ->select('e.id', 'e.name as entity_name', 'e.code_base as entity_code')
            ->first();
    }

    private function getEquipeAudit(int $missionId): array
    {
        $rows = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->select(
                'a.id',
                DB::raw("CONCAT(COALESCE(a.first_name,''), ' ', COALESCE(a.last_name,'')) as nom_complet"),
                'mpaa.role_code as role',
                'a.audit_code',
            )
            ->distinct()
            ->get()
            ->toArray();

        return array_map(fn($r) => (array) $r, $rows);
    }

    // ------------------------------------------------------------------
    //  Constats FRAP
    // ------------------------------------------------------------------

    private function getConstats(int $missionId): array
    {
        $rows = DB::connection('tenant')
            ->table('fiche_observation_frap')
            ->where('mission_id', $missionId)
            ->whereNull('deleted_at')
            ->orderBy('obj_num')
            ->orderBy('code')
            ->get();

        return $rows->map(function ($r) {
            $r->importance = $this->mapPrioriteToImportance($r->priorite ?? 'basse');
            return (array) $r;
        })->toArray();
    }

    /** Convertit la priorité FRAP en niveau d'importance lisible */
    private function mapPrioriteToImportance(string $priorite): string
    {
        return match ($priorite) {
            'critique' => 'critique',
            'haute'    => 'haute',
            'moyenne'  => 'moyenne',
            default    => 'basse',
        };
    }

    // ------------------------------------------------------------------
    //  Lignes du programme de travail CI (objectifs + tests)
    // ------------------------------------------------------------------

    /**
     * Récupère et décode les lignes JSON de mission_phase_prog_ci.
     * Renvoie un tableau plat de lignes avec leur assignment_id.
     */
    private function getProgrammeLines(int $missionId): array
    {
        $progs = DB::connection('tenant')
            ->table('mission_phase_prog_ci')
            ->where('mission_id', $missionId)
            ->orderBy('id')
            ->get(['id', 'assignment_id', 'lignes', 'code']);

        $allLines = [];
        foreach ($progs as $prog) {
            $lignes = $this->decodeJson($prog->lignes, []);
            foreach ($lignes as $ligne) {
                $ligne['_prog_code']      = $prog->code ?? '';
                $ligne['_assignment_id']  = $prog->assignment_id;
                $ligne['_prog_id']        = $prog->id;
                $allLines[] = $ligne;
            }
        }

        return $allLines;
    }

    // ------------------------------------------------------------------
    //  Tableau 3 colonnes (cœur du rapport)
    // ------------------------------------------------------------------

    /**
     * Construit un tableau structuré par objectif > tests > constats.
     *
     * Chaque élément du tableau retourné :
     * [
     *   'num'         => 'O1',
     *   'objectif'    => 'Vérifier …',
     *   'axe'         => 'Optimisation des processus agricoles',
     *   'priorite'    => 'haute',
     *   'criteres_eval'  => '…',
     *   'risque_code' => 'FI-001',
     *   'risque_libelle' => '…',
     *   'type_controle'  => 'Préventif',
     *   'criticite'   => 2,
     *   'responsable' => '…',
     *   'tests'       => [
     *       [
     *          'ref'        => 'T_O1',
     *          'libelle'    => '…',
     *          'procedures' => ['…', '…'],
     *          'auditeur'   => '…',
     *          'constats'   => [ [frap fields…], … ],
     *       ], …
     *   ],
     *   'constats_directs' => [ [frap fields…], … ],  // FRAPs liés à l'objectif sans test précis
     * ]
     */
    private function buildTableauObjectifs(array $progLines, array $constats): array
    {
        $result = [];

        // Index des constats par obj_num et par test_ref pour un lookup rapide
        $constatsByObj  = [];
        $constatsByTest = [];
        foreach ($constats as $c) {
            $objNum  = $c['obj_num']   ?? null;
            $testRef = $c['test_ref']  ?? null;
            if ($objNum)  $constatsByObj[$objNum][]   = $c;
            if ($testRef) $constatsByTest[$testRef][] = $c;
        }

        // Dédoublonner les objectifs par num
        $seen = [];
        foreach ($progLines as $ligne) {
            $num = $ligne['num'] ?? null;
            if (!$num || isset($seen[$num])) continue;
            $seen[$num] = true;

            $tests = $ligne['tests'] ?? [];
            $enrichedTests = [];
            foreach ($tests as $t) {
                $testRef = $t['ref'] ?? null;
                $t['constats'] = $testRef
                    ? ($constatsByTest[$testRef] ?? [])
                    : [];
                $enrichedTests[] = $t;
            }

            // Constats liés à l'objectif sans référence de test précise
            $allTestRefs    = array_column($enrichedTests, 'ref');
            $constatsDirects = array_filter(
                $constatsByObj[$num] ?? [],
                fn($c) => !in_array($c['test_ref'] ?? null, $allTestRefs, true)
            );

            $result[] = [
                'num'             => $num,
                'objectif'        => $ligne['objectif']             ?? '',
                'axe'             => $ligne['_axe_rado']            ?? '',
                'priorite'        => $ligne['_priorite']            ?? 'basse',
                'criteres_eval'   => $ligne['_criteres_eval']       ?? '',
                'risque_code'     => $ligne['_risque_code']         ?? '',
                'risque_libelle'  => $ligne['_risque_libelle']      ?? '',
                'type_controle'   => $ligne['_type_controle']       ?? '',
                'criticite'       => $ligne['_criticite']           ?? 0,
                'responsable'     => $ligne['_responsable']         ?? '',
                'process_name'    => $ligne['_process_name']        ?? '',
                'source'          => $ligne['_source']              ?? '',
                'tests'           => $enrichedTests,
                'constats_directs'=> array_values($constatsDirects),
            ];
        }

        // Constats orphelins (pas d'objectif dans prog_ci)
        $handledNums = array_column($result, 'num');
        $orphans = array_filter($constats, function ($c) use ($handledNums) {
            return !in_array($c['obj_num'] ?? null, $handledNums, true);
        });

        if (!empty($orphans)) {
            $result[] = [
                'num'             => 'AUTRES',
                'objectif'        => 'Autres constats sans objectif associé',
                'axe'             => '',
                'priorite'        => 'basse',
                'criteres_eval'   => '',
                'risque_code'     => '',
                'risque_libelle'  => '',
                'type_controle'   => '',
                'criticite'       => 0,
                'responsable'     => '',
                'process_name'    => '',
                'source'          => '',
                'tests'           => [],
                'constats_directs'=> array_values($orphans),
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    //  Stats, opinion, plan d'actions, points forts
    // ------------------------------------------------------------------

    private function buildStats(array $constats): array
    {
        $stats = ['total' => count($constats), 'critique' => 0, 'significatif' => 0, 'peu_significatif' => 0, 'maintenance' => 0];
        foreach ($constats as $c) {
            match ($c['importance'] ?? 'basse') {
                'critique' => $stats['critique']++,
                'haute'    => $stats['significatif']++,
                'moyenne'  => $stats['peu_significatif']++,
                default    => $stats['maintenance']++,
            };
        }
        return $stats;
    }

    private function buildOpinion(array $constats): array
    {
        $hasCritique  = collect($constats)->contains(fn($c) => ($c['importance'] ?? '') === 'critique');
        $hasHaute     = collect($constats)->contains(fn($c) => ($c['importance'] ?? '') === 'haute');
        $hasMoyenne   = collect($constats)->contains(fn($c) => ($c['importance'] ?? '') === 'moyenne');

        if ($hasCritique) {
            return ['niveau' => 'Critique', 'description' => 'Des faiblesses majeures du contrôle interne ont été identifiées. Des actions correctives urgentes sont requises.'];
        }
        if ($hasHaute) {
            return ['niveau' => 'Haute', 'description' => 'Des faiblesses significatives ont été relevées. Un plan d\'action structuré doit être mis en œuvre rapidement.'];
        }
        if ($hasMoyenne) {
            return ['niveau' => 'Moyenne', 'description' => 'Des améliorations sont souhaitables sur certains contrôles. Le dispositif global est partiellement satisfaisant.'];
        }
        return ['niveau' => 'Basse', 'description' => 'Le dispositif de contrôle interne est globalement satisfaisant. Des ajustements mineurs sont recommandés.'];
    }

    private function buildPlanActions(array $constats): array
    {
        $actions = [];
        foreach ($constats as $c) {
            if (!empty($c['recommandation'])) {
                $recos = explode("\n", $c['recommandation']);
                foreach ($recos as $reco) {
                    $reco = trim($reco, "•\t\r\n ");
                    if ($reco === '') continue;
                    $actions[] = [
                        'recommandation' => $reco,
                        'responsable'    => $c['personne_responsable'] ?? '',
                        'echeance'       => $c['date_echeance'] ?? '',
                        'priorite'       => $this->importanceToLabel($c['importance'] ?? 'basse'),
                        'constat_code'   => $c['num_frap'] ?? $c['code'] ?? '',
                    ];
                }
            }
        }
        return $actions;
    }

    private function buildPointsForts(array $constats): array
    {
        $pf = [];
        foreach ($constats as $c) {
            if (!empty($c['points_forts'])) {
                foreach (explode("\n", $c['points_forts']) as $p) {
                    $p = trim($p, "•\t\r\n ");
                    if ($p !== '') $pf[] = $p;
                }
            }
        }
        return array_unique($pf);
    }

    private function buildObjectifsSpecifiques(array $progLines): array
    {
        $seen = [];
        $result = [];
        foreach ($progLines as $ligne) {
            $num = $ligne['num'] ?? '';
            if (isset($seen[$num])) continue;
            $seen[$num] = true;
            $result[] = [
                'num'              => $num,
                'axe'              => $ligne['_axe_rado']      ?? '',
                'objectif'         => $ligne['objectif']       ?? '',
                'criteres_evaluation' => $ligne['_criteres_eval'] ?? '',
            ];
        }
        return $result;
    }

    // ------------------------------------------------------------------
    //  Critères CI et destinataires
    // ------------------------------------------------------------------

    private function getCriteresCI(int $missionId): array
    {
        // Tente de récupérer les critères CI depuis mission_phase_ar (analyse risques)
        $ar = DB::connection('tenant')
            ->table('mission_phase_ar')
            ->where('mission_id', $missionId)
            ->first();

        if (!$ar) return [];

        $criteres = $this->decodeJson($ar->criteres_evaluation ?? null, []);
        return array_map(fn($c) => is_array($c) ? $c : ['critere' => $c], $criteres);
    }

    private function getDestinataires(int $missionId): array
    {
        // Récupère les membres de l'équipe comme destinataires par défaut
        $equipe = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->select(DB::raw("CONCAT(COALESCE(a.first_name,''), ' ', COALESCE(a.last_name,''), ' (', mpaa.role_code, ')') as dest"))
            ->distinct()
            ->pluck('dest')
            ->toArray();

        return $equipe ?: ['Direction Générale', 'Direction de l\'Audit Interne'];
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    private function decodeJson(?string $value, mixed $default = []): mixed
    {
        if (is_null($value) || $value === '') return $default;
        $decoded = json_decode($value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
    }

    private function importanceToLabel(string $importance): string
    {
        return match ($importance) {
            'critique' => 'Critique',
            'haute'    => 'Significatif',
            'moyenne'  => 'Peu significatif',
            default    => 'Maintenance',
        };
    }

    public function getNiveauMaitrise(array $constat): array
    {
        $imp = $constat['importance'] ?? 'basse';
        return [
            'niveau_initial' => $this->importanceToLabel($imp),
            'statut'         => $constat['statut'] ?? 'draft',
            'indicateur'     => $constat['niveau_controle_interne'] ?? '',
        ];
    }
}