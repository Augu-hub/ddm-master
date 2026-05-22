<?php

namespace App\Http\Controllers\Auditor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Param\Auditor;

/**
 * ════════════════════════════════════════════════════════════════════════
 * ANALYSE DES MARCHÉS — AnalyseMarchesController
 * ════════════════════════════════════════════════════════════════════════
 *
 * Format QCM Excel (3 colonnes) :
 *   Col A = Référence / Titre de la section (ex: "Rapprochement entre les fournisseurs…")
 *   Col B = Étape principale (sous-étape de vérification)
 *   Col C = Référence de procédure (ex: "ref2", "reference 2.16")
 *
 * Règle d'import :
 *   - L1/L2 = en-têtes (QEM / TITRE QEM) → skip
 *   - Col A renseignée → nouvelle section/marché (intitulé = col A, ref_proc = col C)
 *   - Col A vide + col B renseignée → étape de la section courante (ref_etape = col C)
 *
 * Tables :
 *   mission_phase_amq   → formulaire principal QEM
 *   amq_marches         → sections/thèmes du QCM
 *   amq_etapes          → étapes de vérification par section
 *   amq_objectifs       → objectifs d'audit
 *   amq_documents       → documents joints
 */
class AnalyseMarchesController extends BasePhaseFormController
{
    protected string $table       = 'mission_phase_amq';
    protected string $formCode    = 'analyse-marches';
    protected string $codePrefix  = 'AMQ';
    protected string $inertiaPage = 'dashboards/Auditor/Forms/AnalyseMarches';
    protected string $routeEdit   = 'auditor.ac.analyse-marches.edit';

    protected array $validationRules = [
        'intitule_qem'      => 'nullable|string|max:500',
        'fait_par'          => 'nullable|string|max:255',
        'revue_par'         => 'nullable|string|max:255',
        'date_fait'         => 'nullable|date',
        'date_revue'        => 'nullable|date',
        'commentaire_global'=> 'nullable|string',
    ];

    private const TBL_MARCH = 'amq_marches';
    private const TBL_ETAPE = 'amq_etapes';
    private const TBL_OBJ   = 'amq_objectifs';
    private const TBL_DOC   = 'amq_documents';

    // ══════════════════════════════════════════════════════════════
    // formData
    // ══════════════════════════════════════════════════════════════
    protected function formData(Request $request, Auditor $auditor): array
    {
        return [
            'intitule_qem'       => $request->input('intitule_qem'),
            'fait_par'           => $request->input('fait_par'),
            'revue_par'          => $request->input('revue_par'),
            'date_fait'          => $request->input('date_fait')  ?: null,
            'date_revue'         => $request->input('date_revue') ?: null,
            'commentaire_global' => $request->input('commentaire_global'),
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

        $role = $this->getRole($missionId, $auditor->id);

        $phaseAuditeurs = DB::connection('tenant')
            ->table('mission_phase_assignment_auditeurs as mpaa')
            ->join('auditors as a', 'a.id', '=', 'mpaa.auditeur_id')
            ->where('mpaa.assignment_id', $assignmentId)
            ->select(
                'a.id', 'a.audit_code', 'a.last_name', 'a.first_name',
                'mpaa.role_code',
                DB::raw("TRIM(CONCAT(COALESCE(a.last_name,''), ' ', COALESCE(a.first_name,''))) as full_name"),
                DB::raw("UPPER(CONCAT(COALESCE(LEFT(a.last_name,1),'?'), COALESCE(LEFT(a.first_name,1),'?'))) as initials")
            )
            ->orderByRaw("FIELD(mpaa.role_code,'DM','CM','AS','AJ')")
            ->orderBy('a.last_name')
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
            ])
            ->toArray();

        $marchesData = $form ? $this->loadMarchesData($form->id) : [];

        $amqList = DB::connection('tenant')
            ->table($this->table)->where('assignment_id', $assignmentId)
            ->select(['id', 'code', 'intitule_qem', 'validation_status', 'fait_par', 'updated_at'])
            ->orderByDesc('created_at')->get()->toArray();

        $formId = $form?->id;
        $base   = url('/m/audit.core/ac/preparation/analyse-marches');

