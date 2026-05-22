<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil XIII — Audit Analytique
 */
class OutilAuditAnalytiqueController extends OutilBaseController
{
    protected string $outilCode   = 'XIII';
    protected string $outilTable  = 'outil_audit_analytique';
    protected string $outilLabel  = 'Audit Analytique';
    protected string $outilColor  = '#15803d';
    protected string $codePrefix  = 'ANAL';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilAuditAnalytique';

    protected function routeName(): string { return 'auditor.outils.audit-analytique'; }

    protected function validationRules(): array
    {
        return [
            'auditeur'      => 'nullable|string|max:255',
            'source_donnees'=> 'nullable|string|max:255',
            'date_procedure'=> 'nullable|date',
            'periode'       => 'nullable|string|max:100',
            'conclusion'    => 'nullable|string',
            'lignes'        => 'nullable|array',
            'ecarts'        => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'auditeur'      => $v['auditeur'] ?? null,
            'source_donnees'=> $v['source_donnees'] ?? null,
            'date_procedure'=> $v['date_procedure'] ?? null,
            'periode'       => $v['periode'] ?? null,
            'conclusion'    => $v['conclusion'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_audit_analytique_lignes')->where('procedure_id', $id)->delete();
        foreach ($v['lignes'] ?? [] as $idx => $l) {
            $this->db()->table('outil_audit_analytique_lignes')->insert([
                'procedure_id'    => $id,
                'indicateur'      => $l['indicateur'] ?? '',
                'description'     => $l['description'] ?? null,
                'valeur_n1'       => $l['valeur_n1'] ?? null,
                'valeur_n'        => $l['valeur_n'] ?? null,
                'valeur_budget'   => $l['valeur_budget'] ?? null,
                'ecart_n_n1'      => $l['ecart_n_n1'] ?? null,
                'ecart_pct_n_n1'  => $l['ecart_pct_n_n1'] ?? null,
                'ecart_n_budg'    => $l['ecart_n_budg'] ?? null,
                'ecart_pct_budg'  => $l['ecart_pct_budg'] ?? null,
                'ordre'           => $idx + 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
        $this->db()->table('outil_audit_analytique_ecarts')->where('procedure_id', $id)->delete();
        foreach ($v['ecarts'] ?? [] as $idx => $e) {
            $this->db()->table('outil_audit_analytique_ecarts')->insert([
                'procedure_id' => $id,
                'libelle'      => $e['libelle'] ?? '',
                'significatif' => $e['significatif'] ?? 'non',
                'explication'  => $e['explication'] ?? null,
                'ordre'        => $idx + 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'lignes' => $id ? $this->db()->table('outil_audit_analytique_lignes')->where('procedure_id', $id)->orderBy('ordre')->get()->toArray() : [],
            'ecarts' => $id ? $this->db()->table('outil_audit_analytique_ecarts')->where('procedure_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Audit Analytique).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}