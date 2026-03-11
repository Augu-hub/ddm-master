<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>

/* ══════════════════════════════════════════════
   RESET + PAGE
══════════════════════════════════════════════ */
@page {
  size: A4 portrait;
  margin: 0mm;
}

* { margin:0; padding:0; box-sizing:border-box; }

html, body {
  font-family: DejaVu Sans, sans-serif;
  font-size: 9pt;
  color: #1a1a2e;
  background: #ffffff;
  width: 210mm;
}

/* ══════════════════════════════════════════════
   ANTI-COUPURE — règles globales
   DomPDF honore page-break-inside sur block + table
══════════════════════════════════════════════ */
.no-break {
  page-break-inside: avoid;
  break-inside: avoid;
}

/* Chaque section (stitle + contenu) ne se coupe pas */
.section-block {
  page-break-inside: avoid;
  break-inside: avoid;
  margin-bottom: 6mm;
}

/* Chaque ligne de tableau ne se coupe pas */
tr {
  page-break-inside: avoid;
  break-inside: avoid;
}

/* Les blocs de champs info ne se coupent pas */
.row2, .field-group {
  page-break-inside: avoid;
  break-inside: avoid;
}

/* Forcer un saut AVANT une section si besoin */
.break-before {
  page-break-before: always;
}

/* ══════════════════════════════════════════════
   FILIGRANE
══════════════════════════════════════════════ */
.watermark {
  position: fixed;
  top: 108mm; left: 15mm;
  font-size: 64pt; font-weight: 900;
  color: rgba(21,128,61,0.05);
  text-transform: uppercase;
  letter-spacing: 10px;
  transform: rotate(-32deg);
  white-space: nowrap;
  z-index: 0;
}

/* ══════════════════════════════════════════════
   FOOTER FIXE
══════════════════════════════════════════════ */
.page-footer-fixed {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: 10mm;
  background: #fff;
  border-top: 1pt solid #e2e8f0;
  padding: 0 14mm;
  z-index: 100;
}
.pf-table { width: 100%; border-collapse: collapse; }
.pf-table td {
  font-size: 6.5pt; color: #94a3b8;
  padding: 3mm 0; vertical-align: middle;
}
.pf-r { text-align: right; }

/* ══════════════════════════════════════════════
   WRAPPER PRINCIPAL
══════════════════════════════════════════════ */
.wrap {
  padding: 0 14mm 16mm;
  position: relative;
  z-index: 1;
}