        return array_merge(
            parent::buildPayload($missionId, $assignmentId, $auditor, $form),
            [
                'form'           => $form,
                'marchesData'    => $marchesData,
                'phaseAuditeurs' => $phaseAuditeurs,
                'amqList'        => $amqList,
                'currentAuditor' => [
                    'id'         => $auditor->id,
                    'audit_code' => $auditor->audit_code,
                    'last_name'  => $auditor->last_name,
                    'first_name' => $auditor->first_name,
                ],
                'formUrl'        => $base,
                'urlStore'       => route('auditor.ac.analyse-marches.store'),
                'urlUpdate'      => $formId ? route('auditor.ac.analyse-marches.update',    $formId) : null,
                'urlSoumettre'   => $formId ? route('auditor.ac.analyse-marches.soumettre', $formId) : null,
                'urlValider'     => $formId ? route('auditor.ac.analyse-marches.valider',   $formId) : null,
                'urlAiSuggest'   => route('auditor.ac.analyse-marches.ai-suggest'),
                'urlImportExcel' => route('auditor.ac.analyse-marches.import-excel'),
                'urlDocUpload'   => route('auditor.ac.analyse-marches.doc-upload'),
                'urlDeleteDoc'   => route('auditor.ac.analyse-marches.delete-doc'),
                'urlIndex'       => route('audit.ac.preparation.analyse-marches'),
                'backUrl'        => url("/m/audit.core/auditor/missions/{$missionId}/phases"),
            ]
        );
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
            'assignment_id'     => $assignmentId,
            'mission_id'        => $missionId,
            'code'              => $this->genCode($missionId),
            'validation_status' => 'draft',
            'created_by'        => $auditor->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $id   = DB::connection('tenant')->table($this->table)->insertGetId($data);
        $role = $this->getRole($missionId, $auditor->id);
        $this->log($assignmentId, $auditor->id, $role, 'saved', null, 'draft');
        $this->syncMarches($request, $id, $auditor);

