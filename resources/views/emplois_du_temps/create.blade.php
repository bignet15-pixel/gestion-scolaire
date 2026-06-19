<x-app-layout>
{{-- Vue Blade : resources/views/emplois_du_temps/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter un créneau</h1>

            <form action="{{ route('emplois-du-temps.create') }}" method="GET" class="filter-form filter-form-large">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        {{-- Remplit la liste des annees scolaires. --}}
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }} — {{ $annee->statut }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>

                    <a href="{{ route('emplois-du-temps.create') }}" class="btn">
                        Réinitialiser
                    </a>
                </div>

                <input type="hidden" name="semaine" value="{{ $dateReference->format('Y-m-d') }}">
            </form>

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

            {{-- Condition : $affectations->isEmpty(). --}}
            @if ($affectations->isEmpty())
                <div class="alert alert-warning">
                    Aucune affectation active disponible pour cette année scolaire.
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

                <div class="form-group">
                    <label class="form-label">Semaine de programmation</label>
                    <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="{{ old('date_debut', $debutSemaine->format('Y-m-d')) }}"
                    >
                </div>

                <button type="submit" class="btn btn-primary" @disabled($affectations->isEmpty())>
                    Enregistrer
                </button>

                <a href="{{ route('emplois-du-temps.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
