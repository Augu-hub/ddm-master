<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil VIII — Cause / Effet (Ishikawa)
 */
class OutilCauseEffetController extends OutilBaseController
{
    protected string $outilCode   = 'VIII';
    protected string $outilTable  = 'outil_cause_effet';
    protected string $outilLabel  = 'Cause / Effet (Ishikawa)';
    protected string $outilColor  = '#7c3aed';
    protected string $codePrefix  = 'CEFF';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilCauseEffet';

    protected function routeName(): string { return 'auditor.outils.cause-effet'; }

    protected function validationRules(): array
    {
        return [
            'intitule'     => 'nullable|string|max:255',
            'effet_central'=> 'required|string|max:255',
            'description'  => 'nullable|string',
            'participants' => 'nullable|string|max:255',
            'date_analyse' => 'nullable|date',
            'synthese'     => 'nullable|string',
            'causes'       => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'     => $v['effet_central'] ?? ($v['intitule'] ?? 'Cause/Effet'),
            'effet_central'=> $v['effet_central'],
            'description'  => $v['description'] ?? null,
            'participants' => $v['participants'] ?? null,
            'date_analyse' => $v['date_analyse'] ?? null,
            'synthese'     => $v['synthese'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_cause_effet_causes')->where('diagramme_id', $id)->delete();
        foreach ($v['causes'] ?? [] as $idx => $c) {
            $this->db()->table('outil_cause_effet_causes')->insert([
                'diagramme_id'     => $id,
                'categorie'        => $c['categorie'] ?? '',
                'libelle'          => $c['libelle'] ?? ($c['cause_primaire'] ?? ''),
                'sous_cause'       => $c['sous_cause'] ?? null,
                'detail_preuve'    => $c['detail_preuve'] ?? null,
                'priorite'         => (int) ($c['priorite'] ?? 0),
                'action_corrective'=> $c['action_corrective'] ?? null,
                'ordre'            => $idx + 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'causes' => $id ? $this->db()->table('outil_cause_effet_causes')
                ->where('diagramme_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Cause / Effet (Ishikawa)).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}