        $form = DB::connection('tenant')->table($this->table)->where('id', $id)->first();
        return response()->json(['success' => true, 'form' => $form, 'message' => 'Analyse créée.']);
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
                'validated' => 'Formulaire validé — modification impossible.',
                'in_review' => 'Formulaire soumis — seuls CM/DM peuvent modifier.',
                default     => 'Modification non autorisée.',
            }], 403);

        DB::connection('tenant')->table($this->table)->where('id', $formId)
            ->update(array_merge($this->formData($request, $auditor), ['updated_at' => now()]));

        $this->log($assignmentId, $auditor->id, $role, 'saved', $row->validation_status, $row->validation_status);
        $this->syncMarches($request, $formId, $auditor);

        $updated = DB::connection('tenant')->table($this->table)->where('id', $formId)->first();
        return response()->json(['success' => true, 'form' => $updated, 'message' => 'Analyse mise à jour.']);
    }

    // ══════════════════════════════════════════════════════════════
    // syncMarches
    // ══════════════════════════════════════════════════════════════
    private function syncMarches(Request $request, int $amqId, Auditor $auditor): void
    {
        $raw    = $request->input('marches', '[]');
        $marches = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);
        if (empty($marches) || !is_array($marches)) return;

        $existingIds = DB::connection('tenant')
            ->table(self::TBL_MARCH)->where('amq_id', $amqId)->pluck('id')->toArray();
        $keptIds = [];

        foreach ($marches as $mi => $marche) {
            $marcheId = !empty($marche['id']) ? (int) $marche['id'] : null;

            $marcheData = [
                'amq_id'        => $amqId,
                'ordre'         => $mi + 1,
                'reference'     => $marche['reference']     ?? null,
                'ref_procedure' => $marche['ref_procedure'] ?? null,
                'intitule'      => $marche['intitule']      ?? '',
                'objet'         => $marche['objet']         ?? null,
                'montant'       => $marche['montant']       ?? null,
                'attributaire'  => $marche['attributaire']  ?? null,
                'date_marche'   => $marche['date_marche']   ?? null,
                'commentaire'   => $marche['commentaire']   ?? null,
                'forces'        => $this->encodeJson($marche['forces']     ?? null),
                'faiblesses'    => $this->encodeJson($marche['faiblesses'] ?? null),
                'updated_at'    => now(),
            ];

            if ($marcheId) {
                DB::connection('tenant')->table(self::TBL_MARCH)
                    ->where('id', $marcheId)->update($marcheData);
            } else {
                $marcheData['created_at'] = now();
                $marcheId = DB::connection('tenant')->table(self::TBL_MARCH)->insertGetId($marcheData);
            }

            $keptIds[] = $marcheId;
            $this->syncEtapes($amqId, $marcheId, $marche['etapes']    ?? []);
            $this->syncObjectifs($amqId, $marcheId, $marche['objectifs'] ?? []);
        }

        $toDelete = array_diff($existingIds, $keptIds);
        if (!empty($toDelete)) {
            DB::connection('tenant')->table(self::TBL_ETAPE)->whereIn('marche_id', $toDelete)->delete();
            DB::connection('tenant')->table(self::TBL_OBJ)->whereIn('marche_id', $toDelete)->delete();
            DB::connection('tenant')->table(self::TBL_DOC)->whereIn('marche_id', $toDelete)->delete();
            DB::connection('tenant')->table(self::TBL_MARCH)->whereIn('id', $toDelete)->delete();
        }
    }

    private function syncEtapes(int $amqId, int $marcheId, array $etapes): void
    {
        DB::connection('tenant')->table(self::TBL_ETAPE)
            ->where('amq_id', $amqId)->where('marche_id', $marcheId)->delete();

        foreach ($etapes as $i => $etape) {
            if (empty($etape['libelle'])) continue;
            DB::connection('tenant')->table(self::TBL_ETAPE)->insert([
                'amq_id'      => $amqId,
                'marche_id'   => $marcheId,
                'ordre'       => $i + 1,
                'ref_etape'   => $etape['ref_etape']   ?? null,
                'libelle'     => $etape['libelle'],
                'statut'      => $etape['statut']      ?? null,
                'observation' => $etape['observation'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    private function syncObjectifs(int $amqId, int $marcheId, array $objectifs): void
    {
        DB::connection('tenant')->table(self::TBL_OBJ)
            ->where('amq_id', $amqId)->where('marche_id', $marcheId)->delete();

        foreach ($objectifs as $i => $obj) {
            if (empty($obj['libelle'])) continue;
            DB::connection('tenant')->table(self::TBL_OBJ)->insert([
                'amq_id'      => $amqId,
                'marche_id'   => $marcheId,
                'ordre'       => $i + 1,
                'libelle'     => $obj['libelle'],
                'atteint'     => $obj['atteint']     ?? null,
                'commentaire' => $obj['commentaire'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    private function encodeJson(mixed $v): ?string
    {
        if ($v === null) return null;
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        if (is_string($v)) return $v;
        return null;
    }

    // ══════════════════════════════════════════════════════════════
    // POST /import-excel
    //
    // Format QCM (feuille "SANS OBJ") :
    //   L1  → "QEM"
    //   L2  → "TITRE QEM"   ← on récupère col A comme titre du QEM
    //   L3+ → col A = titre section | col B = étape | col C = réf procédure
    // ══════════════════════════════════════════════════════════════
    public function importExcel(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);
            $file = $request->file('file');

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, true);

            $val = fn($row, $col) => trim((string)($row[$col] ?? ''));

            // Ligne 2 col A = titre QEM (optionnel)
            $titreQem = '';
            if (isset($rows[2]) && !empty(trim((string)($rows[2]['A'] ?? '')))) {
                $titreQem = trim((string)($rows[2]['A'] ?? ''));
            }

            $marches       = [];
            $currentMarche = null;

            foreach ($rows as $ri => $row) {
                // Ignorer les 2 premières lignes d'en-tête (QEM + TITRE QEM)
                if ($ri <= 2) continue;

                $colA = $val($row, 'A');
                $colB = $val($row, 'B');
                $colC = $val($row, 'C');

                if (empty($colA) && empty($colB)) continue;

                // Nouvelle section : col A renseignée
                if (!empty($colA)) {
                    if ($currentMarche !== null) $marches[] = $currentMarche;

                    $currentMarche = [
                        'reference'     => '',
                        'ref_procedure' => $colC,   // réf procédure de la section
                        'intitule'      => $colA,   // titre = col A
                        'objet'         => '',
                        'montant'       => null,
                        'attributaire'  => '',
                        'date_marche'   => null,
                        'commentaire'   => '',
                        'etapes'        => [],
                        'objectifs'     => [],
                        'forces'        => [],
                        'faiblesses'    => [],
                    ];

                    // Si col B aussi renseignée sur la même ligne → 1ère étape
                    if (!empty($colB)) {
                        $currentMarche['etapes'][] = [
                            'ref_etape'   => $colC,
                            'libelle'     => $colB,
                            'statut'      => null,
                            'observation' => '',
                        ];
                        // La ref de section et d'étape coïncident, on efface la ref section
                        // pour éviter doublon — on garde uniquement sur l'étape
                        $currentMarche['ref_procedure'] = '';
                    }
                }
                // Étape de la section courante : col A vide, col B renseignée
                elseif (!empty($colB) && $currentMarche !== null) {
                    $currentMarche['etapes'][] = [
                        'ref_etape'   => $colC,
                        'libelle'     => $colB,
                        'statut'      => null,
                        'observation' => '',
                    ];
                }
            }

            if ($currentMarche !== null) $marches[] = $currentMarche;

            return response()->json([
                'success'   => true,
                'marches'   => $marches,
                'count'     => count($marches),
                'titre_qem' => $titreQem,
            ]);

        } catch (\Exception $e) {
            Log::error('[AMQ] importExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // POST /ai-suggest
    // ══════════════════════════════════════════════════════════════
    public function aiSuggest(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $validated = $request->validate([
                'type'            => 'required|in:objectifs_marche,forces_faiblesses,etapes_marche,analyse_complete',
                'marche_intitule' => 'nullable|string|max:500',
                'marche_objet'    => 'nullable|string|max:1000',
                'etapes'          => 'nullable|array',
                'mission_title'   => 'nullable|string|max:500',
                'entity_name'     => 'nullable|string|max:300',
                'mission_id'      => 'nullable|integer',
                'prompt'          => 'nullable|string|max:2000',
            ]);

            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) return response()->json(['success' => false, 'error' => 'Service IA non configuré'], 500);

            $ctx = '';
            if (!empty($validated['mission_title'])) {
                $ctx = "Mission d'audit : {$validated['mission_title']}. Entité : {$validated['entity_name']}.";
            }

            $result = match ($validated['type']) {
                'objectifs_marche'  => $this->suggestObjectifs($apiKey, $validated, $ctx),
                'forces_faiblesses' => $this->suggestForcesFaiblesses($apiKey, $validated, $ctx),
                'etapes_marche'     => $this->suggestEtapes($apiKey, $validated, $ctx),
                'analyse_complete'  => $this->suggestAnalyseComplete($apiKey, $validated, $ctx),
            };

            return response()->json(array_merge(['success' => true], $result));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[AMQ] aiSuggest: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function suggestObjectifs(string $k, array $d, string $ctx): array
    {
        $marche = $d['marche_intitule'] ?? 'Thème';
        $objet  = $d['marche_objet']    ?? '';
        $prompt = <<<PROMPT
Tu es expert en audit interne des marchés publics (OHADA, Code des marchés publics).
{$ctx}
Thème / Marché analysé : "{$marche}" — {$objet}

Génère des objectifs d'audit pertinents.
Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,"objectifs":[
  {"libelle":"Vérifier la régularité de la procédure de passation du marché","atteint":null},
  {"libelle":"S'assurer de la qualification technique et financière du titulaire","atteint":null}
]}
Génère 5 à 8 objectifs précis.
PROMPT;
        return $this->callMistral($k, $prompt, 1200);
    }

    private function suggestForcesFaiblesses(string $k, array $d, string $ctx): array
    {
        $marche = $d['marche_intitule'] ?? 'Thème';
        $objet  = $d['marche_objet']    ?? '';
        $etapes = '';
        if (!empty($d['etapes']) && is_array($d['etapes'])) {
            $etapes = implode("\n", array_map(function ($e) {
                $st = $e['statut'] ?? '?';
                return "- {$e['libelle']} [Statut: {$st}]" . (!empty($e['observation']) ? " — {$e['observation']}" : '');
            }, array_slice($d['etapes'], 0, 20)));
        }
        $prompt = <<<PROMPT
Tu es expert en audit interne.
{$ctx}
Thème : "{$marche}" — {$objet}
Étapes évaluées :
{$etapes}

Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,
 "forces":["Point fort 1","Point fort 2"],
 "faiblesses":["Faiblesse 1","Faiblesse 2"]}
Génère 2 à 5 forces et 2 à 5 faiblesses.
PROMPT;
        return $this->callMistral($k, $prompt, 1000);
    }

    private function suggestEtapes(string $k, array $d, string $ctx): array
    {
        $marche = $d['marche_intitule'] ?? 'Thème';
        $objet  = $d['marche_objet']    ?? '';
        $prompt = <<<PROMPT
Tu es expert en audit des marchés publics.
{$ctx}
Thème : "{$marche}" — {$objet}

Génère des étapes de vérification QCM concrètes (sélection d'échantillon, rapprochement,
vérification de signature, observation, extrapolation…).

Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,"etapes":[
  {"ref_etape":"","libelle":"Sélection d'un échantillon significatif de documents","statut":null,"observation":""},
  {"ref_etape":"","libelle":"Vérification de la signature des responsables hiérarchiques","statut":null,"observation":""},
  {"ref_etape":"","libelle":"Extrapolation des résultats à l'ensemble de la population","statut":null,"observation":""}
]}
Génère 3 à 6 étapes.
PROMPT;
        return $this->callMistral($k, $prompt, 1000);
    }

    private function suggestAnalyseComplete(string $k, array $d, string $ctx): array
    {
        $titre = $d['marche_intitule'] ?? ($d['prompt'] ?? 'Thème');
        $objet = $d['marche_objet']    ?? '';
        $prompt = <<<PROMPT
Tu es expert en audit interne des marchés publics.
{$ctx}
Thème à analyser : "{$titre}" — {$objet}

Génère une fiche QCM complète.
Retourne UNIQUEMENT ce JSON sans markdown :
{"success":true,
 "intitule":"{$titre}",
 "objet":"Description de l'objet",
 "etapes":[
   {"ref_etape":"","libelle":"Sélection d'un échantillon significatif","statut":null,"observation":""},
   {"ref_etape":"","libelle":"Vérification de la présence dans la liste agréée","statut":null,"observation":""},
   {"ref_etape":"","libelle":"Extrapolation des résultats à l'ensemble","statut":null,"observation":""}
 ],
 "objectifs":[
   {"libelle":"Vérifier la conformité de la procédure","atteint":null},
   {"libelle":"S'assurer de la complétude des justificatifs","atteint":null}
 ],
 "forces":[],
 "faiblesses":[]}
Génère 3 à 6 étapes et 2 à 4 objectifs.
PROMPT;
        return $this->callMistral($k, $prompt, 1500);
    }

    private function callMistral(string $apiKey, string $prompt, int $maxTokens): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(90)->post('https://api.mistral.ai/v1/chat/completions', [
            'model'       => 'mistral-medium-latest',
            'max_tokens'  => $maxTokens,
            'temperature' => 0.4,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!$response->ok()) throw new \Exception('Mistral error: ' . $response->status());

        $content = trim($response->json('choices.0.message.content') ?? '');
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/```\s*$/',            '', $content);
        $decoded = json_decode(trim($content), true);
        if (!is_array($decoded)) throw new \Exception('Réponse IA invalide');
        return $decoded;
    }

    // ══════════════════════════════════════════════════════════════
    // POST /doc-upload
    // ══════════════════════════════════════════════════════════════
    public function docUpload(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        try {
            $request->validate([
                'file'      => 'required|file|max:20480',
                'amq_id'    => 'required|integer',
                'marche_id' => 'nullable|integer',
            ]);

            $file     = $request->file('file');
            $amqId    = (int) $request->input('amq_id');
            $marcheId = (int) ($request->input('marche_id') ?? 0);
            $path     = $file->store("amq/{$amqId}/docs", 'public');

            $docData = [
                'amq_id'        => $amqId,
                'marche_id'     => $marcheId ?: null,
                'name'          => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'url'           => Storage::disk('public')->url($path),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'size_label'    => $this->formatSize($file->getSize()),
                'extension'     => $file->getClientOriginalExtension(),
                'uploaded_by'   => $auditor->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $docId = DB::connection('tenant')->table(self::TBL_DOC)->insertGetId($docData);
            $docData['id'] = $docId;
            return response()->json(['success' => true, 'document' => $docData]);

        } catch (\Exception $e) {
            Log::error('[AMQ] docUpload: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // DELETE /delete-doc
    // ══════════════════════════════════════════════════════════════
    public function deleteDoc(Request $request)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $docId = (int) $request->input('doc_id', 0);
        if (!$docId) return response()->json(['error' => 'doc_id manquant'], 422);

        $doc = DB::connection('tenant')->table(self::TBL_DOC)->where('id', $docId)->first();
        if (!$doc) return response()->json(['error' => 'Document introuvable'], 404);

        if (!empty($doc->path)) Storage::disk('public')->delete($doc->path);
        DB::connection('tenant')->table(self::TBL_DOC)->where('id', $docId)->delete();
        return response()->json(['success' => true, 'doc_id' => $docId]);
    }

    // ══════════════════════════════════════════════════════════════
    // soumettre / valider / destroy
    // ══════════════════════════════════════════════════════════════
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
        if ($row->validation_status === 'validated')  return response()->json(['error' => 'Déjà validé'], 422);
        if ($row->validation_status === 'in_review')  return response()->json(['error' => 'Déjà soumis'], 422);

        DB::connection('tenant')->table($this->table)->where('id', $form)->update([
            'validation_status' => 'in_review',
            'submitted_at'      => now(),
            'submitted_by'      => $auditor->id,
            'updated_at'        => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'submitted', 'draft', 'in_review');
        return response()->json(['success' => true, 'status' => 'in_review']);
    }

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
            'validated_by' => $auditor->id, 'validation_note' => $note, 'updated_at' => now(),
        ]);
        DB::connection('tenant')->table('mission_phase_assignments')->where('id', $assignmentId)->update([
            'validation_status' => 'validated', 'validated_at' => now(),
            'validated_by' => $auditor->id, 'updated_at' => now(),
        ]);
        $this->log($assignmentId, $auditor->id, $role, 'validated', 'in_review', 'validated', $note);
        return response()->json(['success' => true, 'status' => 'validated', 'action' => 'validated']);
    }

    public function destroy(Request $request, int $form)
    {
        $auditor = $this->getAuditor();
        if (!$auditor) return response()->json(['error' => 'Non autorisé'], 403);

        $row = DB::connection('tenant')->table($this->table)->where('id', $form)->first();
        if (!$row) return response()->json(['error' => 'Formulaire introuvable'], 404);

        $role = $this->getRole((int) $row->mission_id, $auditor->id);
        if (!in_array($role, ['DM', 'CM'])) return response()->json(['error' => 'Seuls DM/CM peuvent supprimer'], 403);

        $marcheIds = DB::connection('tenant')->table(self::TBL_MARCH)->where('amq_id', $form)->pluck('id');
        if ($marcheIds->isNotEmpty()) {
            DB::connection('tenant')->table(self::TBL_ETAPE)->whereIn('marche_id', $marcheIds)->delete();
            DB::connection('tenant')->table(self::TBL_OBJ)->whereIn('marche_id', $marcheIds)->delete();
        }
        DB::connection('tenant')->table(self::TBL_DOC)->where('amq_id', $form)->delete();
        DB::connection('tenant')->table(self::TBL_MARCH)->where('amq_id', $form)->delete();
        DB::connection('tenant')->table($this->table)->where('id', $form)->delete();
        return response()->json(['success' => true]);
    }

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
    // loadMarchesData
    // ══════════════════════════════════════════════════════════════
    private function loadMarchesData(int $amqId): array
    {
        try {
            $marches = DB::connection('tenant')
                ->table(self::TBL_MARCH)
                ->where('amq_id', $amqId)
                ->orderBy('ordre')
                ->get()->toArray();

            foreach ($marches as &$marche) {
                $marche = (array) $marche;
                $mid    = $marche['id'];

                $marche['etapes'] = DB::connection('tenant')
                    ->table(self::TBL_ETAPE)
                    ->where('marche_id', $mid)->orderBy('ordre')
                    ->get()->map(fn($e) => (array) $e)->toArray();

                $marche['objectifs'] = DB::connection('tenant')
                    ->table(self::TBL_OBJ)
                    ->where('marche_id', $mid)->orderBy('ordre')
                    ->get()->map(fn($o) => (array) $o)->toArray();

                $marche['attached_docs'] = DB::connection('tenant')
                    ->table(self::TBL_DOC)
                    ->where('amq_id', $amqId)->where('marche_id', $mid)
                    ->orderBy('created_at')
                    ->get()->map(fn($d) => (array) $d)->toArray();

                $marche['forces']     = $this->decodeJson($marche['forces']     ?? null);
                $marche['faiblesses'] = $this->decodeJson($marche['faiblesses'] ?? null);
            }
            return $marches;
        } catch (\Exception $e) {
            Log::warning('[AMQ] loadMarchesData: ' . $e->getMessage());
            return [];
        }
    }

    private function decodeJson(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        try { $d = json_decode($v, true); return is_array($d) ? $d : []; }
        catch (\Exception) { return []; }
    }

    private function formatSize(int $b): string
    {
        if ($b < 1024)    return $b . ' o';
        if ($b < 1048576) return round($b / 1024, 1) . ' Ko';
        return round($b / 1048576, 1) . ' Mo';
    }
}