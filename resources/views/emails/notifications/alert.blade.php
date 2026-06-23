<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $notification->titre }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h2 style="color: #1e3a8a;">{{ $ecoleNom }}</h2>

    <p>Bonjour,</p>

    <p>
        {{ $notification->email_resume }}
    </p>

    <p>
        {{ $notification->email_raison_connexion }}
    </p>

    <p>
        Veuillez vous connecter à votre espace pour consulter les informations complètes.
    </p>

    <p style="margin-top: 24px; color: #374151;">
        École {{ $ecoleNom }}
    </p>
</body>
</html>
