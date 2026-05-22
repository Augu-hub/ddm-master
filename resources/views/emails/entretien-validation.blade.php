<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation entretien d'audit</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.5; color: #1e293b; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; }
        .header { text-align: center; border-bottom: 2px solid #1e40af; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #1e40af; margin: 0; font-size: 1.5rem; }
        .content { margin-bottom: 20px; }
        .info { background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #1e40af; }
        .confidentialite { background: #fef9c3; padding: 12px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #eab308; font-size: 0.85rem; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.85rem; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: 600; }
        .button { display: inline-block; background: #1e40af; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; margin: 15px 0; }
        .footer { font-size: 0.75rem; color: #64748b; text-align: center; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Validation de la grille d'entretien</h1>
    </div>
    <div class="content">
        <div class="info">
            <strong>Intitulé :</strong> {{ $entretien->intitule }}<br>
            <strong>Date :</strong> {{ $entretien->date_entretien ?? 'Non renseignée' }}<br>
            <strong>Interlocuteur :</strong> {{ $entretien->interlocuteur ?? 'Non renseigné' }}<br>
            <strong>Fonction :</strong> {{ $entretien->fonction ?? 'Non renseignée' }}<br>
            <strong>Lieu :</strong> {{ $entretien->lieu ?? 'Non renseigné' }}<br>
            <strong>Auditeur :</strong> {{ $auditeur }}
        </div>

        <div class="confidentialite">
            <strong>🔒 Confidentiel – À usage interne uniquement</strong><br>
            Ce formulaire est strictement personnel. Il sert à valider les données de l'entretien d'audit.<br>
            Les informations recueillies sont confidentielles et destinées uniquement à l’amélioration continue de l’entreprise.<br>
            <strong>Aucune donnée ne sera divulguée à des tiers.</strong>
        </div>

        <h3>Objectif de l'entretien</h3>
        <p>{{ $entretien->objectif ?? 'Non défini' }}</p>

        <h3>Questions / Réponses</h3>
        @if($questions->count())
        <table>
            <thead>
                <tr><th>#</th><th>Type</th><th>Question</th><th>Réponse</th><th>Note</th></tr>
            </thead>
            <tbody>
            @foreach($questions as $q)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $q->type }}</td>
                    <td>{{ $q->libelle }}</td>
                    <td>{{ $q->reponse ?? '' }}</td>
                    <td>{{ $q->note ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <p><em>Aucune question enregistrée.</em></p>
        @endif

        <h3>Synthèse</h3>
        <p>{{ $entretien->synthese ?? 'Non renseignée' }}</p>

        <p style="margin: 25px 0 15px;">
            <a href="{{ $confirmUrl }}" class="button">✅ Confirmer cet entretien</a>
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