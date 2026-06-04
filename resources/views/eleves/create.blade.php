<x-app-layout>
{{-- Vue Blade : resources/views/eleves/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter un élève</h1>

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

            <form action="{{ route('eleves.store') }}" method="POST" enctype="multipart/form-data">
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
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Lieu de naissance</label>
                    <input type="text" name="lieu_naissance" class="form-control" value="{{ old('lieu_naissance') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Contact parent</label>
                    <input type="text" name="contact_parent" class="form-control" value="{{ old('contact_parent') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Photo de l’élève</label>

                    <input
                        type="file"
                        name="photo"
                        class="form-control js-image-preview-input"
                        accept="image/*"
                        data-preview="photo-preview-create"
                    >

                    <small>
                        Formats acceptés : JPG, JPEG, PNG, WEBP.
                    </small>

                    <div class="image-preview-box">
                        <img
                            id="photo-preview-create"
                            src=""
                            alt="Aperçu photo"
                            class="image-preview"
                            hidden
                        >

                        <div class="image-preview-placeholder">
                            Aperçu photo
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Enregistrer
                </button>

                <a href="{{ route('eleves.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>