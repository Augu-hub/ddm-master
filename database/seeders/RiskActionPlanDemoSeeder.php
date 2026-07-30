<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * RiskActionPlanDemoSeeder — jeu de données de démonstration « par recommandation ».
 *
 * Pour chaque risque : 1 recommandation étoffée + N plans d'action rattachés
 * (recommendation_id) + tâches de suivi qui pilotent l'avancement.
 *
 * ⚠️ Écrit sur la connexion `tenant` (base du tenant courant). Exécuter dans un
 * contexte tenant configuré. Réinitialise les recommandations / plans / tâches /
 * logs des risques ciblés avant de réinsérer (idempotent par re-exécution).
 */
class RiskActionPlanDemoSeeder extends Seeder
{
    /** Barème des jeux de données, indexé par code_risk. */
    private function dataset(): array
    {
        return [
            'P03R-A03P03R-R02' => [
                'reco' => "Formaliser et renforcer le dispositif de maîtrise de l'activité : procédures écrites, contrôles périodiques documentés et sensibilisation des agents concernés.",
                'plans' => [
                    ['title' => 'Rédiger et diffuser la procédure de contrôle', 'priority' => 'high', 'action' => "Élaborer une procédure écrite décrivant les points de contrôle, la fréquence et les responsables, puis la diffuser à l'ensemble des agents.", 'cost' => 1500000, 'user' => 5, 'start' => -20, 'target' => 25,
                        'tasks' => [['Rédiger le projet de procédure', 'completed'], ['Faire valider par la direction', 'completed'], ['Diffuser et archiver la version validée', 'pending']]],
                    ['title' => 'Mettre en place un contrôle mensuel documenté', 'priority' => 'medium', 'action' => "Instaurer une revue mensuelle sur la base d'une grille de contrôle, avec traçabilité des constats et suites données.", 'cost' => 800000, 'user' => 6, 'start' => null, 'target' => 60,
                        'tasks' => [['Définir la grille de contrôle', 'pending'], ['Planifier les revues mensuelles', 'pending']]],
                ],
            ],
            'P03R-A03P03R-R03' => [
                'reco' => "Réduire la criticité résiduelle par un plan prioritaire : automatiser les points de contrôle clés et instaurer une revue trimestrielle de conformité.",
                'plans' => [
                    ['title' => 'Automatiser les points de contrôle clés', 'priority' => 'critical', 'action' => "Cartographier les contrôles manuels critiques et développer leur automatisation dans l'outil métier afin de fiabiliser la détection.", 'cost' => 3000000, 'user' => 2, 'start' => -15, 'target' => 40,
                        'tasks' => [['Cartographier les contrôles existants', 'completed'], ["Développer l'automatisation", 'in_progress'], ['Recetter et mettre en production', 'pending']]],
                    ['title' => 'Instaurer une revue trimestrielle de conformité', 'priority' => 'high', 'action' => "Mettre en place un comité trimestriel de revue de conformité couvrant l'activité, avec relevé de décisions.", 'cost' => 500000, 'user' => 7, 'start' => null, 'target' => 90,
                        'tasks' => [['Définir la trame de revue', 'pending']]],
                    ['title' => 'Former les responsables au nouveau dispositif', 'priority' => 'medium', 'action' => "Concevoir un support et animer une session de formation pour les responsables d'activité.", 'cost' => 600000, 'user' => 6, 'start' => -40, 'target' => -5, 'done' => true,
                        'tasks' => [['Concevoir le support de formation', 'completed'], ['Animer la session', 'completed']]],
                ],
            ],
            'P02A01-R02' => [
                'reco' => "Sécuriser le processus d'approbation et assurer la traçabilité des opérations sensibles pour prévenir les erreurs et les fraudes.",
                'plans' => [
                    ['title' => 'Mettre en place une double validation', 'priority' => 'high', 'action' => "Définir des seuils de montant au-delà desquels une double validation hiérarchique est requise, et paramétrer l'outil en conséquence.", 'cost' => 1200000, 'user' => 5, 'start' => -10, 'target' => 30,
                        'tasks' => [['Définir les seuils de validation', 'completed'], ["Paramétrer l'outil", 'pending']]],
                    ['title' => 'Journaliser les opérations sensibles', 'priority' => 'medium', 'action' => "Spécifier et déployer un journal horodaté des opérations sensibles (qui, quoi, quand) exploitable en audit.", 'cost' => 900000, 'user' => 6, 'start' => null, 'target' => 50,
                        'tasks' => [['Spécifier le journal des opérations', 'pending'], ['Déployer et tester', 'pending']]],
                ],
            ],
            'P02RA03-R02' => [
                'reco' => "Renforcer la séparation des tâches et le suivi régulier des habilitations afin de limiter les cumuls de fonctions incompatibles.",
                'plans' => [
                    ['title' => 'Réviser la matrice de séparation des tâches', 'priority' => 'high', 'action' => "Recenser les habilitations en place, identifier les cumuls incompatibles et corriger les accès concernés.", 'cost' => 700000, 'user' => 2, 'start' => -25, 'target' => 20,
                        'tasks' => [['Recenser les habilitations', 'completed'], ['Corriger les cumuls incompatibles', 'in_progress']]],
                    ['title' => 'Mettre en place une revue semestrielle des accès', 'priority' => 'medium', 'action' => "Définir et outiller un processus de revue semestrielle des droits d'accès, avec validation par les responsables.", 'cost' => 400000, 'user' => 7, 'start' => null, 'target' => 120,
                        'tasks' => [['Définir le processus de revue', 'pending']]],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $c = DB::connection('tenant');
        $tid = (int) (session('tenant_id') ?? 4);
        $now = Carbon::now();
        $seq = (int) $now->format('Y') * 1000;

        foreach ($this->dataset() as $code => $spec) {
            $risk = $c->table('risk_register')->where('tenant_id', $tid)->where('code_risk', $code)->whereNull('deleted_at')->first();
            if (!$risk) { $this->command?->line("  ⏭  risque {$code} absent"); continue; }

            // Recommandation (1 par risque)
            $reco = $c->table('risk_recommendations')->where('tenant_id', $tid)->where('risk_id', $risk->id)->whereNull('deleted_at')->first();
            if ($reco) {
                $c->table('risk_recommendations')->where('id', $reco->id)->update(['content' => $spec['reco'], 'updated_at' => $now]);
                $recoId = $reco->id;
            } else {
                $recoId = $c->table('risk_recommendations')->insertGetId(['tenant_id' => $tid, 'risk_id' => $risk->id, 'content' => $spec['reco'], 'created_at' => $now, 'updated_at' => $now]);
            }

            // Purge des anciens plans/tâches/logs du risque (repartir propre)
            $oldPlanIds = $c->table('risk_action_plans')->where('tenant_id', $tid)->where('risk_id', $risk->id)->pluck('id');
            if ($oldPlanIds->isNotEmpty()) {
                $c->table('risk_action_tasks')->whereIn('plan_id', $oldPlanIds)->delete();
                if ($c->getSchemaBuilder()->hasTable('risk_action_logs')) $c->table('risk_action_logs')->whereIn('plan_id', $oldPlanIds)->delete();
                if ($c->getSchemaBuilder()->hasTable('risk_action_comments')) $c->table('risk_action_comments')->whereIn('plan_id', $oldPlanIds)->delete();
                $c->table('risk_action_plans')->whereIn('id', $oldPlanIds)->delete();
            }

            foreach ($spec['plans'] as $p) {
                $tasks = $p['tasks'] ?? [];
                $total = count($tasks);
                $done  = count(array_filter($tasks, fn ($t) => $t[1] === 'completed'));
                $progress = $total ? (int) round($done / $total * 100) : 0;
                $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'pending');
                $start  = $p['start'] !== null ? $now->copy()->addDays($p['start'])->toDateString() : null;
                $target = $now->copy()->addDays($p['target'])->toDateString();
                $completion = ($progress >= 100) ? $now->copy()->addDays(min(0, $p['target']))->toDateString() : null;

                $planId = $c->table('risk_action_plans')->insertGetId([
                    'tenant_id' => $tid, 'code' => 'AP-' . (++$seq), 'risk_id' => $risk->id, 'recommendation_id' => $recoId,
                    'entity_id' => $risk->entity_id, 'title' => $p['title'], 'description' => null, 'action_plan' => $p['action'],
                    'priority' => $p['priority'], 'status' => $status, 'assigned_to' => $p['user'],
                    'target_date' => $target, 'start_date' => $start, 'completion_date' => $completion,
                    'progress' => $progress, 'cost_estimate' => $p['cost'], 'is_auto_generated' => 0,
                    'created_by' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);

                $ord = 0;
                foreach ($tasks as $t) {
                    $c->table('risk_action_tasks')->insert([
                        'tenant_id' => $tid, 'plan_id' => $planId, 'title' => $t[0],
                        'assigned_to' => $p['user'], 'status' => $t[1],
                        'completion_date' => $t[1] === 'completed' ? $now->toDateString() : null,
                        'sort_order' => $ord++, 'created_by' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $this->command?->line("  ✔ {$code} · {$p['title']} — {$progress}% ({$done}/{$total} tâches)");
            }
        }
        $this->command?->info('✅ Plans d\'action de démonstration générés (par recommandation).');
    }
}
