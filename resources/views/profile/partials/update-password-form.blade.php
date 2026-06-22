<section>
    <header class="profile-form-header">
        <h2>Modifier le mot de passe</h2>
        <p>
            Saisissez votre mot de passe actuel, puis choisissez un nouveau mot de passe sécurisé.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="form-stack">
        @csrf
        @method('put')

        <div class="form-group">
            <label for="update_password_current_password" class="form-label">
                Mot de passe actuel
            </label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="form-control"
                autocomplete="current-password"
                required
            >

            @foreach ($errors->updatePassword->get('current_password') as $message)
                <p class="form-error">{{ $message }}</p>
            @endforeach
        </div>

        <div class="form-group">
            <label for="update_password_password" class="form-label">
                Nouveau mot de passe
            </label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                class="form-control"
                autocomplete="new-password"
                required
            >

            @foreach ($errors->updatePassword->get('password') as $message)
                <p class="form-error">{{ $message }}</p>
            @endforeach
        </div>

        <div class="form-group">
            <label for="update_password_password_confirmation" class="form-label">
                Confirmer le nouveau mot de passe
            </label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-control"
                autocomplete="new-password"
                required
            >

            @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                <p class="form-error">{{ $message }}</p>
            @endforeach
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Modifier le mot de passe
            </button>
        </div>
    </form>
</section>
