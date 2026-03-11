<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 0mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:9pt; color:#1a1a2e; background:#fff; width:210mm; }

/* Filigrane */
.watermark { position:fixed; top:120mm; left:15mm; font-size:62pt; font-weight:900; color:rgba(21,128,61,0.055); text-transform:uppercase; letter-spacing:8px; transform:rotate(-35deg); white-space:nowrap; }

/* Footer fixe */
.pf { position:fixed; bottom:0; left:0; right:0; height:9mm; background:#fff; border-top:0.5pt solid #e2e8f0; padding:0 14mm; }
.pf table { width:100%; border-collapse:collapse; }
.pf td { font-size:6.5pt; color:#94a3b8; padding:2.5mm 0; vertical-align:middle; }
.pf-r { text-align:right; }

/* Contenu */
.wrap { padding:0 14mm 13mm; }

/* Header bleu */
.hdr { background:#1e3a6e; color:#fff; margin:0 -14mm; padding:10mm 14mm 8mm; margin-bottom:6mm; position:relative; }
.hdr-year { position:absolute; top:3.5mm; right:14mm; font-size:6.5pt; color:rgba(255,255,255,0.4); }
.hdr-badge { float:right; margin-top:1.5mm; padding:1mm 4mm; border-radius:3mm; font-size:7pt; font-weight:700; text-transform:uppercase; }
.b-ok  { background:rgba(220,252,231,.2); border:1pt solid rgba(134,239,172,.5); color:#86efac; }
.b-rev { background:rgba(219,234,254,.2); border:1pt solid rgba(147,197,253,.5); color:#93c5fd; }
.b-dft { background:rgba(241,245,249,.15); border:1pt solid rgba(148,163,184,.4); color:#cbd5e1; }
.hdr-title { font-size:18pt; font-weight:700; line-height:1.1; margin-bottom:2mm; }
.hdr-sub { font-size:9pt; color:rgba(255,255,255,0.75); }

/* Grille info 2 colonnes via float */
.row2 { width:100%; overflow:hidden; margin-bottom:3mm; }
.row2::after { content:''; display:block; clear:both; }
.col-l { float:left; width:48%; margin-right:4%; }
.col-r { float:left; width:48%; }
.flbl { font-size:6.5pt; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.8px; margin-bottom:.8mm; }
.fval { font-size:10pt; font-weight:700; color:#1a1a2e; border-bottom:1.5pt solid #1e3a6e; padding-bottom:.8mm; min-height:4.5mm; }
.fval.sm   { font-size:9pt; font-weight:600; }
.fval.mono { font-family:DejaVu Sans Mono,monospace; color:#1e3a6e; font-size:8.5pt; }
.fval.lt   { color:#475569; font-weight:400; font-size:9pt; }

/* Séparateurs */
.sep  { border:none; border-top:0.5pt solid #e2e8f0; margin:3mm 0; clear:both; }
.sep2 { border:none; border-top:2pt   solid #1e3a6e;  margin:5mm 0; clear:both; }

/* Titre section */
.stitle { font-size:7pt; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#1e3a6e; border-left:3pt solid #1e3a6e; padding-left:2.5mm; margin-bottom:3mm; clear:both; }

/* ══ TABLEAU QPC ══ */
table.tqpc { width:100%; border-collapse:collapse; margin-bottom:4mm; font-size:8.5pt; }

/* En-tête */
table.tqpc thead tr { background:#1e3a6e; color:#fff; }
table.tqpc thead th { padding:2mm 3mm; font-size:7pt; font-weight:700; text-align:left; text-transform:uppercase; letter-spacing:.6px; }
table.tqpc thead th.tc { text-align:center; }

/* Ligne catégorie */
table.tqpc tbody tr.cat { background:#2B7FD4; }
table.tqpc tbody tr.cat td { padding:2.5mm 3mm; font-size:9.5pt; font-weight:700; color:#fff; }
table.tqpc tbody tr.cat td.cat-num { width:14mm; font-size:11pt; font-weight:900; color:#fff; }

/* Ligne item */
table.tqpc tbody tr.item { border-bottom:0.5pt solid #e2e8f0; }
table.tqpc tbody tr.item-alt { background:#f8fafc; border-bottom:0.5pt solid #e2e8f0; }
table.tqpc tbody tr.item td,
table.tqpc tbody tr.item-alt td { padding:1.8mm 3mm; vertical-align:top; }
table.tqpc tbody tr.item td.item-arrow,
table.tqpc tbody tr.item-alt td.item-arrow { width:14mm; color:#94a3b8; font-size:8pt; text-align:center; }
table.tqpc tbody tr.item td.item-code,
table.tqpc tbody tr.item-alt td.item-code { width:28mm; font-size:8pt; font-weight:700; color:#1e3a6e; }
table.tqpc tbody tr.item td.item-lib,
table.tqpc tbody tr.item-alt td.item-lib { font-size:8.5pt; color:#1a1a2e; }
table.tqpc tbody tr.item td.item-file,
table.tqpc tbody tr.item-alt td.item-file { width:38mm; font-size:8pt; color:#64748b; }

/* Bloc validation */
.vbox { background:#f0fdf4; border:0.5pt solid #86efac; padding:3mm 4mm; margin:3mm 0; clear:both; }
table.tv { width:100%; border-collapse:collapse; }
table.tv td { font-size:8pt; padding:1mm 0; }
table.tv td.vl { font-size:6.5pt; font-weight:700; color:#15803d; text-transform:uppercase; width:28mm; }

/* Signatures */
table.ts { width:100%; border-collapse:collapse; margin-top:8mm; }
table.ts td { width:50%; padding-right:10mm; vertical-align:bottom; }
table.ts td:last-child { padding-right:0; }
.slbl { font-size:6.5pt; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.7px; margin-bottom:6mm; }
.sline { border-top:1.5pt solid #1a1a2e; padding-top:1.5mm; font-size:8pt; color:#475569; }
</style>
</head>
<body>

@if($pdc->validation_status === 'validated')
<div class="watermark">VALIDÉ</div>
@endif

<div class="pf">
  <table><tr>
    <td>DIADDEM &mdash; Audit Core &nbsp;&bull;&nbsp; {{ $mission->code_mission ?? '' }} &nbsp;&bull;&nbsp; {{ $pdc->code }}</td>
    <td class="pf-r">Généré le {{ now()->translatedFormat('d F Y') }}</td>
  </tr></table>
</div>

<div class="wrap">

  {{-- HEADER --}}
  <div class="hdr">
    <div class="hdr-year">{{ now()->year }}</div>
    @php
      $bmap = ['draft'=>'b-dft','in_review'=>'b-rev','validated'=>'b-ok'];
      $lmap = ['draft'=>'Brouillon','in_review'=>'En révision','validated'=>'Validé'];
    @endphp
    <span class="hdr-badge {{ $bmap[$pdc->validation_status] ?? 'b-dft' }}">
      {{ $lmap[$pdc->validation_status] ?? $pdc->validation_status }}
    </span>
    <div class="hdr-title">Questionnaire de Prise de Connaissance</div>
    <div class="hdr-sub">
      QPC
      @if(!empty($mission->code_mission)) &mdash; {{ $mission->code_mission }} @endif
      @if(!empty($mission->libelle)) &nbsp;&bull;&nbsp; {{ $mission->libelle }} @endif
    </div>
  </div>

  {{-- Entité + Phase --}}
  <div class="row2">
    <div class="col-l">
      <div class="flbl">Entité auditée</div>
      <div class="fval sm">{{ $mission->entity_name ?? $pdc->entite_auditee ?? '—' }}</div>
    </div>
    <div class="col-r">
      <div class="flbl">Phase</div>
      <div class="fval sm lt">{{ $assignment->phase_label ?? ($assignment->phase_code ?? '—') }}</div>
    </div>
  </div>

  {{-- Code QPC + Intitulé --}}
  <div class="row2">
    <div class="col-l">
      <div class="flbl">Code QPC</div>
      <div class="fval mono">{{ $pdc->code }}</div>
    </div>
    <div class="col-r">
      <div class="flbl">Intitulé QPC</div>
      <div class="fval sm">{{ $pdc->intitule_qpc ?? '—' }}</div>
    </div>
  </div>

  {{-- Fait par + Revu par --}}
  <div class="row2">
    <div class="col-l">
      <div class="flbl">Fait par</div>
      <div class="fval sm">{{ $pdc->fait_par ?? '—' }}</div>
    </div>
    <div class="col-r">
      <div class="flbl">Revu par</div>
      <div class="fval sm lt">{{ $pdc->revue_par ?: '—' }}</div>
    </div>
  </div>

  @if($pdc->date_fait || $pdc->date_revue)
  <div class="row2">
    <div class="col-l">
      <div class="flbl">Date fait</div>
      <div class="fval sm lt">{{ $pdc->date_fait ? \Carbon\Carbon::parse($pdc->date_fait)->translatedFormat('d F Y') : '—' }}</div>
    </div>
    <div class="col-r">
      <div class="flbl">Date revue</div>
      <div class="fval sm lt">{{ $pdc->date_revue ? \Carbon\Carbon::parse($pdc->date_revue)->translatedFormat('d F Y') : '—' }}</div>
    </div>
  </div>
  @endif

  <hr class="sep2">

  {{-- GRILLE QPC --}}
  @if(count($qpcItems) > 0)
  <div class="stitle">Grille QPC — {{ count(array_filter($qpcItems, fn($i) => $i['type']==='item')) }} items</div>
  <table class="tqpc">
    <thead>
      <tr>
        <th style="width:14mm">N°</th>
        <th style="width:28mm">Code</th>
        <th>Libellé / Description</th>
        <th style="width:38mm">Fichier Attaché</th>
      </tr>
    </thead>
    <tbody>
      @php $itemIdx = 0; @endphp
      @foreach($qpcItems as $row)
        @if($row['type'] === 'cat')
        <tr class="cat">
          <td class="cat-num">{{ $row['num'] ?? '' }}</td>
          <td>{{ $row['code'] ?? '' }}</td>
          <td colspan="2">{{ $row['libelle'] ?? '' }}</td>
        </tr>
        @else
        @php $itemIdx++; $cls = ($itemIdx % 2 === 0) ? 'item-alt' : 'item'; @endphp
        <tr class="{{ $cls }}">
          <td class="item-arrow">&#8627;</td>
          <td class="item-code">{{ $row['code'] ?? '' }}</td>
          <td class="item-lib">{{ $row['libelle'] ?? '' }}</td>
          <td class="item-file">{{ $row['fichier'] ?? '' }}</td>
        </tr>
        @endif
      @endforeach
    </tbody>
  </table>
  @else
  <div style="padding:10mm;text-align:center;color:#94a3b8;font-size:9pt;">Aucun item dans ce questionnaire.</div>
  @endif

  {{-- Bloc validation --}}
  @if($pdc->validation_status === 'validated')
  <hr class="sep">
  <div class="vbox">
    <table class="tv"><tr>
      <td class="vl">Validé le</td>
      <td>{{ $pdc->validated_at ? \Carbon\Carbon::parse($pdc->validated_at)->translatedFormat('d F Y \à H:i') : '—' }}</td>
      <td class="vl" style="padding-left:8mm">Validé par</td>
      <td>{{ $validatedBy ?? '—' }}</td>
    </tr></table>
  </div>
  @endif

  {{-- Signatures --}}
  <table class="ts">
    <tr>
      <td><div class="slbl">Fait par :</div><div class="sline">{{ $pdc->fait_par ?? '—' }}</div></td>
      <td><div class="slbl">Signature :</div><div class="sline">&nbsp;</div></td>
    </tr>
    <tr>
      <td style="padding-top:4mm"><div class="slbl">Revu par :</div><div class="sline">{{ $pdc->revue_par ?: '—' }}</div></td>
      <td style="padding-top:4mm"><div class="slbl">Signature :</div><div class="sline">&nbsp;</div></td>
    </tr>
  </table>

</div>
</body>
</html>