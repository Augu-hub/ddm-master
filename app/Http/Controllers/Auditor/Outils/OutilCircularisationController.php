<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil XII — Circularisation
 */
class OutilCircularisationController extends OutilBaseController
{
    protected string $outilCode   = 'XII';
    protected string $outilTable  = 'outil_circularisation';
    protected string $outilLabel  = 'Circularisation';
    protected string $outilColor  = '#0369a1';
    protected string $codePrefix  = 'CIRC';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilCircularisation';

    protected function routeName(): string { return 'auditor.outils.circularisation'; }

    protected function validationRules(): array
    {
        return [
            'date_envoi'           => 'nullable|date',
            'date_limite'          => 'nullable|date',
            'adresse_reception'    => 'nullable|string',
            'auditeur_responsable' => 'nullable|string|max:255',
            'demandes'             => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'date_envoi'           => $v['date_envoi'] ?? null,
            'date_limite'          => $v['date_limite'] ?? null,
            'adresse_reception'    => $v['adresse_reception'] ?? null,
            'auditeur_responsable' => $v['auditeur_responsable'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_circularisation_demandes')->where('fiche_id', $id)->delete();
        foreach ($v['demandes'] ?? [] as $idx => $d) {
            $this->db()->table('outil_circularisation_demandes')->insert([
                'fiche_id'           => $id,
                'nom_tiers'          => $d['nom_tiers'] ?? '',
                'element_confirmer'  => $d['element_confirmer'] ?? null,
                'date_envoi_demande' => $d['date_envoi_demande'] ?? null,
                'date_reponse'       => $d['date_reponse'] ?? null,
                'montant_envoye'     => $d['montant_envoye'] ?? null,
                'montant_confirme'   => $d['montant_confirme'] ?? null,
                'ecart'              => $d['ecart'] ?? null,
                'statut_reponse'     => $d['statut_reponse'] ?? 'en_attente',
                'observation'        => $d['observation'] ?? null,
                'niveau_fiabilite'   => (int) ($d['niveau_fiabilite'] ?? 3),
                'ordre'              => $idx + 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'demandes' => $id ? $this->db()->table('outil_circularisation_demandes')
                ->where('fiche_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Circularisation).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}