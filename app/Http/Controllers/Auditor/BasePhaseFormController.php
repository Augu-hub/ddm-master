<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Param\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Contrôleur de base pour tous les formulaires de phase d'audit.
 *
 * Chaque sous-classe déclare :
 *   - $table      : nom de la table du formulaire (ex: mission_phase_pdc)
 *   - $formCode   : code du formulaire (ex: prise-de-connaissance)
 *   - $codePrefix : préfixe du code généré (ex: PDC)
 *   - $inertiaPage: chemin Inertia (ex: dashboards/Auditor/Forms/PriseDeConnaissance)
 *   - $routeEdit  : nom de la route edit (ex: auditor.pdc.edit)
 *
 * Les méthodes communes (index, edit, store, update, destroy, soumettre, valider)
 * sont toutes implémentées ici et peuvent être surchargées si besoin.
 */
abstract class BasePhaseFormController extends Controller
{
    /** Table de stockage du formulaire */
    protected string $table;

    /** Code du formulaire dans ddmparam.audit_type_forms */
    protected string $formCode;

    /** Préfixe pour la génération du code (ex: PDC, AFF, RO) */
    protected string $codePrefix;

    /** Nom de la colonne code dans la table (override si différent de 'code') */
    protected string $codeColumn = 'code';

    /** Page Inertia à rendre */
    protected string $inertiaPage;

    /** Nom de la route edit (pour redirect après store) */
    protected string $routeEdit;

    /** Champs spécifiques à valider (merge avec les champs communs) */
    protected array $validationRules = [];

    /** Champs JSON à décoder à l'hydratation */
    protected array $jsonFields = [];

    // ══════════════════════════════════════════════════════════════════
    // HELPERS COMMUNS
    // ══════════════════════════════════════════════════════════════════

    protected function getAuditor(): ?Auditor
    {
        $id = Session::get('auditor_id');
        if ($id) {
            $a = Auditor::with(['user', 'entity'])->find($id);
            if ($a) return $a;
        }
        $user = Auth::user();
        if (!$user) return null;
        $a = Auditor::with(['user', 'entity'])
            ->where('email', $user->email)->where('status', 'active')->first();
        if ($a) Session::put('auditor_id', $a->id);
        return $a;
    }

