<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Mission {{ $om->reference_om }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',serif; font-size:9.5pt; color:#1A1A2E; background:#fff; line-height:1.6; }
        .page { width:190mm; min-height:277mm; margin:10mm auto; padding:0; }

        /* EN-TETE */
        .hd-bar   { background:#0F172A; padding:16px 22px 12px; }
        .hd-inner { display:table; width:100%; }
        .hd-left  { display:table-cell; vertical-align:middle; }
        .hd-right { display:table-cell; vertical-align:middle; text-align:right; width:145px; }
        .hd-cab   { font-size:7pt; color:#94A3B8; letter-spacing:2.5px; text-transform:uppercase; margin-bottom:5px; }
        .hd-title { font-size:13pt; font-weight:bold; color:#fff; line-height:1.25; margin-bottom:4px; }
        .hd-ref   { font-size:8.5pt; color:#60A5FA; }
        .ref-box  { background:#1E3A5F; border:1px solid #2D5A8E; border-radius:4px; padding:9px 13px; text-align:center; }
        .ref-num  { font-size:10.5pt; font-weight:bold; color:#fff; display:block; font-family:'DejaVu Sans Mono',monospace; }
        .ref-lbl  { font-size:6pt; color:#94A3B8; text-transform:uppercase; letter-spacing:2px; display:block; margin-top:3px; }
        .stripe1  { height:4px; background:#1E40AF; }
        .stripe2  { height:2px; background:#3B82F6; }

        /* DESTINATAIRE */
        .dest-wrap   { display:table; width:100%; margin:14px 0 10px; }
        .dest-block  { display:table-cell; vertical-align:top; width:55%; }
        .dest-meta   { display:table-cell; vertical-align:top; padding-left:20px; }
        .dest-lbl    { font-size:6.5pt; font-weight:bold; color:#1E40AF; text-transform:uppercase; letter-spacing:2px; margin-bottom:4px; padding-bottom:3px; border-bottom:1px solid #DBEAFE; }
        .dest-entity { font-size:13pt; font-weight:bold; color:#0F172A; margin-bottom:3px; }
        .dest-contact{ font-size:8.5pt; color:#475569; margin-top:2px; }
        .dest-email  { font-size:8pt;   color:#1E40AF;  margin-top:2px; }
        .meta-item   { font-size:8pt; color:#475569; margin-top:6px; line-height:1.6; }
        .meta-lbl    { font-weight:bold; color:#64748B; }

        /* SEPARATEURS */
        .div-blue { border:none; border-top:2px solid #1E40AF; margin:14px 0 12px; }
        .div-thin { border:none; border-top:1px solid #E2E8F0; margin:10px 0; }

        /* INTRO */
        .intro-p { font-size:9.5pt; color:#334155; line-height:1.75; text-align:justify; margin-bottom:14px; }

        /* SECTION TITRE */
        .sec-title-blue  { font-size:7.5pt; font-weight:bold; color:#1E40AF; text-transform:uppercase; letter-spacing:1.8px; padding:6px 10px; background:#EFF6FF; border-left:4px solid #1E40AF; margin-bottom:0; }
        .sec-title-green { font-size:7.5pt; font-weight:bold; color:#065F46; text-transform:uppercase; letter-spacing:1.8px; padding:6px 10px; background:#DCFCE7; border-left:4px solid #059669; margin-bottom:0; }

        /* BLOC CARACTERISTIQUES */
        .char-block { border:1px solid #CBD5E1; border-radius:3px; overflow:hidden; margin-bottom:14px; }
        .char-table { width:100%; border-collapse:collapse; }
        .char-table tr { border-bottom:1px solid #F1F5F9; }
        .char-table tr:last-child { border-bottom:none; }
        .char-table td { padding:6px 10px; font-size:9pt; vertical-align:top; }
        .td-lbl  { color:#64748B; font-weight:bold; width:30%; font-size:8.5pt; background:#F8FAFC; }
        .td-val  { color:#0F172A; }
        .budget-val { color:#059669; font-weight:bold; font-size:10pt; }
        .period-bold { font-weight:bold; }
        .period-jrs  { color:#94A3B8; font-size:8.5pt; }

        /* BLOC PERIODE ENTITE */
        .ent-period { background:#FFF7ED; border:1px solid #FED7AA; border-radius:3px; overflow:hidden; margin-bottom:14px; }
        .ent-period .sec-title { font-size:7.5pt; font-weight:bold; color:#92400E; text-transform:uppercase; letter-spacing:1.8px; padding:6px 10px; background:#FEF3C7; border-left:4px solid #F59E0B; margin-bottom:0; }
        .ent-period .char-table td { background:#FFFBEB; }
        .ent-period .td-lbl { background:#FEF9C3; color:#78350F; }

        /* EQUIPE */
        .team-block { border:1px solid #A7F3D0; border-radius:3px; overflow:hidden; margin-bottom:14px; }
        .team-table { width:100%; border-collapse:collapse; }
        .team-table thead tr { background:#F0FDF4; }
        .team-table th { font-size:7.5pt; font-weight:bold; color:#065F46; text-transform:uppercase; letter-spacing:0.5px; padding:5px 8px; border-bottom:1px solid #A7F3D0; text-align:left; }
        .team-table td { padding:5px 8px; font-size:8.5pt; color:#1E293B; border-bottom:1px solid #ECFDF5; vertical-align:middle; }
        .team-table tr:last-child td { border-bottom:none; }
        .col-num  { color:#94A3B8; font-size:8pt; width:4%; text-align:center; }
        .col-code { font-family:'DejaVu Sans Mono',monospace; font-size:7.5pt; color:#64748B; width:12%; }
        .col-role { width:22%; }
        .role-pill { background:#1E40AF; color:#fff; padding:1px 6px; border-radius:8px; font-size:7pt; font-weight:bold; }
        .role-sub  { font-size:7.5pt; color:#64748B; display:block; margin-top:1px; }
        .scope-badge { background:#F59E0B; color:#fff; padding:1px 5px; border-radius:4px; font-size:6.5pt; font-weight:bold; }

        /* MESSAGE */
        .msg-block { border-left:4px solid #F59E0B; background:#FFFBEB; padding:10px 14px; margin-bottom:14px; }
        .msg-title { font-size:7pt; font-weight:bold; color:#92400E; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:6px; }
        .msg-text  { font-size:9pt; color:#334155; line-height:1.7; }

        /* CLOTURE */
        .closing-p { font-size:9.5pt; color:#334155; line-height:1.75; text-align:justify; margin:14px 0 20px; }

        /* SIGNATURE */
        .sig-row   { display:table; width:100%; margin-top:18px; }
        .sig-left  { display:table-cell; vertical-align:bottom; font-size:9pt; color:#475569; }
        .sig-right { display:table-cell; vertical-align:bottom; text-align:center; width:200px; }
        .sig-role  { font-size:8pt; color:#64748B; margin-bottom:28px; }
        .sig-line  { border-top:1px solid #94A3B8; margin-bottom:5px; }
        .sig-name  { font-size:9.5pt; font-weight:bold; color:#0F172A; }

        /* FOOTER */
        .footer-band { background:#0F172A; padding:7px 18px; margin-top:18px; display:table; width:100%; }
        .footer-l    { display:table-cell; font-size:6.5pt; color:#64748B; vertical-align:middle; }
        .footer-r    { display:table-cell; font-size:6.5pt; color:#60A5FA; text-align:right; vertical-align:middle; }

        /* WATERMARK */
        .watermark { position:fixed; top:42%; left:50%; transform:translate(-50%,-50%) rotate(-38deg); font-size:72pt; color:rgba(30,64,175,.06); font-weight:900; text-transform:uppercase; letter-spacing:10px; }

        .page-break { page-break-before:always; }
    </style>
</head>
<body>

@if($om->status === 'brouillon')
    <div class="watermark">BROUILLON</div>
@endif

@php
    // Si le controlleur passe auditeursByEntite, on l'utilise
    // Sinon on retombe sur $auditeurs global
    $audByEnt = $auditeursByEntite ?? [];
    $globalAuds = isset($auditeurs) ? $auditeurs : collect([]);
@endphp

@foreach($entites as $entiteIdx => $entite)
@if($entiteIdx > 0)<div class="page-break"></div>@endif

<div class="page">

    {{-- EN-TETE --}}
    <div class="hd-bar">
        <div class="hd-inner">
            <div class="hd-left">
                <div class="hd-cab">Cabinet KEKELI &mdash; Direction de l'Audit Interne</div>
                <div class="hd-title">{{ $om->intitule }}</div>
                <div class="hd-ref">
                    Ref. : <strong>{{ $om->reference_om }}</strong>
                    @if($om->phase) &nbsp;&bull;&nbsp; Phase : {{ $om->phase }} @endif
                </div>
            </div>
            <div class="hd-right">
                <div class="ref-box">
                    <span class="ref-num">{{ $om->reference_om }}</span>
                    <span class="ref-lbl">Ordre de Mission</span>
                </div>
            </div>
        </div>
    </div>
    <div class="stripe1"></div>
    <div class="stripe2"></div>

    {{-- DESTINATAIRE --}}
    <div class="dest-wrap">
        <div class="dest-block">
            <div class="dest-lbl">Destinataire</div>
            <div class="dest-entity">{{ $entite->entity_name }}</div>
            @if($entite->nom_contact)
                <div class="dest-contact">A l'attention de : <strong>{{ $entite->nom_contact }}</strong></div>
            @endif
            @if($entite->email_contact)
                <div class="dest-email">{{ $entite->email_contact }}</div>
            @endif
        </div>
        <div class="dest-meta">
            @php
                $destEff = $entite->destinataire ?: $om->destinataire;
                $copieEff = $entite->copie       ?: $om->copie;
            @endphp
            @if($destEff)
                <div class="meta-item"><span class="meta-lbl">Destinataire(s) :</span><br>{{ $destEff }}</div>
            @endif
            @if($copieEff)
                <div class="meta-item"><span class="meta-lbl">Copie :</span><br>{{ $copieEff }}</div>
            @endif
        </div>
    </div>

    <div class="div-blue"></div>

    {{-- INTRO --}}
    @php
        // Choisir l'auditeur chef pour l'intro : specifique entite ou global
        $audsEntite = isset($audByEnt[$entite->entity_id]) ? $audByEnt[$entite->entity_id] : $globalAuds;
        $chef = is_array($audsEntite) ? ($audsEntite[0] ?? null) : $audsEntite->first();
    @endphp
    <p class="intro-p">
        Conformement au plan d'audit interne valide par la Direction Generale,
        @if($chef)
            nous vous informons que le service d'audit interne est mandate pour realiser
            sous la responsabilite de <strong>{{ strtoupper($chef->last_name) }} {{ ucfirst(strtolower($chef->first_name)) }}</strong>
        @else
            nous vous informons que le service d'audit interne est mandate pour realiser
        @endif
        la mission ci-dessous aupres de votre entite et dont les elements caracteristiques sont :
    </p>

    {{-- ELEMENTS CARACTERISTIQUES GLOBAUX --}}
    <div class="sec-title-blue">Elements Caracteristiques de la Mission</div>
    <div class="char-block">
        <table class="char-table">
            <tr>
                <td class="td-lbl">Intitule</td>
                <td class="td-val"><strong>{{ $om->intitule }}</strong></td>
            </tr>
            @if($om->objectif)
            <tr>
                <td class="td-lbl">Objectif</td>
                <td class="td-val">{{ $om->objectif }}</td>
            </tr>
            @endif
            <tr>
                <td class="td-lbl">Entite auditee</td>
                <td class="td-val"><strong>{{ $entite->entity_name }}</strong></td>
            </tr>
            @if($om->lieux)
            <tr><td class="td-lbl">Lieu(x) global</td><td class="td-val">{{ $om->lieux }}</td></tr>
            @endif
            @if($om->domaine)
            <tr><td class="td-lbl">Domaine</td><td class="td-val">{{ $om->domaine }}</td></tr>
            @endif
            @if($om->moyen)
            <tr><td class="td-lbl">Moyens</td><td class="td-val">{{ $om->moyen }}</td></tr>
            @endif
            @if($om->limite)
            <tr><td class="td-lbl">Perimetre / Limites</td><td class="td-val">{{ $om->limite }}</td></tr>
            @endif
            @if($om->budget > 0)
            <tr>
                <td class="td-lbl">Budget alloue</td>
                <td class="td-val"><span class="budget-val">{{ number_format($om->budget, 0, ',', ' ') }} FCFA</span></td>
            </tr>
            @endif
        </table>
    </div>

    {{-- PERIODE SPECIFIQUE DE L'ENTITE --}}
    @if($entite->date_debut || $entite->date_fin || $entite->lieux)
    <div class="ent-period">
        <div class="sec-title">Periode d'intervention &mdash; {{ $entite->entity_name }}</div>
        <table class="char-table">
            @if($entite->date_debut && $entite->date_fin)
            <tr>
                <td class="td-lbl">Periode</td>
                <td class="td-val">
                    Du <span class="period-bold">{{ \Carbon\Carbon::parse($entite->date_debut)->format('d/m/Y') }}</span>
                    au <span class="period-bold">{{ \Carbon\Carbon::parse($entite->date_fin)->format('d/m/Y') }}</span>
                    @if($entite->duree) <span class="period-jrs">({{ $entite->duree }} jours)</span> @endif
                </td>
            </tr>
            @elseif($om->date_debut && $om->date_fin)
            <tr>
                <td class="td-lbl">Periode</td>
                <td class="td-val">
                    Du <span class="period-bold">{{ \Carbon\Carbon::parse($om->date_debut)->format('d/m/Y') }}</span>
                    au <span class="period-bold">{{ \Carbon\Carbon::parse($om->date_fin)->format('d/m/Y') }}</span>
                    @if($om->duree) <span class="period-jrs">({{ $om->duree }} jours)</span> @endif
                </td>
            </tr>
            @endif
            @if($entite->lieux)
            <tr>
                <td class="td-lbl">Lieu specifique</td>
                <td class="td-val"><strong>{{ $entite->lieux }}</strong></td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- EQUIPE D'AUDIT DE CETTE ENTITE --}}
    @php
        $equipeEntite = collect([]);
        if (isset($audByEnt[$entite->entity_id])) {
            $equipeEntite = is_array($audByEnt[$entite->entity_id])
                ? collect($audByEnt[$entite->entity_id])
                : $audByEnt[$entite->entity_id];
        } else {
            // retour sur auditeurs globaux
            $equipeEntite = $globalAuds;
        }
    @endphp

    @if($equipeEntite->count())
    <div class="team-block">
        <div class="sec-title-green">Equipe d'Audit &mdash; {{ $entite->entity_name }}</div>
        <table class="team-table">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th>Nom &amp; Prenom</th>
                    <th class="col-code">Code</th>
                    <th class="col-role">Role</th>
                    <th>Contact</th>
                    <th style="width:8%">Perimetre</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipeEntite as $idx => $aud)
                <tr>
                    <td class="col-num">{{ $idx + 1 }}</td>
                    <td><strong>{{ strtoupper($aud->last_name) }}</strong> {{ ucfirst(strtolower($aud->first_name)) }}</td>
                    <td class="col-code">{{ $aud->audit_code }}</td>
                    <td class="col-role">
                        @if($aud->role)
                            <span class="role-pill">{{ $aud->role }}</span>
                            @if(!empty($aud->role_libelle) && $aud->role_libelle !== $aud->role)
                                <span class="role-sub">{{ $aud->role_libelle }}</span>
                            @endif
                        @else
                            <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    <td style="font-size:7.5pt;color:#475569">{{ $aud->email ?? '—' }}</td>
                    <td style="text-align:center">
                        @if(isset($aud->scope) && $aud->scope === 'global')
                            <span class="scope-badge">Global</span>
                        @else
                            <span style="font-size:7pt;color:#059669">Entite</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- MESSAGE SPECIFIQUE OU GLOBAL --}}
    @php $msgAffiche = $entite->message ?: $om->message_personnalise; @endphp
    @if($msgAffiche)
    <div class="msg-block">
        <div class="msg-title">Message complementaire</div>
        <div class="msg-text">{!! nl2br(e($msgAffiche)) !!}</div>
    </div>
    @endif

    {{-- CLOTURE --}}
    <p class="closing-p">
        Nous vous remercions des dispositions que vous voudrez bien mettre en place
        pour faciliter le travail a l'equipe d'audit et vous prions d'en informer
        les responsables concernes.
    </p>

    {{-- SIGNATURE --}}
    @php $lieuSig = $entite->lieux ?: ($om->lieux ?? 'Cotonou'); @endphp
    <div class="sig-row">
        <div class="sig-left">Fait a {{ $lieuSig }}, le {{ $date_fr }}</div>
        <div class="sig-right">
            <div class="sig-role">Le Directeur de l'Audit Interne</div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $om->emetteur ?? 'Cabinet KEKELI' }}</div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer-band">
        <div class="footer-l">{{ $om->reference_om }} &mdash; {{ $entite->entity_name }} &mdash; Document confidentiel</div>
        <div class="footer-r">Cabinet KEKELI &bull; {{ $date_fr }}</div>
    </div>

</div>
@endforeach

</body>
</html>