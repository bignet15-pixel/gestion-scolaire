<x-app-layout>
    <style>
        .profile-account-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
        }

        .profile-account-hero h1 {
            margin-bottom: 8px;
        }

        .profile-account-hero p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .profile-account-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.7fr);
            gap: 22px;
            align-items: start;
        }

        .profile-account-card {
            margin-bottom: 22px;
        }

        .profile-account-danger {
            border-color: #fecaca;
        }

        .profile-form-header {
            margin-bottom: 18px;
        }

        .profile-form-header h2 {
            margin: 0 0 8px;
            color: var(--primary-dark);
            font-size: 20px;
        }

        .profile-form-header p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
            font-size: 14px;
        }

        .form-stack {
            display: grid;
            gap: 16px;
        }

        .form-error {
            margin: 6px 0 0;
            color: var(--danger);
            font-size: 13px;
            font-weight: 700;
        }

        .link-button {
            display: inline-flex;
            border: 0;
            background: transparent;
            color: var(--primary);
            font-weight: 800;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
        }

        @media (max-width: 960px) {
            .profile-account-hero,
            .profile-account-grid {
                grid-template-columns: 1fr;
            }

            .profile-account-hero {
                flex-direction: column;
            }
        }
    </style>

    <div class="container">
        <div class="card profile-account-hero">
            <div>
                <div class="page-kicker">Compte utilisateur</div>
                <h1>Mon compte</h1>
                <p>
                    Modifiez vos informations personnelles et votre mot de passe.
                </p>
            </div>

            <a href="{{ route('dashboard') }}" class="btn">
                Retour au tableau de bord
            </a>
        </div>

        @if (session('status') === 'profile-updated')
            <div class="alert alert-success">
                Informations du compte mises à jour avec succès.
            </div>
        @endif

        @if (session('status') === 'password-updated')
            <div class="alert alert-success">
                Mot de passe modifié avec succès.
            </div>
        @endif

        <div class="profile-account-grid">
            <div class="card profile-account-card">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="card profile-account-card">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        @if (! auth()->user()->estParent())
            <div class="card profile-account-card profile-account-danger">
                @include('profile.partials.delete-user-form')
            </div>
        @endif
    </div>
</x-app-layout>
