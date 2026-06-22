<section>
    <header class="profile-form-header">
        <h2>Informations du compte</h2>
        <p>
            Ces informations permettent d’identifier votre compte dans le système.
            Le matricule et le rôle restent gérés par l’administration.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="form-stack">
        @csrf
        @method('patch')

        <div class="form-grid">
            <div class="form-group">
                <label for="prenom" class="form-label">Prénom</label>
                <input
                    id="prenom"
                    name="prenom"
                    type="text"
                    class="form-control"
                    value="{{ old('prenom', $user->prenom) }}"
                    required
                    autocomplete="given-name"
                >

                @foreach ($errors->get('prenom') as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            </div>

            <div class="form-group">
                <label for="nom" class="form-label">Nom</label>
                <input
                    id="nom"
                    name="nom"
                    type="text"
                    class="form-control"
                    value="{{ old('nom', $user->nom) }}"
                    required
                    autocomplete="family-name"
                >

                @foreach ($errors->get('nom') as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Adresse email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="form-control"
                    value="{{ old('email', $user->email) }}"
                    required
                    autocomplete="username"
                >

                @foreach ($errors->get('email') as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Téléphone</label>
                <input
                    id="phone"
                    name="phone"
                    type="text"
                    class="form-control"
                    value="{{ old('phone', $user->phone) }}"
                    autocomplete="tel"
                    placeholder="Ex : +22670000000"
                >

                @foreach ($errors->get('phone') as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            </div>

            <div class="form-group">
                <label for="sexe" class="form-label">Sexe</label>
                <select id="sexe" name="sexe" class="form-control">
                    <option value="">Non renseigné</option>
                    <option value="M" @selected(old('sexe', $user->sexe) === 'M')>Masculin</option>
                    <option value="F" @selected(old('sexe', $user->sexe) === 'F')>Féminin</option>
                </select>

                @foreach ($errors->get('sexe') as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            </div>

            <div class="form-group">
                <label class="form-label">Rôle</label>
                <input type="text" class="form-control" value="{{ ucfirst($user->role) }}" disabled>
                <small>Le rôle est modifiable uniquement par le gestionnaire.</small>
            </div>

            <div class="form-group form-group-full">
                <label for="adresse" class="form-label">Adresse</label>
                <input
                    id="adresse"
                    name="adresse"
                    type="text"
                    class="form-control"
                    value="{{ old('adresse', $user->adresse) }}"
                    autocomplete="street-address"
                    placeholder="Ex : Ouagadougou, secteur..."
                >

                @foreach ($errors->get('adresse') as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="alert alert-warning">
                Votre adresse email n’est pas encore vérifiée.
                <button form="send-verification" class="link-button" type="submit">
                    Renvoyer le lien de vérification
                </button>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success">
                    Un nouveau lien de vérification a été envoyé à votre adresse email.
                </div>
            @endif
        @endif

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Enregistrer les informations
            </button>
        </div>
    </form>
</section>
