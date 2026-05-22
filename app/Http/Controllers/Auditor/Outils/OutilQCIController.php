<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil IX — Questionnaire Contrôle Interne
 */
class OutilQCIController extends OutilBaseController
{
    protected string $outilCode   = 'IX';
    protected string $outilTable  = 'outil_qci';
    protected string $outilLabel  = 'Questionnaire Contrôle Interne';
    protected string $outilColor  = '#0f766e';
    protected string $codePrefix  = 'QCI';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilQCI';

    protected function routeName(): string { return 'auditor.outils.qci'; }

    protected function validationRules(): array
    {
        return [
            'intitule'       => 'nullable|string|max:255',
            'perimetre'      => 'nullable|string|max:255',
            'processus'      => 'nullable|string|max:255',
            'cadre_reference'=> 'nullable|string|max:50',
            'date_qci'       => 'nullable|date',
            'conclusion'     => 'nullable|string',
            'sections'       => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'       => $v['intitule'] ?? ($v['processus'] ?? 'QCI'),
            'perimetre'      => $v['perimetre'] ?? null,
            'processus'      => $v['processus'] ?? null,
            'cadre_reference'=> $v['cadre_reference'] ?? 'COSO',
            'date_qci'       => $v['date_qci'] ?? null,
            'conclusion'     => $v['conclusion'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_qci_sections')->where('qci_id', $id)->delete();
        $this->db()->table('outil_qci_questions')->where('qci_id', $id)->delete();
        foreach ($v['sections'] ?? [] as $si => $section) {
            $secId = $this->db()->table('outil_qci_sections')->insertGetId([
                'qci_id'    => $id,
                'libelle'   => $section['libelle'] ?? '',
                'code'      => $section['code'] ?? '',
                'ordre'     => $si + 1,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
            foreach ($section['questions'] ?? [] as $qi => $q) {
                $this->db()->table('outil_qci_questions')->insert([
                    'qci_id'      => $id,
                    'section_id'  => $secId,
                    'num'         => $q['num'] ?? ($qi + 1),
                    'libelle'     => $q['libelle'] ?? '',
                    'reponse'     => $q['reponse'] ?? '',
                    'commentaire' => $q['commentaire'] ?? null,
                    'risque_si_non'=> $q['risque_si_non'] ?? null,
                    'niveau_risque'=> $q['niveau_risque'] ?? null,
                    'ordre'       => $qi + 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'sections'  => $id ? $this->db()->table('outil_qci_sections')->where('qci_id', $id)->orderBy('ordre')->get()->toArray() : [],
            'questions' => $id ? $this->db()->table('outil_qci_questions')->where('qci_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Questionnaire Contrôle Interne).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}