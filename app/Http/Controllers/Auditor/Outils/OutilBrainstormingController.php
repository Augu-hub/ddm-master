<?php

namespace App\Http\Controllers\Auditor\Outils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Outil X — Brainstorming
 */
class OutilBrainstormingController extends OutilBaseController
{
    protected string $outilCode   = 'X';
    protected string $outilTable  = 'outil_brainstorming';
    protected string $outilLabel  = 'Brainstorming';
    protected string $outilColor  = '#d97706';
    protected string $codePrefix  = 'BRAI';
    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilBrainstorming';

    protected function routeName(): string { return 'auditor.outils.brainstorming'; }

    protected function validationRules(): array
    {
        return [
            'intitule'    => 'nullable|string|max:255',
            'problematique'=> 'nullable|string|max:255',
            'animateur'   => 'nullable|string|max:255',
            'participants'=> 'nullable|string',
            'duree'       => 'nullable|string|max:50',
            'date_session'=> 'nullable|date',
            'synthese'    => 'nullable|string',
            'idees'       => 'nullable|array',
        ];
    }

    protected function buildRecord(array $v, array $ctx): array
    {
        return [
            'intitule'    => $v['problematique'] ?? ($v['intitule'] ?? 'Brainstorming'),
            'problematique'=> $v['problematique'] ?? null,
            'animateur'   => $v['animateur'] ?? null,
            'participants'=> $v['participants'] ?? null,
            'duree'       => $v['duree'] ?? null,
            'date_session'=> $v['date_session'] ?? null,
            'synthese'    => $v['synthese'] ?? null,
        ];
    }

    protected function syncChildren(int $id, array $v): void
    {
        $this->db()->table('outil_brainstorming_idees')->where('session_id', $id)->delete();
        foreach ($v['idees'] ?? [] as $idx => $i) {
            $this->db()->table('outil_brainstorming_idees')->insert([
                'session_id'  => $id,
                'libelle'     => $i['libelle'] ?? ($i['idee'] ?? ''),
                'emis_par'    => $i['emis_par'] ?? ($i['emise_par'] ?? null),
                'theme'       => $i['theme'] ?? null,
                'categorie'   => $i['categorie'] ?? null,
                'votes'       => (int) ($i['votes'] ?? 0),
                'retenue'     => (int) ($i['retenue'] ?? 0),
                'priorite'    => (int) ($i['priorite'] ?? 0),
                'ordre'       => $idx + 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    protected function loadChildren(int $id): array
    {
        return [
            'idees' => $id ? $this->db()->table('outil_brainstorming_idees')
                ->where('session_id', $id)->orderBy('ordre')->get()->toArray() : [],
        ];
    }

    protected function buildIaPrompt(array $record, array $children): string
    {
        $data = array_merge($record, array_map(
            fn($v) => is_array($v) ? $v : (array) $v,
            $children
        ));
        return "Analyse cet outil d'audit interne IFACI (Brainstorming).\n\n"
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nFournis: synthese, points_forts, points_faibles, risques, recommandations, score (0-10).";
    }
}