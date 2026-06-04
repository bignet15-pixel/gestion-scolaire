<x-app-layout>
{{-- Vue Blade : resources/views/trimestres/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier un trimestre</h1>

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

            <form action="{{ route('trimestres.update', $trimestre) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected(old('annee_scolaire_id', $trimestre->annee_scolaire_id) == $annee->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $trimestre->nom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ old('date_debut', $trimestre->date_debut?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin', $trimestre->date_fin?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" @selected(old('statut', $trimestre->statut) === 'actif')>
                            Actif
                        </option>
                        <option value="ferme" @selected(old('statut', $trimestre->statut) === 'ferme')>
                            Fermé
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('trimestres.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>