/* ══════════════════════════════════════════════
   HEADER BLEU — ne jamais couper
══════════════════════════════════════════════ */
.hdr {
  background: #1e3a6e;
  color: #fff;
  margin: 0 -14mm;
  padding: 10mm 14mm 9mm;
  margin-bottom: 8mm;
  position: relative;
  page-break-inside: avoid;
  break-inside: avoid;
  page-break-after: avoid;
  break-after: avoid;
}
.hdr-year {
  position: absolute; top: 3.5mm; right: 14mm;
  font-size: 6pt; color: rgba(255,255,255,0.35);
  letter-spacing: 1.5px; text-transform: uppercase;
}
.hdr-badge {
  float: right; margin-top: 2mm;
  padding: 1.2mm 4.5mm; border-radius: 3mm;
  font-size: 7pt; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.b-ok  { background:rgba(220,252,231,.2); border:1pt solid rgba(134,239,172,.5); color:#86efac; }
.b-rev { background:rgba(219,234,254,.2); border:1pt solid rgba(147,197,253,.5); color:#93c5fd; }
.b-dft { background:rgba(241,245,249,.15);border:1pt solid rgba(148,163,184,.4); color:#cbd5e1; }

.hdr-eyebrow {
  font-size: 6.5pt; font-weight: 700; text-transform: uppercase;
  letter-spacing: 1.5px; color: rgba(255,255,255,0.45);
  margin-bottom: 2mm;
}
.hdr-title { font-size: 21pt; font-weight: 700; line-height: 1.1; margin-bottom: 2.5mm; }
.hdr-sub   { font-size: 9pt; color: rgba(255,255,255,0.72); line-height: 1.5; }

/* Bande décorative sous le header */
.hdr-stripe {
  margin: 0 -14mm 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6 0%, #1e3a6e 60%, #0f172a 100%);
}

/* ══════════════════════════════════════════════
   GRILLE INFOS 2 COLONNES (float — DomPDF safe)
══════════════════════════════════════════════ */
.row2 { width: 100%; overflow: hidden; margin-bottom: 4mm; }
.row2::after { content:''; display:block; clear:both; }
.col-l { float:left; width:48%; margin-right:4%; }
.col-r { float:left; width:48%; }

.flbl {
  font-size: 6pt; font-weight:700; color:#64748b;
  text-transform:uppercase; letter-spacing:.9px; margin-bottom:1mm;
}
.fval {
  font-size: 11pt; font-weight:700; color:#1a1a2e;
  border-bottom: 2pt solid #1e3a6e;
  padding-bottom:1mm; min-height:5mm;
  line-height: 1.3;
}
.fval.sm   { font-size:9pt; font-weight:600; }
.fval.mono { font-family: DejaVu Sans Mono, monospace; color:#1e3a6e; font-size:9pt; }
.fval.lt   { color:#475569; font-weight:400; font-size:9pt; }

/* ══════════════════════════════════════════════
   SÉPARATEURS
══════════════════════════════════════════════ */
.sep  { border:none; border-top:0.5pt solid #e2e8f0; margin:4mm 0; clear:both; page-break-after:avoid; }
.sep2 { border:none; border-top:2pt solid #1e3a6e;  margin:6mm 0;  clear:both; page-break-after:avoid; }

/* ══════════════════════════════════════════════
   TITRE DE SECTION
══════════════════════════════════════════════ */
.stitle {
  font-size: 7pt; font-weight:700; text-transform:uppercase;
  letter-spacing: 1.2px; color:#1e3a6e;
  border-left: 3.5pt solid #3b82f6; padding-left:3mm;
  margin-bottom: 3.5mm;
  page-break-after: avoid;
  break-after: avoid;
  clear:both;
}

/* ══════════════════════════════════════════════
   TABLE GÉNÉRIQUE BASE
══════════════════════════════════════════════ */
table.base-tbl {
  width:100%; border-collapse:collapse;
  font-size:8.5pt;
  page-break-inside: auto; /* les lignes individuelles évitent la coupure via tr */
}
table.base-tbl thead {
  display: table-header-group; /* répète le header si déborde */
}
table.base-tbl thead tr {
  background:#1e3a6e; color:#fff;
  page-break-inside: avoid; break-inside: avoid;
}
table.base-tbl thead th {
  padding: 2.5mm 3.5mm; font-size:7pt; font-weight:700;
  text-align:left; text-transform:uppercase; letter-spacing:.6px;
}
table.base-tbl thead th.tc { text-align:center; }
table.base-tbl tbody tr {
  border-bottom: .5pt solid #e2e8f0;
  page-break-inside: avoid; break-inside: avoid;
}
table.base-tbl tbody tr:nth-child(even) { background:#f8fafc; }
table.base-tbl tbody td {
  padding: 2.2mm 3.5mm; vertical-align: middle; line-height: 1.5;
}
table.base-tbl tbody td.tc { text-align:center; color:#1e3a6e; font-weight:700; }

/* ══════════════════════════════════════════════
   CHECKBOX DomPDF-safe
══════════════════════════════════════════════ */
.cb {
  display:inline-block;
  width:3.2mm; height:3.2mm;
  border: 1.2pt solid #94a3b8;
  background:#fff;
  vertical-align:middle;
  margin-right:1.5mm;
}
.cb-ok { background:#1e3a6e; border-color:#1e3a6e; }

/* ══════════════════════════════════════════════
   PARTICIPANTS — colonne signature plus large
══════════════════════════════════════════════ */
table.tp-tbl thead th.sig-col { width: 30mm; }
table.tp-tbl tbody td.sig {
  border-bottom: 1pt solid #94a3b8;
  width: 30mm; height: 8mm;
}

/* ══════════════════════════════════════════════
   POINTS GÉNÉRAUX
══════════════════════════════════════════════ */
table.tg-tbl tbody td.num {
  width: 9mm; font-weight:700; color:#1e3a6e; font-size:8pt;
  vertical-align: top; padding-top: 2.5mm;
}
table.tg-tbl tbody td {
  vertical-align: top; padding-top: 2.5mm;
}

/* ══════════════════════════════════════════════
   PRÉOCCUPATIONS
══════════════════════════════════════════════ */
table.tpr-tbl tbody td.lv {
  width: 20mm; font-size:7pt; font-weight:700;
  text-transform: uppercase; vertical-align: top; padding-top: 2.5mm;
}
.lv-h { color:#dc2626; } .lv-m { color:#d97706; } .lv-l { color:#16a34a; }

/* Badge niveau */
.badge-niv {
  display: inline-block;
  padding: .4mm 2mm; border-radius: 1mm;
  font-size: 6.5pt; font-weight: 700; text-transform: uppercase;
}
.badge-h { background:#fef2f2; color:#dc2626; border:.5pt solid #fca5a5; }
.badge-m { background:#fffbeb; color:#d97706; border:.5pt solid #fcd34d; }
.badge-l { background:#f0fdf4; color:#16a34a; border:.5pt solid #86efac; }

/* ══════════════════════════════════════════════
   NOTES
══════════════════════════════════════════════ */
.notes-box {
  border: .5pt solid #e2e8f0;
  background: #fafbfc;
  padding: 4mm 5mm;
  min-height: 18mm;
  font-size: 8.5pt;
  line-height: 1.7;
  margin-bottom: 4mm;
  page-break-inside: avoid;
  break-inside: avoid;
}

/* ══════════════════════════════════════════════
   BLOC VALIDATION
══════════════════════════════════════════════ */
.vbox {
  background: #f0fdf4;
  border: 1pt solid #86efac;
  border-left: 4pt solid #16a34a;
  padding: 3.5mm 5mm;
  margin: 4mm 0;
  page-break-inside: avoid;
  break-inside: avoid;
}
.vbox-title {
  font-size: 6.5pt; font-weight:700; color:#15803d;
  text-transform: uppercase; letter-spacing:.8px; margin-bottom: 2mm;
}
table.tv { width:100%; border-collapse:collapse; }
table.tv td {
  font-size:8.5pt; padding: 1mm 0; vertical-align: top; line-height:1.5;
}
table.tv td.vl {
  font-size: 6.5pt; font-weight:700; color:#15803d;
  text-transform: uppercase; width: 30mm;
}

/* ══════════════════════════════════════════════
   SIGNATURES
══════════════════════════════════════════════ */
.sig-section {
  page-break-inside: avoid;
  break-inside: avoid;
  margin-top: 10mm;
}
table.ts { width:100%; border-collapse:collapse; }
table.ts td { width:50%; padding-right: 12mm; vertical-align: bottom; }
table.ts td:last-child { padding-right: 0; }
.slbl {
  font-size: 6.5pt; font-weight:700; color:#64748b;
  text-transform: uppercase; letter-spacing:.7px; margin-bottom: 7mm;
}
.sline {
  border-top: 1.5pt solid #1a1a2e;
  padding-top: 2mm; font-size: 8pt; color:#475569;
}

/* ══════════════════════════════════════════════
   DIVIDER DÉCORATIF
══════════════════════════════════════════════ */
.deco-div {
  clear: both;
  height: .5mm;
  background: linear-gradient(90deg, #1e3a6e 0%, #3b82f6 50%, transparent 100%);
  margin: 5mm 0;
  border: none;
  page-break-after: avoid;
}

</style>
</head>
<body>

@if($fro->validation_status === 'validated')
<div class="watermark">VALIDÉ</div>
@endif

{{-- ══ FOOTER FIXE ══ --}}
<div class="page-footer-fixed">
  <table class="pf-table"><tr>
    <td>DIADDEM &mdash; Audit Core &nbsp;&bull;&nbsp; {{ $mission->code_mission ?? '' }} &nbsp;&bull;&nbsp; {{ $fro->code_fro }}</td>
    <td class="pf-r">Généré le {{ now()->translatedFormat('d F Y') }}</td>
  </tr></table>
</div>

<div class="wrap">

  {{-- ══ HEADER ══ --}}
  <div class="hdr no-break">
    <div class="hdr-year">{{ now()->year }}</div>
    @php
      $bmap = ['draft'=>'b-dft','in_review'=>'b-rev','validated'=>'b-ok'];
      $lmap = ['draft'=>'Brouillon','in_review'=>'En révision','validated'=>'Validé'];
    @endphp
    <span class="hdr-badge {{ $bmap[$fro->validation_status] ?? 'b-dft' }}">
      {{ $lmap[$fro->validation_status] ?? $fro->validation_status }}
    </span>
    <div class="hdr-eyebrow">Audit Core &mdash; Préparation de mission</div>
    <div class="hdr-title">Audit Meeting Form</div>
    <div class="hdr-sub">
      Réunion d'Ouverture
      @if(!empty($mission->code_mission)) &mdash; {{ $mission->code_mission }} @endif
      @if(!empty($mission->libelle)) &nbsp;&bull;&nbsp; {{ $mission->libelle }} @endif
    </div>
  </div>
  <div class="hdr-stripe"></div>

  {{-- ══ INFOS GÉNÉRALES ══ --}}
  <div class="section-block">

    {{-- Heure + Date --}}
    <div class="row2">
      <div class="col-l">
        <div class="flbl">Time (Heure)</div>
        <div class="fval">
          @php
            try { echo $fro->heure_debut ? \Carbon\Carbon::createFromFormat('H:i:s',$fro->heure_debut)->format('H\hi') : '—'; }
            catch (\Exception $e) { echo $fro->heure_debut ?? '—'; }
          @endphp
          @if($fro->heure_fin)
            &nbsp;&ndash;&nbsp;
            @php
              try { echo \Carbon\Carbon::createFromFormat('H:i:s',$fro->heure_fin)->format('H\hi'); }
              catch (\Exception $e) { echo $fro->heure_fin; }
            @endphp
          @endif
        </div>
      </div>
      <div class="col-r">
        <div class="flbl">Date</div>
        <div class="fval">{{ $fro->date_reunion ? \Carbon\Carbon::parse($fro->date_reunion)->translatedFormat('d F Y') : '—' }}</div>
      </div>
    </div>

    {{-- Code + Entité --}}
    <div class="row2">
      <div class="col-l">
        <div class="flbl">Code FRO</div>
        <div class="fval mono">{{ $fro->code_fro }}</div>
      </div>
      <div class="col-r">
        <div class="flbl">Entité auditée</div>
        <div class="fval sm">{{ $mission->entity_name ?? '—' }}</div>
      </div>
    </div>

    {{-- Lieu + Phase --}}
    <div class="row2">
      <div class="col-l">
        <div class="flbl">Lieu</div>
        <div class="fval sm lt">{{ $fro->lieu ?? '—' }}</div>
      </div>
      <div class="col-r">
        <div class="flbl">Phase</div>
        <div class="fval sm lt">{{ $assignment->phase_label ?? ($assignment->phase_code ?? '—') }}</div>
      </div>
    </div>

    {{-- Fait par + Revu par --}}
    <div class="row2">
      <div class="col-l">
        <div class="flbl">Fait par</div>
        <div class="fval sm">{{ $fro->fait_par ?? '—' }}</div>
      </div>
      <div class="col-r">
        <div class="flbl">Revu par</div>
        <div class="fval sm lt">{{ $fro->revue_par ?: '—' }}</div>
      </div>
    </div>

  </div>{{-- /section-block infos --}}

  <div class="deco-div"></div>

  {{-- ══ ORDRE DU JOUR ══ --}}
  @if(count($ordreduJour) > 0)
  <div class="section-block">
    <div class="stitle">Introduction &mdash; Ordre du jour</div>
    <table class="base-tbl no-break">
      <thead>
        <tr>
          <th style="width:8mm"></th>
          <th>Point / Sujet</th>
          <th class="tc" style="width:18mm">Durée</th>
          <th class="tc" style="width:18mm">Traité</th>
        </tr>
      </thead>
      <tbody>
        @foreach($ordreduJour as $i => $point)
        <tr>
          <td><span class="cb {{ $fro->validation_status==='validated'?'cb-ok':'' }}"></span></td>
          <td>{{ $point['point'] ?? $point['libelle'] ?? 'Point '.($i+1) }}</td>
          <td class="tc">&#10003;</td>
          <td class="tc">&#10003;</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <hr class="sep">
  @endif

  {{-- ══ PARTICIPANTS ══ --}}
  @if(count($participants) > 0)
  <div class="section-block">
    <div class="stitle">Participants</div>
    <table class="base-tbl tp-tbl">
      <thead>
        <tr>
          <th>Nom &amp; Prénom</th>
          <th>Fonction / Structure</th>
          <th>Contact</th>
          <th class="sig-col">Signature</th>
        </tr>
      </thead>
      <tbody>
        @foreach($participants as $p)
        <tr>
          <td>{{ trim(($p['nom']??'').' '.($p['prenom']??'')) ?: ($p['nom_prenom']??'—') }}</td>
          <td>{{ $p['fonction'] ?? $p['structure'] ?? '—' }}</td>
          <td>{{ $p['email'] ?? $p['contact'] ?? '' }}</td>
          <td class="sig"></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <hr class="sep">
  @endif

  {{-- ══ POINTS GÉNÉRAUX ══ --}}
  @if(count($pointsGeneraux) > 0)
  <div class="section-block">
    <div class="stitle">Points généraux discutés</div>
    <table class="base-tbl tg-tbl no-break">
      <tbody>
        @foreach($pointsGeneraux as $i => $pt)
        <tr>
          <td class="num">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}.</td>
          <td>{{ $pt['libelle'] ?? $pt['description'] ?? (is_string($pt)?$pt:'Point '.($i+1)) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <hr class="sep">
  @endif

  {{-- ══ PRÉOCCUPATIONS ══ --}}
  @if(count($preoccupations) > 0)
  <div class="section-block">
    <div class="stitle">Préoccupations &amp; Points d'attention</div>
    <table class="base-tbl tpr-tbl no-break">
      <tbody>
        @foreach($preoccupations as $pr)
        @php
          $niv = strtolower($pr['niveau'] ?? $pr['niveau_risque'] ?? 'moyen');
          $isH = in_array($niv,['élevé','eleve','critique','high','haut']);
          $isM = in_array($niv,['moyen','medium','modéré','modere']);
          $cls = $isH ? 'lv-h' : ($isM ? 'lv-m' : 'lv-l');
          $badgeCls = $isH ? 'badge-h' : ($isM ? 'badge-m' : 'badge-l');
        @endphp
        <tr>
          <td class="lv">
            <span class="badge-niv {{ $badgeCls }}">{{ strtoupper($niv) }}</span>
          </td>
          <td>{{ $pr['description'] ?? $pr['libelle'] ?? (is_string($pr)?$pr:'') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <hr class="sep">
  @endif

  {{-- ══ NOTES ══ --}}
  <div class="section-block">
    <div class="stitle">Notes &amp; Conclusions</div>
    <div class="notes-box">
      Toutes les parties conviennent de la méthodologie et du calendrier présentés.
      @if($fro->validation_note)
        <br><br><strong>Note&nbsp;:</strong> {{ $fro->validation_note }}
      @endif
    </div>
  </div>

  <div class="deco-div"></div>

  {{-- ══ BLOC VALIDATION ══ --}}
  @if($fro->validation_status === 'validated')
  <div class="vbox section-block">
    <div class="vbox-title">&#10003;&nbsp; Document validé</div>
    <table class="tv">
      <tr>
        <td class="vl">Validé le</td>
        <td>{{ $fro->validated_at ? \Carbon\Carbon::parse($fro->validated_at)->translatedFormat('d F Y \à H:i') : '—' }}</td>
        <td class="vl" style="padding-left:8mm; width:30mm;">Validé par</td>
        <td>{{ $validatedBy ?? '—' }}</td>
      </tr>
    </table>
  </div>
  @endif

  {{-- ══ SIGNATURES ══ --}}
  <div class="sig-section">
    <div class="stitle">Signatures</div>
    <table class="ts">
      <tr>
        <td>
          <div class="slbl">Nom imprimé</div>
          <div class="sline">{{ $fro->fait_par ?? '—' }}</div>
        </td>
        <td>
          <div class="slbl">Signature</div>
          <div class="sline">&nbsp;</div>
        </td>
      </tr>
      <tr>
        <td style="padding-top:8mm">
          <div class="slbl">Nom imprimé</div>
          <div class="sline">&nbsp;</div>
        </td>
        <td style="padding-top:8mm">
          <div class="slbl">Signature</div>
          <div class="sline">&nbsp;</div>
        </td>
      </tr>
    </table>
  </div>

</div>{{-- /wrap --}}
</body>
</html>