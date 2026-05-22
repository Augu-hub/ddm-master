<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════
 * RccAiGeneratorService — v3
 * ════════════════════════════════════════════════════════════════════════
 *
 * Génère les critères RCC en analysant RÉELLEMENT le document guide
 * uploadé (PDF/Word/Excel/TXT).
 *
 * Le responsable de chaque critère est choisi parmi les FONCTIONS
 * de l'assignment (table assignment_functions → functions), pas les auditeurs.
 *
 * Flux :
 *   1. Extraire le texte brut du guide (PDF/DOCX/XLSX/TXT)
 *   2. Construire un prompt avec le texte extrait + fonctions disponibles
 *   3. Appeler Mistral → JSON des critères complets
 *   4. Mapper responsable_fonction_id depuis le libellé suggéré par l'IA
 * ════════════════════════════════════════════════════════════════════════
 */
class RccAiGeneratorService
{
    private const API_URL    = 'https://api.mistral.ai/v1/chat/completions';
    private const MODEL      = 'mistral-small-latest';
    private const MAX_TOKENS = 4000;
    // Limite de caractères extraits du guide envoyés à l'IA
    private const GUIDE_CHAR_LIMIT = 8000;

    // ──────────────────────────────────────────────────────────────
    // POINT D'ENTRÉE
    // ──────────────────────────────────────────────────────────────

