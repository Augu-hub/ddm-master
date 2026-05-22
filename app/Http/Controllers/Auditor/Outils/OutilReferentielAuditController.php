<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil VII — Référentiel d\'Audit
 */
class OutilReferentielAuditController extends OutilBaseController
{
    protected string $outilCode   = 'VII';
    protected string $outilTable  = 'outil_referentiel_audit';
    protected string $outilLabel  = 'Référentiel d\'Audit';
    protected string $outilColor  = '#0891b2';
    protected string $codePrefix  = 'RAUD';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilReferentielAudit';

    protected function routeName(): string { return 'auditor.outils.referentiel-audit'; }

    protected function validationRules(): array
    {
        return [
            'processus'   => 'nullable|string|max:255',
            'cadre_ref'   => 'nullable|string|max:50',
            'date'        => 'nullable|date',
            'lignes'      => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'    => $v['processus'] ?? "Référentiel d'audit",
            'processus'   => $v['processus'] ?? null,
            'cadre_ref'   => $v['cadre_ref'] ?? 'COSO',
            'date'        => $v['date'] ?? null,
            'lignes'      => isset($v['lignes']) ? json_encode($v['lignes'], JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {        // Lignes stockées en JSON dans la table principale
    }

    protected function loadChildren(int $id): array
    {        return [];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Référentiel d\'Audit).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}