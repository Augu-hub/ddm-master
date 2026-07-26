<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;

/**
 * Suggère des indicateurs de performance et une trame de cadre logique à
 * partir de ce qui existe DÉJÀ dans la base tenant pour un programme
 * (= processus de réalisation) : ses objectifs et ses activités.
 *
 * Aucune IA requise : on dérive les propositions des libellés d'objectifs
 * (qui contiennent souvent la cible : « Taux … ≥ 100% », « Nombre … < 1% »)
 * et on complète par des indicateurs standard AP par catégorie KPI
 * (Qualité, Délai, Coût, Satisfaction, Productivité) et par les 3E.
 */
class PerformanceIndicatorSuggester
{
    /** Indicateurs standard par axe 3E — proposés en complément. */
    private const STANDARD = [
        'Économie'   => [
            ['intitule' => "Taux d'exécution budgétaire", 'unite' => '%', 'sens' => 'hausse'],
            ['intitule' => "Coût unitaire moyen", 'unite' => 'FCFA', 'sens' => 'baisse'],
            ['intitule' => "Écart entre coût prévu et coût réel", 'unite' => '%', 'sens' => 'baisse'],
        ],
        'Efficience' => [
            ['intitule' => "Ratio ressources engagées / résultats obtenus", 'unite' => 'ratio', 'sens' => 'baisse'],
            ['intitule' => "Délai moyen de traitement", 'unite' => 'jours', 'sens' => 'baisse'],
            ['intitule' => "Taux de productivité", 'unite' => '%', 'sens' => 'hausse'],
        ],
        'Efficacité' => [
            ['intitule' => "Taux d'atteinte des objectifs", 'unite' => '%', 'sens' => 'hausse'],
            ['intitule' => "Taux de couverture / réalisation", 'unite' => '%', 'sens' => 'hausse'],
            ['intitule' => "Taux de satisfaction des bénéficiaires", 'unite' => '%', 'sens' => 'hausse'],
        ],
    ];

    /**
     * Indicateurs suggérés pour un objectif d'activité.
     * $sourceObjectifId : objectif du processus lié (objectifs.id), si présent.
     */
    public function forObjectif(?int $sourceObjectifId, ?string $objectifLibelle = null): array
    {
        $out = [];

        // 1) À partir de l'objectif de la base (le plus pertinent)
        if ($sourceObjectifId) {
            $src = DB::table('objectifs')->where('id', $sourceObjectifId)->first();
            if ($src) {
                if (!empty($src->kpi)) {
                    $out[] = [
                        'intitule' => $src->kpi,
                        'unite'    => $this->guessUnite($src->kpi),
                        'sens'     => $this->guessSens($src->kpi),
                        'source'   => "Objectif base : {$src->name}",
                    ];
                }
                // Le libellé de l'objectif contient souvent la cible mesurable
                $derived = $this->deriveFromLabel($src->name);
                if ($derived) { $derived['source'] = "Objectif base : {$src->name}"; $out[] = $derived; }
            }
        }

        // 2) À partir du libellé saisi de l'objectif d'activité
        if ($objectifLibelle) {
            $derived = $this->deriveFromLabel($objectifLibelle);
            if ($derived) { $derived['source'] = 'Libellé objectif'; $out[] = $derived; }
        }

        // 3) Compléter avec des indicateurs standard 3E (sans doublon d'intitulé)
        foreach (self::STANDARD as $axe => $inds) {
            foreach ($inds as $i) {
                $out[] = $i + ['source' => "Standard · {$axe}"];
            }
        }

        // Dédoublonnage par intitulé
        $seen = [];
        return array_values(array_filter($out, function ($i) use (&$seen) {
            $k = mb_strtolower(trim($i['intitule']));
            if (isset($seen[$k])) return false;
            $seen[$k] = true;
            return true;
        }));
    }

    /**
     * Trame de cadre logique (volet « ressources et résultats ») pré-remplie
     * depuis le programme : objectifs → Résultats, activités → Activités.
     */
    public function cadreLogiqueForProcess(?int $processId): array
    {
        if (!$processId) return [];

        $lines = [];

        $objectifs = DB::table('objectifs')->where('process_id', $processId)->orderBy('id')->get();
        foreach ($objectifs as $o) {
            $lines[] = [
                'composante'   => 'Résultats',
                'libelle'      => $o->name,
                'indicateur'   => $o->kpi ?: $this->extractIndicatorText($o->name),
                'valeur'       => $o->kpi_target ?? '',
                'unite'        => $this->guessUnite($o->kpi ?: $o->name),
                'source'       => 'Objectifs du programme (base)',
                'observations' => $o->type === 'strategique' ? 'Objectif stratégique' : 'Objectif opérationnel',
            ];
        }

        $activites = DB::table('activities')->where('process_id', $processId)->orderBy('code')->get();
        foreach ($activites as $a) {
            $lines[] = [
                'composante'   => 'Activités',
                'libelle'      => $a->name,
                'indicateur'   => "Taux de réalisation de l'activité",
                'valeur'       => '',
                'unite'        => '%',
                'source'       => "Activités du programme (base) · {$a->code}",
                'observations' => $a->description ?? '',
            ];
        }

        return $lines;
    }

    // ── Heuristiques ────────────────────────────────────────────────────────

    private function deriveFromLabel(string $label): ?array
    {
        $l = mb_strtolower($label);
        if (str_contains($l, 'taux') || str_contains($l, '%')) {
            return ['intitule' => $this->extractIndicatorText($label), 'unite' => '%', 'sens' => $this->guessSens($label)];
        }
        if (str_contains($l, 'nombre') || str_contains($l, 'délai') || str_contains($l, 'delai')) {
            return ['intitule' => $this->extractIndicatorText($label), 'unite' => str_contains($l, 'délai') || str_contains($l, 'delai') ? 'jours' : 'nombre', 'sens' => $this->guessSens($label)];
        }
        if (str_contains($l, 'coût') || str_contains($l, 'cout') || str_contains($l, 'budget')) {
            return ['intitule' => $this->extractIndicatorText($label), 'unite' => 'FCFA', 'sens' => 'baisse'];
        }
        return null;
    }

    private function extractIndicatorText(string $label): string
    {
        // Garde la partie « indicateur » avant une cible chiffrée éventuelle
        $parts = preg_split('/(≥|≤|>|<|=|à moins de|à plus de)/u', $label, 2);
        return trim($parts[0]) ?: $label;
    }

    private function guessUnite(?string $text): string
    {
        $t = mb_strtolower($text ?? '');
        if (str_contains($t, '%') || str_contains($t, 'taux')) return '%';
        if (str_contains($t, 'coût') || str_contains($t, 'cout') || str_contains($t, 'budget') || str_contains($t, 'fcfa')) return 'FCFA';
        if (str_contains($t, 'délai') || str_contains($t, 'delai') || str_contains($t, 'jour')) return 'jours';
        if (str_contains($t, 'nombre')) return 'nombre';
        return '';
    }

    private function guessSens(?string $text): string
    {
        $t = mb_strtolower($text ?? '');
        // Réduire / diminuer / rebut / retour / défaut → à la baisse
        if (preg_match('/r[ée]du|diminu|rebut|retour|d[ée]faut|co[ûu]t|d[ée]lai|erreur|plainte/u', $t)) return 'baisse';
        return 'hausse';
    }
}
