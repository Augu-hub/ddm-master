<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * ParametrageMarchesController — V4
 *
 * Corrections V4 (par rapport à V3) :
 *  - AMI / DAO ne sont PAS des modes de passation : c'est un nouveau
 *    champ "phase_option" sur pm_delais (valeurs : AMI, DAO, AMI+DAO),
 *    indépendant des modes de passation existants.
 *  - Un délai peut désormais être rattaché à PLUSIEURS modes de
 *    passation existants (AOO, AOR, DRP, DC, SD, GAG, ACC...) via la
 *    nouvelle table pivot pm_delai_modes. Le champ condition_mode
 *    (ancien, mono-valeur) est conservé en base (legacy, recopie du
 *    1er mode choisi) mais storeDelai/updateDelai écrivent désormais
 *    dans pm_delai_modes.
 *  - detecterModePassation() filtre désormais les délais applicables
 *    via pm_delai_modes (repli sur condition_mode pour les lignes pas
 *    encore migrées vers le pivot).
 *  - allData() inclut delaiModes.
 *  - Nouvelles routes/méthodes storeDelaiMode / destroyDelaiMode,
 *    symétriques à storeDelaiOrgane / destroyDelaiOrgane.
 */
class ParametrageMarchesController extends Controller
{
    private function db()
    {
        return DB::connection('tenant');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  VUE PRINCIPALE
    // ═══════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        return Inertia::render('dashboards/Auditor/ParametrageMarches/Index', array_merge(
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
        return [
            'typesEntites'    => $this->getTypesEntites(),
            'sourcesFinance'  => $this->getSourcesFinancement(),
            'naturesMarche'   => $this->getNaturesMarche(),
            'modesPassation'  => $this->getModesPassation(),
            'organes'         => $this->getOrganes(),
            'modeOrganes'     => $this->getModeOrganes(),       // [{mode_passation_code, organe_code}]
            'seuilsGeneraux'  => $this->getSeuilsGeneraux(),
            'seuilsAC'        => $this->getSeuilsAC(),          // flat array
            'seuilsAcOrganes' => $this->getSeuilsAcOrganes(),   // [{seuil_ac_id, organe_code}]
            'operations'      => $this->getOperations(),
            'datesReference'  => $this->getDatesReference(),
            'delais'          => $this->getDelais(),
            'delaiOrganes'    => $this->getDelaiOrganes(),      // [{delai_id, organe_code}]
            'delaiModes'      => $this->getDelaiModes(),        // [{delai_id, mode_passation_code}]
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  DÉTECTION AUTOMATIQUE DU MODE DE PASSATION
    // ═══════════════════════════════════════════════════════════════════

    public function detecterModePassation(Request $request)
    {
        $request->validate([
            'montant'            => 'required|numeric|min:0',
            'type_entite_code'   => 'required|string',
            'nature_marche_code' => 'required|string',
        ]);

        $montant = (float) $request->montant;
        $acCode  = $request->type_entite_code;
        $natCode = $request->nature_marche_code;
        $db      = $this->db();

        $matchBornes = fn($q) => $q
            ->where(fn($q2) => $q2
                ->whereNull('valeur_min')
                ->orWhere(fn($q3) => $q3->where('operateur_min', '>=')->where('valeur_min', '<=', $montant))
                ->orWhere(fn($q3) => $q3->where('operateur_min', '>') ->where('valeur_min', '<',  $montant))
            )
            ->where(fn($q2) => $q2
                ->whereNull('valeur_max')
                ->orWhere(fn($q3) => $q3->where('operateur_max', '<=')->where('valeur_max', '>=', $montant))
                ->orWhere(fn($q3) => $q3->where('operateur_max', '<') ->where('valeur_max', '>',  $montant))
            );

        $regleAC = $db->table('pm_seuils_ac')
            ->where('type_entite_code',   $acCode)
            ->where('nature_marche_code', $natCode)
            ->where($matchBornes)
            ->orderBy('sort')
            ->first();

        if ($regleAC) {
            $modeCode = $regleAC->mode_passation_code;
            // Organes de la plage spécifique
            $organes  = $db->table('pm_seuils_ac_organes')
                ->where('seuil_ac_id', $regleAC->id)
                ->pluck('organe_code');
            $source   = 'seuil_ac';
        } else {
            $sg = $db->table('pm_seuils_generaux')
                ->whereNotNull('code_mode_passation')
                ->where($matchBornes)
                ->orderBy('sort')
                ->first();

            $modeCode = $sg?->code_mode_passation ?? 'AOO';
            // Organes du mode (pm_mode_organes)
            $organes  = $db->table('pm_mode_organes')
                ->where('mode_passation_code', $modeCode)
                ->pluck('organe_code');
            $source   = 'seuil_general';
        }

        // Délais applicables : on matche via le pivot pm_delai_modes.
        // Repli sur l'ancien condition_mode pour les lignes qui n'ont
        // encore aucune entrée dans le pivot (non migrées / génériques).
        $delaisApplicables = $db->table('pm_delais as d')
            ->join('pm_operations as o',           'd.operation_id',      '=', 'o.id')
            ->leftJoin('pm_dates_reference as dr',  'd.date_reference_id', '=', 'dr.id')
            ->where(function ($q) use ($db, $modeCode) {
                $q->where(function ($q2) use ($modeCode) {
                    // Cas 1 : le délai a des modes dans le pivot, et le
                    // mode détecté en fait partie
                    $q2->whereExists(function ($sub) use ($modeCode) {
                        $sub->select(DB::raw(1))
                            ->from('pm_delai_modes as dm')
                            ->whereColumn('dm.delai_id', 'd.id')
                            ->where('dm.mode_passation_code', $modeCode);
                    });
                })->orWhere(function ($q2) use ($modeCode) {
                    // Cas 2 : le délai n'a AUCUNE entrée dans le pivot ->
                    // on retombe sur l'ancien condition_mode (NULL = tous)
                    $q2->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pm_delai_modes as dm')
                            ->whereColumn('dm.delai_id', 'd.id');
                    })->where(function ($q3) use ($modeCode) {
                        $q3->whereNull('d.condition_mode')->orWhere('d.condition_mode', $modeCode);
                    });
                });
            })
            ->select('d.*', 'o.libelle as operation_libelle', 'dr.libelle as date_reference_libelle', 'dr.date_valeur')
            ->orderBy('d.sort')
            ->get();

        $mode = $db->table('pm_modes_passation')->where('code', $modeCode)->first();

        return response()->json([
            'mode_passation_code'    => $modeCode,
            'mode_passation_libelle' => $mode?->libelle ?? $modeCode,
            'organes_competents'     => $organes,
            'source_regle'           => $source,
            'delais'                 => $delaisApplicables,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  GETTERS PRIVÉS
    // ═══════════════════════════════════════════════════════════════════

    private function getTypesEntites(): array
    {
        return $this->db()->table('pm_types_entites')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getSourcesFinancement(): array
    {
        return $this->db()->table('pm_sources_financement')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getNaturesMarche(): array
    {
        return $this->db()->table('pm_natures_marche')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getModesPassation(): array
    {
        return $this->db()->table('pm_modes_passation')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getOrganes(): array
    {
        return $this->db()->table('pm_organes_controle')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getModeOrganes(): array
    {
        return $this->db()->table('pm_mode_organes')
            ->orderBy('mode_passation_code')->orderBy('sort')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getSeuilsGeneraux(): array
    {
        return $this->db()->table('pm_seuils_generaux')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getSeuilsAC(): array
    {
        return $this->db()->table('pm_seuils_ac')
            ->orderBy('type_entite_code')->orderBy('nature_marche_code')->orderBy('sort')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getSeuilsAcOrganes(): array
    {
        return $this->db()->table('pm_seuils_ac_organes')
            ->orderBy('seuil_ac_id')->orderBy('sort')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getOperations(): array
    {
        return $this->db()->table('pm_operations')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getDatesReference(): array
    {
        return $this->db()->table('pm_dates_reference')
            ->orderBy('sort')->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getDelais(): array
    {
        return $this->db()
            ->table('pm_delais as d')
            ->join('pm_operations as o',           'd.operation_id',      '=', 'o.id')
            ->leftJoin('pm_dates_reference as dr',  'd.date_reference_id', '=', 'dr.id')
            ->select(
                'd.*',
                'o.code as operation_code',
                'o.libelle as operation_libelle',
                'dr.code as date_reference_code',
                'dr.libelle as date_reference_libelle',
                'dr.date_valeur as date_valeur'
            )
            ->orderBy('d.sort')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getDelaiOrganes(): array
    {
        return $this->db()->table('pm_delai_organes')
            ->orderBy('delai_id')->orderBy('sort')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    private function getDelaiModes(): array
    {
        return $this->db()->table('pm_delai_modes')
            ->orderBy('delai_id')->orderBy('sort')
            ->get()->map(fn($r) => (array)$r)->toArray();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  TYPES ENTITÉS
    // ═══════════════════════════════════════════════════════════════════

    public function storeTypeEntite(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:tenant.pm_types_entites,code',
            'libelle'     => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_types_entites')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_types_entites')->insertGetId($data)]);
    }

    public function updateTypeEntite(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:20|unique:tenant.pm_types_entites,code,{$id}",
            'libelle'     => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_types_entites')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyTypeEntite(int $id)
    {
        $this->db()->table('pm_types_entites')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SOURCES FINANCEMENT
    // ═══════════════════════════════════════════════════════════════════

    public function storeSourceFinancement(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:tenant.pm_sources_financement,code',
            'libelle'     => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_sources_financement')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_sources_financement')->insertGetId($data)]);
    }

    public function updateSourceFinancement(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:20|unique:tenant.pm_sources_financement,code,{$id}",
            'libelle'     => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_sources_financement')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroySourceFinancement(int $id)
    {
        $this->db()->table('pm_sources_financement')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  NATURES MARCHÉ
    // ═══════════════════════════════════════════════════════════════════

    public function storeNatureMarche(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:tenant.pm_natures_marche,code',
            'libelle'     => 'required|string|max:255',
            'sous_type'   => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_natures_marche')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_natures_marche')->insertGetId($data)]);
    }

    public function updateNatureMarche(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:20|unique:tenant.pm_natures_marche,code,{$id}",
            'libelle'     => 'required|string|max:255',
            'sous_type'   => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_natures_marche')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyNatureMarche(int $id)
    {
        $this->db()->table('pm_natures_marche')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  MODES PASSATION
    // ═══════════════════════════════════════════════════════════════════

    public function storeModePassation(Request $request)
    {
        $data = $request->validate([
            'code'         => 'required|string|max:20|unique:tenant.pm_modes_passation,code',
            'libelle'      => 'required|string|max:255',
            'famille'      => 'nullable|string|max:100',
            'code_famille' => 'nullable|string|max:10',
            'description'  => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_modes_passation')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_modes_passation')->insertGetId($data)]);
    }

    public function updateModePassation(Request $request, int $id)
    {
        $data = $request->validate([
            'code'         => "required|string|max:20|unique:tenant.pm_modes_passation,code,{$id}",
            'libelle'      => 'required|string|max:255',
            'famille'      => 'nullable|string|max:100',
            'code_famille' => 'nullable|string|max:10',
            'description'  => 'nullable|string',
        ]);
        $this->db()->table('pm_modes_passation')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyModePassation(int $id)
    {
        $this->db()->table('pm_modes_passation')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ORGANES
    // ═══════════════════════════════════════════════════════════════════

    public function storeOrgane(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:tenant.pm_organes_controle,code',
            'libelle'     => 'required|string|max:255',
            'sigle'       => 'nullable|string|max:20',
            'niveau'      => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_organes_controle')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_organes_controle')->insertGetId($data)]);
    }

    public function updateOrgane(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:20|unique:tenant.pm_organes_controle,code,{$id}",
            'libelle'     => 'required|string|max:255',
            'sigle'       => 'nullable|string|max:20',
            'niveau'      => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_organes_controle')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyOrgane(int $id)
    {
        $this->db()->table('pm_organes_controle')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ORGANES LIÉS AUX MODES PM
    // ═══════════════════════════════════════════════════════════════════

    public function storeModeOrgane(Request $request)
    {
        $data = $request->validate([
            'mode_passation_code' => 'required|string|max:20',
            'organe_code'         => 'required|string|max:20',
        ]);
        $data['sort'] = $this->db()->table('pm_mode_organes')
            ->where('mode_passation_code', $data['mode_passation_code'])->max('sort') + 1;
        $this->db()->table('pm_mode_organes')->insertOrIgnore($data);
        return response()->json(['success' => true]);
    }

    public function destroyModeOrgane(Request $request)
    {
        $request->validate([
            'mode_passation_code' => 'required|string',
            'organe_code'         => 'required|string',
        ]);
        $this->db()->table('pm_mode_organes')
            ->where('mode_passation_code', $request->mode_passation_code)
            ->where('organe_code',         $request->organe_code)
            ->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SEUILS GÉNÉRAUX
    // ═══════════════════════════════════════════════════════════════════

    public function storeSeuilGeneral(Request $request)
    {
        $data = $request->validate([
            'type_seuil'          => 'required|string|max:255',
            'valeur_min'          => 'nullable|numeric|min:0',
            'valeur_max'          => 'nullable|numeric|min:0',
            'operateur_min'       => 'nullable|string|max:5',
            'operateur_max'       => 'nullable|string|max:5',
            'code_mode_passation' => 'nullable|string|max:20',
            'description'         => 'nullable|string',
            'couleur'             => 'nullable|string|max:20',
        ]);
        $data['sort'] = $this->db()->table('pm_seuils_generaux')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_seuils_generaux')->insertGetId($data)]);
    }

    public function updateSeuilGeneral(Request $request, int $id)
    {
        $data = $request->validate([
            'type_seuil'          => 'required|string|max:255',
            'valeur_min'          => 'nullable|numeric|min:0',
            'valeur_max'          => 'nullable|numeric|min:0',
            'operateur_min'       => 'nullable|string|max:5',
            'operateur_max'       => 'nullable|string|max:5',
            'code_mode_passation' => 'nullable|string|max:20',
            'description'         => 'nullable|string',
            'couleur'             => 'nullable|string|max:20',
        ]);
        $this->db()->table('pm_seuils_generaux')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroySeuilGeneral(int $id)
    {
        $this->db()->table('pm_seuils_generaux')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SEUILS PAR AC
    // ═══════════════════════════════════════════════════════════════════

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
        $data['sort'] = $this->db()->table('pm_seuils_ac')
            ->where('type_entite_code', $data['type_entite_code'])->max('sort') + 1;
        $id = $this->db()->table('pm_seuils_ac')->insertGetId($data);

        // Auto-associer les organes du mode PM depuis pm_mode_organes
        $mode = $this->db()->table('pm_modes_passation')
            ->where('code', $data['mode_passation_code'])->first();

        if ($mode && $mode->code_famille === 'PM') {
            $organesCodes = $this->db()->table('pm_mode_organes')
                ->where('mode_passation_code', $data['mode_passation_code'])
                ->pluck('organe_code');

            $sort = 1;
            foreach ($organesCodes as $oc) {
                $this->db()->table('pm_seuils_ac_organes')->insertOrIgnore([
                    'seuil_ac_id' => $id,
                    'organe_code' => $oc,
                    'sort'        => $sort++,
                ]);
            }
        }

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateSeuilAC(Request $request, int $id)
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
        $this->db()->table('pm_seuils_ac')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroySeuilAC(int $id)
    {
        // La FK ON DELETE CASCADE supprime pm_seuils_ac_organes automatiquement
        $this->db()->table('pm_seuils_ac')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ORGANES LIÉS AUX SEUILS AC
    // ═══════════════════════════════════════════════════════════════════

    public function storeSeuilAcOrgane(Request $request)
    {
        $data = $request->validate([
            'seuil_ac_id' => 'required|integer|exists:tenant.pm_seuils_ac,id',
            'organe_code' => 'required|string|max:20',
        ]);
        $data['sort'] = $this->db()->table('pm_seuils_ac_organes')
            ->where('seuil_ac_id', $data['seuil_ac_id'])->max('sort') + 1;
        $this->db()->table('pm_seuils_ac_organes')->insertOrIgnore($data);
        return response()->json(['success' => true]);
    }

    public function destroySeuilAcOrgane(Request $request)
    {
        $request->validate([
            'seuil_ac_id' => 'required|integer',
            'organe_code' => 'required|string',
        ]);
        $this->db()->table('pm_seuils_ac_organes')
            ->where('seuil_ac_id', $request->seuil_ac_id)
            ->where('organe_code', $request->organe_code)
            ->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  OPÉRATIONS
    // ═══════════════════════════════════════════════════════════════════

    public function storeOperation(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:30|unique:tenant.pm_operations,code',
            'libelle'     => 'required|string',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_operations')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_operations')->insertGetId($data)]);
    }

    public function updateOperation(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:30|unique:tenant.pm_operations,code,{$id}",
            'libelle'     => 'required|string',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_operations')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyOperation(int $id)
    {
        $usages = $this->db()->table('pm_delais')->where('operation_id', $id)->count();
        if ($usages > 0) {
            return response()->json([
                'success' => false,
                'message' => "Opération utilisée dans {$usages} délai(s) — supprimez-les d'abord.",
            ], 422);
        }
        $this->db()->table('pm_operations')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  DATES DE RÉFÉRENCE
    // ═══════════════════════════════════════════════════════════════════

    public function storeDateReference(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:30|unique:tenant.pm_dates_reference,code',
            'libelle'     => 'required|string|max:500',
            'date_valeur' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        $data['sort'] = $this->db()->table('pm_dates_reference')->max('sort') + 1;
        return response()->json(['success' => true, 'id' => $this->db()->table('pm_dates_reference')->insertGetId($data)]);
    }

    public function updateDateReference(Request $request, int $id)
    {
        $data = $request->validate([
            'code'        => "required|string|max:30|unique:tenant.pm_dates_reference,code,{$id}",
            'libelle'     => 'required|string|max:500',
            'date_valeur' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        $this->db()->table('pm_dates_reference')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyDateReference(int $id)
    {
        $usages = $this->db()->table('pm_delais')->where('date_reference_id', $id)->count();
        if ($usages > 0) {
            return response()->json([
                'success' => false,
                'message' => "Date utilisée dans {$usages} délai(s) — supprimez-les d'abord.",
            ], 422);
        }
        $this->db()->table('pm_dates_reference')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  DÉLAIS — plusieurs organes (pm_delai_organes), plusieurs modes de
    //  passation (pm_delai_modes) et option de phase AMI/DAO
    // ═══════════════════════════════════════════════════════════════════

    public function storeDelai(Request $request)
    {
        $validated = $request->validate([
            'operation_id'      => 'required|integer|exists:tenant.pm_operations,id',
            'delai_valeur'      => 'nullable|integer|min:0',
            'delai_unite'       => 'nullable|string|max:30',
            'delai_type'        => 'required|string|in:calendaire,ouvrable,sans-delai,non-defini',
            'mot_liaison'       => 'nullable|string|max:20',
            'date_reference_id' => 'nullable|integer|exists:tenant.pm_dates_reference,id',
            // Statut/option du paramètre : AMI, DAO ou AMI+DAO. N'est PAS
            // un mode de passation — c'est indépendant de modes_codes.
            'phase_option'      => 'nullable|string|in:AMI,DAO,AMI+DAO',
            'note'              => 'nullable|string',
            'organes_codes'     => 'required|array|min:1',
            'organes_codes.*'   => 'required|string|max:20',
            // Modes de passation multiples, choisis parmi ceux déjà
            // existants (AOO, AOR, DRP, DC, SD, GAG, ACC...)
            'modes_codes'       => 'nullable|array',
            'modes_codes.*'     => 'string|max:20|exists:tenant.pm_modes_passation,code',
        ]);

        $payload = collect($validated)->except(['organes_codes', 'modes_codes'])->toArray();
        $payload['sort'] = $this->db()->table('pm_delais')->max('sort') + 1;

        // condition_mode (legacy) : recopie du 1er mode choisi, pour
        // compatibilité avec l'ancien code / anciens rapports
        $modesCodes = $validated['modes_codes'] ?? [];
        $payload['condition_mode'] = $modesCodes[0] ?? null;

        // Nullifier valeur/unité si sans-delai ou non-defini
        if (in_array($payload['delai_type'], ['sans-delai', 'non-defini'])) {
            $payload['delai_valeur'] = null;
            $payload['delai_unite']  = null;
            $payload['mot_liaison']  = null;
            $payload['date_reference_id'] = null;
        }

        $id = $this->db()->table('pm_delais')->insertGetId($payload);

        // Organes
        $sort = 1;
        foreach ($validated['organes_codes'] as $oc) {
            $this->db()->table('pm_delai_organes')->insertOrIgnore([
                'delai_id'    => $id,
                'organe_code' => $oc,
                'sort'        => $sort++,
            ]);
        }

        // Modes de passation (pivot)
        $sort = 1;
        foreach ($modesCodes as $mc) {
            $this->db()->table('pm_delai_modes')->insertOrIgnore([
                'delai_id'            => $id,
                'mode_passation_code' => $mc,
                'sort'                => $sort++,
            ]);
        }

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateDelai(Request $request, int $id)
    {
        $validated = $request->validate([
            'operation_id'      => 'required|integer|exists:tenant.pm_operations,id',
            'delai_valeur'      => 'nullable|integer|min:0',
            'delai_unite'       => 'nullable|string|max:30',
            'delai_type'        => 'required|string|in:calendaire,ouvrable,sans-delai,non-defini',
            'mot_liaison'       => 'nullable|string|max:20',
            'date_reference_id' => 'nullable|integer|exists:tenant.pm_dates_reference,id',
            'phase_option'      => 'nullable|string|in:AMI,DAO,AMI+DAO',
            'note'              => 'nullable|string',
            'organes_codes'     => 'required|array|min:1',
            'organes_codes.*'   => 'required|string|max:20',
            'modes_codes'       => 'nullable|array',
            'modes_codes.*'     => 'string|max:20|exists:tenant.pm_modes_passation,code',
        ]);

        $payload = collect($validated)->except(['organes_codes', 'modes_codes'])->toArray();

        $modesCodes = $validated['modes_codes'] ?? [];
        $payload['condition_mode'] = $modesCodes[0] ?? null;

        if (in_array($payload['delai_type'], ['sans-delai', 'non-defini'])) {
            $payload['delai_valeur'] = null;
            $payload['delai_unite']  = null;
            $payload['mot_liaison']  = null;
            $payload['date_reference_id'] = null;
        }

        $this->db()->table('pm_delais')->where('id', $id)->update($payload);

        // Resynchroniser les organes : supprimer tous + réinsérer
        $this->db()->table('pm_delai_organes')->where('delai_id', $id)->delete();
        $sort = 1;
        foreach ($validated['organes_codes'] as $oc) {
            $this->db()->table('pm_delai_organes')->insertOrIgnore([
                'delai_id'    => $id,
                'organe_code' => $oc,
                'sort'        => $sort++,
            ]);
        }

        // Resynchroniser les modes de passation : supprimer tous + réinsérer
        $this->db()->table('pm_delai_modes')->where('delai_id', $id)->delete();
        $sort = 1;
        foreach ($modesCodes as $mc) {
            $this->db()->table('pm_delai_modes')->insertOrIgnore([
                'delai_id'            => $id,
                'mode_passation_code' => $mc,
                'sort'                => $sort++,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroyDelai(int $id)
    {
        // FK ON DELETE CASCADE supprime pm_delai_organes et pm_delai_modes
        $this->db()->table('pm_delais')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ORGANES D'UN DÉLAI (ajout/retrait depuis le tableau)
    // ═══════════════════════════════════════════════════════════════════

    public function storeDelaiOrgane(Request $request)
    {
        $data = $request->validate([
            'delai_id'    => 'required|integer|exists:tenant.pm_delais,id',
            'organe_code' => 'required|string|max:20',
        ]);
        $data['sort'] = $this->db()->table('pm_delai_organes')
            ->where('delai_id', $data['delai_id'])->max('sort') + 1;
        $this->db()->table('pm_delai_organes')->insertOrIgnore($data);
        return response()->json(['success' => true]);
    }

    public function destroyDelaiOrgane(Request $request)
    {
        $request->validate([
            'delai_id'    => 'required|integer',
            'organe_code' => 'required|string',
        ]);
        $this->db()->table('pm_delai_organes')
            ->where('delai_id',    $request->delai_id)
            ->where('organe_code', $request->organe_code)
            ->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  MODES DE PASSATION D'UN DÉLAI (ajout/retrait depuis le tableau,
    //  même logique que pour les organes)
    // ═══════════════════════════════════════════════════════════════════

    public function storeDelaiMode(Request $request)
    {
        $data = $request->validate([
            'delai_id'            => 'required|integer|exists:tenant.pm_delais,id',
            'mode_passation_code' => 'required|string|max:20|exists:tenant.pm_modes_passation,code',
        ]);
        $data['sort'] = $this->db()->table('pm_delai_modes')
            ->where('delai_id', $data['delai_id'])->max('sort') + 1;
        $this->db()->table('pm_delai_modes')->insertOrIgnore($data);
        return response()->json(['success' => true]);
    }

    public function destroyDelaiMode(Request $request)
    {
        $request->validate([
            'delai_id'            => 'required|integer',
            'mode_passation_code' => 'required|string',
        ]);
        $this->db()->table('pm_delai_modes')
            ->where('delai_id',            $request->delai_id)
            ->where('mode_passation_code', $request->mode_passation_code)
            ->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SEED — database/seeders/sql/pm_seed_reference_v2.sql
    // ═══════════════════════════════════════════════════════════════════

    public function seed()
    {
        $db = $this->db();

        $tables = [
            'pm_types_entites', 'pm_sources_financement', 'pm_natures_marche',
            'pm_modes_passation', 'pm_organes_controle', 'pm_seuils_generaux',
            'pm_operations', 'pm_dates_reference',
        ];

        $nonVides = collect($tables)->filter(fn($t) => $db->table($t)->count() > 0)->values();
        if ($nonVides->isNotEmpty()) {
            return response()->json([
                'success'          => false,
                'message'          => 'Tables non vides : ' . $nonVides->implode(', ') . ' — Utilisez Reset avant de re-seeder.',
                'tables_non_vides' => $nonVides,
            ], 422);
        }

        $sqlPath = database_path('seeders/sql/pm_seed_reference_v2.sql');
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
                fn($s) => strlen($s) > 10 && ! str_starts_with(ltrim($s), '--')
            );
            $db->unprepared('SET FOREIGN_KEY_CHECKS=0');
            foreach ($statements as $stmt) {
                $db->unprepared($stmt);
            }
            $db->unprepared('SET FOREIGN_KEY_CHECKS=1');

            $counts = array_combine($tables, array_map(fn($t) => $db->table($t)->count(), $tables));
            $counts['pm_mode_organes'] = $db->table('pm_mode_organes')->count();
            $counts['pm_delais']       = $db->table('pm_delais')->count();
            $counts['pm_seuils_ac']    = $db->table('pm_seuils_ac')->count();

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
        foreach ([
            'pm_delai_modes', 'pm_delai_organes', 'pm_delais',
            'pm_dates_reference', 'pm_operations',
            'pm_seuils_ac_organes', 'pm_seuils_ac',
            'pm_mode_organes', 'pm_seuils_generaux',
            'pm_organes_controle', 'pm_modes_passation',
            'pm_natures_marche', 'pm_sources_financement', 'pm_types_entites',
        ] as $t) {
            $db->table($t)->truncate();
        }
        $db->unprepared('SET FOREIGN_KEY_CHECKS=1');
        return response()->json(['success' => true, 'message' => 'Tables vidées. Relancez le seed.']);
    }
}