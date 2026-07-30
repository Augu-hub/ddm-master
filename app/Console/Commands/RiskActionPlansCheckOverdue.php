<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Relance automatique des plans d'action en retard (chantier #1 — fiabilisation
 * du suivi). Pour chaque tenant : repère les plans dont l'échéance est dépassée
 * et qui ne sont ni terminés ni annulés, puis journalise un log « overdue » et
 * notifie le responsable. Idempotent par jour (aucun doublon si relancé).
 *
 *   php artisan risk:action-plans-overdue            # tous les tenants
 *   php artisan risk:action-plans-overdue --tenant=4 # un seul tenant
 *   php artisan risk:action-plans-overdue --dry      # simulation (aucune écriture)
 *
 * À planifier quotidiennement (voir routes/console.php).
 */
class RiskActionPlansCheckOverdue extends Command
{
    protected $signature = 'risk:action-plans-overdue {--tenant= : Limiter à un tenant (id)} {--dry : Simulation sans écriture}';
    protected $description = "Repère les plans d'action en retard et notifie les responsables";

    public function handle(): int
    {
        $dry   = (bool) $this->option('dry');
        $only  = $this->option('tenant');
        $today = now()->toDateString();

        $tenants = DB::connection('mysql')->table('tenants')
            ->when($only, fn ($q) => $q->where('id', (int) $only))
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('Aucun tenant trouvé.');
            return self::SUCCESS;
        }

        $grandTotal = 0;

        foreach ($tenants as $tenant) {
            $flagged = $this->processTenant($tenant, $today, $dry);
            if ($flagged === null) continue; // tenant sans module risque
            $grandTotal += $flagged;
            $this->line(sprintf('  • %-14s : %d plan(s) en retard %s',
                $tenant->db_name, $flagged, $dry ? '(simulation)' : 'traités'));
        }

        $this->info(($dry ? '[DRY] ' : '') . "Terminé — {$grandTotal} plan(s) en retard au total.");
        return self::SUCCESS;
    }

    /** Traite un tenant. Renvoie le nb de plans relancés, ou null si pas de module risque. */
    private function processTenant(object $tenant, string $today, bool $dry): ?int
    {
        // Configure la connexion tenant (comme le middleware SetTenantDatabase)
        Config::set('database.connections.tenant', array_merge(
            config('database.connections.mysql'),
            [
                'database' => $tenant->db_name,
                'host'     => $tenant->db_host ?: config('database.connections.mysql.host'),
                'username' => $tenant->db_username ?: config('database.connections.mysql.username'),
                'password' => $tenant->db_password ?: config('database.connections.mysql.password'),
            ]
        ));
        DB::purge('tenant');

        try {
            $conn = DB::connection('tenant');
            if (!$conn->getSchemaBuilder()->hasTable('risk_action_plans')) {
                return null;
            }
        } catch (\Throwable $e) {
            $this->warn("  ⚠ {$tenant->db_name} : connexion impossible ({$e->getMessage()})");
            return null;
        }

        $hasLogs   = $conn->getSchemaBuilder()->hasTable('risk_action_logs');
        $hasNotifs = $conn->getSchemaBuilder()->hasTable('risk_action_notifications');

        $overdue = $conn->table('risk_action_plans')
            ->whereNull('deleted_at')
            ->whereNotNull('target_date')
            ->whereDate('target_date', '<', $today)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get(['id', 'tenant_id', 'title', 'assigned_to', 'target_date', 'status']);

        $count = 0;
        foreach ($overdue as $plan) {
            // Idempotence : déjà relancé aujourd'hui ?
            if ($hasLogs) {
                $already = $conn->table('risk_action_logs')
                    ->where('plan_id', $plan->id)
                    ->where('action', 'overdue')
                    ->whereDate('created_at', $today)
                    ->exists();
                if ($already) continue;
            }

            $count++;
            if ($dry) continue;

            $now = now();
            $daysLate = (int) now()->startOfDay()->diffInDays($plan->target_date, true);
            $msg = "Plan d'action en retard : « {$plan->title} » (échéance {$plan->target_date}).";

            if ($hasLogs) {
                $conn->table('risk_action_logs')->insert([
                    'tenant_id'   => $plan->tenant_id,
                    'plan_id'     => $plan->id,
                    'user_id'     => null,
                    'action'      => 'overdue',
                    'description' => $msg . " Retard : {$daysLate} jour(s).",
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            if ($hasNotifs && $plan->assigned_to) {
                $conn->table('risk_action_notifications')->insert([
                    'tenant_id'  => $plan->tenant_id,
                    'plan_id'    => $plan->id,
                    'user_id'    => $plan->assigned_to,
                    'type'       => 'overdue',
                    'message'    => $msg,
                    'is_read'    => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return $count;
    }
}
