<?php

namespace Database\Seeders;

use App\Models\Tenant\RiskCore\RiskMatrixConfig;
use App\Models\Tenant\RiskCore\RiskImpactLevel;
use App\Models\Tenant\RiskCore\RiskFrequencyLevel;
use App\Models\Tenant\RiskCore\RiskCriticalityZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder Fruitiva — tenant_id = 1
 *
 * Peuple une matrice 5×5 complète adaptée au secteur agroalimentaire :
 *   - 1 RiskMatrixConfig  (active)
 *   - 5 RiskImpactLevel
 *   - 5 RiskFrequencyLevel
 *   - 5 RiskCriticalityZone
 *
 * Usage :
 *   php artisan db:seed --class=FruitivaRiskMatrixSeeder
 *   php artisan db:seed --class=FruitivaRiskMatrixSeeder --force  (prod)
 */
class FruitivaRiskMatrixSeeder extends Seeder
{
    private const TENANT_ID = 1;

    public function run(): void
    {
        $this->command->info('🌱 Seeding Fruitiva — Matrice 5×5...');

        DB::connection('tenant')->transaction(function () {
            $this->cleanExisting();
            $config = $this->seedMatrixConfig();
            $this->seedImpactLevels($config);
            $this->seedFrequencyLevels($config);
            $this->seedCriticalityZones($config);
            $this->activateConfig($config);
        });

        $this->command->info('✅ Matrice Fruitiva 5×5 créée et activée.');
        $this->printSummary();
    }

    // ─── Nettoyage ────────────────────────────────────────────────────────────

    private function cleanExisting(): void
    {
        // Suppression propre dans l'ordre des FK
        RiskCriticalityZone::where('tenant_id', self::TENANT_ID)->forceDelete();
        RiskFrequencyLevel::where('tenant_id', self::TENANT_ID)->forceDelete();
        RiskImpactLevel::where('tenant_id', self::TENANT_ID)->forceDelete();
        RiskMatrixConfig::where('tenant_id', self::TENANT_ID)->forceDelete();

        $this->command->line('  → Données existantes supprimées');
    }

    // ─── Config matrice ───────────────────────────────────────────────────────

    private function seedMatrixConfig(): RiskMatrixConfig
    {
        $config = RiskMatrixConfig::create([
            'tenant_id'   => self::TENANT_ID,
            'name'        => 'Matrice Fruitiva Standard',
            'matrix_size' => 5,
            'description' => 'Matrice 5×5 adaptée au secteur agroalimentaire (production, '
                . 'transformation et distribution de fruits et légumes). '
                . 'Score max : 25. Criticité = Impact × Fréquence.',
            'is_active'   => false, // activée en fin de seeder
        ]);

        $this->command->line("  → Config « {$config->name} » créée (id: {$config->id})");

        return $config;
    }

    // ─── Niveaux d'impact ─────────────────────────────────────────────────────

    private function seedImpactLevels(RiskMatrixConfig $config): void
    {
        $levels = [
            [
                'score'       => 1,
                'label'       => 'Négligeable',
                'description' => 'Impact sans conséquence notable sur l\'activité. '
                    . 'Perturbation mineure et localisée, résolue en interne sans '
                    . 'impact sur la production ni sur la livraison clients. '
                    . 'Exemple : légère désorganisation d\'un poste de tri, '
                    . 'perte inférieure à 50 kg de marchandise.',
                'color_code'  => '#22c55e',
                'sort_order'  => 0,
            ],
            [
                'score'       => 2,
                'label'       => 'Mineur',
                'description' => 'Impact limité, absorbable sans recours externe. '
                    . 'Perturbation d\'une ligne de production ≤ 4h, '
                    . 'perte de 50 à 500 kg de marchandise, ou retard de livraison '
                    . 'de moins de 24h sur 1 client. '
                    . 'Pas d\'impact sur la sécurité alimentaire ni sur l\'image.',
                'color_code'  => '#84cc16',
                'sort_order'  => 1,
            ],
            [
                'score'       => 3,
                'label'       => 'Modéré',
                'description' => 'Impact significatif nécessitant une réponse coordonnée. '
                    . 'Arrêt d\'une ligne ≤ 1 jour, perte de 500 kg à 2 tonnes, '
                    . 'ou retard de livraison affectant plusieurs clients. '
                    . 'Risque de plainte client ou de non-conformité réglementaire mineure.',
                'color_code'  => '#eab308',
                'sort_order'  => 2,
            ],
            [
                'score'       => 4,
                'label'       => 'Grave',
                'description' => 'Impact sérieux sur la continuité d\'activité. '
                    . 'Arrêt de production de 1 à 3 jours, perte de 2 à 10 tonnes, '
                    . 'rappel de lot ou alerte sanitaire, perte d\'un client majeur '
                    . 'ou pénalité contractuelle. '
                    . 'Risque d\'atteinte à la réputation de la marque.',
                'color_code'  => '#f97316',
                'sort_order'  => 3,
            ],
            [
                'score'       => 5,
                'label'       => 'Catastrophique',
                'description' => 'Impact critique menaçant la pérennité de l\'entreprise. '
                    . 'Arrêt total de production > 3 jours, perte > 10 tonnes, '
                    . 'accident corporel grave, crise sanitaire avec rappel national, '
                    . 'perte de certification (BRC, IFS, GlobalGAP), '
                    . 'atteinte majeure et durable à la réputation.',
                'color_code'  => '#ef4444',
                'sort_order'  => 4,
            ],
        ];

        foreach ($levels as $data) {
            RiskImpactLevel::create([
                ...$data,
                'tenant_id'        => self::TENANT_ID,
                'matrix_config_id' => $config->id,
            ]);
        }

        $this->command->line('  → 5 niveaux d\'impact créés');
    }

