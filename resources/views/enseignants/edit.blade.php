<x-app-layout>
{{-- Vue Blade : resources/views/enseignants/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier un enseignant</h1>

            <p>
                <strong>Matricule :</strong> {{ $enseignant->matricule }}
            </p>

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

            <form action="{{ route('enseignants.update', $enseignant) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $enseignant->nom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $enseignant->prenom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Sexe</label>
                    <select name="sexe" class="form-control">
                        <option value="M" @selected(old('sexe', $enseignant->sexe) === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe', $enseignant->sexe) === 'F')>Féminin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $enseignant->email) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $enseignant->phone) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $enseignant->adresse) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-control">
                    <small>Laisser vide pour conserver l’ancien mot de passe.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('enseignants.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>