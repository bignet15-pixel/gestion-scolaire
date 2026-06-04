{{-- Vue Blade : resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Scolaire</title>

    {{-- Chargement des fichiers CSS et JavaScript. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-panel">
                <div class="auth-logo">BZ</div>

                <h1>{{ config('ecole.nom') }}</h1>

                <p>
                   {{ config('ecole.devise') }}
                </p>

                <ul class="auth-features">
                    <li>Gestion des élèves et inscriptions</li>
                    <li>Suivi des paiements et impayés</li>
                    <li>Notes, moyennes et classements</li>
                    <li>Espaces gestionnaire et enseignant</li>
                </ul>
            </div>

            <div class="auth-form-panel">
                <h2>Bienvenue</h2>

                <p class="auth-muted">
                    Connectez-vous pour accéder à votre espace.
                </p>

                <a href="{{ route('login') }}" class="btn btn-primary auth-main-btn">
                    Se connecter
                </a>
            </div>
        </div>
    </div>
</body>
</html>
