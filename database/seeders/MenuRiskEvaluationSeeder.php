<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MenuRiskEvaluationSeeder
 *
 * Insère dans ddmparam.menus :
 *
 *   Sous "Risques" (parent_id=68) :
 *     sort 15 → [NEW] Évaluation des risques  (groupe)
 *                 sort 1 → Évaluation inhérente   /m/risk.core/evaluation/inherente
 *                 sort 2 → Évaluation résiduelle  /m/risk.core/evaluation/residuelle
 *                 sort 3 → Évaluation cible       /m/risk.core/evaluation/cible
 *
 * Usage :
 *   php artisan db:seed --class=MenuRiskEvaluationSeeder
 */
class MenuRiskEvaluationSeeder extends Seeder
{
    // ── Constantes projet ──────────────────────────────────────────────────────
    private const MODULE_ID   = 3;
    private const SERVICE_ID  = 2;
    private const PARENT_MAIN = 226;  // "Risques" — groupe racine du module risk.core (key=risk-module)

    // ── Clés d'idempotence ────────────────────────────────────────────────────
    private const KEY_GROUP      = 'risk-evaluation-group';
    private const KEY_INHERENTE  = 'risk-evaluation-inherente';
    private const KEY_RESIDUELLE = 'risk-evaluation-residuelle';
    private const KEY_CIBLE      = 'risk-evaluation-cible';

    public function run(): void
    {
        $this->command->info('🗂️  MenuRiskEvaluationSeeder — ddmparam.menus');

        DB::connection('mysql')->transaction(function () {
            $groupId = $this->insertGroup();
            if ($groupId) {
                $this->insertChildren($groupId);
            }
        });

        $this->command->info('✅ Menus évaluation risk.core insérés.');
        $this->printSummary();
    }

    // ─── Groupe parent "Évaluation des risques" ───────────────────────────────

    private function insertGroup(): int
    {
        // Idempotence
        $existing = DB::connection('mysql')
            ->table('menus')
            ->where('key', self::KEY_GROUP)
            ->first(['id']);

        if ($existing) {
            $this->command->line('  ⏭  SKIP  key=' . self::KEY_GROUP . " (déjà présent, id={$existing->id})");
            return $existing->id;
        }

        $now = now();
        $id  = DB::connection('mysql')->table('menus')->insertGetId([
            'key'          => self::KEY_GROUP,
            'label'        => 'Évaluation des risques',
            'type'         => 'item',
            'icon'         => 'ti ti-chart-dots',
            'url'          => null,
            'route_name'   => null,
            'target'       => null,
            'parent_id'    => self::PARENT_MAIN,
            'sort'         => 22,
            'service_id'   => self::SERVICE_ID,
            'module_id'    => self::MODULE_ID,
            'visible'      => 1,
            'badge_json'   => json_encode(['variant' => 'success', 'text' => 'Nouveau']),
            'tooltip_json' => null,
            'meta_json'    => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $this->command->line("  ✔  INSERT key=" . self::KEY_GROUP . " → groupe (id={$id})");
        return $id;
    }

    // ─── Items enfants ────────────────────────────────────────────────────────

    private function insertChildren(int $groupId): void
    {
        $now = now();

        $items = [
            [
                'key'          => self::KEY_INHERENTE,
                'label'        => 'Évaluation inhérente',
                'type'         => 'item',
                'icon'         => 'ti ti-alert-circle',
                'url'          => '/m/risk.core/evaluation/inherente',
                'route_name'   => 'risk.core.evaluation.inherente',
                'target'       => null,
                'parent_id'    => $groupId,
                'sort'         => 1,
                'service_id'   => self::SERVICE_ID,
                'module_id'    => self::MODULE_ID,
                'visible'      => 1,
                'badge_json'   => null,
                'tooltip_json' => null,
                'meta_json'    => null,
            ],
            [
                'key'          => self::KEY_RESIDUELLE,
                'label'        => 'Évaluation résiduelle',
                'type'         => 'item',
                'icon'         => 'ti ti-shield-check',
                'url'          => '/m/risk.core/evaluation/residuelle',
                'route_name'   => 'risk.core.evaluation.residuelle',
                'target'       => null,
                'parent_id'    => $groupId,
                'sort'         => 2,
                'service_id'   => self::SERVICE_ID,
                'module_id'    => self::MODULE_ID,
                'visible'      => 1,
                'badge_json'   => null,
                'tooltip_json' => null,
                'meta_json'    => null,
            ],
            [
                'key'          => self::KEY_CIBLE,
                'label'        => 'Évaluation cible',
                'type'         => 'item',
                'icon'         => 'ti ti-target',
                'url'          => '/m/risk.core/evaluation/cible',
                'route_name'   => 'risk.core.evaluation.cible',
                'target'       => null,
                'parent_id'    => $groupId,
                'sort'         => 3,
                'service_id'   => self::SERVICE_ID,
                'module_id'    => self::MODULE_ID,
                'visible'      => 1,
                'badge_json'   => null,
                'tooltip_json' => null,
                'meta_json'    => null,
            ],
        ];

        foreach ($items as $item) {
            $exists = DB::connection('mysql')
                ->table('menus')
                ->where('key', $item['key'])
                ->exists();

            if ($exists) {
                $this->command->line("  ⏭  SKIP  key={$item['key']} (déjà présent)");
                continue;
            }

            DB::connection('mysql')->table('menus')->insert(array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            $this->command->line("  ✔  INSERT key={$item['key']} → {$item['url']}");
        }
    }

    // ─── Résumé console ───────────────────────────────────────────────────────

    private function printSummary(): void
    {
        // Récupère le groupe inséré
        $group = DB::connection('mysql')
            ->table('menus')
            ->where('key', self::KEY_GROUP)
            ->first(['id', 'label', 'parent_id', 'sort']);

        if (!$group) {
            $this->command->warn('  ⚠ Groupe introuvable après insertion.');
            return;
        }

        // Récupère les enfants du groupe
        $children = DB::connection('mysql')
            ->table('menus')
            ->where('parent_id', $group->id)
            ->where('module_id', self::MODULE_ID)
            ->orderBy('sort')
            ->get(['id', 'key', 'label', 'sort', 'url', 'route_name']);

        $rows = collect([
            (object)[
                'id'         => $group->id,
                'label'      => '📁 ' . $group->label,
                'parent'     => "Risques (226)",
                'sort'       => $group->sort,
                'url'        => '— groupe —',
                'route_name' => '',
            ],
        ])->concat($children->map(fn ($r) => (object)[
            'id'         => $r->id,
            'label'      => '  └─ ' . $r->label,
            'parent'     => "Évaluation ({$group->id})",
            'sort'       => $r->sort,
            'url'        => $r->url ?? '—',
            'route_name' => $r->route_name ?? '—',
        ]));

        $this->command->newLine();
        $this->command->table(
            ['id', 'parent', 'sort', 'label', 'url'],
            $rows->map(fn ($r) => [
                $r->id,
                $r->parent,
                $r->sort,
                $r->label,
                $r->url,
            ])->toArray()
        );
    }
}
