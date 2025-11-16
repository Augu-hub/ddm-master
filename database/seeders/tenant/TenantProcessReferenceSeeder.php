<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantProcessReferenceSeeder extends Seeder
{
    /** Connexion utilisée */
    protected string $conn = 'tenant';

    public function run(): void
    {
        // ⚠️ Chaque bloc ne s'exécute que si la table existe dans le tenant
        $this->seedMaturityLevels();
        $this->seedCriticityLevels();
        $this->seedRaciRoles();
        $this->seedIDEAAxes();
        $this->seedKpiCategories();
        $this->seedProcessLinkTypes();
        $this->seedActivityControlTypes();
    }

    /* ──────────────────────────────────────────────────────────────────── */
    private function smartUpsert(string $table, array $uniqueBy, array $rows, array $updateCols = null): void
    {
        if (!Schema::connection($this->conn)->hasTable($table)) {
            // Table absente → on ignore silencieusement
            return;
        }

        $cols = Schema::connection($this->conn)->getColumnListing($table);
        $cols = array_map('strtolower', $cols);

        // Filtrer chaque ligne aux colonnes réellement présentes
        $filtered = [];
        foreach ($rows as $row) {
            $r = [];
            foreach ($row as $k => $v) {
                if (in_array(strtolower($k), $cols, true)) {
                    $r[$k] = $v;
                }
            }
            if ($r) $filtered[] = $r;
        }
        if (!$filtered) return;

        // Colonnes à mettre à jour (par défaut = toutes sauf uniques)
        if ($updateCols === null) {
            $updateCols = array_values(array_diff(array_keys($filtered[0]), $uniqueBy));
        }

        DB::connection($this->conn)->table($table)->upsert($filtered, $uniqueBy, $updateCols);
    }

    /* ──────────────────────────────────────────────────────────────────── */
    private function seedMaturityLevels(): void
    {
        // Tables possibles (mets celle qui existe chez toi ; la méthode ignore les autres)
        foreach ([
            'process_maturity_levels',
            'process_maturities',
            'maturity_levels',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'0','label'=>'Non défini','sort'=>0,'weight'=>0,'color'=>'#9CA3AF'],
                ['code'=>'1','label'=>'Initial','sort'=>1,'weight'=>1,'color'=>'#EF4444'],
                ['code'=>'2','label'=>'Répétable','sort'=>2,'weight'=>2,'color'=>'#F59E0B'],
                ['code'=>'3','label'=>'Défini','sort'=>3,'weight'=>3,'color'=>'#3B82F6'],
                ['code'=>'4','label'=>'Maîtrisé','sort'=>4,'weight'=>4,'color'=>'#10B981'],
                ['code'=>'5','label'=>'Optimisé','sort'=>5,'weight'=>5,'color'=>'#7C3AED'],
            ]);
        }
    }

    private function seedCriticityLevels(): void
    {
        foreach ([
            'process_criticities',
            'criticality_levels',
            'process_risk_levels',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'VL','label'=>'Très faible','weight'=>1,'sort'=>1,'color'=>'#22C55E'],
                ['code'=>'L', 'label'=>'Faible',      'weight'=>2,'sort'=>2,'color'=>'#84CC16'],
                ['code'=>'M', 'label'=>'Moyenne',     'weight'=>3,'sort'=>3,'color'=>'#F59E0B'],
                ['code'=>'H', 'label'=>'Élevée',      'weight'=>4,'sort'=>4,'color'=>'#F97316'],
                ['code'=>'VH','label'=>'Très élevée', 'weight'=>5,'sort'=>5,'color'=>'#EF4444'],
            ]);
        }
    }

    private function seedRaciRoles(): void
    {
        foreach ([
            'activity_raci_roles',
            'raci_roles',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'R','label'=>'Responsible','description'=>'Exécute le travail'],
                ['code'=>'A','label'=>'Accountable','description'=>'Détient la responsabilité finale'],
                ['code'=>'C','label'=>'Consulted','description'=>'Consulté pour avis/contenu'],
                ['code'=>'I','label'=>'Informed','description'=>'Informé du résultat/avancement'],
            ]);
        }
    }

    private function seedIDEAAxes(): void
    {
        foreach ([
            'activity_idea_axes',
            'idea_axes',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'I','label'=>'Impact'],
                ['code'=>'D','label'=>'Difficulté'],
                ['code'=>'E','label'=>'Efficience'],
                ['code'=>'A','label'=>'Acceptation'],
            ]);
        }
    }

    private function seedKpiCategories(): void
    {
        foreach ([
            'process_kpi_categories',
            'kpi_categories',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'Q','label'=>'Qualité','sort'=>1],
                ['code'=>'T','label'=>'Délai','sort'=>2],
                ['code'=>'C','label'=>'Coût','sort'=>3],
                ['code'=>'S','label'=>'Satisfaction','sort'=>4],
                ['code'=>'P','label'=>'Productivité','sort'=>5],
            ]);
        }
    }

    private function seedProcessLinkTypes(): void
    {
        foreach ([
            'process_link_types',
            'process_links_types',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'UP',   'label'=>'Amont'],
                ['code'=>'DOWN', 'label'=>'Aval'],
                ['code'=>'SUP',  'label'=>'Support'],
                ['code'=>'CTRL', 'label'=>'Contrôle'],
            ]);
        }
    }

    private function seedActivityControlTypes(): void
    {
        foreach ([
            'activity_control_types',
            'control_types',
        ] as $table) {
            $this->smartUpsert($table, ['code'], [
                ['code'=>'PREV','label'=>'Préventif'],
                ['code'=>'DETC','label'=>'Détectif'],
                ['code'=>'CORR','label'=>'Correctif'],
            ]);
        }
    }
}
