<?php
namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Param\Auditor;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PriseDeConnaissanceController extends BasePhaseFormController
{
    protected string $table      = 'mission_phase_pdc';
    protected string $formCode   = 'prise-de-connaissance';
    protected string $codePrefix = 'PDC';
    protected string $inertiaPage= 'dashboards/Auditor/Forms/PriseDeConnaissance';
    protected string $routeEdit  = 'auditor.pdc.edit';

    protected array $validationRules = [
        'entite_auditee' => 'nullable|string|max:255',
    ];

    // ── Données du formulaire ─────────────────────────────────────
    protected function formData(Request $request, Auditor $auditor): array
    {
        $items = $request->input('qpc_items');
        if (is_string($items)) $items = json_decode($items, true) ?? [];

        return [
            'entite_auditee'   => $request->input('entite_auditee') ?: ($request->input('intitule_qpc') ?: 'QPC'),
            'intitule_qpc'     => $request->input('intitule_qpc', ''),
            'fait_par'         => $request->input('fait_par', ''),
            'revue_par'        => $request->input('revue_par', ''),
            'date_fait'        => $request->input('date_fait'),
            'date_revue'       => $request->input('date_revue'),
            'qpc_items'        => json_encode($items, JSON_UNESCAPED_UNICODE),
        ];
    }

    // ── Payload Inertia ───────────────────────────────────────────
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $pdcList = DB::table('mission_phase_pdc')
            ->where('assignment_id', $assignmentId)
            ->select(['id','code','entite_auditee','intitule_qpc',
                      'validation_status','updated_at'])
            ->orderByDesc('created_at')
            ->get()->toArray();

        return array_merge(parent::buildPayload($missionId, $assignmentId, $auditor, $form), [
            'form'           => $form,
            'pdcList'        => $pdcList,
            'currentAuditor' => [
                'id'         => $auditor->id,
                'audit_code' => $auditor->audit_code,
                'last_name'  => $auditor->last_name,
                'first_name' => $auditor->first_name,
            ],
            'formUrl'     => url('/m/audit.core/ac/preparation/prise-de-connaissance'),
            'backUrl'     => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
        ]);
    }

    // ── Import Excel ──────────────────────────────────────────────
    public function importExcel(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            // Priorité à la feuille QPC_Template
            $sheet = $spreadsheet->getSheetByName('QPC_Template')
                  ?? $spreadsheet->getActiveSheet();

            $items  = [];
            $maxRow = $sheet->getHighestDataRow();

            // Les données commencent à la ligne 8 (lignes 1-7 = en-têtes)
            for ($r = 8; $r <= $maxRow; $r++) {
                $numVal  = trim((string)($sheet->getCell("A{$r}")->getValue() ?? ''));
                $code    = trim((string)($sheet->getCell("B{$r}")->getValue() ?? ''));
                $libelle = trim((string)($sheet->getCell("C{$r}")->getValue() ?? ''));
                $fichier = trim((string)($sheet->getCell("D{$r}")->getValue() ?? ''));

                // Ignorer les lignes vides
                if ($libelle === '' && $code === '') continue;

                // Colonne A : numéro réel (1,2,3…) = catégorie ; vide ou ↳ = item
                $isCategory = ($numVal !== '' && $numVal !== '↳' && is_numeric($numVal));

                if ($isCategory) {
                    $items[] = [
                        'type'    => 'cat',
                        'num'     => $numVal,
                        'code'    => $code,
                        'libelle' => $libelle,
                        'fichier' => '',
                    ];
                } else {
                    // Ligne item (colonne A vide)
                    $items[] = [
                        'type'    => 'item',
                        'code'    => $code,
                        'libelle' => $libelle,
                        'fichier' => $fichier,
                    ];
                }
            }

            return response()->json([
                'items' => $items,
                'count' => count($items),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lecture Excel : ' . $e->getMessage()
            ], 422);
        }
    }

    // ── PDF ───────────────────────────────────────────────────────
    public function pdf(Request $request, int $form): \Illuminate\Http\Response
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $pdc = DB::table($this->table)->where('id', $form)->first();
        if (!$pdc) abort(404);

        $role = $this->getRole($pdc->mission_id, $auditor->id);
        if ($pdc->validation_status !== 'validated' && !in_array($role, ['DM', 'CM'])) {
            abort(403, 'Seuls les QPC validés peuvent être téléchargés.');
        }

        $qpcItems = json_decode($pdc->qpc_items ?? '[]', true) ?? [];

        $mission = DB::table('mission_programmation as mp')
            ->leftJoin('missions as m',  'mp.mission_id', '=', 'm.id')
            ->leftJoin('entities as e',  'm.entity_id',   '=', 'e.id')
            ->where('mp.id', $pdc->mission_id)
            ->select(['mp.id','mp.code_mission','mp.libelle','e.name as entity_name'])
            ->first();

        $assignment = $this->getAssignment($pdc->assignment_id, $pdc->mission_id);

        $validatedBy = null;
        if ($pdc->validated_by) {
            $va = DB::table('auditors')->where('id', $pdc->validated_by)->first();
            $validatedBy = $va ? trim(($va->last_name ?? '') . ' ' . ($va->first_name ?? '')) : null;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.pdc-pdf', compact(
            'pdc', 'mission', 'assignment', 'qpcItems', 'validatedBy'
        ))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 150,
            'chroot'               => base_path(),
        ]);

        $filename = 'PDC-' . ($pdc->code ?? $form) . '.pdf';
        return $request->boolean('download', true)
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    // ── Soumettre ─────────────────────────────────────────────────
    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return back()->withErrors(['auth' => 'Non autorisé']);

        $row = DB::table($this->table)->where('id', $form)->first();
        if (!$row || $row->validation_status !== 'draft')
            return back()->withErrors(['status' => 'Statut invalide']);

        $role = $this->getRole($row->mission_id, $auditor->id);
        DB::table($this->table)->where('id', $form)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($row->assignment_id, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return back()->with('success', 'QPC soumis pour validation');
    }

    // ── Valider / Rejeter ─────────────────────────────────────────
    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return back()->withErrors(['auth' => 'Non autorisé']);

        $row  = DB::table($this->table)->where('id', $form)->first();
        $role = $this->getRole($row->mission_id, $auditor->id);

        if (!in_array($role, ['DM', 'CM']))
            return back()->withErrors(['role' => 'Droits insuffisants']);

        $action = $request->input('action', 'validated');
        $note   = $request->input('note');

        if ($action === 'validated') {
            DB::table($this->table)->where('id', $form)->update([
                'validation_status' => 'validated',
                'validated_at'      => now(),
                'validated_by'      => $auditor->id,
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($row->assignment_id, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        } else {
            DB::table($this->table)->where('id', $form)->update([
                'validation_status' => 'draft',
                'validation_note'   => $note,
                'updated_at'        => now(),
            ]);
            $this->log($row->assignment_id, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
        }

        return back()->with('success', $action === 'validated' ? 'QPC validé' : 'QPC rejeté');
    }
}