<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\ReferentielSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * ReferentielArmpController — administration CENTRALE du référentiel ARMP.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Version consolidée finale (regroupe tous les patches de la conversation) :
 *  - CRUD référentiels simples (types entités, sources financement,
 *    natures marché, modes passation, organes, opérations, dates de
 *    référence)
 *  - CRUD seuils AC + délais (avec organes/modes multiples)
 *  - CRUD grilles de vérification + items (points de contrôle), avec
 *    parité complète face à ParametrageGrillesVerificationController
 *    (tenant) : liaisons articles/opérations/délais/seuils, analyse IA
 *  - Testeur d'affectation (preview de couverture par nature+mode, sans
 *    toucher aux tenants)
 *  - Actions de synchronisation manuelle vers les tenants
 *
 * Remplace, pour la partie ÉDITION, ParametrageMarchesController et
 * ParametrageGrillesVerificationController qui restent en place côté
 * tenant mais en LECTURE SEULE (cf. PATCH_vues_tenant_readonly.txt).
 *
 * Toute écriture ici :
 *  1. modifie `ddmparam.pm_*` (jamais le tenant directement)
 *  2. incrémente `ddmparam.pm_referentiel_versions.version` via bump()
 *  3. sera propagée aux tenants au prochain login (ou via
 *     `php artisan referentiel:sync`)
 */
