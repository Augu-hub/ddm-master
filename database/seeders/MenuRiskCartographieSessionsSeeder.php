<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MenuRiskCartographieSessionsSeeder
 *
 * Branche dans la base centrale (ddmparam1).menus les vues des chantiers #2 et #3 :
 *
 *   Sous "Évaluation des risques" (id 265) :
 *     sort 4  → Cartographie de synthèse   /m/risk.core/evaluation/cartographie
 *
 *   Sous "Risques" (id 226) :
 *     sort 36 → [groupe] Sessions & évolution
 *                 sort 1 → Sessions d'évaluation   /m/risk.core/eval-sessions
 *                 sort 2 → Comparaison d'évolution /m/risk.core/eval-sessions/compare
 *
 * Idempotent (clé unique). Usage :
 *   php artisan db:seed --class=MenuRiskCartographieSessionsSeeder
 */
class MenuRiskCartographieSessionsSeeder extends Seeder
{
    private const MODULE_ID   = 3;
    private const SERVICE_ID  = 2;
    private const PARENT_MAIN = 226; // "Risques"
    private const GROUP_EVAL  = 265; // "Évaluation des risques"

    public function run(): void
    {
        $this->command?->info('🗂️  MenuRiskCartographieSessionsSeeder — menus centraux');

        DB::connection('mysql')->transaction(function () {
            // #2 — Cartographie sous le groupe Évaluation
            $this->upsert([
                'key'        => 'risk-evaluation-cartographie',
                'label'      => 'Cartographie de synthèse',
                'type'       => 'item',
                'icon'       => 'ti ti-map-2',
                'url'        => '/m/risk.core/evaluation/cartographie',
                'route_name' => 'risk.core.evaluation.cartographie',
                'parent_id'  => self::GROUP_EVAL,
                'sort'       => 4,
                'badge_json' => json_encode(['variant' => 'success', 'text' => 'Nouveau']),
            ]);

            // #3 — Groupe "Sessions & évolution" sous Risques
            $groupId = $this->upsert([
                'key'        => 'risk-eval-sessions-group',
                'label'      => 'Sessions & évolution',
                'type'       => 'item',
                'icon'       => 'ti ti-versions',
                'url'        => null,
                'route_name' => null,
                'parent_id'  => self::PARENT_MAIN,
                'sort'       => 36,
                'badge_json' => json_encode(['variant' => 'success', 'text' => 'Nouveau']),
            ]);

            $this->upsert([
                'key'        => 'risk-eval-sessions-index',
                'label'      => "Sessions d'évaluation",
                'type'       => 'item',
                'icon'       => 'ti ti-list-details',
                'url'        => '/m/risk.core/eval-sessions',
                'route_name' => 'risk.core.eval-sessions.index',
                'parent_id'  => $groupId,
                'sort'       => 1,
            ]);

            $this->upsert([
                'key'        => 'risk-eval-sessions-compare',
                'label'      => "Comparaison d'évolution",
                'type'       => 'item',
                'icon'       => 'ti ti-git-compare',
                'url'        => '/m/risk.core/eval-sessions/compare',
                'route_name' => 'risk.core.eval-sessions.compare',
                'parent_id'  => $groupId,
                'sort'       => 2,
            ]);
        });

        $this->command?->info('✅ Menus cartographie + sessions branchés.');
    }

    /** Insère (ou met à jour) une entrée de menu par sa clé. Renvoie l'id. */
    private function upsert(array $item): int
    {
        $now = now();
        $defaults = [
            'target'       => null,
            'service_id'   => self::SERVICE_ID,
            'module_id'    => self::MODULE_ID,
            'visible'      => 1,
            'badge_json'   => null,
            'tooltip_json' => null,
            'meta_json'    => null,
            'updated_at'   => $now,
        ];
        $row = array_merge($defaults, $item);

        $existing = DB::connection('mysql')->table('menus')->where('key', $row['key'])->first(['id']);
        if ($existing) {
            DB::connection('mysql')->table('menus')->where('id', $existing->id)->update($row);
            $this->command?->line("  ↻ UPDATE key={$row['key']} (id={$existing->id})");
            return $existing->id;
        }

        $id = DB::connection('mysql')->table('menus')->insertGetId(array_merge($row, ['created_at' => $now]));
        $this->command?->line("  ✔ INSERT key={$row['key']} → " . ($row['url'] ?? '(groupe)') . " (id={$id})");
        return $id;
    }
}
