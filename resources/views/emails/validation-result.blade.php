<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation hiérarchisation des risques</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6f9; margin: 0; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 2rem; text-align: center; }
        .success { color: #15803d; background: #dcfce7; padding: 1rem; border-radius: 8px; }
        .error { color: #dc2626; background: #fee2e2; padding: 1rem; border-radius: 8px; }
        .btn { display: inline-block; margin-top: 1.5rem; padding: 0.6rem 1.2rem; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; }
        h1 { margin-top: 0; }
    </style>
</head>
<body>
    <div class="container">
        @if($success)
            <div class="success">
                <h1>✓ Merci !</h1>
                <p>{{ $message }}</p>
                @if(isset($fiche))
                    <p><strong>Fiche :</strong> {{ $fiche->intitule }}</p>
                    <p><strong>Date de confirmation :</strong> {{ now()->format('d/m/Y à H:i') }}</p>
                @endif
                <a href="{{ url('/') }}" class="btn">Retour au portail</a>
            </div>
        @else
            <div class="error">
                <h1>⚠️ Lien invalide</h1>
                <p>{{ $message }}</p>
                <p>Veuillez contacter l'auditeur pour obtenir un nouveau lien.</p>
            </div>
        @endif
    </div>
</body>
</html>