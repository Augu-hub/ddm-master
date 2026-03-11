<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AuditorDashboardController extends Controller
{
    private function getConnectedAuditor(): ?Auditor
    {
        $auditorId = Session::get('auditor_id');
        if ($auditorId) {
            $auditor = Auditor::with(['user', 'entity', 'competencies.category'])->find($auditorId);
            if ($auditor) return $auditor;
        }

        $user = Auth::user();
        if (!$user) return null;

        $auditor = Auditor::with(['user', 'entity', 'competencies.category'])
            ->where('email', $user->email)
            ->where('status', 'active')
            ->first();

        if ($auditor) Session::put('auditor_id', $auditor->id);
        return $auditor;
    }

    public function index(Request $request)
    {
        $auditor = $this->getConnectedAuditor();

        if (!$auditor) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Compte auditeur introuvable ou inactif.']);
        }

        Log::info('Dashboard Auditeur', ['auditor_id' => $auditor->id, 'code' => $auditor->audit_code]);

        $currentYear = (int) date('Y');

        // ── Missions affectées avec processus ────────────────────────────────
        $affectations = DB::table('mission_phase_auditeurs as mpa')
            ->select([
                'mpa.id as id',
                'mpa.mission_id',
                'mpa.entites',
                'mp.code_mission',
                'mp.libelle',
                'mp.objectif',
                'mp.date_debut',
                'mp.date_fin',
                'mp.lieux',
                'mp.status',
                DB::raw("COALESCE(mr.code, mpa.role, '—') as mon_role"),
                DB::raw("COALESCE(mr.libelle, mpa.role, '—') as role_libelle"),
                DB::raw("DATE_FORMAT(mp.date_debut, '%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin, '%d/%m/%Y') as date_fin_fr"),
                DB::raw("DATEDIFF(mp.date_fin, mp.date_debut) + 1 as duree"),
                DB::raw("CASE
                    WHEN mp.status = 'terminee'  THEN 100
                    WHEN mp.status = 'annulee'   THEN 0
                    WHEN mp.date_debut > CURDATE() THEN 0
                    WHEN mp.date_fin < CURDATE() AND mp.status != 'terminee' THEN 99
                    WHEN DATEDIFF(mp.date_fin, mp.date_debut) = 0 THEN 100
                    ELSE ROUND(LEAST(
                        (DATEDIFF(CURDATE(), mp.date_debut) / NULLIF(DATEDIFF(mp.date_fin, mp.date_debut), 0)) * 100,
                        99), 0)
                END as progression"),
                DB::raw("(SELECT COALESCE(SUM(montant),0) FROM mission_auditeur_budget_lines WHERE affectation_id = mpa.id) as budget_individuel"),
            ])
            ->join('mission_programmation as mp', 'mpa.mission_id', '=', 'mp.id')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.auditeur_id', $auditor->id)
            ->groupBy(
                'mpa.id','mpa.mission_id','mpa.entites','mp.code_mission','mp.libelle',
                'mp.objectif','mp.date_debut','mp.date_fin',
                'mp.lieux','mp.status','mr.code','mpa.role','mr.libelle'
            )
            ->orderByRaw("FIELD(mp.status,'en_cours','planifiee','terminee','annulee')")
            ->orderBy('mp.date_debut','asc')
            ->get();

        $missionIds     = $affectations->pluck('mission_id')->unique()->filter()->toArray();
        $affectationIds = $affectations->pluck('id')->toArray();

        // ── Processus par mission ─────────────────────────────────────────────
        $processusParMission = [];
        if (!empty($missionIds)) {
            // Essai table processes
            $processusLoaded = false;
            foreach (['processes', 'processus', 'audit_processes'] as $tbl) {
                try {
                    if (!DB::getSchemaBuilder()->hasTable($tbl)) continue;
                    DB::table('mission_programmation as mp')
                        ->join($tbl . ' as proc', 'mp.process_id', '=', 'proc.id')
                        ->whereIn('mp.id', $missionIds)
                        ->select([
                            'mp.id as mission_id',
                            DB::raw("COALESCE(proc.name, proc.libelle, proc.nom, '—') as processus_nom"),
                            DB::raw("COALESCE(proc.code, '—') as processus_code"),
                            DB::raw("COALESCE(proc.description, '') as processus_description"),
                        ])
                        ->get()
                        ->each(function ($r) use (&$processusParMission) {
                            $processusParMission[$r->mission_id] = [
                                'nom'         => $r->processus_nom,
                                'code'        => $r->processus_code,
                                'description' => $r->processus_description,
                            ];
                        });
                    $processusLoaded = true;
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
            // Fallback: colonne process_name dans mission_programmation
            if (!$processusLoaded) {
                try {
                    DB::table('mission_programmation')
                        ->whereIn('id', $missionIds)
                        ->select(['id', 'process_name', 'process_code', 'processus'])
                        ->get()
                        ->each(function ($r) use (&$processusParMission) {
                            $nom = $r->process_name ?? $r->processus ?? null;
                            if ($nom) {
                                $processusParMission[$r->id] = [
                                    'nom'  => $nom,
                                    'code' => $r->process_code ?? '—',
                                    'description' => null,
                                ];
                            }
                        });
                } catch (\Exception $e2) {
                    Log::warning('Processus non chargé: ' . $e2->getMessage());
                }
            }
        }

        // ── Périodes par entité ───────────────────────────────────────────────
        $entityPeriodsParMission = [];
        if (!empty($missionIds)) {
            DB::table('mission_programmation_entity as mpe')
                ->join('entities as e', 'mpe.entity_id', '=', 'e.id')
                ->whereIn('mpe.mission_programmation_id', $missionIds)
                ->select([
                    'mpe.mission_programmation_id as mission_id',
                    'mpe.entity_id',
                    'e.name as entity_name',
                    // Fix: e.code column does not exist, use empty string
                    DB::raw("'' as entity_code"),
                    'mpe.date_debut',
                    'mpe.date_fin',
                    DB::raw("DATE_FORMAT(mpe.date_debut,'%d/%m/%Y') as date_debut_fr"),
                    DB::raw("DATE_FORMAT(mpe.date_fin,'%d/%m/%Y') as date_fin_fr"),
                ])
                ->orderBy('e.name')
                ->get()
                ->each(function ($row) use (&$entityPeriodsParMission) {
                    $entityPeriodsParMission[$row->mission_id][$row->entity_id] = [
                        'entity_id'     => (int) $row->entity_id,
                        'entity_name'   => $row->entity_name,
                        'entity_code'   => $row->entity_code,
                        'date_debut'    => $row->date_debut,
                        'date_fin'      => $row->date_fin,
                        'date_debut_fr' => $row->date_debut_fr,
                        'date_fin_fr'   => $row->date_fin_fr,
                    ];
                });
        }

        // ── Risques par mission ───────────────────────────────────────────────
        $risquesParMission = [];
        if (!empty($missionIds)) {
            // Tentatives sur plusieurs noms de tables possibles
            $riskTables = ['mission_risks', 'audit_risks', 'mission_risk_assessments', 'risks'];
            foreach ($riskTables as $tbl) {
                try {
                    if (!DB::getSchemaBuilder()->hasTable($tbl)) continue;
                    $cols = DB::getSchemaBuilder()->getColumnListing($tbl);
                    // Colonne mission_id obligatoire
                    if (!in_array('mission_id', $cols)) continue;

                    $titreCol  = collect(['titre','title','name','nom','libelle'])->first(fn($c) => in_array($c, $cols)) ?? 'id';
                    $niveauCol = collect(['niveau','level','risk_level','criticite'])->first(fn($c) => in_array($c, $cols));
                    $probaCol  = collect(['probabilite','probability','proba','likelihood'])->first(fn($c) => in_array($c, $cols));
                    $impactCol = collect(['impact','gravite','severity'])->first(fn($c) => in_array($c, $cols));
                    $mesuresCol= collect(['mesures','mesure','mitigation','actions','recommandations'])->first(fn($c) => in_array($c, $cols));
                    $descCol   = collect(['description','details','detail'])->first(fn($c) => in_array($c, $cols));
                    $statutCol = collect(['statut','status','etat'])->first(fn($c) => in_array($c, $cols));

                    DB::table($tbl)
                        ->whereIn('mission_id', $missionIds)
                        ->orderBy('id')
                        ->get()
                        ->each(function ($r) use (&$risquesParMission, $titreCol, $niveauCol, $probaCol, $impactCol, $mesuresCol, $descCol, $statutCol) {
                            $risquesParMission[$r->mission_id][] = [
                                'id'          => $r->id,
                                'titre'       => $r->$titreCol ?? 'Risque #' . $r->id,
                                'description' => $descCol   ? ($r->$descCol   ?? null) : null,
                                'probabilite' => $probaCol  ? ($r->$probaCol  ?? null) : null,
                                'impact'      => $impactCol ? ($r->$impactCol ?? null) : null,
                                'niveau'      => $niveauCol ? ($r->$niveauCol ?? null) : null,
                                'mesures'     => $mesuresCol? ($r->$mesuresCol?? null) : null,
                                'statut'      => $statutCol ? ($r->$statutCol ?? 'identifie') : 'identifie',
                            ];
                        });

                    if (!empty($risquesParMission)) break; // Table trouvée et données chargées
                } catch (\Exception $e) {
                    Log::warning("Table risques $tbl: " . $e->getMessage());
                }
            }

            // Fallback: niveau depuis mission_requests si disponible
            if (empty($risquesParMission)) {
                try {
                    $requestsMap = DB::table('mission_requests')
                        ->whereIn('id', function($q) use ($missionIds) {
                            $q->select('request_id')->from('mission_programmation')
                              ->whereIn('id', $missionIds)->whereNotNull('request_id');
                        })
                        ->select(['id', 'code', 'level', 'coefficient', 'mission_objective'])
                        ->get()
                        ->keyBy('id');

                    DB::table('mission_programmation')
                        ->whereIn('id', $missionIds)
                        ->whereNotNull('request_id')
                        ->select(['id', 'request_id'])
                        ->get()
                        ->each(function ($mp) use (&$risquesParMission, $requestsMap) {
                            $req = $requestsMap[$mp->request_id] ?? null;
                            if ($req && $req->level && $req->level !== 'Mineur') {
                                $risquesParMission[$mp->id][] = [
                                    'id'          => 0,
                                    'titre'       => 'Niveau: ' . $req->level,
                                    'description' => $req->mission_objective ?? null,
                                    'probabilite' => null,
                                    'impact'      => null,
                                    'niveau'      => $req->level,
                                    'mesures'     => 'Coefficient: ' . $req->coefficient,
                                    'statut'      => 'identifie',
                                ];
                            }
                        });
                } catch (\Exception $e3) {
                    Log::warning('Risques fallback échoué: ' . $e3->getMessage());
                }
            }
        }

        // ── Construire affectationEntities ────────────────────────────────────
        $affectationEntities = [];
        foreach ($affectations as $aff) {
            $entiteIds = json_decode($aff->entites ?? '[]', true);
            if (empty($entiteIds)) {
                $entiteIds = array_keys($entityPeriodsParMission[$aff->mission_id] ?? []);
            }

            $processus = $processusParMission[$aff->mission_id] ?? null;
            $risques   = $risquesParMission[$aff->mission_id]   ?? [];

            if (empty($entiteIds)) {
                $affectationEntities[] = $this->buildAffectationEntity($aff, null, null, $processus, $risques);
            } else {
                foreach ($entiteIds as $eid) {
                    $ep = $entityPeriodsParMission[$aff->mission_id][(int)$eid] ?? null;
                    $affectationEntities[] = $this->buildAffectationEntity($aff, (int)$eid, $ep, $processus, $risques);
                }
            }
        }

        // ── entities_list par affectation ─────────────────────────────────────
        $entitiesListParAffectation = [];
        foreach ($affectations as $aff) {
            $entiteIds = json_decode($aff->entites ?? '[]', true);
            if (empty($entiteIds)) {
                $noms = array_column($entityPeriodsParMission[$aff->mission_id] ?? [], 'entity_name');
            } else {
                $noms = [];
                foreach ($entiteIds as $eid) {
                    $name = $entityPeriodsParMission[$aff->mission_id][(int)$eid]['entity_name'] ?? null;
                    if ($name) $noms[] = $name;
                }
            }
            $entitiesListParAffectation[$aff->id] = implode(', ', $noms) ?: '—';
        }

        $affectations = $affectations->map(function ($aff) use ($entitiesListParAffectation, $risquesParMission, $processusParMission) {
            $aff->entities_list = $entitiesListParAffectation[$aff->id] ?? '—';
            $aff->risques       = $risquesParMission[$aff->mission_id]   ?? [];
            $aff->nb_risques    = count($aff->risques);
            $aff->processus     = $processusParMission[$aff->mission_id] ?? null;
            return $aff;
        });

        // ── Équipes par mission ───────────────────────────────────────────────
        $equipesParMission = [];
        if (!empty($missionIds)) {
            $membres = DB::table('mission_phase_auditeurs as mpa')
                ->select([
                    'mpa.mission_id',
                    'a.id as auditeur_id',
                    'a.audit_code',
                    'a.first_name',
                    'a.last_name',
                    'a.avatar',
                    DB::raw("COALESCE(mr.code, mpa.role, '—') as role"),
                    DB::raw("COALESCE(mr.libelle, mpa.role, '—') as role_libelle"),
                ])
                ->join('auditors as a', 'mpa.auditeur_id', '=', 'a.id')
                ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
                ->whereIn('mpa.mission_id', $missionIds)
                ->orderByRaw('COALESCE(mr.niveau, 99) ASC')
                ->orderBy('a.last_name')
                ->get();

            foreach ($membres as $m) {
                $mid = $m->mission_id;
                if (!isset($equipesParMission[$mid])) {
                    $equipesParMission[$mid] = ['total' => 0, 'membres' => []];
                }
                $equipesParMission[$mid]['membres'][] = [
                    'auditeur_id'  => $m->auditeur_id,
                    'audit_code'   => $m->audit_code,
                    'first_name'   => $m->first_name,
                    'last_name'    => $m->last_name,
                    'avatar'       => $m->avatar,
                    'role'         => $m->role,
                    'role_libelle' => $m->role_libelle,
                    'is_me'        => $m->auditeur_id === $auditor->id,
                ];
                $equipesParMission[$mid]['total']++;
            }
        }

        // ── Budget lignes ─────────────────────────────────────────────────────
        $budgetLignes = [];
        if (!empty($affectationIds)) {
            DB::table('mission_auditeur_budget_lines as mabl')
                ->leftJoin('mission_budget_categories as mbc', 'mabl.category_id', '=', 'mbc.id')
                ->leftJoin('entities as e', 'mabl.entity_id', '=', 'e.id')
                ->whereIn('mabl.affectation_id', $affectationIds)
                ->select([
                    'mabl.affectation_id',
                    'mabl.montant',
                    'e.name as entity_name',
                    DB::raw("COALESCE(mbc.libelle, mabl.custom_label, 'Divers') as libelle"),
                ])
                ->orderBy('mabl.id')
                ->get()
                ->each(function ($row) use (&$budgetLignes) {
                    $budgetLignes[$row->affectation_id][] = [
                        'libelle'     => $row->libelle,
                        'montant'     => (float) $row->montant,
                        'entity_name' => $row->entity_name ?? '—',
                    ];
                });
        }

        // ── Statistiques ──────────────────────────────────────────────────────
        $stats = [
            'mes_missions'      => $affectations->count(),
            'en_cours'          => $affectations->where('status', 'en_cours')->count(),
            'planifiees'        => $affectations->where('status', 'planifiee')->count(),
            'terminees'         => $affectations->where('status', 'terminee')->count(),
            'annulees'          => $affectations->where('status', 'annulee')->count(),
            'jours_total'       => (int) $affectations->sum('duree'),
            'budget_total'      => (float) $affectations->sum('budget_individuel'),
            'taux_realisation'  => $affectations->count() > 0
                ? (int) round($affectations->where('status', 'terminee')->count() / $affectations->count() * 100)
                : 0,
            'nb_risques_total'  => array_sum(array_map('count', $risquesParMission)),
        ];

        // ── Prochaine / En cours ──────────────────────────────────────────────
        $prochaineMission = $affectations->where('status', 'planifiee')->sortBy('date_debut')->first();
        $missionEnCours   = $affectations->where('status', 'en_cours')->sortBy('date_debut')->first();

        // ── Calendrier 12 mois ────────────────────────────────────────────────
        $nomsCourt = [
            1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Jun',
            7=>'Jul',8=>'Aoû',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc',
        ];
        $nomsLong  = [
            1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',
            7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre',
        ];
        $statusLabels = [
            'en_cours'=>'En cours','planifiee'=>'Planifiée','terminee'=>'Terminée','annulee'=>'Annulée','libre'=>'Libre',
        ];

        $calendrier = [];
        for ($m = 1; $m <= 12; $m++) {
            $debutMoisTs = mktime(0, 0, 0, $m, 1, $currentYear);
            $finMoisTs   = mktime(23, 59, 59, $m, (int) date('t', $debutMoisTs), $currentYear);

            $itemsDuMois = [];
            $joursTotal  = 0;
            $missionVues = [];

            foreach ($affectationEntities as $ae) {
                $dateDebut = $ae['entity_date_debut'] ?? $ae['date_debut'];
                $dateFin   = $ae['entity_date_fin']   ?? $ae['date_fin'];
                if (!$dateDebut || !$dateFin) continue;

                $debutTs = strtotime($dateDebut);
                $finTs   = strtotime($dateFin);
                if ($finTs < $debutMoisTs || $debutTs > $finMoisTs) continue;

                $jours       = (int) floor((min($finTs, $finMoisTs) - max($debutTs, $debutMoisTs)) / 86400) + 1;
                $joursTotal += $jours;
                $missionVues[$ae['mission_id']] = true;

                $itemsDuMois[] = [
                    'id'                 => $ae['id'],
                    'mission_id'         => $ae['mission_id'],
                    'code_mission'       => $ae['code_mission'],
                    'libelle'            => $ae['libelle'],
                    'status'             => $ae['status'],
                    'mon_role'           => $ae['mon_role'],
                    'processus_nom'      => $ae['processus_nom'],
                    'entity_id'          => $ae['entity_id'],
                    'entity_name'        => $ae['entity_name'],
                    'date_debut'         => $dateDebut,
                    'date_fin'           => $dateFin,
                    'date_debut_fr'      => $ae['entity_date_debut_fr'] ?? $ae['date_debut_fr'],
                    'date_fin_fr'        => $ae['entity_date_fin_fr']   ?? $ae['date_fin_fr'],
                    'jours_dans_mois'    => $jours,
                    'semaines_dans_mois' => round($jours / 5, 1),
                    'nb_risques'         => $ae['nb_risques'],
                ];
            }

            $statuts = collect($itemsDuMois)->pluck('status')->unique();
            if ($statuts->contains('en_cours'))      $status = 'en_cours';
            elseif ($statuts->contains('planifiee')) $status = 'planifiee';
            elseif ($statuts->contains('terminee'))  $status = 'terminee';
            elseif ($statuts->contains('annulee'))   $status = 'annulee';
            else                                      $status = 'libre';

            $calendrier[] = [
                'mois'         => $m,
                'label'        => $nomsCourt[$m],
                'label_long'   => $nomsLong[$m],
                'status'       => $status,
                'status_label' => $statusLabels[$status],
                'nb_missions'  => count($missionVues),
                'jours'        => $joursTotal,
                'semaines'     => round($joursTotal / 5, 1),
                'missions'     => array_values($itemsDuMois),
            ];
        }

        return Inertia::render('dashboards/Auditor/Dashboard', [
            'auditor'             => [
                'id'               => $auditor->id,
                'audit_code'       => $auditor->audit_code,
                'first_name'       => $auditor->first_name,
                'last_name'        => $auditor->last_name,
                'nom_complet'      => strtoupper($auditor->last_name).' '.ucfirst(strtolower($auditor->first_name)),
                'initiales'        => mb_strtoupper(mb_substr($auditor->last_name??'',0,1).mb_substr($auditor->first_name??'',0,1)),
                'email'            => $auditor->email,
                'phone'            => $auditor->phone,
                'gender'           => $auditor->gender,
                'avatar'           => $auditor->avatar,
                'entity'           => $auditor->entity?->name ?? null,
                'audit_experience' => $auditor->audit_experience ?? 0,
                'other_experience' => $auditor->other_experience ?? 0,
                'bio'              => $auditor->bio,
                'status'           => $auditor->status,
            ],
            'affectations'        => $affectations->values(),
            'affectationEntities' => array_values($affectationEntities),
            'equipesParMission'   => $equipesParMission,
            'budgetLignes'        => $budgetLignes,
            'stats'               => $stats,
            'prochaineMission'    => $prochaineMission,
            'missionEnCours'      => $missionEnCours,
            'calendrier'          => $calendrier,
            'currentYear'         => $currentYear,
        ]);
    }

    private function buildAffectationEntity(
        $aff,
        ?int $entityId,
        ?array $ep,
        ?array $processus = null,
        array $risques = []
    ): array {
        return [
            'id'                   => $aff->id,
            'mission_id'           => $aff->mission_id,
            'entity_id'            => $entityId,
            'entity_name'          => $ep['entity_name']   ?? null,
            'entity_code'          => $ep['entity_code']   ?? null,
            'entity_date_debut'    => $ep['date_debut']    ?? $aff->date_debut,
            'entity_date_fin'      => $ep['date_fin']      ?? $aff->date_fin,
            'entity_date_debut_fr' => $ep['date_debut_fr'] ?? $aff->date_debut_fr,
            'entity_date_fin_fr'   => $ep['date_fin_fr']   ?? $aff->date_fin_fr,
            'date_debut'           => $aff->date_debut,
            'date_fin'             => $aff->date_fin,
            'date_debut_fr'        => $aff->date_debut_fr,
            'date_fin_fr'          => $aff->date_fin_fr,
            'code_mission'         => $aff->code_mission,
            'libelle'              => $aff->libelle,
            'objectif'             => $aff->objectif,
            'lieux'                => $aff->lieux,
            'status'               => $aff->status,
            'mon_role'             => $aff->mon_role,
            'role_libelle'         => $aff->role_libelle,
            'duree'                => $aff->duree,
            'progression'          => $aff->progression,
            'budget_individuel'    => (float) $aff->budget_individuel,
            // Processus
            'processus_nom'        => $processus['nom']         ?? null,
            'processus_code'       => $processus['code']        ?? null,
            'processus_description'=> $processus['description'] ?? null,
            // Risques
            'risques'              => $risques,
            'nb_risques'           => count($risques),
        ];
    }
}