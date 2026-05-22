<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil XIV — Observation Directe
 */
class OutilObservationController extends OutilBaseController
{
    protected string $outilCode   = 'XIV';
    protected string $outilTable  = 'outil_observation';
    protected string $outilLabel  = 'Observation Directe';
    protected string $outilColor  = '#9333ea';
    protected string $codePrefix  = 'OBS';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilObservation';

    protected function routeName(): string { return 'auditor.outils.observation'; }

    protected function validationRules(): array
    {
        return [
            'date_observation'        => 'nullable|date',
            'heure_debut'             => 'nullable|string|max:10',
            'heure_fin'               => 'nullable|string|max:10',
            'auditeur'                => 'nullable|string|max:255',
            'localisation'            => 'nullable|string|max:255',
            'interlocuteurs_presents' => 'nullable|string',
            'objectif_audit'          => 'nullable|string',
            'tache_local_observer'    => 'nullable|string',
            'elements_verifier'       => 'nullable|string',
            'pieces_attendues'        => 'nullable|string',
            'points_forts'            => 'nullable|string',
            'points_faibles'          => 'nullable|string',
            'conclusion'              => 'nullable|string',
            'constats'                => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'date_observation'        => $v['date_observation'] ?? null,
            'heure_debut'             => $v['heure_debut'] ?? null,
            'heure_fin'               => $v['heure_fin'] ?? null,
            'auditeur'                => $v['auditeur'] ?? null,
            'localisation'            => $v['localisation'] ?? null,
            'interlocuteurs_presents' => $v['interlocuteurs_presents'] ?? null,
            'objectif_audit'          => $v['objectif_audit'] ?? null,
            'tache_local_observer'    => $v['tache_local_observer'] ?? null,
            'elements_verifier'       => $v['elements_verifier'] ?? null,
            'pieces_attendues'        => $v['pieces_attendues'] ?? null,
            'points_forts'            => $v['points_forts'] ?? null,
            'points_faibles'          => $v['points_faibles'] ?? null,
            'conclusion'              => $v['conclusion'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_observation_constats')->where('observation_id', $id)->delete();
        foreach ($v['constats'] ?? [] as $idx => $c) {
            $this->db()->table('outil_observation_constats')->insert([
                'observation_id'     => $id,
                'element_observe'    => $c['element_observe'] ?? '',
                'conforme_referentiel'=> $c['conforme_referentiel'] ?? null,
                'ecart_constate'     => $c['ecart_constate'] ?? null,
                'risque_associe'     => $c['risque_associe'] ?? null,
                'preuve'             => $c['preuve'] ?? null,
                'ordre'              => $idx + 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'constats' => $id ? $this->db()->table('outil_observation_constats')
                ->where('observation_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Observation Directe).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}