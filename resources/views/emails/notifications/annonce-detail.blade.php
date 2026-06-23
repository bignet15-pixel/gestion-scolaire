<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $annonce->titre }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h2 style="color: #1e3a8a;">{{ $ecoleNom }}</h2>

    <p>Bonjour,</p>

    <p>
        L’école {{ $ecoleNom }} vous transmet l’annonce suivante :
    </p>

    <h3 style="color: #111827;">{{ $annonce->titre }}</h3>

    <p>
        <strong>Type :</strong> {{ $annonce->libelleType() }}<br>
        <strong>Priorité :</strong> {{ $annonce->libellePriorite() }}
        @if ($annonce->classe)
            <br><strong>Classe concernée :</strong> {{ $annonce->classe->nom }}
        @endif
    </p>

    <div style="padding: 14px; border-left: 4px solid #1e3a8a; background: #f8fafc;">
        {!! nl2br(e($annonce->contenu)) !!}
    </div>

    <p style="margin-top: 24px; color: #374151;">
        École {{ $ecoleNom }}
    </p>
</body>
</html>
