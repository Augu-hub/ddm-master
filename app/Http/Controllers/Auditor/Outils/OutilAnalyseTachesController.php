<?php

namespace App\Http\Controllers\Auditor\Outils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OutilAnalyseTachesController extends Controller
{
    use \App\Http\Controllers\Auditor\Outils\FicheTestContextTrait;

    private string $conn = 'tenant';

    private function findOrFail(int $id): object
    {
        $record = DB::connection($this->conn)->table('outil_analyse_taches')->where('id', $id)->first();
        abort_if(!$record, 404, 'Grille d\'analyse des tâches introuvable.');
        return $record;
    }

    public function index(Request $request)
    {
        $missionId = $request->query('mission_id');

        $query = DB::connection($this->conn)
            ->table('outil_analyse_taches as oat')
            ->leftJoin('missions as m', 'm.id', '=', 'oat.mission_id')
            ->leftJoin('users as u', 'u.id', '=', 'oat.created_by')
            ->select('oat.*', 'm.reference as mission_ref', 'm.libelle as mission_libelle',
                DB::raw("CONCAT(u.prenom, ' ', u.nom) as auteur"))
            ->orderByDesc('oat.created_at');

        if ($missionId) $query->where('oat.mission_id', $missionId);

        // Contexte FicheTest : si fiche_test_id présent, chercher outil existant
        $ftCtx = $this->ficheTestContext($request);
        if ($ftCtx['has_context']) {
            $existingId = $this->findExistingOutilId(
                $ftCtx['fiche_test_id'],
                'II',
                $ftCtx['test_ref'],
                $ftCtx['proc_idx']
            );
            if ($existingId) {
                return redirect()->route('auditor.ac.outil-analyse-taches.edit', [$existingId] + $request->query());
            }
        }

        return Inertia::render('Auditor/Outils/OutilAnalyseTaches', [
            'grilles'  => $query->paginate(15)->withQueryString(),
            'missions' => DB::connection($this->conn)->table('missions')->select('id','reference','libelle')->orderBy('reference')->get(),
            'filters'  => ['mission_id' => $missionId],
            'urlStore'      => route('auditor.ac.outil-analyse-taches.store') . ($ftCtx['query_string'] ?? ''),
            'urlUpdate'     => null,
            'backUrl'       => $ftCtx['back_url'] ?? '/',
            'missionContext'=> $ftCtx['missionContext'],
            ]);
    }

