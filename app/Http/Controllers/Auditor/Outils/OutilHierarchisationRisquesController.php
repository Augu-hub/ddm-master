<?php

namespace App\Http\Controllers\Auditor\Outils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;


class OutilHierarchisationRisquesController extends Controller
{
    use \App\Http\Controllers\Auditor\Outils\FicheTestContextTrait;

    private string $conn  = 'tenant';
    private string $table = 'outil_hierarchisation_risques';
    private string $tableDoc = 'outil_hierarchisation_risques_documents';
    private string $tableLignes = 'outil_hierarchisation_risques_lignes';

    private array $config = [
        'allowed_extensions' => ['doc','docx','odt','rtf','pdf','xls','xlsx','csv'],
        'max_file_size'      => 10485760,
    ];

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function db(): \Illuminate\Database\Connection
    {
        return DB::connection($this->conn);
    }

    private function findOrFail(int $id): object
    {
        $record = $this->db()->table($this->table)->where('id', $id)->first();
        abort_if(!$record, 404, 'Fiche introuvable.');
        return $record;
    }

    private function getAuditorRole(): string { return session('auditor_role', 'AS'); }

    private function getAuditorName(): string
    {
        $user = Auth::user();
        return $user ? ($user->name ?? $user->email) : 'Auditeur';
    }

    private static function cssToHex(string $css): string
    {
        return match (trim(strtolower($css))) {
            'success' => '#28a745', 'danger'  => '#dc3545', 'warning' => '#ffc107',
            'info'    => '#17a2b8', 'primary' => '#0d6efd', 'secondary'=> '#6c757d',
            'dark'    => '#343a40', 'light'   => '#f8f9fa',
            default   => str_starts_with($css,'#') ? $css : '#6c757d',
        };
    }

    private function getMissionContext(Request $request): array
    {
        $context = [];
        $missionId = $request->query('mission_id');
        if ($missionId) {
            $mission = $this->db()->table('missions')->where('id', $missionId)->first();
            $context['mission_id']      = $missionId;
            $context['mission_libelle'] = $mission->title ?? $mission->libelle ?? null;
        }
        foreach (['assignment_id','procedure_code','test_ref','obj_num','fiche_test_id','libelle_test','libelle_proc','objectif_audit'] as $key) {
            $val = $request->query($key);
            if ($val !== null) $context[$key] = $val;
        }
        return $context;
    }

    private function docBaseUrl(string $routeName, int $ficheId): string
    {
        return route($routeName, [$ficheId, '__DOC__']);
    }

   private function buildUrlsPayload(int $ficheId): array
    {
        return [
            'urlUpdate'              => route('auditor.ac.outil-hierarchisation-risques.update',    $ficheId),
            'urlSoumettre'           => route('auditor.ac.outil-hierarchisation-risques.soumettre', $ficheId),
            'urlValider'             => route('auditor.ac.outil-hierarchisation-risques.valider',   $ficheId),
            'urlIa'                  => route('auditor.ac.outil-hierarchisation-risques.ia',        $ficheId),
            'urlUploadDoc'           => route('auditor.ac.outil-hierarchisation-risques.upload-doc',$ficheId),
            'urlDownloadDocBase'     => $this->docBaseUrl('auditor.ac.outil-hierarchisation-risques.download-doc', $ficheId),
            'urlValidateDocBase'     => $this->docBaseUrl('auditor.ac.outil-hierarchisation-risques.validate-doc', $ficheId),
            'urlDeleteDocBase'       => $this->docBaseUrl('auditor.ac.outil-hierarchisation-risques.delete-doc',   $ficheId),
            'urlSendValidationEmail' => route('auditor.ac.outil-hierarchisation-risques.send-validation-email', $ficheId),
        ];
    }

