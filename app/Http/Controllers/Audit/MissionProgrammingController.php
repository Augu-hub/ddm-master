<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class MissionProgrammingController extends Controller
{
    // =========================================================================
    // MÉTHODES MÉTIER EXISTANTES (index, create, store, show, etc.)
    // =========================================================================

    public function index(Request $request)
    {
        $entityId = $request->get('entity_id', session('current_entity_id', 1));

        $missions = DB::table('mission_programmation as mp')
            ->select([
                'mp.*',
                DB::raw("TRIM(BOTH ', ' FROM COALESCE(GROUP_CONCAT(DISTINCT COALESCE(e.name, 'Entité inconnue') SEPARATOR ', '),'—')) as entities_list"),
                DB::raw("COALESCE(mb.montant_fixe, 0) as montant_fixe"),
                DB::raw("COALESCE(mb.montant_variable, 0) as montant_variable"),
                DB::raw("COALESCE(mb.devise, 'FCFA') as devise"),
                DB::raw("COALESCE(mb.montant_fixe, 0) + COALESCE(mb.montant_variable, 0) as budget_total"),
                DB::raw("DATE_FORMAT(mp.date_debut, '%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin,   '%d/%m/%Y') as date_fin_fr"),
                DB::raw("CASE WHEN mp.status='terminee' THEN 100 WHEN mp.status='annulee' THEN 0 WHEN mp.date_debut>CURDATE() THEN 0 WHEN mp.date_fin<CURDATE() AND mp.status!='terminee' THEN 100 WHEN DATEDIFF(mp.date_fin,mp.date_debut)=0 THEN 100 ELSE ROUND(LEAST((DATEDIFF(CURDATE(),mp.date_debut)/NULLIF(DATEDIFF(mp.date_fin,mp.date_debut),0))*100,99),0) END as progression"),
                DB::raw("DATEDIFF(mp.date_fin, mp.date_debut) + 1 as jours"),
            ])
            ->leftJoin('mission_programmation_entity as mpe', 'mp.id', '=', 'mpe.mission_programmation_id')
            ->leftJoin('entities as e', 'mpe.entity_id', '=', 'e.id')
            ->leftJoin('mission_budgets as mb', 'mp.id', '=', 'mb.mission_id')
            ->where(function ($q) use ($entityId) {
                $q->where('mpe.entity_id', $entityId)
                  ->orWhereNull('mpe.entity_id')
                  ->orWhereExists(function ($sub) use ($entityId) {
                      $sub->select(DB::raw(1))->from('mission_phase_auditeurs')
                          ->whereColumn('mp.id', 'mission_phase_auditeurs.mission_id')
                          ->whereRaw('JSON_CONTAINS(entites, ?)', [json_encode((string)$entityId)]);
                  });
            })
            ->groupBy('mp.id')->orderBy('mp.date_debut', 'desc')->get();

        $missionIds = $missions->pluck('id')->toArray();
        $affectationsRaw = [];
        if (!empty($missionIds)) {
            $affectationsRaw = DB::table('mission_phase_auditeurs as mpa')
                ->select(['mpa.mission_id','mpa.role','mpa.parent_auditeur_id','mpa.entites','mpa.id as affectation_id',
                    'a.id as auditeur_id','a.audit_code','a.first_name','a.last_name',
                    'mr.code as role_code','mr.libelle as role_libelle','mr.niveau as role_niveau',
                    'parent.audit_code as parent_code','parent.last_name as parent_last_name',
                    DB::raw("(SELECT COALESCE(SUM(montant),0) FROM mission_auditeur_budget_lines WHERE affectation_id=mpa.id) as budget_individuel"),
                ])
                ->join('auditors as a','mpa.auditeur_id','=','a.id')
                ->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')
                ->leftJoin('auditors as parent','mpa.parent_auditeur_id','=','parent.id')
                ->whereIn('mpa.mission_id', $missionIds)
                ->orderByRaw("COALESCE(mr.niveau,99) ASC")->orderBy('a.last_name')->get();
        }

        $equipesParMission = [];
        foreach ($affectationsRaw as $row) {
            $mid = $row->mission_id;
            if (!isset($equipesParMission[$mid])) $equipesParMission[$mid] = ['total'=>0,'membres'=>[]];
            $equipesParMission[$mid]['total']++;
            $equipesParMission[$mid]['membres'][] = [
                'auditeur_id'=>$row->auditeur_id,'audit_code'=>$row->audit_code,
                'first_name'=>$row->first_name,'last_name'=>$row->last_name,
                'role'=>$row->role??($row->role_code??null),'role_libelle'=>$row->role_libelle??$row->role,
                'role_niveau'=>$row->role_niveau??99,'parent_code'=>$row->parent_code,
                'parent_last_name'=>$row->parent_last_name,
                'budget_individuel'=>floatval($row->budget_individuel??0),
                'entites'=>json_decode($row->entites??'[]',true),
            ];
        }

        $budgetLignesParMission = [];
        if (!empty($missionIds)) {
            $lignes = DB::table('mission_budget_lines as mbl')
                ->leftJoin('mission_budget_categories as mbc','mbl.category_id','=','mbc.id')
                ->whereIn('mbl.mission_id',$missionIds)
                ->select(['mbl.mission_id','mbl.montant','mbl.custom_label','mbc.code as category_code',
                    'mbc.libelle as category_libelle',DB::raw("COALESCE(mbc.libelle,mbl.custom_label) as display_label")])
                ->orderBy('mbl.id')->get();
            foreach ($lignes as $ligne) {
                $budgetLignesParMission[$ligne->mission_id][] = [
                    'libelle'=>$ligne->display_label,'code'=>$ligne->category_code??null,
                    'montant'=>floatval($ligne->montant),'custom_label'=>$ligne->custom_label,
                ];
            }
        }

        $base = DB::table('mission_programmation as mp')
            ->leftJoin('mission_programmation_entity as mpe','mp.id','=','mpe.mission_programmation_id')
            ->where(function ($q) use ($entityId) {
                $q->where('mpe.entity_id',$entityId)->orWhereNull('mpe.entity_id')
                  ->orWhereExists(function ($sub) use ($entityId) {
                      $sub->select(DB::raw(1))->from('mission_phase_auditeurs')
                          ->whereColumn('mp.id','mission_phase_auditeurs.mission_id')
                          ->whereRaw('JSON_CONTAINS(entites,?)',[json_encode((string)$entityId)]);
                  });
            });

        $stats = [
            'total'=>(clone $base)->count(),'planifiees'=>(clone $base)->where('mp.status','planifiee')->count(),
            'en_cours'=>(clone $base)->where('mp.status','en_cours')->count(),
            'terminees'=>(clone $base)->where('mp.status','terminee')->count(),
            'annulees'=>(clone $base)->where('mp.status','annulee')->count(),
        ];

        $entities = DB::table('entities')->orderBy('name')->select('id','name')->get();
        return Inertia::render('dashboards/Audit/MissionProgramming/Index',[
            'missions'=>$missions->values(),'equipesParMission'=>$equipesParMission,
            'budgetLignesParMission'=>$budgetLignesParMission,'stats'=>$stats,
            'entities'=>$entities,'selectedEntityId'=>(int)$entityId,
            'filters'=>$request->only(['search','status','date_debut','date_fin']),
        ]);
    }

    public function create(Request $request)
    {
        $entityId = session('current_entity_id', 1);
        $missionId = $request->get('mission_id');
        $data = $this->getCommonData($entityId);
        if ($missionId) {
            $mission = DB::table('missions')->where('id',$missionId)->first();
            if ($mission) $data['preselectedMission'] = $mission;
        }
        return Inertia::render('dashboards/Audit/MissionProgramming/Create', $data);
    }

    public function getMissionEntities(Request $request, $missionId)
    {
        $mission = DB::table('missions')->where('id',$missionId)->first();
        if (!$mission) return response()->json(['success'=>false,'error'=>'Mission introuvable'],404);
        $auditMission = null;
        if (!empty($mission->fpm_number))
            $auditMission = DB::table('audit_missions')->where('code',$mission->fpm_number)->first();
        $entities = [];
        if ($auditMission) {
            $entities = DB::table('audit_mission_entities as ame')
                ->join('entities as e','ame.entity_id','=','e.id')
                ->where('ame.audit_mission_id',$auditMission->id)
                ->select('e.id','e.name','e.code_base')->orderBy('e.name')->get()
                ->map(fn($e)=>[
                    'entity_id'=>(int)$e->id,'entity_name'=>$e->name,'entity_code'=>$e->code_base,
                    'planned_start_date'=>$mission->planned_start_date,'planned_end_date'=>$mission->planned_end_date,
                ]);
        }
        return response()->json(['success'=>true,'entities'=>$entities,'mission'=>[
            'id'=>$mission->id,'code'=>$mission->code,'title'=>$mission->title,
            'fpm_number'=>$mission->fpm_number,'objective'=>$mission->objective,
            'planned_start_date'=>$mission->planned_start_date,'planned_end_date'=>$mission->planned_end_date,
        ]]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mission_id'=>'required|exists:missions,id',
            'code_mission'=>'required|string|max:50|unique:mission_programmation,code_mission',
            'code_programmation'=>'nullable|string|max:50',
            'libelle'=>'required|string|max:255',
            'fpm_number'=>'nullable|string|max:50',
            'objectif'=>'nullable|string',
            'lieux'=>'nullable|string|max:255',
            'date_debut'=>'required|date',
            'date_fin'=>'required|date|after_or_equal:date_debut',
            'entity_periods'=>'required|array|min:1',
            'entity_periods.*.entity_id'=>'required|exists:entities,id',
            'entity_periods.*.planned_start_date'=>'required|date',
            'entity_periods.*.planned_end_date'=>'required|date|after_or_equal:entity_periods.*.planned_start_date',
            'montant_fixe'=>'nullable|numeric|min:0',
            'budget_lines'=>'nullable|array',
            'budget_lines.*.category_id'=>'nullable|exists:mission_budget_categories,id',
            'budget_lines.*.custom_label'=>'nullable|string|max:255|required_without:budget_lines.*.category_id',
            'budget_lines.*.montant'=>'nullable|numeric|min:0',
            'auditeurs'=>'nullable|array',
            'auditeurs.*.auditeur_id'=>'required_with:auditeurs|exists:auditors,id',
            'auditeurs.*.role'=>'nullable|string|max:50',
            'auditeurs.*.parent_auditeur_id'=>'nullable|exists:auditors,id',
            'auditeurs.*.affectations'=>'nullable|array',
            'auditeurs.*.affectations.*'=>'exists:entities,id',
            'auditeurs.*.budget_lines'=>'nullable|array',
            'auditeurs.*.budget_lines.*.entity_id'=>'required_with:auditeurs.*.budget_lines|exists:entities,id',
            'auditeurs.*.budget_lines.*.category_id'=>'nullable|exists:mission_budget_categories,id',
            'auditeurs.*.budget_lines.*.custom_label'=>'nullable|string|max:255|required_without:auditeurs.*.budget_lines.*.category_id',
            'auditeurs.*.budget_lines.*.montant'=>'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) return back()->withErrors($validator->errors());

        $auditeurs   = $request->auditeurs ?? [];
        $auditeurIds = collect($auditeurs)->pluck('auditeur_id')->filter()->toArray();
        $doublons = collect($auditeurs)->groupBy('auditeur_id')->filter(fn($g)=>$g->count()>1)->keys();
        if ($doublons->isNotEmpty()) return back()->withErrors(['auditeurs'=>'Un même auditeur ne peut être affecté plusieurs fois : IDs '.$doublons->implode(', ')]);
        if (collect($auditeurs)->where('role','DM')->count()>1) return back()->withErrors(['auditeurs'=>'Un seul Directeur de Mission (DM) peut être désigné.']);
        if (collect($auditeurs)->where('role','CM')->count()>1) return back()->withErrors(['auditeurs'=>'Un seul Chef de Mission (CM) peut être désigné.']);
        foreach ($auditeurs as $aff) {
            if (empty($aff['auditeur_id'])) continue;
            $role=$aff['role']??null; $parentId=$aff['parent_auditeur_id']??null;
            if ($role==='AS') {
                if (!$parentId) return back()->withErrors(['auditeurs'=>'Un AS doit avoir un CM comme parent.']);
                $parent=collect($auditeurs)->firstWhere('auditeur_id',$parentId);
                if (!$parent||($parent['role']??null)!=='CM') return back()->withErrors(['auditeurs'=>'Le parent d\'un AS doit être un CM.']);
            }
            if ($role==='AJ') {
                if (!$parentId) return back()->withErrors(['auditeurs'=>'Un AJ doit avoir un AS comme parent.']);
                $parent=collect($auditeurs)->firstWhere('auditeur_id',$parentId);
                if (!$parent||($parent['role']??null)!=='AS') return back()->withErrors(['auditeurs'=>'Le parent d\'un AJ doit être un AS.']);
            }
            if ($parentId&&!in_array($parentId,$auditeurIds)) return back()->withErrors(['auditeurs'=>"L'auditeur parent (ID $parentId) n'est pas dans la liste."]);
        }

        DB::beginTransaction();
        try {
            $userId=$Auth=Auth::id(); $entityPeriods=$request->entity_periods;
            $allStarts=collect($entityPeriods)->pluck('planned_start_date')->filter()->sort()->values();
            $allEnds=collect($entityPeriods)->pluck('planned_end_date')->filter()->sort()->values();
            $globalStart=$allStarts->first()??$request->date_debut;
            $globalEnd=$allEnds->last()??$request->date_fin;
            $mainId=DB::table('mission_programmation')->insertGetId([
                'code_mission'=>$request->code_mission,'libelle'=>$request->libelle,
                'numero_fpm'=>$request->fpm_number,'objectif'=>$request->objectif,
                'mission_id'=>$request->mission_id,'lieux'=>$request->lieux,
                'date_debut'=>$globalStart,'date_fin'=>$globalEnd,
                'duree'=>max(1,(int)ceil(abs(strtotime($globalEnd)-strtotime($globalStart))/86400)+1),
                'programme'=>$request->code_programmation,'status'=>'planifiee',
                'created_by'=>$userId,'created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach ($entityPeriods as $ep) {
                DB::table('mission_programmation_entity')->insert([
                    'mission_programmation_id'=>$mainId,'entity_id'=>$ep['entity_id'],
                    'date_debut'=>$ep['planned_start_date'],'date_fin'=>$ep['planned_end_date'],
                ]);
            }
            $totalVariable=0;
            foreach ((array)$request->budget_lines as $line) {
                $montant=floatval($line['montant']??0); $totalVariable+=$montant;
                $d=['mission_id'=>$mainId,'montant'=>$montant,'created_at'=>now(),'updated_at'=>now()];
                if (!empty($line['category_id'])) { $d['category_id']=$line['category_id']; $d['custom_label']=null; }
                else { $d['category_id']=null; $d['custom_label']=$line['custom_label']??'Ligne personnalisée'; }
                DB::table('mission_budget_lines')->insert($d);
            }
            $budgetTypeId=DB::table('budget_types')->where('is_active',1)->value('id');
            if (!$budgetTypeId) $budgetTypeId=DB::table('budget_types')->insertGetId(['code'=>'DEFAULT','libelle'=>'Budget standard','description'=>'Type créé automatiquement','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('mission_budgets')->insert(['mission_id'=>$mainId,'budget_type_id'=>$budgetTypeId,'montant_fixe'=>floatval($request->montant_fixe??0),'est_variable'=>$totalVariable>0?1:0,'montant_variable'=>$totalVariable>0?$totalVariable:0,'devise'=>'FCFA','created_by'=>$userId,'created_at'=>now(),'updated_at'=>now()]);
            if (!empty($request->auditeurs)) {
                $phaseDefaut=DB::table('mission_codephases')->where('is_active',1)->orderBy('ordre')->value('id');
                foreach ($request->auditeurs as $aff) {
                    if (empty($aff['auditeur_id'])) continue;
                    $roleId=null;
                    if ($roleCode=$aff['role']??null) $roleId=DB::table('mission_roles')->where('code',$roleCode)->where('is_active',1)->value('id');
                    $parentId=(!empty($aff['parent_auditeur_id'])&&in_array($aff['parent_auditeur_id'],$auditeurIds))?$aff['parent_auditeur_id']:null;
                    $affectedEntityIds=array_values(array_filter($aff['affectations']??[]));
                    if (!empty($affectedEntityIds)) {
                        $affectationId=DB::table('mission_phase_auditeurs')->insertGetId(['mission_id'=>$mainId,'phase_id'=>$phaseDefaut,'auditeur_id'=>$aff['auditeur_id'],'role'=>$roleCode,'role_id'=>$roleId,'parent_auditeur_id'=>$parentId,'entites'=>json_encode(array_map('intval',$affectedEntityIds)),'date_affectation'=>now()->toDateString(),'affecte_par'=>$userId,'created_at'=>now(),'updated_at'=>now()]);
                        foreach (($aff['budget_lines']??[]) as $line) {
                            $montant=floatval($line['montant']??0);
                            if ($montant<=0||!in_array($line['entity_id'],$affectedEntityIds)) continue;
                            $d=['affectation_id'=>$affectationId,'mission_id'=>$mainId,'auditeur_id'=>$aff['auditeur_id'],'entity_id'=>$line['entity_id'],'montant'=>$montant,'created_at'=>now(),'updated_at'=>now()];
                            if (!empty($line['category_id'])) { $d['category_id']=$line['category_id']; $d['custom_label']=null; }
                            else { $d['category_id']=null; $d['custom_label']=$line['custom_label']??'Ligne personnalisée'; }
                            DB::table('mission_auditeur_budget_lines')->insert($d);
                        }
                    }
                }
            }
            DB::commit();
            // Provisionner immédiatement les phases du tenant (mission_phases +
            // mission_phase_assignments) au lieu d'attendre l'ouverture d'une phase.
            // Idempotent, try/catch interne : ne bloque jamais la création.
            \App\Services\Audit\PhaseSyncService::ensureMissionAssignments((int) $mainId);
            return redirect()->route('audit.core.programmation-missions.index')->with('success','Mission programmée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error'=>'Erreur : '.$e->getMessage()]);
        }
    }

    public function show($id)
    {
        $mission = DB::table('mission_programmation as mp')
            ->select(['mp.*',DB::raw("GROUP_CONCAT(DISTINCT e.name SEPARATOR ', ') as entities_list"),DB::raw("COALESCE(mb.montant_fixe,0) as montant_fixe"),DB::raw("COALESCE(mb.montant_variable,0) as montant_variable"),DB::raw("COALESCE(mb.devise,'FCFA') as devise"),DB::raw("COALESCE(mb.montant_fixe,0)+COALESCE(mb.montant_variable,0) as budget_total"),DB::raw("DATE_FORMAT(mp.date_debut,'%d/%m/%Y') as date_debut_fr"),DB::raw("DATE_FORMAT(mp.date_fin,'%d/%m/%Y') as date_fin_fr")])
            ->leftJoin('mission_programmation_entity as mpe','mp.id','=','mpe.mission_programmation_id')
            ->leftJoin('entities as e','mpe.entity_id','=','e.id')
            ->leftJoin('missions as m','mp.mission_id','=','m.id')
            ->leftJoin('mission_budgets as mb','mp.id','=','mb.mission_id')
            ->where('mp.id',$id)->groupBy('mp.id')->first();
        if (!$mission) abort(404);
        $entities=DB::table('mission_programmation_entity as mpe')->join('entities as e','mpe.entity_id','=','e.id')->where('mpe.mission_programmation_id',$id)->select('e.id','e.name')->get();
        $auditeurs=DB::table('mission_phase_auditeurs as mpa')->select(['a.id as auditeur_id','a.audit_code','a.first_name','a.last_name','mpa.role','mpa.role_id','mpa.parent_auditeur_id','mpa.entites','mpa.id as affectation_id','mr.libelle as role_libelle','mr.niveau as role_niveau','parent.audit_code as parent_code','parent.last_name as parent_last_name',DB::raw("(SELECT COALESCE(SUM(montant),0) FROM mission_auditeur_budget_lines WHERE affectation_id=mpa.id) as budget_individuel")])->join('auditors as a','mpa.auditeur_id','=','a.id')->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')->leftJoin('auditors as parent','mpa.parent_auditeur_id','=','parent.id')->where('mpa.mission_id',$id)->orderByRaw("COALESCE(mr.niveau,99) ASC")->orderBy('a.last_name')->get();
        $budgetLines=DB::table('mission_budget_lines as mbl')->leftJoin('mission_budget_categories as mbc','mbl.category_id','=','mbc.id')->where('mbl.mission_id',$id)->select(['mbl.*','mbc.code as category_code','mbc.libelle as category_libelle',DB::raw("COALESCE(mbc.libelle,mbl.custom_label) as display_label")])->orderBy('mbl.id')->get();
        $budgetParAuditeur=DB::table('mission_auditeur_budget_lines as mabl')->leftJoin('mission_budget_categories as mbc','mabl.category_id','=','mbc.id')->join('auditors as a','mabl.auditeur_id','=','a.id')->leftJoin('entities as e','mabl.entity_id','=','e.id')->where('mabl.mission_id',$id)->select(['mabl.*','a.audit_code','a.last_name','a.first_name','e.name as entity_name','mbc.libelle as category_libelle',DB::raw("COALESCE(mbc.libelle,mabl.custom_label) as display_label")])->orderBy('a.last_name')->orderBy('e.name')->orderBy('mabl.id')->get();
        return Inertia::render('dashboards/Audit/MissionProgramming/Show',['mission'=>$mission,'entities'=>$entities,'auditeurs'=>$auditeurs,'budgetLines'=>$budgetLines,'budgetParAuditeur'=>$budgetParAuditeur,'roles'=>DB::table('mission_roles')->where('is_active',1)->orderBy('niveau')->get()]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status'=>'required|in:planifiee,en_cours,terminee,annulee']);
        DB::table('mission_programmation')->where('id',$id)->update(['status'=>$request->status,'updated_by'=>Auth::id(),'updated_at'=>now()]);
        return back()->with('success','Statut mis à jour.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            DB::table('mission_programmation')->where('id',$id)->delete();
            DB::commit();
            return redirect()->route('audit.core.programmation-missions.index')->with('success','Mission supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error'=>'Erreur suppression : '.$e->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        $entityId = $request->get('entity_id', session('current_entity_id', 1));
        $missions = DB::table('mission_programmation as mp')
            ->leftJoin('mission_programmation_entity as mpe','mp.id','=','mpe.mission_programmation_id')
            ->leftJoin('entities as e','mpe.entity_id','=','e.id')
            ->leftJoin('missions as m','mp.mission_id','=','m.id')
            ->leftJoin('mission_budgets as mb','mp.id','=','mb.mission_id')
            ->select(['mp.*',DB::raw("GROUP_CONCAT(DISTINCT e.name SEPARATOR ', ') as entities_list"),'m.code as mission_ref_code','m.title as mission_ref_title',DB::raw("COALESCE(mb.montant_fixe,0)+COALESCE(mb.montant_variable,0) as budget_total"),DB::raw("DATE_FORMAT(mp.date_debut,'%d/%m/%Y') as date_debut_fr"),DB::raw("DATE_FORMAT(mp.date_fin,'%d/%m/%Y') as date_fin_fr")])
            ->where(function ($q) use ($entityId) { $q->where('mpe.entity_id',$entityId)->orWhereNull('mpe.entity_id'); })
            ->groupBy('mp.id')->orderBy('mp.date_debut','desc')->get();
        return response()->json($missions);
    }

    private function getCommonData(int $entityId): array
    {
        Log::info('Début getCommonData',['entityId'=>$entityId]);
        $totalMissions=DB::table('missions')->count();
        $missions=DB::table('missions as m')->leftJoin('mission_types as mt','m.mission_type_id','=','mt.id')->whereIn('m.status',['brouillon','planifiée','proposed'])->orderBy('m.code')->select(['m.id','m.code','m.title','m.fpm_number','m.objective','m.domain','m.priority','m.status','m.planned_start_date','m.planned_end_date','mt.code as type_code','mt.label as type_label'])->get();
        if ($missions->isEmpty()) $missions=DB::table('missions as m')->leftJoin('mission_types as mt','m.mission_type_id','=','mt.id')->orderBy('m.code')->select(['m.id','m.code','m.title','m.fpm_number','m.objective','m.domain','m.priority','m.status','m.planned_start_date','m.planned_end_date','mt.code as type_code','mt.label as type_label'])->limit(10)->get();
        $missionsArray=$missions->map(fn($m)=>['id'=>$m->id,'code'=>$m->code,'fpm_number'=>$m->fpm_number,'title'=>$m->title,'objective'=>$m->objective,'domain'=>$m->domain,'priority'=>$m->priority,'status'=>$m->status,'planned_start_date'=>$m->planned_start_date,'planned_end_date'=>$m->planned_end_date,'type_code'=>$m->type_code,'type_label'=>$m->type_label])->values();
        $missionCompetencies=[];
        if ($missions->isNotEmpty()) {
            $missionIds=$missions->pluck('id')->toArray();
            DB::table('mission_competency as mc')->join('competencies as c','mc.competency_id','=','c.id')->whereIn('mc.mission_id',$missionIds)->where('c.status','active')->whereNull('c.deleted_at')->select(['mc.mission_id','mc.competency_id',DB::raw('COALESCE(mc.minimum_level,c.level_required) as minimum_level'),'c.name as competency_name','c.code as competency_code'])->get()->each(function($row) use (&$missionCompetencies){ $missionCompetencies[(int)$row->mission_id][]=['competency_id'=>(int)$row->competency_id,'minimum_level'=>(int)$row->minimum_level,'competency_name'=>$row->competency_name,'competency_code'=>$row->competency_code]; });
        }
        $auditorCompetencies=[];
        DB::table('auditor_competencies as ac')->join('competencies as c','ac.competency_id','=','c.id')->where('c.status','active')->whereNull('c.deleted_at')->select(['ac.auditor_id','ac.competency_id','ac.level','ac.is_primary'])->get()->each(function($row) use (&$auditorCompetencies){ $auditorCompetencies[(int)$row->auditor_id][]=['competency_id'=>(int)$row->competency_id,'level'=>(int)$row->level,'is_primary'=>(bool)$row->is_primary]; });
        $globalUnavailabilities=DB::table('global_unavailabilities as gu')->leftJoin('unavailability_types as ut','gu.type','=','ut.code')->where('gu.is_active',1)->whereNull('gu.deleted_at')->select(['gu.id','gu.name','gu.reason','gu.type','gu.date_start','gu.date_end',DB::raw("COALESCE(ut.name,gu.type) as type_name"),DB::raw("COALESCE(ut.color,'#f59e0b') as type_color")])->orderBy('gu.date_start')->get()->map(fn($row)=>['id'=>$row->id,'name'=>$row->name,'reason'=>$row->reason,'type'=>$row->type,'type_name'=>$row->type_name,'type_color'=>$row->type_color,'date_start'=>$row->date_start,'date_end'=>$row->date_end,'is_global'=>true])->values()->toArray();
        $auditorUnavailabilities=[];
        DB::table('auditor_unavailabilities as au')->leftJoin('unavailability_types as ut','au.type','=','ut.code')->where('au.is_approved',1)->whereNull('au.deleted_at')->select(['au.auditor_id','au.date_start','au.date_end','au.type','au.reason',DB::raw("COALESCE(ut.name,au.type) as type_name"),DB::raw("COALESCE(ut.color,'#f59e0b') as type_color")])->get()->each(function($row) use (&$auditorUnavailabilities){ $auditorUnavailabilities[(int)$row->auditor_id][]=['date_start'=>$row->date_start,'date_end'=>$row->date_end,'type'=>$row->type,'type_name'=>$row->type_name,'type_color'=>$row->type_color,'reason'=>$row->reason,'is_global'=>false]; });
        $entities=DB::table('entities')->orderBy('name')->select('id','name','code_base')->get();
        return ['auditeurs'=>DB::table('auditors')->where('status','active')->whereNull('deleted_at')->orderBy('last_name')->get()->map(fn($a)=>['id'=>$a->id,'audit_code'=>$a->audit_code,'first_name'=>$a->first_name,'last_name'=>$a->last_name,'email'=>$a->email??null,'status'=>$a->status]),'phases'=>DB::table('mission_codephases')->where('is_active',1)->orderBy('ordre')->get(),'typesBudget'=>DB::table('budget_types')->where('is_active',1)->get(),'roles'=>DB::table('mission_roles')->where('is_active',1)->orderBy('niveau')->orderBy('ordre')->get(),'budgetCategories'=>DB::table('mission_budget_categories')->where('is_active',1)->orderBy('ordre')->get()->map(fn($c)=>['id'=>$c->id,'code'=>$c->code,'libelle'=>$c->libelle,'montant_defaut'=>isset($c->montant_defaut)?(float)$c->montant_defaut:0]),'missions'=>$missionsArray,'entities'=>$entities,'missionCompetencies'=>$missionCompetencies,'auditorCompetencies'=>$auditorCompetencies,'auditorUnavailabilities'=>$auditorUnavailabilities,'globalUnavailabilities'=>$globalUnavailabilities,'newCode'=>$this->generateMissionCode()];
    }

    private function generateMissionCode(): string
    {
        $prefix='MSS-'.date('ym').'-';
        $count=DB::table('mission_programmation')->whereYear('created_at',date('Y'))->whereMonth('created_at',date('m'))->count();
        return $prefix.str_pad($count+1,3,'0',STR_PAD_LEFT);
    }

    // =========================================================================
    // CONSTANTES COULEURS & STYLES EXPORT EXCEL
    // =========================================================================

    private const MONTH_COLORS = [
        1=>'1D4ED8',2=>'0369A1',3=>'0F766E',4=>'15803D',5=>'65A30D',6=>'CA8A04',
        7=>'C2410C',8=>'B91C1C',9=>'9333EA',10=>'1D4ED8',11=>'0369A1',12=>'0F766E',
    ];

    private const MONTH_LABELS_FR = [
        1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',
        7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc',
    ];

    private const STATUS_COLORS = [
        'planifiee'=>['FEF3C7','D97706'],
        'en_cours' =>['DBEAFE','1E40AF'],
        'terminee' =>['D1FAE5','059669'],
        'annulee'  =>['FEE2E2','DC2626'],
    ];

    private const STATUS_LABELS = [
        'planifiee'=>'Planifiée','en_cours'=>'En cours',
        'terminee'=>'Terminée','annulee'=>'Annulée',
    ];

    // =========================================================================
    // HELPERS STYLE
    // =========================================================================

    private function styleHeader(string $bg='0F172A', string $fg='FFFFFF', int $size=9, bool $bold=true): array
    {
        return [
            'font'      => ['bold'=>$bold,'size'=>$size,'color'=>['rgb'=>$fg],'name'=>'Arial'],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'C4C9D4']]],
        ];
    }

    private function styleData(
        string $bg='FFFFFF', string $fg='334155', int $size=9, bool $bold=false,
        string $hAlign=Alignment::HORIZONTAL_LEFT, bool $wrap=false
    ): array {
        return [
            'font'      => ['bold'=>$bold,'size'=>$size,'color'=>['rgb'=>$fg],'name'=>'Arial'],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
            'alignment' => ['horizontal'=>$hAlign,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>$wrap],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'E2E8F0']]],
        ];
    }

    private function styleGanttBar(): array
    {
        return [
            'font'      => ['bold'=>true,'size'=>8,'color'=>['rgb'=>'FFFFFF'],'name'=>'Arial'],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E3A5F']],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true,'indent'=>1],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'2D4A6B']]],
        ];
    }

    private function applyStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws, string $range, array $style): void
    {
        $ws->getStyle($range)->applyFromArray($style);
    }

    private function mergeWrite(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws, string $range, mixed $value, array $style): void
    {
        $ws->mergeCells($range);
        [$start] = explode(':', $range);
        $ws->getCell($start)->setValue($value);
        $this->applyStyle($ws, $range, $style);
    }

    private function col(int $n): string
    {
        return Coordinate::stringFromColumnIndex($n);
    }

    /**
     * Calcule la hauteur de ligne optimale selon le contenu multi-ligne.
     * base = hauteur ligne simple, lineHeight = points par ligne.
     */
    private function calcRowHeight(string $text, int $colWidthChars, float $base=18, float $lineHeight=14): float
    {
        if (empty(trim($text))) return $base;
        // Estime le nombre de lignes selon la largeur colonne
        $lines = explode("\n", $text);
        $total = 0;
        foreach ($lines as $line) {
            $charsPerLine = max(1, (int)($colWidthChars * 1.4));
            $total += max(1, (int)ceil(mb_strlen($line) / $charsPerLine));
        }
        return max($base, $total * $lineHeight + 4);
    }

    /**
     * Formate la liste des auditeurs d'une mission pour affichage Excel.
     * Retourne ex: "KOFFI Augustin (DM)\nMENSAH Bernard (CM)"
     */
    private function formatAuditeurs(array $team, bool $allMembers=false): string
    {
        if (empty($team)) return '';
        // Trier par rôle niveau
        $sorted = collect($team)->sortBy('role')->values();
        if (!$allMembers) {
            // Prendre seulement le chef (DM ou CM ou 1er)
            $chef = $sorted->first();
            $nom = trim(($chef['nom'] ?? ''));
            $role = $chef['role'] ?? '';
            if ($role) return "{$nom} ({$role})";
            return $nom;
        }
        // Tous les membres
        return $sorted->map(function($m) {
            $nom  = trim($m['nom'] ?? '');
            $role = $m['role'] ?? '';
            return $role ? "{$nom} ({$role})" : $nom;
        })->implode("\n");
    }

    // =========================================================================
    // RÉCUPÉRATION DONNÉES EXPORT
    // =========================================================================

    private function fetchAllMissionsForExport(Request $request): array
    {
        $search    = trim($request->get('search', ''));
        $status    = $request->get('status', '');
        $dateDebut = $request->get('date_debut', '');
        $dateFin   = $request->get('date_fin', '');
        $year      = (int) $request->get('year', date('Y'));

        $query = DB::table('mission_programmation as mp')
            ->select([
                'mp.id','mp.code_mission','mp.numero_fpm','mp.libelle','mp.objectif',
                'mp.lieux','mp.date_debut','mp.date_fin','mp.duree','mp.status','mp.programme',
                DB::raw("TRIM(BOTH ', ' FROM COALESCE(GROUP_CONCAT(DISTINCT COALESCE(e.name,'Entité ?') ORDER BY e.name SEPARATOR ', '),'—')) as entities_list"),
                DB::raw("COALESCE(mb.montant_fixe,0) as montant_fixe"),
                DB::raw("COALESCE(mb.montant_variable,0) as montant_variable"),
                DB::raw("COALESCE(mb.devise,'FCFA') as devise"),
                DB::raw("COALESCE(mb.montant_fixe,0)+COALESCE(mb.montant_variable,0) as budget_total"),
                DB::raw("DATE_FORMAT(mp.date_debut,'%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin,'%d/%m/%Y') as date_fin_fr"),
                DB::raw("MONTH(mp.date_debut) as mois_debut"),
                DB::raw("YEAR(mp.date_debut) as annee_debut"),
                DB::raw("CASE WHEN mp.status='terminee' THEN 100 WHEN mp.status='annulee' THEN 0 WHEN mp.date_debut>CURDATE() THEN 0 WHEN mp.date_fin<CURDATE() AND mp.status!='terminee' THEN 100 WHEN DATEDIFF(mp.date_fin,mp.date_debut)=0 THEN 100 ELSE ROUND(LEAST((DATEDIFF(CURDATE(),mp.date_debut)/NULLIF(DATEDIFF(mp.date_fin,mp.date_debut),0))*100,99),0) END as progression"),
                'mt.code as type_code','mt.label as type_label',
            ])
            ->leftJoin('mission_programmation_entity as mpe','mp.id','=','mpe.mission_programmation_id')
            ->leftJoin('entities as e','mpe.entity_id','=','e.id')
            ->leftJoin('mission_budgets as mb','mp.id','=','mb.mission_id')
            ->leftJoin('missions as m','mp.mission_id','=','m.id')
            ->leftJoin('mission_types as mt','m.mission_type_id','=','mt.id')
            ->groupBy('mp.id','mp.code_mission','mp.numero_fpm','mp.libelle','mp.objectif','mp.lieux','mp.date_debut','mp.date_fin','mp.duree','mp.status','mp.programme','mb.montant_fixe','mb.montant_variable','mb.devise','mt.code','mt.label')
            ->orderBy('mp.date_debut','asc');

        if ($year)      $query->whereYear('mp.date_debut', $year);
        if ($search)    $query->where(fn($q)=>$q->where('mp.code_mission','like',"%{$search}%")->orWhere('mp.libelle','like',"%{$search}%")->orWhere('mp.numero_fpm','like',"%{$search}%"));
        if ($status)    $query->where('mp.status', $status);
        if ($dateDebut) $query->where('mp.date_debut', '>=', $dateDebut);
        if ($dateFin)   $query->where('mp.date_fin',   '<=', $dateFin);

        $missions   = $query->get();
        $missionIds = $missions->pluck('id')->toArray();

        // ── Équipes (NOMS COMPLETS + rôle) ────────────────────────────────────
        $equipesParMission = [];
        if (!empty($missionIds)) {
            DB::table('mission_phase_auditeurs as mpa')
                ->select([
                    'mpa.mission_id',
                    'a.last_name','a.first_name','a.audit_code',
                    'mpa.role',
                    'mr.libelle as role_libelle',
                    DB::raw("COALESCE(mr.niveau,99) as role_niveau"),
                    DB::raw("(SELECT COALESCE(SUM(montant),0) FROM mission_auditeur_budget_lines WHERE affectation_id=mpa.id) as budget_individuel"),
                ])
                ->join('auditors as a','mpa.auditeur_id','=','a.id')
                ->leftJoin('mission_roles as mr','mpa.role_id','=','mr.id')
                ->whereIn('mpa.mission_id',$missionIds)
                ->orderByRaw('COALESCE(mr.niveau,99) ASC')
                ->orderBy('a.last_name')
                ->get()
                ->each(function($row) use (&$equipesParMission) {
                    $mid = $row->mission_id;
                    if (!isset($equipesParMission[$mid])) $equipesParMission[$mid] = [];
                    $equipesParMission[$mid][] = [
                        // ► NOM COMPLET en majuscule NOM + Prénom
                        'nom'               => strtoupper($row->last_name).' '.ucfirst(strtolower($row->first_name)),
                        'nom_court'         => strtoupper($row->last_name).' '.mb_substr(ucfirst(strtolower($row->first_name)),0,1).'.',
                        'code'              => $row->audit_code,
                        'role'              => $row->role ?? '—',
                        'role_libelle'      => $row->role_libelle ?? $row->role ?? '—',
                        'role_niveau'       => (int)($row->role_niveau ?? 99),
                        'budget_individuel' => floatval($row->budget_individuel),
                    ];
                });
        }

        // ── Budget lignes ─────────────────────────────────────────────────────
        $budgetLignesParMission = [];
        if (!empty($missionIds)) {
            DB::table('mission_budget_lines as mbl')
                ->leftJoin('mission_budget_categories as mbc','mbl.category_id','=','mbc.id')
                ->whereIn('mbl.mission_id',$missionIds)
                ->select(['mbl.mission_id','mbl.montant',DB::raw("COALESCE(mbc.libelle,mbl.custom_label,'Divers') as libelle")])
                ->orderBy('mbl.id')->get()
                ->each(function($row) use (&$budgetLignesParMission) {
                    $budgetLignesParMission[$row->mission_id][] = ['libelle'=>$row->libelle,'montant'=>floatval($row->montant)];
                });
        }

        // ── Entités ───────────────────────────────────────────────────────────
        $allEntities = DB::table('entities')->orderBy('name')->pluck('name','id')->toArray();

        $missionsByEntity = [];
        if (!empty($missionIds)) {
            DB::table('mission_programmation_entity as mpe')
                ->join('entities as e','mpe.entity_id','=','e.id')
                ->whereIn('mpe.mission_programmation_id',$missionIds)
                ->select('mpe.mission_programmation_id as mission_id','e.id as entity_id','e.name as entity_name')
                ->orderBy('e.name')->get()
                ->each(function($row) use (&$missionsByEntity) {
                    $eid = $row->entity_id;
                    if (!isset($missionsByEntity[$eid])) $missionsByEntity[$eid]=['name'=>$row->entity_name,'mission_ids'=>[]];
                    $missionsByEntity[$eid]['mission_ids'][] = $row->mission_id;
                });
        }

        return compact('missions','equipesParMission','budgetLignesParMission','allEntities','missionsByEntity','year');
    }

    // =========================================================================
    // EXPORT EXCEL PRINCIPAL
    // =========================================================================

    public function exportExcel(Request $request)
    {
        $data             = $this->fetchAllMissionsForExport($request);
        $missions         = $data['missions'];
        $equipes          = $data['equipesParMission'];
        $budgetLignes     = $data['budgetLignesParMission'];
        $allEntities      = $data['allEntities'];
        $missionsByEntity = $data['missionsByEntity'];
        $year             = $data['year'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Cabinet KEKELI – DIADDEM')
            ->setTitle("Plan d'Audit {$year}")
            ->setSubject("Plan d'Audit Annuel Toutes Entités")
            ->setDescription("Exporté depuis le module de Programmation des Missions");

        $ws1 = $spreadsheet->getActiveSheet()->setTitle('Par Entités');
        $ws2 = $spreadsheet->createSheet()->setTitle('Par Mission');
        $ws3 = $spreadsheet->createSheet()->setTitle('DDM – Plan Directeur');
        $ws4 = $spreadsheet->createSheet()->setTitle('Tableau de Bord');

        $this->buildSheetParEntites($ws1, $missions, $equipes, $budgetLignes, $missionsByEntity, $allEntities, $year);
        $this->buildSheetParMission($ws2, $missions, $equipes, $year);
        $this->buildSheetDDM($ws3, $missions, $equipes, $year);
        $this->buildSheetTableauDeBord($ws4, $missions, $equipes, $budgetLignes, $year);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = "Plan_Audit_{$year}_Complet_".date('Ymd_His').".xlsx";
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(
            function() use ($writer) { $writer->save('php://output'); },
            $filename,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control'       => 'max-age=0',
                'Pragma'              => 'public',
            ]
        );
    }

    // =========================================================================
    // ONGLET 1 – PAR ENTITÉS
    // Colonnes fixes : A=# | B=Entité/Périmètre | C=Code | D=Libellé |
    //                  E=Fréquence | F=Jrs | G..R=Mois 1..12 | S=Équipe | T=Statut
    // =========================================================================

    private function buildSheetParEntites(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
        $missions, $equipes, $budgetLignes, $missionsByEntity, $allEntities, int $year
    ): void {
        $ws->setShowGridlines(false);

        // ── Largeurs colonnes ─────────────────────────────────────────────────
        $ws->getColumnDimension('A')->setWidth(5);    // #
        $ws->getColumnDimension('B')->setWidth(28);   // Entité
        $ws->getColumnDimension('C')->setWidth(14);   // Code mission
        $ws->getColumnDimension('D')->setWidth(36);   // Libellé
        $ws->getColumnDimension('E')->setWidth(12);   // Fréquence
        $ws->getColumnDimension('F')->setWidth(5);    // Jrs

        // Mois G..R (col 7..18) — largeur adaptée pour nom auditeur + retour ligne
        for ($m = 1; $m <= 12; $m++) {
            $ws->getColumnDimension($this->col(6 + $m))->setWidth(16);
        }
        $ws->getColumnDimension($this->col(19))->setWidth(32);  // Équipe complète
        $ws->getColumnDimension($this->col(20))->setWidth(12);  // Statut

        $lastCol = 'T'; // colonne 20

        // ── Ligne 1 : Titre ───────────────────────────────────────────────────
        $ws->getRowDimension(1)->setRowHeight(30);
        $this->mergeWrite($ws, "A1:{$lastCol}1",
            "PLAN D'AUDIT ANNUEL {$year} — TOUTES ENTITÉS  |  Cabinet KEKELI",
            $this->styleHeader('0F172A','FFFFFF',14)
        );

        // ── Ligne 2 : Méta ────────────────────────────────────────────────────
        $ws->getRowDimension(2)->setRowHeight(15);
        $totalMissions = count($missions);
        $totalEntites  = count($allEntities);
        $totalJours    = $missions->sum('duree');

        $this->mergeWrite($ws, 'A2:F2', "Cabinet KEKELI  ·  Exercice {$year}",
            $this->styleHeader('0F172A','94A3B8',8,false));
        $this->mergeWrite($ws, 'G2:N2', "Missions : {$totalMissions}  |  Entités : {$totalEntites}  |  Jours-audit : {$totalJours}",
            $this->styleHeader('0F172A','94A3B8',8,false));
        $this->mergeWrite($ws, "O2:{$lastCol}2", "Édité le ".date('d/m/Y H:i'),
            $this->styleHeader('0F172A','94A3B8',8,false));

        // ── Ligne 3 : Séparateur ──────────────────────────────────────────────
        $ws->getRowDimension(3)->setRowHeight(4);
        $this->applyStyle($ws, "A3:{$lastCol}3", ['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E40AF']]]);

        // ── Ligne 4 : En-têtes colonnes ───────────────────────────────────────
        $ws->getRowDimension(4)->setRowHeight(22);
        foreach (['A'=>'#','B'=>'Entité / Périmètre','C'=>'Code','D'=>'Libellé / Objectif','E'=>'Fréquence','F'=>'Jrs'] as $col=>$h) {
            $ws->getCell($col.'4')->setValue($h);
            $this->applyStyle($ws, $col.'4', $this->styleHeader('0F172A','FFFFFF',9));
        }
        foreach (self::MONTH_LABELS_FR as $m=>$label) {
            $col = $this->col(6+$m);
            $ws->getCell($col.'4')->setValue($label);
            $this->applyStyle($ws, $col.'4', $this->styleHeader(self::MONTH_COLORS[$m],'FFFFFF',9));
        }
        $ws->getCell($this->col(19).'4')->setValue('Équipe Affectée');
        $this->applyStyle($ws, $this->col(19).'4', $this->styleHeader('0F172A','FFFFFF',9));
        $ws->getCell($this->col(20).'4')->setValue('Statut');
        $this->applyStyle($ws, $this->col(20).'4', $this->styleHeader('0F172A','FFFFFF',9));

        // ── Ligne 5 : Compteur missions/mois ─────────────────────────────────
        $ws->getRowDimension(5)->setRowHeight(14);
        $this->mergeWrite($ws, 'A5:F5', 'Nb. missions / mois  ►',
            $this->styleData('F0F4FF','94A3B8',8,true,Alignment::HORIZONTAL_RIGHT));
        $missionsParMois = [];
        foreach ($missions as $miss) {
            $m = (int)$miss->mois_debut;
            $missionsParMois[$m] = ($missionsParMois[$m]??0)+1;
        }
        for ($m=1; $m<=12; $m++) {
            $col = $this->col(6+$m);
            $cnt = $missionsParMois[$m] ?? 0;
            $ws->getCell($col.'5')->setValue($cnt>0 ? $cnt : '');
            $this->applyStyle($ws, $col.'5', $this->styleData(
                $cnt>0?'DBEAFE':'F8FAFC', $cnt>0?'1E40AF':'CBD5E1',
                9,true,Alignment::HORIZONTAL_CENTER
            ));
        }
        foreach ([19,20] as $c) {
            $ws->getCell($this->col($c).'5')->setValue('');
            $this->applyStyle($ws, $this->col($c).'5', $this->styleData('F8FAFC'));
        }

        // ── Index rapide ──────────────────────────────────────────────────────
        $missionIndex = [];
        foreach ($missions as $miss) $missionIndex[$miss->id] = $miss;

        // ── Données par entité ────────────────────────────────────────────────
        $row = 6;
        $seq = 1;

        foreach ($missionsByEntity as $entityId => $entityData) {

            // Bande séparateur entité
            $ws->getRowDimension($row)->setRowHeight(17);
            $this->mergeWrite($ws, "A{$row}:F{$row}",
                "  ▶  ".strtoupper($entityData['name']),
                [
                    'font'      => ['bold'=>true,'size'=>9,'color'=>['rgb'=>'FFFFFF'],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E3A5F']],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'2D4A6B']]],
                ]
            );
            for ($c=7; $c<=20; $c++) {
                $ws->getCell($this->col($c).$row)->setValue('');
                $this->applyStyle($ws, $this->col($c).$row, [
                    'fill'    => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E3A5F']],
                    'borders' => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'2D4A6B']]],
                ]);
            }
            $row++;

            foreach ($entityData['mission_ids'] as $missionId) {
                if (!isset($missionIndex[$missionId])) continue;
                $miss  = $missionIndex[$missionId];
                $bgRow = ($seq % 2 === 1) ? 'FFFFFF' : 'F7F9FF';

                // ── Équipe complète pour cette mission ────────────────────────
                $team      = $equipes[$missionId] ?? [];
                $teamAll   = $this->formatAuditeurs($team, true);   // tous membres, retour ligne
                $teamChef  = $this->formatAuditeurs($team, false);  // chef seulement

                // ── Hauteur ligne calculée selon contenu le plus long ─────────
                $libelle   = $miss->libelle.($miss->objectif ? "\n".mb_substr($miss->objectif,0,90) : '');
                $hLibelle  = $this->calcRowHeight($libelle, 36);
                $hTeam     = $this->calcRowHeight($teamAll, 30);
                $rowHeight = max(22, $hLibelle, $hTeam);
                $ws->getRowDimension($row)->setRowHeight($rowHeight);

                // # séq
                $ws->getCell('A'.$row)->setValue($seq);
                $this->applyStyle($ws, 'A'.$row, $this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));

                // Entité
                $ws->getCell('B'.$row)->setValue($entityData['name']);
                $this->applyStyle($ws, 'B'.$row, $this->styleData($bgRow,'475569',8,false,Alignment::HORIZONTAL_LEFT,true));

                // Code + type
                $ws->getCell('C'.$row)->setValue($miss->code_mission.($miss->type_code ? "\n".$miss->type_code : ''));
                $this->applyStyle($ws, 'C'.$row, $this->styleData($bgRow,'1E40AF',8,true,Alignment::HORIZONTAL_CENTER,true));

                // Libellé + objectif
                $ws->getCell('D'.$row)->setValue($libelle);
                $this->applyStyle($ws, 'D'.$row, $this->styleData($bgRow,'0F172A',9,false,Alignment::HORIZONTAL_LEFT,true));

                // Fréquence
                $freq = $miss->type_label ?? ($miss->numero_fpm ? 'Bi-Annuel' : 'Annuel');
                $ws->getCell('E'.$row)->setValue($freq);
                $this->applyStyle($ws, 'E'.$row, $this->styleData($bgRow,'7C3AED',8,true,Alignment::HORIZONTAL_CENTER));

                // Jours
                $ws->getCell('F'.$row)->setValue($miss->duree ?? '—');
                $this->applyStyle($ws, 'F'.$row, $this->styleData($bgRow,'1E293B',9,true,Alignment::HORIZONTAL_CENTER));

                // ── Barres Gantt (mois concernés) ─────────────────────────────
                $moisDebut = (int)$miss->mois_debut;
                $moisFin   = $miss->date_fin ? (int)date('n', strtotime($miss->date_fin)) : $moisDebut;

                for ($m=1; $m<=12; $m++) {
                    $col = $this->col(6+$m);
                    if ($m >= $moisDebut && $m <= $moisFin) {
                        // Dans la barre : afficher le chef dans le mois de début, vide sinon
                        $cellValue = ($m === $moisDebut) ? $teamChef : '';
                        $ws->getCell($col.$row)->setValue($cellValue);
                        $ws->getStyle($col.$row)->applyFromArray($this->styleGanttBar());
                        $ws->getStyle($col.$row)->getAlignment()->setWrapText(true);
                    } else {
                        $ws->getCell($col.$row)->setValue('');
                        $this->applyStyle($ws, $col.$row, $this->styleData($bgRow));
                    }
                }

                // Équipe complète (colonne S)
                $ws->getCell($this->col(19).$row)->setValue($teamAll ?: '—');
                $this->applyStyle($ws, $this->col(19).$row,
                    $this->styleData($bgRow,'334155',8,false,Alignment::HORIZONTAL_LEFT,true));

                // Statut
                $stat        = $miss->status;
                $sc          = self::STATUS_COLORS[$stat] ?? ['F1F5F9','475569'];
                $sl          = self::STATUS_LABELS[$stat] ?? $stat;
                $ws->getCell($this->col(20).$row)->setValue($sl);
                $this->applyStyle($ws, $this->col(20).$row,
                    $this->styleData($sc[0],$sc[1],8,true,Alignment::HORIZONTAL_CENTER));

                $seq++;
                $row++;
            }
        }

        // Ligne totaux
        $ws->getRowDimension($row)->setRowHeight(16);
        $this->mergeWrite($ws, "A{$row}:F{$row}",
            "TOTAL : {$totalMissions} missions  |  ".count($allEntities)." entités  |  {$totalJours} jours-audit",
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));
        for ($c=7; $c<=20; $c++) {
            $ws->getCell($this->col($c).$row)->setValue('');
            $this->applyStyle($ws, $this->col($c).$row, $this->styleData('EFF6FF'));
        }

        $ws->freezePane('G6');
    }

    // =========================================================================
    // ONGLET 2 – PAR MISSION
    // Colonnes : A=# | B=Type | C=Code | D=Libellé | E=Jrs |
    //            F..Q=Mois 1..12 | R=Entités | S=Équipe | T=Statut
    // =========================================================================

    private function buildSheetParMission(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
        $missions, $equipes, int $year
    ): void {
        $ws->setShowGridlines(false);

        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(10);
        $ws->getColumnDimension('C')->setWidth(14);
        $ws->getColumnDimension('D')->setWidth(34);
        $ws->getColumnDimension('E')->setWidth(5);
        for ($m=1; $m<=12; $m++) $ws->getColumnDimension($this->col(5+$m))->setWidth(16);
        $ws->getColumnDimension($this->col(18))->setWidth(26);  // Entités
        $ws->getColumnDimension($this->col(19))->setWidth(32);  // Équipe
        $ws->getColumnDimension($this->col(20))->setWidth(12);  // Statut
        $lastCol = 'T';

        // Titre
        $ws->getRowDimension(1)->setRowHeight(28);
        $this->mergeWrite($ws, "A1:{$lastCol}1",
            "PLAN D'AUDIT PAR MISSION — TOUTES ENTITÉS — {$year}  |  Cabinet KEKELI",
            $this->styleHeader('0F172A','FFFFFF',13));

        $ws->getRowDimension(2)->setRowHeight(14);
        $this->mergeWrite($ws, "A2:{$lastCol}2",
            "Audit Interne  ·  Autorisé par la Direction  ·  Édité le ".date('d/m/Y H:i'),
            $this->styleHeader('0F172A','94A3B8',8,false));

        $ws->getRowDimension(3)->setRowHeight(4);
        $this->applyStyle($ws, "A3:{$lastCol}3", ['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E40AF']]]);

        // En-têtes
        $ws->getRowDimension(4)->setRowHeight(22);
        foreach (['A'=>'#','B'=>'Type','C'=>'Code Mission','D'=>'Libellé / Objectif','E'=>'Jrs'] as $col=>$h) {
            $ws->getCell($col.'4')->setValue($h);
            $this->applyStyle($ws, $col.'4', $this->styleHeader('0F172A','FFFFFF',9));
        }
        foreach (self::MONTH_LABELS_FR as $m=>$label) {
            $col=$this->col(5+$m);
            $ws->getCell($col.'4')->setValue($label);
            $this->applyStyle($ws, $col.'4', $this->styleHeader(self::MONTH_COLORS[$m],'FFFFFF',9));
        }
        $ws->getCell($this->col(18).'4')->setValue('Entités Couvertes');
        $this->applyStyle($ws, $this->col(18).'4', $this->styleHeader('0F172A','FFFFFF',9));
        $ws->getCell($this->col(19).'4')->setValue('Équipe Affectée');
        $this->applyStyle($ws, $this->col(19).'4', $this->styleHeader('0F172A','FFFFFF',9));
        $ws->getCell($this->col(20).'4')->setValue('Statut');
        $this->applyStyle($ws, $this->col(20).'4', $this->styleHeader('0F172A','FFFFFF',9));

        // Compteur mois
        $ws->getRowDimension(5)->setRowHeight(14);
        $this->mergeWrite($ws, 'A5:E5','Nb. missions / mois  ►',
            $this->styleData('F0F4FF','94A3B8',8,true,Alignment::HORIZONTAL_RIGHT));
        $missionsParMois=[];
        foreach ($missions as $miss) { $m=(int)$miss->mois_debut; $missionsParMois[$m]=($missionsParMois[$m]??0)+1; }
        for ($m=1; $m<=12; $m++) {
            $col=$this->col(5+$m); $cnt=$missionsParMois[$m]??0;
            $ws->getCell($col.'5')->setValue($cnt>0?$cnt:'');
            $this->applyStyle($ws,$col.'5',$this->styleData($cnt>0?'DBEAFE':'F8FAFC',$cnt>0?'1E40AF':'CBD5E1',9,true,Alignment::HORIZONTAL_CENTER));
        }
        foreach([18,19,20] as $c) { $ws->getCell($this->col($c).'5')->setValue(''); $this->applyStyle($ws,$this->col($c).'5',$this->styleData('F8FAFC')); }

        // Données
        $row=6;
        foreach ($missions as $idx=>$miss) {
            $bgRow = ($idx%2===0)?'FFFFFF':'F7F9FF';
            $team      = $equipes[$miss->id] ?? [];
            $teamAll   = $this->formatAuditeurs($team, true);
            $teamChef  = $this->formatAuditeurs($team, false);

            $libelle   = $miss->libelle;
            $hLibelle  = $this->calcRowHeight($libelle, 34);
            $hTeam     = $this->calcRowHeight($teamAll, 30);
            $rowHeight = max(22, $hLibelle, $hTeam);
            $ws->getRowDimension($row)->setRowHeight($rowHeight);

            $ws->getCell('A'.$row)->setValue($idx+1);
            $this->applyStyle($ws,'A'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));

            $ws->getCell('B'.$row)->setValue($miss->type_code??'—');
            $this->applyStyle($ws,'B'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));

            $ws->getCell('C'.$row)->setValue($miss->code_mission.($miss->numero_fpm?"\n".$miss->numero_fpm:''));
            $this->applyStyle($ws,'C'.$row,$this->styleData($bgRow,'334155',8,false,Alignment::HORIZONTAL_CENTER,true));

            $ws->getCell('D'.$row)->setValue($libelle);
            $this->applyStyle($ws,'D'.$row,$this->styleData($bgRow,'0F172A',9,false,Alignment::HORIZONTAL_LEFT,true));

            $ws->getCell('E'.$row)->setValue($miss->duree??'—');
            $this->applyStyle($ws,'E'.$row,$this->styleData($bgRow,'1E293B',9,true,Alignment::HORIZONTAL_CENTER));

            $moisDebut=(int)$miss->mois_debut;
            $moisFin=$miss->date_fin?(int)date('n',strtotime($miss->date_fin)):$moisDebut;

            for ($m=1; $m<=12; $m++) {
                $col=$this->col(5+$m);
                if ($m>=$moisDebut && $m<=$moisFin) {
                    $cellValue = ($m===$moisDebut) ? $teamChef : '';
                    $ws->getCell($col.$row)->setValue($cellValue);
                    $ws->getStyle($col.$row)->applyFromArray($this->styleGanttBar());
                    $ws->getStyle($col.$row)->getAlignment()->setWrapText(true);
                } else {
                    $ws->getCell($col.$row)->setValue('');
                    $this->applyStyle($ws,$col.$row,$this->styleData($bgRow));
                }
            }

            $ws->getCell($this->col(18).$row)->setValue($miss->entities_list??'—');
            $this->applyStyle($ws,$this->col(18).$row,$this->styleData($bgRow,'334155',8,false,Alignment::HORIZONTAL_LEFT,true));

            $ws->getCell($this->col(19).$row)->setValue($teamAll?:'—');
            $this->applyStyle($ws,$this->col(19).$row,$this->styleData($bgRow,'334155',8,false,Alignment::HORIZONTAL_LEFT,true));

            $stat=$miss->status; $sc=self::STATUS_COLORS[$stat]??['F1F5F9','475569']; $sl=self::STATUS_LABELS[$stat]??$stat;
            $ws->getCell($this->col(20).$row)->setValue($sl);
            $this->applyStyle($ws,$this->col(20).$row,$this->styleData($sc[0],$sc[1],8,true,Alignment::HORIZONTAL_CENTER));

            $row++;
        }

        $total=count($missions); $jours=$missions->sum('duree');
        $ws->getRowDimension($row)->setRowHeight(16);
        $this->mergeWrite($ws,"A{$row}:".$this->col(17).$row,"TOTAL : {$total} missions  |  {$jours} jours  |  ".date('d/m/Y'),
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));
        foreach([18,19,20] as $c) { $ws->getCell($this->col($c).$row)->setValue(''); $this->applyStyle($ws,$this->col($c).$row,$this->styleData('EFF6FF')); }

        $ws->freezePane('F6');
    }

    // =========================================================================
    // ONGLET 3 – DDM PLAN DIRECTEUR
    // Colonnes : A=# | B=Type | C=Code – Libellé | D=Jrs |
    //            E..P=Mois 1..12 | Q=Équipe complète | R=Statut
    // =========================================================================

    private function buildSheetDDM(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
        $missions, $equipes, int $year
    ): void {
        $ws->setShowGridlines(false);

        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(10);
        $ws->getColumnDimension('C')->setWidth(40);
        $ws->getColumnDimension('D')->setWidth(5);
        for ($m=1; $m<=12; $m++) $ws->getColumnDimension($this->col(4+$m))->setWidth(16);
        $ws->getColumnDimension($this->col(17))->setWidth(34);  // Équipe
        $ws->getColumnDimension($this->col(18))->setWidth(12);  // Statut
        $lastCol = $this->col(18);

        // Titre
        $ws->getRowDimension(1)->setRowHeight(28);
        $this->mergeWrite($ws, "A1:{$lastCol}1",
            "MY DDM — PLAN D'AUDIT DU DIRECTEUR DE MISSION — {$year}",
            $this->styleHeader('1E3A5F','FFFFFF',13));

        $ws->getRowDimension(2)->setRowHeight(14);
        $this->mergeWrite($ws,'A2:F2',"Directeur de Mission : —  Cabinet KEKELI",
            $this->styleHeader('1E3A5F','94A3B8',8,false));
        $this->mergeWrite($ws,'G2:L2',"Missions supervisées : ".count($missions)."  |  Exercice : {$year}",
            $this->styleHeader('1E3A5F','94A3B8',8,false));
        $this->mergeWrite($ws,"M2:{$lastCol}2","Arrêté au : ".date('d/m/Y'),
            $this->styleHeader('1E3A5F','94A3B8',8,false));

        $ws->getRowDimension(3)->setRowHeight(4);
        $this->applyStyle($ws,"A3:{$lastCol}3",['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E40AF']]]);

        // En-têtes
        $ws->getRowDimension(4)->setRowHeight(22);
        foreach(['A'=>'#','B'=>'Type','C'=>'Code  –  Libellé Mission','D'=>'Jrs'] as $col=>$h) {
            $ws->getCell($col.'4')->setValue($h);
            $this->applyStyle($ws,$col.'4',$this->styleHeader('0F172A','FFFFFF',9));
        }
        foreach(self::MONTH_LABELS_FR as $m=>$label) {
            $col=$this->col(4+$m);
            $ws->getCell($col.'4')->setValue($label);
            $this->applyStyle($ws,$col.'4',$this->styleHeader(self::MONTH_COLORS[$m],'FFFFFF',9));
        }
        $ws->getCell($this->col(17).'4')->setValue('Équipe Affectée (tous membres)');
        $this->applyStyle($ws,$this->col(17).'4',$this->styleHeader('0F172A','FFFFFF',9));
        $ws->getCell($this->col(18).'4')->setValue('Statut');
        $this->applyStyle($ws,$this->col(18).'4',$this->styleHeader('0F172A','FFFFFF',9));

        // Compteur mois
        $ws->getRowDimension(5)->setRowHeight(14);
        $this->mergeWrite($ws,'A5:D5','Nb. missions / mois  ►',
            $this->styleData('F0F4FF','94A3B8',8,true,Alignment::HORIZONTAL_RIGHT));
        $missionsParMois=[];
        foreach($missions as $miss){ $mM=(int)$miss->mois_debut; $missionsParMois[$mM]=($missionsParMois[$mM]??0)+1; }
        for($m=1;$m<=12;$m++){
            $col=$this->col(4+$m); $cnt=$missionsParMois[$m]??0;
            $ws->getCell($col.'5')->setValue($cnt>0?$cnt:'');
            $this->applyStyle($ws,$col.'5',$this->styleData($cnt>0?'DBEAFE':'F8FAFC',$cnt>0?'1E40AF':'CBD5E1',9,true,Alignment::HORIZONTAL_CENTER));
        }
        foreach([17,18] as $c){ $ws->getCell($this->col($c).'5')->setValue(''); $this->applyStyle($ws,$this->col($c).'5',$this->styleData('F8FAFC')); }

        // Données
        $row=6;
        foreach($missions as $idx=>$miss){
            $bgRow=($idx%2===0)?'FFFFFF':'F7F9FF';
            $team     = $equipes[$miss->id] ?? [];
            $teamAll  = $this->formatAuditeurs($team, true);
            $teamChef = $this->formatAuditeurs($team, false);

            $libelle   = $miss->code_mission.'  –  '.$miss->libelle;
            $hLibelle  = $this->calcRowHeight($libelle, 38);
            $hTeam     = $this->calcRowHeight($teamAll, 32);
            $rowHeight = max(22, $hLibelle, $hTeam);
            $ws->getRowDimension($row)->setRowHeight($rowHeight);

            $ws->getCell('A'.$row)->setValue($idx+1);
            $this->applyStyle($ws,'A'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));
            $ws->getCell('B'.$row)->setValue($miss->type_code??'—');
            $this->applyStyle($ws,'B'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));
            $ws->getCell('C'.$row)->setValue($libelle);
            $this->applyStyle($ws,'C'.$row,$this->styleData($bgRow,'0F172A',9,false,Alignment::HORIZONTAL_LEFT,true));
            $ws->getCell('D'.$row)->setValue($miss->duree??'—');
            $this->applyStyle($ws,'D'.$row,$this->styleData($bgRow,'1E293B',9,true,Alignment::HORIZONTAL_CENTER));

            $moisDebut=(int)$miss->mois_debut;
            $moisFin=$miss->date_fin?(int)date('n',strtotime($miss->date_fin)):$moisDebut;

            for($m=1;$m<=12;$m++){
                $col=$this->col(4+$m);
                if($m>=$moisDebut&&$m<=$moisFin){
                    $cellValue=($m===$moisDebut)?$teamChef:'';
                    $ws->getCell($col.$row)->setValue($cellValue);
                    $ws->getStyle($col.$row)->applyFromArray($this->styleGanttBar());
                    $ws->getStyle($col.$row)->getAlignment()->setWrapText(true);
                } else {
                    $ws->getCell($col.$row)->setValue('');
                    $this->applyStyle($ws,$col.$row,$this->styleData($bgRow));
                }
            }

            // Équipe complète colonne Q — TOUS membres, nom complet + rôle, un par ligne
            $ws->getCell($this->col(17).$row)->setValue($teamAll?:'—');
            $this->applyStyle($ws,$this->col(17).$row,$this->styleData($bgRow,'334155',8,false,Alignment::HORIZONTAL_LEFT,true));

            $stat=$miss->status; $sc=self::STATUS_COLORS[$stat]??['F1F5F9','475569']; $sl=self::STATUS_LABELS[$stat]??$stat;
            $ws->getCell($this->col(18).$row)->setValue($sl);
            $this->applyStyle($ws,$this->col(18).$row,$this->styleData($sc[0],$sc[1],8,true,Alignment::HORIZONTAL_CENTER));

            $row++;
        }

        $total=count($missions); $jours=$missions->sum('duree');
        $ws->getRowDimension($row)->setRowHeight(16);
        $this->mergeWrite($ws,"A{$row}:".$this->col(16).$row,
            "TOTAL : {$total} missions supervisées  |  {$jours} jours",
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));
        foreach([17,18] as $c){ $ws->getCell($this->col($c).$row)->setValue(''); $this->applyStyle($ws,$this->col($c).$row,$this->styleData('EFF6FF')); }

        $ws->freezePane('E6');
    }

    // =========================================================================
    // ONGLET 4 – TABLEAU DE BORD
    // =========================================================================

    private function buildSheetTableauDeBord(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
        $missions, $equipes, $budgetLignes, int $year
    ): void {
        $ws->setShowGridlines(false);

        // Colonnes
        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(15);
        $ws->getColumnDimension('C')->setWidth(32);
        $ws->getColumnDimension('D')->setWidth(26);
        $ws->getColumnDimension('E')->setWidth(12);
        $ws->getColumnDimension('F')->setWidth(7);
        $ws->getColumnDimension('G')->setWidth(22);
        $ws->getColumnDimension('H')->setWidth(36);  // Équipe (noms complets)
        $ws->getColumnDimension('I')->setWidth(16);
        $ws->getColumnDimension('J')->setWidth(12);
        $ws->getColumnDimension('K')->setWidth(12);

        // Titre
        $ws->getRowDimension(1)->setRowHeight(32);
        $this->mergeWrite($ws,'A1:K1',
            "TABLEAU DE BORD — PLAN D'AUDIT ANNUEL {$year}  |  Cabinet KEKELI",
            $this->styleHeader('0F172A','FFFFFF',14));

        $ws->getRowDimension(2)->setRowHeight(14);
        $this->mergeWrite($ws,'A2:K2',
            "Vue synthétique — Toutes missions, toutes entités confondues — Édité le ".date('d/m/Y H:i'),
            $this->styleHeader('0F172A','94A3B8',8,false));

        $ws->getRowDimension(3)->setRowHeight(4);
        $this->applyStyle($ws,'A3:K3',['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1E40AF']]]);

        // ── KPIs ──────────────────────────────────────────────────────────────
        $totalMissions = count($missions);
        $planifiees    = $missions->where('status','planifiee')->count();
        $enCours       = $missions->where('status','en_cours')->count();
        $terminees     = $missions->where('status','terminee')->count();
        $annulees      = $missions->where('status','annulee')->count();
        $totalJours    = $missions->sum('duree');
        $totalBudget   = $missions->sum('budget_total');

        $ws->getRowDimension(4)->setRowHeight(13);
        $this->mergeWrite($ws,'A4:K4','▌  INDICATEURS CLÉS DE PERFORMANCE',
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));

        $kpis=[
            ['Total missions',$totalMissions,'1E40AF','DBEAFE'],
            ['Planifiées',$planifiees,'D97706','FEF3C7'],
            ['En cours',$enCours,'1E40AF','DBEAFE'],
            ['Terminées',$terminees,'059669','D1FAE5'],
            ['Annulées',$annulees,'DC2626','FEE2E2'],
            ['Total Jours',$totalJours,'475569','F1F5F9'],
            ['Budget FCFA',number_format($totalBudget,0,',',' '),'059669','D1FAE5'],
        ];

        $ws->getRowDimension(5)->setRowHeight(40);
        $ws->getRowDimension(6)->setRowHeight(16);

        foreach($kpis as $ki=>[$label,$val,$txt,$bg]){
            $col=$this->col($ki+1);
            $ws->getCell($col.'5')->setValue($val);
            $this->applyStyle($ws,$col.'5',[
                'font'      =>['bold'=>true,'size'=>20,'color'=>['rgb'=>$txt],'name'=>'Arial'],
                'fill'      =>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                'alignment' =>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   =>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'E2E8F0']]],
            ]);
            $ws->getCell($col.'6')->setValue($label);
            $this->applyStyle($ws,$col.'6',$this->styleData($bg,'64748B',8,true,Alignment::HORIZONTAL_CENTER));
        }

        $ws->getRowDimension(7)->setRowHeight(10);

        // ── Table détail ──────────────────────────────────────────────────────
        $ws->getRowDimension(8)->setRowHeight(13);
        $this->mergeWrite($ws,'A8:K8','▌  DÉTAIL DES MISSIONS PROGRAMMÉES',
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));

        $ws->getRowDimension(9)->setRowHeight(20);
        foreach(['A'=>'#','B'=>'Code Mission','C'=>'Libellé Mission','D'=>'Entités Couvertes','E'=>'Type','F'=>'Jrs','G'=>'Période','H'=>'Équipe (tous membres)','I'=>'Budget FCFA','J'=>'Avancement','K'=>'Statut'] as $col=>$h){
            $ws->getCell($col.'9')->setValue($h);
            $this->applyStyle($ws,$col.'9',$this->styleHeader('0F172A','FFFFFF',9));
        }

        $row=10;
        foreach($missions as $idx=>$miss){
            $bgRow=($idx%2===0)?'FFFFFF':'F7F9FF';
            $team    = $equipes[$miss->id] ?? [];
            $teamAll = $this->formatAuditeurs($team, true);

            // Hauteur adaptée
            $hTeam   = $this->calcRowHeight($teamAll, 34);
            $hEnt    = $this->calcRowHeight($miss->entities_list??'', 24);
            $rowHeight = max(20, $hTeam, $hEnt);
            $ws->getRowDimension($row)->setRowHeight($rowHeight);

            $ws->getCell('A'.$row)->setValue($idx+1);
            $this->applyStyle($ws,'A'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));

            $ws->getCell('B'.$row)->setValue($miss->code_mission);
            $this->applyStyle($ws,'B'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_LEFT));

            $ws->getCell('C'.$row)->setValue($miss->libelle);
            $this->applyStyle($ws,'C'.$row,$this->styleData($bgRow,'0F172A',9,false,Alignment::HORIZONTAL_LEFT,true));

            $ws->getCell('D'.$row)->setValue($miss->entities_list??'—');
            $this->applyStyle($ws,'D'.$row,$this->styleData($bgRow,'475569',8,false,Alignment::HORIZONTAL_LEFT,true));

            $ws->getCell('E'.$row)->setValue($miss->type_code??'—');
            $this->applyStyle($ws,'E'.$row,$this->styleData($bgRow,'1E40AF',9,true,Alignment::HORIZONTAL_CENTER));

            $ws->getCell('F'.$row)->setValue($miss->duree??'—');
            $this->applyStyle($ws,'F'.$row,$this->styleData($bgRow,'1E293B',9,true,Alignment::HORIZONTAL_CENTER));

            $ws->getCell('G'.$row)->setValue($miss->date_debut_fr.' → '.$miss->date_fin_fr);
            $this->applyStyle($ws,'G'.$row,$this->styleData($bgRow,'475569',8,false,Alignment::HORIZONTAL_CENTER));

            // Colonne H : tous les auditeurs nom complet (rôle) un par ligne
            $ws->getCell('H'.$row)->setValue($teamAll?:'—');
            $this->applyStyle($ws,'H'.$row,$this->styleData($bgRow,'334155',8,false,Alignment::HORIZONTAL_LEFT,true));

            $budgetTotal=(float)($miss->budget_total??0);
            $ws->getCell('I'.$row)->setValue($budgetTotal);
            $ws->getStyle('I'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $this->applyStyle($ws,'I'.$row,$this->styleData($bgRow,'059669',9,true,Alignment::HORIZONTAL_RIGHT));

            $prog=(int)($miss->progression??0);
            $ws->getCell('J'.$row)->setValue($prog.'%');
            $progBg=$prog>=100?'D1FAE5':($prog>0?'DBEAFE':'F8FAFC');
            $progFg=$prog>=100?'059669':($prog>0?'1E40AF':'94A3B8');
            $this->applyStyle($ws,'J'.$row,$this->styleData($progBg,$progFg,9,true,Alignment::HORIZONTAL_CENTER));

            $stat=$miss->status; $sc=self::STATUS_COLORS[$stat]??['F1F5F9','475569']; $sl=self::STATUS_LABELS[$stat]??$stat;
            $ws->getCell('K'.$row)->setValue($sl);
            $this->applyStyle($ws,'K'.$row,$this->styleData($sc[0],$sc[1],8,true,Alignment::HORIZONTAL_CENTER));

            $row++;
        }

        // Totaux
        $ws->getRowDimension($row)->setRowHeight(16);
        $this->mergeWrite($ws,"A{$row}:E{$row}",
            "TOTAL : {$totalMissions} missions  |  {$totalJours} jours",
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));
        $ws->getCell('F'.$row)->setValue("=SUM(F10:F".($row-1).")");
        $this->applyStyle($ws,'F'.$row,$this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_CENTER));
        $ws->getCell('I'.$row)->setValue("=SUM(I10:I".($row-1).")");
        $ws->getStyle('I'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $this->applyStyle($ws,'I'.$row,$this->styleData('EFF6FF','059669',9,true,Alignment::HORIZONTAL_RIGHT));
        foreach(['G','H','J','K'] as $c){ $ws->getCell($c.$row)->setValue(''); $this->applyStyle($ws,$c.$row,$this->styleData('EFF6FF')); }

        // Légende
        $rowLeg=$row+2;
        $ws->getRowDimension($rowLeg)->setRowHeight(13);
        $this->mergeWrite($ws,"A{$rowLeg}:K{$rowLeg}",'▌  LÉGENDE DES STATUTS',
            $this->styleData('EFF6FF','1E40AF',9,true,Alignment::HORIZONTAL_LEFT));
        $rowLeg++;
        $ws->getRowDimension($rowLeg)->setRowHeight(18);
        $legends=[
            ['Planifiée','Mission planifiée – pas encore démarrée','FEF3C7','D97706'],
            ['En cours','Mission en cours d\'exécution','DBEAFE','1E40AF'],
            ['Terminée','Mission clôturée avec succès','D1FAE5','059669'],
            ['Annulée','Mission annulée ou suspendue','FEE2E2','DC2626'],
        ];
        $legCols=['A','B','C','D','E','F','G','H'];
        foreach($legends as $li=>[$code,$label,$lbg,$ltxt]){
            $c1=$legCols[$li*2]; $c2=$legCols[$li*2+1];
            $ws->getCell($c1.$rowLeg)->setValue($code);
            $this->applyStyle($ws,$c1.$rowLeg,$this->styleData($lbg,$ltxt,9,true,Alignment::HORIZONTAL_CENTER));
            $ws->getCell($c2.$rowLeg)->setValue($label);
            $this->applyStyle($ws,$c2.$rowLeg,$this->styleData('FAFAFA','475569',8,false,Alignment::HORIZONTAL_LEFT));
        }

        $ws->freezePane('A10');
    }
}