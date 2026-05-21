<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            background: #fff;
            /* A5 portrait : 148mm × 210mm */
            width: 148mm;
        }

        /* ---- En-tête ---- */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #2c4a7c;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-left  { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: middle; }

        .societe-name {
            font-size: 14px;
            font-weight: bold;
            color: #2c4a7c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .societe-adresse {
            font-size: 8px;
            color: #555;
            margin-top: 2px;
            line-height: 1.5;
        }

        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #2c4a7c;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-ref {
            font-size: 9px;
            color: #444;
            margin-top: 3px;
        }
        .doc-ref span {
            font-weight: bold;
            color: #2c4a7c;
        }

        /* ---- Bloc fournisseur ---- */
        .fourn-bloc {
            background: #f4f7fb;
            border-left: 3px solid #2c4a7c;
            padding: 6px 10px;
            margin-bottom: 10px;
            border-radius: 0 3px 3px 0;
        }
        .fourn-label {
            font-size: 8px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .fourn-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .fourn-detail {
            font-size: 8px;
            color: #555;
            margin-top: 1px;
        }

        /* ---- Tableau des lignes ---- */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .lines-table thead tr {
            background: #2c4a7c;
            color: #fff;
        }
        .lines-table thead th {
            padding: 5px 6px;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .lines-table thead th.r { text-align: right; }
        .lines-table thead th.c { text-align: center; }

        .lines-table tbody tr:nth-child(even) { background: #f8f9fb; }
        .lines-table tbody tr:nth-child(odd)  { background: #fff; }

        .lines-table tbody td {
            padding: 4px 6px;
            font-size: 9px;
            border-bottom: 0.5px solid #e0e4ea;
            vertical-align: middle;
        }
        .lines-table tbody td.r { text-align: right; }
        .lines-table tbody td.c { text-align: center; }

        .produit-name { font-weight: bold; color: #1a1a1a; }
        .prix-zero    { color: #aaa; font-style: italic; }

        /* ---- Total ---- */
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .total-spacer { display: table-cell; width: 55%; }
        .total-box {
            display: table-cell;
            width: 45%;
            background: #2c4a7c;
            color: #fff;
            padding: 7px 10px;
            border-radius: 3px;
        }
        .total-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
        .total-value { font-size: 14px; font-weight: bold; margin-top: 1px; }

        /* ---- Pied de page ---- */
        .footer {
            border-top: 0.5px solid #ccc;
            padding-top: 8px;
            margin-top: 8px;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; width: 50%; }
        .footer-right { display: table-cell; width: 50%; text-align: right; }

        .signature-zone {
            border: 0.5px solid #bbb;
            border-radius: 3px;
            padding: 5px 8px;
            height: 32px;
            display: inline-block;
            min-width: 90px;
        }
        .signature-label {
            font-size: 7.5px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .footer-note {
            font-size: 7.5px;
            color: #aaa;
            margin-top: 6px;
            text-align: center;
        }

        /* ---- Badges type ---- */
        .badge-type {
            display: inline-block;
            background: #e8f0fb;
            color: #2c4a7c;
            font-size: 8px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-top: 3px;
        }
    </style>
</head>
<body>

<!-- ============ EN-TÊTE ============ -->
<div class="header">
    <div class="header-left">
        <div class="societe-name">
            {{ $societe?->Rais_Soc ?? 'TOGODIS' }}
        </div>
        <div class="societe-adresse">
            {{ $societe?->AdrL1 ?? '' }}<br>
            {{ $societe?->AdrL2 ?? '' }}
        </div>
        <div class="badge-type">{{ $entete->TypeEnt ?? 'MAG' }}</div>
    </div>
    <div class="header-right">
        <div class="doc-title">Bordereau d'entrée</div>
        <div class="doc-ref">
            Référence : <span>{{ $entete->EntFolio }}</span>
        </div>
        <div class="doc-ref">
            Date : <span>{{ \Carbon\Carbon::parse($entete->DateEntete)->format('d/m/Y') }}</span>
        </div>
    </div>
</div>

<!-- ============ FOURNISSEUR ============ -->
<div class="fourn-bloc">
    <div class="fourn-label">Fournisseur</div>
    <div class="fourn-name">{{ $fournisseur?->RaisSoc ?? '—' }}</div>
    @if($fournisseur?->VilleFourn || $fournisseur?->ContactFourn)
        <div class="fourn-detail">
            {{ implode(' — ', array_filter([$fournisseur?->VilleFourn, $fournisseur?->ContactFourn])) }}
        </div>
    @endif
</div>

<!-- ============ TABLEAU DES LIGNES ============ -->
<table class="lines-table">
    <thead>
    <tr>
        <th style="width:6%;" class="c">N°</th>
        <th style="width:46%;">Désignation</th>
        <th style="width:13%;" class="c">NbBout</th>
        <th style="width:13%;" class="r">Quantité</th>
        <th style="width:13%;" class="r">P. Achat</th>
        <th style="width:13%;" class="r">Montant</th>
    </tr>
    </thead>
    <tbody>
    @foreach($lignes as $i => $ligne)
        @php
            $montant = $ligne->QteEntre * $ligne->PrixAchat;
        @endphp
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td class="produit-name">{{ $ligne->produit?->DesignPdt ?? $ligne->CodePdt }}</td>
            <td class="c">{{ $ligne->produit?->NbBout ?? '—' }}</td>
            <td class="r">{{ number_format($ligne->QteEntre, 2, ',', ' ') }}</td>
            <td class="r {{ $ligne->PrixAchat == 0 ? 'prix-zero' : '' }}">
                {{ $ligne->PrixAchat == 0 ? '—' : number_format($ligne->PrixAchat, 0, ',', ' ') }}
            </td>
            <td class="r">
                {{ $montant == 0 ? '—' : number_format($montant, 0, ',', ' ') }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- ============ TOTAL ============ -->
<div class="total-row">
    <div class="total-spacer"></div>
    <div class="total-box">
        <div class="total-label">Montant total XOF</div>
        <div class="total-value">
            {{ number_format($total, 0, ',', ' ') }}
        </div>
    </div>
</div>

<!-- ============ PIED ============ -->
<div class="footer">
    <div class="footer-left">
        <div class="signature-label">Bon pour réception</div>
        <div class="signature-zone"></div>
    </div>
    <div class="footer-right">
        <div class="signature-label">Responsable stock</div>
        <div class="signature-zone"></div>
    </div>
</div>

<div class="footer-note">
    Imprimé le {{ now()->format('d/m/Y à H:i') }} — {{ $entete->EntFolio }}
</div>

</body>
</html>
