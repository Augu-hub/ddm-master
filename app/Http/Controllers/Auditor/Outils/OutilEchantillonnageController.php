<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil XV — Échantillonnage Statistique
 */
class OutilEchantillonnageController extends OutilBaseController
{
    protected string $outilCode   = 'XV';
    protected string $outilTable  = 'outil_echantillonnage';
    protected string $outilLabel  = 'Échantillonnage Statistique';
    protected string $outilColor  = '#0c4a6e';
    protected string $codePrefix  = 'ECHA';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilEchantillonnage';

    protected function routeName(): string { return 'auditor.outils.echantillonnage'; }

    protected function validationRules(): array
    {
        return [
            'auditeur'                   => 'nullable|string|max:255',
            'objectif_audit'             => 'nullable|string',
            'population_reference'       => 'nullable|string|max:255',
            'objet_test'                 => 'nullable|string|max:255',
            'type_sondage'               => 'nullable|in:attribut,valeur',
            'taille_population'          => 'nullable|integer',
            'niveau_confiance'           => 'nullable|integer',
            'erreur_max'                 => 'nullable|numeric',
            'ecart_type_exploratoire'    => 'nullable|numeric',
            'taux_presence_exploratoire' => 'nullable|numeric',
            'coefficient_t'              => 'nullable|numeric',
            'taille_calculee'            => 'nullable|integer',
            'taille_retenue'             => 'nullable|integer',
            'intervalle_confiance'       => 'nullable|string|max:255',
            'conclusion'                 => 'nullable|string',
            'elements'                   => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'auditeur'                   => $v['auditeur'] ?? null,
            'objectif_audit'             => $v['objectif_audit'] ?? null,
            'population_reference'       => $v['population_reference'] ?? null,
            'objet_test'                 => $v['objet_test'] ?? null,
            'type_sondage'               => $v['type_sondage'] ?? 'attribut',
            'taille_population'          => $v['taille_population'] ?? null,
            'niveau_confiance'           => $v['niveau_confiance'] ?? 95,
            'erreur_max'                 => $v['erreur_max'] ?? null,
            'ecart_type_exploratoire'    => $v['ecart_type_exploratoire'] ?? null,
            'taux_presence_exploratoire' => $v['taux_presence_exploratoire'] ?? null,
            'coefficient_t'              => $v['coefficient_t'] ?? null,
            'taille_calculee'            => $v['taille_calculee'] ?? null,
            'taille_retenue'             => $v['taille_retenue'] ?? null,
            'intervalle_confiance'       => $v['intervalle_confiance'] ?? null,
            'conclusion'                 => $v['conclusion'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_echantillonnage_elements')->where('fiche_id', $id)->delete();
        foreach ($v['elements'] ?? [] as $idx => $e) {
            $this->db()->table('outil_echantillonnage_elements')->insert([
                'fiche_id'          => $id,
                'reference'         => $e['reference'] ?? '',
                'valeur'            => $e['valeur'] ?? null,
                'attribut_present'  => $e['attribut_present'] ?? null,
                'anomalie_detectee' => $e['anomalie_detectee'] ?? 'non',
                'nature_anomalie'   => $e['nature_anomalie'] ?? null,
                'ordre'             => $idx + 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'elements' => $id ? $this->db()->table('outil_echantillonnage_elements')
                ->where('fiche_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Échantillonnage Statistique).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}