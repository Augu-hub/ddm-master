<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * RÉFÉRENTIEL DE CONTRÔLE DES MARCHÉS PUBLICS (RCM)
 * ════════════════════════════════════════════════════════════════════════
 * Architecture relationnelle (même pattern que RCC) :
 *   mission_phase_ref_marches          — formulaire principal
 *   mission_phase_ref_marches_phases   — affectation auditeur par phase
 *   mission_phase_ref_marches_criteres — critères créés par l'auditeur
 *
 * Workflow :
 *  1. DM/CM crée le RCM + affecte un auditeur par phase
 *  2. Auditeur affecté crée ses critères dans sa phase (save immédiat)
 *  3. Upload preuves par critère
 *  4. Soumission → in_review → validation DM → validated
 * ════════════════════════════════════════════════════════════════════════
 */
class ReferentielMarchesController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ref_marches';
    protected string $formCode    = 'referentiel-marches';
    protected string $codePrefix  = 'RCM';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ReferentielMarches';
    protected string $routeEdit   = 'auditor.ac.referentiel-marches.edit';

    protected array $validationRules = [
        'fait_par'  => 'nullable|string|max:255',
        'revue_par' => 'nullable|string|max:255',
    ];

    private const DISK = 'public';

    public const PHASES = [
        'PLA' => ['label' => 'Planification & Programmation',        'icon' => '📋', 'color' => '#1e40af'],
        'DAO' => ['label' => "Dossier d'Appel d'Offres (DAO)",       'icon' => '📄', 'color' => '#7e22ce'],
        'ROO' => ['label' => 'Réception & Ouverture des Offres',     'icon' => '📬', 'color' => '#0369a1'],
        'EVA' => ['label' => 'Évaluation des Offres & Attribution',  'icon' => '🔍', 'color' => '#b45309'],
        'SAN' => ['label' => 'Signature, Approbation & Notification','icon' => '✍',  'color' => '#15803d'],
        'EXE' => ['label' => 'Exécution & Suivi du Marché',          'icon' => '⚙',  'color' => '#9f1239'],
        'REP' => ['label' => 'Réception & Paiement',                 'icon' => '🏁', 'color' => '#92400e'],
        'CAT' => ['label' => 'Contrôle, Archivage & Transparence',   'icon' => '🗄',  'color' => '#374151'],
    ];

    // ── getRole ───────────────────────────────────────────────────
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

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'fait_par'               => $request->input('fait_par'),
            'revue_par'              => $request->input('revue_par'),
            'autorite_contractante'  => $request->input('autorite_contractante'),
            'exercice_budgetaire'    => $request->input('exercice_budgetaire'),
        ];
    }

    // ── getPhaseAuditeurs ─────────────────────────────────────────
    private function getPhaseAuditeurs(int $assignmentId): array
    {
        return DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select('a.id','a.audit_code','a.last_name','a.first_name','mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"))
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()
            ->map(fn($a) => [
                'id' => $a->id, 'audit_code' => $a->audit_code,
                'last_name' => $a->last_name, 'first_name' => $a->first_name,
                'full_name' => trim($a->full_name), 'role_code' => $a->role_code,
                'role_label' => match($a->role_code) {
                    'DM'=>'Directeur de Mission','CM'=>'Chef de Mission',
                    'AS'=>'Auditeur Senior','AJ'=>'Auditeur Junior',default=>$a->role_code??'—'
                },
            ])->toArray();
    }

    // ── loadPhases ────────────────────────────────────────────────
    private function loadPhases(int $rcmId): array
    {
        $rows = DB::connection('tenant')
            ->table('mission_phase_ref_marches_phases')
            ->where('rcm_id', $rcmId)
            ->orderBy('ordre')
            ->get()->keyBy('phase_code');

        $result = [];
        foreach (self::PHASES as $code => $def) {
            $row = $rows[$code] ?? null;
            $result[$code] = array_merge($def, [
                'phase_code'  => $code,
                'auditeur_id' => $row ? (int)$row->auditeur_id : null,
            ]);
        }
        return $result;
    }

    // ── loadCriteres ──────────────────────────────────────────────
    private function loadCriteres(int $rcmId): array
    {
        return DB::connection('tenant')
            ->table('mission_phase_ref_marches_criteres')
            ->where('rcm_id', $rcmId)
            ->orderBy('phase_code')
            ->orderBy('ordre')
            ->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'id'                 => $c->id,
                'rcm_id'             => $c->rcm_id,
                'phase_code'         => $c->phase_code,
                'ref_controle'       => $c->ref_controle       ?? '',
                'ref_reglementaire'  => $c->ref_reglementaire  ?? '',
                'intitule_procedure' => $c->intitule_procedure ?? '',
                'point_controle'     => $c->point_controle     ?? '',
                'note_preuves'       => $c->note_preuves       ?? '',
                'preuves_fichiers'   => $c->preuves_fichiers
                                        ? json_decode($c->preuves_fichiers, true) ?? [] : [],
                'auditeur_id'        => $c->auditeur_id ? (int)$c->auditeur_id : null,
                'ordre'              => $c->ordre,
            ])->toArray();
    }

    // ── buildPayload ──────────────────────────────────────────────
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $role           = $this->getRole($missionId, $auditor->id);
        $phaseAuditeurs = $this->getPhaseAuditeurs($assignmentId);
        $phases         = $form ? $this->loadPhases($form->id) : array_map(fn($d) => array_merge($d, ['auditeur_id'=>null]), self::PHASES);
        $criteres       = $form ? $this->loadCriteres($form->id) : [];

        // Phases affectées à cet auditeur
        $myPhases = array_keys(array_filter($phases, fn($p) => (int)($p['auditeur_id']??0) === $auditor->id));

        $mission = DB::connection('tenant')
            ->table('mission_programmation as mp')->where('mp.id', $missionId)
            ->select('mp.id','mp.code_mission','mp.libelle','mp.objectif','mp.date_debut','mp.date_fin','mp.lieux','mp.numero_fpm')
            ->first();

        $rcmList = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)
            ->select(['id','code','validation_status','fait_par','updated_at'])
            ->orderByDesc('created_at')->get()->toArray();

        $formId = $form?->id ?? null;

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $form ? (array)$form : null,
                'phases'         => $phases,
                'criteres'       => $criteres,
                'myPhases'       => $myPhases,
                'phaseAuditeurs' => $phaseAuditeurs,
                'rcmList'        => $rcmList,
                'mission'        => $mission ? (array)$mission : null,
                'currentAuditor' => [
                    'id'=>$auditor->id,'audit_code'=>$auditor->audit_code,
                    'last_name'=>$auditor->last_name,'first_name'=>$auditor->first_name,'role'=>$role,
                ],
                'canManage'              => in_array($role, ['DM','CM']),
                'urlStore'               => route('auditor.ac.referentiel-marches.store'),
                'urlUpdate'              => $formId ? route('auditor.ac.referentiel-marches.update',        $formId) : null,
                'urlSoumettre'           => $formId ? route('auditor.ac.referentiel-marches.soumettre',     $formId) : null,
                'urlValider'             => $formId ? route('auditor.ac.referentiel-marches.valider',       $formId) : null,
                'urlUpdatePhases'        => $formId ? route('auditor.ac.referentiel-marches.phases',        $formId) : null,
                'urlStoreCritere'        => $formId ? route('auditor.ac.referentiel-marches.critere.store', $formId) : null,
                'urlUpdateCritere'       => $formId ? route('auditor.ac.referentiel-marches.critere.update',':id')   : null,
                'urlDeleteCritere'       => $formId ? route('auditor.ac.referentiel-marches.critere.destroy',':id')  : null,
                'urlUploadPreuve'        => $formId ? route('auditor.ac.referentiel-marches.upload-preuve', $formId) : null,
                'urlDeletePreuve'        => $formId ? route('auditor.ac.referentiel-marches.delete-preuve', $formId) : null,
                'urlIndex'               => route('audit.ac.preparation.referentiel-marches'),
                'backUrl'                => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ── index / edit ──────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $auditor=$this->getAuditor(); if(!$auditor) abort(403);
            $missionId=(int)($request->input('mission_id')??session('mission_id',0));
            $assignmentId=(int)($request->input('assignment_id')??session('assignment_id',0));
            if(!$missionId||!$assignmentId) abort(422,'Contexte mission manquant.');
            $existing=DB::connection('tenant')->table($this->table)->where('assignment_id',$assignmentId)->first();
            if($existing) return redirect()->route($this->routeEdit,$existing->id)->with('mission_id',$missionId)->with('assignment_id',$assignmentId);
            return \Inertia\Inertia::render($this->inertiaPage,$this->buildPayload($missionId,$assignmentId,$auditor,null));
        } catch(\Exception $e){Log::error('[RCM] index: '.$e->getMessage());return back()->with('error',$e->getMessage());}
    }

    public function edit(Request $request, int $form)
    {
        try {
            $auditor=$this->getAuditor(); if(!$auditor) abort(403);
            $row=DB::connection('tenant')->table($this->table)->where('id',$form)->firstOrFail();
            $missionId=(int)($request->input('mission_id')??session('mission_id')??$row->mission_id);
            $assignmentId=(int)($request->input('assignment_id')??session('assignment_id')??$row->assignment_id);
            if(!$this->canAccess($missionId,$assignmentId,$auditor)) abort(403);
            return \Inertia\Inertia::render($this->inertiaPage,$this->buildPayload($missionId,$assignmentId,$auditor,$row));
        } catch(\Exception $e){Log::error('[RCM] edit: '.$e->getMessage());return back()->with('error',$e->getMessage());}
    }

    // ── store ─────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $auditor=$this->getAuditor(); if(!$auditor) abort(403);
        $missionId=(int)$request->input('mission_id',0);
        $assignmentId=(int)$request->input('assignment_id',0);
        if(!$missionId||!$assignmentId) return response()->json(['success'=>false,'message'=>'Contexte mission manquant.'],422);
        $role=$this->getRole($missionId,$auditor->id);
        if(!$this->canAccess($missionId,$assignmentId,$auditor)) return response()->json(['success'=>false,'message'=>'Accès refusé.'],403);
        if(!in_array($role,['DM','CM'])) return response()->json(['success'=>false,'message'=>'Seuls DM/CM peuvent créer.'],403);
        $assignment=DB::connection('tenant')->table('mission_phase_assignments')->where('id',$assignmentId)->first();
        if(!$assignment||$assignment->status==='pending') return response()->json(['success'=>false,'message'=>'Démarrez la phase avant.'],422);
        $existing=DB::connection('tenant')->table($this->table)->where('assignment_id',$assignmentId)->first();
        if($existing) return response()->json(['success'=>true,'redirect'=>route('auditor.ac.referentiel-marches.edit',$existing->id)]);

        $id=DB::connection('tenant')->table($this->table)->insertGetId(array_merge($this->formData($request,$auditor),[
            'assignment_id'=>$assignmentId,'mission_id'=>$missionId,
            'code'=>$this->genCode($missionId),'validation_status'=>'draft',
            'created_by'=>$auditor->id,'created_at'=>now(),'updated_at'=>now(),
        ]));

        // Créer les lignes de phase avec affectations
        $phaseAffect = $request->input('phase_affectations', []);
        $ordre = 0;
        foreach (array_keys(self::PHASES) as $code) {
            DB::connection('tenant')->table('mission_phase_ref_marches_phases')->insert([
                'rcm_id'=>$id,'phase_code'=>$code,
                'auditeur_id'=>$phaseAffect[$code]??null,'ordre'=>$ordre++,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        $this->log($assignmentId,$auditor->id,$role,'saved',null,'draft');
        return response()->json(['success'=>true,'message'=>'RCM créé.','redirect'=>route('auditor.ac.referentiel-marches.edit',$id)]);
    }

    // ── update (infos générales) ──────────────────────────────────
    public function update(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) abort(403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) abort(404);
        $missionId=(int)($request->input('mission_id')??$row->mission_id);
        $assignmentId=(int)($request->input('assignment_id')??$row->assignment_id);
        $role=$this->getRole($missionId,$auditor->id);
        if(!$this->canAccess($missionId,$assignmentId,$auditor)) return response()->json(['success'=>false,'message'=>'Accès refusé.'],403);
        if(!$this->canEdit($row,$role)) return response()->json(['success'=>false,'message'=>'Modification non autorisée.'],403);
        DB::connection('tenant')->table($this->table)->where('id',$form)->update(array_merge($this->formData($request,$auditor),['synthese'=>$request->input('synthese'),'updated_at'=>now()]));
        $this->log($assignmentId,$auditor->id,$role,'saved',$row->validation_status,$row->validation_status);
        return response()->json(['success'=>true,'message'=>'RCM mis à jour.']);
    }

    // ── updatePhases — affectations DM/CM ────────────────────────
    // PUT referentiel-marches/{rcm}/phases
    public function updatePhases(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) return response()->json(['error'=>'RCM introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])) return response()->json(['error'=>'Seuls DM/CM peuvent gérer les affectations'],403);
        if($row->validation_status==='validated') return response()->json(['error'=>'RCM validé'],403);

        $phaseAffect = $request->input('phase_affectations', []);
        foreach ($phaseAffect as $code => $audId) {
            if (!array_key_exists($code, self::PHASES)) continue;
            DB::connection('tenant')->table('mission_phase_ref_marches_phases')
                ->updateOrInsert(
                    ['rcm_id'=>$form,'phase_code'=>$code],
                    ['auditeur_id'=>$audId??null,'updated_at'=>now()]
                );
        }
        $phases = $this->loadPhases($form);
        return response()->json(['success'=>true,'phases'=>$phases,'message'=>'Affectations enregistrées.']);
    }

    // ════════════════════════════════════════════════════════════════
    // CRITÈRES — CRUD (save immédiat)
    // ════════════════════════════════════════════════════════════════

    // POST referentiel-marches/{rcm}/critere
    public function storeCritere(Request $request, int $rcm)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$rcm)->first();
        if(!$row) return response()->json(['error'=>'RCM introuvable'],404);
        if($row->validation_status==='validated') return response()->json(['error'=>'RCM validé'],403);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);

        $phaseCode=strtoupper(trim($request->input('phase_code','')));
        if(!array_key_exists($phaseCode,self::PHASES)) return response()->json(['error'=>'Phase invalide'],422);

        // Vérifier affectation
        $phase=DB::connection('tenant')->table('mission_phase_ref_marches_phases')
            ->where('rcm_id',$rcm)->where('phase_code',$phaseCode)->first();
        if(!in_array($role,['DM','CM']) && (int)($phase?->auditeur_id??0)!==$auditor->id)
            return response()->json(['error'=>"Vous n'êtes pas affecté à la phase {$phaseCode}"],403);

        $intitule=trim($request->input('intitule_procedure','Nouveau critère'));
        $count=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')
            ->where('rcm_id',$rcm)->where('phase_code',$phaseCode)->count();

        $id=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->insertGetId([
            'rcm_id'              => $rcm,
            'phase_code'          => $phaseCode,
            'ref_controle'        => $request->input('ref_controle') ?? ($phaseCode.'-C'.str_pad($count+1,2,'0',STR_PAD_LEFT)),
            'ref_reglementaire'   => $request->input('ref_reglementaire'),
            'intitule_procedure'  => $intitule,
            'point_controle'      => $request->input('point_controle'),
            'note_preuves'        => $request->input('note_preuves'),
            'preuves_fichiers'    => '[]',
            'auditeur_id'         => $auditor->id,
            'ordre'               => $count,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $critere=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$id)->first();
        return response()->json(['success'=>true,'critere'=>array_merge((array)$critere,['preuves_fichiers'=>[]]),'message'=>'Critère ajouté.']);
    }

    // PUT referentiel-marches/critere/{critere}
    public function updateCritere(Request $request, int $critere)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critere)->first();
        if(!$row) return response()->json(['error'=>'Critère introuvable'],404);
        $rcc=DB::connection('tenant')->table($this->table)->where('id',$row->rcm_id)->first();
        $role=$this->getRole((int)$rcc->mission_id,$auditor->id);
        if($rcc->validation_status==='validated') return response()->json(['error'=>'RCM validé'],403);
        if(!in_array($role,['DM','CM'])&&(int)$row->auditeur_id!==$auditor->id) return response()->json(['error'=>'Accès refusé'],403);

        DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critere)->update([
            'ref_controle'       => $request->input('ref_controle',      $row->ref_controle),
            'ref_reglementaire'  => $request->input('ref_reglementaire', $row->ref_reglementaire),
            'intitule_procedure' => $request->input('intitule_procedure',$row->intitule_procedure),
            'point_controle'     => $request->input('point_controle',    $row->point_controle),
            'note_preuves'       => $request->input('note_preuves',      $row->note_preuves),
            'updated_at'         => now(),
        ]);
        $updated=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critere)->first();
        return response()->json(['success'=>true,'critere'=>array_merge((array)$updated,['preuves_fichiers'=>$updated->preuves_fichiers?json_decode($updated->preuves_fichiers,true)??[]:[]]), 'message'=>'Critère mis à jour.']);
    }

    // DELETE referentiel-marches/critere/{critere}
    public function destroyCritere(Request $request, int $critere)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critere)->first();
        if(!$row) return response()->json(['error'=>'Critère introuvable'],404);
        $rcc=DB::connection('tenant')->table($this->table)->where('id',$row->rcm_id)->first();
        $role=$this->getRole((int)$rcc->mission_id,$auditor->id);
        if($rcc->validation_status==='validated') return response()->json(['error'=>'RCM validé'],403);
        if(!in_array($role,['DM','CM'])&&(int)$row->auditeur_id!==$auditor->id) return response()->json(['error'=>'Accès refusé'],403);
        $fichiers=$row->preuves_fichiers?json_decode($row->preuves_fichiers,true)??[]:[];
        foreach($fichiers as $f) Storage::disk(self::DISK)->delete($f['path']??'');
        DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critere)->delete();
        return response()->json(['success'=>true,'message'=>'Critère supprimé.']);
    }

    // ════════════════════════════════════════════════════════════════
    // PREUVES
    // ════════════════════════════════════════════════════════════════

    // POST referentiel-marches/{rcm}/upload-preuve
    public function uploadPreuve(Request $request, int $rcm)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $request->validate(['critere_id'=>'required|integer','file'=>'required|file|mimes:pdf,xlsx,xls,docx,doc,png,jpg,jpeg|max:10240']);
        $row=DB::connection('tenant')->table($this->table)->where('id',$rcm)->first();
        if(!$row||$row->validation_status==='validated') return response()->json(['error'=>'Non autorisé'],403);
        $critereId=(int)$request->input('critere_id');
        $critere=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critereId)->where('rcm_id',$rcm)->first();
        if(!$critere) return response()->json(['error'=>'Critère introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])&&(int)$critere->auditeur_id!==$auditor->id) return response()->json(['error'=>'Accès refusé'],403);
        $file=$request->file('file');
        $storedPath=$file->store("rcm/{$rcm}/{$critereId}",self::DISK);
        $fileInfo=['name'=>$file->getClientOriginalName(),'path'=>$storedPath,'url'=>Storage::disk(self::DISK)->url($storedPath),'size'=>$file->getSize(),'mime'=>$file->getMimeType(),'uploaded_at'=>now()->toISOString(),'uploaded_by'=>$auditor->id];
        $existing=$critere->preuves_fichiers?json_decode($critere->preuves_fichiers,true)??[]:[];
        $existing[]=$fileInfo;
        DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critereId)->update(['preuves_fichiers'=>json_encode($existing,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
        return response()->json(['success'=>true,'fichier'=>$fileInfo,'message'=>"Fichier '{$fileInfo['name']}' joint."]);
    }

    // DELETE referentiel-marches/{rcm}/delete-preuve
    public function deletePreuve(Request $request, int $rcm)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $critereId=(int)$request->input('critere_id'); $path=$request->input('path');
        if(!$critereId||!$path) return response()->json(['error'=>'Paramètres manquants'],422);
        $row=DB::connection('tenant')->table($this->table)->where('id',$rcm)->first();
        if(!$row||$row->validation_status==='validated') return response()->json(['error'=>'Non autorisé'],403);
        $critere=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critereId)->where('rcm_id',$rcm)->first();
        if(!$critere) return response()->json(['error'=>'Critère introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])&&(int)$critere->auditeur_id!==$auditor->id) return response()->json(['error'=>'Accès refusé'],403);
        Storage::disk(self::DISK)->delete($path);
        $fichiers=array_values(array_filter($critere->preuves_fichiers?json_decode($critere->preuves_fichiers,true)??[]:[], fn($f)=>$f['path']!==$path));
        DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('id',$critereId)->update(['preuves_fichiers'=>json_encode($fichiers,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
        return response()->json(['success'=>true,'message'=>'Fichier supprimé.']);
    }

    // ── Workflow ──────────────────────────────────────────────────
    public function soumettre(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) return response()->json(['error'=>'Formulaire introuvable'],404);
        $missionId=(int)($request->input('mission_id')??$row->mission_id); $assignmentId=(int)($request->input('assignment_id')??$row->assignment_id);
        $role=$this->getRole($missionId,$auditor->id);
        if(!$this->canAccess($missionId,$assignmentId,$auditor)) return response()->json(['error'=>'Accès refusé'],403);
        if($row->validation_status==='validated') return response()->json(['error'=>'Déjà validé'],422);
        if($row->validation_status==='in_review') return response()->json(['error'=>'Déjà soumis'],422);
        DB::connection('tenant')->table($this->table)->where('id',$form)->update(['validation_status'=>'in_review','submitted_at'=>now(),'submitted_by'=>$auditor->id,'updated_at'=>now()]);
        $this->log($assignmentId,$auditor->id,$role,'submitted','draft','in_review');
        return response()->json(['success'=>true,'status'=>'in_review']);
    }

    public function valider(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) return response()->json(['error'=>'Formulaire introuvable'],404);
        $missionId=(int)($request->input('mission_id')??$row->mission_id); $assignmentId=(int)($request->input('assignment_id')??$row->assignment_id);
        $role=$this->getRole($missionId,$auditor->id);
        if(!in_array($role,['DM','CM'])) return response()->json(['error'=>'Seuls DM/CM peuvent valider'],403);
        if($row->validation_status!=='in_review') return response()->json(['error'=>'Formulaire non soumis'],422);
        $action=$request->input('action','validate'); $note=$request->input('note');
        if($action==='reject'){
            if(!$note) return response()->json(['error'=>'Motif obligatoire'],422);
            DB::connection('tenant')->table($this->table)->where('id',$form)->update(['validation_status'=>'draft','validation_note'=>$note,'updated_at'=>now()]);
            $this->log($assignmentId,$auditor->id,$role,'rejected','in_review','draft',$note);
            return response()->json(['success'=>true,'status'=>'draft','action'=>'rejected']);
        }
        if($role!=='DM') return response()->json(['error'=>'Seul le DM peut valider définitivement'],403);
        DB::connection('tenant')->table($this->table)->where('id',$form)->update(['validation_status'=>'validated','validated_at'=>now(),'validated_by'=>$auditor->id,'validation_note'=>$note,'updated_at'=>now()]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id',$assignmentId)->update(['validation_status'=>'validated','validated_at'=>now(),'validated_by'=>$auditor->id,'updated_at'=>now()]);
        $this->log($assignmentId,$auditor->id,$role,'validated','in_review','validated',$note);
        return response()->json(['success'=>true,'status'=>'validated','action'=>'validated']);
    }

    public function destroy(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) return response()->json(['error'=>'Formulaire introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])) return response()->json(['error'=>'Seuls DM/CM peuvent supprimer'],403);
        if($row->validation_status==='validated') return response()->json(['error'=>'Formulaire validé'],403);
        $criteres=DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('rcm_id',$form)->get();
        foreach($criteres as $c){$f=$c->preuves_fichiers?json_decode($c->preuves_fichiers,true)??[]:[];foreach($f as $fi) Storage::disk(self::DISK)->delete($fi['path']??'');}
        DB::connection('tenant')->table('mission_phase_ref_marches_criteres')->where('rcm_id',$form)->delete();
        DB::connection('tenant')->table('mission_phase_ref_marches_phases')->where('rcm_id',$form)->delete();
        DB::connection('tenant')->table($this->table)->where('id',$form)->delete();
        $this->log((int)$row->assignment_id,$auditor->id,$role,'deleted',$row->validation_status,null);
        return response()->json(['success'=>true]);
    }
}