<x-app-layout>
{{-- Vue Blade : resources/views/enseignants/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter un enseignant</h1>

            {{-- Condition : $errors->any(). --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        {{-- Affiche les messages d erreur de validation. --}}
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('enseignants.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="{{ old('prenom') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Sexe</label>
                    <select name="sexe" class="form-control">
                        <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-control" value="{{ old('adresse') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">
                    Enregistrer
                </button>

                <a href="{{ route('enseignants.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>