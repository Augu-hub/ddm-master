<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation hiérarchisation des risques</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.5; color: #1e293b; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; }
        .header { text-align: center; border-bottom: 2px solid #dc2626; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #dc2626; margin: 0; font-size: 1.5rem; }
        .content { margin-bottom: 20px; }
        .info { background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #dc2626; }
        .confidentialite { background: #fef9c3; padding: 12px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #eab308; font-size: 0.85rem; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.85rem; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: 600; }
        .button { display: inline-block; background: #dc2626; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; margin: 15px 0; }
        .footer { font-size: 0.75rem; color: #64748b; text-align: center; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Validation de la hiérarchisation des risques</h1>
    </div>
    <div class="content">
        <div class="info">
            <strong>Objet :</strong> {{ $fiche->intitule }}<br>
            <strong>Date d'analyse :</strong> {{ $fiche->date_analyse ?? 'Non renseignée' }}<br>
            <strong>Périmètre :</strong> {{ $fiche->perimetre ?? 'Non défini' }}<br>
            <strong>Auditeur :</strong> {{ $auditeur }}
        </div>

        <!-- 📢 Message de confidentialité -->
        <div class="confidentialite">
            <strong>🔒 Confidentiel – À usage interne uniquement</strong><br>
            Ce formulaire est strictement personnel. Il sert à valider les données de la hiérarchisation des risques.<br>
            Les informations recueillies sont confidentielles et destinées uniquement à l’amélioration continue de l’entreprise.<br>
            <strong>Aucune donnée ne sera divulguée à des tiers.</strong>
        </div>

        <h3>Risques identifiés</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Risque</th><th>Prob.</th><th>Impact</th><th>Score</th><th>Niveau</th></tr>
            </thead>
            <tbody>
            @foreach($lignes as $l)
                @php $score = $l->probabilite * $l->impact; @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $l->libelle }}</td>
                    <td>{{ $l->probabilite }}</td>
                    <td>{{ $l->impact }}</td>
                    <td>{{ $score }}</td>
                    <td>{{ $score >= 16 ? 'Critique' : ($score >= 8 ? 'Élevé' : ($score >= 4 ? 'Modéré' : 'Faible')) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        @if($lignes->filter(fn($l) => ($l->probabilite * $l->impact) >= 16)->count())
        <h3>⚠️ Risques critiques</h3>
        <ul>
            @foreach($lignes->filter(fn($l) => ($l->probabilite * $l->impact) >= 16) as $l)
            <li><strong>{{ $l->libelle }}</strong> (score {{ $l->probabilite * $l->impact }})<br>
                <em>Traitement prévu :</em> {{ $l->traitement ?? 'Non spécifié' }}</li>
            @endforeach
        </ul>
        @endif

        <p style="margin: 25px 0 15px;">
            <a href="{{ $confirmUrl }}" class="button">✅ Confirmer cette hiérarchisation</a>
        </p>
        <p>Ce lien expirera dans <strong>7 jours</strong>.<br>
        Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
    </div>
    <div class="footer">
        {{ config('app.name') }} - Service d'audit interne
    </div>
</div>
</body>
</html>