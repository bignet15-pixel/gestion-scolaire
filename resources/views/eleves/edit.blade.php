<x-app-layout>
{{-- Vue Blade : resources/views/eleves/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier un élève</h1>

            <p><strong>Matricule :</strong> {{ $eleve->matricule }}</p>

            {{-- Condition : $eleve->photo. --}}
            @if ($eleve->photo)
                <p>
                    <img src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo" width="90">
                </p>
            @endif

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

            <form action="{{ route('eleves.update', $eleve) }}" method="POST" enctype="multipart/form-data">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $eleve->nom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $eleve->prenom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Sexe</label>
                    <select name="sexe" class="form-control">
                        <option value="M" @selected(old('sexe', $eleve->sexe) === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe', $eleve->sexe) === 'F')>Féminin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance', $eleve->date_naissance?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Lieu de naissance</label>
                    <input type="text" name="lieu_naissance" class="form-control" value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Contact parent</label>
                    <input type="text" name="contact_parent" class="form-control" value="{{ old('contact_parent', $eleve->contact_parent) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Photo de l’élève</label>

                    <input
                        type="file"
                        name="photo"
                        class="form-control js-image-preview-input"
                        accept="image/*"
                        data-preview="photo-preview-edit"
                    >

                    <small>
                        Laissez vide si vous ne voulez pas changer la photo.
                    </small>

                    <div class="image-preview-box">
                        <img
                            id="photo-preview-edit"
                            src="{{ $eleve->photo ? asset('storage/' . $eleve->photo) : '' }}"
                            alt="Aperçu photo"
                            class="image-preview"
                            {{-- Condition : verification des criteres avant affichage. --}}
                            @if (! $eleve->photo) hidden @endif
                        >

                        {{-- Condition : ! $eleve->photo. --}}
                        @if (! $eleve->photo)
                            <div class="image-preview-placeholder">
                                Aperçu photo
                            </div>
                        @endif
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('eleves.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>