    /**
     * @param  string      $domaineLibelle
     * @param  string      $domaineCode
     * @param  string      $domaineDesc
     * @param  string      $entiteAuditee
     * @param  string      $objectifRcc
     * @param  string      $missionLibelle
     * @param  string      $contextNote       Précisions libres de l'auditeur
     * @param  int         $nbCriteres
     * @param  array|null  $guideInfo         ['path'=>abs, 'name'=>str, 'mime'=>str]
     * @param  array       $fonctions         Liste des fonctions de l'assignment
     *                                         [{id, libelle, code, role_label, entity_name}]
     * @return array ['success'=>bool, 'criteres'=>array, 'error'=>string|null]
     */
    public function generateCriteres(
        string  $domaineLibelle,
        string  $domaineCode,
        string  $domaineDesc    = '',
        string  $entiteAuditee  = '',
        string  $objectifRcc    = '',
        string  $missionLibelle = '',
        string  $contextNote    = '',
        int     $nbCriteres     = 5,
        ?array  $guideInfo      = null,
        array   $fonctions      = [],
    ): array {
        try {
            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) {
                Log::error('[RCC-IA] Clé API non configurée');
                return $this->fallback($domaineCode, $domaineLibelle, $nbCriteres, $fonctions);
            }

            // 1. Extraire le texte du guide
            $guideText = '';
            if ($guideInfo && !empty($guideInfo['path']) && file_exists($guideInfo['path'])) {
                $guideText = $this->extractText($guideInfo);
                Log::info('[RCC-IA] Guide extrait', ['chars' => strlen($guideText), 'file' => $guideInfo['name']]);
            }

            // 2. Construire le prompt
            $prompt = $this->buildPrompt(
                $domaineLibelle, $domaineCode, $domaineDesc,
                $entiteAuditee, $objectifRcc, $missionLibelle,
                $contextNote, $nbCriteres, $guideText, $fonctions
            );

            // 3. Appel API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(60)
            ->retry(2, 2000)
            ->post(self::API_URL, [
                'model'       => self::MODEL,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Tu es un expert senior en audit interne et contrôle de conformité. '
                                   . 'Tu analyses des documents d\'entreprise et génères des critères de contrôle précis, actionnables et conformes aux normes. '
                                   . 'Réponds UNIQUEMENT en JSON valide. Aucun Markdown, aucune explication, aucun code fence. '
                                   . 'Commence directement par { et termine par }.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens'  => self::MAX_TOKENS,
                'temperature' => 0.3,
            ]);

            if ($response->failed()) {
                Log::warning('[RCC-IA] Erreur API Mistral', ['status' => $response->status(), 'body' => substr($response->body(), 0, 300)]);
                return $this->fallback($domaineCode, $domaineLibelle, $nbCriteres, $fonctions);
            }

            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            if (empty($content)) {
                return $this->fallback($domaineCode, $domaineLibelle, $nbCriteres, $fonctions);
            }

            // 4. Parser + mapper responsables
            $criteres = $this->parseAndMap($content, $domaineCode, $fonctions, $nbCriteres);

            Log::info('[RCC-IA] Succès', ['nb' => count($criteres)]);

            return ['success' => true, 'criteres' => $criteres];

        } catch (\Exception $e) {
            Log::error('[RCC-IA] Exception : '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->fallback($domaineCode, $domaineLibelle, $nbCriteres, $fonctions);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // EXTRACTION DE TEXTE
    // ──────────────────────────────────────────────────────────────

    /**
     * Extrait le texte du guide selon le type de fichier.
     * Renvoie une chaîne limitée à GUIDE_CHAR_LIMIT caractères.
     */
    private function extractText(array $info): string
    {
        $path = $info['path'];
        $mime = strtolower($info['mime'] ?? '');
        $name = strtolower($info['name'] ?? '');
        $text = '';

        try {
            // ── PDF ──────────────────────────────────────────────
            if (str_contains($mime, 'pdf') || str_ends_with($name, '.pdf')) {
                $text = $this->extractPdf($path);
            }
            // ── DOCX ─────────────────────────────────────────────
            elseif (
                str_contains($mime, 'officedocument.wordprocessingml') ||
                str_contains($mime, 'msword') ||
                str_ends_with($name, '.docx') || str_ends_with($name, '.doc')
            ) {
                $text = $this->extractDocx($path);
            }
            // ── XLSX ─────────────────────────────────────────────
            elseif (
                str_contains($mime, 'spreadsheetml') ||
                str_contains($mime, 'excel') ||
                str_ends_with($name, '.xlsx') || str_ends_with($name, '.xls')
            ) {
                $text = $this->extractXlsx($path);
            }
            // ── TXT / MD ─────────────────────────────────────────
            elseif (str_contains($mime, 'text/') || str_ends_with($name, '.txt') || str_ends_with($name, '.md')) {
                $text = file_get_contents($path, false, null, 0, 300000) ?: '';
            }

            // Nettoyer : supprimer caractères non imprimables, réduire espaces
            $text = preg_replace('/[^\x20-\x7e\x0a\x0d\xc0-\xff]/u', ' ', $text);
            $text = preg_replace('/[ \t]{3,}/', '  ', $text);
            $text = preg_replace('/\n{4,}/', "\n\n", $text);
            $text = trim($text);

            // Limiter
            if (mb_strlen($text) > self::GUIDE_CHAR_LIMIT) {
                // Garder début + fin pour couvrir intro et conclusions
                $half  = (int)(self::GUIDE_CHAR_LIMIT / 2);
                $start = mb_substr($text, 0, $half);
                $end   = mb_substr($text, -$half);
                $text  = $start."\n\n[...extrait tronqué...]\n\n".$end;
            }

        } catch (\Exception $e) {
            Log::warning('[RCC-IA] Extraction texte échouée : '.$e->getMessage());
            $text = '';
        }

        return $text;
    }

    /** Extraction PDF : pdftotext en priorité, sinon lecture brute des chaînes */
    private function extractPdf(string $path): string
    {
        // Essai 1 : pdftotext (poppler-utils)
        if ($this->commandExists('pdftotext')) {
            $esc  = escapeshellarg($path);
            $out  = shell_exec("pdftotext -layout {$esc} - 2>/dev/null");
            if (!empty($out)) return $out;
        }

        // Essai 2 : lecture brute des chaînes lisibles dans le PDF
        $raw = file_get_contents($path, false, null, 0, 500000);
        if (!$raw) return '';

        // Extraire les chaînes entre parenthèses (format PDF)
        preg_match_all('/\(([^\)]{3,200})\)/', $raw, $m);
        $strings = array_filter($m[1] ?? [], fn($s) => preg_match('/[a-zA-ZÀ-ÿ]{3}/', $s));
        if ($strings) return implode(' ', $strings);

        // Extraire toutes les séquences imprimables ≥ 4 chars
        preg_match_all('/[\x20-\x7e\xc0-\xff]{4,}/', $raw, $m2);
        return implode(' ', $m2[0] ?? []);
    }

    /** Extraction DOCX (ZIP) : word/document.xml */
    private function extractDocx(string $path): string
    {
        if (!class_exists('ZipArchive')) return '';
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return '';

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if (!$xml) return '';

        // Insérer espace aux sauts de paragraphe
        $xml  = str_replace(['</w:p>', '</w:tr>', '<w:br/>'], ["\n", "\n", " "], $xml);
        // Concaténer les runs de texte
        $text = '';
        if (preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $m)) {
            $text = implode(' ', $m[1]);
        } else {
            $text = strip_tags($xml);
        }
        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Extraction XLSX (ZIP) : xl/sharedStrings.xml + feuilles */
    private function extractXlsx(string $path): string
    {
        if (!class_exists('ZipArchive')) return '';
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return '';

        $parts = [];

        // Chaînes partagées
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss && preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $ss, $m)) {
            $parts = array_merge($parts, $m[1]);
        }

        // Feuilles (sheet1, sheet2…)
        for ($i = 1; $i <= 5; $i++) {
            $sheet = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
            if (!$sheet) continue;
            if (preg_match_all('/<v>(.*?)<\/v>/s', $sheet, $m2)) {
                $parts = array_merge($parts, $m2[1]);
            }
        }

        $zip->close();

        // Décoder entités HTML
        $parts = array_map(fn($s) => html_entity_decode(strip_tags($s), ENT_QUOTES, 'UTF-8'), $parts);
        $parts = array_filter($parts, fn($s) => strlen(trim($s)) > 2);

        return implode(' | ', array_unique($parts));
    }