    protected function getRole(int $missionId, int $auditorId): string
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionId)
            ->where('mpa.auditeur_id', $auditorId)
            ->value(DB::raw("COALESCE(mr.code, mpa.role)")) ?? '—';
    }

    protected function canAccess(int $missionId, int $assignmentId, Auditor $auditor): bool
    {
        $inMission = DB::table('mission_phase_auditeurs')
            ->where('mission_id', $missionId)
            ->where('auditeur_id', $auditor->id)
            ->exists();
        if (!$inMission) return false;

        $role = $this->getRole($missionId, $auditor->id);
        if (in_array($role, ['DM', 'CM'])) return true;

        return DB::table('mission_phase_assignment_auditeurs')
            ->where('assignment_id', $assignmentId)
            ->where('auditeur_id', $auditor->id)
            ->exists();
    }

    protected function canEdit(object $form, string $role): bool
    {
        if ($form->validation_status === 'validated') return false;
        if ($form->validation_status === 'in_review' && !in_array($role, ['CM', 'DM'])) return false;
        return true;
    }

    protected function genCode(int $missionId): string
    {
        $code   = DB::table('mission_programmation')->where('id', $missionId)->value('code_mission') ?? 'MSN';
        $prefix = $this->codePrefix . '-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $code), 0, 6));
        $n      = DB::table($this->table)->where('mission_id', $missionId)->count();
        return $prefix . '-' . str_pad($n + 1, 3, '0', STR_PAD_LEFT);
    }

    protected function log(int $assignmentId, int $actorId, string $role, string $action, ?string $old, ?string $new, ?string $note = null): void
    {
        if (!DB::getSchemaBuilder()->hasTable('mission_phase_validation_log')) return;
        DB::table('mission_phase_validation_log')->insert([
            'assignment_id' => $assignmentId,
            'form_code'     => $this->formCode,
            'actor_id'      => $actorId,
            'actor_role'    => $role,
            'action'        => $action,
            'old_status'    => $old,
            'new_status'    => $new,
            'note'          => $note,
            'created_at'    => now(),
        ]);
    }

    protected function hydrate(object $form): object
    {
        foreach ($this->jsonFields as $field) {
            $form->$field = json_decode($form->$field ?? '[]', true);
        }
        return $form;
    }

    protected function getMission(int $missionId): ?object
    {
        return DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',       'mp.mission_id',      '=', 'm.id')
            ->leftJoin('mission_types as mt', 'm.mission_type_id',  '=', 'mt.id')
            ->leftJoin('entities as e',       'm.entity_id',        '=', 'e.id')
            ->where('mp.id', $missionId)
            ->select([
                'mp.id', 'mp.code_mission', 'mp.libelle', 'mp.objectif',
                'mp.date_debut', 'mp.date_fin', 'mp.lieux', 'mp.status',
                DB::raw("DATE_FORMAT(mp.date_debut,'%d/%m/%Y') as date_debut_fr"),
                DB::raw("DATE_FORMAT(mp.date_fin,'%d/%m/%Y') as date_fin_fr"),
                'e.name as entity_name',
                DB::raw("COALESCE(mt.audit_type_code, mt.code) as audit_type_code"),
            ])
            ->first();
    }

    protected function getAssignment(int $assignmentId, int $missionId): ?object
    {
        return DB::table('mission_phase_assignments as mpa')
            ->join('mission_phases as ph', 'mpa.mission_phase_id', '=', 'ph.id')
            ->where('mpa.id', $assignmentId)
            ->where('mpa.mission_programmation_id', $missionId)
            ->select([
                'mpa.id', 'mpa.status as phase_status',
                'mpa.validation_status', 'mpa.planned_start', 'mpa.planned_end',
                'ph.code as phase_code', 'ph.label as phase_label', 'ph.form_code',
            ])
            ->first();
    }

    protected function getAuditeurs(int $missionId): array
    {
        return DB::table('mission_phase_auditeurs as mpa')
            ->join('auditors as a',       'mpa.auditeur_id', '=', 'a.id')
            ->leftJoin('mission_roles as mr', 'mpa.role_id', '=', 'mr.id')
            ->where('mpa.mission_id', $missionId)
            ->select([
                'a.id', 'a.audit_code as code',
                'a.last_name as nom', 'a.first_name as prenom',
                DB::raw("COALESCE(mr.code, mpa.role, '—') as grade"),
            ])
            ->orderByRaw("COALESCE(mr.niveau,99) ASC")
            ->orderBy('a.last_name')
            ->get()->toArray();
    }

    /**
     * Charge le formulaire existant pour cet assignment.
     * Peut être surchargé si la table a une structure différente.
     */
    protected function loadForm(int $assignmentId, int $missionId): ?object
    {
        $form = DB::table($this->table)
            ->where('assignment_id', $assignmentId)
            ->where('mission_id', $missionId)
            ->first();
        if ($form) $form = $this->hydrate($form);
        return $form;
    }

    /**
     * Données à insérer/mettre à jour depuis la requête.
     * À surcharger dans chaque contrôleur enfant.
     */
    abstract protected function formData(Request $request, Auditor $auditor): array;

    /**
     * Payload Inertia commun.
     */
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        return [
            'mission'      => $this->getMission($missionId),
            'assignment'   => $this->getAssignment($assignmentId, $missionId),
            'auditeurs'    => $this->getAuditeurs($missionId),
            'form'         => $form,
            'auditorRole'  => $this->getRole($missionId, $auditor->id),
            'noMission'    => false,
            'missionId'    => $missionId,
            'assignmentId' => $assignmentId,
            'errors'       => [],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // INDEX
    // ══════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return redirect()->route('login');

        $missionId    = (int)($request->mission_id    ?? 0);
        $assignmentId = (int)($request->assignment_id ?? 0);

        if (!$missionId || !$assignmentId) {
            return inertia($this->inertiaPage, [
                'noMission' => true, 'mission' => null, 'assignment' => null,
                'auditeurs' => [], 'form' => null, 'auditorRole' => null,
                'missionId' => null, 'assignmentId' => null, 'errors' => [],
            ]);
        }

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        $assignment = $this->getAssignment($assignmentId, $missionId);
        if ($assignment && $assignment->phase_status === 'pending') {
            return inertia($this->inertiaPage, array_merge(
                $this->buildPayload($missionId, $assignmentId, $auditor),
                ['phaseNotStarted' => true]
            ));
        }

        $form = $this->loadForm($assignmentId, $missionId);

        return inertia($this->inertiaPage,
            $this->buildPayload($missionId, $assignmentId, $auditor, $form)
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // STORE
    // ══════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $missionId    = (int)$request->input('mission_id', 0);
        $assignmentId = (int)$request->input('assignment_id', 0);

        if (!$missionId || !$assignmentId) {
            return back()->withErrors(['mission_id' => 'Contexte de mission manquant.']);
        }
        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        $assignment = DB::table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending') {
            return back()->withErrors(['assignment' => 'Démarrez la phase avant de remplir ce formulaire.']);
        }

        // Si existe déjà → update
        $existing = DB::table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) {
            $request->merge(['mission_id' => $missionId, 'assignment_id' => $assignmentId]);
            return $this->update($request, $existing->id);
        }

        if (!empty($this->validationRules)) {
            $request->validate($this->validationRules);
        }

        $data = array_merge($this->formData($request, $auditor), [
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            $this->codeColumn   => $this->genCode($missionId),
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $id = DB::table($this->table)->insertGetId($data);

        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');

        $form = $this->hydrate(DB::table($this->table)->where('id', $id)->first());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'form' => $form]);
        }

        return redirect()->route($this->routeEdit, [
            'form'          => $id,
            'mission_id'    => $missionId,
            'assignment_id' => $assignmentId,
        ])->with('success', 'Formulaire créé avec succès.');
    }

    // ══════════════════════════════════════════════════════════════════
    // UPDATE
    // ══════════════════════════════════════════════════════════════════

    public function update(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);
        if (!$this->canEdit($row, $role)) {
            return back()->withErrors(['status' => match ($row->validation_status) {
                'validated' => 'Formulaire validé — modification impossible.',
                'in_review' => 'Formulaire soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }]);
        }

        if (!empty($this->validationRules)) {
            $request->validate($this->validationRules);
        }

        $data = array_merge($this->formData($request, $auditor), ['updated_at' => now()]);
        DB::table($this->table)->where('id', $formId)->update($data);

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);

        $updated = $this->hydrate(DB::table($this->table)->where('id', $formId)->first());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'form' => $updated]);
        }
        return back()->with('success', 'Formulaire mis à jour.');
    }

    // ══════════════════════════════════════════════════════════════════
    // EDIT
    // ══════════════════════════════════════════════════════════════════

    public function edit(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return redirect()->route('login');

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int)($request->mission_id    ?? $row->mission_id);
        $assignmentId = (int)($request->assignment_id ?? $row->assignment_id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        $form = $this->hydrate($row);

        return inertia($this->inertiaPage,
            $this->buildPayload($missionId, $assignmentId, $auditor, $form)
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // DESTROY
    // ══════════════════════════════════════════════════════════════════

    public function destroy(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) abort(404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) abort(403);

        if ($row->validation_status === 'validated') {
            return back()->withErrors(['status' => 'Un formulaire validé ne peut pas être supprimé.']);
        }
        if ($row->validation_status !== 'draft' && !in_array($role, ['DM'])) {
            return back()->withErrors(['status' => 'Suppression non autorisée à ce stade.']);
        }

        DB::table($this->table)->where('id', $formId)->delete();
        $this->log($assignmentId, $auditor->id, $role, 'deleted', $row->validation_status, null);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Formulaire supprimé.');
    }

    // ══════════════════════════════════════════════════════════════════
    // SOUMETTRE
    // ══════════════════════════════════════════════════════════════════

    public function soumettre(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
        if ($row->validation_status === 'validated') {
            return response()->json(['error' => 'Déjà validé'], 422);
        }
        if ($row->validation_status === 'in_review') {
            return response()->json(['error' => 'Déjà soumis pour validation'], 422);
        }

        DB::table($this->table)->where('id', $formId)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');

        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    // ══════════════════════════════════════════════════════════════════
    // VALIDER
    // ══════════════════════════════════════════════════════════════════

    public function valider(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::table($this->table)->where('id', $formId)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int)($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int)($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) {
            return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        }
        if ($row->validation_status !== 'in_review') {
            return response()->json(['error' => 'Le formulaire doit être soumis avant validation'], 422);
        }

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif du rejet obligatoire'], 422);

            DB::table($this->table)->where('id', $formId)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);

            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        // Validation définitive — DM uniquement
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

        DB::table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
            'validated_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);

        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);

        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }
}