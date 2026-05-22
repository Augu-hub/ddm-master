<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Services\Audit\RapportAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * RapportAuditWordController
 * ══════════════════════════
 *
 * Trois endpoints :
 *
 *   GET  /auditor/missions/{missionId}/rapport/data
 *        → Retourne les données JSON brutes pour le composant Vue
 *          (aperçu, stats, constats, objectifs…)
 *
 *   POST /auditor/missions/{missionId}/rapport/generate
 *        → Génère le .docx côté serveur (Node.js + docx-js)
 *          avec les champs éditables injectés, retourne le fichier
 *
 *   PUT  /auditor/missions/{missionId}/rapport/edits
 *        → Sauvegarde les champs éditables en base (optionnel)
 *
 * Routes à ajouter dans routes/auditor.php :
 *   Route::get( 'missions/{missionId}/rapport/data',     [RapportAuditWordController::class, 'data']);
 *   Route::post('missions/{missionId}/rapport/generate', [RapportAuditWordController::class, 'generate']);
 *   Route::put( 'missions/{missionId}/rapport/edits',    [RapportAuditWordController::class, 'saveEdits']);
 */
class RapportAuditWordController extends Controller
{
    protected RapportAuditService $rapportService;

    public function __construct(RapportAuditService $rapportService)
    {
        $this->rapportService = $rapportService;
    }

    // ──────────────────────────────────────────────────────────────
    //  0. Page Inertia — affiche RapportWordModal directement ouvert
    // ──────────────────────────────────────────────────────────────

    public function index(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);

        $mission = DB::connection('tenant')
            ->table('mission_programmation')
            ->where('id', $missionId)
            ->first();

        if (!$mission) abort(404);

