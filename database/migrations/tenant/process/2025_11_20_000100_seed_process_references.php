<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $db     = DB::connection($this->connection);
        $schema = Schema::connection($this->connection);

        $seed = function (string $table, array $rows, string $key='code') use ($db,$schema) : int {
            if (! $schema->hasTable($table)) return 0;
            $n=0;
            foreach ($rows as $row) {
                $db->table($table)->updateOrInsert([$key => $row[$key]], $row);
                $n++;
            }
            return $n;
        };

        $total = 0;

        // ✅ d’après tes migrations réelles
       
        // Criticité → process_criticality_levels
        $total += $seed('process_criticality_levels', [
            ['code'=>'VL','label'=>'Très faible','weight'=>1,'sort'=>1,'color'=>'#22C55E'],
            ['code'=>'L', 'label'=>'Faible',     'weight'=>2,'sort'=>2,'color'=>'#84CC16'],
            ['code'=>'M', 'label'=>'Moyenne',    'weight'=>3,'sort'=>3,'color'=>'#F59E0B'],
            ['code'=>'H', 'label'=>'Élevée',     'weight'=>4,'sort'=>4,'color'=>'#F97316'],
            ['code'=>'VH','label'=>'Très élevée','weight'=>5,'sort'=>5,'color'=>'#EF4444'],
        ]);

        

        // IDEA axes
        $total += $seed('activity_idea_axes', [
            ['code'=>'I','label'=>'Impact','sort'=>1],
            ['code'=>'D','label'=>'Difficulté','sort'=>2],
            ['code'=>'E','label'=>'Efficience','sort'=>3],
            ['code'=>'A','label'=>'Acceptation','sort'=>4],
        ]);

        // KPI categories
        $total += $seed('process_kpi_categories', [
            ['code'=>'Q','label'=>'Qualité','sort'=>1],
            ['code'=>'T','label'=>'Délai','sort'=>2],
            ['code'=>'C','label'=>'Coût','sort'=>3],
            ['code'=>'S','label'=>'Satisfaction','sort'=>4],
            ['code'=>'P','label'=>'Productivité','sort'=>5],
        ]);

        // Process link types
        $total += $seed('process_link_types', [
            ['code'=>'UP',  'label'=>'Amont','sort'=>1],
            ['code'=>'DOWN','label'=>'Aval','sort'=>2],
            ['code'=>'SUP', 'label'=>'Support','sort'=>3],
            ['code'=>'CTRL','label'=>'Contrôle','sort'=>4],
        ]);

        // Activity control types
        $total += $seed('activity_control_types', [
            ['code'=>'PREV','label'=>'Préventif','sort'=>1],
            ['code'=>'DETC','label'=>'Détectif','sort'=>2],
            ['code'=>'CORR','label'=>'Correctif','sort'=>3],
        ]);

        Log::info("Process seed (tenant) rows touched = {$total}");
    }

    public function down(): void
    {
        $db     = DB::connection($this->connection);
        $schema = Schema::connection($this->connection);

        $tables = [
        
            'process_criticality_levels',
            
            'activity_idea_axes',
            'process_kpi_categories',
            'process_link_types',
            'activity_control_types',
        ];

        $codes = [
            '0','1','2','3','4','5',
            'VL','L','M','H','VH',
            'R','A','C','I',
            'I','D','E','A',
            'Q','T','C','S','P',
            'UP','DOWN','SUP','CTRL',
            'PREV','DETC','CORR',
        ];

        foreach ($tables as $table) {
            if (! $schema->hasTable($table)) continue;
            $db->table($table)->whereIn('code', $codes)->delete();
        }
    }
};
