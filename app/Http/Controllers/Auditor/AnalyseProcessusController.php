<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Param\Auditor;

class AnalyseProcessusController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ap';
    protected string $formCode    = 'analyse-processus';
    protected string $codePrefix  = 'AP';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseProcessus';
    protected string $routeEdit   = 'auditor.ac.analyse-processus.edit';

    protected array $validationRules = [
        'synthese'         => 'nullable|string',
        'bpmn_annotations' => 'nullable|string',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // formData — données à persister en base
    // ─────────────────────────────────────────────────────────────────────
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'processus'         => $request->input('processus',    '[]'),
            'acteurs'           => $request->input('acteurs',      '[]'),
            'flux'              => $request->input('flux',         '[]'),
            'observations'      => $request->input('observations', '[]'),
            'synthese'          => $request->input('synthese'),
            'bpmn_annotations'  => $request->input('bpmn_annotations', null),
            'fait_par'          => $request->input('fait_par'),
            'revue_par'         => $request->input('revue_par'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // buildPayload — construit les props Inertia pour la page
    // ─────────────────────────────────────────────────────────────────────
    protected function buildPayload(
        int     $missionId,
        int     $assignmentId,
        Auditor $auditor,
        mixed   $form = null
    ): array {

        // ── Résolution mission_programmation → missions.id réel ───────────
        $missionRow = DB::connection('tenant')
            ->table('mission_programmation')
            ->where('id', $missionId)
            ->select('id', 'mission_id', 'code_mission', 'libelle')
            ->first();

        $realMissionId = $missionRow?->mission_id ?? $missionId;

        // ── Risques liés à la mission ─────────────────────────────────────
        $riskIds = DB::connection('tenant')
            ->table('mission_risk')
            ->where('mission_id', $realMissionId)
            ->pluck('risk_id')
            ->toArray();

        $risks = [];
        if (!empty($riskIds)) {
            $risks = DB::connection('tenant')
                ->table('risks')
                ->whereIn('id', $riskIds)
                ->whereNull('deleted_at')
                ->whereNotNull('process_id')
                ->select(
                    'id', 'code', 'label', 'description',
                    'process_id', 'activity_id',
                    'status', 'criticality', 'frequency_net', 'impact_net', 'owner'
                )
                ->get()
                ->toArray();
        }

        $linkedProcessIds = collect($risks)->pluck('process_id')->unique()->filter()->values()->toArray();
        $activityIds      = collect($risks)->pluck('activity_id')->unique()->filter()->values()->toArray();

        // ── Processus liés à la mission ───────────────────────────────────
        $linkedProcessesRaw = !empty($linkedProcessIds)
            ? DB::connection('tenant')
                ->table('processes')
                ->whereIn('id', $linkedProcessIds)
                ->select('id', 'code', 'name', 'bpmn_xml')
                ->orderBy('code')
                ->get()
                ->toArray()
            : [];

        // ── Tous les processus non liés (sélecteur hors mission) ──────────
        $unlinkedRaw = DB::connection('tenant')
            ->table('processes')
            ->when(!empty($linkedProcessIds), fn ($q) => $q->whereNotIn('id', $linkedProcessIds))
            ->select('id', 'code', 'name', 'bpmn_xml')
            ->orderBy('code')
            ->get()
            ->toArray();

        // ── process_inputs & process_outputs pour TOUS les processus ──────
        $allProcIds = collect($linkedProcessesRaw)->pluck('id')
            ->merge(collect($unlinkedRaw)->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        // Intrants (dédupliqués par process_id + label)
        $inputsByProc = DB::connection('tenant')
            ->table('process_inputs')
            ->whereIn('process_id', $allProcIds)
            ->select('process_id', 'label')
            ->get()
            ->groupBy('process_id')
            ->map(fn ($rows) =>
                collect($rows)->pluck('label')->unique()->values()->implode("\n")
            )
            ->toArray();

        // Extrants (dédupliqués)
        $outputsByProc = DB::connection('tenant')
            ->table('process_outputs')
            ->whereIn('process_id', $allProcIds)
            ->select('process_id', 'label')
            ->get()
            ->groupBy('process_id')
            ->map(fn ($rows) =>
                collect($rows)->pluck('label')->unique()->values()->implode("\n")
            )
            ->toArray();

        // ── Fonctions de l'assignment (propriétaire / intervenants) ───────
        $assignmentFunctions = DB::connection('tenant')
            ->table('assignment_functions as af')
            ->join('functions as f', 'f.id', '=', 'af.function_id')
            ->where('af.assignment_id', $assignmentId)
            ->select('f.id', 'f.name', 'f.character')
            ->distinct()
            ->orderBy('f.character')
            ->get()
            ->map(fn ($fn) => [
                'id'        => $fn->id,
                'name'      => $fn->name,
                'character' => $fn->character,
            ])
            ->toArray();

        // ── Activités ─────────────────────────────────────────────────────
        $activitiesRaw = !empty($activityIds)
            ? DB::connection('tenant')
                ->table('activities')
                ->whereIn('id', $activityIds)
                ->select('id', 'process_id', 'code', 'name', 'description')
                ->orderBy('code')
                ->get()
                ->toArray()
            : [];

        // ── Diagrammes BPMN officiels ─────────────────────────────────────
        // Ces diagrammes sont en LECTURE SEULE pour l'auditeur.
        // L'auditeur fait ses annotations dans mission_phase_ap.bpmn_annotations.
        $bpmnDiagrams = [];
        if (
            !empty($linkedProcessIds) &&
            DB::connection('tenant')->getSchemaBuilder()->hasTable('bpmn_diagrams')
        ) {
            $bpmnDiagrams = DB::connection('tenant')
                ->table('bpmn_diagrams')
                ->whereIn('process_id', $linkedProcessIds)
                ->where('is_current', 1)
                ->select('id', 'process_id', 'bpmn_xml', 'version')
                ->get()
                ->keyBy('process_id')
                ->toArray();
        }

        // ── Sauvegarde précédente (champ processus JSON) ──────────────────
        $savedProcessus = $form
            ? (json_decode($form->processus ?? '[]', true) ?? [])
            : [];

        // ── Annotations BPMN de l'auditeur ────────────────────────────────
        // Format : { "process_id": "<bpmn:definitions…/>" }
        // Retourné tel quel dans $form->bpmn_annotations (string JSON).
        // Le Vue initialise sa localBpmnMap à partir de cette valeur.

        // ── Helper : construire un objet processus complet ────────────────
        $buildProcData = function (
            object $proc,
            bool   $withActivities = true
        ) use (
            $risks,
            $activitiesRaw,
            $bpmnDiagrams,
            $inputsByProc,
            $outputsByProc,
            $savedProcessus
        ) {
            $procId    = $proc->id;
            $procRisks = $withActivities
                ? collect($risks)->where('process_id', $procId)->values()
                : collect();

            $procActIds = $procRisks->pluck('activity_id')->unique()->filter()->values()->toArray();

            $activities = $withActivities
                ? collect($activitiesRaw)
                    ->whereIn('id', $procActIds)
                    ->values()
                    ->map(fn ($a) => [
                        'id'          => $a->id,
                        'code'        => $a->code,
                        'name'        => $a->name,
                        'description' => $a->description ?? '',
                        'risks'       => $procRisks
                            ->where('activity_id', $a->id)
                            ->map(fn ($r) => [
                                'id'     => $r->id,
                                'code'   => $r->code,
                                'label'  => $r->label,
                                'status' => $r->status,
                                'freq'   => $r->frequency_net,
                                'impact' => $r->impact_net,
                                'crit'   => $r->criticality,
                            ])
                            ->values()
                            ->toArray(),
                    ])
                    ->toArray()
                : [];

            // Diagramme BPMN officiel (lecture seule)
            $bpmn = $bpmnDiagrams[$procId] ?? null;

            // Données sauvegardées précédemment pour ce processus
            $saved = collect($savedProcessus)->firstWhere('process_id', $procId) ?? [];

            // Valeurs par défaut depuis process_inputs / process_outputs
            $defEntrees = $inputsByProc[$procId]  ?? '';
            $defSorties = $outputsByProc[$procId] ?? '';

            return [
                'id'                    => $procId,
                'code'                  => $proc->code,
                'name'                  => $proc->name,
                'risk_count'            => $procRisks->count(),
                'activities'            => $activities,

                // BPMN officiel — l'auditeur peut lire mais ne modifie pas bpmn_diagrams
                'bpmn_xml'              => $bpmn?->bpmn_xml ?? $proc->bpmn_xml ?? null,
                'bpmn_diagram_id'       => $bpmn?->id       ?? null,
                'bpmn_version'          => $bpmn?->version  ?? null,

                // Champs éditables — priorité : sauvegarde > défaut DB > vide
                'objectif_strategique'  => $saved['objectif_strategique']  ?? '',
                'objectif_operationnel' => $saved['objectif_operationnel'] ?? '',
                'indicateur'            => $saved['indicateur']            ?? '',
                'proprietaire'          => $saved['proprietaire']          ?? '',
                'autres_intervenants'   => $saved['autres_intervenants']   ?? '',
                'entrees'               => $saved['entrees']               ?? $defEntrees,
                'sorties'               => $saved['sorties']               ?? $defSorties,
                'forces'                => $saved['forces']                ?? '',
                'faiblesses'            => $saved['faiblesses']            ?? '',
                'observations'          => $saved['observations']          ?? '',

                // Valeurs par défaut DB (pour réinitialisation côté Vue)
                'default_entrees'       => $defEntrees,
                'default_sorties'       => $defSorties,
            ];
        };

        // ── processesData (liés à la mission, avec activités + risques) ───
        $processesData = collect($linkedProcessesRaw)
            ->map(fn ($proc) => $buildProcData($proc, true))
            ->values()
            ->toArray();

        // ── unlinkedProcesses (hors mission — données légères) ────────────
        $unlinkedProcesses = collect($unlinkedRaw)
            ->map(fn ($proc) => $buildProcData($proc, false))
            ->values()
            ->toArray();

        // ── Liste des AP existantes pour cet assignment ───────────────────
        $apList = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'validation_status', 'fait_par', 'updated_at'])
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                // Le $form complet est retourné — inclut bpmn_annotations
                'form'                => $form,

                'processesData'       => $processesData,
                'unlinkedProcesses'   => $unlinkedProcesses,
                'assignmentFunctions' => $assignmentFunctions,
                'riskCount'           => count($riskIds),
                'apList'              => $apList,

                'currentAuditor' => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],

                'formUrl' => url('/m/audit.core/ac/preparation/analyse-processus'),
                'backUrl' => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // soumettre
    // ─────────────────────────────────────────────────────────────────────
    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            return back()->withErrors(['auth' => 'Non autorisé']);
        }

        $row = DB::connection('tenant')
            ->table($this->table)
            ->where('id', $form)
            ->first();

        if (!$row || $row->validation_status !== 'draft') {
            return back()->withErrors(['status' => 'Statut invalide']);
        }

        $role = $this->getRole($row->mission_id, $auditor->id);

        DB::connection('tenant')
            ->table($this->table)
            ->where('id', $form)
            ->update([
                'validation_status' => 'in_review',
                'submitted_at'      => now(),
                'submitted_by'      => $auditor->id,
                'updated_at'        => now(),
            ]);

        $this->log(
            $row->assignment_id,
            $auditor->id,
            $role,
            'submitted',
            'draft',
            'in_review'
        );

        return back()->with('success', 'Analyse soumise');
    }

    // ─────────────────────────────────────────────────────────────────────
    // valider / rejeter
    // ─────────────────────────────────────────────────────────────────────
    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) {
            return back()->withErrors(['auth' => 'Non autorisé']);
        }

        $row  = DB::connection('tenant')
            ->table($this->table)
            ->where('id', $form)
            ->first();

        if (!$row) {
            return back()->withErrors(['form' => 'AP introuvable']);
        }

        $role = $this->getRole($row->mission_id, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) {
            return back()->withErrors(['role' => 'Droits insuffisants']);
        }

        $action = $request->input('action', 'validated');
        $note   = $request->input('note');

        if ($action === 'validated') {
            DB::connection('tenant')
                ->table($this->table)
                ->where('id', $form)
                ->update([
                    'validation_status' => 'validated',
                    'validated_at'      => now(),
                    'validated_by'      => $auditor->id,
                    'validation_note'   => $note,
                    'updated_at'        => now(),
                ]);

            $this->log(
                $row->assignment_id,
                $auditor->id,
                $role,
                'validated',
                'in_review',
                'validated',
                $note
            );

            return back()->with('success', 'AP validée');
        }

        // Rejet
        DB::connection('tenant')
            ->table($this->table)
            ->where('id', $form)
            ->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);

        $this->log(
            $row->assignment_id,
            $auditor->id,
            $role,
            'rejected',
            'in_review',
            'draft',
            $note
        );

        return back()->with('success', 'AP rejetée');
    }
}