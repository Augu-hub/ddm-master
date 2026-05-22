<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * RÉFÉRENTIEL DE CONTRÔLE DES TRANSACTIONS FINANCIÈRES (RCT)
 * ════════════════════════════════════════════════════════════════════════
 * Architecture relationnelle (même pattern que RCC) :
 *   mission_phase_ref_transactions              — formulaire principal
 *   mission_phase_ref_transactions_categories   — affectation auditeur/catégorie
 *   mission_phase_ref_transactions_saisies      — saisies auditeur par critère référentiel
 *   ref_transactions_categories                 — catégories (lecture seule)
 *   ref_transactions_criteres                   — critères référentiel (lecture seule)
 *
 * Workflow :
 *  1. DM/CM crée le RCT + affecte un auditeur par catégorie
 *  2. Les critères sont chargés depuis ref_transactions_criteres
 *  3. L'auditeur remplit ses saisies (preuves) — save immédiat
 *  4. Soumission → in_review → validation DM → validated
 * ════════════════════════════════════════════════════════════════════════
 */
class ReferentielTransactionsController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ref_transactions';
    protected string $formCode    = 'referentiel-transactions';
    protected string $codePrefix  = 'RCT';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/ReferentielTransactions';
    protected string $routeEdit   = 'auditor.ac.referentiel-transactions.edit';

    protected array $validationRules = [
        'fait_par'            => 'nullable|string|max:255',
        'revue_par'           => 'nullable|string|max:255',
        'entite_auditee'      => 'nullable|string|max:255',
        'exercice_budgetaire' => 'nullable|string|max:20',
        'periode_controle'    => 'nullable|string|max:50',
    ];

    private const DISK = 'public';

    // ── Lecture BD référentiel ────────────────────────────────────
    private function loadCategories(): array
    {
        return DB::connection('tenant')
            ->table('ref_transactions_categories')
            ->where('actif', true)->orderBy('ordre')
            ->get(['code','libelle','icone','couleur'])
            ->mapWithKeys(fn($r) => [$r->code => ['label'=>$r->libelle,'icon'=>$r->icone??'','color'=>$r->couleur??'#374151']])
            ->toArray();
    }

    private function loadReferentielCriteres(): array
    {
        return DB::connection('tenant')
            ->table('ref_transactions_criteres')
            ->where('actif', true)->orderBy('categorie_code')->orderBy('ordre')
            ->get(['categorie_code','ref_controle','ref_reglementaire','intitule_procedure','point_controle','preuves_attendues'])
            ->toArray();
    }

    // ── getRole ───────────────────────────────────────────────────
    protected function getRole(int $missionId, int $auditorId): string
    {
        $row = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('mission_phase_assignments as mpa','mpa.id','=','mpaa.assignment_id')
            ->where('mpa.mission_programmation_id',$missionId)->where('mpaa.auditeur_id',$auditorId)
            ->select('mpaa.role_code')->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")->first();
        return $row?->role_code ?? 'AJ';
    }

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'fait_par'=>$request->input('fait_par'),'revue_par'=>$request->input('revue_par'),
            'entite_auditee'=>$request->input('entite_auditee'),
            'exercice_budgetaire'=>$request->input('exercice_budgetaire'),
            'periode_controle'=>$request->input('periode_controle'),
        ];
    }

    private function getPhaseAuditeurs(int $assignmentId): array
    {
        return DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a','a.id','=','mpaa.auditeur_id')
            ->where('mpaa.assignment_id',$assignmentId)
            ->select('a.id','a.audit_code','a.last_name','a.first_name','mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as full_name"))
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->get()->map(fn($a)=>[
                'id'=>$a->id,'audit_code'=>$a->audit_code,'last_name'=>$a->last_name,
                'first_name'=>$a->first_name,'full_name'=>trim($a->full_name),'role_code'=>$a->role_code,
                'role_label'=>match($a->role_code){'DM'=>'Directeur de Mission','CM'=>'Chef de Mission','AS'=>'Auditeur Senior','AJ'=>'Auditeur Junior',default=>$a->role_code??'—'},
            ])->toArray();
    }

    // ── loadCategories avec affectations ─────────────────────────
    private function loadCategoriesWithAffect(int $rctId): array
    {
        $cats = $this->loadCategories();
        $rows = DB::connection('tenant')
            ->table('mission_phase_ref_transactions_categories')
            ->where('rct_id',$rctId)->get()->keyBy('categorie_code');
        foreach ($cats as $code => &$cat) {
            $row = $rows[$code] ?? null;
            $cat['auditeur_id'] = $row ? (int)$row->auditeur_id : null;
        }
        return $cats;
    }

    // ── loadCriteres — fusion référentiel BD + saisies table ──────
    private function loadCriteres(int $rctId): array
    {
        $refCriteres = $this->loadReferentielCriteres();
        $cats = $this->loadCategoriesWithAffect($rctId);

        // Saisies de cet auditeur
        $saisies = DB::connection('tenant')
            ->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rctId)->get()
            ->keyBy('ref_controle');

        $result = [];
        foreach ($refCriteres as $def) {
            $ref   = $def->ref_controle;
            $saisie = $saisies[$ref] ?? null;
            $catAuditeurId = (int)($cats[$def->categorie_code]['auditeur_id'] ?? 0);

            $result[] = [
                // Depuis ref_transactions_criteres (lecture seule)
                'categorie_code'     => $def->categorie_code,
                'ref_controle'       => $ref,
                'ref_reglementaire'  => $def->ref_reglementaire,
                'intitule_procedure' => $def->intitule_procedure,
                'point_controle'     => $def->point_controle,
                'preuves_attendues'  => $def->preuves_attendues,
                // Depuis mission_phase_ref_transactions_saisies
                'saisie_id'          => $saisie ? $saisie->id : null,
                'note_preuves'       => $saisie?->note_preuves ?? '',
                'preuves_fichiers'   => $saisie?->preuves_fichiers ? json_decode($saisie->preuves_fichiers,true)??[] : [],
                'auditeur_id'        => $saisie ? (int)$saisie->auditeur_id : ($catAuditeurId ?: null),
            ];
        }
        return $result;
    }

    // ── buildPayload ──────────────────────────────────────────────
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $role           = $this->getRole($missionId,$auditor->id);
        $phaseAuditeurs = $this->getPhaseAuditeurs($assignmentId);
        $categories     = $form ? $this->loadCategoriesWithAffect($form->id) : $this->loadCategories();
        $criteres       = $form ? $this->loadCriteres($form->id) : [];

        $myCategories = array_keys(array_filter($categories,fn($c)=>(int)($c['auditeur_id']??0)===$auditor->id));

        $mission = DB::connection('tenant')->table('mission_programmation as mp')->where('mp.id',$missionId)
            ->select('mp.id','mp.code_mission','mp.libelle','mp.objectif','mp.date_debut','mp.date_fin','mp.lieux','mp.numero_fpm')
            ->first();

        $rctList = DB::connection('tenant')->table($this->table)->where('assignment_id',$assignmentId)
            ->select(['id','code','validation_status','fait_par','updated_at'])->orderByDesc('created_at')->get()->toArray();

        $formId=$form?->id??null;

        return array_merge(
            parent::buildPayload($missionId,$assignmentId,$auditor,$form),
            [
                'form'           => $form ? (array)$form : null,
                'categories'     => $categories,
                'criteres'       => $criteres,
                'myCategories'   => $myCategories,
                'phaseAuditeurs' => $phaseAuditeurs,
                'rctList'        => $rctList,
                'mission'        => $mission?(array)$mission:null,
                'currentAuditor' => ['id'=>$auditor->id,'audit_code'=>$auditor->audit_code,'last_name'=>$auditor->last_name,'first_name'=>$auditor->first_name,'role'=>$role],
                'canManage'      => in_array($role,['DM','CM']),
                'urlStore'          => route('auditor.ac.referentiel-transactions.store'),
                'urlUpdate'         => $formId?route('auditor.ac.referentiel-transactions.update',        $formId):null,
                'urlSoumettre'      => $formId?route('auditor.ac.referentiel-transactions.soumettre',     $formId):null,
                'urlValider'        => $formId?route('auditor.ac.referentiel-transactions.valider',       $formId):null,
                'urlUpdateCats'     => $formId?route('auditor.ac.referentiel-transactions.categories',    $formId):null,
                'urlSaveSaisie'     => $formId?route('auditor.ac.referentiel-transactions.saisie.save',   $formId):null,
                'urlUploadPreuve'   => $formId?route('auditor.ac.referentiel-transactions.upload-preuve', $formId):null,
                'urlDeletePreuve'   => $formId?route('auditor.ac.referentiel-transactions.delete-preuve', $formId):null,
                'urlIaSugg'         => route('auditor.ac.referentiel-transactions.ia-suggestion'),
                'urlIaAnalyse'      => $formId?route('auditor.ac.referentiel-transactions.ia-analyse',    $formId):null,
                'urlIndex'          => route('audit.ac.preparation.referentiel-transactions'),
                'backUrl'           => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
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
        } catch(\Exception $e){Log::error('[RCT] index: '.$e->getMessage());return back()->with('error',$e->getMessage());}
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
        } catch(\Exception $e){Log::error('[RCT] edit: '.$e->getMessage());return back()->with('error',$e->getMessage());}
    }

    // ── store ─────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $auditor=$this->getAuditor(); if(!$auditor) abort(403);
        $missionId=(int)$request->input('mission_id',0); $assignmentId=(int)$request->input('assignment_id',0);
        if(!$missionId||!$assignmentId) return response()->json(['success'=>false,'message'=>'Contexte manquant.'],422);
        $role=$this->getRole($missionId,$auditor->id);
        if(!$this->canAccess($missionId,$assignmentId,$auditor)) return response()->json(['success'=>false,'message'=>'Accès refusé.'],403);
        if(!in_array($role,['DM','CM'])) return response()->json(['success'=>false,'message'=>'Seuls DM/CM peuvent créer.'],403);
        $assignment=DB::connection('tenant')->table('mission_phase_assignments')->where('id',$assignmentId)->first();
        if(!$assignment||$assignment->status==='pending') return response()->json(['success'=>false,'message'=>'Démarrez la phase.'],422);
        $existing=DB::connection('tenant')->table($this->table)->where('assignment_id',$assignmentId)->first();
        if($existing) return response()->json(['success'=>true,'redirect'=>route('auditor.ac.referentiel-transactions.edit',$existing->id)]);

        $id=DB::connection('tenant')->table($this->table)->insertGetId(array_merge($this->formData($request,$auditor),[
            'assignment_id'=>$assignmentId,'mission_id'=>$missionId,
            'code'=>$this->genCode($missionId),'validation_status'=>'draft',
            'created_by'=>$auditor->id,'created_at'=>now(),'updated_at'=>now(),
        ]));

        // Créer lignes catégories avec affectations
        $catAffect=$request->input('cat_affectations',[]);
        $ordre=0;
        $categories=$this->loadCategories();
        foreach(array_keys($categories) as $code){
            DB::connection('tenant')->table('mission_phase_ref_transactions_categories')->insert([
                'rct_id'=>$id,'categorie_code'=>$code,'auditeur_id'=>$catAffect[$code]??null,'ordre'=>$ordre++,'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        // Pré-créer les lignes de saisie (vides) pour chaque critère du référentiel
        $refCriteres=$this->loadReferentielCriteres();
        foreach($refCriteres as $def){
            $audId=$catAffect[$def->categorie_code]??null;
            DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')->insert([
                'rct_id'=>$id,'ref_controle'=>$def->ref_controle,'categorie_code'=>$def->categorie_code,
                'note_preuves'=>null,'preuves_fichiers'=>'[]',
                'auditeur_id'=>$audId??null,'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        $this->log($assignmentId,$auditor->id,$role,'saved',null,'draft');
        return response()->json(['success'=>true,'message'=>'RCT créé.','redirect'=>route('auditor.ac.referentiel-transactions.edit',$id)]);
    }

    // ── update (infos générales) ──────────────────────────────────
    public function update(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) abort(403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) abort(404);
        $missionId=(int)($request->input('mission_id')??$row->mission_id); $assignmentId=(int)($request->input('assignment_id')??$row->assignment_id);
        $role=$this->getRole($missionId,$auditor->id);
        if(!$this->canAccess($missionId,$assignmentId,$auditor)) return response()->json(['success'=>false,'message'=>'Accès refusé.'],403);
        if(!$this->canEdit($row,$role)) return response()->json(['success'=>false,'message'=>'Non autorisé.'],403);
        DB::connection('tenant')->table($this->table)->where('id',$form)->update(array_merge($this->formData($request,$auditor),['synthese'=>$request->input('synthese'),'updated_at'=>now()]));
        $this->log($assignmentId,$auditor->id,$role,'saved',$row->validation_status,$row->validation_status);
        return response()->json(['success'=>true,'message'=>'RCT mis à jour.']);
    }

    // ── updateCategories — affectations DM/CM ────────────────────
    // PUT referentiel-transactions/{rct}/categories
    public function updateCategories(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$form)->first(); if(!$row) return response()->json(['error'=>'RCT introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])) return response()->json(['error'=>'Seuls DM/CM peuvent gérer les affectations'],403);
        if($row->validation_status==='validated') return response()->json(['error'=>'RCT validé'],403);

        $catAffect=$request->input('cat_affectations',[]);
        $categories=$this->loadCategories();
        foreach(array_keys($categories) as $code){
            $audId=$catAffect[$code]??null;
            DB::connection('tenant')->table('mission_phase_ref_transactions_categories')
                ->updateOrInsert(['rct_id'=>$form,'categorie_code'=>$code],['auditeur_id'=>$audId,'updated_at'=>now()]);
            // Mettre à jour les saisies existantes non assignées
            if($audId){
                DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
                    ->where('rct_id',$form)->where('categorie_code',$code)->whereNull('auditeur_id')
                    ->update(['auditeur_id'=>$audId,'updated_at'=>now()]);
            }
        }
        $cats=$this->loadCategoriesWithAffect($form);
        return response()->json(['success'=>true,'categories'=>$cats,'message'=>'Affectations enregistrées.']);
    }

    // ── saveSaisie — save immédiat note + fichiers ─────────────────
    // POST referentiel-transactions/{rct}/saisie
    // Body : ref_controle, note_preuves
    public function saveSaisie(Request $request, int $rct)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $row=DB::connection('tenant')->table($this->table)->where('id',$rct)->first();
        if(!$row||$row->validation_status==='validated') return response()->json(['error'=>'Non autorisé'],403);
        $ref=$request->input('ref_controle'); if(!$ref) return response()->json(['error'=>'ref_controle requis'],422);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);

        $saisie=DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rct)->where('ref_controle',$ref)->first();

        if(!$saisie) return response()->json(['error'=>'Saisie introuvable'],404);

        if(!in_array($role,['DM','CM'])&&(int)$saisie->auditeur_id!==$auditor->id)
            return response()->json(['error'=>'Accès refusé'],403);

        DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rct)->where('ref_controle',$ref)
            ->update(['note_preuves'=>$request->input('note_preuves'),'updated_at'=>now()]);

        return response()->json(['success'=>true,'message'=>'Saisie enregistrée.']);
    }

    // ── uploadPreuve ──────────────────────────────────────────────
    // POST referentiel-transactions/{rct}/upload-preuve
    // Multipart : ref_controle, file
    public function uploadPreuve(Request $request, int $rct)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $request->validate(['ref_controle'=>'required|string|max:30','file'=>'required|file|mimes:pdf,xlsx,xls,docx,doc,png,jpg,jpeg|max:10240']);
        $row=DB::connection('tenant')->table($this->table)->where('id',$rct)->first();
        if(!$row||$row->validation_status==='validated') return response()->json(['error'=>'Non autorisé'],403);
        $ref=$request->input('ref_controle');
        $saisie=DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rct)->where('ref_controle',$ref)->first();
        if(!$saisie) return response()->json(['error'=>'Saisie introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])&&(int)$saisie->auditeur_id!==$auditor->id) return response()->json(['error'=>'Accès refusé'],403);
        $file=$request->file('file');
        $storedPath=$file->store("rct/{$rct}/".str_replace(['/','\\'],['-','-'],$ref),self::DISK);
        $fileInfo=['name'=>$file->getClientOriginalName(),'path'=>$storedPath,'url'=>Storage::disk(self::DISK)->url($storedPath),'size'=>$file->getSize(),'mime'=>$file->getMimeType(),'uploaded_at'=>now()->toISOString(),'uploaded_by'=>$auditor->id];
        $existing=$saisie->preuves_fichiers?json_decode($saisie->preuves_fichiers,true)??[]:[];
        $existing[]=$fileInfo;
        DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rct)->where('ref_controle',$ref)
            ->update(['preuves_fichiers'=>json_encode($existing,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
        return response()->json(['success'=>true,'fichier'=>$fileInfo,'message'=>"Fichier '{$fileInfo['name']}' joint."]);
    }

    // ── deletePreuve ──────────────────────────────────────────────
    // DELETE referentiel-transactions/{rct}/delete-preuve
    // Body : ref_controle, path
    public function deletePreuve(Request $request, int $rct)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        $ref=$request->input('ref_controle'); $path=$request->input('path');
        if(!$ref||!$path) return response()->json(['error'=>'Paramètres manquants'],422);
        $row=DB::connection('tenant')->table($this->table)->where('id',$rct)->first();
        if(!$row||$row->validation_status==='validated') return response()->json(['error'=>'Non autorisé'],403);
        $saisie=DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rct)->where('ref_controle',$ref)->first();
        if(!$saisie) return response()->json(['error'=>'Saisie introuvable'],404);
        $role=$this->getRole((int)$row->mission_id,$auditor->id);
        if(!in_array($role,['DM','CM'])&&(int)$saisie->auditeur_id!==$auditor->id) return response()->json(['error'=>'Accès refusé'],403);
        Storage::disk(self::DISK)->delete($path);
        $fichiers=array_values(array_filter($saisie->preuves_fichiers?json_decode($saisie->preuves_fichiers,true)??[]:[], fn($f)=>$f['path']!==$path));
        DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')
            ->where('rct_id',$rct)->where('ref_controle',$ref)
            ->update(['preuves_fichiers'=>json_encode($fichiers,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
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
        $saisies=DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')->where('rct_id',$form)->get();
        foreach($saisies as $s){$f=$s->preuves_fichiers?json_decode($s->preuves_fichiers,true)??[]:[];foreach($f as $fi) Storage::disk(self::DISK)->delete($fi['path']??'');}
        DB::connection('tenant')->table('mission_phase_ref_transactions_saisies')->where('rct_id',$form)->delete();
        DB::connection('tenant')->table('mission_phase_ref_transactions_categories')->where('rct_id',$form)->delete();
        DB::connection('tenant')->table($this->table)->where('id',$form)->delete();
        $this->log((int)$row->assignment_id,$auditor->id,$role,'deleted',$row->validation_status,null);
        return response()->json(['success'=>true]);
    }

    // ── iaSuggestion (inchangé) ───────────────────────────────────
    public function iaSuggestion(Request $request)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        return response()->json(['success'=>false,'message'=>'Non configuré.']);
    }

    public function iaAnalyseGlobale(Request $request, int $form)
    {
        $auditor=$this->getAuditor(); if(!$auditor) return response()->json(['error'=>'Non autorisé'],403);
        return response()->json(['success'=>false,'message'=>'Non configuré.']);
    }
}