<?php

namespace App\Http\Controllers\Auditor\Outils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * ══════════════════════════════════════════════════════════════
 *  OutilBaseController — Base pour les 15 outils IFACI
 *
 *  Gère automatiquement :
 *   - La réception du contexte fiche-test depuis la query string
 *     (?fiche_test_id=X&procedure_code=Y&test_ref=Z&proc_idx=N&back_url=...)
 *   - L'enregistrement dans fiche_test_outils après store/update
 *   - La construction du payload Inertia avec les URLs et le contexte
 *   - L'analyse IA
 *
 *  Chaque sous-classe définit :
 *    protected string $outilCode   = 'I';
 *    protected string $outilTable  = 'outil_entretiens';
 *    protected string $outilLabel  = "Grille d'Entretien";
 *    protected string $outilColor  = '#1e40af';
 *    protected string $codePrefix  = 'ENTR';
 *    protected string $inertiaPage = 'dashboards/Auditor/Outils/OutilEntretien';
 *
 *  Et implémente :
 *    protected function buildRecord(array $v, array $ctx): array   // champs à insérer/mettre à jour
 *    protected function syncChildren(int $id, array $v): void      // sync tables enfants
 *    protected function loadChildren(int $id): array               // données enfants pour la vue
 *    protected function buildIaPrompt(array $record, array $children): string
 *    protected function validationRules(): array
 * ══════════════════════════════════════════════════════════════
 */
abstract class OutilBaseController extends Controller
{
    protected string $conn        = 'tenant';
    protected string $outilCode   = '';
    protected string $outilTable  = '';
    protected string $outilLabel  = '';
    protected string $outilColor  = '#374151';
    protected string $codePrefix  = 'OUTIL';
    protected string $inertiaPage = '';

    // ════════════════════════════════════════════════════════
    //  À IMPLÉMENTER DANS CHAQUE SOUS-CLASSE
    // ════════════════════════════════════════════════════════

    abstract protected function validationRules(): array;
    abstract protected function buildRecord(array $v, array $ctx): array;
    abstract protected function syncChildren(int $id, array $v): void;
    abstract protected function loadChildren(int $id): array;
    abstract protected function buildIaPrompt(array $record, array $children): string;

    // ════════════════════════════════════════════════════════
    //  HELPERS COMMUNS
    // ════════════════════════════════════════════════════════

    protected function db()
    {
        return DB::connection($this->conn);
    }

    protected function findOrFail(int $id): object
    {
        $r = $this->db()->table($this->outilTable)->where('id', $id)->first();
        abort_if(!$r, 404, "{$this->outilLabel} introuvable.");
        return $r;
    }

