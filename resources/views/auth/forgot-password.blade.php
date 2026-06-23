<x-guest-layout>
{{-- Vue Blade : resources/views/auth/forgot-password.blade.php --}}
@php
    $step = $isVerified ? 'reset' : ($email ? 'verify' : 'email');
@endphp

    <style>
        .auth-card-compact {
            max-width: 920px;
            min-height: 500px;
        }

        .otp-input {
            text-align: center;
            letter-spacing: 8px;
            font-size: 22px;
            font-weight: 900;
        }

        .auth-secondary-form {
            margin-top: 16px;
            text-align: center;
        }

        .auth-link-button {
            border: 0;
            background: transparent;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
        }
    </style>

    <div class="auth-page">
        <div class="auth-card auth-card-compact">
            <div class="auth-panel">
                <div class="auth-logo">BZ</div>

                <h1>Mot de passe oublié</h1>

                <p>
                    Recevez un code OTP par email, vérifiez ce code, puis définissez un nouveau mot de passe.
                </p>

                <ul class="auth-features">
                    <li>Code envoyé par email</li>
                    <li>Expiration automatique</li>
                    <li>Aucun lien externe nécessaire</li>
                </ul>
            </div>

            <div class="auth-form-panel">
                <h2>Réinitialisation</h2>

                <p class="auth-muted">
                    Étape {{ $step === 'email' ? '1' : ($step === 'verify' ? '2' : '3') }} sur 3.
                </p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                @if ($step === 'email')
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">Adresse email du compte</label>

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

                        <button type="submit" class="btn btn-primary auth-main-btn">
                            Envoyer le code OTP
                        </button>
                    </form>
                @elseif ($step === 'verify')
                    <div class="auth-note">
                        Un code OTP a été envoyé à : <strong>{{ $email }}</strong>.
                    </div>

                    <form method="POST" action="{{ route('password.otp.verify') }}">
                        @csrf

                        <div class="form-group">
                            <label for="code" class="form-label">Code OTP</label>

                            <input
                                id="code"
                                class="form-control otp-input"
                                type="text"
                                name="code"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="Ex : 482913"
                                required
                                autofocus
                            >

                            <x-input-error :messages="$errors->get('code')" class="auth-error" />
                        </div>

                        <button type="submit" class="btn btn-primary auth-main-btn">
                            Vérifier le code
                        </button>
                    </form>

                    <form method="POST" action="{{ route('password.email') }}" class="auth-secondary-form">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">

                        <button type="submit" class="auth-link auth-link-button">
                            Renvoyer un nouveau code
                        </button>
                    </form>
                @else
                    <div class="auth-note">
                        Code vérifié pour : <strong>{{ $email }}</strong>.
                    </div>

                    <form method="POST" action="{{ route('password.otp.reset') }}">
                        @csrf

                        <div class="form-group">
                            <label for="password" class="form-label">Nouveau mot de passe</label>

                            <input
                                id="password"
                                class="form-control"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                            >

                            <x-input-error :messages="$errors->get('password')" class="auth-error" />
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>

                            <input
                                id="password_confirmation"
                                class="form-control"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                            >

                            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
                        </div>

                        <button type="submit" class="btn btn-primary auth-main-btn">
                            Réinitialiser le mot de passe
                        </button>
                    </form>
                @endif

                <div class="auth-note">
                    <a href="{{ route('login') }}" class="auth-link">
                        Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
