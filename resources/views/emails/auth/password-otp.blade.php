<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Code de réinitialisation</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#061547;">
    <div style="max-width:620px;margin:0 auto;padding:28px 18px;">
        <div style="background:#ffffff;border-radius:18px;padding:28px;border:1px solid #e5e7eb;">
            <p style="margin:0 0 8px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.08em;font-size:12px;">
                Bangre Zaaka
            </p>

            <h1 style="margin:0 0 18px;font-size:24px;color:#061547;">
                Réinitialisation du mot de passe
            </h1>

            <p style="line-height:1.7;margin:0 0 18px;">
                Bonjour {{ $user->prenom ?? '' }} {{ $user->nom ?? '' }},
            </p>

            <p style="line-height:1.7;margin:0 0 18px;">
                Vous avez demandé la réinitialisation du mot de passe de votre compte. Utilisez le code ci-dessous pour continuer.
            </p>

            <div style="text-align:center;margin:26px 0;">
                <div style="display:inline-block;background:#1f3c88;color:#ffffff;font-size:32px;font-weight:900;letter-spacing:8px;padding:16px 24px;border-radius:14px;">
                    {{ $code }}
                </div>
            </div>

            <p style="line-height:1.7;margin:0 0 18px;">
                Ce code expire dans {{ $expirationMinutes }} minutes.
            </p>

            <p style="line-height:1.7;margin:0;color:#64748b;">
                Si vous n’avez pas demandé cette opération, ignorez simplement cet email.
            </p>
        </div>

        <p style="text-align:center;color:#94a3b8;font-size:12px;margin-top:18px;">
            © {{ date('Y') }} Bangre Zaaka. Tous droits réservés.
        </p>
    </div>
</body>
</html>
