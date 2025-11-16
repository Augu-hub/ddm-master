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
        $db = DB::connection($this->connection);
        $schema = Schema::connection($this->connection);

        /* ════════════════════════════════════════════════
         * 1️⃣ MATURITÉ — Critères CEM1 à CEM12
         * ════════════════════════════════════════════════ */
        if ($schema->hasTable('process_maturity_scales')) {

            // Critères principaux
            $criteres = [
                ['code'=>'CEM1','label'=>'Formalisation du processus','description'=>'Degré de documentation et de formalisation des activités.'],
                ['code'=>'CEM2','label'=>'Enregistrements','description'=>'Existence et fiabilité des enregistrements du processus.'],
                ['code'=>'CEM3','label'=>'Indicateurs et tableaux de bord','description'=>'Présence d’indicateurs et de tableaux de suivi des performances.'],
                ['code'=>'CEM4','label'=>'Actions d’amélioration','description'=>'Existence et suivi des actions correctives et préventives.'],
                ['code'=>'CEM5','label'=>'Système d’information et communication','description'=>'Qualité et rapidité de la communication interne/externe.'],
                ['code'=>'CEM6','label'=>'Gestion des interfaces','description'=>'Maîtrise des échanges entre processus amont et aval.'],
                ['code'=>'CEM7','label'=>'Veille et Benchmark','description'=>'Capacité à rechercher et intégrer les meilleures pratiques externes.'],
                ['code'=>'CEM8','label'=>'Capitalisation du savoir-faire','description'=>'Formalisation et partage des connaissances internes.'],
                ['code'=>'CEM9','label'=>'Maîtrise des risques','description'=>'Capacité à identifier, évaluer et réduire les risques du processus.'],
                ['code'=>'CEM10','label'=>'Gestion des compétences','description'=>'Adéquation entre compétences, ressources et exigences du processus.'],
                ['code'=>'CEM11','label'=>'Satisfaction du client interne/externe','description'=>'Mesure et suivi de la satisfaction des bénéficiaires du processus.'],
                ['code'=>'CEM12','label'=>'Performance et efficacité','description'=>'Capacité du processus à atteindre ses objectifs de résultat.'],
            ];

            foreach ($criteres as $c) {
                $db->table('process_maturity_scales')->updateOrInsert(['code'=>$c['code']], $c);
            }

            // Niveaux génériques
            $levels = [
                1 => 'Fonctionnement de base',
                2 => 'Défini',
                3 => 'Maîtrisé',
                4 => 'Optimisé',
                5 => 'Amélioration permanente',
            ];

            // Descriptions par critère (issus du cahier des charges)
            $descriptions = [
                'CEM1'=>[1=>'Aucune formalisation du processus.',2=>'Existence partielle d’une documentation.',3=>'Documentation complète et utilisée.',4=>'Documentation actualisée et maîtrisée.',5=>'Documentation intégrée et automatisée dans le système qualité.'],
                'CEM2'=>[1=>'Enregistrements inexistants ou non fiables.',2=>'Quelques enregistrements sans suivi formalisé.',3=>'Enregistrements systématiques et archivés.',4=>'Enregistrements exploités pour améliorer les pratiques.',5=>'Enregistrements numériques intégrés et analysés automatiquement.'],
                'CEM3'=>[1=>'Aucun indicateur de suivi défini.',2=>'Indicateurs définis mais peu exploités.',3=>'Indicateurs suivis et analysés régulièrement.',4=>'Indicateurs intégrés au pilotage de la performance.',5=>'Tableaux de bord dynamiques et interconnectés.'],
                'CEM4'=>[1=>'Aucune action d’amélioration planifiée.',2=>'Actions réactives ponctuelles.',3=>'Plan d’action formalisé et suivi.',4=>'Amélioration continue structurée.',5=>'Culture d’amélioration permanente et participative.'],
                'CEM5'=>[1=>'Communication informelle et non structurée.',2=>'Canaux d’information partiellement définis.',3=>'Système d’information structuré et partagé.',4=>'Communication fluide et réactive.',5=>'Système intégré favorisant la transparence et l’agilité.'],
                'CEM6'=>[1=>'Interfaces mal définies entre services.',2=>'Coordination ponctuelle et non planifiée.',3=>'Interfaces formalisées et coordonnées.',4=>'Interactions optimisées entre acteurs.',5=>'Gestion proactive et collaborative des interfaces.'],
                'CEM7'=>[1=>'Aucune activité de veille.',2=>'Veille informelle non structurée.',3=>'Veille organisée et périodique.',4=>'Benchmark régulier avec les meilleures pratiques.',5=>'Culture de veille et d’innovation systématique.'],
                'CEM8'=>[1=>'Aucune capitalisation du savoir-faire.',2=>'Capitalisation ponctuelle non diffusée.',3=>'Outils de capitalisation en place.',4=>'Capitalisation intégrée dans la formation interne.',5=>'Apprentissage organisationnel institutionnalisé.'],
                'CEM9'=>[1=>'Aucun risque identifié.',2=>'Identification partielle des risques.',3=>'Cartographie complète et mise à jour régulière.',4=>'Plan de maîtrise appliqué et suivi.',5=>'Approche proactive et préventive systématique.'],
                'CEM10'=>[1=>'Aucune gestion des compétences.',2=>'Évaluation informelle des compétences.',3=>'Gestion planifiée des compétences clés.',4=>'Plan de développement aligné sur les besoins stratégiques.',5=>'Compétences intégrées dans une démarche de performance continue.'],
                'CEM11'=>[1=>'Aucune mesure de satisfaction client.',2=>'Enquêtes ponctuelles non exploitées.',3=>'Suivi régulier des retours clients.',4=>'Système de mesure intégré et réactif.',5=>'Culture client ancrée et systématisée.'],
                'CEM12'=>[1=>'Aucune mesure de performance du processus.',2=>'Quelques indicateurs suivis sans objectif clair.',3=>'Objectifs fixés et suivis régulièrement.',4=>'Pilotage proactif basé sur les résultats.',5=>'Culture de performance et innovation continue.'],
            ];

            if (! $schema->hasTable('process_maturity_scale_levels')) {
                $schema->create('process_maturity_scale_levels', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('scale_id');
                    $t->integer('level_score');
                    $t->string('level_label');
                    $t->text('level_description')->nullable();
                    $t->timestamps();
                });
            }

            foreach ($descriptions as $code => $data) {
                $scaleId = $db->table('process_maturity_scales')->where('code',$code)->value('id');
                if (!$scaleId) continue;
                foreach ($data as $score => $desc) {
                    $db->table('process_maturity_scale_levels')->updateOrInsert(
                        ['scale_id'=>$scaleId,'level_score'=>$score],
                        ['level_label'=>$levels[$score],'level_description'=>$desc]
                    );
                }
            }
        }

        /* ════════════════════════════════════════════════
         * 2️⃣ MOTRICITÉ — Influence
         * ════════════════════════════════════════════════ */
        if ($schema->hasTable('process_motricity_scales')) {
            $motricite = [
                ['code'=>'MP1','label'=>'Influence nulle','description'=>'Aucun impact sur les autres processus.'],
                ['code'=>'MP2','label'=>'Influence faible','description'=>'Impact limité à quelques interactions.'],
                ['code'=>'MP3','label'=>'Influence notable','description'=>'Impact sur plusieurs processus.'],
                ['code'=>'MP4','label'=>'Influence majeure','description'=>'Impact fort sur les processus connexes.'],
                ['code'=>'MP5','label'=>'Influence forte','description'=>'Processus moteur central de l’organisation.'],
            ];
            foreach ($motricite as $m) $db->table('process_motricity_scales')->updateOrInsert(['code'=>$m['code']], $m);
        }

        /* ════════════════════════════════════════════════
         * 3️⃣ TRANSVERSALITÉ — Partage interservices
         * ════════════════════════════════════════════════ */
        if ($schema->hasTable('process_transversality_scales')) {
            $trans = [
                ['code'=>'TR1','label'=>'Non partagé','description'=>'Processus isolé dans un seul service.'],
                ['code'=>'TR2','label'=>'Partage faible','description'=>'Collaboration ponctuelle entre quelques services.'],
                ['code'=>'TR3','label'=>'Partage notable','description'=>'Impliquant plusieurs services de manière coordonnée.'],
                ['code'=>'TR4','label'=>'Partage majeur','description'=>'Transversal à la majorité des services.'],
                ['code'=>'TR5','label'=>'Très partagé','description'=>'Processus inter-organisationnel, transversal complet.'],
            ];
            foreach ($trans as $t) $db->table('process_transversality_scales')->updateOrInsert(['code'=>$t['code']], $t);
        }

        /* ════════════════════════════════════════════════
         * 4️⃣ POIDS STRATÉGIQUE — Importance stratégique
         * ════════════════════════════════════════════════ */
        if ($schema->hasTable('process_strategic_weight_scales')) {
            $strategic = [
                ['code'=>'PS1','label'=>'Impact nul','description'=>'Aucune influence sur la stratégie globale.'],
                ['code'=>'PS2','label'=>'Impact faible','description'=>'Contribue faiblement aux objectifs stratégiques.'],
                ['code'=>'PS3','label'=>'Impact notable','description'=>'Apporte une contribution significative à la stratégie.'],
                ['code'=>'PS4','label'=>'Impact majeur','description'=>'Essentiel pour la réussite des objectifs stratégiques.'],
                ['code'=>'PS5','label'=>'Impact fort','description'=>'Processus clé et structurant pour la stratégie globale.'],
            ];
            foreach ($strategic as $s) $db->table('process_strategic_weight_scales')->updateOrInsert(['code'=>$s['code']], $s);
        }

        /* ════════════════════════════════════════════════
         * 5️⃣ NORMES DE CRITICITÉ
         * ════════════════════════════════════════════════ */
        if ($schema->hasTable('process_criticality_norms')) {
            $normes = [
                ['min_percent'=>0,'max_percent'=>20,'color'=>'#22C55E','alert_label'=>'Vert','alert_level'=>'Très faible','actions'=>'Aucune modification de conception requise.'],
                ['min_percent'=>20,'max_percent'=>40,'color'=>'#3B82F6','alert_label'=>'Bleu','alert_level'=>'Faible','actions'=>'Maintenance corrective et amélioration ponctuelle.'],
                ['min_percent'=>40,'max_percent'=>60,'color'=>'#FACC15','alert_label'=>'Jaune','alert_level'=>'Moyenne','actions'=>'Maintenance préventive et/ou systématique.'],
                ['min_percent'=>60,'max_percent'=>80,'color'=>'#F97316','alert_label'=>'Orange','alert_level'=>'Élevée','actions'=>'Révision conception et surveillance accrue.'],
                ['min_percent'=>80,'max_percent'=>100,'color'=>'#EF4444','alert_label'=>'Rouge','alert_level'=>'Très élevée','actions'=>'Remise en cause complète de la conception.'],
            ];
            foreach ($normes as $n) $db->table('process_criticality_norms')->updateOrInsert(['min_percent'=>$n['min_percent']], $n);
        }

        Log::info("✅ Référentiels complets d’évaluation des processus (Maturité, Motricité, Transversalité, Stratégique, Normes) insérés.");
    }

    public function down(): void
    {
        foreach ([
            'process_maturity_scale_levels',
            'process_maturity_scales',
            'process_motricity_scales',
            'process_transversality_scales',
            'process_strategic_weight_scales',
            'process_criticality_norms',
        ] as $table) {
            if (Schema::connection($this->connection)->hasTable($table)) {
                DB::connection($this->connection)->table($table)->truncate();
            }
        }
    }
};