    private function extractEmailFromInterlocuteur(?string $s): ?string
    {
        if (!$s) return null;
        preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $s, $m);
        return $m[0] ?? null;
    }

    private function updateAttachedDocumentsJson(int $ficheId): void
    {
        $docs = $this->db()->table($this->tableDoc)
            ->where('fiche_id', $ficheId)->whereNull('deleted_at')
            ->select('id','original_name','file_extension','file_size','status','created_at','uploaded_by')
            ->get();
        $this->db()->table($this->table)->where('id', $ficheId)
            ->update(['attached_documents' => json_encode($docs)]);
    }

    // ─────────────────────────────────────────────────────────────
    // CHARGEMENT DES RISQUES MISSION (même pattern AnalyseRisques)
    // ─────────────────────────────────────────────────────────────

    /**
     * Résout l'entity_id depuis mission → assignment → fallback univers.
     * Essaie les deux connexions (default + tenant).
     */
    private function resolveEntityId(int $missionId, ?int $assignmentId = null): ?int
    {
        // Tenter les deux connexions pour la mission
        $mission = $this->db()->table('missions')->where('id', $missionId)->first()
                ?? DB::table('missions')->where('id', $missionId)->first();

        foreach (['entity_id','entite_id','structure_id','org_id'] as $col) {
            if ($mission && !empty(((array)$mission)[$col])) {
                return (int)((array)$mission)[$col];
            }
        }

        if ($assignmentId) {
            $asgn = $this->db()->table('mission_phase_assignments')->where('id', $assignmentId)->first()
                 ?? DB::table('mission_phase_assignments')->where('id', $assignmentId)->first();
            foreach (['entity_id','entite_id','structure_id'] as $col) {
                if ($asgn && !empty(((array)$asgn)[$col])) return (int)((array)$asgn)[$col];
            }
        }

        // Fallback : premier audit_universe disponible
        $eid = $this->db()->table('audit_universe')->orderByDesc('year')->value('entity_id')
            ?? DB::table('audit_universe')->orderByDesc('year')->value('entity_id');

        return $eid ? (int)$eid : null;
    }

    private function resolveActiveYear(?int $entityId, int $missionId): int
    {
        if ($entityId) {
            $y = $this->db()->table('audit_universe')->where('entity_id', $entityId)->orderByDesc('year')->value('year')
              ?? DB::table('audit_universe')->where('entity_id', $entityId)->orderByDesc('year')->value('year');
            if ($y) return (int)$y;
        }
        $mission = $this->db()->table('missions')->where('id', $missionId)->first()
                ?? DB::table('missions')->where('id', $missionId)->first();
        foreach (['year','annee','exercice','fiscal_year'] as $col) {
            if ($mission && !empty(((array)$mission)[$col])) return (int)((array)$mission)[$col];
        }
        return (int)date('Y');
    }

    /**
     * Charge les risques depuis audit_universe.risques — même logic qu'AnalyseRisquesController.
     * Détecte automatiquement quelle connexion (default/tenant) a les données.
     */
    private function chargerRisquesMission(int $missionId, ?int $assignmentId = null): array
    {
        // Résoudre mission_id réel (depuis programmation si besoin)
        $realMissionId = $missionId;
        $progRow = $this->db()->table('mission_programmation')->where('id', $missionId)->first();
        if ($progRow && !empty($progRow->mission_id)) {
            $realMissionId = (int)$progRow->mission_id;
        }

        $entityId   = $this->resolveEntityId($realMissionId, $assignmentId);
        $activeYear = $this->resolveActiveYear($entityId, $realMissionId);

        if (!$entityId) {
            Log::warning("[VI] chargerRisquesMission: entity_id non résolu pour mission={$missionId}");
            return [];
        }

        // Chercher l'univers (deux connexions)
        $universeRow = $this->db()->table('audit_universe')
            ->where('entity_id', $entityId)->where('year', $activeYear)->first();
        if (!$universeRow) {
            $universeRow = DB::table('audit_universe')
                ->where('entity_id', $entityId)->where('year', $activeYear)->first();
        }
        // Fallback : année la plus récente
        if (!$universeRow) {
            $universeRow = $this->db()->table('audit_universe')
                ->where('entity_id', $entityId)->orderByDesc('year')->first()
                ?? DB::table('audit_universe')->where('entity_id', $entityId)->orderByDesc('year')->first();
            if ($universeRow) $activeYear = (int)$universeRow->year;
        }

        if (!$universeRow || empty($universeRow->risques)) {
            // Fallback direct sur mission_risk si univers vide
            return $this->chargerRisquesDepuisMissionRisk($realMissionId);
        }

        $decoded = json_decode($universeRow->risques, true);
        if (!is_array($decoded) || empty($decoded)) {
            return $this->chargerRisquesDepuisMissionRisk($realMissionId);
        }

        $universeMap = [];
        foreach ($decoded as $entry) {
            $rid = (int)($entry['risk_id'] ?? 0);
            if ($rid > 0) $universeMap[$rid] = $entry;
        }

        if (empty($universeMap)) return [];

        // Détecter quelle connexion a les risques
        $useDefault = false;
        try {
            $cntTenant  = $this->db()->table('risks')->whereIn('id', array_keys($universeMap))->whereNull('deleted_at')->count();
            $cntDefault = DB::table('risks')->whereIn('id', array_keys($universeMap))->whereNull('deleted_at')->count();
            if ($cntDefault > $cntTenant) $useDefault = true;
        } catch (\Exception) {}

        $db = $useDefault ? DB::getFacadeRoot() : $this->db();

        $risks = $db->table('risks as r')
            ->leftJoin('risk_frequency_levels as rfl', fn($j) => $j->on('rfl.id','=','r.frequency_level_id')->whereNull('rfl.deleted_at'))
            ->leftJoin('risk_impact_levels as ril',    fn($j) => $j->on('ril.id','=','r.impact_level_id')->whereNull('ril.deleted_at'))
            ->leftJoin('processes as p',   'p.id',  '=', 'r.process_id')
            ->leftJoin('activities as a',  'a.id',  '=', 'r.activity_id')
            ->leftJoin('risk_types as rt', fn($j) => $j->on('rt.id','=','r.risk_type_id')->whereNull('rt.deleted_at'))
            ->whereIn('r.id', array_keys($universeMap))
            ->whereNull('r.deleted_at')
            ->select([
                'r.id','r.code','r.label','r.description','r.status','r.owner',
                'r.criticality','r.control_procedure','r.impact_net','r.frequency_net',
                'r.process_id','r.activity_id','r.risk_type_id','r.year',
                DB::raw('rfl.level AS frequency_level'), DB::raw('rfl.label AS frequency_label'),
                DB::raw("COALESCE(rfl.color,'secondary') AS frequency_color"),
                DB::raw('ril.level AS impact_level'),    DB::raw('ril.label AS impact_label'),
                DB::raw("COALESCE(ril.color,'secondary') AS impact_color"),
                DB::raw('p.code AS process_code'),       DB::raw('p.name AS process_name'),
                DB::raw('a.code AS activity_code'),      DB::raw('a.name AS activity_name'),
                DB::raw('rt.label AS risk_type_label'),  DB::raw("COALESCE(rt.color,'secondary') AS risk_type_color"),
            ])
            ->orderBy('p.code')->orderByDesc('r.criticality')->orderBy('r.code')
            ->get()
            ->map(function ($row) use ($universeMap) {
                $u = $universeMap[(int)$row->id] ?? null;
                $row->frequency_level = $row->frequency_level ?? null;
                $row->frequency_label = $row->frequency_label ?? '—';
                $row->frequency_color = self::cssToHex($row->frequency_color ?? 'secondary');
                $row->impact_level    = $row->impact_level    ?? null;
                $row->impact_label    = $row->impact_label    ?? '—';
                $row->impact_color    = self::cssToHex($row->impact_color ?? 'secondary');
                $row->process_code    = $row->process_code    ?? '—';
                $row->process_name    = $row->process_name    ?? '—';
                $row->activity_code   = $row->activity_code   ?? '—';
                $row->activity_name   = $row->activity_name   ?? '—';
                $row->risk_type_label = $row->risk_type_label ?? '—';
                $row->risk_type_color = self::cssToHex($row->risk_type_color ?? 'secondary');
                if ($u) {
                    if (isset($u['impact_net'])    && $u['impact_net']    !== null) $row->impact_net    = (int)$u['impact_net'];
                    if (isset($u['frequency_net']) && $u['frequency_net'] !== null) $row->frequency_net = (int)$u['frequency_net'];
                    if (!empty($u['control_procedure'])) $row->control_procedure = $u['control_procedure'];
                }
                $fl = (int)($row->frequency_level ?? 1);
                $il = (int)($row->impact_level    ?? 1);
                $row->criticality  = $row->criticality ?? ($fl * $il);
                $row->is_evaluated = (bool)($u['is_evaluated'] ?? false);
                return $row;
            })
            ->values()
            ->toArray();

        Log::info("[VI] chargerRisquesMission: " . count($risks) . " risques chargés entity={$entityId} year={$activeYear}");
        return $risks;
    }

    /**
     * Fallback : charge via mission_risk → risks JOIN (si audit_universe vide)
     */
    private function chargerRisquesDepuisMissionRisk(int $missionId): array
    {
        try {
            $riskIds = $this->db()->table('mission_risk')
                ->where('mission_id', $missionId)->pluck('risk_id')->toArray();
            if (empty($riskIds)) {
                Log::info("[VI] chargerRisquesDepuisMissionRisk: aucun risk_id pour mission={$missionId}");
                return [];
            }

            // Détecter connexion
            $useDefault = false;
            try {
                $cntDef = DB::table('risks')->whereIn('id', $riskIds)->whereNull('deleted_at')->count();
                $cntTen = $this->db()->table('risks')->whereIn('id', $riskIds)->whereNull('deleted_at')->count();
                if ($cntDef > $cntTen) $useDefault = true;
            } catch (\Exception) {}

            $db = $useDefault ? DB::getFacadeRoot() : $this->db();

            $risks = $db->table('risks as r')
                ->leftJoin('risk_frequency_levels as rfl', fn($j) => $j->on('rfl.id','=','r.frequency_level_id')->whereNull('rfl.deleted_at'))
                ->leftJoin('risk_impact_levels as ril',    fn($j) => $j->on('ril.id','=','r.impact_level_id')->whereNull('ril.deleted_at'))
                ->leftJoin('processes as p',  'p.id',  '=', 'r.process_id')
                ->leftJoin('risk_types as rt', fn($j) => $j->on('rt.id','=','r.risk_type_id')->whereNull('rt.deleted_at'))
                ->whereIn('r.id', $riskIds)->whereNull('r.deleted_at')
                ->select([
                    'r.id','r.code','r.label','r.description','r.status','r.owner',
                    'r.criticality','r.control_procedure','r.impact_net','r.frequency_net',
                    'r.process_id','r.risk_type_id',
                    DB::raw('rfl.level AS frequency_level'), DB::raw('rfl.label AS frequency_label'),
                    DB::raw('ril.level AS impact_level'),    DB::raw('ril.label AS impact_label'),
                    DB::raw('p.code AS process_code'),       DB::raw('p.name AS process_name'),
                    DB::raw('rt.label AS risk_type_label'),
                ])
                ->orderByDesc('r.criticality')->orderBy('r.code')
                ->get()
                ->map(function($r) {
                    $r->frequency_level = $r->frequency_level ?? null;
                    $r->frequency_label = $r->frequency_label ?? '—';
                    $r->impact_level    = $r->impact_level    ?? null;
                    $r->impact_label    = $r->impact_label    ?? '—';
                    $r->process_code    = $r->process_code    ?? '—';
                    $r->process_name    = $r->process_name    ?? '—';
                    $r->risk_type_label = $r->risk_type_label ?? '—';
                    $r->frequency_color = '#6c757d';
                    $r->impact_color    = '#6c757d';
                    $r->risk_type_color = '#6c757d';
                    $fl = (int)($r->frequency_level ?? 1);
                    $il = (int)($r->impact_level    ?? 1);
                    $r->criticality    = $r->criticality ?? ($fl * $il);
                    $r->is_evaluated   = false;
                    return $r;
                })->values()->toArray();

            Log::info("[VI] chargerRisquesDepuisMissionRisk: " . count($risks) . " risques via mission_risk mission={$missionId}");
            return $risks;
        } catch (\Exception $e) {
            Log::warning("[VI] chargerRisquesDepuisMissionRisk: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS WORD (identiques aux autres contrôleurs)
    // ─────────────────────────────────────────────────────────────

    private function extractWordToHtml(string $filePath): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        $html    = '';
        foreach ($phpWord->getSections() as $section)
            foreach ($section->getElements() as $element)
                $html .= $this->renderElementToHtml($element);
        return $html ?: '<p><em>Document vide ou non lisible.</em></p>';
    }

    private function renderElementToHtml($element): string
    {
        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            $html = '<table style="border-collapse:collapse;width:100%;margin:8px 0">';
            foreach ($element->getRows() as $row) {
                $html .= '<tr>';
                foreach ($row->getCells() as $cell) {
                    $html .= '<td style="border:1px solid #cbd5e1;padding:6px 8px;vertical-align:top">';
                    foreach ($cell->getElements() as $el) $html .= $this->renderElementToHtml($el);
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            return $html . '</table>';
        }
        if ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) return '<br>';
        if ($element instanceof \PhpOffice\PhpWord\Element\Paragraph || $element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            $content = '';
            foreach ($element->getElements() as $child) $content .= $this->renderElementToHtml($child);
            $style = $element->getParagraphStyle();
            $css   = 'margin:4px 0;';
            if ($style instanceof \PhpOffice\PhpWord\Style\Paragraph) {
                $align = $style->getAlignment();
                if ($align === \PhpOffice\PhpWord\SimpleType\Jc::CENTER)    $css .= 'text-align:center;';
                elseif ($align === \PhpOffice\PhpWord\SimpleType\Jc::RIGHT) $css .= 'text-align:right;';
            }
            return trim($content) !== '' ? '<p style="'.$css.'">'.$content.'</p>' : '<p style="margin:2px 0">&nbsp;</p>';
        }
        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $t = $element->getText();
            return is_string($t) && trim($t) !== '' ? e($t) : '';
        }
        return '';
    }

    private function htmlToWordFile(string $html): string
    {
        $html = preg_replace(['/<br\s*>/i','/<meta[^>]*>/i','/<!DOCTYPE[^>]*>/i'], ['<br/>','',''], $html);
        $dom  = new \DOMDocument('1.0','UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>', LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $bodyContent = '';
        if ($body = $dom->getElementsByTagName('body')) {
            if ($body->length > 0)
                foreach ($body->item(0)->childNodes as $node)
                    if ($node) $bodyContent .= $dom->saveHTML($node);
        }
        if (empty(trim($bodyContent))) $bodyContent = '<p>Document vide</p>';
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection(['marginLeft'=>1440,'marginRight'=>1440,'marginTop'=>1440,'marginBottom'=>1440]);
        try { \PhpOffice\PhpWord\Shared\Html::addHtml($section, $bodyContent, false, false); }
        catch (\Exception $e) { $section->addText(strip_tags($bodyContent)); }
        $tempFile = tempnam(sys_get_temp_dir(),'wordgen_').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord,'Word2007')->save($tempFile);
        return $tempFile;
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $ftCtx     = $this->ficheTestContext($request);
        $missionId = (int)($request->query('mission_id') ?? 0);

        // ── Résoudre le vrai mission_id si c'est un id de programmation ──
        $realMissionId = $missionId;
        if ($missionId > 0) {
            $progRow = $this->db()->table('mission_programmation')->where('id', $missionId)->first();
            if ($progRow && !empty($progRow->mission_id)) {
                $realMissionId = (int)$progRow->mission_id;
            }
        }

        // ── Si contexte fiche-test : chercher une fiche existante ──────
        if ($ftCtx['has_context']) {
            $existingId = null;
            if (method_exists($this,'findExistingOutilId')) {
                $existingId = $this->findExistingOutilId(
                    $ftCtx['fiche_test_id'], 'VI',
                    $ftCtx['test_ref'], $ftCtx['proc_idx']
                );
            }
            // Chercher aussi par mission + procedure_code + test_ref
            if (!$existingId && $realMissionId) {
                $existing = $this->db()->table($this->table)
                    ->where(function($q) use ($missionId, $realMissionId) {
                        $q->where('mission_id', $missionId)->orWhere('mission_id', $realMissionId);
                    })
                    ->when($ftCtx['test_ref'] ?? null, fn($q, $v) => $q->where('test_ref', $v))
                    ->when($ftCtx['procedure_code'] ?? null, fn($q, $v) => $q->where('procedure_code', $v))
                    ->first();
                $existingId = $existing?->id;
            }

            if ($existingId) {
                return redirect()->route(
                    'auditor.ac.outil-hierarchisation-risques.edit',
                    [$existingId] + $request->query()
                );
            }
        }

        // ── Pas de fiche existante : rendre la vue "nouveau" avec les risques ──
        $missionContext = $ftCtx['missionContext'] ?? $this->getMissionContext($request);
        // S'assurer que mission_id réel est dans le contexte
        if ($realMissionId && $realMissionId !== $missionId) {
            $missionContext['real_mission_id'] = $realMissionId;
        }

        $risquesMission = [];
        if ($realMissionId > 0) {
            $risquesMission = $this->chargerRisquesMission($realMissionId, (int)($request->query('assignment_id') ?? 0) ?: null);
        }

        return Inertia::render('dashboards/Auditor/Outils/OutilHierarchisationRisques', [
            'fiche'          => [],
            'lignes'         => [],
            'documents'      => [],
            'iaResult'       => null,
            'risquesMission' => $risquesMission,
            'urlStore'       => route('auditor.ac.outil-hierarchisation-risques.store'),
            'urlUpdate'      => null,
            'urlSoumettre'   => null,
            'urlValider'     => null,
            'urlIa'          => null,
            'urlUploadDoc'   => '',
            'urlDownloadDocBase'  => '',
            'urlValidateDocBase'  => '',
            'urlDeleteDocBase'    => '',
            'urlSendValidationEmail' => null,
            'backUrl'        => $ftCtx['back_url'] ?? $request->query('back', '/'),
            'missionContext' => $missionContext,
            'auditorRole'    => $this->getAuditorRole(),
            'auditeurNom'    => $this->getAuditorName(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────

    public function edit(Request $request, int $id)
    {
        $fiche     = $this->findOrFail($id);
        $lignes    = $this->db()->table($this->tableLignes)->where('fiche_id',$id)->orderBy('ordre')->get();
        $documents = $this->db()->table($this->tableDoc)->where('fiche_id',$id)->whereNull('deleted_at')->orderByDesc('created_at')->get();

        // Charger les risques de la mission pour import
        $risquesMission = $this->chargerRisquesMission(
            (int)$fiche->mission_id,
            $fiche->assignment_id ?? null
        );

        // IA result
        $iaResult = null;
        if (!empty($fiche->ia_result)) {
            $iaResult = is_string($fiche->ia_result) ? json_decode($fiche->ia_result, true) : (array)$fiche->ia_result;
        }

        $ftCtx          = $this->ficheTestContext($request);
        $missionContext = array_merge($ftCtx['missionContext'] ?? [], $this->getMissionContext($request));

        return Inertia::render('dashboards/Auditor/Outils/OutilHierarchisationRisques', array_merge([
            'fiche'          => $fiche,
            'lignes'         => $lignes,
            'documents'      => $documents,
            'iaResult'       => $iaResult,
            'risquesMission' => $risquesMission,
            'urlStore'       => route('auditor.ac.outil-hierarchisation-risques.store'),
            'backUrl'        => $request->query('back', '/'),
            'missionContext' => $missionContext,
            'auditorRole'    => $this->getAuditorRole(),
            'auditeurNom'    => $this->getAuditorName(),
        ], $this->buildUrlsPayload($id)));
    }

    // ─────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────

    /**
 * STORE – Création d'une nouvelle fiche de hiérarchisation des risques
 */
public function store(Request $request)
{
    // 1. Validation des données entrantes
    $v = $request->validate([
        'mission_id'          => 'required|integer',
        'assignment_id'       => 'nullable|integer',
        'procedure_code'      => 'nullable|string|max:80',
        'test_ref'            => 'nullable|string|max:80',
        'obj_num'             => 'nullable|string|max:20',
        'intitule'            => 'required|string|max:255',
        'perimetre'           => 'nullable|string',
        'date_analyse'        => 'nullable|date',
        'interlocuteur_email' => 'nullable|email|max:255',
        'echelle'             => 'nullable|integer|in:3,5',
        'lignes'              => 'nullable|array',
    ]);

    // 2. Résolution du vrai mission_id (si c'est un ID de programmation)
    $mid = (int)$v['mission_id'];
    $realMid = $mid;
    $progRow = $this->db()->table('mission_programmation')->where('id', $mid)->first();
    if ($progRow && !empty($progRow->mission_id)) {
        $realMid = (int)$progRow->mission_id;
    }
    $mid = $realMid; // on stocke le vrai mission_id

    // 3. Génération du code unique (ex: VI-0018-001)
    $count = $this->db()->table($this->table)->where('mission_id', $mid)->count();
    $code  = 'VI-' . str_pad($mid, 4, '0', STR_PAD_LEFT) . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    // 4. Insertion de la fiche principale
    $id = $this->db()->table($this->table)->insertGetId([
        'code'                => $code,
        'mission_id'          => $mid,
        'assignment_id'       => $v['assignment_id']       ?? null,
        'procedure_code'      => $v['procedure_code']      ?? null,
        'test_ref'            => $v['test_ref']            ?? null,
        'obj_num'             => $v['obj_num']             ?? null,
        'intitule'            => $v['intitule'],
        'perimetre'           => $v['perimetre']           ?? null,
        'date_analyse'        => $v['date_analyse']        ?? null,
        'interlocuteur_email' => $v['interlocuteur_email'] ?? null,
        'echelle'             => $v['echelle']             ?? 5,
        'statut'              => 'draft',
        'validation_status'   => 'pending',
        'created_by'          => Auth::id(),
        'updated_by'          => Auth::id(),
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    // 5. Sauvegarde des lignes de risques
    $this->saveLignes($id, $v['lignes'] ?? []);

    // 6. Lien avec la fiche-test si contexte
    if (method_exists($this, 'saveFicheTestLinkIfPresent')) {
        $this->saveFicheTestLinkIfPresent($request, $id, 'VI', $this->table);
    }

    // 7. Récupération de l'enregistrement fraîchement créé
    $record = $this->db()->table($this->table)->where('id', $id)->first();

    // 8. Réponse JSON (attendue par le frontend Vue)
    if ($request->expectsJson()) {
        return response()->json(array_merge(
            ['success' => true, 'record' => $record],
            $this->buildUrlsPayload($id)   // ← crucial pour que le front ait les URLs
        ));
    }

    // Fallback : redirection classique (normalement jamais atteint car appelé en fetch)
    return redirect()->route('auditor.ac.outil-hierarchisation-risques.edit', $id)
        ->with('success', 'Hiérarchisation créée.');
}

    // ─────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $fiche = $this->findOrFail($id);
        abort_if(
            in_array($fiche->statut,['validated','in_review']) && !in_array($this->getAuditorRole(),['DM','CM']),
            403
        );

        $v = $request->validate([
            'intitule'            => 'required|string|max:255',
            'perimetre'           => 'nullable|string',
            'date_analyse'        => 'nullable|date',
            'interlocuteur_email' => 'nullable|email|max:255',
            'echelle'             => 'nullable|integer|in:3,5',
            'lignes'              => 'nullable|array',
        ]);

        $this->db()->table($this->table)->where('id',$id)->update([
            'intitule'            => $v['intitule'],
            'perimetre'           => $v['perimetre']           ?? null,
            'date_analyse'        => $v['date_analyse']        ?? null,
            'interlocuteur_email' => $v['interlocuteur_email'] ?? null,
            'echelle'             => $v['echelle']             ?? 5,
            'updated_by'          => Auth::id(),
            'updated_at'          => now(),
        ]);

        $this->saveLignes($id, $v['lignes'] ?? []);

        if ($request->expectsJson()) {
            $record = $this->db()->table($this->table)->where('id',$id)->first();
            return response()->json(['success'=>true,'record'=>$record]);
        }
        return back()->with('success','Mise à jour effectuée.');
    }

    private function saveLignes(int $ficheId, array $lignes): void
    {
        $this->db()->table($this->tableLignes)->where('fiche_id',$ficheId)->delete();
        foreach ($lignes as $idx => $l) {
            if (empty(trim($l['libelle'] ?? ''))) continue;
            $this->db()->table($this->tableLignes)->insert([
                'fiche_id'     => $ficheId,
                'libelle'      => $l['libelle'],
                'categorie'    => $l['categorie']    ?? null,
                'causes'       => $l['causes']       ?? null,
                'consequences' => $l['consequences'] ?? null,
                'probabilite'  => max(1,min(5,(int)($l['probabilite'] ?? 1))),
                'impact'       => max(1,min(5,(int)($l['impact']      ?? 1))),
                'traitement'   => $l['traitement']   ?? null,
                'responsable'  => $l['responsable']  ?? null,
                'echeance'     => !empty($l['echeance']) ? $l['echeance'] : null,
                'from_mission' => (int)($l['from_mission'] ?? 0),
                'risk_id'      => $l['risk_id']      ?? null,
                'ordre'        => $idx + 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY / WORKFLOW
    // ─────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $fiche = $this->findOrFail($id);
        abort_if($fiche->statut === 'validated', 403, 'Fiche validée, suppression impossible.');
        $this->db()->table($this->tableLignes)->where('fiche_id',$id)->delete();
        $this->db()->table($this->tableDoc)->where('fiche_id',$id)->delete();
        $this->db()->table($this->table)->where('id',$id)->delete();
        return response()->json(['success'=>true]);
    }

    public function soumettre(Request $request, int $id)
    {
        $fiche = $this->findOrFail($id);
        abort_if($fiche->statut !== 'draft', 422, 'Seul un brouillon peut être soumis.');
        $this->db()->table($this->table)->where('id',$id)->update([
            'statut'=>'in_review','submitted_at'=>now(),'submitted_by'=>Auth::id(),'updated_at'=>now(),
        ]);
        if ($request->expectsJson()) return response()->json(['success'=>true]);
        return back()->with('success','Fiche soumise pour validation.');
    }

    public function valider(Request $request, int $id)
    {
        $fiche = $this->findOrFail($id);
        abort_if($fiche->statut !== 'in_review', 422, 'Fiche non soumise.');
        $v = $request->validate(['decision'=>'required|in:validated,rejected','commentaire'=>'nullable|string']);
        $this->db()->table($this->table)->where('id',$id)->update([
            'statut'          => $v['decision'],
            'validation_note' => $v['commentaire'] ?? null,
            'validated_by'    => Auth::id(),
            'validated_at'    => now(),
            'updated_at'      => now(),
        ]);
        if ($request->expectsJson()) return response()->json(['success'=>true,'statut'=>$v['decision']]);
        return back()->with('success', $v['decision']==='validated' ? 'Fiche validée.' : 'Fiche rejetée.');
    }

    // ─────────────────────────────────────────────────────────────
    // EMAIL DE VALIDATION INTERLOCUTEUR
    // ─────────────────────────────────────────────────────────────

   // Dans App\Http\Controllers\Auditor\Outils\OutilHierarchisationRisquesController.php



// ... autres méthodes ...

/**
 * Envoi de l'email de validation à l'interlocuteur
 */
public function sendValidationEmail(Request $request, int $id)
{
    $fiche = $this->findOrFail($id);
    
    // 🔽 SUPPRIMEZ ou COMMENTEZ cette ligne
    // abort_if($fiche->statut !== 'validated', 422, 'Seule une fiche validée peut être envoyée.');

    $email = $request->input('email')
        ?: $fiche->interlocuteur_email
        ?: $this->extractEmailFromInterlocuteur($fiche->perimetre ?? null);

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return response()->json(['error' => 'Email interlocuteur invalide ou manquant.'], 422);
    }

    $token = Str::random(64);

    $this->db()->table($this->table)->where('id', $id)->update([
        'validation_token'            => $token,
        'validation_token_expires_at' => now()->addDays(7),
        'validation_status'           => 'email_sent',
        'interlocuteur_email'         => $email,
        'updated_at'                  => now(),
    ]);

    $lignes = $this->db()->table($this->tableLignes)
        ->where('fiche_id', $id)
        ->orderBy('ordre')
        ->get();

    $confirmUrl = route('public.outil-hierarchisation-risques.validate', ['token' => $token]);
    $auditeur   = $this->getAuditorName();

    try {
        Mail::send(
            'emails.hierarchisation-validation',
            compact('fiche', 'lignes', 'confirmUrl', 'auditeur'),
            fn($m) => $m->to($email)->subject("Validation hiérarchisation des risques - {$fiche->intitule}")
        );
        Log::info("[VI] Email envoyé à {$email} pour fiche_id={$id}");
    } catch (\Exception $e) {
        Log::error("[VI] Échec envoi email fiche_id={$id}: " . $e->getMessage());
        return response()->json(['error' => 'Échec envoi email : ' . $e->getMessage()], 500);
    }

    return response()->json(['success' => true, 'email' => $email]);
}

/**
 * Validation par token (route publique)
 */
public function validateByToken(Request $request, string $token)
{
    $fiche = $this->db()->table($this->table)
        ->where('validation_token', $token)
        ->where('validation_token_expires_at', '>', now())
        ->first();

    if (!$fiche) {
        return view('emails.validation-result', [
            'success' => false,
            'message' => 'Ce lien est invalide ou a expiré. Veuillez contacter l\'auditeur.',
        ]);
    }

    if ($fiche->validation_status === 'confirmed') {
        return view('emails.validation-result', [
            'success' => true,
            'message' => 'Cette hiérarchisation des risques a déjà été confirmée. Merci.',
            'fiche'   => $fiche,
        ]);
    }

    $this->db()->table($this->table)->where('id', $fiche->id)->update([
        'validation_status' => 'confirmed',
        'confirmed_at'      => now(),
        'updated_at'        => now(),
    ]);

    Log::info("[VI] Confirmation par token fiche_id={$fiche->id} email={$fiche->interlocuteur_email}");

    return view('emails.validation-result', [
        'success' => true,
        'message' => 'Merci ! La hiérarchisation des risques a été confirmée avec succès. Vous pouvez fermer cette page.',
        'fiche'   => $fiche,
    ]);
}

    /**
     * Confirmation par l'interlocuteur via le lien dans l'email.
     * Route publique (sans auth).
     */

    // ─────────────────────────────────────────────────────────────
    // DOCUMENTS
    // ─────────────────────────────────────────────────────────────

    public function uploadDocument(Request $request, int $id)
    {
        $fiche = $this->findOrFail($id);
        $request->validate([
            'document' => 'required|file|mimes:doc,docx,odt,rtf,pdf,xls,xlsx,csv|max:'.($this->config['max_file_size']/1024),
        ]);
        $file     = $request->file('document');
        $ext      = strtolower($file->getClientOriginalExtension());
        $fileName = 'hierarchisation_'.$id.'_'.time().'_'.Str::random(8).'.'.$ext;
        $path     = $file->storeAs('outil_hierarchisation/documents/'.$id, $fileName, 'tenant_uploads');

        $wordHtml = null;
        if (in_array($ext,['doc','docx','odt'])) {
            try { $wordHtml = $this->extractWordToHtml(Storage::disk('tenant_uploads')->path($path)); }
            catch (\Exception $e) { Log::warning('[VI upload] '.$e->getMessage()); }
        }

        $docId = $this->db()->table($this->tableDoc)->insertGetId([
            'fiche_id'         => $id,
            'mission_id'       => $fiche->mission_id,
            'assignment_id'    => $request->input('assignment_id'),
            'file_name'        => $fileName,
            'original_name'    => $file->getClientOriginalName(),
            'file_path'        => $path,
            'file_size'        => $file->getSize(),
            'mime_type'        => $file->getMimeType(),
            'file_extension'   => $ext,
            'word_content_html'=> $wordHtml,
            'status'           => 'draft',
            'uploaded_by'      => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->updateAttachedDocumentsJson($id);
        $doc = $this->db()->table($this->tableDoc)->where('id',$docId)->first();
        return response()->json(['success'=>true,'document'=>$doc]);
    }

    public function downloadDocument(int $ficheId, int $docId)
    {
        $doc = $this->db()->table($this->tableDoc)->where('id',$docId)->where('fiche_id',$ficheId)->first();
        if (!$doc || !Storage::disk('tenant_uploads')->exists($doc->file_path)) abort(404,'Document non trouvé');
        return Storage::disk('tenant_uploads')->download($doc->file_path, $doc->original_name);
    }

    public function validateDocument(Request $request, int $ficheId, int $docId)
    {
        $request->validate(['status'=>'required|in:validated,rejected','comment'=>'nullable|string']);
        $doc = $this->db()->table($this->tableDoc)->where('id',$docId)->where('fiche_id',$ficheId)->first();
        if (!$doc) return response()->json(['error'=>'Document non trouvé'],404);
        $this->db()->table($this->tableDoc)->where('id',$docId)->update([
            'status'             => $request->status,
            'validated_by'       => Auth::id(),
            'validated_at'       => now(),
            'validation_comment' => $request->comment,
            'updated_at'         => now(),
        ]);
        $this->updateAttachedDocumentsJson($ficheId);
        return response()->json(['success'=>true]);
    }

    public function deleteDocument(Request $request, int $ficheId, int $docId)
    {
        $doc = $this->db()->table($this->tableDoc)->where('id',$docId)->where('fiche_id',$ficheId)->first();
        if (!$doc) return response()->json(['error'=>'Document non trouvé'],404);
        if (Storage::disk('tenant_uploads')->exists($doc->file_path))
            Storage::disk('tenant_uploads')->delete($doc->file_path);
        $this->db()->table($this->tableDoc)->where('id',$docId)->update(['deleted_at'=>now()]);
        $this->updateAttachedDocumentsJson($ficheId);
        return response()->json(['success'=>true]);
    }

    // ─────────────────────────────────────────────────────────────
    // IA — Claude
    // ─────────────────────────────────────────────────────────────

    public function ia(Request $request, int $id)
    {
        $fiche        = $this->findOrFail($id);
        $anthropicKey = config('services.anthropic.key') ?: config('services.claude.key');

        if (empty($anthropicKey)) {
            return response()->json(['success'=>false,'error'=>'Clé Anthropic non configurée (services.anthropic.key).'],500);
        }

        $lignes = $this->db()->table($this->tableLignes)->where('fiche_id',$id)->orderBy('ordre')->get();
        if ($lignes->isEmpty()) {
            return response()->json(['success'=>false,'error'=>'Aucun risque à analyser. Ajoutez des risques d\'abord.'],422);
        }

        $prompt  = "Tu es un expert en audit interne IFACI. Analyse cette hiérarchisation des risques.\n\n";
        $prompt .= "Fiche : {$fiche->intitule}";
        if ($fiche->perimetre) $prompt .= "\nPérimètre : {$fiche->perimetre}";
        $prompt .= "\n\nRisques identifiés et évalués :\n";

        foreach ($lignes as $l) {
            $crit   = (int)$l->probabilite * (int)$l->impact;
            $niveau = $crit>=16?'CRITIQUE':($crit>=8?'ÉLEVÉ':($crit>=4?'MODÉRÉ':'FAIBLE'));
            $prompt .= "- {$l->libelle} (P:{$l->probabilite}, I:{$l->impact}, Score:{$crit}, Niveau:{$niveau})";
            if ($l->categorie)    $prompt .= " [Catégorie: {$l->categorie}]";
            if ($l->causes)       $prompt .= "\n  Causes: {$l->causes}";
            if ($l->consequences) $prompt .= "\n  Conséquences: {$l->consequences}";
            if ($l->traitement)   $prompt .= "\n  Traitement prévu: {$l->traitement}";
            $prompt .= "\n";
        }

        $prompt .= "\nRéponds UNIQUEMENT avec un JSON valide (sans markdown) contenant ces clés :\n";
        $prompt .= "synthese (string), risques_majeurs (array), points_forts (array), points_faibles (array), recommandations (array), score (number 0-10)";

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $anthropicKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 1500,
                'system'     => 'Tu es expert en audit interne IFACI. Retourne UNIQUEMENT un JSON valide, sans markdown, sans backticks.',
                'messages'   => [['role'=>'user','content'=>$prompt]],
            ]);

            if (!$response->successful()) {
                Log::error('[VI IA] Anthropic HTTP '.$response->status().' : '.$response->body());
                return response()->json(['success'=>false,'error'=>'Anthropic API error '.$response->status()],500);
            }

            $text   = collect($response->json()['content'] ?? [])->firstWhere('type','text')['text'] ?? '';
            $text   = trim(preg_replace(['/^```(?:json)?\s*/i','/\s*```$/i'],'',trim($text)));
            $result = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('[VI IA] JSON invalide : '.substr($text,0,300));
                return response()->json(['success'=>false,'error'=>'JSON invalide : '.json_last_error_msg()],500);
            }

            $result = array_merge([
                'synthese'=>'','risques_majeurs'=>[],'points_forts'=>[],
                'points_faibles'=>[],'recommandations'=>[],'score'=>0,
            ], $result);

            $result['score'] = is_numeric($result['score'])
                ? min(10, max(0, (float)$result['score'])) : 0;

            // Vérifier que ia_result existe comme colonne avant d'écrire
            try {
                $hasCol = \Illuminate\Support\Facades\Schema::connection($this->conn)
                    ->hasColumn($this->table, 'ia_result');
            } catch (\Exception $e) { $hasCol = true; }

            if ($hasCol) {
                $this->db()->table($this->table)->where('id',$id)->update([
                    'ia_result'       => json_encode($result, JSON_UNESCAPED_UNICODE),
                    'ia_score'        => $result['score'],
                    'ia_generated_at' => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json(['success'=>true,'ia_result'=>$result]);

        } catch (\Exception $e) {
            Log::error('[VI IA] '.$e->getMessage());
            return response()->json(['success'=>false,'error'=>$e->getMessage()],500);
        }
    }
}