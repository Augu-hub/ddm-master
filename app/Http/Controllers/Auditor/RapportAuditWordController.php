<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Services\Audit\RapportAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class RapportAuditWordController extends Controller
{
    public function __construct(protected RapportAuditService $rapportService) {}

    // ══════════════════════════════════════════════════════════════
    //  0 · Page Inertia
    // ══════════════════════════════════════════════════════════════

    public function index(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);
        $mission = DB::connection('tenant')->table('mission_programmation')->where('id', $missionId)->first();
        abort_if(!$mission, 404);
        return inertia('dashboards/Auditor/RapportAudit', [
            'missionId'      => $missionId,
            'missionCode'    => $mission->code_mission,
            'missionLibelle' => $mission->libelle,
            'backUrl'        => url()->previous(),
            'urlData'        => route('auditor.ac.rapport.word.data',     $missionId),
            'urlDownload'    => route('auditor.ac.rapport.word.generate', $missionId),
            'urlHtml'        => route('auditor.ac.rapport.word.html',     $missionId),
            'urlSave'        => route('auditor.ac.rapport.word.edits',    $missionId),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  1 · Données JSON
    // ══════════════════════════════════════════════════════════════

    public function data(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);
        $data  = $this->rapportService->getDonneesRapport($missionId);
        $edits = $this->loadSavedEdits($missionId);
        return response()->json(['data' => $data, 'editable_fields' => $edits]);
    }

    // ══════════════════════════════════════════════════════════════
    //  2 · Génération .docx
    // ══════════════════════════════════════════════════════════════

    public function generate(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);
        $editableFields = $request->input('editable_fields', []);
        $data = $this->mergeEditableFields($this->rapportService->getDonneesRapport($missionId), $editableFields);
        try {
            $docxPath = $this->generateDocx($data, $missionId);
            $filename = 'rapport_audit_mission_' . $missionId . '_' . date('Ymd') . '.docx';
            return response()->download($docxPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  3 · Export HTML
    // ══════════════════════════════════════════════════════════════

    public function exportHtml(Request $request, int $missionId)
    {
        $this->authorizeAccess($missionId);
        $editableFields = $request->input('editable_fields', []);
        $data = $this->mergeEditableFields($this->rapportService->getDonneesRapport($missionId), $editableFields);
        $html     = $this->buildHtml($data, $missionId);
        $filename = 'rapport_audit_mission_' . $missionId . '_' . date('Ymd') . '.html';
        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  4 · Sauvegarde champs éditables
    // ══════════════════════════════════════════════════════════════

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
        DB::connection('tenant')->table('rapport_audit_edits')->updateOrInsert(
            ['mission_id' => $missionId],
            ['editable_fields' => json_encode($fields['editable_fields']), 'updated_at' => now()]
        );
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVÉ · Génération .docx via Node.js
    // ══════════════════════════════════════════════════════════════

    private function generateDocx(array $data, int $missionId): string
    {
        $scriptPath = base_path('resources/js/audit/generateRapportWord.cjs');
        if (!file_exists($scriptPath)) throw new \RuntimeException('Script introuvable : ' . $scriptPath);
        $tmpJson = tempnam(sys_get_temp_dir(), 'rapport_data_') . '.json';
        $tmpDocx = tempnam(sys_get_temp_dir(), 'rapport_') . '.docx';
        try {
            file_put_contents($tmpJson, json_encode($data, JSON_UNESCAPED_UNICODE));
            $result = Process::env([
                'NODE_PATH'        => base_path('node_modules'),
                'DOCX_MODULE_PATH' => env('DOCX_MODULE_PATH', base_path('node_modules/docx')),
            ])->run(['node', $scriptPath, '--input', $tmpJson, '--output', $tmpDocx]);
            if (!$result->successful()) throw new \RuntimeException($result->errorOutput() ?: $result->output());
            if (!file_exists($tmpDocx) || filesize($tmpDocx) === 0) throw new \RuntimeException('Fichier .docx vide ou absent.');
            return $tmpDocx;
        } finally {
            @unlink($tmpJson);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVÉ · Construction HTML — format rapport officiel A4
    // ══════════════════════════════════════════════════════════════

    private function buildHtml(array $data, int $missionId): string
    {
        $data = json_decode(json_encode($data), true);

        $mission   = $data['mission']          ?? [];
        $editable  = $data['editable']         ?? [];
        $constats  = $data['constats']         ?? [];
        $objectifs = $data['tableauObjectifs'] ?? [];
        $stats     = $data['statsConstats']    ?? [];
        $opinion   = $data['opinion']          ?? [];
        $equipe    = $data['equipe']           ?? [];

        $fmt   = static fn($d) => $d ? date('d/m/Y', strtotime((string)$d)) : '—';
        $esc   = static fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $nl    = static fn($s) => nl2br(htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $empty = static fn($s) => trim((string)($s ?? '')) === '';
        $year  = date('Y');
        $today = $fmt(date('Y-m-d'));

        // ── Métadonnées ───────────────────────────────────────────
        $libelle    = $esc($mission['libelle']    ?? '—');
        $numFpm     = $esc($mission['numero_fpm'] ?? '—');
        $lieux      = $esc($mission['lieux']      ?? '—');
        $datesDeb   = $fmt($mission['date_debut'] ?? null);
        $datesFin   = $fmt($mission['date_fin']   ?? null);
        $rapportNum = 'RAP-' . $missionId . '-' . $year;

        // ── Opinion ───────────────────────────────────────────────
        $opinionNiveau = $esc($opinion['niveau'] ?? 'Peu Significatif');
        $opinionTxt    = !$empty($editable['opinion'] ?? '')
            ? $nl($editable['opinion'])
            : $nl($opinion['description'] ?? '');

        // ── Champs libres ─────────────────────────────────────────
        $pfTxt = !$empty($editable['points_forts'] ?? '')
            ? $nl($editable['points_forts'])
            : implode('', array_map(fn($l) => '<div>• ' . $esc($l) . '</div>', $data['pointsForts'] ?? []));
        if ($pfTxt === '') $pfTxt = '<em>[Zone de saisie — points positifs observés.]</em>';

        $normesTxt  = !$empty($editable['normes']  ?? '')
            ? $nl($editable['normes'])
            : "L'audit a été conduit conformément aux Normes Internationales pour la Pratique Professionnelle de l'Audit Interne (IIA). Ces normes requièrent que l'audit soit planifié et exécuté de façon à fournir une assurance raisonnable. Un audit comprend également l'évaluation des contrôles internes applicables et la vérification de la conformité aux lois et réglementations.";
        $limitesTxt      = !$empty($editable['limites']      ?? '') ? $nl($editable['limites'])      : '<em>[Limites de l\'audit — modifiable.]</em>';
        $observationsTxt = !$empty($editable['observations'] ?? '') ? $nl($editable['observations']) : '<em>[Observations générales de la structure auditée — zone de saisie.]</em>';
        $difficulteesTxt = !$empty($editable['difficultes']  ?? '') ? $nl($editable['difficultes'])  : '<em>[Difficultés rencontrées au cours de la mission — zone de saisie.]</em>';

        // ── Stats ─────────────────────────────────────────────────
        $sCrit  = (int)($stats['critique']         ?? 0);
        $sSig   = (int)($stats['significatif']     ?? 0);
        $sPeu   = (int)($stats['peu_significatif'] ?? 0);
        $sTotal = (int)($stats['total']            ?? 0);

        // ── Helpers badge / li class ──────────────────────────────
        $impMap = [
            'critique' => ['Critique',     'b-crit',  'fc'],
            'haute'    => ['Significatif', 'b-sig',   'fs'],
            'moyenne'  => ['Peu sig.',     'b-less',  'fl2'],
            'basse'    => ['Maintenance',  'b-house', 'fh'],
        ];
        $badge = static function (string $imp) use ($impMap): string {
            [$lbl, $cls] = $impMap[$imp] ?? $impMap['basse'];
            return '<span class="b ' . $cls . '">' . $lbl . '</span>';
        };

        // ── Catégorisation des constats par type_frap ─────────────
        // type_frap attendu : QCI | QCC | FCC | FTP | GART
        $byType = ['QCI' => [], 'QCC' => [], 'FCC' => [], 'FTP' => [], 'GART' => []];
        foreach ($constats as $c) {
            $t = strtoupper(trim((string)($c['type_frap'] ?? 'QCI')));
            if (!array_key_exists($t, $byType)) $t = 'QCI';
            $byType[$t][] = $c;
        }

        // ── 1.2 Résumé des constats : 5 sous-sections ────────────
        $sections12 = [
            'QCI'  => ['1.2.1', 'Principaux points de fragilité du dispositif de contrôle interne'],
            'QCC'  => ['1.2.2', 'Principaux points de non-conformité aux normes'],
            'FTP'  => ['1.2.3', 'Principaux points affectant la validité des procédures'],
            'FCC'  => ['1.2.4', "Principaux points d'irrégularités sur les transactions financières"],
            'GART' => ['1.2.5', 'Principaux points de faiblesse affectant la séparation des tâches'],
        ];
        $html12 = '';
        foreach ($sections12 as $typeKey => [$num, $titre]) {
            $rows = $byType[$typeKey] ?? [];
            if (empty($rows)) continue;
            $html12 .= '<div style="font-size:8.5pt;font-weight:bold;color:#333;margin-bottom:1.5mm">'
                . $num . ' — ' . $esc($titre) . '</div>';
            $html12 .= '<ul class="fl">';
            foreach ($rows as $c) {
                $imp   = (string)($c['importance'] ?? 'basse');
                [, $cls, $liCls] = $impMap[$imp] ?? $impMap['basse'];
                $prob  = $esc($c['probleme'] ?? mb_substr((string)($c['fait_constats'] ?? ''), 0, 200));
                $frap  = $esc($c['num_frap'] ?? '');
                $lbl   = $impMap[$imp][0] ?? 'Maintenance';
                $html12 .= '<li class="' . $liCls . '">'
                    . '<span style="flex:1"><strong>' . $frap . '</strong> ' . $prob . '</span>'
                    . '<span class="b ' . $cls . '">' . $lbl . '</span>'
                    . '</li>';
            }
            $html12 .= '</ul>';
        }
        if ($html12 === '') $html12 = '<div class="txt-box"><em>Aucun constat enregistré.</em></div>';

        // ── 1.3 Plan d'actions : 5 sous-tableaux ─────────────────
        $sections13 = [
            'QCI'  => ['1.3.1', "Amélioration du dispositif de contrôle interne (FRAP-QCI)", 'Point de faiblesse (faits / constats)'],
            'QCC'  => ['1.3.2', "Mise aux normes de conformité (FRAP-QCC)",                   'Point de non-conformité'],
            'FCC'  => ['1.3.3', "Régularisation des transactions financières (FRAP-FCC)",      'Irrégularité constatée'],
            'FTP'  => ['1.3.4', "Amélioration des procédures (FRAP-FTP)",                      'Faiblesse des procédures'],
            'GART' => ['1.3.5', "Renforcement de la séparation des tâches (FRAP-GART)",        'Faiblesse séparation des tâches'],
        ];
        $html13 = '';
        foreach ($sections13 as $typeKey => [$num, $titre, $col1]) {
            $rows = $byType[$typeKey] ?? [];
            $html13 .= '<div style="font-size:8.5pt;font-weight:bold;color:#1a3a5c;margin-bottom:1.5mm">'
                . $num . ' — ' . $esc($titre) . '</div>';
            $html13 .= '<table class="at"><thead><tr>'
                . '<th>#</th><th>' . $esc($col1) . '</th><th>Mesures recommandées</th>'
                . '<th>Responsable</th><th>Échéance</th><th>Priorité</th>'
                . '</tr></thead><tbody>';
            if (empty($rows)) {
                $html13 .= '<tr><td colspan="6" style="color:#999;font-style:italic;padding:4px 5px">Aucune action enregistrée.</td></tr>';
            } else {
                foreach ($rows as $i => $c) {
                    $imp  = (string)($c['importance'] ?? 'basse');
                    $prob = $esc($c['probleme'] ?? mb_substr((string)($c['fait_constats'] ?? ''), 0, 120));
                    $reco = $esc($c['recommandation'] ?? '');
                    $resp = $esc($c['responsable'] ?? $c['entite_responsable'] ?? '—');
                    $ech  = isset($c['echeance']) ? $fmt((string)$c['echeance']) : '—';
                    $html13 .= '<tr><td>' . ($i + 1) . '</td><td>' . $prob . '</td><td>' . $reco . '</td>'
                        . '<td>' . $resp . '</td><td>' . $ech . '</td><td>' . $badge($imp) . '</td></tr>';
                }
            }
            $html13 .= '</tbody></table>';
        }

        // ── Section 2 : fiches FRAP par type ─────────────────────
        $sections2 = [
            'QCI'  => ['2.1', 'Constats de Contrôle Interne (FRAP-QCI)'],
            'QCC'  => ['2.2', 'Constats de Non-Conformité (FRAP-QCC)'],
            'FCC'  => ['2.3', 'Constats sur les Opérations Financières (FRAP-FCC)'],
            'FTP'  => ['2.4', 'Constats de Faiblesse des Procédures (FRAP-FTP)'],
            'GART' => ['2.5', 'Constats de Séparation des Tâches (FRAP-GART)'],
        ];

        $buildFrap = function (array $c) use ($esc, $fmt, $impMap, $badge): string {
            $imp      = (string)($c['importance'] ?? 'basse');
            [$lbl, $cls] = $impMap[$imp] ?? $impMap['basse'];
            $frapId   = $esc($c['num_frap']          ?? '');
            $titre    = $esc($c['probleme']           ?? mb_substr((string)($c['fait_constats'] ?? ''), 0, 200));
            $ctrl     = $esc($c['controle_existant']  ?? '');
            $crits    = $esc($c['criteres']           ?? $c['referentiel'] ?? '');
            $faits    = $esc($c['fait_constats']      ?? $c['faits']       ?? '');
            $probleme = $esc($c['probleme']           ?? '');
            $cause    = $esc($c['cause']              ?? '');
            $impact   = $esc($c['impact']             ?? '');
            $reco     = $esc($c['recommandation']     ?? '');
            $ptsForts = $esc($c['points_forts']       ?? '');
            $actions  = $esc($c['actions_convenues']  ?? $c['statut_action'] ?? '');
            $resp     = $esc($c['responsable']        ?? $c['entite_responsable'] ?? '—');
            $ech      = isset($c['echeance']) ? $fmt((string)$c['echeance']) : '—';
            $livrable = $esc($c['livrable']           ?? '');

            $ctrlBadge = $ctrl !== '' ? '<span class="b b-ineff">' . $ctrl . '</span>' : '';

            $out  = '<div class="frap">';
            $out .= '<div class="frap-hd">';
            $out .= '<span class="frap-id">' . $frapId . '</span>';
            $out .= '<span class="frap-ttl">' . $titre . '</span>';
            $out .= '<span class="b ' . $cls . '">' . $lbl . '</span>';
            $out .= $ctrlBadge;
            $out .= '</div>';
            $out .= '<div class="frap-body">';
            if ($crits    !== '') $out .= '<div class="ff full"><label>Critères (référentiel)</label><p>' . $crits    . '</p></div>';
            if ($faits    !== '') $out .= '<div class="ff"><label>Faits (situation constatée)</label><p>' . $faits    . '</p></div>';
            if ($probleme !== '') $out .= '<div class="ff"><label>Problème identifié</label><p>'          . $probleme . '</p></div>';
            if ($cause    !== '') $out .= '<div class="ff"><label>Cause(s) profonde(s)</label><p>'        . $cause    . '</p></div>';
            if ($impact   !== '') $out .= '<div class="ff"><label>Impact(s) potentiel(s)</label><p>'      . $impact   . '</p></div>';
            if ($reco     !== '') $out .= '<div class="ff full"><label>Recommandation</label><p>'         . $reco     . '</p></div>';
            if ($ptsForts !== '') $out .= '<div class="ff full"><label>Points forts observés</label><p>'  . $ptsForts . '</p></div>';
            $out .= '</div>'; // frap-body
            $out .= '<div class="frap-ft">';
            $out .= '<span class="ffl">Actions convenues :</span>';
            $out .= '<span class="fft">' . ($actions !== '' ? $actions : '<em style="color:#bbb">Non renseignées.</em>') . '</span>';
            $out .= '<span class="b b-less">Responsable : ' . $resp . '</span>';
            $out .= '<span class="b b-less">Échéance : ' . $ech . '</span>';
            if ($livrable !== '') $out .= '<span class="b b-less">Livrable : ' . $livrable . '</span>';
            $out .= '</div>';
            $out .= '</div>'; // frap
            return $out;
        };

        // Section 2 HTML — groupé par objectif pour QCI, puis sections 2.2-2.5
        $htmlSec2 = '';
        // 2.1 QCI groupé par objectif
        $qciRows = $byType['QCI'] ?? [];
        $htmlSec2 .= '<div class="sub-t">2.1 — Constats de Contrôle Interne (FRAP-QCI)</div>';
        if (empty($qciRows)) {
            $htmlSec2 .= '<div class="ph">[Aucun constat de contrôle interne enregistré]</div>';
        } else {
            // Grouper par objectif
            $qciByObj = [];
            foreach ($qciRows as $c) {
                $objNum = (string)($c['obj_num'] ?? '__');
                $qciByObj[$objNum][] = $c;
            }
            // Trouver libellé objectif
            $objLibMap = [];
            foreach ($objectifs as $obj) {
                $objLibMap[(string)($obj['num'] ?? '')] = $esc($obj['objectif'] ?? '');
            }
            foreach ($qciByObj as $objNum => $rows) {
                $libObj = $objLibMap[$objNum] ?? $objNum;
                $htmlSec2 .= '<div class="rubrique">&#9658; Rubrique : ' . $libObj . '</div>';
                foreach ($rows as $c) {
                    $htmlSec2 .= $buildFrap($c);
                }
            }
        }
        // 2.2 à 2.5
        foreach (['QCC', 'FCC', 'FTP', 'GART'] as $typeKey) {
            [$num, $titre] = $sections2[$typeKey];
            $rows = $byType[$typeKey] ?? [];
            $htmlSec2 .= '<div class="sub-t">' . $num . ' — ' . $esc($titre) . '</div>';
            if (empty($rows)) {
                $htmlSec2 .= '<div class="ph">[Aucun constat enregistré pour cette catégorie]</div>';
            } else {
                foreach ($rows as $c) {
                    $htmlSec2 .= $buildFrap($c);
                }
            }
        }

        // ── Annexe 1 : objectifs ──────────────────────────────────
        $annexeObjRows = '';
        foreach ($objectifs as $idx => $obj) {
            $annexeObjRows .= '<tr>'
                . '<td rowspan="1">' . $esc($obj['objectif_groupe'] ?? $esc($obj['objectif'] ?? '')) . '</td>'
                . '<td>' . $esc($obj['risque_cle'] ?? '—') . '</td>'
                . '<td>' . $esc($obj['processus']  ?? '—') . '</td>'
                . '<td>' . $esc($obj['sous_processus'] ?? '—') . '</td>'
                . '<td>' . $esc($obj['controles_cles'] ?? '—') . '</td>'
                . '</tr>';
        }
        if ($annexeObjRows === '') {
            $annexeObjRows = '<tr><td colspan="5" style="color:#999;font-style:italic;padding:4px 6px">Aucun objectif enregistré.</td></tr>';
        }

        // ── Annexe 4 : destinataires / équipe ─────────────────────
        $destRows = '';
        foreach ($equipe as $m) {
            $nom    = $esc($m['nom']    ?? '');
            $prenom = $esc($m['prenom'] ?? '');
            $fonct  = $esc($m['role']   ?? $m['fonction'] ?? '');
            $destRows .= '<tr><td>' . $nom . '</td><td>' . $prenom . '</td><td>' . $fonct . '</td></tr>';
        }
        if ($destRows === '') {
            $destRows = '<tr><td colspan="3" style="color:#999;font-style:italic;padding:4px 6px">Aucun destinataire enregistré.</td></tr>';
        }

        // ── Badge opinion sélectionné ─────────────────────────────
        $opinionsMap = [
            'Critique'         => 'b-crit',
            'Significatif'     => 'b-sig',
            'Peu Significatif' => 'b-less',
            'Peu significatif' => 'b-less',
            'Maintenance'      => 'b-house',
        ];
        $selCls = $opinionsMap[$opinionNiveau] ?? 'b-less';

        // ── Signataires (2 premiers membres de l'équipe) ──────────
        $sigHtml = '';
        $sigCount = 0;
        foreach (array_slice($equipe, 0, 2) as $m) {
            $nom = trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? ''));
            $sigHtml .= '<span class="mv">' . $esc($nom) . '</span>';
            $sigHtml .= '<span style="font-size:7.5pt;color:#777">Visa : ____________________</span>';
            $sigCount++;
        }
        if ($sigCount === 0) {
            $sigHtml = '<span class="mv">[Chef de mission]</span>'
                     . '<span style="font-size:7.5pt;color:#777">Visa : ____________________</span>'
                     . '<span class="mv">[Directeur de l\'Audit]</span>'
                     . '<span style="font-size:7.5pt;color:#777">Visa : ____________________</span>';
        }

        // ══════════════════════════════════════════════════════════
        //  CSS — copie exacte du fichier de référence
        // ══════════════════════════════════════════════════════════
        $css = <<<'CSS'
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;font-size:9.5pt;color:#1a1a1a;background:#e8e8e8}
.toolbar{background:#fff;padding:8px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #ccc;position:sticky;top:0;z-index:100}
.toolbar h3{font-size:11pt;font-weight:bold;color:#1a3a5c;flex:1}
.btn-print{background:#1a3a5c;color:#fff;border:none;padding:7px 18px;border-radius:4px;font-size:9pt;cursor:pointer;font-weight:bold;display:flex;align-items:center;gap:5px}
.btn-print:hover{background:#0e2540}
.page{width:210mm;min-height:297mm;background:#fff;margin:6mm auto;padding:18mm 16mm 18mm 20mm;page-break-after:always;position:relative;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.page-footer{position:absolute;bottom:10mm;left:20mm;right:16mm;font-size:7.5pt;color:#aaa;border-top:0.5pt solid #e0e0e0;padding-top:2mm;display:flex;justify-content:space-between}
.cover-frame{display:flex;border:1.5pt solid #333;margin-bottom:6mm}
.cover-logo{width:42mm;background:#f5f5f5;border-right:1pt solid #bbb;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:5mm;min-height:20mm}
.logo-ph{font-size:7.5pt;color:#999;border:0.5pt dashed #bbb;padding:4px 8px;text-align:center}
.cover-mid{flex:1;padding:4mm 6mm}
.cover-mid .dept{font-size:7.5pt;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2mm}
.cover-mid h1{font-size:13pt;font-weight:bold;color:#1a3a5c}
.cover-right{width:42mm;border-left:1pt solid #bbb}
.cr-row{padding:3mm 4mm;border-bottom:.5pt solid #ddd}
.cr-row:last-child{border-bottom:none}
.cr-row .cl{font-size:7pt;color:#888;display:block;margin-bottom:1px}
.cr-row .cv{font-size:8.5pt;font-weight:bold}
.mission-box{border:.5pt solid #ccc;border-radius:2px;padding:4mm 5mm;margin-bottom:4mm;background:#fafafa}
.mission-box .ml{font-size:7pt;color:#888;text-transform:uppercase;display:block;margin-bottom:2px}
.mission-box .mv{font-size:10.5pt;font-weight:bold;color:#1a3a5c}
.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:3mm;margin-bottom:4mm}
.mc{border:.5pt solid #ccc;border-radius:2px;padding:2.5mm 3.5mm}
.mc .ml{font-size:7pt;color:#888;text-transform:uppercase;display:block;margin-bottom:1.5px}
.mc .mv{font-size:8.5pt}
.conf-box{background:#fff3ee;border:.5pt solid #e8a870;border-radius:2px;padding:2.5mm 4mm;font-size:8pt;color:#7a2800}
.toc-title{font-size:10.5pt;font-weight:bold;text-transform:uppercase;letter-spacing:.4px;border-bottom:1.5pt solid #1a3a5c;padding-bottom:2mm;margin-bottom:4mm;color:#1a3a5c}
.toc-t{width:100%;border-collapse:collapse;font-size:8.5pt}
.toc-t tr{border-bottom:.5pt solid #eee}
.toc-t td{padding:2.5px 5px;vertical-align:top}
.toc-t td:first-child{width:16mm;font-weight:bold;color:#555}
.toc-t td:last-child{width:12mm;text-align:right;color:#888}
.toc-sec td{background:#e8eef5;font-weight:bold;padding:3.5px 5px}
.sec-hd{background:#1a3a5c;color:#fff;padding:3mm 5mm;border-radius:2px;margin-bottom:4mm}
.sec-hd h2{font-size:10.5pt;color:#fff;font-weight:bold}
.sub-t{font-size:8.5pt;font-weight:bold;color:#1a3a5c;text-transform:uppercase;letter-spacing:.3px;border-bottom:1pt solid #1a3a5c;padding-bottom:1.5mm;margin-top:5mm;margin-bottom:3mm}
.txt-box{border:.5pt solid #ccc;border-radius:2px;padding:3mm 4mm;background:#fafafa;font-size:8.5pt;line-height:1.5;min-height:10mm;color:#666;font-style:italic;margin-bottom:3mm}
.b{display:inline-block;padding:1.5px 6px;border-radius:2px;font-size:7.5pt;font-weight:bold}
.b-crit{background:#fce8e8;color:#8b1a1a;border:.5pt solid #e07070}
.b-sig{background:#fef3e2;color:#7a4a0a;border:.5pt solid #e8b870}
.b-less{background:#e6f4ee;color:#0f5a3a;border:.5pt solid #60b890}
.b-house{background:#e8f0fb;color:#1a4a8a;border:.5pt solid #7090d8}
.b-ineff{background:#fce8e8;color:#8b1a1a}
.b-room{background:#fef3e2;color:#7a4a0a}
.b-inad{background:#fce8e8;color:#8b1a1a}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:3mm;margin-bottom:4mm}
.sc{border:.5pt solid #ccc;border-radius:2px;padding:2.5mm;text-align:center}
.sc .sn{font-size:16pt;font-weight:bold}
.sc .sl{font-size:7.5pt;color:#777}
.sc1 .sn{color:#8b1a1a} .sc2 .sn{color:#7a4a0a} .sc3 .sn{color:#0f5a3a} .sc4 .sn{color:#1a4a8a}
.fl{list-style:none;margin-bottom:3mm}
.fl li{padding:2.5mm 3.5mm;margin-bottom:1.5mm;border-left:2.5pt solid #ccc;background:#fafafa;font-size:8.5pt;display:flex;justify-content:space-between;align-items:flex-start;gap:3mm}
.fl li.fc{border-left-color:#c0392b} .fl li.fs{border-left-color:#e67e22} .fl li.fl2{border-left-color:#27ae60} .fl li.fh{border-left-color:#2980b9}
.at{width:100%;border-collapse:collapse;font-size:8pt;margin-bottom:4mm}
.at th{background:#1a3a5c;color:#fff;padding:3px 5px;font-size:7.5pt;text-align:left;border:.5pt solid #999}
.at td{padding:3.5px 5px;border:.5pt solid #ccc;vertical-align:top}
.at tr:nth-child(even) td{background:#f5f7fa}
.at td:first-child{width:6mm;text-align:center;font-weight:bold;color:#555}
.frap{border:1pt solid #ccc;border-radius:2px;margin-bottom:5mm;page-break-inside:avoid}
.frap-hd{background:#e8eef5;padding:2.5mm 3.5mm;border-bottom:.5pt solid #ccc;display:flex;align-items:center;gap:3mm;flex-wrap:wrap}
.frap-id{font-family:Courier,monospace;font-size:7.5pt;font-weight:bold;color:#1a3a5c;background:#fff;padding:1px 4px;border:.5pt solid #aac;border-radius:1px;white-space:nowrap}
.frap-ttl{flex:1;font-size:8.5pt;font-weight:bold;color:#1a1a1a}
.frap-body{display:grid;grid-template-columns:1fr 1fr}
.ff{padding:2.5mm 3.5mm;border-right:.5pt solid #e0e0e0;border-bottom:.5pt solid #e0e0e0}
.ff.full{grid-column:1/-1;border-right:none}
.ff label{font-size:7pt;font-weight:bold;color:#1a3a5c;text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:1.5px}
.ff p{font-size:8pt;line-height:1.5;color:#555;font-style:italic}
.frap-ft{background:#f5f7fa;padding:2.5mm 3.5mm;border-top:.5pt solid #ccc;font-size:8pt;display:flex;gap:4mm;align-items:flex-start;flex-wrap:wrap}
.frap-ft .ffl{font-weight:bold;color:#1a3a5c;white-space:nowrap;font-size:7.5pt}
.frap-ft .fft{flex:1;color:#555;font-style:italic}
.rg{display:grid;grid-template-columns:1fr 1fr;gap:3mm;margin-bottom:4mm}
.rc{border:.5pt solid #ccc;border-radius:2px;overflow:hidden}
.rc .rh{padding:2.5mm 3.5mm;font-size:8.5pt;font-weight:bold}
.rc .rb{padding:2.5mm 3.5mm;font-size:8pt;line-height:1.5;color:#444}
.rc-c .rh{background:#fce8e8;color:#8b1a1a} .rc-s .rh{background:#fef3e2;color:#7a4a0a}
.rc-l .rh{background:#e6f4ee;color:#0f5a3a} .rc-h .rh{background:#e8f0fb;color:#1a4a8a}
.ant{width:100%;border-collapse:collapse;font-size:8pt;margin-bottom:4mm}
.ant th{background:#1a3a5c;color:#fff;padding:3px 6px;border:.5pt solid #999;font-size:7.5pt;text-align:left}
.ant td{padding:3.5px 6px;border:.5pt solid #ccc;vertical-align:top}
.ant tr:nth-child(even) td{background:#f5f7fa}
.ant .gr td{background:#e8eef5;font-weight:bold;color:#1a3a5c}
.fiche{border:1pt solid #ccc;border-radius:2px;margin-bottom:5mm;page-break-inside:avoid}
.fiche-hd{padding:2.5mm 3.5mm;display:flex;justify-content:space-between;align-items:center}
.fiche-hd h4{font-size:9pt;color:#fff;font-weight:bold}
.fiche-sec{padding:3mm 4mm;border-bottom:.5pt solid #e0e0e0}
.fiche-sec:last-child{border-bottom:none}
.fiche-sec h5{font-size:7.5pt;font-weight:bold;color:#1a3a5c;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2.5mm}
.fiche-field{background:#fafafa;border:.5pt solid #ddd;border-radius:2px;padding:2.5mm 3.5mm;font-size:8pt;color:#777;font-style:italic;min-height:10mm;margin-bottom:2.5mm}
.sig-row{display:flex;gap:3mm}
.sig-box{flex:1;border:.5pt solid #ccc;border-radius:2px;padding:2mm 3mm}
.sig-box .sl{font-size:7pt;color:#888;display:block;margin-bottom:2mm}
.sig-box .sli{border-bottom:.5pt solid #aaa;height:6mm}
.sig-box .sm{display:flex;justify-content:space-between;font-size:6.5pt;color:#bbb;margin-top:1mm}
.cb-row{display:flex;gap:5mm;font-size:8pt;margin-bottom:2.5mm;flex-wrap:wrap}
.cbi::before{content:"☐ "}
.ph{border:.5pt dashed #bbb;border-radius:2px;padding:3mm;text-align:center;font-size:8pt;color:#bbb;font-style:italic;margin-bottom:4mm}
.rubrique{background:#e8eef5;border-left:3pt solid #1a3a5c;padding:2mm 4mm;font-size:8.5pt;font-weight:bold;color:#1a3a5c;margin-bottom:3mm;margin-top:3mm}
@media print{
  body{background:#fff}
  .toolbar{display:none}
  .page{margin:0;padding:18mm 16mm 18mm 20mm;box-shadow:none;page-break-after:always}
}
CSS;

        // ══════════════════════════════════════════════════════════
        //  HTML assemblage
        // ══════════════════════════════════════════════════════════
        $pageNum = 1;
        $foot = function (string $label) use ($missionId, &$pageNum): string {
            return '<div class="page-footer">'
                . '<span>Rapport d\'Audit Interne — Mission ' . $missionId . ' — Confidentiel</span>'
                . '<span>Page ' . ($pageNum++) . '</span>'
                . '</div>';
        };

        ob_start();

        echo '<!DOCTYPE html><html lang="fr"><head>';
        echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Rapport d\'Audit Interne — Mission ' . $missionId . ' — ' . $year . '</title>';
        echo '<style>' . $css . '</style>';
        echo '</head><body>';

        // ── TOOLBAR ───────────────────────────────────────────────
        echo '<div class="toolbar">';
        echo '<h3>&#128196; Rapport d\'Audit Interne — Mission ' . $missionId . '</h3>';
        echo '<button class="btn-print" onclick="window.print()">&#128424;&#65039; Imprimer / Enregistrer en PDF</button>';
        echo '</div>';

        // ══════════════════════════════════════════════════════════
        //  PAGE 1 — COUVERTURE
        // ══════════════════════════════════════════════════════════
        echo '<div class="page">';

        echo '<div class="cover-frame">';
        echo   '<div class="cover-logo"><div class="logo-ph">LOGO<br>ENTIT&Eacute;</div></div>';
        echo   '<div class="cover-mid">';
        echo     '<div class="dept">Direction de l\'Audit Interne</div>';
        echo     '<h1>Rapport d\'Audit Interne</h1>';
        echo   '</div>';
        echo   '<div class="cover-right">';
        echo     '<div class="cr-row"><span class="cl">N&deg; Rapport</span><span class="cv">' . $rapportNum . '</span></div>';
        echo     '<div class="cr-row"><span class="cl">N&deg; FPM</span><span class="cv">' . $numFpm . '</span></div>';
        echo     '<div class="cr-row"><span class="cl">Version</span><span class="cv">D&eacute;finitive</span></div>';
        echo     '<div class="cr-row"><span class="cl">Date d\'&eacute;mission</span><span class="cv">' . $today . '</span></div>';
        echo   '</div>';
        echo '</div>';

        echo '<div class="mission-box">';
        echo   '<span class="ml">Intitul&eacute; de la mission</span>';
        echo   '<div class="mv">' . $libelle . '</div>';
        echo '</div>';

        echo '<div class="meta-grid">';
        echo   '<div class="mc"><span class="ml">Dates de l\'audit</span><div class="mv">Du ' . $datesDeb . ' au ' . $datesFin . '</div></div>';
        echo   '<div class="mc"><span class="ml">Lieu(x) de l\'audit</span><div class="mv">' . $lieux . '</div></div>';
        echo   '<div class="mc"><span class="ml">R&eacute;f&eacute;rentiel d\'audit</span><div class="mv">Normes IIA &mdash; Proc&eacute;dures internes</div></div>';
        echo   '<div class="mc"><span class="ml">Opinion globale</span><div class="mv"><span class="b ' . $selCls . '">' . $opinionNiveau . '</span></div></div>';
        echo   '<div class="mc"><span class="ml">Entit&eacute;(s) audit&eacute;e(s)</span><div class="mv">' . $esc($mission['entite_auditee'] ?? '—') . '</div></div>';
        echo   '<div class="mc"><span class="ml">Commanditaire</span><div class="mv">' . $esc($mission['commanditaire'] ?? '—') . '</div></div>';
        echo   '<div class="mc" style="grid-column:1/-1"><span class="ml">Rapport &eacute;tabli par</span>';
        echo     '<div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:2mm">' . $sigHtml . '</div>';
        echo   '</div>';
        echo '</div>';

        echo '<div class="conf-box">&#128274; <strong>D&eacute;claration de confidentialit&eacute; :</strong> Ce rapport est confidentiel et s\'adresse uniquement aux personnes auxquelles il est destin&eacute;.</div>';

        echo '<div style="margin-top:8mm">';
        echo '<div class="toc-title">Table des Mati&egrave;res</div>';
        echo '<table class="toc-t">';
        echo   '<tr class="toc-sec"><td>Section 1</td><td>R&eacute;sum&eacute; Ex&eacute;cutif</td><td>p. 2</td></tr>';
        echo   '<tr><td>1.1</td><td>Opinion g&eacute;n&eacute;rale</td><td>p. 2</td></tr>';
        echo   '<tr><td>1.2</td><td>R&eacute;sum&eacute; des constats (1.2.1 &agrave; 1.2.5)</td><td>p. 2</td></tr>';
        echo   '<tr><td>1.3</td><td>Plan d\'actions (1.3.1 &agrave; 1.3.5)</td><td>p. 3</td></tr>';
        echo   '<tr><td>1.4</td><td>R&eacute;sum&eacute; des points forts</td><td>p. 3</td></tr>';
        echo   '<tr><td>1.5</td><td>&Eacute;nonc&eacute; des normes d\'audit</td><td>p. 3</td></tr>';
        echo   '<tr><td>1.6</td><td>Limites de l\'audit</td><td>p. 3</td></tr>';
        echo   '<tr><td>1.7</td><td>Observations g&eacute;n&eacute;rales de la structure audit&eacute;e</td><td>p. 3</td></tr>';
        echo   '<tr><td>1.8</td><td>Difficult&eacute;s rencontr&eacute;es</td><td>p. 3</td></tr>';
        echo   '<tr class="toc-sec"><td>Section 2</td><td>Constats D&eacute;taill&eacute;s (FRAP)</td><td>p. 4</td></tr>';
        echo   '<tr><td>2.1</td><td>Constats de contr&ocirc;le interne (FRAP-QCI)</td><td>p. 4</td></tr>';
        echo   '<tr><td>2.2</td><td>Constats de non-conformit&eacute; (FRAP-QCC)</td><td>p. —</td></tr>';
        echo   '<tr><td>2.3</td><td>Constats sur les op&eacute;rations financi&egrave;res (FRAP-FCC)</td><td>p. —</td></tr>';
        echo   '<tr><td>2.4</td><td>Constats de faiblesse des proc&eacute;dures (FRAP-FTP)</td><td>p. —</td></tr>';
        echo   '<tr><td>2.5</td><td>Constats de s&eacute;paration des t&acirc;ches (FRAP-GART)</td><td>p. —</td></tr>';
        echo   '<tr class="toc-sec"><td>Section 3</td><td>Annexes</td><td>p. —</td></tr>';
        echo   '<tr><td>Annexe 1</td><td>Champ et objectifs sp&eacute;cifiques de l\'audit</td><td>p. —</td></tr>';
        echo   '<tr><td>Annexe 2</td><td>Crit&egrave;res d\'&eacute;valuation du contr&ocirc;le interne</td><td>p. —</td></tr>';
        echo   '<tr><td>Annexe 3</td><td>Fiches de remarque et de non-conformit&eacute;</td><td>p. —</td></tr>';
        echo   '<tr><td>Annexe 4</td><td>Liste des destinataires du rapport</td><td>p. —</td></tr>';
        echo '</table>';
        echo '</div>';

        echo $foot('Page 1');
        echo '</div>'; // page 1

        // ══════════════════════════════════════════════════════════
        //  PAGE 2 — SECTION 1 : RÉSUMÉ EXÉCUTIF
        // ══════════════════════════════════════════════════════════
        echo '<div class="page">';
        echo '<div class="sec-hd"><h2>Section 1 &mdash; R&eacute;sum&eacute; Ex&eacute;cutif</h2></div>';

        // 1.1 Opinion
        echo '<div class="sub-t">1.1 &mdash; Opinion G&eacute;n&eacute;rale</div>';
        echo '<div style="margin-bottom:3mm;display:flex;align-items:center;gap:3mm;flex-wrap:wrap">';
        echo '<span style="font-size:8pt;color:#555">Niveau d\'opinion retenu :</span>';
        foreach ([
            'Critique'         => 'b-crit',
            'Significatif'     => 'b-sig',
            'Peu Significatif' => 'b-less',
            'Maintenance'      => 'b-house',
        ] as $lbl => $cls) {
            $isSelected = ($opinionNiveau === $lbl
                || ($lbl === 'Peu Significatif' && $opinionNiveau === 'Peu significatif'));
            $style = $isSelected ? ' style="border:1.5pt solid currentColor;font-size:8.5pt"' : '';
            $check = $isSelected ? '&#10003; ' : '';
            echo '<span class="b ' . $cls . '"' . $style . '>' . $check . $lbl . '</span>';
        }
        echo '</div>';

        // Stats
        echo '<div class="stat-row">';
        echo '<div class="sc sc1"><div class="sn">' . $sCrit  . '</div><div class="sl">Critique</div></div>';
        echo '<div class="sc sc2"><div class="sn">' . $sSig   . '</div><div class="sl">Significatif</div></div>';
        echo '<div class="sc sc3"><div class="sn">' . $sPeu   . '</div><div class="sl">Peu significatif</div></div>';
        echo '<div class="sc sc4"><div class="sn">' . $sTotal . '</div><div class="sl">Total constats</div></div>';
        echo '</div>';

        // Tableau récap constats
        echo '<table class="at" style="margin-bottom:3mm">';
        echo '<thead><tr><th></th><th>Critique</th><th>Significatif</th><th>Peu significatif</th><th>Maintenance</th><th>Total</th></tr></thead>';
        echo '<tbody>';
        echo '<tr><td>Constats</td>';
        echo '<td><span class="b b-crit">' . $sCrit  . ' Critique</span></td>';
        echo '<td><span class="b b-sig">'  . $sSig   . ' Significatif</span></td>';
        echo '<td><span class="b b-less">' . $sPeu   . ' Peu sig.</span></td>';
        echo '<td><span class="b b-house">' . max(0, $sTotal - $sCrit - $sSig - $sPeu) . ' Maint.</span></td>';
        echo '<td><b>' . $sTotal . ' Total</b></td>';
        echo '</tr></tbody></table>';

        // Texte opinion
        echo '<div class="txt-box">' . ($opinionTxt !== '' ? $opinionTxt : '<em>Zone de saisie libre — résumé narratif de l\'opinion générale.</em>') . '</div>';

        // 1.2
        echo '<div class="sub-t">1.2 &mdash; R&eacute;sum&eacute; des Constats</div>';
        echo $html12;

        echo $foot('Page 2');
        echo '</div>'; // page 2

        // ══════════════════════════════════════════════════════════
        //  PAGE 3 — 1.3 PLAN D'ACTIONS + 1.4 → 1.8
        // ══════════════════════════════════════════════════════════
        echo '<div class="page">';
        echo '<div class="sub-t">1.3 &mdash; Plan d\'Actions</div>';
        echo $html13;
        echo '<div class="sub-t" style="margin-top:4mm">1.4 &mdash; R&eacute;sum&eacute; des Points Forts</div>';
        echo '<div class="txt-box">' . $pfTxt . '</div>';
        echo '<div class="sub-t">1.5 &mdash; &Eacute;nonc&eacute; des Normes d\'Audit</div>';
        echo '<div class="txt-box">' . $normesTxt . '</div>';
        echo '<div class="sub-t">1.6 &mdash; Limites de l\'Audit</div>';
        echo '<div class="txt-box">' . $limitesTxt . '</div>';
        echo '<div class="sub-t">1.7 &mdash; Observations G&eacute;n&eacute;rales de la Structure Audit&eacute;e</div>';
        echo '<div class="txt-box">' . $observationsTxt . '</div>';
        echo '<div class="sub-t">1.8 &mdash; Difficult&eacute;s Rencontr&eacute;es au Cours de la Mission</div>';
        echo '<div class="txt-box">' . $difficulteesTxt . '</div>';
        echo $foot('Page 3');
        echo '</div>'; // page 3

        // ══════════════════════════════════════════════════════════
        //  PAGE 4 — SECTION 2 : FRAP
        // ══════════════════════════════════════════════════════════
        echo '<div class="page">';
        echo '<div class="sec-hd"><h2>Section 2 &mdash; Constats D&eacute;taill&eacute;s (FRAP)</h2></div>';
        echo '<div style="background:#f0f5ff;border:.5pt solid #aac;border-radius:2px;padding:2.5mm 4mm;font-size:8pt;color:#444;margin-bottom:4mm;line-height:1.5">';
        echo 'Les synth&egrave;ses des FRAP sont class&eacute;es par objectif d\'audit regroupé, dans l\'ordre chronologique de leur création. Les rubriques reprennent automatiquement le libellé de l\'objectif d\'audit d\'appartenance.';
        echo '</div>';
        echo $htmlSec2;
        echo $foot('Page 4');
        echo '</div>'; // page 4

        // ══════════════════════════════════════════════════════════
        //  PAGE 5 — SECTION 3 : ANNEXES 1 & 2
        // ══════════════════════════════════════════════════════════
        echo '<div class="page">';
        echo '<div class="sec-hd"><h2>Section 3 &mdash; Annexes</h2></div>';

        // Annexe 1
        echo '<div class="sub-t">Annexe 1 &mdash; Champ de l\'Audit et Objectifs Sp&eacute;cifiques</div>';
        echo '<div style="font-size:8.5pt;font-weight:bold;color:#333;margin-bottom:1.5mm">1.1 &mdash; Champ de l\'audit</div>';
        echo '<div class="txt-box">' . $esc($mission['champ_audit'] ?? '') . ($empty($mission['champ_audit'] ?? '') ? '[Description du p&eacute;rim&egrave;tre audit&eacute; : processus couverts, entit&eacute;s incluses, p&eacute;riode audit&eacute;e&hellip;]' : '') . '</div>';
        echo '<div style="font-size:8.5pt;font-weight:bold;color:#333;margin-bottom:1.5mm">1.2 &mdash; Objectifs sp&eacute;cifiques</div>';
        echo '<table class="ant"><thead><tr>';
        echo '<th>Objectifs groupe</th><th>Risques cl&eacute;s audit&eacute;s</th><th>Processus cl&eacute;</th><th>Sous-processus</th><th>Contr&ocirc;les cl&eacute;s</th>';
        echo '</tr></thead><tbody>';
        echo $annexeObjRows;
        echo '</tbody></table>';

        // Annexe 2
        echo '<div class="sub-t">Annexe 2 &mdash; Crit&egrave;res d\'&Eacute;valuation du Contr&ocirc;le Interne</div>';
        echo '<div style="font-size:8.5pt;margin-bottom:3mm;line-height:1.5;color:#444">Les niveaux de contr&ocirc;le interne sont appr&eacute;ci&eacute;s en fonction du niveau de risques valid&eacute;s par le syst&egrave;me de gestion des risques. <em>(Texte pr&eacute;-param&eacute;tr&eacute;)</em></div>';
        echo '<div class="rg">';
        echo '<div class="rc rc-c"><div class="rh">Critique &mdash; Priorit&eacute; 4 (Urgent)</div><div class="rb">Deux constats critiques ou plus. Contr&ocirc;le <strong>d&eacute;fectueux</strong>. Risque global : <strong>Critique</strong>. Remont&eacute;e imm&eacute;diate &agrave; la Direction G&eacute;n&eacute;rale et au Conseil.</div></div>';
        echo '<div class="rc rc-s"><div class="rh">Significatif &mdash; Priorit&eacute; 3 (&Eacute;lev&eacute;)</div><div class="rb">Majorit&eacute; de constats significatifs, pas plus d\'un critique. Contr&ocirc;le <strong>&agrave; renforcer</strong>. Risque global : <strong>Significatif</strong>.</div></div>';
        echo '<div class="rc rc-l"><div class="rh">Peu Significatif &mdash; Priorit&eacute; 2 (Moyen)</div><div class="rb">Majorit&eacute; de constats peu significatifs, aucun critique. Contr&ocirc;le <strong>acceptable</strong>. Risque global : <strong>Important</strong>.</div></div>';
        echo '<div class="rc rc-h"><div class="rh">Maintenance &mdash; Priorit&eacute; 1 (Insignifiant)</div><div class="rb">Majorit&eacute; de constats de maintenance, aucun significatif. Contr&ocirc;le <strong>efficace</strong>. Risque global : <strong>Mineur</strong>.</div></div>';
        echo '</div>';
        echo '<table class="ant"><thead><tr>';
        echo '<th>Niveau priorit&eacute;</th><th>Appellation</th><th>Efficacit&eacute; du CI</th><th>Appellation efficacit&eacute; CI</th><th>Niveau risque global</th><th>Observation</th>';
        echo '</tr></thead><tbody>';
        echo '<tr><td>4 &mdash; Urgent</td><td><span class="b b-crit">Critique</span></td><td>4</td><td>D&eacute;fectueux</td><td>Critique</td><td>Deux ou plusieurs constats critiques</td></tr>';
        echo '<tr><td>3 &mdash; &Eacute;lev&eacute;</td><td><span class="b b-sig">Significatif</span></td><td>3</td><td>&Agrave; renforcer</td><td>Significatif</td><td>Beaucoup de constats significatifs</td></tr>';
        echo '<tr><td>2 &mdash; Moyen</td><td><span class="b b-less">Peu significatif</span></td><td>2</td><td>Acceptable</td><td>Important</td><td>Quelques constats non significatifs</td></tr>';
        echo '<tr><td>1 &mdash; Insignifiant</td><td><span class="b b-house">Maintenance</span></td><td>1</td><td>Efficace</td><td>Mineur</td><td>Pas de constats</td></tr>';
        echo '</tbody></table>';

        echo $foot('Page 5');
        echo '</div>'; // page 5

        

        // ══════════════════════════════════════════════════════════
        //  PAGE 7 — FICHE NC p2 + ANNEXE 4 DESTINATAIRES
        // ══════════════════════════════════════════════════════════
        echo '<div class="page">';

        // Fiche NC p2
        echo '<div style="font-size:8.5pt;font-weight:bold;color:#8b1a1a;margin-bottom:2mm">Fiche de Non-Conformit&eacute; &nbsp; N&deg; ________ &nbsp; <span style="font-weight:normal;color:#777">(page 2/2)</span></div>';
        echo '<div class="fiche">';
        echo   '<div class="fiche-hd" style="background:#8b1a1a"><h4>FICHE DE NON-CONFORMIT&Eacute; &mdash; Action Corrective</h4></div>';
        echo   '<div class="fiche-sec"><h5>Identification de la Cause Profonde</h5><div class="fiche-field">[Analyse de la cause racine &mdash; m&eacute;thode des 5 Pourquoi ou diagramme d\'Ishikawa]</div></div>';
        echo   '<div class="fiche-sec"><h5>Action Corrective Envisag&eacute;e</h5><div class="cb-row"><span class="cbi">Oui</span><span>&#9675; Non &mdash; Justification : ______________________________________</span></div><div class="fiche-field">[Description d&eacute;taill&eacute;e de l\'action corrective]</div><div style="font-size:7.5pt;margin-bottom:2mm">Date d\'application : ___________________</div><div class="sig-row"><div class="sig-box"><span class="sl">Nom</span><div class="sli"></div><div class="sm"><span>Visa</span><span>Date</span></div></div></div></div>';
        echo   '<div class="fiche-sec"><h5>Approbation Responsable Qualit&eacute;</h5><div class="sig-row"><div class="sig-box"><span class="sl">Nom</span><div class="sli"></div><div class="sm"><span>Visa</span><span>Date</span></div></div></div></div>';
        echo   '<div class="fiche-sec"><h5>V&eacute;rification Mise en &OElig;uvre Action Corrective</h5><div class="sig-row"><div class="sig-box"><span class="sl">Nom</span><div class="sli"></div><div class="sm"><span>Visa</span><span>Date</span></div></div></div></div>';
        echo '</div>';

        // Fiche collective observations
        echo '<div style="font-size:8.5pt;font-weight:bold;color:#333;margin:4mm 0 2mm">Fiche Collective d\'Observations</div>';
        echo '<table class="ant"><thead><tr><th style="width:14mm">N&deg; obs.</th><th>Observation</th></tr></thead><tbody>';
        for ($i = 1; $i <= 5; $i++) {
            echo '<tr><td style="text-align:center">' . $i . '</td><td style="height:9mm">&nbsp;</td></tr>';
        }
        echo '</tbody></table>';

        // Annexe 4 — Destinataires
        echo '<div class="sub-t">Annexe 4 &mdash; Liste des Destinataires du Rapport</div>';
        echo '<table class="ant"><thead><tr><th>Noms</th><th>Pr&eacute;noms</th><th>Fonction / D&eacute;signation</th></tr></thead>';
        echo '<tbody>';
        echo '<tr class="gr"><td colspan="3">&Eacute;quipe d\'Audit &mdash; Mission ' . $missionId . '</td></tr>';
        echo $destRows;
        echo '</tbody></table>';

        echo '<div style="margin-top:10mm;text-align:center;font-size:7.5pt;color:#aaa;border-top:.5pt solid #e0e0e0;padding-top:3mm">';
        echo 'Rapport d\'Audit Interne &mdash; Mission ' . $missionId . ' &mdash; Direction de l\'Audit Interne &nbsp;|&nbsp; Confidentiel &nbsp;|&nbsp; G&eacute;n&eacute;r&eacute; le ' . $today;
        echo '</div>';

        echo $foot('Page 7');
        echo '</div>'; // page 7

        echo '</body></html>';

        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVÉ · Fusion champs éditables
    // ══════════════════════════════════════════════════════════════

    private function mergeEditableFields(array $data, array $editableFields): array
    {
        $data = json_decode(json_encode($data), true);
        if (!empty($editableFields['opinion'])) {
            $data['opinion']['description'] = $editableFields['opinion'];
        }
        if (!empty($editableFields['points_forts'])) {
            $lines = explode("\n", $editableFields['points_forts']);
            $data['pointsForts'] = array_values(array_filter(
                array_map(fn($l) => ltrim(trim($l), '•- '), $lines),
                fn($l) => $l !== ''
            ));
        }
        $data['editable'] = [
            'opinion'      => $editableFields['opinion']      ?? '',
            'normes'       => $editableFields['normes']       ?? '',
            'limites'      => $editableFields['limites']      ?? '',
            'observations' => $editableFields['observations'] ?? '',
            'difficultes'  => $editableFields['difficultes']  ?? '',
        ];
        return $data;
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVÉ · Champs éditables sauvegardés
    // ══════════════════════════════════════════════════════════════

    private function loadSavedEdits(int $missionId): array
    {
        try {
            $row = DB::connection('tenant')->table('rapport_audit_edits')->where('mission_id', $missionId)->first();
            return $row?->editable_fields ? (json_decode($row->editable_fields, true) ?? []) : [];
        } catch (\Exception) {
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVÉ · Contrôle d'accès
    // ══════════════════════════════════════════════════════════════

    private function authorizeAccess(int $missionId): void
    {
        $user = auth()->user();
        abort_if(!$user, 403, 'Non authentifié.');
        $auditor = DB::connection('tenant')->table('auditors')->where('user_id', $user->id)->first();
        abort_if(!$auditor, 403, 'Auditeur introuvable.');
        $hasAccess = DB::connection('tenant')
            ->table('mission_phase_assignments as mpa')
            ->join('mission_phase_assignment_auditeurs as mpaa', 'mpaa.assignment_id', '=', 'mpa.id')
            ->where('mpa.mission_programmation_id', $missionId)
            ->where('mpaa.auditeur_id', $auditor->id)
            ->exists();
        abort_if(!$hasAccess, 403, 'Accès à cette mission non autorisé.');
    }
}