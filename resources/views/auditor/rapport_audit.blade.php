<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'Audit Interne – {{ $mission->libelle }}</title>
    <style>
        /* ============================================================
           BASE
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9.5pt; color: #1a1a1a; background: #e8e8e8; }

        .page {
            width: 210mm; min-height: 297mm; background: #fff;
            margin: 6mm auto; padding: 18mm 16mm 18mm 20mm;
            page-break-after: always; position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .page-footer {
            position: absolute; bottom: 10mm; left: 20mm; right: 16mm;
            font-size: 7.5pt; color: #aaa; border-top: .5pt solid #e0e0e0;
            padding-top: 2mm; display: flex; justify-content: space-between;
        }

        /* ============================================================
           CHAMPS ÉDITABLES
        ============================================================ */
        [contenteditable="true"] {
            outline: none;
            border-bottom: 1px dashed #b0c4de;
            min-width: 30px; min-height: 1em;
            display: inline-block;
            transition: background .15s;
        }
        [contenteditable="true"]:hover  { background: #f0f7ff; }
        [contenteditable="true"]:focus  { background: #e8f3ff; border-bottom-color: #1a3a5c; }

        .editable-block {
            border: 1px dashed #b0c4de;
            border-radius: 3px; padding: 3mm 4mm;
            background: #fafcff; min-height: 8mm;
            font-size: 8.5pt; line-height: 1.5; color: #444;
            outline: none;
        }
        .editable-block:hover { background: #f0f7ff; }
        .editable-block:focus { background: #e8f3ff; border-color: #1a3a5c; }

        /* ============================================================
           COVER PAGE
        ============================================================ */
        .cover-frame { display: flex; border: 1.5pt solid #333; margin-bottom: 6mm; }
        .cover-logo {
            width: 42mm; background: #f5f5f5; border-right: 1pt solid #bbb;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 5mm; min-height: 20mm;
        }
        .logo-ph { font-size: 7.5pt; color: #999; border: .5pt dashed #bbb; padding: 4px 8px; text-align: center; }
        .cover-mid { flex: 1; padding: 4mm 6mm; }
        .cover-mid .dept { font-size: 7.5pt; color: #666; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 2mm; }
        .cover-mid h1 { font-size: 13pt; font-weight: bold; color: #1a3a5c; }
        .cover-right { width: 42mm; border-left: 1pt solid #bbb; }
        .cr-row { padding: 3mm 4mm; border-bottom: .5pt solid #ddd; }
        .cr-row:last-child { border-bottom: none; }
        .cr-row .cl { font-size: 7pt; color: #888; display: block; margin-bottom: 1px; }
        .cr-row .cv { font-size: 8.5pt; font-weight: bold; }

        .mission-box { border: .5pt solid #ccc; border-radius: 2px; padding: 4mm 5mm; margin-bottom: 4mm; background: #fafafa; }
        .mission-box .ml { font-size: 7pt; color: #888; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .mission-box .mv { font-size: 10.5pt; font-weight: bold; color: #1a3a5c; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3mm; margin-bottom: 4mm; }
        .mc { border: .5pt solid #ccc; border-radius: 2px; padding: 2.5mm 3.5mm; }
        .mc .ml { font-size: 7pt; color: #888; text-transform: uppercase; display: block; margin-bottom: 1.5px; }
        .mc .mv { font-size: 8.5pt; }
        .conf-box { background: #fff3ee; border: .5pt solid #e8a870; border-radius: 2px; padding: 2.5mm 4mm; font-size: 8pt; color: #7a2800; }

        /* ============================================================
           TABLE DES MATIÈRES
        ============================================================ */
        .toc-title { font-size: 10.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1.5pt solid #1a3a5c; padding-bottom: 2mm; margin-bottom: 4mm; color: #1a3a5c; }
        .toc-t { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .toc-t tr { border-bottom: .5pt solid #eee; }
        .toc-t td { padding: 2.5px 5px; vertical-align: top; }
        .toc-t td:first-child { width: 16mm; font-weight: bold; color: #555; }
        .toc-t td:last-child { width: 12mm; text-align: right; color: #888; }
        .toc-sec td { background: #e8eef5; font-weight: bold; padding: 3.5px 5px; }

        /* ============================================================
           SECTIONS
        ============================================================ */
        .sec-hd { background: #1a3a5c; color: #fff; padding: 3mm 5mm; border-radius: 2px; margin-bottom: 4mm; }
        .sec-hd h2 { font-size: 10.5pt; color: #fff; font-weight: bold; }
        .sub-t { font-size: 8.5pt; font-weight: bold; color: #1a3a5c; text-transform: uppercase; letter-spacing: .3px; border-bottom: 1pt solid #1a3a5c; padding-bottom: 1.5mm; margin-top: 5mm; margin-bottom: 3mm; }
        .txt-box { border: .5pt solid #ccc; border-radius: 2px; padding: 3mm 4mm; background: #fafafa; font-size: 8.5pt; line-height: 1.5; min-height: 10mm; color: #666; margin-bottom: 3mm; }

        /* ============================================================
           BADGES
        ============================================================ */
        .b { display: inline-block; padding: 1.5px 6px; border-radius: 2px; font-size: 7.5pt; font-weight: bold; white-space: nowrap; }
        .b-crit  { background: #fce8e8; color: #8b1a1a; border: .5pt solid #e07070; }
        .b-sig   { background: #fef3e2; color: #7a4a0a; border: .5pt solid #e8b870; }
        .b-less  { background: #e6f4ee; color: #0f5a3a; border: .5pt solid #60b890; }
        .b-house { background: #e8f0fb; color: #1a4a8a; border: .5pt solid #7090d8; }
        .b-prev  { background: #f0faf5; color: #155724; border: .5pt solid #90c8a0; }
        .b-det   { background: #fff8e6; color: #7a4a0a; border: .5pt solid #d4a820; }
        .b-corr  { background: #fdf0f0; color: #8b1a1a; border: .5pt solid #d08080; }

        /* ============================================================
           STATS
        ============================================================ */
        .stat-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 3mm; margin-bottom: 4mm; }
        .sc { border: .5pt solid #ccc; border-radius: 2px; padding: 2.5mm; text-align: center; }
        .sc .sn { font-size: 16pt; font-weight: bold; }
        .sc .sl { font-size: 7.5pt; color: #777; }
        .sc1 .sn { color: #8b1a1a; } .sc2 .sn { color: #7a4a0a; }
        .sc3 .sn { color: #0f5a3a; } .sc4 .sn { color: #1a4a8a; }

        /* ============================================================
           RÉSUMÉ CONSTATS
        ============================================================ */
        .fl { list-style: none; margin-bottom: 3mm; }
        .fl li { padding: 2.5mm 3.5mm; margin-bottom: 1.5mm; border-left: 2.5pt solid #ccc; background: #fafafa; font-size: 8.5pt; display: flex; justify-content: space-between; align-items: flex-start; gap: 3mm; }
        .fl li.fc  { border-left-color: #c0392b; }
        .fl li.fs  { border-left-color: #e67e22; }
        .fl li.fl2 { border-left-color: #27ae60; }
        .fl li.fh  { border-left-color: #2980b9; }

        /* ============================================================
           PLAN D'ACTIONS — table-layout fixed, colgroup contrôle les largeurs
        ============================================================ */
        .at {
            width: 100%; border-collapse: collapse;
            font-size: 7.5pt; margin-bottom: 4mm;
            table-layout: fixed;
        }
        .at th {
            background: #1a3a5c; color: #fff;
            padding: 3px 4px; font-size: 7pt; text-align: left;
            border: .5pt solid #999; vertical-align: top;
            word-wrap: break-word; overflow-wrap: break-word;
        }
        .at td {
            padding: 3px 4px; border: .5pt solid #ccc;
            vertical-align: top;
            word-wrap: break-word; overflow-wrap: break-word;
        }
        .at tr:nth-child(even) td { background: #f5f7fa; }
        /* Classes utilitaires sur td (pas col) — toujours valides */
        .at-num  { text-align: center; font-weight: bold; color: #555; }
        .at-prio { text-align: center; }

        /* ============================================================
           TABLEAU 3 COLONNES — table-layout:fixed + colgroup pour alignement garanti
        ============================================================ */
        .obj-section { margin-bottom: 8mm; page-break-inside: avoid; }

        /* Bandeau objectif (niveau 1) */
        .obj-banner {
            background: #1a3a5c; color: #fff;
            padding: 2.5mm 4mm; border-radius: 2px 2px 0 0;
            display: flex; align-items: center; gap: 4mm; flex-wrap: wrap;
            margin-bottom: 0;
        }
        .obj-banner .obj-num {
            font-family: Courier, monospace; font-size: 8pt; font-weight: bold;
            background: rgba(255,255,255,.15); padding: 1px 5px; border-radius: 2px;
            white-space: nowrap;
        }
        .obj-banner .obj-title { flex: 1; font-size: 8.5pt; font-weight: bold; }
        .obj-meta {
            background: #e8eef5; border: .5pt solid #b0c4de;
            padding: 2mm 4mm; font-size: 7.5pt; color: #1a3a5c;
            display: flex; gap: 6mm; flex-wrap: wrap; border-top: none;
            margin-bottom: 1mm;
        }
        .obj-meta span { white-space: nowrap; }
        .obj-meta span b { color: #1a3a5c; }

        /* Tableau 3 colonnes */
        .rapport-table {
            width: 100%; border-collapse: collapse;
            font-size: 8pt; margin-bottom: 2mm;
            table-layout: fixed;
        }
        .rapport-table thead tr th {
            background: #2c5282; color: #fff;
            padding: 3px 5px; font-size: 7.5pt;
            border: .5pt solid #1a3a5c; text-align: left;
            word-wrap: break-word; overflow-wrap: break-word;
        }
        .rapport-table td { word-wrap: break-word; overflow-wrap: break-word; min-width: 0; }

        /* Ligne test/critère (niveau 2) */
        .rapport-table tr.row-test td {
            background: #dce8f5;
            border: .5pt solid #b0c4de;
            padding: 2.5mm 4mm;
            font-weight: bold; color: #1a3a5c; font-size: 8pt;
        }

        /* Ligne constat (niveau 3) */
        .rapport-table tr.row-constat td {
            border: .5pt solid #ddd;
            padding: 2.5mm 4mm;
            vertical-align: top;
        }
        .rapport-table tr.row-constat:nth-child(even) td { background: #f8faff; }

        /* Cellule col1 : activités / constat */
        .col1-label { font-size: 7pt; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: .3px; display: block; margin-bottom: 1.5px; }
        .col1-fact  { font-size: 8pt; line-height: 1.45; color: #333; margin-bottom: 2mm; }
        .col1-cause { font-size: 7.5pt; color: #666; margin-bottom: 1.5mm; }
        .col1-impact{ font-size: 7.5pt; color: #666; }
        .col1-frap-id { font-family: Courier, monospace; font-size: 7pt; color: #777; margin-bottom: 1.5mm; display: block; }

        /* Cellule col2 : niveau de maîtrise */
        .nmc-niveau { font-size: 8pt; font-weight: bold; margin-bottom: 2mm; }
        .nmc-statut { font-size: 7.5pt; color: #555; margin-bottom: 1.5mm; }
        .nmc-indicateur { font-size: 7pt; color: #777; font-style: italic; }

        /* Cellule col3 : plan d'action */
        .pa-reco     { font-size: 8pt; line-height: 1.4; color: #333; margin-bottom: 2mm; }
        .pa-resp     { font-size: 7.5pt; color: #444; margin-bottom: 1mm; }
        .pa-echeance { font-size: 7.5pt; color: #444; margin-bottom: 1mm; }
        .pa-livrable { font-size: 7.5pt; color: #777; font-style: italic; }

        /* Ligne vide (aucun constat) */
        .rapport-table tr.row-empty td {
            border: .5pt solid #ddd; padding: 3mm 4mm;
            font-style: italic; color: #aaa; font-size: 8pt;
            text-align: center;
        }

        /* Procédures de test */
        .proc-list { margin: 1.5mm 0 0 4mm; padding: 0; list-style: disc; }
        .proc-list li { font-size: 7.5pt; color: #555; margin-bottom: 1px; line-height: 1.4; }

        /* ============================================================
           FRAP DÉTAILLÉS
        ============================================================ */
        .frap { border: 1pt solid #ccc; border-radius: 2px; margin-bottom: 5mm; page-break-inside: avoid; }
        .frap-hd { background: #e8eef5; padding: 2.5mm 3.5mm; border-bottom: .5pt solid #ccc; display: flex; align-items: flex-start; gap: 3mm; flex-wrap: wrap; }
        .frap-id { font-family: Courier, monospace; font-size: 7.5pt; font-weight: bold; color: #1a3a5c; background: #fff; padding: 1px 4px; border: .5pt solid #aac; border-radius: 1px; white-space: nowrap; flex-shrink: 0; }
        .frap-ttl { flex: 1; font-size: 8.5pt; font-weight: bold; color: #1a1a1a; line-height: 1.4; min-width: 0; }
        .frap-badges { display: flex; gap: 3mm; flex-shrink: 0; align-items: center; }
        /* Corps FRAP : 2 colonnes égales fixes */
        .frap-body { display: grid; grid-template-columns: 50% 50%; }
        .ff { padding: 2.5mm 3.5mm; border-right: .5pt solid #e0e0e0; border-bottom: .5pt solid #e0e0e0; min-width: 0; overflow: hidden; }
        .ff:nth-child(even) { border-right: none; }
        .ff.full { grid-column: 1/-1; border-right: none; }
        .ff label { font-size: 7pt; font-weight: bold; color: #1a3a5c; text-transform: uppercase; letter-spacing: .3px; display: block; margin-bottom: 1.5px; }
        .ff p { font-size: 8pt; line-height: 1.5; color: #555; word-wrap: break-word; overflow-wrap: break-word; }
        /* Pied FRAP : label | texte actions | métadonnées */
        .frap-ft { background: #f5f7fa; padding: 2.5mm 3.5mm; border-top: .5pt solid #ccc; display: grid; grid-template-columns: auto 1fr auto auto auto; gap: 3mm; align-items: start; }
        .frap-ft .ffl { font-weight: bold; color: #1a3a5c; white-space: nowrap; font-size: 7.5pt; }
        .frap-ft .fft { font-size: 8pt; color: #555; font-style: italic; }
        .frap-ft .fmeta { font-size: 7.5pt; white-space: nowrap; }
        .rubrique { background: #e8eef5; border-left: 3pt solid #1a3a5c; padding: 2mm 4mm; font-size: 8.5pt; font-weight: bold; color: #1a3a5c; margin-bottom: 3mm; margin-top: 3mm; }

        /* ============================================================
           ANNEXES — table-layout fixed + cellules qui remplissent leur espace
        ============================================================ */
        .ant {
            width: 100%; border-collapse: collapse;
            font-size: 7.5pt; margin-bottom: 4mm;
            table-layout: fixed;          /* les largeurs viennent des <col width="..."> */
        }
        .ant th {
            background: #1a3a5c; color: #fff;
            padding: 3px 5px; border: .5pt solid #999;
            font-size: 7pt; text-align: left;
            word-wrap: break-word; overflow-wrap: break-word;
            vertical-align: middle;
        }
        .ant td {
            padding: 4px 5px; border: .5pt solid #ccc;
            vertical-align: top;
            word-wrap: break-word; overflow-wrap: break-word;
            /* hauteur minimale garantie même si cellule vide */
            min-height: 8mm; height: 8mm;
        }
        /* Ligne vide visible même sans contenu */
        .ant td:empty::after { content: '\00a0'; display: block; min-height: 8mm; }
        .ant tr:nth-child(even) td { background: #f5f7fa; }
        .ant .gr td { background: #e8eef5; font-weight: bold; color: #1a3a5c; }
        .ph { border: .5pt dashed #bbb; border-radius: 2px; padding: 3mm; text-align: center; font-size: 8pt; color: #bbb; font-style: italic; margin-bottom: 4mm; }
        .sig-row { display: flex; gap: 3mm; }
        .sig-box { flex: 1; border: .5pt solid #ccc; border-radius: 2px; padding: 2mm 3mm; }
        .sig-box .sl { font-size: 7pt; color: #888; display: block; margin-bottom: 2mm; }
        .sig-box .sli { border-bottom: .5pt solid #aaa; height: 6mm; }
        .sig-box .sm { display: flex; justify-content: space-between; font-size: 6.5pt; color: #bbb; margin-top: 1mm; }

        /* ============================================================
           BARRE D'OUTILS ÉDITION (masquée à l'impression)
        ============================================================ */
        .edit-bar {
            position: fixed; top: 10px; right: 10px; z-index: 9999;
            background: #1a3a5c; color: #fff; padding: 6px 12px;
            border-radius: 4px; font-size: 8pt; font-family: Arial, sans-serif;
            display: flex; gap: 8px; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.3);
        }
        .edit-bar button {
            background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
            color: #fff; padding: 3px 10px; border-radius: 3px; cursor: pointer; font-size: 7.5pt;
        }
        .edit-bar button:hover { background: rgba(255,255,255,.3); }
        .edit-indicator { font-size: 7pt; opacity: .7; }

        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 18mm 16mm 18mm 20mm; box-shadow: none; page-break-after: always; }
            .edit-bar { display: none; }
            [contenteditable] { border-bottom: none !important; background: transparent !important; }
            .editable-block { border: none !important; background: transparent !important; }
        }
    </style>
</head>
<body>

@php
    $totalConstats = $statsConstats['total'] ?? 0;
    $critiques     = $statsConstats['critique'] ?? 0;
    $significatifs = $statsConstats['significatif'] ?? 0;
    $peuSig        = $statsConstats['peu_significatif'] ?? 0;
    $maintenance   = $statsConstats['maintenance'] ?? 0;

    // Helpers inline
    $badgeClass = fn(string $imp) => match($imp) {
        'critique' => 'b-crit',
        'haute'    => 'b-sig',
        'moyenne'  => 'b-less',
        default    => 'b-house',
    };
    $badgeText = fn(string $imp) => match($imp) {
        'critique' => 'Critique',
        'haute'    => 'Significatif',
        'moyenne'  => 'Peu significatif',
        default    => 'Maintenance',
    };
    $impClass = fn(string $imp) => match($imp) {
        'critique' => 'fc',
        'haute'    => 'fs',
        'moyenne'  => 'fl2',
        default    => 'fh',
    };
    $statutLabel = fn(string $s) => match($s) {
        'validated'  => 'Validé',
        'in_review'  => 'En revue',
        'submitted'  => 'Soumis',
        default      => 'En cours',
    };
    $ctrlBadge = fn(?string $t) => match(strtolower($t ?? '')) {
        'préventif','preventif' => 'b-prev',
        'détectif','detectif'   => 'b-det',
        'correctif'             => 'b-corr',
        default                 => 'b-house',
    };
@endphp

{{-- ===== BARRE D'ÉDITION ===== --}}
<div class="edit-bar">
    <span class="edit-indicator">✏️ Mode édition</span>
    <button onclick="window.print()">🖨 Imprimer / PDF</button>
    <button onclick="saveEdits()">💾 Copier modifs</button>
</div>

{{-- ======================================================
     PAGE 1 : COUVERTURE
====================================================== --}}
<div class="page">
    <div class="cover-frame">
        <div class="cover-logo"><div class="logo-ph">LOGO<br>ENTITÉ</div></div>
        <div class="cover-mid">
            <div class="dept">Direction de l'Audit Interne</div>
            <h1>Rapport d'Audit Interne</h1>
        </div>
        <div class="cover-right">
            <div class="cr-row">
                <span class="cl">N° Rapport</span>
                <span class="cv" contenteditable="true">RAP-{{ $mission->id }}-{{ date('Y') }}</span>
            </div>
            <div class="cr-row">
                <span class="cl">Version</span>
                <span class="cv" contenteditable="true">Final</span>
            </div>
            <div class="cr-row">
                <span class="cl">Date d'émission</span>
                <span class="cv" contenteditable="true">{{ date('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <div class="mission-box">
        <span class="ml">Intitulé de la mission</span>
        <div class="mv" contenteditable="true">{{ $mission->libelle }}</div>
    </div>

    <div class="meta-grid">
        <div class="mc">
            <span class="ml">Objectif général</span>
            <div class="mv editable-block" contenteditable="true">{{ $mission->objectif ?: 'Non renseigné' }}</div>
        </div>
        <div class="mc">
            <span class="ml">Commanditaire de l'audit</span>
            <div class="mv" contenteditable="true">Direction Générale</div>
        </div>
        <div class="mc">
            <span class="ml">Date(s) de l'audit</span>
            <div class="mv" contenteditable="true">
                {{ \Carbon\Carbon::parse($mission->date_debut)->format('d/m/Y') }}
                au
                {{ \Carbon\Carbon::parse($mission->date_fin)->format('d/m/Y') }}
            </div>
        </div>
        <div class="mc">
            <span class="ml">Lieu(x) de l'audit</span>
            <div class="mv" contenteditable="true">{{ $mission->lieux ?: 'Non spécifié' }}</div>
        </div>
        <div class="mc">
            <span class="ml">Référentiel d'audit</span>
            <div class="mv" contenteditable="true">Normes IIA, COSO, Procédures internes</div>
        </div>
        <div class="mc">
            <span class="ml">Entité(s) auditée(s)</span>
            <div class="mv" contenteditable="true">{{ $entity->entity_name ?? 'Toutes entités' }}</div>
        </div>
        <div class="mc" style="grid-column:1/-1">
            <span class="ml">Rapport établi par</span>
            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:2mm;flex-wrap:wrap;gap:4mm">
                @php $chefMission = collect($equipe)->firstWhere('role', 'CM'); @endphp
                <span class="mv" contenteditable="true">{{ $chefMission['nom_complet'] ?? 'Non spécifié' }}</span>
                <span style="font-size:7.5pt;color:#777">Visa : ____________________</span>
                @php $dm = collect($equipe)->firstWhere('role', 'DM'); @endphp
                <span class="mv" contenteditable="true">{{ $dm['nom_complet'] ?? 'Non spécifié' }}</span>
                <span style="font-size:7.5pt;color:#777">Visa : ____________________</span>
            </div>
        </div>
    </div>

    <div class="conf-box">
        🔒 <strong>Déclaration de confidentialité :</strong>
        <span contenteditable="true">Ce rapport est confidentiel et s'adresse uniquement aux personnes auxquelles il est destiné.</span>
    </div>

    <div style="margin-top:8mm">
        <div class="toc-title">Table des Matières</div>
        <table class="toc-t">
            <tr class="toc-sec"><td>Section 1</td><td>Résumé Exécutif</td><td>p. 2</td></tr>
            <tr><td>1.1</td><td>Opinion générale</td><td>p. 2</td></tr>
            <tr><td>1.2</td><td>Résumé des constats (par objectif / critère)</td><td>p. 2</td></tr>
            <tr><td>1.3</td><td>Plan d'actions (par objectif / critère)</td><td>p. 3</td></tr>
            <tr><td>1.4</td><td>Résumé des points forts</td><td>p. 4</td></tr>
            <tr><td>1.5</td><td>Énoncé des normes d'audit</td><td>p. 4</td></tr>
            <tr><td>1.6</td><td>Limites de l'audit</td><td>p. 4</td></tr>
            <tr><td>1.7</td><td>Observations de la structure auditée</td><td>p. 4</td></tr>
            <tr><td>1.8</td><td>Difficultés rencontrées</td><td>p. 4</td></tr>
            <tr class="toc-sec"><td>Section 2</td><td>Tableau de Maîtrise des Risques par Objectif</td><td>p. 5</td></tr>
            <tr><td>2.1</td><td>Vue hiérarchique Objectifs › Tests › Constats</td><td>p. 5</td></tr>
            <tr class="toc-sec"><td>Section 3</td><td>Annexes</td><td>p. 9</td></tr>
        </table>
    </div>

    <div class="page-footer">
        <span>Rapport d'Audit — {{ $mission->libelle }}</span>
        <span>Page 1</span>
    </div>
</div>

{{-- ======================================================
     PAGE 2 : SECTION 1.1 – OPINION + STATS
====================================================== --}}
<div class="page">
    <div class="sec-hd"><h2>Section 1 — Résumé Exécutif</h2></div>

    {{-- 1.1 Opinion --}}
    <div class="sub-t">1.1 — Opinion Générale</div>
    <div style="margin-bottom:3mm;display:flex;align-items:center;gap:3mm;flex-wrap:wrap">
        <span style="font-size:8pt;color:#555">Niveau d'opinion retenu :</span>
        @php $niv = $opinion['niveau'] ?? 'Non déterminé'; @endphp
        @if($niv === 'Critique')   <span class="b b-crit">Critique</span>
        @elseif($niv === 'Haute')  <span class="b b-sig">Significatif</span>
        @elseif($niv === 'Moyenne')<span class="b b-less">Peu Significatif</span>
        @elseif($niv === 'Basse')  <span class="b b-house">Maintenance</span>
        @else                      <span class="b b-house">Non déterminé</span>
        @endif
    </div>
    <div class="txt-box editable-block" contenteditable="true">{{ $opinion['description'] ?? '' }}</div>

    {{-- Stats --}}
    <div class="stat-row">
        <div class="sc sc1"><div class="sn">{{ $critiques }}</div><div class="sl">Contrôle inadéquat</div></div>
        <div class="sc sc2"><div class="sn">{{ $significatifs }}</div><div class="sl">Contrôle inefficace</div></div>
        <div class="sc sc3"><div class="sn">{{ $peuSig }}</div><div class="sl">À améliorer</div></div>
        <div class="sc sc4"><div class="sn">{{ $totalConstats }}</div><div class="sl">Total constats</div></div>
    </div>

    {{-- ================================================================
         1.2 — RÉSUMÉ DES CONSTATS
         Structuré par Objectif > (sous-section par critère si critère présent)
         Chaque constat affiché avec badge importance — NON modifiable
    ================================================================ --}}
    <div class="sub-t">1.2 — Résumé des Constats</div>

    @php
        /*
         * On regroupe les constats selon la structure du tableau d'objectifs :
         * Pour chaque objectif → pour chaque test (= critère) → liste des FRAP
         * Si un objectif n'a pas de test, on liste les constats directs sous l'objectif.
         * Numérotation : 1.2.1, 1.2.2 … par objectif ; sous-section par test si plusieurs tests.
         */
        $sub12Idx = 0;
    @endphp

    @forelse($tableauObjectifs as $obj)
        @php
            $sub12Idx++;
            // Collect all constats for this objectif (from tests + direct)
            $allConstatsObj = [];
            foreach ($obj['tests'] as $t) {
                foreach ($t['constats'] as $c) { $allConstatsObj[] = ['_test' => $t, 'c' => $c]; }
            }
            foreach ($obj['constats_directs'] as $c) { $allConstatsObj[] = ['_test' => null, 'c' => $c]; }
            if (empty($allConstatsObj)) continue;

            // Does this objectif have multiple tests with their own constats?
            $hasSubTests = count(array_filter($obj['tests'], fn($t) => !empty($t['constats']))) > 0;
        @endphp

        {{-- Titre sous-section 1.2.N —  par objectif --}}
        <div style="margin-top:4mm;margin-bottom:1.5mm;">
            <span style="font-size:8pt;font-weight:bold;color:#1a3a5c">
                1.2.{{ $sub12Idx }} —
                <span contenteditable="true">{{ $obj['objectif'] }}</span>
            </span>
            @if(!empty($obj['axe']))
                <span style="font-size:7pt;color:#777;margin-left:3mm;font-style:italic">{{ $obj['axe'] }}</span>
            @endif
        </div>

        @if($hasSubTests)
            {{-- Sous-sections par test/critère --}}
            @foreach($obj['tests'] as $tIdx => $test)
                @if(empty($test['constats'])) @continue @endif
                <div style="font-size:7.5pt;font-weight:bold;color:#2c5282;margin:2mm 0 1mm 3mm;">
                    ▸ {{ $test['ref'] ?? 'T'.($tIdx+1) }} — <span contenteditable="true">{{ $test['libelle'] ?? '' }}</span>
                </div>
                <ul class="fl" style="margin-left:3mm">
                    @foreach($test['constats'] as $c)
                        @php $imp = $c['importance'] ?? 'basse'; @endphp
                        <li class="{{ $impClass($imp) }}">
                            <span style="flex:1;font-size:8pt">
                                <span style="font-family:Courier,monospace;font-size:7pt;color:#888;margin-right:2mm">{{ $c['num_frap'] ?? $c['code'] ?? '' }}</span>
                                {{ $c['probleme'] ?: substr($c['fait_constats'] ?? '', 0, 140) }}
                            </span>
                            <span class="b {{ $badgeClass($imp) }}">{{ $badgeText($imp) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
            {{-- Constats directs sous cet objectif (sans test) --}}
            @if(!empty($obj['constats_directs']))
                <ul class="fl" style="margin-left:3mm">
                    @foreach($obj['constats_directs'] as $c)
                        @php $imp = $c['importance'] ?? 'basse'; @endphp
                        <li class="{{ $impClass($imp) }}">
                            <span style="flex:1;font-size:8pt">{{ $c['probleme'] ?: substr($c['fait_constats'] ?? '', 0, 140) }}</span>
                            <span class="b {{ $badgeClass($imp) }}">{{ $badgeText($imp) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            {{-- Pas de test : liste directe sous l'objectif --}}
            <ul class="fl" style="margin-left:3mm">
                @foreach($allConstatsObj as $row)
                    @php $c = $row['c']; $imp = $c['importance'] ?? 'basse'; @endphp
                    <li class="{{ $impClass($imp) }}">
                        <span style="flex:1;font-size:8pt">
                            <span style="font-family:Courier,monospace;font-size:7pt;color:#888;margin-right:2mm">{{ $c['num_frap'] ?? $c['code'] ?? '' }}</span>
                            {{ $c['probleme'] ?: substr($c['fait_constats'] ?? '', 0, 140) }}
                        </span>
                        <span class="b {{ $badgeClass($imp) }}">{{ $badgeText($imp) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @empty
        <p style="font-style:italic;color:#aaa;font-size:8pt">Aucun constat enregistré pour cette mission.</p>
    @endforelse

    <div class="page-footer">
        <span>Rapport d'Audit — {{ $mission->libelle }} — Confidentiel</span>
        <span>Page 2</span>
    </div>
</div>

{{-- ======================================================
     PAGE 3 : SECTION 1.3 – PLAN D'ACTIONS PAR OBJECTIF/CRITÈRE
====================================================== --}}
<div class="page">
    <div class="sec-hd"><h2>Section 1 (suite) — Plan d'Actions</h2></div>

    {{-- ================================================================
         1.3 — PLAN D'ACTIONS
         Sous-sections 1.3.1, 1.3.2 … par objectif
         Chaque sous-section = tableau avec colonnes :
         # | Point de faiblesse | Recommandation | Responsable | Échéance | Priorité
         Colonnes MODIFIABLES (sauf #)
    ================================================================ --}}
    <div class="sub-t">1.3 — Plan d'Actions</div>

    @php $sub13Idx = 0; @endphp

    @forelse($tableauObjectifs as $obj)
        @php
            // Collecter toutes les actions (recommandations) de cet objectif
            $actionsObj = [];
            foreach ($obj['tests'] as $t) {
                foreach ($t['constats'] as $c) {
                    if (empty($c['recommandation'])) continue;
                    $recos = array_filter(explode("\n", $c['recommandation']), fn($r) => trim($r) !== '');
                    foreach ($recos as $reco) {
                        $actionsObj[] = [
                            'fait'        => $c['probleme'] ?: substr($c['fait_constats'] ?? '', 0, 80),
                            'reco'        => trim($reco, "•–- \t"),
                            'code'        => $c['num_frap'] ?? $c['code'] ?? '',
                            'responsable' => $c['personne_responsable'] ?? '',
                            'echeance'    => $c['date_echeance'] ?? '',
                            'priorite'    => $badgeText($c['importance'] ?? 'basse'),
                            'imp'         => $c['importance'] ?? 'basse',
                        ];
                    }
                }
            }
            foreach ($obj['constats_directs'] as $c) {
                if (empty($c['recommandation'])) continue;
                $recos = array_filter(explode("\n", $c['recommandation']), fn($r) => trim($r) !== '');
                foreach ($recos as $reco) {
                    $actionsObj[] = [
                        'fait'        => $c['probleme'] ?: substr($c['fait_constats'] ?? '', 0, 80),
                        'reco'        => trim($reco, "•–- \t"),
                        'code'        => $c['num_frap'] ?? $c['code'] ?? '',
                        'responsable' => $c['personne_responsable'] ?? '',
                        'echeance'    => $c['date_echeance'] ?? '',
                        'priorite'    => $badgeText($c['importance'] ?? 'basse'),
                        'imp'         => $c['importance'] ?? 'basse',
                    ];
                }
            }
            if (empty($actionsObj)) continue;
            $sub13Idx++;
        @endphp

        <div style="margin-top:4mm;margin-bottom:1.5mm;">
            <span style="font-size:8pt;font-weight:bold;color:#1a3a5c">
                1.3.{{ $sub13Idx }} —
                <span contenteditable="true">{{ $obj['objectif'] }}</span>
            </span>
        </div>

        <table class="at" style="margin-bottom:4mm">
            <colgroup>
                <col style="width:6mm">
                <col style="width:28%">
                <col style="width:30%">
                <col style="width:16%">
                <col style="width:10%">
                <col style="width:10%">
            </colgroup>
            <thead>
                <tr>
                    <th style="text-align:center">#</th>
                    <th>Point de faiblesse (faits / constats)</th>
                    <th>Mesures recommandées</th>
                    <th>Responsable</th>
                    <th>Échéance</th>
                    <th style="text-align:center">Priorité</th>
                </tr>
            </thead>
            <tbody>
            @foreach($actionsObj as $aIdx => $action)
                <tr>
                    <td class="at-num">{{ $aIdx + 1 }}</td>
                    <td class="at-fail">
                        <span style="font-family:Courier,monospace;font-size:6.5pt;color:#999;display:block">{{ $action['code'] }}</span>
                        <span contenteditable="true">{{ $action['fait'] }}</span>
                    </td>
                    <td class="at-reco" contenteditable="true">{{ $action['reco'] }}</td>
                    <td class="at-resp" contenteditable="true">{{ $action['responsable'] ?: '—' }}</td>
                    <td class="at-date" contenteditable="true">{{ $action['echeance'] ?: '—' }}</td>
                    <td class="at-prio"><span class="b {{ $badgeClass($action['imp']) }}">{{ $action['priorite'] }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @empty
        <p style="font-style:italic;color:#aaa;font-size:8pt">Aucune recommandation formulée.</p>
    @endforelse

    <div class="page-footer">
        <span>Rapport d'Audit — {{ $mission->libelle }} — Confidentiel</span>
        <span>Page 3</span>
    </div>
</div>

{{-- ======================================================
     PAGE 4 : SECTIONS 1.4 → 1.8  (zones de texte libres)
====================================================== --}}
<div class="page">
    <div class="sec-hd"><h2>Section 1 (suite) — Compléments du Résumé Exécutif</h2></div>

    {{-- 1.4 Résumé des Points Forts --}}
    <div class="sub-t">1.4 — Résumé des Points Forts</div>
    <div class="editable-block" contenteditable="true" style="min-height:16mm">
        @if(!empty($pointsForts))
            @foreach($pointsForts as $pf)• {{ $pf }}
            @endforeach
        @else
            Existence d'une charte éthique formalisée et signée par tous les agents. Architecture applicative redondante. Traçabilité complète des modifications dans les journaux d'audit. [Compléter ici les points forts observés…]
        @endif
    </div>

    {{-- 1.5 Énoncé des normes --}}
    <div class="sub-t" style="margin-top:4mm">1.5 — Énoncé des Normes d'Audit</div>
    <div class="editable-block" contenteditable="true" style="min-height:14mm">
        L'audit a été conduit conformément aux Normes Internationales pour la Pratique Professionnelle de l'Audit Interne (IIA). Ces normes requièrent que l'audit soit planifié et exécuté de façon à fournir une assurance raisonnable. Un audit comprend également l'évaluation des contrôles internes applicables et la vérification de la conformité aux lois et réglementations.
    </div>

    {{-- 1.6 Limites de l'audit --}}
    <div class="sub-t" style="margin-top:4mm">1.6 — Limites de l'Audit</div>
    <div class="editable-block" contenteditable="true" style="min-height:14mm">
        @if(!empty($mission->objectif))
            Périmètre limité à : {{ $mission->objectif }}. [Compléter ici les limites rencontrées : impossibilité de s'assurer que toutes les interactions sont enregistrées, incapacité à réconcilier certains fichiers, etc.]
        @else
            [Zone modifiable — ex. : Impossibilité de s'assurer que toutes les interactions clients sont enregistrées. Incapacité à réconcilier les fichiers audio avec les rapports détaillés. Accès limité à certains modules du système d'information…]
        @endif
    </div>

    {{-- 1.7 Observations de la structure auditée --}}
    <div class="sub-t" style="margin-top:4mm">1.7 — Observations Générales de la Structure Auditée</div>
    <div class="editable-block" contenteditable="true" style="min-height:14mm">
        [Zone de saisie — commentaires de la direction auditée. Ex. : L'encadrement est globalement en accord avec les livrables et les échéances proposées. Des engagements formels ont été pris pour la correction des points identifiés dans les délais convenus…]
    </div>

    {{-- 1.8 Difficultés rencontrées --}}
    <div class="sub-t" style="margin-top:4mm">1.8 — Difficultés Rencontrées au Cours de la Mission</div>
    <div class="editable-block" contenteditable="true" style="min-height:14mm">
        [Zone de saisie — ex. : Indisponibilité de certains responsables durant la phase terrain. Délais de transmission des documents requis. Accès limité à certains modules du système d'information. Absence de documentation sur certaines procédures opérationnelles…]
    </div>

    <div class="page-footer">
        <span>Rapport d'Audit — {{ $mission->libelle }} — Confidentiel</span>
        <span>Page 4</span>
    </div>
</div>

{{-- ======================================================
     PAGE 3+ : SECTION 2 – TABLEAU 3 COLONNES
====================================================== --}}
<div class="page">
    <div class="sec-hd"><h2>Section 2 — Tableau de Maîtrise des Risques par Objectif</h2></div>

    <div style="background:#f0f5ff;border:.5pt solid #aac;border-radius:2px;padding:2.5mm 4mm;font-size:8pt;color:#444;margin-bottom:5mm;line-height:1.5">
        Le tableau ci-dessous présente, pour chaque objectif d'audit, les tests réalisés (niveau 2) et les constats associés (niveau 3),
        avec le niveau de maîtrise et le plan d'action correspondant.
        Les zones en <span style="border-bottom:1px dashed #b0c4de;padding:0 2px">pointillés bleus</span> sont modifiables directement.
    </div>

    {{-- ====== BOUCLE SUR LES OBJECTIFS ====== --}}
    @forelse($tableauObjectifs as $objIdx => $obj)
        <div class="obj-section">

            {{-- Bandeau Objectif (niveau 1) --}}
            <div class="obj-banner">
                <span class="obj-num">{{ $obj['num'] }}</span>
                <span class="obj-title" contenteditable="true">{{ $obj['objectif'] }}</span>
                <span class="b {{ $badgeClass(match($obj['priorite'] ?? 'basse') { 'critique'=>'critique','haute','élevée'=>'haute','moyenne'=>'moyenne',default=>'basse' }) }}">
                    {{ ucfirst($obj['priorite'] ?? 'basse') }}
                </span>
                @if(!empty($obj['type_controle']))
                    <span class="b {{ $ctrlBadge($obj['type_controle']) }}">{{ $obj['type_controle'] }}</span>
                @endif
            </div>

            {{-- Métadonnées objectif --}}
            <div class="obj-meta">
                @if(!empty($obj['axe']))
                    <span><b>Axe :</b> <span contenteditable="true">{{ $obj['axe'] }}</span></span>
                @endif
                @if(!empty($obj['risque_code']))
                    <span><b>Risque :</b> {{ $obj['risque_code'] }} — <span contenteditable="true">{{ $obj['risque_libelle'] }}</span></span>
                @endif
                @if(!empty($obj['process_name']))
                    <span><b>Processus :</b> {{ $obj['process_name'] }}</span>
                @endif
                @if(!empty($obj['responsable']))
                    <span><b>Resp. contrôle :</b> <span contenteditable="true">{{ $obj['responsable'] }}</span></span>
                @endif
                @if(!empty($obj['source']))
                    <span style="color:#888;font-style:italic">Source : {{ $obj['source'] }}</span>
                @endif
            </div>

            @if(!empty($obj['criteres_eval']))
                <div style="background:#fffde7;border:.5pt solid #ffe082;border-radius:2px;padding:2mm 4mm;font-size:7.5pt;color:#555;margin-bottom:2mm;line-height:1.45">
                    <b style="color:#7a4a0a">Critères d'évaluation :</b>
                    <span contenteditable="true">{{ $obj['criteres_eval'] }}</span>
                </div>
            @endif

            {{-- Tableau 3 colonnes --}}
            <table class="rapport-table">
                <colgroup>
                    <col style="width:44%">
                    <col style="width:24%">
                    <col style="width:32%">
                </colgroup>
                <thead>
                    <tr>
                        <th>🔍 Activités / Critères / Constats</th>
                        <th>📊 Niveau de Maîtrise</th>
                        <th>📋 Plan d'Action</th>
                    </tr>
                </thead>
                <tbody>

                @php $hasContent = false; @endphp

                {{-- ---- Niveau 2 : Tests ---- --}}
                @foreach($obj['tests'] as $testIdx => $test)
                    @php $hasContent = true; @endphp

                    {{-- Ligne test (niveau 2) --}}
                    <tr class="row-test">
                        <td colspan="3">
                            🧪 Test {{ $test['ref'] ?? ($testIdx + 1) }} :
                            <span contenteditable="true">{{ $test['libelle'] ?? 'Test sans libellé' }}</span>
                            @if(!empty($test['auditeur']))
                                <span style="font-weight:normal;font-size:7.5pt;color:#1a5c8a;margin-left:4mm">👤 {{ $test['auditeur'] }}</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Procédures du test --}}
                    @if(!empty($test['procedures']))
                        <tr>
                            <td colspan="3" style="background:#f5f8ff;border:.5pt solid #dce8f5;padding:2mm 4mm;">
                                <span style="font-size:7pt;font-weight:bold;color:#555;text-transform:uppercase">Procédures de test :</span>
                                <ul class="proc-list">
                                    @foreach($test['procedures'] as $proc)
                                        <li contenteditable="true">{{ $proc }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif

                    {{-- ---- Niveau 3 : Constats du test ---- --}}
                    @forelse($test['constats'] as $cIdx => $c)
                        @php
                            $imp    = $c['importance'] ?? 'basse';
                            $statut = $c['statut']    ?? 'draft';
                            $causes  = array_filter(explode("\n", $c['causes']  ?? ''), fn($s) => trim($s) !== '');
                            $impacts = array_filter(explode("\n", $c['impacts'] ?? ''), fn($s) => trim($s) !== '');
                            $recos   = array_filter(explode("\n", $c['recommandation'] ?? ''), fn($s) => trim(str_replace(['•','–','-'], '', $s)) !== '');
                        @endphp
                        <tr class="row-constat">
                            {{-- Col 1 : Activités / constat --}}
                            <td>
                                <span class="col1-frap-id">{{ $c['num_frap'] ?? $c['code'] ?? '' }}</span>
                                <span class="col1-label">Fait constaté</span>
                                <p class="col1-fact" contenteditable="true">{{ $c['fait_constats'] ?? 'Non renseigné' }}</p>

                                @if(!empty($c['probleme']))
                                    <span class="col1-label">Problème identifié</span>
                                    <p class="col1-fact" contenteditable="true">{{ $c['probleme'] }}</p>
                                @endif

                                @if($causes)
                                    <span class="col1-label">Causes</span>
                                    <ul style="margin-left:4mm;margin-bottom:2mm">
                                        @foreach($causes as $cause)
                                            <li class="col1-cause" contenteditable="true">{{ trim($cause, "•–- \t") }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if($impacts)
                                    <span class="col1-label">Impacts</span>
                                    <ul style="margin-left:4mm">
                                        @foreach($impacts as $impact)
                                            <li class="col1-impact" contenteditable="true">{{ trim($impact, "•–- \t") }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>

                            {{-- Col 2 : Niveau de maîtrise --}}
                            <td>
                                <div class="nmc-niveau">
                                    <span class="b {{ $badgeClass($imp) }}">{{ $badgeText($imp) }}</span>
                                </div>
                                <div class="nmc-statut">
                                    Statut : <span contenteditable="true">{{ $statutLabel($statut) }}</span>
                                </div>
                                @if(!empty($c['niveau_controle_interne']))
                                    <div class="nmc-indicateur" contenteditable="true">
                                        Indicateur : {{ $c['niveau_controle_interne'] }}
                                    </div>
                                @endif
                                @if(!empty($c['commentaires_audite']))
                                    <div style="margin-top:2mm;font-size:7.5pt;color:#555;border-top:.5pt solid #eee;padding-top:2mm">
                                        <b>Note audité :</b><br>
                                        <span contenteditable="true">{{ $c['commentaires_audite'] }}</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Col 3 : Plan d'action --}}
                            <td>
                                @if($recos)
                                    <span class="col1-label">Recommandation(s)</span>
                                    @foreach($recos as $reco)
                                        <p class="pa-reco" contenteditable="true">{{ trim($reco, "•–- \t") }}</p>
                                    @endforeach
                                @else
                                    <p class="pa-reco" style="font-style:italic;color:#aaa" contenteditable="true">Aucune recommandation</p>
                                @endif

                                @if(!empty($c['personne_responsable']))
                                    <div class="pa-resp">👤 <span contenteditable="true">{{ $c['personne_responsable'] }}</span></div>
                                @endif
                                @if(!empty($c['date_echeance']))
                                    <div class="pa-echeance">📅 <span contenteditable="true">{{ $c['date_echeance'] }}</span></div>
                                @endif
                                @if(!empty($c['livrable']))
                                    <div class="pa-livrable" contenteditable="true">{{ $c['livrable'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="row-empty">
                            <td colspan="3">Aucun constat enregistré pour ce test.</td>
                        </tr>
                    @endforelse
                @endforeach

                {{-- Constats directs (sans test associé) --}}
                @foreach($obj['constats_directs'] as $c)
                    @php $hasContent = true; $imp = $c['importance'] ?? 'basse'; $statut = $c['statut'] ?? 'draft';
                         $causes  = array_filter(explode("\n", $c['causes']  ?? ''), fn($s) => trim($s) !== '');
                         $impacts = array_filter(explode("\n", $c['impacts'] ?? ''), fn($s) => trim($s) !== '');
                         $recos   = array_filter(explode("\n", $c['recommandation'] ?? ''), fn($s) => trim(str_replace(['•','–','-'], '', $s)) !== '');
                    @endphp
                    <tr class="row-constat">
                        <td>
                            <span class="col1-frap-id">{{ $c['num_frap'] ?? $c['code'] ?? '' }}</span>
                            <span class="col1-label">Fait constaté</span>
                            <p class="col1-fact" contenteditable="true">{{ $c['fait_constats'] ?? 'Non renseigné' }}</p>
                            @if(!empty($c['probleme']))<p class="col1-fact" contenteditable="true">{{ $c['probleme'] }}</p>@endif
                            @if($causes)<ul style="margin-left:4mm">@foreach($causes as $ca)<li class="col1-cause" contenteditable="true">{{ trim($ca, "•–- \t") }}</li>@endforeach</ul>@endif
                            @if($impacts)<ul style="margin-left:4mm">@foreach($impacts as $im)<li class="col1-impact" contenteditable="true">{{ trim($im, "•–- \t") }}</li>@endforeach</ul>@endif
                        </td>
                        <td>
                            <div class="nmc-niveau"><span class="b {{ $badgeClass($imp) }}">{{ $badgeText($imp) }}</span></div>
                            <div class="nmc-statut">Statut : <span contenteditable="true">{{ $statutLabel($statut) }}</span></div>
                        </td>
                        <td>
                            @foreach($recos as $reco)<p class="pa-reco" contenteditable="true">{{ trim($reco, "•–- \t") }}</p>@endforeach
                            @if(!empty($c['personne_responsable']))<div class="pa-resp">👤 <span contenteditable="true">{{ $c['personne_responsable'] }}</span></div>@endif
                            @if(!empty($c['date_echeance']))<div class="pa-echeance">📅 <span contenteditable="true">{{ $c['date_echeance'] }}</span></div>@endif
                        </td>
                    </tr>
                @endforeach

                @if(!$hasContent)
                    <tr class="row-empty"><td colspan="3">Aucun test ni constat pour cet objectif.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        {{-- Saut de page entre objectifs (sauf le dernier) --}}
        @if(!$loop->last)
            <div style="page-break-after:always"></div>
        @endif
    @empty
        <div class="ph">Aucun objectif de programme de travail trouvé pour cette mission.</div>
    @endforelse

    <div class="page-footer">
        <span>Rapport d'Audit — {{ $mission->libelle }} — Confidentiel</span>
        <span>Section 2</span>
    </div>
</div>

{{-- ======================================================
     PAGE FINALE : SECTION 3 — ANNEXES
     ⚠️ Cette section est conservée TELLE QUELLE (cf. demande)
====================================================== --}}
<div class="page">
    <div class="sec-hd"><h2>Section 3 — Annexes</h2></div>

    <div class="sub-t">Annexe 1 — Objectifs spécifiques d'audit</div>

    {{-- Sous-tableau A : identification des objectifs (3 colonnes lisibles) --}}
    <div style="font-size:7pt;font-weight:bold;color:#2c5282;text-transform:uppercase;letter-spacing:.3px;margin-bottom:1.5mm">
        A — Identification des objectifs
    </div>
    <table class="ant" style="margin-bottom:3mm">
        <colgroup>
            <col style="width:8mm">
            <col style="width:18%">
            <col style="width:42%">
            <col style="width:36%">
        </colgroup>
        <thead>
            <tr>
                <th style="text-align:center">#</th>
                <th>Axe RADO</th>
                <th>Objectif de contrôle</th>
                <th>Risque associé / Source</th>
            </tr>
        </thead>
        <tbody>
        @forelse($objectifsSpecifiques as $obj)
            @php
                // Chercher l'objectif correspondant dans le tableau pour récupérer risque et source
                $objData = collect($tableauObjectifs)->firstWhere('num', $obj['num']);
            @endphp
            <tr>
                <td style="text-align:center;font-family:Courier,monospace;font-weight:bold;color:#1a3a5c">{{ $obj['num'] }}</td>
                <td contenteditable="true">{{ $obj['axe'] ?: '—' }}</td>
                <td contenteditable="true">{{ $obj['objectif'] }}</td>
                <td style="font-size:7pt;color:#555">
                    @if(!empty($objData['risque_code']))
                        <span style="font-family:Courier,monospace;font-weight:bold;color:#1a3a5c">{{ $objData['risque_code'] }}</span>
                        — {{ $objData['risque_libelle'] ?? '' }}<br>
                    @endif
                    @if(!empty($objData['source']))
                        <span style="color:#888;font-style:italic">{{ $objData['source'] }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;color:#aaa;font-style:italic">Aucun objectif défini.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Sous-tableau B : critères d'évaluation (résumé tronqué) --}}
    <div style="font-size:7pt;font-weight:bold;color:#2c5282;text-transform:uppercase;letter-spacing:.3px;margin-bottom:1.5mm">
        B — Critères d'évaluation par objectif
    </div>
    <table class="ant" style="margin-bottom:4mm">
        <colgroup>
            <col style="width:8mm">
            <col style="width:92%">
        </colgroup>
        <thead>
            <tr>
                <th style="text-align:center">#</th>
                <th>Critères d'évaluation</th>
            </tr>
        </thead>
        <tbody>
        @forelse($objectifsSpecifiques as $obj)
            <tr>
                <td style="text-align:center;font-family:Courier,monospace;font-weight:bold;color:#1a3a5c;vertical-align:top">{{ $obj['num'] }}</td>
                <td style="font-size:7pt;line-height:1.5;color:#444" contenteditable="true">
                    {{ $obj['criteres_evaluation'] ?: '—' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="2" style="text-align:center;color:#aaa;font-style:italic">Aucun critère défini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="sub-t">Annexe 2 — Critères d'évaluation du contrôle interne</div>
    <div class="txt-box">
        @if(!empty($criteresCI))
            <ul style="margin-left:4mm">
            @foreach($criteresCI as $crit)
                <li contenteditable="true">{{ $crit['critere'] ?? $crit['point_controle'] ?? 'Critère non défini' }}</li>
            @endforeach
            </ul>
        @else
            <span contenteditable="true">Les critères d'évaluation sont ceux du référentiel COSO/INTOSAI adaptés à l'entité auditée.</span>
        @endif
    </div>

    <div class="sub-t">Annexe 3 — Liste des destinataires</div>
    <ul style="margin-left:4mm;font-size:8.5pt;line-height:1.8">
        @forelse($destinataires as $dest)
            <li contenteditable="true">{{ $dest }}</li>
        @empty
            <li>Aucun destinataire enregistré.</li>
        @endforelse
    </ul>

    <div class="sub-t" style="margin-top:5mm">Annexe 4 — Équipe d'audit</div>
    <table class="ant">
        <colgroup>
            <col style="width:42%">
            <col style="width:18%">
            <col style="width:40%">
        </colgroup>
        <thead>
            <tr>
                <th>Nom & Prénom</th>
                <th>Rôle</th>
                <th>Code auditeur</th>
            </tr>
        </thead>
        <tbody>
        @forelse($equipe as $membre)
            <tr>
                <td contenteditable="true">{{ $membre['nom_complet'] }}</td>
                <td>{{ $membre['role'] }}</td>
                <td style="font-family:Courier,monospace">{{ $membre['audit_code'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Équipe non renseignée.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:6mm">
        <div class="sub-t">Signatures</div>
        <div class="sig-row">
            <div class="sig-box">
                <span class="sl">Chef de Mission</span>
                <div class="sli"></div>
                <div class="sm"><span>Nom :</span><span>Date :</span></div>
            </div>
            <div class="sig-box">
                <span class="sl">Directeur de Mission</span>
                <div class="sli"></div>
                <div class="sm"><span>Nom :</span><span>Date :</span></div>
            </div>
            <div class="sig-box">
                <span class="sl">Responsable Entité Auditée</span>
                <div class="sli"></div>
                <div class="sm"><span>Nom :</span><span>Date :</span></div>
            </div>
        </div>
    </div>

    <div class="page-footer">
        <span>Rapport d'Audit Interne — {{ $mission->libelle }} — Confidentiel — {{ date('d/m/Y') }}</span>
        <span>Section 3 / Fin du rapport</span>
    </div>
</div>

{{-- ======================================================
     SCRIPT — ZONES ÉDITABLES & SAUVEGARDE
====================================================== --}}
<script>
/**
 * Sauvegarde locale des modifications dans le rapport.
 * Copie le contenu HTML modifié dans le presse-papiers
 * pour que l'utilisateur puisse le coller dans un éditeur.
 */
function saveEdits() {
    try {
        const html = document.documentElement.outerHTML;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(html).then(() => {
                showToast('✅ HTML copié dans le presse-papiers.');
            }).catch(() => fallbackCopy(html));
        } else {
            fallbackCopy(html);
        }
    } catch (e) {
        showToast('⚠️ Impossible de copier : ' + e.message);
    }
}

function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('✅ HTML copié (fallback).');
}

function showToast(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#1a3a5c;color:#fff;padding:8px 16px;border-radius:4px;font-size:8pt;z-index:99999;font-family:Arial,sans-serif;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// Indicateur visuel sur chaque zone focalisée
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.addEventListener('focus', () => el.style.outline = 'none');
        el.addEventListener('input', () => {
            // Marqueur non-intrusif que la zone a été modifiée
            el.dataset.modified = 'true';
        });
    });
});
</script>

</body>
</html>