    private function commandExists(string $cmd): bool
    {
        return !empty(shell_exec("which {$cmd} 2>/dev/null"));
    }

    // ──────────────────────────────────────────────────────────────
    // CONSTRUCTION DU PROMPT
    // ──────────────────────────────────────────────────────────────

    private function buildPrompt(
        string $domaineLibelle,
        string $domaineCode,
        string $domaineDesc,
        string $entiteAuditee,
        string $objectifRcc,
        string $missionLibelle,
        string $contextNote,
        int    $nbCriteres,
        string $guideText,
        array  $fonctions,
    ): string {

        // Section guide
        if ($guideText) {
            $guideSection = <<<TXT

═══════════════════════════════════════════════════════════
CONTENU DU GUIDE / DOCUMENT DE RÉFÉRENCE (ANALYSE REQUISE)
═══════════════════════════════════════════════════════════
{$guideText}
═══════════════════════════════════════════════════════════
INSTRUCTION CRITIQUE : Tu dois analyser ce document et en extraire les points de contrôle
pertinents pour le domaine "{$domaineLibelle}". Chaque critère doit être directement
ancré dans le contenu du document (procédure, article, règle ou exigence identifiée).
TXT;
        } else {
            $guideSection = "\nAucun guide fourni : génère des critères basés sur les meilleures pratiques d'audit interne pour ce domaine.";
        }

        // Liste des fonctions disponibles (responsables)
        $fonctionsJson = json_encode(
            array_map(fn($f) => ['id' => $f['id'], 'libelle' => $f['libelle'], 'code' => $f['code']],
            $fonctions),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        // Précisions
        $precisions = $contextNote
            ? "\nPRÉCISIONS DE L'AUDITEUR : {$contextNote}"
            : '';

        return <<<PROMPT
MISSION D'AUDIT : {$missionLibelle}
ENTITÉ AUDITÉE : {$entiteAuditee}
OBJECTIF DU RCC : {$objectifRcc}

DOMAINE : {$domaineLibelle} (code: {$domaineCode})
DESCRIPTION DU DOMAINE : {$domaineDesc}
{$precisions}
{$guideSection}

FONCTIONS RESPONSABLES DISPONIBLES (choisir parmi cette liste) :
{$fonctionsJson}

════════════════════════════════════════════════════════════
TÂCHE : Génère exactement {$nbCriteres} critères de contrôle de conformité.

Pour chaque critère :
  1. ref_controle        : Code unique format "{$domaineCode}-C01", "{$domaineCode}-C02"…
  2. ref_reglementaire   : Citation exacte de l'article, article/section du guide, ou norme (ex: "Guide §3.2 — Procédure de clôture", "Art. 15 Règlement interne", "ISO 9001:2015 §8.4")
  3. intitule_procedure  : Titre court de la procédure ou processus audité, directement issu du document si possible
  4. point_controle      : Description précise et actionnable de CE QUE L'AUDITEUR VÉRIFIE (2-4 phrases). Doit être spécifique, pas générique.
  5. responsable_fonction_id : ID (entier) pris dans la liste des fonctions ci-dessus, celui qui est le plus logiquement responsable de ce point
  6. responsable_libre   : Libellé textuel de la fonction choisie (= "libelle" de la fonction sélectionnée)

RÈGLES ABSOLUES :
- {$nbCriteres} critères exactement
- Ancrer chaque critère dans le CONTENU RÉEL du guide si disponible
- responsable_fonction_id DOIT être un id présent dans la liste fournie (ou null si aucune correspondance)
- Langue : français uniquement
- JSON pur, sans Markdown, commence par {

FORMAT DE RÉPONSE :
{
  "criteres": [
    {
      "ref_controle": "{$domaineCode}-C01",
      "ref_reglementaire": "...",
      "intitule_procedure": "...",
      "point_controle": "...",
      "responsable_fonction_id": 5,
      "responsable_libre": "Nom de la fonction"
    }
  ]
}
PROMPT;
    }

    // ──────────────────────────────────────────────────────────────
    // PARSING + MAPPING
    // ──────────────────────────────────────────────────────────────

    private function parseAndMap(string $content, string $domaineCode, array $fonctions, int $nb): array
    {
        $content = trim($content);

        // Retirer éventuels code fences
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        // Essai 1 : JSON direct
        $json = json_decode($content, true);
        if (is_array($json) && isset($json['criteres'])) {
            return $this->sanitize($json['criteres'], $domaineCode, $fonctions);
        }

        // Essai 2 : extraire JSON entre accolades
        if (preg_match('/(\{[\s\S]*\})/s', $content, $m)) {
            $json = json_decode($m[1], true);
            if (is_array($json) && isset($json['criteres'])) {
                return $this->sanitize($json['criteres'], $domaineCode, $fonctions);
            }
        }

        // Essai 3 : extraire tableau JSON directement
        if (preg_match('/(\[[\s\S]*\])/s', $content, $m)) {
            $arr = json_decode($m[1], true);
            if (is_array($arr)) {
                return $this->sanitize($arr, $domaineCode, $fonctions);
            }
        }

        Log::warning('[RCC-IA] Parsing JSON échoué', ['preview' => mb_substr($content, 0, 300)]);
        return $this->buildFallbackCriteres($domaineCode, '', $nb, $fonctions);
    }

    private function sanitize(array $raw, string $domaineCode, array $fonctions): array
    {
        $validFnIds = array_column($fonctions, 'id');
        $result     = [];
        $idx        = 1;

        foreach ($raw as $c) {
            if (!is_array($c)) continue;

            // Résoudre responsable_fonction_id
            $fnId    = isset($c['responsable_fonction_id']) ? (int)$c['responsable_fonction_id'] : null;
            $fnLibel = trim($c['responsable_libre'] ?? '');

            // Si l'id fourni n'est pas dans la liste, tenter une correspondance par libellé
            if ($fnId && !in_array($fnId, $validFnIds)) {
                $fnId = $this->matchFonctionByLabel($fnLibel, $fonctions);
            }
            // Si pas d'id mais libellé fourni, tenter correspondance
            if (!$fnId && $fnLibel) {
                $fnId = $this->matchFonctionByLabel($fnLibel, $fonctions);
            }
            // Si on a un id, synchroniser le libellé
            if ($fnId) {
                $fn = array_values(array_filter($fonctions, fn($f) => $f['id'] === $fnId))[0] ?? null;
                if ($fn) $fnLibel = $fn['libelle'];
            }

            $result[] = [
                'ref_controle'            => $this->clean($c['ref_controle']       ?? '', 30)  ?: $domaineCode.'-C'.str_pad($idx,2,'0',STR_PAD_LEFT),
                'ref_reglementaire'       => $this->clean($c['ref_reglementaire']  ?? '', 500),
                'intitule_procedure'      => $this->clean($c['intitule_procedure'] ?? '', 255) ?: 'Procédure à définir',
                'point_controle'          => $this->clean($c['point_controle']     ?? '', 2000),
                'responsable_fonction_id' => $fnId,
                'responsable_libre'       => $fnLibel ?: null,
            ];
            $idx++;
        }

        return $result;
    }

    /**
     * Cherche la meilleure correspondance de fonction par libellé (recherche floue).
     */
    private function matchFonctionByLabel(string $label, array $fonctions): ?int
    {
        if (!$label || !$fonctions) return null;
        $needle = mb_strtolower($label);

        // Exact
        foreach ($fonctions as $f) {
            if (mb_strtolower($f['libelle']) === $needle) return $f['id'];
        }
        // Contenu (le label contient le libellé ou vice-versa)
        foreach ($fonctions as $f) {
            $hay = mb_strtolower($f['libelle']);
            if (str_contains($hay, $needle) || str_contains($needle, $hay)) return $f['id'];
        }
        // Mots communs ≥ 1 mot de 4+ lettres
        $needleWords = array_filter(preg_split('/\W+/', $needle) ?: [], fn($w) => mb_strlen($w) >= 4);
        $best = null; $bestScore = 0;
        foreach ($fonctions as $f) {
            $hayWords = array_filter(preg_split('/\W+/', mb_strtolower($f['libelle'])) ?: [], fn($w) => mb_strlen($w) >= 4);
            $score    = count(array_intersect($needleWords, $hayWords));
            if ($score > $bestScore) { $bestScore = $score; $best = $f['id']; }
        }
        return $bestScore > 0 ? $best : null;
    }

    private function clean(string $val, int $max): string
    {
        return mb_substr(trim($val), 0, $max);
    }

    // ──────────────────────────────────────────────────────────────
    // FALLBACK
    // ──────────────────────────────────────────────────────────────

    private function fallback(string $domaineCode, string $domaineLibelle, int $nb, array $fonctions): array
    {
        return [
            'success'  => true,
            'criteres' => $this->buildFallbackCriteres($domaineCode, $domaineLibelle, $nb, $fonctions),
        ];
    }

    private function buildFallbackCriteres(string $code, string $libelle, int $nb, array $fonctions): array
    {
        // Prendre la première fonction disponible comme responsable par défaut
        $defaultFnId    = $fonctions[0]['id']      ?? null;
        $defaultFnLabel = $fonctions[0]['libelle']  ?? null;

        $templates = [
            [
                'ref_reglementaire'  => 'Bonnes pratiques IIA — Normes d\'audit interne',
                'intitule_procedure' => 'Procédure de contrôle interne — '.$libelle,
                'point_controle'     => 'Vérifier l\'existence, la formalisation et l\'application effective de la procédure de contrôle interne relative à '.$libelle.'. S\'assurer que le document est approuvé, diffusé et connu des acteurs concernés.',
            ],
            [
                'ref_reglementaire'  => 'SYSCOHADA révisé 2017 — dispositions applicables',
                'intitule_procedure' => 'Conformité réglementaire et légale — '.$libelle,
                'point_controle'     => 'S\'assurer que l\'entité respecte toutes les obligations réglementaires et légales relatives à '.$libelle.'. Contrôler l\'existence d\'un suivi des évolutions réglementaires et la mise à jour des procédures en conséquence.',
            ],
            [
                'ref_reglementaire'  => 'COSO 2013 — Composante : Activités de contrôle',
                'intitule_procedure' => 'Séparation des tâches et autorisations — '.$libelle,
                'point_controle'     => 'Vérifier que les tâches sensibles liées à '.$libelle.' sont réparties entre plusieurs acteurs (pas de cumul de pouvoirs). Contrôler que toute opération critique nécessite une autorisation formelle de la hiérarchie.',
            ],
            [
                'ref_reglementaire'  => 'ISO 9001:2015 — §7.5 Informations documentées',
                'intitule_procedure' => 'Documentation et traçabilité — '.$libelle,
                'point_controle'     => 'S\'assurer que toutes les opérations relatives à '.$libelle.' sont correctement documentées, horodatées et archivées. Vérifier que les pièces justificatives sont conservées selon les délais légaux et accessibles lors des contrôles.',
            ],
            [
                'ref_reglementaire'  => 'Directive BCEAO — Contrôle interne des établissements financiers',
                'intitule_procedure' => 'Gestion des risques — '.$libelle,
                'point_controle'     => 'Contrôler que les risques inhérents au domaine '.$libelle.' ont été identifiés, évalués et cartographiés. S\'assurer que des mesures de maîtrise adaptées sont en place et font l\'objet d\'un suivi régulier.',
            ],
            [
                'ref_reglementaire'  => 'Charte d\'audit interne — Dispositif de contrôle',
                'intitule_procedure' => 'Revue périodique et reporting — '.$libelle,
                'point_controle'     => 'Vérifier qu\'une revue périodique du dispositif de contrôle relatif à '.$libelle.' est effectuée (fréquence minimale trimestrielle). S\'assurer que les anomalies détectées font l\'objet de plans d\'action formalisés et suivis jusqu\'à résolution.',
            ],
        ];

        $result = [];
        for ($i = 0; $i < $nb; $i++) {
            $tpl      = $templates[$i % count($templates)];
            $result[] = [
                'ref_controle'            => $code.'-C'.str_pad($i+1,2,'0',STR_PAD_LEFT),
                'ref_reglementaire'       => $tpl['ref_reglementaire'],
                'intitule_procedure'      => $tpl['intitule_procedure'],
                'point_controle'          => $tpl['point_controle'],
                'responsable_fonction_id' => $defaultFnId,
                'responsable_libre'       => $defaultFnLabel,
            ];
        }

        return $result;
    }
}