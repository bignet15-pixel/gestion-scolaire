<x-app-layout>
{{-- Vue Blade : resources/views/affectations/create.blade.php --}}
    <div class="container">
        <div class="card">
            <h1>Ajouter une affectation</h1>

            <form action="{{ route('affectations.create') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-control">
                        @foreach ($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string) $selectedAnneeId === (string) $annee->id)>
                                {{ $annee->libelle }}{{ $annee->estFermee() ? ' — fermée' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Afficher
                    </button>
                </div>
            </form>

            @if ($selectedAnnee?->estFermee())
                <div class="alert alert-warning">
                    Cette année scolaire est fermée. Ses affectations sont disponibles uniquement dans l’historique.
                </div>
            @elseif ($classes->isEmpty())
                <div class="alert alert-warning">
                    Aucune classe n’est disponible pour cette année scolaire.
                </div>
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

            <form action="{{ route('affectations.store') }}" method="POST">
                {{-- Jeton de securite du formulaire. --}}
                @csrf

                <input type="hidden" name="annee_scolaire_id" value="{{ $selectedAnneeId }}">

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-control" required @disabled($classes->isEmpty() || $selectedAnnee?->estFermee())>
                        {{-- Remplit la liste des classes disponibles. --}}
                        @forelse ($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(old('classe_id') == $classe->id)>
                                {{ $classe->nom }} — {{ $classe->anneeScolaire->libelle }}
                            </option>
                        @empty
                            <option value="">Aucune classe disponible</option>
                        @endforelse
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Matière</label>
                    <select name="matiere_id" class="form-control">
                        {{-- Affiche les matieres dans le tableau. --}}
                        @foreach ($matieres as $matiere)
                            <option value="{{ $matiere->id }}" @selected(old('matiere_id') == $matiere->id)>
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
                        value="{{ old('coefficient', 1) }}"
                        required
                    >                   
                </div>

                <div class="form-group">
                    <label class="form-label">Enseignant</label>
                    <select name="user_id" class="form-control">
                        {{-- Affiche les enseignants dans le tableau. --}}
                        @foreach ($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected(old('user_id') == $enseignant->id)>
                                {{ $enseignant->name }} — {{ $enseignant->matricule }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date début</label>
                    <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="{{ old('date_debut', date('Y-m-d')) }}"
                        min="{{ $selectedAnnee?->date_debut?->format('Y-m-d') }}"
                        max="{{ $selectedAnnee?->date_fin?->format('Y-m-d') }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Date fin</label>
                    <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="{{ old('date_fin') }}"
                        min="{{ $selectedAnnee?->date_debut?->format('Y-m-d') }}"
                        max="{{ $selectedAnnee?->date_fin?->format('Y-m-d') }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" @selected(old('statut') === 'actif')>Actif</option>
                        <option value="termine" @selected(old('statut') === 'termine')>Terminé</option>
                        <option value="suspendu" @selected(old('statut') === 'suspendu')>Suspendu</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" @disabled($classes->isEmpty() || $selectedAnnee?->estFermee())>
                    Enregistrer
                </button>

                <a href="{{ route('affectations.index') }}" class="btn">
                    Retour
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