    public function edit(int $id)
    {
        $grille = $this->findOrFail($id);
        $taches = DB::connection($this->conn)->table('outil_analyse_taches_lignes')
            ->where('grille_id', $id)->orderBy('ordre')->get();

        return Inertia::render('Auditor/Outils/OutilAnalyseTaches', [
            'grille'   => $grille,
            'taches'   => $taches,
            'missions' => DB::connection($this->conn)->table('missions')->select('id','reference','libelle')->orderBy('reference')->get(),
            'urlStore'      => route('auditor.ac.outil-analyse-taches.store') . ($request->getQueryString() ? '?' . $request->getQueryString() : ''),
            'urlUpdate'     => isset($id) ? route('auditor.ac.outil-analyse-taches.update', $id) . ($request->getQueryString() ? '?' . $request->getQueryString() : '') : null,
            'backUrl'       => $request->query('back', '/'),
            'missionContext'=> $this->ficheTestContext($request)['missionContext'] ?? [],
            ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'mission_id'   => 'required|integer',
            'intitule'     => 'required|string|max:255',
            'processus'    => 'nullable|string|max:255',
            'responsable'  => 'nullable|string|max:255',
            'date_analyse' => 'nullable|date',
            'taches'       => 'nullable|array',
            'taches.*.libelle'          => 'required|string',
            'taches.*.acteur'           => 'nullable|string',
            'taches.*.frequence'        => 'nullable|string',
            'taches.*.risque_associe'   => 'nullable|string',
            'taches.*.controle'         => 'nullable|string',
            'taches.*.observation'      => 'nullable|string',
            'taches.*.ordre'            => 'nullable|integer',
        ]);

        $id = DB::connection($this->conn)->table('outil_analyse_taches')->insertGetId([
            'mission_id'   => $v['mission_id'],
            'intitule'     => $v['intitule'],
            'processus'    => $v['processus'] ?? null,
            'responsable'  => $v['responsable'] ?? null,
            'date_analyse' => $v['date_analyse'] ?? null,
            'statut'       => 'draft',
            'created_by'   => Auth::id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        foreach (($v['taches'] ?? []) as $idx => $t) {
            DB::connection($this->conn)->table('outil_analyse_taches_lignes')->insert([
                'grille_id'       => $id,
                'libelle'         => $t['libelle'],
                'acteur'          => $t['acteur'] ?? null,
                'frequence'       => $t['frequence'] ?? null,
                'risque_associe'  => $t['risque_associe'] ?? null,
                'controle'        => $t['controle'] ?? null,
                'observation'     => $t['observation'] ?? null,
                'ordre'           => $t['ordre'] ?? ($idx + 1),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Enregistrer le lien vers FicheTest si contexte présent
        $this->saveFicheTestLinkIfPresent($request, $id, 'II', 'outil_analyse_taches');

        if ($request->expectsJson()) {
            $rec = $this->db()->table($this->outilTable)->where('id', $id)->first();
            return response()->json(['success' => true, 'record' => $rec]);
        }

        // Si vient de FicheTest → retourner à FicheTest
        if ($request->has('back')) {
            return redirect($request->input('back'))->with('success', 'Créé.');
        }
        return redirect()->route('auditor.ac.outil-analyse-taches.edit', $id)
            ->with('success', 'Grille d\'analyse des tâches créée.');
    }

    public function update(Request $request, int $id)
    {
        $grille = $this->findOrFail($id);
        abort_if($grille->statut === 'validated', 403, 'Document validé, modification impossible.');

        $v = $request->validate([
            'intitule'     => 'required|string|max:255',
            'processus'    => 'nullable|string|max:255',
            'responsable'  => 'nullable|string|max:255',
            'date_analyse' => 'nullable|date',
            'taches'       => 'nullable|array',
            'taches.*.libelle'         => 'required|string',
            'taches.*.acteur'          => 'nullable|string',
            'taches.*.frequence'       => 'nullable|string',
            'taches.*.risque_associe'  => 'nullable|string',
            'taches.*.controle'        => 'nullable|string',
            'taches.*.observation'     => 'nullable|string',
            'taches.*.ordre'           => 'nullable|integer',
        ]);

        DB::connection($this->conn)->table('outil_analyse_taches')->where('id', $id)->update([
            'intitule'     => $v['intitule'],
            'processus'    => $v['processus'] ?? null,
            'responsable'  => $v['responsable'] ?? null,
            'date_analyse' => $v['date_analyse'] ?? null,
            'updated_at'   => now(),
        ]);

        DB::connection($this->conn)->table('outil_analyse_taches_lignes')->where('grille_id', $id)->delete();
        foreach (($v['taches'] ?? []) as $idx => $t) {
            DB::connection($this->conn)->table('outil_analyse_taches_lignes')->insert([
                'grille_id'      => $id,
                'libelle'        => $t['libelle'],
                'acteur'         => $t['acteur'] ?? null,
                'frequence'      => $t['frequence'] ?? null,
                'risque_associe' => $t['risque_associe'] ?? null,
                'controle'       => $t['controle'] ?? null,
                'observation'    => $t['observation'] ?? null,
                'ordre'          => $t['ordre'] ?? ($idx + 1),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        if ($request->expectsJson()) {
            $rec = $this->db()->table($this->outilTable)->where('id', $id)->first();
            return response()->json(['success' => true, 'record' => $rec]);
        }
        return back()->with('success', 'Grille d\'analyse des tâches mise à jour.');
    }

    public function soumettre(int $id)
    {
        $g = $this->findOrFail($id);
        abort_if($g->statut !== 'draft', 422, 'Seul un brouillon peut être soumis.');
        DB::connection($this->conn)->table('outil_analyse_taches')->where('id', $id)
            ->update(['statut' => 'in_review', 'updated_at' => now()]);
        return back()->with('success', 'Grille soumise pour validation.');
    }

    public function valider(Request $request, int $id)
    {
        $g = $this->findOrFail($id);
        abort_if($g->statut !== 'in_review', 422, 'Document non soumis.');
        $v = $request->validate(['decision' => 'required|in:validated,rejected', 'commentaire' => 'nullable|string']);
        DB::connection($this->conn)->table('outil_analyse_taches')->where('id', $id)->update([
            'statut'                 => $v['decision'],
            'commentaire_validation' => $v['commentaire'] ?? null,
            'validated_by'           => Auth::id(),
            'validated_at'           => now(),
            'updated_at'             => now(),
        ]);
        return back()->with('success', $v['decision'] === 'validated' ? 'Grille validée.' : 'Grille rejetée.');
    }


    // ═══════════════════════════════════════════════════════════
    // IA — Analyse via Claude API
    // ═══════════════════════════════════════════════════════════
    public function ia(int $id): \Illuminate\Http\JsonResponse
    {
        $record = $this->findOrFail($id);
        $payload = (array) $record;
        $prompt  = "Analyse ce document d'audit interne IFACI (outil).\n\n"
                 . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                 . "\n\nFournis une analyse JSON UNIQUEMENT (sans markdown) avec: synthese (string), points_forts (array), points_faibles (array), risques (array), recommandations (array), score (number 0-10).";

        try {
            $r = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 1500,
                'system'     => 'Tu es expert audit interne IFACI. Retourne UNIQUEMENT un JSON valide (pas de markdown) avec ces clés: synthese, points_forts, points_faibles, risques, recommandations, score.',
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ])->json();

            $text = collect($r['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '{}';
            $text = trim(preg_replace('/^```json\s*|\s*```$/m', '', $text));
            $data = json_decode($text, true) ?? [];

            DB::connection($this->conn)->table('outil_analyse_taches')->where('id', $id)->update([
                'ia_result'  => json_encode($data, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'ia_result' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id)
    {
        $this->findOrFail($id);
        DB::connection($this->conn)->table('outil_analyse_taches_lignes')->where('grille_id', $id)->delete();
        DB::connection($this->conn)->table('outil_analyse_taches')->where('id', $id)->delete();
        return redirect()->route('auditor.ac.outil-analyse-taches.index')->with('success', 'Grille supprimée.');
    }
}