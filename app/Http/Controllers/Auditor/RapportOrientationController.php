<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * RAPPORT D'ORIENTATION (RADO)
 * ════════════════════════════════════════════════════════════════════════
 *
 * Table : mission_phase_ro
 *   code | assignment_id | mission_id | titre | date_rapport | periode_auditee
 *   objectif_general | methodologie | fait_par | revue_par
 *   axes_audit (JSON) | objectifs_specifiques (JSON) | perimetre (JSON)
 *   equipe_audit (JSON) | calendrier (JSON)
 *   validation_status | submitted_at/by | validated_at/by | validation_note
 */
class RapportOrientationController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ro';
    protected string $formCode    = 'rapport-orientation';
    protected string $codePrefix  = 'RADO';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/RapportOrientation';
    protected string $routeEdit   = 'auditor.ac.rapport-orientation.edit';

    protected array $validationRules = [
        'titre'           => 'nullable|string|max:255',
        'date_rapport'    => 'nullable|date',
        'periode_auditee' => 'nullable|string|max:100',
        'fait_par'        => 'nullable|string|max:255',
        'revue_par'       => 'nullable|string|max:255',
    ];

    // ══════════════════════════════════════════════════════════════
    // getRole
    // ══════════════════════════════════════════════════════════════
    protected function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa', 'mpa.id', '=', 'mpaa.assignment_id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditorId)
            ->select('mpaa.role_code')
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->first();
        return $row?->role_code ?? 'AJ';
    }

    // ══════════════════════════════════════════════════════════════
    // formData
    // ══════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            // Fallback sur le libellé de la mission si titre vide (contrainte NOT NULL)
            'titre'           => $request->input('titre') ?: ($request->input('mission_libelle') ?: 'Rapport d\'orientation'),
            'date_rapport'    => $request->input('date_rapport') ?: now()->toDateString(),
            'periode_auditee' => $request->input('periode_auditee'),
            'fait_par'        => $request->input('fait_par'),
            'revue_par'       => $request->input('revue_par'),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // buildPayload
    // ══════════════════════════════════════════════════════════════
    protected function buildPayload(
        int     $missionId,
        int     $assignmentId,
        Auditor $auditor,
        mixed   $form = null
    ): array {

        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.audit_code', 'a.last_name', 'a.first_name', 'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'), COALESCE(LEFT(a.first_name,1),'?'))) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'audit_code' => $a->audit_code,
                'last_name'  => $a->last_name,
                'first_name' => $a->first_name,
                'full_name'  => trim($a->full_name),
                'initials'   => $a->initials,
                'role_code'  => $a->role_code,
                'role_label' => match ($a->role_code) {
                    'DM' => 'Directeur de Mission', 'CM' => 'Chef de Mission',
                    'AS' => 'Auditeur Senior',      'AJ' => 'Auditeur Junior',
                    default => $a->role_code ?? '—',
                },
            ])->toArray();

        $formData = null;
        if ($form) {
            $formData = array_merge((array) $form, [
                'axes_audit'            => $this->decodeArr($form->axes_audit),
                'objectifs_specifiques' => $this->decodeArr($form->objectifs_specifiques),
                'perimetre'             => $this->decodeArr($form->perimetre),
                'equipe_audit'          => $this->decodeArr($form->equipe_audit),
                'calendrier'            => $this->decodeArr($form->calendrier),
            ]);
        }

        $donneesDB = $this->loadDonneesDB($missionId, $assignmentId);

        $radoList = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'titre', 'validation_status', 'fait_par', 'date_rapport'])
            ->orderByDesc('created_at')->get()->toArray();

        $formId = $form?->id;
        $base   = url('/m/audit.core/ac/preparation/rapport-orientation');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $formData,
                'phaseAuditeurs' => $phaseAuditeurs,
                'radoList'       => $radoList,
                'donneesDB'      => $donneesDB,
                'currentAuditor' => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'       => $base,
                'urlStore'      => route('auditor.ac.rapport-orientation.store'),
                'urlUpdate'     => $formId ? route('auditor.ac.rapport-orientation.update',    $formId) : null,
                'urlSoumettre'  => $formId ? route('auditor.ac.rapport-orientation.soumettre', $formId) : null,
                'urlValider'    => $formId ? route('auditor.ac.rapport-orientation.valider',   $formId) : null,
                'urlAiSuggest'  => route('auditor.ac.rapport-orientation.ai-suggest'),
                'urlIndex'      => route('audit.ac.preparation.rapport-orientation'),
                'backUrl'       => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // loadDonneesDB
    // ══════════════════════════════════════════════════════════════
    private function loadDonneesDB(int $missionId, int $assignmentId): array
    {
        $data = [
            'mission'            => null,
            'entites'            => [],
            'ordre_mission'      => null,
            'risques_retenus'    => [],
            'faiblesses'         => [
                'analyse_risques'     => [],
                'analyse_processus'   => [],
                'repartition_taches'  => [],
                'analyse_procedures'  => [],
                'controle_interne'    => [],
                'controle_conformite' => [],
            ],
            'forces' => [
                'analyse_risques'     => [],
                'analyse_processus'   => [],
                'repartition_taches'  => [],
                'analyse_procedures'  => [],
                'controle_interne'    => [],
                'controle_conformite' => [],
            ],
            'objectifs_controle' => [],
            'pdc'                => null,
            'equipe'             => [],
            'fros_preoccupations'=> [],
            'fros_points'        => [],
        ];

        try {
            // A1. Mission programmation
            $mp = DB::connection('tenant')
                ->table('mission_programmation as mp')
                ->leftJoin('missions as m', 'm.id', '=', 'mp.mission_id')
                ->where('mp.id', $missionId)
                ->select(
                    'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.objectif',
                    'mp.date_debut', 'mp.date_fin', 'mp.lieux', 'mp.numero_fpm',
                    'mp.programme', 'mp.status',
                    'm.title as mission_title', 'm.code as mission_code'
                )->first();
            $data['mission'] = $mp ? (array) $mp : null;

            // A2. Entités
            $entites = DB::connection('tenant')
                ->table('mission_programmation_entity as mpe')
                ->join('entities as e', 'e.id', '=', 'mpe.entity_id')
                ->where('mpe.mission_programmation_id', $missionId)
                ->select('e.id', 'e.name', 'e.code_base', 'mpe.date_debut', 'mpe.date_fin')
                ->get()->toArray();

            if (empty($entites)) {
                $asgn = DB::connection('tenant')
                    ->table('mission_phase_assignments as mpa')
                    ->leftJoin('entities as e', 'e.id', '=', 'mpa.entity_id')
                    ->where('mpa.id', $assignmentId)
                    ->select('e.id', 'e.name', 'e.code_base')->first();
                if ($asgn?->id) $entites = [(array) $asgn];
            }
            $data['entites'] = $entites;

            // A3. Ordre de mission
            $om = DB::connection('tenant')
                ->table('ordre_missions')
                ->where('mission_prog_id', $missionId)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->select([
                    'id', 'reference_om', 'intitule', 'objectif', 'lieux',
                    'domaine', 'limite', 'moyen', 'budget',
                    'date_debut', 'date_fin', 'duree',
                    'emetteur', 'destinataire', 'copie', 'date_limite_diffusion',
                ])->first();
            $data['ordre_mission'] = $om ? (array) $om : null;

            // Référentiels
            $risksRef    = DB::connection('tenant')->table('risks')->select('id', 'code', 'label', 'owner')->get()->keyBy('id');
            $processesRef = DB::connection('tenant')->table('processes')->select('id', 'code', 'name')->get()->keyBy('code');

            // B + C/AR
            $arRow = DB::connection('tenant')
                ->table('mission_phase_ar')
                ->where('assignment_id', $assignmentId)
                ->select('id', 'risques')->first();

            if (!$arRow) {
                $arRow = DB::connection('tenant')
                    ->table('mission_phase_ar')
                    ->where('mission_id', $missionId)
                    ->select('id', 'risques')
                    ->orderByRaw("FIELD(validation_status,'validated','in_review','draft')")
                    ->orderByDesc('updated_at')->first();
            }

            if ($arRow) {
                $savedRisks = $this->decodeArr($arRow->risques);
                $numR       = 1;

                foreach ($savedRisks as $r) {
                    $choix    = $r['choix']     ?? false;
                    $riskCode = $r['risk_code'] ?? ($r['code'] ?? '?');
                    $riskId   = (int) ($r['risk_id'] ?? ($r['id'] ?? 0));

                    $impactNet = (float) ($r['impact_net']    ?? 0);
                    $freqNet   = (float) ($r['frequency_net'] ?? 0);
                    $score     = ($r['glob_resid'] ?? null) > 0
                        ? (float) $r['glob_resid']
                        : $impactNet * $freqNet;

                    $riskRef    = $risksRef[$riskId] ?? null;
                    $processObj = $processesRef[$r['process_code'] ?? ''] ?? null;
                    $libelle    = $riskRef?->label ?? $r['activity_name'] ?? "Risque {$riskCode}";
                    $processNom = $processObj?->name ?? $r['process_code'] ?? '';
                    $owner      = $riskRef?->owner ?? '';

                    if ($choix) {
                        $data['risques_retenus'][] = [
                            'num'           => $numR++,
                            'risk_code'     => $riskCode,
                            'libelle'       => $libelle,
                            'processus'     => $processNom,
                            'service'       => $owner,
                            'impact_net'    => $impactNet,
                            'frequency_net' => $freqNet,
                            'score'         => $score,
                            'nature'        => $r['nature']            ?? '',
                            'qualif'        => $r['qualif_controle']   ?? '',
                            'forces'        => $r['forces']            ?? '',
                            'faiblesses'    => $r['faiblesses']        ?? '',
                            'objectif'      => $r['objectif_controle'] ?? '',
                        ];
                    }

                    $forcesTexte = trim($r['forces'] ?? '');
                    if ($forcesTexte !== '' && $choix && $score <= 8) {
                        foreach ($this->splitTexte($forcesTexte) as $ligne) {
                            $data['forces']['analyse_risques'][] = [
                                'libelle'            => $ligne,
                                'processus_concerne' => $processNom,
                                '_source'            => "AR/{$riskCode}",
                                '_score'             => $score,
                            ];
                        }
                    }

                    $faibTexte = trim($r['faiblesses'] ?? '');
                    if ($choix) {
                        if ($faibTexte !== '' && $score >= 4) {
                            foreach ($this->splitTexte($faibTexte) as $ligne) {
                                $data['faiblesses']['analyse_risques'][] = [
                                    'libelle'            => $ligne,
                                    'processus_concerne' => $processNom,
                                    'fonctions'          => $r['qualif_controle'] ?? '',
                                    'objectif_controle'  => $r['objectif_controle'] ?? '',
                                    '_source'            => "AR/{$riskCode}",
                                    '_score'             => $score,
                                ];
                            }
                        } elseif ($faibTexte === '' && $score >= 9) {
                            $data['faiblesses']['analyse_risques'][] = [
                                'libelle'            => $libelle,
                                'processus_concerne' => $processNom,
                                'fonctions'          => '',
                                'objectif_controle'  => $r['objectif_controle'] ?? '',
                                '_source'            => "AR/{$riskCode}",
                                '_score'             => $score,
                            ];
                        }
                    }

                    if ($choix && !empty($r['objectif_controle'])) {
                        foreach ($this->splitTexte($r['objectif_controle']) as $obj) {
                            $data['objectifs_controle'][] = [
                                'source'    => "AR / {$riskCode}",
                                'libelle'   => $obj,
                                'processus' => $processNom,
                            ];
                        }
                    }
                }
            }

            // C/AP
            $apForms = DB::connection('tenant')
                ->table('mission_phase_ap')->where('assignment_id', $assignmentId)->select('id', 'processus')->get();
            if ($apForms->isEmpty()) {
                $apForms = DB::connection('tenant')
                    ->table('mission_phase_ap')->where('mission_id', $missionId)->select('id', 'processus')->get();
            }
            foreach ($apForms as $apForm) {
                foreach ($this->decodeArr($apForm->processus) as $proc) {
                    $procName = $proc['process_name'] ?? ($proc['name'] ?? $proc['process_code'] ?? '');
                    $procCode = $proc['process_code'] ?? ($proc['code'] ?? '');
                    $ft = trim($proc['forces'] ?? '');
                    if ($ft !== '') {
                        foreach ($this->splitTexte($ft) as $ligne) {
                            $data['forces']['analyse_processus'][] = ['libelle' => $ligne, 'processus_concerne' => $procName, '_source' => "AP/{$procCode}"];
                        }
                    }
                    $wt = trim($proc['faiblesses'] ?? '');
                    if ($wt !== '') {
                        foreach ($this->splitTexte($wt) as $ligne) {
                            $data['faiblesses']['analyse_processus'][] = ['libelle' => $ligne, 'processus_concerne' => $procName, 'fonctions' => $proc['proprietaire'] ?? '', 'objectif_controle' => $proc['objectif_operationnel'] ?? '', '_source' => "AP/{$procCode}"];
                            $data['objectifs_controle'][] = ['source' => "AP / {$procCode}", 'libelle' => "Vérifier : {$ligne}", 'processus' => $procName];
                        }
                    }
                }
            }

            // C/APT
            $aptForms = DB::connection('tenant')
                ->table('mission_phase_apt')->where('assignment_id', $assignmentId)->select('id', 'synthese_ff')->get();
            foreach ($aptForms as $apt) {
                $synFF = $this->decodeArr($apt->synthese_ff);
                foreach ($synFF['forces'] ?? [] as $f) {
                    $lib = is_string($f) ? $f : ($f['libelle'] ?? null);
                    if ($lib) $data['forces']['analyse_procedures'][] = ['libelle' => $lib, 'processus_concerne' => is_array($f) ? ($f['processus'] ?? '') : '', '_source' => 'APT'];
                }
                foreach ($synFF['faiblesses'] ?? [] as $w) {
                    $lib = is_string($w) ? $w : ($w['libelle'] ?? null);
                    if ($lib) $data['faiblesses']['analyse_procedures'][] = ['libelle' => $lib, 'processus_concerne' => is_array($w) ? ($w['processus'] ?? '') : '', 'fonctions' => is_array($w) ? ($w['fonctions'] ?? '') : '', 'objectif_controle' => is_array($w) ? ($w['objectif_controle'] ?? '') : '', '_source' => 'APT/synthese_ff'];
                }
                $procs = DB::connection('tenant')->table('apt_procedures')->where('apt_id', $apt->id)->whereNotNull('niveau_conformite')->get();
                foreach ($procs as $proc) {
                    $n = mb_strtolower(trim($proc->niveau_conformite ?? ''));
                    if ($n === 'conforme') {
                        $data['forces']['analyse_procedures'][] = ['libelle' => $proc->intitule, 'processus_concerne' => $proc->service_dept ?? '', '_source' => 'APT-proc'];
                    } elseif (in_array($n, ['non_conforme','partiellement','nc','pp'])) {
                        $data['faiblesses']['analyse_procedures'][] = ['libelle' => $proc->intitule, 'processus_concerne' => $proc->service_dept ?? '', 'fonctions' => $proc->responsable_proc ?? '', 'objectif_controle' => $proc->commentaire ?? '', '_source' => 'APT-proc'];
                    }
                }
            }

            // C/ACONF
            $aconfForms = DB::connection('tenant')->table('mission_phase_aconf')->where('assignment_id', $assignmentId)->select('id')->get();
            foreach ($aconfForms as $aconfForm) {
                $items = DB::connection('tenant')->table('analyse_conformite_items')->where('analyse_conformite_id', $aconfForm->id)->select('ref_article', 'libelle_norme', 'reponse', 'forces', 'faiblesses', 'objectif')->get();
                foreach ($items as $item) {
                    $rep = strtoupper(trim($item->reponse ?? ''));
                    if ($rep === 'O' && !empty($item->forces)) {
                        foreach ($this->splitTexte($item->forces) as $ligne) {
                            $data['forces']['controle_conformite'][] = ['libelle' => $ligne, 'processus_concerne' => $item->libelle_norme ?? '', '_source' => "ACONF/{$item->ref_article}"];
                        }
                    } elseif ($rep === 'N' && !empty($item->faiblesses)) {
                        foreach ($this->splitTexte($item->faiblesses) as $ligne) {
                            $data['faiblesses']['controle_conformite'][] = ['libelle' => $ligne, 'processus_concerne' => $item->libelle_norme ?? '', 'fonctions' => '', 'objectif_controle' => $item->objectif ?? '', '_source' => "ACONF/{$item->ref_article}"];
                        }
                    }
                }
            }

            // C/AMQ
            $amqForms = DB::connection('tenant')->table('mission_phase_amq')->where('assignment_id', $assignmentId)->select('id')->get();
            foreach ($amqForms as $amqForm) {
                $marches = DB::connection('tenant')->table('amq_marches')->where('amq_id', $amqForm->id)->select('id', 'intitule')->get();
                foreach ($marches as $marche) {
                    $etapes = DB::connection('tenant')->table('amq_etapes')->where('marche_id', $marche->id)->select('libelle', 'statut', 'observation')->get();
                    foreach ($etapes as $etape) {
                        if ($etape->statut === 'oui') {
                            $data['forces']['controle_conformite'][] = ['libelle' => $etape->libelle, 'processus_concerne' => $marche->intitule ?? '', '_source' => 'AMQ'];
                        } elseif ($etape->statut === 'non') {
                            $data['faiblesses']['controle_conformite'][] = ['libelle' => $etape->libelle, 'processus_concerne' => $marche->intitule ?? '', 'fonctions' => '', 'objectif_controle' => $etape->observation ?? '', '_source' => 'AMQ'];
                        }
                    }
                }
            }

            // Numérotation
            foreach ($data['faiblesses'] as $dom => &$items) {
                foreach ($items as $i => &$it) { $it['num'] = $i + 1; }
            }
            foreach ($data['forces'] as $dom => &$items) {
                foreach ($items as $i => &$it) { $it['num'] = $i + 1; }
            }

            // E. PDC
            $pdc = DB::connection('tenant')->table('mission_phase_pdc')->where('assignment_id', $assignmentId)
                ->select(['id', 'code', 'entite_auditee', 'periode_auditee', 'responsable_contact', 'effectif', 'budget_total', 'historique', 'contexte', 'observations', 'activites_principales', 'textes_reglementaires', 'sources_information', 'points_attention'])->first();
            if ($pdc) {
                $data['pdc'] = array_merge((array) $pdc, [
                    'activites_principales' => $this->decodeArr($pdc->activites_principales),
                    'textes_reglementaires' => $this->decodeArr($pdc->textes_reglementaires),
                    'sources_information'   => $this->decodeArr($pdc->sources_information),
                    'points_attention'      => $this->decodeArr($pdc->points_attention),
                ]);
            }

            // F. FROS
            $frosRows = DB::connection('tenant')->table('mission_phase_fros')->where('assignment_id', $assignmentId)
                ->select('code_fro', 'date_reunion', 'points_generaux', 'preoccupations')->orderBy('date_reunion')->get();
            foreach ($frosRows as $fro) {
                foreach ($this->decodeArr($fro->points_generaux) as $pt) {
                    $lib = $pt['libelle'] ?? $pt['point'] ?? null;
                    if ($lib) $data['fros_points'][] = ['source' => $fro->code_fro, 'libelle' => $lib, 'date' => $fro->date_reunion];
                }
                foreach ($this->decodeArr($fro->preoccupations) as $pr) {
                    $lib = $pr['libelle'] ?? $pr['point'] ?? null;
                    if ($lib) $data['fros_preoccupations'][] = ['source' => $fro->code_fro, 'libelle' => $lib, 'date' => $fro->date_reunion];
                }
            }

            // G. Équipe
            $equipeRows = DB::connection('tenant')
                ->table('mission_phase_assignment_auditeurs as mpaa')
                ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
                ->where('mpaa.assignment_id', $assignmentId)
                ->select('a.id', 'a.first_name', 'a.last_name', 'a.audit_code', 'a.email', 'mpaa.role_code', DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"))
                ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")->get();

            if ($equipeRows->isEmpty() && $data['ordre_mission']) {
                $equipeRows = DB::connection('tenant')
                    ->table('ordre_mission_auditeurs as oma')
                    ->join('auditors as a', 'a.id', '=', 'oma.auditeur_id')
                    ->where('oma.om_id', $data['ordre_mission']['id'])
                    ->select('a.id', 'a.first_name', 'a.last_name', 'a.audit_code', 'a.email', 'oma.role as role_code', 'oma.role_libelle', DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"))
                    ->orderBy('oma.ordre')->get();
            }

            foreach ($equipeRows as $eq) {
                $data['equipe'][] = [
                    'nom'        => trim($eq->full_name),
                    'audit_code' => $eq->audit_code ?? '',
                    'email'      => $eq->email ?? '',
                    'role'       => $eq->role_libelle ?? match ($eq->role_code) {
                        'DM' => 'Directeur de Mission', 'CM' => 'Chef de Mission',
                        'AS' => 'Auditeur Senior',      'AJ' => 'Auditeur Junior',
                        default => $eq->role_code ?? '',
                    },
                    'role_code'       => $eq->role_code ?? '',
                    'responsabilites' => '',
                ];
            }

        } catch (\Exception $e) {
            Log::error('[RADO] loadDonneesDB: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        return $data;
    }

    // ══════════════════════════════════════════════════════════════
    // POST /ai-suggest
    // Types : objectifs_audit | champ_action
    // mode  : single_axe (regrouper la sélection en 1 seul axe)
    //       | multi_axes (générer 3-5 axes depuis tout le pool)
    // ══════════════════════════════════════════════════════════════
    public function aiSuggest(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $validated = $request->validate([
                'type'             => 'required|in:objectifs_audit,champ_action',
                'mode'             => 'nullable|in:single_axe,multi_axes',
                'mission_title'    => 'nullable|string|max:500',
                'entity_name'      => 'nullable|string|max:300',
                'objectifs_raw'    => 'nullable|array',
                'faiblesses_raw'   => 'nullable|array',
                'risques_retenus'  => 'nullable|array',
                'mission_objectif' => 'nullable|string|max:1000',
                'lieux'            => 'nullable|string|max:500',
                'periode_auditee'  => 'nullable|string|max:200',
                'axes_selectionnes'=> 'nullable|array',
            ]);

            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) return response()->json(['success' => false, 'error' => 'Service IA non configuré'], 500);

            $ctx = '';
            if (!empty($validated['mission_title'])) {
                $ctx = "Mission d'audit : {$validated['mission_title']}. Entité : {$validated['entity_name']}.";
            }

            $result = match ($validated['type']) {
                'objectifs_audit' => $this->suggestObjectifsAudit($apiKey, $validated, $ctx),
                'champ_action'    => $this->suggestChampAction($apiKey, $validated, $ctx),
            };

            return response()->json(array_merge(['success' => true], $result));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[RADO] aiSuggest: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── IA : Objectifs ───────────────────────────────────────────
    private function suggestObjectifsAudit(string $k, array $d, string $ctx): array
    {
        $objRaw = $d['objectifs_raw'] ?? [];
        $mode   = $d['mode'] ?? 'single_axe';

        $objStr = implode("\n", array_map(
            fn($o) => "- [{$o['source']}] {$o['libelle']}",
            array_slice($objRaw, 0, 30)
        ));

        $faibStr = '';
        foreach ($d['faiblesses_raw'] ?? [] as $dom => $items) {
            if (!is_array($items)) continue;
            foreach (array_slice($items, 0, 4) as $it) {
                $faibStr .= "- [{$dom}] " . ($it['libelle'] ?? '') . "\n";
            }
        }

        // ── MODE : single_axe ─────────────────────────────────────
        if ($mode === 'single_axe') {
            $prompt = <<<'PROMPT_END'
Tu es un expert en audit interne. Réponds UNIQUEMENT avec du JSON valide, sans aucun markdown.

PROMPT_END;
            $prompt .= "Contexte : {$ctx}\n";
            $prompt .= "Objectif de la mission : " . ($d['mission_objectif'] ?? '') . "\n\n";
            $prompt .= "Objectifs sélectionnés (à conserver TELS QUELS, sans modification du texte) :\n{$objStr}\n\n";
            $prompt .= <<<'PROMPT_END'
INSTRUCTIONS STRICTES :
1. Trouve 1 seul axe thématique qui regroupe ces objectifs. Intitulé court (5 mots max, aucune ponctuation spéciale).
2. IMPORTANT : recopie chaque objectif EXACTEMENT tel qu'il apparait dans la liste. Ne reformule pas, ne résume pas.
3. Pour chaque objectif, ajoute "indicateurs" : une courte phrase (source ou critère de vérification).
4. "criteres_evaluation" : texte simple, sans astérisques, sans crochets, sans markdown.
5. "priorite" : exactement haute OU moyenne OU basse.

REPONSE : uniquement ce JSON compact, rien d'autre :
{"success":true,"axes":[{"axe":"Titre court","priorite":"haute","criteres_evaluation":"Texte simple","objectifs":[{"objectif":"Texte exact copié","indicateurs":"Source ou critere"}]}]}
PROMPT_END;
            return $this->callMistral($k, $prompt, 1000);
        }

        // ── MODE : multi_axes (usage futur / non utilisé par la sélection) ──
        $prompt = <<<PROMPT
Tu es expert en audit interne (IIA, COSO 2013).
{$ctx}
Objectif général de la mission : {$d['mission_objectif']}

Objectifs de contrôle disponibles :
{$objStr}

Faiblesses identifiées :
{$faibStr}

Regroupe ces objectifs en 3 à 5 axes d'audit thématiques cohérents.
Pour chaque axe, propose 2 à 4 objectifs spécifiques précis et mesurables.

Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,"axes":[
  {
    "axe":"Intitulé de l'axe thématique",
    "priorite":"haute",
    "criteres_evaluation":"Critères d'évaluation de l'axe",
    "objectifs":[
      {"objectif":"Objectif spécifique 1","indicateurs":"Indicateur de mesure"},
      {"objectif":"Objectif spécifique 2","indicateurs":""}
    ]
  }
]}
priorite: haute | moyenne | basse
PROMPT;
        return $this->callMistral($k, $prompt, 2000);
    }

    // ─── IA : Champ d'action ──────────────────────────────────────
    private function suggestChampAction(string $k, array $d, string $ctx): array
    {
        $prompt = <<<PROMPT
Tu es expert en audit interne.
{$ctx}
Objectif de la mission : {$d['mission_objectif']}
Lieux : {$d['lieux']}
Période auditée : {$d['periode_auditee']}

Propose un champ d'action (étendue) complet pour ce rapport d'orientation d'audit.

Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,"perimetre":[
  {"titre":"Général","contenu":"Description générale des vérifications à effectuer"},
  {"titre":"Fonctionnel","contenu":"Directions, services et fonctions concernés"},
  {"titre":"Géographique","contenu":"Zones, sites et agences à auditer"},
  {"titre":"Temporel","contenu":"Période couverte par l'audit"}
]}
PROMPT;
        return $this->callMistral($k, $prompt, 800);
    }

    private function callMistral(string $apiKey, string $prompt, int $maxTokens): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(90)->post('https://api.mistral.ai/v1/chat/completions', [
            'model'       => 'mistral-medium-latest',
            'max_tokens'  => $maxTokens,
            'temperature' => 0.1,   // Très bas pour forcer le format strict
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Tu es un assistant qui répond UNIQUEMENT avec du JSON valide. Aucun texte, aucun markdown, aucune explication. Juste le JSON brut.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (!$response->ok()) {
            throw new \Exception('Mistral HTTP error: ' . $response->status() . ' — ' . $response->body());
        }

        $content = trim($response->json('choices.0.message.content') ?? '');

        // ── Nettoyage agressif du markdown ────────────────────────
        // 1. Blocs de code ```json ... ``` ou ``` ... ```
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/m', '', $content);

        // 2. Extraire uniquement la portion JSON (de { à })
        if (preg_match('/(\{.*\})/s', $content, $m)) {
            $content = $m[1];
        }

        // 3. Nettoyer le markdown DANS les valeurs des strings JSON
        //    Remplacer **texte** → texte  et  _texte_ → texte
        $content = preg_replace_callback(
            '/"([^"]*)"/',
            fn($matches) => '"' . preg_replace(['/\*\*([^*]+)\*\*/', '/__([^_]+)__/', '/\*([^*]+)\*/', '/_([^_]+)_/'], '$1', $matches[1]) . '"',
            $content
        );

        // 4. Dernier trim
        $content = trim($content);

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            Log::warning('[RADO] Réponse Mistral non-JSON après nettoyage : ' . substr($content, 0, 400));
            throw new \Exception('Réponse IA invalide — JSON attendu. Réessayez.');
        }

        return $decoded;
    }

    // ══════════════════════════════════════════════════════════════
    // store
    // ══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int) $request->input('mission_id', 0);
        $assignmentId = (int) $request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId)
            return response()->json(['success' => false, 'message' => 'Contexte mission manquant.'], 422);
        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $assignment = DB::connection('tenant')
            ->table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending')
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);

        $existing = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'         => $assignmentId,
            'mission_id'            => $missionId,
            'code'                  => $this->genCode($missionId),
            'objectif_general'      => $request->input('objectif_general'),
            'methodologie'          => $request->input('methodologie'),
            'axes_audit'            => $this->toJson($request->input('axes_audit',            '[]')),
            'objectifs_specifiques' => $this->toJson($request->input('objectifs_specifiques', '[]')),
            'perimetre'             => $this->toJson($request->input('perimetre',             '[]')),
            'equipe_audit'          => $this->toJson($request->input('equipe_audit',          '[]')),
            'calendrier'            => $this->toJson($request->input('calendrier',            '[]')),
            // Champs supplémentaires stockés dans le JSON meta (colonne existante)
            'meta'                  => $this->toJson([
                'limites'          => $request->input('limites'),
                'contexte'         => $request->input('contexte'),
                'reference_paa'    => $request->input('reference_paa'),
                'origine_mission'  => $request->input('origine_mission'),
                'approuve_par'     => $request->input('approuve_par'),
                'date_approbation' => $request->input('date_approbation'),
                'destinataires'    => $request->input('destinataires'),
                'documents_requis' => $request->input('documents_requis', '[]'),
            ]),
            'validation_status'     => 'draft',
            'created_by'            => $auditor->id,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($form), 'message' => 'RADO créé.']);
    }

    // ══════════════════════════════════════════════════════════════
    // update
    // ══════════════════════════════════════════════════════════════
    public function update(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor))
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        if (!$this->canEdit($row, $role))
            return response()->json(['success' => false, 'message' => match ($row->validation_status) {
                'validated' => 'Rapport validé — modification impossible.',
                'in_review' => 'Rapport soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update(array_merge(
            $this->formData($request, $auditor),
            [
                'objectif_general'      => $request->input('objectif_general'),
                'methodologie'          => $request->input('methodologie'),
                'axes_audit'            => $this->toJson($request->input('axes_audit',            '[]')),
                'objectifs_specifiques' => $this->toJson($request->input('objectifs_specifiques', '[]')),
                'perimetre'             => $this->toJson($request->input('perimetre',             '[]')),
                'equipe_audit'          => $this->toJson($request->input('equipe_audit',          '[]')),
                'calendrier'            => $this->toJson($request->input('calendrier',            '[]')),
                'meta'                  => $this->toJson([
                    'limites'          => $request->input('limites'),
                    'contexte'         => $request->input('contexte'),
                    'reference_paa'    => $request->input('reference_paa'),
                    'origine_mission'  => $request->input('origine_mission'),
                    'approuve_par'     => $request->input('approuve_par'),
                    'date_approbation' => $request->input('date_approbation'),
                    'destinataires'    => $request->input('destinataires'),
                    'documents_requis' => $request->input('documents_requis', '[]'),
                ]),
                'updated_at'            => now(),
            ]
        ));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $this->hydrateForm($updated), 'message' => 'RADO mis à jour.']);
    }

    // ══════════════════════════════════════════════════════════════
    // soumettre
    // ══════════════════════════════════════════════════════════════
    public function soumettre(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Rapport introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) return response()->json(['error' => 'Accès refusé'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review') return response()->json(['error' => 'Déjà soumis'], 422);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    // ══════════════════════════════════════════════════════════════
    // valider
    // ══════════════════════════════════════════════════════════════
    public function valider(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Rapport introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') return response()->json(['error' => 'Rapport non soumis'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM valide définitivement'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by'      => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by'      => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ══════════════════════════════════════════════════════════════
    // destroy
    // ══════════════════════════════════════════════════════════════
    public function destroy(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Rapport introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);
        if ($row->validation_status === 'validated') return response()->json(['error' => 'Un rapport validé ne peut pas être supprimé'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)->delete();
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'deleted', $row->validation_status, null);
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════
    private function hydrateForm(mixed $row): array
    {
        if (!$row) return [];
        $meta = $this->decodeArr($row->meta ?? null);
        return array_merge((array) $row, [
            'axes_audit'            => $this->decodeArr($row->axes_audit),
            'objectifs_specifiques' => $this->decodeArr($row->objectifs_specifiques),
            'perimetre'             => $this->decodeArr($row->perimetre),
            'equipe_audit'          => $this->decodeArr($row->equipe_audit),
            'calendrier'            => $this->decodeArr($row->calendrier),
            // Champs extra depuis meta
            'limites'          => $meta['limites']          ?? null,
            'contexte'         => $meta['contexte']         ?? null,
            'reference_paa'    => $meta['reference_paa']    ?? null,
            'origine_mission'  => $meta['origine_mission']  ?? null,
            'approuve_par'     => $meta['approuve_par']     ?? null,
            'date_approbation' => $meta['date_approbation'] ?? null,
            'destinataires'    => $meta['destinataires']    ?? null,
            'documents_requis' => $this->decodeArr($meta['documents_requis'] ?? null),
        ]);
    }

    private function decodeArr(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        try { $d = json_decode($v, true); return is_array($d) ? $d : []; }
        catch (\Exception) { return []; }
    }

    private function toJson(mixed $v): string
    {
        if (is_string($v)) { json_decode($v); if (json_last_error() === JSON_ERROR_NONE) return $v; }
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }

    private function splitTexte(string $texte): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\n;]+/', $texte)), fn($l) => $l !== ''));
    }
}