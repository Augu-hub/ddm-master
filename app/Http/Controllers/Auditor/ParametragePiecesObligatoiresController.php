<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * ParametragePiecesObligatoiresController
 *
 * Paramétrage des pièces obligatoires (disponibilité documentaire) :
 *  - Catégories de pièces (pm_pieces_categories)
 *  - Catalogue des pièces obligatoires par catégorie (pm_pieces_obligatoires)
 *  - Grille d'appréciation qualitative selon le % d'absence (pm_grille_appreciation_disponibilite)
 *  - Paramètres numériques d'audit, ex: seuil d'auditabilité (pm_parametres_audit)
 *
 * mission_pieces_disponibilite / mission_pieces_verification sont les
 * tables d'INSTANCE (une vérification par marché audité) — gérées par le
 * module mission, pas par cet écran de paramétrage. On expose juste leur
 * compte ici à titre indicatif (statsCards côté front).
 */
class ParametragePiecesObligatoiresController extends Controller
{
    private function db()
    {
        return DB::connection('tenant');
    }

    // ─────────────────────────────────────────────────────────────
    // VUE PRINCIPALE / API JSON GLOBALE
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        return Inertia::render('dashboards/Auditor/ParametrageMarches/PiecesObligatoires', array_merge(
            $this->buildPayload(),
            ['activeTab' => $request->query('tab', 'categories')]
        ));
    }

    public function apiAll()
    {
        return response()->json($this->buildPayload());
    }

    private function buildPayload(): array
    {
        $db = $this->db();

        return [
            'categories'          => $db->table('pm_pieces_categories')->orderBy('sort')->get(),
            'pieces'              => $db->table('pm_pieces_obligatoires as p')
                                        ->join('pm_pieces_categories as c', 'c.id', '=', 'p.categorie_id')
                                        ->select('p.*', 'c.code as categorie_code', 'c.libelle as categorie_libelle')
                                        ->orderBy('c.sort')->orderBy('p.sort')
                                        ->get(),
            'grilleAppreciation'  => $db->table('pm_grille_appreciation_disponibilite')->orderBy('sort')->get(),
            'parametresAudit'     => $db->table('pm_parametres_audit')->orderBy('sort')->get(),
            'missionsCount'       => $db->getSchemaBuilder()->hasTable('mission_pieces_disponibilite')
                                        ? $db->table('mission_pieces_disponibilite')->count()
                                        : 0,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CATÉGORIES (pm_pieces_categories)
    // ═══════════════════════════════════════════════════════════════════

    public function storeCategorie(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:30|unique:pm_pieces_categories,code',
            'libelle'     => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_pieces_categories')->max('sort') + 1;
        $data['actif'] = 1;

        $id = $this->db()->table('pm_pieces_categories')->insertGetId($data);
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateCategorie(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:30|unique:pm_pieces_categories,code,{$id}",
            'libelle'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'actif'       => 'sometimes|boolean',
        ]);
        $this->db()->table('pm_pieces_categories')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyCategorie(int $id)
    {
        $usages = $this->db()->table('pm_pieces_obligatoires')->where('categorie_id', $id)->count();
        if ($usages > 0) {
            return response()->json([
                'success' => false,
                'message' => "Catégorie utilisée par {$usages} pièce(s) — supprimez-les d'abord.",
            ], 422);
        }
        $this->db()->table('pm_pieces_categories')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PIÈCES OBLIGATOIRES (pm_pieces_obligatoires)
    // ═══════════════════════════════════════════════════════════════════

    public function storePiece(Request $request)
    {
        $data = $request->validate([
            'categorie_id' => 'required|integer|exists:pm_pieces_categories,id',
            'code'         => 'required|string|max:50|unique:pm_pieces_obligatoires,code',
            'libelle'      => 'required|string|max:255',
            'incidence'    => 'required|string|in:directe,sans_incidence',
            'obligatoire'  => 'nullable|boolean',
        ]);
        $data['obligatoire'] = $data['obligatoire'] ?? 1;
        $data['actif']       = 1;
        $data['sort']        = $this->db()->table('pm_pieces_obligatoires')
                                    ->where('categorie_id', $data['categorie_id'])->max('sort') + 1;

        $id = $this->db()->table('pm_pieces_obligatoires')->insertGetId($data);
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updatePiece(Request $request, int $id)
    {
        $data = $request->validate([
            'categorie_id' => 'sometimes|required|integer|exists:pm_pieces_categories,id',
            'code'         => "sometimes|required|string|max:50|unique:pm_pieces_obligatoires,code,{$id}",
            'libelle'      => 'sometimes|required|string|max:255',
            'incidence'    => 'sometimes|required|string|in:directe,sans_incidence',
            'obligatoire'  => 'nullable|boolean',
            'actif'        => 'sometimes|boolean',
            'sort'         => 'sometimes|integer',
        ]);
        $this->db()->table('pm_pieces_obligatoires')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyPiece(int $id)
    {
        $db = $this->db();
        if ($db->getSchemaBuilder()->hasTable('mission_pieces_verification')) {
            $usages = $db->table('mission_pieces_verification')->where('piece_id', $id)->count();
            if ($usages > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Pièce déjà utilisée dans {$usages} vérification(s) de mission — suppression bloquée.",
                ], 422);
            }
        }
        $db->table('pm_pieces_obligatoires')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function reorderPieces(Request $request)
    {
        $data = $request->validate(['items' => 'required|array']);
        $db = $this->db();
        foreach ($data['items'] as $row) {
            $db->table('pm_pieces_obligatoires')->where('id', $row['id'])->update(['sort' => $row['sort']]);
        }
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  GRILLE D'APPRÉCIATION (pm_grille_appreciation_disponibilite)
    // ═══════════════════════════════════════════════════════════════════

    public function storeGrilleAppreciation(Request $request)
    {
        $data = $request->validate([
            'borne_min'     => 'nullable|numeric',
            'operateur_min' => 'nullable|string|max:5',
            'borne_max'     => 'nullable|numeric',
            'operateur_max' => 'nullable|string|max:5',
            'appreciation'  => 'required|string|max:50',
            'couleur'       => 'nullable|string|max:20',
        ]);
        $data['couleur'] = $data['couleur'] ?? 'gray';
        $data['actif']   = 1;
        $data['sort']    = $this->db()->table('pm_grille_appreciation_disponibilite')->max('sort') + 1;

        $id = $this->db()->table('pm_grille_appreciation_disponibilite')->insertGetId($data);
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateGrilleAppreciation(Request $request, int $id)
    {
        $data = $request->validate([
            'borne_min'     => 'nullable|numeric',
            'operateur_min' => 'nullable|string|max:5',
            'borne_max'     => 'nullable|numeric',
            'operateur_max' => 'nullable|string|max:5',
            'appreciation'  => 'required|string|max:50',
            'couleur'       => 'nullable|string|max:20',
            'actif'         => 'sometimes|boolean',
        ]);
        $this->db()->table('pm_grille_appreciation_disponibilite')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyGrilleAppreciation(int $id)
    {
        $this->db()->table('pm_grille_appreciation_disponibilite')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Résout l'appréciation qualitative applicable pour un % d'absence donné.
     * Utilisé par le module mission au moment de calculer la disponibilité
     * documentaire d'un marché audité.
     */
    public function resoudreAppreciation(Request $request)
    {
        $data = $request->validate(['taux_absence' => 'required|numeric|min:0|max:100']);
        $x = (float) $data['taux_absence'];

        $regle = $this->db()->table('pm_grille_appreciation_disponibilite')
            ->where('actif', 1)
            ->where(function ($q) use ($x) {
                $q->whereNull('borne_min')
                  ->orWhere(fn($q2) => $q2->where('operateur_min', '>=')->where('borne_min', '<=', $x))
                  ->orWhere(fn($q2) => $q2->where('operateur_min', '>')->where('borne_min', '<', $x));
            })
            ->where(function ($q) use ($x) {
                $q->whereNull('borne_max')
                  ->orWhere(fn($q2) => $q2->where('operateur_max', '<=')->where('borne_max', '>=', $x))
                  ->orWhere(fn($q2) => $q2->where('operateur_max', '<')->where('borne_max', '>', $x));
            })
            ->orderBy('sort')
            ->first();

        return response()->json(['success' => true, 'appreciation' => $regle]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PARAMÈTRES D'AUDIT (pm_parametres_audit)
    // ═══════════════════════════════════════════════════════════════════

    public function storeParametreAudit(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:50|unique:pm_parametres_audit,code',
            'libelle'     => 'required|string|max:255',
            'valeur'      => 'required|numeric',
            'unite'       => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);
        $data['unite'] = $data['unite'] ?? '%';
        $data['sort']  = $this->db()->table('pm_parametres_audit')->max('sort') + 1;

        $id = $this->db()->table('pm_parametres_audit')->insertGetId($data);
        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateParametreAudit(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "sometimes|required|string|max:50|unique:pm_parametres_audit,code,{$id}",
            'libelle'     => 'sometimes|required|string|max:255',
            'valeur'      => 'sometimes|required|numeric',
            'unite'       => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_parametres_audit')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyParametreAudit(int $id)
    {
        $this->db()->table('pm_parametres_audit')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // SEED / RESET (import du fichier pieces_obligatoires.sql)
    // ─────────────────────────────────────────────────────────────
    public function seed()
    {
        $path = database_path('seeders/sql/pieces_obligatoires.sql');
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Fichier seed introuvable.']);
        }

        $sql = file_get_contents($path);
        // IMPORTANT : ce fichier ne doit contenir AUCUN commentaire "--" —
        // le découpage naïf sur ";\n" fusionnerait un commentaire avec
        // l'instruction réelle qui le suit et la ferait sauter silencieusement
        // (str_starts_with($stmt, '--') s'appliquerait au bloc entier).
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));

        DB::connection('tenant')->transaction(function () use ($statements) {
            foreach ($statements as $stmt) {
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                DB::connection('tenant')->statement($stmt);
            }
        });

        return response()->json(['success' => true, 'message' => 'Pièces obligatoires initialisées.']);
    }

    public function reset()
    {
        $db = $this->db();
        $db->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'mission_pieces_verification',
            'mission_pieces_disponibilite',
            'pm_pieces_obligatoires',
            'pm_pieces_categories',
            'pm_grille_appreciation_disponibilite',
            'pm_parametres_audit',
        ] as $table) {
            if ($db->getSchemaBuilder()->hasTable($table)) {
                $db->table($table)->truncate();
            }
        }
        $db->statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json(['success' => true, 'message' => 'Tables pièces obligatoires vidées.']);
    }
}