    // ─── Niveaux de fréquence ─────────────────────────────────────────────────

    private function seedFrequencyLevels(RiskMatrixConfig $config): void
    {
        $levels = [
            [
                'score'       => 1,
                'label'       => 'Rare',
                'recurrence'  => 'Moins de 1 fois / 10 ans',
                'description' => 'Événement exceptionnel, n\'ayant jamais été observé dans '
                    . 'le secteur agroalimentaire local ou dont la survenue est '
                    . 'théoriquement possible mais jugée très improbable. '
                    . 'Exemple : contamination radiologique, catastrophe naturelle majeure.',
                'color_code'  => '#0ea5e9',
                'sort_order'  => 0,
            ],
            [
                'score'       => 2,
                'label'       => 'Peu probable',
                'recurrence'  => '1 fois / 5 à 10 ans',
                'description' => 'Événement déjà survenu dans le secteur ou des situations '
                    . 'comparables, mais peu fréquent à l\'échelle de Fruitiva. '
                    . 'Exemple : panne majeure du groupe froid, défaillance '
                    . 'd\'un fournisseur stratégique.',
                'color_code'  => '#6366f1',
                'sort_order'  => 1,
            ],
            [
                'score'       => 3,
                'label'       => 'Possible',
                'recurrence'  => '1 fois / 1 à 5 ans',
                'description' => 'Événement susceptible de survenir dans la durée du plan '
                    . 'de management des risques. A déjà été observé chez Fruitiva '
                    . 'ou régulièrement dans le secteur. '
                    . 'Exemple : non-conformité en audit client, rupture '
                    . 'd\'approvisionnement saisonnière.',
                'color_code'  => '#8b5cf6',
                'sort_order'  => 2,
            ],
            [
                'score'       => 4,
                'label'       => 'Probable',
                'recurrence'  => 'Plusieurs fois / an',
                'description' => 'Événement se produisant régulièrement dans les opérations '
                    . 'de Fruitiva. Fait partie du quotidien opérationnel, '
                    . 'des plans d\'action sont généralement en place. '
                    . 'Exemple : variations de qualité matière première, '
                    . 'absentéisme saisonnier, retards transporteur.',
                'color_code'  => '#a855f7',
                'sort_order'  => 3,
            ],
            [
                'score'       => 5,
                'label'       => 'Certain',
                'recurrence'  => 'Mensuel ou plus fréquent',
                'description' => 'Événement quasi-systématique, intégré dans le fonctionnement '
                    . 'normal de l\'entreprise. Se produit au moins une fois par mois. '
                    . 'Exemple : micro-arrêts machine, écarts de pesée, '
                    . 'incivilités ou conflits mineurs, dépassements de DLC sur '
                    . 'de petites quantités.',
                'color_code'  => '#ec4899',
                'sort_order'  => 4,
            ],
        ];

        foreach ($levels as $data) {
            RiskFrequencyLevel::create([
                ...$data,
                'tenant_id'        => self::TENANT_ID,
                'matrix_config_id' => $config->id,
            ]);
        }

        $this->command->line('  → 5 niveaux de fréquence créés');
    }

    // ─── Zones de criticité ───────────────────────────────────────────────────

