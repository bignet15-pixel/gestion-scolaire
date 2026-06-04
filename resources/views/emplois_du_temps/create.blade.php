<x-app-layout>
{{-- Vue Blade : resources/views/emplois_du_temps/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter un créneau</h1>

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

            <form action="{{ route('emplois-du-temps.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <div class="form-group">
                    <label class="form-label">Affectation</label>
                    <select name="classe_matiere_user_id" class="form-control">
                        {{-- Affiche les affectations enseignant classe matiere. --}}
                        @foreach ($affectations as $affectation)
                            <option value="{{ $affectation->id }}" @selected(old('classe_matiere_user_id') == $affectation->id)>
                                {{ $affectation->classe->nom }}
                                —
                                {{ $affectation->matiere->nom }}
                                —
                                {{ $affectation->enseignant->name }}
                                —
                                {{ $affectation->classe->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jour</label>
                    <select name="jour" class="form-control">
                        {{-- Affiche les elements de ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']. --}}
                        @foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'] as $jour)
                            <option value="{{ $jour }}" @selected(old('jour') === $jour)>
                                {{ ucfirst($jour) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Heure début</label>
                    <input type="time" name="heure_debut" class="form-control" value="{{ old('heure_debut') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Heure fin</label>
                    <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Salle</label>
                    <input type="text" name="salle" class="form-control" value="{{ old('salle') }}" placeholder="Ex: Salle CM2">
                </div>

                <button type="submit" class="btn btn-primary">
                    Enregistrer
                </button>

                <a href="{{ route('emplois-du-temps.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>