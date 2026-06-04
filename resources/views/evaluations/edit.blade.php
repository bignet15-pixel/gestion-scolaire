<x-app-layout>
{{-- Vue Blade : resources/views/evaluations/edit.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Modifier une évaluation</h1>

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

            <form action="{{ route('evaluations.update', $evaluation) }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf
                {{-- Methode HTTP du formulaire. --}}
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control">
                        {{-- Remplit la liste des classes disponibles. --}}
                        @foreach ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(old('classe_id', $evaluation->classe_id) == $classe->id)>
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
                            <option value="{{ $matiere->id }}" @selected(old('matiere_id', $evaluation->matiere_id) == $matiere->id)>
                                {{ $matiere->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Trimestre</label>
                    <select name="trimestre_id" class="form-control">
                        {{-- Remplit la liste des trimestres disponibles. --}}
                        @foreach ($trimestres as $trimestre)
                            <option value="{{ $trimestre->id }}" @selected(old('trimestre_id', $evaluation->trimestre_id) == $trimestre->id)>
                                {{ $trimestre->nom }} — {{ $trimestre->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" value="{{ old('nom', $evaluation->nom) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        {{-- Remplit la liste des types d evaluation. --}}
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(old('type', $evaluation->type) === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date évaluation</label>
                    <input type="date" name="date_evaluation" class="form-control" value="{{ old('date_evaluation', $evaluation->date_evaluation?->format('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Heure début</label>
                    <input type="time" name="heure_debut" class="form-control" value="{{ old('heure_debut', $evaluation->heure_debut?->format('H:i')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Heure fin</label>
                    <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin', $evaluation->heure_fin?->format('H:i')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Coefficient</label>
                    <input type="number" step="0.1" name="coefficient" class="form-control" value="{{ old('coefficient', $evaluation->coefficient) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Barème</label>
                    <input type="number" step="0.1" name="bareme" class="form-control" value="{{ old('bareme', $evaluation->bareme) }}">
                </div>

                <button type="submit" class="btn btn-primary">
                    Modifier
                </button>

                <a href="{{ route('evaluations.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>