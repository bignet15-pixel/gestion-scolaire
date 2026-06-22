<x-app-layout>
    <div class="container">
        <div class="card">
            <h1>Modifier un parent</h1>

            <p><strong>Matricule :</strong> {{ $parent->matricule }}</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('parents.update', $parent) }}" method="POST" class="form-grid">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $parent->nom) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $parent->prenom) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Sexe</label>
                    <select name="sexe" class="form-control">
                        <option value="">Non renseigné</option>
                        <option value="M" @selected(old('sexe', $parent->sexe) === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe', $parent->sexe) === 'F')>Féminin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $parent->phone) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $parent->email) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-control">
                    <small>Laissez vide pour conserver le mot de passe actuel.</small>
                </div>

                <div class="form-group form-group-full">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $parent->adresse) }}">
                </div>

                <div class="form-actions form-group-full">
                    <a href="{{ route('parents.index') }}" class="btn">
                        Retour
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Modifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