        return inertia('dashboards/Auditor/RapportAudit', [
            'missionId'      => $missionId,
            'missionCode'    => $mission->code_mission,
            'missionLibelle' => $mission->libelle,
            'backUrl'        => url()->previous(),
            'urlData'        => route('auditor.ac.rapport.word.data',     $missionId), // JSON
            'urlDownload'    => route('auditor.ac.rapport.word.generate', $missionId), // .docx binaire
            'urlSave'        => route('auditor.ac.rapport.word.edits',    $missionId), // sauvegarde
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  1. Données JSON pour le composant Vue (aperçu)
    // ──────────────────────────────────────────────────────────────

    public function data(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);

        $data = $this->rapportService->getDonneesRapport($missionId);

        // Charger les champs éditables sauvegardés (si table existante)
        $edits = $this->loadSavedEdits($missionId);

        return response()->json([
            'data'            => $data,
            'editable_fields' => $edits,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  2. Génération + téléchargement du .docx
    // ──────────────────────────────────────────────────────────────

    public function generate(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);

        $editableFields = $request->input('editable_fields', []);

        // Récupérer les données du rapport
        $data = $this->rapportService->getDonneesRapport($missionId);

        // Injecter les champs éditables dans les données
        $data = $this->mergeEditableFields($data, $editableFields);

        // Générer le .docx via Node.js
        $docxPath = $this->generateDocx($data, $missionId);

        // Retourner le fichier en stream
        $filename = "rapport_audit_mission_{$missionId}_" . date('Ymd') . ".docx";

        return response()->download($docxPath, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    // ──────────────────────────────────────────────────────────────
    //  3. Sauvegarde des champs éditables en base (optionnel)
    // ──────────────────────────────────────────────────────────────

    public function saveEdits(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);

        $fields = $request->validate([
            'editable_fields'              => 'required|array',
            'editable_fields.opinion'      => 'nullable|string|max:5000',
            'editable_fields.points_forts' => 'nullable|string|max:5000',
            'editable_fields.normes'       => 'nullable|string|max:5000',
            'editable_fields.limites'      => 'nullable|string|max:5000',
            'editable_fields.observations' => 'nullable|string|max:5000',
            'editable_fields.difficultes'  => 'nullable|string|max:5000',
        ]);

        DB::connection('tenant')
            ->table('rapport_audit_edits')
            ->updateOrInsert(
                ['mission_id' => $missionId],
                [
                    'editable_fields' => json_encode($fields['editable_fields']),
                    'updated_at'      => now(),
                ]
            );

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    //  Génération du .docx via script Node.js
    // ──────────────────────────────────────────────────────────────

    private function generateDocx(array $data, int $missionId): string
    {
        // Chemin du script Node.js (à placer dans resources/js/audit/ ou storage/app/scripts/)
        $scriptPath = base_path('resources/js/audit/generateRapportWord.cjs');

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("Script de génération introuvable : {$scriptPath}");
        }

        // Fichier temporaire pour les données JSON
        $tmpJson = tempnam(sys_get_temp_dir(), 'rapport_data_') . '.json';
        $tmpDocx = tempnam(sys_get_temp_dir(), 'rapport_') . '.docx';

        try {
            // Sérialiser les données pour Node.js
            file_put_contents($tmpJson, json_encode($data, JSON_UNESCAPED_UNICODE));

            // Exécuter le script Node.js
            $result = Process::run([
                'node',
                $scriptPath,
                '--input',  $tmpJson,
                '--output', $tmpDocx,
            ]);

            if (!$result->successful()) {
                throw new \RuntimeException(
                    "Erreur génération Word : " . $result->errorOutput()
                );
            }

            if (!file_exists($tmpDocx) || filesize($tmpDocx) === 0) {
                throw new \RuntimeException("Le fichier .docx généré est vide ou absent.");
            }

            return $tmpDocx;

        } finally {
            @unlink($tmpJson);
            // $tmpDocx sera supprimé après l'envoi (deleteFileAfterSend)
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Injecte les champs éditables dans le tableau de données du rapport.
     * Les données source (constats, équipe, etc.) restent intactes.
     */
    private function mergeEditableFields(array $data, array $editableFields): array
    {
        // Remplacer l'opinion par celle saisie par l'auditeur
        if (!empty($editableFields['opinion'])) {
            $data['opinion']['description'] = $editableFields['opinion'];
        }

        // Remplacer les points forts
        if (!empty($editableFields['points_forts'])) {
            $data['pointsForts'] = array_filter(
                explode("\n", $editableFields['points_forts']),
                fn($l) => trim($l, "•- \t") !== ''
            );
            $data['pointsForts'] = array_values(array_map(
                fn($l) => ltrim(trim($l), '•- '),
                $data['pointsForts']
            ));
        }

        // Ajouter les champs libres comme clé dédiée
        $data['editable'] = [
            'normes'       => $editableFields['normes']       ?? '',
            'limites'      => $editableFields['limites']      ?? '',
            'observations' => $editableFields['observations']  ?? '',
            'difficultes'  => $editableFields['difficultes']  ?? '',
        ];

        return $data;
    }

    /**
     * Charge les champs éditables précédemment sauvegardés.
     * Retourne un tableau vide si la table n'existe pas encore.
     */
    private function loadSavedEdits(int $missionId): array
    {
        try {
            $row = DB::connection('tenant')
                ->table('rapport_audit_edits')
                ->where('mission_id', $missionId)
                ->first();

            if ($row && $row->editable_fields) {
                return json_decode($row->editable_fields, true) ?? [];
            }
        } catch (\Exception $e) {
            // La table n'existe pas encore — on ignore silencieusement
        }

        return [];
    }

    /**
     * Vérifie que l'auditeur connecté a accès à la mission.
     */
    private function authorizeAccess(int $missionId): void
    {
        $user = auth()->user();
        if (!$user) abort(403, 'Non authentifié.');

        $auditor = DB::connection('tenant')
            ->table('auditors')
            ->where('user_id', $user->id)
            ->first();

        if (!$auditor) abort(403, 'Auditeur introuvable.');

        $hasAccess = DB::connection('tenant')
            ->table('mission_phase_assignments as mpa')
            ->join('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditor->id)
            ->exists();

        if (!$hasAccess) abort(403, 'Accès à cette mission non autorisé.');
    }
}