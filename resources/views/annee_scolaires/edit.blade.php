<x-app-layout>
{{-- Vue Blade : resources/views/annee_scolaires/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier une année scolaire</h1>

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

            <form action="{{ route('annee-scolaires.update', $annee_scolaire) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Libellé</label>
                    <input type="text" name="libelle" class="form-control" value="{{ old('libelle', $annee_scolaire->libelle) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ old('date_debut', $annee_scolaire->date_debut->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin', $annee_scolaire->date_fin->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="active" @selected(old('statut', $annee_scolaire->statut) === 'active')>
                            Active
                        </option>
                        <option value="fermee" @selected(old('statut', $annee_scolaire->statut) === 'fermee')>
                            Fermée
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('annee-scolaires.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>