<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenant\TenantReferenceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * php artisan tenants:sync-reference              → synchronise tous les tenants
 * php artisan tenants:sync-reference --tenant=fruitiva
 * php artisan tenants:sync-reference --diagnose    → n'écrit rien, affiche juste
 *                                                     les incohérences détectées
 *
 * ⚠️ Une seule connexion Laravel existe dans ce projet : `mysql` (= ddmparam).
 *    L'accès aux bases tenant se fait par requêtes cross-DB sur cette même
 *    connexion (`DB::raw("`{db_name}`.`table`")`), jamais en reconfigurant
 *    une connexion dynamique — cf. TenantReferenceSyncService.
 */
class SyncTenantsReferenceData extends Command
{
    protected $signature = 'tenants:sync-reference
                            {--tenant= : Code du tenant à traiter (sinon : tous)}
                            {--diagnose : Mode lecture seule, affiche les écarts sans rien modifier}';

    protected $description = "Synchronise le référentiel ddmparam (audit_types, phases) vers les bases tenant";

    public function handle(TenantReferenceSyncService $sync): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('code', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('Aucun tenant trouvé.');
            return self::FAILURE;
        }

        if ($this->option('diagnose')) {
            return $this->diagnose($tenants);
        }

        foreach ($tenants as $tenant) {
            $this->line("→ Synchro {$tenant->code}...");
            $result = $sync->syncTenant($tenant);

            if (!empty($result['errors'])) {
                $this->error("  ✗ Erreur : " . implode(' | ', $result['errors']));
                continue;
            }

            $this->info(sprintf(
                "  ✓ mission_types: +%d créé(s) / %d mis à jour · phases: +%d créée(s) / %d mise(s) à jour",
                $result['mission_types_created'],
                $result['mission_types_updated'],
                $result['phases_created'],
                $result['phases_updated'],
            ));
        }

        return self::SUCCESS;
    }

    /** Rapport en lecture seule des divergences entre ddmparam et chaque tenant */
    private function diagnose($tenants): int
    {
        $central    = (string) config('database.connections.mysql.database', 'ddmparam');
        $auditTypes = DB::connection('mysql')->table(DB::raw("`{$central}`.`audit_types`"))->get()->keyBy('code');

        foreach ($tenants as $tenant) {
            $this->comment("=== {$tenant->code} ===");

            if (empty($tenant->db_name)) {
                $this->error("  ✗ db_name manquant pour ce tenant.");
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9_]+$/', $tenant->db_name)) {
                $this->error("  ✗ db_name '{$tenant->db_name}' invalide (caractères non autorisés).");
                continue;
            }

            $db = $tenant->db_name;

            $schemaExists = DB::connection('mysql')->selectOne(
                'SELECT COUNT(*) AS n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$db]
            );
            if (!$schemaExists || (int) $schemaExists->n === 0) {
                $this->error("  ✗ Base '{$db}' introuvable sur ce serveur MySQL — tenant jamais provisionné ?");
                continue;
            }

            $hasIssue = false;

            // ── 1) mission_types : rattachements + champs dénormalisés ──
            $rows = DB::connection('mysql')
                ->table(DB::raw("`{$db}`.`mission_types`"))
                ->get();

            foreach ($rows as $row) {
                if (!$row->audit_type_code || !isset($auditTypes[$row->audit_type_code])) {
                    $this->warn("  [{$row->code}] audit_type_code='{$row->audit_type_code}' introuvable dans {$central}.audit_types");
                    $hasIssue = true;
                    continue;
                }
                $ref = $auditTypes[$row->audit_type_code];
                if ($row->audit_type_label !== $ref->label || $row->audit_color !== $ref->color || $row->audit_icon !== $ref->icon) {
                    $this->warn(sprintf(
                        "  [%s] désynchronisé : label='%s' (attendu '%s') · couleur='%s' (attendu '%s')",
                        $row->code, $row->audit_type_label, $ref->label, $row->audit_color, $ref->color
                    ));
                    $hasIssue = true;
                }
            }

            // ── 2) mission_phases : schéma + orphelines vs ddmparam ──
            $oldSchema = DB::connection('mysql')->selectOne(
                "SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'mission_phases' AND COLUMN_NAME = 'code'", [$db]
            );

            if ($oldSchema && (int) $oldSchema->n > 0) {
                $this->warn("  ✗ mission_phases : ANCIEN schéma détecté (colonne 'code'). "
                    . "Appliquer database/sql/migrate_phases_to_central_ids.sql sur ce tenant.");
                $hasIssue = true;
            } else {
                // Nouveau schéma : mission_phases.id doit exister dans audit_type_forms actifs
                $orphans = DB::connection('mysql')->select(
                    "SELECT mp.id FROM `{$db}`.`mission_phases` mp
                     LEFT JOIN `{$central}`.`audit_type_forms` atf
                            ON atf.id = mp.id AND atf.is_active = 1
                     WHERE atf.id IS NULL"
                );
                if ($orphans !== []) {
                    $ids = implode(', ', array_map(fn ($o) => $o->id, array_slice($orphans, 0, 15)));
                    $suffix = count($orphans) > 15 ? '…' : '';
                    $this->warn('  ✗ ' . count($orphans) . " phase(s) orpheline(s) : id sans formulaire actif dans {$central}.audit_type_forms ({$ids}{$suffix})");
                    $hasIssue = true;
                }
            }

            if (!$hasIssue) {
                $this->info('  ✓ Rien à signaler.');
            }
        }

        return self::SUCCESS;
    }
}
