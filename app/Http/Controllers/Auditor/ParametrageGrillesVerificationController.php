<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ParametrageGrillesVerificationController extends Controller
{
    /** Connexion tenant, comme le reste du module audit.core */
    private function db()
    {
        return DB::connection('tenant');
    }

    // ─────────────────────────────────────────────────────────────
    // VUE PRINCIPALE
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        return Inertia::render('dashboards/Auditor/ParametrageMarches/GrillesVerification', array_merge(
            $this->buildPayload(),
            ['activeTab' => $request->query('tab', 'catalogue')]
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // API JSON GLOBALE (rechargement complet côté front)
    // ─────────────────────────────────────────────────────────────
    public function apiAll()
    {
        return response()->json($this->buildPayload());
    }

    private function buildPayload(): array
    {
        $db = $this->db();

        return [
            // Référentiels déjà existants (réutilisés pour les selects nature/mode)
            'naturesMarche'   => $db->table('pm_natures_marche')->orderBy('sort')->get(),
            'modesPassation'  => $db->table('pm_modes_passation')->orderBy('sort')->get(),
            'delais'          => $db->table('pm_delais')->orderBy('sort')->get(),
            'delaiModes'      => $db->table('pm_delai_modes')->get(),
            'delaiOrganes'    => $db->table('pm_delai_organes')->get(),
            'operations'      => $db->table('pm_operations')->orderBy('sort')->get(),
            'seuilsGeneraux'  => $db->table('pm_seuils_generaux')->orderBy('sort')->get(),
            'seuilsAC'        => $db->table('pm_seuils_ac')->orderBy('sort')->get(),
            'typesEntites'    => $db->table('pm_types_entites')->orderBy('sort')->get(),
            'organes'         => $db->table('pm_organes_controle')->orderBy('code')->get(),

            // Le module Grilles lui-même
            'grilles'         => $db->table('pm_grilles_verification')->orderBy('sort')->get(),
            'items'           => $db->table('pm_grilles_verification_items')
                                    ->orderBy('grille_id')->orderBy('sort')->get(),
            'grilleOrganes'   => $db->table('pm_grilles_verification_organes')->get(),
            'articlesLoi'         => $db->table('pm_articles_loi')->get(),
            'itemsArticles'       => $db->table('pm_grilles_verification_items_articles')->get(),
            'itemsDelaisMulti'    => $db->table('pm_grilles_verification_items_delais')->get(),
            'itemsOperations'     => $db->table('pm_grilles_verification_items_operations')->get(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // GRILLES (pm_grilles_verification)
    // ─────────────────────────────────────────────────────────────
    public function storeGrille(Request $request)
    {
        $data = $request->validate([
            'code'                   => 'required|string|max:30|unique:pm_grilles_verification,code',
            'code_parent'            => 'nullable|string|max:20',
            'intitule'               => 'required|string|max:500',
            'nature_marche_code'     => 'nullable|string|max:20',
            'mode_passation_code'    => 'nullable|string|max:20',
            'avec_prequalification'  => 'nullable|boolean',
            'phase_marche'           => 'nullable|string|max:10',
            'description'            => 'nullable|string',
        ]);
        $data['actif'] = 1;
        $data['sort']  = $this->db()->table('pm_grilles_verification')->max('sort') + 1;

        $id = $this->db()->table('pm_grilles_verification')->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateGrille(Request $request, int $id)
    {
        $data = $request->validate([
            'code_parent'            => 'nullable|string|max:20',
            'intitule'               => 'sometimes|required|string|max:500',
            'nature_marche_code'     => 'nullable|string|max:20',
            'mode_passation_code'    => 'nullable|string|max:20',
            'avec_prequalification'  => 'nullable|boolean',
            'phase_marche'           => 'nullable|string|max:10',
            'description'            => 'nullable|string',
            'actif'                  => 'sometimes|boolean',
        ]);

        $this->db()->table('pm_grilles_verification')->where('id', $id)->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * Suppression d'une grille. Les tables pivot/enfants n'ont plus de
     * contrainte FK en base (retirée du seed pour éviter les erreurs 1005 /
     * errno 150 liées aux tables externes déjà existantes), donc le nettoyage
     * des dépendances est fait explicitement ici, dans une transaction.
     */
    public function destroyGrille(int $id)
    {
        $db = $this->db();
        $db->transaction(function () use ($db, $id) {
            $itemIds = $db->table('pm_grilles_verification_items')
                ->where('grille_id', $id)->pluck('id');

            if ($itemIds->isNotEmpty()) {
                $db->table('pm_grilles_verification_items_operations')->whereIn('item_id', $itemIds)->delete();
                $db->table('pm_grilles_verification_items_delais')->whereIn('item_id', $itemIds)->delete();
                $db->table('pm_grilles_verification_items_articles')->whereIn('item_id', $itemIds)->delete();
                $db->table('pm_grilles_verification_items')->whereIn('id', $itemIds)->delete();
            }

            $db->table('pm_grilles_verification_organes')->where('grille_id', $id)->delete();
            $db->table('pm_grilles_verification')->where('id', $id)->delete();
        });

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // ITEMS (pm_grilles_verification_items)
    // ─────────────────────────────────────────────────────────────
    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'grille_id'         => 'required|integer|exists:pm_grilles_verification,id',
            'numero'            => 'required|string|max:10',
            'libelle_controle'  => 'required|string',
            'type_reponse'      => 'nullable|string|max:20',
            'obligatoire'       => 'nullable|boolean',
        ]);

        $data['type_reponse']  = $data['type_reponse']  ?? 'conformite';
        $data['obligatoire']   = $data['obligatoire']   ?? 1;
        $data['actif']         = 1;
        $data['depend_delai']  = str_contains(mb_strtolower($data['libelle_controle']), 'délai')
                               || str_contains(mb_strtolower($data['libelle_controle']), 'delai') ? 1 : 0;
        $data['sort'] = $this->db()->table('pm_grilles_verification_items')
                            ->where('grille_id', $data['grille_id'])->max('sort') + 1;

        $id = $this->db()->table('pm_grilles_verification_items')->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateItem(Request $request, int $id)
    {
        $data = $request->validate([
            'numero'            => 'sometimes|required|string|max:10',
            'libelle_controle'  => 'sometimes|required|string',
            'type_reponse'      => 'nullable|string|max:20',
            'obligatoire'       => 'nullable|boolean',
            'actif'             => 'sometimes|boolean',
            'sort'              => 'sometimes|integer',
        ]);

        $this->db()->table('pm_grilles_verification_items')->where('id', $id)->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * Suppression d'un item : nettoyage explicite des liaisons (pas de FK).
     */
    public function destroyItem(int $id)
    {
        $db = $this->db();
        $db->transaction(function () use ($db, $id) {
            $db->table('pm_grilles_verification_items_operations')->where('item_id', $id)->delete();
            $db->table('pm_grilles_verification_items_delais')->where('item_id', $id)->delete();
            $db->table('pm_grilles_verification_items_articles')->where('item_id', $id)->delete();
            $db->table('pm_grilles_verification_items')->where('id', $id)->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * Rattacher / détacher un item à un délai déjà paramétré (pm_delais)
     * body: { delai_id }  (null pour détacher)
     */
    public function linkItemDelai(Request $request, int $id)
    {
        $data = $request->validate(['delai_id' => 'nullable|integer|exists:pm_delais,id']);

        $this->db()->table('pm_grilles_verification_items')->where('id', $id)->update([
            'delai_id'     => $data['delai_id'],
            'depend_delai' => $data['delai_id'] ? 1 : 0,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Rattacher / détacher un item à un seuil déjà paramétré
     * body: { seuil_id, seuil_ac_id }  (l'un des deux, ou les deux null pour détacher)
     */
    public function linkItemSeuil(Request $request, int $id)
    {
        $data = $request->validate([
            'seuil_id'    => 'nullable|integer|exists:pm_seuils_generaux,id',
            'seuil_ac_id' => 'nullable|integer|exists:pm_seuils_ac,id',
        ]);

        $this->db()->table('pm_grilles_verification_items')->where('id', $id)->update([
            'seuil_id'     => $data['seuil_id']    ?? null,
            'seuil_ac_id'  => $data['seuil_ac_id'] ?? null,
            'depend_seuil' => (!empty($data['seuil_id']) || !empty($data['seuil_ac_id'])) ? 1 : 0,
        ]);

        return response()->json(['success' => true]);
    }

    public function reorderItems(Request $request)
    {
        // body: { items: [{id, sort}, ...] }
        $data = $request->validate(['items' => 'required|array']);
        $db = $this->db();
        foreach ($data['items'] as $row) {
            $db->table('pm_grilles_verification_items')
               ->where('id', $row['id'])
               ->update(['sort' => $row['sort']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Rattacher un organe de contrôle à une grille (pivot pm_grilles_verification_organes)
     * body: { grille_id, organe_code }
     */
    public function storeGrilleOrgane(Request $request)
    {
        $data = $request->validate([
            'grille_id'   => 'required|integer|exists:pm_grilles_verification,id',
            'organe_code' => 'required|string|max:30',
        ]);

        $this->db()->table('pm_grilles_verification_organes')->insertOrIgnore($data);

        return response()->json(['success' => true]);
    }

    /**
     * Détacher un organe d'une grille
     * body: { grille_id, organe_code }
     */
    public function destroyGrilleOrgane(Request $request)
    {
        $data = $request->validate([
            'grille_id'   => 'required|integer',
            'organe_code' => 'required|string|max:30',
        ]);

        $this->db()->table('pm_grilles_verification_organes')
            ->where('grille_id', $data['grille_id'])
            ->where('organe_code', $data['organe_code'])
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Analyse IA d'un seul point de contrôle : extrait les articles de loi
     * cités (numéro + intitulé/contenu), et les enregistre dans la table
     * pivot pm_grilles_verification_items_articles.
     *
     * L'IA ne touche PLUS aux opérations ni aux délais : ce rattachement
     * reste un choix manuel de l'admin depuis le menu déroulant du tableau
     * (liste pm_operations existante) — le délai affiché en découle
     * automatiquement côté front, filtré par l'opération choisie.
     */
    public function iaAnalyserItem(Request $request, int $id, \App\Services\MistralGrillesAssistant $ia)
    {
        $db = $this->db();
        $item = $db->table('pm_grilles_verification_items')->where('id', $id)->first();
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Point de contrôle introuvable.']);
        }

        $resultat = $ia->analyserPointDeControle($item->libelle_controle);

        $articleIds = [];
        foreach ($resultat['articles'] as $art) {
            if (empty($art['numero'])) continue;
            $articleId = $db->table('pm_articles_loi')
                ->where('numero', $art['numero'])
                ->where('source_loi', $art['source_loi'] ?? null)
                ->value('id');

            if (!$articleId) {
                $articleId = $db->table('pm_articles_loi')->insertGetId([
                    'numero'          => $art['numero'],
                    'texte_reference' => $art['texte_reference'] ?? ('Article ' . $art['numero']),
                    'source_loi'      => $art['source_loi'] ?? null,
                    'titre'           => $art['titre'] ?? null,
                    'contenu'         => $art['contenu'] ?? null,
                    'genere_par_ia'   => 1,
                ]);
            } else {
                // Complète l'intitulé/contenu si un article existait déjà
                // sans titre (ex: rattaché manuellement avant analyse IA).
                $existing = $db->table('pm_articles_loi')->where('id', $articleId)->first();
                if (empty($existing->titre) && !empty($art['titre'])) {
                    $db->table('pm_articles_loi')->where('id', $articleId)->update([
                        'titre'   => $art['titre'],
                        'contenu' => $art['contenu'] ?? $existing->contenu,
                    ]);
                }
            }
            $articleIds[] = $articleId;

            $db->table('pm_grilles_verification_items_articles')->insertOrIgnore([
                'item_id'       => $id,
                'article_id'    => $articleId,
                'genere_par_ia' => 1,
            ]);
        }

        return response()->json([
            'success'  => true,
            'articles' => $db->table('pm_articles_loi')->whereIn('id', $articleIds)->get(),
        ]);
    }

    /**
     * Analyse IA de TOUS les points de contrôle d'une grille en une fois.
     */
    public function iaAnalyserGrille(Request $request, int $grilleId, \App\Services\MistralGrillesAssistant $ia)
    {
        $db = $this->db();
        $items = $db->table('pm_grilles_verification_items')->where('grille_id', $grilleId)->get();
        $resultatsIds = [];
        foreach ($items as $item) {
            $r = $this->iaAnalyserItem($request, $item->id, $ia);
            $resultatsIds[$item->id] = json_decode($r->getContent(), true);
        }

        return response()->json(['success' => true, 'resultats' => $resultatsIds]);
    }

    /**
     * Liste les articles et délais rattachés à un item (pour affichage badges multiples)
     */
    public function itemLiaisons(int $id)
    {
        $db = $this->db();
        $articles = $db->table('pm_grilles_verification_items_articles as pivot')
            ->join('pm_articles_loi as a', 'a.id', '=', 'pivot.article_id')
            ->where('pivot.item_id', $id)
            ->get(['a.*', 'pivot.genere_par_ia']);

        $delais = $db->table('pm_grilles_verification_items_delais as pivot')
            ->join('pm_delais as d', 'd.id', '=', 'pivot.delai_id')
            ->where('pivot.item_id', $id)
            ->get(['d.*', 'pivot.genere_par_ia']);

        return response()->json(['success' => true, 'articles' => $articles, 'delais' => $delais]);
    }

    /**
     * Rattacher/détacher manuellement un article à un item (complément du lien IA)
     * body: { article_id }
     */
    public function linkItemArticle(Request $request, int $id)
    {
        $data = $request->validate(['article_id' => 'required|integer|exists:pm_articles_loi,id']);
        $this->db()->table('pm_grilles_verification_items_articles')->insertOrIgnore([
            'item_id' => $id, 'article_id' => $data['article_id'], 'genere_par_ia' => 0,
        ]);
        return response()->json(['success' => true]);
    }

    public function unlinkItemArticle(Request $request, int $id)
    {
        $data = $request->validate(['article_id' => 'required|integer']);
        $this->db()->table('pm_grilles_verification_items_articles')
            ->where('item_id', $id)->where('article_id', $data['article_id'])->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Rattacher/détacher manuellement une opération à un item (M2M)
     * body: { operation_id }
     */
    public function linkItemOperation(Request $request, int $id)
    {
        $data = $request->validate(['operation_id' => 'required|integer|exists:pm_operations,id']);
        $this->db()->table('pm_grilles_verification_items_operations')->insertOrIgnore([
            'item_id' => $id, 'operation_id' => $data['operation_id'], 'genere_par_ia' => 0,
        ]);
        return response()->json(['success' => true]);
    }

    public function unlinkItemOperation(Request $request, int $id)
    {
        $data = $request->validate(['operation_id' => 'required|integer']);
        $this->db()->table('pm_grilles_verification_items_operations')
            ->where('item_id', $id)->where('operation_id', $data['operation_id'])->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Rattacher/détacher manuellement un délai supplémentaire à un item (M2M)
     * body: { delai_id }
     */
    public function linkItemDelaiMulti(Request $request, int $id)
    {
        $data = $request->validate(['delai_id' => 'required|integer|exists:pm_delais,id']);
        $this->db()->table('pm_grilles_verification_items_delais')->insertOrIgnore([
            'item_id' => $id, 'delai_id' => $data['delai_id'], 'genere_par_ia' => 0,
        ]);
        $this->db()->table('pm_grilles_verification_items')->where('id', $id)->update(['depend_delai' => 1]);
        return response()->json(['success' => true]);
    }

    public function unlinkItemDelaiMulti(Request $request, int $id)
    {
        $data = $request->validate(['delai_id' => 'required|integer']);
        $this->db()->table('pm_grilles_verification_items_delais')
            ->where('item_id', $id)->where('delai_id', $data['delai_id'])->delete();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // RÉSOLUTION AUTOMATIQUE POUR L'AUDITEUR
    // ─────────────────────────────────────────────────────────────
    public function resolveForMarche(Request $request)
    {
        $data = $request->validate([
            'nature_marche_code'    => 'required|string',
            'mode_passation_code'   => 'required|string',
            'avec_prequalification' => 'nullable|boolean',
        ]);

        $db = $this->db();

        $grilles = $db->table('pm_grilles_verification')
            ->where('actif', 1)
            ->where(function ($q) use ($data) {
                $q->whereNull('nature_marche_code')
                  ->orWhere('nature_marche_code', $data['nature_marche_code']);
            })
            ->where(function ($q) use ($data) {
                $q->whereNull('mode_passation_code')
                  ->orWhere('mode_passation_code', $data['mode_passation_code']);
            })
            ->where(function ($q) use ($data) {
                $pq = $data['avec_prequalification'] ?? null;
                $q->whereNull('avec_prequalification');
                if (!is_null($pq)) {
                    $q->orWhere('avec_prequalification', $pq);
                }
            })
            ->orderBy('sort')
            ->get();

        $grilleIds = $grilles->pluck('id');

        $items = $db->table('pm_grilles_verification_items')
            ->whereIn('grille_id', $grilleIds)
            ->where('actif', 1)
            ->orderBy('grille_id')->orderBy('sort')
            ->get();

        return response()->json([
            'success' => true,
            'grilles' => $grilles,
            'items'   => $items,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // SEED / RESET (import du fichier grilles_armp_complet.sql)
    // ─────────────────────────────────────────────────────────────
    public function seed()
    {
        $path = database_path('seeders/sql/grilles_armp_complet.sql');
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Fichier seed introuvable.']);
        }

        $sql = file_get_contents($path);
        // découpage naïf sur ";\n" — le fichier ne doit contenir aucun
        // commentaire "--" ni aucune chaîne contenant ce motif, sous peine de
        // voir une instruction entière silencieusement ignorée.
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));

        DB::connection('tenant')->transaction(function () use ($statements) {
            foreach ($statements as $stmt) {
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                DB::connection('tenant')->statement($stmt);
            }
        });

        return response()->json(['success' => true, 'message' => 'Grilles ARMP initialisées.']);
    }

    public function reset()
    {
        $db = $this->db();
        $db->statement('SET FOREIGN_KEY_CHECKS=0');
        $db->table('mission_phase_grille_marches_reponses')->truncate();
        $db->table('mission_phase_grille_marches')->truncate();
        // Tables pivot d'abord (elles n'ont plus de FK, mais l'ordre reste
        // une bonne pratique de lisibilité / cohérence logique)
        foreach ([
            'pm_grilles_verification_items_operations',
            'pm_grilles_verification_items_delais',
            'pm_grilles_verification_items_articles',
            'pm_grilles_verification_organes',
            'pm_articles_loi',
            'pm_grilles_verification_items',
            'pm_grilles_verification',
        ] as $table) {
            if ($db->getSchemaBuilder()->hasTable($table)) {
                $db->table($table)->truncate();
            }
        }
        $db->statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json(['success' => true, 'message' => 'Tables grilles vidées.']);
    }
}