    protected function genCode(int $missionId): string
    {
        $year = date('Y');
        $seq  = $this->db()->table($this->outilTable)->where('mission_id', $missionId)->count() + 1;
        return "{$this->codePrefix}-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    protected function decode(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!$v) return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }

    protected function toJson(mixed $v): string
    {
        if (is_string($v)) {
            json_decode($v);
            if (json_last_error() === JSON_ERROR_NONE) return $v;
        }
        return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
    }

    // ════════════════════════════════════════════════════════
    //  CONTEXTE FICHE-TEST (depuis query string)
    // ════════════════════════════════════════════════════════

    /**
     * Récupère le contexte fiche-test depuis la requête.
     * Transmis par FicheTest.vue via query string quand on navigue vers un outil.
     */
    protected function ficheContext(Request $request): array
    {
        return [
            'fiche_test_id'  => (int) $request->query('fiche_test_id', 0),
            'procedure_code' => $request->query('procedure_code', ''),
            'test_ref'       => $request->query('test_ref', ''),
            'obj_num'        => $request->query('obj_num', ''),
            'proc_idx'       => (int) $request->query('proc_idx', 0),
            'mission_id'     => (int) $request->query('mission_id', 0),
            'assignment_id'  => (int) $request->query('assignment_id', 0),
            'back_url'       => $request->query('back_url', ''),
        ];
    }

    /**
     * Après store/update : enregistre ou met à jour le lien dans fiche_test_outils.
     */
    protected function enregistrerDansFicheTest(
        int    $outilId,
        array  $ctx,
        int    $missionId,
        int    $assignmentId
    ): void {
        if (!$ctx['fiche_test_id']) return;

        $ficheTestId   = $ctx['fiche_test_id'];
        $procedureCode = $ctx['procedure_code'];
        $testRef       = $ctx['test_ref'];
        $procIdx       = $ctx['proc_idx'];
        $objNum        = $ctx['obj_num'];

        try {
            $existing = $this->db()->table('fiche_test_outils')
                ->where('fiche_test_id', $ficheTestId)
                ->where('outil_code', $this->outilCode)
                ->where('procedure_code', $procedureCode)
                ->where('test_ref', $testRef)
                ->where('proc_idx', $procIdx)
                ->where('is_current', 1)
                ->first();

            if ($existing) {
                // Mise à jour du lien existant
                $this->db()->table('fiche_test_outils')
                    ->where('id', $existing->id)
                    ->update([
                        'outil_id'   => $outilId,
                        'updated_at' => now(),
                    ]);
            } else {
                // Marquer les anciens inactifs
                $this->db()->table('fiche_test_outils')
                    ->where('fiche_test_id', $ficheTestId)
                    ->where('outil_code', $this->outilCode)
                    ->where('procedure_code', $procedureCode)
                    ->where('test_ref', $testRef)
                    ->where('proc_idx', $procIdx)
                    ->update(['is_current' => 0, 'updated_at' => now()]);

                // Insérer le nouveau lien
                $this->db()->table('fiche_test_outils')->insert([
                    'fiche_test_id'  => $ficheTestId,
                    'mission_id'     => $missionId,
                    'assignment_id'  => $assignmentId,
                    'procedure_code' => $procedureCode,
                    'test_ref'       => $testRef,
                    'obj_num'        => $objNum,
                    'proc_idx'       => $procIdx,
                    'outil_code'     => $this->outilCode,
                    'outil_table'    => $this->outilTable,
                    'outil_id'       => $outilId,
                    'version'        => 1,
                    'is_current'     => 1,
                    'created_by'     => Auth::id(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("[OutilBase] enregistrerDansFicheTest: " . $e->getMessage());
        }
    }

    /**
     * Construit le payload commun pour Inertia (URLs + contexte mission).
     */
    protected function buildInertiaPayload(
        object $record,
        array  $children,
        array  $ctx,
        array  $extra = []
    ): array {
        $id       = $record->id;
        $iaResult = $record->ia_result
            ? (is_string($record->ia_result) ? json_decode($record->ia_result, true) : $record->ia_result)
            : null;

        // Auditor courant
        $auditor    = $this->db()->table('auditors')->where('user_id', Auth::id())->first();
        $auditeurNom = $auditor ? trim($auditor->last_name . ' ' . $auditor->first_name) : Auth::user()?->name ?? '';

        // Rôle dans la mission
        $auditorRole = 'AJ';
        if ($auditor && $ctx['assignment_id']) {
            $role = $this->db()->table('mission_phase_assignment_auditeurs')
                ->where('assignment_id', $ctx['assignment_id'])
                ->where('auditeur_id', $auditor->id)
                ->value('role_code');
            $auditorRole = $role ?? 'AJ';
        }

        // Libellé mission
        $missionLibelle = '';
        if ($ctx['mission_id']) {
            $m = $this->db()->table('mission_programmation')->where('id', $ctx['mission_id'])->first();
            $missionLibelle = $m?->libelle ?? '';
        }

        // back_url : soit celui transmis, soit la liste de l'outil
        $backUrl = $ctx['back_url'] ?: route('auditor.ac.fiche-test.index');

        return array_merge([
            'record'       => $record,
            'ia_result'    => $iaResult,
            'auditorRole'  => $auditorRole,
            'auditeurNom'  => $auditeurNom,
            'backUrl'      => $backUrl,
            'urlStore'     => route($this->routeName() . '.store'),
            'urlUpdate'    => route($this->routeName() . '.update', $id),
            'urlSoumettre' => route($this->routeName() . '.soumettre', $id),
            'urlValider'   => route($this->routeName() . '.valider', $id),
            'urlIa'        => route($this->routeName() . '.ia', $id),
            'missionContext' => [
                'fiche_test_id'  => $ctx['fiche_test_id'],
                'mission_id'     => $ctx['mission_id'],
                'assignment_id'  => $ctx['assignment_id'],
                'procedure_code' => $ctx['procedure_code'],
                'test_ref'       => $ctx['test_ref'],
                'obj_num'        => $ctx['obj_num'],
                'proc_idx'       => $ctx['proc_idx'],
                'mission_libelle' => $missionLibelle,
                'back_url'       => $backUrl,
            ],
            'outilMeta' => [
                'code'  => $this->outilCode,
                'label' => $this->outilLabel,
                'color' => $this->outilColor,
            ],
        ], $children, $extra);
    }

    // ════════════════════════════════════════════════════════
    //  ACTIONS CRUD
    // ════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $ctx = $this->ficheContext($request);
        $missionId = $ctx['mission_id'] ?: (int) $request->query('mission_id', 0);

        // Si on arrive avec un contexte fiche-test, chercher un outil existant pour cette procédure
        if ($ctx['fiche_test_id'] && $ctx['procedure_code']) {
            $liaison = $this->db()->table('fiche_test_outils')
                ->where('fiche_test_id', $ctx['fiche_test_id'])
                ->where('outil_code', $this->outilCode)
                ->where('procedure_code', $ctx['procedure_code'])
                ->where('test_ref', $ctx['test_ref'])
                ->where('proc_idx', $ctx['proc_idx'])
                ->where('is_current', 1)
                ->first();

            if ($liaison) {
                // Rediriger vers edit avec le contexte
                return redirect()->to(
                    route($this->routeName() . '.edit', $liaison->outil_id)
                    . '?' . http_build_query(array_filter($ctx))
                );
            }

            // Pas encore créé → afficher la page de création avec le contexte
            $auditor    = $this->db()->table('auditors')->where('user_id', Auth::id())->first();
            $auditeurNom = $auditor ? trim($auditor->last_name . ' ' . $auditor->first_name) : '';
            $missionLibelle = '';
            if ($ctx['mission_id']) {
                $m = $this->db()->table('mission_programmation')->where('id', $ctx['mission_id'])->first();
                $missionLibelle = $m?->libelle ?? '';
            }

            return Inertia::render($this->inertiaPage, [
                'record'      => null,
                'auditorRole' => 'AJ',
                'auditeurNom' => $auditeurNom,
                'backUrl'     => $ctx['back_url'] ?: '/',
                'urlStore'    => route($this->routeName() . '.store'),
                'missionContext' => array_merge($ctx, ['mission_libelle' => $missionLibelle]),
                'outilMeta'   => ['code' => $this->outilCode, 'label' => $this->outilLabel, 'color' => $this->outilColor],
            ] + $this->loadChildren(0));
        }

        // Liste normale
        $items = $this->db()->table($this->outilTable . ' as t')
            ->leftJoin('mission_programmation as mp', 'mp.id', '=', 't.mission_id')
            ->select('t.*', 'mp.libelle as mission_libelle', 'mp.code_mission')
            ->orderByDesc('t.created_at')
            ->when($missionId, fn($q) => $q->where('t.mission_id', $missionId))
            ->paginate(20)->withQueryString();

        return Inertia::render($this->inertiaPage . 'Index', [
            'items'    => $items,
            'filters'  => ['mission_id' => $missionId],
            'outilMeta' => ['code' => $this->outilCode, 'label' => $this->outilLabel, 'color' => $this->outilColor],
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $record   = $this->findOrFail($id);
        $ctx      = $this->ficheContext($request);

        // Si pas de mission_id dans ctx, le récupérer depuis l'enregistrement
        if (!$ctx['mission_id'] && $record->mission_id) {
            $ctx['mission_id'] = (int) $record->mission_id;
        }
        if (!$ctx['assignment_id'] && isset($record->assignment_id)) {
            $ctx['assignment_id'] = (int) $record->assignment_id;
        }

        $children = $this->loadChildren($id);
        $payload  = $this->buildInertiaPayload($record, $children, $ctx);

        return Inertia::render($this->inertiaPage, $payload);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $v   = $request->validate($this->validationRules());
        $ctx = $this->ficheContext($request);

        $missionId    = $ctx['mission_id'] ?: (int) ($v['mission_id'] ?? 0);
        $assignmentId = $ctx['assignment_id'] ?: (int) ($v['assignment_id'] ?? 0);

        $baseRecord = $this->buildRecord($v, $ctx);
        $baseRecord['mission_id']   = $missionId;
        $baseRecord['assignment_id'] = $assignmentId ?: null;
        $baseRecord['procedure_code'] = $ctx['procedure_code'] ?: null;
        $baseRecord['test_ref']      = $ctx['test_ref'] ?: null;
        $baseRecord['obj_num']       = $ctx['obj_num'] ?: null;
        $baseRecord['statut']        = 'draft';
        $baseRecord['code']          = $this->genCode($missionId);
        $baseRecord['created_by']    = Auth::id();
        $baseRecord['created_at']    = now();
        $baseRecord['updated_at']    = now();

        $id = $this->db()->table($this->outilTable)->insertGetId($baseRecord);

        $this->syncChildren($id, $v);
        $this->enregistrerDansFicheTest($id, $ctx, $missionId, $assignmentId);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $id]);
        }

        $redirectUrl = route($this->routeName() . '.edit', $id)
            . ($ctx['fiche_test_id'] ? '?' . http_build_query(array_filter($ctx)) : '');

        return redirect()->to($redirectUrl)->with('success', "{$this->outilLabel} créé.");
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $record = $this->findOrFail($id);
        abort_if($record->statut === 'validated', 403, 'Document validé — modification impossible.');

        $v   = $request->validate($this->validationRules());
        $ctx = $this->ficheContext($request);

        $missionId    = $ctx['mission_id'] ?: (int) $record->mission_id;
        $assignmentId = $ctx['assignment_id'] ?: (int) ($record->assignment_id ?? 0);

        $fields = $this->buildRecord($v, $ctx);
        $fields['updated_at'] = now();

        $this->db()->table($this->outilTable)->where('id', $id)->update($fields);
        $this->syncChildren($id, $v);
        $this->enregistrerDansFicheTest($id, $ctx, $missionId, $assignmentId);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', "{$this->outilLabel} mis à jour.");
    }

    public function soumettre(int $id): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $record = $this->findOrFail($id);
        abort_if($record->statut !== 'draft', 422, 'Seul un brouillon peut être soumis.');

        $this->db()->table($this->outilTable)->where('id', $id)->update([
            'statut'       => 'in_review',
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
            'updated_at'   => now(),
        ]);

        if (request()->wantsJson()) return response()->json(['success' => true, 'statut' => 'in_review']);
        return back()->with('success', 'Soumis pour validation.');
    }

    public function valider(Request $request, int $id): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $record = $this->findOrFail($id);
        abort_if($record->statut !== 'in_review', 422, 'Document non soumis.');

        $v      = $request->validate(['action' => 'required|in:validate,reject', 'note' => 'nullable|string']);
        $statut = $v['action'] === 'validate' ? 'validated' : 'draft';

        $this->db()->table($this->outilTable)->where('id', $id)->update([
            'statut'           => $statut,
            'validation_note'  => $v['note'] ?? null,
            'validated_by'     => Auth::id(),
            'validated_at'     => now(),
            'updated_at'       => now(),
        ]);

        if (request()->wantsJson()) return response()->json(['success' => true, 'statut' => $statut]);
        return back()->with('success', $statut === 'validated' ? 'Validé ✓' : 'Rejeté.');
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $this->findOrFail($id);
        $this->syncChildren($id, []); // vide les enfants
        $this->db()->table($this->outilTable)->where('id', $id)->delete();

        if (request()->wantsJson()) return response()->json(['success' => true]);
        return redirect()->back()->with('success', "{$this->outilLabel} supprimé.");
    }

    public function ia(int $id): JsonResponse
    {
        $record   = $this->findOrFail($id);
        $children = $this->loadChildren($id);
        $prompt   = $this->buildIaPrompt((array) $record, $children);

        try {
            $r = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 1500,
                'temperature' => 0.1,
                'system'     => 'Tu es expert audit interne IFACI. Retourne UNIQUEMENT un JSON valide sans markdown avec: synthese (string), points_forts (array de strings), points_faibles (array de strings), risques (array de strings), recommandations (array de strings), score (number 0-10).',
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ])->json();

            $text = collect($r['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '{}';
            $text = trim(preg_replace('/^```json\s*|\s*```$/m', '', $text));
            $data = json_decode($text, true) ?? [];

            $this->db()->table($this->outilTable)->where('id', $id)->update([
                'ia_result'  => json_encode($data, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'ia_result' => $data]);
        } catch (\Exception $e) {
            Log::error("[{$this->outilCode}] ia: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════
    //  HELPER : nom de route
    // ════════════════════════════════════════════════════════

    protected function routeName(): string
    {
        // À surcharger si le nom de route diffère du pattern standard
        return 'auditor.ac.outil-' . strtolower(str_replace('_', '-', $this->outilCode));
    }
}