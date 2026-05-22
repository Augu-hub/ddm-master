<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil III — Diagramme de Flux
 */
class OutilDiagrammeFluxController extends OutilBaseController
{
    protected string $outilCode   = 'III';
    protected string $outilTable  = 'outil_diagramme_flux';
    protected string $outilLabel  = 'Diagramme de Flux';
    protected string $outilColor  = '#6d28d9';
    protected string $codePrefix  = 'DFLUX';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilDiagrammeFlux';

    protected function routeName(): string { return 'auditor.ac.outil-diagramme-flux'; }

    protected function validationRules(): array
    {
        return [
            'processus'           => 'nullable|string|max:255',
            'version'             => 'nullable|string|max:20',
            'date'                => 'nullable|date',
            'description_narrative'  => 'nullable|string',
            'synthese_validations'   => 'nullable|string',
            'activites_json'      => 'nullable|string',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'            => $v['processus'] ?? ($v['intitule'] ?? 'Diagramme de flux'),
            'processus'           => $v['processus'] ?? null,
            'version'             => $v['version'] ?? 'V1',
            'date'                => $v['date'] ?? null,
            'description_narrative'  => $v['description_narrative'] ?? null,
            'synthese_validations'   => $v['synthese_validations'] ?? null,
            'activites_json'      => $v['activites_json'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {        // Pas de table enfant séparée — activités stockées en JSON
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
        return "Analyse cet outil d'audit interne IFACI (Diagramme de Flux).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}