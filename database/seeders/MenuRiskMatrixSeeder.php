<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MenuRiskMatrixSeeder
 *
 * Met à jour les entrées existantes qui pointaient vers des ancres #hash
 * et insère les nouveaux items manquants dans ddmparam.menus.
 *
 * Hiérarchie cible sous "Paramètres & appétence" (id=73, parent_id=68) :
 *
 *   sort 0  → [NEW]    Configuration matrice  /m/risk.core/matrix-config
 *   sort 1  → [EXIST]  Types de risques       /m/risk.core/settings         (id=153, inchangé)
 *   sort 2  → [UPDATE] Niveaux de fréquence   /m/risk.core/frequency        (id=154)
 *   sort 3  → [UPDATE] Niveaux d'impact       /m/risk.core/impact           (id=155)
 *   sort 4  → [UPDATE] Matrice de criticité   /m/risk.core/matrix           (id=156)
 *
 * Accès direct depuis le menu principal (parent_id=68) :
 *   sort 25 → [NEW]    Matrice des risques    /m/risk.core/matrix  (heatmap visible en nav)
 *
 * Usage :
 *   php artisan db:seed --class=MenuRiskMatrixSeeder
 */
class MenuRiskMatrixSeeder extends Seeder
{
    // ── Constantes projet ──────────────────────────────────────────────────────
    private const MODULE_ID    = 3;
    private const SERVICE_ID   = 2;
    private const PARENT_MAIN  = 68;   // "Risques" — groupe racine
    private const PARENT_PARAM = 73;   // "Paramètres & appétence" — sous-groupe

    public function run(): void
    {
        $this->command->info('🗂️  MenuRiskMatrixSeeder — ddmparam.menus');

        DB::connection('param')->transaction(function () {
            $this->updateExistingItems();
            $this->insertNewItems();
        });

        $this->command->info('✅ Menus risk.core mis à jour.');
        $this->printSummary();
    }

    // ─── Mises à jour des entrées existantes ──────────────────────────────────

    private function updateExistingItems(): void
    {
        $updates = [
            // id=154 — "Niveaux de fréquence" → anciennement #frequencies
            154 => [
                'label'      => 'Niveaux de fréquence',
                'icon'       => 'ti ti-wave-sine',
                'url'        => '/m/risk.core/frequency',
                'route_name' => 'risk.core.frequency.index',
                'sort'       => 2,
                'badge_json' => json_encode(['variant' => 'info', 'text' => 'Nouveau']),
            ],

            // id=155 — "Niveaux d'impact" → anciennement #impacts
            155 => [
                'label'      => "Niveaux d'impact",
                'icon'       => 'ti ti-flame',
                'url'        => '/m/risk.core/impact',
                'route_name' => 'risk.core.impact.index',
                'sort'       => 3,
                'badge_json' => json_encode(['variant' => 'info', 'text' => 'Nouveau']),
            ],

            // id=156 — "Matrice de criticité" → anciennement #matrix
            156 => [
                'label'      => 'Matrice de criticité',
                'icon'       => 'ti ti-grid-dots',
                'url'        => '/m/risk.core/matrix',
                'route_name' => 'risk.core.matrix.index',
                'sort'       => 4,
                'badge_json' => json_encode(['variant' => 'info', 'text' => 'Nouveau']),
            ],
        ];

        foreach ($updates as $id => $data) {
            $affected = DB::connection('param')
                ->table('menus')
                ->where('id', $id)
                ->update(array_merge($data, ['updated_at' => now()]));

            $status = $affected ? '✔' : '⚠ (introuvable)';
            $this->command->line("  {$status} UPDATE id={$id} → {$data['url']}");
        }
    }

    // ─── Insertions des nouveaux items ────────────────────────────────────────

    private function insertNewItems(): void
    {
        $inserts = [

            // ── Sous "Paramètres & appétence" (parent_id=73) ──────────────────

            [
                'key'         => 'risk-settings-matrix-config',
                'label'       => 'Configuration matrice',
                'type'        => 'item',
                'icon'        => 'ti ti-adjustments-alt',
                'url'         => '/m/risk.core/matrix-config',
                'route_name'  => 'risk.core.matrix-config.index',
                'target'      => null,
                'parent_id'   => self::PARENT_PARAM,
                'sort'        => 0,
                'service_id'  => self::SERVICE_ID,
                'module_id'   => self::MODULE_ID,
                'visible'     => 1,
                'badge_json'  => json_encode(['variant' => 'success', 'text' => 'Nouveau']),
                'tooltip_json'=> null,
                'meta_json'   => null,
            ],

            // ── Accès direct depuis le menu principal (parent_id=68) ──────────

            [
                'key'         => 'risk-matrix-heatmap',
                'label'       => 'Matrice des risques',
                'type'        => 'item',
                'icon'        => 'ti ti-layout-grid',
                'url'         => '/m/risk.core/matrix',
                'route_name'  => 'risk.core.matrix.index',
                'target'      => null,
                'parent_id'   => self::PARENT_MAIN,
                'sort'        => 25,
                'service_id'  => self::SERVICE_ID,
                'module_id'   => self::MODULE_ID,
                'visible'     => 1,
                'badge_json'  => json_encode(['variant' => 'success', 'text' => 'Nouveau']),
                'tooltip_json'=> null,
                'meta_json'   => null,
            ],
        ];

        $now = now();

        foreach ($inserts as $item) {
            // Idempotence : ne pas insérer si la clé existe déjà
            $exists = DB::connection('param')
                ->table('menus')
                ->where('key', $item['key'])
                ->exists();

            if ($exists) {
                $this->command->line("  ⏭  SKIP  key={$item['key']} (déjà présent)");
                continue;
            }

            DB::connection('param')->table('menus')->insert(array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            $this->command->line("  ✔  INSERT key={$item['key']} → {$item['url']}");
        }
    }

    // ─── Résumé console ───────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $items = DB::connection('param')
            ->table('menus')
            ->where('module_id', self::MODULE_ID)
            ->whereIn('parent_id', [self::PARENT_MAIN, self::PARENT_PARAM])
            ->orderBy('parent_id')
            ->orderBy('sort')
            ->get(['id', 'key', 'label', 'parent_id', 'sort', 'url', 'route_name']);

        $this->command->newLine();
        $this->command->table(
            ['id', 'parent', 'sort', 'label', 'url'],
            $items->map(fn ($r) => [
                $r->id,
                $r->parent_id === self::PARENT_MAIN  ? 'Risques (68)'      : 'Paramètres (73)',
                $r->sort,
                $r->label,
                $r->url ?? '—',
            ])->toArray()
        );
    }
}
