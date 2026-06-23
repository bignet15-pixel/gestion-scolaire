<x-guest-layout>
{{-- Vue Blade : resources/views/auth/login.blade.php --}}
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-panel">
                <div class="auth-logo">BZ</div>

                <h1>Bangre Zaaka</h1>

                <p>
                    Plateforme de gestion scolaire pour le cycle primaire :
                    élèves, classes, paiements, notes, emplois du temps et résultats.
                </p>

                <ul class="auth-features">
                    <li>Espace gestionnaire</li>
                    <li>Espace enseignant</li>
                    <li>Suivi pédagogique et financier</li>
                </ul>
            </div>

            <div class="auth-form-panel">
                <h2>Connexion</h2>

                <p class="auth-muted">
                    Utilisez votre email et votre mot de passe.
                </p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    {{-- Jeton de securite du formulaire. --}}
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>

                        <input
                            id="email"
                            class="form-control"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                        >

                        <x-input-error :messages="$errors->get('email')" class="auth-error" />
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>

                        <input
                            id="password"
                            class="form-control"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >

                        <x-input-error :messages="$errors->get('password')" class="auth-error" />
                    </div>

                    <div class="auth-row">
                        <span></span>

                        <a href="{{ route('password.request') }}" class="auth-link">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary auth-main-btn">
                        Se connecter
                    </button>
                </form>

                <div class="auth-note">
                    Les comptes sont créés par le gestionnaire de l’établissement.
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>