class ReferentielArmpController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()?->email !== 'admin@diaddem.local') {
                abort(403, 'Accès réservé au super administrateur système.');
            }
            return $next($request);
        });
    }

    private function db()
    {
        // Connexion 'mysql' = celle qui héberge ddmparam dans ce projet
        return DB::connection('mysql');
    }

    private function t(string $table): string
    {
        return "ddmparam.{$table}";
    }

    /** Incrémente la version de référentiel : appelé après CHAQUE écriture. */
    private function bump(): void
    {
        app(ReferentielSyncService::class)->bumpVersion();
    }

    // ══════════════════════════════════════════════════════════════
    //  VUE PRINCIPALE + API JSON GLOBALE
    // ══════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        return Inertia::render('dashboards/Param/admin/ReferentielArmp/Index', array_merge(
            ['activeTab' => $request->query('tab', 'grilles')],
            $this->allData()
        ));
    }

    public function apiAll()
    {
        return response()->json($this->allData());
    }

    private function allData(): array
    {
        $db = $this->db();
        return [
            'version'         => (int) $db->table($this->t('pm_referentiel_versions'))->value('version'),
            'tenantsSync'     => $db->table($this->t('tenants'))
                                    ->select('id', 'name', 'code', 'pm_referentiel_version_synced', 'pm_referentiel_synced_at')
                                    ->get(),
            'typesEntites'    => $db->table($this->t('pm_types_entites'))->orderBy('sort')->get(),
            'sourcesFinance'  => $db->table($this->t('pm_sources_financement'))->orderBy('sort')->get(),
            'naturesMarche'   => $db->table($this->t('pm_natures_marche'))->orderBy('sort')->get(),
            'modesPassation'  => $db->table($this->t('pm_modes_passation'))->orderBy('sort')->get(),
            'organes'         => $db->table($this->t('pm_organes_controle'))->orderBy('sort')->get(),
            'modeOrganes'     => $db->table($this->t('pm_mode_organes'))->get(),
            'operations'      => $db->table($this->t('pm_operations'))->orderBy('sort')->get(),
            'datesReference'  => $db->table($this->t('pm_dates_reference'))->orderBy('sort')->get(),
            'seuilsGeneraux'  => $db->table($this->t('pm_seuils_generaux'))->orderBy('sort')->get(),
            'seuilsAC'        => $db->table($this->t('pm_seuils_ac'))->orderBy('sort')->get(),
            'seuilsAcOrganes' => $db->table($this->t('pm_seuils_ac_organes'))->get(),
            'delais'          => $db->table($this->t('pm_delais'))->orderBy('sort')->get(),
            'delaiOrganes'    => $db->table($this->t('pm_delai_organes'))->get(),
            'delaiModes'      => $db->table($this->t('pm_delai_modes'))->get(),
            'grilles'         => $db->table($this->t('pm_grilles_verification'))->orderBy('sort')->get(),
            'items'           => $db->table($this->t('pm_grilles_verification_items'))->orderBy('grille_id')->orderBy('sort')->get(),
            'grilleOrganes'   => $db->table($this->t('pm_grilles_verification_organes'))->get(),
            'articlesLoi'     => $db->table($this->t('pm_articles_loi'))->get(),
            'itemsArticles'   => $db->table($this->t('pm_grilles_verification_items_articles'))->get(),
            'itemsDelaisMulti'=> $db->table($this->t('pm_grilles_verification_items_delais'))->get(),
            'itemsOperations' => $db->table($this->t('pm_grilles_verification_items_operations'))->get(),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  RÉFÉRENTIELS SIMPLES — handler générique par entité
    // ══════════════════════════════════════════════════════════════

    private const SIMPLE_TABLES = [
        'types-entites'       => 'pm_types_entites',
        'sources-financement' => 'pm_sources_financement',
        'natures-marche'      => 'pm_natures_marche',
        'modes-passation'     => 'pm_modes_passation',
        'organes'             => 'pm_organes_controle',
        'operations'          => 'pm_operations',
        'dates-reference'     => 'pm_dates_reference',
    ];

    public function storeSimple(Request $request, string $entity)
    {
        $table = self::SIMPLE_TABLES[$entity] ?? abort(404);
        $data = $request->validate([
            'code'         => "required|string|max:30|unique:mysql.ddmparam.{$table},code",
            'libelle'      => 'required|string|max:500',
            'sous_type'    => 'nullable|string|max:255',
            'famille'      => 'nullable|string|max:100',
            'code_famille' => 'nullable|string|max:10',
            'sigle'        => 'nullable|string|max:20',
            'niveau'       => 'nullable|string|max:50',
            'date_valeur'  => 'nullable|date',
            'description'  => 'nullable|string',
        ]);
        $data = array_filter($data, fn($v) => !is_null($v));
        $data['sort'] = $this->db()->table($this->t($table))->max('sort') + 1;
        $id = $this->db()->table($this->t($table))->insertGetId($data);
        $this->bump();
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateSimple(Request $request, string $entity, int $id)
    {
        $table = self::SIMPLE_TABLES[$entity] ?? abort(404);
        $data = $request->validate([
            'libelle'      => 'required|string|max:500',
            'sous_type'    => 'nullable|string|max:255',
            'famille'      => 'nullable|string|max:100',
            'code_famille' => 'nullable|string|max:10',
            'sigle'        => 'nullable|string|max:20',
            'niveau'       => 'nullable|string|max:50',
            'date_valeur'  => 'nullable|date',
            'description'  => 'nullable|string',
        ]);
        $this->db()->table($this->t($table))->where('id', $id)->update($data);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function destroySimple(string $entity, int $id)
    {
        $table = self::SIMPLE_TABLES[$entity] ?? abort(404);
        $this->db()->table($this->t($table))->where('id', $id)->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    //  SEUILS AC
    // ══════════════════════════════════════════════════════════════

    public function storeSeuilAC(Request $request)
    {
        $data = $request->validate([
            'type_entite_code'    => 'required|string|max:20',
            'nature_marche_code'  => 'required|string|max:20',
            'mode_passation_code' => 'required|string|max:20',
            'valeur_min'          => 'nullable|numeric|min:0',
            'valeur_max'          => 'nullable|numeric|min:0',
            'operateur_min'       => 'nullable|string|max:5',
            'operateur_max'       => 'nullable|string|max:5',
        ]);
        $db = $this->db();
        $data['sort'] = $db->table($this->t('pm_seuils_ac'))->where('type_entite_code', $data['type_entite_code'])->max('sort') + 1;
        $id = $db->table($this->t('pm_seuils_ac'))->insertGetId($data);

        $mode = $db->table($this->t('pm_modes_passation'))->where('code', $data['mode_passation_code'])->first();
        if ($mode && $mode->code_famille === 'PM') {
            $organes = $db->table($this->t('pm_mode_organes'))->where('mode_passation_code', $data['mode_passation_code'])->pluck('organe_code');
            $sort = 1;
            foreach ($organes as $oc) {
                $db->table($this->t('pm_seuils_ac_organes'))->insertOrIgnore(['seuil_ac_id' => $id, 'organe_code' => $oc, 'sort' => $sort++]);
            }
        }
        $this->bump();
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function destroySeuilAC(int $id)
    {
        $this->db()->table($this->t('pm_seuils_ac'))->where('id', $id)->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    //  DÉLAIS
    // ══════════════════════════════════════════════════════════════

    public function storeDelai(Request $request)
    {
        $validated = $request->validate([
            'operation_id'      => 'required|integer|exists:mysql.ddmparam.pm_operations,id',
            'delai_valeur'      => 'nullable|integer|min:0',
            'delai_unite'       => 'nullable|string|max:30',
            'delai_type'        => 'required|string|in:calendaire,ouvrable,sans-delai,non-defini',
            'mot_liaison'       => 'nullable|string|max:20',
            'date_reference_id' => 'nullable|integer|exists:mysql.ddmparam.pm_dates_reference,id',
            'phase_option'      => 'nullable|string|in:AMI,DAO,AMI+DAO',
            'note'              => 'nullable|string',
            'organes_codes'     => 'required|array|min:1',
            'organes_codes.*'   => 'required|string|max:20',
            'modes_codes'       => 'nullable|array',
            'modes_codes.*'     => 'string|max:20|exists:mysql.ddmparam.pm_modes_passation,code',
        ]);

        $db = $this->db();
        $payload = collect($validated)->except(['organes_codes', 'modes_codes'])->toArray();
        $payload['sort'] = $db->table($this->t('pm_delais'))->max('sort') + 1;
        $modesCodes = $validated['modes_codes'] ?? [];
        $payload['condition_mode'] = $modesCodes[0] ?? null;

        if (in_array($payload['delai_type'], ['sans-delai', 'non-defini'])) {
            $payload['delai_valeur'] = $payload['delai_unite'] = $payload['mot_liaison'] = $payload['date_reference_id'] = null;
        }

        $id = $db->table($this->t('pm_delais'))->insertGetId($payload);

        $sort = 1;
        foreach ($validated['organes_codes'] as $oc) {
            $db->table($this->t('pm_delai_organes'))->insertOrIgnore(['delai_id' => $id, 'organe_code' => $oc, 'sort' => $sort++]);
        }
        $sort = 1;
        foreach ($modesCodes as $mc) {
            $db->table($this->t('pm_delai_modes'))->insertOrIgnore(['delai_id' => $id, 'mode_passation_code' => $mc, 'sort' => $sort++]);
        }

        $this->bump();
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateDelai(Request $request, int $id)
    {
        $validated = $request->validate([
            'operation_id'      => 'required|integer|exists:mysql.ddmparam.pm_operations,id',
            'delai_valeur'      => 'nullable|integer|min:0',
            'delai_unite'       => 'nullable|string|max:30',
            'delai_type'        => 'required|string|in:calendaire,ouvrable,sans-delai,non-defini',
            'mot_liaison'       => 'nullable|string|max:20',
            'date_reference_id' => 'nullable|integer|exists:mysql.ddmparam.pm_dates_reference,id',
            'phase_option'      => 'nullable|string|in:AMI,DAO,AMI+DAO',
            'note'              => 'nullable|string',
            'organes_codes'     => 'required|array|min:1',
            'organes_codes.*'   => 'required|string|max:20',
            'modes_codes'       => 'nullable|array',
            'modes_codes.*'     => 'string|max:20|exists:mysql.ddmparam.pm_modes_passation,code',
        ]);

        $db = $this->db();
        $payload = collect($validated)->except(['organes_codes', 'modes_codes'])->toArray();
        $modesCodes = $validated['modes_codes'] ?? [];
        $payload['condition_mode'] = $modesCodes[0] ?? null;

        if (in_array($payload['delai_type'], ['sans-delai', 'non-defini'])) {
            $payload['delai_valeur'] = $payload['delai_unite'] = $payload['mot_liaison'] = $payload['date_reference_id'] = null;
        }

        $db->table($this->t('pm_delais'))->where('id', $id)->update($payload);

        $db->table($this->t('pm_delai_organes'))->where('delai_id', $id)->delete();
        $sort = 1;
        foreach ($validated['organes_codes'] as $oc) {
            $db->table($this->t('pm_delai_organes'))->insertOrIgnore(['delai_id' => $id, 'organe_code' => $oc, 'sort' => $sort++]);
        }
        $db->table($this->t('pm_delai_modes'))->where('delai_id', $id)->delete();
        $sort = 1;
        foreach ($modesCodes as $mc) {
            $db->table($this->t('pm_delai_modes'))->insertOrIgnore(['delai_id' => $id, 'mode_passation_code' => $mc, 'sort' => $sort++]);
        }

        $this->bump();
        return response()->json(['success' => true]);
    }

    public function destroyDelai(int $id)
    {
        $this->db()->table($this->t('pm_delais'))->where('id', $id)->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    //  GRILLES DE VÉRIFICATION
    // ══════════════════════════════════════════════════════════════

    public function storeGrille(Request $request)
    {
        $data = $request->validate([
            'code'                  => 'required|string|max:30|unique:mysql.ddmparam.pm_grilles_verification,code',
            'code_parent'           => 'nullable|string|max:20',
            'intitule'              => 'required|string|max:500',
            'nature_marche_code'    => 'nullable|string|max:20',
            'mode_passation_code'   => 'nullable|string|max:20',
            'avec_prequalification' => 'nullable|boolean',
            'phase_marche'          => 'nullable|string|max:10',
            'description'           => 'nullable|string',
        ]);
        $db = $this->db();
        $data['actif'] = 1;
        $data['sort']  = $db->table($this->t('pm_grilles_verification'))->max('sort') + 1;
        $id = $db->table($this->t('pm_grilles_verification'))->insertGetId($data);
        $this->bump();
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateGrille(Request $request, int $id)
    {
        $data = $request->validate([
            'code_parent'           => 'nullable|string|max:20',
            'intitule'              => 'sometimes|required|string|max:500',
            'nature_marche_code'    => 'nullable|string|max:20',
            'mode_passation_code'   => 'nullable|string|max:20',
            'avec_prequalification' => 'nullable|boolean',
            'phase_marche'          => 'nullable|string|max:10',
            'description'           => 'nullable|string',
            'actif'                 => 'sometimes|boolean',
        ]);
        $this->db()->table($this->t('pm_grilles_verification'))->where('id', $id)->update($data);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function destroyGrille(int $id)
    {
        $db = $this->db();
        $db->transaction(function () use ($db, $id) {
            $itemIds = $db->table($this->t('pm_grilles_verification_items'))->where('grille_id', $id)->pluck('id');
            if ($itemIds->isNotEmpty()) {
                $db->table($this->t('pm_grilles_verification_items_operations'))->whereIn('item_id', $itemIds)->delete();
                $db->table($this->t('pm_grilles_verification_items_delais'))->whereIn('item_id', $itemIds)->delete();
                $db->table($this->t('pm_grilles_verification_items_articles'))->whereIn('item_id', $itemIds)->delete();
                $db->table($this->t('pm_grilles_verification_items'))->whereIn('id', $itemIds)->delete();
            }
            $db->table($this->t('pm_grilles_verification_organes'))->where('grille_id', $id)->delete();
            $db->table($this->t('pm_grilles_verification'))->where('id', $id)->delete();
        });
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ── Items (points de contrôle) ────────────────────────────────────

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'grille_id'        => 'required|integer|exists:mysql.ddmparam.pm_grilles_verification,id',
            'numero'           => 'required|string|max:10',
            'libelle_controle' => 'required|string',
            'type_reponse'     => 'nullable|string|max:20',
            'obligatoire'      => 'nullable|boolean',
            'preuves'          => 'nullable|string',
        ]);
        $db = $this->db();
        $data['type_reponse'] = $data['type_reponse'] ?? 'conformite';
        $data['obligatoire']  = $data['obligatoire']  ?? 1;
        $data['actif']        = 1;
        $data['depend_delai'] = str_contains(mb_strtolower($data['libelle_controle']), 'délai')
                              || str_contains(mb_strtolower($data['libelle_controle']), 'delai') ? 1 : 0;
        $data['sort'] = $db->table($this->t('pm_grilles_verification_items'))->where('grille_id', $data['grille_id'])->max('sort') + 1;
        $id = $db->table($this->t('pm_grilles_verification_items'))->insertGetId($data);
        $this->bump();
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateItem(Request $request, int $id)
    {
        $data = $request->validate([
            'numero'           => 'sometimes|required|string|max:10',
            'libelle_controle' => 'sometimes|required|string',
            'type_reponse'     => 'nullable|string|max:20',
            'obligatoire'      => 'nullable|boolean',
            'preuves'          => 'nullable|string',
            'actif'            => 'sometimes|boolean',
            'sort'             => 'sometimes|integer',
        ]);
        $this->db()->table($this->t('pm_grilles_verification_items'))->where('id', $id)->update($data);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function destroyItem(int $id)
    {
        $db = $this->db();
        $db->transaction(function () use ($db, $id) {
            $db->table($this->t('pm_grilles_verification_items_operations'))->where('item_id', $id)->delete();
            $db->table($this->t('pm_grilles_verification_items_delais'))->where('item_id', $id)->delete();
            $db->table($this->t('pm_grilles_verification_items_articles'))->where('item_id', $id)->delete();
            $db->table($this->t('pm_grilles_verification_items'))->where('id', $id)->delete();
        });
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function reorderItems(Request $request)
    {
        $data = $request->validate(['items' => 'required|array']);
        $db = $this->db();
        foreach ($data['items'] as $row) {
            $db->table($this->t('pm_grilles_verification_items'))->where('id', $row['id'])->update(['sort' => $row['sort']]);
        }
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ── Organes rattachés à une grille ────────────────────────────────

    public function storeGrilleOrgane(Request $request)
    {
        $data = $request->validate([
            'grille_id'   => 'required|integer|exists:mysql.ddmparam.pm_grilles_verification,id',
            'organe_code' => 'required|string|max:30',
        ]);
        $this->db()->table($this->t('pm_grilles_verification_organes'))->insertOrIgnore($data);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function destroyGrilleOrgane(Request $request)
    {
        $data = $request->validate(['grille_id' => 'required|integer', 'organe_code' => 'required|string|max:30']);
        $this->db()->table($this->t('pm_grilles_verification_organes'))
            ->where('grille_id', $data['grille_id'])->where('organe_code', $data['organe_code'])->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ── Liaisons item <-> délai (1-1 legacy) / seuil ──────────────────

    public function linkItemDelai(Request $request, int $id)
    {
        $data = $request->validate(['delai_id' => 'nullable|integer|exists:mysql.ddmparam.pm_delais,id']);
        $this->db()->table($this->t('pm_grilles_verification_items'))->where('id', $id)->update([
            'delai_id' => $data['delai_id'], 'depend_delai' => $data['delai_id'] ? 1 : 0,
        ]);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function linkItemSeuil(Request $request, int $id)
    {
        $data = $request->validate([
            'seuil_id'    => 'nullable|integer|exists:mysql.ddmparam.pm_seuils_generaux,id',
            'seuil_ac_id' => 'nullable|integer|exists:mysql.ddmparam.pm_seuils_ac,id',
        ]);
        $this->db()->table($this->t('pm_grilles_verification_items'))->where('id', $id)->update([
            'seuil_id'     => $data['seuil_id']    ?? null,
            'seuil_ac_id'  => $data['seuil_ac_id'] ?? null,
            'depend_seuil' => (!empty($data['seuil_id']) || !empty($data['seuil_ac_id'])) ? 1 : 0,
        ]);
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ── Liaisons M2M item <-> article / opération / délai multiple ───

    public function itemLiaisons(int $id)
    {
        $db = $this->db();
        $articles = $db->table($this->t('pm_grilles_verification_items_articles') . ' as pivot')
            ->join($this->t('pm_articles_loi') . ' as a', 'a.id', '=', 'pivot.article_id')
            ->where('pivot.item_id', $id)
            ->get(['a.*', 'pivot.genere_par_ia']);

        $delais = $db->table($this->t('pm_grilles_verification_items_delais') . ' as pivot')
            ->join($this->t('pm_delais') . ' as d', 'd.id', '=', 'pivot.delai_id')
            ->where('pivot.item_id', $id)
            ->get(['d.*', 'pivot.genere_par_ia']);

        return response()->json(['success' => true, 'articles' => $articles, 'delais' => $delais]);
    }

    public function linkItemArticle(Request $request, int $id)
    {
        $data = $request->validate(['article_id' => 'required|integer|exists:mysql.ddmparam.pm_articles_loi,id']);
        $this->db()->table($this->t('pm_grilles_verification_items_articles'))->insertOrIgnore([
            'item_id' => $id, 'article_id' => $data['article_id'], 'genere_par_ia' => 0,
        ]);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function unlinkItemArticle(Request $request, int $id)
    {
        $data = $request->validate(['article_id' => 'required|integer']);
        $this->db()->table($this->t('pm_grilles_verification_items_articles'))
            ->where('item_id', $id)->where('article_id', $data['article_id'])->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function linkItemOperation(Request $request, int $id)
    {
        $data = $request->validate(['operation_id' => 'required|integer|exists:mysql.ddmparam.pm_operations,id']);
        $this->db()->table($this->t('pm_grilles_verification_items_operations'))->insertOrIgnore([
            'item_id' => $id, 'operation_id' => $data['operation_id'], 'genere_par_ia' => 0,
        ]);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function unlinkItemOperation(Request $request, int $id)
    {
        $data = $request->validate(['operation_id' => 'required|integer']);
        $this->db()->table($this->t('pm_grilles_verification_items_operations'))
            ->where('item_id', $id)->where('operation_id', $data['operation_id'])->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function linkItemDelaiMulti(Request $request, int $id)
    {
        $data = $request->validate(['delai_id' => 'required|integer|exists:mysql.ddmparam.pm_delais,id']);
        $this->db()->table($this->t('pm_grilles_verification_items_delais'))->insertOrIgnore([
            'item_id' => $id, 'delai_id' => $data['delai_id'], 'genere_par_ia' => 0,
        ]);
        $this->db()->table($this->t('pm_grilles_verification_items'))->where('id', $id)->update(['depend_delai' => 1]);
        $this->bump();
        return response()->json(['success' => true]);
    }

    public function unlinkItemDelaiMulti(Request $request, int $id)
    {
        $data = $request->validate(['delai_id' => 'required|integer']);
        $this->db()->table($this->t('pm_grilles_verification_items_delais'))
            ->where('item_id', $id)->where('delai_id', $data['delai_id'])->delete();
        $this->bump();
        return response()->json(['success' => true]);
    }

    // ── Analyse IA (identique au tenant, service DB-agnostique) ───────

    public function iaAnalyserItem(Request $request, int $id, \App\Services\MistralGrillesAssistant $ia)
    {
        $db = $this->db();
        $item = $db->table($this->t('pm_grilles_verification_items'))->where('id', $id)->first();
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Point de contrôle introuvable.']);
        }

        $resultat = $ia->analyserPointDeControle($item->libelle_controle);

        $articleIds = [];
        foreach ($resultat['articles'] as $art) {
            if (empty($art['numero'])) continue;
            $articleId = $db->table($this->t('pm_articles_loi'))
                ->where('numero', $art['numero'])
                ->where('source_loi', $art['source_loi'] ?? null)
                ->value('id');

            if (!$articleId) {
                $articleId = $db->table($this->t('pm_articles_loi'))->insertGetId([
                    'numero'          => $art['numero'],
                    'texte_reference' => $art['texte_reference'] ?? ('Article ' . $art['numero']),
                    'source_loi'      => $art['source_loi'] ?? null,
                    'titre'           => $art['titre'] ?? null,
                    'contenu'         => $art['contenu'] ?? null,
                    'genere_par_ia'   => 1,
                ]);
            } else {
                $existing = $db->table($this->t('pm_articles_loi'))->where('id', $articleId)->first();
                if (empty($existing->titre) && !empty($art['titre'])) {
                    $db->table($this->t('pm_articles_loi'))->where('id', $articleId)->update([
                        'titre'   => $art['titre'],
                        'contenu' => $art['contenu'] ?? $existing->contenu,
                    ]);
                }
            }
            $articleIds[] = $articleId;

            $db->table($this->t('pm_grilles_verification_items_articles'))->insertOrIgnore([
                'item_id' => $id, 'article_id' => $articleId, 'genere_par_ia' => 1,
            ]);
        }

        $this->bump();
        return response()->json([
            'success'  => true,
            'articles' => $db->table($this->t('pm_articles_loi'))->whereIn('id', $articleIds)->get(),
        ]);
    }

    public function iaAnalyserGrille(Request $request, int $grilleId, \App\Services\MistralGrillesAssistant $ia)
    {
        $db = $this->db();
        $items = $db->table($this->t('pm_grilles_verification_items'))->where('grille_id', $grilleId)->get();
        $resultatsIds = [];
        foreach ($items as $item) {
            $r = $this->iaAnalyserItem($request, $item->id, $ia);
            $resultatsIds[$item->id] = json_decode($r->getContent(), true);
        }
        return response()->json(['success' => true, 'resultats' => $resultatsIds]);
    }

    // ══════════════════════════════════════════════════════════════
    //  TESTEUR D'AFFECTATION — validation de couverture par nature+mode
    // ══════════════════════════════════════════════════════════════

    private const PHASES_ATTENDUES = ['PLA', 'DAO', 'ROO', 'EVA', 'SAN', 'EXE', 'REP', 'CAT'];

    /**
     * GET /admin/referentiel-armp/preview-affectation?nature=X&mode=Y&pq=0|1
     * Simule la résolution des grilles pour une nature+mode donnés,
     * exactement comme le fera MissionAuditAffectationController côté
     * tenant — mais ici sur ddmparam, pour validation en amont.
     */
    public function previewAffectation(Request $request)
    {
        $data = $request->validate([
            'nature' => 'required|string|max:20',
            'mode'   => 'required|string|max:20',
            'pq'     => 'nullable|in:0,1',
        ]);

        $db = $this->db();

        $query = $db->table($this->t('pm_grilles_verification'))
            ->where('actif', 1)
            ->where(fn($q) => $q->whereNull('nature_marche_code')->orWhere('nature_marche_code', $data['nature']))
            ->where(fn($q) => $q->whereNull('mode_passation_code')->orWhere('mode_passation_code', $data['mode']));

        if (array_key_exists('pq', $data) && $data['pq'] !== null && $data['pq'] !== '') {
            $pq = (int) $data['pq'];
            $query->where(fn($q) => $q->whereNull('avec_prequalification')->orWhere('avec_prequalification', $pq));
        }

        $grilles = $query->get();
        $grilleIds = $grilles->pluck('id');

        $itemCounts = $db->table($this->t('pm_grilles_verification_items'))
            ->whereIn('grille_id', $grilleIds)
            ->where('actif', 1)
            ->selectRaw('grille_id, COUNT(*) as nb')
            ->groupBy('grille_id')
            ->pluck('nb', 'grille_id');

        $phasesResolues   = $grilles->pluck('phase_marche')->filter()->unique()->values()->all();
        $phasesManquantes = array_values(array_diff(self::PHASES_ATTENDUES, $phasesResolues));

        $doublons = $grilles->groupBy('phase_marche')
            ->filter(fn($g, $phase) => $phase && $g->count() > 1)
            ->map(fn($g) => $g->pluck('code'));

        return response()->json([
            'success' => true,
            'grilles' => $grilles->map(fn($g) => [
                'id'           => $g->id,
                'code'         => $g->code,
                'intitule'     => $g->intitule,
                'phase_marche' => $g->phase_marche,
                'nb_items'     => $itemCounts[$g->id] ?? 0,
            ])->values(),
            'phases_manquantes' => $phasesManquantes,
            'phases_ambigues'   => $doublons,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  SYNCHRO — actions manuelles pour le Super Admin
    // ══════════════════════════════════════════════════════════════

    public function forceSyncAll(ReferentielSyncService $service)
    {
        $service->bumpVersion();
        $results = $service->syncAllTenants();
        return response()->json(['success' => true, 'results' => $results]);
    }

    public function forceSyncTenant(int $tenantId, ReferentielSyncService $service)
    {
        $tenant = $this->db()->table($this->t('tenants'))->where('id', $tenantId)->first();
        if (!$tenant) return response()->json(['success' => false, 'message' => 'Tenant introuvable'], 404);
        $service->syncTenant($tenantId, $tenant->db_name);
        return response()->json(['success' => true]);
    }
}