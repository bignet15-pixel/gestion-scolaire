<x-app-layout>
{{-- Vue Blade : resources/views/affectations/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier une affectation</h1>

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

            <form action="{{ route('affectations.update', $affectation) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        {{-- Remplit la liste des classes disponibles. --}}
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(old('classe_id', $affectation->classe_id) == $classe->id)>
                                {{ $classe->nom }} — {{ $classe->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Matière</label>
                    <select name="matiere_id" class="form-control">
                        {{-- Affiche les matieres dans le tableau. --}}
                        @foreach ($matieres as $matiere)
                            <option value="{{ $matiere->id }}" @selected(old('matiere_id', $affectation->matiere_id) == $matiere->id)>
                                {{ $matiere->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Coefficient de la matière dans cette classe</label>

                    <input
                        type="number"
                        name="coefficient"
                        class="form-control"
                        min="0.1"
                        max="20"
                        step="0.01"
                        value="{{ old('coefficient', $affectation->coefficient) }}"
                        required
                    >                  
                </div>

                <div class="form-group">
                    <label class="form-label">Enseignant</label>
                    <select name="user_id" class="form-control">
                        {{-- Affiche les enseignants dans le tableau. --}}
                        @foreach ($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected(old('user_id', $affectation->user_id) == $enseignant->id)>
                                {{ $enseignant->name }} — {{ $enseignant->matricule }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ old('date_debut', $affectation->date_debut?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin', $affectation->date_fin?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" @selected(old('statut', $affectation->statut) === 'actif')>Actif</option>
                        <option value="termine" @selected(old('statut', $affectation->statut) === 'termine')>Terminé</option>
                        <option value="suspendu" @selected(old('statut', $affectation->statut) === 'suspendu')>Suspendu</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('affectations.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
