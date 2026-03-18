<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcedureDocumentAnalysisService
{
    private const API_URL    = 'https://api.mistral.ai/v1/chat/completions';
    private const MODEL      = 'mistral-small-latest';
    private const MAX_TOKENS = 3000;

    public function analyzeDocument(string $documentPath, string $documentName, string $mimeType, ?array $context = []): array
    {
        set_time_limit(120);
        if (function_exists('ini_set')) ini_set('max_execution_time', '120');

        try {
            $apiKey = config('services.mistral.api_key');
            if (empty($apiKey)) {
                Log::error('[APT-IA] MISTRAL_API_KEY non configurée');
                return $this->fallbackResult($documentName, $context);
            }

            $textContent = $this->extractTextContent($documentPath, $mimeType, $documentName);
            $prompt      = $this->buildAnalysisPrompt($textContent, $documentName, $context ?? []);

            Log::info('[APT-IA] Début analyse', ['file' => $documentName, 'model' => self::MODEL, 'chars' => strlen($textContent)]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(55)->post(self::API_URL, [
                'model'       => self::MODEL,
                'max_tokens'  => self::MAX_TOKENS,
                'temperature' => 0.2,
                'messages'    => [
                    ['role' => 'system', 'content' => 'Expert audit interne IIA/COSO. Reponds UNIQUEMENT en JSON valide. Aucun markdown.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
            ]);

            if ($response->failed()) {
                Log::error('[APT-IA] Erreur API', ['status' => $response->status()]);
                return $this->fallbackResult($documentName, $context);
            }

            $content = $response->json('choices.0.message.content') ?? '';
            if (empty($content)) return $this->fallbackResult($documentName, $context);

            $result = $this->parseAnalysisResponse($content, $documentName);
            Log::info('[APT-IA] Analyse terminée', ['matrice_b' => count($result['matrice_b'] ?? []), 'collecte_c' => count($result['collecte_c'] ?? [])]);
            return array_merge($result, ['success' => true]);

        } catch (\Exception $e) {
            Log::error('[APT-IA] Exception: ' . $e->getMessage());
            return $this->fallbackResult($documentName, $context);
        }
    }

    private function extractTextContent(string $path, string $mimeType, string $name): string
    {
        $fullPath = Storage::disk('public')->path($path);

        if (str_starts_with($mimeType, 'image/')) {
            return "Document procédure image: {$name}. Génère modélisation complète.";
        }

        $docxMimes = ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'];
        if (in_array($mimeType, $docxMimes) || str_ends_with(strtolower($name), '.docx')) {
            $text = $this->extractDocxText($fullPath);
            if (!empty($text)) return mb_substr($text, 0, 2500);
        }

        if ($mimeType === 'application/pdf' || str_ends_with(strtolower($name), '.pdf')) {
            try {
                $text = shell_exec("pdftotext " . escapeshellarg($fullPath) . " - 2>/dev/null");
                if (!empty(trim($text ?? ''))) return mb_substr(trim($text), 0, 2500);
            } catch (\Exception $e) {}
            return "Document PDF: {$name}. Procédure audit interne. Génère modélisation IIA/COSO.";
        }

        if (str_starts_with($mimeType, 'text/') || str_ends_with(strtolower($name), '.txt')) {
            try {
                $text = file_get_contents($fullPath);
                if ($text !== false) return mb_substr(trim($text), 0, 2500);
            } catch (\Exception $e) {}
        }

        return "Procédure: {$name}. Génère modélisation avec matrice 12+ points, collecte 8 lignes, grille 8 questions, flowchart 6 etapes.";
    }

    private function extractDocxText(string $fullPath): string
    {
        if (!file_exists($fullPath) || !class_exists('ZipArchive')) return '';
        try {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) !== true) return '';
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) return '';
            $text = preg_replace('/<w:p[ >]/', "\n", $xml);
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = preg_replace('/[ \t]+/', ' ', $text);
            return trim(preg_replace('/\n{3,}/', "\n\n", $text));
        } catch (\Exception $e) { return ''; }
    }

    private function buildAnalysisPrompt(string $textContent, string $docName, array $context): string
    {
        $missionInfo = '';
        if (!empty($context['mission_title']))   $missionInfo .= "\nMission: {$context['mission_title']}";
        if (!empty($context['entity_name']))     $missionInfo .= "\nEntite: {$context['entity_name']}";
        if (!empty($context['procedure_title'])) $missionInfo .= "\nProcedure: {$context['procedure_title']}";
        $textTrunc = mb_substr($textContent, 0, 2000);

        return "Analyse ce document de procedure audit. JSON VALIDE UNIQUEMENT sans markdown.\n\nDOC: {$docName}{$missionInfo}\n\nTEXTE:\n{$textTrunc}\n\nRETOURNE CE JSON COMPLET:\n\n{\"synthese\":{\"titre\":\"Titre\",\"description\":\"Description\",\"domaine\":\"Service\",\"ref_procedure\":\"PROC-XXX\",\"version\":\"v1.0\",\"risques_principaux\":[\"Risque1\",\"Risque2\"]},\"matrice_b\":[{\"is_section\":true,\"section\":\"I. EXISTENCE ET FORMALISATION\"},{\"num\":1,\"is_section\":false,\"point_controle\":\"Point controle specifique 1\",\"obj_controle\":\"OC7\",\"obj_audit\":\"OA1\",\"nature\":null,\"controle_present\":null,\"preuve\":\"\",\"observation\":\"\",\"resultat\":null},{\"num\":2,\"is_section\":false,\"point_controle\":\"Point controle specifique 2\",\"obj_controle\":\"OC4\",\"obj_audit\":\"OA1\",\"nature\":null,\"controle_present\":null,\"preuve\":\"\",\"observation\":\"\",\"resultat\":null},{\"is_section\":true,\"section\":\"II. APPLICATION\"},{\"num\":3,\"is_section\":false,\"point_controle\":\"Point controle 3\",\"obj_controle\":\"OC7\",\"obj_audit\":\"OA3\",\"nature\":null,\"controle_present\":null,\"preuve\":\"\",\"observation\":\"\",\"resultat\":null}],\"collecte_c\":[{\"num\":1,\"information\":\"Info collecter\",\"source\":\"Source\",\"methode_collecte\":\"Entretien\",\"statut\":null},{\"num\":2,\"information\":\"Info 2\",\"source\":\"Source 2\",\"methode_collecte\":\"Documentation\",\"statut\":null}],\"grille_d\":[{\"is_axe\":true,\"axe\":\"Axe 1 - Connaissance\"},{\"num\":\"Q1\",\"is_axe\":false,\"question\":\"Question 1 specifique\",\"obj_audit\":\"OA2\",\"reponse\":\"\"},{\"is_axe\":true,\"axe\":\"Axe 2 - Application\"},{\"num\":\"Q2\",\"is_axe\":false,\"question\":\"Question 2 specifique\",\"obj_audit\":\"OA3\",\"reponse\":\"\"}],\"flowchart\":{\"nodes\":[{\"id\":\"start\",\"type\":\"start\",\"label\":\"Debut\"},{\"id\":\"n1\",\"type\":\"process\",\"label\":\"Etape 1\",\"acteur\":\"Agent\"},{\"id\":\"n2\",\"type\":\"decision\",\"label\":\"Validation?\"},{\"id\":\"n3\",\"type\":\"process\",\"label\":\"Etape finale\"},{\"id\":\"end\",\"type\":\"end\",\"label\":\"Fin\"}],\"edges\":[{\"from\":\"start\",\"to\":\"n1\",\"label\":\"\"},{\"from\":\"n1\",\"to\":\"n2\",\"label\":\"\"},{\"from\":\"n2\",\"to\":\"n3\",\"label\":\"Oui\"},{\"from\":\"n2\",\"to\":\"n1\",\"label\":\"Non\"},{\"from\":\"n3\",\"to\":\"end\",\"label\":\"\"}]}}\n\nREGLES ABSOLUES: JSON valide uniquement. Zero apostrophe dans les valeurs (remplace par espace). matrice_b: 10+ points specifiques au document. collecte_c: 6+ elements. grille_d: 3 axes, 6+ questions. flowchart: 5-8 nodes.";
    }

    private function parseAnalysisResponse(string $content, string $docName): array
    {
        $content = trim(preg_replace('/^```(?:json)?\s*/i', '', preg_replace('/```\s*$/', '', trim($content))));

        $json = json_decode($content, true);
        if (is_array($json) && !empty($json['matrice_b'])) return $this->validateAndNormalize($json, $docName);

        $first = strpos($content, '{');
        $last  = strrpos($content, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $candidate = substr($content, $first, $last - $first + 1);
            $json = json_decode($candidate, true) ?: json_decode($this->sanitizeJsonString($candidate), true);
            if (is_array($json)) return $this->validateAndNormalize($json, $docName);
        }

        Log::warning('[APT-IA] JSON invalide — fallback', ['preview' => substr($content, 0, 150)]);
        return $this->getFallbackAnalysis($docName);
    }

    private function sanitizeJsonString(string $json): string
    {
        $out = ''; $inStr = false; $escaped = false; $len = strlen($json);
        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];
            if ($escaped) { $out .= $ch; $escaped = false; continue; }
            if ($ch === '\\') { $out .= $ch; $escaped = true; continue; }
            if ($ch === '"') { $inStr = !$inStr; $out .= $ch; continue; }
            if ($inStr && $ch === "'") { $out .= ' '; continue; }
            $out .= $ch;
        }
        return $out;
    }

    private function validateAndNormalize(array $json, string $docName): array
    {
        $synthese  = $json['synthese']   ?? [];
        $matriceB  = $json['matrice_b']  ?? [];
        $collecteC = $json['collecte_c'] ?? [];
        $grilleD   = $json['grille_d']   ?? [];
        $flowchart = $json['flowchart']  ?? [];

        if (empty($synthese['titre'])) $synthese['titre'] = pathinfo($docName, PATHINFO_FILENAME);
        if (count(array_filter($matriceB,  fn($r) => empty($r['is_section']))) < 5)  $matriceB  = $this->getDefaultMatrice($docName);
        if (count($collecteC) < 4)                                                    $collecteC = $this->getDefaultCollecte();
        if (count(array_filter($grilleD,   fn($r) => empty($r['is_axe'])))    < 4)  $grilleD   = $this->getDefaultGrille($docName);
        if (empty($flowchart['nodes']))                                                $flowchart = $this->getDefaultFlowchart($docName);

        $bpmnXml = $this->generateBpmnXml($flowchart['nodes'] ?? [], $flowchart['edges'] ?? [], $synthese['titre'] ?? $docName);
        return ['synthese' => $synthese, 'matrice_b' => $matriceB, 'collecte_c' => $collecteC, 'grille_d' => $grilleD, 'flowchart' => $flowchart, 'bpmn_xml' => $bpmnXml];
    }

    private function fallbackResult(string $docName, ?array $context): array
    {
        $fallback = $this->getFallbackAnalysis($docName);
        $fallback['bpmn_xml'] = $this->generateBpmnXml($fallback['flowchart']['nodes'], $fallback['flowchart']['edges'], $fallback['synthese']['titre']);
        return array_merge($fallback, ['success' => true, 'fallback' => true]);
    }

    private function getFallbackAnalysis(string $docName): array
    {
        $title = pathinfo($docName, PATHINFO_FILENAME);
        return [
            'synthese'   => ['titre' => $title, 'description' => "Procédure: {$title}", 'domaine' => '', 'ref_procedure' => 'PROC-001', 'version' => 'v1.0', 'risques_principaux' => ['Non-conformité', 'Absence de traçabilité']],
            'flowchart'  => $this->getDefaultFlowchart($docName),
            'matrice_b'  => $this->getDefaultMatrice($docName),
            'collecte_c' => $this->getDefaultCollecte(),
            'grille_d'   => $this->getDefaultGrille($docName),
        ];
    }

    private function getDefaultFlowchart(string $docName): array
    {
        return ['nodes' => [
            ['id' => 'start', 'type' => 'start',    'label' => 'Déclenchement'],
            ['id' => 'n1',    'type' => 'process',  'label' => '1. Réception', 'acteur' => 'Agent'],
            ['id' => 'n2',    'type' => 'decision', 'label' => 'Dossier complet ?'],
            ['id' => 'n3',    'type' => 'process',  'label' => '3. Instruction', 'acteur' => 'Responsable'],
            ['id' => 'n4',    'type' => 'decision', 'label' => 'Validation ?'],
            ['id' => 'n5',    'type' => 'process',  'label' => '5. Finalisation', 'acteur' => 'Agent'],
            ['id' => 'end',   'type' => 'end',      'label' => 'Fin'],
        ], 'edges' => [
            ['from' => 'start', 'to' => 'n1',  'label' => ''],
            ['from' => 'n1',    'to' => 'n2',  'label' => ''],
            ['from' => 'n2',    'to' => 'n3',  'label' => 'Oui'],
            ['from' => 'n2',    'to' => 'n1',  'label' => 'Non'],
            ['from' => 'n3',    'to' => 'n4',  'label' => ''],
            ['from' => 'n4',    'to' => 'n5',  'label' => 'Oui'],
            ['from' => 'n4',    'to' => 'n3',  'label' => 'Non'],
            ['from' => 'n5',    'to' => 'end', 'label' => ''],
        ]];
    }

    private function getDefaultMatrice(string $docName): array
    {
        $t = pathinfo($docName, PATHINFO_FILENAME);
        return [
            ['is_section' => true, 'section' => 'I. EXISTENCE ET FORMALISATION'],
            ['num'=>1,'is_section'=>false,'point_controle'=>"Procédure {$t} documentée et accessible",'obj_controle'=>'OC7','obj_audit'=>'OA1','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>2,'is_section'=>false,'point_controle'=>'Procédure validée et signée par le responsable','obj_controle'=>'OC4','obj_audit'=>'OA1','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>3,'is_section'=>false,'point_controle'=>'Procédure diffusée au personnel concerné','obj_controle'=>'OC7','obj_audit'=>'OA2','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>4,'is_section'=>false,'point_controle'=>'Procédure mise à jour régulièrement','obj_controle'=>'OC7','obj_audit'=>'OA1','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['is_section' => true, 'section' => 'II. APPLICATION ET CONFORMITÉ'],
            ['num'=>5,'is_section'=>false,'point_controle'=>'Personnel formé à l application','obj_controle'=>'OC7','obj_audit'=>'OA2','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>6,'is_section'=>false,'point_controle'=>'Pratique réelle conforme à la procédure','obj_controle'=>'OC7','obj_audit'=>'OA3','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>7,'is_section'=>false,'point_controle'=>'Dérogations tracées et autorisées','obj_controle'=>'OC4','obj_audit'=>'OA3','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['is_section' => true, 'section' => 'III. EFFICACITÉ DES CONTRÔLES'],
            ['num'=>8,'is_section'=>false,'point_controle'=>'Contrôle de 1er niveau opérationnel','obj_controle'=>'OC1','obj_audit'=>'OA5','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>9,'is_section'=>false,'point_controle'=>'Séparation des tâches respectée','obj_controle'=>'OC5','obj_audit'=>'OA5','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>10,'is_section'=>false,'point_controle'=>'Traçabilité des opérations assurée','obj_controle'=>'OC2','obj_audit'=>'OA5','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['is_section' => true, 'section' => 'IV. CONFORMITÉ RÉGLEMENTAIRE'],
            ['num'=>11,'is_section'=>false,'point_controle'=>'Procédure alignée sur les exigences réglementaires','obj_controle'=>'OC7','obj_audit'=>'OA3','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
            ['num'=>12,'is_section'=>false,'point_controle'=>'Reporting des anomalies formalisé','obj_controle'=>'OC2','obj_audit'=>'OA6','nature'=>null,'controle_present'=>null,'preuve'=>'','observation'=>'','resultat'=>null],
        ];
    }

    private function getDefaultCollecte(): array
    {
        return [
            ['num'=>1,'information'=>'Manuel des procédures internes','source'=>'Direction','methode_collecte'=>'Consultation documentaire','statut'=>null],
            ['num'=>2,'information'=>'Organigramme fonctionnel','source'=>'Direction générale','methode_collecte'=>'Consultation documentaire','statut'=>null],
            ['num'=>3,'information'=>'Délégations de pouvoir','source'=>'Direction juridique','methode_collecte'=>'Demande documentaire','statut'=>null],
            ['num'=>4,'information'=>'Journaux informatiques','source'=>'DSI','methode_collecte'=>'Extraction système','statut'=>null],
            ['num'=>5,'information'=>'Rapports de contrôle','source'=>'Contrôle interne','methode_collecte'=>'Demande documentaire','statut'=>null],
            ['num'=>6,'information'=>'Fiches incidents','source'=>'Responsable qualité','methode_collecte'=>'Demande documentaire','statut'=>null],
            ['num'=>7,'information'=>'Résultats audits précédents','source'=>'Audit interne','methode_collecte'=>'Analyse de dossier','statut'=>null],
            ['num'=>8,'information'=>'Échantillon de dossiers traités','source'=>'Archives','methode_collecte'=>'Test de détail','statut'=>null],
        ];
    }

    private function getDefaultGrille(string $docName): array
    {
        $t = pathinfo($docName, PATHINFO_FILENAME);
        return [
            ['is_axe'=>true,'axe'=>'Axe 1 — Connaissance de la procédure'],
            ['num'=>'Q1','is_axe'=>false,'question'=>"Connaissez-vous la procédure {$t} ?",'obj_audit'=>'OA2','reponse'=>''],
            ['num'=>'Q2','is_axe'=>false,'question'=>'Où est-elle accessible ? Version en vigueur ?','obj_audit'=>'OA1','reponse'=>''],
            ['num'=>'Q3','is_axe'=>false,'question'=>'Avez-vous été formé sur cette procédure ?','obj_audit'=>'OA2','reponse'=>''],
            ['is_axe'=>true,'axe'=>'Axe 2 — Application pratique'],
            ['num'=>'Q4','is_axe'=>false,'question'=>'Comment procédez-vous concrètement à chaque étape ?','obj_audit'=>'OA3','reponse'=>''],
            ['num'=>'Q5','is_axe'=>false,'question'=>'Que faites-vous en cas de situation non prévue ?','obj_audit'=>'OA6','reponse'=>''],
            ['num'=>'Q6','is_axe'=>false,'question'=>'Qui valide avant de passer à l etape suivante ?','obj_audit'=>'OA5','reponse'=>''],
            ['is_axe'=>true,'axe'=>'Axe 3 — Difficultés et zones de risque'],
            ['num'=>'Q7','is_axe'=>false,'question'=>'Rencontrez-vous des difficultés pour appliquer cette procédure ?','obj_audit'=>'OA6','reponse'=>''],
            ['num'=>'Q8','is_axe'=>false,'question'=>'Avez-vous constaté des anomalies ? Comment traitées ?','obj_audit'=>'OA7','reponse'=>''],
        ];
    }

    public function generateBpmnXml(array $nodes, array $edges, string $processName = 'Procédure'): string
    {
        if (empty($nodes)) return $this->getDefaultBpmnXml($processName);
        $procId = 'Process_' . uniqid(); $defId = 'Def_' . uniqid();
        $procNameEsc = htmlspecialchars($processName, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $colW=250;$rowH=120;$offX=150;$offY=80;
        $adj=[];$ranks=[];$cols=[];
        foreach($nodes as $n){$adj[$n['id']]=[];}
        foreach($edges as $e){if(isset($adj[$e['from']]))$adj[$e['from']][]=$e['to'];}
        $startId=null;
        foreach($nodes as $n){if($n['type']==='start'){$startId=$n['id'];break;}}
        if(!$startId)$startId=$nodes[0]['id'];
        $queue=[$startId];$ranks[$startId]=0;$cols[$startId]=0;$visited=[$startId=>true];
        while(!empty($queue)){
            $cur=array_shift($queue);
            foreach(($adj[$cur]??[])as $ci=>$child){
                if(!isset($ranks[$child]))$ranks[$child]=($ranks[$cur]??0)+1;
                $pType='';foreach($nodes as $n){if($n['id']===$cur){$pType=$n['type']??'';break;}}
                $edgeLabel='';foreach($edges as $e){if($e['from']===$cur&&$e['to']===$child){$edgeLabel=$e['label']??'';break;}}
                if($pType==='decision'&&!empty($edgeLabel)&&$edgeLabel!=='Oui'){$cols[$child]=($cols[$cur]??0)+1;}
                elseif(!isset($cols[$child])){$cols[$child]=$cols[$cur]??0;}
                if(!isset($visited[$child])){$visited[$child]=true;$queue[]=$child;}
            }
        }
        foreach($nodes as $i=>$n){if(!isset($ranks[$n['id']]))$ranks[$n['id']]=$i+10;if(!isset($cols[$n['id']]))$cols[$n['id']]=0;}
        $dim=['start'=>['w'=>36,'h'=>36],'end'=>['w'=>36,'h'=>36],'process'=>['w'=>160,'h'=>60],'decision'=>['w'=>50,'h'=>50],'document'=>['w'=>150,'h'=>60]];
        $pos=[];foreach($nodes as $n){$d=$dim[$n['type']]??$dim['process'];$pos[$n['id']]=['x'=>$offX+($cols[$n['id']]??0)*$colW,'y'=>$offY+($ranks[$n['id']]??0)*$rowH,'w'=>$d['w'],'h'=>$d['h']];}
        $el='';
        foreach($nodes as $n){
            $id=htmlspecialchars($n['id'],ENT_XML1|ENT_COMPAT,'UTF-8');
            $nm=htmlspecialchars($n['label']??'',ENT_XML1|ENT_COMPAT,'UTF-8');
            $inc=implode('',array_map(fn($x)=>"      <bpmn:incoming>{$x}</bpmn:incoming>\n",$this->getIncomingList($n['id'],$edges)));
            $out=implode('',array_map(fn($x)=>"      <bpmn:outgoing>{$x}</bpmn:outgoing>\n",$this->getOutgoingList($n['id'],$edges)));
            switch($n['type']){
                case 'start':$o=$this->getOutgoing($n['id'],$edges);$el.="    <bpmn:startEvent id=\"{$id}\" name=\"{$nm}\">\n";if($o)$el.="      <bpmn:outgoing>{$o}</bpmn:outgoing>\n";$el.="    </bpmn:startEvent>\n";break;
                case 'end':$i2=$this->getIncoming($n['id'],$edges);$el.="    <bpmn:endEvent id=\"{$id}\" name=\"{$nm}\">\n";if($i2)$el.="      <bpmn:incoming>{$i2}</bpmn:incoming>\n";$el.="    </bpmn:endEvent>\n";break;
                case 'decision':$el.="    <bpmn:exclusiveGateway id=\"{$id}\" name=\"{$nm}\" gatewayDirection=\"Diverging\">\n{$inc}{$out}    </bpmn:exclusiveGateway>\n";break;
                default:$el.="    <bpmn:task id=\"{$id}\" name=\"{$nm}\">\n{$inc}{$out}    </bpmn:task>\n";break;
            }
        }
        foreach($edges as $e){$eid='Flow_'.preg_replace('/[^a-zA-Z0-9]/','_',$e['from'].'_'.$e['to']);$from=htmlspecialchars($e['from'],ENT_XML1|ENT_COMPAT,'UTF-8');$to=htmlspecialchars($e['to'],ENT_XML1|ENT_COMPAT,'UTF-8');$name=!empty($e['label'])?' name="'.htmlspecialchars($e['label'],ENT_XML1|ENT_COMPAT,'UTF-8').'"':'';$el.="    <bpmn:sequenceFlow id=\"{$eid}\" sourceRef=\"{$from}\" targetRef=\"{$to}\"{$name} />\n";}
        $shapes='';$edgesDi='';
        foreach($nodes as $n){$id=htmlspecialchars($n['id'],ENT_XML1|ENT_COMPAT,'UTF-8');$p=$pos[$n['id']];$mrk=($n['type']==='decision')?' isMarkerVisible="true"':'';$shapes.="      <bpmndi:BPMNShape id=\"{$id}_di\" bpmnElement=\"{$id}\"{$mrk}>\n        <dc:Bounds x=\"{$p['x']}\" y=\"{$p['y']}\" width=\"{$p['w']}\" height=\"{$p['h']}\" />\n        <bpmndi:BPMNLabel />\n      </bpmndi:BPMNShape>\n";}
        foreach($edges as $e){$eid='Flow_'.preg_replace('/[^a-zA-Z0-9]/','_',$e['from'].'_'.$e['to']);$sp=$pos[$e['from']]??['x'=>0,'y'=>0,'w'=>100,'h'=>60];$tp=$pos[$e['to']]??['x'=>0,'y'=>200,'w'=>100,'h'=>60];$x1=$sp['x']+intval($sp['w']/2);$y1=$sp['y']+$sp['h'];$x2=$tp['x']+intval($tp['w']/2);$y2=$tp['y'];$edgesDi.="      <bpmndi:BPMNEdge id=\"{$eid}_di\" bpmnElement=\"{$eid}\">\n        <di:waypoint x=\"{$x1}\" y=\"{$y1}\" />\n        <di:waypoint x=\"{$x2}\" y=\"{$y2}\" />\n      </bpmndi:BPMNEdge>\n";}
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<bpmn:definitions xmlns:bpmn=\"http://www.omg.org/spec/BPMN/20100524/MODEL\" xmlns:bpmndi=\"http://www.omg.org/spec/BPMN/20100524/DI\" xmlns:dc=\"http://www.omg.org/spec/DD/20100524/DC\" xmlns:di=\"http://www.omg.org/spec/DD/20100524/DI\" id=\"{$defId}\" targetNamespace=\"http://bpmn.io/schema/bpmn\">\n  <bpmn:process id=\"{$procId}\" name=\"{$procNameEsc}\" isExecutable=\"false\">\n{$el}  </bpmn:process>\n  <bpmndi:BPMNDiagram id=\"BPMNDiagram_1\">\n    <bpmndi:BPMNPlane id=\"BPMNPlane_1\" bpmnElement=\"{$procId}\">\n{$shapes}{$edgesDi}    </bpmndi:BPMNPlane>\n  </bpmndi:BPMNDiagram>\n</bpmn:definitions>";
    }

    private function getOutgoing(string $id,array $e):string{foreach($e as $x){if($x['from']===$id)return 'Flow_'.preg_replace('/[^a-zA-Z0-9]/','_',$x['from'].'_'.$x['to']);}return '';}
    private function getIncoming(string $id,array $e):string{foreach($e as $x){if($x['to']===$id)return 'Flow_'.preg_replace('/[^a-zA-Z0-9]/','_',$x['from'].'_'.$x['to']);}return '';}
    private function getIncomingList(string $id,array $e):array{$r=[];foreach($e as $x){if($x['to']===$id)$r[]='Flow_'.preg_replace('/[^a-zA-Z0-9]/','_',$x['from'].'_'.$x['to']);}return $r;}
    private function getOutgoingList(string $id,array $e):array{$r=[];foreach($e as $x){if($x['from']===$id)$r[]='Flow_'.preg_replace('/[^a-zA-Z0-9]/','_',$x['from'].'_'.$x['to']);}return $r;}

    private function getDefaultBpmnXml(string $title): string
    {
        $t=htmlspecialchars($title,ENT_XML1);
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<bpmn:definitions xmlns:bpmn=\"http://www.omg.org/spec/BPMN/20100524/MODEL\" xmlns:bpmndi=\"http://www.omg.org/spec/BPMN/20100524/DI\" xmlns:dc=\"http://www.omg.org/spec/DD/20100524/DC\" id=\"Definitions_default\" targetNamespace=\"http://bpmn.io/schema/bpmn\">\n  <bpmn:process id=\"Process_default\" name=\"{$t}\" isExecutable=\"false\">\n    <bpmn:startEvent id=\"start\" name=\"Début\"><bpmn:outgoing>Flow_s_t</bpmn:outgoing></bpmn:startEvent>\n    <bpmn:task id=\"task1\" name=\"Etape\"><bpmn:incoming>Flow_s_t</bpmn:incoming><bpmn:outgoing>Flow_t_e</bpmn:outgoing></bpmn:task>\n    <bpmn:endEvent id=\"end\" name=\"Fin\"><bpmn:incoming>Flow_t_e</bpmn:incoming></bpmn:endEvent>\n    <bpmn:sequenceFlow id=\"Flow_s_t\" sourceRef=\"start\" targetRef=\"task1\" />\n    <bpmn:sequenceFlow id=\"Flow_t_e\" sourceRef=\"task1\" targetRef=\"end\" />\n  </bpmn:process>\n  <bpmndi:BPMNDiagram id=\"BPMNDiagram_1\"><bpmndi:BPMNPlane id=\"BPMNPlane_1\" bpmnElement=\"Process_default\">\n    <bpmndi:BPMNShape id=\"start_di\" bpmnElement=\"start\"><dc:Bounds x=\"152\" y=\"82\" width=\"36\" height=\"36\" /></bpmndi:BPMNShape>\n    <bpmndi:BPMNShape id=\"task1_di\" bpmnElement=\"task1\"><dc:Bounds x=\"90\" y=\"180\" width=\"160\" height=\"60\" /></bpmndi:BPMNShape>\n    <bpmndi:BPMNShape id=\"end_di\" bpmnElement=\"end\"><dc:Bounds x=\"152\" y=\"300\" width=\"36\" height=\"36\" /></bpmndi:BPMNShape>\n    <bpmndi:BPMNEdge id=\"Flow_s_t_di\" bpmnElement=\"Flow_s_t\"><di:waypoint x=\"170\" y=\"118\" /><di:waypoint x=\"170\" y=\"180\" /></bpmndi:BPMNEdge>\n    <bpmndi:BPMNEdge id=\"Flow_t_e_di\" bpmnElement=\"Flow_t_e\"><di:waypoint x=\"170\" y=\"240\" /><di:waypoint x=\"170\" y=\"300\" /></bpmndi:BPMNEdge>\n  </bpmndi:BPMNPlane></bpmndi:BPMNDiagram>\n</bpmn:definitions>";
    }
}