<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil XI — Piste d\'Audit
 */
class OutilPisteAuditController extends OutilBaseController
{
    protected string $outilCode   = 'XI';
    protected string $outilTable  = 'outil_piste_audit';
    protected string $outilLabel  = 'Piste d\'Audit';
    protected string $outilColor  = '#4f46e5';
    protected string $codePrefix  = 'PAUD';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilPisteAudit';

    protected function routeName(): string { return 'auditor.outils.piste-audit'; }

    protected function validationRules(): array
    {
        return [
            'operation_testee'  => 'required|string|max:255',
            'identifiant_unique'=> 'nullable|string|max:100',
            'processus'         => 'nullable|string|max:255',
            'auditeur'          => 'nullable|string|max:255',
            'date_piste'        => 'nullable|date',
            'conclusion'        => 'nullable|string',
            'etapes'            => 'nullable|array',
            'resultats'         => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'operation_testee'  => $v['operation_testee'],
            'identifiant_unique'=> $v['identifiant_unique'] ?? null,
            'processus'         => $v['processus'] ?? null,
            'auditeur'          => $v['auditeur'] ?? null,
            'date_piste'        => $v['date_piste'] ?? null,
            'conclusion'        => $v['conclusion'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_piste_audit_etapes')->where('piste_id', $id)->delete();
        foreach ($v['etapes'] ?? [] as $idx => $e) {
            $this->db()->table('outil_piste_audit_etapes')->insert([
                'piste_id'           => $id,
                'position'           => $idx + 1,
                'label'              => $e['label'] ?? '',
                'document_piece'     => $e['document_piece'] ?? ($e['document'] ?? null),
                'identifiant'        => $e['identifiant'] ?? null,
                'date_etape'         => $e['date_etape'] ?? ($e['date'] ?? null),
                'acteur'             => $e['acteur'] ?? null,
                'present'            => $e['present'] ?? null,
                'lien_etape_precedente' => $e['lien_etape_precedente'] ?? null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
        $this->db()->table('outil_piste_audit_resultats')->where('piste_id', $id)->delete();
        foreach ($v['resultats'] ?? [] as $r) {
            $this->db()->table('outil_piste_audit_resultats')->insert([
                'piste_id'    => $id,
                'critere'     => $r['critere'] ?? '',
                'resultat'    => $r['resultat'] ?? null,
                'observation' => $r['observation'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'etapes'   => $id ? $this->db()->table('outil_piste_audit_etapes')->where('piste_id', $id)->orderBy('position')->get()->toArray() : [],
            'resultats'=> $id ? $this->db()->table('outil_piste_audit_resultats')->where('piste_id', $id)->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Piste d\'Audit).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}