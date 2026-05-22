<?php

namespace App\Http\Controllers\Auditor\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════
 * LoadObjectifsRadoTrait
 * ════════════════════════════════════════════════════════════════════════
 *
 * Trait partagé par tous les controllers de programmes de travail :
 *   - ProgCiController
 *   - ProgConformiteController
 *   - ProgMarchesController
 *   - ProgTransactionsController
 *
 * Charge les objectifs depuis le RADO (tous statuts, même draft) dans cet ordre :
 *   1. axes_audit[].objectifs[]   (objectifs groupés par axe IA)
 *   2. objectifs_specifiques[]    (liste plate, fallback)
 *
 * Retourne : ['objectifs' => [...], 'rado_id' => int|null]
 *
 * Chaque objectif retourné a la structure :
 *   [
 *     'objectif'      => string,  // texte EXACT, non reformulé
 *     'axe'           => string,
 *     'priorite'      => string,
 *     'indicateurs'   => string,
 *     'criteres_eval' => string,
 *   ]
 * ════════════════════════════════════════════════════════════════════════
 */
trait LoadObjectifsRadoTrait
{
    /**
     * Point d'entrée principal — appelé par tous les controllers Prog.
     *
     * @param  int  $missionId
     * @param  int  $assignmentId
     * @return array{ objectifs: array, rado_id: int|null }
     */
    protected function chargerObjectifsRadoPourProgramme(int $missionId, int $assignmentId): array
    {
        $prefix = $this->logPrefix();

        // ── Trouver le RADO (tous statuts, assignment puis mission) ──
        $radoRow = DB::connection('tenant')
            ->table('mission_phase_ro')
            ->where('assignment_id', $assignmentId)
            ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
            ->orderByDesc('updated_at')
            ->first();

        if (!$radoRow) {
            $radoRow = DB::connection('tenant')
                ->table('mission_phase_ro')
                ->where('mission_id', $missionId)
                ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                ->orderByDesc('updated_at')
                ->first();
        }

        if (!$radoRow) {
            Log::warning("[{$prefix}] chargerObjectifsRadoPourProgramme : aucun RADO trouvé pour missionId={$missionId}");
            return ['objectifs' => [], 'rado_id' => null];
        }

        Log::info("[{$prefix}] RADO id={$radoRow->id} assignment={$radoRow->assignment_id} status={$radoRow->validation_status}");

        $tousObjectifs = [];
        $textesDeja    = [];

        // ── Source 1 : axes_audit[].objectifs[] ──────────────────
        $axesAudit = $this->decodeArr($radoRow->axes_audit);
        Log::info("[{$prefix}] axes_audit : " . count($axesAudit) . " axe(s) (type=" . gettype($radoRow->axes_audit) . ")");

        foreach ($axesAudit as $axe) {
            if (!is_array($axe)) continue;

            $axeLib   = trim($axe['axe']               ?? '');
            $priorite = trim($axe['priorite']           ?? '');
            $critEval = trim($axe['criteres_evaluation'] ?? '');
            $objListe = is_array($axe['objectifs'] ?? null) ? $axe['objectifs'] : [];

            Log::info("[{$prefix}] Axe '{$axeLib}' → " . count($objListe) . " objectif(s)");

            foreach ($objListe as $obj) {
                $txt = '';
                if (is_array($obj))       $txt = trim($obj['objectif'] ?? $obj['libelle'] ?? '');
                elseif (is_string($obj))  $txt = trim($obj);

                if (strlen($txt) < 4) continue;
                $cle = mb_strtolower($txt);
                if (in_array($cle, $textesDeja, true)) continue;
                $textesDeja[] = $cle;

                $tousObjectifs[] = [
                    'objectif'      => $txt,    // ← TEXTE EXACT DU RADO, jamais reformulé
                    'axe'           => $axeLib,
                    'priorite'      => $priorite,
                    'indicateurs'   => is_array($obj) ? ($obj['indicateurs'] ?? '') : '',
                    'criteres_eval' => $critEval,
                ];
            }
        }

        // ── Source 2 : objectifs_specifiques[] (fallback) ────────
        $objectifsSpecifiques = $this->decodeArr($radoRow->objectifs_specifiques);
        Log::info("[{$prefix}] objectifs_specifiques : " . count($objectifsSpecifiques));

        foreach ($objectifsSpecifiques as $os) {
            $txt = '';
            if (is_array($os))      $txt = trim($os['objectif'] ?? $os['libelle'] ?? '');
            elseif (is_string($os)) $txt = trim($os);

            if (strlen($txt) < 4) continue;
            $cle = mb_strtolower($txt);
            if (in_array($cle, $textesDeja, true)) continue;
            $textesDeja[] = $cle;

            $tousObjectifs[] = [
                'objectif'      => $txt,
                'axe'           => is_array($os) ? ($os['axe']         ?? '') : '',
                'priorite'      => is_array($os) ? ($os['priorite']    ?? '') : '',
                'indicateurs'   => is_array($os) ? ($os['indicateurs'] ?? '') : '',
                'criteres_eval' => '',
            ];
        }

        Log::info("[{$prefix}] Total objectifs RADO collectés : " . count($tousObjectifs));

        return [
            'objectifs' => $tousObjectifs,
            'rado_id'   => $radoRow->id,
        ];
    }

    /**
     * Préfixe log — basé sur le codePrefix du controller.
     */
    private function logPrefix(): string
    {
        return property_exists($this, 'codePrefix') ? $this->codePrefix : 'PROG';
    }

    /**
     * Décoder un champ JSON de la BD (string ou array).
     * Redéfini ici pour être disponible si le controller ne l'a pas.
     */
    protected function decodeArr(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }
}