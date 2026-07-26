<?php
// ════════════════════════════════════════════════════════════════════
// ApPorteeController.php
// AP — Phase 1 : « Portée de l'audit » (formulaire PA, ddmparam id=129).
//
// Maquette : 3 volets dynamiques
//   1) Importance des sujets
//   2) Analyse des ressources et résultats (cadre logique)
//   3) Analyse des risques d'audit (brut → mitigation → net)
//
// ★ Auto-alimentation depuis la base : le volet « ressources/résultats »
//   se pré-remplit avec les objectifs (→ Résultats) et activités
//   (→ Activités) du programme audité (processus de réalisation). Les
//   infos mission (entité, dates, programme) sont chargées d'office.
// ════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Auditor\Ap;

use App\Http\Controllers\Auditor\BasePhaseFormController;
use App\Models\Param\Auditor;
use App\Services\Audit\PerformanceIndicatorSuggester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApPorteeController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_ap_portee';
    protected string $formCode    = 'PA';
    protected string $codePrefix  = 'PA';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AP/PorteeAudit';
    protected string $routeEdit   = 'audit.ap.preparation.portee-audit';

    protected array $jsonFields = ['sujets', 'ressources', 'risques'];
    protected array $validationRules = [];

    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'process_id' => $request->input('process_id') ?: null,
            'synthese'   => $request->input('synthese') ?: null,
            'fait_par'   => $request->input('fait_par') ?: null,
            'revue_par'  => $request->input('revue_par') ?: null,
            'sujets'     => $this->asJson($request->input('sujets')),
            'ressources' => $this->asJson($request->input('ressources')),
            'risques'    => $this->asJson($request->input('risques')),
        ];
    }

    private function asJson($v): string
    {
        if (is_string($v)) return $v ?: '[]';
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }

    // ══════════════════════════════════════════════════════════════════

    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $payload = parent::buildPayload($missionId, $assignmentId, $auditor, $form);

        // Programme audité : on récupère le process_id choisi dans la fiche PC
        // (Présentation Programme) pour proposer la même liaison ici.
        $pcProcessId = DB::table('mission_phase_ap_programme as p')
            ->join('mission_phase_assignments as a', 'p.assignment_id', '=', 'a.id')
            ->where('a.mission_programmation_id', $missionId)
            ->value('p.process_id');

        $payload['programmes'] = DB::table('processes')->orderBy('name')->get(['id', 'name'])->toArray();
        $payload['pcProcessId'] = $pcProcessId ? (int) $pcProcessId : null;

        // Trame cadre logique pré-remplie depuis la base (pour le bouton
        // « charger depuis la base » côté vue, sans écraser la saisie).
        $processId = $form->process_id ?? $pcProcessId;
        $suggester = new PerformanceIndicatorSuggester();
        $payload['cadreLogiqueBase'] = $processId
            ? $suggester->cadreLogiqueForProcess((int) $processId)
            : [];

        // Risques d'audit types proposés (l'auditeur ajuste probas/impacts)
        $payload['risquesTypes'] = $this->risquesTypes();

        $payload['record']      = $form;
        $payload['formUrl']     = url('/m/audit.core/ap/preparation/portee-audit');
        $payload['backUrl']     = url("/m/audit.core/auditor/missions/{$missionId}/phases");
        $payload['chatBaseUrl'] = url("/m/audit.core/missions/{$missionId}/chat/PREPARATION");

        $mission = $payload['mission'] ?? null;
        if ($mission && !empty($mission->audit_type_code)) {
            try {
                $at = DB::table('ddmparam.audit_types')->where('code', strtoupper($mission->audit_type_code))->first(['color', 'icon', 'label']);
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
    // SAVE
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
            DB::table($this->table)->where('id', $existing->id)->update($data + ['updated_at' => now()]);
            $id = $existing->id;
        } else {
            $id = DB::table($this->table)->insertGetId($data + [
                'assignment_id'     => $assignmentId,
                'mission_id'        => $missionId,
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
    // SUGGESTIONS — indicateurs pour un objectif / cadre logique d'un process
    // ══════════════════════════════════════════════════════════════════

    public function suggestIndicateurs(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $suggester = new PerformanceIndicatorSuggester();
        return response()->json([
            'success'     => true,
            'indicateurs' => $suggester->forObjectif(
                $request->input('source_objectif_id') ? (int) $request->input('source_objectif_id') : null,
                $request->input('objectif_libelle')
            ),
        ]);
    }

    public function suggestCadreLogique(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $suggester = new PerformanceIndicatorSuggester();
        return response()->json([
            'success' => true,
            'lignes'  => $suggester->cadreLogiqueForProcess(
                $request->input('process_id') ? (int) $request->input('process_id') : null
            ),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // WORKFLOW
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
            'validation_status' => 'in_review', 'submitted_at' => now(),
            'submitted_by' => $auditor->id, 'updated_at' => now(),
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
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') {
            return response()->json(['message' => 'La fiche doit être soumise avant validation.'], 422);
        }
        $action = $request->input('action', 'validate');
        $note   = $request->input('note');
        if ($action === 'reject') {
            if (!$note) return response()->json(['message' => 'Motif du rejet obligatoire'], 422);
            DB::table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log((int) $row->assignment_id, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }
        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM peut valider définitivement'], 403);
        DB::table($this->table)->where('id', $formId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::table('mission_phase_assignments')->where('id', $row->assignment_id)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log((int) $row->assignment_id, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ══════════════════════════════════════════════════════════════════

    private function risquesTypes(): array
    {
        return [
            ['libelle' => "Accès limité aux données du programme", 'categorie' => 'Disponibilité de l\'information'],
            ['libelle' => "Fiabilité insuffisante des données de performance", 'categorie' => 'Qualité des données'],
            ['libelle' => "Indicateurs non documentés ou non traçables", 'categorie' => 'Traçabilité'],
            ['libelle' => "Périmètre du programme mal défini", 'categorie' => 'Cadrage'],
            ['libelle' => "Indisponibilité des interlocuteurs clés", 'categorie' => 'Organisation'],
        ];
    }
}
