<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil V — Test de Cheminement
 */
class OutilTestCheminementController extends OutilBaseController
{
    protected string $outilCode   = 'V';
    protected string $outilTable  = 'outil_test_cheminement';
    protected string $outilLabel  = 'Test de Cheminement';
    protected string $outilColor  = '#be185d';
    protected string $codePrefix  = 'TCHE';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilTestCheminement';

    protected function routeName(): string { return 'auditor.ac.outil-test-cheminement'; }

    protected function validationRules(): array
    {
        return [
            'intitule'              => 'required|string|max:255',
            'processus_audite'      => 'nullable|string|max:255',
            'reference_transaction' => 'nullable|string|max:100',
            'date_test'             => 'nullable|date',
            'auditeur'              => 'nullable|string|max:255',
            'synthese_ecarts'       => 'nullable|string',
            'conclusion'            => 'nullable|string',
            'etapes'                => 'nullable|array',
            'questions_verification'=> 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'              => $v['intitule'],
            'processus_audite'      => $v['processus_audite'] ?? null,
            'reference_transaction' => $v['reference_transaction'] ?? null,
            'date_test'             => $v['date_test'] ?? null,
            'auditeur'              => $v['auditeur'] ?? null,
            'synthese_ecarts'       => $v['synthese_ecarts'] ?? null,
            'conclusion'            => $v['conclusion'] ?? null,
            'questions_verification'=> isset($v['questions_verification']) ? json_encode($v['questions_verification'], JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_test_cheminement_etapes')->where('test_id', $id)->delete();
        foreach ($v['etapes'] ?? [] as $idx => $e) {
            $this->db()->table('outil_test_cheminement_etapes')->insert([
                'test_id'            => $id,
                'position'           => $idx + 1,
                'label'              => $e['label'] ?? ($e['description'] ?? ''),
                'document_piece'     => $e['document_piece'] ?? ($e['document'] ?? null),
                'identifiant'        => $e['identifiant'] ?? null,
                'date_etape'         => $e['date_etape'] ?? ($e['date'] ?? null),
                'acteur'             => $e['acteur'] ?? null,
                'lien_etape_precedente' => $e['lien_etape_precedente'] ?? null,
                'present'            => $e['present'] ?? null,
                'controle_applique'  => $e['controle_applique'] ?? ($e['controle'] ?? null),
                'conforme_procedure' => $e['conforme_procedure'] ?? ($e['conforme'] ?? null),
                'observation_ecart'  => $e['observation_ecart'] ?? ($e['observation'] ?? null),
                'preuve_collectee'   => $e['preuve_collectee'] ?? ($e['preuve'] ?? null),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'etapes' => $id ? $this->db()->table('outil_test_cheminement_etapes')
                ->where('test_id', $id)->orderBy('position')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Test de Cheminement).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}