    private function seedCriticalityZones(RiskMatrixConfig $config): void
    {
        $zones = [
            [
                'label'      => 'Négligeable',
                'min_score'  => 1,
                'max_score'  => 4,
                'color_code' => '#22c55e',
                'sort_order' => 1,
                // Cellules : (1×1=1), (1×2=2), (1×3=3), (1×4=4), (2×1=2), (2×2=4)
                // → 6 cellules sur 25 (24%)
            ],
            [
                'label'      => 'Faible',
                'min_score'  => 5,
                'max_score'  => 9,
                'color_code' => '#84cc16',
                'sort_order' => 2,
                // Cellules : (1×5=5), (2×3=6), (2×4=8), (3×1=3→non), (3×2=6), (3×3=9)…
                // → 6 cellules sur 25 (24%)
            ],
            [
                'label'      => 'Modéré',
                'min_score'  => 10,
                'max_score'  => 14,
                'color_code' => '#eab308',
                'sort_order' => 3,
                // → 5 cellules sur 25 (20%)
            ],
            [
                'label'      => 'Élevé',
                'min_score'  => 15,
                'max_score'  => 19,
                'color_code' => '#f97316',
                'sort_order' => 4,
                // → 4 cellules sur 25 (16%)
            ],
            [
                'label'      => 'Critique',
                'min_score'  => 20,
                'max_score'  => 25,
                'color_code' => '#ef4444',
                'sort_order' => 5,
                // Cellules : (4×5=20), (5×4=20), (5×5=25)…
                // → 4 cellules sur 25 (16%)
            ],
        ];

        foreach ($zones as $data) {
            RiskCriticalityZone::create([
                'label'            => $data['label'],
                'min_score'        => $data['min_score'],
                'max_score'        => $data['max_score'],
                'color_code'       => $data['color_code'],
                'sort_order'       => $data['sort_order'],
                'tenant_id'        => self::TENANT_ID,
                'matrix_config_id' => $config->id,
            ]);
        }

        $this->command->line('  → 5 zones de criticité créées');
    }

    // ─── Activation ───────────────────────────────────────────────────────────

    private function activateConfig(RiskMatrixConfig $config): void
    {
        // Désactive toutes les autres configs du tenant (au cas où)
        RiskMatrixConfig::where('tenant_id', self::TENANT_ID)
            ->where('id', '!=', $config->id)
            ->update(['is_active' => false]);

        $config->update(['is_active' => true]);

        $this->command->line("  → Configuration « {$config->name} » activée");
    }

    // ─── Résumé console ───────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $config = RiskMatrixConfig::with([
            'impactLevels', 'frequencyLevels', 'criticalityZones',
        ])->where('tenant_id', self::TENANT_ID)->active()->first();

        if (! $config) return;

        $this->command->newLine();
        $this->command->table(
            ['', 'Élément', 'Détail'],
            [
                ['✅', 'Configuration', "{$config->name} ({$config->matrix_label})"],
                ['✅', 'Impacts',       $config->impactLevels->pluck('label')->implode(', ')],
                ['✅', 'Fréquences',    $config->frequencyLevels->pluck('label')->implode(', ')],
                ['✅', 'Zones',         $config->criticalityZones->map(
                    fn ($z) => "{$z->label} [{$z->min_score}–{$z->max_score}]"
                )->implode(', ')],
                ['✅', 'Score max',     $config->max_score],
                ['✅', 'Statut',        'Active'],
            ]
        );

        // Aperçu de la grille dans la console
        $this->command->newLine();
        $this->command->line('  Aperçu de la matrice (Impact × Fréquence) :');
        $this->command->newLine();

        $impacts     = $config->impactLevels->sortByDesc('score');
        $frequencies = $config->frequencyLevels->sortBy('score');
        $zones       = $config->criticalityZones;

        // Header fréquences
        $header = str_pad('Impact\Fréq', 16);
        foreach ($frequencies as $f) {
            $header .= str_pad($f->label, 14);
        }
        $this->command->line("  {$header}");
        $this->command->line('  ' . str_repeat('─', 16 + 14 * 5));

        foreach ($impacts as $impact) {
            $row = str_pad($impact->label, 16);
            foreach ($frequencies as $freq) {
                $score    = $impact->score * $freq->score;
                $zone     = $zones->first(fn ($z) => $score >= $z->min_score && $score <= $z->max_score);
                $zoneLabel = $zone ? mb_substr($zone->label, 0, 10) : '?';
                $row      .= str_pad("{$score} ({$zoneLabel})", 14);
            }
            $this->command->line("  {$row}");
        }

        $this->command->newLine();
    }
}
