<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * ANALYSE DE CONFORMITÉ — AnalyseConformiteController
 * ════════════════════════════════════════════════════════════════════════
 * Même pattern que AnalyseProceduresController :
 *  - hérite de BasePhaseFormController
 *  - DB::connection('tenant')
 *  - Inertia page : dashboards/Auditor/Forms/AnalyseConformite
 *
 * Tables :
 *  - mission_phase_acc       → formulaire principal
 *  - acc_items               → exigences QCC
 *  - acc_ia_propositions     → propositions Mistral
 */
class AnalyseConformiteController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_acc';
    protected string $formCode    = 'analyse-conformite';
    protected string $codePrefix  = 'ACC';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseConformite';
    protected string $routeEdit   = 'auditor.ac.analyse-conformite.edit';

    protected array $validationRules = [
        'intitule_qcc' => 'nullable|string|max:255',
        'fait_par'     => 'nullable|string|max:255',
        'revue_par'    => 'nullable|string|max:255',
        'date_fait'    => 'nullable|date',
        'date_revue'   => 'nullable|date',
        'description'  => 'nullable|string',
    ];

    private const TBL_ITEMS = 'acc_items';
    private const TBL_IA    = 'acc_ia_propositions';

    // ── formData ───────────────────────────────────────────────────
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'intitule_qcc' => $request->input('intitule_qcc'),
            'fait_par'     => $request->input('fait_par'),
            'revue_par'    => $request->input('revue_par'),
            'date_fait'    => $request->input('date_fait')  ?: null,
            'date_revue'   => $request->input('date_revue') ?: null,
            'description'  => $request->input('description'),
        ];
    }

    // ── buildPayload ───────────────────────────────────────────────
    protected function buildPayload(int $missionId, int $assignmentId, Auditor $auditor, mixed $form = null): array
    {
        $role        = $this->getRole($missionId, $auditor->id);
        $items       = $form ? $this->loadItems($form->id) : [];
        $scoreGlobal = $this->calcScore($items);

        $accList = DB::connection('tenant')
            ->table($this->table)
            ->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'intitule_qcc', 'validation_status', 'score_global', 'updated_at'])
            ->orderByDesc('created_at')
            ->get()->toArray();

        $chatMessages = $this->getChatMessages($assignmentId, $auditor->id, $role);
        $formId       = $form?->id;
        $base         = url('/m/audit.core/ac/preparation/analyse-conformite');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $form,
                'items'          => $items,
                'scoreGlobal'    => $scoreGlobal,
                'accList'        => $accList,
                'auditorRole'    => $role,
                'canManage'      => in_array($role, ['DM', 'CM']),
                'currentAuditor' => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'      => $base,
                'urlStore'     => route('auditor.ac.analyse-conformite.store'),
                'urlUpdate'    => $formId ? route('auditor.ac.analyse-conformite.update',    $formId) : null,
                'urlSoumettre' => $formId ? route('auditor.ac.analyse-conformite.soumettre', $formId) : null,
                'urlValider'   => $formId ? route('auditor.ac.analyse-conformite.valider',   $formId) : null,
                'urlImporter'  => route('auditor.ac.analyse-conformite.importer'),
                'urlExporter'  => $formId ? route('auditor.ac.analyse-conformite.exporter',  $formId) : null,
                'urlIaItem'    => $formId ? route('auditor.ac.analyse-conformite.ia.item',    $formId) : null,
                'urlIaSynthese'=> $formId ? route('auditor.ac.analyse-conformite.ia.synthese',$formId) : null,
                'urlIndex'     => route('audit.ac.preparation.analyse-conformite'),
                'backUrl'      => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
                'chatMessages' => $chatMessages,
                'chatBaseUrl'  => url('/api/mission-phase-chat'),
            ]
        );
    }

    // ── store ──────────────────────────────────────────────────────
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

        $assignment = DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->first();
        if (!$assignment || $assignment->status === 'pending')
            return response()->json(['success' => false, 'message' => 'Démarrez la phase avant de remplir ce formulaire.'], 422);

        $existing = DB::connection('tenant')->table($this->table)->where('assignment_id', $assignmentId)->first();
        if ($existing) return $this->update($request, $existing->id);

        $id = DB::connection('tenant')->table($this->table)->insertGetId(array_merge(
            $this->formData($request, $auditor),
            [
                'assignment_id'     => $assignmentId,
                'mission_id'        => $missionId,
                'code'              => $this->genCode($missionId),
                'validation_status' => 'draft',
                'score_global'      => null,
                'created_by'        => $auditor->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        ));

        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');
        $this->syncItems($request, $id);

        return response()->json([
            'success' => true,
            'form'    => DB::connection('tenant')->table($this->table)->where('id', $id)->first(),
            'message' => 'QCC créé avec succès.',
        ]);
    }

    // ── update ─────────────────────────────────────────────────────
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
                'validated' => 'Formulaire validé — modification impossible.',
                'in_review' => 'Soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)
            ->update(array_merge($this->formData($request, $auditor), ['updated_at' => now()]));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $this->syncItems($request, $formId);
        $this->recalcScore($formId);

        return response()->json([
            'success' => true,
            'form'    => DB::connection('tenant')->table($this->table)->where('id', $formId)->first(),
            'message' => 'QCC mis à jour.',
        ]);
    }

    // ── syncItems ──────────────────────────────────────────────────
    private function syncItems(Request $request, int $accId): void
    {
        $raw   = $request->input('items', '[]');
        $items = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);
        if (empty($items) || !is_array($items)) return;

        $existingIds = DB::connection('tenant')->table(self::TBL_ITEMS)->where('acc_id', $accId)->pluck('id')->toArray();
        $keptIds     = [];

        foreach ($items as $i => $item) {
            $itemId = !empty($item['id']) ? (int) $item['id'] : null;
            $rep    = strtoupper(trim($item['reponse'] ?? ''));

            $data = [
                'acc_id'         => $accId,
                'type'           => $item['type']           ?? 'item',
                'ref_article'    => $item['ref_article']    ?? $item['ref']   ?? null,
                'libelle_norme'  => $item['libelle_norme']  ?? $item['ref']   ?? null,
                'exigence_norme' => $item['exigence_norme'] ?? $item['label'] ?? null,
                'reponse'        => in_array($rep, ['O','N','SO']) ? $rep : null,
                'forces'         => $item['forces']         ?? null,
                'faiblesses'     => $item['faiblesses']     ?? null,
                'objectif'       => $item['objectif']       ?? null,
                'observations'   => $item['observations']   ?? null,
                'score'          => $this->calcItemScore(in_array($rep, ['O','N','SO']) ? $rep : null),
                'ordre'          => $i,
                'updated_at'     => now(),
            ];

            if ($itemId) {
                DB::connection('tenant')->table(self::TBL_ITEMS)->where('id', $itemId)->update($data);
                $keptIds[] = $itemId;
            } else {
                $data['created_at'] = now();
                $keptIds[] = DB::connection('tenant')->table(self::TBL_ITEMS)->insertGetId($data);
            }
        }

        $toDelete = array_diff($existingIds, $keptIds);
        if (!empty($toDelete)) {
            DB::connection('tenant')->table(self::TBL_IA)->whereIn('item_id', $toDelete)->delete();
            DB::connection('tenant')->table(self::TBL_ITEMS)->whereIn('id', $toDelete)->delete();
        }
    }

    // ── soumettre ──────────────────────────────────────────────────
    public function soumettre(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!$this->canAccess($missionId, $assignmentId, $auditor)) return response()->json(['error' => 'Accès refusé'], 403);
        if ($row->validation_status === 'validated')                 return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review')                 return response()->json(['error' => 'Déjà soumis'], 422);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');

        return response()->json(['success' => true, 'status' => 'in_review']);
    }

    // ── valider ────────────────────────────────────────────────────
    public function valider(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $missionId    = (int) ($request->input('mission_id')    ?? $row->mission_id);
        $assignmentId = (int) ($request->input('assignment_id') ?? $row->assignment_id);
        $role         = $this->getRole($missionId, $auditor->id);

        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent valider'], 403);
        if ($row->validation_status !== 'in_review') return response()->json(['error' => 'Formulaire non soumis'], 422);

        $action = $request->input('action', 'validate');
        $note   = $request->input('note');

        if ($action === 'reject') {
            if (!$note) return response()->json(['error' => 'Motif obligatoire'], 422);
            DB::connection('tenant')->table($this->table)->where('id', $form)->update([
                'validation_status' => 'draft', 'validation_note' => $note, 'updated_at' => now(),
            ]);
            $this->log($assignmentId, $auditor->id, $role, 'rejected', 'in_review', 'draft', $note);
            return response()->json(['success' => true, 'status' => 'draft', 'action' => 'rejected']);
        }

        if ($role !== 'DM') return response()->json(['error' => 'Seul le DM valide définitivement'], 403);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by'      => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);

        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    // ── destroy ────────────────────────────────────────────────────
    public function destroy(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);

        $itemIds = DB::connection('tenant')->table(self::TBL_ITEMS)->where('acc_id', $form)->pluck('id');
        if ($itemIds->isNotEmpty()) DB::connection('tenant')->table(self::TBL_IA)->whereIn('item_id', $itemIds)->delete();
        DB::connection('tenant')->table(self::TBL_ITEMS)->where('acc_id', $form)->delete();
        DB::connection('tenant')->table($this->table)->where('id', $form)->delete();

        return response()->json(['success' => true]);
    }

    // ── importer Excel ─────────────────────────────────────────────
    public function importer(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

            $file   = $request->file('file');
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $rows   = $reader->load($file->getPathname())->getActiveSheet()->toArray(null, true, true, true);

            $items      = [];
            $currentRef = null;
            $currentLib = null;

            foreach ($rows as $k => $row) {
                if ($k <= 1) continue;
                $col0 = trim((string)($row['A'] ?? ''));
                $col1 = trim((string)($row['B'] ?? ''));
                $col2 = trim((string)($row['C'] ?? ''));
                if (empty($col0) && empty($col1) && empty($col2)) continue;
                if (in_array($col0, ['QCC','TITRE QCC','Réf. Article','Ref Article'])) continue;

                if (!empty($col0) && preg_match('/^[A-Z]{2,4}\s*\d+/i', $col0)) {
                    $currentRef = $col0;
                    $currentLib = $col1;
                    $items[] = ['type' => 'section', 'ref_article' => $col0, 'libelle_norme' => $col1, 'exigence_norme' => null, 'reponse' => null, 'forces' => '', 'faiblesses' => '', 'objectif' => '', 'observations' => ''];
                }

                if (!empty($col2)) {
                    $rep = strtoupper(trim((string)($row['D'] ?? '')));
                    $items[] = [
                        'type' => 'item', 'ref_article' => $currentRef, 'libelle_norme' => $currentLib,
                        'exigence_norme' => $col2,
                        'reponse'     => in_array($rep, ['O','N','SO']) ? $rep : null,
                        'forces'      => trim((string)($row['E'] ?? '')),
                        'faiblesses'  => trim((string)($row['F'] ?? '')),
                        'objectif'    => trim((string)($row['G'] ?? '')),
                        'observations'=> trim((string)($row['J'] ?? '')),
                    ];
                }
            }

            return response()->json(['success' => true, 'items' => $items, 'count' => count(array_filter($items, fn($i) => $i['type'] === 'item'))]);
        } catch (\Exception $e) {
            Log::error('[ACC] importer: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── exporter Excel ─────────────────────────────────────────────
    public function exporter(int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) abort(403);

        $form  = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$form) abort(404);
        $items = $this->loadItems($formId);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('QCC');
        $headers     = ['Réf. Article','Libellé Norme','Exigence Norme','O/N/SO','Forces','Faiblesses','Objectif','Observations'];
        foreach ($headers as $i => $h) $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);

        $r = 2;
        foreach ($items as $item) {
            $sheet->setCellValueByColumnAndRow(1, $r, $item->ref_article    ?? '');
            $sheet->setCellValueByColumnAndRow(2, $r, $item->libelle_norme  ?? '');
            $sheet->setCellValueByColumnAndRow(3, $r, $item->exigence_norme ?? '');
            $sheet->setCellValueByColumnAndRow(4, $r, $item->reponse        ?? '');
            $sheet->setCellValueByColumnAndRow(5, $r, $item->forces         ?? '');
            $sheet->setCellValueByColumnAndRow(6, $r, $item->faiblesses     ?? '');
            $sheet->setCellValueByColumnAndRow(7, $r, $item->objectif       ?? '');
            $sheet->setCellValueByColumnAndRow(8, $r, $item->observations   ?? '');
            $r++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'acc_');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmp);
        return response()->download($tmp, 'QCC_' . ($form->code ?? $formId) . '_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── iaItem ─────────────────────────────────────────────────────
    public function iaItem(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $apiKey = config('services.mistral.api_key');
        if (empty($apiKey)) return response()->json(['success' => false, 'error' => 'Service IA non configuré'], 500);

        $reponse     = $request->input('reponse');
        $statutTexte = match ($reponse) { 'O' => 'CONFORME', 'N' => 'NON CONFORME', 'SO' => 'SANS OBJET', default => 'NON ÉVALUÉ' };

        $systemPrompt = "Tu es un expert en audit qualité et contrôle de conformité. Tu réponds UNIQUEMENT en JSON valide, sans backticks.";
        $userPrompt   = "Analyse cet item QCC et génère une recommandation.\n\n"
            . "Libellé: "    . $request->input('libelle',    '') . "\n"
            . "Exigence: "   . $request->input('exigence',   '') . "\n"
            . "Statut: "     . $statutTexte . "\n"
            . "Forces: "     . ($request->input('forces',    '') ?: 'Aucune') . "\n"
            . "Faiblesses: " . ($request->input('faiblesses','') ?: 'Aucune') . "\n"
            . "Objectif: "   . ($request->input('objectif',  '') ?: 'Non défini') . "\n\n"
            . "JSON attendu:\n"
            . '{"recommendation":"...","type":"amelioration|validation|alerte","priorite":"haute|moyenne|faible","actions":["..."],"echeance_suggere":"court_terme|moyen_terme|long_terme","indicateurs":["..."]}';

        try {
            $result = $this->callMistral($apiKey, $systemPrompt, $userPrompt, 800);

            $itemId = $request->input('item_id');
            if ($itemId) {
                DB::connection('tenant')->table(self::TBL_IA)->insert([
                    'item_id'      => (int) $itemId,
                    'contenu'      => $result['recommendation'] ?? '',
                    'type'         => $result['type']           ?? 'amelioration',
                    'priorite'     => $result['priorite']       ?? 'moyenne',
                    'actions'      => json_encode($result['actions']     ?? []),
                    'indicateurs'  => json_encode($result['indicateurs'] ?? []),
                    'echeance'     => $result['echeance_suggere'] ?? null,
                    'generated_by' => 'mistral',
                    'generated_at' => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            return response()->json(['success' => true, 'proposition' => $result]);
        } catch (\Exception $e) {
            Log::error('[ACC] iaItem: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── iaSynthese ─────────────────────────────────────────────────
    public function iaSynthese(Request $request, int $formId)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $apiKey = config('services.mistral.api_key');
        if (empty($apiKey)) return response()->json(['success' => false, 'error' => 'Service IA non configuré'], 500);

        $form = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        if (!$form) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $items  = $this->loadItems($formId);
        $score  = $this->calcScore($items);
        $resume = collect($items)->map(fn($i) => [
            'libelle'    => $i->libelle_norme,
            'reponse'    => $i->reponse,
            'forces'     => $i->forces,
            'faiblesses' => $i->faiblesses,
        ])->toJson();

        $systemPrompt = "Tu es un expert en audit qualité. Tu réponds UNIQUEMENT en JSON valide, sans backticks.";
        $userPrompt   = "Génère une synthèse globale pour ce QCC.\n\nQCC: {$form->intitule_qcc}\nScore: {$score}%\nItems: {$resume}\n\n"
            . "JSON:\n"
            . '{"niveau_maturite":"initial|en_developpement|defini|gere|optimise","synthese_executive":"...","points_forts":["..."],"axes_amelioration":["..."],"risques_critiques":["..."],"plan_action_prioritaire":[{"action":"...","echeance":"...","responsable":"..."}],"prochaine_etape":"..."}';

        try {
            $result = $this->callMistral($apiKey, $systemPrompt, $userPrompt, 1500);
            return response()->json(['success' => true, 'synthese' => $result]);
        } catch (\Exception $e) {
            Log::error('[ACC] iaSynthese: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function loadItems(int $accId): array
    {
        return DB::connection('tenant')->table(self::TBL_ITEMS)
            ->where('acc_id', $accId)->orderBy('ordre')->get()->toArray();
    }

    private function calcScore(array $items): ?float
    {
        $evaluated = array_filter($items, fn($i) => !empty(is_object($i) ? $i->reponse : ($i['reponse'] ?? null)));
        if (empty($evaluated)) return null;
        $total = array_sum(array_map(fn($i) => $this->calcItemScore(is_object($i) ? $i->reponse : ($i['reponse'] ?? null)) ?? 0, $evaluated));
        return round($total / count($evaluated), 2);
    }

    private function calcItemScore(?string $r): ?int
    {
        return match ($r) { 'O' => 100, 'N' => 0, 'SO' => 50, default => null };
    }

    private function recalcScore(int $accId): void
    {
        $score = $this->calcScore($this->loadItems($accId));
        DB::connection('tenant')->table($this->table)->where('id', $accId)
            ->update(['score_global' => $score, 'updated_at' => now()]);
    }

    private function callMistral(string $apiKey, string $system, string $user, int $maxTokens): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(90)->post('https://api.mistral.ai/v1/chat/completions', [
            'model'       => 'mistral-large-latest',
            'max_tokens'  => $maxTokens,
            'temperature' => 0.4,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
        ]);

        if (!$response->ok()) throw new \Exception('Mistral error: ' . $response->status());

        $content = trim($response->json('choices.0.message.content') ?? '');
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $decoded = json_decode(trim($content), true);
        if (!is_array($decoded)) throw new \Exception('Réponse IA invalide');
        return $decoded;
    }

    // ── getRole ────────────────────────────────────────────────────
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

    // ── getChatMessages ────────────────────────────────────────────
    private function getChatMessages(int $assignmentId, int $auditorId, string $role): array
    {
        if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('mission_phase_chat')) return [];

        return DB::connection('tenant')
            ->table('mission_phase_chat as c')
            ->join('auditors as a', 'c.author_id', '=', 'a.id')
            ->where('c.assignment_id', $assignmentId)
            ->where('c.form_code', $this->formCode)
            ->where(function ($q) use ($auditorId, $role) {
                if ($role === 'DM') { $q->whereRaw('1=1'); return; }
                $visible = match ($role) { 'CM' => ['CM','AS','AJ'], 'AS' => ['AS','AJ'], default => ['AJ'] };
                $q->where('c.author_id', $auditorId)->orWhereIn('c.author_role', $visible);
            })
            ->select([
                'c.id', 'c.content', 'c.type', 'c.priority', 'c.author_id', 'c.author_role',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''),' ',COALESCE(a.first_name,''))) as author_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'),COALESCE(LEFT(a.first_name,1),'?'))) as author_initials"),
                DB::raw("DATE_FORMAT(c.created_at,'%d/%m/%Y %H:%i') as created_at_fr"),
                DB::raw("CASE WHEN c.author_id = {$auditorId} THEN 1 ELSE 0 END as is_mine"),
            ])
            ->orderBy('c.created_at')->get()
            ->map(fn($m) => tap((array)$m, fn(&$m) => $m['is_mine'] = (bool)$m['is_mine']))
            ->toArray();
    }
}