<?php

namespace App\Http\Controllers\Auditor\Outils;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OutilEntretienController extends Controller
{
    use \App\Http\Controllers\Auditor\Outils\FicheTestContextTrait;

    private string $conn = 'tenant';

    private array $config = [
        'allowed_extensions' => ['doc', 'docx', 'odt', 'rtf', 'pdf', 'xls', 'xlsx', 'csv'],
        'max_file_size'      => 10485760, // 10 Mo
    ];

    // ─────────────────────────────────────────────────────────────────
    // HELPERS INTERNES
    // ─────────────────────────────────────────────────────────────────

    private function db(): \Illuminate\Database\Connection
    {
        return DB::connection($this->conn);
    }

    private function findOrFail(int $id): object
    {
        $record = $this->db()->table('outil_entretiens')->where('id', $id)->first();
        abort_if(!$record, 404, 'Grille d\'entretien introuvable.');
        return $record;
    }

    private function getAuditorRole(): string
    {
        return session('auditor_role', 'AS');
    }

    private function getAuditorName(): string
    {
        $user = Auth::user();
        return $user ? ($user->name ?? $user->email) : 'Auditeur';
    }

    /**
     * Construit le contexte mission/procédure depuis les query params.
     * Récupère aussi les libellés textuels du test et de la procédure
     * passés par FicheTest.vue → ouvrirOutil().
     */
    private function getMissionContext(Request $request): array
    {
        $missionId     = $request->query('mission_id');
        $assignmentId  = $request->query('assignment_id');
        $procedureCode = $request->query('procedure_code');
        $testRef       = $request->query('test_ref');
        $ficheTestId   = $request->query('fiche_test_id');

        // Libellés textuels passés par FicheTest.vue
        $libelleTest   = $request->query('libelle_test');
        $libelleProc   = $request->query('libelle_proc');
        $objectifAudit = $request->query('objectif_audit');

        $context = [];

        if ($missionId) {
            $mission                    = $this->db()->table('missions')->where('id', $missionId)->first();
            $context['mission_id']      = $missionId;
            $context['mission_libelle'] = $mission->title ?? $mission->libelle ?? null;
        }

        if ($assignmentId)  $context['assignment_id']  = $assignmentId;
        if ($procedureCode) $context['procedure_code'] = $procedureCode;
        if ($testRef)       $context['test_ref']       = $testRef;
        if ($ficheTestId)   $context['fiche_test_id']  = $ficheTestId;

        // Libellés textuels prioritaires (passés directement en query)
        if ($libelleTest)   $context['libelle_test']   = $libelleTest;
        if ($libelleProc)   $context['libelle_proc']   = $libelleProc;
        if ($objectifAudit) $context['objectif_audit'] = $objectifAudit;

        // Fallback BD : mission_phase_fiche_test (table réelle, pas fiche_tests)
        if ($ficheTestId && (!$objectifAudit || !$libelleTest)) {
            try {
                $ficheTest = $this->db()->table('mission_phase_fiche_test')
                    ->where('id', $ficheTestId)
                    ->first();

                if ($ficheTest && !empty($ficheTest->outils_data)) {
                    $outilsData = is_string($ficheTest->outils_data)
                        ? json_decode($ficheTest->outils_data, true)
                        : (array) $ficheTest->outils_data;

                    foreach ((array) $outilsData as $item) {
                        if (!empty($item['_key']) && $testRef && str_contains($item['_key'], $testRef)) {
                            if (empty($context['objectif_audit']) && !empty($item['objectif_audit'])) {
                                $context['objectif_audit'] = $item['objectif_audit'];
                            }
                            if (empty($context['libelle_test']) && !empty($item['libelle_test'])) {
                                $context['libelle_test'] = $item['libelle_test'];
                            }
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('[OutilEntretien] getMissionContext fallback : ' . $e->getMessage());
            }
        }

        return $context;
    }

    private function updateAttachedDocumentsJson(int $entretienId): void
    {
        $docs = $this->db()->table('outil_entretien_documents')
            ->where('entretien_id', $entretienId)
            ->whereNull('deleted_at')
            ->select('id', 'original_name', 'file_extension', 'file_size', 'status', 'created_at', 'uploaded_by')
            ->get();

        $this->db()->table('outil_entretiens')
            ->where('id', $entretienId)
            ->update(['attached_documents' => json_encode($docs)]);
    }

    private function extractEmailFromInterlocuteur(?string $interlocuteur): ?string
    {
        if (!$interlocuteur) return null;
        preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $interlocuteur, $matches);
        return $matches[0] ?? null;
    }

    private function docBaseUrl(string $routeName, int $entretienId): string
    {
        return route($routeName, [$entretienId, '__DOC__']);
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS WORD — Extraction
    // ─────────────────────────────────────────────────────────────────

    private function extractWordToHtml(string $filePath): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        $html    = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $html .= $this->renderElementToHtml($element);
            }
        }

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
                    foreach ($cell->getElements() as $cellEl) {
                        $html .= $this->renderElementToHtml($cellEl);
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
            return $html;
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
            $text  = $this->extractInlineHtml($element->getTextObject());
            $depth = $element->getDepth();
            $pad   = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
            return '<p style="margin:2px 0">' . $pad . '• ' . $text . '</p>';
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) {
            return '<br>';
        }

        if (
            $element instanceof \PhpOffice\PhpWord\Element\Paragraph ||
            $element instanceof \PhpOffice\PhpWord\Element\TextRun
        ) {
            $content = '';
            foreach ($element->getElements() as $child) {
                $content .= $this->renderElementToHtml($child);
            }

            $style    = $element->getParagraphStyle();
            $cssStyle = 'margin:4px 0;';
            if ($style instanceof \PhpOffice\PhpWord\Style\Paragraph) {
                $align = $style->getAlignment();
                if ($align === \PhpOffice\PhpWord\SimpleType\Jc::CENTER)    $cssStyle .= 'text-align:center;';
                elseif ($align === \PhpOffice\PhpWord\SimpleType\Jc::RIGHT) $cssStyle .= 'text-align:right;';
                elseif ($align === \PhpOffice\PhpWord\SimpleType\Jc::BOTH)  $cssStyle .= 'text-align:justify;';
            }

            return trim($content) !== ''
                ? '<p style="' . $cssStyle . '">' . $content . '</p>'
                : '<p style="margin:2px 0">&nbsp;</p>';
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            return $this->extractInlineHtml($element);
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\Image) {
            try {
                $imgSrc = $element->getImageStringData(true);
                if ($imgSrc) {
                    $type = $element->getImageType();
                    $w    = $element->getWidth()  ? 'width:' . $element->getWidth() . 'px;'   : 'max-width:100%;';
                    $h    = $element->getHeight() ? 'height:' . $element->getHeight() . 'px;' : '';
                    return '<img src="data:' . $type . ';base64,' . $imgSrc
                        . '" style="' . $w . $h . 'display:block;margin:4px 0"/>';
                }
            } catch (\Exception $e) {
                // silencieux
            }
            return '<span style="color:#94a3b8;font-style:italic">[Image]</span>';
        }

        if (method_exists($element, 'getStyle') && method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text) && trim($text) !== '') {
                return '<p style="margin:4px 0">' . e($text) . '</p>';
            }
        }

        return '';
    }

    private function extractInlineHtml($element): string
    {
        $rawText = $element->getText();
        if (!is_string($rawText) || trim($rawText) === '') return '';

        $text      = e($rawText);
        $fontStyle = $element->getFontStyle();

        if ($fontStyle instanceof \PhpOffice\PhpWord\Style\Font) {
            $css = '';

            $bold = method_exists($fontStyle, 'isBold')
                ? $fontStyle->isBold()
                : (method_exists($fontStyle, 'getBold') ? $fontStyle->getBold() : false);

            $italic = method_exists($fontStyle, 'isItalic')
                ? $fontStyle->isItalic()
                : (method_exists($fontStyle, 'getItalic') ? $fontStyle->getItalic() : false);

            $underline = method_exists($fontStyle, 'getUnderline')
                ? $fontStyle->getUnderline()
                : null;

            if ($bold)      $css .= 'font-weight:bold;';
            if ($italic)    $css .= 'font-style:italic;';
            if ($underline && $underline !== \PhpOffice\PhpWord\Style\Font::UNDERLINE_NONE) {
                $css .= 'text-decoration:underline;';
            }

            $color = method_exists($fontStyle, 'getColor') ? $fontStyle->getColor() : null;
            if ($color && $color !== 'auto' && strlen((string)$color) >= 6) {
                $css .= 'color:#' . ltrim((string)$color, '#') . ';';
            }

            $size = method_exists($fontStyle, 'getSize') ? $fontStyle->getSize() : null;
            if ($size) $css .= 'font-size:' . round((float)$size * 1.33) . 'px;';

            if ($css) {
                return '<span style="' . $css . '">' . $text . '</span>';
            }
        }

        return $text;
    }

    private function htmlToWordFile(string $html): string
    {
        $html = preg_replace('/<br\s*>/i', '<br/>', $html);
        $html = preg_replace('/<meta[^>]*>/i', '', $html);
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
        $dom->loadHTML($fullHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $bodyContent = '';
        $body = $dom->getElementsByTagName('body');
        if ($body && $body->length > 0) {
            $bodyNode = $body->item(0);
            if ($bodyNode) {
                foreach ($bodyNode->childNodes as $node) {
                    if ($node) $bodyContent .= $dom->saveHTML($node);
                }
            }
        }

        if (empty(trim($bodyContent))) {
            $bodyContent = '<p>Document vide</p>';
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginLeft' => 1440, 'marginRight' => 1440,
            'marginTop'  => 1440, 'marginBottom' => 1440,
        ]);

        try {
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $bodyContent, false, false);
        } catch (\Exception $e) {
            $section->addText(strip_tags($bodyContent));
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'wordgen_') . '.docx';
        $writer   = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }

    // ─────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $missionId = $request->query('mission_id');

        $query = $this->db()->table('outil_entretiens as oe')
            ->leftJoin('missions as m', 'm.id', '=', 'oe.mission_id')
            ->leftJoin('users as u', 'u.id', '=', 'oe.created_by')
            ->select(
                'oe.*',
                'm.code as mission_ref',
                'm.title as mission_libelle',
                DB::raw("CONCAT(u.name, '') as auteur")
            )
            ->orderByDesc('oe.created_at');

        if ($missionId) {
            $query->where('oe.mission_id', $missionId);
        }

        $entretiens = $query->paginate(15)->withQueryString();

        $missions = $this->db()->table('missions')
            ->select('id', 'code as reference', 'title as libelle')
            ->orderBy('code')
            ->get();

        $ftCtx = $this->ficheTestContext($request);

        // Si on arrive d'une fiche test, rediriger vers l'entretien existant
        if ($ftCtx['has_context']) {
            $existingId = $this->findExistingOutilId(
                $ftCtx['fiche_test_id'], 'I', $ftCtx['test_ref'], $ftCtx['proc_idx']
            );
            if ($existingId) {
                return redirect()->route(
                    'auditor.ac.outil-entretien.edit',
                    [$existingId] + $request->query()
                );
            }
        }

        return Inertia::render('dashboards/Auditor/Outils/OutilEntretien', [
            'entretiens'     => $entretiens,
            'missions'       => $missions,
            'filters'        => ['mission_id' => $missionId],
            'urlStore'       => route('auditor.ac.outil-entretien.store') . ($ftCtx['query_string'] ?? ''),
            'urlUpdate'      => null,
            'backUrl'        => $ftCtx['back_url'] ?? '/',
            'missionContext' => $ftCtx['missionContext'] ?? $this->getMissionContext($request),
            'auditorRole'    => $this->getAuditorRole(),
            'auditeurNom'    => $this->getAuditorName(),
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $entretien = $this->findOrFail($id);

        $documents = $this->db()->table('outil_entretien_documents')
            ->where('entretien_id', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        $questions = $this->db()->table('outil_entretien_questions')
            ->where('entretien_id', $id)
            ->orderBy('ordre')
            ->get();

        $missions = $this->db()->table('missions')
            ->select('id', 'code as reference', 'title as libelle')
            ->orderBy('code')
            ->get();

        $ftCtx         = $this->ficheTestContext($request);
        $missionContext = array_merge(
            $ftCtx['missionContext'] ?? [],
            $this->getMissionContext($request)
        );

        return Inertia::render('dashboards/Auditor/Outils/OutilEntretien', [
            'entretien'              => $entretien,
            'documents'              => $documents,
            'questions'              => $questions,
            'missions'               => $missions,
            'urlStore'               => route('auditor.ac.outil-entretien.store') . ($ftCtx['query_string'] ?? ''),
            'urlUpdate'              => route('auditor.ac.outil-entretien.update', $id) . (isset($ftCtx['query_string']) && $ftCtx['query_string'] ? $ftCtx['query_string'] : ''),
            'urlSoumettre'           => route('auditor.ac.outil-entretien.soumettre', $id),
            'urlValider'             => route('auditor.ac.outil-entretien.valider', $id),
            'urlIa'                  => route('auditor.ac.outil-entretien.ia', $id),
            'urlUploadDoc'           => route('auditor.ac.outil-entretien.upload-doc', $id),
            'urlDocuments'           => route('auditor.ac.outil-entretien.documents', $id),
            'urlPreviewDocBase'      => $this->docBaseUrl('auditor.ac.outil-entretien.preview-doc', $id),
            'urlDownloadDocBase'     => $this->docBaseUrl('auditor.ac.outil-entretien.download-doc', $id),
            'urlEditDocBase'         => $this->docBaseUrl('auditor.ac.outil-entretien.edit-doc', $id),
            'urlSaveDocBase'         => $this->docBaseUrl('auditor.ac.outil-entretien.save-doc', $id),
            'urlLoadExcelBase'       => $this->docBaseUrl('auditor.ac.outil-entretien.load-excel-doc', $id),
            'urlSaveExcelBase'       => $this->docBaseUrl('auditor.ac.outil-entretien.save-excel-doc', $id),
            'urlValidateDocBase'     => $this->docBaseUrl('auditor.ac.outil-entretien.validate-doc', $id),
            'urlDeleteDocBase'       => $this->docBaseUrl('auditor.ac.outil-entretien.delete-doc', $id),
            'urlSendValidationEmail' => route('auditor.ac.outil-entretien.send-validation-email', $id),
            'backUrl'                => $request->query('back', '/'),
            'missionContext'         => $missionContext,
            'auditorRole'            => $this->getAuditorRole(),
            'auditeurNom'            => $this->getAuditorName(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mission_id'           => 'required|integer',
            'assignment_id'        => 'nullable|integer',
            'procedure_code'       => 'nullable|string|max:80',
            'test_ref'             => 'nullable|string|max:80',
            'intitule'             => 'required|string|max:255',
            'objectif'             => 'nullable|string',
            'interlocuteur'        => 'nullable|string|max:255',
            'fonction'             => 'nullable|string|max:255',
            'date_entretien'       => 'nullable|date',
            'lieu'                 => 'nullable|string|max:255',
            'synthese'             => 'nullable|string',
            'sig_auditeur'         => 'nullable|string',
            'sig_interlocuteur'    => 'nullable|string',
            'questions'            => 'nullable|array',
            'questions.*.libelle'  => 'nullable|string',
            'questions.*.reponse'  => 'nullable|string',
            'questions.*.note'     => 'nullable|string',
            'questions.*.type'     => 'nullable|string',
        ]);

        $code = 'ENT-' . strtoupper(Str::random(6));
        $id   = $this->db()->table('outil_entretiens')->insertGetId([
            'mission_id'        => $validated['mission_id'],
            'assignment_id'     => $validated['assignment_id'] ?? null,
            'procedure_code'    => $validated['procedure_code'] ?? null,
            'test_ref'          => $validated['test_ref'] ?? null,
            'code'              => $code,
            'intitule'          => $validated['intitule'],
            'objectif'          => $validated['objectif'] ?? null,
            'interlocuteur'     => $validated['interlocuteur'] ?? null,
            'fonction'          => $validated['fonction'] ?? null,
            'date_entretien'    => $validated['date_entretien'] ?? null,
            'lieu'              => $validated['lieu'] ?? null,
            'synthese'          => $validated['synthese'] ?? null,
            'sig_auditeur'      => $validated['sig_auditeur'] ?? $this->getAuditorName(),
            'sig_interlocuteur' => $validated['sig_interlocuteur'] ?? null,
            'statut'            => 'draft',
            'validation_status' => 'pending',
            'created_by'        => Auth::id(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if (!empty($validated['questions'])) {
            foreach ($validated['questions'] as $idx => $q) {
                // Ignorer les questions sans libellé
                if (empty(trim($q['libelle'] ?? ''))) continue;
                $this->db()->table('outil_entretien_questions')->insert([
                    'entretien_id' => $id,
                    'type'         => $q['type'] ?? 'Ouverte',
                    'libelle'      => $q['libelle'],
                    'reponse'      => $q['reponse'] ?? null,
                    'note'         => $q['note'] ?? null,
                    'ordre'        => $idx + 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        $this->saveFicheTestLinkIfPresent($request, $id, 'I', 'outil_entretiens');

        if ($request->expectsJson()) {
            $record = $this->db()->table('outil_entretiens')->where('id', $id)->first();
            return response()->json(['success' => true, 'record' => $record]);
        }

        return redirect()
            ->route('auditor.ac.outil-entretien.edit', $id)
            ->with('success', 'Grille d\'entretien créée.');
    }

    public function update(Request $request, int $id)
    {
        $entretien = $this->findOrFail($id);
        abort_if(
            in_array($entretien->statut, ['validated', 'in_review'])
            && !in_array($this->getAuditorRole(), ['DM', 'CM']),
            403
        );

        $validated = $request->validate([
            'intitule'             => 'required|string|max:255',
            'objectif'             => 'nullable|string',
            'interlocuteur'        => 'nullable|string|max:255',
            'fonction'             => 'nullable|string|max:255',
            'date_entretien'       => 'nullable|date',
            'lieu'                 => 'nullable|string|max:255',
            'synthese'             => 'nullable|string',
            'sig_auditeur'         => 'nullable|string',
            'sig_interlocuteur'    => 'nullable|string',
            'questions'            => 'nullable|array',
            'questions.*.libelle'  => 'nullable|string',
            'questions.*.reponse'  => 'nullable|string',
            'questions.*.note'     => 'nullable|string',
            'questions.*.type'     => 'nullable|string',
        ]);

        $this->db()->table('outil_entretiens')->where('id', $id)->update([
            'intitule'          => $validated['intitule'],
            'objectif'          => $validated['objectif'] ?? null,
            'interlocuteur'     => $validated['interlocuteur'] ?? null,
            'fonction'          => $validated['fonction'] ?? null,
            'date_entretien'    => $validated['date_entretien'] ?? null,
            'lieu'              => $validated['lieu'] ?? null,
            'synthese'          => $validated['synthese'] ?? null,
            'sig_auditeur'      => $validated['sig_auditeur'] ?? $this->getAuditorName(),
            'sig_interlocuteur' => $validated['sig_interlocuteur'] ?? null,
            'updated_at'        => now(),
        ]);

        // Réécrire toutes les questions
        $this->db()->table('outil_entretien_questions')->where('entretien_id', $id)->delete();
        if (!empty($validated['questions'])) {
            foreach ($validated['questions'] as $idx => $q) {
                // Ignorer les questions sans libellé
                if (empty(trim($q['libelle'] ?? ''))) continue;
                $this->db()->table('outil_entretien_questions')->insert([
                    'entretien_id' => $id,
                    'type'         => $q['type'] ?? 'Ouverte',
                    'libelle'      => $q['libelle'],
                    'reponse'      => $q['reponse'] ?? null,
                    'note'         => $q['note'] ?? null,
                    'ordre'        => $idx + 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        if ($request->expectsJson()) {
            $record = $this->db()->table('outil_entretiens')->where('id', $id)->first();
            return response()->json(['success' => true, 'record' => $record]);
        }

        return back()->with('success', 'Grille mise à jour.');
    }

    public function destroy(int $id)
    {
        $this->findOrFail($id);
        $this->db()->table('outil_entretien_questions')->where('entretien_id', $id)->delete();
        $this->db()->table('outil_entretien_documents')->where('entretien_id', $id)->delete();
        $this->db()->table('outil_entretiens')->where('id', $id)->delete();
        return redirect()
            ->route('auditor.ac.outil-entretien.index')
            ->with('success', 'Grille supprimée.');
    }

    // ─────────────────────────────────────────────────────────────────
    // WORKFLOW
    // ─────────────────────────────────────────────────────────────────

    public function soumettre(Request $request, int $id)
    {
        $entretien = $this->findOrFail($id);
        abort_if($entretien->statut !== 'draft', 422, 'Seul un brouillon peut être soumis.');

        $this->db()->table('outil_entretiens')->where('id', $id)->update([
            'statut'       => 'in_review',
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
            'updated_at'   => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Grille soumise pour validation.');
    }

    public function valider(Request $request, int $id)
    {
        $this->findOrFail($id);

        $validated = $request->validate([
            'decision'    => 'required|in:validated,rejected',
            'commentaire' => 'nullable|string',
        ]);

        $entretien = $this->db()->table('outil_entretiens')->where('id', $id)->first();
        abort_if($entretien->statut !== 'in_review', 422, 'Document non soumis.');

        $newStatut = $validated['decision'];
        $this->db()->table('outil_entretiens')->where('id', $id)->update([
            'statut'          => $newStatut,
            'validation_note' => $validated['commentaire'] ?? null,
            'validated_by'    => Auth::id(),
            'validated_at'    => now(),
            'updated_at'      => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'statut' => $newStatut]);
        }
        return back()->with('success', $newStatut === 'validated' ? 'Grille validée.' : 'Grille rejetée.');
    }

    // ─────────────────────────────────────────────────────────────────
    // EMAIL DE VALIDATION INTERLOCUTEUR
    // ─────────────────────────────────────────────────────────────────

   public function sendValidationEmail(Request $request, int $id)
{
    $entretien = $this->findOrFail($id);
    
    // 🔽 SUPPRIMEZ ou COMMENTEZ cette ligne
    // abort_if($entretien->statut !== 'validated', 422, 'Seule une fiche validée peut être envoyée.');

    $email = $request->input('email')
        ?: $this->extractEmailFromInterlocuteur($entretien->interlocuteur);

    if (!$email) {
        return response()->json([
            'error' => 'Aucun email valide trouvé pour l\'interlocuteur.',
        ], 422);
    }

    $token = Str::random(64);
    $this->db()->table('outil_entretiens')->where('id', $id)->update([
        'validation_token'            => $token,
        'validation_token_expires_at' => now()->addDays(7),
        'validation_status'           => 'email_sent',
        'interlocuteur_email'         => $email,
        'updated_at'                  => now(),
    ]);

    // Récupérer les questions avec réponses
    $questions = $this->db()->table('outil_entretien_questions')
        ->where('entretien_id', $id)
        ->orderBy('ordre')
        ->get();

    $confirmUrl = route('public.outil-entretien.validate', ['token' => $token]);
    $auditeur   = $this->getAuditorName();

    Mail::send(
        'emails.entretien-validation',
        compact('entretien', 'questions', 'confirmUrl', 'auditeur'),
        fn ($m) => $m->to($email)->subject("Validation entretien - {$entretien->intitule}")
    );

    return response()->json(['success' => true, 'email' => $email]);
}
    public function validateByToken(Request $request, string $token)
    {
        $entretien = $this->db()->table('outil_entretiens')
            ->where('validation_token', $token)
            ->where('validation_token_expires_at', '>', now())
            ->first();

        if (!$entretien) {
            return view('emails.validation-result', [
                'success' => false,
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        $this->db()->table('outil_entretiens')->where('id', $entretien->id)->update([
            'validation_status' => 'confirmed',
            'confirmed_at'      => now(),
            'updated_at'        => now(),
        ]);

        return view('emails.validation-result', [
            'success' => true,
            'message' => 'Merci, vos informations ont été confirmées.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // DOCUMENTS
    // ─────────────────────────────────────────────────────────────────

    public function uploadDocument(Request $request, int $id)
    {
        $entretien = $this->findOrFail($id);

        $request->validate([
            'document' => 'required|file|mimes:doc,docx,odt,rtf,pdf,xls,xlsx,csv|max:'
                . ($this->config['max_file_size'] / 1024),
        ]);

        $file         = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $extension    = strtolower($file->getClientOriginalExtension());
        $size         = $file->getSize();

        $fileName = 'entretien_' . $id . '_' . time() . '_' . Str::random(8) . '.' . $extension;
        $path     = $file->storeAs(
            'outil_entretiens/documents/' . $id,
            $fileName,
            'tenant_uploads'
        );

        // Extraction Word → HTML si applicable
        $wordContentHtml = null;
        if (in_array($extension, ['doc', 'docx', 'odt'])) {
            try {
                $fullPath        = Storage::disk('tenant_uploads')->path($path);
                $wordContentHtml = $this->extractWordToHtml($fullPath);
            } catch (\Exception $e) {
                \Log::warning('[uploadDocument] Extraction Word échouée : ' . $e->getMessage());
            }
        }

        $docId = $this->db()->table('outil_entretien_documents')->insertGetId([
            'entretien_id'      => $id,
            'mission_id'        => $entretien->mission_id,
            'assignment_id'     => $request->input('assignment_id'),
            'file_name'         => $fileName,
            'original_name'     => $originalName,
            'file_path'         => $path,
            'file_size'         => $size,
            'mime_type'         => $file->getMimeType(),
            'file_extension'    => $extension,
            'word_content_html' => $wordContentHtml,
            'status'            => 'draft',
            'uploaded_by'       => Auth::id(),
            'created_at'        => now(),
        ]);

        $this->updateAttachedDocumentsJson($id);

        $doc = $this->db()->table('outil_entretien_documents')->where('id', $docId)->first();
        return response()->json(['success' => true, 'document' => $doc]);
    }

    public function getDocuments(int $id)
    {
        $docs = $this->db()->table('outil_entretien_documents')
            ->where('entretien_id', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['documents' => $docs]);
    }

    public function deleteDocument(Request $request, int $entretienId, int $docId)
    {
        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        if (Storage::disk('tenant_uploads')->exists($doc->file_path)) {
            Storage::disk('tenant_uploads')->delete($doc->file_path);
        }

        $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->update(['deleted_at' => now()]);

        $this->updateAttachedDocumentsJson($entretienId);

        return response()->json(['success' => true]);
    }

    public function validateDocument(Request $request, int $entretienId, int $docId)
    {
        $request->validate([
            'status'  => 'required|in:validated,rejected',
            'comment' => 'nullable|string',
        ]);

        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        $this->db()->table('outil_entretien_documents')->where('id', $docId)->update([
            'status'             => $request->status,
            'validated_by'       => Auth::id(),
            'validated_at'       => now(),
            'validation_comment' => $request->comment,
        ]);

        $this->updateAttachedDocumentsJson($entretienId);

        return response()->json(['success' => true]);
    }

    public function downloadDocument(int $entretienId, int $docId)
    {
        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc || !Storage::disk('tenant_uploads')->exists($doc->file_path)) {
            abort(404, 'Document non trouvé');
        }

        return Storage::disk('tenant_uploads')->download($doc->file_path, $doc->original_name);
    }

    public function previewDocument(int $entretienId, int $docId)
    {
        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        if ($doc->word_content_html) {
            return response()->json([
                'html'          => $doc->word_content_html,
                'original_name' => $doc->original_name,
            ]);
        }

        $fullPath = Storage::disk('tenant_uploads')->path($doc->file_path);
        try {
            $html = $this->extractWordToHtml($fullPath);
            $this->db()->table('outil_entretien_documents')
                ->where('id', $docId)
                ->update(['word_content_html' => $html]);
            return response()->json(['html' => $html, 'original_name' => $doc->original_name]);
        } catch (\Exception $e) {
            return response()->json(['download_only' => true, 'original_name' => $doc->original_name]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // ÉDITION WORD
    // ─────────────────────────────────────────────────────────────────

    public function editDocument(int $entretienId, int $docId)
    {
        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        if (!in_array(strtolower($doc->file_extension), ['doc', 'docx'])) {
            return response()->json(['error' => 'Seuls les fichiers .doc/.docx sont éditables.'], 422);
        }

        $filePath = Storage::disk('tenant_uploads')->path($doc->file_path);
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Fichier introuvable sur le serveur.'], 404);
        }

        // Détection contenu placeholder (non extrait)
        $isPlaceholder = !empty($doc->word_content_html) && (
            str_contains($doc->word_content_html, 'word-preview') ||
            str_contains($doc->word_content_html, 'Aperçu non disponible') ||
            str_contains($doc->word_content_html, '📄 Document Word')
        );

        if (!empty($doc->word_content_html) && !$isPlaceholder) {
            return response()->json([
                'html'          => $doc->word_content_html,
                'original_name' => $doc->original_name,
                'doc_id'        => $docId,
                'cached'        => true,
            ]);
        }

        try {
            $html = $this->extractWordToHtml($filePath);
            $this->db()->table('outil_entretien_documents')
                ->where('id', $docId)
                ->update(['word_content_html' => $html, 'updated_at' => now()]);

            return response()->json([
                'html'          => $html,
                'original_name' => $doc->original_name,
                'doc_id'        => $docId,
                'cached'        => false,
            ]);
        } catch (\Exception $e) {
            \Log::error('[editDocument] ' . $e->getMessage());
            return response()->json(['error' => 'Impossible de lire le document : ' . $e->getMessage()], 500);
        }
    }

    public function saveDocument(Request $request, int $entretienId, int $docId)
    {
        $request->validate(['html' => 'required|string']);

        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document introuvable'], 404);
        }

        try {
            $html    = $request->input('html') ?: '<p>Document vide</p>';
            $cleaned = preg_replace('/<br\s*>/i', '<br/>', $html);

            $tempFile = $this->htmlToWordFile($cleaned);
            if (!file_exists($tempFile)) {
                throw new \Exception('Impossible de générer le fichier Word');
            }

            Storage::disk('tenant_uploads')->put($doc->file_path, file_get_contents($tempFile));
            @unlink($tempFile);

            $this->db()->table('outil_entretien_documents')->where('id', $docId)->update([
                'word_content_html' => $html,
                'updated_at'        => now(),
            ]);

            $this->updateAttachedDocumentsJson($entretienId);

            return response()->json(['success' => true, 'message' => 'Document sauvegardé.']);
        } catch (\Exception $e) {
            \Log::error('[saveDocument] ' . $e->getMessage());
            return response()->json(['error' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // EXCEL — Load & Save
    // ─────────────────────────────────────────────────────────────────

    public function loadExcelDocument(int $entretienId, int $docId)
    {
        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        if (!in_array(strtolower($doc->file_extension), ['xls', 'xlsx', 'csv'])) {
            return response()->json(['error' => 'Seuls les fichiers Excel sont supportés'], 422);
        }

        $filePath = Storage::disk('tenant_uploads')->path($doc->file_path);
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Fichier introuvable'], 404);
        }

        try {
            $mimeTypes = [
                'xls'  => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv'  => 'text/csv',
            ];
            $ext         = strtolower($doc->file_extension);
            $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

            return response(file_get_contents($filePath), 200)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', 'inline; filename="' . $doc->original_name . '"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur de lecture : ' . $e->getMessage()], 500);
        }
    }

    public function saveExcelDocument(Request $request, int $entretienId, int $docId)
    {
        $doc = $this->db()->table('outil_entretien_documents')
            ->where('id', $docId)
            ->where('entretien_id', $entretienId)
            ->first();

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        if (!$request->hasFile('document')) {
            return response()->json(['error' => 'Aucun fichier fourni'], 422);
        }

        try {
            $request->validate([
                'document' => 'file|mimes:xls,xlsx,csv|max:' . ($this->config['max_file_size'] / 1024),
            ]);

            $file = $request->file('document');
            Storage::disk('tenant_uploads')->put($doc->file_path, file_get_contents($file->getRealPath()));

            $this->db()->table('outil_entretien_documents')
                ->where('id', $docId)
                ->update(['updated_at' => now()]);

            $this->updateAttachedDocumentsJson($entretienId);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('[saveExcelDocument] ' . $e->getMessage());
            return response()->json(['error' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // IA — Mistral
    // ─────────────────────────────────────────────────────────────────

    public function ia(Request $request, int $id)
    {
        $entretien = $this->findOrFail($id);

        // ── 1. Vérification clé Mistral ───────────────────────────────
        $mistralKey = config('services.mistral.key');
        if (empty($mistralKey)) {
            return response()->json([
                'success' => false,
                'error'   => 'Clé Mistral non configurée. Ajoutez MISTRAL_API_KEY dans votre .env et services.php.',
            ], 500);
        }

        // ── 2. Vérification colonne ia_result (migration manquante ?) ──
        try {
            $hasColumn = \Illuminate\Support\Facades\Schema::connection($this->conn)
                ->hasColumn('outil_entretiens', 'ia_result');
        } catch (\Exception $e) {
            $hasColumn = false;
        }

        // ── 3. Construction payload ────────────────────────────────────
        $questions = $this->db()->table('outil_entretien_questions')
            ->where('entretien_id', $id)
            ->orderBy('ordre')
            ->get();

        $payload = [
            'intitule'       => $entretien->intitule,
            'objectif'       => $entretien->objectif,
            'interlocuteur'  => $entretien->interlocuteur,
            'fonction'       => $entretien->fonction,
            'date_entretien' => $entretien->date_entretien,
            'synthese'       => $entretien->synthese,
            'questions'      => $questions->map(fn ($q) => [
                'type'    => $q->type,
                'libelle' => $q->libelle,
                'reponse' => $q->reponse ?? '',
                'note'    => $q->note ?? '',
            ])->toArray(),
        ];

        $prompt = "Tu es un expert en audit interne IFACI. Analyse cette grille d'entretien d'audit.\n\n"
            . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nRéponds UNIQUEMENT avec un objet JSON valide (pas de markdown, pas de texte avant ou après) "
            . "contenant exactement ces clés :\n"
            . "- synthese : string (résumé global)\n"
            . "- points_forts : array of strings\n"
            . "- points_faibles : array of strings\n"
            . "- risques : array of strings\n"
            . "- recommandations : array of strings\n"
            . "- score : number entre 0 et 10";

        // ── 4. Appel Mistral ───────────────────────────────────────────
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $mistralKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => 'mistral-large-latest',
                'max_tokens'  => 2000,
                'temperature' => 0.2,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es expert audit interne IFACI. Retourne UNIQUEMENT un JSON valide, sans markdown, sans backticks, sans texte avant ou après.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            // ── 5. Vérification réponse HTTP ───────────────────────────
            if (!$response->successful()) {
                $body = $response->body();
                \Log::error('[IA Entretien] Mistral HTTP ' . $response->status() . ' : ' . $body);
                return response()->json([
                    'success' => false,
                    'error'   => 'Mistral API error ' . $response->status() . ' : ' . substr($body, 0, 300),
                ], 500);
            }

            // ── 6. Extraction et nettoyage du JSON ────────────────────
            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? '';

            if (empty(trim($text))) {
                return response()->json(['success' => false, 'error' => 'Réponse vide de Mistral.'], 500);
            }

            // Nettoyer les éventuels backticks markdown
            $text = trim($text);
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/i', '', $text);
            $text = trim($text);

            $result = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('[IA Entretien] JSON invalide : ' . substr($text, 0, 500));
                return response()->json([
                    'success' => false,
                    'error'   => 'JSON invalide retourné par Mistral : ' . json_last_error_msg(),
                    'raw'     => substr($text, 0, 300),
                ], 500);
            }

            // Valeurs par défaut si clés manquantes
            $result = array_merge([
                'synthese'        => '',
                'points_forts'    => [],
                'points_faibles'  => [],
                'risques'         => [],
                'recommandations' => [],
                'score'           => 0,
            ], $result);

            // Normaliser le score en nombre
            $result['score'] = is_numeric($result['score'])
                ? min(10, max(0, (float) $result['score']))
                : 0;

            // ── 7. Persistance (si colonne présente) ──────────────────
            if ($hasColumn) {
                $this->db()->table('outil_entretiens')->where('id', $id)->update([
                    'ia_result'  => json_encode($result, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'ia_result' => $result]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('[IA Entretien] Connexion Mistral impossible : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Impossible de joindre Mistral (timeout ou réseau) : ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            \Log::error('[IA Entretien] Exception : ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}