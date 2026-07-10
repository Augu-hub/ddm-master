<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * ParametrageAuditPerformanceController
 *
 * Paramétrage des 16 référentiels de l'Audit de Performance (préfixe ap_) :
 * bénéficiaires, niveaux de risque, approches d'audit, types d'assurance,
 * attributs de preuve, critères (ECO/EFE/EFK/QDS), méthodes de collecte,
 * risques d'audit, méthodes d'analyse, types de preuve, parties prenantes,
 * facteurs de sélection de thème, dimensions du périmètre (Qui/Quoi/Quand/Où),
 * et — pour la preuve d'audit — ses sous-critères, sa nature et ses sources.
 *
 * Contrairement à ParametrageMarchesController (qui expose une méthode
 * store/update/destroy dédiée par table), les 16 tables ap_* sont de simples
 * référentiels code/libellé homogènes : le CRUD est donc factorisé via une
 * seule route générique /{entity}, pilotée par la map self::ENTITIES.
 * Les particularités de chaque table (ex: `niveau` sur ap_niveaux_risque,
 * `beneficiaire_code` sur ap_criteres) sont déclarées dans les règles de
 * validation de la map, pas dans du code dupliqué.
 */
class ParametrageAuditPerformanceController extends Controller
{
    private function db()
    {
        return DB::connection('tenant');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONFIGURATION DES 13 RÉFÉRENTIELS
    //  clé (slug utilisé dans l'URL) => [table, champs, règles de validation]
    // ═══════════════════════════════════════════════════════════════════

    private const ENTITIES = [
        'beneficiaires' => [
            'table'  => 'ap_beneficiaires',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'niveaux-risque' => [
            'table'  => 'ap_niveaux_risque',
            'fields' => ['niveau', 'code', 'libelle', 'norme', 'couleur'],
            'rules'  => [
                'niveau'  => 'required|integer|min:1|max:3',
                'code'    => 'required|string|max:20',
                'libelle' => 'required|string|max:255',
                'norme'   => 'nullable|string|max:50',
                'couleur' => 'nullable|string|max:20',
            ],
        ],
        'approches-audit' => [
            'table'  => 'ap_approches_audit',
            'fields' => ['code', 'libelle', 'description', 'norme'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
                'norme'       => 'nullable|string|max:50',
            ],
        ],
        'types-assurance' => [
            'table'  => 'ap_types_assurance',
            'fields' => ['code', 'libelle', 'description', 'norme'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
                'norme'       => 'nullable|string|max:50',
            ],
        ],
        'attributs-preuve' => [
            'table'  => 'ap_attributs_preuve',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'criteres' => [
            'table'  => 'ap_criteres',
            'fields' => ['code', 'nature', 'description', 'but', 'norme', 'beneficiaire_code'],
            'rules'  => [
                'code'              => 'required|string|max:20',
                'nature'            => 'required|string|max:100',
                'description'       => 'nullable|string',
                'but'               => 'nullable|string|max:255',
                'norme'             => 'nullable|string|max:50',
                'beneficiaire_code' => 'nullable|string|max:20',
            ],
        ],
        'methodes-collecte' => [
            'table'  => 'ap_methodes_collecte',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'risques-audit' => [
            'table'  => 'ap_risques_audit',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'methodes-analyse' => [
            'table'  => 'ap_methodes_analyse',
            'fields' => ['code', 'libelle', 'description', 'type'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
                'type'        => 'nullable|string|in:quantitative,qualitative',
            ],
        ],
        'types-preuve' => [
            'table'  => 'ap_types_preuve',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'parties-prenantes' => [
            'table'  => 'ap_parties_prenantes',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'facteurs-selection-theme' => [
            'table'  => 'ap_facteurs_selection_theme',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:30',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'perimetre-dimensions' => [
            'table'  => 'ap_perimetre_dimensions',
            'fields' => ['code', 'libelle', 'questions_cles'],
            'rules'  => [
                'code'           => 'required|string|max:20',
                'libelle'        => 'required|string|max:50',
                'questions_cles' => 'nullable|string',
            ],
        ],
        'sous-criteres-preuve' => [
            'table'  => 'ap_sous_criteres_preuve',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'nature-preuve' => [
            'table'  => 'ap_nature_preuve',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
        'sources-preuve' => [
            'table'  => 'ap_sources_preuve',
            'fields' => ['code', 'libelle', 'description'],
            'rules'  => [
                'code'        => 'required|string|max:20',
                'libelle'     => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════
    //  VUE PRINCIPALE
    // ═══════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        return Inertia::render('dashboards/Auditor/ParametrageAuditPerformance/Index', array_merge(
            ['activeTab' => $request->query('tab', 'referentiels')],
            $this->allData()
        ));
    }

    public function apiAll()
    {
        return response()->json($this->allData());
    }

    private function allData(): array
    {
        $out = [];
        foreach (self::ENTITIES as $slug => $cfg) {
            // beneficiaires -> beneficiaires ; niveaux-risque -> niveauxRisque ;
            // facteurs-selection-theme -> facteursSelectionTheme
            $propKey = Str::camel($slug);
            try {
                $out[$propKey] = $this->db()->table($cfg['table'])
                    ->orderBy('sort')
                    ->get()
                    ->map(fn ($r) => (array) $r)
                    ->toArray();
            } catch (\Exception $e) {
                // Ne fait jamais planter toute la page pour une seule table.
                // On journalise pour diagnostiquer (table absente, mauvaise
                // connexion, colonne manquante, etc.) et on renvoie un
                // tableau vide : le référentiel concerné apparaîtra "vide"
                // dans la vue plutôt que de casser les 15 autres.
                \Illuminate\Support\Facades\Log::warning(
                    "[ParametrageAuditPerformance] Échec lecture table '{$cfg['table']}' (entité '{$slug}') : " . $e->getMessage()
                );
                $out[$propKey] = [];
            }
        }

        return $out;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CRUD GÉNÉRIQUE — /{entity} (slug ci-dessus)
    // ═══════════════════════════════════════════════════════════════════

    private function config(string $entity): array
    {
        if (! isset(self::ENTITIES[$entity])) {
            abort(404, "Référentiel « {$entity} » inconnu.");
        }

        return self::ENTITIES[$entity];
    }

    public function store(Request $request, string $entity)
    {
        $cfg  = $this->config($entity);
        $data = $request->validate($this->rulesWithUniqueCode($cfg, null));

        $data['sort'] = $this->db()->table($cfg['table'])->max('sort') + 1;
        $id = $this->db()->table($cfg['table'])->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function update(Request $request, string $entity, int $id)
    {
        $cfg  = $this->config($entity);
        $data = $request->validate($this->rulesWithUniqueCode($cfg, $id));

        $this->db()->table($cfg['table'])->where('id', $id)->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(string $entity, int $id)
    {
        $cfg = $this->config($entity);

        $this->db()->table($cfg['table'])->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Ajoute la contrainte d'unicité sur `code` (sauf pour ap_niveaux_risque,
     * qui utilise `niveau` comme clé fonctionnelle en plus de `code`).
     */
    private function rulesWithUniqueCode(array $cfg, ?int $id): array
    {
        $rules = $cfg['rules'];

        if (isset($rules['code'])) {
            $unique = "unique:tenant.{$cfg['table']},code" . ($id ? ",{$id}" : '');
            $rules['code'] .= "|{$unique}";
        }

        return $rules;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SEED — database/seeders/sql/ap_parametrage_performance.sql
    // ═══════════════════════════════════════════════════════════════════

    public function seed()
    {
        $db     = $this->db();
        $tables = array_map(fn ($cfg) => $cfg['table'], self::ENTITIES);

        $nonVides = collect($tables)->filter(function ($t) use ($db) {
            try {
                return $db->table($t)->count() > 0;
            } catch (\Exception $e) {
                // La table n'existe pas encore — le script de seed la créera
                return false;
            }
        })->values();

        if ($nonVides->isNotEmpty()) {
            return response()->json([
                'success'          => false,
                'message'          => 'Tables non vides : ' . $nonVides->implode(', ') . ' — Utilisez Reset avant de re-seeder.',
                'tables_non_vides' => $nonVides,
            ], 422);
        }

        $sqlPath = database_path('seeders/sql/ap_parametrage_performance.sql');
        if (! file_exists($sqlPath)) {
            return response()->json([
                'success' => false,
                'message' => "Fichier SQL introuvable : {$sqlPath}",
            ], 500);
        }

        try {
            $sql        = file_get_contents($sqlPath);
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn ($s) => strlen($s) > 10 && ! str_starts_with(ltrim($s), '--')
            );

            $db->unprepared('SET FOREIGN_KEY_CHECKS=0');
            foreach ($statements as $stmt) {
                $db->unprepared($stmt);
            }
            $db->unprepared('SET FOREIGN_KEY_CHECKS=1');

            $counts = [];
            foreach ($tables as $t) {
                $counts[$t] = $db->table($t)->count();
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Seed effectué depuis ' . basename($sqlPath),
                'inserted' => $counts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur seed : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  RESET
    // ═══════════════════════════════════════════════════════════════════

    public function reset()
    {
        $db = $this->db();
        $db->unprepared('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::ENTITIES as $cfg) {
            try {
                $db->table($cfg['table'])->truncate();
            } catch (\Exception $e) {
                // table pas encore créée — rien à vider
            }
        }

        $db->unprepared('SET FOREIGN_KEY_CHECKS=1');

        return response()->json(['success' => true, 'message' => 'Tables vidées. Relancez le seed.']);
    }
}