<?php

namespace App\Imports;

use App\Models\AnalyseConformite;
use App\Models\AnalyseConformiteItem;
use App\Models\QccPhase;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class QccImport implements ToCollection
{
    public function __construct(
        private readonly AnalyseConformite $form
    ) {}

    public function collection(Collection $rows)
    {
        $currentPhase = null;
        $currentPhaseModel = null;
        $ordre = 0;

        foreach ($rows as $row) {
            $ref       = trim($row[0] ?? '');
            $libelle   = trim($row[1] ?? '');
            $exigence  = trim($row[2] ?? '');

            // Ignore les lignes vides et l'en-tête
            if (empty($ref) && empty($libelle) && empty($exigence)) continue;
            if ($ref === 'QCC' || $ref === 'TITRE QCC') continue;

            // Nouvelle phase (ex: "QHS 1", "QHS 2")
            if (!empty($ref) && preg_match('/^QHS\s*\d+/i', $ref)) {
                $currentPhase = $ref;
                $currentPhaseModel = QccPhase::updateOrCreate(
                    [
                        'analyse_conformite_id' => $this->form->id,
                        'ref_article'           => $ref,
                    ],
                    [
                        'libelle_norme' => $libelle,
                        'ordre'         => $ordre++,
                    ]
                );
            }

            // Ligne d'exigence
            if (!empty($exigence) && $currentPhaseModel) {
                AnalyseConformiteItem::updateOrCreate(
                    [
                        'analyse_conformite_id' => $this->form->id,
                        'phase_id'              => $currentPhaseModel->id,
                        'exigence_norme'        => $exigence,
                    ],
                    [
                        'ref_article'   => $currentPhase,
                        'libelle_norme' => $currentPhaseModel->libelle_norme,
                        'reponse'       => $this->mapReponse($row[3] ?? null),
                        'forces'        => $row[4] ?? null,
                        'faiblesses'    => $row[5] ?? null,
                        'objectif'      => $row[6] ?? null,
                        'observations'  => $row[9] ?? null,
                    ]
                );
            }
        }
    }

    private function mapReponse($value): ?string
    {
        if (empty($value)) return null;
        $v = strtoupper(trim($value));
        return in_array($v, ['O', 'N', 'SO']) ? $v : null;
    }
}