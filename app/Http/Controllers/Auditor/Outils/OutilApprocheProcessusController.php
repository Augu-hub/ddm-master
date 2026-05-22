<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil IV — Approche Processus
 */
class OutilApprocheProcessusController extends OutilBaseController
{
    protected string $outilCode   = 'IV';
    protected string $outilTable  = 'outil_approche_processus';
    protected string $outilLabel  = 'Approche Processus';
    protected string $outilColor  = '#b45309';
    protected string $codePrefix  = 'APROC';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilApprocheProcessus';

    protected function routeName(): string { return 'auditor.ac.outil-approche-processus'; }

    protected function validationRules(): array
    {
        return [
            'domaine'     => 'nullable|string|max:255',
            'date_analyse'=> 'nullable|date',
            'lignes'      => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'    => $v['domaine'] ?? 'Approche processus',
            'domaine'     => $v['domaine'] ?? null,
            'date_analyse'=> $v['date_analyse'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_approche_processus_lignes')->where('fiche_id', $id)->delete();
        foreach ($v['lignes'] ?? [] as $idx => $l) {
            $this->db()->table('outil_approche_processus_lignes')->insert([
                'fiche_id'           => $id,
                'type'               => $l['type_processus'] ?? ($l['type'] ?? ''),
                'libelle'            => $l['nom'] ?? ($l['libelle'] ?? ''),
                'finalite'           => $l['finalite'] ?? null,
                'elements_entrants'  => $l['entrants'] ?? ($l['elements_entrants'] ?? null),
                'elements_sortants'  => $l['sortants'] ?? ($l['elements_sortants'] ?? null),
                'activites_princ'    => $l['activites'] ?? ($l['activites_princ'] ?? null),
                'clients'            => $l['clients'] ?? null,
                'fournisseurs'       => $l['fournisseurs'] ?? null,
                'ordre'              => $idx + 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'lignes' => $id ? $this->db()->table('outil_approche_processus_lignes')
                ->where('fiche_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Approche Processus).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}