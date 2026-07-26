<?php
// ════════════════════════════════════════════════════════════════════
// ApProgrammeController.php
// AP (Audit de Performance) — Phase 1 : « Présentation générale du
// programme » (formulaire PC, ddmparam.audit_type_forms id=128).
//
// Implémente les maquettes FranckS :
//  · rubriques 1-6 (mandats/missions/objectifs, résultats 3E+qualité,
//    gouvernance, sources, ressources) + rubriques libres JSON ;
//  · structuration en actions : activités (AC_01.0n) → objectifs
//    (OB_01.0n.m) → fiches indicateurs (IND-nnn) ;
//  · fiche activité (résultat, responsable, membres, budgets, extrants
//    EFE entrées/sorties) ;
//  · point financier (dotation/exécution/taux) & physique par année.
//
// ★ LIAISON PROGRAMME : l'audit de performance porte sur les PROCESSUS
//   DE RÉALISATION du tenant (= programmes). La fiche référence
//   processes.id et chaque objectif d'activité peut être LIÉ à un
//   objectif du processus déjà en base (objectifs.id).
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor\Ap;

use App\Http\Controllers\Auditor\BasePhaseFormController;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApProgrammeController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ap_programme';
    protected string $formCode    = 'PC';
    protected string $codePrefix  = 'APP';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AP/PresentationProgramme';
    protected string $routeEdit   = 'audit.ap.preparation.presentation-programme';

    protected array $jsonFields = ['rubriques_extra'];

    protected array $validationRules = [];

    private const SCALARS = [
        'process_id', 'objectif_general',
        'mandats', 'missions_prog', 'objectifs_prog',
        'res_economie', 'res_efficacite', 'res_efficience', 'res_qualite',
        'gouv_organisation', 'gouv_rel_interieures', 'gouv_rel_exterieures',
        'src_legislation', 'src_rapports_anterieurs',
        'ress_humaines', 'ress_financieres', 'ress_techniques',
        'fait_par', 'revue_par',
    ];

    protected function formData(Request $request, Auditor $auditor): array
    {
        $data = [];
        foreach (self::SCALARS as $f) {
            $data[$f] = $request->input($f) ?: null;
        }
        $data['process_id']      = $request->input('process_id') ?: null;
        $data['rubriques_extra'] = is_string($request->input('rubriques_extra'))
            ? $request->input('rubriques_extra')
            : json_encode($request->input('rubriques_extra', []), JSON_UNESCAPED_UNICODE);
        return $data;
    }

    // ══════════════════════════════════════════════════════════════════
    // PAYLOAD — liaison base : programmes (processes) + objectifs
    // ══════════════════════════════════════════════════════════════════

    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $payload = parent::buildPayload($missionId, $assignmentId, $auditor, $form);

        // ★ Les programmes = processus de réalisation du tenant
        $payload['programmes'] = DB::table('processes')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        // ★ Objectifs déjà en base, groupés par processus — pour lier chaque
        // objectif d'activité à un objectif réel du programme.
        $payload['objectifsBase'] = DB::table('objectifs')
            ->orderBy('name')
            ->get(['id', 'process_id', 'name', 'type', 'kpi', 'kpi_target'])
            ->groupBy('process_id')
            ->toArray();

        // Enfants de la fiche (si elle existe)
        $payload['financier'] = [];
        $payload['activites'] = [];
        if ($form && isset($form->id)) {
            $payload['financier'] = $this->loadFinancier((int) $form->id);
            $payload['activites'] = $this->loadActivites((int) $form->id);
        }

        // Alias sémantique + URLs (le front pilote tout via formUrl)
        $payload['record']      = $form;
        $payload['formUrl']     = url('/m/audit.core/ap/preparation/presentation-programme');
        $payload['backUrl']     = url("/m/audit.core/auditor/missions/{$missionId}/phases");
        $payload['chatBaseUrl'] = url("/m/audit.core/missions/{$missionId}/chat/PREPARATION");

        // Couleur/label AP depuis ddmparam
        $mission = $payload['mission'] ?? null;
        if ($mission && !empty($mission->audit_type_code)) {
            try {
                $at = DB::table('ddmparam.audit_types')
                    ->where('code', strtoupper($mission->audit_type_code))
                    ->first(['color', 'icon', 'label']);
                if ($at) {
                    $mission->audit_color      = $at->color;
                    $mission->audit_icon       = $at->icon;
                    $mission->audit_type_label = $at->label;
                }
            } catch (\Throwable $e) {
            }
        }

        return $payload;
    }

    // ══════════════════════════════════════════════════════════════════
    // SAVE — upsert JSON de la fiche (une par assignment)
    // ══════════════════════════════════════════════════════════════════

    public function save(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $missionId    = (int) $request->input('mission_id', 0);
        $assignmentId = (int) $request->input('assignment_id', 0);
        if (!$missionId || !$assignmentId) {
            return response()->json(['message' => 'Contexte de mission manquant.'], 422);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $assignment = DB::table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending') {
            return response()->json(['message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);
        }

        $role     = $this->getRole($missionId, $auditor->id);
        $existing = DB::table($this->table)->where('assignment_id', $assignmentId)->first();

        if ($existing && !$this->canEdit($existing, $role)) {
            return response()->json(['message' => 'Fiche verrouillée (validée ou soumise).'], 422);
        }

        $data = $this->formData($request, $auditor);

        if ($existing) {
            DB::table($this->table)->where('id', $existing->id)
                ->update($data + ['updated_at' => now()]);
            $id = $existing->id;
        } else {
            $id = DB::table($this->table)->insertGetId($data + [
                'assignment_id'     => $assignmentId,
                'mission_id'        => $missionId,
                'code'              => $this->genCode($missionId),
                'validation_status' => 'draft',
                'created_by'        => $auditor->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'draft_saved', null, 'draft');
        }

        return response()->json([
            'success' => true,
            'record'  => $this->hydrate(DB::table($this->table)->where('id', $id)->first()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POINT FINANCIER & PHYSIQUE
    // ══════════════════════════════════════════════════════════════════

    public function saveFinancier(Request $request)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        $rows = $request->validate([
            'rows'                  => 'required|array|max:30',
            'rows.*.annee'          => 'required|integer|min:1990|max:2100',
            'rows.*.dotation'       => 'nullable|numeric',
            'rows.*.execution'      => 'nullable|numeric',
            'rows.*.point_physique' => 'nullable|numeric|min:0|max:100',
        ])['rows'];

        $now = now();
        $years = [];
        foreach ($rows as $r) {
            $years[] = (int) $r['annee'];
            DB::table('ap_prog_financier')->updateOrInsert(
                ['programme_id' => $prog->id, 'annee' => (int) $r['annee']],
                [
                    'dotation'       => $r['dotation'] ?? null,
                    'execution'      => $r['execution'] ?? null,
                    'point_physique' => $r['point_physique'] ?? null,
                    'updated_at'     => $now,
                ]
            );
        }
        // Années retirées de l'écran → supprimées
        DB::table('ap_prog_financier')
            ->where('programme_id', $prog->id)
            ->whereNotIn('annee', $years ?: [0])
            ->delete();

        return response()->json(['success' => true, 'financier' => $this->loadFinancier((int) $prog->id)]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ACTIVITÉS (AC_01.0n) + fiche activité
    // ══════════════════════════════════════════════════════════════════

    public function saveActivite(Request $request)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        $data = $request->validate([
            'id'              => 'nullable|integer',
            'intitule'        => 'required|string|max:500',
            'resultat'        => 'nullable|string',
            'responsable'     => 'nullable|string|max:255',
            'membres'         => 'nullable|array',
            'quantite'        => 'nullable|string|max:100',
            'budget_global'   => 'nullable|numeric',
            'budget_exercice' => 'nullable|numeric',
            'commentaires'    => 'nullable|string',
            'extrants'        => 'nullable|array|max:50',
        ]);

        $payload = [
            'intitule'        => $data['intitule'],
            'resultat'        => $data['resultat'] ?? null,
            'responsable'     => $data['responsable'] ?? null,
            'membres'         => json_encode($data['membres'] ?? [], JSON_UNESCAPED_UNICODE),
            'quantite'        => $data['quantite'] ?? null,
            'budget_global'   => $data['budget_global'] ?? null,
            'budget_exercice' => $data['budget_exercice'] ?? null,
            'commentaires'    => $data['commentaires'] ?? null,
            'extrants'        => json_encode($data['extrants'] ?? [], JSON_UNESCAPED_UNICODE),
            'updated_at'      => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('ap_prog_activites')
                ->where('id', $data['id'])->where('programme_id', $prog->id)
                ->update($payload);
            $id = (int) $data['id'];
        } else {
            $n = DB::table('ap_prog_activites')->where('programme_id', $prog->id)->count() + 1;
            $id = DB::table('ap_prog_activites')->insertGetId($payload + [
                'programme_id' => $prog->id,
                'code'         => 'AC_01.' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                'sort_order'   => $n * 10,
                'created_at'   => now(),
            ]);
        }

        return response()->json(['success' => true, 'activites' => $this->loadActivites((int) $prog->id), 'saved_id' => $id]);
    }

    public function deleteActivite(Request $request, int $id)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        DB::table('ap_prog_activites')->where('id', $id)->where('programme_id', $prog->id)->delete();
        return response()->json(['success' => true, 'activites' => $this->loadActivites((int) $prog->id)]);
    }

    // ══════════════════════════════════════════════════════════════════
    // OBJECTIFS D'ACTIVITÉ (OB_01.0n.m) — liables aux objectifs du processus
    // ══════════════════════════════════════════════════════════════════

    public function saveObjectif(Request $request)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        $data = $request->validate([
            'id'                 => 'nullable|integer',
            'activite_id'        => 'required|integer',
            'libelle'            => 'required|string',
            'source_objectif_id' => 'nullable|integer',
        ]);

        $activite = DB::table('ap_prog_activites')
            ->where('id', $data['activite_id'])->where('programme_id', $prog->id)->first();
        if (!$activite) return response()->json(['error' => 'Activité introuvable'], 404);

        $payload = [
            'libelle'            => $data['libelle'],
            'source_objectif_id' => $data['source_objectif_id'] ?? null,
            'updated_at'         => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('ap_prog_objectifs')
                ->where('id', $data['id'])->where('activite_id', $activite->id)
                ->update($payload);
        } else {
            $n = DB::table('ap_prog_objectifs')->where('activite_id', $activite->id)->count() + 1;
            // AC_01.02 → OB_01.02.3
            $suffix = preg_replace('/^AC_/', '', $activite->code);
            DB::table('ap_prog_objectifs')->insert($payload + [
                'activite_id' => $activite->id,
                'code'        => "OB_{$suffix}.{$n}",
                'sort_order'  => $n * 10,
                'created_at'  => now(),
            ]);
        }

        return response()->json(['success' => true, 'activites' => $this->loadActivites((int) $prog->id)]);
    }

    public function deleteObjectif(Request $request, int $id)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        DB::table('ap_prog_objectifs as o')
            ->join('ap_prog_activites as a', 'o.activite_id', '=', 'a.id')
            ->where('o.id', $id)->where('a.programme_id', $prog->id)
            ->delete();
        return response()->json(['success' => true, 'activites' => $this->loadActivites((int) $prog->id)]);
    }

    // ══════════════════════════════════════════════════════════════════
    // FICHES INDICATEURS (IND-nnn)
    // ══════════════════════════════════════════════════════════════════

    public function saveIndicateur(Request $request)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        $data = $request->validate([
            'id'                     => 'nullable|integer',
            'objectif_id'            => 'required|integer',
            'intitule'               => 'required|string|max:500',
            'service_utilisateur'    => 'nullable|string|max:255',
            'unite_mesure'           => 'nullable|string|max:100',
            'periodicite_mesure'     => 'nullable|string|max:100',
            'periodicite_indicateur' => 'nullable|string|max:100',
            'dernieres_valeurs'      => 'nullable|array|max:10',
            'nature_donnees'         => 'nullable|string',
            'mode_collecte'          => 'nullable|string|max:255',
            'service_synthese'       => 'nullable|string|max:255',
            'structure_validation'   => 'nullable|string|max:255',
            'mode_calcul'            => 'nullable|string',
            'interpretation'         => 'nullable|string',
            'sens_evolution'         => 'nullable|in:hausse,baisse',
            'limites'                => 'nullable|string',
            'date_livraison'         => 'nullable|string|max:150',
            'plan_amelioration'      => 'nullable|string',
            'commentaires'           => 'nullable|string',
        ]);

        // L'objectif doit appartenir à ce programme
        $objectif = DB::table('ap_prog_objectifs as o')
            ->join('ap_prog_activites as a', 'o.activite_id', '=', 'a.id')
            ->where('o.id', $data['objectif_id'])->where('a.programme_id', $prog->id)
            ->select('o.id')->first();
        if (!$objectif) return response()->json(['error' => 'Objectif introuvable'], 404);

        $payload = [
            'intitule'               => $data['intitule'],
            'service_utilisateur'    => $data['service_utilisateur'] ?? null,
            'unite_mesure'           => $data['unite_mesure'] ?? null,
            'periodicite_mesure'     => $data['periodicite_mesure'] ?? null,
            'periodicite_indicateur' => $data['periodicite_indicateur'] ?? null,
            'dernieres_valeurs'      => json_encode($data['dernieres_valeurs'] ?? [], JSON_UNESCAPED_UNICODE),
            'nature_donnees'         => $data['nature_donnees'] ?? null,
            'mode_collecte'          => $data['mode_collecte'] ?? null,
            'service_synthese'       => $data['service_synthese'] ?? null,
            'structure_validation'   => $data['structure_validation'] ?? null,
            'mode_calcul'            => $data['mode_calcul'] ?? null,
            'interpretation'         => $data['interpretation'] ?? null,
            'sens_evolution'         => $data['sens_evolution'] ?? null,
            'limites'                => $data['limites'] ?? null,
            'date_livraison'         => $data['date_livraison'] ?? null,
            'plan_amelioration'      => $data['plan_amelioration'] ?? null,
            'commentaires'           => $data['commentaires'] ?? null,
            'updated_at'             => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('ap_prog_indicateurs')->where('id', $data['id'])
                ->where('objectif_id', $objectif->id)->update($payload);
        } else {
            $n = DB::table('ap_prog_indicateurs as i')
                ->join('ap_prog_objectifs as o', 'i.objectif_id', '=', 'o.id')
                ->join('ap_prog_activites as a', 'o.activite_id', '=', 'a.id')
                ->where('a.programme_id', $prog->id)->count() + 1;
            DB::table('ap_prog_indicateurs')->insert($payload + [
                'objectif_id' => $objectif->id,
                'code'        => 'IND-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                'created_at'  => now(),
            ]);
        }

        return response()->json(['success' => true, 'activites' => $this->loadActivites((int) $prog->id)]);
    }

    public function deleteIndicateur(Request $request, int $id)
    {
        [$prog, $err] = $this->programmeForWrite($request);
        if ($err) return $err;

        DB::table('ap_prog_indicateurs as i')
            ->join('ap_prog_objectifs as o', 'i.objectif_id', '=', 'o.id')
            ->join('ap_prog_activites as a', 'o.activite_id', '=', 'a.id')
            ->where('i.id', $id)->where('a.programme_id', $prog->id)
            ->delete();
        return response()->json(['success' => true, 'activites' => $this->loadActivites((int) $prog->id)]);
    }

    // ══════════════════════════════════════════════════════════════════
    // WORKFLOW — soumettre / valider (fiche + assignment)
    // ══════════════════════════════════════════════════════════════════

    public function soumettreFiche(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Fiche introuvable'], 404);
        if (!$this->canAccess((int) $row->mission_id, (int) $row->assignment_id, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
        if ($row->validation_status !== 'draft') {
            return response()->json(['message' => 'Seul un brouillon peut être soumis.'], 422);
        }

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        DB::table($this->table)->where('id', $formId)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    public function validerFiche(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Fiche introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        }
        if ($row->validation_status !== 'in_review') {
            return response()->json(['message' => 'La fiche doit être soumise avant validation.'], 422);
        }

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['message' => 'Motif du rejet obligatoire'], 422);
            DB::table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log((int) $row->assignment_id, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') {
            return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);
        }

        DB::table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'validation_note'   => $note,
            'updated_at'        => now(),
        ]);
        DB::table('mission_phase_assignments')->where('id', $row->assignment_id)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVÉ
    // ══════════════════════════════════════════════════════════════════

    /** Charge la fiche pour une écriture enfant + contrôles accès/verrou. */
    private function programmeForWrite(Request $request): array
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return [null, response()->json(['error' => 'Non autorisé'], 403)];

        $progId = (int) $request->input('programme_id', 0);
        $prog   = DB::table($this->table)->where('id', $progId)->first();
        if (!$prog) return [null, response()->json(['error' => 'Fiche programme introuvable — enregistrez-la d\'abord.'], 404)];

        if (!$this->canAccess((int) $prog->mission_id, (int) $prog->assignment_id, $auditor)) {
            return [null, response()->json(['error' => 'Accès refusé'], 403)];
        }

        $role = $this->getRole((int) $prog->mission_id, $auditor->id);
        if (!$this->canEdit($prog, $role)) {
            return [null, response()->json(['message' => 'Fiche verrouillée (validée ou soumise).'], 422)];
        }

        return [$prog, null];
    }

    private function loadFinancier(int $programmeId): array
    {
        return DB::table('ap_prog_financier')
            ->where('programme_id', $programmeId)
            ->orderBy('annee')
            ->get(['id', 'annee', 'dotation', 'execution', 'point_physique'])
            ->toArray();
    }

    private function loadActivites(int $programmeId): array
    {
        $activites = DB::table('ap_prog_activites')
            ->where('programme_id', $programmeId)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $actIds = $activites->pluck('id');

        $objectifs = DB::table('ap_prog_objectifs as o')
            ->leftJoin('objectifs as src', 'o.source_objectif_id', '=', 'src.id')
            ->whereIn('o.activite_id', $actIds->all() ?: [0])
            ->orderBy('o.sort_order')->orderBy('o.id')
            ->get([
                'o.id', 'o.activite_id', 'o.code', 'o.libelle', 'o.source_objectif_id',
                'src.name as source_name', 'src.type as source_type', 'src.kpi as source_kpi',
            ]);

        $indicateurs = DB::table('ap_prog_indicateurs')
            ->whereIn('objectif_id', $objectifs->pluck('id')->all() ?: [0])
            ->orderBy('id')
            ->get();

        $indByObjectif = $indicateurs->map(function ($i) {
            $i->dernieres_valeurs = json_decode($i->dernieres_valeurs ?? '[]', true) ?: [];
            return $i;
        })->groupBy('objectif_id');

        $objByActivite = $objectifs->map(function ($o) use ($indByObjectif) {
            $o->indicateurs = array_values(($indByObjectif[$o->id] ?? collect())->toArray());
            return $o;
        })->groupBy('activite_id');

        return $activites->map(function ($a) use ($objByActivite) {
            $a->membres   = json_decode($a->membres ?? '[]', true) ?: [];
            $a->extrants  = json_decode($a->extrants ?? '[]', true) ?: [];
            $a->objectifs = array_values(($objByActivite[$a->id] ?? collect())->toArray());
            return $a;
        })->values()->toArray();
    }
}
